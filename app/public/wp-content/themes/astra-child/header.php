<?php
/**
 * The header for Astra Theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php 
if ( apply_filters( 'astra_header_profile_gmpg_link', true ) ) {
	?>
	 <link rel="profile" href="https://gmpg.org/xfn/11"> 
	 <?php
} 
?>
<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
<style type="text/css">
	
	
	.select-control, .item-title, .item-title h2, tbody .course-list-table-data-row td, .note, #usersOverviewTable, .reporting-dashboard-quick-stats__number, #coursesOverviewTable, .wisdm-learndash-reports-course-completion-table, .learndash-wrapper,  .learndash-wrapper h1{ color: #000; }
	#usersOverviewTable .reporting-table-see-details:hover,
	#coursesOverviewTable .reporting-table-see-details:hover,
	.dataTable td a,
	.custom-reports td a
	{
		    border: 0;
    		color: #0290c2 !important;
	}
	body.single-sfwd-lessons h1, .ld-focus-content h1,
	.learndash-wrapper .ld-focus .ld-focus-main .ld-focus-content h1{
		color: #808285;
	}
	.ld-focus-sidebar,
	.ld-topic-title
	.entry-content h1 {
       color: #808285 !important;            
	}
	.has-black-color a{
		color: #000 !important;
	}
</style>
<script type="text/javascript">
	console.log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
</script>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>

<a
	class="skip-link screen-reader-text"
	href="#content"
	role="link"
	title="<?php echo esc_attr( astra_default_strings( 'string-header-skip-link', false ) ); ?>">
		<?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>
</a>

<div
<?php
	echo astra_attr(
		'site',
		array(
			'id'    => 'page',
			'class' => 'hfeed site',
		)
	);
	?>
>
	<?php
	astra_header_before();

	astra_header();

	astra_header_after();

	astra_content_before();
	?>
	<div id="content" class="site-content">
		<div class="ast-container">
		<?php astra_content_top(); ?>
