"use strict";

(function( $ ) {
	var itsecMalwareScan = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$( document ).on( 'click', '#itsec-malware-scan-start', this.startScan );
			$( document ).on( 'click', '.itsec-malware-scan-results-wrapper .itsec-malware-scan-toggle-details', this.toggleDetails );
		},

		toggleDetails: function( e ) {
			e.preventDefault();

			var $container = $(this).parents( '.itsec-malware-scan-results-section' );
			var $details = $container.find( '.itsec-malware-scan-details' );

			if ( $details.is(':visible') ) {
				$(this).html( itsecMalwareScanData.showDetailsText );
				$details.hide();
			} else {
				$(this).html( itsecMalwareScanData.hideDetailsText );
				$details.show();
			}
		},

		startScan: function( e ) {
			e.preventDefault();

			itsecMalwareScanData.originalSubmitButtonText = $(this).val();

			$(this)
				.prop( 'disabled', true )
				.val( itsecMalwareScanData.clickedButtonText );

			var data = {
				'action': 'run-scan'
			};

			itsecUtil.sendWidgetAJAXRequest( 'malware-scan', data, itsecMalwareScan.handleResponse );
		},

		handleResponse: function( results ) {
			$('#itsec-malware-scan-start').hide();

			if ( results.response && results.response.length ) {
				$('.itsec-malware-scan-results-wrapper').html( results.response );
			}

			if ( results.errors.length > 0 ) {
				var message;

				$.each( results.errors, function( index, error ) {
					message = '<div class="notice notice-error notice-alt"><p><strong>' + error + '</strong></p></div>';
					$('.itsec-malware-scan-results-wrapper').append( message );
				} );
			}

			if ( results.warnings.length > 0 ) {
				$.each( results.warnings, function( index, warning ) {
					message = '<div class="notice notice-warning notice-alt"><p>' + warning + '</p></div>';
					$('.itsec-malware-scan-results-wrapper').append( message );
				} );
			}
		},
	};

	$(document).ready(function() {
		itsecMalwareScan.init();
	});
})( jQuery );
