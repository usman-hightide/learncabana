<?php
/**
 * Settings API for managing team and global configuration.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Status\IsTeamConnected;
use TrustedLogin\Vendor\Webhooks\Freescout;
use TrustedLogin\Vendor\Webhooks\Helpscout;

/**
 * Responsible for all read/write of settings plugin uses for teams.
 *
 * Encryption class doesn't follow this rule BTW, but you should.
 */
final class SettingsApi {


	/**
	 * The name of the option we store team settings in.
	 */
	const TEAM_SETTING_NAME = 'trustedlogin_vendor_team_settings';

	/**
	 * The name of the option we store team settings in.
	 */
	const GLOBAL_SETTING_NAME = 'trustedlogin_vendor_other_settings';

	/**
	 * The name of the option we store log hash in.
	 *
	 * @see Traits/Logger
	 */
	const LOG_LOCATION_SETTING_NAME = 'trustedlogin_vendor_log_location';

	/**
	 * Top-level team fields that exist only in REST responses.
	 *
	 * `id` is injected client-side at src/api.js:12-21 to give React
	 * stable list keys. `public_key_fingerprint` is injected by
	 * {@see SettingsApi::toResponseData()} for the UI to display
	 * key-rotation state without exposing the full public key.
	 *
	 * Both must be stripped from any inbound shape AND from anything
	 * we write to disk — round-tripping the GET response shape is the
	 * canonical UI write pattern, so neither field should ever land
	 * in the stored option.
	 */
	private const TEAM_RESPONSE_ONLY_FIELDS = array( 'id', 'public_key_fingerprint' );

	/**
	 * Per-helpdesk-entry fields that exist only in REST responses.
	 *
	 * `secret_set` and `secret_fingerprint` replace the plaintext
	 * `secret` in the response (see scrub_helpdesk_settings()) so the
	 * raw value never lands in window.tlVendor. On a POST round-trip
	 * they must never reach storage.
	 */
	private const HELPDESK_RESPONSE_ONLY_FIELDS = array( 'secret_set', 'secret_fingerprint' );

	/**
	 * Fields whose values are managed server-side (set by
	 * {@see Endpoints\Settings::verifyAccountId()} on the result of a
	 * SaaS verify, or otherwise transient). When the POST omits these
	 * keys, hydration MUST NOT carry the existing values forward —
	 * otherwise a stale error message survives a successful re-verify.
	 *
	 * `IsTeamConnected::KEY` ('connected') is intentionally NOT in this
	 * list because Endpoints\Settings::update() forcibly resets it to
	 * VALUE_NOT_CHECKED before verifyAccountId runs.
	 */
	private const SERVER_MANAGED_FIELDS = array( 'message', IsTeamConnected::STATUS_KEY );

	/**
	 * Collection of team settings objects.
	 *
	 * @var TeamSettings[]
	 */
	protected $teamSettings = array();

	/**
	 * Global settings for the plugin.
	 *
	 * @var array
	 */
	protected $globalSettings;

	/**
	 * Default global settings values.
	 *
	 * @var array
	 */
	protected $globalSettingsDefaults = array(
		'integrations'  => array(
			'helpscout' => array(
				'enabled' => true,
			),
			'freescout' => array(
				'enabled' => true,
			),
		),
		'error_logging' => false,
	);

	/**
	 * Initialise the settings API with team data and optional global settings.
	 *
	 * @param TeamSettings[]|array[] $team_data Collection of team data.
	 * @param array                  $globalSettings Values for global settings.
	 */
	public function __construct( array $team_data, array $globalSettings = array() ) {

		$this->setGlobalSettings( $globalSettings );

		foreach ( $team_data as $values ) {
			if ( is_array( $values ) ) {
				$values = new TeamSettings( $values );
			}
			if ( is_a( $values, TeamSettings::class ) ) {
				$this->teamSettings[] = $values;
			}
		}
	}

