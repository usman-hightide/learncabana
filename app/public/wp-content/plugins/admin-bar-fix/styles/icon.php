html #wpadminbar{
	opacity: <?php echo intval( $this->settings['inactive_opacity'] ) / 100 ?>;
	width: <?php echo intval( $this->settings['inactive_size'] ) ?>px;
	min-width: 0;
	transition: all <?php echo intval( $this->settings['animation_duration'] ) ?>ms ease;
	transition-property: width, opacity, left;
	transition-delay: <?php echo intval( $this->settings['mouseleave_delay'] ) ?>ms;
	<?php if( $this->settings['adminbar_position'] == 'center' ): ?>
		left: calc( 50% - <?php echo ceil( intval( $this->settings['inactive_size'] ) / 2 ) ?>px );
	<?php elseif( $this->settings['adminbar_position'] == 'end' ): ?>
		left: calc( 100% - <?php echo intval( $this->settings['inactive_size'] ) ?>px );
	<?php endif ?>
}
html #wpadminbar:hover{
	width: 100%;
	transition-delay: <?php echo intval( $this->settings['mouseenter_delay'] ) ?>ms;
	<?php if( $this->settings['adminbar_position'] == 'center' || $this->settings['adminbar_position'] == 'end' ): ?>
		left: 0;
	<?php endif ?>
}
html #wp-toolbar:not(:hover),
html #wp-toolbar:not(:hover) > ul{
	min-width: auto;
}