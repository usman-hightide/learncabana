<?php
namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! empty( self::$groups_query ) && 1 !== count( self::$groups_query ) ) { 
	$job_titles = get_field_object('field_64e526dc93b03'); ?>
	<div class="reporting-group-selector" id="reporting-group-selector-container">
		<form method="GET" class="reporting-group-selector__form">
			<?php if ( is_admin() ) { ?>
				<input type="hidden" name="page" value="<?php echo esc_attr( htmlspecialchars( ultc_get_filter_var( 'page', '' ) ) ); ?>">
			<?php } ?>

			<input id="reporting-group-selector-tab" type="hidden" name="tab" value="<?php echo esc_attr( htmlspecialchars( ultc_get_filter_var( 'tab', 'courseReportTab' ) ) ); ?>">

			<div class="reporting-group-selector__label-container">
				<label for="reporting-group-selector">
					<?php esc_html_e( 'Group', 'uncanny-learndash-reporting' ); ?>
				</label>
			</div>
			<div class="reporting-group-selector__select-container">
				<select name="group_id" id="reporting-group-selector" class="reporting-group-selector__select">
					<option value="all"><?php esc_html_e( 'All Users', 'uncanny-learndash-reporting' ); ?></option>
					<?php
					foreach ( self::$groups_query as $group ) {
						?>
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
							if ( $job_title === self::$isolated_job_title ) {
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
			<div class="reporting-group-selector__submit-container">
				<input value="<?php esc_attr( esc_html_e( 'Filter', 'uncanny-learndash-reporting' ) ); ?>" type="submit" id="reporting-group-selector__submit">
			</div>
		</form>
	</div>
	<?php
}
?>