	/**
	 * Create instance from saved data.
	 *
	 * @return SettingsApi
	 */
	public static function fromSaved() {

		$saved = get_option( self::TEAM_SETTING_NAME, array() );

		$data = array();
		if ( ! empty( $saved ) ) {
			// Decode as associative arrays. Without `true`, nested values
			// come back as stdClass, which silently bypasses defensive
			// `is_array()` shape guards downstream (the regression
			// commit `3b45a53` worked around). Decoding as assoc at the
			// source eliminates the shape-drift class entirely.
			$saved = json_decode( $saved, true );
			if ( ! is_array( $saved ) ) {
				$saved = array();
			}
			foreach ( $saved as $value ) {
				// Sanitize at the read boundary too: a row written before
				// sanitize-on-write was in place may still carry junk;
				// stripping on read means consumers always see clean data.
				$team   = new TeamSettings( self::sanitizeTeamForStorage( (array) $value ) );
				$data[] = $team;
			}
		}
		$obj     = new self( $data );
		$globals = get_option( self::GLOBAL_SETTING_NAME, null );
		if ( ! empty( $globals ) ) {
			$globals = json_decode( $globals, true );
			if ( ! is_array( $globals ) ) {
				$globals = array();
			}

			$obj->setGlobalSettings( $globals );
		}

		return $obj;
	}

	/**
	 * Save data
	 *
	 * @since 0.10.0
	 * @return SettingsApi
	 */
	public function save() {
		$data = array();
		foreach ( $this->teamSettings as $setting ) {
			// Sanitize at the storage boundary: drop response-only and
			// client-only fields (id, public_key_fingerprint, secret_set,
			// secret_fingerprint) so polluted rows lift on the next
			// save cycle even if no other code path stripped them.
			$_setting = self::sanitizeTeamForStorage( $setting->toArray() );
			// If enabled a helpdesk...
			if ( ! empty( $setting->getHelpdesks() ) ) {
				if ( ! isset( $_setting[ TeamSettings::HELPDESK_SETTINGS ] ) ) {
					$_setting[ TeamSettings::HELPDESK_SETTINGS ] = array();
				}
				$account_id = $setting->get( 'account_id' );
				foreach ( $setting->getHelpdesks() as $helpdesk ) {
					// Ensure the helpdesk settings are an array.
					$helpdesk_settings = (array) ( $_setting[ TeamSettings::HELPDESK_SETTINGS ] ?? array() );
					$existing          = isset( $helpdesk_settings[ $helpdesk ] ) && is_array( $helpdesk_settings[ $helpdesk ] )
						? $helpdesk_settings[ $helpdesk ]
						: array();
					// Generate when entry is missing OR when it's a callback-only
					// shell (no secret yet). mergeHelpdeskSettings preserves
					// callback-only POSTs, so without this guard a freshly added
					// provider can land with an empty webhook secret and stay
					// unusable until the user re-saves.
					if ( ! empty( $existing ) && ! empty( $existing['secret'] ) ) {
						continue;
					}
					$generated = $this->newHelpdeskSettings( $account_id, $helpdesk );
					// Drop any blank `secret` from $existing before merging so it
					// doesn't overwrite the freshly generated one. Mirrors the
					// guard in mergeHelpdeskSettings(). Other $existing fields
					// (e.g. a POSTed `callback`) win over $generated by design.
					if ( isset( $existing['secret'] ) && '' === trim( (string) $existing['secret'] ) ) {
						unset( $existing['secret'] );
					}
					$_setting[ TeamSettings::HELPDESK_SETTINGS ][ $helpdesk ] = array_replace( $generated, $existing );
				}
			}
			$data[] = $_setting;
		}

		// autoload=false; only read from admin / REST paths.
		update_option( self::TEAM_SETTING_NAME, wp_json_encode( $data ), false );
		update_option( self::GLOBAL_SETTING_NAME, wp_json_encode( $this->getGlobalSettings() ), false );
		$count = $this->count();

		/**
		 * Hook: Fires after settings are saved.
		 *
		 * @since 2.0.0
		 * @param int $count Number of teams saved.
		 */
		do_action( 'trustedlogin_connector_settings_saved', $count );

		/**
		 * Deprecated settings-saved action alias.
		 *
		 * @deprecated 1.1
		 */
		do_action_deprecated( 'trustedlogin_vendor_settings_saved', array( $count ), '1.1', 'trustedlogin_connector_settings_saved' );

		// When saving settings, maybe mark onboarding complete.
		if ( $count ) {
			\TrustedLogin\Vendor\Status\Onboarding::setHasOnboarded();
		}

		return $this;
	}

