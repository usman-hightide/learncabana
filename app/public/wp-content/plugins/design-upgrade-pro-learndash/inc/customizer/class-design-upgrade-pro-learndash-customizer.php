<?php

/**
 * Implements Customizer functionality for "LearnDash 3.0"
 *
 * Add custom sections and settings to the Customizer.
 *
 * @package   design-upgrade-pro-learndash
 * @copyright Copyright (c) 2018, Escape Creative, LLC
 * @license   GPL2+
 */
class LDX_Design_Upgrade_Pro_Learndash_Customizer {

	/**
	 * LDX_Design_Upgrade_Pro_Learndash_Customizer constructor.
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function __construct() {

		add_action( 'customize_register', array( $this, 'ldx_register_customize_sections' ) );

	}

	/**
	 * Add all sections and panels to the Customizer
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function ldx_register_customize_sections( $wp_customize ) {

		// Set default template as "legacy"
		$template = 'legacy';
		// If LD3 option exists, check & reassign the $template variable
		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			$template = \LearnDash_Theme_Register::get_active_theme_key();
		}

		if ( 'legacy' === $template ) { // @legacy

			/*
			 * Add Panels
			 */

			// Panel containing all LearnDash sub-sections (General, Colors, etc.)
			$wp_customize->add_panel( 'ldx_learndash_styles_panel', array(
				'title' => 'LearnDash',
				'description' => __( 'These styles only apply to LearnDash elements. Use the window on the right to visit a page that contains LearnDash content.', 'design-upgrade-pro-learndash' ),
				'priority' => 160 // Mixed with top-level-section hierarchy.
			) );

			// General
			$wp_customize->add_section( 'ldx_learndash_general', array(
				'title'    => __( 'General Design', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 110
			) );

			// Lesson, Topic, Quiz Lists
			$wp_customize->add_section( 'ldx_learndash_list_tables', array(
				'title'    => LearnDash_Custom_Label::get_label( 'lesson' ) . ', ' . LearnDash_Custom_Label::get_label( 'topic' ) . ', ' . LearnDash_Custom_Label::get_label( 'quiz' ) . ' Lists',
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 120
			) );

			// Progress Bar
			$wp_customize->add_section( 'ldx_learndash_progress_bar', array(
				'title'    => __( 'Progress Bar', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 130
			) );

			// Buttons
			$wp_customize->add_section( 'ldx_learndash_buttons', array(
				'title'    => __( 'Buttons', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 140
			) );

			// Profile
			$wp_customize->add_section( 'ldx_learndash_profile', array(
				'title'    => __( 'Profile', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 160
			) );

			// Topic Pages
			$wp_customize->add_section( 'ldx_learndash_topics', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'topic' ) . ' Pages', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 170
			) );

			// Course Navigation Widget
			$wp_customize->add_section( 'ldx_learndash_widget_coursenav', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'course' ) . ' Navigation Widget', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 180
			) );

			// Course Grid
			// 9/23/2019 - No longer checking for course grid add-on to be active.
			// It will now always appear.

