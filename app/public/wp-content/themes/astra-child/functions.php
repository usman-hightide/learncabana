<?php
// Exit if accessed directly!
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

// END ENQUEUE PARENT ACTION
// Disable editing of specific user profile fields
function make_profile_fields_readonly($user) {
    // Specify the fields you want to make read-only
    $readonly_fields = array(
        // 'first_name',
        'last_name',
        'nickname',
        'display_name',
        // Add more fields here...
    );

    // Loop through the fields
    foreach ($readonly_fields as $field) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                // Find the input field and disable it
                $('#<?php echo $field; ?>').prop('readonly', true);
            });
        </script>
        <?php
    }
}
// add_action('admin_footer-user-edit.php', 'make_profile_fields_readonly');
// add_action('admin_footer-profile.php', 'make_profile_fields_readonly');

// Disable editing of "last name" user profile fields for subscribers
add_action('admin_head-user-edit.php', 'disable_lastname_for_specific_role');
add_action('admin_head-profile.php', 'disable_lastname_for_specific_role');

function disable_lastname_for_specific_role() {
    // Check if the current user has the specific role to restrict
    if (current_user_can('subscriber') && !current_user_can('administrator') ) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Hide the last name field wrap
                // $('form#your-profile tr.user-last-name-wrap').hide();
                // Or disable the input field (user cannot type but field still visible)
                $('#last_name').prop('readonly', true);
                $('#display_name').css('pointer-events', 'none');
                $('#display_name').prop('disabled', true);
                $('#nickname').prop('readonly', true);
            });
        </script>
        <?php
    }
}



/**
 * Bypass Force Login to allow for exceptions.
 *
 * @param bool $bypass Whether to disable Force Login. Default false.
 * @return bool
 */
function my_forcelogin_bypass( $bypass ) {

  // Allow custom login & lost-password pages
  if ( is_page( array( 'lostpassword', 'resetpass' ) ) ) {
    $bypass = true;
  }

  return $bypass;
}
add_filter( 'v_forcelogin_bypass', 'my_forcelogin_bypass' );

function hide_unused_roles($roles) {
    $used_roles = array('subscriber', 'group_leader', 'administrator', 'sub_admin'); // Add any unused roles you want to hide
    $allRoles = array_keys($roles);

    foreach ($allRoles as $role) {

        if (!in_array($role, $used_roles)) {
            // echo ">>>>>".$role. var_dump(in_array($role, $allRoles));
            unset($roles[$role]);
        }
    }

    return $roles;
}

add_filter('editable_roles', 'hide_unused_roles');

function disable_course_enrollment_email( $send_email, $user_id, $course_id, $enrollment_data ) {
    // Set $send_email to false to disable the course enrollment email
    $send_email = false;
    //logic to insert into schedular
    // try {
    //     global $wpdb;

    //     $table_name = $wpdb->prefix . 'course_enrollment_schedular';
    //     $data = array(
    //         'user_id' => $user_id,
    //         'course_id' => $course_id,
    //         `send_email`=> json_encode($send_email),
    //         `enrollment_data` => json_encode($enrollment_data),
    //         `is_notified`=> 0,
    //     );

    //     $wpdb->insert($table_name, $data);
    // }
    // catch(Exception $e) {
    //      wp_mail("mbasitmunir@hightideinc.com", "error Log", $e->getMessage());
    // }

    return $send_email;
}
add_filter( 'learndash_email_send_on_user_enrolled', 'disable_course_enrollment_email', 10, 4 );


/************************************************/
function disable_course_enrollment_email_notification($send_notification, $user_id, $course_id) {
    // Disable the course enrollment email notification by returning false
    $send_notification = false;
    // try {
    //     global $wpdb;

    //     $table_name = $wpdb->prefix . 'course_enrollment_schedular';
    //     $data = array(
    //         'user_id' => $user_id,
    //             'course_id' => $course_id,
    //             'send_email'=> '',
    //             'enrollment_data' => '',
    //             'is_notified'=> 0,
    //     );

    //     $wpdb->insert($table_name, $data);
    // }
    // catch(Exception $e) {
    //      wp_mail("mbasitmunir@hightideinc.com", "error Log", $e->getMessage());
    // }    
    return $send_notification;
}
add_filter('learndash_enrollment_notification', 'disable_course_enrollment_email_notification', 10, 3);

