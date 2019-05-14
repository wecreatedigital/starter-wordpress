( function( $, config ) {
	$( function() {
		$( document ).on( 'click', '.itsec-recaptcha-opt-in__agree', function( e ) {
			e.preventDefault();

			var $optins = $( '.itsec-recaptcha-opt-in' )
				.addClass( 'itsec-recaptcha-opt-in--loading' );

			$.ajax( {
				url     : config[ 'googlejs' ],
				dataType: 'script',
				cache   : true,
				success : function() {
					$optins.each( function() {
						var $optin = $( this );
						$optin.parents( 'form' ).append( $( '<input type="hidden">' ).attr( {
							name : 'recaptcha-opt-in',
							value: 'true',
						} ) );

						var $template = $( '.itsec-recaptcha-opt-in__template', $optin );
						$optin.replaceWith( $template.html() );
					} );

					if ( window.grecaptcha && window.grecaptcha.render && config.onload && window[ config.onload ] ) {
						window[ config.onload ]();
					}
				},
			} );
		} );
	} );
} )( jQuery, window[ 'ITSECRecaptchaOptIn' ] );
