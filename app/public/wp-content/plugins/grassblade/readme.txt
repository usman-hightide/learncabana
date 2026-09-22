=== GrassBlade xAPI Companion ===
Tags: GrassBlade, Tin Can API, xAPI, Experience API
Requires at least: 4
Tested up to: 6.8.2
Contributors: Pankaj Agrawal

First xAPI Plugin on Wordpress. Host xAPI, SCORM and cmi5 Content on Wordpress, track user data. Supports packages from Articulate, Lectora, DominKnow, iSpring and more.

== Description ==

#### GrassBlade xAPI Companion

Upload and host xAPI, SCORM, cmi5 and HTML5 content built using authoring tools like Articulate, iSpring, DominKnow, Lectora and more. Also host and track H5P content, and videos like YouTube, Vimeo, Wistia, MP4, HLS, etc. Use all these content to track completion, and restrict progress in LMSes like LearnDash LMS, LifterLMS, WP Courseware and LearnPress. You can also tracking events like Enrolled, Unenrolled, Signup, Page Views, Post Edits, etc.

#### Features:
* One Click Content Upload
* Embed xAPI packages in wordpress post, pages, or custom posts.
* SCORM 1.2 and SCORM 2004 support
* cmi5 support
* Advanced Video Tracking
* Add Launch Link to Open in new window or a lightbox
* Statement Viewer
* H5P Integration
* Get and Set State using Shortcodes
* Direct upload from Dropbox
* Secure Tokens

#### Supports xAPI and SCORM Packages:
* Articulate Storyline
* Lectora Inspire
* DominKnow Claro
* iSpring Pro, and more

#### Supports Non-Tin Can Packages:
* Articulate Studio
* Captivate, and more

#### Integrates with LearnDash LMS, LifterLMS, WP Courseware, LearnPress and more.

== Changelog ==

= v6.2.12 =
* Feature: Added support for mov & webm in Advanced Video Tracking
* Fixed: Content showing twice in LearnDash Lesson when Modern UI setting is enabled
* Fixed: Vimeo video with mp4 in url not loading
* Fixed: DASH video not loading
* Fixed: Divi Editor not loading due to Guttenberg assets loading conflict

= v6.2.11 =
* Feature: Added ability to bulk import via Uploaded Folder
* Improvement: SCORM / remove scaled,min,max,raw if it is not number for better xAPI Compliance
* Improvement: Completion Testing Tool: Improved test Multiple Contents with Same Activity ID
* Improvement: Progress Snapshot Report UI/icons
* Improvement: Show message on content/lesson page to admin/editor if xAPI Content is not Published
* Fixed: Achievements Report not showing in LearnDash 2.0+
* Fixed: LearnDash v4.21.4+: Rich Quiz Report doesn't open from LearnDash User Profile page
* other minor changes

= v6.2.10 =
* Fixed: Styling/layout issues in Progress Snapshot Report, Quiz Report and Question Report
* Fixed: Override of xAPI Settings in content page not working for Video and SCORM content

= v6.2.9 =
* Feature: Added Step wise Drill Down Details in Progress Snapshot Report
* Improvement: Upgraded all Gutenberg Blocks to apiVersion 2, allowing additional settings in Blocks from other plugins.
* Other minor improvements and bug fixes

= v6.2.8 =
* Fixed: Mark Complete button not getting hidden on Hide Button condition in LearnDash v4.21.0+

= v6.2.7 =
* Added ability to force completion of LearnDash Lessons, required from Manual Completions for LearnDash v1.9+

= v6.2.6 =
Improvement: Added ability to upload GomoLearning HTML (no tracking) package. Added ability to upload no tracking packages which have html file inside a folder
Fixed: Mark Complete button not getting hidden on Hide Button condition in LearnDash Legacy Theme
Other minor bug fixes

= v6.2.5 =
* Feature: Completion Testing Tool: Added Registrations Test for checking list of registrastions
* Feature: Added ability to launch the content as user by admin, from Completion Testing Tool
* Feature: Added ability to change the resume, by changing current registration to resume as another attempt, from Completion Testing Tool
* PHP 8.3 related fix

= v6.2.4 =
* Fixed: xAPI Video not able to resume since GrassBlade xAPI Version v6.2.1

= v6.2.3 =
* Fixed: LearnDash reporting Completion Failed in some cases causing re-trigger from LRS.
* Fixed: Duplicate statements being sent in some cases
* Fixed: Multiple xAPI Content on same page using Guttenberg Blocks are all changing to same xAPI Content
* Other minor fixes

