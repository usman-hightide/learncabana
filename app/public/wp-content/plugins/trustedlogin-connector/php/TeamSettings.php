<?php
/**
 * Class: TrustedLogin Team Settings
 *
 * @package trustedlogin-vendor
 * @version 0.10.0
 */

namespace TrustedLogin\Vendor;

use Exception;
use TrustedLogin\Vendor\Status\IsTeamConnected;
use TrustedLogin\Vendor\Webhooks\Freescout;
use TrustedLogin\Vendor\Webhooks\Helpscout;

/**
 * Object-representation of one Team's settings.
 */
class TeamSettings {


	const HELPDESK_SETTINGS = 'helpdesk_settings';

	/**
	 * Canonical slugs for the helpdesk providers the plugin ships
	 * support for. New providers add a new constant here so the
	 * switch-cases across SettingsApi / Webhooks\Factory have a single
	 * source of truth (no `'helpscut'` typo can escape to the SaaS).
	 *
	 * @since 2.0.0
	 */
	const HELPDESK_HELPSCOUT = 'helpscout';
	const HELPDESK_FREESCOUT = 'freescout';

	const DEFAULT_HELPDESKS = array(
		self::HELPDESK_HELPSCOUT,
		self::HELPDESK_FREESCOUT,
	);

	/**
	 * Default settings values.
	 *
	 * @var array
	 * @since 0.10.0
	 */
	private $defaults;

	/**
	 * Current settings values.
	 *
	 * @var array
	 * @since 0.10.0
	 */
	private $values;


	/**
	 * Initialise team settings, merging supplied values with defaults.
	 *
	 * @since 0.10.0
	 *
	 * @param array $values Values to set.
	 */
	public function __construct( array $values = array() ) {
		$this->defaults = array(
			'account_id'            => '',
			'private_key'           => '',
			'public_key'            => '',
			// Empty = "No help desk" (issue #163). Older Add Team flows
			// defaulted to 'helpscout' — that behavior is now opt-in.
			'helpdesk'              => array(),
			'approved_roles'        => array( 'administrator' ),
			'debug_enabled'         => 'on',
			'enable_audit_log'      => 'on',
			IsTeamConnected::KEY    => false,
			'message'               => '',
			'status'                => false,
			'name'                  => '',
			self::HELPDESK_SETTINGS => array(),
		);

		$this->values = wp_parse_args( $values, $this->defaults );
	}

	/**
	 * Convert team settings to array.
	 *
	 * @return array
	 */
	public function toArray() {
		if ( ! is_array( $this->values['helpdesk'] ) ) {
			$this->values['helpdesk'] = array( $this->values['helpdesk'] );
		}
		return $this->values;
	}

	/**
	 * Active helpdesk slugs for this team, or [] when none configured.
	 * Empty-string entries are filtered out (corrupt-write defense).
	 *
	 * @since 0.10.0
	 * @since 2.0.0 Empty helpdesk now means "no helpdesk," not "all defaults."
	 *
	 * @return string[]
	 */
	public function getHelpdesks() {
		$helpdesks = $this->get( 'helpdesk' );

		if ( is_string( $helpdesks ) ) {
			$helpdesks = '' === trim( $helpdesks ) ? array() : array( $helpdesks );
		}

		if ( ! is_array( $helpdesks ) ) {
			return array();
		}

		$cleaned = array();
		foreach ( $helpdesks as $slug ) {
			if ( is_string( $slug ) && '' !== trim( $slug ) ) {
				$cleaned[] = $slug;
			}
		}

		return $cleaned;
	}

	/**
	 * Reset all values
	 *
	 * @since 0.10.0
	 *
	 * @param array $values Values to set.
	 * @return $this
	 */
	public function reset( array $values ) {
		$this->values = array();
		foreach ( $this->defaults as $key => $default ) {
			if ( isset( $values[ $key ] ) && ! empty( $values[ $key ] ) ) {
				$value = $values[ $key ];
				if ( is_object( $value ) ) {
					$value = (array) $value;
					foreach ( $value as $k => $v ) {
						if ( is_object( $v ) ) {
							$value[ $k ] = (array) $v;
						}
					}
				}

				$this->values[ $key ] = $value;
			} else {
				$this->values[ $key ] = $default;
			}
		}
		if ( empty( $this->values['approved_roles'] ) ) {
			$this->values['approved_roles'] = array( 'administrator' );
		}
		// "No help desk" is a valid saved state — do not re-default
		// helpdesk to ['helpscout'] here.
		return $this;
	}

