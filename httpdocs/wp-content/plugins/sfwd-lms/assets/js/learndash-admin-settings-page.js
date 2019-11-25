jQuery(document).ready(function() {

	//console.log('wp[%o]', wp);
	//console.log('isRtl[%o]', isRtl);

	if ((typeof learndash_admin_settings_data !== 'undefined') && (typeof learndash_admin_settings_data.json !== 'undefined')) {
		learndash_admin_settings_data = learndash_admin_settings_data.json.replace(/&quot;/g, '"');
		learndash_admin_settings_data = jQuery.parseJSON(learndash_admin_settings_data);
	} else {
		learndash_admin_settings_data = {};
	}

	learndash_course_edit_page_billing_cycle_javascript();

	if (jQuery('.sfwd_options .sfwd_option_input select[data-ld-select2="1"]').length) {
		jQuery('.sfwd_options .sfwd_option_input select[data-ld-select2="1"]').each(function (idx, item) {
			var parent_ld_select = jQuery(item).parent('span.ld-select');
			if (typeof parent_ld_select !== 'undefined') {
				jQuery(parent_ld_select).addClass('ld-select2');
			} 

			var placeholder = jQuery(item).attr('placeholder');
			if ((typeof placeholder === 'undefined') || (placeholder === '')) {
				placeholder = jQuery("option[value='']", item).text();
			}
			if ((typeof placeholder === 'undefined') || (placeholder === '')) {
				placeholder = 'Select an option';
			}

			jQuery(item).select2({
				placeholder: placeholder,
				width: 'resolve',
				dir: (window.isRtl) ? 'rtl' : '',
				dropdownAutoWidth: true
			});
		});
	}

	/**
	 * Handle color picker settings fields.
	 */
	if (jQuery('.sfwd_input_type_colorpicker .learndash-section-field-colorpicker').length) {
		jQuery('.sfwd_input_type_colorpicker .learndash-section-field-colorpicker').wpColorPicker();
	}

	/**
	 * Handle the combination Select + Button field type actions.
	 */
	if ( jQuery( '.sfwd_options .sfwd_input_type_select-edit-delete' ).length ) {
		jQuery( '.sfwd_options .sfwd_input_type_select-edit-delete' ).each( function( idx, item ) {
			var item_spinner = jQuery(item).find('.spinner');
			item_spinner.css( 'float', 'none' );

			jQuery( item ).find( 'select' ).change( function( e ) {
				
				var select_val = jQuery( item ).find( 'select' ).val();
				
				// Hide any previous update message.
				jQuery( item ).find( '.message' ).hide();

				if ( select_val.length ) {
					var select_text = jQuery( item ).find( 'select option:selected' ).text();
					jQuery( item ).find( 'input[type="text"]' ).val( select_text );
					jQuery( item ).find( 'input[type="text"]' ).attr( 'disabled', false );
					jQuery( item ).find( 'input[type="button"]' ).attr( 'disabled', false );
				} else {
					jQuery( item ).find( 'input[type="text"]' ).val( '' );
					jQuery( item ).find( 'input[type="button"]' ).attr( 'disabled', true );
					jQuery( item ).find( 'input[type="text"]' ).attr( 'disabled', true );
				}
			});

			jQuery( item ).find( 'input[type="button"]' ).click( function ( e ) {
				var field_action = jQuery( e.currentTarget ).data( 'action' );
				var field_value = jQuery( item ).find( 'select' ).val();
				var updated_text = jQuery( item ).find( 'input[type="text"]' ).val();

				var post_data = jQuery(item).find('.ajax_data').data( 'ajax' );
				if ( typeof post_data !== 'undefined' ) {
					post_data['field_action'] = field_action;
					post_data['field_value'] = field_value;
					post_data['field_text'] = updated_text;

					item_spinner.css('visibility', 'visible');
				
					jQuery.ajax({
						type: 'POST',
						url: ajaxurl,
						dataType: 'json',
						cache: false,
						data: post_data,
						error: function (jqXHR, textStatus, errorThrown) {
						},
						success: function ( reply_data ) {
							item_spinner.css('visibility', 'hidden');

							if ( ( typeof reply_data.status !== 'undefined' ) && ( reply_data.status === true ) ) {
								if (field_action == 'update') {
									jQuery(item).find( 'select option[value="'+ field_value +'"]' ).text( updated_text );
								} else if (field_action == 'delete') {
									jQuery(item).find('select option[value="' + field_value + '"]').remove();
								}

								jQuery( item ).find('select').val( '' );
								jQuery( item ).find('input[type="text"]').val( '' );
							}

							if ( typeof reply_data.message !== 'undefined' ) {
								jQuery( item ).find( '.message' ).html( reply_data.message );
								jQuery( item ).find( '.message' ).show().fadeOut(3000);
							}
						}
					});
				}
			});
		});
	}

	/**
	 * Handle Media Upload setting fields.
	 */
	if (jQuery('.sfwd_options .learndash-section-field-media-upload_wrapper').length) {
		jQuery('.sfwd_options .learndash-section-field-media-upload_wrapper').each(function (idx, item) {
			var media_upload_field = jQuery(item).find('.learndash-section-field-media-upload');
			var media_preview_field = jQuery(item).find('img.image-preview');

			jQuery(item).find('input[type="button"].image-upload-button').click(function (e) {
				e.preventDefault();
				var file_frame;

				// If the media frame already exists, reopen it.
				if (file_frame) {
					// Open frame
					file_frame.open();
					return;
				} 

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});

				// When an image is selected, run a callback.
				file_frame.on('select', function () {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();

					// Do something with attachment.id and/or attachment.url here
					jQuery(media_preview_field).attr('src', attachment.url).css('width', 'auto');
					jQuery(media_upload_field).val(attachment.id);
				});

				// Finally, open the modal
				file_frame.open();
			});

			jQuery(item).find('input[type="button"].image-remove-button').click(function (e) {
				e.preventDefault();
				jQuery(media_upload_field).val('');
				var default_image_url = jQuery(media_preview_field).data('default');
				if (typeof default_image_url !== 'undefined') {
					jQuery(media_preview_field).attr('src', default_image_url);
				} else {
					jQuery(media_preview_field).attr('src', '');
				}
			});
		});
	}

	/**
	 * On the Course Settings metabox. If it contains no items we remove the metabox.
	 */
	if (!jQuery('body.post-type-sfwd-courses #sfwd-courses.postbox .inside .sfwd_input').length) {
		jQuery('body.post-type-sfwd-courses #sfwd-courses.postbox').remove();
	}

	/**
	 * On the Lesson Settings metabox. If it contains no items we remove the metabox.
	 */
	
	if (!jQuery('body.post-type-sfwd-lessons #sfwd-lessons.postbox .inside .sfwd_input').length) {
		jQuery('body.post-type-sfwd-lessons #sfwd-lessons.postbox').remove();
	}
	
	/**
	 * On the Lesson Settings metabox. If it contains no items we remove the metabox.
	 */
	
	if (!jQuery('body.post-type-sfwd-topic #sfwd-topic.postbox .inside .sfwd_input').length) {
		jQuery('body.post-type-sfwd-topic #sfwd-topic.postbox').remove();
	}
	
	/**
	 * On the Quiz Settings metabox. If it contains no items we remove the metabox.
	 */
	
	if (!jQuery('body.post-type-sfwd-topic #sfwd-quiz.postbox .inside .sfwd_input').length) {
		jQuery('body.post-type-sfwd-topic #sfwd-quiz.postbox').hide();
	}
	
	/**
	 * For the checkbox-switch can have dual labels. One for the 'on' state and one for the 
	 * 'off' state. This piece of code hooks into the change early event and swaps the labels.
	 */
	jQuery('.sfwd_options').on('ld_setting_switch_changed_early', function (event) {
		if (jQuery(event.ld_trigger_data.element).hasClass('learndash-section-field-checkbox-switch')) {
			var ld_switch_wrapper = jQuery(event.ld_trigger_data.element).parents('.ld-switch-wrapper');
			if ((typeof ld_switch_wrapper !== 'undefined') && (jQuery('span.label-text-multple', ld_switch_wrapper).length ) ) {
				jQuery('span.label-text-multple', ld_switch_wrapper).find('.ld-label-text').hide();
				if ('checked' === event.ld_trigger_data.state ) {
					var switch_val = jQuery(event.ld_trigger_data.element).val();
					jQuery('span.label-text-multple', ld_switch_wrapper).find('.ld-label-text-' + switch_val).show();
				} else {
					jQuery('span.label-text-multple', ld_switch_wrapper).find('span.ld-label-text-').show();
				}
			}
		}
	});

	/**
	 * Handle Settings fields switch open/close state change logic.
 	*/
	if (jQuery('.sfwd_options input.learndash-section-field-checkbox-switch').length) {
		jQuery('.sfwd_options input.learndash-section-field-checkbox-switch').each(function (idx, item) {
			
			jQuery(item).click(function (e) {
				checked_state = 'unchecked';
				if (jQuery(e.currentTarget).is(':checked') ) {
					checked_state = 'checked';
				}
				
				var trigger_data = {
					'type': 'ld_setting_switch_changed_early',
					'element': e.currentTarget,
					'class': settings_sub_trigger_class,
					'state': checked_state
				}

				jQuery('.sfwd_options').trigger({
					type: 'ld_setting_switch_changed_early',
					ld_trigger_data: trigger_data
				});

				var settings_sub_trigger_class = jQuery(e.currentTarget).data('settings-sub-trigger');
				if ((typeof settings_sub_trigger_class !== 'undefined') && (jQuery('.sfwd_options .' + settings_sub_trigger_class).length)) {
					trigger_data['type'] = 'ld_setting_switch_sub_changed_late';

					if ('checked' === checked_state) {
						jQuery('.sfwd_options .' + settings_sub_trigger_class).slideDown(500, function () {
							jQuery('.sfwd_options .' + settings_sub_trigger_class).removeClass('ld-settings-sub-state-closed');
							jQuery('.sfwd_options .' + settings_sub_trigger_class).addClass('ld-settings-sub-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'], 
								ld_trigger_data: trigger_data
							});
						});
						
					} else {
						jQuery('.sfwd_options .' + settings_sub_trigger_class).slideUp(400, function () {
							jQuery('.sfwd_options .' + settings_sub_trigger_class).addClass('ld-settings-sub-state-closed');
							jQuery('.sfwd_options .' + settings_sub_trigger_class).removeClass('ld-settings-sub-state-open');

							jQuery('.sfwd_options').trigger({ 
								type: trigger_data['type'], 
								ld_trigger_data: trigger_data
							});
						});
					}
				}
				var settings_inner_trigger_class = jQuery(e.currentTarget).data('settings-inner-trigger');
				if ((typeof settings_inner_trigger_class !== 'undefined') && (jQuery('.sfwd_options .' + settings_inner_trigger_class).length)) {
					trigger_data['type'] = 'ld_setting_switch_inner_changed_late';

					if ('checked' === checked_state) {
						jQuery('.sfwd_options .' + settings_inner_trigger_class).slideDown(500, function () {
							jQuery('.sfwd_options .' + settings_inner_trigger_class).removeClass('ld-settings-inner-state-closed');
							jQuery('.sfwd_options .' + settings_inner_trigger_class).addClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});
						});

					} else {
						jQuery('.sfwd_options .' + settings_inner_trigger_class).slideUp(400, function () {
							jQuery('.sfwd_options .' + settings_inner_trigger_class).addClass('ld-settings-inner-state-closed');
							jQuery('.sfwd_options .' + settings_inner_trigger_class).removeClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});
						});
					}
				}
				//e.preventDefault();
				e.stopPropagation();
			});
		});
	}

	/**
	 * Handle Settings fields select state change logic.
 	*/
	if (jQuery('.sfwd_options select.learndash-section-field-select').length) {
		jQuery('.sfwd_options select.learndash-section-field-select').each(function (idx, item) {
			jQuery(item).change(function (e) {

				var select_val = jQuery(e.currentTarget).val();
				if ((typeof select_val === 'undefined') || ('-1' == select_val)) {
					select_val = '';
				}

				var trigger_data = {
					'element': e.currentTarget,
					'state': 'open',
					'value': select_val
				}
				
				var settings_sub_trigger_class = jQuery(e.currentTarget).data('settings-sub-trigger');
				if ((typeof settings_sub_trigger_class !== 'undefined') && ('' !== settings_sub_trigger_class )) {
					trigger_data['class'] = settings_inner_trigger_class;
					trigger_data['type'] = 'ld_setting_select_sub_changed_early';
					
					if ( jQuery('.sfwd_options .' + settings_sub_trigger_class).length ) {
						trigger_data['state'] = 'closed';

						// First we need to close any open items
						jQuery('.sfwd_options .' + settings_sub_trigger_class).each(function (idx, item) {
							jQuery(item).slideUp('fast', function () {
								jQuery(item).removeClass('ld-settings-sub-state-open');
								jQuery(item).addClass('ld-settings-sub-state-closed');

								jQuery('.sfwd_options').trigger({
									type: trigger_data['type'],
									ld_trigger_data: trigger_data
								});
							});
						});
					}

					if ('' !== select_val) {
						//settings_sub_trigger_class = settings_sub_trigger_class + '_'+select_val;
						if (jQuery('.sfwd_options .' + settings_sub_trigger_class).length) {

							trigger_data['class'] = settings_sub_trigger_class;
							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});

							trigger_data['type'] = 'ld_setting_switch_sub_changed_late';
							trigger_data['state'] = 'open';

							jQuery('.sfwd_options .' + settings_sub_trigger_class).slideDown(500, function () {
								jQuery('.sfwd_options .' + settings_sub_trigger_class).removeClass('ld-settings-sub-state-closed');
								jQuery('.sfwd_options .' + settings_sub_trigger_class).addClass('ld-settings-sub-state-open');

								jQuery('.sfwd_options').trigger({
									type: trigger_data['type'],
									ld_trigger_data: trigger_data
								});
							});
						}
					}
				}
				

				var settings_inner_trigger_class = jQuery(e.currentTarget).data('settings-inner-trigger');
				if ((typeof settings_inner_trigger_class !== 'undefined') && ('' !== settings_inner_trigger_class)) {
					trigger_data['class'] = settings_inner_trigger_class;

					// First we need to close any open items
					var parent_fieldset = jQuery(e.currentTarget).parents('.sfwd_option_div')[0];
					if (typeof parent_fieldset !== 'undefined') {
						jQuery('.ld-settings-inner-state-open', parent_fieldset).each(function (idx, item) {
							jQuery(item).slideUp('fast', function () {
								jQuery(item).removeClass('ld-settings-inner-state-open');
								jQuery(item).addClass('ld-settings-inner-state-closed');
								
								trigger_data['type'] = 'ld_setting_switch_sub_changed_late';
								trigger_data['state'] = 'closed';

								jQuery('.sfwd_options').trigger({
									type: 'ld_setting_select_changed_early',
									ld_trigger_data: trigger_data
								});
								
							});
						});
					}

					settings_inner_trigger_class = settings_inner_trigger_class + '_' + select_val;

					if (jQuery('.sfwd_options .' + settings_inner_trigger_class).length) {
						
						trigger_data['type'] = 'ld_setting_switch_inner_changed_early';
						trigger_data['state'] = 'open';

						jQuery('.sfwd_options').trigger({
							type: trigger_data['type'],
							ld_trigger_data: trigger_data
						});
						
						trigger_data['type'] = 'ld_setting_switch_inner_changed_late';
						jQuery('.sfwd_options .' + settings_inner_trigger_class).slideDown(500, function () {
							jQuery('.sfwd_options .' + settings_inner_trigger_class).removeClass('ld-settings-inner-state-closed');
							jQuery('.sfwd_options .' + settings_inner_trigger_class).addClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});
						});
					}
				}

				//e.preventDefault();
				e.stopPropagation();
			});
		});
	}
	
	/**
	 * Handle Settings fields radio open/close state change logic.
  	*/
	if (jQuery('.sfwd_options input.learndash-section-field-radio').length) {
		jQuery('.sfwd_options input.learndash-section-field-radio').each(function (idx, item) {
			jQuery(item).click(function (e) {

				// First we need to close any open items
				var parent_fieldset = jQuery(e.currentTarget).parents('fieldset')[0];
				if (typeof parent_fieldset !== 'undefined') {
					jQuery('.ld-settings-inner-state-open', parent_fieldset).each(function (idx, item) {
						jQuery(item).slideUp('fast', function () {
							jQuery(item).removeClass('ld-settings-inner-state-open');
							jQuery(item).addClass('ld-settings-inner-state-closed');

							jQuery('.sfwd_options').trigger({
								type: 'ld_setting_radio_changed_early',
								ld_trigger_data: {
									'type': 'ld_setting_changed',
									'element': item,
									'class': '',
									'state': 'unchecked'
								}
							});
						});
					});
				}

				var settings_sub_trigger_class = jQuery(item).data('settings-inner-trigger');
				if ((typeof settings_sub_trigger_class !== 'undefined') && (jQuery('.sfwd_options .' + settings_sub_trigger_class).length)) {

					checked_state = 'unchecked';
					if (jQuery(e.currentTarget).is(':checked')) {
						checked_state = 'checked';
					}

					var trigger_data = {
						'type': 'ld_setting_radio_inner_changed_early',
						'element': e.currentTarget,
						'class': settings_sub_trigger_class,
						'state': checked_state
					}
					jQuery('.sfwd_options').trigger({
						type: 'ld_setting_changed_early',
						ld_trigger_data: trigger_data
					});

					trigger_data['type'] = 'ld_setting_radio_inner_changed_later';
					if ('checked' === checked_state) {	
						jQuery('.sfwd_options .' + settings_sub_trigger_class).slideDown(500, function () {
							jQuery('.sfwd_options .' + settings_sub_trigger_class).removeClass('ld-settings-inner-state-closed');
							jQuery('.sfwd_options .' + settings_sub_trigger_class).addClass('ld-settings-inner-state-open');

							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});
						});
					} else {
						jQuery('.sfwd_options .' + settings_sub_trigger_class).slideUp(400, function () {
							jQuery('.sfwd_options .' + settings_sub_trigger_class).removeClass('ld-settings-inner-state-open');
							jQuery('.sfwd_options .' + settings_sub_trigger_class).addClass('ld-settings-inner-state-closed');
							
							jQuery('.sfwd_options').trigger({
								type: trigger_data['type'],
								ld_trigger_data: trigger_data
							});
						});
					}
				}
				//e.preventDefault();
				e.stopPropagation();
			});
		});
	}

	/**
	 * Advanced Settings inline fields toggle.
	 */
	/*
	if (jQuery('.sfwd_options .ld-settings-sub-advanced a.ld-settings-sub-advanced-trigger').length) {
		jQuery('.sfwd_options .ld-settings-sub-advanced a.ld-settings-sub-advanced-trigger').each(function (idx, item) {
			jQuery(item).click(function (e) {
				var parent_div = jQuery(e.currentTarget).parent('div.ld-settings-sub-advanced');
				if (parent_div !== undefined) {
					var advanced_inner = jQuery('div.ld-settings-sub-advanced-inner', parent_div);
					if (advanced_inner !== undefined) {
						jQuery(advanced_inner).slideToggle(500, function () {
						});
					} 
				}
			});
		});
	}
	*/

	/**
	 * Used on the Quiz Result Messages expand/collapse.
	 */
	if (jQuery('.sfwd_options #resultList li .expand-arrow').length) {
		jQuery('.sfwd_options #resultList li .expand-arrow').each(function (idx, item) {
			jQuery(item).click(function (e) {
				var parent_li = jQuery(e.currentTarget).parents('li');
				if (parent_li !== undefined) {
					var div_resultEditor = jQuery('.resultEditor', parent_li);
					if (div_resultEditor !== undefined) {
						if (jQuery(e.currentTarget).hasClass('expand-arrow-down')) {
							jQuery(e.currentTarget).addClass('expand-arrow-up');
							jQuery(e.currentTarget).removeClass('expand-arrow-down');
							jQuery(div_resultEditor).slideDown(400);
						} else {
							jQuery(e.currentTarget).addClass('expand-arrow-down');
							jQuery(e.currentTarget).removeClass('expand-arrow-up');

							jQuery(div_resultEditor).slideUp(500);
						}
					}
				}
			});
		});
	}


	/**
	 * Handle coordination between three checkbox-switch elements. Only one can
	 * be active. When one is active the other two are disabled and show tooltip
	 * messages.
	 */
	var learndash_settings_track_items = {};
	if (jQuery('body.post-type-sfwd-lessons .sfwd_options').length) {
		learndash_settings_track_items = {
			'learndash-lesson-display-content-settings_lesson_video_enabled': '',
			'learndash-lesson-display-content-settings_lesson_assignment_upload': '',
			'learndash-lesson-display-content-settings_forced_lesson_time_enabled': '',
		};
		learndash_update_radio_tracked_items();
	}

	if (jQuery('body.post-type-sfwd-topic .sfwd_options').length) {
		var learndash_settings_track_items = {
			'learndash-topic-display-content-settings_lesson_video_enabled': '',
			'learndash-topic-display-content-settings_lesson_assignment_upload': '',
			'learndash-topic-display-content-settings_forced_lesson_time_enabled': '',
		};
		learndash_update_radio_tracked_items();
	}

	jQuery('.sfwd_options').on('ld_setting_switch_changed_early', function (event) {
		learndash_update_radio_tracked_items();
	});

	function learndash_update_radio_tracked_items() {
		var checked_count = 0;
		if (!jQuery.isEmptyObject(learndash_settings_track_items) ) {
			jQuery.each(learndash_settings_track_items, function (item_id, value) {
				if (jQuery('.sfwd_options input#' + item_id).length) {
					if (jQuery('.sfwd_options input#' + item_id).is(':checked') ) {
						learndash_settings_track_items[item_id] = true;
						checked_count += 1;
					} else {
						learndash_settings_track_items[item_id] = false;
					}
				}
			});

			if (checked_count > 0 ) {
				jQuery.each(learndash_settings_track_items, function (item_id, value) {
					if ( value !== true ) {
						jQuery('#'+item_id).attr('disabled', 'disabled');
						jQuery('#' + item_id).parent('.ld-switch').addClass('-disabled');
					}
				});
			} else {
				jQuery.each(learndash_settings_track_items, function (item_id, value) {
					if (value !== true) {
						jQuery('#' + item_id).attr('disabled', false);
						jQuery('#' + item_id).parent('.ld-switch').removeClass('-disabled');
					}
				});
			}
		}
	}

	/**
	 * Handle the Lessons selector on the Topic edit screen when the Course selector is changed.
	 */
	jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_course").change(function () {
		if (window['sfwd_topic_lesson'] == undefined)
			window['sfwd_topic_lesson'] = jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson").val();

		var data = {
			'action': 'select_a_lesson',
			'course_id': jQuery(this).val()
		};

		if ( jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson").length ) {

			var lesson_selector_nonce = jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson").data('ld_selector_nonce');
			if (typeof lesson_selector_nonce !== 'undefined') {
				data.ld_selector_nonce = lesson_selector_nonce;
			}
			var lesson_selector_default = jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson").data('ld_selector_default');
			if (typeof lesson_selector_default !== 'undefined') {
				data.ld_selector_default = lesson_selector_default;
			}
			jQuery.post(ajaxurl, data, function (json) {
				window['response'] = json;
				html = ''; //'<option value="0">' + sfwd_data.select_a_lesson_lang + '</option>';
				jQuery.each(json.opt, function (i, opt) {
					if (opt.key != '' && opt.key != '0') {
						selected = (opt.key == window['sfwd_topic_lesson']) ? 'selected=selected' : '';
						html += "<option value='" + opt.key + "' " + selected + ">" + opt.value + "</option>";
					}
				});
				jQuery("body.post-type-sfwd-topic .sfwd_options select#learndash-topic-access-settings_lesson").html(html);
			}, "json");
		}
	});

	/**
	 * Handle the Lessons selector on the Topic edit screen when the Course selector is changed.
	 */
	jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_course").change(function () {
		if (window['sfwd_quiz_lesson'] == undefined)
			window['sfwd_quiz_lesson'] = jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson").val();

		var data = {
			'action': 'select_a_lesson_or_topic',
			'course_id': jQuery(this).val()
		};

		if (jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson").length ) {
			var lesson_selector_nonce = jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson").data('ld_selector_nonce');
			if (typeof lesson_selector_nonce !== 'undefined') {
				data.ld_selector_nonce = lesson_selector_nonce;
			}
			
			var lesson_selector_default = jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson").data('ld_selector_default');
			if (typeof lesson_selector_default !== 'undefined') {
				data.ld_selector_default = lesson_selector_default;
			}
			jQuery.post(ajaxurl, data, function (json) {
				window['response'] = json;
				html = ''; //'<option value="0">' + sfwd_data.select_a_lesson_lang + '</option>';
				jQuery.each(json.opt, function (i, opt) {
					if (opt.key != '' && opt.key != '0') {
						selected = (opt.key == window['sfwd_quiz_lesson']) ? 'selected=selected' : '';
						html += "<option value='" + opt.key + "' " + selected + ">" + opt.value + "</option>";
					}
				});
				jQuery("body.post-type-sfwd-quiz .sfwd_options select#learndash-quiz-access-settings_lesson").html(html);
			}, "json");
		}
	});

	/**
	 * Handle the Quiz Run Once Cookie Selector.
	 */
	if ( jQuery('select#learndash-quiz-progress-settings_quizRunOnceType').length ) {
		jQuery('select#learndash-quiz-progress-settings_quizRunOnceType').change(function () {
			var select_val = jQuery(this).val();
			
			// If value is '2' for Logged in users only then no cookie is used. 
			// So hide the cookie field.
			if (select_val == 2) {
				jQuery('#learndash-quiz-progress-settings_quizRunOnceCookie_field').hide();
			} else {
				jQuery('#learndash-quiz-progress-settings_quizRunOnceCookie_field').show();
			}
		});
		jQuery('select#learndash-quiz-progress-settings_quizRunOnceType').change();
	}
	
	/**
	 * Handle Settings Themes select state change logic.
 	*/
	if (jQuery('.sfwd_options select#learndash_settings_courses_themes_active_theme').length) {
		jQuery('.sfwd_options select#learndash_settings_courses_themes_active_theme').each(function (idx, item) {
			jQuery(item).change(function (e) {
				var select_theme_val = jQuery(e.currentTarget).val();

				jQuery('.sfwd_options .ld-theme-settings-section-state-open').slideUp(500, function () {
					jQuery(this).removeClass('ld-theme-settings-section-state-open');
					jQuery(this).addClass('ld-theme-settings-section-state-closed');
				});

				if (select_theme_val !== '' ) {
					jQuery('.sfwd_options .ld-theme-settings-section-' + select_theme_val).slideDown(500, function () {
						jQuery(this).removeClass('ld-theme-settings-section-state-closed');
						jQuery(this).addClass('ld-theme-settings-section-state-open');
					});
				}

				e.stopPropagation();
			});
		});
	}

	/**
	 * Handle the Template load button on the Quiz / Questions edit metabox.
	 */
	if (jQuery('.sfwd_options input[name="templateLoad"]').length) {
		jQuery('.sfwd_options input[name="templateLoad"]').click(function () {
			if (jQuery('.sfwd_options select[name="templateLoadId"]').length) {
				var template_load_url = jQuery('.sfwd_options select[name="templateLoadId"]').val();
				if (template_load_url != '') {
					window.location.href = template_load_url;
				}
			}

			return false;
		});
	}

	/**
	 * Handle number fields with limits and filtering.
	 */
	function learndash_get_input_config( input_field ) {
		var input_config = input_field.data('input-config');
		if (typeof input_config === 'undefined') {
			input_config = {};

			input_config['input_min'] = input_field.attr('min');
			if (typeof input_config['input_min'] === 'undefined') {
				input_config['input_min'] = '';
			}

			input_config['input_max'] = input_field.attr('max');
			if (typeof input_config['input_max'] === 'undefined') {
				input_config['input_max'] = '';
			}

			input_config['input_step'] = input_field.attr('step');
			if (typeof input_config['input_step'] === 'undefined') {
				input_config['input_step'] = '';
			}
			
			input_config["can_decimal"] = input_field.attr("can_decimal");
			if ( ( typeof input_config["can_decimal"] !== "undefined") && ( '' !== input_config['can_decimal'] ) ) {
				if ( "true" === input_config["can_decimal"] ) {
					input_config["can_decimal"] = 2;
				} else {
					input_config['can_decimal'] = parseInt( input_config['can_decimal'].toString() );
				}

				if ( '' === input_config['can_decimal'] ) {
					input_config["can_decimal"] = false;
				}
			} else {
				input_config["can_decimal"] = false;
			}

			input_config["can_empty"] = input_field.attr("can_empty");
			if ((typeof input_config["can_empty"] !== "undefined") && ( ( "true" === input_config["can_empty"] ) || ( '1' === input_config["can_empty"] ) ) ) {
        		input_config["can_empty"] = true;
      		} else {
				input_config["can_empty"] = false;
			}
			
			if ( ( "" === input_config["input_step"] ) && ( "" === input_config["input_min"] ) && ( "" === input_config["input_max"] ) ) {
            	return false;
			}

			if ( input_config["can_decimals"] > 0 ) {
				if ( '' !== input_config['input_min'] ) {
					input_config["input_min"] = parseFloat( input_config["input_min"] );
				}

				if ( '' !== input_config['input_max'] ) {
					input_config["input_max"] = parseFloat( input_config["input_max"] );
				}

				if ( '' !== input_config['input_step'] ) {
					input_config["input_step"] = parseFloat( input_config["input_step"] );
				}
			} else {
               if ( '' !== input_config['input_min'] ) {
				   input_config['input_min'] = parseInt( input_config['input_min'] );
			   }

			   if ( '' !== input_config['input_max'] ) {
				   input_config['input_max'] = parseInt( input_config['input_max'] );
			   }

			   if ( '' !== input_config['input_step'] ) {
				   input_config['input_step'] = parseInt( input_config['input_step'] );
			   }
            }
			
			input_field.data("input-config", input_config);
		}

		return input_config;
	}

	var learndash_settings_fields_errors = {};

	function learndash_set_input_error( target, invalid ) {
		if (jQuery(target).length) {
			//if (typenow !== 'undefined') {
			//	console.log('typenow[%o]', typenow);
			//}
			
  			var input_wrapper = jQuery(target).parents(".sfwd_input");
  			if ("undefined" !== typeof input_wrapper) {
				var input_id = jQuery(input_wrapper).attr('id');

				if ( invalid === true ) {
					if ( "undefined" === typeof learndash_settings_fields_errors[input_id] ) {
						var input_label = jQuery('.sfwd_option_label label', input_wrapper).html();
						if ("undefined" !== typeof input_label) {
							var input_error = jQuery(target).next(".learndash-section-field-error").html();
							if ("undefined" !== typeof input_error) {
								learndash_settings_fields_errors[input_id] = input_label.trim() + " - " + input_error;
							} else {
								learndash_settings_fields_errors[input_id] = input_label.trim();
							}
						}
					}

	    			jQuery(input_wrapper).addClass("learndash_settings_field_invalid");
					jQuery(input_wrapper).find(".learndash-section-field-error").show();
				} else {
					if ( "undefined" !== typeof learndash_settings_fields_errors[input_id] ) {
						delete learndash_settings_fields_errors[input_id]
					}
					jQuery(input_wrapper).removeClass("learndash_settings_field_invalid");
            		jQuery(input_wrapper).find(".learndash-section-field-error").hide();
				}
			}
		}

		learndash_update_header_notice();
	}

	function learndash_update_header_notice() {
		if ( ! jQuery('#learndash-settings-fields-notice-errors').length ) {
			if ("undefined" === typeof learndash_admin_settings_data['admin_notice_settings_fields_errors_container'] ) {
				return;
			}
			
			jQuery( learndash_admin_settings_data['admin_notice_settings_fields_errors_container'] ).insertAfter("hr.wp-header-end");
		}

		if ( jQuery('#learndash-settings-fields-notice-errors').length ) {
			var notice_el = jQuery("#learndash-settings-fields-notice-errors");
			var object_keys = Object.keys(learndash_settings_fields_errors);

			var error_field_list = '';
			for (var i = 0; i < object_keys.length; i++) { 
				var object_key = object_keys[i];
				var error_field_label = learndash_settings_fields_errors[object_key];
				if ( '' !== error_field_label ) {
					error_field_list += "<li>" + error_field_label + '</li>';
				}
			}

			//if ( '' !== error_field_list ) {
			//	error_field_list = "<p><ul>" + error_field_list + '</ul></p>';
			//}
			jQuery("ul.errors-list", notice_el).html(error_field_list);
			if (object_keys.length > 0 ) {				
        		notice_el.show();
      		} else {
        		notice_el.hide();
      		}
		}		
	}


	if (jQuery('.sfwd_options input[type="number"]').length) {
		jQuery('.sfwd_options input[type="number"]').each(function (idx, item) {
			jQuery(item).on( 'keypress', function (e) {
				var input_config = learndash_get_input_config(jQuery(e.currentTarget));
				if ( false === input_config ) {
					return;
				}
				var charCode = e.which ? e.which : e.keyCode;
				var input_value_new = String.fromCharCode(charCode);
				var input_value_current = e.currentTarget.valueAsNumber;
		
				if (input_value_new === '.') {
					if ("undefined" === typeof input_value_current || isNaN(input_value_current)) {
						learndash_set_input_error(e.currentTarget, true);
						e.preventDefault();
						return false;
					}
					
					if ( input_config["can_decimal"] === false ) {
						e.preventDefault();
						return false;
					} else {
						learndash_set_input_error(e.currentTarget, false);
						return true;
					}
				}

				if ( input_value_new === '-') {
					if ( ( "undefined" !== typeof input_config['input_min'] ) && ( input_config['input_min'] !== '' ) && ( input_config['input_min'] < 0 ) ) {
						return true;
					} else {
						e.preventDefault();
						return false;
					}
				}

				if ( ( "undefined" !== typeof input_config["input_max"] ) && ( '' !== input_config["input_max"] ) && ( input_value_current > input_config["input_max"] ) ) {
					learndash_set_input_error(e.currentTarget, true);
					e.preventDefault();
					return false;
				}
					
				if ( ( "undefined" !== typeof input_config['input_min'] ) && ( '' !== input_config['input_min'] ) && ( input_value_current < input_config['input_min'] ) ) {
					learndash_set_input_error(e.currentTarget, true);
					e.preventDefault();
					return false;
				}

				if (charCode < 48 || charCode > 57) {
					e.preventDefault();
					return false;
				} 
		
				learndash_set_input_error(e.currentTarget, false);
				return true;
			});

			jQuery(item).on("invalid", function(e) {
		        if (jQuery(e.currentTarget).length) {
					learndash_set_input_error(e.currentTarget, true);

					// prevent showing the default display
              		e.preventDefault();
            	}
      		});
			
			jQuery(item).on("input", function(e) {
        		if (jQuery(e.currentTarget).length) {
					var input_config = learndash_get_input_config(jQuery(e.currentTarget));
					if ( false === input_config ) {
						return;
					}

					var input_value_current = e.currentTarget.valueAsNumber;					
					if ( "undefined" === typeof input_value_current ) {
        				learndash_set_input_error(e.currentTarget, true);
        				return false;
					}

					if ( ( input_config['can_empty'] ) && ( isNaN( input_value_current) ) ) {
						learndash_set_input_error(e.currentTarget, false);
            			return true;
					} else if (isNaN(input_value_current)) {
						learndash_set_input_error(e.currentTarget, true);
            			return false;
					}
				   
					if ( "undefined" !== typeof input_config["input_max"] && "" !== input_config["input_max"] && input_value_current > input_config["input_max"] ) {
						learndash_set_input_error(e.currentTarget, true);
						return false;
					}
					
					if ( ( "undefined" !== typeof input_config['input_min'] ) && ( '' !== input_config['input_min'] ) && ( input_value_current < input_config['input_min'] ) ) {
						learndash_set_input_error(e.currentTarget, true);
              			return false;
					}

					if ( ( "undefined" !== typeof input_config['can_decimal'] ) && ( false !== input_config['can_decimal'] ) ) {
						input_value_current_split = input_value_current.toString().split(".");
						if ( input_value_current_split.length > 1 ) {
							if ( input_value_current_split[1].length > input_config['can_decimal'] ) {
								var input_value_current_fixed = input_value_current.toFixed(input_config["can_decimal"]);
								if (input_value_current_fixed !== input_value_current) {
									jQuery(e.currentTarget).val(input_value_current_fixed);
								}
							}
						}
					}

					learndash_set_input_error(e.currentTarget, false);
        		}
			});
		});
	}


	jQuery('form#ld_data_remove_form').submit( function( event ) {
		var ld_data_remove_verify = jQuery('input#ld_data_remove_verify').val();
		var ld_data_remove_confirm = jQuery('input#ld_data_remove_verify').data('confirm');

		if ((typeof ld_data_remove_verify === 'undefined') || (typeof ld_data_remove_confirm === 'undefined')) {
			event.preventDefault();
			return;
		}

		ld_data_remove_verify = ld_data_remove_verify.trim();
		if ((ld_data_remove_verify === '') || (ld_data_remove_confirm === '')) {
			event.preventDefault();
			return;
		}
		
		if (!confirm(ld_data_remove_confirm ) ) {
			event.preventDefault();
			return;
		}
		// If we get to here the form will submit.
	});
});

