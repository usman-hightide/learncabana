<?php
/**
 * Team connection status checker.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Status;

use TrustedLogin\Vendor\TeamSettings;

/**
 * Checks if a team is connected to TrustedLogin.
 */
class IsTeamConnected {

	const KEY = 'connected';

	const STATUS_KEY = 'status';

	const VALUE_NOT_CHECKED = 'not_checked';

	const VALUE_CHECKED_NOT_CONNECTED = 'checked_not_connected';

	const VALUE_CHECKED_IS_CONNECTED = 'checked_is_connected';

	/**
	 * Check if we were able to verify the team's connection.
	 *
	 * This is not as good as \Endpoints\Settings::verifyAccountId()
	 *
	 * @param TeamSettings $team The team to verify.
	 * @return TeamSettings The same team with its connection status flag updated.
	 */
	public static function check( TeamSettings $team ) {
		\trustedlogin_connector()->log(
			'Checking team connection status',
			__METHOD__,
			'debug',
			array(
				'account_id'     => $team->get( 'account_id' ),
				'current_status' => $team->get( static::KEY ),
			)
		);

		$check = \trustedlogin_connector()
			->verifyAccount( $team );

		if ( $check ) {
			\trustedlogin_connector()->log(
				'Team is connected',
				__METHOD__,
				'info',
				array(
					'account_id' => $team->get( 'account_id' ),
				)
			);
			$team = static::setConnected( $team );
		} else {
			\trustedlogin_connector()->log(
				'Team is NOT connected',
				__METHOD__,
				'warning',
				array(
					'account_id' => $team->get( 'account_id' ),
				)
			);
			$team = static::setNotConnected( $team );
		}
		return $team;
	}

	/**
	 * Set the team as connected
	 *
	 * @param TeamSettings $team Parameter.
	 * @return TeamSettings
	 */
	public static function setConnected( TeamSettings $team ) {
		$team->set( static::KEY, static::VALUE_CHECKED_IS_CONNECTED );
		return $team;
	}

	/**
	 * Set the team as not connected
	 *
	 * @param TeamSettings $team Parameter.
	 * @return TeamSettings
	 */
	public static function setNotConnected( TeamSettings $team ) {
		$team->set( static::KEY, static::VALUE_CHECKED_NOT_CONNECTED );
		return $team;
	}

	/**
	 * Check if we need to verify the team's connection.
	 *
	 * The @throws this method used to advertise was wrong: every key
	 * accessed here (`IsTeamConnected::KEY`, `account_id`) is in
	 * TeamSettings::$defaults, so TeamSettings::get() never hits the
	 * Invalid-key throw path.
	 *
	 * @param TeamSettings $team The team settings to check.
	 */
	public static function needToCheck( TeamSettings $team ) {
		$current_value = $team->get( static::KEY );
		$needs_check   = in_array(
			$current_value,
			array(
				static::VALUE_NOT_CHECKED,
				null,
			),
			true
		);

		\trustedlogin_connector()->log(
			'Checking if team needs verification',
			__METHOD__,
			'debug',
			array(
				'account_id'    => $team->get( 'account_id' ),
				'current_value' => $current_value,
				'needs_check'   => $needs_check,
			)
		);

		return $needs_check;
	}

	/**
	 * Returns true when the stored value matches the connected sentinel.
	 *
	 * Param is intentionally untyped: callers pass the option as-is, which
	 * is `null` for any team that has never been verified (no `connected`
	 * key written yet). PHP 8 throws TypeError on `string $value` + `null`,
	 * which previously took down the whole settings GET response.
	 *
	 * @param mixed $value Raw option value; `null`, `''`, or one of the
	 *                     `VALUE_*` sentinels.
	 * @return bool
	 */
	public static function valueToBoolean( $value ) {
		return static::VALUE_CHECKED_IS_CONNECTED === $value;
	}
}