	/**
	 * Reconcile a POSTed team payload against its stored counterpart.
	 *
	 * Returns a TeamSettings whose values are the deep-merge of:
	 *   - the existing team for this account_id (if any), AS BASE,
	 *     minus server-managed fields (so a stale `message` doesn't
	 *     survive a successful re-verify).
	 *   - the submitted payload, AS OVERRIDES, after dropping unknown
	 *     top-level keys, response-only fields, and empty private_key.
	 *
	 * Submitted helpdesk_settings entries are merged into existing
	 * entries field-by-field; the existing `secret` is preserved
	 * unless POST supplies a non-empty replacement. After merging,
	 * any helpdesk_settings entry whose key is not in the active
	 * `helpdesk` list is pruned — preventing a deselected provider's
	 * stale secret from remaining revealable via HelpdeskSecret::get.
	 *
	 * @since 2.0.0
	 *
	 * @param array $submitted_team Raw team payload from a REST POST.
	 * @return TeamSettings Reconciled team ready for addSetting() + save().
	 */
	public function hydrateSubmittedTeam( array $submitted_team ) {
		$submitted_team = self::sanitizeTeamForStorage( $submitted_team );
		$account_id     = isset( $submitted_team['account_id'] ) ? (string) $submitted_team['account_id'] : '';
		$existing_team  = null;

		if ( '' !== $account_id ) {
			try {
				$existing_team = $this->getByAccountId( $account_id );
			} catch ( \Exception $e ) {
				unset( $e ); // New account; absence is the signal.
				$existing_team = null;
			}
		}

		$merged = $existing_team
			? self::sanitizeTeamForStorage( $existing_team->toArray() )
			: array();

		// Drop server-managed fields from the merge base. They're
		// re-set by verifyAccountId on success / failure; carrying
		// stale values forward survives a successful re-verify.
		foreach ( self::SERVER_MANAGED_FIELDS as $key ) {
			unset( $merged[ $key ] );
		}

		foreach ( $submitted_team as $key => $value ) {
			if ( ! TeamSettings::validKey( $key ) ) {
				continue; // Drop unknown top-level keys.
			}

			// Empty private_key → preserve. The UI never receives
			// the plaintext key (toResponseData strips it), so the
			// only legitimate reason POST blanks is "no change.".
			if ( 'private_key' === $key
				&& '' === trim( (string) $value )
				&& ! empty( $merged['private_key'] )
			) {
				continue;
			}

			if ( TeamSettings::HELPDESK_SETTINGS === $key ) {
				$merged[ $key ] = self::mergeHelpdeskSettings(
					isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ? $merged[ $key ] : array(),
					is_array( $value ) ? $value : array()
				);
				continue;
			}

			$merged[ $key ] = $value;
		}

		// Prune helpdesk_settings entries whose helpdesk is no longer
		// in the active list. Closes the "switch provider but old
		// secret remains revealable" gap.
		if ( isset( $merged[ TeamSettings::HELPDESK_SETTINGS ] )
			&& is_array( $merged[ TeamSettings::HELPDESK_SETTINGS ] )
		) {
			$active = isset( $merged['helpdesk'] ) ? $merged['helpdesk'] : array();
			if ( is_string( $active ) ) {
				$active = array( $active );
			}
			if ( ! is_array( $active ) ) {
				$active = array();
			}
			$pruned = array();
			foreach ( $merged[ TeamSettings::HELPDESK_SETTINGS ] as $hd => $entry ) {
				if ( in_array( $hd, $active, true ) ) {
					$pruned[ $hd ] = $entry;
				}
			}
			$merged[ TeamSettings::HELPDESK_SETTINGS ] = $pruned;
		}

		return new TeamSettings( self::sanitizeTeamForStorage( $merged ) );
	}

