<?php
if (!defined('ABSPATH')) exit;
$endpoint = $grassblade_settings["endpoint"];
$lrs = str_replace("/xAPI", "", $endpoint);
$authority = explode("-", $grassblade_settings["user"]);
$authority = $authority[0];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completion Test Suite</title>
    <script src="<?php echo plugins_url('../../js/jquery.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../../assets/select2/select2.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../../js/script.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('completion_test.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../../js/xapiwrapper.js', __FILE__); ?>"></script>

    <link rel="stylesheet" href="<?php echo plugins_url('style.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('../../css/styles.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('../../assets/select2/select2.min.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo includes_url("/css/dashicons.css", __FILE__); ?>">
</head>

<body style="font-family: sans-serif;">
    <div class="grassblade_completion_test grassblade_lrstest">
        <div class="completions_header">
            <h1>Completion Tracking Test</h1>
            <form class="completion_test_form">
                <table>
                    <tr>
                        <th>User: </th>
                        <td>
                            <?php
                            if( !empty($user_id)) {
                                echo $name.  " (ID: ".$user_id.") <input type='hidden' value='".$user_id."' name='user_id' id='user_id' />";
                            }
                            else
                            {
                                echo "<input type='text' value='' name='user_search' id='user_search' /><br><small>(Type the User ID, Email id or Username)</small>";
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Content: </th>
                        <td>
                            <select name="content_id" id="xapi_content_selector" class="gb_select2_xapi_content" placeholder="select xapi content">
                                <option value="">--Select xAPI Content--</option>
                                <?php
                                $xapi_contents = get_posts(["post_type" => 'gb_xapi_content', 'posts_per_page' => '-1', 'status' => 'publish']);
                                foreach ($xapi_contents as $xapi_content) {
                                    $selected = ( $content_id == $xapi_content->ID )? 'selected':'';
                                    echo "<option value='$xapi_content->ID' $selected>$xapi_content->post_title</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php if( empty($user_id) ) { ?>
                    <input type="hidden" name="action" value="show_completion_test" />
                    <button class="start_test_button" type='submit'>Submit</button>
                <?php } else { ?>
                    <div style="display: flex; align-items:center; justify-content:center; gap: 5px;">
                        <div class="start_test_button" onclick="run_completion_tests();">Start Testing</div>
                        <div id="reset_learner_progress" >
                            <a onclick="run_reset_learner_progress(this); return false;" class="reset_learner_progress_button">Restart Content for this User</a>
                            <span>
                                <span class="dashicons-before dashicons-info" onClick="grassblade_test_lightbox_show(this)">
                                    <div>
                                        <h3>About:</h3>
                                        <div>This button let's you reset current progress of the user and restart the content from beginning.</div>
                                        <div>This will not remove any completions or scores</div>

                                        <h3>Reason for Use:</h3>
                                        <div>Due to bug in contents, sometimes user's are stuck in the content and either see a white screen on launch or are not able to process</div>
                                        <div>Clicking this button will reset the content for the user and they can start again.</div>

                                        <h3>Impact:</h3>
                                        <div>If the user is mid-way in the content, that partial progress is lost.</div>
                                    </div>
                                </span>
                            </span>
                        </div>
                    </div>
                <?php } ?>
            </form>
        </div>
        <div class="completions-tests-container" style="display: none;">
            <div id="lrstest1" class="lrs-test">
                <b class="test-title">1. Completion Tracking is enabled?</b>
                <div class="button-lrstest" onclick="is_completion_tracking_enabled()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span> <span><span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>Completions tracking is feature to check completion status of xAPI Content and then complete the lesson/topic/quiz where the xAPI Content is added.</div>

                                <h3>Reason for Failure:</h3>
                                <div>
                                    <p>Common reasons for failure are:</p>
                                    <ol>
                                        <li>The Completion Tracking option box is not checked in the xAPI Content edit page.</li>
                                    </ol>
                                </div>

                                <h3>Impact:</h3>
                                <div>The completion will not be recorded in the WordPress and associated lessons/topics/quizzes will not be completed automatically.</div>

                                <h3>Solution:</h3>
                                <p>Go to the edit page of the xAPI Content and check the box say "Completion Tracking" in xAPI Content Details section.</p>
                                <a href="edit_page_url" target="_blank" class="edit_page_button button-lrstest">Go to Edit Page</a></li>
                                <h3>xAPI Content Settings</h3>
                                <div class="message">No Settings Found</div>
                            </div>
                        </span></span>
                </div>
            </div>
            <div id="lrstest2" class="lrs-test">
                <b class="test-title">2. Multiple Contents with Same Activity ID</b>
                <div class="button-lrstest" onclick="contents_with_same_activity_id()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span>
                    <span>
                        <span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>This check helps you find xAPI content in WordPress with identical Activity IDs. Completing any of these content will result in completion of only the content with the <i class="dashicons dashicons-yes"></i> icon, and associated LMS lessons, topics, or quizzes.</div>

                                <h3>Reason for Failure:</h3>
                                <div>When two or more xAPI Contents have same xAPI Content ID.</div>

                                <h3>Impact:</h3>
                                <div>It impacts the completion of the associated lesson/topic/quiz and can complete all the lessons/topics/quizzes where a content with same xAPI Content is added.</div>

                                <h3>Solution:</h3>
                                <p>Re-export your xAPI Content again with a unique Activity ID from your authoring tool and upload it again.</p>
                                <p>Delete any extra xAPI Content with same Activity ID</p>

                                <div class="results-found">
                                    <h3 class="results-title">Contents Found</h3>
                                    <ol>
                                        No matching content found.
                                    </ol>
                                </div>
                            </div>
                        </span>
                    </span>
		    <br><br>
                    <span class="message"></span>
                </div>
            </div>
            <div id="lrstest3" class="lrs-test">
                <b class="test-title">3. Content Added on & Completion</b>
                <div class="button-lrstest" onclick="content_added_and_completion()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span>
                    <span>
                        <span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>This test checks the LMS pages where this content is added and checks the completion status of the user.</div>

                                <h3>Reason for Failure:</h3>
                                <div>When the same xAPI Content is added on two or more LMS pages and user didn't completed the step.</div>

                                <h3>Impact:</h3>
                                <div>It impacts the completion of the associated lesson/topic/quiz and can complete all the lessons/topics/quizzes where a content with same xAPI Activity ID is added.</div>

                                <h3>Solution:</h3>
                                <p>Re-export your xAPI Content again with a unique Activity ID from your authoring tool and upload it again. Then use that content on the other lessons/topics/quizzes.</p>

                                <div class="results-found">
                                    <h3 class="results-title">Contents Found</h3>
                                    <ol>
                                        No Contents Found.
                                    </ol>
                                </div>
                            </div>
                        </span>
                    </span>
	 	    <br><br>
                    <span class="message"></span>
                </div>
            </div>
            <div id="lrstest4" class="lrs-test dont-auto-reset">
                <b class="test-title">4. LMS Check</b>
                <div class="button-lrstest" onclick="check_lms_integration()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span>
                    <span>
                        <span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>This test checks if appropriate integration addon is installed for a supported installed LMS.</div>

                                <h3>Reason for Failure:</h3>
                                <div>A supported LMS is installed but additional integration addon is not installed. No addon is required if you're using LearnDash LMS plugin.</div>

                                <h3>Impact:</h3>
                                <div>Completion Tracking and other features specific to LMS integration doesn't work.</div>

                                <h3>Solution:</h3>
                                <p>Need to install the appropriate addon.</p>
                            </div>
                        </span>
                    </span>
                    <span class="message"></span>
                </div>
            </div>
            <div id="lrstest5" class="lrs-test dont-auto-reset">
                <b>5. Completion Triggers in GrassBlade LRS</b>
                <div class="button-lrstest" onclick="grassblade_check_triggers_in_lrs()"><?php _e("Test", "grassblade") ?></div>
                <br>
                <div>
                    <b>Compatible With:</b> <span>GrassBlade LRS</span>
                </div>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span>
                </div>
                <div class="lrstest-diagram">
                    <span data-no="1" class="dashicons dashicons-dashboard"></span>
                    <span data-no="2" class="dashicons dashicons-minus"></span>
                    <span data-no="3" class="dashicons dashicons-no"></span>
                    <span data-no="3" class="dashicons dashicons-minus middle"></span>
                    <span data-no="3" class="dashicons dashicons-yes"></span>
                    <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
                    <span data-no="5" class="dashicons dashicons-wordpress"></span>
                </div>
                <div class="verbs">
                    <div class="verb_attempted sub-test"><span class="dashicons no-change dashicons-yes"></span> <span class="dashicons no-change dashicons-no"></span> <span class="test-title">Attempted Trigger</span>
                        <span><span class="dashicons-before dashicons-info">
                                <div>
                                    <h3>About:</h3>
                                    <div>Attempted Trigger is configured in the GrassBlade LRS. It sends back the information to WordPress that the content has been started. This trigger is not critical.</div>

                                    <h3>Reason for Failure:</h3>
                                    <div>This test will fail if this trigger is not configured in the LRS.
                                    </div>

                                    <h3>Impact:</h3>
                                    <div>In your LMS with the feature added, the Course Status shows as "In Progress" after the content is started. This will not happen if Attempted Trigger is not setup.</div>

                                    <h3>Solution:</h3>
                                    <p>Create a trigger in the LRS with following configuration:</p>
                                    <ol>
                                        <li><b>Name:</b> All Attempted</li>
                                        <li><b>Type:</b> Completion</li>
                                        <li><b>URL:</b> <code><?php echo admin_url('admin-ajax.php?action=grassblade_xapi_track'); ?></code></li>
                                        <li><b>Verb:</b> attempted</li>
                                        <li><b>Authority:</b> <?php echo $authority; ?></li>
                                        <li><b>Status:</b> ON</li>
                                    </ol>
                                    <a class="button-primary" href="<?php echo $lrs . "/Triggers"; ?>" target="_blank">Go to Triggers</a>
                                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                                </div>
                            </span></span>
                    </div>
                    <div class="verb_passed sub-test"><span class="dashicons no-change dashicons-yes"></span> <span class="dashicons no-change dashicons-no"></span> <span class="test-title">Passed Trigger</span>
                        <span><span class="dashicons-before dashicons-info">
                                <div>
                                    <h3>About:</h3>
                                    <div>Passed Trigger is configured in the GrassBlade LRS. It sends back the information to WordPress that the content has been Passed/Completed.</div>

                                    <h3>Reason for Failure:</h3>
                                    <div>This test will fail if this trigger is not configured in the LRS.
                                    </div>

                                    <h3>Impact:</h3>
                                    <div>Without this trigger, your lesson/topic/quiz/unit with Completion Tracking enabled will not be marked as complete if the content sends "passed" verb on completion.</div>

                                    <h3>Solution:</h3>
                                    <p>Create a trigger in the LRS with following configuration:</p>
                                    <ol>
                                        <li><b>Name:</b> All Passed</li>
                                        <li><b>Type:</b> Completion</li>
                                        <li><b>URL:</b> <code><?php echo admin_url('admin-ajax.php?action=grassblade_completion_tracking'); ?></code></li>
                                        <li><b>Verb:</b> passed</li>
                                        <li><b>Authority:</b> <?php echo $authority; ?></li>
                                        <li><b>Status:</b> ON</li>
                                    </ol>
                                    <a class="button-primary" href="<?php echo $lrs . "/Triggers"; ?>" target="_blank">Go to Triggers</a>
                                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                                </div>
                            </span></span>
                    </div>
                    <div class="verb_failed sub-test"><span class="dashicons no-change dashicons-yes"></span> <span class="dashicons no-change dashicons-no"></span> <span class="test-title">Failed Trigger</span>
                        <span><span class="dashicons-before dashicons-info">
                                <div>
                                    <h3>About:</h3>
                                    <div>Failed Trigger is configured in the GrassBlade LRS. It sends back the information to WordPress that the content has been Failed. If passing percentage is configured in xAPI Content, the status passed/failed depends on the passing pecentage and hence a "failed" verb can also be used for completion.</div>

                                    <h3>Reason for Failure:</h3>
                                    <div>This test will fail if this trigger is not configured in the LRS.
                                    </div>

                                    <h3>Impact:</h3>
                                    <div>Without this trigger, your lesson/topic/quiz/unit with Completion Tracking enabled will not be marked as complete or failed if the content sends "failed" verb on completion. The score will not show on LMS or GrassBlade User Report on WordPress.</div>

                                    <h3>Solution:</h3>
                                    <p>Create a trigger in the LRS with following configuration:</p>
                                    <ol>
                                        <li><b>Name:</b> All Failed</li>
                                        <li><b>Type:</b> Completion</li>
                                        <li><b>URL:</b> <code><?php echo admin_url('admin-ajax.php?action=grassblade_completion_tracking'); ?></code></li>
                                        <li><b>Verb:</b> failed</li>
                                        <li><b>Authority:</b> <?php echo $authority; ?></li>
                                        <li><b>Status:</b> ON</li>
                                    </ol>
                                    <a class="button-primary" href="<?php echo $lrs . "/Triggers"; ?>" target="_blank">Go to Triggers</a>
                                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                                </div>
                            </span></span>
                    </div>
                    <div class="verb_completed sub-test"><span class="dashicons no-change dashicons-yes"></span> <span class="dashicons no-change dashicons-no"></span> <span class="test-title">Completed Trigger</span>
                        <span><span class="dashicons-before dashicons-info">
                                <div>
                                    <h3>About:</h3>
                                    <div>Completed Trigger is configured in the GrassBlade LRS. It sends back the information to WordPress that the content has been Passed/Completed.</div>

                                    <h3>Reason for Failure:</h3>
                                    <div>This test will fail if this trigger is not configured in the LRS.
                                    </div>

                                    <h3>Impact:</h3>
                                    <div>Without this trigger, your lesson/topic/quiz/unit with Completion Tracking enabled will not be marked as complete if the content sends "completed" verb on completion.</div>

                                    <h3>Solution:</h3>
                                    <p>Create a trigger in the LRS with following configuration:</p>
                                    <ol>
                                        <li><b>Name:</b> All Completed</li>
                                        <li><b>Type:</b> Completion</li>
                                        <li><b>URL:</b> <code><?php echo admin_url('admin-ajax.php?action=grassblade_completion_tracking'); ?></code></li>
                                        <li><b>Verb:</b> completed</li>
                                        <li><b>Authority:</b> <?php echo $authority; ?></li>
                                        <li><b>Status:</b> ON</li>
                                    </ol>
                                    <a class="button-primary" href="<?php echo $lrs . "/Triggers"; ?>" target="_blank">Go to Triggers</a>
                                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                                </div>
                            </span></span>
                    </div>
                </div>
            </div>
            <div id="lrstest6" class="lrs-test">
                <b class="test-title">6. Statement Test </b>
                <div class="button-lrstest" onclick="statement_tests()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span> <span><span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>This test finds out if there are any "passed" or "completed" verb statements are present in the LRS for the selected xAPI Content and the user.</div>

                                <h3>Reason for Failure:</h3>
                                <div>
                                    <p>Common reasons for failure are:</p>
                                    <ol>
                                        <li>There is no statement sent to the LRS for that xAPI Content.</li>
                                        <li>There is no statement sent to the LRS for that xAPI Content and the selected user.</li>
                                        <li>In case of the above steps goes true then it checks "Original Activity ID" of the content and if that is not matching the current Activity ID, then I will check the statement for the original activity ID and the selected user.</li>
                                    </ol>
                                </div>

                                <h3>Impact:</h3>
                                <div>GrassBlade xAPI Content is not sending any statements to the LRS or completion tracking will not work.</div>

                                <h3>Solution:</h3>
                                <p>Go to the GrassBlade Settings page and check the run the "LRS connection test" if all the tests given below are failed.</p>
                                <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                            </div>
                        </span></span>
                </div>
                <div class="lrstest-diagram">
                    <span data-no="1" class="dashicons dashicons-dashboard"></span>
                    <span data-no="2" class="dashicons dashicons-minus"></span>
                    <span data-no="3" class="dashicons no-change dashicons-no"></span>
                    <span data-no="3" class="dashicons no-change dashicons-minus middle"></span>
                    <span data-no="3" class="dashicons no-change dashicons-yes"></span>
                    <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
                    <span data-no="5" class="dashicons dashicons-wordpress"></span>
                </div>
                <div class="sub-tests">
                    <div data-test-no="1" data-test-name="current_activity_statement" class="current_activity_statement sub-test">
                        <span class="dashicons dashicons-no"></span> <span class="test-title">Content Statement Test</span>
                        <span class="response"></span>
                        <span>
                            <span class="dashicons-before dashicons-info">
                                <div>
                                    <div class="statement">
                                        <h3>Found Statement</h3>
                                        <div>No Statement Found</div>
                                    </div>

                                    <h3>About:</h3>
                                    <div>This check search in the LRS to see if there are any "passed" or "completed" verb statements present for the selected xAPI Content.</div>

                                    <h3>Reason for Failure:</h3>
                                    <div>This test will fail if there are no statement sent to the LRS for the current xAPI Content.</div>

                                    <h3>Impact:</h3>
                                    <ul>
                                        <li>This test will fail if there are no "passed" or "completed" verb statements sent to the LRS for the current xAPI Content and the user.<li>
                                        <li>No user finished all the steps of the content or completed the content.</li>
                                    </ul>

                                    <h3>Solution:</h3>
                                    <div>Check whether your package is xAPI or SCORM or cmi5 compliant. Also run the LRS Connection Test from GrassBlade Settings page to see if your LRS is properly connected.</div>
                                    <br>
                                    <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                                </div>
                            </span>
                        </span>
                    </div>
                    <div data-test-no="2" data-test-name="current_user_activity_statement" class="current_user_activity_statement sub-test">
                        <span class="dashicons dashicons-no"></span> <span class="test-title">Statement - User & Current Activity ID</span>
                        <span class="message"></span>
                        <span class="response"></span>
                        <span>
                            <span class="dashicons-before dashicons-info">
                                <div>
                                    <div class="statement">
                                        <h3>Found Statement</h3>
                                        <div>No Statement Found</div>
                                    </div>

                                    <h3>About:</h3>
                                    <div>This check search in the LRS to see if there are any "passed" or "completed" verb statements present for the selected xAPI Content and the user.</div>

                                    <h3>Reason for Failure:</h3>
                                    <ul>
                                        <li>This test will fail if there are no "passed" or "completed" verb statements sent to the LRS for the current xAPI Content and the user.</li>
                                        <li>This user didn't finished all the steps of the content or completed the content.</li>
                                    </ul>

                                    <h3>Impact:</h3>
                                    <div>No learner activity is being recorded and completion tracking will not work.</div>

                                    <h3>Solution:</h3>
                                        <ul>
                                            <li>Check whether your package is xAPI or SCORM or cmi5 compliant.</li>
                                            <li>User must no be under some firewall. Ex. internal network of companies or comporates.</li>
                                            <li>Run the LRS Connection Test from GrassBlade Settings page to see if your LRS is properly connected.</li>
                                        </ul>
                                    <br>
                                    <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                                </div>
                            </span>
                        </span>
                    </div>
                    <div data-test-no="3" data-test-name="original_activity_statement" class="original_activity_statement sub-test">
                        <span class="dashicons dashicons-no"></span> <span class="test-title"> Statement - Original Activity ID</span>
                        <span class="message"></span>
                        <span class="response"></span>
                        <span><span class="dashicons-before dashicons-info">
                            <div>
                                <div class="statement">
                                    <h3>Found Statement</h3>
                                    <div>No Statement Found</div>
                                </div>

                                <h3>About:</h3>
                                <ul>
                                    This check search in the LRS to see if there are any "passed" or "completed" verb statements present for the selected xAPI Content's Original Activity ID.
                                    This test only runs when the Original Activity ID is present and not matching the current Activity ID.
                                </ul>

                                <h3>Reason for Failure:</h3>
                                <div>
                                    <li>This test will fail if there are no "passed" or "completed" verb statements sent to the LRS for the current xAPI Content.</li>
                                    <li>No user finished all the steps of the content or completed the content with Original Activity ID.</li>
                                </div>

                                <h3>Impact:</h3>
                                <div>No learner activity is being recorded and completion tracking will not work.</div>

                                <h3>Solution:</h3>
                                    <ul>
                                        <li>Check whether your package is xAPI or SCORM or cmi5 compliant.</li>
                                        <li>User must no be under some firewall. Ex. internal network of companies or comporates.</li>
                                        <li>Run the LRS Connection Test from GrassBlade Settings page to see if your LRS is properly connected.</li>
                                    </ul>
                                <br>
                                <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                            </div>
                            </span></span>
                    </div>
                    <div data-test-no="4" data-test-name="original_user_activity_statement" class="original_user_activity_statement sub-test">
                        <span class="dashicons dashicons-no"></span> <span class="test-title"> Statement - User & Original Activity ID</span>
                        <span class="message"></span>
                        <span class="response"></span>
                        <span>
                            <span class="dashicons-before dashicons-info">
                            <div>
                                <div class="statement">
                                    <h3>Found Statement</h3>
                                    <div>No Statement Found</div>
                                </div>

                                <h3>About:</h3>
                                <div>
                                    This check search in the LRS to see if there are any "passed" or "completed" verb statements present for the selected xAPI Content's Original Activity ID and the user.
                                    This test only runs when the Original Activity ID is present and not matching the current Activity ID.
                                </div>

                                <h3>Reason for Failure:</h3>
                                <ul>
                                    <li>This test will fail if there are no "passed" or "completed" verb statements sent to the LRS for the current xAPI Content and the user.</li>
                                    <li>No user finished all the steps of the content or completed the content with Original Activity ID.</li>
                                </ul>

                                <h3>Impact:</h3>
                                <div>No learner activity is being recorded and completion tracking will not work.</div>

                                <h3>Solution:</h3>
                                    <ul>
                                        <li>Check whether your package is xAPI or SCORM or cmi5 compliant.</li>
                                        <li>User must no be under some firewall. Ex. internal network of companies or comporates.</li>
                                        <li>Run the LRS Connection Test from GrassBlade Settings page to see if your LRS is properly connected.</li>
                                    </ul>
                                <br>
                                <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                            </div>
                            </span>
                        </span>
                    </div>
                    <div data-test-no="6" data-test-name="revision_activity_id_test" class="sub-test">
                        <span class="dashicons dashicons-no"></span> <span class="test-title"> Revision Activity ID Test </span>
                        <span class="response"></span>
                        <span><span class="dashicons-before dashicons-info">
                            <div>
                                <div class="statement">
                                    <h3>Found Statement</h3>
                                    <div>No Statement Found</div>
                                </div>

                                <h3>About:</h3>
                                <div>
                                    This check search in the LRS to see if there are any "passed" or "completed" verb statements present for the selected xAPI Content's Activity ID present in the revisions of the xAPI Content.
                                    This test only runs when the Revision's Activity IDs is present and not matching the current Activity ID.
                                </div>

                                <h3>Reason for Failure:</h3>
                                <ul>
                                    <li>This test will fail if there are no "passed" or "completed" verb statements sent to the LRS for the current xAPI Content's revision</li>
                                    <li>No user finished all the steps of the content or completed the content with Revision's Activity IDs.</li>
                                </ul>

                                <h3>Impact:</h3>
                                <div>It will not impact the setup but good to check and see if content's Activity ID changed or updated in the past.</div>

                                <h3>Solution:</h3>
                                    <ul>
                                        <li>No solution required.</li>
                                    </ul>
                                <br>
                                <a href="<?php echo admin_url("admin.php?page=grassblade-lrs-settings"); ?>" target="_blank" class="button-lrstest">Go to GrassBlade Settings</a></li>
                            </div>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            <div id="lrstest7" class="lrs-test">
                <b class="test-title">7. Error Log Test</b>
                <div class="button-lrstest" onclick="error_log_test()"><?php _e("Test", "grassblade") ?></div><br>
                <div>
                    <b>Compatible With:</b> <span>GrassBlade LRS v2.13.0+</span>
                </div>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span> <span><span class="dashicons-before dashicons-info">
                        <div>
                            <h3>About:</h3>
                            <div>This test fetch the triggers run in the LRS when the current user completed the content. So, you can can understand what happened when xAPI Content was completed.</div>

                            <h3>Reason for Failure:</h3>
                            <div>
                                <p>Common reasons for failure are:</p>
                                <ol>
                                    <li>Completion Tracking not enabled in the xAPI Content</li>
                                    <li>Content not completed by the current users.</li>
                                    <li>No passed/completed/failed verb statement recieved in the LRS for the selected content and the user. </li>
                                    <li><a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion triggers</a> not present in the LRS.</li>
                                </ol>
                            </div>

                            <h3>Impact:</h3>
                            <div>Completion tracking and it's related features will not work.</div>

                            <h3>Solution:</h3>
                            <ol>
                                <li>Check whether content is xAPI/SCORM/cmi5.</li>
                                <li>Check Completion Triggers are present in the LRS</li>
                                <li>Try to complete all the steps of the xAPI Content and check the LRS for passed/failed/completed verb statement in the LRS.</li>
                            </ol>
                            <br>
                            <a href="edit_page_url" target="_blank" class="button-lrstest">Go to Edit Page</a></li>
                        </div>
                    </span></span>
                </div>
                <div class="lrstest-diagram">
                    <span data-no="1" class="dashicons dashicons-dashboard"></span>
                    <span data-no="2" class="dashicons dashicons-minus"></span>
                    <span data-no="3" class="dashicons dashicons-no"></span>
                    <span data-no="3" class="dashicons dashicons-minus middle"></span>
                    <span data-no="3" class="dashicons dashicons-yes"></span>
                    <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
                    <span data-no="5" class="dashicons dashicons-wordpress"></span>
                </div>
                <span class="message"></span>
            </div>
            <?php if( !empty($related_users["all"]) && !empty($related_users["user"]) ) {
                $test_status = count($related_users["all"]) > 1 ? "failed" : "passed";
                ?>
                <div id="lrstest8" class="lrs-test">
                    <b class="test-title">8. GrassBlade Email Test:</b>
                    <div class="status_div">
                        <b>Status:</b><span class="<?php echo $test_status; ?>"> <?php echo ucwords($test_status); ?> | GrassBlade Email (<?php echo $related_users["grassblade_email"]; ?>) is asscociated with <?php echo count($related_users["all"]); ?> users. </span>
                        <span><span class="dashicons-before dashicons-info">
                            <div>
                                <h3>About:</h3>
                                <div>This test checks if the user's GrassBlade Email is associated with more than one user.</div>

                                <h3>What is GrassBlade Email?</h3>
                                <div>The user identifier in the LRS is usually the Email ID of the user. Changing email id may cause the reporting to be split up, so, we store the user's email id as GrassBlade Email ID and use that email id for storing any data in the LRS for the user.</div>

                                <h3>Reason for Failure:</h3>
                                <div> xAPI Standard needs the Email ID to be an Inverse Functional Identifier, that is guaranteed to only ever refer to that one user. </div>
                                <div> If there are more than one user's with same GrassBlade Email, this condition fails, and the LRS cannot pinpoint which of these users the data belongs to. </div>

                                <h3>Impact:</h3>
                                <div>If a user changed their email and created a new account on your site with the same email then completion tracking won't work for newly created user. </div>
                                <div>All completions for all users with same GrassBlade Email will be recorded under only one user.</div>
                                <div>All completion by these users will go to this user: <br>
                                    <small><i><?php echo "<a href='".admin_url("user-edit.php?user_id=".$related_users["user"]->ID)."' target='_blank'>".$related_users["user"]->display_name."</a> (User ID: ".$related_users["user"]->ID." | Email: ".$related_users["user"]->user_email.")"; ?></i></small>
                                </div>

                                <h3>Affected Users:</h3>
                                <?php
                                // dd($user_by_gb_email);
                                    $counter = 1;
                                    foreach($related_users["all"] as $u) {
                                        $name = $u->display_name;
                                        $name .= ( $related_users["user"]->ID == $u->ID )? "*" : "";
                                        echo "<b>$counter.</b> <a href='".admin_url('user-edit.php?user_id='.$u->ID)."' target='_blank'>$name</a> (User ID: ".$u->ID." | User Email: ".$u->user_email.")<br>";
                                        $counter++;
                                    }
                                ?>
                                <h3>Solution(s):</h3>
                                <p>Best solution would be to delete one of the users.</p>
                            </div>
                        </span></span><br>
                        <?php
                            $counter = 1;
                            foreach($related_users["all"] as $u) {
                                $name = $u->display_name;
                                $name .= ( $related_users["user"]->ID == $u->ID )? "*" : "";
                                echo "<b>$counter.</b> <a href='".admin_url('user-edit.php?user_id='.$u->ID)."' target='_blank'>$name</a> (User ID: ".$u->ID." | User Email: ".$u->user_email.")<br>";
                                $counter++;
                            }
                        ?>
                    </div>
                 </div>
            <?php } ?>
            <br><br>
            <div id="lrstest9" class="lrs-test">
                <b class="test-title">9. Active Registrations</b>
                <div class="button-lrstest" onclick="run_registrations_test()"><?php _e("Test", "grassblade") ?></div><br>
                <div class="status_div">
                    <b>Status:</b> <span class="status">Unknown</span> <span><span class="dashicons-before dashicons-info">
                        <div>
                            <h3>About:</h3>
                            <div>
                                This test displays the attempt registrations of the content for this user. <BR>
                                You can also change the current registration, this will change which attempt the user will resume to.<br>
                                As an admin, you can also launch the content as this user, and see the current state in the content.
                            </div>

                            <br>
                        </div>
                    </span></span>
                </div>
                <div class="lrstest-diagram">
                    <span data-no="1" class="dashicons dashicons-dashboard"></span>
                    <span data-no="2" class="dashicons dashicons-minus"></span>
                    <span data-no="3" class="dashicons dashicons-no"></span>
                    <span data-no="3" class="dashicons dashicons-minus middle"></span>
                    <span data-no="3" class="dashicons dashicons-yes"></span>
                    <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
                    <span data-no="5" class="dashicons dashicons-wordpress"></span>
                </div>
                <span class="message"></span>
                <span class="lrstest9_results"></span>
                <br>
                <br>
            </div>
        </div>
    </div>
    <div class="grassblade_test_lightbox" style="display: none;">
        <div class='grassblade_close'><a class='grassblade_close_btn' href='#' onClick='return grassblade_test_lightbox_close();'>X</a></div>
        <h1 class="test-title"></h1>
        <div class="test-info"></div>
    </div>
    <script type="text/javascript">
        //<![CDATA[
        var agent_id = "<?php echo $agent_id; ?>";
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        var wp_admin_url = "<?php echo admin_url('/'); ?>";
        //]]>
        jQuery(document).ready(function() {
            jQuery('.gb_select2_xapi_content').select2({
                width: "100%",
                placeholder: "Select xAPI Content",
            });
        });
        jQuery(window).on("load", function() {
            jQuery(".grassblade_lrstest .dashicons-before.dashicons-info").on("click", function() {
                grassblade_test_lightbox_show(this);
            });

            //<![CDATA[
            ADL.XAPIWrapper.changeConfig(<?php echo json_encode($config); ?>);
            //]]>

            jQuery(".grassblade_lrstest [data-test-name] .test-title").on("click", function() {
                var context = jQuery(this).parent();

                grassblade_reset_test(context);
                grassblade_start_test_message(context);
                setTimeout(function() {
                    var test_name = jQuery(context).data("test-name")
                    if (typeof window["grassblade_test_" + test_name] == "function")
                        window["grassblade_test_" + test_name](context);
                }, 500);
            });
        });
    </script>

</body>

</html>