			$wp_customize->add_section( 'ldx_learndash_course_grid', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'course' ) . ' Grid', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles apply to the LearnDash Course Grid add-on and/or the Uncanny Toolkit Pro for LearnDash Enhanced Course Grid.', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx_learndash_styles_panel',
				'priority' => 190
			) );

			$this->ldx_learndash_course_grid_section( $wp_customize );

			/*
			 * Add settings to sections.
			 */
			$this->ldx_learndash_general_section( $wp_customize );
			$this->ldx_learndash_list_tables_section( $wp_customize );
			$this->ldx_learndash_progress_bar_section( $wp_customize );
			$this->ldx_learndash_buttons_section( $wp_customize );
			$this->ldx_learndash_profile_section( $wp_customize );
			$this->ldx_learndash_topics_section( $wp_customize );
			$this->ldx_learndash_widget_coursenav_section( $wp_customize );

		} else { // @ld3

			/*
			 * Add Panels
			 */

			// Panel containing all LearnDash sub-sections (General, Colors, etc.)
			$wp_customize->add_panel( 'ldx3_learndash_styles_panel', array(
				'title' => 'LearnDash Design Upgrade',
				'description' => __( 'These styles only apply to LearnDash elements. Use the window on the right to visit a page that contains LearnDash content.', 'design-upgrade-pro-learndash' ),
				'priority' => 160 // Mixed with top-level-section hierarchy.
			) );

			// General
			$wp_customize->add_section( 'ldx3_learndash_general', array(
				'title'    => __( 'General Design', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Used in various places throughout the learning interface. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-general-design/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 110
			) );

			// Buttons
			$wp_customize->add_section( 'ldx3_learndash_buttons', array(
				'title'    => __( 'Buttons', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles only affect LearnDash buttons (previous/next ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'lesson' ) . __( ', mark complete, expand all, etc.). <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-buttons/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 120
			) );

			// Course Content Lists
			$wp_customize->add_section( 'ldx3_learndash_list_tables', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'course' ) . ' Content Lists', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Applies to "', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Content" at the bottom of ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'course' ) . __( ' pages, as well as "', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Content", ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' and assignment lists. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-course-content-lists/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 130
			) );

			// Infobar
			$wp_customize->add_section( 'ldx3_learndash_course_page', array(
				'title'    => __( 'Infobar', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles affect the infobar at the top of ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'course' ) . __( ' and ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'group' ) . __( ' pages, for users who are <strong>not</strong> enrolled. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-infobar/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 140
			) );

			// Focus Mode
			$wp_customize->add_section( 'ldx3_learndash_focus_mode', array(
				'title'    => __( 'Focus Mode', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles apply to all Focus Mode pages when Focus Mode is enabled in your LearnDash settings. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-focus-mode/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 150
			) );

			// Course Navigation
			// Applies to both Focus Mode sidebar AND course nav widget
			$wp_customize->add_section( 'ldx3_learndash_course_nav', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'course' ) . ' Navigation', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles apply to the Focus Mode sidebar AND the ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Navigation widget. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-course-navigation/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 160
			) );

			// Profile
			$wp_customize->add_section( 'ldx3_learndash_profile', array(
				'title'    => __( 'Profile', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles apply to the "LearnDash Profile" block and [ld_profile] shortcode. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-profile/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 170
			) );

			// Progress Bar
			$wp_customize->add_section( 'ldx3_learndash_progress_bar', array(
				'title'    => __( 'Progress Bar', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Used in several places to indicate a user\'s progress. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-progress-bar/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 180
			) );

			// Alerts
			$wp_customize->add_section( 'ldx3_learndash_alerts', array(
				'title'    => __( 'Alerts', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Alerts are short messages displayed after an action is taken, or when further action is necessary. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-alerts/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 190
			) );

			// Tooltips
			$wp_customize->add_section( 'ldx3_learndash_tooltips', array(
				'title'    => __( 'Tooltips', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Tooltips are the small notes that appear when you hover over certain LearnDash elements & icons. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-tooltips/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 200
			) );

			// Login & Registration
			$wp_customize->add_section( 'ldx3_learndash_login_register', array(
				'title'    => __( 'Login & Registration', 'design-upgrade-pro-learndash' ),
				'description' => __( 'These styles apply to the LearnDash login & registration modal when it is enabled in your LearnDash settings. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-login-registration/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 210
			) );

			// Course Grid
			// 9/23/2019 - No longer checking for course grid add-on to be active.
			// It will now always appear.

			$wp_customize->add_section( 'ldx3_learndash_course_grid', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'course' ) . ' Grid', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Styles apply to the LearnDash Course Grid add-on – whether used for displaying courses, lessons, topics, quizzes or groups – and the Uncanny Toolkit Pro for LearnDash Enhanced Course Grid. <strong>Not compatible</strong> with the new layouts in Course Grid 2.x. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-course-grid/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 220
			) );

			// Pagination
			$wp_customize->add_section( 'ldx3_learndash_pagination', array(
				'title'    => __( 'Pagination', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Styles apply to all instances of LearnDash pagination – course content lists, focus mode sidebar, course navigation widget &amp; the course grid. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-pagination/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 230
			) );

			if ( defined( 'LEARNDASH_ACHIEVEMENTS_FILE' ) ) {

				// Achievements (add-on)
				$wp_customize->add_section( 'ldx3_learndash_achievements', array(
					'title'       => __( 'Achievements', 'design-upgrade-pro-learndash' ),
					'description' => __( 'Styles apply to the LearnDash Achievements add-on. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-achievements/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
					'panel'       => 'ldx3_learndash_styles_panel',
					'priority'    => 235
				) );

			}

			// Group Courses List (added in LearnDash 3.2)
			$wp_customize->add_section( 'ldx3_learndash_group_courses_list', array(
				'title'    => __( LearnDash_Custom_Label::get_label( 'group' ) . ' ' . LearnDash_Custom_Label::get_label( 'courses' ) .  ' List', 'design-upgrade-pro-learndash' ),
				'description' => __( 'Styles apply to the list of ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'courses' ) . __( ' on a single ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'group' ) . __( ' page. Requires LearnDash 3.2 or higher. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-group-courses-list/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
				'panel'    => 'ldx3_learndash_styles_panel',
				'priority' => 240
			) );

			// WisdmLabs: Ratings, Review & Feedback
			if ( defined( 'WDM_LD_COURSE_VERSION' ) ) {

				$wp_customize->add_section( 'ldx3_wisdm_ratings_reviews', array(
					'title'    => __( 'Ratings, Reviews & Feedback', 'design-upgrade-pro-learndash' ),
					'description' => __( 'Styles apply to the WisdmLabs Ratings, Reviews & Feedback plugin. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-wisdmlabs-ratings-reviews-feedback/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
					'panel'    => 'ldx3_learndash_styles_panel',
					'priority' => 250
				) );

			} // if WDM_LD_COURSE_VERSION

			// Tin Canny Reporting
			if ( defined( 'UO_REPORTING_FILE' ) ) {

				$wp_customize->add_section( 'ldx3_tincanny_reporting', array(
					'title'    => __( 'Tin Canny Reporting', 'design-upgrade-pro-learndash' ),
					'description' => __( 'Styles apply to the Uncanny Owl Tin Canny Reporting plugin. <a href="https://ldx.training/course/using-design-upgrade-learndash/lesson/du-tin-canny-reporting/" target="_blank">Docs →</a>', 'design-upgrade-pro-learndash' ),
					'panel'    => 'ldx3_learndash_styles_panel',
					'priority' => 260
				) );

			} // if UO_REPORTING_FILE

			/*
			 * Add settings to sections.
			 */
			$this->ldx3_learndash_general_section( $wp_customize );
			$this->ldx3_learndash_buttons_section( $wp_customize );
			$this->ldx3_learndash_list_tables_section( $wp_customize );
			$this->ldx3_learndash_focus_mode_section( $wp_customize );
			$this->ldx3_learndash_profile_section( $wp_customize );
			$this->ldx3_learndash_course_page_section( $wp_customize );
			$this->ldx3_learndash_course_nav_section( $wp_customize );
			$this->ldx3_learndash_progress_bar_section( $wp_customize );
			$this->ldx3_learndash_alerts_section( $wp_customize );
			$this->ldx3_learndash_tooltips_section( $wp_customize );
			$this->ldx3_learndash_login_register_section( $wp_customize );
			$this->ldx3_learndash_course_grid_section( $wp_customize );
			$this->ldx3_learndash_pagination_section( $wp_customize );
			if ( defined( 'LEARNDASH_ACHIEVEMENTS_FILE' ) ) {
				$this->ldx3_learndash_achievements_section( $wp_customize );
			}
			$this->ldx3_learndash_group_courses_list_section( $wp_customize );
			$this->ldx3_wisdm_ratings_reviews_section( $wp_customize );
			$this->ldx3_tincanny_reporting_section( $wp_customize );

		} // end legacy vs. ld3

	} // function ldx_register_customize_sections()


	/**
	 * General Design: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_general_section( $wp_customize ) {

		/* Global Border Radius */
		$wp_customize->add_setting( 'ldx3_design_upgrade[global_border_radius]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[global_border_radius]', array(
			'label'       => __( 'Global Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Used on various LearnDash elements (containers, alerts, tooltips, menus, etc.). Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_general',
			'settings' => 'ldx3_design_upgrade[global_border_radius]',
			'priority' => 9
		) );

		/* Link Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[color_link]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[color_link]', array(
			'label'    => __( 'Link Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_general',
			'settings' => 'ldx3_design_upgrade[color_link]',
			'priority' => 10
		) ) );

		/* Link Hover Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[color_link_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[color_link_hover]', array(
			'label'    => __( 'Link Hover Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_general',
			'settings' => 'ldx3_design_upgrade[color_link_hover]',
			'priority' => 20
		) ) );

		/* Correct/Complete Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[color_correct]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[color_correct]', array(
			'label'       => __( 'Complete/Correct Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'We recommend some variation of green. Used for completed icons, correct answers, positive results, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_general',
			'settings'    => 'ldx3_design_upgrade[color_correct]',
			'priority'    => 30
		) ) );

		/* "Incorrect" Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[color_incorrect]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[color_incorrect]', array(
			'label'       => __( 'Incorrect Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'We recommend some variation of red. Used for incorrect answers, negative results, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_general',
			'settings'    => 'ldx3_design_upgrade[color_incorrect]',
			'priority'    => 31
		) ) );

		/* "Incorrect" Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[color_in_progress]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[color_in_progress]', array(
			'label'       => __( 'In Progress Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Currently only being used with some third-party integrations (BuddyBoss, Tin Canny Reporting)', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_general',
			'settings'    => 'ldx3_design_upgrade[color_in_progress]',
			'priority'    => 32
		) ) );

	}


	/**
	 * Buttons: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_buttons_section( $wp_customize ) {

		/* Button Border Radius */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_border_radius]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[btn_border_radius]', array(
			'label'       => __( 'Button Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_buttons',
			'settings' => 'ldx3_design_upgrade[btn_border_radius]',
			'priority' => 9
		) );

		/* Primary Button Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_primary_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_primary_bg_color]', array(
			'label'       => __( 'Primary Button', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Used for primary actions like <em>Take this ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'course' ) . __( '</em>, <em>Mark Complete</em>, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_primary_bg_color]',
			'priority'    => 10
		) ) );

		/* Primary Button Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_primary_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_primary_text_color]', array(
			'label'       => __( 'Primary Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_primary_text_color]',
			'priority'    => 11
		) ) );

		/* Primary Button Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_primary_bg_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_primary_bg_color_hover]', array(
			'label'       => __( 'Hover: Primary Button', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_primary_bg_color_hover]',
			'priority'    => 12
		) ) );

		/* Primary Button Text Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_primary_text_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_primary_text_color_hover]', array(
			'label'       => __( 'Hover: Primary Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_primary_text_color_hover]',
			'priority'    => 13
		) ) );

		/* Standard Button Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_standard_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_standard_bg_color]', array(
			'label'       => __( 'Standard Button', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Used for secondary actions like next/previous navigation, expand/collapse all, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_standard_bg_color]',
			'priority'    => 20
		) ) );

		/* Standard Button Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_standard_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_standard_text_color]', array(
			'label'       => __( 'Standard Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_standard_text_color]',
			'priority'    => 21
		) ) );

		/* Standard Button Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_standard_bg_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_standard_bg_color_hover]', array(
			'label'       => __( 'Hover: Standard Button', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_standard_bg_color_hover]',
			'priority'    => 22
		) ) );

		/* Standard Button Text Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[btn_standard_text_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[btn_standard_text_color_hover]', array(
			'label'       => __( 'Hover: Standard Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_buttons',
			'settings'    => 'ldx3_design_upgrade[btn_standard_text_color_hover]',
			'priority'    => 23
		) ) );

	}


	/**
	 * List Tables: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_list_tables_section( $wp_customize ) {

		/* List Tables: Disable Expand/Collapse */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_disable_expand_collapse]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_disable_expand_collapse]', array(
			'label'    => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove Expand/Collapse links & always display all content.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_disable_expand_collapse]',
			'priority' => 5
		) );

		/* List Tables: Course Content Header Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_course_content_header]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_course_content_header]', array(
			'label'      => '"' . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Content" Header Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_list_tables',
			'settings'   => 'ldx3_design_upgrade[list_tables_course_content_header]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'boxed'      => __( 'Boxed', 'design-upgrade-pro-learndash' ),
				'hidden'     => __( 'Hidden', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 10
		) );

		/* List Tables: "Course Content" Header: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_course_content_header_bg]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_course_content_header_bg]', array(
			'label'    => '"' . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Content" Header Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_course_content_header_bg]',
			'priority' => 11
		) ) );

		/* List Tables: "Course Content" Header: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_course_content_header_text]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_course_content_header_text]', array(
			'label'    => '"' . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Content" Header Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_course_content_header_text]',
			'priority' => 12
		) ) );

		/* List Tables: Container Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_container_style]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_container_style]', array(
			'label'      => __( 'Container Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_list_tables',
			'settings'   => 'ldx3_design_upgrade[list_tables_container_style]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'boxed'      => __( 'Boxed', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 15
		) );

		/* List Tables: Container Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_container_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_container_bg_color]', array(
			'label'    => __( 'Container Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_container_bg_color]',
			'priority' => 16
		) ) );

		/* List Tables: Container Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_container_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_container_border_width]', array(
			'label'    => __( 'Container Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_container_border_width]',
			'priority' => 17
		) );

		/* List Tables: Container Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_container_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_container_border_color]', array(
			'label'    => __( 'Container Border', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_container_border_color]',
			'priority' => 18
		) ) );

		/* List Tables: Lesson Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_style]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_lesson_style]', array(
			'label'      => LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_list_tables',
			'settings'   => 'ldx3_design_upgrade[list_tables_lesson_style]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'table'      => __( 'Table', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 19
		) );

		/* List Tables: Section Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_section_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_section_bg_color]', array(
			'label'    => __( 'Section Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_section_bg_color]',
			'priority' => 20
		) ) );

		/* List Tables: Section Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_section_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_section_text_color]', array(
			'label'    => __( 'Section Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_section_text_color]',
			'priority' => 21
		) ) );

		/* List Tables: "Lesson Content" Header: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_header_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_header_bg_color]', array(
			'label'    => '"' . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Content" Header Background', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Also applies to ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'topic' ) . ', ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' & assignment lists.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_header_bg_color]',
			'priority' => 25
		) ) );

		/* List Tables: "Lesson Content" Header: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_header_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_header_text_color]', array(
			'label'    => '"' . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Content" Header Text', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Also applies to ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'topic' ) . ', ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' & assignment lists.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_header_text_color]',
			'priority' => 26
		) ) );

		/* List Tables: Lesson Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_lesson_bg_color]', array(
			'label'       => LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Background', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Also applies to ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'topic' ) . ', ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' & assignment lists.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_list_tables',
			'settings'    => 'ldx3_design_upgrade[list_tables_lesson_bg_color]',
			'priority'    => 30
		) ) );

		/* List Tables: HOVER: Lesson Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_bg_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_lesson_bg_color_hover]', array(
			'label'       => __( 'Hover: ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_list_tables',
			'settings'    => 'ldx3_design_upgrade[list_tables_lesson_bg_color_hover]',
			'priority'    => 31
		) ) );

		/* List Tables: Lesson Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_lesson_text_color]', array(
			'label'       => LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Text', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Also applies to ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'topic' ) . ', ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' & assignment lists.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_list_tables',
			'settings'    => 'ldx3_design_upgrade[list_tables_lesson_text_color]',
			'priority'    => 32
		) ) );

		/* List Tables: HOVER: Lesson Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_text_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_lesson_text_color_hover]', array(
			'label'       => __( 'Hover: ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_list_tables',
			'settings'    => 'ldx3_design_upgrade[list_tables_lesson_text_color_hover]',
			'priority'    => 33
		) ) );

		/* List Tables: Lesson Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_lesson_border_width]', array(
			'label'    => LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_lesson_border_width]',
			'priority' => 34
		) );

		/* List Tables: Lesson Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_lesson_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_lesson_border_color]', array(
			'label'    => LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Border', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_lesson_border_color]',
			'priority' => 35
		) ) );

		/* List Tables: Hide "Lesson Content" Header */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_hide_lesson_content_header]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_hide_lesson_content_header]', array(
			'label'    => __( 'Hide "', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'lesson' ) . __( ' Content" Header', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_hide_lesson_content_header]',
			'priority' => 40
		) );

		/* List Tables: Line Separator Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_line_separator_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[list_tables_line_separator_color]', array(
			'label'    => __( 'Line Separators', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_line_separator_color]',
			'priority' => 50
		) ) );

		/* List Tables: Indent Topics */
		$wp_customize->add_setting( 'ldx3_design_upgrade[list_tables_indent_topics]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[list_tables_indent_topics]', array(
			'label'    => __( 'Indent ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'topics' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_list_tables',
			'settings' => 'ldx3_design_upgrade[list_tables_indent_topics]',
			'priority' => 55
		) );

	}


	/**
	 * Focus Mode: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_focus_mode_section( $wp_customize ) {

		/* Focus Mode: Content Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_content_width]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_content_width]', array(
			'label'      => __( 'Content Width', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_focus_mode',
			'settings'   => 'ldx3_design_upgrade[focus_mode_content_width]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Inherit LearnDash Setting', 'design-upgrade-pro-learndash' ),
				'stretched'  => __( 'Edge-to-Edge', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );

		/* Focus Mode: Main Content: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_content_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_content_bg_color]', array(
			'label'    => __( 'Content Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_content_bg_color]',
			'priority' => 8
		) ) );

		/* Focus Mode: Content Animation */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_content_animation]', array(
			'type'              => 'option',
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_content_animation]', array(
			'label'      => __( 'Content Animation', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_focus_mode',
			'settings'   => 'ldx3_design_upgrade[focus_mode_content_animation]',
			'type'       => 'select',
			'choices'    => array(
				'none'       => __( 'None', 'design-upgrade-pro-learndash' ),
				'fade-right' => __( 'Fade In Right', 'design-upgrade-pro-learndash' ),
				'fade-left'  => __( 'Fade In Left', 'design-upgrade-pro-learndash' ),
				'fade-up'    => __( 'Fade In Up', 'design-upgrade-pro-learndash' ),
				'fade-down'  => __( 'Fade In Down', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 10
		) );

		/* Focus Mode: Hide Page Title */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_page_title]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_page_title]', array(
			'label'    => __( 'Hide Page Title', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_page_title]',
			'priority' => 15
		) );

		/* Focus Mode: Hide Breadcrumbs */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_breadcrumbs]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_breadcrumbs]', array(
			'label'    => __( 'Hide Breadcrumbs', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_breadcrumbs]',
			'priority' => 20
		) );

		/* Focus Mode: Hide Bottom Buttons */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_bottom_actions]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_bottom_actions]', array(
			'label'    => __( 'Hide ALL Bottom Buttons', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_bottom_actions]',
			'priority' => 25
		) );

		/* Focus Mode: Hide "Back to" Link */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_backto_link]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_backto_link]', array(
			'label'    => __( 'Hide "Back to..." Link', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_backto_link]',
			'priority' => 26
		) );

		/* Focus Mode: User Display Name */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_display_name]', array(
			'type'              => 'option',
			'default'           => 'username',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_display_name]', array(
			'label'      => __( 'User Display Name', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_focus_mode',
			'settings'   => 'ldx3_design_upgrade[focus_mode_display_name]',
			'type'       => 'select',
			'choices'    => array(
				'username'       => __( 'Username (default)', 'design-upgrade-pro-learndash' ),
				'firstname'      => __( 'First Name', 'design-upgrade-pro-learndash' ),
				'fullname'       => __( 'First & Last Name', 'design-upgrade-pro-learndash' ),
				'displayname'    => __( 'Display Name', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 29
		) );

		/* Focus Mode: Avatar Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_avatar_style]', array(
			'type'              => 'option',
			'default'           => 'circle',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_avatar_style]', array(
			'label'      => __( 'Avatar Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_focus_mode',
			'settings'   => 'ldx3_design_upgrade[focus_mode_avatar_style]',
			'type'       => 'select',
			'choices'    => array(
				'circle'    => __( 'Circle', 'design-upgrade-pro-learndash' ),
				'square'    => __( 'Square', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 30
		) );

		/* Focus Mode: Hide Avatar */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_avatar]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_avatar]', array(
			'label'    => __( 'Hide Avatar', 'design-upgrade-pro-learndash' ),
			'type'     => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_avatar]',
			'priority' => 31
		) );

		/* Focus Mode: Hide Name */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_hide_name]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_hide_name]', array(
			'label'    => __( 'Hide Name', 'design-upgrade-pro-learndash' ),
			'type'     => 'checkbox',
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_hide_name]',
			'priority' => 32
		) );

		/* Focus Mode: Dropdown Menu: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_topmenu_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_topmenu_bg_color]', array(
			'label'    => __( 'Dropdown Menu: Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_topmenu_bg_color]',
			'priority' => 40
		) ) );

		/* Focus Mode: Dropdown Menu: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_topmenu_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_topmenu_text_color]', array(
			'label'    => __( 'Dropdown Menu: Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_topmenu_text_color]',
			'priority' => 41
		) ) );

		/* Focus Mode: Sidebar: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_sidebar_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_sidebar_bg_color]', array(
			'label'    => __( 'Sidebar Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_sidebar_bg_color]',
			'priority' => 50
		) ) );

		/* Focus Mode: Sidebar: Course Title Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_sidebar_course_bg]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_sidebar_course_bg]', array(
			'label'    => __( 'Sidebar: ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_sidebar_course_bg]',
			'priority' => 55
		) ) );

		/* Focus Mode: Sidebar: Course Title Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_sidebar_course_text]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_sidebar_course_text]', array(
			'label'    => __( 'Sidebar: ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'course' ) . __( ' Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_sidebar_course_text]',
			'priority' => 55
		) ) );

		/* Comments: Avatar Shape */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_comments_avatar_shape]', array(
			'type'              => 'option',
			'default'           => 'circle',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_comments_avatar_shape]', array(
			'label'      => __( 'Comments: Avatar Shape', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_focus_mode',
			'settings'   => 'ldx3_design_upgrade[focus_mode_comments_avatar_shape]',
			'type'       => 'select',
			'choices'    => array(
				'circle'    => __( 'Circle', 'design-upgrade-pro-learndash' ),
				'square'    => __( 'Square', 'design-upgrade-pro-learndash' ),
				'inherit'   => __( 'Inherit Border Radius', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 60
		) );

		/* Comments: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_comments_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_comments_bg_color]', array(
			'label'    => __( 'Comments: Background Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_comments_bg_color]',
			'priority' => 65
		) ) );

		/* Admin Comments: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_admin_comments_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_admin_comments_bg_color]', array(
			'label'    => __( 'Admin Comments: Background Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_admin_comments_bg_color]',
			'priority' => 70
		) ) );

		/* Admin Comments: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_admin_comments_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_admin_comments_border_width]', array(
			'label'    => __( 'Admin Comments: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_admin_comments_border_width]',
			'priority' => 71
		) );

		/* Admin Comments: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_admin_comments_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_admin_comments_border_color]', array(
			'label'    => __( 'Admin Comments: Border Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_admin_comments_border_color]',
			'priority' => 72
		) ) );

		/* Admin Comments: Avatar Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_width]', array(
			'label'    => __( 'Admin Comments: Avatar Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_width]',
			'priority' => 73
		) );

		/* Admin Comments: Avatar Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_color]', array(
			'label'    => __( 'Admin Comments: Avatar Border Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_focus_mode',
			'settings' => 'ldx3_design_upgrade[focus_mode_admin_comments_avatar_border_color]',
			'priority' => 74
		) ) );

	}


	/**
	 * Course Navigation: @ld3
	 *
	 * Applies to both Focus Mode sidebar AND course navigation widget.
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_course_nav_section( $wp_customize ) {

		/* Course Nav: Disable Expand/Collapse */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_disable_expand_collapse]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[coursenav_disable_expand_collapse]', array(
			'label'       => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Always display all ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'topics' ) . __( ' and ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'quizzes' ) . '.',
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_nav',
			'settings'    => 'ldx3_design_upgrade[coursenav_disable_expand_collapse]',
			'priority'    => 5
		) );

		/* Course Nav: Strikethrough Completed Items */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_strikethrough_completed]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[coursenav_strikethrough_completed]', array(
			'label'       => __( 'Strikethrough Completed Items', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_nav',
			'settings'    => 'ldx3_design_upgrade[coursenav_strikethrough_completed]',
			'priority'    => 6
		) );

		/* Course Nav: Section: BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_section_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_section_bg_color]', array(
			'label'    => __( 'Section Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_section_bg_color]',
			'priority' => 10
		) ) );

		/* Course Nav: Section: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_section_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_section_text_color]', array(
			'label'    => __( 'Section Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_section_text_color]',
			'priority' => 11
		) ) );

		/* Course Nav: Link Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_link_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_link_text_color]', array(
			'label'    => __( 'Link Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_link_text_color]',
			'priority' => 20
		) ) );

		/* Course Nav: Hover: Link BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_link_bg_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_link_bg_color_hover]', array(
			'label'    => __( 'Hover: Link Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_link_bg_color_hover]',
			'priority' => 25
		) ) );

		/* Course Nav: Hover: Link Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_link_text_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_link_text_color_hover]', array(
			'label'    => __( 'Hover: Link Text', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_link_text_color_hover]',
			'priority' => 26
		) ) );

		/* Course Nav: Line Separator Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[coursenav_line_separator_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[coursenav_line_separator_color]', array(
			'label'    => __( 'Line Separators', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_nav',
			'settings' => 'ldx3_design_upgrade[coursenav_line_separator_color]',
			'priority' => 30
		) ) );

	}


	/**
	 * Progress Bar: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_progress_bar_section( $wp_customize ) {

		/* Progress Bar Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_style]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_style]', array(
			'label'      => __( 'Progress Bar Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_progress_bar',
			'settings'   => 'ldx3_design_upgrade[progress_bar_style]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'striped'    => __( 'Striped', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );


		/* Progress Bar Container Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_container_bg]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[progress_bar_container_bg]', array(
			'label'    => __( 'Progress Bar Container', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_container_bg]',
			'priority' => 10
		) ) );

		/* Progress Bar Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_bg]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[progress_bar_bg]', array(
			'label'    => __( 'Progress Bar', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_bg]',
			'priority' => 11
		) ) );

		/* Progress Bar Border Radius */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_border_radius]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_border_radius]', array(
			'label'    => __( 'Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_border_radius]',
			'priority' => 20
		) );

		/* Progress Bar Height */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_height]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_height]', array(
			'label'    => __( 'Height', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_height]',
			'priority' => 30
		) );

		/* Progress Bar Animation */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_animation]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_animation]', array(
			'label'    => __( 'Animate', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'description' => __( 'Add a smooth animation to the progress bar on each page load.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_animation]',
			'priority' => 40
		) );

		/* Hide % Complete */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_hide_percent_complete]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_hide_percent_complete]', array(
			'label'    => __( 'Hide "% Complete"', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_hide_percent_complete]',
			'priority' => 45
		) );

		/* Hide Steps */
		$wp_customize->add_setting( 'ldx3_design_upgrade[progress_bar_hide_steps]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[progress_bar_hide_steps]', array(
			'label'    => __( 'Hide "X/Y Steps"', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_progress_bar',
			'settings' => 'ldx3_design_upgrade[progress_bar_hide_steps]',
			'priority' => 50
		) );

	}


	/**
	 * Profile: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_profile_section( $wp_customize ) {

		/* Profile Summary Layout */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_summary_layout]', array(
			'type'              => 'option',
			'default'           => 'stacked',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_summary_layout]', array(
			'label'       => __( 'Profile Summary: Layout', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_summary_layout]',
			'type'        => 'select',
			'choices'     => array(
				'stacked'     => __( 'Stacked (default)', 'design-upgrade-pro-learndash' ),
				'horizontal'  => __( 'Horizontal', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 1
		) );

		/* Profile Summary: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_summary_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[profile_summary_bg_color]', array(
			'label'       => __( 'Profile Summary: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_summary_bg_color]',
			'priority'    => 2
		) ) );

		/* Profile Summary: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_summary_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[profile_summary_text_color]', array(
			'label'       => __( 'Profile Summary: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_summary_text_color]',
			'priority'    => 3
		) ) );

		/* Profile Stats Layout */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_stats_layout]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_stats_layout]', array(
			'label'       => __( 'Profile Stats: Layout', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_stats_layout]',
			'type'        => 'select',
			'choices'     => array(
				'horizontal'  => __( 'Horizontal (default)', 'design-upgrade-pro-learndash' ),
				'stacked'     => __( 'Stacked', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 4
		) );

		/* Hide Section: User Info */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_user_section]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_user_section]', array(
			'label'    => __( 'Hide Section: User Info', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_profile',
			'settings' => 'ldx3_design_upgrade[profile_hide_user_section]',
			'priority' => 5
		) );

		/* Hide Section: Stats */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_stats_section]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_stats_section]', array(
			'label'    => __( 'Hide Section: Statistics', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_profile',
			'settings' => 'ldx3_design_upgrade[profile_hide_stats_section]',
			'priority' => 10
		) );

		/* Hide Section: Your Courses */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_courses_section]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_courses_section]', array(
			'label'    => __( 'Hide Section: Your ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'courses' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_profile',
			'settings' => 'ldx3_design_upgrade[profile_hide_courses_section]',
			'priority' => 15
		) );

		/* Avatar Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_avatar_style]', array(
			'type'              => 'option',
			'default'           => 'circle',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_avatar_style]', array(
			'label'       => __( 'Avatar Style', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_avatar_style]',
			'type'        => 'select',
			'choices'     => array(
				'circle'       => __( 'Circle', 'design-upgrade-pro-learndash' ),
				'square'       => __( 'Square', 'design-upgrade-pro-learndash' ),
				'borderradius' => __( 'Inherit Global Border Radius', 'design-upgrade-pro-learndash' ),
				'hidden'       => __( 'Hidden', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 20
		) );

		/* Avatar Size */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_avatar_size]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_avatar_size]', array(
			'label'    => __( 'Avatar Size', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 150,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px). Max <code>150</code>.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_profile',
			'settings' => 'ldx3_design_upgrade[profile_avatar_size]',
			'priority' => 21
		) );

		/* Hide "Edit Profile" Link */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_edit_profile_link]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_edit_profile_link]', array(
			'label'       => __( 'Hide "Edit Profile" Link', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_edit_profile_link]',
			'priority'    => 25
		) );

		/* Custom URL for 'Edit Profile' Link */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_custom_edit_profile_url]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_custom_edit_profile_url]', array(
			'label'    => __( 'Custom "Edit Profile" URL', 'design-upgrade-pro-learndash' ),
			'type'        => 'url',
			'input_attrs' => array(
				'placeholder'   => 'https://'
			),
			'description' => __( 'Send users to a custom page when clicking the "Edit Profile" link. Full URL is required, including https://.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_profile',
			'settings' => 'ldx3_design_upgrade[profile_custom_edit_profile_url]',
			'priority' => 26
		) );

		/* Hide Stat: Courses */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_stat_courses]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_stat_courses]', array(
			'label'       => __( 'Hide Statistic: ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'courses' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_stat_courses]',
			'priority'    => 32
		) );

		/* Hide Stat: Completed */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_stat_completed]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_stat_completed]', array(
			'label'       => __( 'Hide Statistic: Completed', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_stat_completed]',
			'priority'    => 33
		) );

		/* Hide Stat: Certificates */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_stat_certificates]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_stat_certificates]', array(
			'label'       => __( 'Hide Statistic: Certificates', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_stat_certificates]',
			'priority'    => 34
		) );

		/* Hide Stat: Points */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_stat_points]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_stat_points]', array(
			'label'       => __( 'Hide Statistic: Points', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_stat_points]',
			'priority'    => 35
		) );

		/* Hide "Your Courses" Header */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_courses_header]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_courses_header]', array(
			'label'       => __( 'Hide "Your Courses" Header', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_courses_header]',
			'priority'    => 36
		) );

		/* Profile: Disable Expand/Collapse */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_disable_expand_collapse]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_disable_expand_collapse]', array(
			'label'       => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove the "Expand All | Collapse All" links &amp; always display all ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'course' ) . __( ' information.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_disable_expand_collapse]',
			'priority'    => 40
		) );

		/* Profile: Disable Search */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_disable_search]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_disable_search]', array(
			'label'       => __( 'Disable Search', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_disable_search]',
			'priority'    => 50
		) );

		/* Hide Quizzes in "Your Courses" */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_quiz_list]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_quiz_list]', array(
			'label'       => __( 'Hide ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'quiz' ) . __( ' Lists', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove all ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . __( ' information inside the "Your ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'courses' ) . __( '" area.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_quiz_list]',
			'priority'    => 60
		) );

		/* Hide Essays in "Your Courses" */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_essay_list]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_essay_list]', array(
			'label'       => __( 'Hide Essay Lists', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove all essay information inside the "Your ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'courses' ) . __( '" area.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_essay_list]',
			'priority'    => 62
		) );

		/* Hide Assignments in "Your Courses" */
		$wp_customize->add_setting( 'ldx3_design_upgrade[profile_hide_assignment_list]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[profile_hide_assignment_list]', array(
			'label'       => __( 'Hide Assignment Lists', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove all assignment information inside the "Your ', 'design-upgrade-pro-learndash' ) . LearnDash_Custom_Label::get_label( 'courses' ) . __( '" area.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_profile',
			'settings'    => 'ldx3_design_upgrade[profile_hide_assignment_list]',
			'priority'    => 64
		) );

	}


	/**
	 * Infobar: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_course_page_section( $wp_customize ) {

		/* Infobar: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[course_status_bg_color]', array(
			'label'       => __( 'Infobar: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_bg_color]',
			'priority'    => 10
		) ) );

		/* Infobar: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[course_status_text_color]', array(
			'label'       => __( 'Infobar: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_text_color]',
			'priority'    => 11
		) ) );

		/* Infobar: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[course_status_border_width]', array(
			'label'    => __( 'Infobar: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_course_page',
			'settings' => 'ldx3_design_upgrade[course_status_border_width]',
			'priority' => 14
		) );

		/* Infobar: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[course_status_border_color]', array(
			'label'       => __( 'Infobar: Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_border_color]',
			'priority'    => 15
		) ) );

		/* Hide Column Headers */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_hide_column_labels]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[course_status_hide_column_labels]', array(
			'label'       => __( 'Hide Column Labels', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Hides "Current Status," "Price," and "Get Started" labels at the top of each column.' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_hide_column_labels]',
			'priority'    => 19
		) );

		/* Hide Column: Status */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_hide_status]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[course_status_hide_status]', array(
			'label'       => __( 'Hide Column: Current Status', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_hide_status]',
			'priority'    => 20
		) );

		/* Hide Column: Price */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_hide_price]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[course_status_hide_price]', array(
			'label'       => __( 'Hide Column: Price', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_hide_price]',
			'priority'    => 21
		) );

		/* Hide Column: Get Started */
		$wp_customize->add_setting( 'ldx3_design_upgrade[course_status_hide_action]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[course_status_hide_action]', array(
			'label'       => __( 'Hide Column: Get Started', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_page',
			'settings'    => 'ldx3_design_upgrade[course_status_hide_action]',
			'priority'    => 22
		) );

	}


	/**
	 * Alerts: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_alerts_section( $wp_customize ) {

		/* Alerts: Enable Smaller Alerts */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_size_compact]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[alert_size_compact]', array(
			'label'       => __( 'Reduce Alert Size', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_size_compact]',
			'priority'    => 4
		) );

		/* Remove Alert Icons */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_remove_icons]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[alert_remove_icons]', array(
			'label'       => __( 'Remove Alert Icons', 'design-upgrade-pro-learndash' ),
			'description' => __( 'This will remove the icons for ALL alerts.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_remove_icons]',
			'priority'    => 5
		) );

		/* Alerts: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[alert_border_width]', array(
			'label'    => __( 'Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_alerts',
			'settings' => 'ldx3_design_upgrade[alert_border_width]',
			'priority' => 9
		) );

		/* Alert Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_text_color]', array(
			'label'       => __( 'General Alert: Text/Icon/Button', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_text_color]',
			'priority'    => 10
		) ) );

		/* Alert BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_bg_color]', array(
			'label'       => __( 'General Alert: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_bg_color]',
			'priority'    => 11
		) ) );

		/* Alert Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_border_color]', array(
			'label'       => __( 'General Alert: Border', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_border_color]',
			'priority'    => 12
		) ) );

		/* Alert WARNING Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_warning_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_warning_text_color]', array(
			'label'       => __( 'Warning: Text/Button', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_warning_text_color]',
			'priority'    => 20
		) ) );

		/* Alert WARNING BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_warning_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_warning_bg_color]', array(
			'label'       => __( 'Warning: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_warning_bg_color]',
			'priority'    => 21
		) ) );

		/* Alert WARNING Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_warning_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_warning_border_color]', array(
			'label'       => __( 'Warning: Border/Icon', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_warning_border_color]',
			'priority'    => 22
		) ) );

		/* Alert SUCCESS Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_success_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_success_text_color]', array(
			'label'       => __( 'Success: Text/Icon/Button', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_success_text_color]',
			'priority'    => 30
		) ) );

		/* Alert SUCCESS BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_success_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_success_bg_color]', array(
			'label'       => __( 'Success: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_success_bg_color]',
			'priority'    => 31
		) ) );

		/* Alert SUCCESS Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[alert_success_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[alert_success_border_color]', array(
			'label'       => __( 'Success: Border', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_alerts',
			'settings'    => 'ldx3_design_upgrade[alert_success_border_color]',
			'priority'    => 32
		) ) );

	}


	/**
	 * Tooltips: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_tooltips_section( $wp_customize ) {

		/* Tooltip Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tooltip_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tooltip_bg_color]', array(
			'label'       => __( 'Background Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_tooltips',
			'settings'    => 'ldx3_design_upgrade[tooltip_bg_color]',
			'priority'    => 10
		) ) );

		/* Tooltip Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tooltip_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tooltip_text_color]', array(
			'label'       => __( 'Text Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_tooltips',
			'settings'    => 'ldx3_design_upgrade[tooltip_text_color]',
			'priority'    => 11
		) ) );

	}


	/**
	 * Login & Registration: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.3
	 * @return void
	 */
	private function ldx3_learndash_login_register_section( $wp_customize ) {

		/* Login/Register Popup: Overlay Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[log_reg_overlay_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[log_reg_overlay_color]', array(
			'label'       => __( 'Overlay Color (experimental)', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[log_reg_overlay_color]',
			'priority'    => 5
		) ) );

		/* Login/Register Popup: Overlay Opacity */
		$wp_customize->add_setting( 'ldx3_design_upgrade[log_reg_overlay_opacity]', array(
			'type'              => 'option',
			'default'           => 'B3',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[log_reg_overlay_opacity]', array(
			'label'      => __( 'Overlay Opacity (experimental)', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_login_register',
			'settings'   => 'ldx3_design_upgrade[log_reg_overlay_opacity]',
			'type'       => 'select',
			'choices'    => array(
				'1A'       => __( '10%', 'design-upgrade-pro-learndash' ),
				'33'       => __( '20%', 'design-upgrade-pro-learndash' ),
				'4D'       => __( '30%', 'design-upgrade-pro-learndash' ),
				'66'       => __( '40%', 'design-upgrade-pro-learndash' ),
				'80'       => __( '50%', 'design-upgrade-pro-learndash' ),
				'99'       => __( '60%', 'design-upgrade-pro-learndash' ),
				'B3'       => __( '70% (default)', 'design-upgrade-pro-learndash' ),
				'CC'       => __( '80%', 'design-upgrade-pro-learndash' ),
				'E6'       => __( '90%', 'design-upgrade-pro-learndash' )
			),
			'priority'   => 6
		) );

		/* Login/Register Popup: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[log_reg_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[log_reg_border_width]', array(
			'label'    => __( 'Popup: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_login_register',
			'settings' => 'ldx3_design_upgrade[log_reg_border_width]',
			'priority' => 9
		) );

		/* Login/Register Popup: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[log_reg_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[log_reg_border_color]', array(
			'label'       => __( 'Popup: Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[log_reg_border_color]',
			'priority'    => 10
		) ) );

		/* Login/Register Popup: Close Icon Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[log_reg_close_icon_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[log_reg_close_icon_color]', array(
			'label'       => __( 'Popup: Close Icon', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[log_reg_close_icon_color]',
			'priority'    => 12
		) ) );

		/* Login: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[login_panel_bg_color]', array(
			'label'       => __( 'Login: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_bg_color]',
			'priority'    => 20
		) ) );

		/* Login: Heading Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_heading_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[login_panel_heading_color]', array(
			'label'       => __( 'Login: Heading', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_heading_color]',
			'priority'    => 21
		) ) );

		/* Login: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[login_panel_text_color]', array(
			'label'       => __( 'Login: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_text_color]',
			'priority'    => 22
		) ) );

		/* Login: Input BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_input_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[login_panel_input_bg_color]', array(
			'label'       => __( 'Login: Input Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_input_bg_color]',
			'priority'    => 23
		) ) );

		/* Login: Input Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_input_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[login_panel_input_text_color]', array(
			'label'       => __( 'Login: Input Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_input_text_color]',
			'priority'    => 24
		) ) );

		/* Login: Remove description text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_remove_desc_text]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[login_panel_remove_desc_text]', array(
			'label'       => __( 'Login: Remove description text', 'design-upgrade-pro-learndash' ),
			'description' => __( 'The text that appears directly below the "Login" heading.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_remove_desc_text]',
			'priority'    => 25
		) );

		/* Login: Remove logo */
		$wp_customize->add_setting( 'ldx3_design_upgrade[login_panel_remove_logo]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[login_panel_remove_logo]', array(
			'label'       => __( 'Login: Remove logo', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[login_panel_remove_logo]',
			'priority'    => 26
		) );

		/* Register: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[register_panel_bg_color]', array(
			'label'       => __( 'Register: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_bg_color]',
			'priority'    => 30
		) ) );

		/* Register: Heading Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_heading_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[register_panel_heading_color]', array(
			'label'       => __( 'Register: Heading', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_heading_color]',
			'priority'    => 31
		) ) );

		/* Register: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[register_panel_text_color]', array(
			'label'       => __( 'Register: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_text_color]',
			'priority'    => 32
		) ) );

		/* Register: Input BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_input_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[register_panel_input_bg_color]', array(
			'label'       => __( 'Register: Input Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_input_bg_color]',
			'priority'    => 33
		) ) );

		/* Register: Input Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_input_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[register_panel_input_text_color]', array(
			'label'       => __( 'Register: Input Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_input_text_color]',
			'priority'    => 34
		) ) );

		/* Register: Remove description text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_remove_desc_text]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[register_panel_remove_desc_text]', array(
			'label'       => __( 'Register: Remove description text', 'design-upgrade-pro-learndash' ),
			'description' => __( 'The text that appears directly below the "Register" heading.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_remove_desc_text]',
			'priority'    => 35
		) );

		/* Register: Remove email confirmation text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[register_panel_remove_email_conf_text]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[register_panel_remove_email_conf_text]', array(
			'label'       => __( 'Register: Remove email confirmation text', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_login_register',
			'settings'    => 'ldx3_design_upgrade[register_panel_remove_email_conf_text]',
			'priority'    => 36
		) );

	}


	/**
	 * Course Grid: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_course_grid_section( $wp_customize ) {

		/* Grid Item: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_item_border_width]', array(
			'label'       => __( 'Grid Item: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_item_border_width]',
			'priority'    => 10
		) );

		/* Grid Item: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_item_border_color]', array(
			'label'       => __( 'Grid Item: Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_item_border_color]',
			'priority'    => 11
		) ) );

		/* Grid Item: Border Radius */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_border_radius]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_item_border_radius]', array(
			'label'       => __( 'Grid Item: Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_item_border_radius]',
			'priority'    => 12
		) );

		/* Grid Item: Shadow */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_shadow]', array(
			'type'              => 'option',
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_item_shadow]', array(
			'label'      => __( 'Grid Item: Shadow', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_course_grid',
			'settings'   => 'ldx3_design_upgrade[grid_item_shadow]',
			'type'       => 'select',
			'choices'    => array(
				'none'       => __( 'None', 'design-upgrade-pro-learndash' ),
				'shadow'     => __( 'Shadow', 'design-upgrade-pro-learndash' )
			),
			'priority'   => 20
		) );

		/* Grid Item Hover: Shadow */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_hover_shadow]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_item_hover_shadow]', array(
			'label'       => __( 'Grid Item Hover: Add Shadow?', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_item_hover_shadow]',
			'priority'    => 45
		) );

		/* Grid Item Hover: Transform */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_item_hover_transform]', array(
			'type'              => 'option',
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_item_hover_transform]', array(
			'label'      => __( 'Grid Item Hover: Transform', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_course_grid',
			'settings'   => 'ldx3_design_upgrade[grid_item_hover_transform]',
			'type'       => 'select',
			'choices'    => array(
				'none'     => __( 'None', 'design-upgrade-pro-learndash' ),
				'lift'     => __( 'Lift', 'design-upgrade-pro-learndash' ),
				'enlarge'  => __( 'Enlarge', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 46
		) );

		/* Grid Ribbon: Position */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_position]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_ribbon_position]', array(
			'label'      => __( 'Ribbon: Position', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_course_grid',
			'settings'   => 'ldx3_design_upgrade[grid_ribbon_position]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'top-left'   => __( 'Top Left', 'design-upgrade-pro-learndash' ),
				'top-right'  => __( 'Top Right', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 59
		) );

		/* Grid Ribbon: Default: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_default_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_default_bg_color]', array(
			'label'       => __( 'Ribbon: Default: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_default_bg_color]',
			'priority'    => 60
		) ) );

		/* Grid Ribbon: Default: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_default_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_default_text_color]', array(
			'label'       => __( 'Ribbon: Default: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_default_text_color]',
			'priority'    => 61
		) ) );

		/* Grid Ribbon: Enrolled: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_enrolled_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_enrolled_bg_color]', array(
			'label'       => __( 'Ribbon: Enrolled: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_enrolled_bg_color]',
			'priority'    => 62
		) ) );

		/* Grid Ribbon: Enrolled: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_enrolled_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_enrolled_text_color]', array(
			'label'       => __( 'Ribbon: Enrolled: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_enrolled_text_color]',
			'priority'    => 63
		) ) );

		/* Grid Ribbon: Free: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_free_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_free_bg_color]', array(
			'label'       => __( 'Ribbon: Free: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_free_bg_color]',
			'priority'    => 64
		) ) );

		/* Grid Ribbon: Free: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_free_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_free_text_color]', array(
			'label'       => __( 'Ribbon: Free: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_free_text_color]',
			'priority'    => 65
		) ) );

		/* Grid Ribbon: Custom: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_custom_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_custom_bg_color]', array(
			'label'       => __( 'Ribbon: Custom: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_custom_bg_color]',
			'priority'    => 66
		) ) );

		/* Grid Ribbon: Custom: Text */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_ribbon_custom_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_ribbon_custom_text_color]', array(
			'label'       => __( 'Ribbon: Custom: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_ribbon_custom_text_color]',
			'priority'    => 67
		) ) );

		/* Grid Category Selector: Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_selector_width]', array(
			'type'              => 'option',
			'default'           => 'full',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_selector_width]', array(
			'label'      => __( 'Category Selector: Width', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_course_grid',
			'settings'   => 'ldx3_design_upgrade[grid_selector_width]',
			'type'       => 'select',
			'choices'    => array(
				'full'     => __( 'Full', 'design-upgrade-pro-learndash' ),
				'inline'   => __( 'Inline', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 80
		) );

		/* Grid Category Selector: Background */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_selector_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[grid_selector_bg_color]', array(
			'label'       => __( 'Category Selector: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_selector_bg_color]',
			'priority'    => 90
		) ) );

		/* Grid Category Selector: Padding */
		$wp_customize->add_setting( 'ldx3_design_upgrade[grid_selector_padding]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[grid_selector_padding]', array(
			'label'       => __( 'Category Selector: Padding', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_course_grid',
			'settings'    => 'ldx3_design_upgrade[grid_selector_padding]',
			'priority'    => 92
		) );

	} // function ldx3_learndash_course_grid_section


	/**
	 * Pagination: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_pagination_section( $wp_customize ) {

		/* Pagination: Remove Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_remove_background]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[pagination_remove_background]', array(
			'label'    => __( 'Remove Background Color', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_pagination',
			'settings' => 'ldx3_design_upgrade[pagination_remove_background]',
			'priority' => 5
		) );

		/* Pagination: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_bg_color]', array(
			'label'    => __( 'Background Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_pagination',
			'settings' => 'ldx3_design_upgrade[pagination_bg_color]',
			'priority' => 10
		) ) );

		/* Pagination: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_text_color]', array(
			'label'    => __( 'Text Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_pagination',
			'settings' => 'ldx3_design_upgrade[pagination_text_color]',
			'priority' => 15
		) ) );

		/* Pagination: Arrow Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_arrow_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_arrow_color]', array(
			'label'    => __( 'Arrow Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_pagination',
			'settings' => 'ldx3_design_upgrade[pagination_arrow_color]',
			'priority' => 20
		) ) );

		/* Pagination: Arrow Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_arrow_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_arrow_color_hover]', array(
			'label'    => __( 'Hover: Arrow Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_pagination',
			'settings' => 'ldx3_design_upgrade[pagination_arrow_color_hover]',
			'priority' => 21
		) ) );

		/* Pagination: Arrow Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_arrow_style]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[pagination_arrow_style]', array(
			'label'      => __( 'Arrow Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_pagination',
			'settings'   => 'ldx3_design_upgrade[pagination_arrow_style]',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'circle'     => __( 'Circle', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 30
		) );

		/* Pagination: Arrow BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_arrow_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_arrow_bg_color]', array(
			'label'       => __( 'Arrow Background', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Only applies if using the "Circle" Arrow Style.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_pagination',
			'settings'    => 'ldx3_design_upgrade[pagination_arrow_bg_color]',
			'priority'    => 31
		) ) );

		/* Pagination: Arrow BG Color: Hover */
		$wp_customize->add_setting( 'ldx3_design_upgrade[pagination_arrow_bg_color_hover]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[pagination_arrow_bg_color_hover]', array(
			'label'       => __( 'Hover: Arrow Background', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Only applies if using the "Circle" Arrow Style.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_pagination',
			'settings'    => 'ldx3_design_upgrade[pagination_arrow_bg_color_hover]',
			'priority'    => 32
		) ) );

	}


	/**
	 * Achievements: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_achievements_section( $wp_customize ) {

		/* Achievements: Popup Container: Vertical Position */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_vertical_position]', array(
			'type'              => 'option',
			'default'           => 'top',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_vertical_position]', array(
			'label'      => __( 'Popup Container: Vertical Position', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_achievements',
			'settings'   => 'ldx3_design_upgrade[achievements_popup_vertical_position]',
			'type'       => 'select',
			'choices'    => array(
				'top'      => __( 'Top (default)', 'design-upgrade-pro-learndash' ),
				'bottom'   => __( 'Bottom', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 1
		) );

		/* Achievements: Popup Container: Border Width  */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_border_width]', array(
			'label'       => __( 'Popup Container: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_achievements',
			'settings'    => 'ldx3_design_upgrade[achievements_popup_border_width]',
			'priority'    => 10
		) );

		/* Achievements: Popup Container: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[achievements_popup_border_color]', array(
			'label'    => __( 'Popup Container: Border Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_achievements',
			'settings' => 'ldx3_design_upgrade[achievements_popup_border_color]',
			'priority' => 11
		) ) );

		/* Achievements: Popup Container: Border Radius  */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_border_radius]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_border_radius]', array(
			'label'       => __( 'Popup Container: Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_achievements',
			'settings'    => 'ldx3_design_upgrade[achievements_popup_border_radius]',
			'priority'    => 12
		) );

		/* Achievements: Popup Container: Shadow */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_boxshadow]', array(
			'type'              => 'option',
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_boxshadow]', array(
			'label'      => __( 'Popup Container: Shadow', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_learndash_achievements',
			'settings'   => 'ldx3_design_upgrade[achievements_popup_boxshadow]',
			'type'       => 'select',
			'choices'    => array(
				'none'    => __( 'None (default)', 'design-upgrade-pro-learndash' ),
				'small'   => __( 'Small', 'design-upgrade-pro-learndash' ),
				'large'   => __( 'Large', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 15
		) );

		/* Achievements: Popup: Hide Image */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_hide_image]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_hide_image]', array(
			'label'    => __( 'Achievements Popup: Hide Image', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_achievements',
			'settings' => 'ldx3_design_upgrade[achievements_popup_hide_image]',
			'priority' => 19
		) );

		/* Achievements: Popup: Title Font Size */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_title_fontsize]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_title_fontsize]', array(
			'label'       => __( 'Popup Title: Font Size', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 8,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_achievements',
			'settings'    => 'ldx3_design_upgrade[achievements_popup_title_fontsize]',
			'priority'    => 20
		) );

		/* Achievements: Popup: Title Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_title_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[achievements_popup_title_color]', array(
			'label'    => __( 'Popup Title: Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_achievements',
			'settings' => 'ldx3_design_upgrade[achievements_popup_title_color]',
			'priority' => 21
		) ) );

		/* Achievements: Popup: Message Font Size */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_message_fontsize]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[achievements_popup_message_fontsize]', array(
			'label'       => __( 'Popup Message: Font Size', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 8,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_achievements',
			'settings'    => 'ldx3_design_upgrade[achievements_popup_message_fontsize]',
			'priority'    => 25
		) );

		/* Achievements: Popup: Message Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[achievements_popup_message_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[achievements_popup_message_color]', array(
			'label'    => __( 'Popup Message: Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx3_learndash_achievements',
			'settings' => 'ldx3_design_upgrade[achievements_popup_message_color]',
			'priority' => 26
		) ) );

		/* My Achievements: Badge/Icon Size */
		$wp_customize->add_setting( 'ldx3_design_upgrade[my_achievements_icon_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[my_achievements_icon_width]', array(
			'label'       => __( 'My Achievements: Icon Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 8,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Shown when using the "My Achievements" block/shortcode <code>[ld_my_achievements]</code>. Value is in pixels (px). Default <code>40</code>', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_learndash_achievements',
			'settings'    => 'ldx3_design_upgrade[my_achievements_icon_width]',
			'priority'    => 30
		) );

	}


	/**
	 * Group Courses List: @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_learndash_group_courses_list_section( $wp_customize ) {

		/* List Tables: Disable Expand/Collapse */
		$wp_customize->add_setting( 'ldx3_design_upgrade[group_courses_list_disable_expand_collapse]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[group_courses_list_disable_expand_collapse]', array(
			'label'    => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_learndash_group_courses_list',
			'settings' => 'ldx3_design_upgrade[group_courses_list_disable_expand_collapse]',
			'priority' => 5
		) );

	}


	/**
	 * WisdmLabs: Ratings, Reviews & Feedback @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx3_wisdm_ratings_reviews_section( $wp_customize ) {

		/* Rating Bar Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[rating_bar_style]', array(
			'type'              => 'option',
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[rating_bar_style]', array(
			'label'      => __( 'Rating Bar Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_wisdm_ratings_reviews',
			'settings'   => 'ldx3_design_upgrade[rating_bar_style]',
			'type'       => 'select',
			'choices'    => array(
				'default'     => __( 'Default', 'design-upgrade-pro-learndash' ),
				'amazon'      => __( 'Amazon.com', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );

		/* Color: Empty Star */
		$wp_customize->add_setting( 'ldx3_design_upgrade[rating_star_empty_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[rating_star_empty_color]', array(
			'label'       => __( 'Empty Star', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_wisdm_ratings_reviews',
			'settings'    => 'ldx3_design_upgrade[rating_star_empty_color]',
			'priority'    => 10
		) ) );

		/* Color: Filled Star */
		$wp_customize->add_setting( 'ldx3_design_upgrade[rating_star_filled_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[rating_star_filled_color]', array(
			'label'       => __( 'Filled Star', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_wisdm_ratings_reviews',
			'settings'    => 'ldx3_design_upgrade[rating_star_filled_color]',
			'priority'    => 11
		) ) );

		/* Hide: Sort/Filter Section */
		$wp_customize->add_setting( 'ldx3_design_upgrade[wisdm_rrf_hide_sort_filter]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[wisdm_rrf_hide_sort_filter]', array(
			'label'    => __( 'Hide Sort/Filter Options', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_wisdm_ratings_reviews',
			'settings' => 'ldx3_design_upgrade[wisdm_rrf_hide_sort_filter]',
			'priority' => 20
		) );

		/* Hide: Helpful? */
		$wp_customize->add_setting( 'ldx3_design_upgrade[wisdm_rrf_hide_helpful]', array(
			'type'              => 'option',
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[wisdm_rrf_hide_helpful]', array(
			'label'    => __( 'Hide Helpful Ratings', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'  => 'ldx3_wisdm_ratings_reviews',
			'settings' => 'ldx3_design_upgrade[wisdm_rrf_hide_helpful]',
			'priority' => 25
		) );

	} // function ldx3_wisdm_ratings_reviews_section


	/**
	 * Uncanny Owl: Tin Canny Reporting @ld3
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.10
	 * @return void
	 */
	private function ldx3_tincanny_reporting_section( $wp_customize ) {

		/* Container: Shadow Style */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_container_shadow]', array(
			'type'              => 'option',
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[tincanny_container_shadow]', array(
			'label'      => __( 'Container: Shadow', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx3_tincanny_reporting',
			'settings'   => 'ldx3_design_upgrade[tincanny_container_shadow]',
			'type'       => 'select',
			'choices'    => array(
				'none'     => __( 'None', 'design-upgrade-pro-learndash' ),
				'small'    => __( 'Small', 'design-upgrade-pro-learndash' ),
				'large'    => __( 'Large', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );

		/* Container: Border Width */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_container_border_width]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx3_design_upgrade[tincanny_container_border_width]', array(
			'label'       => __( 'Container: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'style' => 'width:60px;display:block;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_container_border_width]',
			'priority'    => 10
		) );

		/* Container: Border Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_container_border_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_container_border_color]', array(
			'label'       => __( 'Container: Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_container_border_color]',
			'priority'    => 11
		) ) );

		/* Container: Header BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_container_header_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_container_header_bg_color]', array(
			'label'       => __( 'Container: Header: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_container_header_bg_color]',
			'priority'    => 20
		) ) );

		/* Container: Header Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_container_header_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_container_header_text_color]', array(
			'label'       => __( 'Container: Header: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_container_header_text_color]',
			'priority'    => 21
		) ) );

		/* Table Header: Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_table_header_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_table_header_bg_color]', array(
			'label'       => __( 'Table Header: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_table_header_bg_color]',
			'priority'    => 30
		) ) );

		/* Table Header: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_table_header_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_table_header_text_color]', array(
			'label'       => __( 'Table Header: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_table_header_text_color]',
			'priority'    => 31
		) ) );

		/* Table Row: Hover Background Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_table_row_hover_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_table_row_hover_bg_color]', array(
			'label'       => __( 'Table Row: Hover: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_table_row_hover_bg_color]',
			'priority'    => 35
		) ) );

		/* Graph: Course Completions Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_chart_course_completions_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_chart_course_completions_color]', array(
			'label'       => __( 'Charts: Course Completions', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_chart_course_completions_color]',
			'priority'    => 40
		) ) );

		/* Graph: Course Completions Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_chart_tincan_statements_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_chart_tincan_statements_color]', array(
			'label'       => __( 'Charts: Tin Can Statements', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_chart_tincan_statements_color]',
			'priority'    => 41
		) ) );

		/* User Report Tabs: BG Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_user_report_tab_bg_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_user_report_tab_bg_color]', array(
			'label'       => __( 'User Report Tabs: Background (Hover/Selected)', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_user_report_tab_bg_color]',
			'priority'    => 50
		) ) );

		/* User Report Tabs: Text Color */
		$wp_customize->add_setting( 'ldx3_design_upgrade[tincanny_user_report_tab_text_color]', array(
			'type'              => 'option',
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx3_design_upgrade[tincanny_user_report_tab_text_color]', array(
			'label'       => __( 'User Report Tabs: Text (Hover/Selected)', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx3_tincanny_reporting',
			'settings'    => 'ldx3_design_upgrade[tincanny_user_report_tab_text_color]',
			'priority'    => 51
		) ) );

	} // function ldx3_tincanny_reporting_section


