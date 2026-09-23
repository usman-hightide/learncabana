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
 * Ticket: Weekly Report Sent to Managers Email.
 * Layout: Weekly Supervisor Email sample (manager-report.php).
 * Does not alter the learner weekly reminder above.
 */

if ( ! defined( 'LC_MANAGER_REPORT_WARN_DAYS' ) ) {
	define( 'LC_MANAGER_REPORT_WARN_DAYS', 14 );
}

/** @return string[] */
function lc_manager_report_sm_team_titles() {
	return array(
		'Assistant Store Manager',
		'Shift Leader',
		'Sales Associate',
	);
}

/**
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
 * Whether the cron body should run (Monday site-time, forced admin/test, or wp-config).
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
	return (int) current_time( 'N' ) === 1;
}

/**
 * Whether wp_mail should fire. Dry-run sets this false.
 *
 * @return bool
 */
function lc_manager_weekly_emails_enabled() {
	if ( ! empty( $GLOBALS['lc_manager_report_dry_run'] ) ) {
		return false;
	}
	return (bool) apply_filters( 'lc_enable_manager_weekly_emails', true );
}

function lc_manager_report_initial_stats() {
	return array(
		'enrollments' => array(
			'week'  => array(
				'enrolled'  => 0,
				'activity'  => 0,
				'minutes'   => 0,
				'completed' => 0,
			),
			'total' => array(
				'not_started' => 0,
				'in_progress' => 0,
				'warning'     => 0,
				'overdue'     => 0,
				'completed'   => 0,
			),
		),
		'plans'   => array(
			'met'     => 0,
			'warning' => 0,
			'not_met' => 0,
		),
		'certs'   => array(
			'met'     => 0,
			'warning' => 0,
			'not_met' => 0,
		),
		'actions' => array(
			'accepted' => 0,
			'review'   => 0,
			'pending'  => 0,
		),
	);
}

function lc_manager_report_initial_group_stats() {
	return array(
		'learner_ids' => array(),
		'week'        => array(
			'activity'  => 0,
			'completed' => 0,
		),
		'total'       => array(
			'not_started' => 0,
			'active'      => 0,
			'warning'     => 0,
			'overdue'     => 0,
			'completed'   => 0,
		),
	);
}

/**
 * Normalize job_titles meta (string or list) for comparison.
 *
 * @param mixed $raw Meta value.
 * @return string[]
 */
function lc_manager_report_normalize_titles( $raw ) {
	if ( is_array( $raw ) ) {
		return array_values(
			array_filter(
				array_map(
					static function ( $t ) {
						return is_string( $t ) ? trim( $t ) : '';
					},
					$raw
				)
			)
		);
	}
	if ( is_string( $raw ) && '' !== $raw ) {
		return array( trim( $raw ) );
	}
	return array();
}

/**
 * Direct reports for a manager; optional job_titles allow-list.
 *
 * @param int           $manager_id Manager user ID.
 * @param string[]|null $job_titles Allowed titles, or null for any.
 * @return object[] Rows with ID.
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
			$titles = lc_manager_report_normalize_titles( get_user_meta( (int) $user->ID, 'job_titles', true ) );
			if ( empty( array_intersect( $titles, $job_titles ) ) ) {
				continue;
			}
		}
		$out[] = $user;
	}

	return $out;
}

/**
 * Classify one enrollment: not_started|active|warning|overdue|completed
 *
 * @param int $user_id User ID.
 * @param int $course_id Course ID.
 * @return string
 */