/*function block_user_from_course_enrollment_emails($recipient, $email_type, $user_id, $course_id) {
    // Check if the email type is for new course enrollment
    if ($email_type === 'new_course_enrollment') {
        // Block the user from receiving the email by returning an empty recipient
        try {
            global $wpdb;

            $table_name = $wpdb->prefix . 'course_enrollment_schedular';
            $data = array(
                'user_id' => $user_id,
                'course_id' => $course_id,
                'send_email'=> '',
                'enrollment_data' => '',
                'is_notified'=> 0,
            );

            $wpdb->insert($table_name, $data);
        }
        catch(Exception $e) {
             wp_mail("mbasitmunir@hightideinc.com", "error Log", $e->getMessage());
        }    
        return $recipient;
    }

    // Return the original recipient for other email types
    return $recipient;
}

add_filter('learndash_email_recipient', 'block_user_from_course_enrollment_emails', 10, 4);*/


/************************************************/


require_once dirname(__FILE__) . '/custom-reports/custom-learning-reports.php';


// Schedule email sending based on ACF date field
function schedule_email_based_on_date($user_id) {
    $user = get_user_by('ID', $user_id);
    $date = get_field('expiry_date', 'user_' . $user_id); // Replace 'acf_date_field' with the ACF field key

    // Check if the date field is set and valid
    if ($date && strtotime($date)) {
        $timestamp = strtotime($date);
        
        // Calculate the timestamp one month before the selected date
        $one_month_before = strtotime('-1 month', $timestamp);
        
        // Schedule the email using WordPress Cron
        wp_schedule_single_event($one_month_before, 'send_email_event', array($user_id));
    }
}

// Hook the function to update_user_meta action
add_action('profile_update', 'schedule_email_based_on_date', 10, 2);

// Email sending function
function send_email_function($user_id) {
    // Get user details
    $user = get_user_by('ID', $user_id);
    $email = $user->user_email;
	$date = get_field('expiry_date', 'user_' . $user_id);
    
    // Send the email
    $subject = 'Provincial License Renewal Reminder';
    $message = 'Hello, your License Expiry Date '.$date.' is coming up in 30 days. Please ensure you have renewed your license before this date to prevent any disruptions to your schedule.'."\n\nThank you,"."\nCannabis Learning";
    wp_mail($email, $subject, $message);
}

// Hook the function to the scheduled event
add_action('send_email_event', 'send_email_function');


/***
To disable the default enrollment of group leaders in courses in LearnDash, you can use a custom code snippet. The following example demonstrates how you can achieve this:
**/
/*add_action('learndash_group_leader_post_add', 'disable_group_leader_enrollment', 10, 3);

function disable_group_leader_enrollment($group_leader_id, $group_id, $course_id) {
    // Get the group leader user object
    $group_leader = get_userdata($group_leader_id);
    
    // Check if the group leader has a specific role
    if ($group_leader && in_array('group_leader', (array) $group_leader->roles)) {
        // Unenroll the group leader from the course
        learndash_remove_user_from_course($group_leader_id, $course_id);
    }
}*/
/************************************/

function stop_mails_to_locked_users($recipients, $email){
    $locked_users = get_locked_users(); // Assuming you have a function that retrieves locked users.
    
    // Convert both arrays to lowercase for reliable comparison
    $recipients = array_map('strtolower', $recipients);
    $locked_users = array_map('strtolower', $locked_users);
    
    // Find the difference between the arrays
    $valid_recipients = array_diff($recipients, $locked_users);
    
    return $valid_recipients;
}

// Use the filter provided by your LMS plugin, 'lms_email_recipients' is just a placeholder.
add_filter('lms_email_recipients', 'stop_mails_to_locked_users', 10, 2);



//New User Register Email WIth Temporary password

add_action('user_register', 'send_temp_password', 10, 1);
function send_temp_password($user_id) {
    // Generate a random password.
    $temp_password = wp_generate_password();
    // Set the new user's password
    wp_set_password($temp_password, $user_id);
    // Store the password in user_meta data.
    update_user_meta($user_id, 'temp_password', $temp_password);
}


