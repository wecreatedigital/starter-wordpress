/* global grecaptcha */

function itsecRecaptchav3Load() {
	var submit = function( $form, $input, isClick ) {
		return function( e ) {
			e.preventDefault();

			var $this = jQuery( this ).attr( 'disabled', true );

			if ( isClick ) {
				jQuery( '<input type="hidden">' ).attr( {
					name : jQuery( this ).attr( 'name' ),
					value: jQuery( this ).val(),
				} ).appendTo( $form );
			}

			grecaptcha.ready( function() {
				grecaptcha.execute( $input.data( 'sitekey' ), { action: $input.data( 'action' ) } )
					.then( callback( $form, $input ) )
					.then( function() {
						$this.removeAttr( 'disabled' );
					} );
			} );
		};
	};

	var callback = function( $form, $input ) {
		return function( token ) {
			$form.off( 'submit.itsecRecaptcha' );
			$form.off( 'click.itsecRecaptcha' );

			$input.val( token );

			// Properly submit forms that have an input with a name of "submit".
			if ( jQuery( ':input[name="submit"]', $form ).length ) {
				HTMLFormElement.prototype.submit.call( $form.get( 0 ) );
			} else {
				$form.trigger( 'submit' );
			}
		};
	};

	jQuery( function() {
		jQuery( '.g-recaptcha' ).each( function() {
			var $input = jQuery( this ),
				$form = $input.parents( 'form' );

			if ( !$form ) {
				return;
			}

			$form.on( 'submit.itsecRecaptcha', 'form', submit( $form, $input, false ) );
			$form.on( 'click.itsecRecaptcha', ':submit', submit( $form, $input, true ) );
		} );
	} );
}