function lc_manager_classify_course_status( $user_id, $course_id ) {
	$progress = function_exists( 'learndash_user_get_course_progress' )
		? learndash_user_get_course_progress( $user_id, $course_id )
		: array();
	$status   = isset( $progress['status'] ) ? (string) $progress['status'] : 'not_started';

	if ( 'completed' === $status ) {
		return 'completed';
	}

	$due_raw = function_exists( 'get_field' ) ? get_field( 'due_date', $course_id ) : '';
	$due_ts  = $due_raw ? strtotime( (string) $due_raw ) : false;
	$today   = strtotime( 'today', (int) current_time( 'timestamp' ) );

	if ( $due_ts ) {
		if ( $due_ts < $today ) {
			return 'overdue';
		}
		$warn_until = strtotime( '+' . (int) LC_MANAGER_REPORT_WARN_DAYS . ' days', $today );
		if ( $due_ts <= $warn_until ) {
			return 'warning';
		}
	}

	if ( 'not_started' === $status || '' === $status ) {
		return 'not_started';
	}

	return 'active';
}

/**
 * Assignment actions: accepted (approved), review (submitted unapproved).
 *
 * @param array $stats Stats by ref.
 * @param int   $user_id User ID.
 */
function lc_manager_accumulate_assignment_actions( &$stats, $user_id ) {
	global $wpdb;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, m.meta_value AS approval_status
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m
				ON m.post_id = p.ID AND m.meta_key = 'approval_status'
			WHERE p.post_type = 'sfwd-assignment'
				AND p.post_status = 'publish'
				AND p.post_author = %d",
			$user_id
		)
	);

	if ( empty( $rows ) ) {
		return;
	}

	foreach ( $rows as $row ) {
		if ( '1' === (string) $row->approval_status ) {
			$stats['actions']['accepted'] += 1;
		} else {
			$stats['actions']['review'] += 1;
		}
	}
}

/**
 * Build stats for a Store Manager from direct team members.
 *
 * @param int $store_manager_id Manager ID.
 * @param int $last_week_time Unix cutoff.
 * @return array{0:array,1:array}
 */
function lc_manager_build_store_manager_stats( $store_manager_id, $last_week_time ) {
	$stats         = lc_manager_report_initial_stats();
	$active_groups = array();
	$users         = lc_manager_report_get_direct_reports( $store_manager_id, lc_manager_report_sm_team_titles() );

	foreach ( $users as $user ) {
		$user_id   = (int) $user->ID;
		$group_ids = function_exists( 'learndash_get_users_group_ids' )
			? learndash_get_users_group_ids( $user_id )
			: array();

		foreach ( (array) $group_ids as $group_id ) {
			$group_id = (int) $group_id;
			$gid      = (string) $group_id;
			if ( ! isset( $active_groups[ $gid ] ) ) {
				$active_groups[ $gid ] = lc_manager_report_initial_group_stats();
			}
			$active_groups[ $gid ]['learner_ids'][ $user_id ] = $user_id;

			$group_time = (int) get_user_meta( $user_id, 'learndash_group_' . $group_id . '_enrolled_at', true );
			$courses    = function_exists( 'learndash_group_enrolled_courses' )
				? learndash_group_enrolled_courses( $group_id )
				: array();

			foreach ( (array) $courses as $course_id ) {
				$course_id = (int) $course_id;
				$bucket    = lc_manager_classify_course_status( $user_id, $course_id );

				if ( $group_time > $last_week_time ) {
					$stats['enrollments']['week']['enrolled'] += 1;
					if ( function_exists( 'learndash_get_user_course_attempts_time_spent' ) ) {
						$seconds = (int) learndash_get_user_course_attempts_time_spent( $user_id, $course_id );
						if ( $seconds > 0 ) {
							$stats['enrollments']['week']['minutes'] += (int) round( $seconds / 60 );
						}
					}
				}

				if ( 'completed' === $bucket ) {
					$stats['enrollments']['total']['completed'] += 1;
					$stats['plans']['met'] += 1;
					$stats['certs']['met'] += 1;
					$active_groups[ $gid ]['total']['completed'] += 1;
					if ( $group_time > $last_week_time ) {
						$stats['enrollments']['week']['completed'] += 1;
						$active_groups[ $gid ]['week']['completed'] += 1;
					}
				} elseif ( 'overdue' === $bucket ) {
					$stats['enrollments']['total']['overdue'] += 1;
					$stats['plans']['not_met'] += 1;
					$stats['certs']['not_met'] += 1;
					$stats['actions']['pending'] += 1;
					$active_groups[ $gid ]['total']['overdue'] += 1;
				} elseif ( 'warning' === $bucket ) {
					$stats['enrollments']['total']['warning'] += 1;
					$stats['plans']['warning'] += 1;
					$stats['certs']['warning'] += 1;
					$stats['actions']['pending'] += 1;
					$active_groups[ $gid ]['total']['warning'] += 1;
				} elseif ( 'not_started' === $bucket ) {
					$stats['enrollments']['total']['not_started'] += 1;
					$stats['plans']['not_met'] += 1;
					$stats['certs']['not_met'] += 1;
					$stats['actions']['pending'] += 1;
					$active_groups[ $gid ]['total']['not_started'] += 1;
				} else {
					$stats['enrollments']['total']['in_progress'] += 1;
					$stats['plans']['not_met'] += 1;
					$stats['certs']['not_met'] += 1;
					$active_groups[ $gid ]['total']['active'] += 1;
					if ( $group_time > $last_week_time ) {
						$stats['enrollments']['week']['activity'] += 1;
						$active_groups[ $gid ]['week']['activity'] += 1;
					}
				}
			}
		}

		lc_manager_accumulate_assignment_actions( $stats, $user_id );
	}

	return array( $stats, $active_groups );
}