= v6.2.2 =
* Fixed: Double score recorded in H5P content
* Fixed: SCORM: error on upload when imsmanifest.xml has comments in first line
* Fixed: H5P content not hiding in LearnPress when retakes are exhausted
* Fixed: Error in Elementor Editor dropdown, added select2.full.js

= v6.2.1 =
* Fixed: Warning and reduced size of xAPI Content when global width/height is set in %

= v6.2.0 =
* Feature: Added Video Reports: Video Attempts Report, Video Overview Report, Video GradeBook Report. (Required: GrassBlade LRS v2.13.12+)
* Feature: xAPI Content dropdown list made searchable in different places
* Improvement: Locatisation of strings added via javascript. Mostly, labels and strings added in reports
* Improvement: Added registration to LearnDash Native Quiz xAPI Statements
* Improvement: Responsive Content: Allow using vw and vh for the xAPI Content Width/Height
* Improvement: Rebuild of Score and Completion calculation system
* Fixed: Failed statement without any result is received as 100% score
* Fixed: When xAPI Content is passed, but LearnDash Quiz content failed, it triggers a 0 score quiz passing in LearnDash v2.11+
* Fixed: Permission error due to syncronous requests from SCORM, LRS Connection Test, Videos, etc if sync-xhr is blocked by server
* Fixed: LearnDash Certificate and Quiz Continue button not showing on LearnDash Quiz page when using xAPI Content
* Fixed: Changed select2 version to 4.0.13 to avoid conflict with other LMSes
* Fixed: xAPI Content on LearnDash quiz not adding completion data to learndash_user_activity table
* More improvements and bug fixes

= 6.1.9 =
* Fixed: Conflict with Premium Addons for Elementor's Ticktok Widget

= 6.1.8 =
* Feature: Added heatmap progressbar for videos showing parts of the video user has watched.
* Feature: Reports: Added ability to hide reports from group leaders using Report settings
* Fixed: Bulk Settings export not exporting xAPI Content's which are in Pending Review or Future status
* Fixed: Interactive Videos: Allow editors or authors with edit permission on xAPI Content to add questions on it.

= 6.1.7 =
* Feature: Completion Testing Tool: New Test to detect if same grassblade email is used by multiple users
* Feature: Completion Testing Tool: Added button to restart course for the selected user
* Feature: Resume Behaviour setting added to easily define resume behaviour instead of directly using Registration value
* Fixed: WP Roles based groups not showing on LRS

= 6.1.6 =
* Improvement: Reports: center the data in reports
* Completion Behaviour: added fix to 'enable on completion' behaviour in  MasterStudy 3.3.3.9+
* Fixed: bulk settings export not exporting xAPI Content's which are in draft or private status
* Fixed other minor bugs and warnings

= 6.1.5 =
Feature: Sending statements for joined-group and left-group to LRS when users or group leaders are added/removed from group
Feature: Added Date Range selection for Question Report (Required: GrassBlade LRS v2.13.11+)
Fixed: Some types of Slides not working on Elementor
Improvement: Increased Quiz Report and Question Report timeout to 120 sec
Fixed several minor bugs

= 6.1.4 =
* Fixed minor bugs

= 6.1.3 =
* Upgrade: Upgraded jQuery version to 3.7.1
* Upgrade: Upgraded jQuery depricated functions error and success to done and fail
* Fixed: Attempted statement not being sent for LearnDash Courses when there is no xAPI Content [since LearnDash v3.4.0+, due to change in LearnDash hook data structure]
* Other minor bug fixes and cleanup

= 6.1.2 =
* Improvement: Events Tracking: Don't send statements to the LRS for update of Menu/Menu Items. Was sending separate statements for each menu item, if Post Update Event tracking was enabled.

= 6.1.1 =
* Fixed: Not able to upload content in latest version of WordPress.

= 6.1.0 =
* Feature: Test Suite for Testing Completion Tracking issues.
* Feature: Reports: Added Quiz Report and Question Report.
* Feature: Added Sensei LMS check in LRS test
* Improvement: Switched to using own mimetype function for Content Security due to issues on some servers
* Improvement: Content Launch: A universal and more secure launch url hiding the main content url.
* Fixed: REST API: WordPress 6.2 some installations GrassBlade LRS WP API request with API Key not authenticating.  Authentication method updated to allow header keys to be case-insensitive
* Fixed: Addons/Groups/PMPro: Fatal Error on Admin Report page course selection when course is deleted after adding to PMPro Membership.
* Fixed: Issue with Rich Quiz Reporting when Activity ID has & in the URL.
* Fixed: cmi5 error when remote fopen is disabled.
* Fixed: Interactive Video: resuming on a question freezes the video
* Fixed: Interactive Video: Fixed object id in answered statements being sent is changing & to &amp;
* Code cleanup

