( function( $, itsec ) {

	function doConditionalFields() {
		const recaptchaType = $( 'input[name="recaptcha[type]"]:checked' ).val().replace( /[^\w\d]+/, '' );

		$( '.itsec-recaptcha-show-for-type' ).hide();
		$( '.itsec-recaptcha-hide-for-type' ).show();

		$( '.itsec-recaptcha-show-for-type--type-' + recaptchaType ).show();
		$( '.itsec-recaptcha-hide-for-type--type-' + recaptchaType ).hide();
	}

	$( doConditionalFields );

	itsec.events.on( 'modulesReloaded', doConditionalFields );
	$( document ).on( 'change', 'input[name="recaptcha[type]"]', doConditionalFields );
} )( jQuery, itsecSettingsPage );
