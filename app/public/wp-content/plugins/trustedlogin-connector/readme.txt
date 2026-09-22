=== TrustedLogin Connector ===
Contributors: trustedlogin
Donate link: https://www.trustedlogin.com
Tags: support, security, login
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The right way to provide support for WordPress customers. Offering secure login and one-time secret sharing.

== Description ==

## Use TrustedLogin to log into your customers’ sites securely and easily

Do you provide support for WordPress websites? TrustedLogin allows you to log into your customers’ sites securely. The TrustedLogin Connector plugin connects your WordPress site to the [TrustedLogin](https://www.trustedlogin.com) service.

### How it works

1. [Create an account on TrustedLogin.com](https://app.trustedlogin.com)
1. Install the TrustedLogin Connector plugin on your WordPress site
1. Integrate the TrustedLogin SDK into your code

Your users will then be able to grant you access to their site and provide you with an Access Key. With this plugin, you can log into their site using the Access Key.

## Start free, no credit card required

TrustedLogin includes a free plan that lets you connect customer sites and start handling support access right away. No credit card, no trial countdown. Upgrade your TrustedLogin account when your team needs more capacity or premium features. [Create your free account at TrustedLogin.com](https://app.trustedlogin.com) and you can have the Connector installed and your first team connected in minutes.

## Share passwords and credentials with encrypted, time-limited links

Stop pasting passwords, API keys, and other short-lived credentials into email and chat, where they sit in archives forever and get accidentally re-shared. Anyone you've granted the Create Secrets capability (support agents, internal admins, or customers themselves) can generate an encrypted link that expires on a schedule you set, optionally requires a passphrase, and can either burn after the first view or stay reusable until its expiration. Every reveal lands in your audit trail.

### How it works

1. Open the **Secrets** page in your WordPress dashboard
1. Paste the credential, set an optional passphrase and expiration, and choose whether the link should burn after the first view or stay reusable
1. Share the generated link with the other party

The recipient opens the link, sees the credential, and the rules you set take effect. Every reveal, every failed passphrase attempt, and every destruction is logged, so you always know who saw what and when.

## See every support session at a glance

The Login Activity dashboard puts every access event on one screen: a per-agent breakdown with a stacked bar chart, daily or weekly buckets you can scope from one day to twelve months back, and a clickable legend that filters the view to a single teammate. Refused login attempts include the exact reason and the recommended next step right inside the dashboard, so you never have to guess what went wrong.

Administrators see every team. Other capability holders see only the teams they're approved to support. When a support escalation lands and you need to know who touched what, and when, the answer is one click away.

## Add Grant Access to any Gravity Forms form

If you already use Gravity Forms to take support requests, onboarding intake, or sales inquiries, you don't need a separate page to capture site access. Drop the TrustedLogin field into any form. Requesters paste their site URL, click a button, complete the grant flow in a popup, and the access key lands in the form entry alongside the rest of their submission.

The field works in the Gravity Forms editor, on the front-end form, and on the entry-detail page. It honors the form's label placement so it matches the rest of the form's look, and the entry-detail view ships with a one-click login link so agents can open the customer's site directly from the entry.

## Grant access from inside Help Scout, Freescout, or your own helpdesk

When a customer is already in a conversation with your support team, they shouldn't have to leave that conversation to grant access. The Connector ships webhook integrations for [Help Scout](https://www.helpscout.com/) and [Freescout](https://freescout.net/) that show a button right inside the helpdesk thread. The customer clicks, completes the grant, and the access key appears in the agent's inbox without ever leaving the ticket.

Webhooks are HMAC-signed end-to-end and configured through the helpdesk's standard webhook interface. Building your own helpdesk integration? The same webhook surface is available to any provider you wire up.

## Fine-grained capability control without rewriting WordPress roles

Not everyone on your team needs to do everything. The Permissions matrix lets you grant or revoke the four TrustedLogin capabilities (Sign in to client sites, View Activity, Create Secrets, and Manage Secrets) per WordPress role, all from a single admin screen. Your editors might handle access grants but not secret management. Your contractors might view activity but not log in themselves. Every capability change is recorded in the audit log.

The matrix respects WordPress's role system without forcing you to edit it. Granting any TrustedLogin capability to a low-trust role triggers a type-to-confirm modal, so you can't accidentally hand a Subscriber the keys to a customer site.

## Manage support access across multiple teams and products

Whether you support more than one SaaS product, run multiple client engagements, or operate as an agency with several brands, the Connector lets you register multiple teams in a single install. Each team has its own access settings, approved roles, helpdesk integration (or no helpdesk at all), and webhook signing secret.

Login Activity, Permissions, and Secrets all respect team scoping. Capability holders see only the teams they're approved to support. Administrators see everything in one place.

== Frequently Asked Questions ==

### Do I need to have a TrustedLogin account?

Yes, you need to have a TrustedLogin account to use this plugin. You can create an account at [TrustedLogin.com](https://app.trustedlogin.com).

### Is there a free plan?

Yes. TrustedLogin's free plan lets you connect customer sites and handle secure support access without entering payment details. [Sign up at TrustedLogin.com](https://app.trustedlogin.com) and install this plugin to connect your WordPress site. You can upgrade your TrustedLogin account later if your team needs more capacity or premium features.

### Does it require any special configuration?

Yes, you need to have the TrustedLogin SDK integrated into your code. You can find the SDK and instructions on how to integrate it in the [TrustedLogin documentation](https://docs.trustedlogin.com).

### What are the Terms of Service?

By using TrustedLogin, you agree to the [TrustedLogin Terms of Service](https://www.trustedlogin.com/authorized-user-terms/).

### What is the Privacy Policy?

By using TrustedLogin, you agree to the [TrustedLogin Privacy Policy](https://www.trustedlogin.com/privacy-policy/).

== Installation ==

1. Upload this plugin to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Login Activity inside WordPress: every access attempt at a glance, broken out per agent across daily time buckets with a clickable legend.
2. Sites dashboard at TrustedLogin.com: every customer site your team can support, with connection status, expiry, and one-click "Log in" for each.
3. Site detail: the access key, expiration, last sync, endpoint URL, and a Revoke button when the session is done.
4. WordPress onboarding: paste your TrustedLogin account credentials, pick which roles can grant support access, and you're live.
5. Access Key Log-In: paste a customer's access key and pick which of their connected sites to land on.
6. Secrets dashboard: every one-time credential your team has shared, with status, view counts, expiration, and a one-click Create Secret button.
7. Permissions matrix: grant or revoke the four TrustedLogin capabilities per WordPress role from a single screen.
8. Help Desk integration: customers grant access from inside Help Scout or Freescout without leaving the ticket thread.

== Changelog ==

= 2.0.1 on May 24, 2026 =

This hotfix restores missing build dependencies and improves the automatic deploy process to prevent the error in the future.

= 2.0.0 on May 13, 2026 =

A major upgrade brings a brand-new Login Activity dashboard with per-agent charts, Secrets for sharing credentials through encrypted links, a Permissions matrix that gives administrators per-role control over TrustedLogin capabilities; a Gravity Forms field that streamlines support flows, and security hardening.

* **Login Activity dashboard:** See all logins by your team, color-coded by agent and understand your support activity at a glance.
* **One-time Secrets:** Share passwords, API keys, and other short-lived credentials through encrypted links. Each secret can be passphrase-protected, auto-destructs after viewing (or after its TTL), and leaves an audit trail the creator can inspect from the new Secrets admin page.
* **Gravity Forms TrustedLogin field:** A dedicated TrustedLogin field in the Gravity Forms editor makes it easy for your users to grant access to customer support during their support request, shaving hours round-trip. Requires Client SDK 1.10.0.
* **Permissions matrix:** A dedicated admin screen to clearly grant or revoke TrustedLogin capabilities.

__Added:__

* Teams can now be configured without any help desk integration.
* A new "Default landing page" setting lets clicking the top-level TrustedLogin menu open Settings, Teams, Secrets, Login Activity, Access Key Log-In, or Permissions directly. New installs default to Access Key Log-In after onboarding completes (Settings before onboarding).

__Improved:__

* TrustedLogin admin pages now hide third-party admin notices (WordPress core update banners, plugin cache-cleared notifications, and similar chatter) for a focused, clutter-free support experience.

__Fixed:__

* Help Scout widget now shows more information when misconfigured, instead of a generic "error" pill.
* Adding a team via the "Add Team" flow now persists the selected Approved Roles instead of dropping them until re-configured.
* If the TrustedLogin service is unreachable, the plugin now surfaces a graceful failure message instead of a PHP fatal error.
* A PHP 8.4 deprecation notice.

__Security hardening:__

* Stored encryption keys are kept at rest using an authenticated cipher derived from your WordPress salts. Existing installs are upgraded transparently on first load.
* The Connector alerts the site administrator when stored encryption material or one-time secrets can no longer be unlocked, for example after a security plugin rotates WordPress salts.
* Outbound requests to TrustedLogin are now signed.
* SaaS responses are verified against a freshness window and a pinned signing key. Signing-key rotations are surfaced through a persistent admin notice and a one-time admin email so they never slip past you.
* Stricter input validation across every public REST endpoint.

__Developer Updates:__

* **Minimum required PHP version is now 7.4.**
* Added additional capabilities in addition to `trustedlogin_access_key_login`: `trustedlogin_view_activity`, `trustedlogin_create_secret`, `trustedlogin_manage_secrets`. Teams admin and Permissions admin pages require `manage_options`.
  * Note: On upgrade, roles listed in a team's `approved_roles` configuration is automatically granted `trustedlogin_access_key_login`.
* v2 request-bound HMAC bearer is the default for SaaS-bound traffic. The `trustedlogin/connector/auth/use-v2-bearer` filter can pin to the legacy static bearer as a rollback knob; the downgrade is logged and recorded in the `tlc_v2_bearer_downgrade_last_seen` site option for operational visibility.
* New REST routes `/wp-json/trustedlogin/v1/permissions` and `/trustedlogin/v1/login-attempts`
* New filters:
  - `trustedlogin/connector/trusted-proxies` is an array of proxy IPs or CIDR ranges whose `X-Forwarded-For` / `CF-Connecting-IP` headers are trusted. Default: empty. See [Running behind a reverse proxy or CDN](https://docs.trustedlogin.com/Connector/running-behind-a-proxy).
  - `trustedlogin/connector/secrets/rate-limit/enabled` returns false to disable per-IP rate limits on the one-time Secrets endpoints (must stay true in production).
  - `trustedlogin/connector/secrets/notify-admin-on-hmac-failure` returns false to suppress the rate-limited admin email on secret HMAC verification failure.
  - `trustedlogin/connector/debug-constant` overrides the effective value of the `TRUSTEDLOGIN_DEBUG` constant at runtime.
  - `trustedlogin/connector/suppress_admin_notices` controls whether third-party admin notices are hidden on TL admin pages (defaults to true). Return false to restore WordPress's default notice chrome.

= 1.2.2 on September 22, 2025 =

* Fixed team verification: Teams are now re-verified when settings are updated
* Improved log file location handling in debug mode
* Added comprehensive logging throughout the team connection verification process

= 1.2.1 on September 8, 2024 =

* Updated the plugin readme to point to the [TrustedLogin Privacy Policy](https://www.trustedlogin.com/privacy-policy/) and [Terms of Service](https://www.trustedlogin.com/authorized-user-terms/).
* Code formatting improvements
* Security improvements

= 1.2 on August 26, 2024 =

- Added support for free trials
- Added a loading indicator when adding, updating, or deleting a team
- Improved handling errors returned from TrustedLogin app
- Fixed inability to connect to a team using the dropdown when there are multiple teams
- Fixed error when creating a file that prevents directory browsing in the log directory

= 1.1.1 on April 30, 2024 =

- Added `index.html` files to log directories to prevent potential browsing
- Deprecated `trustedlogin/vendor/customers/licenses' hook in favor of `trustedlogin/connector/customers/licenses`

= 1.1.0 on April 30, 2024 =

- **Renamed the plugin file to `trustedlogin-connector.php` - this will require you reactivate the plugin after updating!**
- Updated code to better comply with WP Coding Standards
- Fixed error logs being written when the setting is disabled
- Error logs are now deleted when disabling the Debug Logging setting

__Developer Notes:__

- Required PHP version is now 7.2 or higher
- Logging now uses `WP_Filesystem` to write the log files
- Logging now returns boolean values for success/failure and `null` for logging is disabled
- Updated the translation textdomain to `trustedlogin-connector`
- Renamed the Composer package to `trustedlogin/trustedlogin-connector`
- Renamed functions (deprecated functions will be removed in a future release):
  - `trustedlogin_vendor()` to `trustedlogin_connector()`
  - `trusted_login_vendor_prepare_data()` to `trustedlogin_connector_prepare_data()`
  - `trustedlogin_vendor_deactivate()` to `trustedlogin_connector_deactivate()`
- Renamed hooks (deprecated actions will be removed in a future release):
  - `trustedlogin_vendor` to `trustedlogin_connector`
  - `trustedlogin_vendor_settings_saved` to `trustedlogin_connector_settings_saved`
  - `trustedlogin/vendor/encryption/keys-option` to `trustedlogin/connector/encryption/keys-option`
- Removed the following methods, since they are not needed (they are now handled by the JS `AccessKeyForm` component since 0.13.0):
  - `TrustedLoginService::handleMultipleSecretIds()`
  - `TrustedLoginService::maybeRedirectSupport()`

A full list of changes can be found in the [TrustedLogin Connector GitHub repository](https://github.com/trustedlogin/trustedlogin-connector/releases/tag/v1.1.0).

= 1.0.0 on January 26, 2024 =

- Renamed the plugin to TrustedLogin Connector
- Added checks to make sure the Account ID is a number
- Fixed resetting teams not working

= 0.15.1 on September 27, 2023 =

- Disabled autocomplete on the Access Key input field
- Added minimum and maximum length values to the Access Key input, helping prevent invalid Access Key submission
- Fixed PHP warning
- Fixed incorrect method usage when resetting teams (thanks @danieliser)

= 0.15.0 on September 4, 2023 =

- Added support for the FreeScout help desk. Requires installing the [FreeScout TrustedLogin Module](https://github.com/trustedlogin/freescout-module)
- Added support for logging into multiple sites when the same Access Key is used on multiple sites (when a license key is shared)
- Set required length for an Access Key when submitting the form
- Added an error notifying when the Access Key is invalid
- Updated to display the Site Access menu item when the user has a support role
- Delete the log file when Reset All is performed
- Refactored the help desk provider classes

= 0.14.0 on May 25, 2023 =

- Improved experience when there are multiple URLs using the same Access Key: each matching site will be presented as a clickable link
- Implemented an additional check to ensure users attempting login have the necessary roles defined in the plugin settings prior to enabling login
- Added notice that logging is not changeable when the `TRUSTEDLOGIN_DEBUG` constant is defined
- Obfuscated log file location for enhanced security
- Implemented the ability to reset the encryption keys for a site
- Removed AuditLog.php until it's implemented
- Added missing `index.php` files to prevent website crawling
- Added exception handling for `TypeError` and `SodiumException` errors in the encryption class
- Fixed spinner not displaying upon Access Key submission
- Fixed global logging settings not saving
