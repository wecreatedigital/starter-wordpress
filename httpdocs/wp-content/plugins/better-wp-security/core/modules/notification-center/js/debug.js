(function ( $, itsecUtil ) {
	"use strict";

	$( function () {
		$( '#itsec-notification-center-notifications' ).on( 'click', '.button', function () {

			var $btn = $( this );
			$btn.prop( 'disabled', true );

			itsecUtil.sendModuleAJAXRequest( 'notification-center', { id: $btn.data( 'id' ), silent: $btn.hasClass( 'itsec__send-notification--silent' ) ? 1 : 0 }, function ( response ) {

				$btn.prop( 'disabled', false );

				if ( response.success ) {
					$( 'table', '#itsec-notification-center-notifications' ).replaceWith( response.response );
				}

				itsecUtil.displayNotices( response, $( '#itsec-messages' ) );
			} );
		} );
	} );
})( jQuery, window.itsecUtil );