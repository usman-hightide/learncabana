<?php
/**
 * Plugin Name: LearnDash LMS - Course Grid
 * Plugin URI: https://www.learndash.com/
 * Description: Build LearnDash course grid easily.
 * Version: 2.0.10
 * Author: LearnDash
 * Author URI: https://www.learndash.com/
 * Text Domain: learndash-course-grid
 * Domain Path: languages
 *
 * @package LearnDash\Course_Grid
 */

namespace LearnDash;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

define( 'LEARNDASH_COURSE_GRID_VERSION', '2.0.10' );

use LearnDash\Course_Grid\Admin\Meta_Boxes;
use LearnDash\Course_Grid\Security;
use LearnDash\Course_Grid\Skins;
use LearnDash\Course_Grid\AJAX;
use LearnDash\Course_Grid\Shortcodes;
use LearnDash\Course_Grid\Blocks;
use LearnDash\Course_Grid\Compatibility;
use stdClass;

/**
 * Course_Grid class.
 */
class Course_Grid {
	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Security module.
	 *
	 * @since 2.0.10
	 *
	 * @var Security
	 */
	public $security;

	/**
	 * Skins module.
	 *
	 * @since 2.0.0
	 *
	 * @var Skins
	 */
	public $skins;

	/**
	 * AJAX module.
	 *
	 * @since 2.0.10
	 *
	 * @var AJAX
	 */
	public $ajax;

	/**
	 * Shortcodes module.
	 *
	 * @since 2.0.0
	 *
	 * @var Shortcodes
	 */
	public $shortcodes;

	/**
	 * Blocks module.
	 *
	 * @since 2.0.0
	 *
	 * @var Blocks
	 */
	public $blocks;

	/**
	 * Compatibility module.
	 *
	 * @since 2.0.10
	 *
	 * @var Compatibility
	 */
	public $compatibility;

	/**
	 * Posts objects.
	 *
	 * @since 2.0.0
	 * @depreacated 2.0.10
	 *
	 * @var object
	 */
	public $posts;

	/**
	 * Admin module.
	 *
	 * @since 2.0.10
	 *
	 * @var object
	 */
	public $admin;

	/**
	 * Retrieves the plugin singleton instance.
	 *
	 * @since 2.0.0
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) || ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->define_constants();

		spl_autoload_register( [ $this, 'autoload' ] );

		add_action( 'plugins_loaded', [ $this, 'load_translations' ] );

		$this->security      = new Security();
		$this->skins         = new Skins();
		$this->ajax          = new AJAX();
		$this->shortcodes    = new Shortcodes();
		$this->blocks        = new Blocks();
		$this->compatibility = new Compatibility();

		// Include files manually.
		include_once LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'includes/functions.php';

		// Admin.
		if ( is_admin() ) {
			$this->admin             = new stdClass();
			$this->admin->meta_boxes = new Meta_Boxes();
		}
	}

	/**
	 * Defines constants used by the plugin
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function define_constants() {
		if ( ! defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) {
			define( 'LEARNDASH_COURSE_GRID_FILE', __FILE__ );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_PATH' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_URL' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_PATH' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_PATH', LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'templates/' );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_URL' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_URL', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'templates/' );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_ASSET_PATH' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_ASSET_PATH', LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'assets/' );
		}

		if ( ! defined( 'LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL' ) ) {
			define( 'LEARNDASH_COURSE_GRID_PLUGIN_ASSET_URL', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'assets/' );
		}

		// Added for backward compatibility.
		if ( ! defined( 'LEARNDASH_COURSE_GRID_COLUMNS' ) ) {
			define( 'LEARNDASH_COURSE_GRID_COLUMNS', 3 );
		}
	}

	/**
	 * Autoload function for dynamically loading classes based on the LearnDash Course Grid namespace.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $class The fully-qualified class name to be autoloaded.
	 *
	 * @return void
	 */
	public function autoload( $class ) {
		$class_components = explode( '\\', $class );
		$class_file       = str_replace( '_', '-', strtolower( $class_components[ count( $class_components ) - 1 ] ) );
		$filename         = $class_file . '.php';

		$file = false;

		if ( strpos( $class, 'LearnDash\\Course_Grid\\Shortcodes\\' ) !== false ) {
			$file = 'includes/shortcodes/class-' . $filename;
		} elseif ( strpos( $class, 'LearnDash\\Course_Grid\\Gutenberg\\Blocks\\' ) !== false ) {
			$file = 'includes/gutenberg/blocks/' . $class_file . '/index.php';
		} elseif ( strpos( $class, 'LearnDash\\Course_Grid\\Admin\\' ) !== false ) {
			$file = 'includes/admin/class-' . $filename;
		} elseif ( strpos( $class, 'LearnDash\\Course_Grid\\Lib' ) !== false ) {
			$file = 'includes/lib/class-' . $filename;
		} elseif ( strpos( $class, 'LearnDash\\Course_Grid\\' ) !== false ) {
			$file = 'includes/class-' . $filename;
		}

		if ( $file && file_exists( LEARNDASH_COURSE_GRID_PLUGIN_PATH . $file ) ) {
			include_once LEARNDASH_COURSE_GRID_PLUGIN_PATH . $file;
		}
	}

	/**
	 * Load translations.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function load_translations() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intended use of WP core filter hook.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'learndash-course-grid' );

		load_textdomain( 'learndash-course-grid', WP_LANG_DIR . '/plugins/learndash-course-grid-' . $locale . '.mo' );

		load_plugin_textdomain( 'learndash-course-grid', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		include LEARNDASH_COURSE_GRID_PLUGIN_PATH . 'includes/class-translations.php';
	}
}

/**
 * Returns the main instance of the plugin.
 *
 * @since 2.0.0
 *
 * @return Course_Grid
 */
function course_grid() {
	return Course_Grid::instance();
}

// Initialize the plugin.
course_grid();
