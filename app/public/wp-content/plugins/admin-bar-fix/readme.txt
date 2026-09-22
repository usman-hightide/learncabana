=== Admin Bar Fix ===
Contributors: kubiq
Donate link: https://www.paypal.me/jakubnovaksl
Tags: adminbar, margin, fix, enhancement, adjustment
Requires at least: 3.0.1
Tested up to: 6.9
Stable tag: 2.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fix broken layout when too many items are displayed in the admin bar, remove annoying top margin, hide some unnecessary items from your admin bar

== Description ==

Fix the broken layout when too many items are displayed in your admin bar, remove the annoying top margin, hide some unnecessary items from your admin bar

<ul>
	<li>fix multiline admin bar</li>
	<li>remove 32px margin inserted by WordPress</li>
	<li>hide admin bar for any user role</li>
	<li>hide admin bar on smaller screens</li>
	<li>hide admin bar items that you do not need</li>
	<li>
		select from 3 admin bar styles:
		<ol>
			<li>ghost - set lower opacity and smaller height and expand it on hover</li>
			<li>vertical - show admin bar as icons in a vertical panel and expand it on hover</li>
			<li>icon - hide admin bar into a single small icon and expand it on hover</li>
			<li>bottom - move admin bar to the bottom of your screen, you can also set similar things like in the ghost style</li>
		</ol>
	</li>
	<li>
		settings:
		<ul>
			<li>Position</li>
			<li>Inactive opacity</li>
			<li>Inactive size</li>
			<li>Animation duration</li>
			<li>Mouse enter delay</li>
			<li>Mouse leave delay</li>
		</ul>
	</li>
</ul>

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Manage your settings in 'Settings >> Admin Bar Fix'

== Screenshots ==

1. Plugin settings
2. Fixed multiline admin bar
3. Hidden not needed admin bar items
4. Ghost style
5. Vertical style
6. Icon style
7. Bottom style

== Changelog ==

= 2.5 =
* Tested on WordPress 6.9

= 2.4 =
* Tested on WordPress 6.5

= 2.3 =
* fixed PHP 8 deprecation notice

= 2.2 =
* Tested on WordPress 6.3
* new bottom position

= 2.1 =
* add option to remove `admin-bar` class from body

= 2.0 =
* Tested on WordPress 6.1
* Fix for multisites/network

= 1.0 =
* First version