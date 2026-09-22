<?php

/**
 * Plugin Name:       Design Upgrade Pro for LearnDash
 * Description:       <strong>Pro Version:</strong> Customize LearnDash's design &ndash; course content, profile page, focus mode, course navigation widget, course grid, login/registration, etc. &ndash; with 100+ options in the WordPress Customizer.
 * Version:           2.21.1
 * Author:            Escape Creative
 * Author URI:        https://escapecreative.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       design-upgrade-pro-learndash
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * LearnDash Dependency Check
 * Must have LearnDash active. Otherwise, deactivate plugin.
 * @link https://wordpress.stackexchange.com/questions/127818/how-to-make-a-plugin-require-another-plugin
 */
add_action( 'admin_init', 'ldx_learndash_check' );

function ldx_learndash_check() {

	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! class_exists( 'SFWD_LMS' ) ) {

		add_action( 'admin_notices', 'ldx_activate_plugin_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	} // end if is_plugin_active
} // end ldx_learndash_check()

function ldx_activate_plugin_notice() { ?>
	<div class="notice notice-error is-dismissible">
		<p><strong>Error:</strong> Please install &amp; activate the LearnDash plugin before you can use Design Upgrade Pro for LearnDash.</p>
	</div>
<?php }


/**
 * Current plugin version.
 * Start at version 1.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LDX_DESIGN_UPGRADE_PRO_LEARNDASH_VERSION', '2.21.1' );

/**
 * Define Constants
 */
define( 'LDX_DESIGN_UPGRADE_PRO_LEARNDASH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


/**
 * Adds <body> class when plugin is active.
 *
 * @since 2.0
 */
include_once plugin_dir_path( __FILE__ ) . 'inc/body-class.php';


/**
 * Theme Compatibility
 *
 * @since 1.2.1
 */
include_once plugin_dir_path( __FILE__ ) . 'inc/theme-compat.php';


/**
 * Custom URL for 'Edit Profile' link
 *
 * @since 1.7
 */
include_once plugin_dir_path( __FILE__ ) . 'inc/learndash-functions.php';


/**
 * Add extra class names to various elements.
 *
 * @since 2.6.1
 */
include_once plugin_dir_path( __FILE__ ) . 'inc/extra-classes.php';


/**
 * Add improved LearnDash styles to the front-end.
 * This also adds the inline styles from the Customizer.
 *
 * @since 1.0
 * @return void
 */
function ldx_design_upgrade_pro_learndash_enqueue_css() {

	// check to see if free plugin is installed
	if( wp_style_is( 'ldx-design-upgrade-learndash', 'enqueued' ) ) {

		// do nothing

	} else {

		// Set default template as "legacy"
		$template = 'legacy';

		// If LD3 option exists, check & reassign the $template variable
		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			$template = \LearnDash_Theme_Register::get_active_theme_key();
		}

		if ( 'legacy' === $template ) {

			// Add stylesheet for "Legacy" template
			wp_enqueue_style( 'ldx-design-upgrade-learndash', plugins_url( 'assets/css/learndash.css', __FILE__ ), array( 'learndash_style', 'sfwd_front_css', 'learndash_template_style_css', 'learndash_quiz_front_css' ), '2.21.1' );

		} else {

			// Add stylesheet for "LearnDash 3.0" template
			wp_enqueue_style( 'ldx-design-upgrade-learndash', plugins_url( 'assets/css/ld3.css', __FILE__ ), array( 'learndash_quiz_front_css', 'learndash-front' ), '2.21.1' );

		} // end: legacy/LD3 check

	} // end: wp_style_is check

	// Add stylesheet for PRO features only
	wp_enqueue_style( 'ldx-design-upgrade-learndash-pro', plugins_url( 'assets/css/ld3-pro.css', __FILE__ ), array( 'ldx-design-upgrade-learndash' ), '2.21.1' );

	// Add inline styles from the Customizer
	wp_add_inline_style( 'ldx-design-upgrade-learndash', ldx_design_upgrade_pro_learndash_customizer_css() );

} // function ldx_design_upgrade_pro_learndash_enqueue_css()

add_action( 'wp_enqueue_scripts', 'ldx_design_upgrade_pro_learndash_enqueue_css' );


/**
 * Include Customizer settings
 */
require LDX_DESIGN_UPGRADE_PRO_LEARNDASH_PLUGIN_DIR . '/inc/customizer/class-design-upgrade-pro-learndash-customizer.php';
new LDX_Design_Upgrade_Pro_Learndash_Customizer();


/**
 * Add Plugin Action Links to Customizer & License Key pages.
 *
 * @since 1.0
 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
 */
add_filter( 'plugin_action_links', 'ldx_add_plugin_action_links', 10, 5 );

function ldx_add_plugin_action_links( $actions, $plugin_file ) {
	static $plugin;

	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);

	if ($plugin == $plugin_file) {

		$open_license_key_page = array('license_key' => '<a href="' . esc_url( admin_url( '/options-general.php?page=design-upgrade-learndash-license' ) ) . '">' . __( 'License Key', 'design-upgrade-pro-learndash' ) . '</a>');

		$template = 'legacy';
		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			$template = \LearnDash_Theme_Register::get_active_theme_key();
		}

		if ( 'legacy' === $template ) {

			$open_customizer = array('customizer' => '<a href="' . esc_url( admin_url( '/customize.php?autofocus[panel]=ldx_learndash_styles_panel' ) ) . '">' . __( 'Customize', 'design-upgrade-pro-learndash' ) . '</a>');

		} else {

			$open_customizer = array('customizer' => '<a href="' . esc_url( admin_url( '/customize.php?autofocus[panel]=ldx3_learndash_styles_panel' ) ) . '">' . __( 'Customize', 'design-upgrade-pro-learndash' ) . '</a>');

		} // end legacy/ld3 check

		$actions = array_merge($open_license_key_page, $actions);
		$actions = array_merge($open_customizer, $actions);

	} // if ($plugin == $plugin_file)

	return $actions;

} // function ldx_add_plugin_action_links()

/**
 * BuddyBoss Compatibility
 *
 * @since 2.19
 * Must come AFTER the Customizer settings have loaded.
 */
include_once plugin_dir_path( __FILE__ ) . 'integrations/buddyboss-theme.php';

/**
 * Setup EDD Software Licensing & Automatic Updates
 *
 * @since 1.0
 * @link https://docs.easydigitaldownloads.com/article/383-automatic-upgrades-for-wordpress-plugins
 */
// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'LDX_EDD_STORE_URL', 'https://escapecreative.com' );

// the download ID for the product in Easy Digital Downloads
define( 'LDX_EDD_ITEM_ID', 450 );

// the name of our product
define( 'LDX_EDD_ITEM_NAME', 'Design Upgrade Pro for LearnDash' );

// the name of the settings page for the license input to be displayed
define( 'LDX_EDD_PLUGIN_LICENSE_PAGE', 'design-upgrade-learndash-license' );

if( !class_exists( 'LDX_Design_Upgrade_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/LDX_Design_Upgrade_Plugin_Updater.php' );
}

function ldx_edd_plugin_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'ldx_edd_license_key' ) );

	// setup the updater
	$edd_updater = new LDX_Design_Upgrade_Plugin_Updater( LDX_EDD_STORE_URL, __FILE__,
		array(
			'version' => '2.21.1',                  // current version number
			'license' => $license_key,             // license key (used get_option above to retrieve from DB)
			'item_id' => LDX_EDD_ITEM_ID,       // ID of the product
			'author'  => 'Escape Creative', // author of this plugin
			'beta'    => false,
		)
	);

}
add_action( 'admin_init', 'ldx_edd_plugin_updater', 0 );


/************************************
* the code below is just a standard
* options page. Substitute with
* your own.
*************************************/

function ldx_edd_license_menu() {
	add_options_page( 'Design Upgrade Pro for LearnDash License Key', 'Design Upgrade Pro LearnDash License', 'manage_options', LDX_EDD_PLUGIN_LICENSE_PAGE, 'ldx_edd_license_page' );
}
add_action('admin_menu', 'ldx_edd_license_menu');

function ldx_edd_license_page() {
	$license = get_option( 'ldx_edd_license_key' );
	$status  = get_option( 'ldx_edd_license_status' );
	?>
	<div class="wrap">
		<h2><?php _e('Design Upgrade Pro for LearnDash - License Key'); ?></h2>
		<ol>
			<li><?php _e('Enter license key'); ?></li>
			<li><?php _e('Click <b>Save Changes</b>'); ?></li>
			<li><?php _e('<b>*IMPORTANT*</b> After the page reloads, you must click <b>Activate License</b> to finalize activation'); ?></li>
		</ol>
		<form method="post" action="options.php">

			<?php settings_fields('ldx_edd_license'); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('License Key'); ?>
						</th>
						<td>
							<input id="ldx_edd_license_key" name="ldx_edd_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<p><?php _e( 'Your license key is in your purchase receipt email, or you can find it on your <a href="https://escapecreative.com/account/">account page</a>.' ); ?></p>
						</td>
					</tr>
					<?php if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Activate License'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									Status: <span style="color:green; font-weight:bold;"><?php _e('Active'); ?></span>
									<?php wp_nonce_field( 'ldx_edd_nonce', 'ldx_edd_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License'); ?>" style="display:block; margin-top:8px;"/>
								<?php } else {
									wp_nonce_field( 'ldx_edd_nonce', 'ldx_edd_nonce' ); ?>
									<input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>

		</form>
	<?php
}

function ldx_edd_register_option() {
	// creates our settings in the options table
	register_setting('ldx_edd_license', 'ldx_edd_license_key', 'ldx_edd_sanitize_license' );
}
add_action('admin_init', 'ldx_edd_register_option');

function ldx_edd_sanitize_license( $new ) {
	$old = get_option( 'ldx_edd_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'ldx_edd_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}



/************************************
* this illustrates how to activate
* a license key
*************************************/

function ldx_edd_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_activate'] ) ) {

		// run a quick security check
		if( ! check_admin_referer( 'ldx_edd_nonce', 'ldx_edd_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'ldx_edd_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => LDX_EDD_ITEM_ID,
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( LDX_EDD_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'design-upgrade-pro-learndash' );
			}

		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired' :

						$message = sprintf(
							__( 'Your license key expired on %s.' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'disabled' :
					case 'revoked' :

						$message = __( 'Your license key has been disabled.', 'design-upgrade-pro-learndash' );
						break;

					case 'missing' :

						$message = __( 'Invalid license.', 'design-upgrade-pro-learndash' );
						break;

					case 'invalid' :
					case 'site_inactive' :

						$message = __( 'Your license is not active for this site.', 'design-upgrade-pro-learndash' );
						break;

					case 'item_name_mismatch' :

						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), LDX_EDD_ITEM_NAME );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.', 'design-upgrade-pro-learndash' );
						break;

					default :

						$message = __( 'An error occurred, please try again.', 'design-upgrade-pro-learndash' );
						break;
				}

			}

		}

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			$base_url = admin_url( 'options-general.php?page=' . LDX_EDD_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			wp_redirect( $redirect );
			exit();
		}

		// $license_data->license will be either "valid" or "invalid"

		update_option( 'ldx_edd_license_status', $license_data->license );
		wp_redirect( admin_url( 'options-general.php?page=' . LDX_EDD_PLUGIN_LICENSE_PAGE ) );
		exit();
	}
}
add_action('admin_init', 'ldx_edd_activate_license');


/***********************************************
* Illustrates how to deactivate a license key.
* This will decrease the site count
***********************************************/

function ldx_edd_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_license_deactivate'] ) ) {

		// run a quick security check
		if( ! check_admin_referer( 'ldx_edd_nonce', 'ldx_edd_nonce' ) )
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = trim( get_option( 'ldx_edd_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id'    => LDX_EDD_ITEM_ID,
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( LDX_EDD_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'design-upgrade-pro-learndash' );
			}

			$base_url = admin_url( 'options-general.php?page=' . LDX_EDD_PLUGIN_LICENSE_PAGE );
			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

			wp_redirect( $redirect );
			exit();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'ldx_edd_license_status' );
		}

		wp_redirect( admin_url( 'options-general.php?page=' . LDX_EDD_PLUGIN_LICENSE_PAGE ) );
		exit();

	}
}
add_action('admin_init', 'ldx_edd_deactivate_license');

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer.
 */
function ldx_edd_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

		switch( $_GET['sl_activation'] ) {

			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;

			case 'true':
			default:
				// Developers can put a custom success message here for when activation is successful if they wish.
				break;

		}
	}
}
add_action( 'admin_notices', 'ldx_edd_admin_notices' );


/**
 * Generate CSS based on the Customizer settings.
 *
 * @since 1.0
 * @return string
 */
