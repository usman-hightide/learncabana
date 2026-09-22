<?php
$status       = $module['status'];
$status_text  = __( 'Inactive', 'ub' );
$module_class = '';
$module_check = ! empty( $module['only_pro'] );
if ( $module_check ) {
	$status       = 'inactive';
	$module_class = 'branda-module-for-pro';
} elseif ( 'active' === $status ) {
	$status_text = __( 'Active', 'ub' );
}
$url = add_query_arg(
	array(
		'page'   => sprintf( 'branding_group_%s', $module['group'] ),
		'module' => $module['module'],
	),
	is_network_admin() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php' )
);
?>
<tr data-id="<?php echo esc_attr( $module['module'] ); ?>">
	<td class="sui-table--name sui-table-item-title">
		<?php if ( ! $module_check ) { ?>
			<?php echo esc_attr( $module['name'] ); ?>
		<?php } else { ?>
			<span class="<?php echo $module_class; ?>">
				<?php echo esc_attr( $module['name'] ); ?>
			</span>
			&nbsp;
			<?php echo Branda_Helper::maybe_pro_tag(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php } ?>
	</td>
	<td class="sui-table--status <?php echo $module_class; ?>">
		<div class="branda-status-elements">
			<?php if ( 'subsite' !== $mode ) { ?>
						<span class="branda-module-status sui-tooltip module-status-<?php echo esc_attr( $status ); ?>" data-tooltip="<?php echo esc_attr( $status_text ); ?>"></span>
			<?php } ?>
			<a href="<?php echo esc_url( $url ); ?>" class="sui-button-icon sui-tooltip sui-tooltip-top-right-mobile" data-tooltip="<?php esc_attr_e( 'Edit Module', 'ub' ); ?>">
			<i class="sui-icon-pencil" aria-hidden="true"></i>
			</a>
		</div>
	</td>
</tr>

