(function ( $, itsec ) {

	$( function () {
		$( document ).on( 'click', '#itsec-fingerprinting-download', function ( e ) {
			e.preventDefault();

			var $status = $( '#itsec-fingerprinting-maxmind-db-status' ),
				$container = $( '#itsec-fingerprinting-maxmind-db-download-container' ),
				$button = $( this );

			$button.prop( 'disabled', true );

			itsec.sendModuleAJAXRequest( 'fingerprinting', { method: 'download' }, function ( response ) {
				if ( response.success ) {
					$container.addClass( 'itsec-fingerprinting-maxmind-db-downloaded' );
				} else {
					$button.removeProp( 'disabled' );
				}

				itsec.displayNotices( response, $status, true );
			} );
		} )
	} );

})( jQuery, itsecUtil );