/**
 * Roll up child manager saved stats (AM←SM, DM←AM).
 *
 * @param int      $manager_id Manager ID.
 * @param string[] $child_titles Allowed child job titles.
 * @return array{0:array,1:array}
 */
function lc_manager_rollup_from_child_managers( $manager_id, $child_titles ) {
	$stats         = lc_manager_report_initial_stats();
	$active_groups = array();
	$children      = lc_manager_report_get_direct_reports( $manager_id, $child_titles );

	foreach ( $children as $child ) {
		$child_stats  = get_user_meta( $child->ID, 'lc_manager_stats', true );
		$child_groups = get_user_meta( $child->ID, 'lc_manager_groups_stats', true );

		if ( ! empty( $child_stats ) && is_array( $child_stats ) ) {
			foreach ( array( 'enrolled', 'activity', 'minutes', 'completed' ) as $key ) {
				$stats['enrollments']['week'][ $key ] += (int) ( $child_stats['enrollments']['week'][ $key ] ?? 0 );
			}
			foreach ( array( 'not_started', 'in_progress', 'warning', 'overdue', 'completed' ) as $key ) {
				$stats['enrollments']['total'][ $key ] += (int) ( $child_stats['enrollments']['total'][ $key ] ?? 0 );
			}
			foreach ( array( 'met', 'warning', 'not_met' ) as $key ) {
				$stats['plans'][ $key ] += (int) ( $child_stats['plans'][ $key ] ?? 0 );
				$stats['certs'][ $key ] += (int) ( $child_stats['certs'][ $key ] ?? ( $child_stats['plans'][ $key ] ?? 0 ) );
			}
			foreach ( array( 'accepted', 'review', 'pending' ) as $key ) {
				$stats['actions'][ $key ] += (int) ( $child_stats['actions'][ $key ] ?? 0 );
			}
		}

		if ( empty( $child_groups ) || ! is_array( $child_groups ) ) {
			continue;
		}

		foreach ( $child_groups as $group_id => $gstats ) {
			// Ignore broken legacy numeric keys from previous usort bug when empty of totals.
			$gid = (string) $group_id;
			if ( ! isset( $active_groups[ $gid ] ) ) {
				$active_groups[ $gid ] = lc_manager_report_initial_group_stats();
			}
			if ( ! empty( $gstats['learner_ids'] ) && is_array( $gstats['learner_ids'] ) ) {
				foreach ( $gstats['learner_ids'] as $lid ) {
					$active_groups[ $gid ]['learner_ids'][ (int) $lid ] = (int) $lid;
				}
			}
			foreach ( array( 'not_started', 'active', 'warning', 'overdue', 'completed' ) as $key ) {
				$active_groups[ $gid ]['total'][ $key ] += (int) ( $gstats['total'][ $key ] ?? 0 );
			}
			// Legacy in_progress → active.
			if ( isset( $gstats['total']['in_progress'] ) && empty( $gstats['total']['active'] ) ) {
				$active_groups[ $gid ]['total']['active'] += (int) $gstats['total']['in_progress'];
			}
			$active_groups[ $gid ]['week']['activity']  += (int) ( $gstats['week']['activity'] ?? 0 );
			$active_groups[ $gid ]['week']['completed'] += (int) ( $gstats['week']['completed'] ?? 0 );
		}
	}

	return array( $stats, $active_groups );
}

