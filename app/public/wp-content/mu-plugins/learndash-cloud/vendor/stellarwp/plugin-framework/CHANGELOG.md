# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Version 1.18.2]

### Fixed

* Explicitly use WP-CLI to set permalink structure during setup

## [Version 1.18.1]

### Fixed

* Corrected page cache file path after recent Solid Performance update

## [Version 1.18.0]

### Added

* Base class and functionality for admin pages
* Configuration for Solid Performance

### Updated

* * Flush rewrite rules during setup

### Fixed

* Filtering out VC URLS based on SSL and redirects

## [Version 1.17.0]

### Fixed

* Re-enable WP emails on core updates ([#146])

## [Version 1.16.0]

### Fixed

* Conflicting nonce field IDs ([#147])

## [Version 1.15.0]

### Fixed

* Robots blocklist logic to allow for filtering ([#145])

## [Version 1.14.0]

### Added

* Default robots.txt file for temporary domains ([#142])

### Fixed

* Prevent installing OCP drop-in before OCP is configured ([#143])

## [Version 1.13.0]

### Added

* `\Modules\Robots` for handling bots ([#140])
* `wp_die()` handler ([#139])

## [Version 1.12.0]

### Added

* `\Support\WooCommerceMonitor` for collecting additional WooCommerce metrics ([#135])

### Updated

* Write Object Cache Pro config before activating the plugin ([#137])

## [Version 1.11.0]

### Updated

* Additional telemetry data for various plugin counts ([#132])

## [Version 1.10.0]

### Updated

* Added ability to block core WP releases via Feature Flags service ([#130])

## [Version 1.9.1]

### Updated

* Added `theme_name` section to Telemetry report data ([#125])

## [Version 1.9.0]

### Updated

* Limit Visual Comparison to 5 URLs ([#119])
* Added `telemetry_info` section to Telemetry report data ([#120])

## [Version 1.8.0]

### Added

* New-site support for W3 Total Cache ([#103])

## [Version 1.7.2]

### Fixed

* Feature flags should check reomte if no timestamp is available

## [Version 1.7.1]

### Fixed

* Auto updates should only be disabled if explicitly set - should default to true in extensions of Settings

## [Version 1.7.0]

### Added

* `init` method that gets mapped to the WP `init` action hook ([#98])

### Fixed

* Excessive calls to feature flag service ([#95])

## [Version 1.6.1]

### Fixed

* Use PHP `rand()` to avoid issues seen with the pluggable WP function `wp_rand()` ([#91])

## [Version 1.6.0]

### Added

* Code migrations from NXMU ([#76])

## [Version 1.5.0]

### Added

* Support for Feature Flags ([#85])

### Fixed

* Correctly set the `WP_REDIS_DISABLED` constant for Object Cache Pro ([#83])

## [Version 1.4.0]

### Added

* Adding support for Object Cache Pro ([#58])

## [Version 1.3.0]

### Added

* Create a Service and Module for recording telemetry data about the hosted site for platform improvements. ([#57]) 

### Updated

* Coding Standards and Static Analysis improvements ([#57])

## [Version 1.2.0]

### Added

* Provide documentation for the various testing tools within the framework ([#54])

### Fixed

* Use case-insensitive compare when determining if site is on a custom domain ([#55])

## [Version 1.1.0]

### Added

* Add the `enableProvisioningLogs()` command to the abstract Setup command ([#42])
* Add the `refresh()` method to the ProvidesSettings contract ([#48])
* Add a shell script for comparing plugin versions ([#49])
* Automatically create a draft release when a release branch is merged into main ([#51])
* Automatically rebuild the `dist/` directory when preparing a release ([#52])

### Fixed

* Don't put equals signs between arguments when invoking commands ([#50])

## [Version 1.0.1]

### Fixed

* Don't attempt to retrieve setup instructions from the StellarWP Partner Gateway without a valid ID ([#45])
* Log the attempted URL if setup instruction requests fail ([#44])

## [Version 1.0.0]

Initial release of the framework, including the following modules:

* AutoLogin
* ExtensionConfig
* GoLiveWidget
* Maintenance
* PurgeCaches
* SupportUsers

[Unreleased]: https://github.com/stellarwp/plugin-framework/compare/main...develop
[Version 1.0.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.0.0
[Version 1.0.1]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.0.1
[Version 1.1.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.1.0
[Version 1.2.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.2.0
[Version 1.3.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.3.0
[Version 1.4.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.4.0
[Version 1.5.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.5.0
[Version 1.6.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.6.0
[Version 1.6.1]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.6.1
[Version 1.7.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.7.0
[Version 1.7.1]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.7.1
[Version 1.7.2]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.7.2
[Version 1.8.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.8.0
[Version 1.9.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.9.0
[Version 1.9.1]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.9.1
[Version 1.10.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.10.0
[Version 1.11.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.11.0
[Version 1.12.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.12.0
[Version 1.13.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.13.0
[Version 1.14.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.14.0
[Version 1.15.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.15.0
[Version 1.16.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.16.0
[Version 1.17.0]: https://github.com/stellarwp/plugin-framework/releases/tag/v1.17.0
[#42]: https://github.com/stellarwp/plugin-framework/pull/42
[#44]: https://github.com/stellarwp/plugin-framework/pull/44
[#45]: https://github.com/stellarwp/plugin-framework/pull/45
[#48]: https://github.com/stellarwp/plugin-framework/pull/48
[#49]: https://github.com/stellarwp/plugin-framework/pull/49
[#50]: https://github.com/stellarwp/plugin-framework/pull/50
[#51]: https://github.com/stellarwp/plugin-framework/pull/51
[#52]: https://github.com/stellarwp/plugin-framework/pull/52
[#54]: https://github.com/stellarwp/plugin-framework/pull/54
[#55]: https://github.com/stellarwp/plugin-framework/pull/55
[#57]: https://github.com/stellarwp/plugin-framework/pull/57
[#58]: https://github.com/stellarwp/plugin-framework/pull/58
[#76]: https://github.com/stellarwp/plugin-framework/pull/76
[#83]: https://github.com/stellarwp/plugin-framework/pull/83
[#85]: https://github.com/stellarwp/plugin-framework/pull/85
[#91]: https://github.com/stellarwp/plugin-framework/pull/91
[#95]: https://github.com/stellarwp/plugin-framework/pull/95
[#98]: https://github.com/stellarwp/plugin-framework/pull/98
[#103]: https://github.com/stellarwp/plugin-framework/pull/103
[#119]: https://github.com/stellarwp/plugin-framework/pull/119
[#120]: https://github.com/stellarwp/plugin-framework/pull/120
[#125]: https://github.com/stellarwp/plugin-framework/pull/125
[#130]: https://github.com/stellarwp/plugin-framework/pull/130
[#132]: https://github.com/stellarwp/plugin-framework/pull/132
[#135]: https://github.com/stellarwp/plugin-framework/pull/135
[#137]: https://github.com/stellarwp/plugin-framework/pull/137
[#139]: https://github.com/stellarwp/plugin-framework/pull/139
[#140]: https://github.com/stellarwp/plugin-framework/pull/140
[#142]: https://github.com/stellarwp/plugin-framework/pull/142
[#143]: https://github.com/stellarwp/plugin-framework/pull/143
[#145]: https://github.com/stellarwp/plugin-framework/pull/145
[#146]: https://github.com/stellarwp/plugin-framework/pull/146
[#147]: https://github.com/stellarwp/plugin-framework/pull/147