add_action('user_register', 'email_temp_password', 10, 1);
function email_temp_password($user_id) {
    // Get the user's data.
    $user_data = get_userdata($user_id);
    // Get the temporary password from user meta data.
    $temp_password = get_user_meta($user_id, 'temp_password', true);
    // Construct the email.
    $to = $user_data->user_email;
    $subject = 'Your Temporary Password';
    $message = 'Your temporary password is: ' . $temp_password;
    // Send the email.
    wp_mail($to, $subject, $message);
}

add_action('wp_login', 'force_password_change_on_first_login', 10, 2);
function force_password_change_on_first_login($user_login, $user) {
    // Check if the user has a temporary password.
    $temp_password = get_user_meta($user->ID, 'temp_password', true);
    if ($temp_password) {
        // Redirect the user to the change password page.
        wp_redirect(site_url() . '/wp-admin/profile.php');
        // Delete the temporary password from user meta data.
        delete_user_meta($user->ID, 'temp_password');
        exit();
    }
}


if ( !function_exists('wp_password_change_notification') ) {
    function wp_password_change_notification( $user ) {
        return;
    }
}




function enqueue_custom_tincanny_script() {
    // Only load on the specific Tin Canny reporting page
    $current_screen = get_current_screen();
    if ($current_screen->base == 'toplevel_page_uncanny-learnDash-reporting') {
        wp_enqueue_script('custom-tincanny', get_template_directory_uri() . '/custom-tincanny.js', array('jquery'), '1.0.0', true);
    }
}
add_action('admin_enqueue_scripts', 'enqueue_custom_tincanny_script');

function admin_default_page() {
  return '/my-courses';
}

add_filter('login_redirect', 'admin_default_page');

function lc_restrict_backend() {

    $user_id = get_current_user_id();

    $meta_exists = metadata_exists('user', $user_id, 'wp_2fa_2fa_status');
    $meta_value  = get_user_meta($user_id, 'wp_2fa_2fa_status', true);

    if ( ! current_user_can('edit_courses') && $meta_exists ) {
        if ( $meta_value !== 'user_needs_to_setup_2fa' ) {
            wp_redirect( site_url() );
        } 
    }
}
//add_action('admin_init', 'lc_restrict_backend');

// add_action( 'admin_init', function() {
//     // Check if current user is a subscriber
//     if ( current_user_can( 'subscriber' ) ) {
//         global $pagenow;

//         // Block access only to profile.php (user profile page)
//         if ( $pagenow === 'profile.php' ) {
//             wp_redirect( home_url() ); // Redirect to homepage
//             exit;
//         }
//     }
// });


// add_filter( 'learndash_notifications_send_notification', "lc_learndash_send_notification", 10, 2 );
// function lc_learndash_send_notification( $send, $ld_notifications_shortcode_data ) {
//     update_option( 'ld_notifications_shortcode_data', $ld_notifications_shortcode_data );
//     update_option( 'ld_notifications_send', $send );
//     return $send;
// }

add_action("template_redirect", "show_lc_ld_meta");
function show_lc_ld_meta() {
    if( isset($_GET["show_lc_ld_meta"]) ) {
        $shortcode_data = get_option('ld_notifications_shortcode_data');
        $send = get_option('ld_notifications_send');

        echo "<pre>";
        print_r( $shortcode_data );
        echo "</pre>";
        die;
    }
}

add_filter( 'learndash_notifications_send_notification', 'lc_learndash_send_notification', 9999, 2 );
function lc_learndash_send_notification( $send, $ld_notifications_shortcode_data ) {

    // Optional: Store for debugging
    update_option( 'ld_notifications_shortcode_data', $ld_notifications_shortcode_data );
    update_option( 'ld_notifications_send', $send );

    // Safely extract IDs
    $course_id       = isset( $ld_notifications_shortcode_data['course_id'] ) ? $ld_notifications_shortcode_data['course_id'] : 0;
    $user_id         = isset( $ld_notifications_shortcode_data['user_id'] ) ? $ld_notifications_shortcode_data['user_id'] : 0;
    $notification_id = isset( $ld_notifications_shortcode_data['notification_id'] ) ? $ld_notifications_shortcode_data['notification_id'] : 0;

    if ( ! $course_id || ! $user_id || ! $notification_id ) {
        return $send; // Bail early if data is incomplete
    }

    // Fetch languages
    $course_language       = get_post_meta( $course_id, 'course_language', true );
    $notification_language = get_post_meta( $notification_id, 'notification_language', true );
    $user_language         = get_user_meta( $user_id, 'locale', true );

    // Normalize formats
    $user_language = ( $user_language === 'de_DE' ) ? 'german' : 'english';
    $course_language = strtolower( $course_language );
    $notification_language = strtolower( $notification_language );

    // Default to not sending
    $send = false;

    if ( $user_language === $course_language && $course_language === $notification_language ) {
        $send = true;
    }

    update_option( 'ld_notifications_user_language', $user_language."_".$user_id );
    update_option( 'ld_notifications_course_language', $course_language."_".$course_id );
    update_option( 'ld_notifications_notification_language', $notification_language."_". $notification_id);

    return $send;
}

