jQuery(document).ready(function() {
	var selectors_array = [];
	jQuery('.learndash-binary-selector').each(function() {
		var selector_id = jQuery(this).prop('id');
		if (typeof selector_id !== 'undefined') {		
			selectors_array[selector_id] = new learndash_binary_selector(this);
			selectors_array[selector_id].init();
		}
	});
});

function learndash_binary_selector(selector_div) {
	var self = this;
	
	self.selector_div = selector_div;
	self.selector_id = jQuery(self.selector_div).prop('id');	
	self.selected_items = {};
	
	self.selector_data_loaded = false;
	self.selector_data = [];

	this.init = function() {
		self.get_selector_data(true);
		self.get_selector_form_element();
		self.update_legend();

		self.init_actions();
		self.init_pager();
		self.init_search();
		//self.init_lazy_load();
	}
	
	this.init_actions = function() {

		jQuery( '.learndash-binary-selector-section-middle', self.selector_div ).on( 'click', 'a.learndash-binary-selector-button-add', self.add_selected_items );
		jQuery( '.learndash-binary-selector-section-middle', self.selector_div ).on( 'click', 'a.learndash-binary-selector-button-remove', self.remove_selected_items );
	}

	this.init_pager = function() {
		if (jQuery('ul.learndash-binary-selector-pager', self.selector_div).length) {
		
			jQuery('ul.learndash-binary-selector-pager', self.selector_div).each(function(e){
				var section_pager_el = this;
				
				if (jQuery(section_pager_el).hasClass('learndash-binary-selector-pager-left'))
					var position = 'left';
				else if (jQuery(section_pager_el).hasClass('learndash-binary-selector-pager-right'))
					var position = 'right';
		
				if (typeof position !== 'undefined') {
					if ( ( typeof self.selector_data[position] !== 'undefined' ) && ( typeof self.selector_data[position]['pager'] !== 'undefined' ) ) {
						self.update_pager( section_pager_el, self.selector_data[position]['pager'] );	
					}
				}
			});
		
			jQuery('.learndash-binary-selector-section ul.learndash-binary-selector-pager a', self.selector_div).click(function(e){
				e.preventDefault();
				self.handle_pager(this);
			});
		}
	}
	
	this.init_search = function() {

		if (jQuery('.learndash-binary-selector-search', self.selector_div).length) {
			
			// Hold reference to our interval loop for key press
			var interval_ref;
			
			// We setup a search values object which will hold the left and right side searcheds. This will allow 
			// dual search on left and right without loosing our settings.
			var search_values = {};
			
			// Set time for .20 seconds. 1/5 of a second. 
			var search_timeout = 200; 

			// Activate logic on fucus.
			jQuery('.learndash-binary-selector-search', self.selector_div).focus(function() {
				var search_el = this;
				
				var section_el = self.get_section_el(search_el);
				if (typeof section_el === 'undefined') {
					return;
				}
				var selector_html_id = jQuery(self.selector_div).attr('id');
				if ( typeof selector_html_id === 'undefined' ) {
					return;
				}
				
				if (jQuery(search_el).hasClass('learndash-binary-selector-search-left')) {
					var position = 'left';
				} else if (jQuery(search_el	).hasClass('learndash-binary-selector-search-right')) {
					var position = 'right';
				} else {				
					return;
				}
				search_values[position] = {};
				search_values[position]['query_data'] = {};

				//search_values[position] = position;

				if (typeof self.selector_data[position] === 'undefined')
					return;

				if (typeof self.selector_data['selector_class'] === 'undefined')
					return;

				if (typeof self.selector_data['selector_nonce'] === 'undefined')
					return;

				if (typeof self.selector_data['query_vars'] === 'undefined')
					return;
				
				// Copy our query vars from the section data element. Will be used as a starting point for the search queries
				search_values[position]['query_data']['query_vars'] = self.selector_data['query_vars'];
				
				// Set and clear the search query var. Will be used to hold the search value passed via AJAX
				//search_values[position]['query_data']['query_vars']['search'] = '';
				
				search_values[position]['query_data']['position'] = position;
				search_values[position]['query_data']['selector_class'] = self.selector_data['selector_class'];
				search_values[position]['query_data']['selector_nonce'] = self.selector_data['selector_nonce'];
				search_values[position]['selector_html_id'] = selector_html_id;
				
				search_values[position]['query_data']['selected_ids'] = self.get_selector_form_element(true);
				
				// Grab the current value of the search input and store it as part of our data.
				search_values[position]['current_value'] = jQuery(search_el).val();

				//--------------			
				
				if (interval_ref != '') {
					clearInterval(interval_ref);
				}
								
				interval_ref = setInterval(function() {
					search_values[position]['current_value'] = jQuery(search_el).val();
					
					// If search was cleared we need to reset the display to show the regular non-search items
					if (search_values[position]['current_value'] == '') {
						if (search_values[position]['query_data']['query_vars']['search'] != search_values[position]['current_value']) {
							search_values[position]['query_data']['query_vars']['search'] = search_values[position]['current_value'];
						
							// Reset our query vars to the existing values. 
							search_values[position]['query_data']['query_vars'] = self.selector_data['query_vars'];
							search_values[position]['query_data']['query_vars']['search'] = '';
							
							var post_data = {
								'action': 'learndash_binary_selector_pager',
								'query_data': search_values[position]['query_data'],
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
									if (typeof reply_data['html_options'] !== 'undefined') {
										jQuery('.learndash-binary-selector-items', section_el).empty().append(reply_data['html_options'])
									}
									var section_pager_el = jQuery('ul.learndash-binary-selector-pager', section_el);
									if (typeof section_pager_el !== 'undefined') {
										jQuery(section_pager_el).show();
										if (typeof reply_data['pager'] !== 'undefined') {
											self.selector_data[position]['pager'] = reply_data['pager'];
											self.update_pager( section_pager_el, self.selector_data[position]['pager'] );	
											self.update_legend();
										}
									}
								}
							});
						
							//if (jQuery('.learndash-binary-selector-pager', section_el).length) {
							//	jQuery('.learndash-binary-selector-pager', section_el).show();
							//}
						}
					} else {
						
						//if (jQuery('.learndash-binary-selector-pager', section_el).length) {
						//	jQuery('.learndash-binary-selector-pager', section_el).hide();
						//}
						
						if ( ( search_values[position]['current_value'].length >= 3 ) && ( search_values[position]['query_data']['query_vars']['search'] != search_values[position]['current_value'] ) ) {
							
							search_values[position]['query_data']['query_vars']['search'] = search_values[position]['current_value'];

							//query_data_parsed['data_query']['s'] = search_value;
							search_values[position]['query_data']['query_vars']['paged'] = 1;		
							//search_values[position]['query_data']['query_vars']['search'] = search_value;

							var post_data = {
								'action': 'learndash_binary_selector_pager',
								'query_data': search_values[position]['query_data'],
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
									if (typeof reply_data['html_options'] !== 'undefined') {
										jQuery('.learndash-binary-selector-items', section_el).empty().append(reply_data['html_options'])
									} else {
										jQuery('.learndash-binary-selector-items', section_el).empty().append('');
									}

									if (typeof reply_data['pager'] !== 'undefined') {
										var section_pager_el = jQuery('.learndash-binary-selector-pager', section_el);
										if (typeof section_pager_el !== 'undefined') {
											jQuery(section_pager_el).show();
											self.selector_data[position]['pager'] = reply_data['pager'];
											self.update_pager(section_pager_el, reply_data['pager']);
											self.update_legend();
										}
									} else {
										var section_pager_el = jQuery('.learndash-binary-selector-pager', section_el);
										jQuery(section_pager_el).hide();
									}
								}
							});
						}
					
						if (!jQuery('.learndash-binary-selector-search', self.section_el).is(':focus')) {
							clearInterval(interval_ref);
						}
					}
				}, search_timeout);
			});
		}	
	}
	
	this.update_legend = function() {
		
		var options_size = jQuery('select.learndash-binary-selector-items-left option', self.selector_div).length;
		jQuery('.learndash-binary-selector-legend-left span.items-total-count', self.selector_div).html(options_size);

		//var total_items_count = self.get_selector_data_element('total_items');
		//jQuery('.learndash-binary-selector-legend-left span.items-total-count', self.selector_div).html(total_items_count);


		var options_size = jQuery('select.learndash-binary-selector-items-right option', self.selector_div).length;
		//jQuery('.learndash-binary-selector-legend-right span.items-loaded-count', self.selector_div).html(options_size);

		//var selected_items_length = Object.keys(self.selected_items).length;
		jQuery('.learndash-binary-selector-legend-right span.items-total-count', self.selector_div).html(options_size);
	}
	
	this.handle_pager = function(clicked_el) {
		
		var section_el = self.get_section_el( clicked_el );
		if (typeof section_el === 'undefined')
			return;

		var selector_html_id = jQuery(self.selector_div).attr('id');
		if (typeof selector_html_id === 'undefined')
			return;

		var section_pager_el = jQuery(clicked_el).parents('ul.learndash-binary-selector-pager');
		if ( typeof section_pager_el === 'undefined' ) {
			return;
		}

		var query_data = {};

		if (typeof self.selector_data['query_vars'] === 'undefined')
			return;

		query_data['query_vars'] = self.selector_data['query_vars'];

		if (typeof self.selector_data['selector_class'] === 'undefined')
			return;

		query_data['selector_class'] = self.selector_data['selector_class'];
		query_data['selector_nonce'] = self.selector_data['selector_nonce'];
		query_data['selector_html_id'] = selector_html_id;
		
		if (jQuery(section_pager_el).hasClass('learndash-binary-selector-pager-left'))
			var position = 'left';
		else if (jQuery(section_pager_el).hasClass('learndash-binary-selector-pager-right'))
			var position = 'right';

		if (typeof position === 'undefined')
			return;

		query_data['position'] = position;

		if (typeof self.selector_data[position] === 'undefined')
			return;
		
		if (typeof self.selector_data[position]['pager'] === 'undefined')
			return;
		
		var selector_pager = self.selector_data[position]['pager'];
				
		if (jQuery(clicked_el).hasClass('learndash-binary-selector-pager-prev')) {
			if (selector_pager['current_page'] == 1)
				return;

			query_data['query_vars']['paged'] = parseInt(selector_pager['current_page']) - 1;
			
		} else if (jQuery(clicked_el).hasClass('learndash-binary-selector-pager-next')) {
			if (parseInt(selector_pager['current_page']) == parseInt(selector_pager['total_pages']))
				return;
	
			query_data['query_vars']['paged'] = parseInt(selector_pager['current_page']) + 1;
		}

		query_data['selected_ids'] = self.get_selector_form_element(true);
		
		var post_data = {
			'action': 'learndash_binary_selector_pager',
			'query_data': query_data,
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
				
				if (typeof reply_data['html_options'] !== 'undefined') {
					jQuery('.learndash-binary-selector-items', section_el).empty().append(reply_data['html_options'])
				}
				
				if (typeof reply_data['query-vars'] !== 'undefined') {
					self.selector_data[position]['query-vars'] = reply_data['query-vars'];
				}

				if (typeof reply_data['pager'] !== 'undefined') {
					self.selector_data[position]['pager'] = reply_data['pager'];
					self.update_pager( section_pager_el, self.selector_data[position]['pager'] );	
					self.update_legend();
					
				}
			}
		});
	}
	
	this.update_pager = function( section_pager_el, section_pager_data ) {
		
		if ( typeof section_pager_el !== 'undefined' ) {
			if ( typeof section_pager_data['current_page'] !== 'undefined' )
				var current_page = parseInt( section_pager_data['current_page'] );
			else 
				var current_page = 0;
			
			if ( typeof section_pager_data['total_pages'] !== 'undefined' )
				var total_pages = parseInt( section_pager_data['total_pages'] );
			else 
				var total_pages = 0;

			if ( typeof section_pager_data['total_items'] !== 'undefined' )
				var total_items = parseInt( section_pager_data['total_items'] );
			else 
				var total_items = 0;
			
			if ( ( current_page >= 1 ) && ( total_pages >= 1  ) ) {	
				
				jQuery('.learndash-binary-selector-pager-info span.current_page', section_pager_el).html( current_page );
				jQuery('.learndash-binary-selector-pager-info span.current_page', section_pager_el).show();

				jQuery('.learndash-binary-selector-pager-info span.total_pages', section_pager_el).html( total_pages );
				jQuery('.learndash-binary-selector-pager-info span.total_pages', section_pager_el).show();

				jQuery('.learndash-binary-selector-pager-info span.total_items span.total_items_count', section_pager_el).html( total_items );
				jQuery('.learndash-binary-selector-pager-info span.total_items', section_pager_el).show();

				jQuery('.learndash-binary-selector-pager-info', section_pager_el).show();
				
				if (current_page == 1)
					jQuery('.learndash-binary-selector-pager-prev a', section_pager_el).hide();
				else 
					jQuery('.learndash-binary-selector-pager-prev a', section_pager_el).show();
				
				if (current_page == total_pages) 
					jQuery('.learndash-binary-selector-pager-next a', section_pager_el).hide();
				else
					jQuery('.learndash-binary-selector-pager-next a', section_pager_el).show();
				
			} else {
				jQuery('.learndash-binary-selector-pager-info span.current_page', section_pager_el).hide();
				jQuery('.learndash-binary-selector-pager-info span.total_pages', section_pager_el).hide();
				jQuery('.learndash-binary-selector-pager-prev a', section_pager_el).hide();
				jQuery('.learndash-binary-selector-pager-next a', section_pager_el).hide();
			}
		}
	}
	
	this.init_lazy_load = function() {
		
		//if (jQuery(self.selector_div).hasClass('learndash_lazy_load') ) {
		//	self.lazy_load_items();			
		//}
	}

	this.add_selected_items = function(e) {
		
		e.preventDefault();
		
		var items_changed = [];
		if ((jQuery('.learndash-binary-selector-items-left', self.selector_div).length) && (jQuery('.learndash-binary-selector-items-right', self.selector_div).length)) {

			jQuery('.learndash-binary-selector-items-left option:selected', self.selector_div).each(function() {	
				var option_left_el = jQuery(this);
				
				if (!option_left_el.hasClass('learndash-binary-selector-item-disabled')) {
					jQuery(option_left_el, self.selector_div).removeClass('learndash-binary-selector-item-selected');
					jQuery(option_left_el, self.selector_div).clone().appendTo(jQuery('.learndash-binary-selector-items-right', self.selector_div));
					jQuery(option_left_el, self.selector_div).addClass('learndash-binary-selector-item-disabled');
					jQuery(option_left_el, self.selector_div).prop('selected', false);
					jQuery(option_left_el, self.selector_div).prop('disabled', true);

					var data_id = jQuery(option_left_el).attr('data-value');
					if (data_id != '') {
						items_changed.push(data_id);
					}
				}
			});
		} 	
		
		self.update_selected_form_element( 'add', items_changed );
	}

	this.remove_selected_items = function(e) {
		e.preventDefault();

		var items_changed = [];
		
		if ((jQuery('.learndash-binary-selector-items-left', self.selector_div).length) && (jQuery('.learndash-binary-selector-items-right', self.selector_div).length)) {
			jQuery('.learndash-binary-selector-items-right option:selected', self.selector_div).each(function() {	
				var option_right_el = jQuery(this);

				var data_id = jQuery(option_right_el).attr('data-value');
				if (data_id != '') {
					items_changed.push(data_id);
				}
				
				if (jQuery('.learndash-binary-selector-items-left option[data-value="'+data_id+'"]', self.selector_div).length) {
					jQuery('.learndash-binary-selector-items-left option[data-value="'+data_id+'"]', self.selector_div).removeClass('learndash-binary-selector-item-disabled');
					jQuery('.learndash-binary-selector-items-left option[data-value="'+data_id+'"]', self.selector_div).removeClass('learndash-binary-selector-item-selected');
					jQuery('.learndash-binary-selector-items-left option[data-value="'+data_id+'"]', self.selector_div).prop('disabled', false);
				}
				jQuery(option_right_el).remove();
			});
		} 	
		self.update_selected_form_element( 'remove', items_changed );
	}
	
	this.get_section_el = function(child_el) {
		if ( typeof child_el !== 'undefined' ) {
			return jQuery( child_el ).parents( '.learndash-binary-selector-section' );
		}
	}
	
	
	// We update our internal selector form element.
	// action is 'add' or 'remove'. 
	// changed_ids is array of items to add or remove.	
	this.update_selected_form_element = function( action, changed_ids ) {
		if (changed_ids.length) {
			if ( action == 'add' ) {

				jQuery.each(changed_ids, function(index, item) {
					var item_val = parseInt(item);
					if (jQuery.inArray(item_val, self.selected_items) == -1) {
						self.selected_items.push(item_val);
					}
				});
				self.save_selector_form_element();

			} else if ( action == 'remove' ) {
				jQuery.each(changed_ids, function(index, item) {
					var item_val = parseInt(item);
					var item_pos = jQuery.inArray(item_val, self.selected_items); 
					if (item_pos !== -1) {
						self.selected_items.splice(item_pos, 1);
					}
				});
				self.save_selector_form_element();
			}
		}
	}

