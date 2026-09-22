<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://pluginstack.dev/
 * @since      1.7.0
 *
 * @package    hab_Hide_Admin_Bar_Based_On_User_Roles
 * @subpackage hab_Hide_Admin_Bar_Based_On_User_Roles/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    hab_Hide_Admin_Bar_Based_On_User_Roles
 * @subpackage hab_Hide_Admin_Bar_Based_On_User_Roles/admin
 * @author     Ankit Panchal <support@pluginstack.dev>
 */
class hab_Hide_Admin_Bar_Based_On_User_Roles_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.7.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.7.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.7.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Add AJAX action for dismissing review banner
		add_action('wp_ajax_hab_dismiss_review_banner', array($this, 'hab_dismiss_review_banner'));

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.7.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in hab_Hide_Admin_Bar_Based_On_User_Roles_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The hab_Hide_Admin_Bar_Based_On_User_Roles_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'hide-admin-bar-settings' ) {

			wp_enqueue_style( 'select2-css', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'ultimakit_bootstrap_main', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'ultimakit_bootstrap_rtl', plugin_dir_url( __FILE__ ) . 'css/bootstrap.rtl.min.css', array(), $this->version, 'all' );
			// Enqueue toastr CSS.
			wp_enqueue_style( 'toastr-css', plugin_dir_url( __FILE__ ) . 'css/toastr.min.css', array(), $this->version, 'all' );
			
			wp_enqueue_style('dashicons');
                            
			wp_enqueue_style( 'tagsinput-css', plugin_dir_url( __FILE__ ) . 'css/jquery.tagsinput.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/main.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-admin', plugin_dir_url( __FILE__ ) . 'css/hide-admin-bar-based-on-user-roles-admin.css', array(), $this->version, 'all' );
			
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.7.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in hab_Hide_Admin_Bar_Based_On_User_Roles_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The hab_Hide_Admin_Bar_Based_On_User_Roles_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'hide-admin-bar-settings' || $_GET['page'] == 'hide-admin-bar-settings-affiliation' || $_GET['page'] == 'hide-admin-bar-settings-account' || $_GET['page'] == 'hide-admin-bar-settings-contact' ) ) {
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'ultimakit_bootstrap_bundle', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array( 'jquery' ), $this->version, false );
			// Enqueue toastr.js.
			wp_enqueue_script( 'toastr-js', plugin_dir_url( __FILE__ ) . 'js/toastr.min.js', array( 'jquery' ), $this->version, true );

			wp_enqueue_script( 'tagsinput-js', plugin_dir_url( __FILE__ ) . 'js/jquery.tagsinput.min.js', array( 'jquery' ), $this->version, false );

			wp_enqueue_script(
				'silent-installer', 
				plugin_dir_url(__FILE__) . 'js/silent-installer.js', 
				array('jquery'), 
				'1.0', 
				true
			);
			
			wp_localize_script('silent-installer', 'silent_installer_vars', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('silent_installer'),
				'installing_text' => __('Installing...', 'hide-admin-bar-based-on-user-roles'),
				'activated_text' => __('Installed & Activated!', 'hide-admin-bar-based-on-user-roles'),
				'error_text' => __('Installation Failed', 'hide-admin-bar-based-on-user-roles'),
				'already_installed' => __('Already Installed & Active', 'hide-admin-bar-based-on-user-roles'),
				'checking_status' => __('Checking plugin status...', 'hide-admin-bar-based-on-user-roles'),
				'downloading' => __('Downloading plugin...', 'hide-admin-bar-based-on-user-roles'),
				'installing' => __('Installing plugin...', 'hide-admin-bar-based-on-user-roles'),
				'activating' => __('Activating plugin...', 'hide-admin-bar-based-on-user-roles')
			));

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hide-admin-bar-based-on-user-roles-admin.js', array( 'jquery' ), $this->version, false );
			$args = array(
				'url'       => admin_url( 'admin-ajax.php' ),
				'hba_nonce' => wp_create_nonce( 'hba-nonce' ),
			);
			wp_localize_script( $this->plugin_name, 'ajaxVar', $args );

			
		}


	}


	public function generate_admin_menu_page() {

		add_options_page( __( 'Hide Admin Bar Settings', 'hide-admin-bar-based-on-user-roles' ), __( 'Hide Admin Bar Settings', 'hide-admin-bar-based-on-user-roles' ), 'manage_options', 'hide-admin-bar-settings', array(
			$this,
			'hide_admin_bar_settings'
		) );

	}

	public function hide_admin_bar_settings() {

		$settings      = get_option( "hab_settings" );
		$hab_reset_key = get_option( "hab_reset_key" );

		if ( ! empty( $hab_reset_key ) && isset( $_GET["reset_plugin"] ) && $_GET["reset_plugin"] == $hab_reset_key ) {
			update_option( "hab_settings", "" );
			update_option( "hab_reset_key", rand( 0, 999999999 ) );
			echo '<script>window.location.reload();</script>';
		}
		?>
		<?php
			// Add the promotional banner here
			if (!file_exists(WP_PLUGIN_DIR . '/hide-admin-bar-pro/hide-admin-bar-pro.php')) {
				include plugin_dir_path(__FILE__) . 'partials/promotional-banner.php';
			}
			
			// Add the review banner
			include plugin_dir_path(__FILE__) . 'partials/review-banner.php';
		?>
		<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #6610F2;">
			<div class="container-fluid p-2">
				<a class="navbar-brand" href="#">
					<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ).'images/hide-admin-bar-logo.svg' ); ?>"  class="d-inline-block align-top" alt="hide-admin-bar-based-on-user-roles-logo" width="225px">
					<div class="wpuk-version-info"><?php esc_html_e( 'Current version: ', 'hide-admin-bar-based-on-user-roles' ); ?><?php echo HIDE_ADMIN_BAR_BASED_ON_USER_ROLES; ?></div>
				</a>
				
				<div class="navbar-nav ml-auto">
					<a class="nav-item nav-link" target="_blank" href="https://wordpress.org/support/plugin/hide-admin-bar-based-on-user-roles/reviews/#new-post" style="color: #ffffff; margin-right: 20px"><?php esc_html_e( 'Leave Feedback', 'hide-admin-bar-based-on-user-roles' ); ?></a>
				</div>
			</div>
		</nav>

		<div class="wrap">
			<div class="container-fluid module-container">
				
				<div class="row">
					<?php
						$menu_active_class = '';
						$menu_active_class = 'active show';
					?>
					<!-- Nav tabs -->
					<ul class="nav nav-tabs" id="wpukTabs" role="tablist">
						<li class="nav-item" role="presentation">
							<a class="nav-link <?php echo $menu_active_class; ?>" id="hab-modules-tab" data-bs-toggle="tab" href="#hab-modules" role="tab" aria-controls="hab-modules" aria-selected="true"><?php esc_html_e( 'Settings', 'hide-admin-bar-based-on-user-roles' ); ?></a>
						</li>

						<?php if (!defined('HAB_PRO_VERSION')) { ?>
						<li class="nav-item" role="presentation">
							<a class="nav-link" id="pro-features-tab" data-bs-toggle="tab" href="#pro-features" role="tab" aria-controls="pro-features" aria-selected="false"><?php esc_html_e( 'Pro Features', 'hide-admin-bar-based-on-user-roles' ); ?> <span class="badge bg-warning text-dark">NEW</span></a>
						</li>
						<?php } ?>
						<?php do_action('hab_admin_menu_tabs'); ?>

						<li class="nav-item" role="presentation">
							<a class="nav-link" id="xwpstack-tab" style="font-weight: 600; text-decoration: underline;" href="https://pluginstack.dev/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get All PluginStack Plugins for 75% OFF!', 'hide-admin-bar-based-on-user-roles' ); ?></a>
						</li>
					</ul>
					<!-- Tab panes -->
					<div class="tab-content" id="wpukTabsContent">
						<div class="tab-pane fade show <?php echo $menu_active_class; ?>" id="hab-modules" role="tabpanel" aria-labelledby="modules-tab">
							<div class="row">
								<form class="form-sample">
									<div class="row">
										<div class="col-md-12">
											<div class="form-group row">
												<label class="col-sm-6 col-form-label"><?php esc_html_e( 'Hide Admin Bar for All Users', 'hide-admin-bar-based-on-user-roles' ); ?></label>
												<div class="col-sm-6">
													<?php
													$disableForAll = ( isset( $settings["hab_disableforall"] ) ) ? $settings["hab_disableforall"] : "";
													$checked       = ( $disableForAll == 'yes' ) ? "checked" : "";
													echo '<div class="icheck-square">
															<input tabindex="5" ' . $checked . ' type="checkbox" id="hide_for_all">
														</div>';
													?>
												</div>
											</div>
										</div>
									</div>
									<?php if ( $disableForAll == "no" || empty( $disableForAll ) ) { ?>
										<div class="row mt-3">
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-6 col-form-label"><?php esc_html_e( 'Hide Admin Bar for All Guests Users', 'hide-admin-bar-based-on-user-roles' ); ?></label>
													<div class="col-sm-6">
														<?php
														$disableForAllGuests = ( isset( $settings["hab_disableforallGuests"] ) ) ? $settings["hab_disableforallGuests"] : "";
														$checkedGuests       = ( $disableForAllGuests == 'yes' ) ? "checked" : "";
														echo '<div class="icheck-square">
															<input tabindex="5" ' . $checkedGuests . ' type="checkbox" id="hide_for_all_guests">
														</div>';
														?>

													</div>
												</div>
											</div>
										</div>
										<div class="row mt-3">
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-6 col-form-label"><?php esc_html_e( 'User Roles', 'hide-admin-bar-based-on-user-roles' ); ?>
														<br/><br/><?php esc_html_e( 'Hide admin bar for selected user roles.', 'hide-admin-bar-based-on-user-roles' ); ?>
													</label>
													<div class="col-sm-6">
														<?php
														global $wp_roles;
														$exRoles = ( isset( $settings["hab_userRoles"] ) ) ? $settings["hab_userRoles"] : "";
														$checked = '';

														$roles = $wp_roles->get_names();
														if ( is_array( $roles ) ) {
															foreach ( $roles as $key => $value ):
																if ( is_array( $exRoles ) ) {
																	$checked = ( in_array( $key, $exRoles ) ) ? "checked" : "";
																}

																echo '<div class="icheck-square">
																<input name="userRoles[]" ' . $checked . ' tabindex="5" type="checkbox" value="' . $key . '">&nbsp;&nbsp;' . $value . '
															</div>';
															endforeach;
														}
														?>

													</div>
												</div>
											</div>
										</div>
										<div class="row mt-3">
											<div class="col-md-12">
												<div class="form-group row">
													<label class="col-sm-6 col-form-label"><?php esc_html_e( 'Capabilities Blacklist', 'hide-admin-bar-based-on-user-roles' );
														echo '<br />';
														esc_html_e( 'Hide admin bar for selected user capabilities', 'hide-admin-bar-based-on-user-roles' ); ?></label>
													<div class="col-sm-6">
														<?php
														$caps = ( isset( $settings["hab_capabilities"] ) ) ? $settings["hab_capabilities"] : "";
														?>
														<div class="icheck-square">
															<textarea name="hab_capabilities"
																	id="hab_capabilities" rows="5" cols="50"><?php echo $caps; ?></textarea>
														</div>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
									<div class="row mt-3">
										<div class="col-md-12">
											<button type="button" class="btn btn-primary btn-fw"
													id="submit_roles"><?php esc_html_e( "Save Changes", 'hide-admin-bar-based-on-user-roles' ); ?></button>
										</div>
										<div class="col-md-12">
											<br/>
											<p><?php esc_html_e( "You can reset plugin settings by visiting this url without login to admin panel. Keep it safe.", 'hide-admin-bar-based-on-user-roles' ); ?>
												<br/><a href="<?php echo admin_url() . "options-general.php?page=hide-admin-bar-settings&reset_plugin=" . $hab_reset_key; ?>"
														target="_blank"><?php echo admin_url() . "options-general.php?page=hide-admin-bar-settings&reset_plugin=" . $hab_reset_key; ?></a>
											</p>
										</div>
									</div>
								</form>
								<script>
									if (jQuery('#hab_capabilities').length) {
										jQuery('#hab_capabilities').tagsInput({
											'width': '100%',
											'height': '75%',
											'interactive': true,
											'defaultText': '<?php _e('Add More', 'hide-admin-bar-based-on-user-roles'); ?>',
											'removeWithBackspace': true,
											'minChars': 0,
											'maxChars': 20, // if not provided there is no limit
											'placeholderColor': '#666666'
										});
									}
								</script>
							</div>
						</div> <!-- WordPress Tab End --->
						
						<?php if (!defined('HAB_PRO_VERSION')) { ?>
						<div class="tab-pane fade" id="pro-features" role="tabpanel" aria-labelledby="pro-features-tab">
							<div class="row">
								<div class="col-md-12 mt-4">
									<div class="card1 w-100 border-primary">
										<div class="card-header bg-primary text-white py-3 px-4 rounded-top">
											<h3 class="m-0 fw-bold"><?php esc_html_e('Upgrade to Hide Admin Bar Pro', 'hide-admin-bar-based-on-user-roles'); ?></h3>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-7">
													<h4 class="mb-4 fw-bold position-relative py-2" style="color: #000000; border-bottom: 2px solid #000000; letter-spacing: 0.5px; text-shadow: 0 1px 1px rgba(0,0,0,0.1);"><?php esc_html_e('Take Complete Control of Your WordPress Admin Bar', 'hide-admin-bar-based-on-user-roles'); ?> <span class="position-absolute" style="width: 50px; height: 3px; background-color: #000000; bottom: -3px; left: 0;"></span></h4>
													
													<p class="lead" style="color: #000000;"><?php esc_html_e('The Pro version gives you powerful features to customize the admin bar experience for every user on your site.', 'hide-admin-bar-based-on-user-roles'); ?></p>
													
													<div class="feature-list mt-4">
														<div class="feature-item d-flex align-items-start mb-4">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-admin-users" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('User-Specific Controls', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Hide the admin bar for specific users by username, not just by role or capability.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
														
														<div class="feature-item d-flex align-items-start mb-4">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-admin-appearance" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('Page-Specific Controls', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Show or hide the admin bar on specific pages, posts, or custom post types.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
														
														<div class="feature-item d-flex align-items-start mb-4">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-clock" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('Auto-Hide Timer', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Set the admin bar to automatically hide after a specific time period of inactivity.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
														
														<div class="feature-item d-flex align-items-start mb-4">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-admin-customizer" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('Custom Admin Bar Items', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Add, remove, or modify specific items in the admin bar for different user roles.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
														
														<div class="feature-item d-flex align-items-start mb-4">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-smartphone" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('Device-Specific Settings', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Configure different admin bar behavior for desktop, tablet, and mobile devices.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
														
														<div class="feature-item d-flex align-items-start">
															<div class="feature-icon me-3">
																<span class="dashicons dashicons-shield" style="font-size: 24px; color: #000000;"></span>
															</div>
															<div class="feature-content">
																<h5 style="color: #000000;"><?php esc_html_e('Priority Support', 'hide-admin-bar-based-on-user-roles'); ?></h5>
																<p><?php esc_html_e('Get dedicated support from our team of WordPress experts.', 'hide-admin-bar-based-on-user-roles'); ?></p>
															</div>
														</div>
													</div>
													
													<div class="cta-buttons mt-5">
														<a href="https://pluginstack.dev/plugins/hide-admin-bar/pro" target="_blank" class="btn btn-primary btn-lg me-3"><?php esc_html_e('Upgrade to Pro', 'hide-admin-bar-based-on-user-roles'); ?></a>
														<a href="https://pluginstack.dev/plugins/hide-admin-bar/pro" target="_blank" class="btn btn-outline-primary btn-lg"><?php esc_html_e('View Demo', 'hide-admin-bar-based-on-user-roles'); ?></a>
													</div>
												</div>
												
												<div class="col-md-5">
													<div class="card bg-light mt-4">
														<div class="card-body">
															<h4 class="text-center mb-4" style="color: #000000;"><?php esc_html_e('Pro vs Free Comparison', 'hide-admin-bar-based-on-user-roles'); ?></h4>
															
															<table class="table">
																<thead>
																	<tr>
																		<th style="color: #000000;"><?php esc_html_e('Feature', 'hide-admin-bar-based-on-user-roles'); ?></th>
																		<th class="text-center" style="color: #000000;"><?php esc_html_e('Free', 'hide-admin-bar-based-on-user-roles'); ?></th>
																		<th class="text-center" style="color: #000000;"><?php esc_html_e('Pro', 'hide-admin-bar-based-on-user-roles'); ?></th>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Hide for All Users', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Role-Based Control', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Capability-Based Control', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Hide for Guests', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Time-Based Conditions', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('User-Specific Control', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Page-Specific Control', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Device-Specific Settings', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Custom Admin Bar Items', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																	<tr>
																		<td style="color: #000000;"><?php esc_html_e('Priority Support', 'hide-admin-bar-based-on-user-roles'); ?></td>
																		<td class="text-center"><span class="dashicons dashicons-no-alt text-danger"></span></td>
																		<td class="text-center"><span class="dashicons dashicons-yes-alt text-success"></span></td>
																	</tr>
																</tbody>
															</table>
															
															<div class="text-center mt-4">
																<a href="https://pluginstack.dev/plugins/hide-admin-bar/pro" target="_blank" class="btn btn-success btn-lg w-100"><?php esc_html_e('Get Pro Now', 'hide-admin-bar-based-on-user-roles'); ?></a>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>

						<?php do_action('hab_admin_menu_tabs_content'); ?>

						<div class="tab-pane fade" id="xwpstack" role="tabpanel" aria-labelledby="xwpstack-tab" style="display:none !important;">
							<style>
								.xwpstack-hero {
									position: relative;
									background: radial-gradient(circle at 12% 18%, rgba(92, 160, 255, 0.2), transparent 32%), radial-gradient(circle at 78% 8%, rgba(76, 255, 217, 0.18), transparent 38%), linear-gradient(135deg, #0b0f2a 0%, #0f1a42 45%, #060915 100%);
									border: 1px solid rgba(255,255,255,0.06);
									border-radius: 26px;
									overflow: hidden;
									color: #eaf1ff;
									box-shadow: 0 28px 58px -24px rgba(4, 12, 40, 0.9);
								}
								.xwpstack-hero:after {
									content: '';
									position: absolute;
									inset: 0;
									background:
										radial-gradient(circle at 20% 24%, rgba(255,255,255,0.10), transparent 38%),
										radial-gradient(circle at 84% 12%, rgba(119, 209, 255, 0.20), transparent 42%),
										radial-gradient(circle at 62% 78%, rgba(255, 255, 255, 0.09), transparent 48%);
									filter: blur(0px);
								}
								.xwpstack-hero .content { position: relative; z-index: 1; }
								.xwpstack-pill { padding: 8px 16px; border-radius: 999px; background: rgba(255,255,255,0.14); color: #d7e2ff; font-weight: 700; letter-spacing: 0.02em; backdrop-filter: blur(4px); box-shadow: 0 8px 20px -14px rgba(0,0,0,0.8); }
								.xwpstack-heading { font-weight: 700; color: #ffffff; line-height: 1.1; }
								.xwpstack-sub { color: #d5ddff; max-width: 720px; }
								.xwpstack-feature-chip { padding: 8px 12px; border-radius: 12px; background: rgba(255,255,255,0.08); color: #e7edff; border: 1px solid rgba(255,255,255,0.12); font-size: 12px; backdrop-filter: blur(2px); }
								.xwpstack-stat { text-align: center; padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.06); color: #eaf1ff; border: 1px solid rgba(255,255,255,0.12); }
								.xwpstack-grid .card { border: 1px solid #e6ebf6; border-radius: 16px; transition: transform .2s ease, box-shadow .2s ease; background: linear-gradient(180deg, #ffffff 0%, #f5f7ff 100%); }
								.xwpstack-grid .card:hover { transform: translateY(-5px); box-shadow: 0 22px 36px -24px rgba(12, 31, 74, 0.35); }
								.xwpstack-grid .card:before { content:''; display:block; height:4px; width:100%; border-radius:14px 14px 0 0; background: linear-gradient(90deg, #3b82f6, #22d3ee); }
								.xwpstack-tag { padding: 6px 10px; border-radius: 10px; background: #e8f5e9; color: #1b5e20; font-size: 12px; }
								.xwpstack-card-title { font-weight: 700; color: #0c1f4a; }
								.xwpstack-card-tagline { font-weight: 600; color: #0f2f6d; }
							</style>
							<div class="row">
								<div class="col-12">
									<div class="xwpstack-hero p-4 p-lg-5">
										<div class="row g-4 align-items-center content">
											<div class="col-lg-8">
												<div class="d-flex align-items-center gap-2 mb-3">
													<span class="xwpstack-pill"><?php esc_html_e( 'xWPStack • Premium plugin suite', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="badge bg-light text-dark" style="border-radius:12px;"><?php esc_html_e( 'Performance without compromises', 'hide-admin-bar-based-on-user-roles' ); ?></span>
												</div>
												<h3 class="xwpstack-heading mb-3"><?php esc_html_e( 'Build, market, and scale—minus the plugin bloat.', 'hide-admin-bar-based-on-user-roles' ); ?></h3>
												<p class="xwpstack-sub mb-4">
													<?php esc_html_e( 'A high-polish stack for teams who care about lighthouse scores, privacy, and conversions. Zero duct tape—just fast, interoperable plugins built to ship content and commerce at scale.', 'hide-admin-bar-based-on-user-roles' ); ?>
												</p>
												<div class="d-flex flex-wrap gap-2 mb-4">
													<span class="xwpstack-feature-chip"><?php esc_html_e( 'Performance-first', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-feature-chip"><?php esc_html_e( 'Privacy-friendly analytics', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-feature-chip"><?php esc_html_e( 'AI-assisted SEO', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-feature-chip"><?php esc_html_e( 'Editor-loved workflows', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-feature-chip"><?php esc_html_e( 'Conversion-safe UX', 'hide-admin-bar-based-on-user-roles' ); ?></span>
												</div>
												<div class="d-flex flex-wrap gap-2">
													<a href="https://pluginstack.dev/" target="_blank" class="btn btn-light btn-lg text-primary fw-bold"><?php esc_html_e( 'Explore the stack', 'hide-admin-bar-based-on-user-roles' ); ?></a>
													<a href="https://pluginstack.dev/plugins" target="_blank" class="btn btn-outline-light btn-lg text-light"><?php esc_html_e( 'See all plugins', 'hide-admin-bar-based-on-user-roles' ); ?></a>
												</div>
											</div>
											<div class="col-lg-4 ms-lg-auto">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row mt-4 xwpstack-grid">
								<?php
								$plugins = array(
									array(
										'title'       => __( '01 • UltimaKit for WP', 'hide-admin-bar-based-on-user-roles' ),
										'tagline'     => __( 'All-in-one WordPress operating system.', 'hide-admin-bar-based-on-user-roles' ),
										'description' => __( '192+ performance-tuned modules for SEO, security, publishing, WooCommerce, and Gravity Forms—replace a dozen plugins with one.', 'hide-admin-bar-based-on-user-roles' ),
										'link'        => 'https://pluginstack.dev/plugins/ultimakit-for-wp/',
										'link_text'   => __( 'View Details', 'hide-admin-bar-based-on-user-roles' ),
									),
									array(
										'title'       => __( '02 • Page Visit Counter Analytics', 'hide-admin-bar-based-on-user-roles' ),
										'tagline'     => __( 'Privacy-friendly analytics for WordPress.', 'hide-admin-bar-based-on-user-roles' ),
										'description' => __( 'First-party, cookie-light analytics with referrers, funnels, heatmaps, and reports you can actually ship to legal.', 'hide-admin-bar-based-on-user-roles' ),
										'link'        => 'https://pluginstack.dev/plugins/page-visit-counter-analytics/',
										'link_text'   => __( 'View Details', 'hide-admin-bar-based-on-user-roles' ),
									),
									array(
										'title'       => __( '03 • xSEOKit', 'hide-admin-bar-based-on-user-roles' ),
										'tagline'     => __( 'AI-powered SEO tools for WordPress.', 'hide-admin-bar-based-on-user-roles' ),
										'description' => __( 'AI-assisted meta titles, descriptions, and content scoring with safeguards for speed and brand voice.', 'hide-admin-bar-based-on-user-roles' ),
										'link'        => 'https://pluginstack.dev/plugins/xseokit/',
										'link_text'   => __( 'View Details', 'hide-admin-bar-based-on-user-roles' ),
									),
									array(
										'title'       => __( '04 • Hide Admin Bar Pro', 'hide-admin-bar-based-on-user-roles' ),
										'tagline'     => __( 'Clean up your WordPress admin experience.', 'hide-admin-bar-based-on-user-roles' ),
										'description' => __( 'Granular visibility rules for roles, users, and paths so teams see only what they need.', 'hide-admin-bar-based-on-user-roles' ),
										'link'        => 'https://pluginstack.dev/plugins/hide-admin-bar-pro/',
										'link_text'   => __( 'View Details', 'hide-admin-bar-based-on-user-roles' ),
									),
									array(
										'title'       => __( '05 • UltimaKit for Gravity Forms', 'hide-admin-bar-based-on-user-roles' ),
										'tagline'     => __( 'Enhance Gravity Forms with powerful add-ons.', 'hide-admin-bar-based-on-user-roles' ),
										'description' => __( 'Deep Gravity Forms add-ons: analytics, AI helpers, automations, and UX upgrades that stay lightweight.', 'hide-admin-bar-based-on-user-roles' ),
										'link'        => 'https://pluginstack.dev/plugins/ultimakit-for-gravity-forms/',
										'link_text'   => __( 'View Details', 'hide-admin-bar-based-on-user-roles' ),
									),
								);

								foreach ( $plugins as $plugin ) :
									?>
									<div class="col-md-6 col-lg-4 mb-3">
										<div class="card h-100">
											<div class="card-body d-flex flex-column">
												<div class="mb-2 fw-bold text-primary"><?php echo esc_html( $plugin['title'] ); ?></div>
												<div class="mb-2 text-dark"><?php echo esc_html( $plugin['tagline'] ); ?></div>
												<p class="text-secondary flex-grow-1"><?php echo esc_html( $plugin['description'] ); ?></p>
												<div class="d-flex flex-wrap gap-2 mb-3">
													<span class="xwpstack-tag"><?php esc_html_e( 'Fast', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-tag"><?php esc_html_e( 'Lightweight', 'hide-admin-bar-based-on-user-roles' ); ?></span>
													<span class="xwpstack-tag"><?php esc_html_e( 'Easy to Use', 'hide-admin-bar-based-on-user-roles' ); ?></span>
												</div>
												<a class="btn btn-outline-primary w-100" href="<?php echo esc_url( $plugin['link'] ); ?>" target="_blank"><?php echo esc_html( $plugin['link_text'] ); ?></a>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div> <!-- xWPStack Tab End --->
						

					</div>
					<!-- Duplicate the above block for each module you have -->
				</div>
			</div>
		</div>

		<?php
	}

	public function save_user_roles() {
		global $wpdb;

		if ( current_user_can( 'manage_options' ) && wp_verify_nonce( $_POST['hbaNonce'], 'hba-nonce' ) ) {

			// Sanitize and validate all inputs
			$UserRoles      = isset($_REQUEST['UserRoles']) ? array_map('sanitize_text_field', (array)$_REQUEST['UserRoles']) : array();
			$caps           = isset($_REQUEST['caps']) ? sanitize_text_field(str_replace("&nbsp;", "", $_REQUEST['caps'])) : '';
			$disableForAll  = isset($_REQUEST['disableForAll']) ? sanitize_text_field($_REQUEST['disableForAll']) : 'no';
			$forGuests      = isset($_REQUEST['forGuests']) ? sanitize_text_field($_REQUEST['forGuests']) : 'no';

			// Validate disableForAll is either 'yes' or 'no'
			$disableForAll = in_array($disableForAll, array('yes', 'no')) ? $disableForAll : 'no';
			
			// Validate forGuests is either 'yes' or 'no'
			$forGuests = in_array($forGuests, array('yes', 'no')) ? $forGuests : 'no';

			$settings                      = array();
			$settings['hab_disableforall'] = $disableForAll;

			if ( $disableForAll == 'no' ) {
				$settings['hab_userRoles']           = $UserRoles;
				$settings['hab_capabilities']        = $caps;
				$settings['hab_disableforallGuests'] = $forGuests;
			}
			update_option( "hab_settings", $settings );
			echo "Success";
		} else {
			echo "Failed";
		}
		wp_die();
	}

	public function upgrader_process_complete() {
	}

	public function enqueue_silent_installer() {
    }

    public function check_plugin_status() {
        // Check nonce
        if (!check_ajax_referer('silent_installer', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }

        // Check user capabilities
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => 'You do not have permission to install plugins.'));
        }

        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        
        if (empty($plugin_slug)) {
            wp_send_json_error(array('message' => 'Plugin slug is required.'));
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
        $all_plugins = get_plugins();
        $plugin_base_file = false;
        
        foreach ($all_plugins as $file => $plugin) {
            if (strpos($file, $plugin_slug . '/') === 0) {
                $plugin_base_file = $file;
                break;
            }
        }

        wp_send_json_success(array(
            'installed' => !empty($plugin_base_file),
            'active' => !empty($plugin_base_file) && is_plugin_active($plugin_base_file)
        ));
    }

    public function handle_silent_install_plugin() {
        // Check nonce
        if (!check_ajax_referer('silent_installer', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }

        // Check user capabilities
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => 'You do not have permission to install plugins.'));
        }

        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        
        if (empty($plugin_slug)) {
            wp_send_json_error(array('message' => 'Plugin slug is required.'));
        }

        // Include required files
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

        // Check if plugin is already installed
        $installed_plugins = get_plugins();
        $plugin_base_file = false;

        foreach ($installed_plugins as $file => $plugin) {
            if (strpos($file, $plugin_slug . '/') === 0) {
                $plugin_base_file = $file;
                break;
            }
        }

        // If plugin is not installed, install it
        if (!$plugin_base_file) {
            try {
                // Get plugin info
                $api = plugins_api('plugin_information', array(
                    'slug' => $plugin_slug,
                    'fields' => array(
                        'short_description' => false,
                        'sections' => false,
                        'requires' => false,
                        'rating' => false,
                        'ratings' => false,
                        'downloaded' => false,
                        'last_updated' => false,
                        'added' => false,
                        'tags' => false,
                        'compatibility' => false,
                        'homepage' => false,
                        'donate_link' => false,
                    ),
                ));

                if (is_wp_error($api)) {
                    wp_send_json_error(array('message' => $api->get_error_message()));
                }

                $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
                $install_result = $upgrader->install($api->download_link);

                if (is_wp_error($install_result)) {
                    wp_send_json_error(array('message' => $install_result->get_error_message()));
                }

                $plugin_base_file = $upgrader->plugin_info();

            } catch (Exception $e) {
                wp_send_json_error(array('message' => $e->getMessage()));
            }
        }

        // Activate the plugin
        if ($plugin_base_file) {
            try {
                $activation_result = activate_plugin($plugin_base_file);
                
                if (is_wp_error($activation_result)) {
                    wp_send_json_error(array('message' => $activation_result->get_error_message()));
                }

                wp_send_json_success(array(
                    'message' => 'Plugin installed and activated successfully',
                    'plugin_file' => $plugin_base_file
                ));

            } catch (Exception $e) {
                wp_send_json_error(array('message' => $e->getMessage()));
            }
        } else {
            wp_send_json_error(array('message' => 'Plugin installation failed.'));
        }
    }

    public function hab_dismiss_review_banner() {
        if (!check_ajax_referer('hab_dismiss_review_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $user_id = get_current_user_id();
        $dismiss_type = sanitize_text_field($_POST['dismiss_type']);

        if ($dismiss_type === 'permanent') {
            update_user_meta($user_id, 'hab_hide_review_banner', 'permanent');
        } else if ($dismiss_type === '30days') {
            update_user_meta($user_id, 'hab_hide_review_until', time() + (30 * DAY_IN_SECONDS));
        } else if ($dismiss_type === 'now') {
            // Set to show again after 15 days
            update_user_meta($user_id, 'hab_hide_review_until', time() + (15 * DAY_IN_SECONDS));
        }

        wp_send_json_success();
    }
}