// add_action('template_redirect', "lc_redirect_user_to_locale");
function lc_redirect_user_to_locale () {

    if ( is_user_logged_in() && !is_admin() && !wp_doing_ajax() ) {

        $user_id = get_current_user_id();
        $locale = get_user_meta( $user_id, 'locale', true );

        if ( empty($locale) ) {
            return;
        }

        // Convert WP locale (like en_US) to WPML language code (like en, de, fr)
        $lang_code = substr( $locale, 0, 2 );
        $current_lang = apply_filters( 'wpml_current_language', null );
        if ( $current_lang !== $lang_code ) {
            $lang_home = home_url() ."/". $lang_code;
            wp_redirect( $lang_home );
            exit;
        }
    }
}

// add_filter( 'sanitize_file_name', 'wpld_sanitize_assignment_filenames', 10, 2 );
function wpld_sanitize_assignment_filenames( $filename, $raw_filename ) {
    // Replace special characters with hyphens
    $filename = preg_replace( '/[^A-Za-z0-9\-_\.]/', '-', $filename );

    // Remove duplicate hyphens
    $filename = preg_replace( '/-+/', '-', $filename );

    return $filename;
}

add_filter(
    'learndash_notifications_send_notification',
    '__return_true',
    999,
    2
);

// Run once to set all courses language to English
function ld_set_all_learndash_courses_to_english_acf() {

    if( !isset($_GET["ldcl"]) ) return;

    $per_page = isset($_GET["per_page"]) ? $_GET["per_page"] : 20;
    $current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $counter = 1;
    // Get all courses
    $courses = get_posts([
        'post_type'      => 'sfwd-courses',
        'posts_per_page' => $per_page,
        'paged'          => $current_page, 
        'post_status'    => 'publish',
        'fields'         => 'ids', // only get IDs
    ]);

    if (empty($courses)) {
        echo "No courses found!";
        die;
    }

    foreach ($courses as $course_id) {
        // Set ACF field 'course_language' to 'English'
        $course_lang_meta = get_post_meta($course_id, 'course_language', true);
        if( $course_lang_meta !== 'german' ) {
            // update_field('course_language', 'english', $course_id);
            // update_post_meta($course_id, 'course_language', 'english');
            echo "<pre>";
            echo $counter++ . " - " . $course_id . " - " . var_dump( $course_lang_meta );
            echo "</pre>";
        }
    }

    die;

    // Optional: log in debug
}
add_action('template_redirect', 'ld_set_all_learndash_courses_to_english_acf');

add_filter( 'learndash_notifications_shortcode_output', 'ld_notifications_shortcode_output_function', 10, 3 );
function ld_notifications_shortcode_output_function( $result, $atts, $data ) {

    if ( empty( $atts['field'] ) || empty( $atts['show'] ) ) {
        return $result;
    }

    if ( $atts['field'] === 'assignment' && $atts['show'] === 'file_link' ) {   

        if ( empty( $data['assignment_id'] ) ) {
            return $result;
        }

        $assignment_id = $data['assignment_id'];
        $file_name     = get_post_meta( $assignment_id, 'file_name', true );

        if ( empty( $file_name ) ) {
            return $result;
        }

        $file_download_action = 'ld_download_assignment';

        // Build the download URL
        $download_url = add_query_arg(
            array(
                'action'        => $file_download_action,
                'assignment_id' => $assignment_id,
                'file_name'     => $file_name,
            ),
            home_url( '/' )
        );

        $result = sprintf(
            '<a style="padding: 5px 10px; background: black; color:white;" href="%s" target="_blank" class="button">%s</a>',
            esc_url( $download_url ),
            esc_html__( 'Download Assignment', 'text-domain' )
        );
    }

    return $result;
}

