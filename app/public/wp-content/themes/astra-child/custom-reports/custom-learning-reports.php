<?php

// Register a custom cron interval for every five minutes
function custom_cron_intervals($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display' => __('Every Five Minutes')
    );
    $schedules['every_minutes'] = array(
        'interval' => 60, // 1 minutes in seconds
        'display' => __('Every Minutes')
    );

    $schedules['every_seconds'] = array(
        'interval' => 1,
        'display' => __('Every Seconds')
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_intervals');

// add_action("template_redirect", "send_learn_dash_weekly_report_func");
function send_learn_dash_weekly_report_func() {
    // if( !isset( $_GET["reminder_test"] ) ) return;

    // $current_user = wp_get_current_user();

    // Check if the user is logged in (optional but recommended)
    // if ( is_user_logged_in() ) {
    //     $userid = $current_user->ID;
    // }

    // Get all users with LearnDash access
    $users = get_users(array(
        'role' => 'subscriber', // Replace with the role assigned to your LearnDash users
    ));

    // $users = get_users(array(
    //     'include' => array($userid), // Replace with your user IDs
    // ));


    if (empty($users) || count($users) == 0) {
    } else {
        // wp_mail('mbasitmunir@hightideinc.com', "debugging", "subscribers :". count($users));
        //$users = [$users[0]];
        // Iterate through each user
        foreach ($users as $user) {
            $htmlTemplate = $message = file_get_contents(dirname(__FILE__) . '/learner-report.php');
            $user_id = $user->ID;
            $user_email = $user->user_email;

            // Check if baba_user_locked is yes for the current user
            $baba_user_locked = get_user_meta($user_id, 'baba_user_locked', true);
            if ($baba_user_locked === 'yes') {
                continue; // Skip sending email if baba_user_locked is true for the user
            }

            // Retrieve user stats for the past week
            $enrolled_courses = learndash_user_get_enrolled_courses($user_id);
            /*if($user_email == '19ckoehli86@gmail.com') {

            echo "<pre>";
            print_r($user);
            var_dump($enrolled_courses);
            echo "</pre>";
            }*/
            // $courses = ld_get_mycourses($user_id); // Replace with the ID of the course you want to generate the report for
            // Iterate through each course
            
            $strHtml ='';

            if($user_email != '19ckoehli86@gmail.com' && false) {
                continue; 
            } else {
                $index =1;

                foreach ($enrolled_courses as $course) {

                    $course_id = $course;
                    $course = get_post($course_id);
                    // Get the user meta key for course enrollment
                    $enrollment_meta_key = '_sfwd-courses';

                    $course_enrollment = get_user_meta($user_id, $enrollment_meta_key, true);
                    /*echo "<pre>";
                    echo "<>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>><br>";
                    var_dump($course_enrollment);
                    echo "<-------------------------------------------------->>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>><br>";
                    echo "</pre>";*/
                    $enroller_name = get_user_meta($course->enroller_id, 'enroller_name', true);
                    //var_dump($enroller_name);
                    $status = learndash_course_status($user->ID, $course_id);

                    /*******************************************/
                    
                    // Check if the user has access to the course
                    if (sfwd_lms_has_access($course_id, $user_id)) {
                        // Get the course progress details for the user
                        // $course_progress = learndash_course_progress(array(
                        //     'user_id' => $user_id,
                        //     'course_id' => $course_id,
                        // ));
                        $course_progress = learndash_user_get_course_progress($user_id, $course_id);


                        if ($course_progress !== '') {
                            // Extract the progress data
                            // $progress_data = explode('/', strip_tags($course_progress));

                            // $course_completed = explode(" ", $progress_data[0]);
                            //$course_total = (int) $progress_data[1];

                            // Calculate the progress percentage
                            //$course_percentage = ($course_completed / $course_total) * 100;
                            // learndash_get_lesson_progress
                            // $percentage = (int)preg_replace('/\s+/', '', $course_completed[0]);
                            // $percentage = $course_completed[0];
                            $completed = $course_progress['completed'];
                            $total     = $course_progress['total'];
                            $percentage = ($total > 0) ? round(($completed / $total) * 100) : 0;
                            $status = ucfirst($course_progress['status']);
                        } else {
                            $percentage = '0%';
                        }

                    } else {
                        $percentage = '0%';
                    }

                    /*******************************************************************/

                    // $status = learndash_get_users_course_status($user_id, $course_id);
                    $courseDueDate = get_field('due_date', $course_id);
                    // $status = $percentage < 100 ? "Incomplete" : "Complete";

                    $due_date = date('d-M-Y', strtotime(get_field('due_date', $course_id))); // Replace 'due_date' with the name of your custom field
                    
                    $date1=date_create($due_date);
                    $date2=date_create(date("d-M-Y", time()));
                    $diff=date_diff($date1,$date2);
                    $diffDays = $diff->format("%R%a days");
                    $borderStr = '';
                    if($index == count($enrolled_courses)) {
                        $borderStr = 'border-bottom: 1px solid #e6e6e6;';
                    }
                    $strHtml .="<tr>";
                    $strHtml .= "<td style='padding:5px 10px; border-left: 1px solid #e6e6e6;".$borderStr."'>".$course->post_title."</td>";
                    // $strHtml .= "<td style='padding:5px 10px;".$borderStr."'>".($enroller_name ?? 'N/A')."</td>";
                    $strHtml .= "<td style='padding:5px 10px;".$borderStr."'>".$percentage."%</td>";
                    $strHtml .= "<td style='padding:5px 10px; border-right: 1px solid #e6e6e6;".$borderStr."'>".$status."</td>";
                    // $strHtml .= "<td>".$dueDate."</td>";
                    // $strHtml .= "<td>".(empty($courseDueDate) ? '' : $diffDays)."</td>";
                    $strHtml .="</tr>";
                    $index++;

                }
                

                // Prepare the email content
                $subject = 'Weekly Reminder - '.date('d-M-Y', time()).' for Cannabis Learning';
                $htmlTemplate = str_replace("{user_name}", $user->display_name, $htmlTemplate);
                $htmlTemplate = str_replace("{generatedStats}", $strHtml, $htmlTemplate);
                $htmlTemplate = str_replace("{user_email}", $user->user_email, $htmlTemplate);

                $headers = array('Content-Type: text/html; charset=UTF-8','From: Learncabana <admin@learncabana.com>');
                // Send the email
                // wp_mail('faisal@basecampconsulting.co', $subject, $htmlTemplate, $headers);
                // echo $subject . "<br><br>";
                // echo "htmlTemplate : ". $htmlTemplate;
                // die("email sent to: ". $user_email );
                wp_mail($user_email, $subject, $htmlTemplate, $headers);
            }
        }
    }
}


function schedule_learn_dash_weekly_report() {
    if (!wp_next_scheduled('send_learn_dash_weekly_report')) {
        //$timestamp = wp_next_scheduled( 'send_learn_dash_weekly_report' );
        //wp_unschedule_event( $timestamp, 'send_learn_dash_weekly_report' );
        $event_time = strtotime( '07:00:00' );
        wp_schedule_event($event_time, 'weekly', 'send_learn_dash_weekly_report');
        //wp_schedule_event(time(), 'weekly', 'send_learn_dash_weekly_report');
    }
}

function schedule_event() {
//    if ( ! wp_next_scheduled( 'custom_event' ) ) {
        // Replace '02:00:00' with the desired time in HH:MM:SS format
        $event_time = strtotime( '00:00:00' ); 
        $timestamp = wp_next_scheduled( 'custom_event' );
        wp_unschedule_event( $timestamp, 'custom_event' );
        // wp_schedule_event( $event_time, 'daily', 'custom_event' );
  //  }
}
add_action( 'wp', 'schedule_event' );

add_action( 'custom_event', 'send_learn_dash_weekly_report_func' );

// Schedule the weekly report function
add_action('wp', 'schedule_learn_dash_weekly_report');

// Hook the function to the scheduled event
add_action('send_learn_dash_weekly_report', 'send_learn_dash_weekly_report_func');

/***************************************************************************/
/**
 * Supervisor weekly learning follow-up (SM / AM / DM) — Mondays.
 * Does not alter the learner weekly reminder above.
 */

function sort_active_groups($group1, $group2){
    return $group2['total']['not_started'] - $group1['total']['not_started'];
}

/**
 * Job titles Store Managers follow up with (ticket scope).
 *
 * @return string[]
 */
function lc_manager_report_sm_team_titles() {
	return array(
		'Assistant Store Manager',
		'Shift Leader',
		'Sales Associate',
	);
}

/**
 * Role-specific follow-up copy for the manager email.
 *
 * @param string $role store_manager|area_manager|district_manager
 * @return string
 */
function lc_manager_report_follow_up_note( $role ) {
	switch ( $role ) {
		case 'store_manager':
			return 'Please follow up with your Assistant Store Managers, Shift Leaders, and Sales Associates on incomplete learning. Ensure overall completion for your store team before they work independently.';
		case 'area_manager':
			return 'The figures below include Store Managers and their teams in your area. Please follow up with your Store Managers — it is their responsibility to ensure all their staff are trained.';
		case 'district_manager':
			return 'The figures below include Area Managers and everyone under them in your district. Please follow up with your Area Managers — it is their responsibility to ensure all their stores are trained.';
		default:
			return 'Please follow up on incomplete learning for your team to ensure overall completion.';
	}
}

/**
 * Whether the manager weekly job should run now.
 * Mondays in site timezone, secure admin run, or wp-config force.
 *
 * @return bool
 */
function lc_manager_report_should_run() {
	if ( ! empty( $GLOBALS['lc_manager_report_force'] ) ) {
		return true;
	}

	if ( defined( 'LC_FORCE_MANAGER_REPORT' ) && LC_FORCE_MANAGER_REPORT ) {
		return true;
	}

	// ISO-8601 numeric day: 1 = Monday (site timezone).
	return (int) current_time( 'N' ) === 1;
}

/**
 * Direct reports for a manager (unlocked), optionally filtered by job_titles.
 *
 * @param int          $manager_id Manager user ID.
 * @param string[]|null $job_titles Allowed titles, or null for any.
 * @return object[] Rows with ID and user_locked.
 */
function lc_manager_report_get_direct_reports( $manager_id, $job_titles = null ) {
	global $wpdb;

	$manager_id = absint( $manager_id );
	if ( ! $manager_id ) {
		return array();
	}

	$users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID, m1.meta_value AS user_locked
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} m
				ON m.user_id = u.ID AND m.meta_key = 'supervisor' AND m.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} m1
				ON m1.user_id = u.ID AND m1.meta_key = 'baba_user_locked'",
			(string) $manager_id
		)
	);

	if ( empty( $users ) ) {
		return array();
	}

	$out = array();
	foreach ( $users as $user ) {
		if ( null !== $user->user_locked && 'yes' === $user->user_locked ) {
			continue;
		}
		if ( is_array( $job_titles ) && ! empty( $job_titles ) ) {
			$title = get_user_meta( (int) $user->ID, 'job_titles', true );
			if ( ! in_array( $title, $job_titles, true ) ) {
				continue;
			}
		}
		$out[] = $user;
	}

	return $out;
}

