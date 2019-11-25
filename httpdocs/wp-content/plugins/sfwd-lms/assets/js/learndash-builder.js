jQuery(document).ready(function($) {
	//"use strict";
	var course_id = 0;
	var ld_typenow = '';
	var cb_form_unsaved = false;
	var builder_items_count = 0;
	var builder_items_points = 0;

	var ld_builder_new_step_ajax_pid = '';

	var touch = ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch;
	var touchEvent = touch ? 'touchstart' : 'hover';
	
	if ( jQuery( '#learndash_builder_box_wrap' ).length ) {
		course_id = jQuery( '#learndash_builder_box_wrap' ).data('ld-course-id');
		ld_typenow = jQuery('#learndash_builder_box_wrap').data('ld-typenow');
	}

	if ( jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).length ) {

		// set the first selector set to open by default. 
		jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container' ).each(function (index, item) {
			if ( index == 0 ) {
				jQuery( '.learndash-selector-post-listing', item).slideDown('slow', function(){
					jQuery( '.learndash-selector-header .ld-course-builder-action-show-hide', item).removeClass('ld-course-builder-action-show');
					jQuery( '.learndash-selector-header .ld-course-builder-action-show-hide', item).addClass('ld-course-builder-action-hide');
				});
			} else {
				jQuery('.learndash-selector-post-listing', item).hide();
				jQuery( '.learndash-selector-header .ld-course-builder-action-show-hide', item).addClass('ld-course-builder-action-show');
				jQuery( '.learndash-selector-header .ld-course-builder-action-show-hide', item).removeClass('ld-course-builder-action-hide');
			}

			// Since we are looping the selectors we initialize the disabled items
			var selector_type = jQuery(item).data('ld-type');
			if ( typeof selector_type !== 'undefined' ) {
				selector_update_disabled_items( selector_type );
				selector_update_empty( selector_type );
			}
		});


		//course_builder_box_wrap_resize();
		/*
		function course_builder_box_wrap_resize( e ) {
			jQuery( window ).resize(function() {
				console.log('in resize');
				
				//var ld_course_builder_box_wrap_width = jQuery( '#learndash_builder_box_wrap' ).width();
				//console.log('ld_course_builder_box_wrap_width[%o]', ld_course_builder_box_wrap_width);
				//if ( ld_course_builder_box_wrap_width < 500 ) {
				//	jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).css( 'width', '40%' );
				//	jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).css( 'width', 'calc(60% - 5px)' );
				//} else {
				//	jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).css( 'width', '30%' );
				//	jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).css( 'width', 'calc(70% - 5px)' );
				//}
				
				
				// First we want to adjust the column widths of the selectors and step items. 
				//var ld_course_builder_box_wrap_width = jQuery( '#learndash_builder_box_wrap' ).width();
				//console.log('ld_course_builder_box_wrap_width[%o]', ld_course_builder_box_wrap_width);
				
				//var ld_course_builder_selectors_width = jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).outerWidth();
				//console.log('ld_course_builder_selectors_width[%o]', ld_course_builder_selectors_width);
				
				//var new_Width = ld_course_builder_box_wrap_width - ld_course_builder_selectors_width;
				//console.log('new_Width[%o]', new_Width);

				//jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).width(new_Width-25);
				
				// trigger resize on any edit title elements
				//course_builder_resize_title_edit();
			});
		}
		*/
		//jQuery( document ).trigger( 'resize' );

		jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'click', '.ld-course-builder-action-show-hide', selector_show_hide );
		function selector_show_hide( event, action ) {
			event.stopImmediatePropagation();
			
			var parent_title_el = jQuery( event.currentTarget).parents('h3');
			var parent_items_el = jQuery(parent_title_el).parent();
			var post_listing = jQuery( parent_items_el ).find('.learndash-selector-post-listing');

			if ( ( typeof action === 'undefined' ) || ( ( action != 'open') && ( action != 'close' ) ) )  {
				if ( jQuery( post_listing ).is(":visible") ) {
					action = 'close';
				} else {
					action = 'open';
				}
			} 

			if ( action == 'open' ) {
				jQuery( post_listing ).slideDown('slow', function(){
					jQuery( event.currentTarget).removeClass('ld-course-builder-action-show');
					jQuery( event.currentTarget).addClass('ld-course-builder-action-hide');
					
				});
			} else {
				jQuery( post_listing ).slideUp('slow', function(){
					jQuery( event.currentTarget).addClass('ld-course-builder-action-show');
					jQuery( event.currentTarget).removeClass('ld-course-builder-action-hide');
				});
			}
		}


		jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'click', 'li', selector_click_item );
		function selector_click_item ( event ) {
			var selector_container = selector_get_type_from_item( event.currentTarget );
			if ( jQuery( event.currentTarget ).hasClass( 'ld-disabled' ) ) {
				jQuery( event.currentTarget ).removeClass( 'ld-selected' );
			} else {
				
				if ( jQuery( event.currentTarget ).hasClass( 'ld-selected' ) ) {
					jQuery( event.currentTarget ).removeClass( 'ld-selected' );
				} else {
					jQuery( event.currentTarget ).addClass( 'ld-selected' );
				}
			}
		}

		jQuery( '#learndash_builder_box_wrap' ).on( 'click', '.learndash_selectors .pager-info button', selector_pager_click );
		function selector_pager_click( e ) {
			e.stopImmediatePropagation();
			var selector_container = jQuery(e.currentTarget).parents( '.learndash-selector-container' );
			
			if ( typeof selector_container !== 'undefined' ) {
				var selector_type = jQuery(selector_container).data('ld-type');
				if ( typeof selector_type !== 'undefined' ) {
				
					var selector_paged = jQuery(e.currentTarget ).data('page');
					if ( typeof selector_type === 'undefined' ) {
						selector_paged = 1;
					}

					selector_pager_process( selector_paged, selector_type, selector_container );
				}
			}
			return false;
		}

		function selector_pager_process( selector_paged, selector_type, selector_container ) {
			var post_data = {
				'action': 'learndash_builder_selector_pager',
				'builder_data': get_builder_asset_post_data(),
				'builder_query_args': {
					'post_type': selector_type,
					'paged': selector_paged
				},
			};

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function(reply_data) {
					if ( typeof reply_data !== 'undefined') {
						
						if (typeof reply_data['selector_pager'] !== 'undefined') {
							jQuery('.pager-info', selector_container).html( reply_data['selector_pager'] );
						}

						if (typeof reply_data['selector_rows'] !== 'undefined') {
							jQuery('ul.learndash-selector-post-listing', selector_container).html( reply_data['selector_rows'] );
							jQuery('ul.learndash-selector-post-listing li', selector_container).draggable( draggable_objects[selector_type] );
					
							selector_update_disabled_items( selector_type );
						}
					}
				}
			});
		}

		if ( jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-search input' ).length) {
		
			// Hold reference to our interval loop for key press
			var search_interval_ref;
					
			// Set time for .25 seconds. 1/4 of a second. 
			var search_timeout = 250; 

			var search_el = null;

			var search_value = '';
			var search_width = null;

			// Activate logic on fucus.
			jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-search input' ).focus( function( e ) {
				var selector_container = jQuery( e.currentTarget ).parents( '.learndash-selector-container' );				
				if ( typeof selector_container !== 'undefined' ) {
					var selector_type = jQuery(selector_container).data('ld-type');					
					if ( typeof selector_type !== 'undefined' ) {
				
						search_el = jQuery( this );
				
						search_width = search_el.width();
						console.log('search_width[%o]', search_width);
						
						jQuery('.learndash-selector-pager', selector_container).hide();
						jQuery('.learndash-selector-search', selector_container).width('100%');
						
						search_interval_ref = setInterval( function() {
							var search_value_tmp = search_el.val();

							// If search was cleared we need to reset the display to show the regular non-search items
							if ( ( search_value_tmp.length == 0 ) && ( search_value != search_value_tmp ) ) {
								// Here clear the search results and show the normal lis of items. 
								search_value = search_value_tmp;
								selector_search( selector_container, search_value );
								
							} else {
								if ( ( search_value_tmp.length >= 3 ) && ( search_value != search_value_tmp ) ) {
									search_value = search_value_tmp;
									selector_search( selector_container, search_value );
								}
							}
					
							if ( !search_el.is(':focus') ) {
								clearInterval( search_interval_ref );
								jQuery('.learndash-selector-pager', selector_container).show();
								jQuery('.learndash-selector-search', selector_container).width(search_width+'px');
								return;
							}
					
						}, search_timeout );
					}
				}
			});
		}
	}

	function selector_search( selector_container, search_value ) {
		if ( ( typeof selector_container !== 'undefined' ) && ( typeof search_value !== 'undefined' ) ) {
			var selector_type = jQuery( selector_container ).data('ld-type');
			if ( typeof selector_type !== 'undefined' ) {
				var post_data = {
					'action': 'learndash_builder_selector_search',
					'builder_data': get_builder_asset_post_data(),
					'builder_query_args': {
						'post_type': selector_type,
						'paged': 1,
						's': search_value
					},
				};
				
				jQuery.ajax({
					type: "POST",
					url: ajaxurl,
					dataType: "json",
					cache: false,
					data: post_data,
					error: function(jqXHR, textStatus, errorThrown ) {
						//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
					},
					success: function(reply_data) {
			
						if ( typeof reply_data !== 'undefined') {
							if ( typeof reply_data['selector_rows'] !== 'undefined') {
								jQuery('ul.learndash-selector-post-listing', selector_container).html( reply_data['selector_rows'] );
								jQuery('ul.learndash-selector-post-listing li', selector_container).draggable( draggable_objects[selector_type] );
				
								selector_update_disabled_items( selector_type );
							}
						}
					}
				});
			}
		}
	}



	function build_html_element_map( ) {
		var builder_items = new Object();
		
		if ( jQuery('#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items').length) {
			jQuery( '#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items' ).children().each(function( lesson_item_idx, lesson_item) {

				var lesson_id = jQuery( lesson_item ).data('ld-id');
				
				builder_items_count += 1;
				builder_items['sfwd-lessons:'+lesson_id.toString()] = {};

				jQuery('.ld-course-builder-lesson-topic-items', lesson_item ).children().each(function( topic_item_idx, topic_item) {
					
					var topic_id = jQuery( topic_item ).data('ld-id');
					
					builder_items_count += 1;
					builder_items['sfwd-lessons:'+lesson_id.toString()]['sfwd-topic:'+topic_id.toString()] = {};
					
					jQuery('.ld-course-builder-topic-quiz-items', topic_item).children().each(function( topic_quiz_item_idx, topic_quiz_item) {
						var topic_quiz_id = jQuery( topic_quiz_item ).data('ld-id');
						builder_items['sfwd-lessons:'+lesson_id.toString()]['sfwd-topic:'+topic_id.toString()]['sfwd-quiz:'+topic_quiz_id.toString()] = {};
					});
				});

				jQuery('.ld-course-builder-lesson-quiz-items', lesson_item).children().each(function( lesson_quiz_item_idx, lesson_quiz_item) {
					var lesson_quiz_id = jQuery( lesson_quiz_item ).data('ld-id');
					builder_items['sfwd-lessons:'+lesson_id.toString()]['sfwd-quiz:'+lesson_quiz_id.toString()] = {};
				});
				
			});

			var has_global_quizzes = false;
			jQuery('#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-course-quiz-items' ).children().each(function( quiz_item_idx, quiz_item) {
				var quiz_id = jQuery( quiz_item ).data('ld-id');
				has_global_quizzes = true;
				builder_items['sfwd-quiz:'+quiz_id.toString()] = {};
			});
			
			if ( has_global_quizzes == true )
				builder_items_count += 1;
		} else if ( jQuery('#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-question-items').length) {
			jQuery('#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-question-items').children().each(function (question_item_idx, question_item) {
				var question_id = jQuery(question_item).data('ld-id');
				var points = jQuery('.ld-course-builder-points', question_item).data('ld-points');

				builder_items_points = parseInt( builder_items_points ) + parseInt( points );

				builder_items_count += 1;
				builder_items['sfwd-question:' + question_id.toString()] = {};
			});
		}
		return builder_items;
	}

	// The following taken from https://jsfiddle.net/KyleMit/Geupm/2/
	/////////////////////////////////////////////////////////////////
	var draggable_objects = [];
	
	draggable_objects['sfwd-lessons'] = {
		cancel: '.ld-disabled',
		handle: ".ld-course-builder-action-lesson-move",
		connectToSortable: '#learndash_builder_box_wrap .ld-course-builder-lesson-items',
		helper: function() {
			var selected = jQuery('#learndash-selector-post-listing-sfwd-lessons li.ld-selected');
			
			if (selected.length === 0) {
				selected = $(this);
			}
			
			return ld_course_builder_draggable_helper( selected );
		}		
	};
	
	draggable_objects['sfwd-topic'] = {
		cancel: '.ld-disabled',
		handle: ".ld-course-builder-action-topic-move",
        connectToSortable: '#learndash_builder_box_wrap .ld-course-builder-topic-items',
		//cursor: 'move',
		helper: function() {
			var selected = jQuery('#learndash-selector-post-listing-sfwd-topic li.ld-selected');
			if (selected.length === 0) {
				selected = $(this);
			}

			return ld_course_builder_draggable_helper( selected );
		}
    };
	
	draggable_objects['sfwd-quiz'] = {
		cancel: '.ld-disabled',
		handle: ".ld-course-builder-action-quiz-move",
        connectToSortable: '#learndash_builder_box_wrap .ld-course-builder-quiz-items',
		//cursor: 'move',
		helper: function() {
			var selected = jQuery('#learndash-selector-post-listing-sfwd-quiz li.ld-selected');
			if (selected.length === 0) {
				selected = $(this);
			}
			return ld_course_builder_draggable_helper( selected );
		}
    };
	
	draggable_objects['sfwd-question'] = {
		cancel: '.ld-disabled',
		handle: ".ld-course-builder-action-question-move",
		connectToSortable: '#learndash_builder_box_wrap .ld-course-builder-question-items',
		//cursor: 'move',
		helper: function () {
			var selected = jQuery('#learndash-selector-post-listing-sfwd-question li.ld-selected');
			if (selected.length === 0) {
				selected = $(this);
			}
			return ld_course_builder_draggable_helper(selected);
		}
	};

	// Common helper function for the draggables
	function ld_course_builder_draggable_helper( selected ) {
		
		var container = $('<div/>').attr('id', 'ld-selector-draggable-group');

		if ( ( typeof selected !== 'undefined' ) && ( selected.length ) ) {
			var max_width = 0;
			jQuery(selected).each(function( selected_idx, selected_el ) {
				//console.log('selected_el[%o]', selected_el );
				jQuery('.ld-course-builder-sub-actions', selected_el).hide();
				var el_width = jQuery(selected_el).outerWidth();
				if ( el_width > max_width )
					max_width = el_width;
			});
		
			container.css('list-style', 'none');
			container.css('width', max_width + 'px');
			container.append(selected.clone());
		}
		return container;
	}
		
	var sortable_objects = [];
	
	sortable_objects['sfwd-lessons'] = {
        //containment: "#learndash_builder_box_wrap",
		items: "> div.ld-course-builder-lesson-item",
		handle: ".ld-course-builder-action-lesson-move",
		tolerance: 'pointer',
		opacity: 0.7,
		revert: 300,
        delay: 150,
        dropOnEmpty: true,
        placeholder: "movable-placeholder",
        start: function(e, ui) {
            ui.placeholder.height(ui.helper.outerHeight());
			jQuery('li', ui.helper).css('clear', 'both');
			jQuery( 'span.ld-course-builder-sub-actions', ui.helper ).hide();	
        },
		stop: function(event, ui) {
			var element_id = jQuery( ui.item ).attr('id');
			
			if ( element_id == 'ld-selector-draggable-group' ) {
				var new_lessons = '';
				
				var ld_selected = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-lessons').data('ld-selected');
				if ( typeof ld_selected === 'undefined' ) {
					ld_selected = [];
				}

				ui.item.children('li').each( function( ) {
					var inner_el = this;

					var element_ld_id = jQuery( inner_el ).data('ld-id');
					ld_selected.push( parseInt( element_ld_id ) );

					jQuery( 'span.ld-course-builder-sub-actions', inner_el ).hide();
										
					new_lessons = new_lessons+'<div id="ld-course-builder-lesson-item-'+element_ld_id+'" class="ld-course-builder-item ld-course-builder-lesson-item" data-ld-type="sfwd-lessons" data-ld-id="'+element_ld_id+'">'+jQuery( inner_el ).html()+'</div>';
				
					if ( typeof element_ld_id !== 'undefined' ) {
						selector_set_item_disabled( 'sfwd-lessons', element_ld_id, true);
						selector_set_item_selected( 'sfwd-lessons', element_ld_id, false );
					}
				});
				jQuery( ui.item ).replaceWith( new_lessons );
				
				// after the Lesson(s) added we reset the jQuery UI sortable logic to include the new child items
				jQuery("#learndash_builder_box_wrap .ld-course-builder-topic-items").sortable( sortable_objects['sfwd-topic'] );
				jQuery("#learndash_builder_box_wrap .ld-course-builder-quiz-items").sortable( sortable_objects['sfwd-quiz'] );

				ld_selected = jQuery.unique( ld_selected );	
				jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-lessons' ).data( 'ld-selected', ld_selected );
								
				update_builder_items_element();
				
			} else {
				jQuery( 'span.ld-course-builder-sub-actions', ui.item ).hide();
				update_builder_items_element();
			}
		}
    };
		
	sortable_objects['sfwd-topic'] = {
        //containment: "#learndash_builder_box_wrap",
        items: "> div.ld-course-builder-topic-item",
        handle: ".ld-course-builder-action-topic-move",
        connectWith: '.ld-course-builder-topic-items',
        placeholder: "movable-placeholder",
		tolerance: 'pointer',
        opacity: 0.7,
        revert: 300,
        delay: 150,
        dropOnEmpty: true,
        start: function(e, ui) {
            ui.placeholder.height(ui.helper.outerHeight());
			ui.placeholder.width('100%');
			jQuery( 'span.ld-course-builder-sub-actions', ui.helper ).hide();
			
			//console.log('ui.item[%o]', ui.item );
			
			//var parent_builder_item = jQuery( ui.item ).parents('.ld-course-builder-item').first();
			//console.log('parent_builder_item[%o]', parent_builder_item );
			//if ( typeof ld_selected === 'undefined' ) {
			//	var parent_id_start = jQuery( parent_builder_item ).attr('id');
			//	console.log('parent_id_start[%o]', parent_id_start );
			//} 
        },
		stop: function(event, ui) {
			var element_id = jQuery( ui.item ).attr('id');
			if ( element_id == 'ld-selector-draggable-group' ) {
				
				var new_lessons = '';
				var ld_selected = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-topic').data('ld-selected');
				if ( typeof ld_selected === 'undefined' ) {
					ld_selected = [];
				}

				ui.item.children('li').each( function( ) {
					var inner_el = this;

					var element_ld_id = jQuery( inner_el ).data('ld-id');
					ld_selected.push( parseInt( element_ld_id ) );

					jQuery( 'span.ld-course-builder-sub-actions', inner_el ).hide();
										
					new_lessons = new_lessons+'<div id="ld-course-builder-topic-item-'+element_ld_id+'" class="ld-course-builder-item ld-course-builder-topic-item" data-ld-type="sfwd-topic" data-ld-id="'+element_ld_id+'">'+jQuery( inner_el ).html()+'</div>';

					if ( typeof element_ld_id !== 'undefined' ) {
						selector_set_item_disabled( 'sfwd-topic', element_ld_id, true);
						jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-topic li#ld-post-'+element_ld_id).removeClass('ld-selected');
					}
				});
				jQuery( ui.item ).replaceWith( new_lessons );

				// after the Lesson(s) added we reset the jQuery UI sortable logic to include the new child items
				jQuery("#learndash_builder_box_wrap .ld-course-builder-quiz-items").sortable( sortable_objects['sfwd-quiz'] );

				ld_selected = jQuery.unique( ld_selected );
				jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-topic' ).data( 'ld-selected', ld_selected );
				
				update_builder_items_element();
				
			} else {
				jQuery( ui.item ).addClass('ld-course-builder-item-update');
				
				//var parent_builder_item = jQuery( ui.item ).parents('.ld-course-builder-item').first();
				//console.log('parent_builder_item[%o]', parent_builder_item );
				//if ( typeof ld_selected === 'undefined' ) {
				//	var parent_id_end = jQuery( parent_builder_item ).attr('id');
				//	console.log('parent_id_end[%o]', parent_id_end );
				//}
				
				jQuery( 'span.ld-course-builder-sub-actions', ui.item ).hide();
				update_builder_items_element();
			}
		}
    };
	
	sortable_objects['sfwd-quiz'] = {
        //containment: "#learndash_builder_box_wrap",
        items: "> div.ld-course-builder-quiz-item",
        handle: ".ld-course-builder-action-quiz-move",
        connectWith: '.ld-course-builder-quiz-items',
        placeholder: "movable-placeholder",
		tolerance: 'pointer',
        opacity: 0.7,
        revert: 300,
        delay: 150,
        dropOnEmpty: true,
        start: function(e, ui) {
            ui.placeholder.height(ui.helper.outerHeight());
			ui.placeholder.width('100%');
			jQuery( 'span.ld-course-builder-sub-actions', ui.helper ).hide();	
        },
		stop: function(event, ui) {
			var element_id = jQuery( ui.item ).attr('id');
			if ( element_id == 'ld-selector-draggable-group' ) {
				
				var new_lessons = '';
				var ld_selected = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-quiz').data('ld-selected');
				if ( typeof ld_selected === 'undefined' ) {
					ld_selected = [];
				}

				ui.item.children('li').each( function( ) {
					var inner_el = this;

					var element_ld_id = jQuery( inner_el ).data('ld-id');
					ld_selected.push( parseInt( element_ld_id ) );
					
					jQuery( 'span.ld-course-builder-sub-actions', inner_el ).hide();
										
					new_lessons = new_lessons+'<div id="ld-course-builder-quiz-item-'+element_ld_id+'" class="ld-course-builder-item ld-course-builder-quiz-item" data-ld-type="sfwd-quiz" data-ld-id="'+element_ld_id+'">'+jQuery( inner_el ).html()+'</div>';
					
					if ( typeof element_ld_id !== 'undefined' ) {
						selector_set_item_disabled( 'sfwd-quiz', element_ld_id, true);
						jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-quiz li#ld-post-'+element_ld_id).removeClass('ld-selected');
					}
				});
				jQuery( ui.item ).replaceWith( new_lessons );

				ld_selected = jQuery.unique( ld_selected );
				jQuery( '#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-quiz' ).data( 'ld-selected', ld_selected );
				update_builder_items_element();
				
			} else if ( element_id == 'ld-draggable-builder-group' ) {
				
				ui.item.children('div').each( function( ) {
					var inner_el = this;
					jQuery(inner_el).removeClass('ld-selected');
				});
				
			} else {
				jQuery( ui.item ).addClass('ld-course-builder-item-update');
				jQuery( 'span.ld-course-builder-sub-actions', ui.item ).hide();
				update_builder_items_element();
			}
		},
    };
		
	sortable_objects['sfwd-question'] = {
		//containment: "#learndash_builder_box_wrap",
		items: "> div.ld-course-builder-question-item",
		handle: ".ld-course-builder-action-question-move",
		connectWith: '.ld-course-builder-question-items',
		placeholder: "movable-placeholder",
		tolerance: 'pointer',
		opacity: 0.7,
		revert: 300,
		delay: 150,
		dropOnEmpty: true,
		start: function (e, ui) {
			ui.placeholder.height(ui.helper.outerHeight());
			ui.placeholder.width('100%');
			jQuery('span.ld-course-builder-sub-actions', ui.helper).hide();
		},
		stop: function (event, ui) {
			var element_id = jQuery(ui.item).attr('id');
			if (element_id == 'ld-selector-draggable-group') {

				var new_lessons = '';
				var ld_selected = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-question').data('ld-selected');
				if (typeof ld_selected === 'undefined') {
					ld_selected = [];
				}

				ui.item.children('li').each(function () {
					var inner_el = this;

					var element_ld_id = jQuery(inner_el).data('ld-id');
					ld_selected.push(parseInt(element_ld_id));

					jQuery('span.ld-course-builder-sub-actions', inner_el).hide();

					new_lessons = new_lessons + '<div id="ld-course-builder-question-item-' + element_ld_id + '" class="ld-course-builder-item ld-course-builder-question-item" data-ld-type="sfwd-question" data-ld-id="' + element_ld_id + '">' + jQuery(inner_el).html() + '</div>';

					if (typeof element_ld_id !== 'undefined') {
						selector_set_item_disabled('sfwd-question', element_ld_id, true);
						jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-question li#ld-post-' + element_ld_id).removeClass('ld-selected');
					}
				});
				jQuery(ui.item).replaceWith(new_lessons);

				ld_selected = jQuery.unique(ld_selected);
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-sfwd-question').data('ld-selected', ld_selected);
				update_builder_items_element();

			} else if (element_id == 'ld-draggable-builder-group') {

				ui.item.children('div').each(function () {
					var inner_el = this;
					jQuery(inner_el).removeClass('ld-selected');
				});

			} else {
				jQuery(ui.item).addClass('ld-course-builder-item-update');
				jQuery('span.ld-course-builder-sub-actions', ui.item).hide();
				update_builder_items_element();
			}
		},
	};

    // Draggable / Sortable Lessons
    jQuery('#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-sfwd-lessons li').draggable( draggable_objects['sfwd-lessons'] );
    jQuery('#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-lesson-items').sortable( sortable_objects['sfwd-lessons'] );
	
	
    // Draggable / Sortable Topics
    jQuery( '#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-sfwd-topic li' ).draggable( draggable_objects['sfwd-topic'] );
    jQuery( '#learndash_builder_box_wrap .ld-course-builder-topic-items' ).sortable( sortable_objects['sfwd-topic'] );

    // Draggable / Sortable Quizzes
    jQuery( '#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-sfwd-quiz li' ).draggable( draggable_objects['sfwd-quiz'] );
    jQuery( '#learndash_builder_box_wrap .ld-course-builder-quiz-items' ).sortable( sortable_objects['sfwd-quiz'] );

	// Draggable / Sortable Questions
	jQuery('#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-sfwd-question li').draggable(draggable_objects['sfwd-question']);
	jQuery('#learndash_builder_box_wrap .ld-course-builder-question-items').sortable(sortable_objects['sfwd-question']);

	function update_builder_items_element() {
		cb_form_unsaved = true;
		
		builder_items_count = 0;
		builder_items_points = 0;

		var builder_elements = build_html_element_map();
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items .learndash_builder_items_total .learndash_builder_items_total_value').html( builder_items_count );		
		
		jQuery('#learndash_builder_box_wrap .learndash_builder_items .learndash_builder_points_total_value').html(builder_items_points);


		var builder_elements_str = JSON.stringify( builder_elements );
		if ('sfwd-courses' == ld_typenow ) {
			jQuery( '#learndash_builder_box_wrap input#learndash_builder_data' ).val(builder_elements_str);
		} else if ('sfwd-quiz' == ld_typenow) {
			jQuery('#learndash_builder_box_wrap input#learndash_builder_data').val(builder_elements_str);
		}
	}
	
	jQuery( '#learndash_builder_box_wrap .learndash_builder_items').on('click', '.ld-course-builder-action-show-hide', show_hide_sub_elements );	
	function show_hide_sub_elements( e, action ) {
		e.stopImmediatePropagation();
		
		var builder_item = jQuery( e.currentTarget).parents('.ld-course-builder-item').first();
		var closest_sub_items = jQuery( builder_item ).find('.ld-course-builder-sub-items').first();
		
		if ( ( typeof action === 'undefined' ) || ( ( action != 'open') && ( action != 'close' ) ) )  {
			if ( jQuery( closest_sub_items ).is(":visible") ) {
				action = 'close';
			} else {
				action = 'open';
			}
		} 
		
		if ( action == 'open' ) {
			jQuery( closest_sub_items ).slideDown('slow', function(){
				jQuery( e.currentTarget).removeClass('ld-course-builder-action-show');
				jQuery( e.currentTarget).addClass('ld-course-builder-action-hide');				
			});
		} else {
			jQuery( closest_sub_items ).slideUp('slow', function(){
				jQuery( e.currentTarget).addClass('ld-course-builder-action-show');
				jQuery( e.currentTarget).removeClass('ld-course-builder-action-hide');
				
			});
		}
	}

	jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).on( 'click', '.ld-course-builder-action-remove', builder_remove_element );
		
	function builder_remove_element( event ) {
		var remove_item = jQuery( event.currentTarget ).closest( '.ld-course-builder-item' );
		if ( ( typeof remove_item === 'undefined' ) || ( remove_item === '' ) )
			return;
	
		var remove_item_id = jQuery( remove_item ).attr('data-ld-id');
		var remove_item_type = jQuery( remove_item ).attr('data-ld-type');

		var confirm_title = get_builder_asset_message('confirm_remove_'+remove_item_type);
		if ( confirm_title === '' ) {
			confirm_title = 'Confirm delete';
		}

		// Be nice and add the title to the confirm popup
		var remove_item_title = jQuery( 'span.ld-course-builder-title-text', remove_item ).html();
		if ( remove_item_title !== '' ) {
			confirm_title += '\r\n\r\n' + remove_item_title;
		}

		if ( confirm( confirm_title ) ) {
			var child_items = jQuery( remove_item ).find('.ld-course-builder-item');
			if ( typeof child_items !== 'undefined' ) {

				jQuery( child_items ).each(function( child_item_idx, child_item ) {
					var item_type = jQuery( child_item ).attr( 'data-ld-type' );
					var item_id = jQuery(child_item).attr('data-ld-id');
					if (( typeof item_type !== 'undefined' ) && ( typeof item_id !== 'undefined' )) {
						selector_set_item_disabled(item_type, item_id, false);
					}
				});
			}
		
			selector_set_item_disabled( remove_item_type, remove_item_id, false);
				
			// Finally remove DOM element
			jQuery( remove_item ).remove();
		
			update_builder_items_element();
		}
	}

	jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'click', '.ld-course-builder-action-trash', builder_trash_element );
	function builder_trash_element( event ) {
		var trash_item = jQuery( event.currentTarget ).closest( 'li' );
		if ( ( typeof trash_item === 'undefined' ) || ( trash_item === '' ) )
			return;
	
		var trash_item_id = jQuery( trash_item ).attr('data-ld-id');		
		var trash_item_type = jQuery( trash_item ).attr('data-ld-type');
		
		var confirm_title = get_builder_asset_message('confirm_trash_'+trash_item_type);
		if ( confirm_title === '' ) {
			confirm_title = 'Confirm delete';
		}

		// Be nice and add the title to the confirm popup
		var trash_item_title = jQuery( 'span.ld-course-builder-title-text', trash_item ).html();
		if ( trash_item_title !== '' ) {
			confirm_title += '\r\n\r\n' + trash_item_title;
		}

		if ( confirm( confirm_title ) ) {
			jQuery( trash_item ).remove();
			selector_update_empty( trash_item_type );

			var post_data = {
				'action': 'learndash_builder_selector_step_trash',
				'builder_data': get_builder_asset_post_data(),
				'builder_query_args': {
					'post_id': trash_item_id,
					'post_type': trash_item_type,
				},
			};

			jQuery.ajax({
				type: "POST",
				url: ajaxurl,
				dataType: "json",
				cache: false,
				data: post_data,
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				},
				success: function(reply_data) {
					if ( typeof reply_data !== 'undefined') {
						if ( reply_data['status'] === true ) {
							
						}
					}
				}
			});
		}
	}
	
	// Simple handler to show when changes have been made
	window.onbeforeunload = function() {
		if ((typeof learndash_builder_assets[ld_typenow]['post_data']['builder_editor'] !== 'undefined') && (learndash_builder_assets[ld_typenow]['post_data']['builder_editor'] === 'classic' )) {
			if ( cb_form_unsaved === true ) {
				var learndash_unload_message = get_builder_asset_message('learndash_unload_message');
				if ((typeof learndash_unload_message !== 'undefined') && (learndash_unload_message !== '')) {
					return learndash_unload_message;
				}
			}
		}
	}

	// If we are submitting the form then don't show the warning. 
	var parent_form = jQuery( '#learndash_builder_box_wrap' ).parents( 'form' );
	if ( typeof parent_form !== 'undefined' ) {
		jQuery( parent_form ).submit(function() {
			cb_form_unsaved = false;
		});
	}


	if ( touchEvent == 'hover' ) {
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).on( 'mouseover', '.ld-course-builder-actions', builder_step_show_element_actions );
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).on( 'mouseleave', '.ld-course-builder-actions', builder_step_hide_element_actions );

		jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'mouseover', '.ld-course-builder-actions', builder_step_show_element_actions );
		jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'mouseleave', '.ld-course-builder-actions', builder_step_hide_element_actions );


	} else if ( touchEvent == 'touchstart' ) {
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items' ).on( 'click', '.ld-course-builder-actions', builder_step_show_element_actions );
		jQuery( '#learndash_builder_box_wrap .learndash_selectors' ).on( 'click', '.ld-course-builder-actions', builder_step_show_element_actions );
	}
		
	function builder_step_show_element_actions( event ) {
		event.stopImmediatePropagation();

		var element_move_action = jQuery( event.currentTarget );
		var sub_actions = jQuery( '.ld-course-builder-sub-actions', element_move_action );
		if ( typeof sub_actions !== 'undefined' ) {
		
			// We need to check if the element is being dragged. If it is being dragged it will have 
			// a parent container div#ld-selector-draggable-group in that case we hide the sub-actions. 
			var parent_id = jQuery(element_move_action).closest('div#ld-selector-draggable-group').attr('id');		
			if ( typeof parent_id === 'undefined' ) {		
				if ( jQuery( sub_actions ).is(':visible' ) ) {
					if ( touchEvent == 'touchstart' ) {
						//jQuery( sub_actions ).hide( 'slide', {direction: 'left'}, 150);
						jQuery( sub_actions ).hide();
					}
				} else {
					// First hide all visible sub-action elements 
					if ( touchEvent == 'touchstart' ) {
						jQuery( '#learndash_builder_box_wrap .ld-course-builder-sub-actions').hide();
					}
				
					var pos = jQuery(element_move_action).position();
			    	var width = jQuery(element_move_action).outerWidth();
			    	var sub_actions_width = jQuery(sub_actions).outerWidth();

					if ( jQuery( 'body' ).hasClass( 'rtl' ) ) {
						var position_left = (pos.left - sub_actions_width + 5) + "px";
					} else {
						var position_left = (pos.left + width) + "px";
					}

					jQuery( sub_actions ).css({
						'background-color': "#B8B8B8",
						height: '20px',
						position: "absolute",
						top: pos.top + "px",
						left: position_left
					}).show();
					//}).show( 'slide', {direction: 'left'}, 600);
				}
			} 
		} 
	}
	
	function builder_step_hide_element_actions( event ) {
		var element_move_action = jQuery( event.currentTarget );
		var sub_actions = jQuery( '.ld-course-builder-sub-actions', element_move_action );
		if ( typeof sub_actions !== 'undefined' ) {
			//jQuery( sub_actions ).hide( 'slide', {direction: 'left'}, 600);
			jQuery( sub_actions ).hide();
		}
	}	
		

	jQuery( '#learndash_builder_box_wrap' ).on( 'mouseover', '.ld-course-builder-title', builder_edit_title_show_pencil );
	jQuery( '#learndash_builder_box_wrap' ).on( 'mouseleave', '.ld-course-builder-title', builder_edit_title_hide_pencil );
	function builder_edit_title_show_pencil( event ) {
		event.stopImmediatePropagation();

		var show_pencil = true;

		// Disable showing the pencil on disabled items. 
		var parent_el = jQuery( event.currentTarget ).closest('.ld-course-builder-item')[0];
		if ( typeof parent_el !== 'undefined' ) {
			if ( jQuery( parent_el ).hasClass('ld-disabled' ) ) {
				show_pencil = false;
			}

			if ( jQuery( parent_el ).hasClass('ld-course-builder-title-edit' ) ) {
				show_pencil = false;
			}

			if ( show_pencil == true ) {
				jQuery( '.ld-course-builder-edit-title-pencil', event.currentTarget ).show();
			}
		}
	}
	
	function builder_edit_title_hide_pencil( event ) {
		event.stopImmediatePropagation();
		jQuery( '.ld-course-builder-edit-title-pencil', event.currentTarget ).hide();
	}
	
	jQuery( '#learndash_builder_box_wrap' ).on( 'click', '.ld-course-builder-title', builder_edit_title );
	function builder_edit_title( event ) {
		event.stopImmediatePropagation();

		var title_el = event.currentTarget;

		var parent_el = jQuery( title_el ).parents('.ld-course-builder-item');
		if ( typeof parent_el !== 'undefined' ) {
		
			if ( jQuery( parent_el ).hasClass('ld-disabled') ) return;
			if ( jQuery( parent_el ).hasClass('ld-course-builder-title-edit') ) return;
			else jQuery( parent_el ).addClass('ld-course-builder-title-edit');

			if ( jQuery( parent_el ).hasClass('ld-selected') ) jQuery( parent_el ).removeClass('ld-selected');

			//jQuery( parent_el ).addClass('ld-course-builder-title-edit');
		
			// Hide the actions menu when editing title. This prevents moving the element also yields more space
			jQuery( '.ld-course-builder-actions', parent_el ).hide();
		
			var title_org = jQuery( '.ld-course-builder-title-text', title_el ).html();

			//jQuery( '.ld-course-builder-title-text', title_el ).html( '<input style="width:'+parent_el_width+'px" type="text" value="'+title_org+'" />' );
			jQuery( '.ld-course-builder-title-text', title_el ).html( '<input type="text" value="'+title_org+'" />' );
			
			jQuery( '.ld-course-builder-title-text input', title_el ).focus();
			jQuery( '.ld-course-builder-title-text input', title_el ).select();
		
			jQuery( '.ld-course-builder-edit-title-pencil', title_el ).hide();
			jQuery( '.ld-course-builder-edit-title-ok', title_el ).show();
			jQuery( '.ld-course-builder-edit-title-cancel', title_el ).show();

			jQuery( title_el ).on( 'click', '.ld-course-builder-edit-title-ok', function( event_confirm ) {
				handle_title_save(event_confirm);
			});

			jQuery( title_el ).on('keypress', ':focus', function (event_confirm) {
				if (event_confirm.keyCode == 13) {
					handle_title_save(event_confirm);
				}
			});

			function handle_title_save(event_confirm) {
				event_confirm.stopImmediatePropagation();

				var parent_el = jQuery(event_confirm.currentTarget).parents('.ld-course-builder-item');

				jQuery(parent_el).removeClass('ld-course-builder-title-edit');
				var title_new = jQuery('.ld-course-builder-title-text input', title_el).val();

				jQuery('.ld-course-builder-title-text input', title_el).remove();
				jQuery('.ld-course-builder-edit-title-ok', title_el).hide();
				jQuery('.ld-course-builder-edit-title-cancel', title_el).hide();

				// Re-show the actions menu
				jQuery('.ld-course-builder-actions', parent_el).show();

				if ((title_new != '') && (title_new != title_org)) {
					jQuery('.ld-course-builder-title-text', title_el).html(title_new);

					// Update our original title value with the new title.
					title_org = title_new;

					var parent_el = jQuery(title_el).parents('.ld-course-builder-item');
					if (typeof parent_el !== 'undefined') {
						var step_id = jQuery(parent_el).data('ld-id');
						var step_type = jQuery(parent_el).data('ld-type');

						var post_data = {
							'action': 'learndash_builder_selector_step_title',
							'builder_data': get_builder_asset_post_data(),
							'builder_query_args': {
								'new_title': title_new,
								'post_id': step_id,
								'post_type': step_type,
							}
						};
						console.log('post_data[%o]', post_data);

						jQuery.ajax({
							type: "POST",
							url: ajaxurl,
							dataType: "json",
							cache: false,
							data: post_data,
							error: function (jqXHR, textStatus, errorThrown) {
								//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
							},
							success: function (reply_data) {
								if ((typeof reply_data['status'] !== 'undefined') && (reply_data['status'] === true)) {
									var selector_item = jQuery('#learndash_builder_box_wrap .learndash_selectors li#ld-post-' + step_id);
									jQuery('.ld-course-builder-title-text', selector_item).html(title_new);

									//var builder_item = jQuery( '#learndash_builder_box_wrap .learndash_builder_items div#ld-course-builder-lesson-item-'+step_id );
									var builder_item = jQuery('#learndash_builder_box_wrap .learndash_builder_items #ld-post-' + step_id);
									jQuery('.ld-course-builder-title-text', builder_item).first().html(title_new);
								}
							}
						});
					}
				} else {
					jQuery('.ld-course-builder-title-text', title_el).html(title_org);
				}
			}

			jQuery( title_el ).on( 'click', '.ld-course-builder-edit-title-cancel', function( event_cancel ) {
				event_cancel.stopImmediatePropagation();
			
				var parent_el = jQuery( event_cancel.currentTarget ).parents('.ld-course-builder-item');
						
				jQuery( parent_el ).removeClass('ld-course-builder-title-edit');
				jQuery( '.ld-course-builder-title-text input', title_el ).remove();
				jQuery( '.ld-course-builder-edit-title-ok', title_el ).hide();
				jQuery( '.ld-course-builder-edit-title-cancel', title_el ).hide();

				// Re-show the actions menu
				jQuery( '.ld-course-builder-actions', parent_el ).show();
			
				if ( jQuery( '.ld-course-builder-action-show-hide', parent_el ).length ) {
					var selector_container = course_buider_get_parent_selector_container( parent_el );
					if ( ( typeof selector_container !== 'undefined' ) && ( jQuery( selector_container ).hasClass( 'learndash_builder_items' ) ) ) {
						jQuery( '.ld-course-builder-action-show-hide', parent_el ).show();
					}
				}
				jQuery( '.ld-course-builder-title-text', title_el ).html( title_org );
			});
		}
	}
	
	// Utility function. We need to see if an element is on the left or right side of CB. 
	function course_buider_get_parent_selector_container( el ) {
		if ( typeof el !== 'undefined' ) {
			var selector_container = jQuery( el ).closest( '.learndash_selectors' );
			if ( ( typeof selector_container !== 'undefined' ) && ( selector_container.length > 0 ) ) {
				return selector_container[0];
			} else {
				var builder_container = jQuery( el ).closest( '.learndash_builder_items' );
				if ( ( typeof builder_container !== 'undefined' ) && ( builder_container.length > 0 ) ) {
					return builder_container[0];
				}
			}
		}
	}
	
	function selector_update_disabled_items( selector_type, disabled_ids ) {
		if ( selector_type !== '' ) {
			var selector_container = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container[data-ld-type="'+selector_type+'"]' );
			if ( typeof selector_container !== 'undefined' ) {
				
				var selected_items = jQuery(selector_container).data('ld-selected');

				if (( typeof selected_items !== 'undefined' ) && ( selected_items.length )) {
					jQuery.each(selected_items, function( index, value ) {
						selector_set_item_disabled( selector_type, value, true);
					});
				}
			}
		}
	}
	
	function selector_update_empty( selector_type ) {
	
		if ( selector_type !== '' ) {
			var selector_container = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container[data-ld-type="'+selector_type+'"]' );
			
			if ( jQuery( 'ul#learndash-selector-post-listing-'+selector_type+' li', selector_container ).length ) {
				jQuery( '.learndash-selector-pager', selector_container ).show();
				jQuery( '.learndash-selector-search', selector_container ).show();
			} else {
				jQuery( '.learndash-selector-pager', selector_container ).hide();
				jQuery( '.learndash-selector-search', selector_container ).hide();
			}
		}	
	}
	
	
	function selector_set_item_disabled( selector_type, selected_id, disabled ) {
		if ( ( selector_type !== '' ) && ( selected_id !== '' ) )  {
			var disabled_items = jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type).data('ld-selected');
			if ( typeof disabled_items === 'undefined' ) {
				disabled_items = [];
			}
			
			var changed = false;
			if ( disabled === true ) {
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type+' ul.learndash-selector-post-listing li[data-ld-id="'+selected_id+'"]' ).addClass( 'ld-disabled' );
				if ( disabled_items.indexOf( parseInt( selected_id ) ) === -1 ) {
					disabled_items.push( parseInt( selected_id ) );
					changed = true;
				}
				
			} else {
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type+' ul.learndash-selector-post-listing li[data-ld-id="'+selected_id+'"]' ).removeClass( 'ld-disabled' );
				disabled_items = disabled_items.filter(function(e) { return e !== parseInt(selected_id) });
				changed = true;
			}
			if ( changed == true ) {
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type).data('ld-selected', disabled_items );
			}
		}
	}
	
	function selector_set_item_selected( selector_type, selected_id, selected ) {
		if ( ( selector_type !== '' ) || ( selected_id !== '' ) ) {
						
			if ( selected === true ) {
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type+' ul.learndash-selector-post-listing li[data-ld-id="'+selected_id+'"]').addClass( 'ld-selected' );
			} else {
				jQuery('#learndash_builder_box_wrap .learndash_selectors .learndash-selector-container-'+selector_type+' ul.learndash-selector-post-listing li[data-ld-id="'+selected_id+'"]').removeClass( 'ld-selected' );
			}
		}
	}
	
	function selector_get_type_from_item( el ) {
		if ( el !== '' ) {
			var selector_container = jQuery( el ).closest( '.learndash-selector-container' );
			if ( typeof selector_container !== 'undefined' ) {
				var selector_type = jQuery(selector_container).data('ld-type');
				return selector_type;
			}
		}
	}
		
	jQuery('.learndash_selectors').on('click', '.ld-course-builder-action-add', builder_add_new_step );
	function builder_add_new_step( event ) {
		event.stopImmediatePropagation();
		
		// Check to ensure we are not adding too quickly. This will show the spinner.
		if ( !jQuery( event.currentTarget ).hasClass( 'ld-course-builder-action-add-pending' ) ) {
			jQuery( event.currentTarget ).addClass( 'ld-course-builder-action-add-pending' );

			var selector_container = jQuery( event.currentTarget ).closest( '.learndash-selector-container' );
			if ( typeof selector_container !== 'undefined' ) {

				var selector_show_hide = jQuery( selector_container ).find( '.ld-course-builder-action-show-hide' );
				if ( jQuery( selector_show_hide ).hasClass( 'ld-course-builder-action-show' ) ) {
					jQuery( '.ld-course-builder-action-show-hide', selector_container ).trigger( 'click', ['open'] );
				}
		
				var selector_type = jQuery(selector_container).data('ld-type');				
				if ( typeof selector_type !== 'undefined' ) {
					
					var d = new Date();
					var n = d.getTime();
					var new_item_id = 'new-step-'+n;
					
					var first_item = jQuery( '.learndash-row-placeholder li', selector_container ).clone();
					//console.log('first_item[%o]', first_item);
					
					jQuery( first_item ).attr('id', new_item_id );
					jQuery( first_item ).attr('data-ld-id', '' );
					jQuery( first_item ).removeClass( 'ld-disabled' );
					jQuery( first_item ).removeClass( 'ld-selected' );
					jQuery( first_item ).removeClass( 'ld-course-builder-title-edit' );
					jQuery( first_item ).addClass( 'ld-new-step' );

					var title_el = jQuery( '.ld-course-builder-header .ld-course-builder-title', first_item );
					if ( jQuery( title_el ).hasClass( 'ld-course-builder-title-edit' ) ) { 
						if ( jQuery( 'input[type="text"]', title_el ).length ) {
							jQuery( 'input[type="text"]', title_el ).remove();
						}
					}
										
					jQuery( '#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-'+selector_type ).prepend( first_item );
					jQuery( '#learndash_builder_box_wrap .learndash_selectors ul#learndash-selector-post-listing-'+selector_type+' li' ).draggable( draggable_objects[selector_type] );
					jQuery( '#learndash_builder_box_wrap #'+new_item_id+' .ld-course-builder-title-text' ).trigger( 'click' );
					
					selector_update_empty( selector_type );
					
					builder_trigger_new_steps_ajax( );

					// now hide the spinner
					jQuery( event.currentTarget ).removeClass('ld-course-builder-action-add-pending');
				}
			}
		}
	}
	
	function builder_trigger_new_steps_ajax( ) {
		
		if ( ld_builder_new_step_ajax_pid == '' ) {
		
			if ( jQuery( '#learndash_builder_box_wrap .ld-new-step' ).length ) {
				var new_items_set = {};

				//jQuery( '#learndash_builder_box_wrap .ld-new-step' ).slice(0, 2).each( function( item_idx, item_el ) {
				jQuery( '#learndash_builder_box_wrap .ld-new-step' ).each( function( item_idx, item_el ) {
			
					var new_item = {};
					new_item.item_id = jQuery( item_el ).attr( 'id' );
					new_item.post_type = jQuery( item_el ).data( 'ld-type' );
				
					if ( jQuery( 'input[type="text"]', item_el ).length ) {
						new_item.post_title = jQuery( 'input[type="text"]', item_el ).val();
					} else {
						new_item.post_title = jQuery( '.ld-course-builder-title-text', item_el ).val();
					}
					new_items_set[new_item.item_id] = new_item;
				});

				if ( Object.keys(new_items_set).length > 0 ) {
				
					var post_data = {
						'action': 'learndash_builder_selector_step_new',
						'builder_data': get_builder_asset_post_data(),
						'builder_query_args': {
							'new_steps': new_items_set
						}
					};
					
					ld_builder_new_step_ajax_pid = jQuery.ajax({
						type: "POST",
						url: ajaxurl,
						dataType: "json",
						cache: false,
						data: post_data,
						error: function(jqXHR, textStatus, errorThrown ) {
							//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
						},
						success: function(reply_data) {
							if ( typeof reply_data !== 'undefined') {
								if ( typeof reply_data['new_steps'] !== 'undefined' ) {
									jQuery.each( reply_data['new_steps'], function ( old_step_id, new_step_set ) {
										if ( jQuery( '#learndash_builder_box_wrap #'+old_step_id ).length ) {
											if ( jQuery( '#learndash_builder_box_wrap #'+old_step_id ).hasClass( 'ld-new-step' ) ) {
												jQuery( '#learndash_builder_box_wrap #'+old_step_id ).removeClass( 'ld-new-step' );
												jQuery( '#learndash_builder_box_wrap #'+old_step_id ).attr('data-ld-id', new_step_set.post_id );
												jQuery( '#learndash_builder_box_wrap #'+old_step_id+' a.ld-course-builder-action-view' ).attr('href', new_step_set.view_url.replace(/&amp;/g, '&' ) );
												jQuery( '#learndash_builder_box_wrap #'+old_step_id+' a.ld-course-builder-action-edit' ).attr('href', new_step_set.edit_url.replace(/&amp;/g, '&' )  );
												jQuery( '#learndash_builder_box_wrap #'+old_step_id ).attr('id', 'ld-post-'+new_step_set.post_id );
											} 
										} 
									});
								}
							}
							ld_builder_new_step_ajax_pid = '';
							builder_trigger_new_steps_ajax();
						}
					});
				}
			}
		} 
	}
	
	function get_builder_asset_message( message_key ) {
		if (typeof learndash_builder_assets[ld_typenow]['messages'] !== 'undefined') {
			if ((typeof message_key !== 'undefined') && (typeof learndash_builder_assets[ld_typenow]['messages'][message_key] !== 'undefined')) {
				return learndash_builder_assets[ld_typenow]['messages'][message_key];
			} else {
				return learndash_builder_assets[ld_typenow]['messages'];
			}
		}
	}

	function get_builder_asset_post_data( data_key ) {
		if (typeof learndash_builder_assets[ld_typenow]['post_data'] !== 'undefined') {
			if ((typeof data_key !== 'undefined') && (typeof learndash_builder_assets[ld_typenow]['post_data'][data_key] !== 'undefined')) {
				return learndash_builder_assets[ld_typenow]['post_data'][data_key];
			} else {
				return learndash_builder_assets[ld_typenow]['post_data'];
			}
		}
	}

	jQuery('#learndash_builder_box_wrap .learndash_selectors').on('click', '.ld-show-all', builder_show_all_selectors );
	function builder_show_all_selectors( event ) {
		jQuery( '#learndash_builder_box_wrap .learndash_selectors h3.learndash-selector-header .ld-course-builder-action-show-hide').trigger('click', ['open'] );
	}

	jQuery('#learndash_builder_box_wrap .learndash_selectors').on('click', '.ld-hide-all', builder_hide_all_selectors );
	function builder_hide_all_selectors( event ) {
		jQuery( '#learndash_builder_box_wrap .learndash_selectors h3.learndash-selector-header .ld-course-builder-action-show-hide').trigger('click', ['close'] );
	}
	
	
	jQuery('#learndash_builder_box_wrap .learndash_builder_items').on('click', '.ld-show-all', builder_show_all_builders );
	function builder_show_all_builders( event ) {
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-action-show-hide').trigger('click', ['open'] );
	}

	jQuery('#learndash_builder_box_wrap .learndash_builder_items').on('click', '.ld-hide-all', builder_hide_all_builders );
	function builder_hide_all_builders( event ) {
		jQuery( '#learndash_builder_box_wrap .learndash_builder_items .ld-course-builder-action-show-hide').trigger('click', ['close'] );
	}
	
	// Enf of functions
});
