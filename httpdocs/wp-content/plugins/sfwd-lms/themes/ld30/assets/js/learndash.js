jQuery(document).ready(function($) {

	var hash = window.location.hash;

	if (hash == '#login') {
		openLoginModal();
	}

	if( typeof ldGetUrlVars()['login'] !== 'undefined' ) {

		var loginStatus = ldGetUrlVars()['login'];

		if( loginStatus == 'failed' ) {
			openLoginModal();
		}
	}

	if( typeof ldGetUrlVars()['ld-topic-page'] !== 'undefined' ) {

		var topicPage = ldGetUrlVars()['ld-topic-page'];
		var topicIds  = topicPage.split('-');
		var topicId   = Object.values(topicIds)[0];

		var lesson = $('#ld-expand-' + topicId);
		var button = $(lesson).find('.ld-expand-button');

		ld_expand_element( button );

		$('html, body').animate({
		    scrollTop: ( $(lesson).offset().top )
		}, 500 );

	}

	$('body').on('click', 'a[href="#login"]', function(e) {
		e.preventDefault();
		openLoginModal();
	});


	$('body').on('click', '.ld-modal-closer', function(e) {
		e.preventDefault();
		closeLoginModal();
	});

	$('body').on('click', '#ld-comments-post-button', function(e) {
		$(this).addClass('ld-open');
		$('#ld-comments-form').removeClass('ld-collapsed');
		$('textarea#comment').focus();
	});

	// Close modal if clicking away
	/*
	$('body').on('click', function(e) {
		if ($('.learndash-wrapper').hasClass('ld-modal-open')) {
			if ( ! $(e.target).parents('.ld-modal').length && (! $(e.target).is('a'))) {
				closeLoginModal();
			}
		}
	});
	*/
	
	// Close modal on Esc key
	$(document).keyup(function(e) {
	     if (e.keyCode === 27) {
	     	closeLoginModal();
	    }
	});

	$( '.learndash-wrapper' ).on( 'click', 'a.user_statistic', show_user_statistic );

	focusMobileCheck();

	$('body').on('click', '.ld-focus-sidebar-trigger', function(e) {
		if ($('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
			openFocusSidebar();
		} else {
			closeFocusSidebar();
		}
	});

	$('body').on('click', '.ld-mobile-nav a', function(e) {
		e.preventDefault();
		if ($('.ld-focus').hasClass('ld-focus-sidebar-collapsed')) {
			openFocusSidebar();
		} else {
			closeFocusSidebar();
		}

	});

	$('.ld-js-register-account').click(function(e) {

		e.preventDefault();
		
		$('.ld-login-modal-register .ld-modal-text').slideUp('slow');
		$('.ld-login-modal-register .ld-alert').slideUp('slow');
		$(this).slideUp('slow', function() {
			
			$('#ld-user-register').slideDown('slow');
		});

	});

	var windowWidth = $(window).width();

	$(window).on('orientationchange', function() {
		windowWidth = $(window).width();
	});

	$(window).on('resize', function() {
		if ($(this).width() !== windowWidth) {
			setTimeout(function() {
				focusMobileCheck();
			}, 50);
		}
	});

	if( $('.ld-course-status-content').length ) {
		var tallest = 0;

		$('.ld-course-status-content').each(function() {
			if($(this).height() > tallest){
			   tallest = $(this).height();
		   }
		});

		$('.ld-course-status-content').height(tallest);

	}

	function focusMobileCheck() {
		if ($(window).width() < 768) {
			closeFocusSidebar();
		} else {
			openFocusSidebar();
		}
	}

	function closeFocusSidebar() {
		$('.ld-focus').addClass('ld-focus-sidebar-collapsed');
		$('.ld-mobile-nav').removeClass('expanded');
		positionTooltips();
	}

	function openFocusSidebar() {
		$('.ld-focus').removeClass('ld-focus-sidebar-collapsed');
		$('.ld-mobile-nav').addClass('expanded');
		positionTooltips();
	}


	$('.ld-file-input' ).each( function() {
		var $input	 = $( this ),
			$label	 = $input.next( 'label' ),
			labelVal = $label.html();

		$input.on( 'change', function( e ) {

			var fileName = '';
			if( this.files && this.files.length > 1 )
				fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
			else if( e.target.value )
				fileName = e.target.value.split( '\\' ).pop();
			if( fileName ) {
				$label.find( 'span' ).html( fileName );
				$label.addClass('ld-file-selected');
				$('#uploadfile_btn').attr( 'disabled', false );
			} else {
				$label.html( labelVal );
				$label.removeClass('ld-file-selected');
				$('#uploadfile_btn').attr( 'disabled', true );
			}
		});

		// Firefox bug fix
		$input
		.on( 'focus', function(){ $input.addClass( 'has-focus' ); })
		.on( 'blur', function(){ $input.removeClass( 'has-focus' ); });
	});


	$('body').on('click', '.ld-expand-button', function(e) {

		e.preventDefault();

		ld_expand_element( $(this) );

		positionTooltips();

	});

	$('body').on('click', '.ld-search-prompt', function(e) {

		e.preventDefault();

		$('#course_name_field').focus();

		ld_expand_element( $(this) );

	});

	function ld_expand_button_state(state, elm) {
		var $expandText = ($(elm)[0].hasAttribute('data-ld-expand-text')) ? $(elm).attr('data-ld-expand-text') : 'Expand';
		var $collapseText = ($(elm)[0].hasAttribute('data-ld-collapse-text')) ? $(elm).attr('data-ld-collapse-text') : 'Collapse';

		if (state == 'collapse') {
			$(elm).removeClass('ld-expanded');
			if ($collapseText !== 'false') {
				$(elm).find('.ld-text').text($expandText);
			}
		} else {
			$(elm).addClass('ld-expanded');
			if ($collapseText !== 'false') {
				$(elm).find('.ld-text').text($collapseText);
			}
		}
	}

	function ld_expand_element( elm, collapse ) {

	   if(collapse === undefined) {
	      collapse = false;
	   }

		// Get the button's state
		var $expanded = $(elm).hasClass('ld-expanded');


		// Get the element to expand
		if ($(elm)[0].hasAttribute('data-ld-expands')) {

			var $expands 		= $(elm).attr('data-ld-expands');
			var $expandElm 		= $('#' + $expands);
			var $expandsChild 	= $('#' + $expands).find('.ld-item-list-item-expanded');

			if ($expandsChild.length) {
				$expandElm = $expandsChild;
			}

			var totalHeight = 0;

			$expandElm.find('> *').each(function() {
				totalHeight += $(this).outerHeight();
			});

			$expandElm.attr('data-height', '' + (totalHeight + 50) + '');

			// If the element expands a list

			if ($('#' + $expands)[0].hasAttribute('data-ld-expand-list')) {

				var $container = $('#' + $expands);
				var innerButtons = $container.find('.ld-expand-button');
				if ($expanded) {
					ld_expand_button_state('collapse', elm);
					innerButtons.each(function() {
						ld_expand_element($(this), true);
					});
				} else {
					ld_expand_button_state('expand', elm);
					innerButtons.each(function() {
						ld_expand_element($(this));
					});

				}

			// If the element expands an item

			} else if ($('#' + $expands).length) {

				if ($expanded || collapse == true) {
					ld_expand_singular_item(elm, $('#' + $expands), $expandElm);
				} else {
					ld_collapse_singular_item(elm, $('#' + $expands), $expandElm);
				}

			} else {
				console.log('LearnDash: No expandable content was found');
			}
			positionTooltips();
		}

	}

	function ld_expand_singular_item(elm, $containerElm, $expandElm) {

		$containerElm.removeClass('ld-expanded');
		ld_expand_button_state('collapse', elm);

		$expandElm.css({
			'max-height': 0
		});
	}

	function ld_collapse_singular_item(elm, $containerElm, $expandElm) {

			$containerElm.addClass('ld-expanded');

			ld_expand_button_state('expand', elm);

			$expandElm.css({
				'max-height': $expandElm.data('height')
			});
	}

	$('body').on('click', '.ld-closer', function(e) {
		ld_expand_element( $('.ld-search-prompt'), true );
	});

	$('body').on('click', '.ld-tabs-navigation .ld-tab', function() {
		var $tab = $('#' + $(this).attr('data-ld-tab'));
		if ($tab.length) {
			$('.ld-tabs-navigation .ld-tab.ld-active').removeClass('ld-active');
			$(this).addClass('ld-active');
			$('.ld-tabs-content .ld-tab-content.ld-visible').removeClass('ld-visible');
			$tab.addClass('ld-visible');
		}
		positionTooltips();
	});

	var $tooltips = $('*[data-ld-tooltip]');

	initTooltips();

	function initTooltips() {

		// Clear out old tooltips


		if( $('#learndash-tooltips').length ) {
			$('#learndash-tooltips').remove();
			$tooltips = $('*[data-ld-tooltip]');
		}

		if ($tooltips.length) {
			$('body').prepend('<div id="learndash-tooltips"></div>');
			var $ctr =1;
			$tooltips.each(function() {
				var anchor = $(this);
				if (anchor.hasClass('ld-item-list-item')) {
					anchor = anchor.find('.ld-item-title');
				}
				var elementOffsets = {
					top: anchor.offset().top,
					left: anchor.offset().left + (anchor.outerWidth() / 2)
				};
				var $content = $(this).attr('data-ld-tooltip');
				var $rel_id = Math.floor((Math.random() * 99999));
				//var $tooltip = '<span id="ld-tooltip-' + $rel_id + '" class="ld-tooltip" style="top:' + elementOffsets.top + 'px; left:' + elementOffsets.left + 'px;">' + $content + '</span>';
				var $tooltip = '<span id="ld-tooltip-' + $rel_id + '" class="ld-tooltip">' + $content + '</span>';
				$(this).attr('data-ld-tooltip-id', $rel_id);
				$('#learndash-tooltips').append($tooltip);
				$ctr++;
				var $tooltip = $('#ld-tooltip-' + $rel_id);
				$(this).hover(
					function() {
						$tooltip.addClass('ld-visible');
					},
					function() {
						$tooltip.removeClass('ld-visible');
					}
				);
			});

			$(window).on('resize', function() {
				// Reposition tooltips after resizing
				positionTooltips();
			});

			$(window).add('.ld-focus-sidebar-wrapper').on('scroll', function() {
				// Hide tooltips so they don't persist while scrolling
				$('.ld-visible.ld-tooltip').removeClass('ld-visible');
				// Reposition tooltips after scrolling
				positionTooltips();
			});

			positionTooltips();
		}
	}

	function openLoginModal() {
		var modal_wrapper = $('.learndash-wrapper-login-modal');
		if ((typeof modal_wrapper !== 'undefined') && ( modal_wrapper.length ) ) {
			// Move the model to be first element of the body. See LEARNDASH-3503
			$(modal_wrapper).prependTo('body');
			$(modal_wrapper).addClass('ld-modal-open');

			// Removed LEARNDASH-3867 #4
			$('html, body').animate({
				scrollTop: $('.ld-modal', modal_wrapper).offset().top
			}, 50);
		}
	}

	function closeLoginModal() {
		$('.learndash-wrapper').removeClass('ld-modal-open');
	}

	function positionTooltips() {

		if ( typeof $tooltips !== 'undefined' ) {
			setTimeout(function() {
				
				$tooltips.each(function() {
					var anchor = $(this);
					var $rel_id = anchor.attr('data-ld-tooltip-id');
					$tooltip = $('#ld-tooltip-' + $rel_id);

					if (anchor.hasClass('ld-item-list-item')) {
						//anchor = anchor.find('.ld-item-title');
						anchor = anchor.find('.ld-status-icon');
					}
					console.log('anchor[%o]', anchor);
					var parent_focus = jQuery(anchor).parents('.ld-focus-sidebar');
					console.log('parent_focus[%o]', parent_focus);
					var left_post = anchor.offset().left + (anchor.outerWidth() + 10);
					if (parent_focus.length) {
						left_post = anchor.offset().left + (anchor.outerWidth() -18);
					} 

					$tooltip.css({
						'top' : anchor.offset().top + -3,
						//'left' : anchor.offset().left + (anchor.outerWidth() / 2),
						'left': left_post, //anchor.offset().left + (anchor.outerWidth() +10),
						'margin-left' : 0,
						'margin-right' : 0
					}).removeClass('ld-shifted-left ld-shifted-right');
					if ($tooltip.offset().left <= 0) {
						$tooltip.css({ 'margin-left' : Math.abs($tooltip.offset().left) }).addClass('ld-shifted-left');
					}
					var $tooltipRight = $(window).width() - ($tooltip.offset().left + $tooltip.outerWidth());
					if ($tooltipRight <= 0) {
						$tooltip.css({ 'margin-right' : Math.abs($tooltipRight) }).addClass('ld-shifted-right');
					}

				});
			}, 500);
		}
	}


	$('body').on( 'click', '#ld-profile .ld-reset-button', function(e) {

		e.preventDefault();

		var searchVars = {
			shortcode_instance : $('#ld-profile').data('shortcode_instance')
		};

		$('#ld-profile #ld-main-course-list').addClass('ld-loading');

		$.ajax({
			type: 'GET',
			url: ajaxurl + '?action=ld30_ajax_profile_search',
			data: searchVars,
			success: function( response ) {

				if( typeof response.data.markup !== 'undefined' ) {
					$('#ld-profile').html( response.data.markup );
				}

			}
		});

	});

	$('body').on( 'submit', '.ld-item-search-fields', function(e) {

		e.preventDefault();

		var searchVars = {
			shortcode_instance : $('#ld-profile').data('shortcode_instance')
		};

		searchVars['ld-profile-search'] = $(this).parents('.ld-item-search-wrapper').find('#course_name_field').val();

		$('#ld-profile #ld-main-course-list').addClass('ld-loading');

		$.ajax({
			type: 'GET',
			url: ajaxurl + '?action=ld30_ajax_profile_search',
			data: searchVars,
			success: function( response ) {

				if( typeof response.data.markup !== 'undefined' ) {
					$('#ld-profile').html( response.data.markup );
				}

			}
		});

	});

	$('body').on( 'click', '.ld-pagination a', function(e) {

		e.preventDefault();

		var linkVars = {};

		$(this).attr('href').replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
	        linkVars[key] = value;
	    });

		linkVars.pager_results = $(this).parents('.ld-pagination').data('pager-results');

		linkVars.context   = $(this).data('context');

		if( linkVars.context != 'profile' ) {

			linkVars.lesson_id = $(this).data('lesson_id');
			linkVars.course_id = $(this).data('course_id');

			if( $('.ld-course-nav-' + linkVars.course_id ).length ) {
				linkVars.widget_instance = $('.ld-course-nav-' + linkVars.course_id ).data('widget_instance');
			}

		}

		if( linkVars.context == 'course_topics' ) {
			$('#ld-topic-list-' + linkVars.lesson_id ).addClass('ld-loading');
			$('#ld-nav-content-list-' + linkVars.lesson_id ).addClass('ld-loading');
		}

		if( linkVars.context == 'course_lessons' ) {
			$('#ld-item-list-' + linkVars.course_id ).addClass('ld-loading');
			$('#ld-lesson-list-' + linkVars.course_id ).addClass('ld-loading');
		}

		if( linkVars.context == 'profile' ) {
			$('#ld-profile #ld-main-course-list').addClass('ld-loading');
			linkVars.shortcode_instance = $('#ld-profile').data('shortcode_instance');
		}

		if( linkVars.context == 'course_content_shortcode' ) {
			$('.ld-course-content-' + linkVars.course_id ).addClass('ld-loading');
			linkVars.shortcode_instance = $('.ld-course-content-' + linkVars.course_id ).data('shortcode_instance');
		}

		if( linkVars.context == 'course_info_courses' ) {
			$('.ld-user-status').addClass('ld-loading');
			linkVars.shortcode_instance = $('.ld-user-status').data('shortcode-atts');
			console.log(linkVars);
		}

		$.ajax({
			type: 'GET',
			url: ajaxurl + '?action=ld30_ajax_pager',
			data: linkVars,
			success: function( response ) {

				// If we have a course listing, update

				if( linkVars.context == 'course_topics' ) {

					if( $('#ld-topic-list-' + linkVars.lesson_id ).length ) {

						if( typeof response.data.topics !== 'undefined' ) {
							$('#ld-topic-list-' + linkVars.lesson_id ).html( response.data.topics );
						}

						if( typeof response.data.pager !== 'undefined' ) {
							$('#ld-expand-'  + linkVars.lesson_id ).find('.ld-table-list-footer').html( response.data.pager );
						}

						learndashSetMaxHeight( $('.ld-lesson-item-' + linkVars.lesson_id ).find('.ld-item-list-item-expanded') );

						$('#ld-topic-list-' + linkVars.lesson_id ).removeClass('ld-loading');

					}

					if( $('#ld-nav-content-list-' + linkVars.lesson_id ).length ) {


						if( typeof response.data.nav_topics !== 'undefined' ) {
							$('#ld-nav-content-list-' + linkVars.lesson_id ).find('.ld-table-list-items' ).html( response.data.topics );
						}

						if( typeof response.data.pager !== 'undefined' ) {
							$('#ld-nav-content-list-' + linkVars.lesson_id ).find('.ld-table-list-footer' ).html( response.data.pager );
						}

						$('#ld-nav-content-list-' + linkVars.lesson_id ).removeClass('ld-loading');

					}

				}

				if( linkVars.context == 'course_lessons') {

					if( $('#ld-item-list-' + linkVars.course_id ).length ) {

						if( typeof response.data.lessons !== 'undefined' ) {
							$( '#ld-item-list-' + linkVars.course_id ).html( response.data.lessons ).removeClass('ld-loading');
						}

					}

					if( $( '#ld-lesson-list-' + linkVars.course_id ).length ) {

						if( typeof response.data.nav_lessons !== 'undefined' ) {
							$( '#ld-lesson-list-' + linkVars.course_id ).html( response.data.nav_lessons ).removeClass('ld-loading');
						}

					}

				}

				if( linkVars.context == 'profile' ) {

					if( typeof response.data.markup !== 'undefined' ) {
						$('#ld-profile').html( response.data.markup );
					}

				}

				if( linkVars.context == 'course_content_shortcode' ) {

					if( typeof response.data.markup !== 'undefined' ) {
						$('#learndash_post_' + linkVars.course_id ).replaceWith( response.data.markup );
					}

				}

				if( linkVars.context == 'course_info_courses' ) {

					if( typeof response.data.markup !== 'undefined' ) {
						$('.ld-user-status').replaceWith( response.data.markup );
					}

				}

				$('body').trigger( 'ld_has_paginated' );

				initTooltips();

			}
		});

	});

	if( $('#learndash_timer').length ) {

		var timer_el 		= jQuery( '#learndash_timer' );
		var timer_seconds 	= timer_el.attr('data-timer-seconds');
		var timer_button_el = jQuery( timer_el.attr('data-button') );

		var cookie_key = timer_el.attr('data-cookie-key');

		if (typeof cookie_key !== 'undefined') {
			var cookie_name = 'learndash_timer_cookie_'+cookie_key;
		} else {
			var cookie_name = 'learndash_timer_cookie';
		}

		cookie_timer_seconds = jQuery.cookie(cookie_name);

		if (typeof cookie_timer_seconds !== 'undefined') {
			timer_seconds = parseInt( cookie_timer_seconds );
		}

		if ( timer_seconds == 0 ) {
			$(timer_el).hide();
		}

		$(timer_button_el).on( 'learndash-time-finished', function() {
			$(timer_el).hide();
		});

	}

	$(document).on( 'learndash_video_disable_assets', function( event, status ) {

        if ( typeof learndash_video_data == 'undefined' ) {
            return false;
        }

		if (learndash_video_data.videos_shown == 'BEFORE' ) {

			if ( status == true ) {
				$('.ld-lesson-topic-list').hide();
				$('.ld-lesson-navigation').find( '#ld-nav-content-list-' + ldVars.postID ).addClass('user_has_no_access');
				$('.ld-quiz-list').hide();
			} else {
				$('.ld-lesson-topic-list').slideDown();
				$('.ld-quiz-list').slideDown();
				$('.ld-lesson-navigation').find( '#ld-nav-content-list-' + ldVars.postID ).removeClass('user_has_no_access');
			}
		}

	});

	$('.learndash-wrapper').on( 'click', '.wpProQuiz_questionListItem input[type="radio"]', function(e) {

		$(this).parents('.wpProQuiz_questionList').find('label').removeClass('is-selected');
		$(this).parents('label').addClass('is-selected');

	});

	function show_user_statistic( e ) {

		e.preventDefault();

		var refId 				= 	jQuery(this).data('ref_id');
		var quizId 				= 	jQuery(this).data('quiz_id');
		var userId 				= 	jQuery(this).data('user_id');
		var statistic_nonce 	= 	jQuery(this).data('statistic_nonce');
		var post_data = {
			'action': 'wp_pro_quiz_admin_ajax',
			'func': 'statisticLoadUser',
			'data': {
				'quizId': quizId,
				'userId': userId,
				'refId': refId,
				'statistic_nonce': statistic_nonce,
				'avg': 0
			}
		}

		jQuery('#wpProQuiz_user_overlay, #wpProQuiz_loadUserData').show();
		var content = jQuery('#wpProQuiz_user_content').hide();

		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			cache: false,
			data: post_data,
			error: function(jqXHR, textStatus, errorThrown ) {
			},
			success: function(reply_data) {

				if ( typeof reply_data.html !== 'undefined' ) {

					content.html(reply_data.html);
					jQuery('#wpProQuiz_user_content').show();

					jQuery('#wpProQuiz_loadUserData').hide();

					content.find('.statistic_data').click(function() {
						jQuery(this).parents('tr').next().toggle('fast');

						return false;
					});
				}
			}
		});

		jQuery('#wpProQuiz_overlay_close').click(function() {
			jQuery('#wpProQuiz_user_overlay').hide();
		});
	}

	function learndashSetMaxHeight( elm ) {

		var totalHeight = 0;

		elm.find('> *').each(function() {
			totalHeight += $(this).outerHeight();
		});

		elm.attr('data-height', '' + (totalHeight + 50) + '');

		elm.css({
			'max-height': totalHeight + 50
		});
	}

});

function ldGetUrlVars() {

    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });

    return vars;

}
