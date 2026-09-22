html #wpadminbar{
	opacity: <?php echo intval( $this->settings['inactive_opacity'] ) / 100 ?>;
	width: auto;
	max-width: <?php echo intval( $this->settings['inactive_size'] ) ?>px;
	min-width: 0;
	height: auto;
	transition: all <?php echo intval( $this->settings['animation_duration'] ) ?>ms ease;
	transition-property: max-width, opacity;
	transition-delay: <?php echo intval( $this->settings['mouseleave_delay'] ) ?>ms;
	<?php if( $this->settings['adminbar_position'] == 'center' ): ?>
		top: 50%;
		transform: translateY(-50%);
	<?php endif ?>
}
html #wpadminbar:hover{
	max-width: 100%;
	transition-delay: <?php echo intval( $this->settings['mouseenter_delay'] ) ?>ms;
}
html #wp-toolbar,
html #wp-toolbar > ul{
	width: 100%;
	heigh: auto;
	flex-direction: column;
}
html #wpadminbar .ab-top-menu > .menupop > .ab-sub-wrapper{
	top: -1px;
	left: 100%;
	margin-left: -1px;
}
html #wp-admin-bar-user-info .avatar{
	position: static;
}
html #wpadminbar #wp-admin-bar-my-account.with-avatar #wp-admin-bar-user-actions > li{
	margin-left: 0;
}