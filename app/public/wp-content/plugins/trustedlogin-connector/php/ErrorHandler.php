<?php
/**
 * Error and exception handler for the TrustedLogin Connector plugin.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor;

use TrustedLogin\Vendor\Traits\Logger;

/**
 * When in debug mode, log all errors to our log.
 *
 * @see https://github.com/inpsyde/Wonolog/blob/master/src/PhpErrorController.php
 */
final class ErrorHandler {

	use Logger;

	/**
	 * Register error handlers
	 *
	 * @see https://github.com/inpsyde/Wonolog/blob/b1af1bcc8bdec2bd153a323bbbf507166c9c8e1b/src/Controller.php#L103-L106
	 */
	public static function register() {

		$controller = new self();
		register_shutdown_function( array( $controller, 'onFatal' ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- This class IS the error handler.
		set_error_handler( array( $controller, 'onError' ), E_ALL | E_STRICT );
		set_exception_handler( array( $controller, 'onException' ) );
	}
	/**
	 * Error handler.
	 *
	 * @param  int        $num Parameter.
	 * @param  string     $str Parameter.
	 * @param  string     $file Parameter.
	 * @param  int        $line Parameter.
	 * @param  array|null $context Parameter.
	 *
	 * @return bool
	 */
	public function onError( $num, $str, $file, $line, $context = array() ) {
		// Only log errors originating inside this plugin; pass others through.
		$file = (string) $file;
		$root = self::plugin_root();
		if ( '' === $root || 0 !== strpos( $file, $root ) ) {
			return false;
		}
		$this->log( implode( ' ', array( $num, $str, "$file:$line" ) ), __METHOD__, 'error', $context );
		return false;
	}

	/**
	 * Returns the absolute path of the plugin root, used as a prefix match for
	 * onError()'s file-scope filter.
	 *
	 * Cached per-request via static so we don't dirname() on every error.
	 *
	 * @return string
	 */
	private static function plugin_root() {
		static $root = null;
		if ( null === $root ) {
			// Trailing separator so the prefix match in onError() can't
			// also match a sibling plugin whose name starts with the
			// same characters (e.g. trustedlogin-connector-extras).
			$root = rtrim( dirname( __DIR__ ), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
		}
		return $root;
	}

	/**
	 * Uncaught exception handler.
	 *
	 * @param  \Throwable $e Parameter.
	 *
	 * @throws \Throwable Re-throws the caught exception after logging.
	 */
	public function onException( $e ) {

		$this->onError( $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine() );

		throw $e;
	}

	/**
	 * Checks for a fatal error, work-around for `set_error_handler` not working with fatal errors.
	 */
	public function onFatal() {

		$last_error = error_get_last();
		if ( ! $last_error ) {
			return;
		}

		$error = array_merge(
			array(
				'type'    => -1,
				'message' => '',
				'file'    => '',
				'line'    => 0,
			),
			$last_error
		);

		$fatals = array(
			E_ERROR,
			E_PARSE,
			E_CORE_ERROR,
			E_CORE_WARNING,
			E_COMPILE_ERROR,
			E_COMPILE_WARNING,
		);

		if ( in_array( $error['type'], $fatals, true ) ) {
			$this->onError( $error['type'], $error['message'], $error['file'], $error['line'] );
		}
	}
}