function ldx_design_upgrade_pro_learndash_customizer_css() {

	// Set default template as "legacy"
	$template = 'legacy';
	// If LD3 option exists, check & reassign the $template variable
	if ( class_exists( 'LearnDash_Theme_Register' ) ) {
		$template = \LearnDash_Theme_Register::get_active_theme_key();
	}

	if ( 'legacy' === $template ) {

		/**
		 * Inline Styles - @legacy
		 *
		 * @since 1.0
		 */
		$css = '';

		// Positive/Correct & Negative/Incorrect Colors
		$ldx_color_correct = get_theme_mod( 'ldx_color_correct', '' );
		$ldx_color_incorrect = get_theme_mod( 'ldx_color_incorrect', '' );


		// Positive/Correct
		if( $ldx_color_correct != '' ) {

			// Background Color
			// $css .= 'div.wpProQuiz_content .wpProQuiz_time_limit .wpProQuiz_progress{background:' . $ldx_color_correct . ';}';

			// Text Color
			$css .= '#learndash_profile .learndash_profile_quizzes .passed .scores,div.wpProQuiz_content .wpProQuiz_answerCorrect,div.wpProQuiz_content .wpProQuiz_answerCorrect .wpProQuiz_questionInput[type="text"],div#wpProQuiz_user_content .wpProQuiz_answerCorrect input[type="text"],div.wpProQuiz_content .wpProQuiz_answerCorrect .wpProQuiz_sortStringItem,.wpProQuiz_clozeCorrect,div#wpProQuiz_user_content .wpProQuiz_cloze.wpProQuiz_answerCorrect,div.wpProQuiz_content .wpProQuiz_response .wpProQuiz_correct > span,.wpProQuiz_modal_window div#wpProQuiz_user_content .wpProQuiz_answerCorrect,.leardash-course-status-completed{color:' . $ldx_color_correct . ';}';

			// Add things that need !important separately
			$css .= '.ld-quiz-progress-content-container [style*="green"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table th[style*="green"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table td[style*="green"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table thead th:nth-child(4){color:' . $ldx_color_correct . ' !important;}';

		}


		// Negative/Incorrect
		if( $ldx_color_incorrect != '' ) {

			// Text Color
			$css .= 'div.wpProQuiz_content .wpProQuiz_answerIncorrect,div.wpProQuiz_content .wpProQuiz_answerIncorrect .wpProQuiz_questionInput[type="text"],div#wpProQuiz_user_content .wpProQuiz_answerIncorrect input[type="text"],div.wpProQuiz_content .wpProQuiz_answerIncorrect .wpProQuiz_sortStringItem,div#wpProQuiz_user_content .wpProQuiz_cloze.wpProQuiz_answerIncorrect,div.wpProQuiz_content .wpProQuiz_response .wpProQuiz_incorrect > span,.wpProQuiz_modal_window div#wpProQuiz_user_content .wpProQuiz_answerIncorrect,.ld-error{color:' . $ldx_color_incorrect . ';}';

			// Add things that need !important separately
			$css .= '.ld-quiz-progress-content-container [style*="red"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table th[style*="red"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table td[style*="red"],.wpProQuiz_modal_window div#wpProQuiz_user_content .wp-list-table thead th:nth-child(5){color:' . $ldx_color_incorrect . ' !important;}';

		}


		// Link Color
		$ldx_color_link = get_theme_mod( 'ldx_color_link', '#2196f3' );
		$ldx_color_link_hover = get_theme_mod( 'ldx_color_link_hover', '#1565c0' );

		$css .= 'div#learndash_lessons h4 > a,div#learndash_quizzes h4 > a,body .expand_collapse a,div#learndash_profile .expand_collapse a,div#learndash_course_content .expand_collapse a,div#learndash_profile .expand_collapse a,#learndash_course_content .learndash_topic_dots ul > li a,div#learndash_lesson_topics_list ul > li > span a,div#learndash_profile .learndash-course-link a,div.course_navigation .widget_course_return a,#course_navigation .widget_course_return a,div#learndash_profile .profile_edit_profile a,div#learndash_profile .quiz_title a { color:' . $ldx_color_link . ';}';


		// Link Hover Color (should match selectors above)
		// Include both :hover and :active states
		if( $ldx_color_link_hover != '' ) {

			$css .= 'div#learndash_lessons h4 > a:hover,div#learndash_lessons h4 > a:active,div#learndash_quizzes h4 > a:hover,div#learndash_quizzes h4 > a:active,body .expand_collapse a:hover,body .expand_collapse a:active,div#learndash_profile .expand_collapse a:hover,div#learndash_profile .expand_collapse a:active,div#learndash_course_content .expand_collapse a:hover,div#learndash_course_content .expand_collapse a:active,div#learndash_course_content .learndash_topic_dots ul > li a:hover,div#learndash_course_content .learndash_topic_dots ul > li a:active,div#learndash_lesson_topics_list ul > li > span a:hover,div#learndash_lesson_topics_list ul > li > span a:active,div#learndash_profile .learndash-course-link a:hover,div#learndash_profile .learndash-course-link a:active,div.course_navigation .widget_course_return a:hover,div.course_navigation .widget_course_return a:active,#course_navigation .widget_course_return a:hover,#course_navigation .widget_course_return a:active,div#learndash_profile .profile_edit_profile a:hover,div#learndash_profile .profile_edit_profile a:active,div#learndash_profile .quiz_title a:hover,div#learndash_profile .quiz_title a:active { color:' . $ldx_color_link_hover . ';}';

		}


		// Course Content Table: Item Padding

		$ldx_list_table_item_padding = get_theme_mod( 'ldx_list_table_item_padding', '' );

		if( $ldx_list_table_item_padding === 'medium' ) {

			// Lesson Rows
			// !important needed bc uo_dashboard adds !important in its stylesheet
			$css .= '.learndash div#lessons_list>div h4>a,.learndash div#course_list>div h4>a,.learndash div#quiz_list>div h4>a,.single-sfwd-lessons .learndash #learndash_lesson_topics_list ul>li>span a,.singular-sfwd-lessons .learndash #learndash_lesson_topics_list ul>li>span a{padding:0.75em 60px 0.75em 0.75em !important;}';
			// Topic Rows
			$css .= 'div#learndash_course_content .learndash_topic_dots ul>li a{padding: 0.75em 60px 0.75em 1.75em;}';
			// Headings
			$css .= 'body div#learndash_lessons div#lesson_heading,body div#learndash_quizzes div#quiz_heading, body .learndash div#learndash_lesson_topics_list div>strong{padding:0.75em;}';
			// List Count Numbers
			$css .= 'div#lessons_list .list-count,div#quiz_list .list-count{padding-top:0.75em;}';

		} elseif ( $ldx_list_table_item_padding === 'large' ) {

			// Lesson Rows
			$css .= '.learndash div#lessons_list>div h4>a,.learndash div#course_list>div h4>a,.learndash div#quiz_list>div h4>a,.single-sfwd-lessons .learndash #learndash_lesson_topics_list ul>li>span a,.singular-sfwd-lessons .learndash #learndash_lesson_topics_list ul>li>span a{padding:1em 60px 1em 1em !important;}';
			// Topic Rows
			$css .= 'div#learndash_course_content .learndash_topic_dots ul>li a{padding:1em 60px 1em 2em;}';
			// Headings
			$css .= 'body div#learndash_lessons div#lesson_heading,body div#learndash_quizzes div#quiz_heading, body .learndash div#learndash_lesson_topics_list div>strong{padding:1em;}';
			// List Count Numbers
			$css .= 'div#lessons_list .list-count,div#quiz_list .list-count{padding-top:1em;}';

		} else {

			// do nothing (applies default padding from main stylesheet)

		}


		// Course Content Table: Border Width & Color

		$ldx_list_table_border_width = get_theme_mod( 'ldx_list_table_border_width', '' );
		$ldx_color_list_table_border = get_theme_mod( 'ldx_color_list_table_border', '' );
		$ldx_list_table_border_radius = get_theme_mod( 'ldx_list_table_border_radius', '' );
		
		if( $ldx_list_table_border_width != '' && $ldx_color_list_table_border != '' ) {

			// Remove line separator from last item in table
			// NOTE: Doesn't work for Topics Lists bc of LD markup
			//       This only removes them from lesson & quiz lists
			$css .= '#learndash_lessons>div>div:last-of-type,#learndash_quizzes>div>div:last-of-type,div#learndash_lesson_topics_list ul>li:last-of-type>span a{border-bottom:0;}';

			// AND... apply border-radius to bottom edges of last item
			$css .= '#learndash_lessons>div>div:last-of-type a,#learndash_quizzes>div>div:last-of-type a,div#learndash_lesson_topics_list ul>li:last-of-type>span a,div#learndash_lesson_topics_list ul>li:last-of-type>span.topic_item{border-bottom-left-radius:' . $ldx_list_table_border_radius . 'px;border-bottom-right-radius:' . $ldx_list_table_border_radius . 'px;}';

			// Add Border to Course Content Lists
			$css .= '.learndash #learndash_lessons,.learndash #learndash_quizzes,.learndash div#learndash_lesson_topics_list > div{border:' . $ldx_list_table_border_width . 'px solid ' . $ldx_color_list_table_border . ';}';

			// Remove Border from [uo_dashboard] output
			$css .= '.learndash.dashboard #learndash_lessons,.learndash.dashboard #learndash_quizzes{border:0;}';

		}


		// Border Radius
		if( $ldx_list_table_border_radius != '' ) {

			// Containers
			$css .= '.learndash #learndash_lessons,.learndash #learndash_quizzes,.learndash #learndash_profile,.learndash div#learndash_lesson_topics_list > div{border-radius:' . $ldx_list_table_border_radius . 'px;}';

			// Table Headers
			$css .= '.learndash div#learndash_lessons div#lesson_heading,.learndash div#learndash_quizzes div#quiz_heading,body div.learndash div#learndash_lesson_topics_list div > strong{border-top-left-radius:calc(' . $ldx_list_table_border_radius . 'px - 1px);border-top-right-radius:calc(' . $ldx_list_table_border_radius . 'px - 1px);}';

			// Last Lesson Row
			// Adds the border radius to the last lesson item
			// Comment out for now bc it looks weird when the "list count" is still being used.
			// $css .= '#lessons_list > div:last-child, #course_list > div:last-child, #quiz_list > div:last-child, #learndash_lesson_topics_list ul > li:last-child, #lessons_list > div:last-child a, #course_list > div:last-child a, #quiz_list > div:last-child a, #learndash_lesson_topics_list ul > li:last-child > span a{border-radius:0 0 ' . $ldx_list_table_border_radius . 'px ' . $ldx_list_table_border_radius . 'px;}';

		}


		// List Table Background Color
		$ldx_color_list_table_bg = get_theme_mod( 'ldx_color_list_table_bg', '' );

		if( $ldx_color_list_table_bg != '' ) {	

			$css .= '#learndash_lessons,#learndash_quizzes,#learndash_lesson_topics_list > div{background-color:' . $ldx_color_list_table_bg . ';}';

		}


		// List Table Header Background Color
		$ldx_color_list_table_header_bg = get_theme_mod( 'ldx_color_list_table_header_bg', '' );

		if( $ldx_color_list_table_header_bg != '' ) {

			$css .= 'body div#learndash_lessons div#lesson_heading,body div#learndash_quizzes div#quiz_heading,body .learndash div#learndash_lesson_topics_list div > strong,body div#learndash_profile .learndash_profile_heading:nth-of-type(2),body div#learndash_profile .profile_info + .learndash_profile_heading{background-color:' . $ldx_color_list_table_header_bg . ';}';

		}


		// List Table Header Text Color
		$ldx_color_list_table_header_text = get_theme_mod( 'ldx_color_list_table_header_text', '' );

		if( $ldx_color_list_table_header_text != '' ) {

			$css .= 'body div#learndash_lessons div#lesson_heading,body div#learndash_profile div.learndash_profile_heading,body div#learndash_quizzes div#quiz_heading,body .learndash div#learndash_lesson_topics_list div > strong {color:' . $ldx_color_list_table_header_text . ';}';

		}


		// List Table Line Separator Color
		$ldx_list_table_line_separator_color = get_theme_mod( 'ldx_list_table_line_separator_color', '' );

		if( $ldx_list_table_line_separator_color != '' ) {

			// Bottom Border Separators on Lessons
			$css .= '#learndash_lessons > div > div,#learndash_quizzes > div > div,#learndash_lesson_topics_list ul > li > span a{border-bottom:1px solid ' . $ldx_list_table_line_separator_color . ';}';

			// Remove Separator on :last-child Lesson/Topic?
			// Design looks better with separator on the last item
			// $css .= '#learndash_lessons #lessons_list > div:last-child, #course_list > div:last-child, #learndash_quizzes #quiz_list > div:last-child, #learndash_lessons #lessons_list > div:last-child a, #course_list > div:last-child a, #learndash_quizzes #quiz_list > div:last-child a, #learndash_lesson_topics_list ul > li:last-child > span a{border-bottom:0;}';

			// Top Border Separators on Topics
			$css .= '#learndash_course_content .learndash_topic_dots ul > li a{border-top:1px solid ' . $ldx_list_table_line_separator_color . ';}';

		}


		// Text/Link Color
		$ldx_color_list_table_text = get_theme_mod( 'ldx_color_list_table_text', '' );
		$ldx_color_list_table_text_hover = get_theme_mod( 'ldx_color_list_table_text_hover', '' );

		if( $ldx_color_list_table_text != '' ) {

			// Add color to <a> tags & .list-count numbers
			// These need to be more specific than the general link colors above
			// Uncanny Toolkit [uo_dashboard] compat:
			// --> div#learndash_profile.dashboard a
			$css .= '#learndash_course_content div#learndash_lessons h4 > a,div#learndash_quizzes #quiz_list h4 > a,div#learndash_lesson_topics_list ul > li > span a,div#learndash_lesson_topics_list ul > li > .topic_item a span,div#learndash_course_content .learndash_topic_dots ul > li a,div#learndash_course_content .learndash_topic_dots ul > li a span,div#lessons_list .list-count,div#quiz_list .list-count,div#learndash_profile.dashboard a,div#learndash_course_content .learndash_topic_dots a > span{color:' . $ldx_color_list_table_text . ';}';

		}

		// Text/Link Hover Color
		if( $ldx_color_list_table_text_hover != '' ) {

			$css .= '#learndash_course_content div#learndash_lessons h4 > a:hover,div#learndash_quizzes #quiz_list h4 > a:hover,div#learndash_lesson_topics_list ul > li > span a:hover,div#learndash_lesson_topics_list ul > li > span a:hover span,div#learndash_course_content .learndash_topic_dots ul > li a:hover,div#learndash_course_content .learndash_topic_dots ul > li a:hover span,#learndash_course_content div#learndash_lessons h4 > a:active,div#learndash_quizzes #quiz_list h4 > a:active,div#learndash_lesson_topics_list ul > li > span a:active,div#learndash_course_content .learndash_topic_dots ul > li a:active,#learndash_course_content div#learndash_lessons h4 > a:focus,div#learndash_quizzes #quiz_list h4 > a:focus,div#learndash_lesson_topics_list ul > li > span a:focus,div#learndash_course_content .learndash_topic_dots ul > li a:focus,div#learndash_profile.dashboard a:hover,div#learndash_profile.dashboard a:active,div#learndash_profile.dashboard a:focus{color:' . $ldx_color_list_table_text_hover . ';}';

		}


		// List Table Row Colors
		$ldx_color_rows_alt = get_theme_mod( 'ldx_color_rows_alt', '' );
		$ldx_color_rows_hover = get_theme_mod( 'ldx_color_rows_hover', '' );


		// Alternating Rows
		if( $ldx_color_rows_alt != '' ) {

			// Add background color to lesson rows
			$css .= 'div#learndash_lesson_topics_list ul > li:nth-of-type(even),div#lessons_list > div:nth-of-type(even),div#quiz_list > div:nth-of-type(even),div.learndash_profile_quizzes > div:nth-of-type(even){background-color:' . $ldx_color_rows_alt . ';}';

			// Add transparent black overlay to topics
			// Need a better solution for this
			// $css .= '#learndash_course_content .learndash_topic_dots ul > li:nth-of-type(odd) a{background: rgba(255,255,255,1);}';

		}


		// Row Hover Color
		if( $ldx_color_rows_hover != '' ) {

			$css .= '#learndash_course_content div#learndash_lessons h4 > a:hover,div#learndash_quizzes #quiz_list h4 > a:hover,div#learndash_lesson_topics_list ul > li > span.topic_item:hover,div#learndash_course_content .learndash_topic_dots ul > li a:hover{background-color:' . $ldx_color_rows_hover . ';}';

		}


		// Remove "Course Content" Header Text
		$ldx_list_table_remove_course_content_header = get_theme_mod( 'ldx_list_table_remove_course_content_header', '' );

		if( $ldx_list_table_remove_course_content_header === true ) {

			$css .= '#learndash_course_content_title{display:none;}#learndash_course_content{margin-top:3em;}';

		}

		// Remove List Count
		$ldx_list_table_remove_number_count = get_theme_mod( 'ldx_list_table_remove_number_count', '' );

		if( $ldx_list_table_remove_number_count === true ) {

			$css .= '#lessons_list .list-count,#quiz_list .list-count{display:none;}';

		}


		// Disable Expand/Collapse
		// Always shows all lesson, topic & quiz rows

		$ldx_list_table_disable_expand_collapse = get_theme_mod( 'ldx_list_table_disable_expand_collapse', '' );
			

		if( $ldx_list_table_disable_expand_collapse === true ) {

			// Expand all topics by default
			$css .= '.learndash_course_content #learndash_lessons .learndash_topic_dots,#learndash_course_content #learndash_lessons .learndash_topic_dots{display:block;}';

			// Hide the expand/collapse links
			$css .= '.learndash_course_content .expand_collapse,#learndash_course_content .expand_collapse{display:none;}';

		}


		// Remove Status Checkmarks
		$ldx_list_table_remove_status = get_theme_mod( 'ldx_list_table_remove_status', '' );
			

		if( $ldx_list_table_remove_status === true ) {

			// Hide icons
			$css .= '.learndash_lessons .notcompleted:after,.learndash_lessons .completed:after,#learndash_lessons .notcompleted:after,#learndash_lessons .completed:after,#learndash_course_content .learndash_topic_dots ul>li .topic-notcompleted:after,#learndash_course_content .learndash_topic_dots ul>li .topic-completed:after,.learndash_quizzes .notcompleted:after,.learndash_quizzes .completed:after,.learndash_lesson_topics_list .topic-notcompleted:after,.learndash_lesson_topics_list .topic-completed:after{display:none;}';

			// Hide the "Status" column heading
			$css .= '#learndash_lessons #lesson_heading .right,#learndash_quizzes #quiz_heading .right{display:none;}';

		}


		// Progress Bar Variables
		$ldx_progress_bar_style = get_theme_mod( 'ldx_progress_bar_style', false );
		$ldx_progress_bar_container_bg = get_theme_mod( 'ldx_progress_bar_container_bg', '' );
		$ldx_progress_bar_bg = get_theme_mod( 'ldx_progress_bar_bg', '' );
		$ldx_progress_bar_border_radius = get_theme_mod( 'ldx_progress_bar_border_radius', '' );
		$ldx_progress_bar_height = get_theme_mod( 'ldx_progress_bar_height', '' );
		$ldx_progress_bar_animation = get_theme_mod( 'ldx_progress_bar_animation', false );
		$ldx_progress_bar_show_steps = get_theme_mod( 'ldx_progress_bar_show_steps', 'noshow' );


		/* Progress Bar Style (default, flat, striped) */
		if( $ldx_progress_bar_style === 'flat' ) {	

			$css .= 'dd.course_progress{box-shadow:none;}';

		} elseif( $ldx_progress_bar_style === 'striped' ) {

			$css .= 'dd.course_progress div.course_progress_blue,body div.wpProQuiz_content .wpProQuiz_time_limit .wpProQuiz_progress,dd.uo-course-progress div.course_progress{background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size:1rem 1rem;}';

		} else {

			// do nothing; default styles are used

		} // end if

		// Progress Bar Container Color
		if( $ldx_progress_bar_container_bg != '' ) {

			$css .= 'dd.course_progress{background-color:' . $ldx_progress_bar_container_bg . ';}';

		}

		// Progress Bar Color
		if( $ldx_progress_bar_bg != '' ) {	

			$css .= 'body dd.course_progress div.course_progress_blue,.site dd.course_progress div.course_progress_blue,body div.wpProQuiz_content .wpProQuiz_time_limit .wpProQuiz_progress{background-color:' . $ldx_progress_bar_bg . ';}';

		}

		// Progress Bar Border Radius
		if( $ldx_progress_bar_border_radius != '' ) {

			$css .= 'body dd.course_progress,body div.wpProQuiz_content .wpProQuiz_time_limit .wpProQuiz_progress{border-radius:' . $ldx_progress_bar_border_radius . 'px;}';

		}

		// Progress Bar Height
		if( $ldx_progress_bar_height != '' ) {	

			$css .= 'body dd.course_progress,body div.wpProQuiz_content .wpProQuiz_time_limit .wpProQuiz_progress{height:' . $ldx_progress_bar_height . 'px;}';

		}

		// Progress Bar Animation
		if( $ldx_progress_bar_animation === true ) {	

			$css .= 'div.course_progress_blue{animation: .5s ease .6s both ldx-progress-bar;}';

		}

		// Progress Bar: Show X out of Y Steps Completed
		if( $ldx_progress_bar_show_steps === 'below' || $ldx_progress_bar_show_steps === 'above' ) {	

			// Setup necessary, global CSS
			// Add margin to prevent overlap with widget title
			$css .= 'dd.course_progress{overflow:visible;';

			if( $ldx_progress_bar_show_steps === 'above' ) {

				$css .= 'margin-top:40px;';

			}

			// Hide steps from [ld_profile] so they don't overlap
			// with "XX% Complete" language.
			$css .= '}#learndash_profile dd.course_progress:after{display:none;}';

			$css .= 'dd.course_progress div.course_progress_blue{border-radius:' . $ldx_progress_bar_border_radius . 'px;}';

			// Start :after declaration
			$css .= 'dd.course_progress:after{position:absolute;content:attr(title);left:0;display:block;font-size:80%;padding:0 2px;';

			// BELOW progress bar
			if( $ldx_progress_bar_show_steps === 'below' ) {	

				$css .= 'bottom:-22px;';

			// ABOVE progress bar
			} elseif( $ldx_progress_bar_show_steps === 'above' ) {

				$css .= 'top:-22px;';

			} else {

				// do nothing; steps are hidden

			} // end IF statement for positioning

			$css .= '}';

		} // end $ldx_progress_bar_show_steps


		// Button Variables
		$ldx_btn_border_radius = get_theme_mod( 'ldx_button_border_radius', '' );
		$ldx_btn_primary_bg = get_theme_mod( 'ldx_button_primary_bg', '' );
		$ldx_btn_primary_text_color = get_theme_mod( 'ldx_button_primary_text_color', '' );
		$ldx_btn_standard_bg = get_theme_mod( 'ldx_button_standard_bg', '' );
		$ldx_btn_standard_text_color = get_theme_mod( 'ldx_button_standard_text_color', '' );

		// Button Border Radius
		if( $ldx_btn_border_radius != '' ) {	

			$css .= '.learndash .btn-blue,.learndash .btn-join,.learndash #btn-join,.learndash_checkout_buttons input.btn-join[type="button"],.learndash_checkout_button input[type="submit"],.learndash a#quiz_continue_link,.learndash #learndash_back_to_lesson a,#learndash_next_prev_link a.prev-link,#learndash_next_prev_link a.next-link,#sfwd-mark-complete #learndash_mark_complete_button,.learndash .wpProQuiz_button,.learndash .wpProQuiz_button[name="reShowQuestion"],.wpProQuiz_content .wpProQuiz_button2,.ld_course_grid .thumbnail.course a.btn-primary{border-radius:' . $ldx_btn_border_radius . 'px;}';

		}
		
		// Standard Buttons: Background & Text Color
		if( $ldx_btn_standard_bg != '' || $ldx_btn_standard_text_color != '' ) {

			// add standard button class/IDs
			$css .= 'body .learndash #learndash_back_to_lesson a,body .learndash #learndash_back_to_lesson a:hover,body .learndash #learndash_back_to_lesson a:active,body .learndash #learndash_back_to_lesson a:focus,.learndash #learndash_next_prev_link a.next-link,.learndash #learndash_next_prev_link a.prev-link,.learndash .wpProQuiz_button[name="reShowQuestion"],.learndash #learndash_next_prev_link a.next-link:hover,.learndash #learndash_next_prev_link a.prev-link:hover,.learndash .wpProQuiz_button[name="reShowQuestion"]:hover,.learndash #learndash_next_prev_link a.next-link:active,.learndash #learndash_next_prev_link a.prev-link:active,.learndash .wpProQuiz_button[name="reShowQuestion"]:active,.learndash #learndash_next_prev_link a.next-link:focus,.learndash #learndash_next_prev_link a.prev-link:focus,.learndash .wpProQuiz_button[name="reShowQuestion"]:focus,div.wpProQuiz_content .wpProQuiz_button2,div.wpProQuiz_content .wpProQuiz_button2:hover,div.wpProQuiz_content .wpProQuiz_button2:active,div.wpProQuiz_content .wpProQuiz_button2:focus,div.wpProQuiz_quiz .wpProQuiz_button,div.wpProQuiz_quiz .wpProQuiz_button:hover,div.wpProQuiz_quiz .wpProQuiz_button:focus,div.wpProQuiz_quiz .wpProQuiz_button:active{';

			if( $ldx_btn_standard_bg != '' ) {
				$css .= 'background-color:' . $ldx_btn_standard_bg . ';';
			}
			if( $ldx_btn_standard_text_color != '' ) {
				$css .= 'color:' . $ldx_btn_standard_text_color . ';';
			}

			$css .= '}';

		} // if either/or has a value

		// Primary Buttons: Background & Text Color
		if( $ldx_btn_primary_bg != '' || $ldx_btn_primary_text_color != '' ) {

			// add primary button class/IDs
			$css .= '.learndash .learndash_join_button .btn-join,.learndash .learndash_join_button #btn-join,.learndash #btn-join,.learndash #btn-join:hover,.learndash #btn-join:active,.learndash #btn-join:focus,.learndash_checkout_buttons input.btn-join[type="button"],.learndash_checkout_button input[type="submit"],#sfwd-mark-complete #learndash_mark_complete_button,.learndash .wpProQuiz_button,.learndash .learndash_join_button .btn-join:hover,.learndash .learndash_join_button #btn-join:hover,.learndash_checkout_buttons input.btn-join[type="button"]:hover,.learndash_checkout_button input[type="submit"]:hover,#sfwd-mark-complete #learndash_mark_complete_button:hover,.learndash .wpProQuiz_button:hover,.learndash .learndash_join_button .btn-join:active,.learndash .learndash_join_button #btn-join:active,.learndash_checkout_buttons input.btn-join[type="button"]:active,.learndash_checkout_button input[type="submit"]:active,#sfwd-mark-complete #learndash_mark_complete_button:active,.learndash .wpProQuiz_button:active,.learndash .learndash_join_button .btn-join:focus,.learndash .learndash_join_button #btn-join:focus,.learndash_checkout_buttons input.btn-join[type="button"]:focus,.learndash_checkout_button input[type="submit"]:focus,#sfwd-mark-complete #learndash_mark_complete_button:focus,.learndash .wpProQuiz_button:focus,form#sfwd-mark-complete input#learndash_mark_complete_button[disabled],.ld_course_grid .thumbnail.course a.btn-primary,.ld_course_grid .thumbnail.course a.btn-primary:hover,.ld_course_grid .thumbnail.course a.btn-primary:active,.ld_course_grid .thumbnail.course a.btn-primary:focus,.learndash .learndash_course_certificate .btn-blue,.learndash .learndash_course_certificate .btn-blue:visited,.learndash .learndash_course_certificate .btn-blue:hover,.learndash .learndash_course_certificate .btn-blue:active,.learndash .learndash_course_certificate .btn-blue:focus,.learndash .quiz_continue_link a#quiz_continue_link,.learndash .quiz_continue_link a#quiz_continue_link:hover,.learndash .quiz_continue_link a#quiz_continue_link:active,.learndash .quiz_continue_link a#quiz_continue_link:focus,.learndash .wpProQuiz_content .wpProQuiz_button[name="restartQuiz"],.learndash .wpProQuiz_content .wpProQuiz_button[name="restartQuiz"]:hover,.learndash .wpProQuiz_content .wpProQuiz_button[name="restartQuiz"]:active,.learndash .wpProQuiz_content .wpProQuiz_button[name="restartQuiz"]:focus,.learndash .wpProQuiz_content .wpProQuiz_button[name="next"],.learndash .wpProQuiz_content .wpProQuiz_button[name="next"]:hover,.learndash .wpProQuiz_content .wpProQuiz_button[name="next"]:active,.learndash .wpProQuiz_content .wpProQuiz_button[name="next"]:focus{';

			if( $ldx_btn_primary_bg != '' ) {
				$css .= 'background-color:' . $ldx_btn_primary_bg . ';';
			}
			if( $ldx_btn_primary_text_color != '' ) {
				$css .= 'color:' . $ldx_btn_primary_text_color . ';';
			}

			$css .= '}';

		} // if either/or has a value


		// Avatar Shape
		$ldx_profile_avatar_shape = get_theme_mod( 'ldx_profile_avatar_shape', '' );

		if( $ldx_profile_avatar_shape === 'rounded' ) {	

			// If a border radius is set on list table headings, use it
			if( $ldx_list_table_border_radius != '' ) {	

				$css .= 'div#learndash_profile .profile_info .profile_avatar img{ border-radius: ' . $ldx_list_table_border_radius . 'px;}';

			// if not, use an arbitrary 6px
			} else {

				$css .= 'div#learndash_profile .profile_info .profile_avatar img{ border-radius:6px;}';

			}

		} elseif( $ldx_profile_avatar_shape === 'circle' ) {

			$css .= 'div#learndash_profile .profile_info .profile_avatar img{ border-radius:50%;}';

		} else {

			// force square (some themes use circles)
			$css .= 'div#learndash_profile .profile_info .profile_avatar img{ border-radius:0;}';

		} // end if


		// Disable Expand/Collapse on Profile
		// Always shows all profile info & registered courses
		// COMPATIBILITY:
		// UO Toolkit uses the same selectors for [uo_dashboard],
		// but places expand_collapse OUTSIDE of #learndash_profile.
		// However, hiding .expand_collapse by itself also means this
		// affects course content tables.
		$ldx_profile_disable_expand_collapse = get_theme_mod( 'ldx_profile_disable_expand_collapse', '' );
			

		if( $ldx_profile_disable_expand_collapse === true ) {

			// Expand all topics by default
			$css .= '#learndash_profile .flip{display:block !important;}';

			// Hide the expand/collapse links & clickable arrows
			$css .= '#learndash_profile .expand_collapse,#learndash_profile .list_arrow{display:none;}';

		}


		// Hide Profile Info
		// Just show "registered courses" with the [ld_profile] shortcode
		$ldx_profile_hide_profile_info = get_theme_mod( 'ldx_profile_hide_profile_info', '' );
			

		if( $ldx_profile_hide_profile_info === true ) {

			// Hide the profile section & profile heading
			$css .= '#learndash_profile .expand_collapse + .learndash_profile_heading, #learndash_profile .profile_info{display:none;}';

		}

		// Hide "Earned Course Points"
		$ldx_profile_hide_course_points = get_theme_mod( 'ldx_profile_hide_course_points', '' );

		if( $ldx_profile_hide_course_points === true ) {

			// Hide it
			$css .= '#learndash_course_points_user_message{display:none;}';

		}

		// Hide "Edit Profile" Link
		$ldx_profile_hide_edit_profile_link = get_theme_mod( 'ldx_profile_hide_edit_profile_link', '' );

		if( $ldx_profile_hide_edit_profile_link === true ) {

			// Hide the profile section & profile heading
			$css .= '#learndash_profile .profile_edit_profile{display:none;}';

		}


		// Hide Quizzes in Profile
		$ldx_profile_hide_quizzes = get_theme_mod( 'ldx_profile_hide_quizzes', '' );
			

		if( $ldx_profile_hide_quizzes === true ) {

			// Hide all quiz areas within "Registered Courses" section
			$css .= '#learndash_profile .learndash_profile_quizzes{display:none;}';

		}

		// Hide Status Column & Icons
		$ldx_profile_hide_status_column = get_theme_mod( 'ldx_profile_hide_status_column', '' );

		if( $ldx_profile_hide_status_column === true ) {

			$css .= '.ld_profile_status,.learndash-course-status{display:none;}';

		}

		// Hide "Course Progress Overview" Text
		// Shown just above each progress bar in the "Registered Courses" section
		$ldx_profile_hide_progress_overview_text = get_theme_mod( 'ldx_profile_hide_progress_overview_text', '' );

		if( $ldx_profile_hide_progress_overview_text === true ) {

			$css .= '#learndash_profile .flip .course_overview_heading{display:none;}';

		}


		// Hide Topics Dots
		$ldx_topics_hide_topic_progress_dots = get_theme_mod( 'ldx_topics_hide_topic_progress_dots', '' );
			

		if( $ldx_topics_hide_topic_progress_dots === true ) {

			// Hide topic dots
			$css .= '.learndash .learndash_topic_dots.type-dots{display:none;}';

		}


		// Course Nav Widget: Show All, Disable Arrows
		$ldx_widget_coursenav_show_all = get_theme_mod( 'ldx_widget_coursenav_show_all', '' );

		if( $ldx_widget_coursenav_show_all === true ) {

			// Hide the flippable arrow
			$css .= '.learndash_navigation_lesson_topics_list>div>.list_arrow.flippable,.learndash_navigation_lesson_topics_list>div>.list_arrow.collapse{display:none;}';

			// Show all topics beneath each lesson
			// !important is needed bc LD adds inline display styles
			$css .= '.learndash_navigation_lesson_topics_list .learndash_topic_widget_list {display:block !important;}';

		}

		// Course Nav Widget: Item Padding

		$ldx_widget_coursenav_item_padding = get_theme_mod( 'ldx_widget_coursenav_item_padding', '' );

		if( $ldx_widget_coursenav_item_padding === 'medium' ) {

			$css .= '.learndash_navigation_lesson_topics_list .lesson a,.course_navigation a{padding-top:0.75em;padding-bottom:0.75em;}';
			$css .= '#course_navigation .learndash_navigation_lesson_topics_list .list_arrow{top:0.625em;}';

		} elseif ( $ldx_widget_coursenav_item_padding === 'large' ) {

			$css .= '.learndash_navigation_lesson_topics_list .lesson a,.course_navigation a{padding-top:1em;padding-bottom:1em;}';
			$css .= '#course_navigation .learndash_navigation_lesson_topics_list .list_arrow{top:0.9375em;}';

		} else {

			// do nothing (applies default padding from main stylesheet)

		}

		// Course Nav Widget: Default Text Color
		$ldx_widget_coursenav_text_color = get_theme_mod( 'ldx_widget_coursenav_text_color', '' );

		if( $ldx_widget_coursenav_text_color != '' ) {

			$css .= '.course_navigation .learndash_navigation_lesson_topics_list a,div#course_navigation a{color:' . $ldx_widget_coursenav_text_color . ';}';

		}

		// Course Nav Widget: Hover: Text & Background Color
		$ldx_widget_coursenav_text_hover_color = get_theme_mod( 'ldx_widget_coursenav_text_hover_color', '' );
		$ldx_widget_coursenav_background_hover_color = get_theme_mod( 'ldx_widget_coursenav_background_hover_color', '' );

		// Check if fields have value
		if( $ldx_widget_coursenav_text_hover_color != '' || $ldx_widget_coursenav_background_hover_color != '' ) {

			// Start CSS Declaration
			$css .= '.course_navigation .learndash_navigation_lesson_topics_list a:hover,.course_navigation .learndash_navigation_lesson_topics_list a:active,.course_navigation .learndash_navigation_lesson_topics_list a:focus,#course_navigation .learndash_navigation_lesson_topics_list a:hover,#course_navigation .learndash_navigation_lesson_topics_list a:active,#course_navigation .learndash_navigation_lesson_topics_list a:focus{';

			// Hover: Text Color
			if( $ldx_widget_coursenav_text_hover_color != '' ) {

				$css .= 'color:' . $ldx_widget_coursenav_text_hover_color . ';';

			}

			// Hover: Background Color
			if( $ldx_widget_coursenav_background_hover_color != '' ) {

				$css .= 'background-color:' . $ldx_widget_coursenav_background_hover_color . ';';

			}

			// Close CSS declaration
			$css .= '}';

		} // if fields have value

		// Course Nav Widget: Current Items (background & text color)
		$ldx_widget_coursenav_current_item_background_color = get_theme_mod( 'ldx_widget_coursenav_current_item_background_color', '' );
		$ldx_widget_coursenav_current_item_text_color = get_theme_mod( 'ldx_widget_coursenav_current_item_text_color', '' );

		// Check if fields have a value
		if( $ldx_widget_coursenav_current_item_background_color != '' || $ldx_widget_coursenav_current_item_text_color != '' ) {

			// Start CSS Declaration
			$css .= '.single-sfwd-lessons .learndash_navigation_lesson_topics_list .learndash-current-menu-item.lesson a,.single-sfwd-topic .learndash_navigation_lesson_topics_list .learndash-current-menu-item > .topic_item a,.single-sfwd-quiz .learndash_navigation_lesson_topics_list .learndash-current-menu-item.quiz-item a,.single-sfwd-lessons #course_navigation .learndash-current-menu-item.lesson a,.single-sfwd-topic #course_navigation .learndash-current-menu-item > .topic_item a,.single-sfwd-quiz #course_navigation .learndash-current-menu-item.quiz-item a{';

			// Current Item: Background Color
			if( $ldx_widget_coursenav_current_item_background_color != '' ) {

				$css .= 'background-color:' . $ldx_widget_coursenav_current_item_background_color . ';';

			}

			// Current Item: Text Color
			if( $ldx_widget_coursenav_current_item_text_color != '' ) {

				$css .= 'color:' . $ldx_widget_coursenav_current_item_text_color . ';';

			}

			// Close CSS declaration
			$css .= '}';

		}	// if fields have a value


		// Course Nav Widget: Lesson Styles
		
		// Setup all variables
		$ldx_widget_coursenav_lesson_top_spacing = get_theme_mod( 'ldx_widget_coursenav_lesson_top_spacing', '' );
		$ldx_widget_coursenav_lesson_indent = get_theme_mod( 'ldx_widget_coursenav_lesson_indent', '' );
		$ldx_widget_coursenav_lesson_bold = get_theme_mod( 'ldx_widget_coursenav_lesson_bold', '' );
		$ldx_widget_coursenav_lesson_text_color = get_theme_mod( 'ldx_widget_coursenav_lesson_text_color', '' );
		$ldx_widget_coursenav_lesson_bottom_border = get_theme_mod( 'ldx_widget_coursenav_lesson_bottom_border', '' );
		$ldx_widget_coursenav_lesson_border_color = get_theme_mod( 'ldx_widget_coursenav_lesson_border_color', '' );
		$ldx_widget_coursenav_lesson_border_radius = get_theme_mod( 'ldx_widget_coursenav_lesson_border_radius', '' );
		$ldx_widget_coursenav_lesson_background_color = get_theme_mod( 'ldx_widget_coursenav_lesson_background_color', '' );



		// Check if any one of these fields has a value
		if( $ldx_widget_coursenav_lesson_top_spacing != '' || $ldx_widget_coursenav_lesson_indent != '' || $ldx_widget_coursenav_lesson_bold != '' || $ldx_widget_coursenav_lesson_text_color != '' || $ldx_widget_coursenav_lesson_bottom_border !='' || $ldx_widget_coursenav_lesson_border_radius != '' || $ldx_widget_coursenav_lesson_background_color != '' ) {

			// Start CSS Declaration
			$css .= '.learndash_navigation_lesson_topics_list .lesson a,div#course_navigation .lesson a{';

			// Lesson Top Spacing
			if( $ldx_widget_coursenav_lesson_top_spacing != '' ) {

				$css .= 'margin-top:' . $ldx_widget_coursenav_lesson_top_spacing . 'px;';

			}

			// Lesson Indentation
			if( $ldx_widget_coursenav_lesson_indent != '' ) {	

				$css .= 'padding-left:' . $ldx_widget_coursenav_lesson_indent . 'px;';

			}

			// Lesson Font Weight: Bold?
			if( $ldx_widget_coursenav_lesson_bold != '' ) {

				$css .= 'font-weight:bold;';

			}

			// Lesson Text Color
			if( $ldx_widget_coursenav_lesson_text_color != '' ) {	

				$css .= 'color:' . $ldx_widget_coursenav_lesson_text_color . ';';

			}

			// Lesson Bottom Border
			if( $ldx_widget_coursenav_lesson_bottom_border != '' ) {	

				$css .= 'border-bottom:' . $ldx_widget_coursenav_lesson_bottom_border . 'px solid #ccc;';

			}

			// Lesson Border Color
			if( $ldx_widget_coursenav_lesson_border_color != '' ) {	

				$css .= 'border-color:' . $ldx_widget_coursenav_lesson_border_color . ';';

			}

			// Lesson Border Radius
			if( $ldx_widget_coursenav_lesson_border_radius != '' ) {	

				$css .= 'border-radius:' . $ldx_widget_coursenav_lesson_border_radius . 'px;';

			}

			// Lesson Background Color
			if( $ldx_widget_coursenav_lesson_background_color != '' ) {	

				$css .= 'background-color:' . $ldx_widget_coursenav_lesson_background_color . ';';

			}

			// Close CSS declaration
			$css .= '}';

		}

		// Lesson Top Spacing
		// No Margin on First Lesson
		if( $ldx_widget_coursenav_lesson_top_spacing != '' ) {

			$css .= '.learndash_navigation_lesson_topics_list > div:first-child .list_lessons .lesson a{margin-top:0;}';

		}

		// Course Nav Widget: Topic Line Separators (width & color)
		$ldx_widget_coursenav_topic_separator = get_theme_mod( 'ldx_widget_coursenav_topic_separator', '' );
		$ldx_widget_coursenav_topic_separator_color = get_theme_mod( 'ldx_widget_coursenav_topic_separator_color', '' );

		// Check if fields have a value
		if( $ldx_widget_coursenav_topic_separator != '' || $ldx_widget_coursenav_topic_separator_color != '' ) {

			// Start CSS Declaration
			$css .= '.learndash_navigation_lesson_topics_list .topic-completed, .learndash_navigation_lesson_topics_list .topic-notcompleted{';

			// Topic Separator: Width
			if( $ldx_widget_coursenav_topic_separator != '' ) {

				$css .= 'border-bottom:' . $ldx_widget_coursenav_topic_separator . 'px solid #ccc;';

			}

			// Topic Separator: Color
			if( $ldx_widget_coursenav_topic_separator_color != '' ) {

				$css .= 'border-color:' . $ldx_widget_coursenav_topic_separator_color . ';';

			}

			// Close CSS declaration
			$css .= '}';

		}	// if fields have a value


		// Course Nav Widget: Topic Indentation
		$ldx_widget_coursenav_topic_indent = get_theme_mod( 'ldx_widget_coursenav_topic_indent', '' );

		if( $ldx_widget_coursenav_topic_indent != '' ) {	

			// Indentation for Topics (1st level after Lessons)
			$css .= '.learndash_navigation_lesson_topics_list .topic-completed,.learndash_navigation_lesson_topics_list .topic-notcompleted{padding-left:' . $ldx_widget_coursenav_topic_indent . 'px;}';

		}

		// Course Nav Widget: Topic Quiz Indentation
		$ldx_widget_coursenav_topic_quiz_indent = get_theme_mod( 'ldx_widget_coursenav_topic_quiz_indent', '' );

		if( $ldx_widget_coursenav_topic_quiz_indent != '' ) {	

			// Indentation for Topic Quizzes (2nd level after Lessons)
			$css .= '.widget ul.learndash-topic-quiz-list a{padding-left:' . $ldx_widget_coursenav_topic_quiz_indent . 'px;}';

		}

		// Course Nav Widget: Remove Status Checkmarks
		$ldx_widget_coursenav_remove_status = get_theme_mod( 'ldx_widget_coursenav_remove_status', '' );

		if( $ldx_widget_coursenav_remove_status === true ) {

			$css .= '.learndash_navigation_lesson_topics_list .topic-completed,.learndash_navigation_lesson_topics_list .topic-notcompleted{background-size:0;}';

		}

		// Course Nav Widget: Hide "Return to Course" Link
		$ldx_widget_coursenav_hide_return_link = get_theme_mod( 'ldx_widget_coursenav_hide_return_link', '' );

		if( $ldx_widget_coursenav_hide_return_link === true ) {

			$css .= '#course_navigation .widget_course_return{display:none;}';

		}

		// Course Nav Widget: Move "Return to Course" Link to Top
		$ldx_widget_coursenav_return_link_top = get_theme_mod( 'ldx_widget_coursenav_return_link_top', '' );

		if( $ldx_widget_coursenav_return_link_top === true ) {

			$css .= '.widget_ldcoursenavigation #course_navigation .ld-course-navigation-widget-content-contaiiner{display:flex;flex-direction:column;}.widget_ldcoursenavigation #course_navigation .widget_course_return{order:-1;margin-top:0;margin-bottom:1em;}';

		}

		// Course Grid
		// Register Variables
		// Grid Items
		// #et-boc is added for support for the Divi plugin
		// $ldx_course_grid_item_animation = get_theme_mod( 'ldx_course_grid_item_animation', '' );
		$ldx_course_grid_item_border_width = get_theme_mod( 'ldx_course_grid_item_border_width', '' );
		$ldx_course_grid_item_border_color = get_theme_mod( 'ldx_course_grid_item_border_color', '' );
		$ldx_course_grid_item_border_radius = get_theme_mod( 'ldx_course_grid_item_border_radius', '' );
		$ldx_course_grid_item_shadow = get_theme_mod( 'ldx_course_grid_item_shadow', '' );
		$ldx_course_grid_item_btn_width = get_theme_mod( 'ldx_course_grid_item_btn_width', '' );
		$ldx_course_grid_item_bar_transparency = get_theme_mod( 'ldx_course_grid_item_bar_transparency', '' );
		$ldx_course_grid_item_hover_shadow = get_theme_mod( 'ldx_course_grid_item_hover_shadow', '' );
		$ldx_course_grid_item_hover_transform = get_theme_mod( 'ldx_course_grid_item_hover_transform', '' );
		// Grid Ribbons
		$ldx_course_grid_ribbon_position = get_theme_mod( 'ldx_course_grid_ribbon_position', '' );
		$ldx_course_grid_ribbon_border_radius = get_theme_mod( 'ldx_course_grid_ribbon_border_radius', '' );
		$ldx_course_grid_ribbon_default_bg_color = get_theme_mod( 'ldx_course_grid_ribbon_default_bg_color', '' );
		$ldx_course_grid_ribbon_default_text_color = get_theme_mod( 'ldx_course_grid_ribbon_default_text_color', '' );
		$ldx_course_grid_ribbon_enrolled_bg_color = get_theme_mod( 'ldx_course_grid_ribbon_enrolled_bg_color', '' );
		$ldx_course_grid_ribbon_enrolled_text_color = get_theme_mod( 'ldx_course_grid_ribbon_enrolled_text_color', '' );
		$ldx_course_grid_ribbon_free_bg_color = get_theme_mod( 'ldx_course_grid_ribbon_free_bg_color', '' );
		$ldx_course_grid_ribbon_free_text_color = get_theme_mod( 'ldx_course_grid_ribbon_free_text_color', '' );
		$ldx_course_grid_ribbon_custom_bg_color = get_theme_mod( 'ldx_course_grid_ribbon_custom_bg_color', '' );
		$ldx_course_grid_ribbon_custom_text_color = get_theme_mod( 'ldx_course_grid_ribbon_custom_text_color', '' );
		// Category Selector
		$ldx_course_grid_selector_width = get_theme_mod( 'ldx_course_grid_selector_width', '' );
		$ldx_course_grid_selector_hide_label = get_theme_mod( 'ldx_course_grid_selector_hide_label', '' );
		$ldx_course_grid_selector_bg_color = get_theme_mod( 'ldx_course_grid_selector_bg_color', '' );
		$ldx_course_grid_selector_border_radius = get_theme_mod( 'ldx_course_grid_selector_border_radius', '' );
		$ldx_course_grid_selector_padding = get_theme_mod( 'ldx_course_grid_selector_padding', '' );

		// Grid Items: Border & Shadow
		if( $ldx_course_grid_item_border_width != '' || $ldx_course_grid_item_border_color != '' || $ldx_course_grid_item_border_radius != '' || $ldx_course_grid_item_shadow === 'none' ) {

			// Start Declaration
			$css .= 'body .ld-course-list-content .ld_course_grid,body #ld_course_list .ld-course-list-items.row .ld_course_grid,#et-boc .ld-course-list-items.row .ld_course_grid{';

				// Grid Item: Border Width
				if( $ldx_course_grid_item_border_width != '' ) {	

					$css .= 'border-width:' . $ldx_course_grid_item_border_width . 'px;';

				}

				// Grid Item: Border Color
				if( $ldx_course_grid_item_border_color != '' ) {	

					$css .= 'border-color:' . $ldx_course_grid_item_border_color . ';';

				}

				// Grid Item: Border Radius
				if( $ldx_course_grid_item_border_radius != '' ) {	

					$css .= 'border-radius:' . $ldx_course_grid_item_border_radius . 'px;';

				}

				// Grid Item: Shadow
				if( $ldx_course_grid_item_shadow === 'none' ) {	

					$css .= 'box-shadow:none;';

				}

			// End Declaration for Border & Box Shadow properties
			$css .= '}';

		} // end IF any grid items have a value for border or box-shadow
		
		// Do nothing for "default". Inherits free plugin styles.

		// Grid Button: Width
		if( $ldx_course_grid_item_btn_width === 'full' ) {	

			$css .= '.ld_course_grid .thumbnail.course a.btn-primary{display:block;}';

		}
		// Do nothing for "inline". Inherits free plugin styles.

		// Grid Progress Bar: Transparency
		if( $ldx_course_grid_item_bar_transparency === true ) {

			$css .= '.ld_course_grid dd.course_progress{background-color:rgba(255,255,255,0.65);}';

		}

		// Grid Item Hover: Shadow
		if( $ldx_course_grid_item_hover_shadow === true || $ldx_course_grid_item_hover_transform === 'lift' || $ldx_course_grid_item_hover_transform === 'enlarge' ) {

			$css .= 'body .ld-course-list-items.row .ld_course_grid:hover,body #ld_course_list .ld-course-list-items.row .ld_course_grid:hover{';

				// Shadow
				if( $ldx_course_grid_item_hover_shadow === true ) {

					$css .= 'box-shadow:0 1px 4px rgba(0,0,0,0.09),0 6px 14px rgba(0,0,0,0.14);';

				}

				// Lift
				if( $ldx_course_grid_item_hover_transform === 'lift' ) {

					$css .= 'transform:translateY(-5px);';

				}

				// Enlarge
				if( $ldx_course_grid_item_hover_transform === 'enlarge' ) {

					$css .= 'transform:scale(1.04);';

				}

			$css .= '}';

		} // if it has a hover effect

		// Grid Ribbon: Position
		if( $ldx_course_grid_ribbon_position === 'top-left' ) {

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price{left:8px;right:unset;}';

		}
		if( $ldx_course_grid_ribbon_position === 'top-right' ) {

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price{right:8px;left:unset;}';

		}

		// Grid Ribbon: Border Radius
		if( $ldx_course_grid_ribbon_border_radius != '' ) {	

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price,#et-boc .ld_course_grid_price{border-radius:' . $ldx_course_grid_ribbon_border_radius . 'px;}';

		}

		// Grid Ribbon: Default Colors
		if( $ldx_course_grid_ribbon_default_bg_color != '' || $ldx_course_grid_ribbon_default_text_color != '' ) {	

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price{';

				// Background Color
				if( $ldx_course_grid_ribbon_default_bg_color != '' ) {
				
					$css .= 'background-color:' . $ldx_course_grid_ribbon_default_bg_color . ';';

				}

				// Text Color
				if( $ldx_course_grid_ribbon_default_text_color != '' ) {
				
					$css .= 'color:' . $ldx_course_grid_ribbon_default_text_color . ';';

				}

			$css .= '}';

		} // Grid Ribbon: Default Colors

		// Grid Ribbon: Enrolled Colors
		if( $ldx_course_grid_ribbon_enrolled_bg_color != '' || $ldx_course_grid_ribbon_enrolled_text_color != '' ) {	

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price.ribbon-enrolled,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price.ribbon-enrolled,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price.ribbon-enrolled{';

				// Background Color
				if( $ldx_course_grid_ribbon_enrolled_bg_color != '' ) {
				
					$css .= 'background-color:' . $ldx_course_grid_ribbon_enrolled_bg_color . ';';

				}

				// Text Color
				if( $ldx_course_grid_ribbon_enrolled_text_color != '' ) {
				
					$css .= 'color:' . $ldx_course_grid_ribbon_enrolled_text_color . ';';

				}

			$css .= '}';

		} // Grid Ribbon: Enrolled Colors

		// Grid Ribbon: Free Colors
		if( $ldx_course_grid_ribbon_free_bg_color != '' || $ldx_course_grid_ribbon_free_text_color != '' ) {	

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price.free,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price.free,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price.free{';

				// Background Color
				if( $ldx_course_grid_ribbon_free_bg_color != '' ) {
				
					$css .= 'background-color:' . $ldx_course_grid_ribbon_free_bg_color . ';';

				}

				// Text Color
				if( $ldx_course_grid_ribbon_free_text_color != '' ) {
				
					$css .= 'color:' . $ldx_course_grid_ribbon_free_text_color . ';';

				}

			$css .= '}';

		} // Grid Ribbon: Free Colors

		// Grid Ribbon: Custom Colors
		if( $ldx_course_grid_ribbon_custom_bg_color != '' || $ldx_course_grid_ribbon_custom_text_color != '' ) {	

			$css .= 'body .ld-course-list-content .ld_course_grid .thumbnail.course .ld_course_grid_price.custom,body #ld_course_list .ld_course_grid .thumbnail.course .ld_course_grid_price.custom,body #et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price.custom{';

				// Background Color
				if( $ldx_course_grid_ribbon_custom_bg_color != '' ) {
				
					$css .= 'background-color:' . $ldx_course_grid_ribbon_custom_bg_color . ';';

				}

				// Text Color
				if( $ldx_course_grid_ribbon_custom_text_color != '' ) {
				
					$css .= 'color:' . $ldx_course_grid_ribbon_custom_text_color . ';';

				}

			$css .= '}';

		} // Grid Ribbon: Custom Colors


		// Grid Category Selector: Hide Label
		if( $ldx_course_grid_selector_hide_label === true ) {	

			$css .= '#ld_course_categorydropdown label,#ld_lesson_categorydropdown label,#ld_topic_categorydropdown label{display:none;}';

		}

		// Check if any of these values are used:
		if( $ldx_course_grid_selector_width === 'inline' || $ldx_course_grid_selector_bg_color != '' || $ldx_course_grid_selector_border_radius != '' || $ldx_course_grid_selector_padding != '' ) {

			// Open CSS Statement
			$css .= 'body div#ld_course_categorydropdown,body div#ld_lesson_categorydropdown,body div#ld_topic_categorydropdown,body #et-boc #ld_course_categorydropdown,body #et-boc #ld_lesson_categorydropdown,body #et-boc #ld_topic_categorydropdown{';

			// Grid Category Selector: Width
			if( $ldx_course_grid_selector_width === 'inline' ) {	

				$css .= 'display:inline-block;';

			}

			// Grid Category Selector: Background Color
			if( $ldx_course_grid_selector_bg_color != '' ) {	

				$css .= 'background-color:' . $ldx_course_grid_selector_bg_color . ';';

			}

			// Grid Category Selector: Border Radius
			if( $ldx_course_grid_selector_border_radius != '' ) {	

				$css .= 'border-radius:' . $ldx_course_grid_selector_border_radius . 'px;';

			}

			// Grid Category Selector: Padding
			if( $ldx_course_grid_selector_padding != '' ) {	

				$css .= 'padding:' . $ldx_course_grid_selector_padding . 'px;';

			}

			// Close CSS Statement
			$css .= '}';

		} // end check on multiple grid cat selector values

		// Print CSS
		return $css;

	} else {

		/**
		 * Inline Styles - @LD3
		 *
		 * @since 2.0
		 */
		// Get options from serialized array; store in a variable
		$ldx3_option = get_option( 'ldx3_design_upgrade' );
		
		// Start Empty Variable
		$css = '';

		// START :ROOT
		// Place all CSS custom property changes here, within :root
		$css .= ':root{';

			/**
			 * GENERAL DESIGN
			 */
			if ( isset( $ldx3_option['global_border_radius'] ) && $ldx3_option['global_border_radius'] != '' ) {
				$css .= '--ldx-global-border-radius:' . $ldx3_option['global_border_radius'] . 'px;';
			}

			if ( !empty( $ldx3_option['color_link'] ) ) {
				$css .= '--ldx-color-link:' . $ldx3_option['color_link'] . ';';
			}

			if ( !empty( $ldx3_option['color_link_hover'] ) ) {
				$css .= '--ldx-color-link-hover:' . $ldx3_option['color_link_hover'] . ';';
			}

			if ( !empty( $ldx3_option['color_correct'] ) ) {
				$css .= '--ldx-color-correct:' . $ldx3_option['color_correct'] . ';';
			}

			if ( !empty( $ldx3_option['color_incorrect'] ) ) {
				$css .= '--ldx-color-incorrect:' . $ldx3_option['color_incorrect'] . ';';
			}

			if ( !empty( $ldx3_option['color_in_progress'] ) ) {
				$css .= '--ldx-color-in-progress:' . $ldx3_option['color_in_progress'] . ';';
			}

			/**
			 * BUTTONS
			 */
			if ( isset( $ldx3_option['btn_border_radius'] ) && $ldx3_option['btn_border_radius'] != '' ) {
				$css .= '--ldx-btn-border-radius:' . $ldx3_option['btn_border_radius'] . 'px;';
				$css .= '--lqc-button-border-radius:' . $ldx3_option['btn_border_radius'] . 'px;';
			}

			if ( !empty( $ldx3_option['btn_primary_bg_color'] ) ) {
				$css .= '--ldx-btn-primary-bg-color:' . $ldx3_option['btn_primary_bg_color'] . ';';
				$css .= '--lqc-button-primary-bg:' . $ldx3_option['btn_primary_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['btn_primary_text_color'] ) ) {
				$css .= '--ldx-btn-primary-text-color:' . $ldx3_option['btn_primary_text_color'] . ';';
				$css .= '--lqc-button-primary-text:' . $ldx3_option['btn_primary_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['btn_primary_bg_color_hover'] ) ) {
				$css .= '--ldx-btn-primary-bg-color-hover:' . $ldx3_option['btn_primary_bg_color_hover'] . ';';
				$css .= '--lqc-button-primary-bg-hover:' . $ldx3_option['btn_primary_bg_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['btn_primary_text_color_hover'] ) ) {
				$css .= '--ldx-btn-primary-text-color-hover:' . $ldx3_option['btn_primary_text_color_hover'] . ';';
				$css .= '--lqc-button-primary-text-hover:' . $ldx3_option['btn_primary_text_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['btn_standard_bg_color'] ) ) {
				$css .= '--ldx-btn-standard-bg-color:' . $ldx3_option['btn_standard_bg_color'] . ';';
				$css .= '--lqc-button-standard-bg:' . $ldx3_option['btn_standard_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['btn_standard_text_color'] ) ) {
				$css .= '--ldx-btn-standard-text-color:' . $ldx3_option['btn_standard_text_color'] . ';';
				$css .= '--lqc-button-standard-text:' . $ldx3_option['btn_standard_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['btn_standard_bg_color_hover'] ) ) {
				$css .= '--ldx-btn-standard-bg-color-hover:' . $ldx3_option['btn_standard_bg_color_hover'] . ';';
				$css .= '--lqc-button-standard-bg-hover:' . $ldx3_option['btn_standard_bg_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['btn_standard_text_color_hover'] ) ) {
				$css .= '--ldx-btn-standard-text-color-hover:' . $ldx3_option['btn_standard_text_color_hover'] . ';';
				$css .= '--lqc-button-standard-text-hover:' . $ldx3_option['btn_standard_text_color_hover'] . ';';
			}

			/**
			 * COURSE CONTENT LISTS / LIST TABLES
			 */
			if ( !empty( $ldx3_option['list_tables_course_content_header_bg'] ) ) {
				$css .= '--ldx-content-lists-course-content-bg-color:' . $ldx3_option['list_tables_course_content_header_bg'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_course_content_header_text'] ) ) {
				$css .= '--ldx-content-lists-course-content-text-color:' . $ldx3_option['list_tables_course_content_header_text'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_section_bg_color'] ) ) {
				$css .= '--ldx-content-lists-section-bg-color:' . $ldx3_option['list_tables_section_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_section_text_color'] ) ) {
				$css .= '--ldx-content-lists-section-text-color:' . $ldx3_option['list_tables_section_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_header_bg_color'] ) ) {
				$css .= '--ldx-content-lists-header-bg-color:' . $ldx3_option['list_tables_header_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_header_text_color'] ) ) {
				$css .= '--ldx-content-lists-header-text-color:' . $ldx3_option['list_tables_header_text_color'] . ';';
			}

			if ( isset( $ldx3_option['list_tables_lesson_border_width'] ) && $ldx3_option['list_tables_lesson_border_width'] != '' ) {
				$css .= '--ldx-content-lists-lesson-border-width:' . $ldx3_option['list_tables_lesson_border_width'] . 'px;';
			}

			if ( !empty( $ldx3_option['list_tables_lesson_bg_color'] ) ) {
				$css .= '--ldx-content-lists-lesson-bg-color:' . $ldx3_option['list_tables_lesson_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_lesson_text_color'] ) ) {
				$css .= '--ldx-content-lists-lesson-text-color:' . $ldx3_option['list_tables_lesson_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_lesson_border_color'] ) ) {
				$css .= '--ldx-content-lists-lesson-border-color:' . $ldx3_option['list_tables_lesson_border_color'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_lesson_bg_color_hover'] ) ) {
				$css .= '--ldx-content-lists-lesson-bg-color-hover:' . $ldx3_option['list_tables_lesson_bg_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_lesson_text_color_hover'] ) ) {
				$css .= '--ldx-content-lists-lesson-text-color-hover:' . $ldx3_option['list_tables_lesson_text_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['list_tables_line_separator_color'] ) ) {
				$css .= '--ldx-content-lists-separator-color:' . $ldx3_option['list_tables_line_separator_color'] . ';';
			}

			/**
			 * FOCUS MODE
			 */
			if ( !empty( $ldx3_option['focus_mode_content_bg_color'] ) ) {
				$css .= '--ldx-focus-mode-content-bg-color:' . $ldx3_option['focus_mode_content_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_sidebar_bg_color'] ) ) {
				$css .= '--ldx-focus-mode-sidebar-bg-color:' . $ldx3_option['focus_mode_sidebar_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_topmenu_bg_color'] ) ) {
				$css .= '--ldx-focus-mode-topmenu-bg-color:' . $ldx3_option['focus_mode_topmenu_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_topmenu_text_color'] ) ) {
				$css .= '--ldx-focus-mode-topmenu-text-color:' . $ldx3_option['focus_mode_topmenu_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_sidebar_course_bg'] ) ) {
				$css .= '--ldx-focus-mode-sidebar-course-bg-color:' . $ldx3_option['focus_mode_sidebar_course_bg'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_sidebar_course_text'] ) ) {
				$css .= '--ldx-focus-mode-sidebar-course-text-color:' . $ldx3_option['focus_mode_sidebar_course_text'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_comments_bg_color'] ) ) {
				$css .= '--ldx-focus-mode-comment-bg-color:' . $ldx3_option['focus_mode_comments_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['focus_mode_admin_comments_bg_color'] ) ) {
				$css .= '--ldx-focus-mode-comment-admin-bg-color:' . $ldx3_option['focus_mode_admin_comments_bg_color'] . ';';
			}

			if ( isset( $ldx3_option['focus_mode_admin_comments_border_width'] ) && $ldx3_option['focus_mode_admin_comments_border_width'] != '' ) {
				$css .= '--ldx-focus-mode-comment-admin-border-width:' . $ldx3_option['focus_mode_admin_comments_border_width'] . 'px;';
			}

			if ( !empty( $ldx3_option['focus_mode_admin_comments_border_color'] ) ) {
				$css .= '--ldx-focus-mode-comment-admin-border-color:' . $ldx3_option['focus_mode_admin_comments_border_color'] . ';';
			}

			if ( isset( $ldx3_option['focus_mode_admin_comments_avatar_border_width'] ) && $ldx3_option['focus_mode_admin_comments_avatar_border_width'] != '' ) {
				$css .= '--ldx-focus-mode-comment-admin-avatar-border-width:' . $ldx3_option['focus_mode_admin_comments_avatar_border_width'] . 'px;';
			}

			if ( !empty( $ldx3_option['focus_mode_admin_comments_avatar_border_color'] ) ) {
				$css .= '--ldx-focus-mode-comment-admin-avatar-border-color:' . $ldx3_option['focus_mode_admin_comments_avatar_border_color'] . ';';
			}

			/**
			 * COURSE NAVIGATION
			 */
			
			if ( !empty( $ldx3_option['coursenav_section_bg_color'] ) ) {
				$css .= '--ldx-course-nav-section-bg-color:' . $ldx3_option['coursenav_section_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['coursenav_section_text_color'] ) ) {
				$css .= '--ldx-course-nav-section-text-color:' . $ldx3_option['coursenav_section_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['coursenav_link_text_color'] ) ) {
				$css .= '--ldx-course-nav-link-text-color:' . $ldx3_option['coursenav_link_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['coursenav_link_bg_color_hover'] ) ) {
				$css .= '--ldx-course-nav-link-bg-color-hover:' . $ldx3_option['coursenav_link_bg_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['coursenav_link_text_color_hover'] ) ) {
				$css .= '--ldx-course-nav-link-text-color-hover:' . $ldx3_option['coursenav_link_text_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['coursenav_line_separator_color'] ) ) {
				$css .= '--ldx-course-nav-line-separator-color:' . $ldx3_option['coursenav_line_separator_color'] . ';';
			}

			/**
			 * COURSE PAGE / COURSE STATUS
			 */
			
			if ( !empty( $ldx3_option['course_status_bg_color'] ) ) {
				$css .= '--ldx-course-status-bg-color:' . $ldx3_option['course_status_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['course_status_text_color'] ) ) {
				$css .= '--ldx-course-status-text-color:' . $ldx3_option['course_status_text_color'] . ';';
			}

			if ( isset( $ldx3_option['course_status_border_width'] ) && $ldx3_option['course_status_border_width'] != '' ) {
				$css .= '--ldx-course-status-border-width:' . $ldx3_option['course_status_border_width'] . 'px;';
			}

			if ( !empty( $ldx3_option['course_status_border_color'] ) ) {
				$css .= '--ldx-course-status-border-color:' . $ldx3_option['course_status_border_color'] . ';';
			}

			/**
			 * PROGRESS BAR
			 */
			if ( !empty( $ldx3_option['progress_bar_container_bg'] ) ) {
				$css .= '--ldx-progress-bar-container-bg:' . $ldx3_option['progress_bar_container_bg'] . ';';
			}

			if ( !empty( $ldx3_option['progress_bar_bg'] ) ) {
				$css .= '--ldx-progress-bar-bg:' . $ldx3_option['progress_bar_bg'] . ';';
			}

			if ( isset( $ldx3_option['progress_bar_border_radius'] ) && $ldx3_option['progress_bar_border_radius'] != '' ) {
				$css .= '--ldx-progress-bar-border-radius:' . $ldx3_option['progress_bar_border_radius'] . 'px;';
			}

			if ( isset( $ldx3_option['progress_bar_height'] ) && $ldx3_option['progress_bar_height'] != '' ) {
				$css .= '--ldx-progress-bar-height:' . $ldx3_option['progress_bar_height'] . 'px;';
			}

			/**
			 * ALERTS
			 */
			if ( isset( $ldx3_option['alert_border_width'] ) && $ldx3_option['alert_border_width'] != '' ) {
				$css .= '--ldx-alert-border-width:' . $ldx3_option['alert_border_width'] . 'px;';
			}
			if ( !empty( $ldx3_option['alert_text_color'] ) ) {
				$css .= '--ldx-alert-color-text:' . $ldx3_option['alert_text_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_bg_color'] ) ) {
				$css .= '--ldx-alert-color-bg:' . $ldx3_option['alert_bg_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_border_color'] ) ) {
				$css .= '--ldx-alert-color-border:' . $ldx3_option['alert_border_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_warning_text_color'] ) ) {
				$css .= '--ldx-alert-warning-color-text:' . $ldx3_option['alert_warning_text_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_warning_bg_color'] ) ) {
				$css .= '--ldx-alert-warning-color-bg:' . $ldx3_option['alert_warning_bg_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_warning_border_color'] ) ) {
				$css .= '--ldx-alert-warning-color-border:' . $ldx3_option['alert_warning_border_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_success_text_color'] ) ) {
				$css .= '--ldx-alert-success-color-text:' . $ldx3_option['alert_success_text_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_success_bg_color'] ) ) {
				$css .= '--ldx-alert-success-color-bg:' . $ldx3_option['alert_success_bg_color'] . ';';
			}
			if ( !empty( $ldx3_option['alert_success_border_color'] ) ) {
				$css .= '--ldx-alert-success-color-border:' . $ldx3_option['alert_success_border_color'] . ';';
			}

			/**
			 * TOOLTIPS
			 */
			if ( !empty( $ldx3_option['tooltip_bg_color'] ) ) {
				$css .= '--ldx-tooltip-bg-color:' . $ldx3_option['tooltip_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['tooltip_text_color'] ) ) {
				$css .= '--ldx-tooltip-text-color:' . $ldx3_option['tooltip_text_color'] . ';';
			}

			/**
			 * LOGIN & REGISTRATION
			 */
			if ( !empty( $ldx3_option['log_reg_overlay_opacity'] ) ) {
				$css .= '--ldx-log-reg-overlay-opacity:' . $ldx3_option['log_reg_overlay_opacity'] . ';';
			}

			if ( !empty( $ldx3_option['log_reg_border_width'] ) ) {
				$css .= '--ldx-log-reg-border-width:' . $ldx3_option['log_reg_border_width'] . 'px;';
			}

			if ( !empty( $ldx3_option['log_reg_border_color'] ) ) {
				$css .= '--ldx-log-reg-border-color:' . $ldx3_option['log_reg_border_color'] . ';';
			}

			if ( !empty( $ldx3_option['log_reg_close_icon_color'] ) ) {
				$css .= '--ldx-log-reg-close-icon-color:' . $ldx3_option['log_reg_close_icon_color'] . ';';
			}

			if ( !empty( $ldx3_option['login_panel_bg_color'] ) ) {
				$css .= '--ldx-login-panel-bg-color:' . $ldx3_option['login_panel_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['login_panel_heading_color'] ) ) {
				$css .= '--ldx-login-panel-heading-color:' . $ldx3_option['login_panel_heading_color'] . ';';
			}

			if ( !empty( $ldx3_option['login_panel_text_color'] ) ) {
				$css .= '--ldx-login-panel-text-color:' . $ldx3_option['login_panel_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['login_panel_input_bg_color'] ) ) {
				$css .= '--ldx-login-panel-input-bg-color:' . $ldx3_option['login_panel_input_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['login_panel_input_text_color'] ) ) {
				$css .= '--ldx-login-panel-input-text-color:' . $ldx3_option['login_panel_input_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['register_panel_bg_color'] ) ) {
				$css .= '--ldx-register-panel-bg-color:' . $ldx3_option['register_panel_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['register_panel_heading_color'] ) ) {
				$css .= '--ldx-register-panel-heading-color:' . $ldx3_option['register_panel_heading_color'] . ';';
			}

			if ( !empty( $ldx3_option['register_panel_text_color'] ) ) {
				$css .= '--ldx-register-panel-text-color:' . $ldx3_option['register_panel_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['register_panel_input_bg_color'] ) ) {
				$css .= '--ldx-register-panel-input-bg-color:' . $ldx3_option['register_panel_input_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['register_panel_input_text_color'] ) ) {
				$css .= '--ldx-register-panel-input-text-color:' . $ldx3_option['register_panel_input_text_color'] . ';';
			}

			/**
			 * COURSE GRID
			 */
			if ( !empty( $ldx3_option['grid_selector_bg_color'] ) ) {
				$css .= '--ldx-grid-filter-bg-color:' . $ldx3_option['grid_selector_bg_color'] . ';';
			}

			if ( isset( $ldx3_option['grid_selector_padding'] ) && $ldx3_option['grid_selector_padding'] != '' ) {
				$css .= '--ldx-grid-filter-padding:' . $ldx3_option['grid_selector_padding'] . 'px;';
			}

			if ( isset( $ldx3_option['grid_item_border_width'] ) && $ldx3_option['grid_item_border_width'] != '' ) {
				$css .= '--ldx-grid-item-border-width:' . $ldx3_option['grid_item_border_width'] . 'px;';
			}

			if ( isset( $ldx3_option['grid_item_border_radius'] ) && $ldx3_option['grid_item_border_radius'] != '' ) {
				$css .= '--ldx-grid-item-border-radius:' . $ldx3_option['grid_item_border_radius'] . 'px;';
			}

			if ( !empty( $ldx3_option['grid_item_border_color'] ) ) {
				$css .= '--ldx-grid-item-border-color:' . $ldx3_option['grid_item_border_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_default_bg_color'] ) ) {
				$css .= '--ldx-grid-ribbon-bg-color:' . $ldx3_option['grid_ribbon_default_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_default_text_color'] ) ) {
				$css .= '--ldx-grid-ribbon-text-color:' . $ldx3_option['grid_ribbon_default_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_enrolled_bg_color'] ) ) {
				$css .= '--ldx-grid-ribbon-enrolled-bg-color:' . $ldx3_option['grid_ribbon_enrolled_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_enrolled_text_color'] ) ) {
				$css .= '--ldx-grid-ribbon-enrolled-text-color:' . $ldx3_option['grid_ribbon_enrolled_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_free_bg_color'] ) ) {
				$css .= '--ldx-grid-ribbon-free-bg-color:' . $ldx3_option['grid_ribbon_free_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_free_text_color'] ) ) {
				$css .= '--ldx-grid-ribbon-free-text-color:' . $ldx3_option['grid_ribbon_free_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_custom_bg_color'] ) ) {
				$css .= '--ldx-grid-ribbon-custom-bg-color:' . $ldx3_option['grid_ribbon_custom_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['grid_ribbon_custom_text_color'] ) ) {
				$css .= '--ldx-grid-ribbon-custom-text-color:' . $ldx3_option['grid_ribbon_custom_text_color'] . ';';
			}

			/**
			 * PAGINATION
			 */
			if ( isset( $ldx3_option['pagination_remove_background'] ) && $ldx3_option['pagination_remove_background'] === true ) {

				$css .= '--ldx-pagination-bg-color:transparent;';

			} else {

				if ( !empty( $ldx3_option['pagination_bg_color'] ) ) {
					$css .= '--ldx-pagination-bg-color:' . $ldx3_option['pagination_bg_color'] . ';';
				}

			} // pagination remove bg

			if ( !empty( $ldx3_option['pagination_text_color'] ) ) {
				$css .= '--ldx-pagination-text-color:' . $ldx3_option['pagination_text_color'] . ';';
			}

			if ( !empty( $ldx3_option['pagination_arrow_color'] ) ) {
				$css .= '--ldx-pagination-arrow-color:' . $ldx3_option['pagination_arrow_color'] . ';';
			}

			if ( !empty( $ldx3_option['pagination_arrow_color_hover'] ) ) {
				$css .= '--ldx-pagination-arrow-color-hover:' . $ldx3_option['pagination_arrow_color_hover'] . ';';
			}

			if ( !empty( $ldx3_option['pagination_arrow_bg_color'] ) ) {
				$css .= '--ldx-pagination-arrow-bg-color:' . $ldx3_option['pagination_arrow_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['pagination_arrow_bg_color_hover'] ) ) {
				$css .= '--ldx-pagination-arrow-bg-color-hover:' . $ldx3_option['pagination_arrow_bg_color_hover'] . ';';
			}

			/**
			 * PROFILE
			 */
			if ( !empty( $ldx3_option['profile_summary_bg_color'] ) ) {
				$css .= '--ldx-profile-summary-bg-color:' . $ldx3_option['profile_summary_bg_color'] . ';';
			}

			if ( !empty( $ldx3_option['profile_summary_text_color'] ) ) {
				$css .= '--ldx-profile-summary-text-color:' . $ldx3_option['profile_summary_text_color'] . ';';
			}

			/**
			 * ACHIEVEMENTS ADD-ON
			 */
			if ( isset( $ldx3_option['achievements_popup_border_width'] ) && $ldx3_option['achievements_popup_border_width'] != '' ) {
				$css .= '--ldx-achievements-popup-border-width:' . $ldx3_option['achievements_popup_border_width'] . 'px;';
			}
			if ( !empty( $ldx3_option['achievements_popup_border_color'] ) ) {
				$css .= '--ldx-achievements-popup-border-color:' . $ldx3_option['achievements_popup_border_color'] . ';';
			}
			if ( isset( $ldx3_option['achievements_popup_border_radius'] ) && $ldx3_option['achievements_popup_border_radius'] != '' ) {
				$css .= '--ldx-achievements-popup-border-radius:' . $ldx3_option['achievements_popup_border_radius'] . 'px;';
			}

			if ( isset( $ldx3_option['achievements_popup_title_fontsize'] ) && $ldx3_option['achievements_popup_title_fontsize'] != '' ) {
				$css .= '--ldx-achievements-popup-title-font-size:' . $ldx3_option['achievements_popup_title_fontsize'] . 'px;';
			}
			if ( !empty( $ldx3_option['achievements_popup_title_color'] ) ) {
				$css .= '--ldx-achievements-popup-title-color:' . $ldx3_option['achievements_popup_title_color'] . ';';
			}

			if ( isset( $ldx3_option['achievements_popup_message_fontsize'] ) && $ldx3_option['achievements_popup_message_fontsize'] != '' ) {
				$css .= '--ldx-achievements-popup-message-font-size:' . $ldx3_option['achievements_popup_message_fontsize'] . 'px;';
			}
			if ( !empty( $ldx3_option['achievements_popup_message_color'] ) ) {
				$css .= '--ldx-achievements-popup-message-color:' . $ldx3_option['achievements_popup_message_color'] . ';';
			}

			if ( isset( $ldx3_option['my_achievements_icon_width'] ) && $ldx3_option['my_achievements_icon_width'] != '' ) {
				$css .= '--ldx-my-achievements-icon-width:' . $ldx3_option['my_achievements_icon_width'] . 'px;';
			}

			/**
			 * WISDMLABS: RATINGS, REVIEWS & FEEDBACK
			 */
			if ( defined( 'WDM_LD_COURSE_VERSION' ) ) {

				if ( !empty( $ldx3_option['rating_star_empty_color'] ) ) {
					$css .= '--ldx-star-rating-color-empty:' . $ldx3_option['rating_star_empty_color'] . ';';
				}

				if ( !empty( $ldx3_option['rating_star_filled_color'] ) ) {
					$css .= '--ldx-star-rating-color-filled:' . $ldx3_option['rating_star_filled_color'] . ';';
				}

			} // if WDM_LD_COURSE_VERSION

			/**
			 * UNCANNY OWL: TIN CANNY REPORTING
			 */
			if ( defined( 'UO_REPORTING_FILE' ) ) {

				if ( isset( $ldx3_option['tincanny_container_border_width'] ) && $ldx3_option['tincanny_container_border_width'] != '' ) {
					$css .= '--ldx-tincanny-container-border-width:' . $ldx3_option['tincanny_container_border_width'] . 'px;';
				}

				if ( !empty( $ldx3_option['tincanny_container_border_color'] ) ) {
					$css .= '--ldx-tincanny-container-border-color:' . $ldx3_option['tincanny_container_border_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_container_header_bg_color'] ) ) {
					$css .= '--ldx-tincanny-container-header-bg-color:' . $ldx3_option['tincanny_container_header_bg_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_container_header_text_color'] ) ) {
					$css .= '--ldx-tincanny-container-header-text-color:' . $ldx3_option['tincanny_container_header_text_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_table_header_bg_color'] ) ) {
					$css .= '--ldx-tincanny-table-header-bg-color:' . $ldx3_option['tincanny_table_header_bg_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_table_header_text_color'] ) ) {
					$css .= '--ldx-tincanny-table-header-text-color:' . $ldx3_option['tincanny_table_header_text_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_table_row_hover_bg_color'] ) ) {
					$css .= '--ldx-tincanny-table-row-hover-color:' . $ldx3_option['tincanny_table_row_hover_bg_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_chart_course_completions_color'] ) ) {
					$css .= '--ldx-tincanny-chart-course-completions-color:' . $ldx3_option['tincanny_chart_course_completions_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_chart_tincan_statements_color'] ) ) {
					$css .= '--ldx-tincanny-chart-tincan-statements-color:' . $ldx3_option['tincanny_chart_tincan_statements_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_user_report_tab_bg_color'] ) ) {
					$css .= '--ldx-tincanny-user-report-tab-bg-color:' . $ldx3_option['tincanny_user_report_tab_bg_color'] . ';';
				}

				if ( !empty( $ldx3_option['tincanny_user_report_tab_text_color'] ) ) {
					$css .= '--ldx-tincanny-user-report-tab-text-color:' . $ldx3_option['tincanny_user_report_tab_text_color'] . ';';
				}

			} // if UO_REPORTING_FILE

		$css .= '}';
		// END :ROOT


		/**
		 * GENERAL DESIGN
		 */

		if ( !empty( $ldx3_option['color_link'] ) ) {
				$css .= '.learndash-wrapper .ld-expand-button.ld-button-alternate .ld-icon{';
				$css .= 'background-color: var(--ldx-color-link);';
				$css .= '}';
			}

		/**
		 * LIST TABLES
		 */
		
		// Disable Expand/Collapse
		if ( isset( $ldx3_option['list_tables_disable_expand_collapse'] ) && $ldx3_option['list_tables_disable_expand_collapse'] === true ) {

			$css .= '.learndash-wrapper .ld-lesson-list .ld-item-list-item .ld-item-list-item-expanded{';
			$css .= 'max-height:unset;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-lesson-list .ld-expand-button{';
			$css .= 'display:none;';
			$css .= '}';

			$css .= '@media (max-width: 640px){';
			$css .= '.learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview .ld-item-details{';
			$css .= 'margin:0;';
			$css .= 'padding:0;';
			$css .= '}';
			$css .= '}';

		}

		// "Course Content" Header
		if ( isset( $ldx3_option['list_tables_course_content_header'] ) ) {

			// Set color, no matter what style is used
			$css .= '.ld-lesson-list .ld-section-heading,';
			$css .= '.ld-lesson-list .ld-section-heading h2{'; // @astra
			$css .= 'color:var(--ldx-content-lists-course-content-text-color);';
			$css .= '}';

			// If "boxed" style...
			if ( $ldx3_option['list_tables_course_content_header'] === 'boxed' ) {

				$css .= '.ld-lesson-list .ld-section-heading{';
				$css .= 'padding:0.75em 0.75em 0.75em 1.25em;';
				$css .= 'border-radius:var(--ldx-global-border-radius);';
				$css .= 'background-color:var(--ldx-content-lists-course-content-bg-color);';
				$css .='}';

				// @RTL
				$css .= '.rtl .ld-lesson-list .ld-section-heading{';
				$css .= 'padding:0.75em 1.25em 0.75em 0.75em;';
				$css .='}';

			} // end if 'boxed'

			if ( $ldx3_option['list_tables_course_content_header'] === 'hidden' ) {

				$css .= '.learndash-wrapper .ld-lesson-list .ld-section-heading h2{';
				$css .= 'display:none;';
				$css .= '}';

				$css .= '.learndash-wrapper .ld-lesson-list .ld-section-heading .ld-item-list-actions{';
				$css .= 'margin-left:auto;';
				$css .= '}';
				// @RTL
				$css .= '.rtl .learndash-wrapper .ld-lesson-list .ld-section-heading .ld-item-list-actions{';
				$css .= 'margin-right:auto;';
				$css .= '}';

			} // end if 'hidden'

		} // end "course content" header

		// List Table Container Style
		if ( isset( $ldx3_option['list_tables_container_style'] ) && $ldx3_option['list_tables_container_style'] === 'boxed' ) {

			$css .= '.ld-lesson-list > .ld-item-list-items{';
			$css .= 'padding:0.75em 0.75em 1px 0.75em;';
			$css .= 'border-radius:var(--ldx-global-border-radius);';

			// Background Color
			if ( !empty( $ldx3_option['list_tables_container_bg_color'] ) ) {

				$css .= 'background-color:' . $ldx3_option['list_tables_container_bg_color'] . ';';

			}

			// Border Width
			if ( isset( $ldx3_option['list_tables_container_border_width'] ) && $ldx3_option['list_tables_container_border_width'] != '' ) {

				$css .= 'border-style:solid;';
				$css .= 'border-width:' . $ldx3_option['list_tables_container_border_width'] . 'px;';

			}

			// Border Color
			if ( !empty( $ldx3_option['list_tables_container_border_color'] ) ) {

				$css .= 'border-color:' . $ldx3_option['list_tables_container_border_color'] . ';';

			}

			// If "Course Content" header is 'boxed'
			// Removed top border & border radius from container
			if ( isset( $ldx3_option['list_tables_course_content_header'] ) && $ldx3_option['list_tables_course_content_header'] === 'boxed' ) {

				$css .= 'border-top:0;';
				$css .= 'border-top-left-radius:0;';
				$css .= 'border-top-right-radius:0;';

			}

			// If "Course Content" header is NOT 'boxed'
			// Add a top margin to the container
			if ( isset( $ldx3_option['list_tables_course_content_header'] ) && $ldx3_option['list_tables_course_content_header'] != 'boxed' ) {

				$css .= 'margin-top:1em;';

			}
			
			$css .= '}'; // .ld-lesson-list .ld-item-list-items

			$css .= '.learndash-wrapper .ld-item-list.ld-lesson-list .ld-section-heading{';
			$css .= 'margin-bottom:0;';
			$css .= 'border-bottom-left-radius:0;';
			$css .= 'border-bottom-right-radius:0;';
			$css .= '}';

			$css .= '.ld-lesson-list .ld-item-list-items .ld-item-list-section-heading:first-child .ld-lesson-section-heading{';
			$css .= 'margin-top:0;';
			$css .= '}';

		} // if container style = 'boxed'

		// LESSON STYLE
		if ( isset( $ldx3_option['list_tables_lesson_style'] ) && $ldx3_option['list_tables_lesson_style'] === 'table' ) {

			$css .= '.ld-lesson-list .ld-item-list-items{';
			// Set border radius to 0
			$css .= '--ldx-global-border-radius:0;';
			// Add padding to bottom
			$css .= 'padding-bottom:0.75em;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-lesson-list .ld-item-list-item{';
			$css .= 'margin-top:calc( var(--ldx-content-lists-lesson-border-width) * -1 );';
			$css .= 'margin-bottom:0;';
			$css .= '}';

			if ( !empty( $ldx3_option['list_tables_section_bg_color'] ) ) {

				$css .= '.learndash-wrapper .ld-item-list.ld-lesson-list .ld-lesson-section-heading{';
				$css .= 'margin-bottom:0;';
				$css .= '}';

			}

			// If a boxed container, add bottom padding back in
			if ( isset( $ldx3_option['list_tables_course_content_header'] ) && $ldx3_option['list_tables_course_content_header'] === 'boxed' ) {

				$css .= '.ld-lesson-list .ld-item-list-items{';
				$css .= 'padding-bottom:0.75em;';
				$css .= '}';

			}

		}

		// SECTION BACKGROUND COLOR
		if ( !empty( $ldx3_option['list_tables_section_bg_color'] ) ) {

			$css .= '.learndash-wrapper .ld-item-list.ld-lesson-list .ld-lesson-section-heading{';
			$css .= 'padding:0.5em 1em;';
			$css .= 'background-color:var(--ldx-content-lists-section-bg-color);';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '}';

		}
		
		// HIDE "LESSON CONTENT" HEADER
		if ( isset( $ldx3_option['list_tables_hide_lesson_content_header'] ) && $ldx3_option['list_tables_hide_lesson_content_header'] === true ) {

			$css .= '.learndash-wrapper .ld-item-list.ld-lesson-list .ld-topic-list .ld-table-list-header,';
			$css .= '.learndash-wrapper .ld-lesson-list .ld-item-list-item .ld-item-list-item-expanded::before{';
			$css .= 'display:none;';
			$css .= '}';

			// Add top border
			$css .= '.learndash-wrapper .ld-lesson-list .ld-item-list-item-expanded .ld-topic-list{';
			$css .= 'border-top:var(--ldx-content-lists-lesson-border-width) solid var(--ldx-content-lists-separator-color);';
			$css .= '}';

		}

		// TOPIC INDENTATION
		if ( isset( $ldx3_option['list_tables_indent_topics'] ) && $ldx3_option['list_tables_indent_topics'] === true ) {

			$css .= '.learndash-wrapper .ld-lesson-list .ld-topic-list .ld-table-list-item-preview{';
			$css .= 'padding-left:3em;';
			$css .= '}';
			// @RTL
			$css .= '.rtl .learndash-wrapper .ld-lesson-list .ld-topic-list .ld-table-list-item-preview{';
			$css .= 'padding-right:3em;';
			$css .= '}';

		}

		/**
		 * COURSE PAGE / COURSE STATUS
		 */
		// HIDE column labels/headers
		if ( isset( $ldx3_option['course_status_hide_column_labels'] ) && $ldx3_option['course_status_hide_column_labels'] === true ) {

			$css .= '.learndash-wrapper .ld-course-status.ld-course-status-not-enrolled .ld-course-status-label{display:none;}';

		}

		// HIDE all 3 columns
		if ( isset( $ldx3_option['course_status_hide_status'] ) && $ldx3_option['course_status_hide_status'] === true && isset( $ldx3_option['course_status_hide_price'] ) && $ldx3_option['course_status_hide_price'] === true && isset( $ldx3_option['course_status_hide_action'] ) && $ldx3_option['course_status_hide_action'] === true ) {

			$css .= '.learndash-wrapper .ld-course-status.ld-course-status-not-enrolled{display:none;}';

		}

		// HIDE status
		if ( isset( $ldx3_option['course_status_hide_status'] ) && $ldx3_option['course_status_hide_status'] === true ) {

			$css .= '.ld-course-status-not-enrolled .ld-course-status-seg-status{display:none;}';

		}

		// HIDE price
		if ( isset( $ldx3_option['course_status_hide_price'] ) && $ldx3_option['course_status_hide_price'] === true ) {

			$css .= '.ld-course-status-not-enrolled .ld-course-status-seg-price{display:none;}';

		}

		// HIDE action
		if ( isset( $ldx3_option['course_status_hide_action'] ) && $ldx3_option['course_status_hide_action'] === true ) {

			$css .= '.ld-course-status-not-enrolled .ld-course-status-seg-action{display:none;}';

		}


		/**
		 * FOCUS MODE
		 */
		
		// CONTENT WIDTH (edge-to-edge)
		if ( isset( $ldx3_option['focus_mode_content_width'] ) && $ldx3_option['focus_mode_content_width'] === 'stretched' ) {
			$css .= '.single-sfwd-lessons .learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content{max-width:unset;padding:51px 0 0;}.single-sfwd-topic .learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content{max-width:unset;padding:51px 0 0;}.single-sfwd-quiz .learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content{max-width:unset;padding:51px 0 0;}.ld-focus-content .learndash-wrapper .ld-content-actions{padding:1em;}.ld-focus-content .ld-topic-list,.ld-focus-content .ld-assignment-list,.ld-focus-content .ld-alert,.wpProQuiz_content{margin:1em;}.learndash-wrapper .ld-tabs .ld-tabs-content div[id^="ld-tab-materials"]{margin:0 1em;}.learndash-wrapper .ld-focus-comments{margin:2em 1em;}.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content > h1{padding:0.5em;}.ld-focus-content .learndash-wrapper .ld-breadcrumbs{border-radius:0;}@media (max-width:640px){.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content,.learndash-wrapper .ld-focus.ld-focus-sidebar-collapsed .ld-focus-main .ld-focus-content{padding:0;margin:0;}}';
		}

		// CONTENT ANIMATION
		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-right' ) {

			$css .= '.ld-focus-content{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-right;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-left' ) {

			$css .= '.ld-focus-content{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-left;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-up' ) {

			$css .= '.ld-focus-content{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-up;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-down' ) {

			$css .= '.ld-focus-content{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-down;}';

		}

		// HIDE PAGE TITLE
		if ( isset( $ldx3_option['focus_mode_hide_page_title'] ) && $ldx3_option['focus_mode_hide_page_title'] === true ) {

			$css .= '.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content > h1{display:none;}';

		}

		// HIDE BREADCRUMBS
		if ( isset( $ldx3_option['focus_mode_hide_breadcrumbs'] ) && $ldx3_option['focus_mode_hide_breadcrumbs'] === true ) {

			// Must hide lesson, topic & quiz status divs
			$css .= '.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content .ld-topic-status,.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content .ld-lesson-status,.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content .ld-quiz-status{display:none;}';

		}

		// HIDE BOTTOM CONTENT ACTIONS
		if ( isset( $ldx3_option['focus_mode_hide_bottom_actions'] ) && $ldx3_option['focus_mode_hide_bottom_actions'] === true ) {

			$css .= '.ld-focus-content .ld-content-actions{display:none;}';

		} elseif (
		// HIDE "BACK TO" LINK ONLY
		isset( $ldx3_option['focus_mode_hide_backto_link'] ) && $ldx3_option['focus_mode_hide_backto_link'] === true ) {

			// @TODO drop backwards compatibility at some point
			// LD 3.1.3 and below
			$css .= '.ld-focus-content .ld-content-actions>a{display:none;}';
			// LD 3.1.4 and above
			$css .= '.learndash-wrapper .ld-content-actions .ld-content-action .ld-course-step-back{display:none;}';

		} else {}		

		// AVATAR STYLE (circle or square)
		if ( isset( $ldx3_option['focus_mode_avatar_style'] ) && $ldx3_option['focus_mode_avatar_style'] === 'square' ) {

			if ( isset( $ldx3_option['focus_mode_hide_avatar'] ) && $ldx3_option['focus_mode_hide_avatar'] != true ) {

				$css .= '.learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu .ld-profile-avatar{';
				$css .= 'border-radius:0;';
				$css .= 'width:50px;';
				$css .= 'height:50px;';
				$css .= '}';

				$css .= '.learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu{';
				$css .= 'padding:0;';
				$css .= '}';

				$css .= '.rtl .learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu .ld-profile-avatar{';
				$css .= 'margin-left:0;';
				$css .= '}';

			}
		}

		// HIDE AVATAR & NAME
		if ( isset( $ldx3_option['focus_mode_hide_avatar'] ) && $ldx3_option['focus_mode_hide_avatar'] === true ) {

			if ( isset( $ldx3_option['focus_mode_hide_name'] ) && $ldx3_option['focus_mode_hide_name'] === true ) {

				$css .= '.learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu{display:none;}';

			}
		}

		// HIDE AVATAR ONLY
		if ( isset( $ldx3_option['focus_mode_hide_avatar'] ) && $ldx3_option['focus_mode_hide_avatar'] === true ) {

			$css .= '.learndash-wrapper .ld-focus-header .ld-profile-avatar{display:none;}';

		}

		// HIDE NAME ONLY
		if ( isset( $ldx3_option['focus_mode_hide_name'] ) && $ldx3_option['focus_mode_hide_name'] === true ) {

			$css .= '.ld-focus-header .ld-user-menu .ld-text{display:none;}.learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu{flex-basis:50px;}';

			if ( isset( $ldx3_option['focus_mode_avatar_style'] ) && $ldx3_option['focus_mode_avatar_style'] === 'square' ) {

				$css .= '.learndash-wrapper .ld-focus .ld-focus-header .ld-user-menu .ld-profile-avatar{margin:0;}';

			}
		}

		// COMMENTS: AVATAR SHAPE (circle, square, inherit)
		if ( isset( $ldx3_option['focus_mode_comments_avatar_shape'] ) && $ldx3_option['focus_mode_comments_avatar_shape'] === 'square' ) {

			$css .= '.learndash-wrapper .ld-focus-comment .ld-comment-avatar img,';
			$css .= '.wdm_course_rating_reviews .review-author-img-wrap .avatar{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		if ( isset( $ldx3_option['focus_mode_comments_avatar_shape'] ) && $ldx3_option['focus_mode_comments_avatar_shape'] === 'inherit' ) {

			$css .= '.learndash-wrapper .ld-focus-comment .ld-comment-avatar img,';
			$css .= '.wdm_course_rating_reviews .review-author-img-wrap .avatar{';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '}';

		}

		/**
		 * COURSE NAVIGATION
		 */
		
		// DISABLE EXPAND/COLLAPSE
		if ( isset( $ldx3_option['coursenav_disable_expand_collapse'] ) && $ldx3_option['coursenav_disable_expand_collapse'] === true ) {

			$css .= '.learndash-wrapper .ld-focus-sidebar .ld-course-navigation .ld-lesson-item-expanded,';
			$css .= '.learndash-wrapper .ld-course-navigation .ld-lesson-item-expanded{';
			$css .= 'max-height:unset;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-course-navigation .ld-expand-button{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// STRIKETHROUGH COMPLETED ITEMS
		if ( isset( $ldx3_option['coursenav_strikethrough_completed'] ) && $ldx3_option['coursenav_strikethrough_completed'] === true ) {

			$css .= '.ld-status-complete ~ .ld-lesson-title,';
			$css .= '.ld-quiz-complete ~ .ld-lesson-title,';
			$css .= '.ld-table-list-item.learndash-complete .ld-topic-title{';
			$css .= 'text-decoration:line-through;';
			$css .= 'opacity:0.55;';
			$css .= '}';

		}

		/**
		 * PROFILE
		 */
		// PROFILE: SUMMARY LAYOUT
		if ( isset( $ldx3_option['profile_summary_layout'] ) && $ldx3_option['profile_summary_layout'] === 'horizontal' ) {

			$css .= '@media (min-width:800px){.learndash-wrapper .ld-profile-summary{';
			$css .= 'display:flex;';
			$css .= 'flex-wrap:wrap;';
			$css .= '}}';
			
			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-card{';
			$css .= 'margin:0 1.5em 0 0;';
			$css .= '}';

			// Only left-align user info on larger screens
			$css .= '@media (min-width:800px){.learndash-wrapper .ld-profile-summary .ld-profile-card{';
			$css .= 'align-items:flex-start;';
			$css .= 'max-width:35%;';
			$css .= '}}';

			$css .= '.rtl .learndash-wrapper .ld-profile-summary .ld-profile-card{';
			$css .= 'margin-right:0;';
			$css .= 'margin-left:1.5em;';
			$css .= '}';

			$css .= '@media (max-width:800px){.learndash-wrapper .ld-profile-summary .ld-profile-card{';
			$css .= 'margin-bottom:1.5em;';
			$css .= '}}';

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'margin:0 0 0 auto;';
			$css .= 'align-items:center;';
			$css .= '}';

			$css .= '@media (max-width:800px){.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'margin-left:auto;';
			$css .= 'margin-right:auto;';
			$css .= '}}';

			$css .= '@media (min-width:801px){.rtl .learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'margin:0 auto 0 0;';
			$css .= '}}';

			// If also hiding stats, center the user info
			if ( isset( $ldx3_option['profile_hide_stats_section'] ) && $ldx3_option['profile_hide_stats_section'] === true ) {

				$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-card{';
				$css .= 'max-width:none;';
				$css .= 'margin:0;';
				$css .= 'align-items:center;';
				$css .= '}';

			} // end hiding stats

			// If also hiding user info, remove top margin on stats
			if ( isset( $ldx3_option['profile_hide_user_section'] ) && $ldx3_option['profile_hide_user_section'] === true ) {

				$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
				$css .= 'margin-top:0;';
				$css .= '}';

			} // end hiding stats

		} // end PROFILE: SUMMARY LAYOUT

		// PROFILE: STATS LAYOUT
		if ( isset( $ldx3_option['profile_stats_layout'] ) && $ldx3_option['profile_stats_layout'] === 'stacked' ) {

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'flex-direction:column;';
			$css .= 'max-width:200px;';
			$css .= '}';

			// If summary layout is stacked, we need to center the stats
			if ( !isset( $ldx3_option['profile_summary_layout'] ) || $ldx3_option['profile_summary_layout'] === 'stacked' ) {

				$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
				$css .= 'margin-left:auto;';
				$css .= 'margin-right:auto;';
				$css .= '}';

			}

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats .ld-profile-stat{';
			$css .= 'flex-basis:auto;';
			$css .= 'min-width:160px;';
			$css .= 'text-align:left;';
			$css .= 'border-right:0;';
			$css .= 'border-bottom:1px solid rgba(0,0,0,0.1);';
			$css .= 'padding:0.375em 4px;';
			$css .= '}';

			$css .= '@media (max-width:640px){.learndash-wrapper #ld-profile .ld-profile-stats .ld-profile-stat{';
			$css .= 'margin-bottom:0 !important;';
			$css .= 'padding:0.375em 4px !important;';
			$css .= 'border-bottom:1px solid rgba(0,0,0,0.1);';
			$css .= '}}';

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats .ld-profile-stat strong{';
			$css .= 'font-size:1em;';
			$css .= 'display:inline-block;';
			$css .= 'margin-bottom:0;';
			$css .= 'min-width:2em;';
			$css .= '}';

		} // end PROFILE: STATS LAYOUT

		// PROFILE: BG COLOR
		if ( !empty( $ldx3_option['profile_summary_bg_color'] ) ) {
			$css .= '.learndash-wrapper .ld-profile-summary{';
			$css .= 'padding:1.25em;';
			$css .= '}';
		}

		// HIDE SECTION: USER INFO
		if ( isset( $ldx3_option['profile_hide_user_section'] ) && $ldx3_option['profile_hide_user_section'] === true ) {
			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-card{';
			$css .= 'display:none;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'margin-top:0;';
			$css .= '}';
		}

		// HIDE SECTION: STATS
		if ( isset( $ldx3_option['profile_hide_stats_section'] ) && $ldx3_option['profile_hide_stats_section'] === true ) {
			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{';
			$css .= 'display:none;';
			$css .= '}';
		}

		// HIDE SECTION: COURSES
		if ( isset( $ldx3_option['profile_hide_courses_section'] ) && $ldx3_option['profile_hide_courses_section'] === true ) {
			$css .= '#ld-profile .ld-course-list{';
			$css .= 'display:none;';
			$css .= '}';
		}

		// AVATAR STYLE
		// SQUARE
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'square' ) {

			$css .= '.ld-profile-card .ld-profile-avatar,';
			$css .= '.uo-reporting--frontend .reporting-user-card__avatar:empty::before,';
			$css .= '.uo-reporting--frontend .reporting-user-card__avatar img{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		// GLOBAL BORDER RADIUS
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'borderradius' ) {

			$css .= '.ld-profile-card .ld-profile-avatar,';
			$css .= '.uo-reporting--frontend .reporting-user-card__avatar:empty::before,';
			$css .= '.uo-reporting--frontend .reporting-user-card__avatar img{';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '}';

		}

		// AVATAR HIDDEN
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'hidden' ) {

			$css .= '.ld-profile-card .ld-profile-avatar,';
			$css .= '.uo-reporting--frontend .reporting-user-card__avatar{';
			$css .= 'display:none;';
			$css .= '}';

			// for Tin Canny Reporting
			$css .= '.uo-reporting--frontend .reporting-user-card__content{';
			$css .= 'padding:0;';
			$css .= '}';

		}

		// PROFILE: AVATAR SIZE
		if ( isset( $ldx3_option['profile_avatar_size'] ) && $ldx3_option['profile_avatar_size'] != '' ) {

			$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-card .ld-profile-avatar{';
			$css .= 'width:' . $ldx3_option['profile_avatar_size'] . 'px;';
			$css .= 'height:' . $ldx3_option['profile_avatar_size'] . 'px;';
			$css .= '}';

		}

		// HIDE "EDIT PROFILE" LINK
		if ( isset( $ldx3_option['profile_hide_edit_profile_link'] ) && $ldx3_option['profile_hide_edit_profile_link'] === true ) {

			$css .= '.ld-profile-card .ld-profile-edit-link{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: COURSES
		if ( isset( $ldx3_option['profile_hide_stat_courses'] ) && $ldx3_option['profile_hide_stat_courses'] === true ) {

			$css .= '.ld-profile-stat-courses{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: COMPLETED
		if ( isset( $ldx3_option['profile_hide_stat_completed'] ) && $ldx3_option['profile_hide_stat_completed'] === true ) {

			$css .= '.ld-profile-stat-completed{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: CERTIFICATES
		if ( isset( $ldx3_option['profile_hide_stat_certificates'] ) && $ldx3_option['profile_hide_stat_certificates'] === true ) {

			$css .= '.ld-profile-stat-certificates{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: POINTS
		if ( isset( $ldx3_option['profile_hide_stat_points'] ) && $ldx3_option['profile_hide_stat_points'] === true ) {

			$css .= '.ld-profile-stat-points{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE "YOUR COURSES" HEADER
		if ( isset( $ldx3_option['profile_hide_courses_header'] ) && $ldx3_option['profile_hide_courses_header'] === true ) {

			$css .= '#ld-profile .ld-section-heading h3{';
			$css .= 'display:none;';
			$css .= '}';

			$css .= '#ld-profile .ld-item-list .ld-section-heading .ld-item-list-actions{';
			$css .= 'margin-left:auto;';
			$css .= 'margin-bottom:0.75em;';
			$css .= '}';

		}

		// PROFILE: DISABLE EXPAND/COLLAPSE
		if ( isset( $ldx3_option['profile_disable_expand_collapse'] ) && $ldx3_option['profile_disable_expand_collapse'] === true ) {

			// expands all content
			$css .= '#ld-profile .ld-item-list .ld-item-list-item .ld-item-list-item-expanded{';
			$css .= 'max-height:unset;';
			$css .= '}';

			// removes both "expand all" and individual arrows for each course
			$css .= '#ld-profile .ld-expand-button,';
			$css .= '.learndash-wrapper #ld-profile .ld-item-list .ld-item-list-item .ld-item-details{';
			$css .= 'display:none !important;';
			$css .= '}';

		} 

		// DISABLE PROFILE COURSE SEARCH
		if ( isset( $ldx3_option['profile_disable_search'] ) && $ldx3_option['profile_disable_search'] === true ) {

			$css .= '.learndash-wrapper .ld-item-list .ld-section-heading .ld-search-prompt{';
			$css .= 'display:none;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-item-list .ld-item-search{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// PROFILE: HIDE QUIZZES
		if ( isset( $ldx3_option['profile_hide_quiz_list'] ) && $ldx3_option['profile_hide_quiz_list'] === true ) {

			$css .= '#ld-profile .ld-quiz-list{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// PROFILE: HIDE ESSAYS
		if ( isset( $ldx3_option['profile_hide_essay_list'] ) && $ldx3_option['profile_hide_essay_list'] === true ) {

			$css .= '#ld-profile .ld-essay-list{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// PROFILE: HIDE ASSIGNMENTS
		if ( isset( $ldx3_option['profile_hide_assignment_list'] ) && $ldx3_option['profile_hide_assignment_list'] === true ) {

			$css .= '#ld-profile .ld-assignment-list{';
			$css .= 'display:none;';
			$css .= '}';

		}

		/**
		 * PROGRESS BAR
		 */
		if ( isset( $ldx3_option['progress_bar_style'] ) && $ldx3_option['progress_bar_style'] === 'striped' ) {
			$css .= '.ld-progress-bar .ld-progress-bar-percentage,';
			$css .= '.ultp-dashboard-course__row .ultp-dashboard-course__details .ultp-dashboard-course__right .ultp-dashboard-course__progress-bar,';
			$css .= '.ldx-plugin .ulg-manage-progress-course__row .ulg-manage-progress-course__details .ulg-manage-progress-course__right .ulg-manage-progress-course__progress-bar,';
			$css .= '.wdm-tabs-wrapper .wdm-progress-bar,';
			$css .= 'body #tab-3 .ldgr-course-progress-bar,';
			$css .= '.wdm-tabs-wrapper dd.course_progress div.course_progress_blue,';
			$css .= 'body .el-cls-progress .el-archive-pg-bar .el-archive-percentage,';
			$css .= 'dd.uo-course-progress div.course_progress{';
			$css .= 'background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent) !important;';
			$css .= 'background-size:1rem 1rem !important;';
			$css .= '}';
		}

		if ( isset( $ldx3_option['progress_bar_animation'] ) && $ldx3_option['progress_bar_animation'] === true ) {
			$css .= '.ld-progress-bar-percentage,';
			$css .= '.ultp-dashboard-course__progress-bar,';
			$css .= '.ulg-manage-progress-course__progress-bar{';
			$css .= 'animation:.5s ease .6s both ldx-progress-bar;';
			$css .= '}';
		}

		if ( isset( $ldx3_option['progress_bar_hide_percent_complete'] ) && $ldx3_option['progress_bar_hide_percent_complete'] === true ) {
			$css .= '.ld-progress-stats .ld-progress-percentage,';
			$css .= 'body .el-cls-progress .el-archive-percentage-text{';
			$css .= 'display:none;';
			$css .= '}';
		}

		if ( isset( $ldx3_option['progress_bar_hide_steps'] ) && $ldx3_option['progress_bar_hide_steps'] === true ) {
			$css .= '.ld-focus-header .ld-progress-stats .ld-progress-steps,';
			$css .= '.learndash-widget .ld-progress-stats .ld-progress-steps,';
			$css .= '#ld-profile .ld-progress-stats .ld-progress-steps{';
			$css .= 'display:none;';
			$css .= '}';
		}

		/**
		 * ALERTS
		 */
		
		if ( isset( $ldx3_option['alert_size_compact'] ) && $ldx3_option['alert_size_compact'] === true ) {

			$css .= '.learndash-wrapper .ld-alert{';
			$css .= 'padding-top:10px;';
			$css .= 'padding-bottom:10px;';
			$css .= 'padding-left:54px;';
			$css .= 'min-height:unset;';
			$css .= '}';

			$css .= '.rtl .learndash-wrapper .ld-alert{';
			$css .= 'padding-left:15px;';
			$css .= 'padding-right:54px;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon.ld-icon{';
			$css .= 'left:10px;';
			$css .= '}';

			$css .= '.rtl .learndash-wrapper .ld-alert .ld-alert-icon.ld-icon{';
			$css .= 'left:unset;';
			$css .= 'right:10px;';
			$css .= '}';

			// for general & success alerts
			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon.ld-icon-checkmark{';
			$css .= 'font-size:18px;';
			$css .= 'padding:5px;';
			$css .= '';
			$css .= '}';

			// for certificate alerts
			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon.ld-icon-certificate{';
			$css .= 'font-size:18px;';
			$css .= 'padding:8px;';
			$css .= '';
			$css .= '}';

			// warning icons need a different font-size and don't have their own 3px padding
			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon.ld-icon-alert{';
			$css .= 'font-size:24px;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon.ld-icon-calendar{';
			$css .= 'font-size:17px;';
			$css .= 'padding:6px;';
			$css .= '}';

			// alert buttons
			$css .= '.learndash-wrapper .ld-alert .ld-button{';
			$css .= 'padding-top:.25em;';
			$css .= 'padding-bottom:.25em;';
			$css .= 'font-size:80%;';
			$css .= '}';
		}

		if ( isset( $ldx3_option['alert_remove_icons'] ) && $ldx3_option['alert_remove_icons'] === true ) {
			$css .= '.learndash-wrapper .ld-alert .ld-alert-icon{';
			$css .= 'display:none;';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-alert{';
			$css .= 'padding:.75em 1em;';
			$css .= 'min-height:unset;';
			$css .= '}';
		}

		/**
		 * LOGIN & REGISTRATION
		 */
		if ( isset( $ldx3_option['log_reg_overlay_color'] ) && !empty( $ldx3_option['log_reg_overlay_color'] ) ) {
			$css .= '.learndash-wrapper-login-modal.ld-modal-open{';
			$css .= 'position:fixed;';
			$css .= 'width:100vw;';
			$css .= 'height:100vh;';
			$css .= 'z-index:100;';
			$css .= 'overflow-y:auto;';
			$css .= 'background-color:' . $ldx3_option['log_reg_overlay_color'];

			if ( isset( $ldx3_option['log_reg_overlay_opacity'] ) && !empty( $ldx3_option['log_reg_overlay_opacity'] ) ) {

				$css .= $ldx3_option['log_reg_overlay_opacity'];

			} else {

				$css .= 'B3';

			}

			$css .= ';}';
		}

		if ( isset( $ldx3_option['login_panel_remove_desc_text'] ) && $ldx3_option['login_panel_remove_desc_text'] === true ) {

			$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-login .ld-modal-text{';
			$css .= 'display:none;';
			$css .= '}';

		}

		if ( isset( $ldx3_option['login_panel_remove_logo'] ) && $ldx3_option['login_panel_remove_logo'] === true ) {

			$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-form .ld-login-modal-branding{';
			$css .= 'display:none;';
			$css .= '}';

		}

		if ( isset( $ldx3_option['register_panel_remove_desc_text'] ) && $ldx3_option['register_panel_remove_desc_text'] === true ) {

			$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-register .ld-modal-text{';
			$css .= 'display:none;';
			$css .= '}';

		}

		if ( isset( $ldx3_option['register_panel_remove_email_conf_text'] ) && $ldx3_option['register_panel_remove_email_conf_text'] === true ) {

			$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-register #reg_passmail{';
			$css .= 'display:none;';
			$css .= '}';

		}

		/**
		 * COURSE GRID
		 */

		// GRID FILTER WIDTH
		if ( isset( $ldx3_option['grid_selector_width'] ) && $ldx3_option['grid_selector_width'] === 'inline' ) {

			$css .= '#ld_course_categorydropdown,#ld_lesson_categorydropdown, #ld_topic_categorydropdown,#ld_group_categorydropdown,';
			$css .= '#uo_course_categorydropdown form{';
			$css .= 'display:inline-block;';
			$css .= 'width:auto;';
			$css .= '}';

		}

		// ADD SHADOW
		if ( isset( $ldx3_option['grid_item_shadow'] ) && $ldx3_option['grid_item_shadow'] === 'shadow' ) {

			$css .= 'body div.ld-course-list-content .ld_course_grid,#ld_course_list .ld-course-list-items .ld_course_grid,#et-boc .ld-course-list-items .ld_course_grid,.uo-grid-wrapper .grid-course .uo-border{';
			$css .= 'box-shadow:0 1px 4px rgba(0,0,0,0.05),0 4px 14px rgba(0,0,0,0.08);';
			$css .= '}';

		}

		// HOVER: ADD SHADOW
		if ( isset( $ldx3_option['grid_item_hover_shadow'] ) && $ldx3_option['grid_item_hover_shadow'] === true ) {

			$css .= '.ld-course-list-content .ld_course_grid:hover,#ld_course_list .ld-course-list-items .ld_course_grid:hover,#et-boc .ld-course-list-items .ld_course_grid:hover,.uo-grid-wrapper .grid-course .uo-border:hover{';
			$css .= 'box-shadow:0 1px 4px rgba(0,0,0,0.05),0 4px 14px rgba(0,0,0,0.08);';
			$css .= '}';

		}

		// HOVER: TRANSFORM (LIFT/ENLARGE)
		if ( isset( $ldx3_option['grid_item_hover_transform'] ) && $ldx3_option['grid_item_hover_transform'] != 'none' ) {

			$css .= '.ld-course-list-content .ld_course_grid:hover,#ld_course_list .ld-course-list-items .ld_course_grid:hover,#et-boc .ld-course-list-items .ld_course_grid:hover,.uo-grid-wrapper .grid-course .uo-border:hover{';

				// LIFT
				if ( $ldx3_option['grid_item_hover_transform'] === 'lift' ) {

					$css .= 'transform:translateY(-5px);';

				}

				// ENLARGE
				if ( $ldx3_option['grid_item_hover_transform'] === 'enlarge' ) {

					$css .= 'transform:scale(1.02);';

				}

			$css .= '}';

		}

		// GRID RIBBON POSITION
		if( isset( $ldx3_option['grid_ribbon_position'] ) && $ldx3_option['grid_ribbon_position'] === 'top-left' ) {

			$css .= 'body .ld-course-list-items .ld_course_grid .thumbnail.course .ld_course_grid_price,body .ld-course-list-items .ld_course_grid .thumbnail.course .ribbon,#et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price,.uo-grid-wrapper #ribbon{left:8px;right:unset;}';

		}

		if( isset( $ldx3_option['grid_ribbon_position'] ) && $ldx3_option['grid_ribbon_position'] === 'top-right' ) {

			$css .= 'body .ld-course-list-items .ld_course_grid .thumbnail.course .ld_course_grid_price,body .ld-course-list-items .ld_course_grid .thumbnail.course .ribbon,#et-boc .ld_course_grid .thumbnail.course .ld_course_grid_price,.uo-grid-wrapper #ribbon{right:8px;left:unset;}';

		}


		/**
		 * PAGINATION
		 */
		if ( isset( $ldx3_option['pagination_arrow_style'] ) && $ldx3_option['pagination_arrow_style'] === 'circle' ) {

			$css .= '.learndash-wrapper .ld-pagination .ld-pages a,';
			$css .= '.learndash-pager a{';
			$css .= 'background:var(--ldx-pagination-arrow-bg-color);';
			$css .= '}';

			$css .= '.learndash-wrapper .ld-pagination .ld-pages a:hover,';
			$css .= '.learndash-pager a:hover{';
			$css .= 'background:var(--ldx-pagination-arrow-bg-color-hover);';
			$css .= '}';

			$css .= '.learndash-pager a.disabled:hover{';
			$css .= 'background:var(--ldx-pagination-arrow-bg-color);';
			$css .= '}';

		}

		/**
		 * GROUP COURSES LIST
		 */
		
		// DISABLE EXPAND/COLLAPSE
		if ( isset( $ldx3_option['group_courses_list_disable_expand_collapse'] ) && $ldx3_option['group_courses_list_disable_expand_collapse'] === true ) {

			$css .= '.learndash_post_groups .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-expanded{';
			$css .= 'max-height:unset;';
			$css .= '}';

			$css .= '.learndash_post_groups .learndash-wrapper .ld-item-list .ld-expand-button{';
			$css .= 'display:none !important;';
			$css .= '}';

			$css .= '.learndash_post_groups .learndash-wrapper .ld-item-details .ld-status{';
			$css .= 'margin-right:1em;';
			$css .= '}';

		}

		/**
		 * ACHIEVEMENTS ADD-ON
		 */

		if ( defined( 'LEARNDASH_ACHIEVEMENTS_FILE' ) ) {

			if ( isset( $ldx3_option['achievements_popup_vertical_position'] ) && $ldx3_option['achievements_popup_vertical_position'] === 'bottom' ) {

				$css .= 'body #noty_layout__topLeft,body #noty_layout__topRight{';
				$css .= 'top:unset;';
				$css .= 'bottom:20px;';
				$css .= '}';

			}

			if ( isset( $ldx3_option['achievements_popup_boxshadow'] ) && $ldx3_option['achievements_popup_boxshadow'] === 'small' ) {

				$css .= '.noty_layout .noty_theme__learndash.noty_bar{';
				$css .= 'box-shadow:0 2px 4px rgba(0,0,0,0.18);';
				$css .= '}';

			}

			if ( isset( $ldx3_option['achievements_popup_boxshadow'] ) && $ldx3_option['achievements_popup_boxshadow'] === 'large' ) {

				$css .= '.noty_layout .noty_theme__learndash.noty_bar{';
				$css .= 'box-shadow:0 8px 12px rgba(0,0,0,0.09);';
				$css .= '}';

			}

			if ( isset( $ldx3_option['achievements_popup_hide_image'] ) && $ldx3_option['achievements_popup_hide_image'] === true ) {

				$css .= '.noty_bar .noty_body .image{';
				$css .= 'display:none;';
				$css .= '}';

				$css .= '.noty_bar .noty_body .text{';
				$css .= 'padding:0;';
				$css .= '}';

			}

		} // if ( defined( 'LEARNDASH_ACHIEVEMENTS_FILE' ) )


		/**
		 * WISDMLABS: RATINGS, REVIEWS & FEEDBACK
		 */
		if ( defined( 'WDM_LD_COURSE_VERSION' ) ) {

			// RATING BAR STYLE
			if ( isset( $ldx3_option['rating_bar_style'] ) && $ldx3_option['rating_bar_style'] === 'amazon' ) {

				$css .= '.course-reviews-section .review-split-percent{';
				$css .= 'height:17px;';
				// $css .= 'border-radius:1px;';
				$css .= '}';

				$css .= '.course-reviews-section .review-split-percent .review-split-percent-inner{';
				$css .= 'border-radius:1px;';
				$css .= '}';

				$css .= '.course-reviews-section .review-split-percent .review-split-percent-inner-1{';
				$css .= 'background-color:#f3f3f3;';
				$css .= 'background:linear-gradient(to bottom,#eee,#f6f6f6);';
				$css .= 'box-shadow:inset 0 1px 2px rgba(0,0,0,.4),inset 0 0 0 1px rgba(0,0,0,.1);';
				$css .= '}';

				$css .= '.course-reviews-section .review-split-percent .review-split-percent-inner-2{';
				// $css .= 'box-shadow:inset 0 0 0 1px rgba(0,0,0,.25),inset 0 -1px 0 rgba(0,0,0,.05);';
				$css .= 'box-shadow:inset 0 0 0 1px rgba(0,0,0,.15),inset 0 -6px 7px rgba(0,0,0,.1);';
				$css .= '}';

			}

			// HIDE SORT/FILTER SECTION
			if ( isset( $ldx3_option['wisdm_rrf_hide_sort_filter'] ) && $ldx3_option['wisdm_rrf_hide_sort_filter'] === true ) {

				$css .= '.course-reviews-section .filter-options{';
				$css .= 'display:none;';
				$css .= '}';

				$css .= '.course-reviews-section .wdm_course_rating_reviews{';
				$css .= 'padding-top:2em;';
				$css .= 'border-top:1px solid #ebebeb;';
				$css .= '}';

			}

			// HIDE HELPFUL? RATINGS
			if ( isset( $ldx3_option['wisdm_rrf_hide_helpful'] ) && $ldx3_option['wisdm_rrf_hide_helpful'] === true ) {

				$css .= '.wdm_course_rating_reviews .review-helpful-wrap,';
				$css .= '.wdm_course_rating_reviews span.review-helpful-count{';
				$css .= 'display:none;';
				$css .= '}';

				$css .= '.course-reviews-section .review-footer a.reply_to_review_link{';
				$css .= 'border:0;';
				$css .= 'margin:0;';
				$css .= 'padding:0;';
				$css .= '}';

			}

			// REVIEWS BG COLOR
			// Uses Focus Mode Comments BG color setting
			if ( !empty( $ldx3_option['focus_mode_comments_bg_color'] ) ) {

				$css .= '.wdm_course_rating_reviews .comment-review-inner{';
				$css .= 'padding:1em;';
				$css .= 'margin-bottom:1.25em;';
				$css .= 'border:0;';
				$css .= 'border-radius:var(--ldx-global-border-radius);';
				$css .= 'background-color:var(--ldx-focus-mode-comment-bg-color);';
				$css .= '}';

			}

		} // if WDM_LD_COURSE_VERSION

		/**
		 * UNCANNY OWL: TIN CANNY REPORTING
		 */
		if ( isset( $ldx3_option['tincanny_container_shadow'] ) && $ldx3_option['tincanny_container_shadow'] === 'small' ) {
		
			$css .= '.reporting-dashboard-col-inner-container,';
			$css .= '.reporting-metabox{';
			$css .= 'box-shadow:0 0 2px rgba(0,0,0,0.04),0 3px 5px rgba(0,0,0,0.07);';
			$css .= '}';

		}

		if ( isset( $ldx3_option['tincanny_container_shadow'] ) && $ldx3_option['tincanny_container_shadow'] === 'large' ) {
		
			$css .= '.reporting-dashboard-col-inner-container,';
			$css .= '.reporting-metabox{';
			$css .= 'box-shadow:0 3px 7px rgba(0,0,0,0.04),0 12px 18px rgba(0,0,0,0.06);';
			$css .= '}';

		}

		// Print CSS
		return $css;

	} // if legacy vs LD3

} // function ldx_design_upgrade_pro_learndash_customizer_css()
