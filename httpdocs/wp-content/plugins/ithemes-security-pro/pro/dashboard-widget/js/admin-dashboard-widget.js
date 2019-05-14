jQuery( document ).ready( function ( $ ) {

	//Add full width class to malware or file-change for proper formatting (where needed)
	var has_malware = false;
	var has_file_scan = false;

	if ( jQuery( '.itsec_malware_widget' ).length ) {
		has_malware = true;
	}

	if ( jQuery( '.itsec_file-change_widget' ).length ) {
		has_file_scan = true;
	}

	if ( has_malware == true && has_file_scan != true ) {
		jQuery( '.itsec_malware_widget' ).addClass( 'full-width' );
	}

	if ( has_malware != true && has_file_scan == true ) {
		jQuery( '.itsec_file-change_widget' ).addClass( 'full-width' );
	}

	function initializeScan() {

		var $button = $( '#itsec_dashboard_one_time_file_check' );

		if ( ! $button || ! $button.length ) {
			return;
		}

		if ( ! window.ITSECFileChangeScanner ) {
			console.error( 'Tried to run file change scan without ITSECFileChangeScanner being available.' );

			return;
		}

		var scan = new window.ITSECFileChangeScanner( $button, {
			classList       : 'button-secondary',
			messageContainer: $( '#itsec_dashboard_one_time_file_check_results' ),
		} );

		$button.on( 'click', function ( e ) {
			e.preventDefault();

			scan.start();
		} );
	}

	initializeScan();

	//process clear lockouts
	jQuery( '.itsec_release_lockout' ).bind( 'click', function ( event ) {

		event.preventDefault();

		var caller = this;

		if ( jQuery( caller ).hasClass( 'locked_host' ) ) {
			var lock_type = 'host';
		}

		if ( jQuery( caller ).hasClass( 'locked_user' ) ) {
			var lock_type = 'user';
		}

		var data = {
			action  : 'itsec_release_dashboard_lockout',
			nonce   : jQuery( caller ).attr( 'href' ),
			type    : lock_type,
			resource: jQuery( caller ).attr( 'id' )
		};

		//call the ajax
		jQuery.post( ajaxurl, data, function ( response ) {

			if ( response == 1 ) {

				var item = jQuery( caller ).closest( 'li' );

				var list = jQuery( item ).closest( 'ul' )

				jQuery( item ).remove();

				var list_length = jQuery( list ).children( 'li' ).length

				if ( list_length == 0 ) {

					if ( lock_type == 'user' ) {

						jQuery( list ).replaceWith( itsec_dashboard_widget_js.user );

					} else {

						jQuery( list ).replaceWith( itsec_dashboard_widget_js.host );

					}

				}

				var current_total = parseInt( jQuery( '#current-itsec-lockout-summary-total' ).html() );

				jQuery( '#current-itsec-lockout-summary-total' ).html( current_total - 1 );

			}

		} );

	} );

	$( document ).on( 'click', '#itsec-dashboard-widget .notice-dismiss', function() {
		$.post( ajaxurl, {
			action: 'itsec-dismiss-dashboard-widget-nag',
			nonce : itsec_dashboard_widget_js.dismiss_nonce,
		}, function( response ) {
			if ( !response.success ) {
				alert( response.data.message || 'An unexpected error occurred.' );
			}
		} );
	} );

} );