function send_learn_dash_weekly_report_manager_func() {
    if ( ! lc_manager_report_should_run() ) {
        return;
    }

    global $wpdb;
    
    $last_week_time = time() - 604800;
    
    $initial = array(
        'enrollments' => array(
            'week' => array(
                'enrolled' => 0,
                'activity' => 0,
                'completed' => 0
            ),
            'total' => array(
                'enrolled' => 0,
                'not_started' => 0,
                'in_progress' => 0,
                'completed' => 0
            )
        ),
        'plans' => array(
            'met' => 0,
            'not_met' => 0
        ),
        'actions' => array(
            'accepted' => 0,
            'pending' => 0
        )
    );

    $initialGroup = array(
        'week' => array(
            'activity' => 0,
            'completed' => 0
        ),
        'total' => array(
            'enrolled' => 0,
            'not_started' => 0,
            'in_progress' => 0,
            'completed' => 0
        )
    );

    // Get all users with LearnDash access
    // $managers = get_users(array(
    //     'role' => 'manager',
    // ));
    // $storeManagers = get_users(array(
    //     'meta_key' => 'job_titles',
    //     'meta_value' => ['Sales Associate', 'Shift Leader', 'Assistant Store Manager'],
    //     'meta_compare' => 'IN'
    // ));
    $storeManagers = get_users(array(
        'meta_key' => 'job_titles',
        'meta_value' => 'Store Manager'
    ));
    $areaManagers = get_users(array(
        'meta_key' => 'job_titles',
        'meta_value' => 'Area Manager'
    ));
    $districtManagers = get_users(array(
        'meta_key' => 'job_titles',
        'meta_value' => 'District Manager'
    ));
    
    if(!(empty($storeManagers) || count($storeManagers) == 0)){
        foreach ($storeManagers as $storeManager) {
            $stats = $initial;
            $locked = get_user_meta($storeManager->ID, 'baba_user_locked', true);
            if($locked === 'yes'){
                continue;
            }

            $subject = 'Weekly Learning Follow-up Reminder - '.date('d-M-Y', time()).' for Cannabis Learning';
            $user_id = $storeManager->ID;
            $user_email = $storeManager->user_email;
            
            $activeGroups = array();

            // Ticket: SM follows up on ASM / Shift Leader / Sales Associate only.
            $users = lc_manager_report_get_direct_reports( $user_id, lc_manager_report_sm_team_titles() );
            if(count($users) > 0){
                foreach ( $users as $user ) {
                    $group_ids = learndash_get_users_group_ids($user->ID);
                    foreach ($group_ids as $group_id) {
                        $enrolled_courses = learndash_group_enrolled_courses($group_id);
                        $group_time = (int) get_user_meta($user->ID, 'learndash_group_'.$group_id.'_enrolled_at', true);
                        $groupStats = isset($activeGroups[''.$group_id]) ? $activeGroups[''.$group_id] : $initialGroup;
                        // $group_completed_percentage = learndash_get_user_group_completed_percentage($group_ids[0], $user->ID);
                        // $enrolled_courses = learndash_user_get_enrolled_courses($user->ID);
                        foreach ($enrolled_courses as $course) {
                            // $course_time = ld_course_access_from($course, $user->ID);
                            if($group_time > $last_week_time){
                                $stats['enrollments']['week']['enrolled'] += 1;
                            }
                            $stats['enrollments']['total']['enrolled'] += 1;
                            $groupStats['total']['enrolled'] += 1;
                            $course_progress = learndash_user_get_course_progress($user->ID, $course);
                            if($course_progress['status'] === 'not_started'){
                                $stats['enrollments']['total']['not_started'] += 1;
                                $groupStats['total']['not_started'] += 1;
                                $stats['plans']['not_met'] += 1;
                            }
                            else if($course_progress['status'] === 'in_progress'){
                                $stats['enrollments']['total']['in_progress'] += 1;
                                $groupStats['total']['in_progress'] += 1;
                                $stats['plans']['not_met'] += 1;
                                if($group_time > $last_week_time){
                                    $stats['enrollments']['week']['activity'] += 1;
                                    $groupStats['week']['activity'] += 1;
                                }
                            }
                            else if($course_progress['status'] === 'completed'){
                                $stats['enrollments']['total']['completed'] += 1;
                                $groupStats['total']['completed'] += 1;
                                $stats['plans']['met'] += 1;
                                if($group_time > $last_week_time){
                                    $stats['enrollments']['week']['completed'] += 1;
                                    $groupStats['week']['completed'] += 1;
                                }
                            }
                        }
                        $activeGroups[''.$group_id] = $groupStats;
                    }
                    $total_assignments = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT count(p.ID) as total
                            FROM {$wpdb->posts} p
                            LEFT JOIN {$wpdb->postmeta} m 
                            ON m.post_id = p.ID AND m.meta_key = 'approval_status'
                            WHERE p.post_type = 'sfwd-assignment' AND p.post_status = 'publish' AND p.post_author = %d",
                            $user->ID,
                        )
                    );
                    $approved_assignments = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT count(m.meta_value) as total
                            FROM {$wpdb->posts} p
                            LEFT JOIN {$wpdb->postmeta} m 
                            ON m.post_id = p.ID AND m.meta_key = 'approval_status'
                            WHERE p.post_type = 'sfwd-assignment' AND p.post_status = 'publish' AND p.post_author = %d",
                            $user->ID,
                        )
                    );
                    $stats['actions']['accepted'] += $approved_assignments;
                    $stats['actions']['pending'] += ($total_assignments - $approved_assignments);
                }
                //Sort groups by not started descending
                usort($activeGroups, 'sort_active_groups');

                //Store stats for further use
                update_user_meta($user_id, 'lc_manager_stats', $stats);
                update_user_meta($user_id, 'lc_manager_groups_stats', $activeGroups);

                $htmlTemplate = lcGenerateTemplate($stats, $activeGroups, $storeManager->display_name, 'store_manager');
                
                $headers = array('Content-Type: text/html; charset=UTF-8','From: Learncabana <admin@learncabana.com>');
                wp_mail($user_email, $subject, $htmlTemplate, $headers);
            }
        }
    }
    if(!(empty($areaManagers) || count($areaManagers) == 0)){
        foreach ($areaManagers as $areaManager) {
            $stats = $initial;
            $locked = get_user_meta($areaManager->ID, 'baba_user_locked', true);
            if($locked === 'yes'){
                continue;
            }
            
            $subject = 'Weekly Learning Follow-up Reminder - '.date('d-M-Y', time()).' for Cannabis Learning';
            $user_id = $areaManager->ID;
            $user_email = $areaManager->user_email;
            $lastWeekHtml = '';
            $totalEnrollmentsHtml = '';
            $learningPlansHtml = '';
            $actionStatusHtml = '';
            $activeGroupsHtml = '';
            $activeGroups = array();

            // Ticket: AM sees SM+below (via SM rollups); follows up with Store Managers.
            $users = lc_manager_report_get_direct_reports( $user_id, array( 'Store Manager' ) );

            if(count($users) > 0){
                foreach ( $users as $user ) {
                    $lc_manager_stats = get_user_meta($user->ID, 'lc_manager_stats', true);
                    $lc_manager_groups_stats = get_user_meta($user->ID, 'lc_manager_groups_stats', true);
                    
                    if(!empty($lc_manager_stats)){
                        $stats['enrollments']['week']['enrolled'] += $lc_manager_stats['enrollments']['week']['enrolled'];
                        $stats['enrollments']['week']['activity'] += $lc_manager_stats['enrollments']['week']['activity'];
                        $stats['enrollments']['week']['completed'] += $lc_manager_stats['enrollments']['week']['completed'];

                        $stats['enrollments']['total']['enrolled'] += $lc_manager_stats['enrollments']['total']['enrolled'];
                        $stats['enrollments']['total']['not_started'] += $lc_manager_stats['enrollments']['total']['not_started'];
                        $stats['enrollments']['total']['in_progress'] += $lc_manager_stats['enrollments']['total']['in_progress'];
                        $stats['enrollments']['total']['completed'] += $lc_manager_stats['enrollments']['total']['completed'];
                        
                        $stats['plans']['met'] += $lc_manager_stats['plans']['met'];
                        $stats['plans']['not_met'] += $lc_manager_stats['plans']['not_met'];
                        
                        $stats['actions']['accepted'] += $lc_manager_stats['actions']['accepted'];
                        $stats['actions']['pending'] += $lc_manager_stats['actions']['pending'];
                    }

                    if(!empty($lc_manager_groups_stats)){
                        foreach ($lc_manager_groups_stats as $group_id => $groups_stats) {
                            if(isset($activeGroups[''.$group_id])){
                                $activeGroups[''.$group_id]['total']['enrolled'] += $groups_stats['total']['enrolled'];
                                $activeGroups[''.$group_id]['total']['not_started'] += $groups_stats['total']['not_started'];
                                $activeGroups[''.$group_id]['total']['in_progress'] += $groups_stats['total']['in_progress'];
                                $activeGroups[''.$group_id]['total']['completed'] += $groups_stats['total']['completed'];

                                $activeGroups[''.$group_id]['week']['activity'] += $groups_stats['week']['activity'];
                                $activeGroups[''.$group_id]['week']['completed'] += $groups_stats['week']['completed'];
                            }
                            else{
                                $activeGroups[''.$group_id] = $groups_stats;
                            }
                        }
                    }
                }
                //Sort groups by not started descending
                usort($activeGroups, 'sort_active_groups');

                //Store stats for further use
                update_user_meta($user_id, 'lc_manager_stats', $stats);
                update_user_meta($user_id, 'lc_manager_groups_stats', $activeGroups);

                $htmlTemplate = lcGenerateTemplate($stats, $activeGroups, $areaManager->display_name, 'area_manager');

                $headers = array('Content-Type: text/html; charset=UTF-8','From: Learncabana <admin@learncabana.com>');
                wp_mail($user_email, $subject, $htmlTemplate, $headers);
            }
        }
    }
    if(!(empty($districtManagers) || count($districtManagers) == 0)){
        foreach ($districtManagers as $districtManager) {
            $stats = $initial;
            $locked = get_user_meta($districtManager->ID, 'baba_user_locked', true);
            if($locked === 'yes'){
                continue;
            }
            
            $subject = 'Weekly Learning Follow-up Reminder - '.date('d-M-Y', time()).' for Cannabis Learning';
            $user_id = $districtManager->ID;
            $user_email = $districtManager->user_email;
            $lastWeekHtml = '';
            $totalEnrollmentsHtml = '';
            $learningPlansHtml = '';
            $actionStatusHtml = '';
            $activeGroupsHtml = '';
            $activeGroups = array();

            // Ticket: DM sees AM+below (via AM rollups); follows up with Area Managers.
            $users = lc_manager_report_get_direct_reports( $user_id, array( 'Area Manager' ) );

            if(count($users) > 0){
                foreach ( $users as $user ) {
                    $lc_manager_stats = get_user_meta($user->ID, 'lc_manager_stats', true);
                    $lc_manager_groups_stats = get_user_meta($user->ID, 'lc_manager_groups_stats', true);
                    
                    if(!empty($lc_manager_stats)){
                        $stats['enrollments']['week']['enrolled'] += $lc_manager_stats['enrollments']['week']['enrolled'];
                        $stats['enrollments']['week']['activity'] += $lc_manager_stats['enrollments']['week']['activity'];
                        $stats['enrollments']['week']['completed'] += $lc_manager_stats['enrollments']['week']['completed'];

                        $stats['enrollments']['total']['enrolled'] += $lc_manager_stats['enrollments']['total']['enrolled'];
                        $stats['enrollments']['total']['not_started'] += $lc_manager_stats['enrollments']['total']['not_started'];
                        $stats['enrollments']['total']['in_progress'] += $lc_manager_stats['enrollments']['total']['in_progress'];
                        $stats['enrollments']['total']['completed'] += $lc_manager_stats['enrollments']['total']['completed'];
                        
                        $stats['plans']['met'] += $lc_manager_stats['plans']['met'];
                        $stats['plans']['not_met'] += $lc_manager_stats['plans']['not_met'];
                        
                        $stats['actions']['accepted'] += $lc_manager_stats['actions']['accepted'];
                        $stats['actions']['pending'] += $lc_manager_stats['actions']['pending'];
                    }

                    if(!empty($lc_manager_groups_stats)){
                        foreach ($lc_manager_groups_stats as $group_id => $groups_stats) {
                            if(isset($activeGroups[''.$group_id])){
                                $activeGroups[''.$group_id]['total']['enrolled'] += $groups_stats['total']['enrolled'];
                                $activeGroups[''.$group_id]['total']['not_started'] += $groups_stats['total']['not_started'];
                                $activeGroups[''.$group_id]['total']['in_progress'] += $groups_stats['total']['in_progress'];
                                $activeGroups[''.$group_id]['total']['completed'] += $groups_stats['total']['completed'];

                                $activeGroups[''.$group_id]['week']['activity'] += $groups_stats['week']['activity'];
                                $activeGroups[''.$group_id]['week']['completed'] += $groups_stats['week']['completed'];
                            }
                            else{
                                $activeGroups[''.$group_id] = $groups_stats;
                            }
                        }
                    }
                }
                //Sort groups by not started descending
                usort($activeGroups, 'sort_active_groups');

                //Store stats for further use
                update_user_meta($user_id, 'lc_manager_stats', $stats);
                update_user_meta($user_id, 'lc_manager_groups_stats', $activeGroups);

                $htmlTemplate = lcGenerateTemplate($stats, $activeGroups, $districtManager->display_name, 'district_manager');

                $headers = array('Content-Type: text/html; charset=UTF-8','From: Learncabana <admin@learncabana.com>');
                wp_mail($user_email, $subject, $htmlTemplate, $headers);
            }
        }
    }
}

