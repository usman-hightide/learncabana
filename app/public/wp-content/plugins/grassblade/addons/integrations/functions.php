<?php

class gb_integrations
{
    function __construct()
    {
        add_action("init", array($this, "init"), 20);
    }
    function init() {
        include_once(dirname(__FILE__, 2) . '/vendor/RationalOptionPages/RationalOptionPages.php');
        $pages = array(
            'gb-integrations'    => array(
                'id' => 'gb-integrations-page',
                'parent_slug'    => 'grassblade-lrs-settings',
                'page_title'    => __('Manage Integrations', 'grassblade'),
                'sections'        => array(
                    // 'section-groups'    => array(
                    //     'title'            => __('Manage Groups', 'grassblade'),
                    //     'fields'        => array(
                    //         'gb_pmpro_group'        => array(
                    //             'id'            => 'gb_pmpro_group',
                    //             'title'            => __('Paid Membership Pro', 'grassblade'),
                    //             'type'            => 'checkbox',
                    //         ),
                    //     ),
                    // ),
                ),
            ),
        );
        $pages['gb-integrations']["sections"] = apply_filters("grassblade/integrations/settings", $pages['gb-integrations']["sections"]);
        //$option_page = new grassblade\vendor\RationalOptionPages($pages);
        $option_page = new grassblade\vendor\RationalOptionPages($pages);
    }
    static function is_enabled($key, $default = true) {
        global $grassblade;
        if( empty( $grassblade["integrations_enabled"] ) ) {
            $grassblade["integrations_enabled"] = get_option("gb-integrations");
        }
        if( empty($grassblade["integrations_enabled"]) || !is_array($grassblade["integrations_enabled"]) || !isset($grassblade["integrations_enabled"][$key]) )
            return $default;

        return ($grassblade["integrations_enabled"][$key] == "on");
    }
}

$grassblade_integrations = new gb_integrations();
