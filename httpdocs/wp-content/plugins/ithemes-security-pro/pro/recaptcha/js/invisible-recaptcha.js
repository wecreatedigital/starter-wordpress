/* global grecaptcha */

function itsecInvisibleRecaptchaLoad() {

	var captchas = jQuery( '.g-recaptcha' );

	var submit = function ( $form, id, isClick ) {
		return function ( e ) {

			if ( itsecRecaptchaHasUserFacingError() ) {
				return;
			}

			e.preventDefault();
			grecaptcha.execute( id );

			if ( isClick ) {
				jQuery( '<input type="hidden">' ).attr( {
					name : jQuery( this ).attr( 'name' ),
					value: jQuery( this ).val()
				} ).appendTo( $form );
			}
		}
	};

	var callback = function ( $form ) {
		return function ( token ) {
			$form.off( 'submit.itsecRecaptcha' );
			$form.off( 'click.itsecRecaptcha' );

			jQuery( 'textarea[name="g-recaptcha-response"]', $form ).val( token );

			// Properly submit forms that have an input with a name of "submit".
			if ( jQuery( ':input[name="submit"]', $form ).length ) {
				HTMLFormElement.prototype.submit.call( $form.get( 0 ) );
			} else {
				$form.trigger( 'submit' );
			}
		};
	};

	jQuery.each( captchas, function ( i, el ) {
		var $captcha = jQuery( el );

		var $form = $captcha.parents( 'form' ), captchaId = $captcha.attr( 'id' );

		var clientId = grecaptcha.render( captchaId, {
			sitekey : $captcha.data( 'sitekey' ),
			callback: callback( $form ),
			size    : 'invisible'
		} );

		$form.on( 'submit.itsecRecaptcha', 'form', submit( $form, clientId, false ) );
		$form.on( 'click.itsecRecaptcha', ':submit', submit( $form, clientId, true ) );
	} );
}

function itsecRecaptchaHasUserFacingError() {
	return 0 !== jQuery( '.grecaptcha-user-facing-error' ).length && '' !== jQuery( '.grecaptcha-user-facing-error' ).first().html();
}