= 6.0.0 =
* Feature: Added integration for BuddyPress groups and reporting based on it.
* Feature: Added integration for PMPro levels as groups and reporting based on it.
* Feature: Added integration for BuddyPress groups and reporting based on it.
* Feature: Added integration for WP Roles as groups and reporting based on it.
* Improvement: Code restructuring for all groups related reporting.
* Improvement: Admin Reports, fixed UI issues on Admin reports for different themes
* Fixed: Error on manual grading of LearnDash essay
* Fixed: On some H5P content types, video getting struck and questions hidden.
* Fixed: Advanced Reports / LD Profile: Rich Quiz Report doesnt show.
* Fixed: Advanced Reports / Achievement Report: Report doesn't load if an Achievement is deleted.
* several other fixes and improvements.


= 5.3.4 =
* Improved: SCORM: Added viewport for better display on mobile devices
* Improved: REST API: Updated code to reduce conflicts
* Fixed: LearnPress content showing even when retakes are exhausted. Added remove_content parameter in grassblade to remove content and show only score table.
* Fixed: issue with content attempted related function causing attempt start tracking to not function correctly
* Other bug fixes

= 5.3.3 =
* Feature: Content Structure / GrassBlade LRS Integration: Send xAPI Content details to GrassBlade LRS even if LearnDash LMS is not installed. Restructure related code.
* Fixed: Addons Page: issues related to addons page specially on network website.

= 5.3.2 =
* Fixed: Interactive Video: Database table for questions not getting created.
* Fixed: Interactive Video: xAPI Statement for true-false statement invalid.

= 5.3.0 =
* Feature: Added Integractive Video support (questions) for Advanced Video Tracking
* Fixed: H5P Completion Behaviour button show/hide not working on some LMS and on other LRS
* Other minor bug fixes

= 5.2.3 =
* Fixed: LearnDash Native Quiz: sorting question not sending user responses in the statements correctly.
* Fixed: Statements sending username instead of User's Name
* Other minor bug fixes

= 5.2.2 =
* Fixed: LearnDash Quiz submit error in PHP 8 with matrix question.
* Fixed: target = url not working

= 5.2.0 =
* Feature: Added support for TutorLMS and MasterStudy LMS
* Feature: Added Achievement Report for LearnDash, LifterLMS, and GamiPress
* Feature: Added name format settings, and used that for showing user's name in all the reports
* Improved: Rebuilt the reporting and merged the Completion Report and User Completion Report
* Fixed: LRS Test: 1. Fixed PUT request showing false passed because of POST request
* Fixed: Progress Snapshop Report showing 100% or larger bar on some old completions. Now showing text Completed if date is not available
* Fixed: Date/Time on GrassBlade results and reports showed time in UTC, changed to WordPress configured timezone
* Fixed: CopyProtect code removing extra code on double code
* Fixed: Completion Tracking not working when email id is changed. Updated for better detection of user
* Fixed: H5P: Several access permisisons related issues
* Fixed: Video end screen showing exit button instead of results button for logged in user if Name and ID is selected for actor
* Fixed: Elementor xAPI Content block not showing on multi-site if Elementor is not Network Activated

= 5.1.0 =
* Feature: Added LMS Administrator role selection for full reporting access
* Feature: Added ability to show reports on all content based on role selection in GrassBlade Settings > Reports Settings
* Fixed: Copy Protect: Fixed several issues with copy protect enable/disable specially on very large files
* Fixed: LearnDash Native Quiz: failed statement not being sent on recent versions of LearnDash
* Improvment: Delete Folder: user wordpress function to delete folder
* Improvment: Reports: Added ability to toggle: Show all courses/Show Group Courses
* Improvment: Reports: Updated to show only courses of selected groups.
* Improvment: Reports: Don't show private and draft courses to Group Leaders
* Several other fixes and improvements

