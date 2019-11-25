/**
 * LearnDash Block Functions
 * 
 * This is a collection of common functions used within the LeanDash blocks
 * 
 * @since 2.5.9
 * @package LearnDash
 */

/**
 * Will retrive meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks 
 * are made to ensure that a post is being edited. 
 * @param string token Token to return from meta array. If not provided will array is returned. 
 */
export function ldlms_get_post_edit_meta( token ) {	
	if ( ( typeof token !== 'undefined') && (token != '') ) {
		if (typeof ldlms_settings['meta']['post'][token] !== 'undefined') {
			return ldlms_settings['meta']['post'][token];
		}
	} else {
		if (typeof ldlms_settings['meta']['post'] !== 'undefined') {
			return ldlms_settings['meta']['post'];
		}
	}
}

/**
 * Will retrive meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks 
 * are made to ensure that a post is being edited. 
 * @param string token Token to return from meta array. If not provided will array is returned. 
 */
export function ldlms_get_setting(token, default_value) {
	if ( ( typeof token !== 'undefined' ) && ( token != '' ) && ( typeof ldlms_settings['settings'][token] !== 'undefined' ) ) {
		var token_value = ldlms_settings['settings'][token];
		return ldlms_settings['settings'][token];
	} 
	return default_value;
}


/**
 * Returns the label for custom label element 
 * @param string token Will represent the custom label field to retreive Course, Courses, Lesson, Quiz.
 */
export function ldlms_get_custom_label( token ) {
	if ((typeof ldlms_settings['meta']['post'] !== 'undefined') && (token != '')) {
		if (typeof ldlms_settings['settings']['custom_labels'][token] !== 'undefined') {
			token = ldlms_settings['settings']['custom_labels'][token];
		}
	}
	return token;
}

/**
 * Returns the lowercase label for custom label element 
 * @param string token Will represent the custom label field to retreive Course, Courses, Lesson, Quiz.
 */
export function ldlms_get_custom_label_lower(token) {
	if ((typeof ldlms_settings['meta']['post'] !== 'undefined') && (token != '')) {
		if (typeof ldlms_settings['settings']['custom_labels'][token + '_lower'] !== 'undefined') {
			token = ldlms_settings['settings']['custom_labels'][token + '_lower'];
		}
	}
	return token;
}

/**
 * Returns the slug for custom label element 
 * @param string token Will represent the custom label field to retreive Course, Courses, Lesson, Quiz.
 */
export function ldlms_get_custom_label_slug(token) {
	if (token != '') {
		if (typeof ldlms_settings['settings']['custom_labels'][token + '_slug'] !== 'undefined') {
			token = ldlms_settings['settings']['custom_labels'][token + '_slug'];
		}
	}
	return token;
}

/**
 * Will retrive meta information about the post being edited. For now
 * this is only loaded on post edit screen for Gutenberg. So no checks 
 * are made to ensure that a post is being edited. 
 * @param string token Token to return from meta array. If not provided will array is returned. 
 */
export function ldlms_get_per_page(token) {
	if ((typeof token !== 'undefined') && (token != '')) {
		if (typeof ldlms_settings['settings']['per_page'][token] !== 'undefined') {
			return ldlms_settings['settings']['per_page'][token];
		}
	} else if (typeof ldlms_settings['meta']['posts_per_page'] !== 'undefined') {
		return ldlms_settings['meta']['posts_per_page'];
	}
}

/**
 * Returns integet value for variable.
 * 
 * @param mixed var_value Variable to determin integer from.
 * 
 * @return integer value of zero.
 */
export function ldlms_get_integer_value( var_value ) {
	if  ( typeof var_value === 'undefined' ) {
		var_value = 0;
	}
	var_value = parseInt(var_value);
	if (isNaN(var_value)) {
		var_value = 0;
	}
	
	return var_value;
}