	/**
	 * Strip response-only fields and unknown keys from a team shape before storage.
	 *
	 * Idempotent. Used at every storage boundary.
	 *
	 * The unknown-key strip is the secret-leak defense: legacy installs
	 * (and the e2e bootstrap fixture pre-cleanup) carry top-level shapes
	 * like `helpdesk_data` that hold raw webhook secrets but bypass the
	 * `helpdesk_settings` scrub model. Whitelisting via
	 * {@see TeamSettings::validKey()} guarantees those blobs never round-
	 * trip through the storage boundary into the React bootstrap.
	 *
	 * @param array $team Raw team data array to sanitize.
	 * @return array Sanitized team data.
	 */
	private static function sanitizeTeamForStorage( array $team ) {
		foreach ( self::TEAM_RESPONSE_ONLY_FIELDS as $field ) {
			unset( $team[ $field ] );
		}

		foreach ( array_keys( $team ) as $key ) {
			if ( ! TeamSettings::validKey( $key ) ) {
				unset( $team[ $key ] );
			}
		}

		if ( isset( $team[ TeamSettings::HELPDESK_SETTINGS ] )
			&& is_array( $team[ TeamSettings::HELPDESK_SETTINGS ] )
		) {
			foreach ( $team[ TeamSettings::HELPDESK_SETTINGS ] as $helpdesk => $entry ) {
				$entry = is_array( $entry ) ? $entry : array();
				foreach ( self::HELPDESK_RESPONSE_ONLY_FIELDS as $field ) {
					unset( $entry[ $field ] );
				}
				$team[ TeamSettings::HELPDESK_SETTINGS ][ $helpdesk ] = $entry;
			}
		}

		return $team;
	}

	/**
	 * Deep-merge submitted helpdesk_settings onto the existing map.
	 *
	 * Per-entry rule:
	 *   - Submitted entry skipped IFF both existing and submitted are
	 *     empty after response-only stripping. Submitted entries with
	 *     non-secret fields (e.g., a new callback) MUST land even
	 *     when no existing entry and no secret.
	 *   - Submitted `secret` preserved from existing IFF blank in POST.
	 *   - Other fields override existing on collision via
	 *     {@see array_replace_recursive()} semantics (PHP-native;
	 *     same convention as wp_parse_args for nested-on-nested).
	 *
	 * @param array $existing Existing helpdesk settings map.
	 * @param array $submitted Submitted helpdesk settings map.
	 *
	 * @return array Merged helpdesk settings.
	 */
	private static function mergeHelpdeskSettings( array $existing, array $submitted ) {
		$merged = $existing;

		foreach ( $submitted as $helpdesk => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$current = isset( $existing[ $helpdesk ] ) && is_array( $existing[ $helpdesk ] )
				? $existing[ $helpdesk ]
				: array();

			foreach ( self::HELPDESK_RESPONSE_ONLY_FIELDS as $field ) {
				unset( $entry[ $field ] );
			}

			$has_submitted_secret = isset( $entry['secret'] )
				&& '' !== trim( (string) $entry['secret'] );
			if ( ! $has_submitted_secret ) {
				unset( $entry['secret'] );
			}

			// Skip ONLY if both sides are empty. Submitted entries
			// with any usable non-secret field MUST land even when
			// there's no existing entry yet.
			if ( empty( $current ) && empty( $entry ) ) {
				continue;
			}

			$merged[ $helpdesk ] = array_replace_recursive( $current, $entry );
		}

		return $merged;
	}

