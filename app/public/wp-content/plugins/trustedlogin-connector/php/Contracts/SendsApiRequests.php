<?php
/**
 * Outbound API-request contract for the TrustedLogin SaaS client.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Contracts;

use WP_Error;

/**
 * Implementations send a single REST request to the TrustedLogin SaaS
 * and return the wp_remote_request-style response. ApiSend is the
 * production implementation; tests double this interface.
 */
interface SendsApiRequests {


	/**
	 * API Function: send the API request
	 *
	 * @since 0.4.0
	 *
	 * @param string $url The complete url for the REST API request.
	 * @param mixed  $data Data to send as JSON-encoded request body.
	 * @param string $method HTTP request method (must be 'POST', 'PUT', 'GET', or 'DELETE').
	 * @param array  $additional_headers Any additional headers to send in request (required for auth/etc).
	 *
	 * @return array|false|WP_Error - wp_remote_post response, false if invalid HTTP method, WP_Error if request errors
	 */
	public function send( $url, $data, $method, $additional_headers );
}
