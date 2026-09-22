<?php
/**
 * Branda Email Logs class.
 *
 * @package Branda
 * @subpackage Emails
 */

if ( ! class_exists( 'Branda_Email_Logs' ) ) {

	class Branda_Email_Logs extends Branda_Helper {

		protected $option_name = 'ub_email_logs';
		private $module_name;

		public function __construct() {
			parent::__construct();
			$this->module      = 'email-logs';
			$this->module_name = Branda_Helper::hyphen_to_underscore( $this->module );

			// Replace module content with upsell page in free builds.
			add_filter( 'branda_get_module_content', array( $this, 'change_main_content' ), 10, 2 );

			// If the settings panel is rendered, show the same upsell page.
			add_filter( 'ultimatebranding_settings_' . $this->module_name, array( $this, 'admin_options_page' ) );
		}

		/**
		 * Show upsell page for the main module content.
		 *
		 * @param string $content Current module content.
		 * @param array  $module Current module.
		 * @return string
		 */
		public function change_main_content( $content, $module ) {
			if ( $this->module !== $module['module'] ) {
				return $content;
			}

			return $this->upgrade_to_pro();
		}

		/**
		 * Show upsell page for settings panel.
		 *
		 * @param string $content Current settings content.
		 * @return string
		 */
		public function admin_options_page( $content ) {
			return $this->upgrade_to_pro();
		}

		/**
		 * Render upsell page.
		 *
		 * @return string
		 */
		private function upgrade_to_pro() {
			$args     = array(
				'utm_campaign' => 'branda_emaillogs_upgrade',
				'description'  => __( 'Get detailed information about your emails with Branda Pro. You can check recipients information and export all log history. Try it today with a WPMU DEV Membership!', 'ub' ),
			);
			$template = '/admin/common/modules/only-for-pro';

			return $this->render( $template, $args, true );
		}
	}
}

new Branda_Email_Logs();