<?php

add_action( 'rest_api_init', function () {
  register_rest_route( 'grassblade/v1', '/courses', array(
    'methods' => 'GET',
    'callback' => 'grassblade_get_course_structure_rest_api',
    'permission_callback' => function () {
      return current_user_can( 'connect_grassblade_lrs' ) ||  current_user_can( 'manage_options' );
    }
  ) );
} );