= 5.0.0 =
* Feature: Advanced Video Tracking: Added support for Wistia
* Feature: Added Elementor Block for easier integration.
* Feature: Visible on Completion feature added to show any block or text on completion of content
* Feature: Copy Protect to disable copy,paste,cut,drag and right click inside the content.
* Feature: Test for 404 permalink error and fix automatically.
* Feature: LRS Connection Test and Test Setup: Test for Dependent Addons for LMS
* Fixed: Fixed placement of grassblade content not working on LearnDash quiz page using [grassblade] shortcode.
* Fixed: LearnDash materials tab contents not showing when using H5P interactive videos
* Performance improvement for SCORM
* Several fixes, code update and cleanup.

= 4.1.6 =
* Fixed: js error in classic editor
* Fixed: JSON error in updated statements if there is complex html in a post

= 4.1.4 =
* SCORM: Storying cmi.objectives.* and cmi.interactions.* variables can be enabled using filter when required
* Trigger Messages: Additional LRS log messages for status based on passing/failing
* Feature: Disable Video Endscreen using additional filter code.
* Improvement: Better display of errors and warnings on content edit page.

= 4.1.0 =
* Feature: Reporting Features (beta) added for Admin and Group Leaders for LearnDash.
* Feature: Admin Reports Gutenberg Block for Admins and Group Leaders.
* Improvement: User Report: Average score needs to be average of only attempted courses.
* Improvement: User Report: Show only Attempted courses by default.
* Improvement: Video Player: Improved security and flexibility by including the player inside WordPress context. Removed URL parameters.
* Improvement: Video Player: Added end screen messages to custom label settings.
* Fixed: SCORM: Fullscreen button not visible.
* Fixed: Several minor errors and bugs.

= 4.0.1 =
* Fixed: Latest Rise xAPI package showing error on upload

= 4.0.0 =
* Feature: cmi5: Added support for cmi5 /beta
* Feature: Added settings for Table Styles
* Improvement: SCORM: Automatically add description from imsmanifest to the xAPI Content page
* Fixed errors/warnings

= 3.6.4 =
* Fixed: New Post creation statements: extensions need to be sent in object.definition
* Fixed: LearnDash Quiz Statements: statement with v0.95 getting rejected by other LRSes when category is added.
* Fixed: LearnDash Quiz Statements: Scaled value is sent as string

= 3.6.3 =
* Improvement: Show a message when added xAPI Content is trashed or deleted
* Fixed: SCORM 1.2 scaled value calculated wrong when max score is not 100

= 3.6.2 =
* Feature: Added ability to get user meta from GrassBlade LRS
* Fixed: After completion of xAPI Content on LearnDash Quiz page, the user not sent to next step.

= 3.6.1 =
* Fixed: SCORM 2004 not loading. Reverted the change (v3.5.4: SCORM: Fullscreen button not visible for iSpring player. Changed from using frameset to iframe)

= 3.6.0 =
* Fixed: Warnings in PHP 8
* Fixed: Statement Viewer not working on WordPress 5.6 in some installations
* Fixed: Dropbox and direct upload not working on WordPress 5.6 in some installations

= 3.5.4 =
* Fixed: SCORM: Fullscreen button not visible for iSpring player. Changed from using frameset to iframe

= 3.5.3 =
* Fixed: Course Structure integration for LRS and Manual Completions Integration showing only 20 lessons if more than 20 lessons in the course.
* Fixed: SCORM: Not able to load some demo SCORM packages that has parameters in the launch path

= 3.5.2 =
* SCORM: Fixed bug where Articulate loads 404 page due to undefined location.

= 3.5.1 =
* Fixed: DB Upgrade error due to old MySql/MariaDB version by checking the version before running upgrade query

= 3.5.0 =
* Improvement: GrassBlade LRS REST API: Updated the way REST API is connected and authenticated with GrassBlade LRS, reducing the dependency on server behaviour.
* Improvement: GrassBlade LRS SSO improvement & security: If REST API is configured SSO doesn't check IP (GrassBlade LRS v2.8.0+) for same domain. If REST API not configured or using different domain, old method is used only "if" SSO IP is configured.
* Improvement: reduce GB_POOLING_TIME to 15 seconds from 45. And, During long polling Write and close the session if some other plugin has started it. Otherwise longpolling will restrict other requests to the website
* Fixed: SCORM registration value not based on configured value
* Fixed: to show extra space on left in block editor
* Fixed: Javascript error when lazyloading script.js file
* Fixed: Deprication Warning due to jQuery.fn.load
* Several bug fixes

