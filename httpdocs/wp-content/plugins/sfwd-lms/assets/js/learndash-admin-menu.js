jQuery(document).ready(function () {
	/**
	 * Moves the new LD admin panel into the correct position in the DOM.
	 */

	if (jQuery('#sfwd-header').length) {
		if (jQuery('#wpbody-content #screen-meta-links').length) {
			jQuery('#sfwd-header').insertAfter('#wpbody-content #screen-meta-links');
		} else if (jQuery('#wpbody-content #screen-meta').length) {
			jQuery('#sfwd-header').insertAfter('#wpbody-content #screen-meta');
		} else if (jQuery('#wpbody-content').length) {
			jQuery('#sfwd-header').prepend('#wpbody-content');
		}
	}

	// Move the onboarding to be below the header
	if ((jQuery('section.ld-onboarding-screen').length) && (jQuery('#sfwd-header').length)) {

		// In the onboarding section is within a metabox we leave it.
		var parent = jQuery('section.ld-onboarding-screen').closest('.meta-box-sortables');
		if ((typeof parent === 'undefined') || (parent.length == 0)) {
			jQuery('section.ld-onboarding-screen').insertAfter('#sfwd-header');
			jQuery('.wrap').hide();
		}
	}
});
