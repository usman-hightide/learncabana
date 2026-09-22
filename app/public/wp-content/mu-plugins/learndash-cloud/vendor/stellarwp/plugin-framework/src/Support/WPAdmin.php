<?php

namespace StellarWP\PluginFramework\Support;

/**
 * Helpers for dealing with elements within WP Admin.
 */
class WPAdmin
{
    /**
     * Create an inline form that will trigger an action.
     *
     * On the front-end of the site, the POST request will be directed to wp-admin/admin-post.php;
     * while within WP-Admin, it will post to the current page.
     *
     * Relevant actions:
     *
     * - admin_action_{action} (WP-Admin, authenticated user)
     * - admin_action_nopriv_{action} (WP-Admin, unauthenticated user)
     * - admin_post_{action} (Front-end, authenticated user)
     * - admin_post_nopriv_{action} (Front-end, unauthenticated user)
     *
     * @param string $action  The action name.
     * @param string $label   The text for the button.
     * @param string $classes Optional. Additional CSS classes to apply to the button, separated by
     *                        spaces. Default is empty.
     * @param string $id      Optional. ID for the nonce field. This may be needed to avoid conflicts
     *                        with core WP nonce fields (e.g. in user profile field).
     *
     * @return string Markup for an inline <form> element.
     */
    public static function adminPostButton($action, $label, $classes = '', $id = '')
    {
        $template = <<<'EOT'
<form method="POST" action="" class="stellarwp-admin-bar-inline-form">
    <button name="stellarwpAction" type="submit" value="%1$s" class="%2$s">%3$s</button>
    %4$s
</form>
EOT;
        return sprintf(
            $template,
            esc_attr($action),
            esc_attr($classes),
            esc_html($label),
            wp_nonce_field($action, ! empty($id) ? $id : '_wpnonce', ! is_admin(), false)
        );
    }
}
