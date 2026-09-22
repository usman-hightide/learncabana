/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!***************************************!*\
  !*** ./src/assets/js/admin-script.js ***!
  \***************************************/
/* eslint-disable -- TODO: will be removed later after JS updates. */
jQuery(document).ready(function ($) {
  var LD_Achievements = LD_Achievements || {};
  LD_Achievements.admin = {
    init() {
      this.toggle_child_input();
      this.select_image();
      this.settings_page();
      this.submit_disabled_fields();
      this.ajax_get_children_list();
    },
    toggle_child_input() {
      if ($('.ld_achievements_metabox_settings').length > 0) {
        $('select[name="trigger"]').change(function (e) {
          LD_Achievements.admin.update_select_values();
          const option_class = $(this).val();
          if (option_class === '') {
            return;
          }
          $('.sfwd_input.' + option_class).show();
          $('.sfwd_input.child-input').not('.' + option_class).hide();
          $('.sfwd_input.hide_on_' + option_class).hide();
        });
        $(window).load(function (e) {
          LD_Achievements.admin.update_select_values_onload();
          const option_class = $('select[name="trigger"]').val();
          if (option_class === '') {
            return;
          }
          $('.sfwd_input.' + option_class).show();
          $('.sfwd_input.child-input').not('.' + option_class).hide();
          $('.sfwd_input.hide-empty-select').hide();
        });
      }
    },
    select_image() {
      if ($('#image-field').length == 0) {
        return;
      }
      const image_field = $('#image-field');
      const image_preview_holder = $('#image-preview-holder');
      const image_preview = $('#image-preview-holder img');
      const image_selector_buttons = $('.image-selector-buttons');
      const icon_selection = $('.icon-selection');
      $(document).on('click', '.select-image-btn', function (e) {
        e.preventDefault();
        icon_selection.toggle();
      });
      $(window).load(function () {
        const image = $('#image-field').val();
        const icon = $('img.radio-btn[src="' + image + '"]');
        if (image.length === 0 && icon.length > 0) {
          icon.addClass('selected');
          $('.icon-selection').show();
        } else if (image.length > 0) {
          image_selector_buttons.hide();
          image_field.val(image);
          image_preview.attr('src', image);
          image_preview_holder.show();
        }
      });
      $(document).on('click', '.icon-selection .radio-btn', function (e) {
        e.preventDefault();
        $('.icon-selection input[type=radio]').removeAttr('checked');
        $('.icon-selection .radio-btn').removeClass('selected');
        $(this).prev().attr('checked', 'checked');
        $(this).addClass('selected');
        $('#image-field').val($(this).attr('src'));
      });
      let uploader;
      $(document).on('click', '#upload-image', function (e) {
        e.preventDefault();
        if (uploader) {
          uploader.open();
          return;
        }
        uploader = wp.media.frames.file_frame = wp.media({
          title: 'Choose Image',
          button: {
            text: 'Choose Image'
          },
          multiple: false
        });
        uploader.on('select', function () {
          attachment = uploader.state().get('selection').first().toJSON();
          image_field.val(attachment.url);
          image_preview.attr('src', attachment.url);
          image_preview_holder.show();
          $('.radio-btn.selected').removeClass('selected');
          image_selector_buttons.hide();
          icon_selection.hide();
        });
        uploader.open();
      });
      $(document).on('click', '#remove-image-btn', function (e) {
        e.preventDefault();
        image_preview_holder.hide();
        image_preview.attr('src', '');
        image_field.val('');
        image_selector_buttons.show();
      });
    },
    settings_page() {
      $('.color-picker').wpColorPicker();
    },
    submit_disabled_fields() {
      $('form').on('submit', function () {
        $(this).find(':input').prop('disabled', false);
      });
    },
    ajax_get_children_list() {
      $('.parent_field select').change(function (e) {
        const el = $(this);
        let parent_type = '';
        const val = $(this).val();
        const name = $(this).attr('name');
        switch (name) {
          case 'course_id':
            parent_type = 'course';
            break;
          case 'lesson_id':
            parent_type = 'lesson';
            break;
          case 'topic_id':
            parent_type = 'topic';
            break;
        }
        const course_id = $('select[name="course_id"]').val();
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'ld_achievements_get_children_list',
            course_id,
            parent_type,
            parent_id: val,
            nonce: LD_Achievements_Admin_Data.nonce
          }
        }).done(function (data) {
          let response = data;
          response = JSON.parse(response);
          if (el.attr('name').indexOf('course') != '-1') {
            $('select[name="topic_id"]').html('<option>' + LD_Achievements_Admin_Data.select_lesson_first + '</option>');
            $('select[name="quiz_id"]').html('<option>' + LD_Achievements_Admin_Data.select_topic_first + '</option>');
            $('select[name="lesson_id"]').html('<option>' + LD_Achievements_Admin_Data.select_lesson + '</option>' + '<option value="all">' + LD_Achievements_Admin_Data.all_lessons + '</option>');
            $.each(response, function (i, val) {
              $('select[name="lesson_id"]').append('<option value="' + i + '">' + val + '</option>');
            });
          }
          if (el.attr('name').indexOf('lesson') != '-1') {
            $('select[name="quiz_id"]').html('<option>' + LD_Achievements_Admin_Data.select_topic_first + '</option>');
            $('select[name="topic_id"]').html('<option>' + LD_Achievements_Admin_Data.select_topic + '</option>' + '<option value="all">' + LD_Achievements_Admin_Data.all_topics + '</option>');
            $.each(response, function (i, val) {
              $('select[name="topic_id"]').append('<option value="' + i + '">' + val + '</option>');
            });
          }
          if (el.attr('name').indexOf('topic') != '-1') {
            $('select[name="quiz_id"]').html('<option>' + LD_Achievements_Admin_Data.select_quiz + '</option>' + '<option value="all">' + LD_Achievements_Admin_Data.all_quizzes + '</option>');
            $.each(response, function (i, val) {
              $('select[name="quiz_id"]').append('<option value="' + i + '">' + val + '</option>');
            });
          }
        });
      });
    },
    update_select_values() {
      $('select[name="course_id"]').prop('selectedIndex', 0);
      $('select[name="lesson_id"]').html('<option>' + LD_Achievements_Admin_Data.select_course_first + '</option>');
      $('select[name="topic_id"]').html('<option>' + LD_Achievements_Admin_Data.select_lesson_first + '</option>');
      $('select[name="quiz_id"]').html('<option>' + LD_Achievements_Admin_Data.select_topic_first + '</option>');
    },
    update_select_values_onload() {
      if ($('select[name="course_id"]').val() === '') {
        $('select[name="course_id"]').prop('selectedIndex', 0);
      }
      if ($('select[name="lesson_id"]').val() === '') {
        $('select[name="lesson_id"]').html('<option>' + LD_Achievements_Admin_Data.select_course_first + '</option>');
      }
      if ($('select[name="topic_id"]').val() === '') {
        $('select[name="topic_id"]').html('<option>' + LD_Achievements_Admin_Data.select_lesson_first + '</option>');
      }
      if ($('select[name="quiz_id"]').val() === '') {
        $('select[name="quiz_id"]').html('<option>' + LD_Achievements_Admin_Data.select_topic_first + '</option>');
      }
    }
  };
  LD_Achievements.admin.init();
});
/******/ })()
;
//# sourceMappingURL=admin-script.js.map