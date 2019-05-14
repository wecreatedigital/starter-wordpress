"use strict";

var itsecSettingsPage = {

	events: jQuery( {} ),

	init: function() {
		jQuery( '.itsec-module-settings-container' ).hide();

		this.bindEvents();

		jQuery( '.itsec-settings-view-toggle .itsec-selected' ).removeClass( 'itsec-selected' ).trigger( 'click' );
		jQuery( '.itsec-settings-toggle' ).trigger( 'change' );

		this.initFilters();
		this.initCurrentModule();
		this.makeNoticesDismissible();
	},

	initFilters: function() {
		var module_type = itsecUtil.getUrlParameter( 'module_type' );
		if ( false === module_type || 0 === jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) ).length ) {
			module_type = 'recommended';
		}
		jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) + ' a' ).trigger( 'click' );
	},

	initCurrentModule: function() {

		var module = itsecUtil.getUrlParameter( 'module' );
		if ( 'string' === typeof module ) {
			jQuery( '#itsec-module-card-' + module.replace( /[^\w-]/g, '' ) + ' button.itsec-toggle-settings' ).trigger( 'click' );
		}
	},

	bindEvents: function() {

		if ( itsecSettingsPage.bindEvents.bound ) {
			return;
		}

		jQuery(window).on("popstate", function(e, data) {
			if ( null !== e.originalEvent.state && 'string' == typeof e.originalEvent.state.module && '' !== e.originalEvent.state.module.replace( /[^\w-]/g, '' ) ) {
				jQuery( '#itsec-module-card-' + e.originalEvent.state.module.replace( /[^\w-]/g, '' ) + ' button.itsec-toggle-settings' ).trigger( 'itsec-popstate' );
			} else {
				itsecSettingsPage.closeGridSettingsModal( e );
			}

			if ( null !== e.originalEvent.state && 'string' == typeof e.originalEvent.state.module_type && '' !== e.originalEvent.state.module_type.replace( /[^\w-]/g, '' ) ) {
				jQuery( '#itsec-module-filter-' + e.originalEvent.state.module_type.replace( /[^\w-]/g, '' ) + ' a' ).trigger( 'itsec-popstate' );
			}
		});

		var $container = jQuery( '#wpcontent' );

		$container.on( 'click', '.itsec-module-filter a', this.filterView );
		$container.on( 'itsec-popstate', '.itsec-module-filter a', this.filterView );
		$container.on( 'click', '.itsec-settings-view-toggle a', this.toggleView );
//		$container.on( 'click', '.itsec-toggle-settings, .itsec-module-card-content h2', this.toggleSettings );
		$container.on( 'click', 'a[data-module-link]', this.openModuleFromLink );
		$container.on( 'click', '.list .itsec-module-card:not(.itsec-module-pro-upsell) .itsec-module-card-content, .itsec-toggle-settings, .itsec-module-settings-cancel', this.toggleSettings );
		$container.on( 'itsec-popstate', '.list .itsec-module-card-content, .itsec-toggle-settings', this.toggleSettings );
		$container.on( 'click', '.itsec-close-modal, .itsec-modal-background', this.closeGridSettingsModal );
		$container.on( 'keyup', this.closeGridSettingsModal );
		$container.on( 'click', '.itsec-toggle-activation', this.toggleModuleActivation );
		$container.on( 'click', '.itsec-module-settings-save', this.saveSettings );
		$container.on( 'click', '.itsec-reload-module', this.reloadModule );
		$container.on( 'click', '.itsec-details-toggle-container a[href="#"]', this.toggleDetails );

		$container.on( 'change', '#itsec-filter', this.logPageChangeFilter );

		// For use by module content to show/hide settings sections based upon an input.
		$container.on( 'change', '.itsec-settings-toggle', this.toggleModuleContent );
		$container.on( 'click', '.itsec-copy-trigger', this.handleCopy );

		itsecSettingsPage.bindEvents.bound = true;
	},

	toggleDetails: function( e ) {
		e.preventDefault();

		var $details = jQuery(this).parent().find( '.itsec-details-toggle-details' ).toggleClass( 'hide-if-js' );

		if ( $details.hasClass( 'hide-if-js' ) ) {
			jQuery(this).html( itsec_page.translations.show_information );
		} else {
			jQuery(this).html( itsec_page.translations.hide_description );
		}
	},

	logPageChangeFilter: function( e ) {
		var filter = jQuery( this ).val();
		var url = itsec_page.logs_page_url + '&filter=' + filter;
		window.location.href = url;
	},

	toggleModuleContent: function( e ) {
		if ( 'checkbox' === jQuery(this).attr( 'type' ) ) {
			var show = jQuery(this).prop( 'checked' );
		} else {
			var show = ( jQuery(this).val() ) ? true : false;
		}

		var $content = jQuery( '.' + jQuery(this).attr( 'id' ) + '-content' );

		if ( show ) {
			$content.show();


			var $container = jQuery( '.itsec-module-cards-container' );

			if ( $container.hasClass( 'grid' ) ) {
				var $modal = jQuery(this).parents( '.itsec-module-settings-content-container' );
				var scrollOffset = $modal.scrollTop() + jQuery(this).parent().position().top;

				$modal.animate( {'scrollTop': scrollOffset}, 'slow' );
			}
		} else {
			$content.hide();
		}
	},

	handleCopy: function( e ) {

		e.preventDefault();

		var $trigger = jQuery( e.currentTarget );
		var fromId = $trigger.data( 'copy-from' );

		if ( ! fromId.length ) {
			return;
		}

		var el = document.getElementById( fromId );

		var removeSelect = itsecSettingsPage.selectText( el );

		try {

			document.execCommand( 'copy' );
			removeSelect();
			$trigger.text( itsec_page.translations.copied );

		} catch ( e ) {
			var $p = jQuery( '<p></p>' ).text( itsec_page.translations.copy_instruction ),
				$notice = jQuery( '<div class="notice notice-alt notice-info"></div>' ).append( $p ),
				$el = jQuery( el );

			$trigger.after( $notice );

			var removeNotice = function () {
				$notice.fadeOut( function () {
					$notice.remove();
				} );
			};
			var copy = function () {

				setTimeout( function () {
					removeNotice();
					removeSelect();
				}, 100 );

				$el.off( 'copy', copy );

				return true;
			};

			$el.on( 'copy', copy );

			setTimeout( removeNotice, 5000 );
		}
	},

	// https://stackoverflow.com/a/987376
	selectText: function( element ) {
		var doc = document, text = element, range, selection;

		if ( doc.body.createTextRange ) { // ie
			range = document.body.createTextRange();
			range.moveToElementText( text );
			range.select();
		} else if ( window.getSelection ) {
			selection = window.getSelection();
			range = document.createRange();
			range.selectNodeContents( text );
			selection.removeAllRanges();
			selection.addRange( range );
		}

		return function() {
			if ( selection ) {
				selection.removeAllRanges();
			} else {
				range.collapse();
			}
		};
	},

	saveSettings: function( e ) {
		e.preventDefault();

		var $button = jQuery(this);

		if ( $button.hasClass( 'itsec-module-settings-save' ) ) {
			var module = $button.parents( '.itsec-module-card' ).attr( 'id' ).replace( 'itsec-module-card-', '' );
		} else {
			var module = '';
		}

		$button.prop( 'disabled', true );

		var data = {
			'--itsec-form-serialized-data': jQuery( '#itsec-module-settings-form' ).serialize()
		};

		itsecUtil.sendAJAXRequest( module, 'save', data, itsecSettingsPage.saveSettingsCallback );
	},

	saveSettingsCallback: function( results ) {
		if ( '' === results.module ) {
			jQuery( '#itsec-save' ).prop( 'disabled', false );
		} else {
			jQuery( '#itsec-module-card-' + results.module + ' button.itsec-module-settings-save' ).prop( 'disabled', false );
		}

		var $container = jQuery( '.itsec-module-cards-container' );

		if ( $container.hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		itsecSettingsPage.clearMessages();

		if ( results.errors.length > 0 || results.warnings.length > 0 || ! results.closeModal ) {
			itsecSettingsPage.showErrors( results.errors, results.module, 'open' );
			itsecSettingsPage.showErrors( results.warnings, results.module, 'open', 'warning' );
			itsecSettingsPage.showMessages( results.messages, results.module, 'open' );
			itsecSettingsPage.showMessages( results.infos, results.module, 'open', 'info' );

			if ( 'grid' === view ) {
				$container.find( '.itsec-module-settings-content-container:visible' ).animate( {'scrollTop': 0}, 'fast' );
			}

			if ( 'list' === view ) {
				jQuery(document).scrollTop( 0 );
			}
		} else {
			itsecSettingsPage.showMessages( results.messages, results.module, 'closed' );
			itsecSettingsPage.showMessages( results.infos, results.module, 'closed', 'info' );

			if ( 'grid' === view ) {
				$container.find( '.itsec-module-settings-content-container:visible' ).scrollTop( 0 );
				itsecSettingsPage.closeGridSettingsModal();
			}
		}
	},

	clearMessages: function() {
		jQuery( '#itsec-settings-messages-container, .itsec-module-messages-container' ).empty();
	},

	showErrors: function( errors, module, containerStatus, type ) {
		jQuery.each( errors, function( index, error ) {
			itsecSettingsPage.showError( error, module, containerStatus, type );
		} );
	},

	showError: function( error, module, containerStatus, type ) {

		type = type || 'error';

		if ( jQuery( '.itsec-module-cards-container' ).hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		if ( 'closed' !== containerStatus && 'open' !== containerStatus ) {
			containerStatus = 'closed';
		}

		if ( 'string' !== typeof module ) {
			module = '';
		}


		if ( 'closed' === containerStatus || '' === module ) {
			var container = jQuery( '#itsec-settings-messages-container' );

			if ( '' === module ) {
				container.addClass( 'no-module' );
			}
		} else {
			var container = jQuery( '#itsec-module-card-' + module + ' .itsec-module-messages-container' );
		}

		var $notice = jQuery( '<div class="notice"><p><strong>' + error + '</strong></p></div>' );
		$notice.addClass( 'notice-' + type );

		if ( containerStatus === 'open' || module.length ) {
			$notice.addClass( 'notice-alt' );
		}

		container.append( $notice ).addClass( 'visible' );
	},

	showMessages: function( messages, module, containerStatus, type ) {
		jQuery.each( messages, function( index, message ) {
			itsecSettingsPage.showMessage( message, module, containerStatus, type );
		} );
	},

	showMessage: function( message, module, containerStatus, type ) {

		type = type || 'success';

		if ( jQuery( '.itsec-module-cards-container' ).hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		if ( 'closed' !== containerStatus && 'open' !== containerStatus ) {
			containerStatus = 'closed';
		}

		if ( 'string' !== typeof module ) {
			module = '';
		}


		if ( 'closed' === containerStatus || '' === module ) {
			var container = jQuery( '#itsec-settings-messages-container' );

			var dismiss = function () {

				if ( container.is( ':hover' ) ) {
					return setTimeout( dismiss, 2000 );
				}

				container.removeClass( 'visible' );
				setTimeout( function () {
					container.find( 'div' ).remove();
				}, 500 );
			};

			setTimeout( dismiss, 4000 );
		} else {
			var container = jQuery( '#itsec-module-card-' + module + ' .itsec-module-messages-container' );
		}

		var $notice = jQuery( '<div class="notice fade"><p><strong>' + message + '</strong></p></div>' );
		$notice.addClass( 'notice-' + type );

		if ( containerStatus === 'open' || module.length ) {
			$notice.addClass( 'notice-alt' );
		}

		container.append( $notice ).addClass( 'visible' );
	},

	filterView: function( e ) {
		e.preventDefault();

		var $activeLink = jQuery(this),
			$oldLink = $activeLink.parents( '.itsec-feature-tabs' ).find( '.current' ),
			type = $activeLink.parent().attr( 'id' ).substr( 20 );

		$oldLink.removeClass( 'current' );
		$activeLink.addClass( 'current' );

		if ( 'all' === type ) {
			jQuery( '.itsec-module-card' ).show();
		} else {
			jQuery( '.itsec-module-type-' + type ).show();
			jQuery( '.itsec-module-card' ).not( '.itsec-module-type-' + type ).hide();
		}

		// We use this to avoid pushing a new state when we're trying to handle a popstate
		if ( 'itsec-popstate' !== e.type ) {
			var url = '?page=itsec&module_type=' + type;
			var module = itsecUtil.getUrlParameter( 'module' );
			if ( 'string' === typeof module ) {
				url += '&module=' + module;
			}

			window.history.pushState( {'module_type':type}, type, url );
		}
	},

	toggleView: function( e ) {
		e.preventDefault();

		var $self = jQuery(this);

		if ( $self.hasClass( 'itsec-selected' ) ) {
			// Do nothing if already selected.
			return;
		}

		var $view = $self.attr( 'class' ).replace( 'itsec-', '' );

		$self.addClass( 'itsec-selected' ).siblings().removeClass( 'itsec-selected' );
		jQuery( '.itsec-module-settings-container' ).hide();

		jQuery( '.itsec-toggle-settings' ).each(function( index ) {
			var $button = jQuery( this );

			if ( $button.parents( '.itsec-module-card' ).hasClass( 'itsec-module-type-enabled' ) && ! $button.hasClass( 'information-only' ) ) {
				$button.html( itsec_page.translations.show_settings );
			} else if ( $button.hasClass( 'information-only' ) ) {
				$button.html( itsec_page.translations.information_only );
			} else {
				$button.html( itsec_page.translations.show_description );
			}
		});

		var $cardContainer = jQuery( '.itsec-module-cards-container' );
		jQuery.post( ajaxurl, {
			'action':                   'itsec-set-user-setting',
			'itsec-user-setting-nonce': $self.parent().data( 'nonce' ),
			'setting':                  'itsec-settings-view',
			'value':                    $view
		} );

		$cardContainer.fadeOut( 100, function() {
			$cardContainer.removeClass( 'grid list' ).addClass( $view );
		} );
		$cardContainer.fadeIn( 100 );
	},

	openModuleFromLink: function( e ) {

		var $link = jQuery( this ), module = $link.data( 'module-link' ),
			$module = jQuery( '.itsec-module-card[data-module-id="' + module + '"]' ),
			highlight = $link.data( 'highlight-setting-id' );

		if ( ! $module.length ) {
			return; // safety check
		}

		e.preventDefault();

		jQuery( '.itsec-module-settings-container:visible' ).hide();

		var $listClassElement = $module.parents( '.itsec-module-cards-container' ),
			$toggleButton = $module.find( '.itsec-toggle-settings' );

		if ( highlight && highlight.length ) {
			jQuery( 'label[for="' + highlight + '"]', $module ).parents( 'tr' ).addClass( 'itsec-highlighted-setting' );
		}

		if ( $listClassElement.hasClass( 'list' ) ) {
			itsecSettingsPage.toggleListSettingsCard.call( $toggleButton, e );
		} else if ( $listClassElement.hasClass( 'grid' ) ) {
			itsecSettingsPage.showGridSettingsModal.call( $toggleButton, e );
		}

		var type = $module.hasClass( 'itsec-module-type-advanced' ) ? 'advanced' : 'recommended';

		window.history.pushState( {module: module}, module, '?page=itsec&module=' + module + '&module_type=' + type );

		var href = $link.attr( 'href' );

		if ( href && href.length > 1 && href.charAt( 0 ) === '#' ) {
			setTimeout( function () {
				jQuery( '.itsec-module-settings-content-container', '#itsec-module-card-notification-center' ).scrollTo( jQuery( href ), 'swing', { offset: -30 } );
			}, 350 );
		}
	},

	toggleSettings: function( e ) {
		e.stopPropagation();

		var $listClassElement = jQuery(e.currentTarget).parents( '.itsec-module-cards-container' );

		if ( $listClassElement.hasClass( 'list') ) {
			itsecSettingsPage.toggleListSettingsCard.call( this, e );
		} else if ( $listClassElement.hasClass( 'grid' ) ) {
			itsecSettingsPage.showGridSettingsModal.call( this, e );
		}

		// We use this to avoid pushing a new state when we're trying to handle a popstate
		if ( 'itsec-popstate' !== e.type ) {
			var module_id = jQuery(this).closest('.itsec-module-card').data( 'module-id' );

			var module_type = itsecUtil.getUrlParameter( 'module_type' );
			if ( false === module_type || 0 === jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) ).length ) {
				module_type = 'recommended';
			}
			window.history.pushState( {'module':module_id}, module_id, '?page=itsec&module=' + module_id + '&module_type=' + module_type );
		}
	},

	toggleListSettingsCard: function( e ) {
		e.preventDefault();

		var $container = jQuery(this);

		if ( ! $container.hasClass( 'itsec-module-card-content' ) ) {
			$container = $container.parents( '.itsec-module-card' ).find( '.itsec-module-card-content' );
		}

		var $settings = $container.siblings( '.itsec-module-settings-container' ),
			isVisible = $settings.is( ':visible' );
		$settings.stop().slideToggle( 300 );

		if ( ! isVisible ) {
			var $highlighted = jQuery( '.itsec-highlighted-setting', $settings );

			if ( $highlighted.length ) {
				setTimeout( function () {
					jQuery.scrollTo( $highlighted.first(), 'swing', {
						offset: { top: -30 },
						onAfter: function() {
							var $el = jQuery( 'input[type!="button"], textarea, select', $highlighted ).not( ':hidden' ).first();
							itsecUtil.focus( $el, $highlighted );
						}
					} );
				}, 50 );
			} else {
				var $el = jQuery( 'input[type!="button"], textarea, select', $settings ).not( ':hidden' ).first();
				itsecUtil.focus( $el, $settings );
			}
		}

		var $button = $container.find( '.itsec-toggle-settings' );

		if ( $container.parent().hasClass( 'itsec-module-type-enabled' ) ) {
			if ( $button.html() == itsec_page.translations.show_settings ) {
				$button.html( itsec_page.translations.hide_settings );
			} else {
				$button.html( itsec_page.translations.show_settings );
			}
		} else {
			if ( $button.hasClass( 'information-only' ) ) {
				if ( $button.html() == itsec_page.translations.show_information ) {
					$button.html( itsec_page.translations.hide_description );
				} else {
					$button.html( itsec_page.translations.show_information );
				}
			} else {
				if ( $button.html() == itsec_page.translations.show_description ) {
					$button.html( itsec_page.translations.hide_description );
				} else {
					$button.html( itsec_page.translations.show_description );
				}
			}
		}
	},

	showGridSettingsModal: function( e ) {
		e.preventDefault();

		var $module = jQuery(this).parents( '.itsec-module-card' ),
			$settingsContainer = $module.find( '.itsec-module-settings-container' ),
			$modalBackground = jQuery( '.itsec-modal-background' );

		$module.show();

		$modalBackground.fadeIn();
		$settingsContainer.fadeIn( 200 );

		jQuery( 'body' ).addClass( 'itsec-modal-open' );

		var $highlighted = jQuery( '.itsec-highlighted-setting', $module ).first();

		if ( $highlighted.length ) {
			jQuery( '.itsec-module-settings-content-container', $module ).scrollTo( $highlighted, 'swing', {
				offset: { top: -20 },
				onAfter: function() {
					var $el = jQuery( 'input[type!="button"], textarea, select', $highlighted ).not( ':hidden' ).first();
					itsecUtil.focus( $el, $highlighted );
				}
			} );
		} else {
			var $el = jQuery( 'input[type!="button"], textarea, select', $settingsContainer ).not( ':hidden' ).first();
			itsecUtil.focus( $el, $settingsContainer );
		}
	},

	closeGridSettingsModal: function( e ) {
		if ( 'undefined' !== typeof e ) {
			e.preventDefault();

			// For keyup events, only process esc
			if ( 'keyup' === e.type && 27 !== e.which ) {
				return;
			}
		}

		jQuery( '.itsec-modal-background' ).fadeOut();
		jQuery( '.itsec-module-settings-container' ).fadeOut( 200 );
		jQuery( 'body' ).removeClass( 'itsec-modal-open' );

		if ( 'undefined' === typeof e || 'popstate' !== e.type ) {
			var module_type = itsecUtil.getUrlParameter( 'module_type' );
			if ( false === module_type || 0 === jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) ).length ) {
				module_type = 'recommended';
			}
			window.history.pushState( {'module':'', 'module_type':module_type}, module_type, '?page=itsec&module_type=' + module_type );
		}

		if ( jQuery( '#search' ).val().length ) {
			jQuery( '#search' ).focus();
		}
	},

	toggleModuleActivation: function( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $button = jQuery(this),
			$card = $button.parents( '.itsec-module-card' ),
			$buttons = $card.find( '.itsec-toggle-activation' ),
			module = $card.attr( 'id' ).replace( 'itsec-module-card-', '' );

		$buttons.prop( 'disabled', true );

		if ( $button.html() == itsec_page.translations.activate ) {
			var method = 'activate';
		} else {
			var method = 'deactivate';
		}

		itsecUtil.sendAJAXRequest( module, method, {}, itsecSettingsPage.toggleModuleActivationCallback );
	},

	setModuleToActive: function( module ) {
		var args = {
			'module': module,
			'method': 'activate',
			'errors': []
		};

		itsecSettingsPage.toggleModuleActivationCallback( args );
	},

	setModuleToInactive: function( module ) {
		var args = {
			'module': module,
			'method': 'deactivate',
			'errors': []
		};

		itsecSettingsPage.toggleModuleActivationCallback( args );
	},

	toggleModuleActivationCallback: function( results ) {
		var module = results.module;
		var method = results.method;

		var $card = jQuery( '#itsec-module-card-' + module ),
			$buttons = $card.find( '.itsec-toggle-activation' )

		if ( results.errors.length > 0 ) {
			$buttons
				.html( itsec_page.translations.error )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );

			setTimeout( function() {
				itsecSettingsPage.isModuleActive( module );
			}, 1000 );

			itsecSettingsPage.showErrors( results.errors, results.module, 'closed', 'error' );

			return;
		}

		if ( 'activate' === method ) {
			$buttons
				.html( itsec_page.translations.deactivate )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', false );

			$card
				.addClass( 'itsec-module-type-enabled' )
				.removeClass( 'itsec-module-type-disabled' );

			var newToggleSettingsLabel = itsec_page.translations.show_settings;
		} else {
			$buttons
				.html( itsec_page.translations.activate )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );

			$card
				.addClass( 'itsec-module-type-disabled' )
				.removeClass( 'itsec-module-type-enabled' );

			var newToggleSettingsLabel = itsec_page.translations.show_description;
		}

		$card.find( '.itsec-toggle-settings' ).html( newToggleSettingsLabel );

		var enabledCount = jQuery( '.itsec-module-type-enabled' ).length,
			disabledCount = jQuery( '.itsec-module-type-disabled' ).length;

		jQuery( '#itsec-module-filter-enabled .count' ).html( '(' + enabledCount + ')' );
		jQuery( '#itsec-module-filter-disabled .count' ).html( '(' + disabledCount + ')' );


		itsecSettingsPage.showErrors( results.warnings, results.module, 'closed', 'warning' );
		itsecSettingsPage.showMessages( results.messages, results.module, 'closed' );
		itsecSettingsPage.showMessages( results.infos, results.module, 'closed', 'info' );
	},

	isModuleActive: function( module ) {
		var data = {
			'module': module,
			'method': 'is_active'
		};

		itsecUtil.sendAJAXRequest( module, 'is_active', {}, itsecSettingsPage.isModuleActiveCallback );
	},

	isModuleActiveCallback: function( results ) {
		if ( true === results.response ) {
			results.method = 'activate';
		} else if ( false === results.response ) {
			results.method = 'deactivate';
		} else {
			return;
		}

		itsecSettingsPage.toggleModuleActivationCallback( results );
	},

	reloadModule: function( module ) {
		if ( module.preventDefault ) {
			module.preventDefault();

			module = jQuery(this).parents( '.itsec-module-card' ).attr( 'id' ).replace( 'itsec-module-card-', '' );
		}

		var method = 'get_refreshed_module_settings';
		var data = {};

		itsecUtil.sendAJAXRequest( module, method, data, function( results ) {
			if ( results.success && results.response ) {
				var $card = jQuery( '#itsec-module-card-' + module );
				var isHidden = $card.is( ':hidden' );

				jQuery( '.itsec-module-settings-content-main', $card ).html( results.response );

				if ( isHidden ) {
					$card.hide();
				} else {
					jQuery( '.itsec-settings-toggle' ).trigger( 'change' );
				}
			} else if ( results.errors && results.errors.length > 0 ) {
				itsecSettingsPage.showErrors( results.errors, results.module, 'open' );
			} else if ( results.warnings && results.warnings.length > 0 ) {
				itsecSettingsPage.showErrors( results.warnings, results.module, 'open', 'warning' );
			}

			itsecSettingsPage.events.trigger( 'moduleReloaded', module );

			itsecSettingsPage.makeNoticesDismissible();
		} );
	},

	reloadAllModules: function( _, initialResponse) {
		itsecUtil.sendAJAXRequest( '#', 'get_refreshed_module_form', null, function ( response ) {

			if ( ! response.success || response.errors.length ) {
				return;
			}

			var $open;

			if ( jQuery( 'body' ).hasClass( 'itsec-modal-open' ) ) {
				var $newModules = jQuery( response.response ), $cardsList = jQuery( '.itsec-module-cards' );
				$open = jQuery( '.itsec-module-settings-container:visible' ).parents( '.itsec-module-card' );

				jQuery( '.itsec-module-card', $newModules ).each( function () {
					var $new = jQuery( this ), $current = jQuery( '#' + $new.attr( 'id' ), $cardsList );

					if ( $new.attr( 'id' ).length && $new.attr( 'id' ) === $open.attr( 'id' ) ) {
						jQuery( '.itsec-module-settings-content-main', $current ).html( jQuery( '.itsec-module-settings-content-main', $new ).html() );
					} else {
						jQuery( '.itsec-module-settings-container', $new ).hide();
						$current.replaceWith( $new );
					}
				} );

			} else {
				jQuery( '.itsec-module-cards-container' ).html( response.response );
			}

			itsecSettingsPage.initFilters();

			if ( ! $open ) {
				jQuery( '.itsec-module-settings-container' ).hide();
			}

			if ( initialResponse ) {
				itsecSettingsPage.showMessages( initialResponse.messages, initialResponse.module, $open ? 'open' : 'closed' );
				itsecSettingsPage.showMessages( initialResponse.infos, initialResponse.module, $open ? 'open' : 'closed', 'info' );
				itsecSettingsPage.showErrors( initialResponse.errors, initialResponse.module, $open ? 'open' : 'closed' );
				itsecSettingsPage.showErrors( initialResponse.warnings, initialResponse.module, $open ? 'open' : 'closed', 'warning' );
			}

			itsecSettingsPage.makeNoticesDismissible();
			itsecSettingsPage.events.trigger( 'modulesReloaded', initialResponse );
		} );
	},

	reloadWidget: function( widget ) {
		var method = 'get_refreshed_widget_settings';
		var data = {};

		itsecUtil.sendAJAXRequest( module, method, data, function( results ) {
			if ( results.success && results.response ) {
				jQuery( '#itsec-sidebar-widget-' + module + ' .inside' ).html( results.response );
			} else {
				itsecSettingsPage.showErrors( results.errors, results.module, 'closed' );
				itsecSettingsPage.showErrors( results.warnings, results.module, 'closed', 'warning' );
			}
		} );
	},

	// Make notices dismissible
	makeNoticesDismissible: function() {
		jQuery( '.notice.itsec-is-dismissible' ).each( function() {
			var $el = jQuery( this ),
				$button = jQuery( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
				btnText = itsec_page.translations.dismiss || '';

			// Don't rebind twice
			if ( jQuery( '.notice-dismiss', $el ).length ) {
				return;
			}

			// Ensure plain text
			$button.find( '.screen-reader-text' ).text( btnText );
			$button.on( 'click.wp-dismiss-notice', function( event ) {
				event.preventDefault();

				$el.trigger( 'itsec-dismiss-notice' );

				$el.fadeTo( 100, 0, function() {
					$el.slideUp( 100, function() {
						$el.remove();
					});
				});
			});

			$el.append( $button );
		});
	},

	refreshPage: function() {
		location.reload( true );
	}
};

