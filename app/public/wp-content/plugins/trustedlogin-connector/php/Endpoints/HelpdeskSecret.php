<?php
/**
 * On-demand reveal endpoint for the per-team per-helpdesk webhook-signing secret.
 *
 * Exists so the Integration-configure panel can populate its "Secret key"
 * input without writing the raw value into window.tlVendor at page load.
 * See SettingsApi::toResponseData() for the bootstrap-side scrubbing and
 * the security rationale (T1-6 sibling — XSS-exfil surface narrowing).
 *
 * Gated by manage_options + the standard WP REST nonce. An admin session
 * is the only principal that legitimately needs this; low-priv users
 * never reach the Integration page at all, and REST callers from outside
 * the admin SPA don't have a use case for it.
 *
 * @package TrustedLogin\Vendor\Endpoints
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\TeamSettings;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Traits\SlidingWindowRateLimit;

/**
 * HelpdeskSecret endpoint for REST API.
 *
 * @since 2.0.0
 */
class HelpdeskSecret extends Endpoint {

	use Logger;
	use SlidingWindowRateLimit;

	/**
	 * Per-user sliding-window cap on reveals.
	 *
	 * @since 2.0.0
	 */
	const REVEAL_RATE_LIMIT = 20;

	/**
	 * Window (seconds) over which REVEAL_RATE_LIMIT reveals are counted.
	 *
	 * @since 2.0.0
	 */
	const REVEAL_RATE_WINDOW_SECONDS = 60;

	/**
	 * Get the helpdesk secret route.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'helpdesks/(?P<helpdesk>[a-z0-9_-]+)/secret';
	}

	/**
	 * Get REST endpoint args for the get method.
	 *
	 * @inheritdoc
	 */
	protected function getArgs() {
		return array(
			'helpdesk'   => array(
				'required' => true,
				'type'     => 'string',
			),
			'account_id' => array(
				'required' => true,
				'type'     => 'integer',
			),
		);
	}

	/**
	 * Reveal the helpdesk webhook secret.
	 *
	 * @inheritdoc
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get( \WP_REST_Request $request ) {
		$helpdesk   = sanitize_key( (string) $request->get_param( 'helpdesk' ) );
		$account_id = absint( $request->get_param( 'account_id' ) );

		if ( '' === $helpdesk || 0 === $account_id ) {
			return new \WP_REST_Response(
				array( 'error' => 'invalid_param' ),
				400
			);
		}

		$user_id           = get_current_user_id();
		$actor_ip          = \TrustedLogin\Vendor\Utils::get_ip();
		$actor_ua          = \TrustedLogin\Vendor\Utils::get_user_agent();
		$bypass_rate_limit = defined( 'TL_E2E_DISABLE_RATE_LIMITS' )
			&& constant( 'TL_E2E_DISABLE_RATE_LIMITS' )
			&& function_exists( 'wp_get_environment_type' )
			&& in_array( wp_get_environment_type(), array( 'local', 'development' ), true );

		if ( ! $bypass_rate_limit && $this->enforce_rate_limit( $this->bucket_key( $user_id ), self::REVEAL_RATE_LIMIT, self::REVEAL_RATE_WINDOW_SECONDS ) ) {
			// Rate-limit hits are the highest-value forensic signal —.
			// always include actor IP + UA (and verify trusted-proxies.
			// handling — Utils::get_ip() respects the filter) so a.
			// post-incident pass can correlate burst patterns even if.
			// the user_id was hijacked via XSS.
			$this->log(
				sprintf( 'HelpdeskSecret reveal rate-limited for user_id=%d', $user_id ),
				__METHOD__,
				'warning',
				array(
					'user_id'    => $user_id,
					'helpdesk'   => $helpdesk,
					'account_id' => $account_id,
					'actor_ip'   => $actor_ip,
					'actor_ua'   => $actor_ua,
				)
			);
			$response = new \WP_REST_Response(
				array( 'error' => 'rate_limited' ),
				429
			);
			$response->header( 'Retry-After', (string) self::REVEAL_RATE_WINDOW_SECONDS );
			return $response;
		}

		try {
			$team = SettingsApi::fromSaved()->getByAccountId( $account_id );
		} catch ( \Exception $e ) {
			unset( $e ); // Lookup failure is sufficient signal; detail not surfaced to caller.
			return new \WP_REST_Response(
				array( 'error' => 'team_not_found' ),
				404
			);
		}

		// Defense-in-depth: only return a secret for a helpdesk slug
		// the team currently carries in its active list. Inactive
		// providers stay unrevealable over REST.
		$active = $team->getHelpdesks();
		if ( ! in_array( $helpdesk, $active, true ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'helpdesk_not_active' ),
				404
			);
		}

		// TeamSettings stores sub-maps as either arrays or stdClass.
		// depending on which write path landed them: the matrix-panel.
		// save writes arrays, the older `json_decode($json)` path in.
		// SettingsApi uses the object form. Normalize through `(array)`.
		// so both shapes produce the same field access downstream.
		$helpdesk_settings = (array) $team->get( TeamSettings::HELPDESK_SETTINGS );
		$entry             = $helpdesk_settings[ $helpdesk ] ?? null;

		if ( null === $entry ) {
			return new \WP_REST_Response(
				array( 'error' => 'helpdesk_not_configured' ),
				404
			);
		}

		$entry  = (array) $entry;
		$secret = isset( $entry['secret'] ) ? (string) $entry['secret'] : '';

		// Audit-log every successful reveal so a post-hoc forensic pass.
		// can spot exfil attempts even if rate-limit bursts stay under.
		// the cap. Secret VALUE is never logged — only the principal +.
		// target of the reveal.
		$this->log(
			sprintf( 'HelpdeskSecret revealed for user_id=%d, helpdesk=%s, account_id=%d', $user_id, $helpdesk, $account_id ),
			__METHOD__,
			'info',
			array(
				'user_id'    => $user_id,
				'helpdesk'   => $helpdesk,
				'account_id' => $account_id,
				'actor_ip'   => $actor_ip,
				'actor_ua'   => $actor_ua,
			)
		);

		$response = new \WP_REST_Response(
			array( 'secret' => $secret ),
			200
		);

		// Never cacheable — the consumer might be one of many admin.
		// tabs and a stale Cloudflare / CDN cache would be a data leak.
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Get per-user transient key for rate limiting.
	 *
	 * Scoped per user_id so one admin's activity can't rate-limit another admin out of the panel.
	 *
	 * @since 2.0.0
	 *
	 * @param int $user_id The user ID for the rate limit bucket.
	 *
	 * @return string
	 */
	private function bucket_key( $user_id ) {
		return 'tl_helpdesk_secret_reveals_' . (int) $user_id;
	}
}
