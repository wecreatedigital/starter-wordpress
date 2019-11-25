jQuery(document).ready(function ($) {
	if ((typeof learndash_admin_pointers_data.pointers !== 'undefined') && (Object.keys(learndash_admin_pointers_data.pointers).length > 0 )) {
		if ((typeof learndash_admin_pointers_data.pointer_color === 'undefined') || (learndash_admin_pointers_data.pointers.pointer_color == '')) {
			learndash_admin_pointers_data.pointer_color = '#00a0d2';
		}
		
		jQuery.each(learndash_admin_pointers_data.pointers, function (pointer_idx, pointer_item) {
			jQuery(pointer_item.target).pointer({
				content: pointer_item.options.content,
				position: {
					edge: pointer_item.options.position.edge,
					align: pointer_item.options.position.align
				},
				close: function () {
					$.post(ajaxurl, {
						pointer: pointer_item.pointer_id,
						action: 'dismiss-wp-pointer'
					});
				}
			}).pointer('open');
		});

		// On the new install we need to push the top down sice it will get cut off by the top of the screen.
		if (jQuery('.wp-pointer #ld-pointer-title-learndash-new-install').length) {
			jQuery('.wp-pointer #ld-pointer-title-learndash-new-install').each(function (idx, item) {
				var pointer_el = jQuery(item).parents('.wp-pointer');
				if (typeof pointer_el !== 'undefined') {
					var pointer_el_pos = jQuery(pointer_el).position();
					jQuery(pointer_el).css('top', pointer_el_pos.top + 7 + 'px');

					var arrow_el = jQuery('.wp-pointer-arrow', pointer_el);
					if (typeof arrow_el !== 'undefined') {
						var arrow_el_pos = jQuery(arrow_el).position();
						jQuery(arrow_el).css('top', arrow_el_pos.top - 3 + 'px');

						/*
						#adminmenu li.current a.menu-top, 
						#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, 
						#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head, 
						.folded #adminmenu li.current.menu-top
						*/
						//var pointer_color = learndash_admin_pointers_data.pointer_color;
						/*
						var pointer_color = jQuery('#adminmenu li.current a.menu-top').css('background-color');
						console.log('#1 pointer_color[%o]', pointer_color);
						if (typeof pointer_color === 'undefined') {
							pointer_color = jQuery('#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu').css('background-color');
							console.log('#2 pointer_color[%o]', pointer_color);
						}
						if (typeof pointer_color === 'undefined') {
							pointer_color = jQuery('#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head').css('background-color');
							console.log('#3 pointer_color[%o]', pointer_color);
						}
						if (typeof pointer_color === 'undefined') {
							pointer_color = jQuery('.folded #adminmenu li.current.menu-top').css('background-color');
							console.log('#4 pointer_color[%o]', pointer_color);
						} 
						if (typeof pointer_color === 'undefined') {
							pointer_color = learndash_admin_pointers_data.pointer_color;
							console.log('#5 pointer_color[%o]', pointer_color);
						}

						console.log('pointer_color[%o]', pointer_color);

						jQuery('.wp-pointer-arrow-inner', arrow_el).css('border-right-color', pointer_color );
						*/
					}
				}
			});
		}
	}	
});