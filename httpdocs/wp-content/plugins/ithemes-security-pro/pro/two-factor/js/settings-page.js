jQuery( document ).ready( function ( $ ) {
	var updateVisibleSections = function() {
		var availableMethods = jQuery( '#itsec-two-factor-available_methods' ).val();
		var emailMethodEnabled = 'all' === availableMethods || ( 'custom' === availableMethods && jQuery('#itsec-two-factor-custom_available_methods-Two_Factor_Email').prop( 'checked' ) );
		var protectUserType = jQuery( '#itsec-two-factor-protect_user_type' ).val();
		var excludeType = $( '#itsec-two-factor-exclude_type' ).val();
		var rememberType = $( '#itsec-two-factor-allow_remember' ).val();
		var appPasswordsType = $( '#itsec-two-factor-application_passwords_type' ).val();

		if ( emailMethodEnabled ) {
			jQuery( '.itsec-two-factor-requires-email-provider' ).show();
			jQuery( '.itsec-two-factor-requires-no-email-provider' ).hide();

			if ( 'custom' === protectUserType ) {
				jQuery( '#itsec-two-factor-protect_user_type_roles-container' ).show();
			} else {
				jQuery( '#itsec-two-factor-protect_user_type_roles-container' ).hide();
			}
		} else {
			jQuery( '.itsec-two-factor-requires-email-provider' ).hide();
			jQuery( '.itsec-two-factor-requires-no-email-provider' ).show();
		}

		if ( 'custom' === availableMethods ) {
			jQuery( '#itsec-two-factor-available_methods-container' ).show();
		} else {
			jQuery( '#itsec-two-factor-available_methods-container' ).hide();
		}

		if ( 'custom' === appPasswordsType ) {
			$( '#itsec-two-factor-application_passwords_roles-container' ).show();
		} else {
			$( '#itsec-two-factor-application_passwords_roles-container' ).hide();
		}

		if ( 'custom' === excludeType ) {
			$( '#itsec-two-factor-exclude_roles-container' ).show();
		} else {
			$( '#itsec-two-factor-exclude_roles-container' ).hide();
		}

		if ( 'custom' === rememberType ) {
			$( '#itsec-two-factor-allow_remember_roles-container' ).show();
		} else {
			$( '#itsec-two-factor-allow_remember_roles-container' ).hide();
		}
	};


	var $container = jQuery( '#wpcontent' );

	$container.on( 'change', '#itsec-two-factor-available_methods', updateVisibleSections );
	$container.on( 'change', '#itsec-two-factor-protect_user_type', updateVisibleSections );
	$container.on( 'change', '#itsec-two-factor-exclude_type', updateVisibleSections );
	$container.on( 'change', '#itsec-two-factor-custom_available_methods-Two_Factor_Email', updateVisibleSections );
	$container.on( 'change', '#itsec-two-factor-application_passwords_type', updateVisibleSections );
	$container.on( 'change', '#itsec-two-factor-allow_remember', updateVisibleSections );

	updateVisibleSections();
} );