function lcGenerateTemplate($stats, $activeGroups, $display_name, $role = 'store_manager'){
    $lastWeekHtml = '';
    $totalEnrollmentsHtml = '';
    $learningPlansHtml = '';
    $actionStatusHtml = '';
    $activeGroupsHtml = '';
    $htmlTemplate = $message = file_get_contents(dirname(__FILE__) . '/manager-report.php');

    //Last week enrollments
    $lastWeekHtml .= "<tr>";
    $lastWeekHtml .= "<td style='padding:5px 10px; border-left: 1px solid #e6e6e6; text-align: center;border-bottom: 1px solid #e4e4e4;'>".$stats['enrollments']['week']['enrolled']."</td>";
    $lastWeekHtml .= "<td style='padding:5px 10px; text-align: center;border-bottom: 1px solid #e4e4e4;'>".$stats['enrollments']['week']['activity']."</td>";
    $lastWeekHtml .= "<td style='padding:5px 10px; text-align: center;border-right: 1px solid #e6e6e6;border-bottom: 1px solid #e4e4e4;'>".$stats['enrollments']['week']['completed']."</td>";
    $lastWeekHtml .= "</tr>";

    //Total enrollments
    $totalEnrollmentsHtml .= "<tr>";
    $totalEnrollmentsHtml .= "<td style='border-bottom: 1px solid #e4e4e4;padding:5px 10px; border-left: 1px solid #e6e6e6; text-align: center;'>".$stats['enrollments']['total']['not_started']."</td>";
    $totalEnrollmentsHtml .= "<td style='border-bottom: 1px solid #e4e4e4;padding:5px 10px; text-align: center;'>".$stats['enrollments']['total']['in_progress']."</td>";
    $totalEnrollmentsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;border-right: 1px solid #e6e6e6;'>".$stats['enrollments']['total']['completed']."</td>";
    $totalEnrollmentsHtml .= "</tr>";
    
    //Learning Plans
    $learningPlansHtml .= "<tr>";
    $learningPlansHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; border-left: 1px solid #e6e6e6; text-align: center;'>Learning Plans</td>";
    $learningPlansHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$stats['plans']['met']."</td>";
    $learningPlansHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;border-right: 1px solid #e6e6e6;'>".$stats['plans']['not_met']."</td>";
    $learningPlansHtml .= "</tr>";
    
    //Action Status
    $actionStatusHtml .= "<tr>";
    $actionStatusHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; border-left: 1px solid #e6e6e6; text-align: center;'>Actions</td>";
    $actionStatusHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$stats['actions']['accepted']."</td>";
    $actionStatusHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;border-right: 1px solid #e6e6e6;'>".$stats['actions']['pending']."</td>";
    $actionStatusHtml .= "</tr>";

    foreach($activeGroups as $group_id => $activeGroup){
        $groupDetail = get_post($group_id);
        $group_title = ( $groupDetail && ! empty( $groupDetail->post_title ) ) ? $groupDetail->post_title : ( 'Group #' . $group_id );
        $activeGroupsHtml .= "<tr>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; border-left: 1px solid #e6e6e6; text-align: center;'>".$group_title."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$activeGroup['total']['enrolled']."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$activeGroup['total']['not_started']."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$activeGroup['total']['in_progress']."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$activeGroup['total']['completed']."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center;'>".$activeGroup['week']['activity']."</td>";
        $activeGroupsHtml .= "<td style='border-bottom: 1px solid #e4e4e4; padding:5px 10px; text-align: center; border-right: 1px solid #e6e6e6;'>".$activeGroup['week']['completed']."</td>";
        $activeGroupsHtml .= "</tr>";
    }

    $htmlTemplate = str_replace("{user_name}", $display_name, $htmlTemplate);
    $htmlTemplate = str_replace("{reportDate}", date("d-M-Y", time()), $htmlTemplate);
    $htmlTemplate = str_replace("{lastWeekStats}", $lastWeekHtml, $htmlTemplate);
    $htmlTemplate = str_replace("{totalEnrollments}", $totalEnrollmentsHtml, $htmlTemplate);
    $htmlTemplate = str_replace("{learningPlans}", $learningPlansHtml, $htmlTemplate);
    $htmlTemplate = str_replace("{actionStatus}", $actionStatusHtml, $htmlTemplate);
    $htmlTemplate = str_replace("{activeGroups}", $activeGroupsHtml, $htmlTemplate);
    $htmlTemplate = str_replace("{follow_up_note}", lc_manager_report_follow_up_note( $role ), $htmlTemplate);

    return $htmlTemplate;
}

