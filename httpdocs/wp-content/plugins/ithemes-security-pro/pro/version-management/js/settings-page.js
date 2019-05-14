(function ( config, $, Backbone, View, template ) {

	var Package = Backbone.Model.extend( {
		defaults: {

			name: '',
			file: '',
			kind: '', // 'plugin' or 'theme'

			type : 'enabled', // 'enabled' or 'disabled' or 'delay'.
			delay: 0,

			checked: false,
		}
	} );

	var Packages = Backbone.Collection.extend( {
		model: Package,
	} );

	var App = View.extend( {
		template : template( 'itsec-vm-app' ),
		className: 'itsec-vm-app',
		tagName  : 'table',

		initialize: function ( options ) {
			this.views.add( 'thead', [new Header( { collection: this.collection, kind: options.kind } )] );

			var views = [];

			this.collection.forEach( (function ( model ) {
				views.push( new PackageRow( { model: model } ) );
			}).bind( this ) );

			this.views.add( 'tbody', views );
		}
	} );

	var Header = View.extend( {
		template : template( 'itsec-vm-header' ),
		className: 'itsec-vm-header',
		tagName  : 'tr',

		events: {
			'click .itsec-vm-column__bulk input'     : 'onBulk',
			'click .itsec-vm-header__button--enable' : 'onEnable',
			'click .itsec-vm-header__button--disable': 'onDisable',
			'click .itsec-vm-header__button--delay'  : 'onDelay',
			'change .itsec-vm-column__days input'    : 'onDaysChange',
		},

		_kind: '',

		initialize: function ( options ) {
			this._kind = options.kind;
		},

		onBulk: function ( e ) {
			this.collection.forEach( function ( model ) {
				model.set( 'checked', e.target.checked );
			} );
		},

		onEnable: function ( e ) {
			e.preventDefault();

			this.collection.forEach( function ( model ) {
				if ( model.get( 'checked' ) ) {
					model.set( 'type', 'enabled' );
				}
			} );
		},

		onDisable: function ( e ) {
			e.preventDefault();

			this.collection.forEach( function ( model ) {
				if ( model.get( 'checked' ) ) {
					model.set( 'type', 'disabled' );
				}
			} );
		},

		onDelay: function ( e ) {
			e.preventDefault();

			var days = this.getDays();

			this.collection.forEach( function ( model ) {
				if ( model.get( 'checked' ) ) {
					model.set( {
						type : 'delay',
						delay: days,
					} );
				}
			} );
		},

		onDaysChange: function ( e ) {
			var days = this.getDays();

			this.collection.forEach( function ( model ) {
				if ( model.get( 'checked' ) && model.get( 'type' ) === 'delay' ) {
					model.set( 'delay', days );
				}
			} );
		},

		getDays: function () {
			return parseInt( this.$( '.itsec-vm-column__days input' ).val().replace( /\D/g, '' ) ) || 3;
		},

		prepare: function () {
			return {
				d: {
					kind: this._kind,
				}
			}
		}
	} );

	var PackageRow = View.extend( {
		template : template( 'itsec-vm-package' ),
		className: function () {
			return 'itsec-vm-package' + (this._focused.indexOf( 'itsec-vm-package__type--' ) === 0 ? ' itsec-vm-package--focused' : '');
		},
		tagName  : 'tr',

		events: {
			'change .itsec-vm-package__bulk input' : 'onCheckChange',
			'change .itsec-vm-package__type input' : 'onTypeChange',
			'change .itsec-vm-package__delay input': 'onDelayChange',

			'focusin input' : 'onFocusIn',
			'focusout input': 'onFocusOut',
		},

		_focused: '',

		initialize: function () {
			this.listenTo( this.model, 'change', this.render );
		},

		onCheckChange: function ( e ) {
			this.model.set( 'checked', e.target.checked );
		},

		onTypeChange: function ( e ) {
			this._focused = e.target.id;
			var type = this.$( '.itsec-vm-package__type input:checked' ).val();
			this.model.set( 'type', type );
		},

		onDelayChange: function ( e ) {
			var days = e.target.value.replace( /\D/g, '' );
			days = parseInt( days );

			this.model.set( 'delay', days );
		},

		onFocusIn: function ( e ) {
			this._focused = e.target.id;
			this.$el.attr( 'class', this.className() );
		},

		onFocusOut: function ( e ) {
			this._focused = '';
			this.$el.attr( 'class', this.className() );
		},

		prepare: function () {
			return {
				m: this.model.toJSON(),
				d: {
					bulkLabel  : config.bulkLabel.replace( '%s', this.model.get( 'name' ) ),
					bulkChecked: this.model.get( 'checked' ) ? 'checked' : '',
					delay      : this.model.get( 'delay' ) || '',
				}
			}
		},

		render: function () {
			View.prototype.render.apply( this );

			if ( this._focused.length ) {
				this.$( '#' + escapeSelector( this._focused ) ).focus();
			}

			return this;
		}
	} );

	function render( kind ) {

		var app = new App( {
			el        : '#itsec-vm-app--' + kind,
			collection: new Packages( config.packages.filter( function ( model ) {return model.kind === kind} ) ),
			kind      : kind,
		} );

		app.render();
	}

	/**
	 * Escape a string for use in a jQuery selector.
	 *
	 * @link http://alexandregiannini.blogspot.com/2011/05/escaping-strings-for-jquery-selectors.html
	 *
	 * @param {String} expression
	 * @returns {String}
	 */
	function escapeSelector( expression ) {
		return expression.replace( /[!"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, '\\$&' );
	}

	$( function () {

		$( document ).on( 'change', '#itsec-version-management-plugin_automatic_updates', function ( e ) {
			var type = $( this ).val();

			if ( type === 'custom' ) {
				$( '#itsec-version-management-plugin-container' ).removeClass( 'hidden' );
			} else {
				$( '#itsec-version-management-plugin-container' ).addClass( 'hidden' );
			}
		} );

		$( document ).on( 'change', '#itsec-version-management-theme_automatic_updates', function ( e ) {
			var type = $( this ).val();

			if ( type === 'custom' ) {
				$( '#itsec-version-management-theme-container' ).removeClass( 'hidden' );
			} else {
				$( '#itsec-version-management-theme-container' ).addClass( 'hidden' );
			}
		} );

		render( 'plugin' );
		render( 'theme' );
	} );

})( window['ITSECVersionManagement'], jQuery, Backbone, wp.Backbone.View, wp.template );