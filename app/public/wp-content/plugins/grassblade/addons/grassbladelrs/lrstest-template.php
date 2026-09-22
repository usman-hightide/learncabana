<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    $endpoint = $grassblade_settings["endpoint"];
    $lrs = rtrim(str_replace("/xAPI", "", $endpoint), "/");
    $authority = explode("-", $grassblade_settings["user"]);
    $authority = $authority[0];
?>
<link rel="stylesheet" media="all" href="<?php echo plugins_url( "style.css", __FILE__ ); ?>"/>
<div class="grassblade_lrstest" style="margin-top: 50px; margin-bottom: 50px;display: none;">
    <h1>LRS Connection Test</h1>
    <div id="lrstest1" class="lrs-test">
        <b>1. xAPI Authentication Settings</b> <div class="button-lrstest" onclick="grassblade_lrstest1_start()"><?php _e("Test", "grassblade") ?></div><br>
           <br>
        <div>
            <b>Compatible With:</b> <span>Any LRS</span>
        </div>

        <div class="status_div">
            <b>Status:</b> <span class="status">Unknown</span>
        </div>

        <div class="lrstest-diagram" onclick="grassblade_lrstest_anim(this, 'connecting');">
            <span data-no="1" class="dashicons dashicons-wordpress"></span>
            <span data-no="2" class="dashicons dashicons-minus"></span>
            <span data-no="3" class="dashicons dashicons-no"></span>
            <span data-no="3" class="dashicons dashicons-minus middle"></span>
            <span data-no="3" class="dashicons dashicons-yes"></span>
            <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
            <span data-no="5" class="dashicons dashicons-dashboard"></span>
        </div>
        <div class="sub-tests">
            <div data-test-no="1" data-test-name="post_statement" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> POST Statement</span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>Statements sent to the LRS can be send using HTTP POST, PUT or GET request methods.</div>

                    <h3>Reason for Failure:</h3>
                    <div>POST Request failure generally means the LRS is down.
                    </div>

                    <h3>Impact:</h3>
                    <div>Your xAPI Contents that use the LRS will not function correctly. Completions will stop.</div>

                    <h3>Solution: </h3>
                    <ol>
                        <li>You need to check if the LRS is functional. Also check if you are able to reach the <a href="<?php echo $lrs; ?>" target="_blank">LRS Dashboard</a> and run the reports.
                        <a href="<?php echo $lrs; ?>" target="_blank" class="button-primary">Go to LRS Dashboard</a></li>
                        <li>Make sure you are using the latest version of the LRS</li>
                        <li>If you are using installable GrassBlade LRS. Check this article for <a href="https://www.nextsoftwaresolutions.com/kb/grassblade-lrs-installation-error/" target="_blank">fixing common installation issues.</a></li>
                    </ol>
                </div></span></span>
            </div>
            <div data-test-no="2" data-test-name="get_statement" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> GET Statement</span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>GET Statements request is used to fetch the data from this LRS.</div>

                    <h3>Reason for Failure:</h3>
                    <div>GET Request failure generally means the LRS is down. Or the request is blocked by your LRS settings.</div>

                    <h3>Impact:</h3>
                    <div>You will not be able to view the statements in Statement Viewer.</div>

                    <h3>Solution: </h3>
                    <ol>
                        <li>You need to check if the LRS is functional. Also check if you are able to reach the <a href="<?php echo $lrs; ?>" target="_blank">LRS Dashboard</a> and run the reports.
                        <a href="<?php echo $lrs; ?>" target="_blank" class="button-primary">Go to LRS Dashboard</a></li>
                        <li>Make sure you are using the latest version of the LRS</li>
                        <li>If you are using installable GrassBlade LRS. Check this article for <a href="https://www.nextsoftwaresolutions.com/kb/grassblade-lrs-installation-error/" target="_blank">fixing common installation issues.</a></li>
                    </ol>
                </div></span></span>
            </div>
            <div data-test-no="3" data-test-name="put_state" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> PUT State</span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>Some xAPI requests like storing the resume information (State Request) also uses PUT Request method. </div>

                    <h3>Reason for Failure:</h3>
                    <div>It might fail if your server blocks PUT requests. </div>

                    <h3>Impact:</h3>
                    <div>Depends on the content and authoring tool. The content may not play, or it may play but not resume when you come back later.</div>

                    <h3>Solution:</h3>
                    <p> You might need to talk to your host or IT department. This commonly happens in IIS Server if you have WebDav enabled.</p>
                    </div></span></span>
            </div>
            <div data-test-no="4" data-test-name="get_state" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> GET State</span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>This request is used to read back the stored state data, like resume data. </div>

                    <h3>Reason for Failure:</h3>
                    <div>GET Request failure generally means the LRS is down. Or the request is blocked by your LRS settings.</div>

                    <h3>Impact:</h3>
                    <div>Depends on the content and authoring tool. The content may not play, or it may play but not resume when you come back later.</div>

                    </div></span></span>
            </div>
        </div>
        <br><br>
    </div>

    <div id="lrstest2" class="lrs-test">
        <b class="test-title">2. WordPress REST API Configuration in GrassBlade LRS</b> <div class="button-lrstest" onclick="grassblade_lrstest2_start()"><?php _e("Test", "grassblade") ?></div><br>
        <div>
            <b>Compatible With:</b> <span>GrassBlade LRS v2.6.0+</span>
        </div>
        <div class="status_div">
            <b>Status:</b> <span class="status">Unknown</span> <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>WordPress REST API integration is used to connect LRS with WordPress for several features like Getting Course Structure and Groups from LearnDash.</div>

                    <h3>Reason for Failure:</h3>
                    <div>
                        <p>Common reasons for failure are:</p>
                        <ol>
                            <li>This test will fail if you are using GrassBlade LRS version lower than 2.6.0, or any other LRS.</li>
                            <li>This test will fail if the WordPress URL, user and password is not configured in the LRS.</li>
                            <li>Username/Password configured is not correct, or was changed later.</li>
                            <li>WordPress JSON REST API is blocked by some plugin on WordPress.</li>
                        </ol>
                    </div>

                    <h3>Impact:</h3>
                    <div>LRS will not be able to pull Course Structure and Groups data from WordPress. And the same cannot be used in reports and filtering.</div>

                    <h3>Solution:</h3>
                    <p>Go to the GrassBlade LRS > Configure > Integrations and check your settings:</p>
                     <a href="<?php echo $lrs; ?>/Configure/Integrations" target="_blank" class="button-primary">Go to LRS Integrations</a></li>
                </div></span></span>
        </div>

        <div class="lrstest-diagram">
            <span data-no="1" class="dashicons dashicons-wordpress"></span>
            <span data-no="2" class="dashicons dashicons-minus"></span>
            <span data-no="3" class="dashicons dashicons-no"></span>
            <span data-no="3" class="dashicons dashicons-minus middle"></span>
            <span data-no="3" class="dashicons dashicons-yes"></span>
            <span data-no="4" class="dashicons dashicons-arrow-right-alt"></span>
            <span data-no="5" class="dashicons dashicons-dashboard"></span>
        </div>

    </div>

    <div id="lrstest3" class="lrs-test">
        <b>3. Completion Triggers in GrassBlade LRS</b> <div class="button-lrstest" onclick="grassblade_lrstest3_start()"><?php _e("Test", "grassblade") ?></div><br>
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
            <div class="verb_attempted sub-test"><span class="dashicons dashicons-yes"></span> <span class="dashicons dashicons-no"></span> <span class="test-title">Attempted Trigger</span>
                <span><span class="dashicons-before dashicons-info"><div>
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
                    <a class="button-primary" href="<?php echo $lrs."/Triggers"; ?>" target="_blank">Go to Triggers</a>
                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                </div></span></span>
            </div>
            <div class="verb_passed sub-test"><span class="dashicons dashicons-yes"></span> <span class="dashicons dashicons-no"></span> <span class="test-title">Passed Trigger</span>
                <span><span class="dashicons-before dashicons-info"><div>
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
                    <a class="button-primary" href="<?php echo $lrs."/Triggers"; ?>" target="_blank">Go to Triggers</a>
                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                </div></span></span>
            </div>
            <div class="verb_failed sub-test"><span class="dashicons dashicons-yes"></span> <span class="dashicons dashicons-no"></span> <span class="test-title">Failed Trigger</span>
                <span><span class="dashicons-before dashicons-info"><div>
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
                    <a class="button-primary" href="<?php echo $lrs."/Triggers"; ?>" target="_blank">Go to Triggers</a>
                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                </div></span></span>
            </div>
            <div class="verb_completed sub-test"><span class="dashicons dashicons-yes"></span> <span class="dashicons dashicons-no"></span> <span class="test-title">Completed Trigger</span>
                <span><span class="dashicons-before dashicons-info"><div>
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
                    <a class="button-primary" href="<?php echo $lrs."/Triggers"; ?>" target="_blank">Go to Triggers</a>
                    <p>For more details check this article on <a href="https://www.nextsoftwaresolutions.com/kb/using-grassblade-completion-tracking-with-learndash/" target="_blank">Completion Tracking</a></p>
                </div></span></span>
            </div>
        </div>
    </div>
    <div id="lrstest4" class="lrs-test">
        <b>4. Additional Tests for Common Issues</b> <div class="button-lrstest" onclick="grassblade_lrstest4_start()"><?php _e("Test", "grassblade") ?></div><br>
            <br>
        <div>
            <b>Compatible With:</b> <span>Any LRS</span>
        </div>

        <div class="status_div">
            <b>Status:</b> <span class="status">Unknown</span>
        </div>
        <br><br>
        <div class="sub-tests">
            <div data-test-no="1" data-test-name="wp_mod_security" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> Mod Security (WordPress) </span><span class="response"></span>  <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>Mod Security is a security module of Apache with complicated configurations. These configurations decide to block some requests while allowing other requests which are required.  </div>

                    <h3>Reason for Failure:</h3>
                    <div>Experience API (xAPI) Specification uses IDs in the format of a URL. These IDs (in the format of URLs) are used when launching the content. They are also sent during the requests to store and retrieve data in the LRS. Some hosts configure Mod Security to block requests that pass a URL as a parameter in the URL.
                    </div>

                    <h3>Impact:</h3>
                    <div>Most of these requests including xAPI content launch will get blocked by it and you will see a security error if your server has Mod Security blocking these requests</div>

                    <h3>Solution:</h3>
                    <p>You will need to contact your host to modify the Mod Security configuration to allow URL as a parameter in the URL.</p>
                    <p>Please also check and send the following two URLs:</p>
                    <ol>
                        <li><a href="<?php echo get_bloginfo("url")."?a=http://google.com"; ?>" target="_blank"><?php echo get_bloginfo("url")."?a=http://google.com"; ?></a> - On issue, this will give an error.</li>
                        <li><a href="<?php echo get_bloginfo("url")."?a=google.com"; ?>" target="_blank"><?php echo get_bloginfo("url")."?a=google.com"; ?></a> - This will work fine</li>
                    </ol>
                    <p>For more details please <a href="https://www.nextsoftwaresolutions.com/kb/403-forbidden-error-in-xapi-content/" target="_blank">check this article.</a></p>
                </div></span></span></div>
            <div data-test-no="2" data-test-name="lrs_mod_security" class="sub-test"><span class="dashicons dashicons-minus"></span> <span class="test-title"> Mod Security (LRS) </span><span class="response"></span>  <span>
                <span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>Mod Security is a security module of Apache with complicated configurations. These configurations decide to block some requests while allowing other requests which are required.  </div>

                    <h3>Reason for Failure:</h3>
                    <div>Experience API (xAPI) Specification uses IDs in the format of a URL. These IDs (in the format of URLs) are used when launching the content. They are also sent during the requests to store and retrieve data in the LRS. Some hosts configure Mod Security to block requests that pass a URL as a parameter in the URL.
                    </div>

                    <h3>Impact:</h3>
                    <div>Most of these requests including xAPI content launch will get blocked by it and you will see a security error if your server has Mod Security blocking these requests</div>

                    <h3>Solution:</h3>
                    <p>You will need to contact your LRS host to modify the Mod Security configuration to allow URL as a parameter in the URL.</p>
                    <p>Please also check and send the following two URLs:</p>
                    <ol>
                        <li><a href="<?php echo $endpoint."about?a=http://google.com"; ?>" target="_blank"><?php echo $endpoint."about?a=http://google.com"; ?></a> - On issue, this will give an error.</li>
                        <li><a href="<?php echo $endpoint."about?a=google.com"; ?>" target="_blank"><?php echo $endpoint."about?a=google.com"; ?></a> - This will always work fine</li>
                    </ol>
                    <p>For more details please <a href="https://www.nextsoftwaresolutions.com/kb/403-forbidden-error-in-xapi-content/" target="_blank">check this article.</a></p>
                </div></span>
            </span></div>
            <div data-test-no="3" data-test-name="put_state" class="sub-test">
                <span class="dashicons dashicons-minus"></span> <span class="test-title"> PUT Request </span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>Content built using some authoring tools use HTTP PUT Request method to send data to the LRS. Additionally, some xAPI requests like storing the resume information (State Request) also uses PUT Request method. </div>

                    <h3>Reason for Failure:</h3>
                    <div>It might fail if your server blocks PUT requests. </div>

                    <h3>Impact:</h3>
                    <div>Depends on the content and authoring tool. The content may not play, or it may play but not resume when you come back later.</div>

                    <h3>Solution:</h3>
                    <p> You might need to talk to your host or IT department. This commonly happens in IIS Server if you have WebDav enabled.</p>

                    </div></span>
                </span>
            </div>
            <div data-test-no="3" data-test-name="lms_check" class="sub-test">
                <span class="dashicons dashicons-minus"></span> <span class="test-title"> LMS Integration Check </span> <span class="response"></span>
                <span><span class="dashicons-before dashicons-info"><div>
                    <h3>About:</h3>
                    <div>This test checks if appropriate integration addon is installed for a supported installed LMS.</div>

                    <h3>Reason for Failure:</h3>
                    <div>A supported LMS is installed but additional integration addon is not installed.</div>

                    <h3>Impact:</h3>
                    <div>Completion Tracking and other features specific to LMS integration doesn't work.</div>

                    <h3>Solution:</h3>
                    <p> Need to install the appropriate addon.</p>

                    </div></span>
                </span>
            </div>
        </div>
        <br><br>
    </div>
    <div class="grassblade_test_lightbox" style="display: none;">
        <div class='grassblade_close'><a class='grassblade_close_btn' href='#' onClick='return grassblade_test_lightbox_close();'>X</a></div>
        <h1 class="test-title"></h1>
        <div class="test-info"></div>
    </div>
    <script type="text/javascript">
        jQuery(window).on("load", function() {
            //<![CDATA[
            ADL.XAPIWrapper.changeConfig(<?php echo json_encode($config); ?>);
            //]]>

            jQuery(".grassblade_lrstest [data-test-name] .test-title").on("click", function() {
                var context = jQuery(this).parent();

                grassblade_reset_test(context);
                grassblade_start_test_message(context);
                setTimeout(function() {
                    var test_name = jQuery(context).data("test-name")
                    if(typeof window["grassblade_test_" + test_name] == "function")
                        window["grassblade_test_" + test_name](context);
                }, 500);
            });

            jQuery(".grassblade_lrstest .dashicons-before.dashicons-info").on("click", function() {
                grassblade_test_lightbox_show(this);
            });
        });
    </script>
</div>
