<?php
/**
 * PublicKey implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Encryption;

/**
 * Public key endpoint for retrieving the server's public key.
 */
class PublicKey extends Endpoint {

	/**
	 * REST API route endpoint.
	 *
	 * @return string The route path.
	 */
	protected function route() {
		return 'public_key';
	}

	/**
	 * Retrieve the server's public key.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The response object.
	 */
	public function get( \WP_REST_Request $request ) {
		$public_key = \trustedlogin_connector()->getPublicKey();

		$response = new \WP_REST_Response();

		if ( ! is_wp_error( $public_key ) ) {
			$data = array(
				'publicKey' => $public_key,
			);
			$response->set_data( $data );
			$response->set_status( self::PUBLIC_KEY_SUCCESS_STATUS );
		} else {
			$response->set_status( self::PUBLIC_KEY_ERROR_STATUS );
		}

		return $response;
	}

	/**
	 * Check authorization for public key endpoint.
	 *
	 * The public key is always accessible.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True to allow access.
	 */
	public function authorize( \WP_REST_Request $request ) {
		return true;
	}
}