function learndash_course_edit_page_billing_cycle_javascript() {
	var selector = jQuery('body.post-type-sfwd-courses .sfwd_options select[name=course_price_billing_t3]');
	var parent = selector.parent();
	var billing_cycle = selector.val();

	function build_notice(message) {
		return '<div id="sfwd-courses_course_price_billing_cycle_instructions"><label class="sfwd_help_text">' + message + '</label></div>';
	}

	function output_message() {
		switch (billing_cycle) {
			case "D":
				message = sfwd_data.valid_recurring_paypal_day_range;
				parent.append(build_notice(message));
				break;
			case "W":
				message = sfwd_data.valid_recurring_paypal_week_range;
				parent.append(build_notice(message));
				break;
			case "M":
				message = sfwd_data.valid_recurring_paypal_month_range;
				parent.append(build_notice(message));
				break;
			case "Y":
				message = sfwd_data.valid_recurring_paypal_year_range;
				parent.append(build_notice(message));
			default:
				break;
		}
	}
	output_message();

	selector.change(function (e) {
		billing_cycle = selector.val();
		jQuery("#sfwd-courses_course_price_billing_cycle_instructions").remove();
		output_message(billing_cycle);
	});
};

/** 
 * Trigger resize on load to trigger the resizing of 
 * .sfwd_options .ld-settings-sub elements.
 */
