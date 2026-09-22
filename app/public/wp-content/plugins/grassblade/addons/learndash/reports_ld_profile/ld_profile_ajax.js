function gb_learndash_ld30_show_user_statistic( e ) {
	e.preventDefault();

	var refId 				= 	jQuery( this ).data( 'ref-id' );
	var quizId 				= 	jQuery( this ).data( 'quiz-id' );
	var userId 				= 	jQuery( this ).data( 'user-id' );
	var statistic_nonce 	= 	jQuery( this ).data( 'statistic-nonce' );
	var post_data = {
		action: 'wp_pro_quiz_admin_ajax_statistic_load_user',
		func: 'statisticLoadUser',
		data: {
			quizId: quizId,
			userId: userId,
			refId: refId,
			statistic_nonce: statistic_nonce,
			avg: 0,
		},
	};

	jQuery( '#wpProQuiz_user_overlay, #wpProQuiz_loadUserData' ).show();
	var content = jQuery( '#wpProQuiz_user_content' ).hide();

	//console.log('- learndash.js');

	jQuery.ajax( {
		type: 'POST',
		url: ldVars.ajaxurl,
		dataType: 'json',
		cache: false,
		data: post_data,
		error: function( jqXHR, textStatus, errorThrown ) {
		},
		success: function( reply_data ) {
			if ( 'undefined' !== typeof reply_data.html ) {
				content.html( reply_data.html );
				jQuery( '#wpProQuiz_user_content' ).show();

				//console.log('trigger event change - learndash.js');
				jQuery( 'body' ).trigger( 'learndash-statistics-contentchanged' );

				jQuery( '#wpProQuiz_loadUserData' ).hide();

				content.find( '.statistic_data' ).on( 'click', function() {
					jQuery( this ).parents( 'tr' ).next().toggle( 'fast' );

					return false;
				} );
			}
		},
	} );

	jQuery( '#wpProQuiz_overlay_close' ).on( 'click', function() {
		jQuery( '#wpProQuiz_user_overlay' ).hide();
	} );
}
jQuery( '.learndash-wrapper' ).on( 'click', 'a.user_statistic', gb_learndash_ld30_show_user_statistic );
grassblade_enable_quiz_report_links();