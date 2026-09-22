<?php
/**
 * Base endpoint class for REST API routes.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

/**
 * Base class for all endpoints to extend.
 */
abstract class Endpoint {

	/**
	 * Error code for public key sucess.
	 */
	const PUBLIC_KEY_SUCCESS_STATUS = 200;

	/**
	 * Error code for public key error.
	 */
	const PUBLIC_KEY_ERROR_STATUS = 501;

	/**
	 * Namespace for all routes
	 */
	const NAMESPACE = 'trustedlogin/v1';

	/**
	 * Register endpoint.
	 *
	 * @param bool $editable Defaults to true. If false, the endpoint will not be updateable.
	 * @param bool $readable Defaults to true. If false, the endpoint will not be readable.
	 */
	public function register( $editable = true, $readable = true ) {

		if ( $editable ) {
			register_rest_route(
				self::NAMESPACE,
				$this->route(),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'authorize' ),
					'args'                => $this->updateArgs(),
				)
			);
		}
		if ( $readable ) {
			register_rest_route(
				self::NAMESPACE,
				$this->route(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( $this, 'authorize' ),
					'args'                => $this->getArgs(),
				)
			);
		}
	}

	/**
	 * Get the route URI
	 *
	 * @return string
	 */
	abstract protected function route();

	/**
	 * Get the args for GET requests
	 *
	 * @return array
	 */
	protected function getArgs() {
		return array();
	}

	/**
	 * Get the args for POST requests
	 *
	 * @return array
	 */
	protected function updateArgs() {
		return array();
	}



	/**
	 * Callback for GET requests
	 *
	 * @param \WP_REST_Request $request Parameter.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	abstract public function get( \WP_REST_Request $request );

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- Default body throws unconditionally; subclasses override with a real return.
	/**
	 * Callback for POST requests
	 *
	 * @param \WP_REST_Request $request Parameter.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 *
	 * @throws \LogicException When a subclass that wires POST → update() forgets to override this method.
	 */
	public function update( \WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WP REST callback signature.
		// Subclasses using the parent register() flow that wires POST →
		// update() must override this. The previous default returned 501
		// silently — that masked missing-override regressions until a
		// production caller hit them. Throwing makes the omission fatal
		// at registration-time of any test that exercises the route.
		throw new \LogicException( sprintf( 'Endpoint subclass %s must override update().', static::class ) );
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Permission callback for get and update.
	 *
	 * @param \WP_REST_Request $request Parameter.
	 * @return bool|\WP_Error
	 */
	public function authorize( \WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WP REST permission_callback signature.
		$capability = is_multisite() ? 'delete_sites' : 'manage_options';
		return current_user_can( $capability );
	}
}
