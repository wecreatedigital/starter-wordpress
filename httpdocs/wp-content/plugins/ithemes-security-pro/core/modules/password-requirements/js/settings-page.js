(function ( $ ) {

	$( function () {

		$( '.itsec-password-requirements-container' ).each( function () {
			updateVisibility( $( this ).data( 'code' ) );
		} );

		$( '.itsec-password-requirements-container__enabled-wrap input[type="checkbox"]' ).on( 'change', function ( e ) {
			updateVisibility( $( this ).parents( '.itsec-password-requirements-container' ).data( 'code' ) );
		} )
	} );

	function updateVisibility( code ) {
		var $checkbox = $( '.itsec-password-requirements-container__enabled-wrap--' + code + ' input[type="checkbox"]' ),
			$details = $( '.itsec-password-requirements-container__settings-wrap--' + code );

		if ( $checkbox.is( ':checked' ) ) {
			$details.show();
		} else {
			$details.hide();
		}
	}
})( jQuery );