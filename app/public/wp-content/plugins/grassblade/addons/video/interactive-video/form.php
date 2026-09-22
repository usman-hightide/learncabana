<div id="gb_questions_form">
	<div id="gb_question_list">
		<div class="gb_grid_1_col">
			<span>Loading...</span>
		</div>
	</div>
</div>
<div class="video_section">
	<div class="gb_video">
	<div class="gb_content_preview grassblade_preview_button">
			<?php
				$src = grassblade(array("id" => $content_id, "target" => "url"));
				echo grassblade_lightbox($src, "", __("Preview", "grassblade"), "100%", "100%", 1.77);
 				//echo do_shortcode("[grassblade id=".$content_id." target=lightbox show_results=0 version=none class='grassblade_preview_button' width='95%' aspect='1.77' button_type='0' text='Preview'] ") ;
			 ?>
	</div>
	</div>
</div>