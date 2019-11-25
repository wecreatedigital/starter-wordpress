jQuery(document).ready(function(){
	jQuery('table#learndash-data-upgrades button.learndash-data-upgrades-button').click(function(e) {
		e.preventDefault();

		var parent_tr 	= jQuery(this).parents('tr')
		var data_nonce 	= jQuery(this).attr('data-nonce');
		var data_slug 	= jQuery(this).attr('data-slug');

		var continue_checked = jQuery('.learndash-data-upgrades-continue input[type="checkbox"]', parent_tr).prop('checked');
		if (typeof continue_checked == 'undefined') {
			continue_checked = false;
		}

		var mismatched_checked = false;
		if (jQuery('.learndash-data-upgrades-mismatched input[type="checkbox"]', parent_tr).length) {
			mismatched_checked = jQuery('.learndash-data-upgrades-mismatched input[type="checkbox"]', parent_tr).prop('checked');
			if (typeof mismatched_checked === 'undefined') {
				mismatched_checked = false;
			}
		}

		var process_quiz = false;
		var proquiz_prefix = false;
		//var proquiz_rename = false;
		if (data_slug === 'rename-wpproquiz-tables') {
			jQuery(e.currentTarget).hide();
			jQuery('div#learndash-data-upgrades-rename-wpproquiz-tables-show-tables-list table', parent_tr).hide();
			
			jQuery('button.learndash-data-upgrades-button-'+data_slug+'-reload', parent_tr).show();
			jQuery('button.learndash-data-upgrades-button-' + data_slug + '-reload', parent_tr).click( function(){
				window.location.reload(true);
				return false;
			});
			
			if (jQuery('input[name="learndash-data-upgrades-quiz"]', parent_tr).length) {
				process_quiz = jQuery('input[name="learndash-data-upgrades-quiz"]', parent_tr).val();
				if (typeof process_quiz === 'undefined') {
					process_quiz = false;
				}
			}
		
			if (jQuery('input[name="learndash-data-upgrades-prefix"]', parent_tr).length) {
				var proquiz_prefix_selected = jQuery('input[name="learndash-data-upgrades-prefix"]:checked', parent_tr);
				if ((typeof proquiz_prefix_selected !== 'undefined') && (proquiz_prefix_selected.length > 0 )) {
					jQuery('input[name="learndash-data-upgrades-prefix"]', parent_tr).attr('disabled', true );
					//var current_prefix = jQuery(proquiz_prefix_selected).data('current-prefix');
					var current_prefix_selected = jQuery(proquiz_prefix_selected).val();
					//if (current_prefix === current_prefix_selected ) {
					//	return false;
					//} else {
						proquiz_prefix = current_prefix_selected;
					//}
				}
			}
			/*
			if (jQuery('input[name="learndash-data-upgrades-rename"]', parent_tr).length) {
				var rename = jQuery('input[name="learndash-data-upgrades-rename"]:checked', parent_tr);
				if ((typeof rename !== 'undefined') && ( rename.length > 0 ) ) {
					proquiz_rename = 1;
				}
			}
			*/
		}

		// Hide the Continue option.
		jQuery('.learndash-data-upgrades-continue', parent_tr).hide();	

		// Close all other progress meters
		jQuery('table#learndash-data-upgrades .learndash-data-upgrades-status').hide();
				
		// disable all other buttons
		jQuery('table#learndash-data-upgrades button.learndash-data-upgrades-button').prop('disabled', true);
		
		var post_data = {
			'action': 'learndash-data-upgrades',
			'data': {
				'init': 1,
				'nonce': data_nonce,
				'slug': data_slug,
				'continue': continue_checked,
				'mismatched': mismatched_checked,
				'quiz': process_quiz,
				'proquiz_prefix': proquiz_prefix,
				//'proquiz_rename': proquiz_rename
			}
		}
		//console.log('post_data[%o]', post_data);
		
		learndash_data_upgrades_do_ajax( post_data, parent_tr );
	});

	if ( jQuery('table#learndash-data-upgrades tr#learndash-data-upgrades-container-rename-wpproquiz-tables').length) {
		var parent_tr = jQuery('table#learndash-data-upgrades tr#learndash-data-upgrades-container-rename-wpproquiz-tables');
		
		// Show the tables listing details.
		jQuery('a.learndash-data-upgrades-show-tables', parent_tr).click(function (e) {
			e.preventDefault();
			jQuery('div#learndash-data-upgrades-rename-wpproquiz-tables-show-tables-list', parent_tr).toggle('slow');
		});	

		// Show the related tables listing based on the prefix selected.
		jQuery('input[name="learndash-data-upgrades-prefix"]', parent_tr).change(function (e) {
			e.preventDefault();
			var prefix = jQuery('input[name="learndash-data-upgrades-prefix"]:checked', parent_tr).val();
			jQuery('div#learndash-data-upgrades-rename-wpproquiz-tables-show-tables-list table', parent_tr).hide();
			if ((typeof prefix !== 'undefined') && (prefix != '')) {
				jQuery('div#learndash-data-upgrades-rename-wpproquiz-tables-show-tables-list table#tables-list-'+prefix, parent_tr).show('slow');
			}
		});
		jQuery('input[name="learndash-data-upgrades-prefix"]', parent_tr).change();
	}
});

