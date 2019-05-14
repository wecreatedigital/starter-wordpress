"use strict";

var itsecLogsPage = {
	init: function() {
		this.bindEvents();

		var id = itsecUtil.getUrlParameter( 'id' );

		if ( false !== id ) {
			itsecLogsPage.showLog( id );
		}

		itsecLogsPage.originalHREF = jQuery( '.itsec-module-cards-container .subsubsub a.current' ).attr( 'href' );

		this.migrateOldLogs();
	},

	bindEvents: function() {
		var $container = jQuery( '#wpcontent' );

		$container.on( 'click', '.itsec-logs-view-details', this.showModal );
		$container.on( 'click', '.itsec-close-modal, .itsec-modal-background', this.closeModal );
		$container.on( 'click', '.itsec-log-raw-details-toggle', this.toggleRawDetails );
		$container.on( 'keyup', this.closeModal );
	},

	toggleRawDetails: function( e ) {
		e.preventDefault();

		if ( jQuery( '.itsec-log-raw-details' ).is( ':visible' ) ) {
			jQuery( this ).html( itsec_page.translations.show_raw_details );
			jQuery( '.itsec-log-raw-details' ).hide();
		} else {
			jQuery( this ).html( itsec_page.translations.hide_raw_details );
			jQuery( '.itsec-log-raw-details' ).show();
		}
	},

	showModal: function( e ) {
		e.preventDefault();

		var id = jQuery( this ).parent().parent().find( 'td.column-id' ).html();

		try {
			if ( '' != itsecLogsPage.originalHREF ) {
				window.history.replaceState( {}, '', itsecLogsPage.originalHREF + '&id=' + id );
			}
		} catch( err ) {}

		itsecLogsPage.showLog( id );
	},

	showLog: function( id ) {
		var $modalBackground = jQuery( '.itsec-modal-background' ),
			$detailsContainer = jQuery( '#itsec-log-details-container' );

		jQuery( '#itsec-log-details-container .itsec-module-messages-container' ).html( '' );
		jQuery( '#itsec-log-details-container .itsec-module-settings-content-main' ).html( itsec_page.translations.loading );

		$modalBackground.fadeIn();
		$detailsContainer.fadeIn( 200 );

		jQuery( 'body' ).addClass( 'itsec-modal-open' );

		var $cached_data = jQuery( '#itsec-logs-cache-id-' + id );

		if ( $cached_data.length ) {
			jQuery( '#itsec-log-details-container' ).html( $cached_data.html() );
			jQuery( '.itsec-log-raw-details' ).hide();
			return;
		}

		var postData = {
			'action': itsec_page.ajax_action,
			'nonce':  itsec_page.ajax_nonce,
			'id':     id,
		};

		jQuery.post( ajaxurl, postData )
			.always(function( a, status, b ) {
				itsecLogsPage.updateDetails( a, status, b, id );
			});
	},

	updateDetails: function( a, status, b, id ) {
		var results = {
			'id':            id,
			'status':        status,
			'jqxhr':         null,
			'success':       false,
			'response':      null,
			'errors':        [],
			'warnings':      [],
			'messages':      [],
			'functionCalls': [],
			'redirect':      false,
			'closeModal':    true
		};


		if ( 'ITSEC_Response' === a.source && 'undefined' !== a.response ) {
			// Successful response with a valid format.
			results.jqxhr = b;
			results.success = a.success;
			results.response = a.response;
			results.errors = a.errors;
			results.warnings = a.warnings;
			results.messages = a.messages;
			results.functionCalls = a.functionCalls;
			results.redirect = a.redirect;
			results.closeModal = a.closeModal;
		} else if ( a.responseText ) {
			// Failed response.
			results.jqxhr = a;
			var errorThrown = b;

			if ( 'undefined' === typeof results.jqxhr.status ) {
				results.jqxhr.status = -1;
			}

			if ( 'timeout' === status ) {
				var error = itsec_page.translations.ajax_timeout;
			} else if ( 'parsererror' === status ) {
				var error = itsec_page.translations.ajax_parsererror;
			} else if ( 403 == results.jqxhr.status ) {
				var error = itsec_page.translations.ajax_forbidden;
			} else if ( 404 == results.jqxhr.status ) {
				var error = itsec_page.translations.ajax_not_found;
			} else if ( 500 == results.jqxhr.status ) {
				var error = itsec_page.translations.ajax_server_error;
			} else {
				var error = itsec_page.translations.ajax_unknown;
			}

			error = error.replace( '%1$s', status );
			error = error.replace( '%2$s', errorThrown );

			results.errors = [ error ];
		} else {
			// Successful response with an invalid format.
			results.jqxhr = b;

			results.response = a;
			results.errors = [ itsec_page.translations.ajax_invalid ];
		}


		if ( results.redirect ) {
			window.location = results.redirect;
		}


		var $messages = jQuery( '#itsec-log-details-container .itsec-module-messages-container' ),
			$content  = jQuery( '#itsec-log-details-container .itsec-module-settings-content-main' );

		$messages.html( '' );

		for ( var i = 0; i < results.errors.length; i++ ) {
			$messages.append( '<div class="error inline"><p><strong>' + results.errors[i] + '</strong></p></div>' );
		}
		for ( var i = 0; i < results.warnings.length; i++ ) {
			$messages.append( '<div class="warning inline"><p><strong>' + results.warnings[i] + '</strong></p></div>' );
		}
		for ( var i = 0; i < results.messages.length; i++ ) {
			$messages.append( '<div class="info inline"><p><strong>' + results.messages[i] + '</strong></p></div>' );
		}

		$content.html( results.response );


		var $div = jQuery( '<div>', {id: 'itsec-logs-cache-id-' + id} );
		$div.html( jQuery( '#itsec-log-details-container' ).html() );

		jQuery( '#itsec-logs-cache' ).append( $div );
	},

	closeModal: function( e ) {
		if ( 'undefined' !== typeof e ) {
			e.preventDefault();

			// For keyup events, only process esc
			if ( 'keyup' === e.type && 27 !== e.which ) {
				return;
			}
		}


		try {
			if ( '' != itsecLogsPage.originalHREF ) {
				window.history.replaceState( {}, '', itsecLogsPage.originalHREF );
			}
		} catch( err ) {}


		jQuery( '.itsec-modal-background' ).fadeOut();
		jQuery( '#itsec-log-details-container' ).fadeOut( 200 );
		jQuery( 'body' ).removeClass( 'itsec-modal-open' );
	},

	migrateOldLogs: function() {
		var $status = jQuery( '#old-logs-migration-status' );

		if ( $status.length < 1 ) {
			return;
		}

		var message = itsec_page.translations.log_migration_started.replace( '%1$s', '<img src="' + itsec_page.translations.log_migration_loading_url + '" />' );

		$status.append( '<div class="notice notice-info notice-alt"><p><strong>' + message + '</strong></p></div>' );

		itsecLogsPage.sendMigrationRequest();
	},

	handleMigrationCallback: function( results ) {
		var clearStatus = false;

		if ( results.response && results.response.length ) {
			if ( 'incomplete' === results.response ) {
				if ( 'undefined' === typeof itsecLogsPage.callCount ) {
					itsecLogsPage.callCount = 1;
				}

				itsecLogsPage.callCount++;

				if ( 0 === itsecLogsPage.callCount % 10 ) {
					// Every 10 requests, delay a bit to prevent from slamming the server.
					setTimeout( itsecLogsPage.sendMigrationRequest, 5000 );
				} else {
					itsecLogsPage.sendMigrationRequest();
				}

				return;
			}

			jQuery('#old-logs-migration-status').html( results.response );
		} else {
			clearStatus = true;
		}

		if ( results.errors.length > 0 ) {
			if ( 'undefined' === typeof itsecLogsPage.errorCount ) {
				itsecLogsPage.errorCount = 0;
			}

			itsecLogsPage.errorCount++;

			if ( itsecLogsPage.errorCount < 10 ) {
				// Keep retrying until we reach 10 errors, but delay a bit before retrying.
				setTimeout( itsecLogsPage.sendMigrationRequest, 5000 );

				return;
			}
		}

		if ( clearStatus ) {
			jQuery('#old-logs-migration-status').html( '' );
		}

		if ( results.errors.length > 0 ) {
			var message = '<div class="notice notice-error notice-alt"><p><strong>' + itsec_page.translations.log_migration_failed + '</strong></p></div>';
			jQuery('#old-logs-migration-status').append( message );
		}

		if ( results.warnings.length > 0 ) {
			jQuery.each( results.warnings, function( index, warning ) {
				message = '<div class="notice notice-warning notice-alt"><p>' + warning + '</p></div>';
				jQuery('#old-logs-migration-status').append( message );
			} );
		}
	},

	sendMigrationRequest: function() {
		itsecUtil.sendAJAXRequest( 'logs', 'handle_logs_migration', {}, itsecLogsPage.handleMigrationCallback, itsec_page.ajax_action, itsec_page.ajax_nonce );
	}
};

jQuery(document).ready(function( $ ) {
	itsecLogsPage.init();
});
