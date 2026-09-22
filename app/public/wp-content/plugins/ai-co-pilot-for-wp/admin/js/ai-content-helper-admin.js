(function( $ ) {
	'use strict';
	jQuery( document ).ready(function() {
		$('.aich_prompt_repeater .csf-repeater-wrapper .csf-repeater-item').each(function( index, item ) {
			let promptTitle = $(this).find('.csf-field.csf-field-text .csf-fieldset input').val();
			$(this).find('.csf-field-heading').append(promptTitle);
			$('.aich_prompt_repeater .csf-repeater-wrapper .csf-repeater-item .csf-repeater-content div').not(":first-child").hide();
		});

		$('.aich_prompt_repeater .csf-repeater-wrapper .csf-repeater-item .csf-repeater-content .csf-field-heading').click(function () {
			$('.aich_prompt_repeater .csf-repeater-wrapper .csf-repeater-item .csf-repeater-content div').not(":first-child").hide();
			$(this).parent().find('div').fadeIn();
		});

		$('.aich_select_language select').on('change', function() {
			let langText = $(this).parent().find('span').text();
			$(this).parents('.csf-cloneable-item').find('h4.csf-cloneable-title .csf-cloneable-text').text('');
			$(this).parents('.csf-cloneable-item').find('h4.csf-cloneable-title .csf-cloneable-text').append(langText);
			$(this).parents('.csf-cloneable-item').find('.group_label input').val(langText);
		});

		$(document).on('click', '.category_heading', function() {
			// $('.wp_ai_prompts_list').hide();
			$(this).toggleClass("active");
			$(this).parent().find('.wp_ai_prompts_list').slideToggle('fast');
			return false;
		});
	});
})( jQuery );
