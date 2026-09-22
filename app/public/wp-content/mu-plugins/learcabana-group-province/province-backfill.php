<?php
/**
 * Explicit/manual province backfill only — never runs automatically on admin_init.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run group province backfill from titles.
 * Does not modify users. Skips DM/AM titles without an inferable province code.
 *
 * @param bool $dry_run If true, compute but do not write.
 * @return array{updated:int,skipped:int,unchanged:int,dry_run:bool}
 */
function lc_run_province_backfill( $dry_run = false ) {
	$groups = get_posts(
		array(
			'post_type'      => 'groups',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$updated   = 0;
	$skipped   = 0;
	$unchanged = 0;

	foreach ( $groups as $group_id ) {
		$existing   = get_post_meta( $group_id, 'province', true );
		$normalized = lc_normalize_province_code( $existing );

		if ( '' !== $normalized ) {
			if ( (string) $existing !== $normalized ) {
				if ( ! $dry_run ) {
					update_post_meta( $group_id, 'province', $normalized );
				}
				++$updated;
			} else {
				++$unchanged;
			}
			continue;
		}

		$title = get_the_title( $group_id );
		$code  = lc_infer_province_from_group_title( $title );
		if ( '' === $code ) {
			++$skipped;
			continue;
		}

		if ( ! $dry_run ) {
			update_post_meta( $group_id, 'province', $code );
		}
		++$updated;
	}

	if ( ! $dry_run ) {
		update_option( 'lc_province_backfill_v1_done', 1, false );
		update_option( 'lc_province_backfill_v1_count', $updated, false );
		update_option( 'lc_province_backfill_v1_last_run', gmdate( 'c' ), false );
	}

	return array(
		'updated'   => $updated,
		'skipped'   => $skipped,
		'unchanged' => $unchanged,
		'dry_run'   => (bool) $dry_run,
	);
}

/**
 * Admin Tools page — explicit backfill only.
 */
function lc_register_province_tools_page() {
	add_management_page(
		'Learn Cabana Province Backfill',
		'LC Province Backfill',
		'manage_options',
		'lc-province-backfill',
		'lc_render_province_tools_page'
	);
}
add_action( 'admin_menu', 'lc_register_province_tools_page' );

/**
 * Handle explicit backfill form POST.
 */
function lc_handle_province_backfill_request() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['lc_province_backfill_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	check_admin_referer( 'lc_province_backfill_action', 'lc_province_backfill_nonce' );

	$action = sanitize_text_field( wp_unslash( $_POST['lc_province_backfill_action'] ) );
	$result = null;

	if ( 'dry_run' === $action ) {
		$result = lc_run_province_backfill( true );
	} elseif ( 'run' === $action ) {
		$result = lc_run_province_backfill( false );
	}

	if ( null !== $result ) {
		set_transient(
			'lc_province_backfill_notice_' . get_current_user_id(),
			$result,
			60
		);
	}

	wp_safe_redirect( admin_url( 'tools.php?page=lc-province-backfill' ) );
	exit;
}
add_action( 'admin_init', 'lc_handle_province_backfill_request' );

/**
 * Render Tools UI.
 */
function lc_render_province_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = get_transient( 'lc_province_backfill_notice_' . get_current_user_id() );
	if ( $notice ) {
		delete_transient( 'lc_province_backfill_notice_' . get_current_user_id() );
	}

	$assigned = (int) $GLOBALS['wpdb']->get_var(
		"SELECT COUNT(DISTINCT post_id) FROM {$GLOBALS['wpdb']->postmeta}
		 WHERE meta_key = 'province' AND meta_value <> ''"
	);
	?>
	<div class="wrap">
		<h1>Learn Cabana — Group Province Backfill</h1>
		<p>This tool assigns <strong>Group-level</strong> province meta from unambiguous group titles/codes. It never writes user meta.</p>
		<p><strong>Automatic backfill is disabled.</strong> Nothing runs unless you click a button below.</p>
		<p>Groups currently with province set: <strong><?php echo esc_html( (string) $assigned ); ?></strong></p>

		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					echo esc_html(
						sprintf(
							'%s — updated/inferred: %d, already set: %d, skipped (no unambiguous match, e.g. DM/AM): %d',
							! empty( $notice['dry_run'] ) ? 'Dry run' : 'Backfill complete',
							(int) $notice['updated'],
							(int) $notice['unchanged'],
							(int) $notice['skipped']
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'lc_province_backfill_action', 'lc_province_backfill_nonce' ); ?>
			<p>
				<button type="submit" class="button" name="lc_province_backfill_action" value="dry_run">Dry run (no writes)</button>
				<button type="submit" class="button button-primary" name="lc_province_backfill_action" value="run" onclick="return confirm('Write province meta to matching LearnDash groups on THIS site only?');">Run backfill</button>
			</p>
		</form>

		<p>CLI alternative (local): <code>wp eval 'print_r( lc_run_province_backfill(false) );'</code></p>
	</div>
	<?php
}

/**
 * Legacy function name kept for scripts; no longer hooked to admin_init auto-run.
 *
 * @param bool $force Ignored except for API compatibility; always runs when called.
 */
function lc_maybe_backfill_group_provinces( $force = false ) {
	return lc_run_province_backfill( false );
}
