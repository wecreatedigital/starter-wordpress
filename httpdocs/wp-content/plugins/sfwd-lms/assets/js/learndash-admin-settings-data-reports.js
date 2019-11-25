jQuery(document).ready(function(){
	jQuery('table#learndash-data-reports button').click(function(e) {
		e.preventDefault();

		var parent_tr 	= jQuery(this).parents('tr')
		var data_nonce 	= jQuery(this).attr('data-nonce');
		var data_slug 	= jQuery(this).attr('data-slug');

		// Close all other progress meters
		jQuery('table#learndash-data-reports .learndash-data-reports-status').hide();
				
		// disable all other buttons
		jQuery('table#learndash-data-reports button.learndash-data-reports-button').prop('disabled', true);

		// Hide all download buttons
		jQuery('table#learndash-data-reports a.learndash-data-reports-download-link').hide();
		
		var post_data = {
			'action': 'learndash-data-reports',
			'data': {
				'init': 1,
				'nonce': data_nonce,
				'slug': data_slug,
			}
		}
		
		learndash_data_reports_do_ajax( post_data, parent_tr );
		
	});
});

function learndash_data_reports_do_ajax( post_data, container ) {
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
					if (jQuery('.learndash-data-reports-status', container).length) {
					
						jQuery('.learndash-data-reports-status', container).show();
					
						if ( typeof reply_data['data']['progress_percent'] !== 'undefined' ) {
							jQuery('.learndash-data-reports-status .progress-meter-image', container).css('width', reply_data['data']['progress_percent']+'%');
						}

						if ( typeof reply_data['data']['progress_label'] !== 'undefined' ) {
							jQuery('.learndash-data-reports-status .progress-label', container).html(reply_data['data']['progress_label']);
						}
					}

					var total_count = 0;
					if ( typeof reply_data['data']['total_count'] !== 'undefined' )
						total_count = parseInt(reply_data['data']['total_count']);
					
					var result_count = 0;
					if ( typeof reply_data['data']['result_count'] !== 'undefined' ) 
						result_count = parseInt(reply_data['data']['result_count']);
					
					if ( result_count < total_count ) {
						post_data['data'] = reply_data['data'];
						learndash_data_reports_do_ajax( post_data, container );
					} else {
						// Re-enable the buttons
						jQuery('table#learndash-data-reports button.learndash-data-reports-button').prop('disabled', false);

						if (( typeof reply_data['data']['report_download_link'] !== 'undefined' ) && ( reply_data['data']['report_download_link'] != '' )) {
							window.location.href = reply_data['data']['report_download_link'];
						}
					}
				}
			}
		}
	});
}