/**
 * Sort groups by attention score; preserve keys (uasort).
 *
 * @param array $a Group stats.
 * @param array $b Group stats.
 * @return int
 */
function lc_manager_sort_active_groups( $a, $b ) {
	$score_a = (int) ( $a['total']['overdue'] ?? 0 ) + (int) ( $a['total']['warning'] ?? 0 ) + (int) ( $a['total']['not_started'] ?? 0 );
	$score_b = (int) ( $b['total']['overdue'] ?? 0 ) + (int) ( $b['total']['warning'] ?? 0 ) + (int) ( $b['total']['not_started'] ?? 0 );
	return $score_b - $score_a;
}

/** @return string */
function lc_manager_reporting_url() {
	return esc_url( home_url( '/reporting-dashboard-2/?tab=userReportTab' ) );
}

/**
 * @param int  $n Number.
 * @param bool $link Whether to hyperlink when > 0.
 * @return string
 */
function lc_manager_link_number( $n, $link = true ) {
	$n = (int) $n;
	if ( ! $link || $n <= 0 ) {
		return (string) $n;
	}
	return '<a href="' . lc_manager_reporting_url() . '" style="color:#0066cc;text-decoration:underline;">' . $n . '</a>';
}

/**
 * Build HTML email from manager-report.php.
 *
 * @param array  $stats Stats.
 * @param array  $active_groups Groups keyed by ID.
 * @param string $display_name Name.
 * @param string $role Role key.
 * @return string
 */
