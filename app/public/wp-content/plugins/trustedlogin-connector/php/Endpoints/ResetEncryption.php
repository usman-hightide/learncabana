<?php
/**
 * Encryption key reset endpoint.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Utils;
use WP_REST_Response;
use WP_REST_Request;

/**
 * Endpoint to reset encryption keys.
 */
class ResetEncryption extends Settings {

	use Logger;

	/**
	 * Body-param token the caller must submit verbatim. Acts as a
	 * second intent interlock on top of the REST nonce.
	 *
	 * @since 2.0.0
	 */
	const CONFIRMATION_TOKEN = 'destroy-and-regenerate-keys';

	/**
	 * Return the REST route URI for encryption reset.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'settings/encryption/reset';
	}

	/**
	 * Return the accepted arguments for POST requests to this route.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {
		return array(
			'confirm' => array(
				'type'        => 'string',
				'required'    => true,
				'description' => 'Must equal "' . self::CONFIRMATION_TOKEN . '" to authorize the reset.',
			),
		);
	}

	/**
	 * Reset encryption keys with POST request.
	 *
	 * @param WP_REST_Request $request The REST request requiring the confirmation token. Must include the confirmation token.
	 * @return WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {
		$confirm = (string) $request->get_param( 'confirm' );
		if ( ! hash_equals( self::CONFIRMATION_TOKEN, $confirm ) ) {
			return new WP_REST_Response(
				array(
					'error'   => 'confirmation_required',
					'message' => sprintf(
						/* translators: %s is the literal confirmation token. */
						__( 'Reset must be confirmed by submitting `confirm=%s`.', 'trustedlogin-connector' ),
						self::CONFIRMATION_TOKEN
					),
				),
				400
			);
		}

		// Audit-log every successful reset with the retired-key
		// fingerprint + actor IP/UA, stamped before the destructive
		// op so the audit row is intact even if deleteKeys() throws.
		$encryption          = \trustedlogin_connector()->getEncryption();
		$retired_fingerprint = '';
		try {
			$retired_public      = $encryption->getPublicKey();
			$retired_fingerprint = is_string( $retired_public ) ? substr( hash( 'sha256', $retired_public ), 0, 16 ) : '';
		} catch ( \Throwable $e ) {
			unset( $e );
		}

		$this->log(
			sprintf(
				'Encryption reset: retired_fingerprint=%s by user_id=%d',
				$retired_fingerprint,
				get_current_user_id()
			),
			__METHOD__,
			'warning',
			array(
				'retired_fingerprint' => $retired_fingerprint,
				'user_id'             => get_current_user_id(),
				'actor_ip'            => Utils::get_ip(),
				'actor_ua'            => Utils::get_user_agent(),
			)
		);

		// Delete keys.
		$encryption->deleteKeys();
		// Makes new keys.
		$encryption->getPublicKey();

		// Email the site administrator with a paper trail of who
		// triggered the rotation and when. Rate-limited and
		// filter-suppressible by the same hook as the tamper alert.
		$encryption->notifyAdminOfReset();

		return new WP_REST_Response( array(), 204 );  // Set the status code to 204 No Content.
	}
}