/*
	this.update_selected_form_element = function() {
		//self.selected_items = [];
		
		//jQuery('.learndash-binary-selector-items-right li', self.selector_div).each(function() {
		jQuery('.learndash-binary-selector-items-right option', self.selector_div).each(function() {	
			
			var data_id = jQuery(this).attr('data-value');
			if (typeof data_id !== 'undefined') {
				self.selected_items.push(data_id);
			}
		});
		
		self.save_selector_form_element( self.selected_items );
		
		// We store the array of selected items as a stringified JSON item then decode on the server. 
		// The reason for this is to bypass the PHP max_post_items limit of 1000. We could pass a comma
		// separated string. But then we would need to parse this each time the user moves items in JS
		// as well on convert to array on the server. 
		jQuery('input.learndash-binary-selector-form-element', self.selector_div).val(JSON.stringify(self.selected_items));
		
		self.update_legend();
		
	}
*/
	this.get_selector_form_element = function( return_raw ) {
		if (typeof return_raw === 'undefined') 
			return_raw = false;

		var selected_items = jQuery('input.learndash-binary-selector-form-element', self.selector_div).val();

		if (return_raw == false) {
			self.selected_items = JSON.parse(selected_items);

			// The returned 'type' from JSON.parse() is an object. We want to convert this to an array to better manage adding, removing items
			self.selected_items = jQuery.map(self.selected_items, function(el) { return parseInt(el) });
			
			return self.selected_items;
		} else {
			return selected_items;
		}
	}

	this.save_selector_form_element = function( ) {
		jQuery('input.learndash-binary-selector-form-element', self.selector_div).val(JSON.stringify(self.selected_items));
		jQuery('input.learndash-binary-selector-form-changed', self.selector_div).val('1');
	}

	this.sort_right_options = function() {
		return;
		// For now no sorting. Jsut append to the bottom. 
		/*
		var options = jQuery('.learndash-binary-selector-items-right li', self.selector_div);		
		var arr = options.map(function(_, o) { return { t: jQuery(o).text(), v: o.value }; }).get();		
		arr.sort(function(o1, o2) { return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0; });
		options.each(function(i, o) {
		  o.value = arr[i].v;
		  jQuery(o).text(arr[i].t);
		});
		*/
	}

	this.get_selector_data = function(force_reload) {
		
		if (typeof force_reload === 'undefined') 
			force_reload = false;
			
		if ((self.selector_data_loaded != true) || (force_reload === true)) {
			self.selector_data_loaded = true;

			element_data = jQuery(self.selector_div).attr('data');
			if ( typeof element_data === 'undefined' ) {
				return;
			}
			self.selector_data = JSON.parse(element_data);
		}
		return self.selector_data;
	}
	
	this.get_selector_data_element = function( element_key ) {
		var selector_data = self.get_selector_data();
		
		if (( typeof selector_data !== 'undefined' ) && ( typeof element_key !== 'undefined' ) && ( typeof selector_data[element_key] !== 'undefined' )) {
			return selector_data[element_key];
		}
	}

	this.set_selector_data_element = function( element_key, element_value ) {
		self.get_selector_data();
		
		this.selector_data[element_key] = element_value;
	}

	
	this.update_selector_data = function(query_data) {
		jQuery(self.selector_div).attr('data', JSON.stringify(query_data));
		this.selector_data = query_data;
	}
	
	this.lazy_load_items = function(query_data) {

		if ( typeof query_data === 'undefined' ) {
			query_data = self.get_selector_data();
		}

		if (typeof query_data.query_vars.paged === 'undefined' ) {
			query_data.query_vars.paged = 0;
		}

		query_data.query_vars.paged = parseInt(query_data.query_vars.paged) + 1;

		var post_data = {
			'action': 'learndash_binary_selector_lazy_loader',
			'query-data': query_data
		};

		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			cache: false,
			data: post_data,
			error: function(jqXHR, textStatus, errorThrown ) {
				//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
				//console.log('error [%o]', textStatus);
			},
			success: function(reply_data) {
				if ( typeof reply_data !== 'undefined' ) {
					if ( typeof reply_data['html_options'] !== 'undefined' ) {
						if ( reply_data['html_options'] != '' ) {
							jQuery('select.learndash-binary-selector-items-left', self.selector_div).append(reply_data['html_options']);
							
							// Delete the received html_options...
							delete reply_data['html_options'];

							//this.set_selector_data_element()//reply_data
							self.update_selector_data(reply_data);
							self.update_legend();
							
							// ...as we are going to loop to ourself and regenerate
							self.lazy_load_items(reply_data);
						} else {
							// We then update the load element data attribute
							//jQuery(self.selector_div).attr('data', JSON.stringify(reply_data));
							self.update_selector_data( reply_data );
							self.set_selected_items();
						}
					}
				}
				
			}
		});
	}
	
	this.set_selected_items = function() {
		//var selected_items = jQuery('input.learndash-binary-selector-form-element', self.selector_div).val();
		self.get_selector_form_element();
		
		// We clear out all the options on the right side because we are about to re-add them after the lazy load
		jQuery('select.learndash-binary-selector-items-right', self.selector_div).empty();
		
		// We also reset all the items on the left side with default states as we are going to loop over the hidden input and re-add them.
		jQuery('select.learndash-binary-selector-items-left > option.learndash-binary-selector-item ', self.selector_div).removeClass('learndash-binary-selector-item-disabled');
		jQuery('select.learndash-binary-selector-items-left > option.learndash-binary-selector-item ', self.selector_div).prop('selected', false);
		jQuery('select.learndash-binary-selector-items-left > option.learndash-binary-selector-item ', self.selector_div).prop('disabled', false);

		//selected_items = JSON.parse(selected_items);
		
		jQuery.each(self.selected_items, function(index, item) {
			jQuery('.learndash-binary-selector-items-left option.learndash-binary-selector-item[value="'+item+'"]', self.selector_div).each(function(){
				var option_left_el = jQuery(this);
				jQuery(option_left_el, self.selector_div).clone().appendTo(jQuery('.learndash-binary-selector-items-right', self.selector_div));
				jQuery(option_left_el, self.selector_div).addClass('learndash-binary-selector-item-disabled');
				jQuery(option_left_el, self.selector_div).prop('selected', false);
				jQuery(option_left_el, self.selector_div).prop('disabled', true);
				
			});
		});
		
		self.update_legend();
		
	}
}

jQuery.expr[":"].Contains = jQuery.expr.createPseudo(function(arg) {
    return function( elem ) {
        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
    };
});