function lcGenerateTemplate( $stats, $active_groups, $display_name, $role = 'store_manager' ) {
	$html = file_get_contents( dirname( __FILE__ ) . '/manager-report.php' );
	$td   = 'border: 1px solid #e6e6e6; padding:5px 10px; text-align: center;';

	$last_week  = '<tr>';
	$last_week .= '<td style="' . $td . '">' . (int) $stats['enrollments']['week']['enrolled'] . '</td>';
	$last_week .= '<td style="' . $td . '">' . (int) $stats['enrollments']['week']['activity'] . '</td>';
	$last_week .= '<td style="' . $td . '">' . number_format_i18n( (int) $stats['enrollments']['week']['minutes'] ) . '</td>';
	$last_week .= '<td style="' . $td . '">' . (int) $stats['enrollments']['week']['completed'] . '</td>';
	$last_week .= '</tr>';

	$total  = '<tr>';
	$total .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['enrollments']['total']['not_started'] ) . '</td>';
	$total .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['enrollments']['total']['in_progress'] ) . '</td>';
	$total .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['enrollments']['total']['warning'] ) . '</td>';
	$total .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['enrollments']['total']['overdue'] ) . '</td>';
	$total .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['enrollments']['total']['completed'] ) . '</td>';
	$total .= '</tr>';

	$plans  = '<tr>';
	$plans .= '<td style="' . $td . '">Learning Plans</td>';
	$plans .= '<td style="' . $td . '">' . (int) $stats['plans']['met'] . '</td>';
	$plans .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['plans']['warning'] ) . '</td>';
	$plans .= '<td style="' . $td . '">' . (int) $stats['plans']['not_met'] . '</td>';
	$plans .= '</tr>';

	$certs  = '<tr>';
	$certs .= '<td style="' . $td . '">Certifications</td>';
	$certs .= '<td style="' . $td . '">' . (int) $stats['certs']['met'] . '</td>';
	$certs .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['certs']['warning'] ) . '</td>';
	$certs .= '<td style="' . $td . '">' . (int) $stats['certs']['not_met'] . '</td>';
	$certs .= '</tr>';

	$actions  = '<tr>';
	$actions .= '<td style="' . $td . '">Actions</td>';
	$actions .= '<td style="' . $td . '">' . (int) $stats['actions']['accepted'] . '</td>';
	$actions .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['actions']['review'] ) . '</td>';
	$actions .= '<td style="' . $td . '">' . lc_manager_link_number( $stats['actions']['pending'] ) . '</td>';
	$actions .= '</tr>';

	$groups_html = '';
	if ( ! empty( $active_groups ) && is_array( $active_groups ) ) {
		uasort( $active_groups, 'lc_manager_sort_active_groups' );
		foreach ( $active_groups as $group_id => $g ) {
			$post     = get_post( (int) $group_id );
			$title    = ( $post && ! empty( $post->post_title ) ) ? $post->post_title : ( 'Group #' . $group_id );
			$learners = ! empty( $g['learner_ids'] ) ? count( $g['learner_ids'] ) : 0;

			$groups_html .= '<tr>';
			$groups_html .= '<td style="' . $td . '">' . esc_html( $title ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . (int) $learners . '</td>';
			$groups_html .= '<td style="' . $td . '">' . lc_manager_link_number( $g['total']['not_started'] ?? 0 ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . lc_manager_link_number( $g['total']['active'] ?? 0 ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . lc_manager_link_number( $g['total']['warning'] ?? 0 ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . lc_manager_link_number( $g['total']['overdue'] ?? 0 ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . (int) ( $g['week']['activity'] ?? 0 ) . '</td>';
			$groups_html .= '<td style="' . $td . '">' . (int) ( $g['week']['completed'] ?? 0 ) . '</td>';
			$groups_html .= '</tr>';
		}
	}

	$replacements = array(
		'{user_name}'        => esc_html( $display_name ),
		'{reportDate}'       => esc_html( date_i18n( 'd-M-Y' ) ),
		'{follow_up_note}'   => esc_html( lc_manager_report_follow_up_note( $role ) ),
		'{reporting_url}'    => lc_manager_reporting_url(),
		'{lastWeekStats}'    => $last_week,
		'{totalEnrollments}' => $total,
		'{learningPlans}'    => $plans,
		'{certifications}'   => $certs,
		'{actionStatus}'     => $actions,
		'{activeGroups}'     => $groups_html,
	);

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $html );
}

/**
 * Persist stats and optionally email one manager.
 *
 * @param WP_User $manager Manager.
 * @param array   $stats Stats.
 * @param array   $active_groups Groups.
 * @param string  $role Role key.
 * @return array Result meta for logging/tests.
 */
function lc_manager_process_and_maybe_send( $manager, $stats, $active_groups, $role ) {
	$result = array(
		'user_id'    => (int) $manager->ID,
		'email'      => $manager->user_email,
		'role'       => $role,
		'sent'       => false,
		'skipped'    => false,
		'has_data'   => false,
	);

	update_user_meta( $manager->ID, 'lc_manager_stats', $stats );
	update_user_meta( $manager->ID, 'lc_manager_groups_stats', $active_groups );

	$has_data = (
		(int) $stats['enrollments']['total']['not_started']
		+ (int) $stats['enrollments']['total']['in_progress']
		+ (int) $stats['enrollments']['total']['warning']
		+ (int) $stats['enrollments']['total']['overdue']
		+ (int) $stats['enrollments']['total']['completed']
	) > 0;
	$result['has_data'] = $has_data;

	if ( ! $has_data ) {
		$result['skipped'] = true;
		return $result;
	}

	$html    = lcGenerateTemplate( $stats, $active_groups, $manager->display_name, $role );
	$subject = 'Weekly Learning Follow-up Reminder - ' . date_i18n( 'd-M-Y' ) . ' for Cannabis Learning';
	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Learncabana <admin@learncabana.com>',
	);

	// Always store last rendered HTML for admin preview/debug.
	update_user_meta( $manager->ID, 'lc_manager_last_report_html', $html );
	update_user_meta( $manager->ID, 'lc_manager_last_report_at', time() );

	if ( ! lc_manager_weekly_emails_enabled() ) {
		$result['skipped'] = true;
		return $result;
	}

	$sent = wp_mail( $manager->user_email, $subject, $html, $headers );
	$result['sent'] = (bool) $sent;
	return $result;
}

/**
 * Main job: SM → AM → DM.
 *
 * @return array Summary for tests/admin notice.
 */
function send_learn_dash_weekly_report_manager_func() {
	$summary = array(
		'ran'     => false,
		'sent'    => 0,
		'skipped' => 0,
		'roles'   => array(),
	);

	if ( ! lc_manager_report_should_run() ) {
		return $summary;
	}
	$summary['ran'] = true;

	$last_week_time = time() - WEEK_IN_SECONDS;

	$store_managers = get_users(
		array(
			'meta_key'   => 'job_titles',
			'meta_value' => 'Store Manager',
			'number'     => -1,
		)
	);
	$area_managers = get_users(
		array(
			'meta_key'   => 'job_titles',
			'meta_value' => 'Area Manager',
			'number'     => -1,
		)
	);
	$district_managers = get_users(
		array(
			'meta_key'   => 'job_titles',
			'meta_value' => 'District Manager',
			'number'     => -1,
		)
	);

	foreach ( $store_managers as $manager ) {
		if ( 'yes' === get_user_meta( $manager->ID, 'baba_user_locked', true ) ) {
			continue;
		}
		list( $stats, $groups ) = lc_manager_build_store_manager_stats( $manager->ID, $last_week_time );
		$r = lc_manager_process_and_maybe_send( $manager, $stats, $groups, 'store_manager' );
		$summary['roles'][] = $r;
		if ( $r['sent'] ) {
			$summary['sent']++;
		} elseif ( $r['skipped'] ) {
			$summary['skipped']++;
		}
	}

	foreach ( $area_managers as $manager ) {
		if ( 'yes' === get_user_meta( $manager->ID, 'baba_user_locked', true ) ) {
			continue;
		}
		list( $stats, $groups ) = lc_manager_rollup_from_child_managers( $manager->ID, array( 'Store Manager' ) );
		$r = lc_manager_process_and_maybe_send( $manager, $stats, $groups, 'area_manager' );
		$summary['roles'][] = $r;
		if ( $r['sent'] ) {
			$summary['sent']++;
		} elseif ( $r['skipped'] ) {
			$summary['skipped']++;
		}
	}

	foreach ( $district_managers as $manager ) {
		if ( 'yes' === get_user_meta( $manager->ID, 'baba_user_locked', true ) ) {
			continue;
		}
		list( $stats, $groups ) = lc_manager_rollup_from_child_managers( $manager->ID, array( 'Area Manager' ) );
		$r = lc_manager_process_and_maybe_send( $manager, $stats, $groups, 'district_manager' );
		$summary['roles'][] = $r;
		if ( $r['sent'] ) {
			$summary['sent']++;
		} elseif ( $r['skipped'] ) {
			$summary['skipped']++;
		}
	}

	update_option( 'lc_manager_report_last_summary', $summary, false );
	return $summary;
}

/**
 * Schedule Mondays 07:00 site timezone (weekly).
 */
function schedule_learn_dash_weekly__manager_report() {
	$hook     = 'send_learn_dash_weekly_manager_report';
	$existing = wp_next_scheduled( $hook );
	if ( $existing ) {
		$local_dow  = (int) wp_date( 'N', $existing );
		$local_hour = (int) wp_date( 'G', $existing );
		if ( 1 !== $local_dow || 7 !== $local_hour ) {
			wp_unschedule_event( $existing, $hook );
			$existing = false;
		}
	}

	if ( ! $existing ) {
		$tz   = wp_timezone();
		$now  = new DateTimeImmutable( 'now', $tz );
		$next = $now->modify( 'next Monday' )->setTime( 7, 0, 0 );
		if ( 1 === (int) $now->format( 'N' ) && (int) $now->format( 'G' ) < 7 ) {
			$next = $now->setTime( 7, 0, 0 );
		}
		wp_schedule_event( $next->getTimestamp(), 'weekly', $hook );
	}
}
add_action( 'wp', 'schedule_learn_dash_weekly__manager_report' );
add_action( 'send_learn_dash_weekly_manager_report', 'send_learn_dash_weekly_report_manager_func' );

/** @return string */
function lc_manager_report_get_secure_run_url( $dry_run = false ) {
	$args = array( 'action' => 'lc_run_manager_report' );
	if ( $dry_run ) {
		$args['dry_run'] = '1';
	}
	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'lc_run_manager_report' );
}

function lc_manager_report_handle_secure_admin_run() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden', 'astra-child' ), 403 );
	}
	check_admin_referer( 'lc_run_manager_report' );

	$dry = ! empty( $_GET['dry_run'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $dry ) {
		$GLOBALS['lc_manager_report_dry_run'] = true;
	}
	$GLOBALS['lc_manager_report_force'] = true;
	$summary = send_learn_dash_weekly_report_manager_func();
	unset( $GLOBALS['lc_manager_report_force'], $GLOBALS['lc_manager_report_dry_run'] );

	wp_safe_redirect(
		add_query_arg(
			array(
				'lc_manager_report' => $dry ? 'dry' : 'done',
				'sent'              => isset( $summary['sent'] ) ? (int) $summary['sent'] : 0,
				'skipped'           => isset( $summary['skipped'] ) ? (int) $summary['skipped'] : 0,
			),
			admin_url( 'tools.php?page=lc-manager-report-test' )
		)
	);
	exit;
}
add_action( 'admin_post_lc_run_manager_report', 'lc_manager_report_handle_secure_admin_run' );

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