= 3.4.3 =
* Feature: View User Report from Users List in WP ADMIN
* Fixed: Updating course from LearnDash Course Builder is removing all xAPI Contents from Lessons and Quizzes added using dropdown metabox
* Fixed several minor bugs

= 3.4.2 =
* Improved: Added space on the left of content in Gutenberg editor for easy selection of the xAPI Content block
* Fixed: Several minor bugs, warnings and notices
* Fixed: Test Suite: REST API test not working on some LRSes

= 3.4.0 =
* Feature: Completion Tracking of H5P along with scores, now without an LRS. LRS required for detailed responses tracking.
* Feature: Added setting "Completion on Module Completion". Fixes completion not happening on iSpring or Articulate Storyline due to only one passed statement received in the LRS, specially with old content on Chrome 80+
* Improvement: Added editable labels for message displayed when xAPI Content is passed and failed, also added to localization strings.
* Improvement: xAPI Statements: Add by-whom information when a statement is sent for a user different from the user logged in
* Improvement: Added registration field to grassblade_completions table
* Fixed: LearnDash Quiz not sending correct answer choices when choices are not in english.
* Fixed: Content Security not working for Audio/Video
* Fixed: Content upload issue on some websites.
* Fixed: Errors on older version of WordPress and PHP
* Fixed: Custom activity id generation without ending slash to avoid completion issue on some Articulate versions
* Fixed: Endpoint changes to a slash / when endpoint is blank
* Fixed: Content Security setting working opposite. .htaccess generated when disabled, and deleted when disabled.
* Fixed: Gutenberg xAPI Block showing Block rendered as empty on Quiz edit page if Lesson has xAPI Content with Completion Tracking Enabled.
* Fixed: Gutenberg xAPI Block: jQuery error in Gutenberg xAPI Block during Completion Enable/Disable check
* Fixed: Events Tracking: updated: Description ... sent when post content is empty
* Fixed: Security Improvements
* Fixed: Several other bugs

= 3.3.0 =
* Feature: Added LRS Connection Test Suite to test for many possible issues
* Feature: GrassBlade LRS: Added option to auto-fill/update API details from GrassBlade LRS 2.6.0+
* Feature: Settings Update: Send a statement on GrassBlade settings change. Also used for by LRS for detection of settings update
* Improvement: Updated Add-ons page
* Improvement: Content Upload: Automatically add xAPI Content title based on uploaded package
* Improvement: Upgraded xAPI Library
* Improvement: Changed code showing list of xAPI Content in xAPI Content dropdown selection of Gutenberg editor. For better performance, and compatibility with some other plugins.
* Improvement: Updated SCORM for better bookmarking in Chrome 80+
* Fixed: Bulk Upload: SCORM version not updated on upload of SCORM content.
* Fixed: Bulk Upload: HLS and DASH zip uploads not working.
* Fixed: User Report: xAPI Content titles and User Name not showing correctly.
* Legacy: Removed "Update" button in metabox, which was now only visible when Non-Gutenberg page
* Fixed: Rich Quiz Report on multi-page LD Profile page
* Fixed: Completion not working when there is & in Activity ID
* Fixed: Some minor bugs

= 3.2.4 =
* Fixed: SCORM issues in Elucidat
* Fixed: Test Setup option
* Fixed: Security Fixes.
* Improvement: Internationalization update for User Report
* Improvement: Some SCORM content sending verb passed/failed, and other sending completed. Changed to completed for consistency
* Fixed: Minor bugs
* Fixed: LearnDash Native Quiz giving error with not logged in user, when guest tracking is disabled.


= 3.2.1 =
* Fixed: Error with SSO SAML plugin due to old method of wp_login action
* Fixed: Support page giving error when Apache is installed a CGI instead of module.

= 3.2.0 =
* Feature: Added support for Lifter LMS via AddOn
* Fixed: Redirect to undefined page on auto redirected when lesson is completed already
* Fixed: Do not run LearnDash specific code when LearnDash is not installed.
* Fixed: Advance Completion behaviour for WP Courseware and LearnPress

= 3.1.10 =
* Fixed: Double Next Lesson button vibible in LearnDash having xAPI Content - Hide Button setting in LearnDash v3.1.4 and above

= 3.1.9 =
* Fixed: Quiz Report not loading on LearnDash Profile in LearnDash v3.1.4
* Enable Custom Fields in xAPI Content

