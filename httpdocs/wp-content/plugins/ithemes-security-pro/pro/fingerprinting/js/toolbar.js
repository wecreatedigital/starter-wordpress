(function ( $, Backbone, MicroModal, _, wp, config ) {

	var info = ['ip', 'browser', 'platform', 'date-time'];

	var View = wp.Backbone.View.extend( {
		prepare: function () {
			if ( this.model ) {
				return {
					c: config,
					m: this.model.toJSON(),
				}
			}

			return wp.Backbone.View.prototype.prepare.apply( this );
		}
	} );

	var App = View.extend( {
		template  : wp.template( 'itsec-fingerprint-app' ),
		initialize: function ( options ) {

			this.listenTo( this.collection, 'add', this.onAdd );
			this.listenTo( this.collection, 'remove', this.onRemove );
			this.listenTo( this.collection, 'update', this.updateBubble );

			this.collection.forEach( (function ( fingerprint ) {
				this.views.add( '.itsec-fingerprint-cards', new Card( { model: fingerprint } ) )
			}).bind( this ) );

			this.views.add( '.itsec-fingerprint-empty-state-container', new EmptyState() );
		},

		onAdd: function ( model ) {
			this.views.add( '.itsec-fingerprint-cards', new Card( { model: model } ) );
		},

		onRemove: function ( model ) {

			var views = this.views.get( '.itsec-fingerprint-cards' );

			for ( var i = 0; i < views.length; i++ ) {
				var maybeView = views[i];

				if ( maybeView.model === model ) {
					this.views.unset( maybeView );
					this.render();
					return;
				}
			}
		},

		updateBubble: function () {
			var $count = $( '.itsec-login-alert-bubble__count' ),
				c = this.collection.length;

			if ( c ) {
				$count.text( c );
				$( '.itsec-login-alert-bubble' ).show();
			} else {
				$( '.itsec-login-alert-bubble' ).hide();
				$( '#wp-admin-bar-itsec-fingerprinting' ).removeClass( 'hover' );
			}
		}
	} );

	var EmptyState = View.extend( { template: wp.template( 'itsec-fingerprint-empty-state' ), className: 'itsec-fingerprint-empty-state' } );

	var Card = View.extend( {
		template : wp.template( 'itsec-fingerprint-card' ),
		tagName  : 'li',
		className: function () {
			var className = 'itsec-fingerprint-card';

			if ( !this.model.get( 'map-small' ).length ) {
				className += ' itsec-fingerprint-card--no-map';
			}

			return className;
		},

		events: {
			'click .itsec-fingerprint-header'            : 'openModal',
			'click .itsec-fingerprint-card__launch-modal': 'openModal',
		},

		openModal: function ( e ) {
			if ( $( e.target ).closest( '.itsec-fingerprint-header__info-container' ).length ) {
				return;
			}

			if ( !document.getElementById( 'itsec-fingerprint-modal-' + this.model.get( 'uuid' ) ) ) {
				return;
			}

			MicroModal.show( 'itsec-fingerprint-modal-' + this.model.get( 'uuid' ) );
		},

		initialize: function () {
			if ( this.model.get( 'map-small' ).length ) {
				this.views.add( '.itsec-fingerprint-card__header', new FingerprintHeader( { model: this.model } ) );
			}

			this.views.add( '.itsec-fingerprint-card__info', new FingerprintInfo( { model: this.model } ) );
			this.views.add( '.itsec-fingerprint-card__footer', new FingerprintFooter( {
				model    : this.model,
				addNotice: function ( type, message ) {
					alert( message );
				}
			} ) );
		},

		render: function () {
			wp.Backbone.View.prototype.render.apply( this );

			for ( var i = 0; i < info.length; i++ ) {
				var key = info[i];

				var $el = this.$( '.itsec-fingerprint-card__info-' + key );
				$el.text( $el.text().replace( '%s', this.model.get( key ) ) );
			}
		}
	} );

	var Modal = View.extend( {
		template  : wp.template( 'itsec-fingerprint-modal' ),
		microModal: null,
		className : function () {
			var className = '';

			if ( !this.model.get( 'map-small' ).length ) {
				className += ' itsec-fingerprint-modal--no-map';
			}

			return className;
		},

		events: {
			'click .itsec-fingerprint-footer__action-approve': 'onApprove'
		},

		initialize: function () {
			if ( this.model.get( 'map-small' ).length ) {
				this.views.add( '.itsec-fingerprint-modal__header-container', new FingerprintHeader( { model: this.model, size: 'large' } ) );
			}

			this.views.add( 'aside', new FingerprintInfo( { model: this.model } ) );
			this.views.add( 'footer', new FingerprintFooter( {
				model       : this.model,
				addNotice   : this.addNotice.bind( this ),
				clearNotices: this.clearNotices.bind( this ),
			} ) );
		},

		addNotice: function ( type, message ) {

			wp.a11y.speak( message, 'error' === type ? 'assertive' : 'polite' );

			var $notice = jQuery( '<div>', { class: 'notice notice-alt notice-' + type } )
				.append( jQuery( '<p>', { html: message } ) );

			this.$( '.itsec-fingerprint-modal__notices-container' ).append( $notice );
		},

		clearNotices: function () {
			this.$( '.itsec-fingerprint-modal__notices-container' ).html( '' );
		},

		render: function () {
			wp.Backbone.View.prototype.render.apply( this );
		},
	} );

	var Modals = View.extend( {
		initialize: function () {
			this.listenTo( this.collection, 'add', this.onAdd );

			this.collection.forEach( (function ( model ) {
				this.views.add( new Modal( { model: model } ) );
			}).bind( this ) );
		},

		onAdd: function ( model ) {
			this.views.add( new Modal( { model: model } ) );
		},
	} );

	var FingerprintHeader = View.extend( {
		template : wp.template( 'itsec-fingerprint-header' ),
		tagName  : 'div',
		className: 'itsec-fingerprint-header',

		render: function () {
			wp.Backbone.View.prototype.render.apply( this );

			var map = this.model.get( 'map-small' );

			if ( this.options.size === 'large' ) {
				map = this.model.get( 'map-large' );
			}

			this.$el.css( 'background-image', 'url(' + map + ')' );
		}
	} );

	var FingerprintInfo = View.extend( {
		template : wp.template( 'itsec-fingerprint-info' ),
		tagName  : 'dl',
		className: 'itsec-fingerprint-info',

		render: function () {
			wp.Backbone.View.prototype.render.apply( this );

			for ( var i = 0; i < info.length; i++ ) {
				var key = info[i];

				this.$( '.itsec-fingerprint-info__part--' + key ).text( this.model.get( key ) );
			}

			if ( this.model.get( 'map-small' ).length ) {
				var $line = this.$( '.itsec-fingerprint-info__part--date-time' );
				$line.prev().remove();
				$line.remove();
			}
		}
	} );

	var FingerprintFooter = View.extend( {
		template : wp.template( 'itsec-fingerprint-footer' ),
		className: 'itsec-fingerprint-footer',

		events: {
			'click .itsec-fingerprint-footer__action-approve': 'onApprove',
			'click .itsec-fingerprint-footer__action-deny'   : 'onDeny',
		},

		onApprove: function ( e ) {
			e.preventDefault();

			if ( this.model.collection ) {
				this.fireAjax( 'approve' );
			}
		},

		onDeny: function ( e ) {
			e.preventDefault();

			if ( this.model.collection ) {
				this.fireAjax( 'deny' );
			}
		},

		fireAjax( action ) {
			this.options.clearNotices && this.options.clearNotices();

			this.$( '.itsec-fingerprint-footer__action' ).prop( 'disabled', true );

			ajax( { itsec_uuid: this.model.get( 'uuid' ), itsec_action: action } )
				.done( (function ( response ) {
					this.options.addNotice && this.options.addNotice( 'success', response.message );

					if ( response.url ) {
						window.location = response.url;
					}

					this.model.collection.remove( this.model );
				}).bind( this ) )
				.fail( (function ( response ) {
					this.options.addNotice && this.options.addNotice( 'error', response.message );
				}).bind( this ) )
				.always( (function () {
					this.$( '.itsec-fingerprint-footer__action' ).prop( 'disabled', false );
				}).bind( this ) );
		}
	} );

	var Fingerprint = Backbone.Model.extend( { idAttribute: 'uuid' } );
	var Fingerprints = Backbone.Collection.extend( { model: Fingerprint } );

	function toolbar() {
		var modalsContainer = document.createElement( 'div' );
		modalsContainer.id = 'itsec-fingerprinting-modals';
		document.body.appendChild( modalsContainer );

		var fingerprints = new Fingerprints( config.fingerprints );

		var app = new App( {
			el        : '#wp-admin-bar-itsec-fingerprinting-cards',
			collection: fingerprints,
		} );
		app.render();

		var modals = new Modals( {
			el        : '#itsec-fingerprinting-modals',
			collection: fingerprints,
		} );
		modals.render(); // We can lazy render this later.

		$( document ).on( 'heartbeat-send', function ( e, data ) {
			data.itsec_fingerprinting = {
				request: true,
				uuids  : fingerprints.map( function ( model ) {return model.get( 'uuid' )} ),
			};
		} );

		$( document ).on( 'heartbeat-tick', function ( e, data ) {
			if ( !data.itsec_fingerprinting || !data.itsec_fingerprinting.new ) {
				return;
			}

			for ( var i = 0; i < data.itsec_fingerprinting.new.length; i++ ) {
				var fingerprint = data.itsec_fingerprinting.new[i];
				fingerprints.add( fingerprint );
			}

			for ( var i = 0; i < data.itsec_fingerprinting.remove.length; i++ ) {
				fingerprints.remove( data.itsec_fingerprinting.remove[i] );
			}
		} );
	}

	/**
	 * Perform an ajax request to the Fingerprinting Framework.
	 *
	 * @param {Object} data
	 * @returns {*|$.promise}
	 */
	function ajax( data ) {
		return wp.ajax.post( 'itsec-fingerprint-action', _.extend( {}, {
			nonce: config.nonce,
		}, data ) );
	}

	$( toolbar );

})( jQuery, window.Backbone, window.MicroModal, window._, window.wp, window['ITSECFingerprintToolbar'] );