"use strict";

var itsecUtil = {

	focus: function( $el, $fallback ) {
		if ( itsecUtil.isElementVisible( $el ) && jQuery( window ).height() > 800 ) {
			$el.focus();
		} else {
			$fallback.prop( 'tabindex', -1 ).focus();
		}
	},

	isElementVisible: function( $el ) {

		var $window = jQuery( window ), height = $window.height(), width = $window.width(), offset = $el.offset();

		if ( ! $el || ! offset ) {
			return false;
		}

		return offset.top < height && offset.left < width;
	},

	sendModuleAJAXRequest: function( module, data, callback ) {
		itsecUtil.sendAJAXRequest( module, 'handle_module_request', data, callback );
	},

	sendWidgetAJAXRequest: function( widget, data, callback ) {
		itsecUtil.sendAJAXRequest( widget, 'handle_widget_request', data, callback );
	},

	sendAJAXRequest: function( module, method, data, callback, action, nonce ) {
		var postData = {
			'action': itsec_util.ajax_action,
			'nonce':  itsec_util.ajax_nonce,
			'module': module,
			'method': method,
			'data':   data,
		};

		if ( 'undefined' !== typeof action ) {
			postData.action = action;
		}

		if ( 'undefined' !== typeof nonce ) {
			postData.nonce = nonce;
		}

		jQuery.post( ajaxurl, postData )
			.always(function( a, status, b ) {
				itsecUtil.processAjaxResponse( a, status, b, module, method, data, callback );
			});
	},

	processAjaxResponse: function( a, status, b, module, method, data, callback ) {
		var results = {
			'module':        module,
			'method':        method,
			'data':          data,
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
			results.infos = a.infos;
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

			var error = '';

			if ( 'timeout' === status ) {
				error = itsec_util.translations.ajax_timeout;
			} else if ( 'parsererror' === status ) {
				error = itsec_util.translations.ajax_parsererror;
			} else if ( 403 == results.jqxhr.status ) {
				error = itsec_util.translations.ajax_forbidden;
			} else if ( 404 == results.jqxhr.status ) {
				error = itsec_util.translations.ajax_not_found;
			} else if ( 500 == results.jqxhr.status ) {
				error = itsec_util.translations.ajax_server_error;
			} else {
				error = itsec_util.translations.ajax_unknown;
			}

			error = error.replace( '%1$s', status );
			error = error.replace( '%2$s', errorThrown );

			results.errors = [ error ];
		} else {
			// Successful response with an invalid format.
			results.jqxhr = b;

			results.response = a;
			results.errors = [ itsec_util.translations.ajax_invalid ];
		}


		if ( results.redirect ) {
			window.location = results.redirect;
		}


		if ( 'function' === typeof callback ) {
			callback( results );
		} else if ( 'function' === typeof console.log ) {
			console.log( 'ERROR: Unable to handle settings AJAX request due to an invalid callback:', callback, {'data': postData, 'results': results} );
		}


		if ( results.functionCalls ) {
			for ( var i = 0; i < results.functionCalls.length; i++ ) {
				if ( 'object' === typeof itsecSettingsPage && 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof itsecSettingsPage[results.functionCalls[i][0]] ) {
					itsecSettingsPage[results.functionCalls[i][0]]( results.functionCalls[i][1], results );
				} else if ( 'string' === typeof results.functionCalls[i] && 'function' === typeof window[results.functionCalls[i]] ) {
					window[results.functionCalls[i]]();
				} else if ( 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof window[results.functionCalls[i][0]] ) {
					window[results.functionCalls[i][0]]( results.functionCalls[i][1] );
				} else if ( 'function' === typeof console.log ) {
					console.log( 'ERROR: Unable to call missing function:', results.functionCalls[i] );
				}
			}
		}
	},

	getUrlParameter: function( name ) {
		var pageURL = decodeURIComponent( window.location.search.substring( 1 ) ),
			URLParameters = pageURL.split( '&' ),
			parameterName,
			i;

		// Loop through all parameters
		for ( i = 0; i < URLParameters.length; i++ ) {
			parameterName = URLParameters[i].split( '=' );

			// If this is the parameter we're looking for
			if ( parameterName[0] === name ) {
				// Return the value or true if there is no value
				return parameterName[1] === undefined ? true : parameterName[1];
			}
		}
		// If the requested parameter doesn't exist, return false
		return false;
	},

	buildNotices: function ( response, asAlt ) {
		var notices = [],
			types = ['error', 'warning', 'message', 'info'];

		for ( var i = 0; i < types.length; i++ ) {
			for ( var j = 0; j < response[types[i] + 's'].length; j++ ) {
				notices.push( itsecUtil.makeNotice( response[types[i] + 's'][j], types[i], asAlt ) );
			}
		}

		return notices;
	},

	makeNotice: function ( message, type, asAlt ) {
		type = type === 'message' ? 'success' : type;

		var className = 'notice notice-' + type;

		if ( asAlt ) {
			className += ' notice-alt';
		}

		return jQuery( '<div>', { class: className } )
			.append( jQuery( '<p>', { html: message } ) );
	},

	displayNotices: function ( response, $container, asAlt ) {
		var notices = itsecUtil.buildNotices( response, asAlt );

		for ( var i = 0; i < notices.length; i++ ) {
			(function ( $notice ) {
				$container.append( $notice );
				setTimeout( function () {$notice.remove();}, 10000 );
			})( notices[i].clone() );
		}
	},
};