	/**
	 * Get team setting, by id
	 *
	 * @since 0.10.0
	 * @throws \Exception If account not found.
	 *
	 * @param string|int $account_id Account to search for.
	 * @return TeamSettings
	 */
	public function getByAccountId( $account_id ) {
		$account_id = (int) $account_id;

		foreach ( $this->teamSettings as $setting ) {
			if ( $account_id === (int) $setting->get( 'account_id' ) ) {
				return $setting;
			}
		}

		// translators: %d is the account id that wasn't found.
		throw new \Exception( esc_html( sprintf( __( 'Account not found: %d.', 'trustedlogin-connector' ), $account_id ) ) );
	}

	/**
	 * Update team setting.
	 *
	 * @since 0.10.0
	 * @param TeamSettings $value New settings object.
	 * @throws \Exception When no team with a matching account ID is found.
	 * @return SettingsApi
	 */
	public function updateByAccountId( TeamSettings $value ) {
		foreach ( $this->teamSettings as $key => $setting ) {
			if ( (string) $value->get( 'account_id' ) === (string) $setting->get( 'account_id' ) ) {
				$this->teamSettings[ $key ] = $value;
				return $this;
			}
		}
		throw new \Exception( esc_html__( 'Cannot save; account not found.', 'trustedlogin-connector' ) );
	}

