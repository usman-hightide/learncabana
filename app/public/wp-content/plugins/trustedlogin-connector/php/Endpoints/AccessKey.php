<?php
/**
 * AccessKey implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;

use TrustedLogin\Vendor\AccessKeyLogin;
use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\Traits\Logger;

/**
 * AccessKey endpoint for REST API.
 *
 * @since 2.0.0
 */
class AccessKey extends Endpoint {

	use Logger;

	/**
	 * Get the access key route.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'access_key';
	}

	/**
	 * Get access key endpoint handler.
	 *
	 * @inheritdoc
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function get( \WP_REST_Request $request ) {
		// This should never happen, but just in case!
		return new \WP_Error( 'method_not_allowed', esc_html__( 'Method not allowed.', 'trustedlogin-connector' ), array( 'status' => 405 ) );
	}

	/**
	 * Authorize access key request.
	 *
	 * @inheritdoc
	 * @param \WP_REST_Request $request The REST request object.
	 */
	public function authorize( \WP_REST_Request $request ) {
		// Requires ACCESS_KEY_LOGIN; the nonce check follows.
		if ( ! Capabilities::current_user_can( Capabilities::ACCESS_KEY_LOGIN ) ) {
			return new \WP_Error(
				'forbidden',
				esc_html__( 'You do not have permission to log in to client sites.', 'trustedlogin-connector' ),
				array( 'status' => 403 )
			);
		}

		// Valid nonce?
		$valid = wp_verify_nonce(
			sanitize_text_field( wp_unslash( $request->get_param( AccessKeyLogin::NONCE_NAME ) ) ),
			AccessKeyLogin::NONCE_ACTION
		);

		if ( ! $valid ) {
			$this->log( 'Nonce is invalid; could be insecure request. Refresh the page and try again.', __METHOD__, 'error' );
			return new \WP_Error( 'bad_nonce', esc_html__( 'The nonce was not set for the request.', 'trustedlogin-connector' ) );
		}
		return true;
	}

	/**
	 * Get REST endpoint args for the update method.
	 *
	 * @return array
	 */
	public function updateArgs() {
		return array(
			AccessKeyLogin::ACCESS_KEY_INPUT_NAME => array(
				'required' => true,
				'type'     => 'string',
			),
			AccessKeyLogin::ACCOUNT_ID_INPUT_NAME => array(
				'required' => true,
				'type'     => 'string',
			),
			AccessKeyLogin::NONCE_NAME            => array(
				'required' => true,
				'type'     => 'string',
			),
		);
	}

	/**
	 * Update/process access key request.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function update( \WP_REST_Request $request ) {
		$handler = new AccessKeyLogin();
		// $trusted=true: REST authorize() already covered the cap + nonce.
		$parts = $handler->handle(
			array(
				AccessKeyLogin::ACCESS_KEY_INPUT_NAME =>
					$request->get_param( AccessKeyLogin::ACCESS_KEY_INPUT_NAME ),
				AccessKeyLogin::ACCOUNT_ID_INPUT_NAME =>
					$request->get_param( AccessKeyLogin::ACCOUNT_ID_INPUT_NAME ),
			),
			true
		);
		if ( is_wp_error( $parts ) ) {
			return $parts;
		}
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $parts,
			),
			200
		);
	}
}
