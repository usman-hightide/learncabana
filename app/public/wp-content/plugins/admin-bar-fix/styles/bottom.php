html #wpadminbar{
	top: auto;
	bottom: 0;
	opacity: <?php echo intval( $this->settings['inactive_opacity'] ) / 100 ?>;
	transform: translateY(<?php echo 32 - intval( $this->settings['inactive_size'] ) ?>px);
	transition: all <?php echo intval( $this->settings['animation_duration'] ) ?>ms ease;
	transition-property: transform, opacity;
	transition-delay: <?php echo intval( $this->settings['mouseleave_delay'] ) ?>ms;
}
html #wpadminbar:hover{
	transform: translateY(0px);
	transition-delay: <?php echo intval( $this->settings['mouseenter_delay'] ) ?>ms;
}
html #wpadminbar .ab-top-menu > .menupop > .ab-sub-wrapper{
	top: auto;
	bottom: 100%;
}
#wpadminbar .menupop li.hover > .ab-sub-wrapper,
#wpadminbar .menupop li:hover > .ab-sub-wrapper{
	bottom: 0;
	margin-top: auto;
	margin-bottom: -6px;
}