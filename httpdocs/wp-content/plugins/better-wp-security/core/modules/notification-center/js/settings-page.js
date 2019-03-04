jQuery( function ( $ ) {

	$( document ).on( 'click', '.itsec-notification-center-enable-notification input[type="checkbox"]', function () {
		toggleSettings( $( this ) );
	} );

	$( document ).on( 'itsec-dismiss-notice', '.itsec-notification-center-mail-errors-container .notice.itsec-is-dismissible', function () {
		itsecUtil.sendModuleAJAXRequest( 'notification-center', { method: 'dismiss-mail-error' }, function ( r ) {
			if ( r.success ) {
				jQuery( '#itsec-module-card-notification-center' ).removeClass( 'itsec-module-status--warning' );
			}
		} )
	} );

	function initializeHiding() {

		$( '.itsec-notification-center-enable-notification input[type="checkbox"]' ).each( function () {
			toggleSettings( $( this ) );
		} );
	}

	initializeHiding();

	function toggleSettings( $input ) {
		var isEnabled = $input.is( ':checked' ), slug = $input.data( 'slug' );

		var $other = $( 'tr:not(.itsec-notification-center-enable-notification)', '#itsec-notification-center-notification-' + slug );

		if ( isEnabled ) {
			$other.show();
		} else {
			$other.hide();
		}
	}

	$( document ).on( 'change', '.itsec-notification-center-user-list-type', function ( e ) {
		$( this ).next().toggleClass( 'hidden' );
	} );

	itsecSettingsPage.events.on( 'modulesReloaded', initializeHiding );
	itsecSettingsPage.events.on( 'moduleReloaded', function ( _, module ) {
		if ( 'notification-center' === module ) {
			initializeHiding();
		}
	} );
} );
