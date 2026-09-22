<?php
/**
 * REST endpoint for the Permissions matrix UI.
 *
 * GET  /trustedlogin/v1/permissions returns { roles, capabilities, matrix }
 * POST /trustedlogin/v1/permissions applies a single cell flip via
 *      Capabilities::set_role_cap and returns the refreshed matrix.
 *
 * Gated by the base Endpoint::authorize() which checks `manage_options`
 * (or `delete_sites` on multisite). This is intentionally strict: only
 * users who can already change every cap are allowed to redistribute TL
 * caps to other roles, so the endpoint is never an escalation primitive.
 *
 * @package TrustedLogin\Vendor\Endpoints
 * @since 2.0.0
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\Capabilities;
use TrustedLogin\Vendor\Traits\Logger;
use TrustedLogin\Vendor\Utils;

/**
 * Permissions endpoint for the REST API.
 *
 * @since 2.0.0
 */
class Permissions extends Endpoint {

	use Logger;

	/**
	 * Maps Capabilities::set_role_cap() error codes to HTTP statuses.
	 *
	 * Keeping this as a constant rather than inline so a future cap
	 * error code (e.g. `role_read_only` for integrator-locked roles)
	 * has a clear place to slot in.
	 */
	const ERROR_STATUSES = array(
		'admin_immutable' => 403,
		'unknown_cap'     => 400,
		'unknown_role'    => 400,
	);

	/**
	 * Get the permissions route.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'permissions';
	}

	/**
	 * Get the roles + capabilities + matrix payload.
	 *
	 * Returns the roles + capabilities + matrix payload the UI needs to
	 * render the grid in one round-trip.
	 *
	 * Role display names pass through translate_user_role() so l10n
	 * picks up WP core's translations for built-in roles.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {

		// One query per request; WP caches count_users() internally.
		$user_counts = count_users();
		$avail_roles = isset( $user_counts['avail_roles'] ) && is_array( $user_counts['avail_roles'] )
			? $user_counts['avail_roles']
			: array();

		$roles = array();
		foreach ( wp_roles()->roles as $slug => $data ) {
			$display_name = isset( $data['name'] ) ? (string) $data['name'] : (string) $slug;
			$role_obj     = get_role( $slug );

			// Low-trust = role lacks edit_others_posts. UI gates a
			// type-to-confirm modal on this flag.
			$low_trust = $role_obj
				? ! $role_obj->has_cap( 'edit_others_posts' )
				: true;

			$user_count = isset( $avail_roles[ $slug ] ) ? (int) $avail_roles[ $slug ] : 0;

			$roles[] = array(
				'slug'       => $slug,
				'name'       => translate_user_role( $display_name ),
				'immutable'  => Capabilities::IMMUTABLE_ROLE === $slug,
				'low_trust'  => $low_trust,
				'user_count' => $user_count,
				// Pre-built filtered users.php URL so the React
				// component doesn't need to know admin_url() shape.
				// `list_users` cap-gated on the wp-admin side, so a
				// link rendered for a user without that cap will hit
				// a permissions screen — the matrix only renders for
				// users with TL caps + manage_options anyway, so
				// this is a no-op in practice.
				'users_url'  => $user_count > 0
					? esc_url_raw( admin_url( 'users.php?role=' . rawurlencode( $slug ) ) )
					: '',
			);
		}

		$capabilities = array();
		foreach ( Capabilities::labels() as $cap => $meta ) {
			$capabilities[] = array(
				'slug'        => $cap,
				'label'       => $meta['label'],
				'description' => $meta['description'],
			);
		}

		return new \WP_REST_Response(
			array(
				'roles'        => $roles,
				'capabilities' => $capabilities,
				'matrix'       => Capabilities::matrix(),
			),
			200
		);
	}

	/**
	 * Apply a single cell flip to permissions matrix.
	 *
	 * Body: `{ role, cap, granted }`.
	 *
	 * Returns the refreshed matrix on success so the client can
	 * reconcile optimistic state cheaply. Error shape mirrors the
	 * Activity endpoint's: `{ error: <code>, message: <localized> }`.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ) {

		$role    = sanitize_key( (string) $request->get_param( 'role' ) );
		$cap     = sanitize_key( (string) $request->get_param( 'cap' ) );
		$granted = (bool) $request->get_param( 'granted' );

		$result = Capabilities::set_role_cap( $role, $cap, $granted );

		if ( is_wp_error( $result ) ) {
			$code   = $result->get_error_code();
			$status = isset( self::ERROR_STATUSES[ $code ] ) ? self::ERROR_STATUSES[ $code ] : 400;
			return new \WP_REST_Response(
				array(
					'error'   => $code,
					'message' => $result->get_error_message(),
				),
				$status
			);
		}

		// Audit-log every successful cap flip with actor IP + UA so
		// a post-hoc forensic pass can attribute the change without
		// cross-referencing wp-admin access logs.
		$this->log(
			sprintf(
				'Permissions update: role=%s cap=%s granted=%s by user_id=%d',
				$role,
				$cap,
				$granted ? 'true' : 'false',
				get_current_user_id()
			),
			__METHOD__,
			'info',
			array(
				'role'     => $role,
				'cap'      => $cap,
				'granted'  => $granted,
				'user_id'  => get_current_user_id(),
				'actor_ip' => Utils::get_ip(),
				'actor_ua' => Utils::get_user_agent(),
			)
		);

		return new \WP_REST_Response(
			array( 'matrix' => Capabilities::matrix() ),
			200
		);
	}

	/**
	 * Get REST endpoint args for the update method.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {
		return array(
			'role'    => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
			'cap'     => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
			'granted' => array(
				'type'              => 'boolean',
				'required'          => true,
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