	/**
	 * Set a value
	 *
	 * @since 0.10.0
	 *
	 * @param string $key Setting to set.
	 * @param mixed  $value The new value.
	 * @throws Exception When $key is not a valid setting key.
	 * @return $this
	 */
	public function set( $key, $value ) {
		if ( $this->valid( $key ) ) {
			$this->values[ $key ] = $value;
		} else {
			throw new Exception( 'Invalid key' );
		}
		return $this;
	}

	/**
	 * Get a value
	 *
	 * @since 0.10.0
	 * @param string $key Setting to get.
	 * @throws Exception If $key is invalid.
	 * @return mixed
	 */
	public function get( $key ) {
		if ( $this->valid( $key ) ) {
			$value = $this->values[ $key ] ?? null;
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			return $value;
		}
		throw new Exception( 'Invalid key' );
	}

	/**
	 * Check if key is valid
	 *
	 * @since 0.10.0
	 * @param string $key Setting to get.
	 * @return bool
	 */
	public function valid( $key ) {
		return array_key_exists( $key, $this->defaults );
	}

	/**
	 * Static counterpart to {@see TeamSettings::valid()} for callers
	 * that need schema filtering without paying the constructor's
	 * `wp_parse_args` cost (e.g., SettingsApi::hydrateSubmittedTeam
	 * runs this per-key, per-team, per-request).
	 *
	 * Keep in sync with the keys in __construct's $this->defaults.
	 * The test `test_validKey_matches_constructor_defaults` enforces
	 * this — a future field added to defaults must also be added here
	 * or the test will fail.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key Field name to test.
	 * @return bool
	 */
	public static function validKey( $key ) {
		static $keys = null;
		if ( null === $keys ) {
			$keys = array(
				'account_id',
				'private_key',
				'public_key',
				'helpdesk',
				'approved_roles',
				'debug_enabled',
				'enable_audit_log',
				IsTeamConnected::KEY,
				'message',
				'status',
				'name',
				self::HELPDESK_SETTINGS,
			);
		}
		return in_array( $key, $keys, true );
	}

	/**
	 * Get settings for current helpdesk data.
	 *
	 * @since 0.10.0
	 *
	 * @param string $type The helpdesk type (e.g., 'helpscout', 'freescout').
	 *
	 * @return array
	 */
	public function getHelpdeskData( $type = self::HELPDESK_HELPSCOUT ) {
		$helpdesks  = $this->get( 'helpdesk' );
		$account_id = $this->get( 'account_id' );
		if ( empty( $helpdesks ) ) {
			$helpdesks = array( $type );
			$this->set( 'helpdesk', $helpdesks );
		}
		if ( ! is_array( $helpdesks ) ) {
			$helpdesks = array( $helpdesks );
		}

		switch ( $type ) {
			case self::HELPDESK_FREESCOUT:
				$callback = Freescout::actionUrl( $account_id );
				break;
			case self::HELPDESK_HELPSCOUT:
			default:
				$callback = Helpscout::actionUrl( $account_id );
				break;
		}

		$helpdeskSettings = $this->get( self::HELPDESK_SETTINGS );
		if ( $helpdeskSettings ) {
			$helpdesk = $helpdesks[0];
			if ( isset( $helpdeskSettings[ $helpdesk ] ) ) {
				$data = $helpdeskSettings[ $helpdesk ];
				if ( is_object( $data ) ) {
					$data = (array) $data;
				}
				return array(
					'secret'   => $data['secret'] ?? '',
					'callback' => $callback,
				);
			}
		}

		return array();
	}
}
