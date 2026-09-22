<?php

namespace StellarWP\LearnDashCloud\Modules;

use StellarWP\PluginFramework\Modules\SupportUsers as FrameworkSupportUsers;

class SupportUsers extends FrameworkSupportUsers
{
    /**
     * Retrieve default arguments for creating new support users.
     *
     * @return Array<string,scalar>
     */
    protected function getDefaultUserAttributes()
    {
        $uniqid = uniqid();

        return [
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'description'   => 'This is a temporary user created by LearnDash support, and will automatically be removed after 24 hours of inactivity.',
            'display_name'  => 'LearnDash Support',
            'first_name'    => 'LearnDash',
            'last_name'     => 'Support',
            'nickname'      => 'LearnDash Support',
            'use_ssl'       => true,
            'user_email'    => sprintf('nwblackhole+%s@mylearndash.com', $uniqid),
            'user_login'    => 'learndash_support_' . $uniqid,
            'user_url'      => 'https://www.learndash.com',
        ];
    }
}