= 3.1.8 =
* Feature: Compatibility update for Advanced Completion Behaviours in WP Courseware, LearnPress LMS integrations.
* Feature: Added category (tool://grassblade/xapi/#<version>) for all server side grassblade generated statements. TODO: Add to SCORM & Video tracking statements
* Fixed: Some notices on User Report page.
* Fixed: LearnDash Native Quiz: Fixed statements not sending for LearnDash Quiz if Allow Guest (Ask Name/Email) is used.
* Fixed: LearnDash Native Quiz: Attempted statements for Native Quiz not sent for any guests.
* Security Fix

= 3.1.7 =
* Added shortcode grassblade_attempts_progress for getting progress inside Articulate Rise.

= 3.1.6 =
* Feature: Added support page to raise support ticket from the plugin.
* SCORM: Updated launch mechnaism so LRS details are not in URL.
* SCORM: Improvements for security on Require Login and Guest access feature.
* Fixed: Resume of Articulate Rise SCORM content.
* Fixed: User Report: Fixed divide by 0 error for avg_score calculation
* Improvment: PageViews Tracking: Stop sending wc-ajax requests like wc-fragments
* Fixed: Quiz Report: LearnDash Profile page showing Fatal Error on LearnDash old version < v3.1

= 3.1.4 =
* SCORM: Fixed iSpring SCORM packages not sending any data to LRS
* SCORM: Fixed resume not working in Elucidat SCORM packages

= 3.1.2 =
* Improvement: SCORM: change learner_id to use email instead of user id.

= 3.1.1 =
* Feature: Added settings search
* Feature: Added Custom Label setting
* Improvement: SCORM statements improved with more accurate name and response

= 3.1.0 =
* Feature Added: Full User Report of all content for users. Along with support for Rich Quiz Report.
* Fixed: MPEG-DASH video not working.

= 3.0.4 =
* Fixed: Show latest completion status in the completion message.
* Bug Fixes

= 3.0.3 =
* Bug fixes

= 3.0 =
* Feature: Added SCORM Support along with Completion Tracking and xAPI Statements generation
* Feature: Show Rich Quiz Report in Results table and on LearnDash Profile page
* Feature: Show a congratulations message on xAPI/SCORM Content completion when completion tracking is enabled and completion behaviour is not hide button.
* Feature Improvement: Completion Tracking: Mark both LearnDash Lesson as well as Topic as completed automatically, if Quiz under Topic is completed by xAPI Content, and everything else is completed
* Fixed: Completion Tracking: LearnDash Next Lesson Button was not visible if Lesson Progression was disabled
* Fixed: Content Security not working on some Windows based servers
* Fixed: Completion Behaviour: Mark Complete button was getting enabled even on a failed statement. Marking checks in the background before enabling the button.

= 2.2.1 =
* Fixed: LearnDash: Next Lesson button not showing on Lesson with completed quizzes if Course Progression is disabled & Lesson not yet completed.

= 2.2 =
* New advanced Completion Tracking features: Hide Button, Show button on completion, Enable button on completion, Auto-redirect on completion.
* Added GrassBlade Add-ons page
* Fixed: Course structure not updating on LRS if debug display is enabled.
* Fixed: Error on sending enrollment on bulk user import
* Fixed: Content Security not working on some Windows based servers

= 2.1.11 =
* Fix access check bug with H5P on LearnDash
* Fix error on uploading via Internet Explorer

= 2.1.10 =
* Fixed: H5P error in guest mode when used with Secure Tokens
* Fixed: Logout statements generated for guests (not logged in users)

= 2.1.9 =
* Fixed: Logout statements generated on cron job
* Fixed: Upload error for non-tracking content
* Other minor improvements and bug fixes


= 2.1.8 =
* Fixed: Dropbox Upload: error when no dropbox key added
* Fixed: Events Trackigs: don't use verb updated when post is deleted. Might change verbs for different statuses.
* Feature: Add GrassBlade filter for filtering plugins related to GrassBlade

= 2.1.7 =
* Feature: New imporved direct uploader with progress bar, and new version of Dropbox Uploader
* Feature: Ability to upload videos.
* Feature: Support for HLS (.m3u8) and MPEG-DASH (.mpd)
* Feature: Added Events tracking for: New Post Creation, Post Updation, User Login, User Logout, User Registration, User Deletion, User Enrollment in Course, User Unenrollment from Course, New Comment
* Change: Moved PageViews tracking settings to Events Tracking page
* Fixed: Upload related issues.
* Store versions information of xAPI Contents

= 2.0.5 =
* Fixed: Video Pro private videos, restricted for domains not work.
* Other bug fixes

= 2.0.4 =
* Fixed: Update button not working on xAPI Content page

= 2.0.3 =
* Using Gutenberg editor for xAPI Content
* Fixed: Completion not working for xAPI Content added as sub block via Guttenberg Blocks inside accordion, tabs, columns, etc
* Other bug fixes

= 2.0.2 =
* Loading H5P modules In-Page using H5P's own shortcode, instead of embed link.
* Fixed: Next Lesson Link not showing in LD 3.0
* Other bug fixes

= 2.0 =
* Feature: Added xAPI Video Profile 1.0 support for advanced video analytics. Supports: Self-hosted Videos (mp4, etc), Audios (mp3, wav), YouTube, Vimeo.
* Feature: GrassBlade xAPI Companion Blocks for xAPI Content, LeaderBoard, and User Score
* Feature: Fluid responsive Lightbox as well as In Page content boxes, auto adjusting in desktop, Android as well as iOS
* Feature: Added Aspect Lock setting, so that the responsive adjustment is locked in a aspect ratio. In Page is always aspect locked.
* Fixed WordPress REST API not working in some servers, specially FCGI based
* Add Select All/None option in Bulk Import
* Fixed: mark complete button visible on LearnDash 3.0
* Added grassblade_video_player filter for switching to old video player.
* Fixed: Secured Tokens not working when used with "Name and User ID" based User Identifier
* Use the WordPress Date/Time format in "Your Results" table and Leaderboard Table.
* Security Fix
* Other bug fixes

= 1.6.7.2 =
* Fixed: Issue with WordPress REST API connection from GrassBlade LRS for some servers.

= 1.6.7.1 =
* Fixed: LD3.0 Mark Complete button visible

= 1.6.7 =
* Security Fix

= 1.6.6 =
* Fix uploading of Articulate Rise non xAPI content

= 1.6.5 =
* Bug Fixes

= 1.6.4 =
* Show trigger messages only when related functions are called via trigger
* Do not block content for completion of previous content if LearnDash Lesson Progression is disabled

= 1.6.3 =
* Ability to change LearnDash Course Progress to In Progress if any xAPI Content has been started

= 1.6.2 =
* Fix video play issue caused due to partial content fetch in xAPI Content with Content Security enabled.
* Bug fixes

= 1.6.1 =
* Bug fixes

= 1.6 =
* Fixed: LearnDash Quiz sending grouping.id=false if quiz is not attached to a course
* Fixed: Bulk Settings upload not updating xapi_activity_id causing issue during completion tracking
* Fixed: Error on launching H5P content.
* Content auto sizing when weight/height is in %.
* Improve Pass/Fail checking.
* Fixed: Not able to mark the lesson as complete when there is xAPI Content as well as Topic/Quiz on a Lesson.
* Fixed: When shared course steps is enabled, completion of xAPI Content marks the Lesson or Topic as complete even if child Topic/Quiz is not complete.
* Fixed: several bugs

= 1.5.22 =
* Fixed: xAPI based quiz completion not added to learndash activity table
* Fixed: several bugs

= 1.5.21 =
* Fixed: WordPress HTTP API connection from GrassBlade LRS, and support for LearnDash course and content integration with GrassBlade LRS (Version >= 2.1.1.8)
* Fixed: several bugs

= 1.5.20 =
* Fixed: LearnDash Lesson getting marked as completed if content on it is completed, even when there are incomplete quizzes under it.

= 1.5.19 =
* Fixed: Completion Tracking with Shared Course Steps in LearnDash not working.
* Fixed: Completion Tracking not working when actor type is account/user id.

= 1.5.17 =
* Feature: Added actor_type parameter and User Identifier setting to select whether to use user email id or user id to send as identifier to the LRS.
* Added error message when the LRS is using http and WordPress is using https.

= 1.5.16 =
* Fixed: content with completion tracking disabled should not restrict access to quiz

= 1.5.15 =
* Fixed: completion behaviour and completed statement after xAPI quiz completion
* Auto generation of new registration value after every completion.
* Change Print Certificate link to button
* Fixed: Video using mp4 url
* Fixed: GrassBlade trying to mark lesson in unenrolled course, and user getting enrolled to course. Now, the mark complete will happen only when user is enrolled to course
* Fixed: several bugs

= 1.5.14 =
* Fixed: 500 error on uploading content, and on editing content linked to other pages
* Updated Bulk Import feature with several changes

= 1.5.12 =
* Fixed BadgeOS compatibility code issue
* Fixed few other bugs and minor adjustments.

= 1.5.11 =
* Fixed LearnDash Quiz page with xAPI Content not getting marked as complete
* Added time-from,time-to information in video related statements. And changed time format to seconds.
* Show Next Lesson link on LearnDash pages with xAPI Content
* Added Bulk Import and Bulk Settings options under xAPI Content

= 1.5.10 =
* Added xAPI Statement to LRS for LearnDash Assignment upload.

= 1.5.9 =
* Added ability to track additional verbs. (Requires addon code)
* Fixed: Associated content not showing on xAPI Content edit page
* Fixed: Warning during update check
* Fixed: Vimeo not showing fullscreen button.

= 1.5.8 =
* Show original activity id

= 1.5.7 =
* Fixed Secure Tokens

= 1.5.6 =
* Added LearnDash Topic Completion xAPI statements
* Fixed bug

= 1.5.5 =
* Added LearnDash Quiz Tracking
* Allow Non-xAPI (non tracking) mode for Video
* Added BadgeOS Badge Earned Tracking to LRS
* Added BadgeOS Compatibility code.
* Added help text and other bug fixes.

= 1.5.4 =
* Fixed tracking issue with emails having + sign in them
* Added more infomation/suggestion in exhaustive test on upload errors.

= 1.5.3 =
* Bug Fixes: Video Button not showing. Content Details getting erased on WordPress 4.4

= 1.5.2 =
* Bug Fixes related to H5P Permissions

= 1.5.1 =
* Bug Fixes related to H5P

= 1.5.0 =
* Secure Tokens
* H5P Integration
* LeaderBoard for xAPI Content
* Ability to place xapi content in any part of the page using shortcode.
* Groups integration with the LRS.
* Record and show scores and completion on any page, post, lesson or quiz.
* Ability to change the URL slug for xAPI Content.
* Support for non Tin Can version of Articulate Studio 13
* LearnDash Mark Complete button is gone
* Ability to disable Statement Viewer

= 1.4.1 =
* Ability to upload image buttons instead of using text links
* Ability to test errors in Completion Tracking setup

= 1.3 =
* Bug fixes
* Added Video Tracking for YouTube and Vimeo
* Added content security for static content.
* Added better error information and suggestions.
* Added GrassBlade LRS SSO.
* Removed Shortcode Generator. Shortcode would still work.

= 1.2 =
* Bug fixes
* Updated the way completion triggering works

= 1.1 =
* Added Meta box to easily select and add xAPI Content on any page/post
* Added completion tracking and mark completion integration with LearnDash Lessons/Topics/Quizzes
* Upgraded Statement Viewer to 1.0
* Added v1.0 option
* Added preview page for xAPI Contents
* Added shorter shortcode with only content id
* Made registration field static by default to support bug on Articulate related to resume/bookmark feature.
* Few minor bug fixes

= 0.7.1 =
* Bug fixes

= 0.7 =
* Feature to upload your package from Dropbox

= 0.6.2 =
* Fixed one click upload link for LearnDash Integration

= 0.6.1 =
* Bug fixes

= 0.6 =
* Added get_state and set_state shortcodes to utilize State API

= 0.5.3.4 =
* Internationalization capable code.
* Bug fixes

= 0.5.3.3 =
* Bug fixes
* Quiz completion reporting for LearnDash quizzes

= 0.5.3.2 =
* Bug fixes

= 0.5.3 =
* Lesson and Course Attempt and Completions of LearnDash LMS

= 0.5.1 =
* Added option to decide showing content on xAPI Content page

= 0.5 =
* Added option to open in a Lightbox
* Advanced Content Uploader
* Bug fixes.

= 0.4.2 =
* Added registration parameter
* Added support for categories in xAPI Content
* Added Referer in Page Views to track where the user came from.
* Changed Branding
* Bug fixes.

= 0.4.0 =
* Fix support for Articulate Storline after their changes.
* Added support for DominKnow Calro Tin Can package
* Added support for Lectora Inspire Tin Can package
* Added support for iSpring Tin Can package
* Added Statement Viewer
* Added activity_id
* Added target options to be able to choose from embeding content in page, or a Launch link.

= 0.3.0 =
* Added One click upload

= 0.2.0 =
* Added short code generator

= 0.1.0 =
* Launch!
