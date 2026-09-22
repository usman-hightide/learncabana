<?php
/**
 * Settings implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Endpoints;

use TrustedLogin\Vendor\SettingsApi;
use TrustedLogin\Vendor\Status\IsTeamConnected;
use TrustedLogin\Vendor\TeamSettings;
/**
 * Settings implementation.
 */
class Settings extends Endpoint {

	/**
	 * Wire-only team fields the React UI sends but the server doesn't
	 * store. Stripped before the strict-key check in update() so the
	 * full GET-edit-POST round-trip doesn't 400 on legitimate keys.
	 */
	const WIRE_ONLY_TEAM_KEYS = array(
		'id',
		'public_key_fingerprint',
	);

	/**
	 * Return the REST route URI for settings.
	 *
	 * @inheritdoc
	 */
	protected function route() {
		return 'settings';
	}

	/**
	 * Return the accepted arguments for POST requests to this route.
	 *
	 * @inheritdoc
	 */
	protected function updateArgs() {
		return array(
			'teams' => array(
				'type'     => 'array',
				'required' => true,
			),
		);
	}

	/**
	 * Handler for requests to GET settings
	 *
	 * @param \WP_REST_Request $request Parameter.
	 * @return \WP_REST_Response
	 */
	public function get( \WP_REST_Request $request ) {
		return $this->createResponse(
			SettingsApi::fromSaved()
		);
	}

	/**
	 * Handler for requests to POST settings updates.
	 *
	 * @param \WP_REST_Request $request Parameter.
	 * @return \WP_REST_Response
	 */
	public function update( \WP_REST_Request $request ) {
		$teams = $request->get_param( 'teams' );

		// Validate every submitted team BEFORE the destructive reset.
		// + rebuild loop. A POST with a malformed team (account_id.
		// missing/blank, unknown top-level key) must not be allowed.
		// to wipe the existing collection.
		if ( ! empty( $teams ) ) {
			foreach ( $teams as $team ) {
				$team       = (array) $team;
				$account_id = isset( $team['account_id'] ) ? trim( (string) $team['account_id'] ) : '';
				if ( '' === $account_id ) {
					return new \WP_REST_Response(
						array(
							'error'   => 'invalid_param',
							'message' => __( 'account_id is required for every submitted team.', 'trustedlogin-connector' ),
						),
						400
					);
				}
				foreach ( array_keys( $team ) as $key ) {
					$key = (string) $key;
					if ( in_array( $key, self::WIRE_ONLY_TEAM_KEYS, true ) ) {
						continue;
					}
					if ( ! TeamSettings::validKey( $key ) ) {
						// Truncate the reflected key + wrap in backticks so a.
						// hostile admin tool can't probe the sanitizer with a.
						// long payload.
						$safe_key = mb_substr( sanitize_text_field( $key ), 0, 32 );
						return new \WP_REST_Response(
							array(
								'error'   => 'unknown_param',
								'message' => sprintf(
									/* translators: %s is the unknown field name. */
									__( 'Unknown team field: `%s`', 'trustedlogin-connector' ),
									$safe_key
								),
							),
							400
						);
					}
				}
			}
		}

		// Existing-team lookup base for the merge. fromSaved() now.
		// sanitizes on read (drops junk fields, decodes assoc) so the.
		// merge sees clean data.
		$existing_api = SettingsApi::fromSaved();
		$settings_api = SettingsApi::fromSaved()->reset();

		if ( ! empty( $teams ) ) {
			foreach ( $teams as $team ) {
				$teamSetting = $existing_api->hydrateSubmittedTeam( (array) $team );

				// Force re-verification by resetting the connection.
				// status — verifyAccountId() consults this and skips.
				// when already-checked.
				$teamSetting->set( IsTeamConnected::KEY, IsTeamConnected::VALUE_NOT_CHECKED );

				$this->verifyAccountId( $teamSetting );

				$settings_api->addSetting( $teamSetting );
			}
		}

		$settings_api->save();
		return $this->createResponse(
			// Get from saved so generated secret/ url is returned.
			SettingsApi::fromSaved()
		);
	}

