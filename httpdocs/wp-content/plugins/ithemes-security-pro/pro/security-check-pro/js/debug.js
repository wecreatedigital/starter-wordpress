(function ( $, itsecUtil ) {
	"use strict";

	$( function () {
		$( '#itsec-debug-run-security-check-pro' ).on( 'click', function () {

			var $btn = $( this );
			$btn.prop( 'disabled', true );

			itsecUtil.sendModuleAJAXRequest( 'security-check-pro', { run: true }, function ( response ) {

				$btn.prop( 'disabled', false );

				itsecUtil.displayNotices( response, $( '#itsec-messages' ) );
			} );
		} );
	} );
})( jQuery, window.itsecUtil );