function lc_manager_report_render_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$status  = isset( $_GET['lc_manager_report'] ) ? sanitize_text_field( wp_unslash( $_GET['lc_manager_report'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$sent    = isset( $_GET['sent'] ) ? (int) $_GET['sent'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$skipped = isset( $_GET['skipped'] ) ? (int) $_GET['skipped'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$summary = get_option( 'lc_manager_report_last_summary', array() );
	$dry_url = lc_manager_report_get_secure_run_url( true );
	$live_url = lc_manager_report_get_secure_run_url( false );

	// Optional preview: ?preview_user=ID
	$preview_user = isset( $_GET['preview_user'] ) ? absint( $_GET['preview_user'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$preview_html = '';
	if ( $preview_user ) {
		$preview_html = get_user_meta( $preview_user, 'lc_manager_last_report_html', true );
		if ( ! is_string( $preview_html ) ) {
			$preview_html = '';
		}
	}
	$preview_user_obj = $preview_user ? get_userdata( $preview_user ) : false;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Manager Learning Follow-up Test', 'astra-child' ); ?></h1>

		<?php if ( 'done' === $status ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: sent count 2: skipped count */
						__( 'Live run finished. Emails sent: %1$d. Skipped (no team data / disabled): %2$d. Check WP Mail SMTP log.', 'astra-child' ),
						$sent,
						$skipped
					)
				);
				?>
			</p></div>
		<?php elseif ( 'dry' === $status ) : ?>
			<div class="notice notice-info is-dismissible"><p>
				<?php
				echo esc_html(
					__( 'Dry-run finished (no emails sent). Open View HTML on any row with Has data = yes to preview the email at the top of this page.', 'astra-child' )
				);
				?>
				<?php echo esc_html( sprintf( ' Skipped empty: %d.', $skipped ) ); ?>
			</p></div>
		<?php endif; ?>

		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'Live send warning:', 'astra-child' ); ?></strong>
			<?php esc_html_e( '“Run live” emails every unlocked SM / AM / DM who has in-scope team data. Prefer Dry-run on local/staging first.', 'astra-child' ); ?></p>
		</div>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $dry_url ); ?>">
				<?php esc_html_e( 'Dry-run (build stats, no email)', 'astra-child' ); ?>
			</a>
			<a class="button button-primary" href="<?php echo esc_url( $live_url ); ?>"
				onclick="return confirm('Send real Weekly Learning Follow-up emails to all in-scope managers now?');">
				<?php esc_html_e( 'Run live (send emails)', 'astra-child' ); ?>
			</a>
		</p>

		<?php if ( $preview_user && $preview_html ) : ?>
			<div id="lc-manager-preview" style="margin: 20px 0 30px; padding: 16px; background: #fff; border: 2px solid #2271b1; border-radius: 4px; max-width: 980px;">
				<h2 style="margin-top:0;">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: user id 2: display name or email */
							__( 'Email preview — user #%1$d (%2$s)', 'astra-child' ),
							$preview_user,
							$preview_user_obj ? $preview_user_obj->display_name : ''
						)
					);
					?>
				</h2>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'tools.php?page=lc-manager-report-test' ) ); ?>">
						<?php esc_html_e( 'Close preview', 'astra-child' ); ?>
					</a>
				</p>
				<hr>
				<div class="lc-manager-preview-body">
					<?php echo $preview_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- stored report HTML we generate ?>
				</div>
			</div>
			<script>
				(function () {
					var el = document.getElementById('lc-manager-preview');
					if (el) {
						el.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				})();
			</script>
		<?php elseif ( $preview_user && ! $preview_html ) : ?>
			<div class="notice notice-error"><p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: user id */
						__( 'No saved preview HTML for user #%d. Run Dry-run again, then click View HTML.', 'astra-child' ),
						$preview_user
					)
				);
				?>
			</p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Last run summary', 'astra-child' ); ?></h2>
		<?php if ( empty( $summary ) || empty( $summary['roles'] ) ) : ?>
			<p><?php esc_html_e( 'No summary yet. Run a dry-run.', 'astra-child' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px;">
				<thead>
					<tr>
						<th>User ID</th>
						<th>Email</th>
						<th>Role</th>
						<th>Has data</th>
						<th>Sent</th>
						<th>Preview</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $summary['roles'] as $row ) : ?>
					<tr>
						<td><?php echo (int) $row['user_id']; ?></td>
						<td><?php echo esc_html( $row['email'] ); ?></td>
						<td><?php echo esc_html( $row['role'] ); ?></td>
						<td><?php echo ! empty( $row['has_data'] ) ? 'yes' : 'no'; ?></td>
						<td><?php echo ! empty( $row['sent'] ) ? 'yes' : 'no'; ?></td>
						<td>
							<?php if ( ! empty( $row['has_data'] ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'tools.php?page=lc-manager-report-test&preview_user=' . (int) $row['user_id'] . '#lc-manager-preview' ) ); ?>">
									<?php esc_html_e( 'View HTML', 'astra-child' ); ?>
								</a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><?php echo esc_html( sprintf( 'Totals — sent: %d, skipped: %d', (int) ( $summary['sent'] ?? 0 ), (int) ( $summary['skipped'] ?? 0 ) ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
