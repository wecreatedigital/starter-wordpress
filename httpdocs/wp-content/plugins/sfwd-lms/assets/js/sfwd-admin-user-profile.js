jQuery( document ).ready(function( $ ) {

	// Handles showing the delete data options when the checkbox is set. 
	jQuery('#learndash_delete_user_data input#learndash_delete_user_data_checkbox').change(function () {
		jQuery('#learndash_delete_user_data #learndash_delete_user_data_options').toggle(this.checked);
	}).change(); //ensure visible state matches initially
	
	
	jQuery('#learndash_delete_user_data select#learndash_specific_delete_user_options_course').change(function () {
		var selected_course_id = jQuery(this).val();
		console.log('selected_course_id[%o]', selected_course_id);
		
		var post_data = {
			'action': 'learndash_user_profile_selected_course',
			'selected_course_id': selected_course_id
		};
		//console.log('post_data[%o]', post_data);
		
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			cache: false,
			data: post_data,
			error: function(jqXHR, textStatus, errorThrown ) {
				//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				console.log('error [%o]', textStatus);
			},
			success: function(reply_data) {
				//console.log('reply_data[%o]', reply_data);
				if (reply_data['courses'] != undefined) {
					jQuery('#learndash_delete_user_data select#learndash_specific_delete_user_options_course').empty().append(reply_data['courses']);
				}

				if (reply_data['lessons'] != undefined) {
					jQuery('#learndash_delete_user_data select#learndash_specific_delete_user_options_lesson').empty().append(reply_data['lessons']);
				} 

				if (reply_data['lessons'] != undefined) {
					jQuery('#learndash_delete_user_data select#learndash_specific_delete_user_options_topic').empty().append(reply_data['topics']);
				}

				if (reply_data['lessons'] != undefined) {
					jQuery('#learndash_delete_user_data select#learndash_specific_delete_user_options_quiz').empty().append(reply_data['quizzes']);
				}
			}
		});
		
	});
});