	/**
	 * Check if setting is in collection
	 *
	 * @since 0.10.0
	 * @param string $account_id Parameter.
	 * @return bool
	 */
	public function hasSetting( $account_id ) {
		foreach ( $this->teamSettings as $setting ) {
			if ( (string) $account_id === (string) $setting->get( 'account_id' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add or update a setting to collection
	 *
	 * @since 0.10.0
	 * @param TeamSettings $setting Parameter.
	 * @return $this
	 */
	public function addSetting( TeamSettings $setting ) {
		// If we have it already, update.
		if ( $this->hasSetting( $setting->get( 'account_id' ) ) ) {
			$this->updateByAccountId( $setting );
			return $this;
		}
		// add it to collection.
		$this->teamSettings[] = $setting;
		return $this;
	}

	/**
	 * Get count of settings
	 *
	 * @return int
	 */
	public function count() {
		return ! empty( $this->teamSettings ) ? count( $this->teamSettings ) : 0;
	}

	/**
	 * Reset all teams and maybe global settings

	 * @param bool $resetGeneralSettings Parameter.
	 * @since 0.10.0
	 * @return $this
	 */
	public function reset( $resetGeneralSettings = false ) {
		$this->teamSettings = array();
		if ( $resetGeneralSettings ) {
			$this->globalSettings = $this->globalSettingsDefaults;
		}
		return $this;
	}

	/**
	 * Convert to array of arrays
	 *
	 * @since 0.10.0
	 * @return array
	 */
	public function toArray() {

		return array(
			'teams'        => $this->allTeams( true ),
			'integrations' => $this->getIntegrationSettings(),
		);
	}


	/**
	 * Get all Teams as an array
	 *
	 * @since 0.10.0
	 * @param bool $as_array If true, teams are converted to array.
	 * @return array
	 */
	public function allTeams( $as_array = false ) {
		$data = array();
		foreach ( $this->teamSettings as $setting ) {
			if ( $as_array ) {
				$data[] = $setting->toArray();
			} else {
				$data[] = $setting;
			}
		}
		return $data;
	}

	/**
	 * Check if any connected team is active.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function hasConnectedTeam() {
		foreach ( $this->teamSettings as $setting ) {
			if ( $setting->get( 'connected' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replaces each helpdesk's `secret` with a short SHA-256 fingerprint +
	 * a boolean `secret_set`, so the bootstrap doesn't ship raw webhook
	 * signing keys to `window.tlVendor` on every admin page load. Any XSS
	 * in wp-admin — ours or another plugin — would otherwise exfiltrate
	 * these keys verbatim. The React integration panel fetches the real
	 * secret on demand via the `/helpdesks/<helpdesk>/secret` REST route.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, array<string, mixed>> $helpdesk_settings Map of helpdesk slug → settings array.
	 *
	 * @return array<string, array<string, mixed>> Same shape with `secret` removed and `secret_set` / `secret_fingerprint` added per entry.
	 */
	private static function scrub_helpdesk_settings( array $helpdesk_settings ) {
		foreach ( $helpdesk_settings as $hd => $entry ) {
			// Normalize stdClass → array so the inner `secret` read
			// works regardless of which write path produced it. See
			// the corresponding note in toResponseData().
			if ( is_object( $entry ) ) {
				$entry = (array) $entry;
			}
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$secret = (string) ( $entry['secret'] ?? '' );
			unset( $entry['secret'] );
			$entry['secret_set']         = '' !== $secret;
			$entry['secret_fingerprint'] = '' !== $secret ? substr( hash( 'sha256', $secret ), 0, 8 ) : '';
			$helpdesk_settings[ $hd ]    = $entry;
		}
		return $helpdesk_settings;
	}

	/**
	 * Get the settings, as used by API and UI
	 *
	 * @return array
	 */
	public function toResponseData() {
		$data  = $this->toArray();
		$teams = $data['teams'] ?? array();

		foreach ( $teams as $i => $team ) {
			$teams[ $i ][ IsTeamConnected::KEY ] = IsTeamConnected::valueToBoolean(
				isset( $team[ IsTeamConnected::KEY ] ) ? $team[ IsTeamConnected::KEY ] : null
			);

			// Strip the sodium private key from API responses; return only a
			// short fingerprint so the UI can display key-rotation state.
			if ( isset( $teams[ $i ]['private_key'] ) ) {
				unset( $teams[ $i ]['private_key'] );
			}

			if ( ! empty( $team['public_key'] ) && is_string( $team['public_key'] ) ) {
				$teams[ $i ]['public_key_fingerprint'] = substr( hash( 'sha256', $team['public_key'] ), 0, 16 );
			}

			// Scrub per-helpdesk webhook-signing secrets from the bootstrap.
			// The raw value reaches ConfigureIntegration via the nonce-
			// gated /helpdesks/<hd>/secret endpoint instead.
			//
			// The shape of helpdesk_settings varies by write path —
			// matrix-panel saves write arrays, older json_decode()
			// paths leave stdClass. Cast through (array) here so both
			// shapes flow through the scrub uniformly.
			if ( isset( $teams[ $i ][ TeamSettings::HELPDESK_SETTINGS ] ) ) {
				$hs_raw = $teams[ $i ][ TeamSettings::HELPDESK_SETTINGS ];
				if ( is_array( $hs_raw ) || is_object( $hs_raw ) ) {
					$teams[ $i ][ TeamSettings::HELPDESK_SETTINGS ] =
						self::scrub_helpdesk_settings( (array) $hs_raw );
				}
			}
		}
		$data['teams'] = $teams;
		$debugMode     = TRUSTEDLOGIN_DEBUG;
		if ( is_null( $debugMode ) ) {
			$debugMode = 'NULL';
		}

		return array_merge(
			$data,
			array(
				'debug_mode'           => $debugMode,
				'error_logging'        => $this->isErrorLogggingEnabled(),
				'integrations'         => $this->getIntegrationSettings(),
				'default_landing_page' => isset( $this->globalSettings['default_landing_page'] )
					? (string) $this->globalSettings['default_landing_page']
					: 'settings',
			)
		);
	}

	/**
	 * Get integration settings
	 *
	 * @since 0.10.0
	 * @return array
	 */
	public function getIntegrationSettings() {
		$settings = isset( $this->globalSettings['integrations'] ) ? $this->globalSettings['integrations'] : array();
		return $settings;
	}


	/**
	 * Get the global settings
	 */
	public function getGlobalSettings() {
		return $this->globalSettings;
	}

	/**
	 * Is error logging enabled?
	 *
	 * @return bool
	 */
	public function isErrorLogggingEnabled() {
		/**
		 * Filters the effective value of the TRUSTEDLOGIN_DEBUG constant.
		 *
		 * Defaults to whatever the constant is defined to (or `null` if not
		 * defined), letting consumers override the value at runtime. A
		 * non-null filtered value forces logging on or off; `null` falls
		 * through to the plugin setting below.
		 *
		 * Primarily exists so tests can simulate the constant: PHP constants
		 * can't be redefined, and the plugin bootstrap defines
		 * TRUSTEDLOGIN_DEBUG as `null` early, so tests cannot otherwise
		 * exercise the "constant overrides setting" code path.
		 *
		 * @since 2.0.0
		 *
		 * @param mixed $debug The current effective constant value
		 *                     (`true`, `false`, `null`, or any value).
		 */
		$debug = apply_filters(
			'trustedlogin/connector/debug-constant',
			defined( 'TRUSTEDLOGIN_DEBUG' ) ? TRUSTEDLOGIN_DEBUG : null
		);

		// If the override is set (truthy or falsy bool), use it to control all logging.
		if ( null !== $debug ) {
			return (bool) $debug;
		}

		// Otherwise, use the plugin setting.
		if ( ! isset( $this->globalSettings['error_logging'] ) ) {
			return false;
		}
		return (bool) $this->globalSettings['error_logging'];
	}

	/**
	 * Set the global settings
	 *
	 * Values will be merged with existing settings.
	 *
	 * @param array $globalSettings Parameter.
	 * @return $this
	 */
	public function setGlobalSettings( array $globalSettings ) {

		// When resetting from saved, deal with json_decode not being recursive for array conversion.
		if ( isset( $globalSettings['integrations'] ) && is_object( $globalSettings['integrations'] ) ) {
			$globalSettings['integrations'] = (array) $globalSettings['integrations'];
			foreach ( $globalSettings['integrations'] as $i => $value ) {
				$globalSettings['integrations'][ $i ] = (array) $value;
			}
		}
		$this->globalSettings = wp_parse_args(
			$globalSettings,
			! empty( $this->globalSettings ) ? $this->globalSettings : $this->globalSettingsDefaults
		);
		return $this;
	}

	/**
	 * Reset helpdesk settings for one account.
	 *
	 * @param string $accountId The account ID.
	 * @param string $helpdesk The helpdesk provider slug.
	 *
	 * @return SettingsApi
	 *
	 * @throws \RuntimeException If the helpdesk is not active on the team.
	 */
	public function resetHelpdeskSettings( $accountId, $helpdesk ) {
		$team = $this->getByAccountId( $accountId );

		// Helpdesk slug must be in the team's active list before
		// (re)provisioning. Pruned providers stay pruned.
		$active = $team->getHelpdesks();
		if ( ! in_array( $helpdesk, $active, true ) ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Helpdesk %s is not active on team %s', $helpdesk, $accountId ) )
			);
		}

		$settings = $this->newHelpdeskSettings( $accountId, $helpdesk );
		// Guard against null — TeamSettings::get() returns null for unset keys,
		// and array_merge() with a null arg fatals on PHP 8+.
		$existing = (array) ( $team->get( TeamSettings::HELPDESK_SETTINGS ) ?? array() );
		$team->set(
			TeamSettings::HELPDESK_SETTINGS,
			array_merge(
				$existing,
				array( $helpdesk => $settings )
			)
		);
		$this->save();
		return $this;
	}

	/**
	 * Generate a new helpdesk settings entry with secret and callback.
	 *
	 * @param string $accountId The account ID.
	 * @param string $helpdesk The helpdesk provider slug.
	 *
	 * @return array Configuration array with secret and callback.
	 */
	protected function newHelpdeskSettings( $accountId, $helpdesk ) {

		switch ( $helpdesk ) {
			case TeamSettings::HELPDESK_FREESCOUT:
				$callback = Freescout::actionUrl( $accountId );
				break;
			case TeamSettings::HELPDESK_HELPSCOUT:
			default:
				$callback = Helpscout::actionUrl( $accountId );
				break;
		}

		return array(
			'secret'   => AccessKeyLogin::makeSecret(),
			'callback' => $callback,
		);
	}
}