/*
jQuery(window).load(function () {
	setTimeout(function () {
		jQuery(window).trigger('resize');
	}, 1000); 	
});
*/

/**
 * Handle moving metabox description into title area.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		const LDdescriptions = document.querySelectorAll('.ld-metabox-description');
		Array.prototype.forEach.call(LDdescriptions, description => {
			// Find the metabox h2 and append the description inside it
			// description.parentNode is <div class="inside"></div>
			// description.parentNode.previousElementSibling is the <h2 class="hndle"></h2>
			description.parentNode.previousElementSibling.appendChild(description);
		});
	});
})()

//if ('undefined' === typeof window.learndash) {

	/**
	 * @namespace learndash
	 */
//	window.learndash = {};
//}

//if ('undefined' === typeof window.learndash.admin) {

	/**
	 * @namespace learndash.admin
	 */
//	window.learndash.admin = {};
//}

/**
 * @namespace learndash.admin.settings
 */
/*
window.learndash.admin.settings = {
	toggleClassName: 'learndash-section-field-checkbox-switch',
	toggleSettingsVisibility: function (event) {
		if (event.target.classList.contains(this.toggleClassName)) {
			var dataSet = event.target.dataset.settingsSubTrigger;
			if (dataSet) {
				var divToToggle = document.querySelector('.' + dataSet);
				if (divToToggle) {
					divToToggle.style.display = divToToggle.style.display === 'none' ? 'block' : 'none';
				}
			}
		}
	},
	eventListeners: function () {
		document.querySelector('body').addEventListener('change', learndash.admin.settings.toggleSettingsVisibility.bind(learndash.admin.settings));
	}
};
window.onload = learndash.admin.settings.eventListeners;
*/