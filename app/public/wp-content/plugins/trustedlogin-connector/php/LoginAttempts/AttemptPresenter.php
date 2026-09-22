<?php
/**
 * Login attempt presenter for converting SaaS data to React-consumable format.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\LoginAttempts;

/**
 * Maps a SaaS /login-attempts row into the React-consumable shape
 * with the agent-context heading and HTML-strip on user-influenceable
 * fields.
 *
 * The "Your" / "Sarah Chen's" heading branches are dormant when
 * the upstream response omits `access_key.creator`. They ship
 * ready for that field becoming available; today's production
 * rendering uses the generic "An earlier login attempt failed".
 */
final class AttemptPresenter {

	/** Recognised values of `code_action` from the spec. */
	const ACTION_RETRY            = 'retry';
	const ACTION_REISSUE          = 'reissue';
	const ACTION_CONTACT_CUSTOMER = 'contact_customer';

	const KNOWN_CODE_ACTIONS = array(
		self::ACTION_RETRY,
		self::ACTION_REISSUE,
		self::ACTION_CONTACT_CUSTOMER,
	);

	/** Defensive: SaaS already filters this, but strip if it ever leaks. */
	const SCRUB_FIELDS = array( 'detailed_reason' );

	/**
	 * Current user ID for heading context.
	 *
	 * @var int
	 */
	private $current_user_id;

	/**
	 * Initialise the presenter with the current user ID.
	 *
	 * @param int $current_user_id The agent driving the request — used
	 *                             to pick the "Your" heading branch
	 *                             when their id matches the access-key
	 *                             creator.
	 */
	public function __construct( int $current_user_id ) {
		$this->current_user_id = $current_user_id;
	}

	/**
	 * Map a SaaS row into the React-consumable shape. Untrusted
	 * fields are run through wp_strip_all_tags / esc_url_raw before
	 * leaving the function. Internal-only fields named in
	 * SCRUB_FIELDS are removed up front.
	 *
	 * @param array $row Raw row from /api/v1/logs/login-attempts/*.
	 *
	 * @return array{
	 *     id:string,
	 *     code:string,
	 *     code_message:string,
	 *     code_action:string,
	 *     client_site_url:string,
	 *     client_ip:string,
	 *     attempted_at:string,
	 *     heading:string,
	 *     creator:?array{id:int,name:string}
	 * }
	 */
	public function present( array $row ): array {
		$row = $this->scrub( $row );

		$creator    = isset( $row['access_key']['creator'] ) ? $row['access_key']['creator'] : null;
		$creator_id = isset( $creator['id'] ) ? (int) $creator['id'] : 0;
		$creator_nm = isset( $creator['name'] ) ? (string) $creator['name'] : '';

		return array(
			'id'              => isset( $row['id'] ) ? (string) $row['id'] : '',
			'code'            => isset( $row['code'] ) ? (string) $row['code'] : '',
			'code_message'    => isset( $row['code_message'] ) ? wp_strip_all_tags( (string) $row['code_message'] ) : '',
			'code_action'     => $this->normalize_action( isset( $row['code_action'] ) ? (string) $row['code_action'] : '' ),
			'client_site_url' => isset( $row['client_site_url'] ) ? esc_url_raw( (string) $row['client_site_url'] ) : '',
			'client_ip'       => $this->client_ip_for_role( $row ),
			'attempted_at'    => isset( $row['attempted_at'] ) ? (string) $row['attempted_at'] : '',
			'heading'         => $this->build_heading( $creator_id, $creator_nm ),
			'creator'         => $creator_id > 0
				? array(
					'id'   => $creator_id,
					'name' => wp_strip_all_tags( $creator_nm ),
				)
				: null,
		);
	}

	/**
	 * Scrub internal-only fields BEFORE presenting. Defense-in-depth
	 * even though SaaS already filters them.
	 *
	 * @param array $row The row to scrub.
	 *
	 * @return array
	 */
	private function scrub( array $row ): array {
		foreach ( self::SCRUB_FIELDS as $f ) {
			unset( $row[ $f ] );
		}

		return $row;
	}

	/**
	 * Pick the heading for the detail panel. Three branches: the
	 * caller is the access-key creator, a different identified agent
	 * is the creator, or the creator is unknown.
	 *
	 * @param int    $creator_id   Access-key creator's user id.
	 * @param string $creator_name Display name; HTML stripped on output.
	 *
	 * @return string Already-translated heading string.
	 */
	private function build_heading( int $creator_id, string $creator_name ): string {
		if ( $creator_id > 0 && $creator_id === $this->current_user_id ) {
			return __( 'Your earlier login attempt failed', 'trustedlogin-connector' );
		}

		if ( $creator_id > 0 && '' !== $creator_name ) {
			return sprintf(
				/* translators: %s is the agent who issued the original access key */
				__( '%s\'s earlier login attempt failed', 'trustedlogin-connector' ),
				wp_strip_all_tags( $creator_name )
			);
		}

		return __( 'An earlier login attempt failed', 'trustedlogin-connector' );
	}

	/**
	 * Map the raw `code_action` string to a known value. Unknown
	 * actions fall back to ACTION_CONTACT_CUSTOMER so the React app
	 * always renders a sensible CTA.
	 *
	 * @param string $action The action code to normalize.
	 *
	 * @return string One of self::KNOWN_CODE_ACTIONS.
	 */
	private function normalize_action( string $action ): string {
		return in_array( $action, self::KNOWN_CODE_ACTIONS, true )
			? $action
			: self::ACTION_CONTACT_CUSTOMER;
	}

	/**
	 * Read the client IP that should be rendered for this agent.
	 *
	 * SaaS returns the redacted IP only; raw IPs aren't surfaced to
	 * the Connector and there's no role-based view today (the
	 * upstream auth is team-scoped, not per-user).
	 *
	 * @param array $row The attempt row data.
	 *
	 * @return string
	 */
	private function client_ip_for_role( array $row ): string {
		return isset( $row['client_ip_redacted'] ) ? (string) $row['client_ip_redacted'] : '';
	}
}
