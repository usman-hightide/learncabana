=== Hide Admin Bar Based on User Roles ===
Contributors: ankitmaru, siapanchal
Tags: admin bar, hide admin bar, toolbar, user roles, admin toolbar
Plugin URI: https://pluginstack.dev/plugins/hide-admin-bar-pro
Author: PluginStackDev
Author URI: https://pluginstack.dev
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 5.6
Stable tag: 7.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hide the WordPress Admin Bar for specific user roles, capabilities, devices, pages, or time windows. The ultimate toolbar control plugin for membership sites, WooCommerce stores, and clean frontend experiences.

== Description ==

**Hide Admin Bar Based On User Roles** gives you complete control over who sees the WordPress toolbar.

Whether you are running a membership site, a WooCommerce store, or simply want a cleaner frontend for your subscribers, this plugin lets you hide the admin bar with precision — by role, capability, device, page, or time. Stop exposing backend links to users who don't need them.

The plugin is lightweight, developer-friendly, and works immediately upon activation — no configuration required to get started.

https://www.youtube.com/watch?v=25WBldgArAk

https://www.youtube.com/watch?v=_BAwxGVnKNY

> Simple but great plugin. 🙂  
> - [wptoolsdev](https://wordpress.org/support/topic/simple-but-great-plugin-12/)

> Works flawlessly! 🙂  
> - [thebrazeneye](https://wordpress.org/support/topic/works-flawlessly-129/)

### 🚀 Key Features (Free)
* **Hide for All Users:** Completely remove the admin bar from the frontend for everyone.
* **Hide for Guests:** Ensure non-logged-in visitors never see the toolbar.
* **Role-Based Hiding:** Select specific roles (e.g., Subscriber, Customer, Editor) to hide the bar for.
* **Capability-Based Hiding:** Hide the bar based on WordPress capabilities (e.g., hide for anyone who cannot `manage_options`).
* **Lightweight & Fast:** Zero bloat — no external requests, no database overhead on the frontend.

### 🏆 Premium Features (Pro)
Unlock advanced visibility logic with the [Pro version](https://pluginstack.dev/plugins/hide-admin-bar-pro):
* **Page-Based Targeting:** Show or hide the admin bar only on specific URLs, post types, or page templates.
* **Device Detection:** Hide the toolbar on Mobile or Tablet to save screen space, while keeping it on Desktop.
* **Per-User Overrides:** Manually force the admin bar to show or hide for individual user accounts.
* **Time-Based Visibility:** Automatically hide the bar during specific hours of the day.
* **Smart Redirects:** Redirect users to the homepage or a custom URL when they try to access the backend.
* **Inactivity Auto-Hide:** Automatically slide the toolbar away after a configurable period of inactivity.
* **Import / Export Settings:** Back up and migrate your configuration across sites in one click.

== You can check our other plugins: ==
<ol>
<li><a href="https://wordpress.org/plugins/ultimakit-for-wp/">All-in-One WordPress Toolkit for SEO, Security, Customization, and Performance</a></li>
<li><a href="https://wordpress.org/plugins/like-dislike-for-wp/">Like Dislike For WP</a></li>
<li><a href="https://wordpress.org/plugins/disable-block-editor-fullscreen-mode/">Disable Block Editor FullScreen mode</a></li>
<li><a href="https://wordpress.org/plugins/noteflow/">NoteFlow – Smart Notes Manager for WordPress Admin</a></li>
</ol>

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hide-admin-bar-based-on-user-roles` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the **Settings->Hide Admin Bar** screen to configure your visibility rules.

**OR**  
Install it via Plugins → Add New → Search: *Hide Admin Bar Based on User Roles*

== Frequently Asked Questions ==

= Does this plugin affect Administrators? =
By default, no. You have full control. You can choose to hide it for Administrators if you wish, but most users keep it visible for admins and hidden for everyone else.

= Can I hide the admin bar for WooCommerce customers only? =
Yes. Go to Settings → Hide Admin Bar and check the "Customer" user role. It works with any custom role registered by WooCommerce or other plugins.

= Will this plugin slow down my site? =
No. The visibility logic runs at the `show_admin_bar` filter level — it is one of the earliest and lightest hooks in WordPress. There is no frontend CSS or JavaScript loaded.

= Is this plugin compatible with other themes? =
Yes. It follows standard WordPress coding practices and works with all major themes and page builders including Elementor, Divi, Beaver Builder, and Bricks.

= Can I hide the admin bar on specific pages only? (Pro) =
Yes — with the Pro version you can target specific URLs, post types, or page templates for granular page-level control.

= Does it work on multisite? =
Yes. The plugin supports WordPress Multisite networks.

== Screenshots ==

1. Hide admin bar for all users
2. Hide by roles or capabilities

== Changelog ==

= 7.2.4 =
* Fixed: Minor compatibility issues with the latest WordPress version.

= 7.2.3 =
* Fixed: Minor compatibility issues with the latest WordPress version.

= 7.2.1 =
* Fixed: Minor compatibility issues with the latest WordPress version.

= 7.2.0 =
* Improved: Admin settings UI refresh for better usability.
* Fixed: Minor compatibility issues with the latest WordPress version.

= 7.1.0 =
* Improved: Admin settings UI refresh for better usability.
* Improved: Code quality and inline documentation.
* Fixed: Minor compatibility issues with the latest WordPress version.

= 7.0.3 - 31-01-2026 =
* Fixed: Minor bugs and stability improvements.

= 7.0.1 - 25-11-2025 =
* Fixed: Minor bugs and stability improvements.

= 6.0.3 - 03-07-2025 =
Fixed some bugs and improvements.

= 6.0.2 - 03-06-2025 =
Fixed some bugs and improvements.

= 6.0.0 - 20-04-2025 =
* Major release: Pro version launched (optional, upgrade via Freemius)
* Added: Auto Hide Admin Bar (Pro)
* Added: Page-based, device-based, and time-based visibility modules (Pro)
* Added: Per-user override, redirect on hide, import/export
* Improved: Settings layout and compatibility with other plugins
* Refactored: Modular architecture for clean feature handling

= 5.2.0 - 02-04-2025 =
* I18N Issues Fixes
* Compatibility tested & Tested with the latest WordPress version.

= 5.0.0 - 16-02-2025 =
* Admin ui updated.
* Compatibility tested & Tested with the latest WordPress version.

= 4.1.0 - 28-12-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 4.0.0 - 09-11-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.9.1 - 30-10-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.9.0 - 25-09-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.8.3 - 13-08-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.8.2 - 01-07-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.8.1 - 24-05-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.8.0 - 15-05-2024 =
Compatibility tested & Tested with the latest WordPress version & UltimaKit For WP support added.

= 3.7.2 - 03-03-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.7.1 - 06-02-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.7.0 - 01-01-2024 =
Compatibility tested & Tested with the latest WordPress version.

= 3.6.2 - 09-11-2023 =
Compatibility tested & Tested with the latest WordPress version.

= 3.6.1 - 01-10-2023 =
Compatibility tested & Tested with the latest WordPress version.

= 3.6.0 - 02-08-2023 =
Compatibility tested & Tested with the latest WordPress version.

= 3.5.6 =
Compatibility tested & Tested with the latest WordPress version.

= 3.5.5 =
Compatibility tested & Tested with the latest WordPress version.

= 3.5.4 =
Compatibility tested & Tested with the latest WordPress version.

= 3.5.3 =
Compatibility tested & Tested with the latest WordPress version.

= 3.5.2 =
Security Fixes and Improvements.

= 3.5.1 =
Security Fixes and Improvements.

= 3.5.0 =
Security Fixes and Improvements.

= 3.4.1 =
Security Fixes and Improvements.

= 3.4.0 =
Security Fixes and Improvements and multisite support reverted.

= 3.3.0 =
Security Fixes and Improvements and multisite support added.

= 3.2.0 =
Security Fixes and Improvements.

= 3.1.0 =
Security Fixes and Improvements.

= 3.0.0 =
Security Fixes and Improvements.

= 2.9.0 =
Improvements & Fixes

= 2.8.0 =
Improvements & Fixes

= 2.7.0 =
Improvements & Fixes

= 2.6.0 =
Improvements

= 2.5.0 =
Improvements

= 2.4.0 =
Improvements

= 2.3.0 =
Improvements

= 2.2.0 =
Improvements

= 2.1.0 =
Improvements

= 2.0.0 =
Bug Fixes & Improvements

= 1.8.0 =
Bug Fixes & Improvements

= 1.7.0 =
Bug Fixes & Improvements

= 1.6.1 =
Bug Fixes & Improvements

= 1.6 =
Bug Fixes & Improvements

= 1.5 =
Bug Fixes & Improvements

= 1.4 =
Code Improvements, GUI Updated, New Feature Integration.

= 1.3 =
Code Improvements, GUI Updated.

= 1.2 =
Fixed some bugs and improvements and new feature added -> Hide admin bar for all guest users.

= 1.1 =
Fixed some bugs and improvements.

= 1.0 =
First release

== Upgrade Notice ==