/**
 * Schedule manager follow-up for Mondays 07:00 (site timezone).
 * Uses weekly interval from the next Monday anchor.
 */
function schedule_learn_dash_weekly__manager_report() {
    $hook = 'send_learn_dash_weekly_manager_report';

    // Migrate away from any prior daily / mistimed schedule.
    $existing = wp_next_scheduled( $hook );
    if ( $existing ) {
        $local_dow = (int) wp_date( 'N', $existing );
        $local_hour = (int) wp_date( 'G', $existing );
        if ( 1 !== $local_dow || 7 !== $local_hour ) {
            wp_unschedule_event( $existing, $hook );
            $existing = false;
        }
    }

    if ( ! $existing ) {
        $tz = wp_timezone();
        $now = new DateTimeImmutable( 'now', $tz );
        $next = $now->modify( 'next Monday' )->setTime( 7, 0, 0 );
        // If today is Monday and before 07:00, use today.
        if ( 1 === (int) $now->format( 'N' ) && (int) $now->format( 'G' ) < 7 ) {
            $next = $now->setTime( 7, 0, 0 );
        }
        wp_schedule_event( $next->getTimestamp(), 'weekly', $hook );
    }
}

add_action( 'wp', 'schedule_learn_dash_weekly__manager_report' );
add_action( 'send_learn_dash_weekly_manager_report', 'send_learn_dash_weekly_report_manager_func' );