jQuery(document).ready(function( $ ) {
	itsecSettingsPage.init();

	if ( itsec_page.show_security_check ) {
		jQuery( '.itsec-settings-view-toggle a.itsec-grid' ).trigger( 'click' );
		jQuery( '#itsec-module-card-security-check .itsec-toggle-settings' ).trigger( 'click' );
	}


	jQuery( '.dialog' ).click( function ( event ) {
		event.preventDefault();

		var target = jQuery( this ).attr( 'href' );
		var title = jQuery( this ).parents( '.inside' ).siblings( 'h3.hndle' ).children( 'span' ).text();

		jQuery( '#' + target ).dialog( {
			dialogClass  : 'wp-dialog itsec-dialog itsec-dialog-logs',
			modal        : true,
			closeOnEscape: true,
			title        : title,
			height       : ( jQuery( window ).height() * 0.8 ),
			width        : ( jQuery( window ).width() * 0.8 ),
			open         : function ( event, ui ) {
				jQuery( '.ui-widget-overlay' ).bind( 'click', function () {
					jQuery( this ).siblings( '.ui-dialog' ).find( '.ui-dialog-content' ).dialog( 'close' );
				} );
			}
		} );

		jQuery( '.ui-dialog :button' ).blur();
	} );

	var regex = /[^\w]/ig;

	var $search = $( '#search' ), $cardsContainer = $( '.itsec-module-cards' ),
		$cards = $( '.itsec-module-card', $cardsContainer ),
		$searchFilter = $( '#itsec-module-filter-search' ),
		$currentFilter = $( '.itsec-feature-tabs .current' ).parent();

	itsecSettingsPage.events.on( 'modulesReloaded', function() {
		$cardsContainer = $( '.itsec-module-cards' );
		$cards = $( '.itsec-module-card', $cardsContainer );
	} );

	$search.on( 'input', _.debounce( function () {
		var query = $search.val().trim().replace( regex, ' ' );

		var $maybeCurrent = $( '.itsec-feature-tabs .current' ).parent();

		if ( $maybeCurrent && $maybeCurrent.prop( 'id' ) !== 'itsec-module-filter-search' ) {
			$currentFilter = $maybeCurrent;
		}

		$( '.itsec-highlighted-setting', $cards ).removeClass( 'itsec-highlighted-setting' );

		if ( !query.length ) {
			$searchFilter.addClass( 'hide-if-js' );
			$( 'a', $searchFilter ).removeClass( 'current' );
			$( 'a', $currentFilter ).addClass( 'current' );

			var type = $currentFilter.prop( 'id' ).substr( 20 );

			if ( 'all' === type ) {
				$cards.show();
			} else {
				$( '.itsec-module-type-' + type ).show();
				$( '.itsec-module-card' ).not( '.itsec-module-type-' + type ).hide();
			}

			return;
		}

		var $titleMatches = $( ".itsec-module-card-content > h2:itsecContains('" + query + "')", $cards ),
			$titleMatchesCards = $titleMatches.parents( '.itsec-module-card' );

		var $descriptionMatches = $( ".itsec-module-card-content > p:itsecContains('" + query + "')", $cards ),
			$descriptionMatchesCards = $descriptionMatches.parents( '.itsec-module-card' );

		var $settingMatches = $( ".itsec-module-settings-container .form-table tr > th > label:itsecContains('" + query + "')", $cards ),
			$settingMatchesCards = $settingMatches.parents( '.itsec-module-card' );


		var $matches = $titleMatchesCards.add( $descriptionMatchesCards ).add( $settingMatchesCards );

		$searchFilter.removeClass( 'hide-if-js' );
		$( 'a', $currentFilter ).removeClass( 'current' );
		$( 'a', $searchFilter ).addClass( 'current' );
		$( '.count', $searchFilter ).text( '(' + $matches.length + ')' );

		$cards.hide();
		$matches.show();

		$settingMatches.parents( 'tr' ).addClass( 'itsec-highlighted-setting' );

		if ( $matches.length === 1 ) {
			$( '.itsec-toggle-settings', $matches.first() ).click();
		}
	}, 250 ) );

	$.expr[":"].itsecContains = $.expr.createPseudo( function ( arg ) {
		return function ( elem ) {
			var candidate = $( elem ).text().toUpperCase().replace( regex, ' ' ), term = arg.toUpperCase();
			var index = candidate.indexOf( term );

			if ( index === -1 ) {
				return false;
			}

			if ( index === 0 ) {
				return true;
			}

			var prior = candidate.charAt( index - 1 ), next = candidate.charAt( term.length + index );

			// full word
			return prior === ' ' && ( next === ' ' || next === '' );
		};
	} );
});
