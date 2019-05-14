/**
 *
 * @param {jQuery} $
 * @param Backbone
 * @param _
 * @param wp
 * @param {Object} config
 * @param {ITSECLoginInterstitial} interstitial
 */
(function ( $, Backbone, _, wp, config, interstitial ) {

	var App = wp.Backbone.View.extend( {
		template: wp.template( 'itsec-app' ),

		initialize: function ( options ) {
			this.screens = options.screens || {};
			this.form = options.form;
			this._screenCache = {};

			this.listenTo( this.model, 'change:screen', this.changeScreen );
			this.listenTo( this.model, 'change:complete', this.complete );
			this.listenTo( this.model, 'change:submit', this.submit );
			this.changeScreen();
		},

		changeScreen: function () {

			var screen = this.model.get( 'screen' ),
				screenConstructor = this.screens[screen],
				view;

			this.view && this.view.onLeave && this.view.onLeave();

			if ( typeof this._screenCache[screen] !== 'undefined' ) {
				view = this._screenCache[screen];
				view._ensureElement();
				view.initialize( view.options );
			} else if ( screenConstructor ) {
				view = screenConstructor( { model: this.model } );
			} else {
				view = false;
			}

			this.view = view;

			this.views.set( '.itsec-screen-container', view );
			this.render();
		},

		complete: function ( complete ) {
			if ( complete ) {
				this.model.set( 'screen', 'summary' );
			}
		},

		submit: function ( submit ) {
			if ( submit ) {
				if ( !$( '#itsec_two_factor_on_board_data' ).length ) {
					this.form.append(
						$( '<input>' )
							.prop( 'id', 'itsec_two_factor_on_board_data' )
							.prop( 'name', 'itsec_two_factor_on_board_data' )
							.prop( 'type', 'hidden' )
							.prop( 'value', JSON.stringify( this.collection.toJSON() ) )
					);
				}

				this.form.submit();
			}
		},
	} );

	var ScreenIntro = wp.Backbone.View.extend( {
		template : wp.template( 'itsec-screen-intro' ),
		className: 'itsec-screen itsec-screen--intro',

		events: {
			'click .itsec-screen__actions--continue': 'onContinue',
		},

		onContinue: function ( e ) {
			e.preventDefault();
			this.model.set( 'screen', 'providers' );
		},

		prepare: function () {
			return {
				c: config,
			}
		},
	} );

	var ScreenProviders = wp.Backbone.View.extend( {
		template : wp.template( 'itsec-screen-providers' ),
		className: 'itsec-screen itsec-screen--providers',

		events: {
			'click .itsec-screen__actions--continue': 'onContinue',
		},

		initialize: function () {
			var views = [];

			this.listenTo( this.collection, 'change:status', this.render );

			this.collection.forEach( (function ( provider ) {
				views.push( new ProviderOptionView( { model: provider, state: this.model } ) );
			}).bind( this ) );

			this.views.add( '.itsec-providers__list', views );
		},

		onContinue: function ( e ) {
			e.preventDefault();

			if ( !this.canComplete() ) {
				return this.showNotCompletedWarning();
			}

			var totp = this.collection.get( 'Two_Factor_Totp' );
			var email = this.collection.get( 'Two_Factor_Email' );
			var backupCodes = this.collection.get( 'Two_Factor_Backup_Codes' );

			this.model.set( 'isCompleting', true );

			if ( backupCodes && backupCodes.get( 'status' ) === 'enabled' && backupCodes.get( 'config' ).codes.length ) {
				var backupCodeState = this.model.get( 'backup-codes' );

				if ( !backupCodeState.copied && !backupCodeState.downloaded && !backupCodeState.displayed ) {
					this.model.set( 'complete', false );
					this.model.set( 'backup-codes', _.extend( {}, backupCodeState, { showWarning: true } ) );
					this.model.set( 'screen', 'Two_Factor_Backup_Codes' );

					return;
				}
			}

			if ( email && email.get( 'status' ) === 'enabled' && config.confirm_email ) {
				var emailState = this.model.get( 'email' );

				// We only want the user to go through the confirmation process if they haven't setup their mobile app.
				if ( !emailState.isConfirmed && (!totp || totp.get( 'status' ) === 'disabled') ) {
					this.model.set( 'complete', false );
					this.model.set( 'screen', 'email-confirm' );

					return;
				}
			}

			this.model.set( 'complete', true );
		},

		canComplete: function () {

			if ( config.can_skip ) {
				return true;
			}

			return this.hasConfigured();
		},

		hasConfigured: function () {
			return !!this.collection.find( function ( model ) {
				return model.get( 'status' ) === 'enabled' && model.isConfigured();
			} )
		},

		prepare: function () {

			var disabled = '';

			if ( config.can_skip && !this.hasConfigured() ) {
				disabled = 'disabled';
			} else if ( !this.canComplete() ) {
				disabled = 'disabled';
			}

			return {
				c: config,
				d: {
					disabled: disabled,
				}
			}
		},

		showNotCompletedWarning: function () {

			var $notice = addNotice( config.l10n.require_notice, 'itsec-two-factor-required', 'warning' );
			$notice.on( 'click.continue', 'button', (this.onContinue).bind( this ) );

			return $notice;
		},
	} );

	var ProviderOptionView = wp.Backbone.View.extend( {

		template : wp.template( 'itsec-provider' ),
		className: function () {
			return 'itsec-provider itsec-provider--' + this.model.id;
		},

		events: {
			'click .itsec-provider__action--disable': 'onDisable',
			'click .itsec-provider__action--enable' : 'onEnable',
			'click .itsec-provider__configure'      : 'onConfigure',
		},

		initialize: function ( options ) {
			this.state = options.state;

			this.listenTo( this.model, 'change:status', this.render );
		},

		onDisable: function ( e ) {
			e.preventDefault();

			this.model.set( 'status', 'disabled' );
		},

		onEnable: function ( e ) {
			e.preventDefault();

			this.model.set( 'status', this.model.isConfigured() ? 'enabled' : 'not-configured' );
		},

		onConfigure: function ( e ) {
			e.preventDefault();

			this.model.set( 'status', this.model.isConfigured() ? 'enabled' : 'not-configured' );
			this.state.set( 'screen', this.model.get( 'id' ) );
		},

		prepare: function () {
			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					status_label: config.l10n[this.model.get( 'status' )] || this.model.get( 'status' ),
				},
			}
		}
	} );

	var ViewSummary = wp.Backbone.View.extend( {
		template: wp.template( 'itsec-screen-summary' ),
		events  : {
			'click .itsec-screen__actions--continue': 'onContinue',
		},

		onContinue: function ( e ) {
			e.preventDefault();

			this.model.set( 'submit', true );
		},

		prepare: function () {
			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					summary: this.getSummary(),
				},
			}
		},

		getSummary: function () {

			var labels = [];

			this.collection.forEach( function ( model ) {
				if ( model.get( 'status' ) === 'enabled' ) {
					labels.push( model.get( 'label' ) );
				}
			} );

			return config.l10n.summary.replace( '%l', listItems( labels ) );
		},
	} );

	var ViewEmailConfirm = wp.Backbone.View.extend( {
		template: wp.template( 'itsec-screen-email-confirm' ),

		events: {
			'click #itsec-email__cannot_find'       : 'onCannotFind',
			'click .itsec-screen__actions--continue': 'onContinue',
			'click .itsec-screen__actions--cancel'  : 'onCancel',
			'click .itsec-screen__actions--back'    : 'onBack',
			'keyup #itsec-email__confirm-code'      : 'onTypeCode',
			'keypress'                              : 'onEnter',
		},

		initialize: function ( options ) {
			this.state = options.state;
			this.listenTo( this.state, 'change:email', function ( _, stateSubTree ) {
				if (
					stateSubTree.isConfirming !== this.state.previous( 'email' ).isConfirming ||
					stateSubTree.cannotFind !== this.state.previous( 'email' ).cannotFind
				) {
					this.render();
				}
			} );

			if ( !this.state.get( 'email' ).emailSent ) {
				ajax( { itsec_method: 'send-email-code' } )
			}

			interstitial.setOnStateChange( this.onInterstitialStateChange.bind( this ) );
		},

		onCannotFind: function ( e ) {
			e.preventDefault();

			this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { cannotFind: true } ) );
		},

		onContinue: function ( e ) {
			e.preventDefault();

			this.$notice && this.$notice.remove();

			this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { isConfirming: true } ) );

			ajax( {
				itsec_method    : 'verify-email-code',
				itsec_email_code: this.state.get( 'email' ).code,
			} ).done( (function ( data ) {
				this.model.set( 'status', 'enabled' );
				this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { isConfirmed: true } ) );

				if ( this.state.get( 'isCompleting' ) ) {
					this.state.set( 'complete', true );
				} else {
					this.state.set( 'screen', 'providers' );
				}
			}).bind( this ) ).fail( (function ( data ) {
				this.$notice = addNotice( data.message, 'itsec-verify-email-message', 'error' );
			}).bind( this ) ).always( (function () {
				this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { isConfirming: false } ) );
			}).bind( this ) );
		},

		onCancel: function ( e ) {
			this.model.set( 'status', 'disabled' );
			this.state.set( 'screen', 'providers' );
		},

		onBack: function ( e ) {
			e.preventDefault();

			this.state.set( 'screen', 'providers' );
		},

		onTypeCode: function ( e ) {
			this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { code: e.target.value } ) );

			if ( e.target.value && e.target.value.length > 0 ) {
				this.$( '.itsec-screen__actions--continue' ).prop( 'disabled', false );
			} else {
				this.$( '.itsec-screen__actions--continue' ).prop( 'disabled', true );
			}
		},

		onEnter: function ( e ) {
			if ( e.which === 13 ) {
				this.onContinue( e );
			}
		},

		onLeave: function () {
			this.$notice && this.$notice.remove();
		},

		onInterstitialStateChange: function( newState, prevState ) {
			if ( newState.email_verified && ! prevState.email_verified ) {
				this.$notice && this.$notice.remove();

				this.model.set( 'status', 'enabled' );
				this.state.set( 'email', _.extend( {}, this.state.get( 'email' ), { isConfirming: false, isConfirmed: true } ) );

				if ( this.state.get( 'isCompleting' ) ) {
					this.state.set( 'complete', true );
				} else {
					this.state.set( 'screen', 'providers' );
				}
			}
		},

		prepare: function () {
			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					disabled  : this.state.get( 'email' ).isConfirming || this.state.get( 'email' ).code.length === 0 ? 'disabled' : '',
					code      : this.state.get( 'email' ).code,
					cannotFind: this.state.get( 'email' ).cannotFind,
				},
			}
		},
	} );

	var ViewTotp = wp.Backbone.View.extend( {
		template : wp.template( 'itsec-screen-provider-totp' ),
		className: 'itsec-screen itsec-screen--totp',

		events: {
			'click .itsec-totp__view-secret'            : 'onViewSecret',
			'click .itsec-screen__actions--continue'    : 'onContinue',
			'click .itsec-screen__actions--cancel'      : 'onCancel',
			'click .itsec-screen__actions--back'        : 'onBack',
			'click .itsec-totp__device-switcher .button': 'onDeviceToggle',
		},

		initialize: function ( options ) {
			this.state = options.state;

			this.listenTo( this.state, 'change:totp', this.render );
		},

		onViewSecret: function ( e ) {
			e.preventDefault();

			this.state.set( 'totp', _.extend( {}, this.state.get( 'totp' ), { show_secret: true } ) );
		},

		onContinue: function ( e ) {
			e.preventDefault();

			this.state.set( 'screen', 'totp-confirm' );
		},

		onCancel: function ( e ) {
			e.preventDefault();

			this.model.set( 'status', 'disabled' );
			this.state.set( 'screen', 'providers' );
		},

		onBack: function ( e ) {
			e.preventDefault();

			this.state.set( 'screen', 'providers' );
		},

		onDeviceToggle: function ( e ) {
			e.preventDefault();

			var type;

			if ( $( e.target ).hasClass( 'itsec-totp__device-switcher-button--ios' ) ) {
				type = 'ios';
			} else {
				type = 'android';
			}

			this.state.set( 'totp', _.extend( {}, this.state.get( 'totp' ), { device: type } ) );
		},

		prepare: function () {
			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					show_secret: this.state.get( 'totp' ).show_secret,
					device     : this.state.get( 'totp' ).device,
				}
			}
		}
	} );

	var ViewTotpConfirm = wp.Backbone.View.extend( {
		template: wp.template( 'itsec-screen-totp-confirm' ),

		events: {
			'click .itsec-screen__actions--continue': 'onContinue',
			'click .itsec-screen__actions--cancel'  : 'onCancel',
			'click .itsec-screen__actions--back'    : 'onBack',
			'keyup #itsec-totp__confirm-code'       : 'onTypeCode',
			'keypress'                              : 'onEnter',
		},

		initialize: function ( options ) {
			this.state = options.state;
			this.listenTo( this.state, 'change:totp', function ( _, stateSubTree ) {
				if ( stateSubTree.isConfirming !== this.state.previous( 'totp' ).isConfirming ) {
					this.render();
				}
			} );
		},

		onContinue: function ( e ) {
			e.preventDefault();

			this.$notice && this.$notice.remove();

			this.state.set( 'totp', _.extend( {}, this.state.get( 'totp' ), { isConfirming: true } ) );

			ajax( {
				itsec_method     : 'verify-totp-code',
				itsec_totp_code  : this.state.get( 'totp' ).code,
				itsec_totp_secret: this.model.get( 'config' ).secret,
			} ).done( (function ( data ) {
				this.model.set( 'status', 'enabled' );
				this.state.set( 'screen', 'providers' );
			}).bind( this ) ).fail( (function ( data ) {
				this.$notice = addNotice( data.message, 'itsec-verify-code-message', 'error' );
			}).bind( this ) ).always( (function () {
				this.state.set( 'totp', _.extend( {}, this.state.get( 'totp' ), { isConfirming: false } ) );
			}).bind( this ) );
		},

		onCancel: function ( e ) {
			this.model.set( 'status', 'disabled' );
			this.state.set( 'screen', 'providers' );
		},

		onBack: function ( e ) {
			e.preventDefault();

			this.state.set( 'screen', 'providers' );
		},

		onTypeCode: function ( e ) {
			this.state.set( 'totp', _.extend( {}, this.state.get( 'totp' ), { code: e.target.value } ) );
		},

		onEnter: function ( e ) {
			if ( e.which === 13 ) {
				this.onContinue( e );
			}
		},

		onLeave: function () {
			this.$notice && this.$notice.remove();
		},

		prepare: function () {
			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					disabled: this.state.get( 'totp' ).isConfirming ? 'disabled' : '',
					code    : this.state.get( 'totp' ).code,
				},
			}
		},
	} );

	var ViewBackupCodes = wp.Backbone.View.extend( {
		template : wp.template( 'itsec-screen-backup-codes' ),
		className: 'itsec-screen itsec-screen--backup-codes',

		events: {
			'click .itsec-screen__actions--continue'   : 'onContinue',
			'click .itsec-screen__actions--cancel'     : 'onCancel',
			'click .itsec-screen__actions--back'       : 'onBack',
			'copy'                                     : 'onCopy',
			'click .itsec-screen__actions--download'   : 'onDownload',
			'click .itsec-backup-codes__generate-codes': 'onGenerate',
		},

		initialize: function ( options ) {
			this.state = options.state;

			this.listenTo( this.state, 'change:backup-codes', this.maybeShowWarning );
			this.listenTo( this.state, 'change:backup-codes', this.render );

			if ( this.state.get( 'backup-codes' ).showWarning ) {
				this.showWarning();
			}
		},

		onContinue: function ( e ) {
			e.preventDefault();

			var state = this.state.get( 'backup-codes' );

			if ( !state.copied && !state.downloaded && !state.displayed && this.model.get( 'config' ).codes.length ) {
				this.showWarning();

				return;
			}

			if ( this.state.get( 'isCompleting' ) ) {
				this.state.set( 'complete', true );
			} else {
				this.model.set( 'status', 'enabled' );
				this.state.set( 'screen', 'providers' );
			}
		},

		onCancel: function ( e ) {
			this.model.set( 'status', 'disabled' );
			this.state.set( 'screen', 'providers' );
		},

		onBack: function ( e ) {
			e.preventDefault();

			this.state.set( 'screen', 'providers' );
		},

		onCopy: function () {
			this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { copied: true } ) );
		},

		onDownload: function () {
			this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { downloaded: true } ) );
		},

		onGenerate: function ( e ) {
			e.preventDefault();

			this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { isGenerating: true } ) );

			ajax( { itsec_method: 'generate-backup-codes' } ).done( (function ( data ) {
				this.model.set( 'config', _.extend( {}, this.model.get( 'config' ), {
					codes     : data.codes,
					code_count: data.code_count,
				} ) );
			}).bind( this ) ).fail( (function ( data ) {
				this.$notice = addNotice( data.message, 'itsec-generate-codes-message', 'error' );
			}).bind( this ) ).always( (function () {
				this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { isGenerating: false } ) );
			}).bind( this ) );
		},

		onLeave: function () {
			this.$notice && this.$notice.remove();
			this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { warningVisible: false } ) );
		},

		maybeShowWarning: function ( stateSubTree ) {
			var previous = this.state.previous( 'backup-codes' );

			if ( stateSubTree.showWarning && !previous.showWarning ) {
				this.showWarning();
				this.state.set( _.extend( {}, stateSubTree, { showWarning: false } ) );
			}
		},

		showWarning: function () {

			this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { displayed: true, showWarning: false, warningVisible: true } ) );

			var id = 'itsec-backup-codes__warning-message';
			var $notice = addNotice( config.l10n.backup_codes_warning, id, 'warning' );
			this.$notice = $notice.on( 'click.continue', 'button', (function ( e ) {
				e.preventDefault();
				this.$notice && this.$notice.remove();
				this.state.set( 'backup-codes', _.extend( {}, this.state.get( 'backup-codes' ), { warningVisible: false } ) );
			}).bind( this ) );
		},

		prepare: function () {

			var newlineCodes = '',
				codes = this.model.get( 'config' ).codes;

			for ( var i = 0; i < codes.length; i++ ) {
				newlineCodes += codes[i] + '\n';
			}

			var state = this.state.get( 'backup-codes' );

			return {
				c: config,
				m: this.model.toJSON(),
				d: {
					newlineCodes    : encodeURIComponent( newlineCodes ),
					enabled         : typeof document.createElement( 'a' ).download !== 'undefined',
					generateDisabled: state.isGenerating ? 'disabled' : '',
					continueDisabled: (state.isGenerating || (state.warningVisible && !state.copied && !state.downloaded)) ? 'disabled' : '',
				},
			}
		},

		render: function () {
			wp.Backbone.View.prototype.render.apply( this );

			if ( !this.model.get( 'config' ).codes.length ) {
				this.$( 'p' ).html( this.$( 'p' ).html().replace( '%d', this.model.get( 'config' ).code_count ) );
			}
		},
	} );

	var State = Backbone.Model.extend( {
		defaults: {
			screen        : 'intro',
			isCompleting  : false,
			complete      : false,
			submit        : false,
			totp          : {
				show_secret : false,
				isConfirming: false,
				code        : '',
				device      : 'ios',
			},
			email         : {
				code        : '',
				emailSent   : false,
				cannotFind  : false,
				isConfirmed : false,
				isConfirming: false,
			},
			'backup-codes': {
				copied        : false,
				downloaded    : false,
				displayed     : false,
				warningVisible: false,
				showWarning   : false,
				isGenerating  : false,
			},
		},
	} );

	var Provider = Backbone.Model.extend( {
		isConfigured: function () {
			return true;
		}
	} );

	var ProviderTotp = Provider.extend( {
		isConfigured: function () {
			return this.get( 'config.code_confirmed' );
		}
	} );

	var ProviderBackupCodes = Provider.extend( {
		isConfigured: function () {
			return this.get( 'config' ).code_count > 0;
		}
	} );

	var Providers = Backbone.Collection.extend( {
		model: function ( data ) {
			switch ( data.id ) {
				case 'Two_Factor_Totp':
					return new ProviderTotp( data );
				case 'Two_Factor_Backup_Codes':
					return new ProviderBackupCodes( data );
				default:
					return new Provider( data );
			}
		},
	} );

	/**
	 * Perform an ajax request to the interstitial.
	 *
	 * @param {Object} data
	 * @returns {*|$.promise}
	 */
	function ajax( data ) {
		return interstitial.ajax( data );
	}

	/**
	 * Add a notice.
	 *
	 * @param {String} message
	 * @param {String} id
	 * @param {String} [type] one of 'info', 'warning', 'error' or 'success'. Defaults to 'info'.
	 * @returns {*|HTMLElement}
	 */
	function addNotice( message, id, type ) {

		var exists = $( '#' + id );

		if ( exists.length ) {
			return exists;
		}

		type = type || 'info';

		var $notice = $( '<p>', { html: message, class: 'itsec-notice itsec-notice-' + type, id: id } );

		$( '#itsec-2fa-on-board' ).before( $notice );

		if ( type === 'info' ) {
			wp.a11y.speak( message, 'polite' );
		} else {
			wp.a11y.speak( message, 'assertive' );
		}

		return $notice;
	}

	/**
	 * Convert an array of items to a list.
	 *
	 * @param {Array<String>} items
	 *
	 * @return {String}
	 */
	function listItems( items ) {

		var out = items.shift();

		if ( items.length === 1 ) {
			out += config.list.between_only_two + items.shift();
		}

		var length = items.length;

		while ( length ) {
			var item = items.shift();
			length--;

			if ( length === 0 ) {
				out += config.list.between_last_two + item;
			} else {
				out += config.list.between + item;
			}
		}

		return out;
	}

	function onBoard() {
		var state = new State();
		var providers = new Providers( config.providers );

		var app = new App( {
			el        : '#itsec-2fa-on-board-app',
			form      : $( "#itsec-2fa-on-board" ),
			model     : state,
			collection: providers,
			screens   : {
				intro                  : function ( options ) {
					return new ScreenIntro( options );
				},
				providers              : function ( options ) {
					options.collection = providers;

					return new ScreenProviders( options );
				},
				Two_Factor_Totp        : function ( options ) {
					options.state = options.model;
					options.model = providers.get( 'Two_Factor_Totp' );

					return new ViewTotp( options );
				},
				'totp-confirm'         : function ( options ) {
					options.state = options.model;
					options.model = providers.get( 'Two_Factor_Totp' );

					return new ViewTotpConfirm( options )
				},
				'email-confirm'        : function ( options ) {
					options.state = options.model;
					options.model = providers.get( 'Two_Factor_Email' );

					return new ViewEmailConfirm( options );
				},
				Two_Factor_Backup_Codes: function ( options ) {
					options.state = options.model;
					options.model = providers.get( 'Two_Factor_Backup_Codes' );

					return new ViewBackupCodes( options );
				},
				summary                : function ( options ) {
					options.collection = providers;

					return new ViewSummary( options );
				},
			}
		} );
		app.render();
	}

	$( function () {
		onBoard();
	} );

})( jQuery, window.Backbone, window._, window.wp, window['ITSEC2FAOnBoard'], window['itsecLoginInterstitial'] );
