html #wpadminbar{
	opacity: <?php echo intval( $this->settings['inactive_opacity'] ) / 100 ?>;
	transform: translateY(<?php echo min( 0, intval( $this->settings['inactive_size'] ) - 32 ) ?>px);
	transition: all <?php echo intval( $this->settings['animation_duration'] ) ?>ms ease;
	transition-property: transform, opacity;
	transition-delay: <?php echo intval( $this->settings['mouseleave_delay'] ) ?>ms;
}
html #wpadminbar:hover{
	transform: translateY(0px);
	transition-delay: <?php echo intval( $this->settings['mouseenter_delay'] ) ?>ms;
}