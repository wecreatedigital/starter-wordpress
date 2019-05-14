(function ( $, wp ) {

	$( function () {
		$( document ).on( 'click', '#wp-admin-bar-itsec-fingerprinting-unknown button', function ( e ) {
			e.preventDefault();

			var $btn = $( this ),
				nonce = $btn.data( 'nonce' );

			$btn.prop( 'disabled', true );

			wp.ajax.post( 'itsec-fingerprint-confirm', { nonce: nonce } )
				.done( function ( response ) {
					alert( response.message );
				} )
				.fail( function ( response ) {
					alert( response.message || 'Unknown error occurred.' );
				} )
				.always( function () {
					$btn.prop( 'disabled', false );
				} );
		} );
	} );
})( jQuery, window.wp );