// download assignment file
add_action('init', function () {

    if ( !isset($_GET['action']) || $_GET['action'] !== 'ld_download_assignment' ) {
        return;
    }

    // Redirect if not logged in
    if (!is_user_logged_in()) {

        $current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
        wp_redirect(wp_login_url($current_url));
        exit;
    }

    if ( empty($_GET['assignment_id']) ) {
        wp_die('Assignment ID missing.');
    }

    $assignment_id = intval($_GET['assignment_id']);
    $user_id       = get_current_user_id();

    // Get course ID from assignment
    $course_id = learndash_get_course_id($assignment_id);

    if (empty($course_id)) {
        wp_die('Invalid assignment.');
    }

    // Get groups attached to that course
    $groups = learndash_get_course_groups($course_id);

    if (empty($groups)) {
        wp_die('No groups associated with this assignment.');
    }

    $is_group_leader = false;

    foreach ($groups as $group_id) {
        if ( function_exists('learndash_is_group_leader_of_group') &&
             learndash_is_group_leader_of_group($user_id, $group_id) ) {

            $is_group_leader = true;
            break;
        }
    }

    if (!$is_group_leader && !current_user_can('manage_options')) {
        wp_die('You are not allowed to download this assignment.');
    }

    // ===== File Handling =====

    $file_name = sanitize_file_name($_GET['file_name']);

    if (strpos($file_name, '..') !== false) {
        wp_die('Invalid file.');
    }

    $upload_dir = wp_upload_dir();
    $base_path  = trailingslashit($upload_dir['basedir']) . 'learndash/assignments/';
    $file_path  = $base_path . $file_name;

    if (!file_exists($file_path)) {
        wp_die('File not found.');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . mime_content_type($file_path));
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Pragma: public');

    readfile($file_path);
    exit;
});

add_filter('auto_update_plugin', '__return_false');

// Quick view in assignments
add_filter('post_row_actions', 'ld_add_pdf_quick_view_action', 30, 2);
function ld_add_pdf_quick_view_action($row_actions = array(), $post = null): array {

    if (!$post || $post->post_type !== 'sfwd-assignment') {
        return $row_actions;
    }

    // Try multiple LearnDash file storage keys
    $file_url = get_post_meta( $post->ID, 'file_link', true );

    // Only PDF check
    $file_is_pdf = in_array(
        strtolower( pathinfo( $file_url, PATHINFO_EXTENSION ) ),
        array( 'pdf' ),
        true
	);

    if ( $file_is_pdf ) {
        $view_label = __('Quick View', 'learndash');

        $row_actions['hide-if-no-js'] = sprintf(
            '<a class="view-learndash-assignment-pdf" href="%s" data-title="%s" aria-label="%s">%s</a>',
            esc_url( $file_url ),
            esc_attr( get_post_meta( $post->ID, 'file_name', true ) ),
            esc_attr( $view_label ),
            esc_html( $view_label )
        );
    } else {
        $key = 'hide-if-no-js';
        if (isset($row_actions[$key])) {
            $value = $row_actions[$key];
            unset($row_actions[$key]);       // remove it
            $row_actions[$key] = $value;     // re-add 
        }
    }

    return $row_actions;
}

add_action('admin_footer', function () {
?>
<script>
jQuery(document).ready(function($) {

    // Override LearnDash click handler safely
    $(document).on('click', '.view-learndash-assignment-pdf', function (e) {
        e.preventDefault();

        const url = $(this).attr('href');

        let content = '';
        content = '<iframe src="/?ld_pdf=' + encodeURIComponent(url) + '" style="width:100%;height:80vh;border:0;"></iframe>';
        $('#learndash-admin-table-modal')
        .html(content)
        .dialog({
            modal: true,
            draggable: false,
            resizable: false,
            width: '70%',
            title: $(this).data('title')
        });
    });

});
</script>
<?php
});

// allow file read to the "Quick View" Iframe
add_action('init', function () {

    if (!isset($_GET['ld_pdf'])) return;

    $file = esc_url_raw($_GET['ld_pdf']);

    // security: allow only uploads
    if (strpos($file, wp_upload_dir()['baseurl']) === false) {
        wp_die('Forbidden');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="file.pdf"');

    readfile(str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $file));
    exit;
});
