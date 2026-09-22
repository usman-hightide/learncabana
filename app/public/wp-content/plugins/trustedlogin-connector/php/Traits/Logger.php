<?php
/**
 * Logger trait for TrustedLogin operations.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Traits;

use DateTime;
use TrustedLogin\Vendor\FailureLog;
use TrustedLogin\Vendor\SettingsApi;

trait Logger {


	/**
	 * The random hash used for log location
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * Logs a message to a file using the WordPress Filesystem API.
	 *
	 * Call using `trustedlogin_connector()->log( 'message', __METHOD__ );`
	 *
	 * @param string $message  The message to log.
	 * @param string $method   The method issuing the log call.
	 * @param string $logLevel The log level (e.g., 'info', 'warning', etc.).
	 * @param array  $context  Additional context to log with the message.
	 *
	 * @return bool|null True if the message was written to the log file, false if not, null if error logging is disabled.
	 */
	public function log( $message, $method, $logLevel = 'info', $context = array() ) {

		if ( ! trustedlogin_connector()->getSettings()->isErrorLogggingEnabled() ) {
			return null;
		}

		$context  = (array) $context;
		$logLevel = strtolower( is_string( $logLevel ) ? $logLevel : 'info' );
		$message  = $this->format_log_entry( $logLevel, $message, $context );

		$logFileName = $this->getLogFileName();
		if ( '' === (string) $logFileName ) {
			// getLogFileName() returns '' when the per-request hash
			// can't be derived. Don't fall back to a predictable path.
			return false;
		}
		$logFileDir = dirname( $logFileName );

		$wp_filesystem = $this->init_wp_filesystem();

		if ( is_wp_error( $wp_filesystem ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback when WP_Filesystem itself is unavailable; standard PHP log is the only surface left.
			error_log( $wp_filesystem->get_error_message() );
		}

		// If we're running tests, don't use the WP Filesystem API.
		if ( is_wp_error( $wp_filesystem ) || ( defined( 'DOING_TL_VENDOR_TESTS' ) && DOING_TL_VENDOR_TESTS ) ) {
			// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_touch, WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			if ( ! file_exists( $logFileName ) ) {
				wp_mkdir_p( dirname( $logFileName ) ); // Create the directory if it doesn't exist.
				touch( $logFileName );
			}

			$file = fopen( $logFileName, 'a' );

			$file_written = fwrite( $file, $message . "\n" );
			$file_closed  = fclose( $file );

			// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_touch, WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			return ( $file_written && $file_closed );
		}

		if ( ! $wp_filesystem->is_dir( $logFileDir ) ) {
			$wp_filesystem->mkdir( $logFileDir, FS_CHMOD_DIR ); // Ensure permission compatibility.
		}

		if ( ! $wp_filesystem->exists( $logFileName ) ) {
			$wp_filesystem->touch( $logFileName );
		}

		$this->prevent_directory_browsing( $logFileDir );

		// Read existing content and append new message.
		$existing_content = $wp_filesystem->get_contents( $logFileName );
		$new_content      = $existing_content . $message . "\n";

		return $wp_filesystem->put_contents( $logFileName, $new_content, FS_CHMOD_FILE );
	}

	/**
	 * Records a connector-side failure event in BOTH the file-based
	 * debug log (for forensics, when error_logging is on) AND the
	 * FailureLog ring buffer (always on, drives the admin panel).
	 *
	 * Use this for any failure that an admin should see — envelope
	 * verification failed, decryption failed, SaaS unreachable,
	 * webhook signature failed, etc. Call sites that just want to
	 * log a regular informational/debug line should keep using
	 * {@see self::log()} directly.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type     FailureLog event type (machine-readable
	 *                         identifier, e.g. 'envelope_sig_failed').
	 *                         Surfaces in the admin panel; the React
	 *                         renderer maps it to plain-English copy.
	 * @param string $severity One of FailureLog::SEVERITY_*.
	 * @param string $message  Free-form message for the file log.
	 *                         Not stored in FailureLog.
	 * @param string $method   Calling method (typically __METHOD__).
	 * @param array  $context  Context fields. Stored verbatim in the
	 *                         file log (caller is responsible for
	 *                         redaction); FailureLog applies its
	 *                         strict ALLOWED_CONTEXT_KEYS allowlist
	 *                         before persisting.
	 *
	 * @return void
	 */
	public function failure( $type, $severity, $message, $method, array $context = array() ) {
		// File log gets the full free-form context (caller-redacted).
		// Severity maps directly to log level.
		$this->log( $message, $method, $severity, $context );

		// FailureLog gets the structured event with the allowlist
		// applied — load-bearing security property: even if a future
		// caller passes envelope content / ciphertext / a private key
		// in $context, it never lands in the option.
		FailureLog::record( $type, $severity, $context );
	}

	/**
	 * Deletes the log file.
	 *
	 * @since 1.1
	 *
	 * @return bool True on success, false on failure.
	 */
	public function deleteLog() {
		$logFileName = $this->getLogFileName();

		$wp_filesystem = $this->init_wp_filesystem();

		if ( is_wp_error( $wp_filesystem ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback when WP_Filesystem itself is unavailable; standard PHP log is the only surface left.
			error_log( $wp_filesystem->get_error_message() );
		}

		if ( is_wp_error( $wp_filesystem ) || ( defined( 'DOING_TL_VENDOR_TESTS' ) && DOING_TL_VENDOR_TESTS ) ) {
			return unlink( $logFileName ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Using unlink() because it provides a return value.
		}

		return $wp_filesystem->delete( $logFileName );
	}

	/**
	 * Initializes the WordPress filesystem API.
	 *
	 * @since 1.1
	 *
	 * @return \WP_Filesystem_Base|\WP_Error The filesystem object.
	 */
	private function init_wp_filesystem() {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return $wp_filesystem;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$filesystem_initialized = WP_Filesystem();

		if ( ! $filesystem_initialized ) {
			return new \WP_Error( 'failed_wp_filesystem_init', esc_html__( 'TrustedLogin logging failed: unable to initialize WP_Filesystem.', 'trustedlogin-connector' ) );
		}

		return $wp_filesystem;
	}

	/**
	 * Prevents directory listing of the log folder by dropping an empty
	 * `index.php` in it. Matches the pattern WordPress core uses in
	 * {@see wp_privacy_generate_personal_data_export_file()} for the
	 * personal-data-export directory.
	 *
	 * This is only defence-in-depth. The real protection is the
	 * 64-char SHA-256 hash in the log filename itself — an attacker
	 * who doesn't know the hash can't guess the path regardless of
	 * whether the directory is listable. `.htaccess` / `web.config`
	 * rules would help on Apache / IIS but pure-nginx hosts (Kinsta,
	 * WP Engine, SpinupWP, K8s installs) don't read per-directory
	 * files, so WP core's one-file approach is the portable floor.
	 *
	 * @since 1.1.1
	 *
	 * @param string $dirpath Path to directory to protect.
	 *
	 * @return bool True if the index file exists or was created; false on write failure.
	 */
	private function prevent_directory_browsing( $dirpath ) {

		if ( defined( 'DOING_TL_VENDOR_TESTS' ) && DOING_TL_VENDOR_TESTS ) {
			return false;
		}

		$wp_filesystem = $this->init_wp_filesystem();

		if ( is_wp_error( $wp_filesystem ) ) {
			return false;
		}

		$index_pathname = trailingslashit( $dirpath ) . 'index.php';

		if ( $wp_filesystem->exists( $index_pathname ) ) {
			return true;
		}

		return (bool) $wp_filesystem->put_contents(
			$index_pathname,
			"<?php\n// Silence is golden.\n",
			FS_CHMOD_FILE
		);
	}

	/**
	 * Formats the message for logging.
	 *
	 * @see https://github.com/katzgrau/KLogger/blob/master/src/Logger.php#L260-L294
	 *
	 * @param string $level   The Log Level of the message.
	 * @param string $message The message to log.
	 * @param array  $context The context.
	 *
	 * @return string
	 */
	protected function formatMessage( $level, $message, $context ) {
		return $this->format_log_entry( $level, $message, $context ) . PHP_EOL;
	}

	/**
	 * Builds the canonical log line: `[ts] [level] message {pretty-json-context}`.
	 *
	 * Single source of truth for the on-disk log format. Both log() and
	 * formatMessage() route through this helper so the two can never drift
	 * apart again — debug-log parsers and support runbooks depend on the
	 * exact shape.
	 *
	 * Returns the bare line with no trailing newline; callers append their
	 * own line terminator (log() appends "\n" before writing to the file,
	 * formatMessage() appends PHP_EOL).
	 *
	 * @since 2.0.0
	 *
	 * @param string $level   Lowercased log level.
	 * @param string $message Free-form message body.
	 * @param array  $context Structured context, encoded as pretty JSON.
	 *
	 * @return string
	 */
	private function format_log_entry( $level, $message, $context ) {
		$line = "[{$this->getTimestamp()}] [{$level}] {$message}";
		if ( $context ) {
			$line .= ' ' . wp_json_encode( $context, JSON_PRETTY_PRINT );
		}

		return $line;
	}

	/**
	 * Gets the correctly formatted Date/Time for the log entry.
	 *
	 * PHP DateTime is dump, and you have to resort to trickery to get microseconds
	 * to work correctly, so here it is.
	 *
	 * @see https://github.com/katzgrau/KLogger/blob/master/src/Logger.php#L296-L311
	 *
	 * @return string
	 */
	private function getTimestamp() {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$originalTime = microtime( true );
		$micro        = sprintf( '%06d', ( $originalTime - floor( $originalTime ) ) * 1000000 );
		$date         = new DateTime( gmdate( 'Y-m-d H:i:s.' . $micro, (int) $originalTime ) );

		return $date->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Returns a random hash for the log file.
	 *
	 * @return string Random hash.
	 */
	private function getHash() {

		if ( $this->hash ) {
			return $this->hash;
		}

		$hash = get_option( SettingsApi::LOG_LOCATION_SETTING_NAME, false );

		if ( $hash ) {
			$this->hash = $hash;

			return $hash;
		}

		$this->hash = hash( 'sha256', uniqid( (string) wp_rand(), true ) );

		update_option( SettingsApi::LOG_LOCATION_SETTING_NAME, $this->hash );

		return $this->hash;
	}

	/**
	 * Returns the directory name where the log file.
	 *
	 * @since 0.14.0
	 * @return string
	 */
	private function getLogFileDirectoryName() {
		return 'trustedlogin-logs';
	}

	/**
	 * Get full path to the error log file.
	 *
	 * @see https://github.com/trustedlogin/vendor/issues/83
	 * @param bool $fullPath Whether to return the full path or just the filename.
	 * @return string
	 */
	public function getLogFileName( $fullPath = true ) {

		// Only use plugin dir during tests, not in debug mode.
		if (
			// @phpstan-ignore-next-line
			defined( 'DOING_TL_VENDOR_TESTS' ) && DOING_TL_VENDOR_TESTS
		) {
			return dirname( __DIR__, 2 ) . '/trustedlogin-connector.log';
		}

		$hash = $this->getHash();

		// Refuse to log when the hash is unavailable. The hash directory
		// is the primary URL-guessing defense — a predictable fallback
		// path under uploads/ would land logs at a web-readable
		// location. Surface the failure via the PHP error log so it's
		// visible to operators monitoring the standard channel.
		if ( ! $hash ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Logger init failure; cannot use our own log channel here.
			error_log( 'TrustedLogin: log hash unavailable; logging suppressed for this request.' );

			return '';
		}

		$upload_dir = wp_upload_dir();

		if ( ! $fullPath ) {
			return '/' . str_replace( ABSPATH, '', $upload_dir['basedir'] . '/' . $this->getLogFileDirectoryName() . '/vendor-' . $hash . '.log' );
		}

		return wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . $this->getLogFileDirectoryName() . '/' ) . 'vendor-' . $hash . '.log';
	}
}
