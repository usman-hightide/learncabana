<?php
/**
 * Signature Key endpoint.
 *
 * @package TrustedLogin\Vendor\Endpoints
 */

namespace TrustedLogin\Vendor\Endpoints;

/**
 * Class SignatureKey
 *
 * @package TrustedLogin\Vendor\Endpoints
 */
class SignatureKey extends Endpoint {


	/**
	 * Get the route URI
	 *
	 * @return string
	 */
	protected function route() {
		return 'signature_key';
	}

	/**
	 * Callback for GET requests
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {
		$sign_public_key = \trustedlogin_connector()->getSignatureKey();

		$response = new \WP_REST_Response();

		if ( ! is_wp_error( $sign_public_key ) ) {
			$data = array( 'signatureKey' => $sign_public_key );
			$response->set_data( $data );
			$response->set_status( self::PUBLIC_KEY_SUCCESS_STATUS );
		} else {
			$response->set_status( self::PUBLIC_KEY_ERROR_STATUS );
		}

		return $response;
	}

	/**
	 * Returns true for permission callbacks for get and update.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return true
	 */
	public function authorize( \WP_REST_Request $request ) {
		return true;
	}
}
