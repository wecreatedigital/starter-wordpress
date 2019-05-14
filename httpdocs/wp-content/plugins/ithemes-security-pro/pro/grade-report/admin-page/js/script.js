"use strict";

var itsecGradeReportPage = {
	init: function() {
		this.bindEvents();

		itsecGradeDonut.draw( 'itsec-grade-donut', 'itsec-grade-donut-data' );

		this.redraw();
	},

	bindEvents: function() {
		var $container = jQuery( '#wpcontent' );

		jQuery( window ).resize( itsecGradeReportPage.redraw );

		$container.on( 'click', '#itsec-select-all-issues', this.selectAllIssues );
		$container.on( 'click', '#itsec-resolve-issues', this.resolveSelectedIssues );
//		$container.on( 'click', '.itsec-view-report', this.showReport );
		$container.on( 'click', '.itsec-resolve-issues', this.showResolveIssues );
		$container.on( 'click', '.itsec-card-security-score .itsec-card-subheading a', this.showResolveIssues );
		$container.on( 'click', '.itsec-close-modal, .itsec-modal-background', this.closeModal );
		$container.on( 'keyup', this.closeModal );
	},

	redraw: function() {
		itsecGradeReportPage.renderSummary();
		itsecGradeReportPage.scaleResolveIssues();
	},

	scaleResolveIssues: function() {
		var $container = jQuery( '#itsec-resolve-issues-container' );

		if ( ! $container.is( ':visible' ) ) {
			return;
		}

		var $sections = jQuery( '#itsec-resolve-issues-sections' );
		var $cards = jQuery( '#itsec-resolve-issues-sections .itsec-card' );

		var containerHeight = jQuery( '.itsec-module-settings-content-container', $container ).height();
		containerHeight -= $sections.outerHeight( true ) - $sections.height();

		var cardHeight = Math.floor( containerHeight / $cards.length );

		$cards.each( function() {
			var $header = jQuery( '.itsec-card-header', this );
			var $list = jQuery( '.itsec-section-list', this );

			var height = cardHeight - $header.outerHeight() - ( $list.outerHeight( true ) - $list.height() );

			$list.css( 'max-height', height + 'px' );
		} );
	},

	selectAllIssues: function() {
		if ( jQuery( this ).prop( 'checked' ) ) {
			jQuery( '#itsec-resolve-issues-sections input[type="checkbox"]' ).prop( 'checked', true );
		} else {
			jQuery( '#itsec-resolve-issues-sections input[type="checkbox"]' ).prop( 'checked', false );
		}
	},

	resolveSelectedIssues: function( e ) {
		e.preventDefault();

		var $this = jQuery( this );
		$this.prop( 'disabled', true );

		var $modalBackground = jQuery( '.itsec-modal-background' ),
		    $detailsContainer = jQuery( '#itsec-resolve-issues-container' ),
		    $messagesContainer = jQuery( '.itsec-module-messages-container', $detailsContainer ),
		    $contentContainer = jQuery( '.itsec-module-settings-content-main', $detailsContainer );

		$modalBackground.fadeIn();
		$detailsContainer.fadeIn( 200 );

		jQuery( 'body' ).addClass( 'itsec-modal-open' );

		var selected = jQuery( '#itsec-resolve-issues-container form input:checkbox:checked' ).map( function() {
			return jQuery( this ).data( 'id' );
		} ).get();

		var postData = {
			'action':   itsec_page.ajax_action,
			'_wpnonce': itsec_page.ajax_nonce,
			'method':   'resolve_selected_issues',
			'selected': selected
		};

		jQuery.post( ajaxurl, postData )
			.always(function( a, status, b ) {
				$this.prop( 'disabled', false );
				itsecGradeReportPage.handleResolveResponse( a, status, b, postData.method, $messagesContainer, $contentContainer );
			});
	},

	renderSummary: function() {
		var $canvas = jQuery( '#itsec-summary-canvas-container canvas' );
		var canvas = $canvas.get( 0 );

		var font = '16px sans-serif';
		var sectionHeight = 50;
		var barHeight = 23;
		var barCount = $canvas.children().length;
		var legendPad = 20;

		canvas.width = jQuery( '#itsec-summary-canvas-container' ).width();
		canvas.height = barCount * sectionHeight + legendPad + 15;

		var canvasWidth = $canvas.width();
		var canvasHeight = $canvas.height();

		var ctx = canvas.getContext( '2d' );

		//Reset the canvas
		ctx.clearRect( 0, 0, canvasWidth, canvasHeight );
		ctx.restore();
		ctx.save();

		ctx.font = font;


		var xMin = 0.5;
		var xMax = canvasWidth - 0.5;

		var text0 = ctx.measureText( '0' );
		var text100 = ctx.measureText( '100' );

		var minX = Math.round( text0.width / 2 ) + 0.5;
		var maxX = canvasWidth - Math.round( text100.width / 2 ) - 0.5;
		var minY = 0.5;
		var maxY = sectionHeight * barCount + 0.5;
		var width = maxX - minX;

		ctx.strokeStyle = '#DDDEDF';
		ctx.fillStyle = jQuery( '#itsec-summary-canvas-captions' ).css( 'color' );

		for ( var percent = 0; percent <= 100; percent += 20 ) {
			var x = minX + Math.round( width * percent / 100 );

			ctx.beginPath();
			ctx.moveTo( x, minY );
			ctx.lineTo( x, maxY );
			ctx.stroke();

			var text = ctx.measureText( percent );
			ctx.fillText( percent, x - Math.round( text.width / 2 ), maxY + legendPad );
		}

		var y = minY;

		var offsetTop = Math.round( ( sectionHeight - barHeight ) / 2 );
		var offsetBottom = offsetTop + barHeight;

		$canvas.children().map( function () {
			var current = parseFloat( jQuery( this ).data( 'current' ) );
			var potential = parseFloat( jQuery( this ).data( 'potential' ) );
			var max = parseFloat( jQuery( this ).data( 'max' ) );
			var currentX = minX + Math.round( current / max * width )
			var potentialX = minX + Math.round( potential / max * width );

			itsecGradeReportPage.drawBar( ctx, '#F0F2F8', minX, y + offsetTop, maxX, y + offsetBottom, 0 );
			itsecGradeReportPage.drawBar( ctx, '#B2E8DA', minX, y + offsetTop, potentialX, y + offsetBottom, 3 );
			itsecGradeReportPage.drawBar( ctx, '#00A0D2', minX, y + offsetTop, currentX, y + offsetBottom, 3 );

			y += sectionHeight;
		});
	},

	drawBar: function( ctx, color, x1, y1, x2, y2, radius ) {
		var oldFillStyle = ctx.fillStyle;
		var oldStrokeStyle = ctx.strokeStyle;

		ctx.fillStyle = color;
		ctx.strokeStyle = color;

		ctx.beginPath();
		ctx.moveTo(x1 + radius, y1);
		ctx.lineTo(x2 - radius, y1);
		ctx.quadraticCurveTo(x2, y1, x2, y1 + radius);
		ctx.lineTo(x2, y2 - radius);
		ctx.quadraticCurveTo(x2, y2, x2 - radius, y2);
		ctx.lineTo(x1 + radius, y2);
		ctx.quadraticCurveTo(x1, y2, x1, y2 - radius);
		ctx.lineTo(x1, y1 + radius);
		ctx.quadraticCurveTo(x1, y1, x1 + radius, y1);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();

		ctx.fillStyle = oldFillStyle;
		ctx.strokeStyle = oldStrokeStyle;
	},

	showResolveIssues: function( e ) {
		e.preventDefault();

		var $modalBackground = jQuery( '.itsec-modal-background' ),
		    $detailsContainer = jQuery( '#itsec-resolve-issues-container' );

		var $contentContainer = jQuery( '#itsec-resolve-issues-container .itsec-module-settings-content-main' );

//		$contentContainer.append( itsec_page.translations.loading );

		$modalBackground.fadeIn();
		$detailsContainer.fadeIn( 200 );

		jQuery( 'body' ).addClass( 'itsec-modal-open' );

		itsecGradeReportPage.scaleResolveIssues();

/*		var postData = {
			'action': itsec_page.ajax_action,
			'nonce':  itsec_page.ajax_nonce,
			'id':     id,
			'method': method
		};

		jQuery.post( ajaxurl, postData )
			.always(function( a, status, b ) {
				itsecGradeReportPage.updateDetails( a, status, b, id, $contentContainer );
			});*/
	},

/*	showReport: function( e ) {
		e.preventDefault();

		var $modalBackground = jQuery( '.itsec-modal-background' ),
		    $detailsContainer = jQuery( '#itsec-report-container' ),
		    id = jQuery( this ).closest( '.itsec-card' ).attr( 'class' ).split( ' ' )[1].substr( 11 );

		try {
			if ( '' != itsecGradeReportPage.originalHREF ) {
				window.history.replaceState( {}, '', itsecGradeReportPage.originalHREF + '&id=' + id );
			}
		} catch( err ) {}

		jQuery( '#itsec-report-container .itsec-module-messages-container' ).html( '' );
		jQuery( '#itsec-report-container .itsec-modal-title' ).html( '' );

		var $contentContainer = jQuery( '#itsec-report-container .itsec-module-settings-content-main' );
		$contentContainer.html( '' );

		var method = 'summary-report';

		if ( 'summary' !== id ) {
			jQuery( '.itsec-card-' + id + ' .itsec-card-header' ).clone().appendTo( '#itsec-report-container .itsec-modal-title' );

			var count = jQuery( '.itsec-card-' + id + ' .itsec-category-list li' ).length;
			var numCriteria = itsec_page.translations.num_criteria.replace( /%d/, count );
			jQuery( '#itsec-report-container .itsec-modal-title .itsec-card-subheading' ).removeClass( 'itsec-score-calculation' ).html( numCriteria );

			var $cardIdentifier = jQuery( '<div class="itsec-card-' + id + '"></div>' );
			var $content = jQuery( '<div class="itsec-report-content"></div>' );
			$contentContainer.append( $cardIdentifier );
			$cardIdentifier.append( '<div class="itsec-category-icon"></div>' ).append( $content );
			$contentContainer = $content;

			method = 'report';
		}

		$contentContainer.append( itsec_page.translations.loading );

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
			'method': method
		};

		jQuery.post( ajaxurl, postData )
			.always(function( a, status, b ) {
				itsecGradeReportPage.updateDetails( a, status, b, id, $contentContainer );
			});
	},*/

	handleResolveResponse: function( a, status, b, method, $messages, $content ) {
		var results = {
			'method':        method,
			'status':        status,
			'jqxhr':         null,
			'success':       false,
			'response':      null,
			'errors':        [],
			'warnings':      [],
			'messages':      [],
			'infos':         [],
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
			results.infos = ( null === a.infos ) ? [] : a.infos;
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


		$messages.html( '' );

		for ( var i = 0; i < results.errors.length; i++ ) {
			$messages.append( '<div class="notice notice-alt notice-error"><p><strong>' + results.errors[i] + '</strong></p></div>' );
		}
		for ( var i = 0; i < results.warnings.length; i++ ) {
			$messages.append( '<div class="notice notice-alt notice-warning"><p><strong>' + results.warnings[i] + '</strong></p></div>' );
		}
		for ( var i = 0; i < results.messages.length; i++ ) {
			$messages.append( '<div class="notice notice-alt notice-success"><p><strong>' + results.messages[i] + '</strong></p></div>' );
		}
		for ( var i = 0; i < results.infos.length; i++ ) {
			$messages.append( '<div class="notice notice-alt notice-info"><p><strong>' + results.infos[i] + '</strong></p></div>' );
		}

		if ( results.functionCalls ) {
			for ( var i = 0; i < results.functionCalls.length; i++ ) {
				if ( 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof itsecGradeReportPage[results.functionCalls[i][0]] ) {
					itsecGradeReportPage[results.functionCalls[i][0]]( results.functionCalls[i][1], results );
				} else if ( 'string' === typeof results.functionCalls[i] && 'function' === typeof window[results.functionCalls[i]] ) {
					window[results.functionCalls[i]]();
				} else if ( 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof window[results.functionCalls[i][0]] ) {
					window[results.functionCalls[i][0]]( results.functionCalls[i][1] );
				} else if ( 'function' === typeof console.log ) {
					console.log( 'ERROR: Unable to call missing function:', results.functionCalls[i] );
				}
			}
		}

/*		$content.html( results.response );


		var $div = jQuery( '<div>', {id: 'itsec-grade-report-cache-id-' + id} );
		$div.html( jQuery( '#itsec-report-container' ).html() );

		jQuery( '#itsec-grade-report-cache' ).append( $div );*/
	},

	reloadModule: function( module ) {},

	updatePageAfterFixes: function( data ) {
		var $modalContainer = jQuery( '#itsec-resolve-issues-container' ),
		    $modalTitle = jQuery( '.itsec-modal-title', $modalContainer ),
		    $modalContent = jQuery( '.itsec-module-settings-content-main', $modalContainer ),
		    $cards = jQuery( '#itsec-grade-report-cards' );

		$modalTitle.html( data.modalTitle );
		$modalContent.html( data.modalContentMain );
		$cards.html( data.cards );

		itsecGradeDonut.draw( 'itsec-grade-donut', 'itsec-grade-donut-data' );

		itsecGradeReportPage.redraw();
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
			if ( '' != itsecGradeReportPage.originalHREF ) {
				window.history.replaceState( {}, '', itsecGradeReportPage.originalHREF );
			}
		} catch( err ) {}


		jQuery( '.itsec-modal-background' ).fadeOut();
		jQuery( '#itsec-report-container' ).fadeOut( 200 );
		jQuery( '#itsec-resolve-issues-container' ).fadeOut( 200 );
		jQuery( 'body' ).removeClass( 'itsec-modal-open' );
	}
};


jQuery(document).ready(function( $ ) {
	itsecGradeReportPage.init();
});