function learndash_data_upgrades_do_ajax( post_data, container ) {
	if ( ( typeof post_data === 'undefined' ) || ( post_data == '' ) ) {
		active_post_data = {};
		return false;
	}
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		dataType: "json",
		cache: false,
		data: post_data,
		error: function(jqXHR, textStatus, errorThrown ) {
		},
		success: function(reply_data) {
			if ( typeof reply_data !== 'undefined' ) {

				if ( typeof reply_data['data'] !== 'undefined' ) {

					// Update the progress meter
					if (jQuery('.learndash-data-upgrades-status', container).length) {
					
						jQuery('.learndash-data-upgrades-status', container).show();
						jQuery('.learndash-data-upgrades-status .progress-meter', container).show();

						if ( typeof reply_data['data']['progress_percent'] !== 'undefined' ) {
							if ( reply_data['data']['progress_percent'] == '100' ) {
								jQuery('.learndash-data-upgrades-status .progress-meter', container).hide();
							} else {
								jQuery('.learndash-data-upgrades-status .progress-meter-image', container).css('width', reply_data['data']['progress_percent']+'%');
							}
						}

						if ( typeof reply_data['data']['progress_label'] !== 'undefined' ) {
							jQuery('.learndash-data-upgrades-status .progress-label', container).html(reply_data['data']['progress_label'] );
						}
					}
					if ( ( typeof reply_data['data']['last_run_info'] !== 'undefined' ) && ( reply_data['data']['last_run_info'] != '' ) ) {
						jQuery('p.description', container).html(reply_data['data']['last_run_info']);
					}

					var total_count = 0;
					if ( typeof reply_data['data']['total_count'] !== 'undefined' )
						total_count = parseInt(reply_data['data']['total_count']);
					
					var result_count = 0;
					if ( typeof reply_data['data']['result_count'] !== 'undefined' ) 
						result_count = parseInt(reply_data['data']['result_count']);
					
					//if ( result_count < total_count ) {
					jQuery('.learndash-data-upgrades-status .progress-label', container).removeClass('progress-label-in-progress');
					jQuery('.learndash-data-upgrades-status .progress-label', container).removeClass('progress-label-in-complete');
					jQuery('.learndash-data-upgrades-status .progress-label', container).removeClass('progress-label-complete');

					if (typeof reply_data['data']['progress_slug'] !== 'undefined') {
						jQuery('.learndash-data-upgrades-status .progress-label', container).addClass('progress-label-' + reply_data['data']['progress_slug']);

						if ( reply_data['data']['progress_slug'] == 'complete' ) {
							jQuery('table#learndash-data-upgrades button.learndash-data-upgrades-button').prop('disabled', false);
						} else {
							jQuery('.learndash-data-upgrades-status .progress-label', container).addClass('progress-label-' + reply_data['data']['progress_slug']);
							post_data['data'] = reply_data['data'];
							learndash_data_upgrades_do_ajax(post_data, container);

						}
					}
				}
			}
		}
	});
}
