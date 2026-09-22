<?php
/**
 * Custom Tin Canny groups dropdown — Group + Province + DM + AM + Job Titles.
 * Loaded via tinccanny_get_part_path (MU-plugin), not by editing Tin Canny core.
 *
 * @package LearnCabana
 */

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( empty( self::$groups_query ) || 1 === count( self::$groups_query ) ) {
	return;
}

$job_titles = function_exists( 'get_field_object' ) ? get_field_object( 'field_64e526dc93b03' ) : null;
$provinces  = function_exists( 'lc_get_province_choices' ) ? lc_get_province_choices() : array();
$selected_province = function_exists( 'lc_get_requested_province' ) ? lc_get_requested_province() : '';

$district_managers = function_exists( 'lc_get_managers_for_dropdown' ) ? lc_get_managers_for_dropdown( 'District Manager' ) : array();
$selected_dm       = function_exists( 'lc_get_requested_district_manager_id' ) ? lc_get_requested_district_manager_id() : 0;
$area_managers     = function_exists( 'lc_get_area_managers_for_district' )
	? lc_get_area_managers_for_district( $selected_dm )
	: ( function_exists( 'lc_get_managers_for_dropdown' ) ? lc_get_managers_for_dropdown( 'Area Manager' ) : array() );
$selected_am       = function_exists( 'lc_get_requested_area_manager_id' ) ? lc_get_requested_area_manager_id() : 0;

$current_tab = function_exists( 'ultc_get_filter_var' ) ? ultc_get_filter_var( 'tab', 'courseReportTab' ) : 'courseReportTab';
$show_org_filters = ( 'userReportTab' === $current_tab );

