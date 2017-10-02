jQuery( document ).ready( function () {
	var updateVisibleSections = function() {
		var requireSSL = jQuery( '#itsec-ssl-require_ssl' ).val();

		if ( 'advanced' === requireSSL ) {
			jQuery( '.itsec-ssl-advanced-setting' ).show();
		} else {
			jQuery( '.itsec-ssl-advanced-setting' ).hide();
		}
	};


	var $container = jQuery( '#wpcontent' );

	$container.on( 'change', '#itsec-ssl-require_ssl', updateVisibleSections );

	updateVisibleSections();
} );
