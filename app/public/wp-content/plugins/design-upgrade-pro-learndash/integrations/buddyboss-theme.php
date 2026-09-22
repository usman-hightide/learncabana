<?php
/**
 * Included in main plugin file to add compatibility for the BuddyBoss theme.
 */

if ( 'BuddyBoss Theme' == $active_theme->name || 'BuddyBoss Theme' == $active_theme->parent_theme ) {

	add_action( 'wp_head', 'ldx_buddyboss_css', 100 );

	function ldx_buddyboss_css() {

		$ldx3_option = get_option( 'ldx3_design_upgrade' );

		$css = '<style id="ldx-design-upgrade-buddyboss-css">';

		//* DARK MODE
		// Styles that don't have Customizer options, but we need to make sure
		// they work with BuddyBoss dark mode.
		
		// quiz review box
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_reviewDiv .wpProQuiz_reviewQuestion, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_reviewSummary .wpProQuiz_reviewQuestion, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_box {background-color:#232323;}';

		// quiz <label>s for single/multiple choice
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem label {background-color:#232323;}';
		//hover & .is-selected
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem label:hover,.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem label.is-selected {color:#121212;}';

		// quiz sortable elements
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem .wpProQuiz_sortable, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem.wpProQuiz_answerIncorrect .wpProQuiz_sortable, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem.wpProQuiz_answerCorrect .wpProQuiz_sortable {background-color:#232323;}';

		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_listItem[data-type="matrix_sort_answer"] .wpProQuiz_sortStringItem, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="matrix_sort_answer"] .wpProQuiz_sortStringItem {background-color:#232323;}';

		// quiz essay, <textarea>, cloze questions
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_forms input[type="text"], .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_forms textarea, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="cloze_answer"] .wpProQuiz_questionListItem .wpProQuiz_cloze input[type="text"], .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem textarea.wpProQuiz_questionEssay, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem label input.wpProQuiz_questionInput[type="text"] {background:#232323;border:0;}';
		// focus
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_forms input[type="text"]:focus, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_forms textarea:focus, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="cloze_answer"] .wpProQuiz_questionListItem .wpProQuiz_cloze input[type="text"]:focus, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem textarea.wpProQuiz_questionEssay:focus, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem label input.wpProQuiz_questionInput[type="text"]:focus {background:#232323;box-shadow:0;}';

		// quiz essay upload file
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content form[name="uploadEssay"] {background:#232323;}';
		// button and button:hover
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionListItem form[name="uploadEssay"] label{color:#aaa;}';

		// essay graded disclaimer
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .graded-disclaimer{color:#aaa;}';

		// quiz assessment questions
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="assessment_answer"] label {box-shadow:inset 0 0 0 1px #aaa;}';
		// hover
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="assessment_answer"] label:hover {color:#121212;}';
		// .is-selected
		$css .= '.bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="assessment_answer"] label:focus-within, .bb-dark-theme .learndash .wpProQuiz_content .wpProQuiz_questionList[data-type="assessment_answer"] label.is-selected {color:#121212;}';


		//* GENERAL STYLES
		
		// If big global border radius, hide arrow next to tooltips
		if ( isset( $ldx3_option['global_border_radius'] ) && $ldx3_option['global_border_radius'] > 7 ) {

			$css .= '[data-balloon][data-balloon-pos="left"]:before,[data-balloon][data-balloon-pos="right"]:before{display:none;}';

		}

		//* GLOBAL BORDER RADIUS

		if ( isset( $ldx3_option['global_border_radius'] ) && $ldx3_option['global_border_radius'] != '' ) {

			$css .= '.bb-single-course-sidebar .widget{';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '}';

			$css .= '.bb-course-preview-wrap{';
			$css .= 'border-radius:var(--ldx-global-border-radius) var(--ldx-global-border-radius) 0 0;';
			$css .= '}';

		}

		//* LINK COLOR

		if ( !empty( $ldx3_option['color_link'] ) ) {
			$css .= '.learndash-wrapper .ld-tabs .ld-tabs-navigation .ld-tab{';
			$css .= 'color:var(--ldx-color-link);';
			$css .= '}';
			$css .= '';
			$css .= '';


			// Don't change links in focus mode bc of dark theme
			// $css .= '#learndash-page-content .ld-focus-comments .ld-comment-avatar .ld-comment-avatar-author a.ld-comment-permalink,';
			// $css .= '.learndash-wrapper .comment-reply-title a,';
			// $css .=  '#learndash-page-content .ld-focus-comments .ld-focus-comments__heading-actions .ld-expand-button,';
			// $css .= '.learndash-wrapper .ld-tab-content a{';
			// $css .= 'color: var(--ldx-color-link);';
			// $css .= '}';
		}

		// LINK COLOR: HOVER
		if ( !empty( $ldx3_option['color_link_hover'] ) ) {
			$css .= '.learndash-wrapper .ld-tabs .ld-tabs-navigation .ld-tab:hover,';
			$css .= '.learndash-wrapper .ld-tabs .ld-tabs-navigation .ld-tab.ld-active{';
			$css .= 'color:var(--ldx-color-link-hover);';
			$css .= '}';

			$css .= 'body .learndash-wrapper .ld-tabs .ld-tabs-navigation .ld-tab.ld-active::after{';
			$css .= 'background-color:var(--ldx-color-link-hover);';
			$css .= '}';

			// "expand all / collapse all"
			$css .= '.ld-item-list-actions .ld-expand-button:hover{';
			$css .= 'color:var(--ldx-color-link-hover);';
			$css .= '}';

			

			// Don't change links in focus mode bc of dark theme
			// $css .= '#learndash-page-content .ld-focus-comments .ld-comment-avatar .ld-comment-avatar-author a.ld-comment-permalink:hover,';
			// $css .= '.learndash-wrapper .comment-reply-title a:hover,';
			// $css .=  '#learndash-page-content .ld-focus-comments .ld-focus-comments__heading-actions .ld-expand-button:hover,';
			// $css .= '.learndash-wrapper .ld-tab-content a:hover,';
			// $css .= '.bb-about-instructor h5 a:hover{';
			// $css .= 'color: var(--ldx-color-link-hover);';
			// $css .= '}';
		}

		//* CORRECT COLOR / COMPLETED COLOR

		if ( !empty( $ldx3_option['color_correct'] ) ) {

			// completed checkmark icons & status labels
			$css .= '.learndash-wrapper .ld-status-icon.ld-status-complete,';
			$css .= '.learndash-wrapper .ld-status-icon.ld-quiz-complete,';
			$css .= '.i-progress.i-progress-completed,';
			$css .= '.learndash-wrapper .ld-status.ld-status-complete,';
			$css .= '.learndash-wrapper .bb-ld-status .ld-status.ld-status-complete{';
			$css .= 'background:var(--ldx-color-correct) !important;';
			$css .= '}';

		}

		//* IN PROGRESS COLOR
		if ( !empty( $ldx3_option['color_in_progress'] ) ) {

			// in progress label on profile
			$css .= 'body .learndash-wrapper #ld-profile .ld-status.ld-status-progress{';
			$css .= 'background-color:var(--ldx-color-in-progress);';
			$css .= '}';

			// in progress icons
			$css .= '.learndash-wrapper #ld-profile .ld-status-icon.ld-status-in-progress{';
			$css .= 'border-right-color:var(--ldx-color-in-progress);';
			$css .= 'border-bottom-color:var(--ldx-color-in-progress);';
			$css .= '}';

			$css .= 'body .bb-progress .bb-progress-circle{';
			$css .= 'border-color:var(--ldx-color-in-progress);';
			$css .= '}';

			// in progress icon on course page (for lessons)
			$css .= 'body .learndash-wrapper .ld-secondary-in-progress-icon{';
			$css .= 'color:var(--ldx-color-in-progress);';
			$css .= '}';

		}

		//* BUTTONS
		
		// PRIMARY BUTTONS
		// Mark Complete, Post Comment, Login, Login to Enroll
		// Course Grid BTN, Search (profile)
		$css .= '.learndash-wrapper .learndash_content_wrap .learndash_mark_complete_button,';
		$css .= '.bb-single-course-sidebar a.btn-advance,';
		$css .= '#learndash-page-content .ld-focus-comments .form-submit #submit,';
		$css .= 'body .ld-course-list-items .ld_course_grid .bb-cover-list-item p.ld_course_grid_button.entry-content a,';
		$css .= '.learndash-wrapper .ld-course-resume.ld-button,';
		$css .= '.learndash-wrapper #ld-profile .ld-item-search .ld-item-search-fields .ld-item-search-submit .ld-button{';
		$css .= 'border:0;background: var(--ldx-btn-primary-bg-color);color: var(--ldx-btn-primary-text-color);';
		$css .= '}';

		$css .= '.learndash-wrapper .learndash_content_wrap .learndash_mark_complete_button:hover,';
		$css .= '.bb-single-course-sidebar a.btn-advance:hover,';
		$css .= '#learndash-page-content .ld-focus-comments .form-submit #submit:hover,';
		$css .= 'body .ld-course-list-items .ld_course_grid .bb-cover-list-item p.ld_course_grid_button.entry-content a:hover,';
		$css .= '.learndash-wrapper .ld-course-resume.ld-button:hover,';
		$css .= '.learndash-wrapper #ld-profile .ld-item-search .ld-item-search-fields .ld-item-search-submit .ld-button:hover{';
		$css .= 'border:0;background: var(--ldx-btn-primary-bg-color-hover);color: var(--ldx-btn-primary-text-color-hover);';
		$css .= '}';

		if ( isset( $ldx3_option['login_panel_remove_logo'] ) && $ldx3_option['login_panel_remove_logo'] === true ) {

			$css .= '.ld-modal.ld-login-modal .ld-login-modal-branding{';
			$css .= 'display:none;';
			$css .= '}';

		}

		//* COURSE CONTENT LISTS
		
		$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview{';
		$css .= 'padding:0;';
		$css .= '}';

		$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items a.ld-table-list-item-preview{';
		$css .= 'border-radius:var(--ldx-global-border-radius);';
		$css .= '}';

		// expand/collapse arrow
		$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-list-item-preview .ld-item-details{';
		$css .= 'margin-left:12px;';
		$css .= '}';

		// CONTAINER STYLE, BOXED
		if ( isset( $ldx3_option['list_tables_container_style'] ) && $ldx3_option['list_tables_container_style'] === 'boxed' ) {
		
			$css .= 'body .learndash-wrapper .ld-item-list.ld-lesson-list .ld-section-heading{';
			$css .= 'margin:0;';
			$css .= '}';

			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item{';
			$css .= 'margin-top:0;';
			$css .= '}';

			// Remove border radius
			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items a.ld-table-list-item-preview{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		// LESSON STYLE, TABLE
		if ( isset( $ldx3_option['list_tables_lesson_style'] ) && $ldx3_option['list_tables_lesson_style'] === 'table' ) {

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item,';
			$css .= '.single-groups .learndash-wrapper .ld-item-list .ld-item-list-item{';
			$css .= 'margin-bottom:0 !important;';
			$css .= '}';

		}

		// SECTION HEADING TEXT COLOR
		if ( !empty( $ldx3_option['list_tables_section_text_color'] ) ) {
		
			$css .= 'body .learndash-wrapper .ld-item-list.ld-lesson-list .ld-lesson-section-heading{';
			$css .= 'color:var(--ldx-content-lists-section-text-color);';
			$css .= '}';

		}

		// LESSON CONTENT HEADER
		if ( !empty( $ldx3_option['list_tables_header_bg_color'] ) ) {

			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-header{';
			$css .= 'background:var(--ldx-content-lists-header-bg-color) !important;';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['list_tables_header_text_color'] ) ) {

			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-header,';
			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-header .ld-table-list-lesson-details{';
			$css .= 'color:var(--ldx-content-lists-header-text-color);';
			$css .= '}';

		}

		// LESSONS
		$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview,';
		$css .= '.single-groups .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview{';
		$css .= 'border-radius:var(--ldx-global-border-radius);';
		$css .= '}';

		if ( !empty( $ldx3_option['list_tables_lesson_bg_color'] ) ) {

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview,';
			$css .= '.single-groups .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview{';
			$css .= 'background:var(--ldx-content-lists-lesson-bg-color);';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['list_tables_lesson_text_color'] ) ) {

			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items div.ld-table-list-item a.ld-table-list-item-preview .ld-topic-title,';
			$css .= 'body .learndash-wrapper .ld-item-list-item-expanded .ld-table-list-items .ld-table-list-item .ld-table-list-item-quiz .ld-item-title{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color);';
			$css .= '}';

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview .ld-item-details .ld-expand-button .ld-icon{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color) !important;';
			$css .= '}';

			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-list-item-preview .ld-item-name .ld-item-title .ld-item-components span{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color);';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['list_tables_lesson_bg_color_hover'] ) ) {

			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview:hover{';
			$css .= 'background:var(--ldx-content-lists-lesson-bg-color-hover);';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['list_tables_lesson_text_color_hover'] ) ) {

			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item a.ld-item-name:hover,';
			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview:hover a.ld-item-name .ld-item-title,';
			$css .= 'body .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-list-item-preview:hover .ld-item-name .ld-item-title .ld-item-components span,';
			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items div.ld-table-list-item a.ld-table-list-item-preview:hover .ld-topic-title,';
			$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items div.ld-table-list-item a.ld-table-list-item-preview:hover .ld-topic-title::before,';
			$css .= 'body .learndash-wrapper .ld-item-list-item-expanded .ld-table-list-items .ld-table-list-item .ld-table-list-item-quiz .ld-table-list-item-preview:hover .ld-item-title{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color-hover);';
			$css .= '}';

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-preview:hover .ld-expand-button .ld-icon{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color-hover) !important;';
			$css .= '}';

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item.ld-item-lesson-item .ld-item-list-item-preview:hover .ld-item-name .ld-item-title .ld-item-components span{';
			$css .= 'color:var(--ldx-content-lists-lesson-text-color-hover);';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['list_tables_lesson_border_color'] ) ) {

			$css .= '.single-sfwd-courses .learndash-wrapper .ld-item-list .ld-item-list-item,';
			$css .= '.single-groups .learndash-wrapper .ld-item-list .ld-item-list-item{';
			$css .= 'border:var(--ldx-content-lists-lesson-border-width) solid var(--ldx-content-lists-lesson-border-color);';
			$css .= '}';

		}

		// SAMPLE LESSONS
		$css .= 'body .learndash-wrapper .ld-status-unlocked{';
		$css .= 'background:rgba(0,0,0,0.05);';
		$css .= '}';

		// TOPIC ROWS, remove border radius
		$css .= 'body .learndash-wrapper .ld-table-list .ld-table-list-items a.ld-table-list-item-preview.ld-topic-row{';
		$css .= 'border-radius:0;';
		$css .= '}';
		

		//* FOCUS MODE

		// CONTENT WIDTH (edge-to-edge)
		if ( isset( $ldx3_option['focus_mode_content_width'] ) && $ldx3_option['focus_mode_content_width'] === 'stretched' ) {
			$css .= '#learndash-page-content{padding:25px 0 0 0;}.ld-in-focus-mode .bb-lms-header,.learndash-wrapper .learndash_content_wrap .ld-table-list{padding:0 1rem;}.learndash-wrapper .learndash_content_wrap .ld-content-actions{padding:1rem;}';
		}

		// CONTENT BACKGROUND COLOR
		if ( !empty( $ldx3_option['focus_mode_content_bg_color'] ) ) {
			$css .= '#learndash-page-content{background-color:var(--ldx-focus-mode-content-bg-color);}';
		}

		// CONTENT ANIMATION
		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-right' ) {

			$css .= '.learndash-content-body{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-right;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-left' ) {

			$css .= '.learndash-content-body{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-left;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-up' ) {

			$css .= '.learndash-content-body{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-up;}';

		}

		if ( isset( $ldx3_option['focus_mode_content_animation'] ) && $ldx3_option['focus_mode_content_animation'] === 'fade-down' ) {

			$css .= '.learndash-content-body{animation:300ms ease-in-out 300ms 1 normal backwards ldx-content-fadein-down;}';

		}

		// HIDE PAGE TITLE
		if ( isset( $ldx3_option['focus_mode_hide_page_title'] ) && $ldx3_option['focus_mode_hide_page_title'] === true ) {

			$css .= '.ld-in-focus-mode .learndash-wrapper .bb-lms-header .lms-header-title{display:none;}';

		}

		// HIDE BREADCRUMBS
		if ( isset( $ldx3_option['focus_mode_hide_breadcrumbs'] ) && $ldx3_option['focus_mode_hide_breadcrumbs'] === true ) {

			// Must hide lesson, topic & quiz status divs
			$css .= '.learndash-wrapper .bb-ld-info-bar .ld-breadcrumbs{display:none;}';

		}

		// HIDE "BACK TO..." LINK
		if ( isset( $ldx3_option['focus_mode_hide_backto_link'] ) && $ldx3_option['focus_mode_hide_backto_link'] === true ) {

			$css .= '.lms-topic-sidebar-course-navigation a.course-entry-link{display:none;}';

		}

		// AVATAR STYLE (circle or square)
		if ( isset( $ldx3_option['focus_mode_avatar_style'] ) && $ldx3_option['focus_mode_avatar_style'] === 'square' ) {			

			$css .= '.site-header--bb .user-link img{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		// HIDE AVATAR & NAME
		if ( isset( $ldx3_option['focus_mode_hide_avatar'] ) && $ldx3_option['focus_mode_hide_avatar'] === true ) {

			if ( isset( $ldx3_option['focus_mode_hide_name'] ) && $ldx3_option['focus_mode_hide_name'] === true ) {

				$css .= '.ld-in-focus-mode .site-header--bb .user-wrap-container{display:none;}';

			}
		}

		// HIDE AVATAR ONLY
		if ( isset( $ldx3_option['focus_mode_hide_avatar'] ) && $ldx3_option['focus_mode_hide_avatar'] === true ) {

			$css .= '.ld-in-focus-mode .site-header--bb .user-wrap > .user-link img{display:none;}';

		}

		// HIDE NAME ONLY
		if ( isset( $ldx3_option['focus_mode_hide_name'] ) && $ldx3_option['focus_mode_hide_name'] === true ) {

			$css .= '.ld-in-focus-mode .site-header--bb .user-wrap > .user-link .user-name{display:none;}';

		}

		// SIDEBAR BG COLOR
		if ( !empty( $ldx3_option['focus_mode_sidebar_bg_color'] ) ) {

			$css .= 'body .lms-topic-sidebar-wrapper,body .lms-topic-sidebar-data{background-color:var(--ldx-focus-mode-sidebar-bg-color);}';

		}

		// SIDEBAR COURSE TEXT COLOR
		if ( !empty( $ldx3_option['focus_mode_sidebar_course_text'] ) ) {

			$css .= '.lms-topic-sidebar-course-navigation .ld-course-navigation .course-entry-title{color:var(--ldx-focus-mode-sidebar-course-text-color);}';

		}

		// COMMENTS: BACKGROUND / BORDER
		if ( !empty( $ldx3_option['focus_mode_comments_bg_color'] ) ) {

			$css .= '#learndash-page-content .ld-focus-comments .ld-comment-wrapper{';
			$css .= 'background-color:var(--ldx-focus-mode-comment-bg-color);';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '
	    border:1px solid rgba(0,0,0,0.1);';
			$css .= '}';

		}

		// ADMIN COMMENTS
		if ( !empty( $ldx3_option['focus_mode_admin_comments_bg_color'] ) ) {

			$css .= '#learndash-page-content .ld-focus-comments .role-administratorrole-bbp_keymaster .ld-comment-wrapper{';
			$css .= 'background-color:var(--ldx-focus-mode-comment-admin-bg-color);';
			$css .= '}';

		}

		if ( isset( $ldx3_option['focus_mode_admin_comments_border_width'] ) && $ldx3_option['focus_mode_admin_comments_border_width'] != '' ) {

			$css .= '#learndash-page-content .ld-focus-comments .role-administratorrole-bbp_keymaster .ld-comment-wrapper{';
			$css .= '
	    border:var(--ldx-focus-mode-comment-admin-border-width) solid var(--ldx-focus-mode-comment-admin-border-color);';
			$css .= '}';

		}

		if ( isset( $ldx3_option['focus_mode_admin_comments_avatar_border_width'] ) && $ldx3_option['focus_mode_admin_comments_avatar_border_width'] != '' ) {

			$css .= '#learndash-page-content .ld-focus-comments .role-administratorrole-bbp_keymaster .ld-comment-avatar>img{';
			$css .= 'border:var(--ldx-focus-mode-comment-admin-avatar-border-width) solid var(--ldx-focus-mode-comment-admin-avatar-border-color);';
			$css .= '}';

		}


		// COMMENT REPLY LINK/BUTTON
		$css .= '#learndash-page-content .ld-focus-comments .ld-comment-reply a.comment-reply-link{';
		$css .= 'padding:.125em 1em;';
		$css .= 'border-radius:var(--ldx-btn-border-radius);';
		$css .= 'box-shadow:inset 0 0 0 1px #939597 !important;';
		$css .= '}';

		$css .= '#learndash-page-content .ld-focus-comments .ld-comment-reply a.comment-reply-link:hover{';
		$css .= 'box-shadow: inset 0 0 0 1px rgb(0 0 0 / 75%) !important;';
		$css .= 'color: rgba(0,0,0,.9);';
		$css .= '}';

		// dark mode
		$css .= '.bb-dark-theme #learndash-page-content .ld-focus-comments .ld-comment-reply a.comment-reply-link:hover{';
		$css .= 'box-shadow: inset 0 0 0 1px rgb(255,255,255,.75) !important;';
		$css .= 'color: rgba(255,255,255,.9);';
		$css .= '}';

		//* PROGRESS BAR

		// Height
		if ( isset( $ldx3_option['progress_bar_height'] ) && $ldx3_option['progress_bar_height'] != '' ) {

			$css .= '.ld-progress-bar,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar,';
			$css .= '.ld-progress-bar .ld-progress-bar-percentage,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar .ld-progress-bar-percentage,';
			$css .= '.learndash-theme.single-sfwd-courses .ld-progress.ld-progress-inline .ld-progress-bar,';
			$css .= '.learndash-theme.single-sfwd-courses .ld-progress .ld-progress-bar .ld-progress-bar-percentage{';
			$css .= 'height:var(--ldx-progress-bar-height);';
			$css .= '}';

		}

		// Border Radius
		if ( isset( $ldx3_option['progress_bar_border_radius'] ) && $ldx3_option['progress_bar_border_radius'] != '' ) {

			$css .= '.ld-progress-bar,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar,';
			$css .= '.ld-progress-bar .ld-progress-bar-percentage,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar .ld-progress-bar-percentage{';
			$css .= 'border-radius:var(--ldx-progress-bar-border-radius);';
			$css .= '}';

		}

		// Container Background Color
		if ( !empty( $ldx3_option['progress_bar_container_bg'] ) ) {

			$css .= '.ld-progress-bar,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar,';
			$css .= '.single-sfwd-courses .learndash-wrapper .ld-progress .ld-progress-bar{';
			$css .= 'background-color:var(--ldx-progress-bar-container-bg);';
			$css .= '}';

		}

		// Bar Background Color
		if ( !empty( $ldx3_option['progress_bar_bg'] ) ) {

			$css .= '.ld-progress-bar .ld-progress-bar-percentage,';
			$css .= '.learndash-wrapper .ld-progress .ld-progress-bar .ld-progress-bar-percentage{';
			$css .= 'background-color:var(--ldx-progress-bar-bg);';
			$css .= '}';

		}

		// Hide % Complete
		if ( isset( $ldx3_option['progress_bar_hide_percent_complete'] ) && $ldx3_option['progress_bar_hide_percent_complete'] === true ) {

			$css .= 'body .lms-topic-sidebar-progress .course-completion-rate,body .ld-course-list-items .course-completion-rate{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// Hide X/Y Steps
		if ( isset( $ldx3_option['progress_bar_hide_steps'] ) && $ldx3_option['progress_bar_hide_steps'] === true ) {
			$css .= '.learndash-wrapper .ld-progress-steps{';
			$css .= 'display:none;';
			$css .= '}';
		}


		//* ALERTS

		// ALERT BORDER WIDTH
		if ( isset( $ldx3_option['alert_border_width'] ) && $ldx3_option['alert_border_width'] != '' ) {

			$css .= 'body .learndash-wrapper .ld-alert{';
			$css .= 'border-width:var(--ldx-alert-border-width);';
			$css .= '}';

		}

		// REMOVE ALERT ICONS
		if ( isset( $ldx3_option['alert_remove_icons'] ) && $ldx3_option['alert_remove_icons'] === true ) {
			$css .= '.site-main .learndash-wrapper .ld-alert .ld-alert-icon{';
			$css .= 'display:none;';
			$css .= '}';

		}

		//* COURSE NAVIGATION
		
		$css .= '.lms-topic-sidebar-progress .course-progress-wrap,';
		$css .= '.lms-topic-sidebar-wrapper .lms-course-members-list .lms-course-sidebar-heading{';
		$css .= 'border-color:rgba(0,0,0,0.1);';
		$css .= '}';
		
		// Disable Expand/Collapse
		if ( isset( $ldx3_option['coursenav_disable_expand_collapse'] ) && $ldx3_option['coursenav_disable_expand_collapse'] === true ) {

			$css .= '.lms-lesson-item .lms-lesson-content{';
			$css .= 'display:block !important;';
			$css .= '}';

			$css .= '.lms-toggle-lesson{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// Strikethrough Completed Items
		if ( isset( $ldx3_option['coursenav_strikethrough_completed'] ) && $ldx3_option['coursenav_strikethrough_completed'] === true ) { /* do nothing */
		} else {

			$css .= '.bb-completed-item{';
			$css .= 'text-decoration:none;';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['coursenav_section_bg_color'] ) ) {

			$css .= '.lms-topic-sidebar-wrapper .ld-item-list-section-heading,';
			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list .lms-course-quizzes-heading{';
			$css .= 'background:var(--ldx-course-nav-section-bg-color);';
			$css .= '}';

			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list .lms-course-quizzes-heading{';
			$css .= 'padding:5px 30px 5px 25px;';
			$css .= 'margin:20px 0 0;';
			$css .= 'line-height:27px;';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['coursenav_section_text_color'] ) ) {
			$css .= '.lms-topic-sidebar-wrapper .ld-item-list-section-heading .ld-lesson-section-heading,';
			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list .lms-course-quizzes-heading{';
			$css .= 'color:var(--ldx-course-nav-section-text-color);';
			$css .= '}';
		}

		if ( !empty( $ldx3_option['coursenav_link_text_color'] ) ) {
			$css .= '.lms-topic-sidebar-wrapper .lms-lessions-list > ol li a.bb-lesson-head,';
			$css .= '.bb-type-list .lms-topic-item a,';
			$css .= '.lms-quiz-list li a,';
			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list > ul li a{';
			$css .= 'color:var(--ldx-course-nav-link-text-color);';
			$css .= '}';
		}

		if ( !empty( $ldx3_option['coursenav_link_bg_color_hover'] ) ) {
			$css .= '.lms-topic-sidebar-wrapper .lms-lessions-list > ol li a.bb-lesson-head:hover,';
			$css .= '.bb-type-list .lms-topic-item a:hover,';
			$css .= '.lms-quiz-list li a:hover,';
			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list > ul li a:hover{';
			$css .= 'background:var(--ldx-course-nav-link-bg-color-hover);';
			$css .= '}';

			// dark mode
			$css .= '.bb-dark-theme .lms-topic-sidebar-wrapper .lms-lessions-list > ol li a.bb-lesson-head:hover,';
			$css .= '.bb-dark-theme .bb-type-list .lms-topic-item a:hover,';
			$css .= '.bb-dark-theme .lms-quiz-list li a:hover,';
			$css .= '.bb-dark-theme .lms-topic-sidebar-wrapper .lms-course-quizzes-list > ul li a:hover{';
			$css .= 'background:rgba(146,164,183,.1);';
			$css .= '}';
		}

		if ( !empty( $ldx3_option['coursenav_link_text_color_hover'] ) ) {
			$css .= '.lms-topic-sidebar-wrapper .lms-lessions-list > ol li a.bb-lesson-head:hover,';
			$css .= '.bb-type-list .lms-topic-item a:hover,';
			$css .= '.lms-quiz-list li a:hover,';
			$css .= '.lms-topic-sidebar-wrapper .lms-course-quizzes-list > ul li a:hover{';
			$css .= 'color:var(--ldx-course-nav-link-text-color-hover);';
			$css .= '}';
		}


		//* LOGIN & REGISTRATION

		if ( !empty( $ldx3_option['log_reg_close_icon_color'] ) ) {
			$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-alert-warning .ld-alert-content,';
			$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-modal-closer,';
			$css .= '.learndash-wrapper .ld-modal.ld-can-register .ld-modal-closer{';
			$css .= 'color:var(--ldx-log-reg-close-icon-color);';
			$css .= '}';
		}		
			
		if ( !empty( $ldx3_option['login_panel_heading_color'] ) ) {
			$css .= 'body .learndash-wrapper .ld-login-modal .ld-login-modal-login .ld-modal-heading{';
			$css .= 'color:var(--ldx-login-panel-heading-color);';
			$css .= '}';
		}
			
		if ( !empty( $ldx3_option['login_panel_text_color'] ) ) {
			$css .= 'body .learndash-wrapper .ld-login-modal .ld-login-modal-login .ld-modal-text,';
			$css .= 'body .learndash-wrapper .ld-login-modal .ld-login-modal-form label{';
			$css .= 'color:var(--ldx-login-panel-text-color);';
			$css .= '}';
		}

		// Input Labels
		$css .= 'body .learndash-wrapper .ld-login-modal .ld-login-modal-form label{';
		$css .= 'margin:10px 0 8px 2px;';
		$css .= '}';

		if ( !empty( $ldx3_option['login_panel_input_bg_color'] ) ) {
			$css .= '.ld-modal.ld-login-modal .ld-login-modal-form .input{';
			$css .= 'background:var(--ldx-login-panel-input-bg-color);';
			
			$css .= '}';
		}

		if ( !empty( $ldx3_option['login_panel_input_text_color'] ) ) {
			$css .= '.ld-modal.ld-login-modal .ld-login-modal-form .input{';
			$css .= 'color:var(--ldx-login-panel-input-text-color);';
			$css .= '}';
		}

		$css .= '.ld-modal.ld-login-modal .ld-login-modal-form input[type="submit"],';
		$css .= '.ld-modal.ld-login-modal .ld-login-modal-form input[type="submit"]:hover{';
		$css .= 'background:var(--ldx-login-panel-text-color);';
		$css .= 'color:var(--ldx-login-panel-bg-color);';
		$css .= '}';

		if ( !empty( $ldx3_option['register_panel_bg_color'] ) ) {
			$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-login-modal-register{';
			$css .= 'background:var(--ldx-register-panel-bg-color);';
			$css .= '}';
		}

		if ( !empty( $ldx3_option['register_panel_text_color'] ) ) {
			$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-login-modal-register{';
			$css .= 'color:var(--ldx-register-panel-text-color);';
			$css .= '}';
		}

		$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-register .ld-button,';
		$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-login-modal-register #wp-submit{';
		$css .= 'background:var(--ldx-register-panel-text-color);';
		$css .= 'color:var(--ldx-register-panel-bg-color);';
		$css .= '}';

		$css .= '.learndash-wrapper .ld-login-modal .ld-login-modal-register .ld-button:hover,';
		$css .= '.ld-modal.ld-login-modal.ld-can-register .ld-login-modal-register #wp-submit:hover{';
		$css .= 'background:var(--ldx-register-panel-text-color);';
		$css .= 'color:var(--ldx-register-panel-bg-color);';
		$css .= '}';
		


		if ( !empty( $ldx3_option['register_panel_input_bg_color'] ) ) {
			$css .= '--ldx-register-panel-input-bg-color:' . $ldx3_option['register_panel_input_bg_color'] . ';';
		}

		if ( !empty( $ldx3_option['register_panel_input_text_color'] ) ) {
			$css .= '--ldx-register-panel-input-text-color:' . $ldx3_option['register_panel_input_text_color'] . ';';
		}

		


		//* COURSE GRID

		// CATEGORY SELECTOR
		$css .= '#ld_course_categorydropdown form {';
		$css .= 'margin-bottom:0;';
		$css .= '}';

		// GRID ITEMS
		$css .= 'body .ld-course-list-items .ld_course_grid .bb-cover-list-item{border:0;border-radius:0;}';

		$css .= '.bb-course-items .bb-cover-list-item,.bb-course-item-wrap{transition:all 0.2s ease-in-out;}';

		// BORDER RADIUS
		if ( isset( $ldx3_option['grid_item_border_radius'] ) && $ldx3_option['grid_item_border_radius'] != '' ) {

			$css .= '.bb-course-items .bb-cover-list-item{overflow:hidden;}';

			$css .= '.bb-course-items .bb-cover-list-item{';
			$css .= 'border-radius:var(--ldx-grid-item-border-radius);';
			$css .= '}';

			$css .= '.ld-course-list-content.grid-view .bb-cover-list-item:not(.bb-course-no-content) .bb-cover-wrap,';
			$css .= '.bb-course-items .bb-cover-wrap,';
			$css .= '.bb-course-items.list-view .bb-cover-wrap{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		// BORDER WIDTH
		if ( isset( $ldx3_option['grid_item_border_width'] ) && $ldx3_option['grid_item_border_width'] != '' ) {

			$css .= '.bb-course-items .bb-cover-list-item{';
			$css .= 'border-width:var(--ldx-grid-item-border-width);';
			$css .= '}';

		}

		// BORDER COLOR
		if ( isset( $ldx3_option['grid_item_border_color'] ) && $ldx3_option['grid_item_border_color'] != '' ) {

			$css .= '.bb-course-items .bb-cover-list-item{';
			$css .= 'border-color:var(--ldx-grid-item-border-color);';
			$css .= '}';

		}

		// ADD SHADOW
		if ( isset( $ldx3_option['grid_item_shadow'] ) && $ldx3_option['grid_item_shadow'] === 'shadow' ) {

			$css .= '.bb-course-items .bb-cover-list-item{';
			$css .= 'box-shadow:0 1px 4px rgba(0,0,0,0.05),0 4px 14px rgba(0,0,0,0.08);';
			$css .= '}';

		}

		// HOVER: ADD SHADOW
		if ( isset( $ldx3_option['grid_item_hover_shadow'] ) && $ldx3_option['grid_item_hover_shadow'] === true ) {

			$css .= '.bb-course-items .bb-cover-list-item:hover{';
			$css .= 'box-shadow:0 1px 4px rgba(0,0,0,0.05),0 4px 14px rgba(0,0,0,0.08);';
			$css .= '}';

		}

		// HOVER: TRANSFORM (LIFT/ENLARGE)
		if ( isset( $ldx3_option['grid_item_hover_transform'] ) && $ldx3_option['grid_item_hover_transform'] != 'none' ) {

			$css .= '.bb-course-items .bb-cover-list-item:hover{';

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
		

		// RIBBONS

		// "Complete" Ribbons
		if ( !empty( $ldx3_option['color_correct'] ) ) {

			$css .= 'body .bb-cover-list-item .ld-status-complete.ld-secondary-background{background-color:var(--ldx-color-correct);}';

		}

		// "Default" Ribbons
		// Used for "Start Course" and "In Progress"
		if ( !empty( $ldx3_option['grid_ribbon_default_bg_color'] ) || !empty( $ldx3_option['grid_ribbon_default_text_color'] ) ) {

			$css .= '.bb-cover-list-item .ld-status.ld-status-progress{';

				// Background Color
				if( !empty( $ldx3_option['grid_ribbon_default_bg_color'] ) ) {
				
					$css .= 'background-color:var(--ldx-grid-ribbon-bg-color);';

				}

				// Text Color
				if( !empty( $ldx3_option['grid_ribbon_default_text_color'] ) ) {
				
					$css .= 'color:var(--ldx-grid-ribbon-text-color);';

				}

			$css .= '}';

		} // "Default" Ribbons

		// "Free" Ribbons
		// Used for "Free" and "Not Enrolled"
		if ( !empty( $ldx3_option['grid_ribbon_free_bg_color'] ) || !empty( $ldx3_option['grid_ribbon_free_text_color'] ) ) {

			$css .= '.bb-cover-list-item .ld-status.ld-status-incomplete{';

				// Background Color
				if( !empty( $ldx3_option['grid_ribbon_free_bg_color'] ) ) {
				
					$css .= 'background-color:var(--ldx-grid-ribbon-free-bg-color);';

				}

				// Text Color
				if( !empty( $ldx3_option['grid_ribbon_free_text_color'] ) ) {
				
					$css .= 'color:var(--ldx-grid-ribbon-free-text-color);';

				}

			$css .= '}';

		} // "Free" Ribbons

		// "Custom" Ribbons
		if ( !empty( $ldx3_option['grid_ribbon_custom_bg_color'] ) || !empty( $ldx3_option['grid_ribbon_custom_text_color'] ) ) {

			$css .= '.bb-cover-list-item .ld-status.ld-custom-ribbon-text{';

				// Background Color
				if( !empty( $ldx3_option['grid_ribbon_custom_bg_color'] ) ) {
				
					$css .= 'background-color:var(--ldx-grid-ribbon-custom-bg-color);';

				}

				// Text Color
				if( !empty( $ldx3_option['grid_ribbon_custom_text_color'] ) ) {
				
					$css .= 'color:var(--ldx-grid-ribbon-custom-text-color);';

				}

			$css .= '}';

		} // "Custom" Ribbons

		// Ribbon Position
		if( isset( $ldx3_option['grid_ribbon_position'] ) && $ldx3_option['grid_ribbon_position'] === 'top-left' ) {

			$css .= '.bb-cover-list-item .ld-status{left:0;border-radius:0 var(--ldx-global-border-radius) var(--ldx-global-border-radius) 0;}';

		}

		if( isset( $ldx3_option['grid_ribbon_position'] ) && $ldx3_option['grid_ribbon_position'] === 'top-right' ) {

			$css .= '.bb-cover-list-item .ld-status{right:0;border-radius:var(--ldx-global-border-radius) 0 0 var(--ldx-global-border-radius);}';

		}


		//* PROFILE
		
		$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-heading{bottom:65px;}';

		$css .= '.learndash-wrapper .ld-profile-summary .ld-profile-stats{margin-top:0;}';

		$css .= '#ld-profile .ld-table-list .ld-table-list-items{background:transparent;}';

		// Border color for line between stats
		$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat{border-color:rgba(0,0,0,0.1);}';

		// Stats: return opacity on name of stat to 1
		$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat span{opacity:1;}';

		$css .= '.learndash-wrapper #ld-profile .ld-item-list .ld-item-list-item{margin:0!important;}';

		$css .= '.learndash-wrapper #ld-profile .ld-item-list .ld-item-list-item .ld-item-list-item-expanded .ld-progress{padding:10px 23px;}';

		$css .= '.learndash-wrapper #ld-profile .ld-item-list-item-expanded .ld-item-contents{padding:0;}';
		$css .= '.learndash-wrapper .ld-item-list .ld-item-list-item .ld-item-list-item-expanded .ld-item-contents .ld-table-list,.learndash-wrapper #ld-profile .ld-item-list-item-expanded .ld-table-list.ld-quiz-list{margin:15px 23px;}';

		// PROFILES SUMMARY BG
		if ( !empty( $ldx3_option['profile_summary_bg_color'] ) ) {

			$css .= '.learndash-wrapper .ld-profile-summary{';
			$css .= 'padding:0;';
			$css .= 'background:transparent;';
			$css .= 'border-radius:0;';
			$css .= '}';

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card{';
			$css .= 'border:0;';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= 'background-color:var(--ldx-profile-summary-bg-color);';
			$css .= '}';

		}

		// PROFILE SUMMARY TEXT
		if ( !empty( $ldx3_option['profile_summary_text_color'] ) ) {

			// Stats: reduce opacity on name of stat
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat span{opacity:.75;}';

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-heading,';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat strong,';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat span{';
			$css .= 'color:var(--ldx-profile-summary-text-color);';
			$css .= '}';

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-edit-link{';
			$css .= 'color:var(--ldx-profile-summary-text-color);';
			$css .= 'border-color:var(--ldx-profile-summary-text-color) !important;';
			$css .= '}';

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-edit-link:hover{';
			$css .= 'box-shadow:0 0 0 1px var(--ldx-profile-summary-text-color) !important;';
			$css .= 'text-decoration:none;';
			$css .= '}';

		}

		// HIDE SECTION: STATS
		if ( isset( $ldx3_option['profile_hide_stats_section'] ) && $ldx3_option['profile_hide_stats_section'] === true ) {

			$css .= '@media (min-width:992px){';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary{';
			$css .= 'margin-bottom:4em;';
			$css .= '}';
			$css .= '}';

		}

		// AVATAR STYLE
		// CIRCLE
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'circle' ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar,';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar .avatar{';
			$css .= 'border-radius:50%;';
			$css .= '}';

		}

		// SQUARE
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'square' ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar,';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar .avatar{';
			$css .= 'border-radius:0;';
			$css .= '}';

		}

		// INHERIT BORDER RADIUS
		if ( isset( $ldx3_option['profile_avatar_style'] ) && $ldx3_option['profile_avatar_style'] === 'borderradius' ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar,';
			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-card .ld-profile-avatar .avatar{';
			$css .= 'border-radius:var(--ldx-global-border-radius);';
			$css .= '}';

		}

		// HIDE STAT: COURSES
		if ( isset( $ldx3_option['profile_hide_stat_courses'] ) && $ldx3_option['profile_hide_stat_courses'] === true ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat.ld-profile-stat-courses{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: COMPLETED
		if ( isset( $ldx3_option['profile_hide_stat_completed'] ) && $ldx3_option['profile_hide_stat_completed'] === true ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat.ld-profile-stat-completed{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: CERTIFICATES
		if ( isset( $ldx3_option['profile_hide_stat_certificates'] ) && $ldx3_option['profile_hide_stat_certificates'] === true ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat.ld-profile-stat-certificates{';
			$css .= 'display:none;';
			$css .= '}';

		}

		// HIDE STAT: POINTS
		if ( isset( $ldx3_option['profile_hide_stat_points'] ) && $ldx3_option['profile_hide_stat_points'] === true ) {

			$css .= '.learndash-wrapper #ld-profile .ld-profile-summary .ld-profile-stats .ld-profile-stat.ld-profile-stat-points{';
			$css .= 'display:none;';
			$css .= '}';

		}


		//* PAGINATION
		// PAGINATION BG COLOR
		if ( !empty( $ldx3_option['pagination_bg_color'] ) ) {

			$css .= 'body .learndash-wrapper .ld-pagination .ld-pages{';
			$css .= 'background:var(--ldx-pagination-bg-color);';
			$css .= 'padding:.5em;';
			$css .= '}';

		}

		// PAGINATION TEXT COLOR
		if ( !empty( $ldx3_option['pagination_text_color'] ) ) {

			$css .= '.learndash-wrapper .ld-pagination{';
			$css .= 'color:var(--ldx-pagination-text-color);';
			$css .= '}';

		}

		// PAGINATION ARROW COLOR
		if ( !empty( $ldx3_option['pagination_arrow_color'] ) ) {

			$css .= 'body .learndash-wrapper .ld-pagination .ld-pages a{';
			$css .= 'color:var(--ldx-pagination-arrow-color);';
			$css .= '}';

		}

		if ( !empty( $ldx3_option['pagination_arrow_color_hover'] ) ) {

			$css .= '.learndash-pager .bp-pagination a:hover{';
			$css .= 'color:inherit;';
			$css .= '}';

			$css .= 'body .learndash-wrapper .ld-pagination .ld-pages a:hover{';
			$css .= 'color:var(--ldx-pagination-arrow-color-hover);';
			$css .= '}';

		}

		if ( isset( $ldx3_option['pagination_arrow_style'] ) && $ldx3_option['pagination_arrow_style'] === 'circle' ) {

			$css .= 'body .learndash-wrapper .ld-pagination .ld-pages a{';
			$css .= 'background:var(--ldx-pagination-arrow-bg-color);';
			$css .= 'width:22px;';
			$css .= 'height:22px;';
			$css .= 'opacity:1;';
			$css .= '}';

			$css .= 'body .learndash-wrapper .ld-pagination .ld-pages a:hover{';
			$css .= 'background:var(--ldx-pagination-arrow-bg-color-hover);';
			$css .= '}';

		}


		//* TOOLTIPS
		// Use colors in BB
		// This is for global border radius
		$css .= '[data-balloon]:after,[data-bp-tooltip]:after{border-radius:var(--ldx-global-border-radius);}';
		

		$css .= '</style>';

		echo $css;

	} // function ldx_buddyboss_css()

} // end if BuddyBoss Theme