<div style="text-align: left; margin-bottom: 30px;">
	<img src="https://learncabana.com/wp-content/uploads/2023/06/cropped-cannabana-logo-1.png" alt="Canna Cabana" />
</div>
<div>
	{user_name},

	<p style="margin-bottom: 20px;">
		Here is your Report Dashboard Summary for {reportDate} for your team.
	</p>

	<p style="margin-bottom: 30px; padding: 12px 14px; background: #f5f5f5; border-left: 4px solid #2f6f4e;">
		<strong>Follow-up this week:</strong> {follow_up_note}
	</p>

	<table style="border-collapse: collapse; border: 1px solid #e6e6e6; width: 100%; max-width: 720px;">
		<tr>
			<th colspan="4" style="border: 1px solid #e6e6e6; padding: 16px 8px; text-align: left;">
				<h4 style="margin: 0; font-size: 16px;">Enrollments (Last Week)</h4>
			</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e4e4e4;">Enrolled</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e4e4e4;">Activity</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e4e4e4;">Minutes</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e4e4e4;">Completed</th>
		</tr>
		{lastWeekStats}
	</table>

	<br><br>

	<table style="border-collapse: collapse; border: 1px solid #e6e6e6; width: 100%; max-width: 720px;">
		<tr>
			<th colspan="5" style="border: 1px solid #e6e6e6; padding: 16px 8px; text-align: left;">
				<h4 style="margin: 0; font-size: 16px;">Enrollments (Total)</h4>
			</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Not Started</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">In Progress</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Warning</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Overdue</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Completed</th>
		</tr>
		{totalEnrollments}
	</table>

	<br><br>

	<table style="border-collapse: collapse; border: 1px solid #e6e6e6; width: 100%; max-width: 720px;">
		<tr>
			<th colspan="4" style="border: 1px solid #e6e6e6; padding: 16px 8px; text-align: left;">
				<h4 style="margin: 0; font-size: 16px;">Learning Plans &amp; Certifications</h4>
			</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;"></th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Met</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Warning</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Not Met</th>
		</tr>
		{learningPlans}
		{certifications}
	</table>

	<br><br>

	<table style="border-collapse: collapse; border: 1px solid #e6e6e6; width: 100%; max-width: 720px;">
		<tr>
			<th colspan="4" style="border: 1px solid #e6e6e6; padding: 16px 8px; text-align: left;">
				<h4 style="margin: 0; font-size: 16px;">Action Status</h4>
			</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;"></th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Accepted</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Review</th>
			<th style="background:#E6E6E6; padding:5px 10px; border: 1px solid #e6e6e6;">Pending</th>
		</tr>
		{actionStatus}
	</table>

	<br><br>

	<table style="border-collapse: collapse; border: 1px solid #e6e6e6; width: 100%; max-width: 960px;">
		<tr>
			<th colspan="8" style="border: 1px solid #e6e6e6; padding: 16px 8px; text-align: left;">
				<h4 style="margin: 0; font-size: 16px;">Most Active Groups</h4>
			</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;"></th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;"></th>
			<th colspan="4" style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6; text-align: center;">Enrollments</th>
			<th colspan="2" style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6; text-align: center;">Last Week</th>
		</tr>
		<tr>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Group</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Learners</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Not Started</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Active</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Warn</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">ODue</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Activity</th>
			<th style="background:#E6E6E6; padding:5px 8px; border: 1px solid #e6e6e6;">Cmpl</th>
		</tr>
		{activeGroups}
	</table>

	<br><br>

	<p>
		The first section shows your team's progress over the last week (enrollments, activity, minutes, and completions).<br><br>
		The second section shows overall progress. Click hyperlinked numbers to open reporting and review courses that are not started, in progress, in warning, or overdue:<br>
		<a href="{reporting_url}">Open User Report</a>
		<br><br>
		If you have team members who are onboarding, follow up on their progress. No new hires should be scheduled to work independently before completing onboarding in full. For access support, email cannabislearning@cannacabana.com.
	</p>
	<br>
	<p>
		Thank you for your work!<br>
		Cannabis Learning
	</p>
</div>
