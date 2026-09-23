<div style="text-align: left; margin-bottom: 30px;">
    <img src="https://learncabana.com/wp-content/uploads/2023/06/cropped-cannabana-logo-1.png" />
</div>
<div class="">
    {user_name},

    <p style="margin-bottom: 30px;">
        Here is your weekly learning follow-up summary for {reportDate} for your team.
    </p>

    <p style="margin-bottom: 30px; padding: 12px 14px; background: #f5f5f5; border-left: 4px solid #2f6f4e;">
        <strong>Follow-up this week:</strong> {follow_up_note}
    </p>

    <table class="border: 1px solid #e6e6e6;">
        <tr>
            <th colspan="3" style="border: 1px solid #e6e6e6;padding: 20px 5px;"><h4 style="font-weight: bold;font-size: 16px;text-align: left;margin: 0px;">Enrollments(Last Week)</h4></th>
        </tr>
        <tr>
            <th style="background:#E6E6E6; padding:5px 10px; border-left: 1px solid #e4e4e4;border-top: 1px solid #e4e4e4;border-bottom: 1px solid #e4e4e4;">Enrolled</th>
            <th style="background:#E6E6E6; padding:5px 10px;border-top: 1px solid #e4e4e4;border-bottom: 1px solid #e4e4e4;;">Activity</th>
            <th style="background:#E6E6E6; padding:5px 10px;border-top: 1px solid #e4e4e4;border-bottom: 1px solid #e4e4e4;border-right: 1px solid #e4e4e4">Completed</th>
        </tr>
        {lastWeekStats}
    </table>
    <br>
    <br>
    <br>
    <table class="border: 1px solid #e6e6e6;">
        <tr>
            <th colspan="3" style="border: 1px solid #e6e6e6;padding: 20px 5px;"><h4 style="font-weight: bold;font-size: 16px;text-align: left;margin: 0px;">Enrollments(Total)</h4></th>
        </tr>
        <tr>
            <th style="background:#E6E6E6; padding:5px 10px; border-left: 1px solid #e6e6e6;">Not Started</th>
            <th style="background:#E6E6E6; padding:5px 10px;">In Progress</th>
            <th style="background:#E6E6E6; padding:5px 10px;border-right: 1px solid #e6e6e6;">Completed</th>
        </tr>
        {totalEnrollments}
    </table>
    <br><br><br>
    <table class="border: 1px solid #e6e6e6;">
        <tr>
            <th colspan="3" style="border: 1px solid #e6e6e6;padding: 20px 5px;"><h4 style="font-weight: bold;font-size: 16px;text-align: left;margin: 0px;">Learning Plans</h4></th>
        </tr>
        <tr>
            <th style="background:#E6E6E6; padding:5px 10px; border-left: 1px solid #e6e6e6;"></th>
            <th style="background:#E6E6E6; padding:5px 10px;">Met</th>
            <th style="background:#E6E6E6; padding:5px 10px;border-right: 1px solid #e6e6e6;">Not Met</th>
        </tr>
        {learningPlans}
    </table>
    <br><br><br>
    <table class="border: 1px solid #e6e6e6;">
        <tr>
            <th colspan="3" style="border: 1px solid #e6e6e6;padding: 20px 5px;"><h4 style="font-weight: bold;font-size: 16px;text-align: left;margin: 0px;">Action Status</h4></th>
        </tr>
        <tr>
            <th style="background:#E6E6E6; padding:5px 10px; border-left: 1px solid #e6e6e6;"></th>
            <th style="background:#E6E6E6; padding:5px 10px;">Accepted</th>
            <th style="background:#E6E6E6; padding:5px 10px;border-right: 1px solid #e6e6e6;">Pending</th>
        </tr>
        {actionStatus}
    </table>
    <br>
    <br>
    <br/>

    <table class="border: 1px solid #e6e6e6;">
        <tr>
            <th colspan="7" style="border: 1px solid #e6e6e6;padding: 20px 5px;"><h4 style="font-weight: bold;font-size: 16px;text-align: left;margin: 0px;">Most Active Groups</h4></th>
        </tr>
        <tr>
            <th colspan="1" style="background:#E6E6E6; padding: 5px 10px; border-width: 0px 0px 1px 0px;"><h4 style="font-weight: bold;  font-size: 16px;"> </h4></th>
            <th colspan="4" style="background:#E6E6E6; padding: 5px 10px; border-width: 0px 0px 1px 0px;"><h4 style="font-weight: bold;  font-size: 16px;">Enrollments</h4></th>
            <th colspan="2" style="background:#E6E6E6; padding: 5px 10px; border-width: 0px 0px 1px 0px;"><h4 style="font-weight: bold;  font-size: 16px;">Last Week</h4></th>
        </tr>
        <tr>
            <th style="background:#E6E6E6; padding:5px 10px; border-left: 1px solid #e6e6e6;">Group</th>
            <th style="background:#E6E6E6; padding:5px 10px;">Learner</th>
            <th style="background:#E6E6E6; padding:5px 10px;">Not Started</th>
            <th style="background:#E6E6E6; padding:5px 10px;">Active</th>
            <th style="background:#E6E6E6; padding:5px 10px;">Cmpl</th>
            <th style="background:#E6E6E6; padding:5px 10px;">Activity</th>
            <th style="background:#E6E6E6; padding:5px 10px; border-right: 1px solid #e6e6e6;">cmpl</th>
        </tr>
        {activeGroups}
    </table>
    <br>
    <br>
    <br/>
    <p>The first section shows your team's progress over the last week (enrollments, activity, and completions).<br><br>
        The second section shows overall progress. Use the Reports Dashboard to review incomplete learning in more detail:<br>
        <a href="https://learncabana.com/reporting-dashboard-2/?tab=userReportTab">Open User Report</a>
        <br><br>
        No new hires should be scheduled to work independently before completing onboarding in full. For access support, email cannabislearning@cannacabana.com.
    </p>
    <br><br>
    <p>
        Thank you for your work! <br>
        Cannabis Learning
    </p>
</div>