	/**
	 * Verify that the account id is valid
	 *
	 * @param TeamSettings $team Parameter.
	 * @return void|bool
	 */
	public function verifyAccountId( TeamSettings $team ) {
		// Log the team's current connection status BEFORE checking.
		\trustedlogin_connector()->log(
			'Checking team connection status',
			__METHOD__,
			'debug',
			array(
				'account_id'      => $team->get( 'account_id' ),
				'connected_value' => $team->get( IsTeamConnected::KEY ),
				'status_value'    => $team->get( IsTeamConnected::STATUS_KEY ),
			)
		);

		if ( ! IsTeamConnected::needToCheck( $team ) ) {
			\trustedlogin_connector()->log(
				'Skipping account verification - not needed',
				__METHOD__,
				'debug',
				array(
					'account_id' => $team->get( 'account_id' ),
					'reason'     => 'Team already has connection status: ' . $team->get( IsTeamConnected::KEY ),
				)
			);
			return;
		}

		$team_account_id = $team->get( 'account_id' );

		\trustedlogin_connector()->log(
			'Starting team account verification',
			__METHOD__,
			'debug',
			array(
				'raw_account_id' => $team_account_id,
			)
		);

		// Validate if the team account ID is an integer. If so, convert to int from string.
		$team_account_id = filter_var( $team_account_id, FILTER_VALIDATE_INT );

		// Validate if the POSTed account_id is an integer.
		if ( ! $team_account_id ) {
			\trustedlogin_connector()->log(
				'Invalid account ID - not an integer',
				__METHOD__,
				'error',
				array(
					'raw_account_id'      => $team->get( 'account_id' ),
					'filtered_account_id' => $team_account_id,
				)
			);

			$team->set(
				IsTeamConnected::KEY,
				false
			);
			$team->set( IsTeamConnected::STATUS_KEY, 'error' );

			return false;
		}

		\trustedlogin_connector()->log(
			'Calling API to verify account',
			__METHOD__,
			'debug',
			array(
				'account_id' => $team_account_id,
			)
		);

		$r = \trustedlogin_connector()->getApiHandler(
			$team_account_id,
			'',
			$team
		)->verify(
			$team_account_id
		);

		if ( ! is_wp_error( $r ) ) {
			\trustedlogin_connector()->log(
				'Team account verified successfully',
				__METHOD__,
				'info',
				array(
					'account_id' => $team_account_id,
					'status'     => $r->status ?? 'unknown',
					'name'       => $r->name ?? 'unknown',
				)
			);

			$team = IsTeamConnected::setConnected( $team );
			$team->set( IsTeamConnected::STATUS_KEY, $r->status ?? '' );
			// Only overwrite `name` when the SaaS verify response actually.
			// carries one. `name` is intentionally NOT in.
			// SettingsApi::SERVER_MANAGED_FIELDS — leaving it in the.
			// merge base means a previously verified team name survives a.
			// transient SaaS failure. The set() below is therefore the.
			// single authoritative-write point: SaaS-supplied name wins,.
			// otherwise the merge base's existing name is preserved.
			if ( isset( $r->name ) && '' !== (string) $r->name ) {
				$team->set( 'name', (string) $r->name );
			}
		} else {
			\trustedlogin_connector()->log(
				'Team account verification failed',
				__METHOD__,
				'error',
				array(
					'account_id'    => $team_account_id,
					'error_code'    => $r->get_error_code(),
					'error_message' => $r->get_error_message(),
				)
			);

			$team->set(
				IsTeamConnected::KEY,
				false
			);
			$team->set( IsTeamConnected::STATUS_KEY, 'error' );
			$team->set( 'message', $r->get_error_message() );
		}

		return ! is_wp_error( $r );
	}

	/**
	 * Build a REST response from a SettingsApi instance.
	 *
	 * @param SettingsApi $settingsApi The settings API instance to serialise.
	 * @return \WP_REST_Response
	 */
	protected function createResponse( SettingsApi $settingsApi ) {
		return rest_ensure_response(
			$settingsApi->toResponseData()
		);
	}
}
