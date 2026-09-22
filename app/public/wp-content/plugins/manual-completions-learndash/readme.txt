=== Manual Completions for LearnDash ===
Contributors: liveaspankaj
Donate link:
Tags: learndash, grassblade, manual, completion, mark complete, bulk
Requires at least: 4.0
Tested up to: 7.0
Stable tag: 2.0
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take full control of student progress. Manually mark LearnDash courses, lessons, topics, and quizzes as complete or incomplete, either for individual users or in bulk.

== Description ==

Manual Completions for LearnDash gives admins a simple and powerful way to review completion status and manually mark LearnDash courses, lessons, topics, and quizzes as complete or incomplete.

Use it for individual updates or manage bulk completions for hundreds of users at once. With a single click, you can load all users enrolled in a course and process their completion status quickly.

The plugin also supports CSV uploads using fields such as user_id, course_id, lesson_id, topic_id, and quiz_id. This makes it easy to prepare a list of completions, review them, and process them in bulk in any order you prefer.

Manual Completions can also bypass completion restrictions applied to xAPI content when needed.

When a course step linked to xAPI content is marked incomplete, the related xAPI content completion status is removed to ensures the user must complete the xAPI content again.

**Tracking:**
- If you have an LRS, you can see tracking data, including the user id and name of the admin who marked the lesson complete.

**Requirements to use this plugin:**
To use this plugin you need these two plugins:
1. [LearnDash LMS](https://www.nextsoftwaresolutions.com/r/learndash/wp_mcl_plugin_page)
2. [GrassBlade xAPI Companion](https://www.nextsoftwaresolutions.com/grassblade-lrs-experience-api/)

**Other Manual Completion Plugins:**
[Manual Completions for LifterLMS](https://www.nextsoftwaresolutions.com/manual-completions-for-lifterlms/)
[Manual Completions for TutorLMS](https://www.nextsoftwaresolutions.com/manual-completions-for-tutorlms/)
[Manual Completions for LearnPress](https://www.nextsoftwaresolutions.com/manual-completions-for-learnpress/)

**Related Plugins for LearnDash LMS:**
[Manage Enrollments for LearnDash LMS](https://www.nextsoftwaresolutions.com/manage-enrollment-for-learndash/)
[Autocomplete LearnDash Lessons and Topics](https://www.nextsoftwaresolutions.com/autocomplete-learndash-lessons-and-topics/)
[Visibility Control for LearnDash LMS](https://www.nextsoftwaresolutions.com/learndash-visibility-control/)

== Installation ==

This section describes how to install the plugin and get it working.


1. Upload the plugin files to the `/wp-content/plugins/manual-completions-learndash` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WP ADMIN > GrassBlade > Manual Completions for LearnDash or (WP ADMIN > LearnDash > Manual Completions)


== Frequently Asked Questions ==


== Screenshots ==

1. Manual Completions page
2. Checking Completion status
3. Marking Completions
4. Status Reported in the LRS (if you have an LRS)
5. CSV Upload Format
6. Get all enrolled users

== Changelog ==

= 2.0 =
* Feature: Added ability to mark incomplete for courses, lessons, topics, and quizzes.
* Security: Several security improvements.
* Fixed: Various minor bugs and improvements.

= 1.10 =
* Improvement: Added example csv file for upload.
* Fixed: Wrong message shown when content is already completed.

= 1.9 =
* Fixed: GrassBlade Icon not displaying on multisite.
* Fixed: Not able to mark complete for LearnDash Lessons having Video with Video Progression enabled. Required: GrassBlade xAPI Companion v2.6.7+
* Fixed: Not able to mark complete for LearnDash External Lesson. Required: GrassBlade xAPI Companion v2.6.7+
* Other minor improvements.

= 1.8 =
* Improvement: CSV Upload. Allow uploading user_email or user_login instead of user_id.
* Improvement: Store quiz completion data in LearnDash Activity/ActivityMeta table. Default Quiz Completion data: score = 100, percentage = 100%, points and total points = 1, 1 out of 1 questions.

= 1.7 =
* Improvement: User search is changed for select2 to own code to improve performance for large websites.
* Feature: Added Total count

= 1.6 =
* Fixed: issues related to addons page specially on network website.

= 1.5 =
* Feature: Added ability to pull list of all enrolled users.

= 1.4 =
* Feature: Added ability to mark complete entire Course or entire Lesson.

= 1.3 =
* Fixed: Styling to work with v4.0 of GrassBlade xAPI Companion


= 1.2 =
* Fixed: Completion with CSV upload showing Unknown Error.
* Fixed: Topic and Quiz selection causing issues

= 1.1 =
* Fixed CSS issue on GrassBlade page
* Added Addons page

= 1.0 =
* Initial Commit


