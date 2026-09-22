<template id="gb_tmpl_show_question_option">
	<div class="question_option">
		<input id="{{question_id}}_option_{{option_index}}" type="{{input_type}}" name="options[]" class="choice_input" value="{{option_index}}">
		<label for="{{question_id}}_option_{{option_index}}">{{option_value}}</label>
	</div>
</template>

<template id="gb_tmpl_show_question_true_false_options">
	<div class="question_option">
		<input id="{{question_id}}_option_true" type="radio" name="options[]" class="choice_input" value="1">
		<label for="{{question_id}}_option_true"><?php echo __('True','grassblade')?></label>
	</div>
	<div class="question_option">
		<input id="{{question_id}}_option_false" type="radio" name="options[]" class="choice_input" value="0">
		<label for="{{question_id}}_option_false"><?php echo __('False','grassblade')?></label>
	</div>
</template>

<template id="gb_tmpl_show_question">
	<div data-question-id="{{question_id}}">
		<form action="save_answer" method="post">
				<h2 class="question_title"> {{question_title}} </h2>
				<div class="question_options"></div>
				<small id="gb_error_message" style="display: none;"><?php echo __('Please select a response.','grassblade')?></small>
				<input hidden type="text" name="question_id" value="{{question_id}}" class="question_id">
			<input hidden type="text" name="content_id" value="{{content_id}}" >
			<div class="gb_form_actions">
				<span class="gb_quiz_button" onClick="gb_submit_response()"><?php echo __('Submit','grassblade')?></span>
			</div>
		</form>
	</div>
</template>