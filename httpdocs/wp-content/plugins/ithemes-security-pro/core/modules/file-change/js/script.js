jQuery( document ).ready( function ( $ ) {

	var $notice = $( '#itsec-file-change-warning-dialog' ),
		$button = $('.notice-dismiss', $notice);

	$button.off( 'click.wp-dismiss-notice' );

	$button.click( function ( e ) {
		e.preventDefault();

		var $button = $( this );

		$button.prop( 'disabled', true );
		$button.css( 'opacity', .5 );

		var data = {
			action: itsec_file_change.ajax_action,
			nonce : itsec_file_change.ajax_nonce,
		};

		$.post( ajaxurl, data, function ( response ) {
			$button.prop( 'disabled', false );
			$button.css( 'opacity', 1 );

			if ( response.success ) {
				$notice.fadeTo( 100, 0, function () {
					$notice.slideUp( 100, function () {
						$notice.remove();
					} );
				} );
			} else {
				alert( response.data.message )
			}
		} );
	} );
} );