// Job title selected state (existing Tin Canny/custom behavior).
$selected_job_title = '';
if ( function_exists( 'ultc_filter_has_var' ) && ultc_filter_has_var( 'job_titles' ) ) {
	$selected_job_title = ultc_filter_input( 'job_titles' );
} elseif ( isset( self::$isolated_job_title ) ) {
	$selected_job_title = self::$isolated_job_title;
}
?>
<div class="reporting-group-selector" id="reporting-group-selector-container">
	<form method="GET" class="reporting-group-selector__form lc-filter-form--stacked">
		<?php if ( is_admin() ) { ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( htmlspecialchars( ultc_get_filter_var( 'page', '' ) ) ); ?>">
		<?php } ?>

		<input id="reporting-group-selector-tab" type="hidden" name="tab" value="<?php echo esc_attr( htmlspecialchars( $current_tab ) ); ?>">

		<div class="lc-filter-field">
			<div class="reporting-group-selector__label-container">
				<label for="reporting-group-selector">
					<?php esc_html_e( 'Group', 'uncanny-learndash-reporting' ); ?>
				</label>
			</div>
			<div class="reporting-group-selector__select-container">
				<select name="group_id" id="reporting-group-selector" class="reporting-group-selector__select">
					<option value="all"><?php esc_html_e( 'All Users', 'uncanny-learndash-reporting' ); ?></option>
					<?php foreach ( self::$groups_query as $group ) { ?>
						<option
							<?php
							if ( $group->ID === self::$isolated_group ) {
								echo 'selected="selected"';
							}
							?>
							value="<?php echo esc_attr( $group->ID ); ?>"><?php echo esc_html( $group->post_title ); ?></option>
					<?php } ?>
				</select>
			</div>
		</div>

		<?php /* Province / DM / AM — User Report only. */ ?>
		<div class="lc-filter-field lc-org-filter-item lc-province-filter-item"<?php echo $show_org_filters ? '' : ' style="display:none;"'; ?>>
			<div class="reporting-group-selector__label-container">
				<label for="reporting-province-selector"><?php esc_html_e( 'Province', 'uncanny-learndash-reporting' ); ?></label>
			</div>
			<div class="reporting-group-selector__select-container lc-org-select-wrap lc-province-select-wrap">
				<select
					name="province"
					id="reporting-province-selector"
					class="reporting-group-selector__select"
					<?php disabled( ! $show_org_filters ); ?>
				>
					<option value="all"><?php esc_html_e( 'All Provinces', 'uncanny-learndash-reporting' ); ?></option>
					<?php foreach ( $provinces as $province_code => $province_label ) { ?>
						<option
							<?php selected( $province_code, $selected_province ); ?>
							value="<?php echo esc_attr( $province_code ); ?>"
						>
							<?php echo esc_html( $province_label ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>

		<div class="lc-filter-field lc-org-filter-item"<?php echo $show_org_filters ? '' : ' style="display:none;"'; ?>>
			<div class="reporting-group-selector__label-container">
				<label for="reporting-district-manager-selector"><?php esc_html_e( 'District Manager', 'uncanny-learndash-reporting' ); ?></label>
			</div>
			<div class="reporting-group-selector__select-container lc-org-select-wrap">
				<select
					name="district_manager"
					id="reporting-district-manager-selector"
					class="reporting-group-selector__select"
					<?php disabled( ! $show_org_filters ); ?>
				>
					<option value="all"><?php esc_html_e( 'All District Managers', 'uncanny-learndash-reporting' ); ?></option>
					<?php foreach ( $district_managers as $dm ) { ?>
						<option
							<?php selected( (int) $dm['id'], $selected_dm ); ?>
							value="<?php echo esc_attr( $dm['id'] ); ?>"
						>
							<?php echo esc_html( $dm['name'] ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>

		<div class="lc-filter-field lc-org-filter-item"<?php echo $show_org_filters ? '' : ' style="display:none;"'; ?>>
			<div class="reporting-group-selector__label-container">
				<label for="reporting-area-manager-selector"><?php esc_html_e( 'Area Manager', 'uncanny-learndash-reporting' ); ?></label>
			</div>
			<div class="reporting-group-selector__select-container lc-org-select-wrap">
				<select
					name="area_manager"
					id="reporting-area-manager-selector"
					class="reporting-group-selector__select"
					<?php disabled( ! $show_org_filters ); ?>
				>
					<option value="all"><?php esc_html_e( 'All Area Managers', 'uncanny-learndash-reporting' ); ?></option>
					<?php foreach ( $area_managers as $am ) { ?>
						<option
							<?php selected( (int) $am['id'], $selected_am ); ?>
							value="<?php echo esc_attr( $am['id'] ); ?>"
						>
							<?php echo esc_html( $am['name'] ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>

		<?php if ( ! empty( $job_titles['choices'] ) && is_array( $job_titles['choices'] ) ) { ?>
			<div class="lc-filter-field">
				<div class="reporting-job_title-selector__label-container">
					<label for="reporting-job_title-selector">
						<?php esc_html_e( 'Job Titles', 'uncanny-learndash-reporting' ); ?>
					</label>
				</div>
				<div class="reporting-job_title-selector__select-container">
					<select name="job_titles" id="reporting-job_title-selector" class="reporting-job_title-selector__select">
						<option value="all"><?php esc_html_e( 'All', 'uncanny-learndash-reporting' ); ?></option>
						<?php foreach ( $job_titles['choices'] as $job_title ) { ?>
							<option
								<?php
								if ( $job_title === $selected_job_title ) {
									echo 'selected="selected"';
								}
								?>
								value="<?php echo esc_attr( $job_title ); ?>"
							>
								<?php echo esc_html( $job_title ); ?>
							</option>
						<?php } ?>
					</select>
				</div>
			</div>
		<?php } ?>

		<div class="reporting-group-selector__submit-container lc-filter-submit">
			<input value="<?php esc_attr( esc_html_e( 'Filter', 'uncanny-learndash-reporting' ) ); ?>" type="submit" id="reporting-group-selector__submit">
		</div>
	</form>
</div>
