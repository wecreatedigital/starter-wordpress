(function ( $, itsec ) {
	$( function () {
		var $status = $( '#itsec_password_expiration_status' ),
			$undoNotice = $( '#itsec_password_expiration_undo' );

		$( document ).on( 'click', '#itsec-password-requirements-force-expiration', function ( e ) {
			e.preventDefault();

			var $button = $( this );

			$button.prop( 'disabled', true );

			itsec.sendModuleAJAXRequest( 'password-requirements', { password_requirement: 'force', method: 'force-expiration' }, function ( response ) {
				$button.removeProp( 'disabled' );
				itsec.displayNotices( response, $status, true );
				$undoNotice.html( response.response );
			} );
		} );

		$( document ).on( 'click', '#itsec-password-requirements-force-expiration-undo', function ( e ) {
			e.preventDefault();

			var $button = $( this );
			$button.css( { opacity: .5 } );

			itsec.sendModuleAJAXRequest( 'password-requirements', { password_requirement: 'force', method: 'force-expiration-undo' }, function ( response ) {
				$button.removeProp( 'disabled' );
				itsec.displayNotices( response, $status, true );
				$undoNotice.html( response.response );
			} );
		} );
	} );
})( jQuery, itsecUtil );