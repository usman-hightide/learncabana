<?php
/**
 * Block automated LMS emails to locked user accounts.
 *
 * Lock User Account (baba_user_locked) only blocks login by default.
 * This module stops LearnDash Notifications, weekly reports, license
 * reminders, and other wp_mail traffic from reaching locked users.
 *
 * @package AstraChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a user account is locked.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function lc_is_user_account_locked( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}

	return 'yes' === get_user_meta( $user_id, 'baba_user_locked', true );
}

/**
 * Whether an email address belongs to a locked Learn Cabana user.
 *
 * @param string $email Email address.
 * @return bool
 */
function lc_email_belongs_to_locked_user( $email ) {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return false;
	}

	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		return false;
	}

	return lc_is_user_account_locked( $user->ID );
}

/**
 * Parse a wp_mail recipient string / array into a flat list of emails.
 *
 * @param string|array $recipients Raw recipients.
 * @return string[]
 */
function lc_parse_mail_recipients( $recipients ) {
	if ( empty( $recipients ) ) {
		return array();
	}

	if ( is_array( $recipients ) ) {
		$list = $recipients;
	} else {
		$list = preg_split( '/[,;]+/', (string) $recipients );
	}

	$emails = array();
	foreach ( (array) $list as $item ) {
		$item = trim( (string) $item );
		if ( '' === $item ) {
			continue;
		}
		// Handle "Name <email@example.com>" format.
		if ( preg_match( '/<([^>]+)>/', $item, $matches ) ) {
			$item = trim( $matches[1] );
		}
		$email = sanitize_email( $item );
		if ( is_email( $email ) ) {
			$emails[] = strtolower( $email );
		}
	}

	return array_values( array_unique( $emails ) );
}

/**
 * Remove locked-user addresses from a recipient list.
 *
 * @param string|array $recipients Raw recipients.
 * @return array{kept: string[], removed: string[]}
 */
function lc_strip_locked_mail_recipients( $recipients ) {
	$emails   = lc_parse_mail_recipients( $recipients );
	$kept     = array();
	$removed  = array();

	foreach ( $emails as $email ) {
		if ( lc_email_belongs_to_locked_user( $email ) ) {
			$removed[] = $email;
			continue;
		}
		$kept[] = $email;
	}

	return array(
		'kept'    => $kept,
		'removed' => $removed,
	);
}

/**
 * Safety net: do not deliver mail when every To recipient is a locked user.
 * Also strips locked addresses from To / Cc / Bcc when mixed recipients exist.
 *
 * @param null|bool $return Short-circuit value.
 * @param array     $atts   wp_mail attributes.
 * @return null|bool
 */
function lc_pre_wp_mail_block_locked_users( $return, $atts ) {
	if ( null !== $return ) {
		return $return;
	}

	if ( empty( $atts['to'] ) ) {
		return $return;
	}

	$to = lc_strip_locked_mail_recipients( $atts['to'] );
	if ( empty( $to['kept'] ) && ! empty( $to['removed'] ) ) {
		/**
		 * Fires when an email is blocked because all recipients are locked.
		 *
		 * @param array $atts    Original wp_mail attributes.
		 * @param array $removed Locked recipient emails.
		 */
		do_action( 'lc_blocked_mail_to_locked_users', $atts, $to['removed'] );
		return false;
	}

	return $return;
}
add_filter( 'pre_wp_mail', 'lc_pre_wp_mail_block_locked_users', 10, 2 );

/**
 * Strip locked users from To / Cc / Bcc before send (mixed-recipient emails).
 *
 * @param array $args wp_mail args.
 * @return array
 */
function lc_wp_mail_strip_locked_recipients( $args ) {
	if ( empty( $args['to'] ) ) {
		return $args;
	}

	$to = lc_strip_locked_mail_recipients( $args['to'] );
	if ( ! empty( $to['kept'] ) ) {
		$args['to'] = $to['kept'];
	}

	if ( ! empty( $args['headers'] ) ) {
		$headers = is_array( $args['headers'] ) ? $args['headers'] : explode( "\n", str_replace( "\r\n", "\n", $args['headers'] ) );
		$clean   = array();

		foreach ( $headers as $header ) {
			$header = trim( (string) $header );
			if ( '' === $header ) {
				continue;
			}
			if ( preg_match( '/^(cc|bcc)\s*:\s*(.+)$/i', $header, $matches ) ) {
				$stripped = lc_strip_locked_mail_recipients( $matches[2] );
				if ( ! empty( $stripped['kept'] ) ) {
					$clean[] = $matches[1] . ': ' . implode( ', ', $stripped['kept'] );
				}
				continue;
			}
			$clean[] = $header;
		}

		$args['headers'] = $clean;
	}

	return $args;
}
add_filter( 'wp_mail', 'lc_wp_mail_strip_locked_recipients', 5 );

/**
 * LearnDash Notifications: remove locked-user emails from recipient list.
 *
 * @param array $emails      Recipient emails.
 * @param array $recipients  Recipient roles/types.
 * @param int   $user_id     Triggering user ID.
 * @param int   $course_id   Course ID.
 * @param int   $group_id    Group ID.
 * @return array
 */
function lc_filter_ld_notification_emails_for_locked_users( $emails, $recipients = array(), $user_id = 0, $course_id = 0, $group_id = 0 ) {
	// If the learner who triggered the notification is locked, do not notify them.
	if ( $user_id && lc_is_user_account_locked( $user_id ) ) {
		$user = get_userdata( $user_id );
		if ( $user && ! empty( $user->user_email ) ) {
			$emails = array_values(
				array_filter(
					(array) $emails,
					static function ( $email ) use ( $user ) {
						return strtolower( (string) $email ) !== strtolower( $user->user_email );
					}
				)
			);
		}
	}

	$emails = array_values(
		array_filter(
			(array) $emails,
			static function ( $email ) {
				return ! lc_email_belongs_to_locked_user( $email );
			}
		)
	);

	return $emails;
}
add_filter( 'learndash_notification_recipients_emails', 'lc_filter_ld_notification_emails_for_locked_users', 10, 5 );

/**
 * Skip LearnDash delayed / cron notifications when the learner is locked.
 *
 * Hook: learndash_notifications_send_notification (used by delayed email cron).
 *
 * @param bool  $send           Whether to send.
 * @param array $shortcode_data Notification context (user_id, course_id, ...).
 * @return bool
 */
function lc_skip_ld_notification_for_locked_users( $send, $shortcode_data = array() ) {
	if ( ! $send ) {
		return false;
	}

	$user_id = 0;
	if ( is_array( $shortcode_data ) && ! empty( $shortcode_data['user_id'] ) ) {
		$user_id = absint( $shortcode_data['user_id'] );
	}

	if ( $user_id && lc_is_user_account_locked( $user_id ) ) {
		return false;
	}

	return $send;
}
add_filter( 'learndash_notifications_send_notification', 'lc_skip_ld_notification_for_locked_users', 10, 2 );
