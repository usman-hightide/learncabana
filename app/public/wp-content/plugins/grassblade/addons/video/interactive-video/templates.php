<template id="gb_tmpl_single_choice_option">
	<li >
		<div class="single_choice_option option">
		<input type="text" name="options[]" value="{{value}}" class="gb_choice_data" required>
		<input type="radio" name="correct_choice" class="gb_correct_choice" value='{{count}}' {{checked}}>
		<label>Correct</label>
		<i id="gb_remove_field" class="gb_remove_field dashicons dashicons-no" onclick="gb_remove_option(this)"></i>
		</div>
	</li>
</template>

<template id="gb_tmpl_multi_choice_option">
	<li>
		<div class="multi_choice_option option">
			<input type="text" name="options[]" value="{{value}}" required>
			<input type="checkbox" name="correct_choice[]" id="multi_choice_correct" value="{{count}}" {{checked}}>
			<label for="multi_choice_correct">Correct</label>
			<i id="gb_remove_field" class="gb_remove_field dashicons dashicons-no" onclick="gb_remove_option(this)"></i>
		</div>
	</li>
</template>

<template id="gb_tmpl_single_choice_question">
	<div class="gb_single_choice_question">
		<label> <?php echo __('Add Choices','grassblade')?></label>
		<ol class="single_choice_input gb_question_options"></ol>
		<button class="button button_secondary" id="add_new_option" onclick="add_question_option(this, 'single_choice'); return false;"><?php echo __('Add Option','grassblade')?></button>
		<br>
		<br>
	</div>
</template>

<template id="gb_tmpl_multi_choice_question" style="display: none;">
	<div class="gb_multi_choice_question">
		<label><?php echo __('Add Choices','grassblade')?></label>
		<ol class="multi_choice_input gb_question_options"></ol>
		<button class="button button_secondary" id="add_multi_choice" onclick="add_question_option(this, 'multi_choice'); return false;"><?php echo __('Add Option','grassblade')?></button>
		<br>
		<br>
	</div>
</template>
<template id="gb_tmpl_true_false_question" style="display: none;">
	<div class="gb_true_false_question">
		<h4><?php echo __('Select Correct Response','grassblade')?></h4>
		<div class="true_false_option">
			<input type="radio" name="correct_choice" value="1" {{checked:correct_choices.1}}>
			<span><?php echo __('True','grassblade')?></span>
		</div>
		<div class="true_false_option">
			<input type="radio" name="correct_choice" value="0" {{checked:correct_choices.0}} >
			<span><?php echo __('False','grassblade')?></span>
		</div>
	</div>
</template>
<template id="gb_tmpl_question_form">
	<span class="dashicons dashicons-arrow-up-alt2 gb_question_close" onclick="gb_question_close(this);"></span>
	<form action="" method="post" class="gb_question_builder" enctype="multipart/form-data">
		<span><b><?php echo __('Question ID:','grassblade')?></b> {{ID}}</span>
		<div class="question_type_selector">
			<label for="question_type"><?php echo __('Select Question Type','grassblade')?></label>
			<select name="question_type" class="gb_question_type" onchange="gb_question_type_changed(this);">
				<option value="">-- <?php echo __('Select','grassblade')?> --</option>
				<option value="single_choice" {{selected:question_type.single_choice}}><?php echo __('Single Choice','grassblade')?></option>
				<option value="multi_choice" {{selected:question_type.multi_choice}}><?php echo __('Multi-Choice','grassblade')?></option>
				<option value="true_false" {{selected:question_type.true_false}}><?php echo __('True/False','grassblade')?></option>
			</select>
		</div>
		<div class="gb_question" style="display: none;">
			<div class="gb_question_title">
				<label class="heading" for="question_title"><?php echo __('Question','grassblade')?></label>
				<br>
				<input type="text" name="question_title" class="gb_question_title_input" value="{{question}}" onkeyup="gb_question_validate(this)" required>
				<div class="gb_error_msg gb_question_validate"><?php echo __('Question is required.','grassblade')?></div>
			</div>

			<div class="gb_question_settings">

			</div>
			<div>
				<label for="timestamp"><?php echo __('Select Timestamp:','grassblade')?></label>
				<input type="text" pattern="[\d]{2}:[\d]{2}:[\d]{2}" name="timestamp" class="gb_timestamp" value="{{answer_settings.timestamp}}" placeholder="HH:MM:SS" onkeyup="gb_question_validate(this)" required>
				<div class="gb_error_msg gb_question_validate"><?php echo __('Timestamp needs to be in the format of HH:MM:SS','grassblade')?></div>
			</div>

			<input type="hidden" name="question_id" value="{{ID}}">

			<div class="gb_question_save">
				<input type="text" name="post_id" hidden value="<?php echo $content_id; ?>">
				<br><br>
				<div class="message"></div>
				<div class="gb_form_actions">
					<span class="button button_primary" class="submitbtn" onclick="gb_save_question(this);" ><?php echo __('Save Question','grassblade')?></span>
					<span class="button gb_button_delete" style="display: none;" onclick="gb_delete_question(this)"><?php echo __('Delete', 'grassblade'); ?></span>
				</div>
			</div>
		</div>
	</form>
</template>

<template id="gb_tmpl_question_list_item_new">
	<div class="gb_grid_1_col">
		<span onclick="gb_question_add_new(this);" ><span class="dashicons-before dashicons-plus"></span><?php echo __('Add new question', 'grassblade')?> </span>
	</div>
</template>

<template id="gb_tmpl_question_list_item">
	<div data-question_id="{{question_id}}">
		<span> {{question_id}} </span><span> {{question}} </span><span>{{question_type}}</span><span onclick="gb_question_goto(this)" class='gb_clickable'>{{answer_settings.timestamp}}</span><span class="dashicons-before dashicons-edit gb_clickable" onclick="gb_question_edit(this)"></span>
	</div>
</template>