/**
 * Secure admin-only run URL (nonce + manage_options).
 * Prefer the Tools page button; URL shape:
 *   /wp-admin/admin-post.php?action=lc_run_manager_report&_wpnonce=...
 */
function lc_manager_report_get_secure_run_url() {
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=lc_run_manager_report' ),
		'lc_run_manager_report'
	);
}

/**
 * Handle secure admin run — emails all in-scope SM / AM / DM.
 */
function lc_manager_report_handle_secure_admin_run() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden', 'astra-child' ), 403 );
	}

	check_admin_referer( 'lc_run_manager_report' );

	$GLOBALS['lc_manager_report_force'] = true;
	send_learn_dash_weekly_report_manager_func();
	unset( $GLOBALS['lc_manager_report_force'] );

	wp_safe_redirect(
		add_query_arg(
			'lc_manager_report',
			'done',
			admin_url( 'tools.php?page=lc-manager-report-test' )
		)
	);
	exit;
}
add_action( 'admin_post_lc_run_manager_report', 'lc_manager_report_handle_secure_admin_run' );

/**
 * Tools → Manager Follow-up Test (admins only).
 */
function lc_manager_report_register_tools_page() {
	add_management_page(
		__( 'Manager Follow-up Test', 'astra-child' ),
		__( 'Manager Follow-up Test', 'astra-child' ),
		'manage_options',
		'lc-manager-report-test',
		'lc_manager_report_render_tools_page'
	);
}
add_action( 'admin_menu', 'lc_manager_report_register_tools_page' );

/**
 * Render Tools page with Run button + secure URL.
 */
function lc_manager_report_render_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$done = isset( $_GET['lc_manager_report'] ) && 'done' === $_GET['lc_manager_report']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$url  = lc_manager_report_get_secure_run_url();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manager Learning Follow-up Test', 'astra-child' ); ?></h1>

		<?php if ( $done ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Manager follow-up job finished (SM -> AM -> DM). Check WP Mail SMTP Email Log for results.', 'astra-child' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Warning:', 'astra-child' ); ?></strong>
			<?php esc_html_e( 'This sends real emails to all unlocked Store, Area, and District Managers who have in-scope teams. Confirm mail delivery is working first.', 'astra-child' ); ?></p>
		</div>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( $url ); ?>"
				onclick="return confirm('Send Weekly Learning Follow-up emails to all in-scope managers now?');">
				<?php esc_html_e( 'Run manager follow-up now', 'astra-child' ); ?>
			</a>
		</p>

		<p><strong><?php esc_html_e( 'Secure admin URL (expires with your login nonce):', 'astra-child' ); ?></strong></p>
		<p><code style="word-break:break-all;"><?php echo esc_html( $url ); ?></code></p>
	</div>
	<?php
}