/**************************
 * END LD3 / START LEGACY *
 **************************/


	/**
	 * General Design: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function ldx_learndash_general_section( $wp_customize ) {

		/* Link Color */
		$wp_customize->add_setting( 'ldx_color_link', array(
			'default'           => '#2196f3',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_link', array(
			'label'    => __( 'Link Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_general',
			'settings' => 'ldx_color_link',
			'priority' => 10
		) ) );

		/* Link Hover Color */
		$wp_customize->add_setting( 'ldx_color_link_hover', array(
			'default'           => '#1565c0',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_link_hover', array(
			'label'    => __( 'Link Hover Color', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_general',
			'settings' => 'ldx_color_link_hover',
			'priority' => 20
		) ) );

		/* "Correct" Color */
		$wp_customize->add_setting( 'ldx_color_correct', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_correct', array(
			'label'       => __( '"Correct" Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'We recommend some variation of green. Shown for correct answers, positive results, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_general',
			'settings'    => 'ldx_color_correct',
			'priority'    => 30
		) ) );

		/* "Incorrect" Color */
		$wp_customize->add_setting( 'ldx_color_incorrect', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_incorrect', array(
			'label'       => __( '"Incorrect" Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'We recommend some variation of red. Shown for incorrect answers, negative results, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_general',
			'settings'    => 'ldx_color_incorrect',
			'priority'    => 31
		) ) );

	} // function ldx_learndash_general_section()


	/**
	 * Lesson, Topic, Quiz Lists: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_list_tables_section( $wp_customize ) {

		/* List Table Padding */
		$wp_customize->add_setting( 'ldx_list_table_item_padding', array(
			'default'           => 'small',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_list_table_item_padding', array(
			'label'      => __( 'Padding', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_list_tables',
			'settings'   => 'ldx_list_table_item_padding',
			'type'       => 'select',
			'choices'    => array(
				'small'    => __( 'Small', 'design-upgrade-pro-learndash' ),
				'medium'   => __( 'Medium', 'design-upgrade-pro-learndash' ),
				'large'    => __( 'Large', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 10
		) );

		/* List Table Background Color */
		$wp_customize->add_setting( 'ldx_color_list_table_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_bg', array(
			'label'       => __( 'Background Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_bg',
			'priority'    => 20
		) ) );

		/* List Table Border Width */
		$wp_customize->add_setting( 'ldx_list_table_border_width', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_list_table_border_width', array(
			'label'       => __( 'Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_border_width',
			'priority'    => 25
		) );

		/* List Table Border Color */
		$wp_customize->add_setting( 'ldx_color_list_table_border', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_border', array(
			'label'       => __( 'Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_border',
			'priority'    => 26
		) ) );

		/* List Table Border Radius */
		$wp_customize->add_setting( 'ldx_list_table_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_list_table_border_radius', array(
			'label'       => __( 'Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_border_radius',
			'priority'    => 27
		) );

		/* List Table Header Background Color */
		$wp_customize->add_setting( 'ldx_color_list_table_header_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_header_bg', array(
			'label'       => __( 'Header Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_header_bg',
			'priority'    => 30
		) ) );

		/* List Table Header Text Color */
		$wp_customize->add_setting( 'ldx_color_list_table_header_text', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_header_text', array(
			'label'       => __( 'Header Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_header_text',
			'priority'    => 31
		) ) );

		/* List Table Line Separators Color */
		$wp_customize->add_setting( 'ldx_list_table_line_separator_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_list_table_line_separator_color', array(
			'label'       => __( 'Line Separators', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_line_separator_color',
			'priority'    => 40
		) ) );

		/* List Table Text/Link Color */
		$wp_customize->add_setting( 'ldx_color_list_table_text', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_text', array(
			'label'       => __( 'Text Color', 'design-upgrade-pro-learndash' ),
			'description' => __( 'If your background color is dark, change this to something light.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_text',
			'priority'    => 50
		) ) );

		/* List Table Link Hover Color */
		$wp_customize->add_setting( 'ldx_color_list_table_text_hover', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_list_table_text_hover', array(
			'label'       => __( 'Text Hover Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_list_table_text_hover',
			'priority'    => 51
		) ) );

		/* Alternating Rows Background Color */
		$wp_customize->add_setting( 'ldx_color_rows_alt', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_rows_alt', array(
			'label'       => __( 'Alternating Rows', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Highlight alternating rows in ' . LearnDash_Custom_Label::label_to_lower( 'lesson' ) . ', ' . LearnDash_Custom_Label::label_to_lower( 'topic' ) . ' &amp; ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . ' lists. Leave blank to disable.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_color_rows_alt',
			'priority'    => 60
		) ) );

		/* Alternating Rows Hover Color */
		$wp_customize->add_setting( 'ldx_color_rows_hover', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_color_rows_hover', array(
			'label'    => __( 'Row Hover Background', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_list_tables',
			'settings' => 'ldx_color_rows_hover',
			'priority' => 61
		) ) );

		/* Remove "Course Content" Header Text */
		$wp_customize->add_setting( 'ldx_list_table_remove_course_content_header', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_list_table_remove_course_content_header', array(
			'label'       => __( 'Remove "' . LearnDash_Custom_Label::get_label( 'course' ) . ' Content" Header Text', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_remove_course_content_header',
			'priority'    => 69
		) );

		/* Remove Lesson & Quiz Numbers */
		$wp_customize->add_setting( 'ldx_list_table_remove_number_count', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_list_table_remove_number_count', array(
			'label'       => __( 'Remove List Count', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_remove_number_count',
			'priority'    => 70
		) );

		/* Disable Expand/Collapse */
		$wp_customize->add_setting( 'ldx_list_table_disable_expand_collapse', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_list_table_disable_expand_collapse', array(
			'label'       => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove the "Expand All | Collapse All" links &amp; always display all ' . LearnDash_Custom_Label::label_to_lower( 'topics' ) . '.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_disable_expand_collapse',
			'priority'    => 75
		) );

		/* Remove Status Checkmarks */
		$wp_customize->add_setting( 'ldx_list_table_remove_status', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_list_table_remove_status', array(
			'label'       => __( 'Remove Status Checkmarks', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_list_tables',
			'settings'    => 'ldx_list_table_remove_status',
			'priority'    => 79
		) );

	} // function ldx_learndash_list_tables_section


	/**
	 * Progress Bar: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_progress_bar_section( $wp_customize ) {

		/* Progress Bar Style (default, flat, striped) */
		$wp_customize->add_setting( 'ldx_progress_bar_style', array(
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_progress_bar_style', array(
			'label'      => __( 'Progress Bar Style', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_progress_bar',
			'settings'   => 'ldx_progress_bar_style',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'flat'       => __( 'Flat', 'design-upgrade-pro-learndash' ),
				'striped'    => __( 'Striped', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );


		/* Progress Bar Container Color */
		$wp_customize->add_setting( 'ldx_progress_bar_container_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_progress_bar_container_bg', array(
			'label'    => __( 'Progress Bar Container', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_progress_bar',
			'settings' => 'ldx_progress_bar_container_bg',
			'priority' => 10
		) ) );

		/* Progress Bar Color */
		$wp_customize->add_setting( 'ldx_progress_bar_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_progress_bar_bg', array(
			'label'    => __( 'Progress Bar', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_progress_bar',
			'settings' => 'ldx_progress_bar_bg',
			'priority' => 11
		) ) );

		/* Progress Bar Border Radius */
		$wp_customize->add_setting( 'ldx_progress_bar_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_progress_bar_border_radius', array(
			'label'    => __( 'Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_progress_bar',
			'settings' => 'ldx_progress_bar_border_radius',
			'priority' => 20
		) );

		/* Progress Bar Height */
		$wp_customize->add_setting( 'ldx_progress_bar_height', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_progress_bar_height', array(
			'label'    => __( 'Height', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 1,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_progress_bar',
			'settings' => 'ldx_progress_bar_height',
			'priority' => 30
		) );

		/* Progress Bar Animation */
		$wp_customize->add_setting( 'ldx_progress_bar_animation', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_progress_bar_animation', array(
			'label'    => __( 'Animate', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'description' => __( 'Add a smooth animation to the progress bar on each page load.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_progress_bar',
			'settings' => 'ldx_progress_bar_animation',
			'priority' => 40
		) );

		/* Progress Bar: Show Steps: above or below */
		$wp_customize->add_setting( 'ldx_progress_bar_show_steps', array(
			'default'           => 'noshow',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_progress_bar_show_steps', array(
			'label'      => __( 'Show Steps?', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_progress_bar',
			'settings'   => 'ldx_progress_bar_show_steps',
			'type'       => 'select',
			'description' => __( 'Display "X out of Y steps completed"', 'design-upgrade-pro-learndash' ),
			'choices'    => array(
				'noshow'      => __( 'Don\'t Show', 'design-upgrade-pro-learndash' ),
				'below'       => __( 'Below', 'design-upgrade-pro-learndash' ),
				'above'       => __( 'Above', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 50
		) );

	} // function ldx_learndash_progress_bar_section


	/**
	 * Buttons: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_buttons_section( $wp_customize ) {

		/* Button Border Radius */
		$wp_customize->add_setting( 'ldx_button_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_button_border_radius', array(
			'label'       => __( 'Button Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_buttons',
			'settings' => 'ldx_button_border_radius',
			'priority' => 9
		) );

		/* Primary Button Color */
		$wp_customize->add_setting( 'ldx_button_primary_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_button_primary_bg', array(
			'label'       => __( 'Primary Button', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Used for primary actions like <em>Take this ' . LearnDash_Custom_Label::get_label( 'course' ) . '</em>, <em>Mark Complete</em>, <em>Start ' . LearnDash_Custom_Label::get_label( 'quiz' ) . '</em>, etc.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_buttons',
			'settings'    => 'ldx_button_primary_bg',
			'priority'    => 10
		) ) );

		/* Primary Button Text Color */
		$wp_customize->add_setting( 'ldx_button_primary_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_button_primary_text_color', array(
			'label'       => __( 'Primary Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_buttons',
			'settings'    => 'ldx_button_primary_text_color',
			'priority'    => 11
		) ) );

		/* Standard Button Color */
		$wp_customize->add_setting( 'ldx_button_standard_bg', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_button_standard_bg', array(
			'label'       => __( 'Standard Button', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Used for secondary actions like next/previous navigation.', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_buttons',
			'settings'    => 'ldx_button_standard_bg',
			'priority'    => 20
		) ) );

		/* Standard Button Text Color */
		$wp_customize->add_setting( 'ldx_button_standard_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_button_standard_text_color', array(
			'label'       => __( 'Standard Button Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_buttons',
			'settings'    => 'ldx_button_standard_text_color',
			'priority'    => 21
		) ) );

	} // function ldx_learndash_buttons_section


	/**
	 * Profile: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_profile_section( $wp_customize ) {

		/* Avatar Style */
		$wp_customize->add_setting( 'ldx_profile_avatar_shape', array(
			'default'           => 'square',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_profile_avatar_shape', array(
			'label'       => __( 'Avatar Shape', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_avatar_shape',
			'type'        => 'select',
			'choices'     => array(
				'square'    => __( 'Square', 'design-upgrade-pro-learndash' ),
				'rounded'   => __( 'Rounded', 'design-upgrade-pro-learndash' ),
				'circle'    => __( 'Circle', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 10
		) );

		/* Disable Expand/Collapse on Profile */
		$wp_customize->add_setting( 'ldx_profile_disable_expand_collapse', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_disable_expand_collapse', array(
			'label'       => __( 'Disable Expand/Collapse', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove the "Expand All | Collapse All" links. Always display all registered ' . LearnDash_Custom_Label::label_to_lower( 'course' ) . ' information. Also applies to Uncanny Toolkit\'s [uo_dashboard].', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_disable_expand_collapse',
			'priority'    => 50
		) );

		/* Hide Profile Info, Just Show Registered Courses */
		$wp_customize->add_setting( 'ldx_profile_hide_profile_info', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_profile_info', array(
			'label'       => __( 'Hide Profile Info', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove basic profile information &amp; just display "Registered ' . LearnDash_Custom_Label::get_label( 'courses' ) . '" when using the [ld_profile] shortcode.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_profile_info',
			'priority'    => 60
		) );

		/* Hide "Earned Course Points" */
		$wp_customize->add_setting( 'ldx_profile_hide_course_points', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_course_points', array(
			'label'       => __( 'Hide "Earned Course Points"', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_course_points',
			'priority'    => 62
		) );

		/* Hide "Edit Profile" Link */
		$wp_customize->add_setting( 'ldx_profile_hide_edit_profile_link', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_edit_profile_link', array(
			'label'       => __( 'Hide "Edit Profile" Link', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_edit_profile_link',
			'priority'    => 63
		) );

		/* Custom URL for 'Edit Profile' Link */
		$wp_customize->add_setting( 'ldx_profile_custom_edit_profile_link', array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw'
		) );
		$wp_customize->add_control( 'ldx_profile_custom_edit_profile_link', array(
			'label'    => __( 'Custom "Edit Profile" URL', 'design-upgrade-pro-learndash' ),
			'type'        => 'url',
			'input_attrs' => array(
				'placeholder'   => 'https://'
			),
			'description' => __( 'Send users to a custom page when clicking the "Edit Profile" link. Full URL is required, including https://.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_profile',
			'settings' => 'ldx_profile_custom_edit_profile_link',
			'priority' => 64
		) );

		/* Hide Quizzes in the Registered Courses Section */
		$wp_customize->add_setting( 'ldx_profile_hide_quizzes', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_quizzes', array(
			'label'       => __( 'Hide ' . LearnDash_Custom_Label::get_label( 'quizzes' ), 'design-upgrade-pro-learndash' ),
			'description' => __( 'Remove all ' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . ' information inside the "Registered ' . LearnDash_Custom_Label::get_label( 'courses' ) . '" area when using the [ld_profile] shortcode.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_quizzes',
			'priority'    => 65
		) );

		/* Hide Status Column/Icon in the Registered Courses Section */
		$wp_customize->add_setting( 'ldx_profile_hide_status_column', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_status_column', array(
			'label'       => __( 'Hide "Status" Column &amp; Icons', 'design-upgrade-pro-learndash' ),
			'description' => __( 'It will still show the progress bar &amp; "XX% Complete" text.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_status_column',
			'priority'    => 66
		) );

		/* Hide "Course Progress Overview" Text */
		$wp_customize->add_setting( 'ldx_profile_hide_progress_overview_text', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_profile_hide_progress_overview_text', array(
			'label'       => __( 'Hide "' . LearnDash_Custom_Label::get_label( 'course' ) . ' Progress Overview" Text', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_profile',
			'settings'    => 'ldx_profile_hide_progress_overview_text',
			'priority'    => 69
		) );

	} // function ldx_learndash_profile_section


	/**
	 * Topics: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_topics_section( $wp_customize ) {

		/* Hide Topic Dots */
		$wp_customize->add_setting( 'ldx_topics_hide_topic_progress_dots', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_topics_hide_topic_progress_dots', array(
			'label'       => __( 'Hide ' . LearnDash_Custom_Label::get_label( 'topic' ) . ' Progress Dots', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_topics',
			'settings'    => 'ldx_topics_hide_topic_progress_dots',
			'priority'    => 10
		) );

	} // function ldx_learndash_topics_section


	/**
	 * Course Navigation Widget: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_widget_coursenav_section( $wp_customize ) {

		/* Course Nav Widget Padding */
		$wp_customize->add_setting( 'ldx_widget_coursenav_item_padding', array(
			'default'           => 'small',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_item_padding', array(
			'label'      => __( 'Padding', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_widget_coursenav',
			'settings'   => 'ldx_widget_coursenav_item_padding',
			'type'       => 'select',
			'choices'    => array(
				'small'    => __( 'Small', 'design-upgrade-pro-learndash' ),
				'medium'   => __( 'Medium', 'design-upgrade-pro-learndash' ),
				'large'    => __( 'Large', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 5
		) );

		/* Show All Topics/Quizzes. Disable Expand/Collapse Arrows */
		$wp_customize->add_setting( 'ldx_widget_coursenav_show_all', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_show_all', array(
			'label'       => __( 'Always show ' . LearnDash_Custom_Label::label_to_lower( 'topics' ) . '/' . LearnDash_Custom_Label::label_to_lower( 'quizzes' ) . '.', 'design-upgrade-pro-learndash' ),
			'description' => __( 'Show all ' . LearnDash_Custom_Label::label_to_lower( 'topics' ) . ' &amp; ' . LearnDash_Custom_Label::label_to_lower( 'quizzes' ) . ' beneath each ' . LearnDash_Custom_Label::label_to_lower( 'lesson' ) . '. Removes the arrows that open/close a ' . LearnDash_Custom_Label::label_to_lower( 'lesson' ) . '.', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_show_all',
			'priority'    => 10
		) );

		/* Default Text Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_text_color', array(
			'label'       => __( 'Text Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_text_color',
			'priority'    => 11
		) ) );

		/* Text Hover Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_text_hover_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_text_hover_color', array(
			'label'       => __( 'Hover: Text Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_text_hover_color',
			'priority'    => 12
		) ) );

		/* Background Hover Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_background_hover_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_background_hover_color', array(
			'label'       => __( 'Hover: Background Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_background_hover_color',
			'priority'    => 13
		) ) );

		/* Current Item Text Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_current_item_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_current_item_text_color', array(
			'label'       => __( 'Current Item: Text Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_current_item_text_color',
			'priority'    => 15
		) ) );

		/* Current Item Background Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_current_item_background_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_current_item_background_color', array(
			'label'       => __( 'Current Item: Background Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_current_item_background_color',
			'priority'    => 16
		) ) );

		/* Lesson Font Weight: Bold? */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_bold', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_lesson_bold', array(
			'label'       => __( 'Bold All ' . LearnDash_Custom_Label::get_label( 'lessons' ) . '?', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_lesson_bold',
			'priority'    => 20
		) );

		/* Lesson Top Spacing */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_top_spacing', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_lesson_top_spacing', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Top Spacing', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_lesson_top_spacing',
			'priority' => 21
		) );

		/* Lesson Indentation */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_indent', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_lesson_indent', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Indentation', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_lesson_indent',
			'priority' => 22
		) );

		/* Lesson Text Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_lesson_text_color', array(
			'label'       => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Text Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_lesson_text_color',
			'priority'    => 23
		) ) );

		/* Lesson Bottom Border */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_bottom_border', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_lesson_bottom_border', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Bottom Border', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 50,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_lesson_bottom_border',
			'priority' => 24
		) );

		/* Lesson Bottom Border Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_border_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_lesson_border_color', array(
			'label'       => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_lesson_border_color',
			'priority'    => 25
		) ) );

		/* Lesson Border Radius */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_lesson_border_radius', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 50,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_lesson_border_radius',
			'priority' => 26
		) );

		/* Lesson Background Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_lesson_background_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_lesson_background_color', array(
			'label'       => __( LearnDash_Custom_Label::get_label( 'lesson' ) . ' Background Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_lesson_background_color',
			'priority'    => 27
		) ) );

		/* Topic Line Separators */
		$wp_customize->add_setting( 'ldx_widget_coursenav_topic_separator', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_topic_separator', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'topic' ) . ' Line Separators', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 50,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px). Enter 0 to remove.', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_topic_separator',
			'priority' => 40
		) );

		/* Topic Line Separators: Color */
		$wp_customize->add_setting( 'ldx_widget_coursenav_topic_separator_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_widget_coursenav_topic_separator_color', array(
			'label'       => __( LearnDash_Custom_Label::get_label( 'topic' ) . ' Line Separator Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_topic_separator_color',
			'priority'    => 41
		) ) );

		/* Topic Indentation */
		$wp_customize->add_setting( 'ldx_widget_coursenav_topic_indent', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_topic_indent', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'topic' ) . ' Indentation', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_topic_indent',
			'priority' => 42
		) );

		/* Topic Quiz Indentation */
		/* Only for Topic Quizzes. Lesson Quizzes use value above. */
		$wp_customize->add_setting( 'ldx_widget_coursenav_topic_quiz_indent', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_topic_quiz_indent', array(
			'label'    => __( LearnDash_Custom_Label::get_label( 'topic' ) . ' ' . LearnDash_Custom_Label::get_label( 'quiz' ) . ' Indentation', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'  => 'ldx_learndash_widget_coursenav',
			'settings' => 'ldx_widget_coursenav_topic_quiz_indent',
			'priority' => 50
		) );

		/* Remove Status Checkmarks */
		$wp_customize->add_setting( 'ldx_widget_coursenav_remove_status', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_remove_status', array(
			'label'       => __( 'Remove Status Checkmarks', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_remove_status',
			'priority'    => 70
		) );

		/* Hide "Return to Course" link */
		$wp_customize->add_setting( 'ldx_widget_coursenav_hide_return_link', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_hide_return_link', array(
			'label'       => __( 'Hide "Return to [' . LearnDash_Custom_Label::get_label( 'course' ) . ']" link', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_hide_return_link',
			'priority'    => 80
		) );

		/* Move "Return to Course" link to top */
		$wp_customize->add_setting( 'ldx_widget_coursenav_return_link_top', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_widget_coursenav_return_link_top', array(
			'label'       => __( 'Move "Return to [' . LearnDash_Custom_Label::get_label( 'course' ) . ']" link to top', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_widget_coursenav',
			'settings'    => 'ldx_widget_coursenav_return_link_top',
			'priority'    => 81
		) );

	} // function ldx_learndash_widget_coursenav_section

	/**
	 * Course Grid: @legacy
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @access private
	 * @since  1.0
	 * @return void
	 */
	private function ldx_learndash_course_grid_section( $wp_customize ) {

		/* Grid Item: Border Width */
		$wp_customize->add_setting( 'ldx_course_grid_item_border_width', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_border_width', array(
			'label'       => __( 'Grid Item: Border Width', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_item_border_width',
			'priority'    => 10
		) );

		/* Grid Item: Border Color */
		$wp_customize->add_setting( 'ldx_course_grid_item_border_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_item_border_color', array(
			'label'       => __( 'Grid Item: Border Color', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_item_border_color',
			'priority'    => 11
		) ) );

		/* Grid Item: Border Radius */
		$wp_customize->add_setting( 'ldx_course_grid_item_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_border_radius', array(
			'label'       => __( 'Grid Item: Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_item_border_radius',
			'priority'    => 12
		) );

		/* Grid Item: Shadow */
		$wp_customize->add_setting( 'ldx_course_grid_item_shadow', array(
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_shadow', array(
			'label'      => __( 'Grid Item: Shadow', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_course_grid',
			'settings'   => 'ldx_course_grid_item_shadow',
			'type'       => 'select',
			'choices'    => array(
				'none'     => __( 'None', 'design-upgrade-pro-learndash' ),
				'default'  => __( 'Default', 'design-upgrade-pro-learndash' )
			),
			'priority'   => 20
		) );

		/* Grid Button: Width */
		$wp_customize->add_setting( 'ldx_course_grid_item_button_width', array(
			'default'           => 'inline',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_button_width', array(
			'label'      => __( 'Button: Width', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_course_grid',
			'settings'   => 'ldx_course_grid_item_button_width',
			'type'       => 'select',
			'choices'    => array(
				'inline'   => __( 'Inline', 'design-upgrade-pro-learndash' ),
				'full'     => __( 'Full', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 30
		) );

		/* Grid Progress Bar: Transparency */
		$wp_customize->add_setting( 'ldx_course_grid_item_bar_transparency', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_bar_transparency', array(
			'label'       => __( 'Transparent Progress Bar?', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_item_bar_transparency',
			'priority'    => 40
		) );

		/* Grid Item Hover: Shadow */
		$wp_customize->add_setting( 'ldx_course_grid_item_hover_shadow', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_hover_shadow', array(
			'label'       => __( 'Grid Item Hover: Add Shadow?', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_item_hover_shadow',
			'priority'    => 45
		) );

		/* Grid Item Hover: Transform */
		$wp_customize->add_setting( 'ldx_course_grid_item_hover_transform', array(
			'default'           => 'none',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_item_hover_transform', array(
			'label'      => __( 'Grid Item Hover: Transform', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_course_grid',
			'settings'   => 'ldx_course_grid_item_hover_transform',
			'type'       => 'select',
			'choices'    => array(
				'none'     => __( 'None', 'design-upgrade-pro-learndash' ),
				'lift'     => __( 'Lift', 'design-upgrade-pro-learndash' ),
				'enlarge'  => __( 'Enlarge', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 46
		) );

		/* Grid Ribbon: Position */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_position', array(
			'default'           => 'default',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_ribbon_position', array(
			'label'      => __( 'Ribbon: Position', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_course_grid',
			'settings'   => 'ldx_course_grid_ribbon_position',
			'type'       => 'select',
			'choices'    => array(
				'default'    => __( 'Default', 'design-upgrade-pro-learndash' ),
				'top-left'    => __( 'Top Left', 'design-upgrade-pro-learndash' ),
				'top-right'   => __( 'Top Right', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 50
		) );

		/* Grid Ribbon: Border Radius */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_ribbon_border_radius', array(
			'label'       => __( 'Ribbon: Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_border_radius',
			'priority'    => 55
		) );

		/* Grid Ribbon: Default: Background */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_default_bg_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_default_bg_color', array(
			'label'       => __( 'Ribbon: Default: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_default_bg_color',
			'priority'    => 60
		) ) );

		/* Grid Ribbon: Default: Text */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_default_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_default_text_color', array(
			'label'       => __( 'Ribbon: Default: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_default_text_color',
			'priority'    => 61
		) ) );

		/* Grid Ribbon: Enrolled: Background */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_enrolled_bg_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_enrolled_bg_color', array(
			'label'       => __( 'Ribbon: Enrolled: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_enrolled_bg_color',
			'priority'    => 62
		) ) );

		/* Grid Ribbon: Enrolled: Text */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_enrolled_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_enrolled_text_color', array(
			'label'       => __( 'Ribbon: Enrolled: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_enrolled_text_color',
			'priority'    => 63
		) ) );

		/* Grid Ribbon: Free: Background */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_free_bg_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_free_bg_color', array(
			'label'       => __( 'Ribbon: Free: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_free_bg_color',
			'priority'    => 64
		) ) );

		/* Grid Ribbon: Free: Text */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_free_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_free_text_color', array(
			'label'       => __( 'Ribbon: Free: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_free_text_color',
			'priority'    => 65
		) ) );

		/* Grid Ribbon: Custom: Background */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_custom_bg_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_custom_bg_color', array(
			'label'       => __( 'Ribbon: Custom: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_custom_bg_color',
			'priority'    => 66
		) ) );

		/* Grid Ribbon: Custom: Text */
		$wp_customize->add_setting( 'ldx_course_grid_ribbon_custom_text_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_ribbon_custom_text_color', array(
			'label'       => __( 'Ribbon: Custom: Text', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_ribbon_custom_text_color',
			'priority'    => 67
		) ) );

		/* Grid Category Selector: Width */
		$wp_customize->add_setting( 'ldx_course_grid_selector_width', array(
			'default'           => 'full',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_selector_width', array(
			'label'      => __( 'Category Selector: Width', 'design-upgrade-pro-learndash' ),
			'section'    => 'ldx_learndash_course_grid',
			'settings'   => 'ldx_course_grid_selector_width',
			'type'       => 'select',
			'choices'    => array(
				'full'     => __( 'Full', 'design-upgrade-pro-learndash' ),
				'inline'   => __( 'Inline', 'design-upgrade-pro-learndash' )
			),
			'priority'    => 80
		) );

		/* Grid Category Selector: Hide Label */
		$wp_customize->add_setting( 'ldx_course_grid_selector_hide_label', array(
			'default'           => false,
			'sanitize_callback' => array( $this, 'ldx_sanitize_checkbox' )
		) );
		$wp_customize->add_control( 'ldx_course_grid_selector_hide_label', array(
			'label'       => __( 'Category Selector: Hide Extra Label?', 'design-upgrade-pro-learndash' ),
			'type'        => 'checkbox',
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_selector_hide_label',
			'priority'    => 85
		) );

		/* Grid Category Selector: Background */
		$wp_customize->add_setting( 'ldx_course_grid_selector_bg_color', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_hex_color'
		) );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ldx_course_grid_selector_bg_color', array(
			'label'       => __( 'Category Selector: Background', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_selector_bg_color',
			'priority'    => 90
		) ) );

		/* Grid Category Selector: Border Radius */
		$wp_customize->add_setting( 'ldx_course_grid_selector_border_radius', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_selector_border_radius', array(
			'label'       => __( 'Category Selector: Border Radius', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'max'   => 100,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_selector_border_radius',
			'priority'    => 91
		) );

		/* Grid Category Selector: Padding */
		$wp_customize->add_setting( 'ldx_course_grid_selector_padding', array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field'
		) );
		$wp_customize->add_control( 'ldx_course_grid_selector_padding', array(
			'label'       => __( 'Category Selector: Padding', 'design-upgrade-pro-learndash' ),
			'type'        => 'number',
			'input_attrs' => array(
				'min'   => 0,
				'style' => 'width: 60px;'
			),
			'description' => __( 'Value is in pixels (px).', 'design-upgrade-pro-learndash' ),
			'section'     => 'ldx_learndash_course_grid',
			'settings'    => 'ldx_course_grid_selector_padding',
			'priority'    => 92
		) );

	} // function ldx_learndash_course_grid_section


	/**
	 * Sanitize Checkbox
	 *
	 * Accepts only "true" or "false" as possible values.
	 *
	 * @param $input
	 *
	 * @access public
	 * @since  1.0
	 * @return bool
	 */
	public function ldx_sanitize_checkbox( $input ) {
		return ( $input === true ) ? true : false;
	}

} // class LDX_Design_Upgrade_Pro_Learndash_Customizer