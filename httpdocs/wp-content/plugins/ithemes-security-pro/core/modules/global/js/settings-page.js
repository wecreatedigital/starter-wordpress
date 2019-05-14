function itsec_change_show_error_codes( args ) {
	var show = args[0];

	if ( show ) {
		jQuery( 'body' ).addClass( 'itsec-show-error-codes' );
	} else {
		jQuery( 'body' ).removeClass( 'itsec-show-error-codes' );
	}
}

function itsec_change_write_files( args ) {
	var enabled = args[0];

	if ( enabled ) {
		jQuery( 'body' ).removeClass( 'itsec-write-files-disabled' ).addClass( 'itsec-write-files-enabled' );
	} else {
		jQuery( 'body' ).removeClass( 'itsec-write-files-enabled' ).addClass( 'itsec-write-files-disabled' );
	}
}

var itsec_log_type_changed = function() {
	var type = jQuery( '#itsec-global-log_type' ).val();

	if ( 'both' === type ) {
		jQuery( '#itsec-global-log_rotation' ).parents( 'tr' ).show();
		jQuery( '#itsec-global-file_log_rotation' ).parents( 'tr' ).show();
		jQuery( '#itsec-global-log_location' ).parents( 'tr' ).show();
	} else if ( 'file' === type ) {
		jQuery( '#itsec-global-log_rotation' ).parents( 'tr' ).hide();
		jQuery( '#itsec-global-file_log_rotation' ).parents( 'tr' ).show();
		jQuery( '#itsec-global-log_location' ).parents( 'tr' ).show();
	} else {
		jQuery( '#itsec-global-log_rotation' ).parents( 'tr' ).show();
		jQuery( '#itsec-global-file_log_rotation' ).parents( 'tr' ).hide();
		jQuery( '#itsec-global-log_location' ).parents( 'tr' ).hide();
	}
};

jQuery( document ).ready(function($) {
	var $container = jQuery( '#wpcontent' );

	$container.on( 'click', '#itsec-global-add-to-whitelist', function( e ) {
		e.preventDefault();

		var whitelist = jQuery( '#itsec-global-lockout_white_list' ).val();
		whitelist = whitelist.trim();
		whitelist += "\n" + itsec_global_settings_page.ip;
		jQuery( '#itsec-global-lockout_white_list' ).val( whitelist );
	} );

	$container.on( 'click', '#itsec-global-reset-log-location', function( e ) {
		e.preventDefault();

		jQuery( '#itsec-global-log_location' ).val( itsec_global_settings_page.log_location );
	} );

	$container.on( 'change', '#itsec-global-log_type', itsec_log_type_changed );

	itsec_log_type_changed();

	function proxyHeaderChanged() {
		if ( 'manual' === $( "#itsec-global-proxy" ).val() ) {
			$( '.itsec-global-proxy_header-container' ).show();
		} else {
			$( '.itsec-global-proxy_header-container' ).hide();
		}
	}

	proxyHeaderChanged();
	$( document ).on( 'change', '#itsec-global-proxy', proxyHeaderChanged );
	itsecSettingsPage.events.on( 'modulesReloaded', proxyHeaderChanged );
});
