function ITSECFileChangeScanner( $el, options ) {

	this.$el = $el;
	this.options = jQuery.extend( {}, {
		messageContainer: jQuery(),
		scanningClass   : 'itsec-is-scanning',
		classList       : null,
		onStart         : null,
		onCancel        : null,
		onFinish        : null,
		onAbort         : null,
		l10n            : window['ITSECFileChangeScannerl10n'],
	}, options );

	this.isRunning = false;
	this.results = null;
	this.deferred = null;
	this.originalClass = $el.prop( 'class' );
	this.originalHeartbeat = wp.heartbeat.interval();

	jQuery( document ).on( 'heartbeat-send', (function ( e, d ) {
		this.heartbeatSend( e, d );
	}).bind( this ) );
	jQuery( document ).on( 'heartbeat-tick', (function ( e, d ) {
		this.heartbeatTick( e, d );
	}).bind( this ) );
}

ITSECFileChangeScanner.prototype.start = function () {

	var deferred = jQuery.Deferred();

	if ( this.isRunning ) {
		deferred.reject( { alreadyInProgress: true } );

		return deferred.promise();
	}

	this.deferred = deferred;
	this.$el.prop( 'disabled', true );

	itsecUtil.sendModuleAJAXRequest( 'file-change', { method: 'one-time-scan' }, (function ( results ) {
		this.options.messageContainer.html( '' );

		if ( results.errors && results.errors.length > 0 ) {
			$.each( results.errors, (function ( index, error ) {
				this.message( error );
			}).bind( this ) );
		} else if ( !results.success ) {
			this.message( this.options.l10n.unknown_error );
		} else {
			this.onStart();

			return;
		}

		this.onStop();
		deferred.reject( { cancelled: true } );
		this.options.onCancel && this.options.onCancel( this );
	}).bind( this ) );

	return deferred.promise();
};

ITSECFileChangeScanner.prototype.heartbeatSend = function ( e, data ) {
	if ( !data.itsec_file_change_scan_status ) {
		data.itsec_file_change_scan_status = this.isRunning ? 1 : 0;
	}
};

ITSECFileChangeScanner.prototype.heartbeatTick = function ( e, data ) {

	if ( !data.itsec_file_change_scan_status || !this.isRunning ) {
		return;
	}

	if ( data.itsec_file_change_scan_status.running ) {
		this.status( data.itsec_file_change_scan_status.message );
	} else if ( data.itsec_file_change_scan_status.complete ) {
		this.status( data.itsec_file_change_scan_status.message );
		this.onStop();

		if ( data.itsec_file_change_scan_status.found_changes ) {
			this.message( this.options.l10n.found_changes.replace( '#REPLACE_ID#', data.itsec_file_change_scan_status.found_changes ) );
		} else {
			this.message( this.options.l10n.no_changes, 'success' );
		}
	} else if ( data.itsec_file_change_scan_status.aborted ) {
		this.message( data.itsec_file_change_scan_status.message );
		this.onStop();
		this.options.onAbort && this.options.onAbort( this );
		this.deferred.reject( { aborted: true } );
	} else {
		this.onStop();
		this.options.onFinish && this.options.onFinish( this );
		this.deferred.resolve( data.itsec_file_change_scan_status );
	}
};

ITSECFileChangeScanner.prototype.onStart = function () {

	if ( this.options.classList ) {
		this.$el.prop( 'class', this.options.classList );
	} else {
		this.$el.addClass( this.options.scanningClass );
	}

	this.$el.prop( 'disabled', true );

	this.isRunning = true;
	this.options.onStart && this.options.onStart( this );
	this.status( this.options.l10n.scanning_button_text );
	wp.heartbeat.interval( 'fast' );
};

ITSECFileChangeScanner.prototype.onStop = function () {

	if ( this.options.classList ) {
		this.$el.prop( 'class', this.originalClass );
	} else {
		this.$el.removeClass( this.options.scanningClass );
	}

	this.$el.prop( 'disabled', false );
	this.status( this.options.l10n.button_text );
	this.isRunning = false;
	wp.heartbeat.interval( this.originalHeartbeat );
};

ITSECFileChangeScanner.prototype.status = function ( message ) {
	if ( this.$el.is( 'input' ) ) {
		this.$el.val( message );
	} else {
		this.$el.text( message );
	}
};

ITSECFileChangeScanner.prototype.message = function ( message, type ) {
	type = type || 'error';

	var $notice = jQuery( '<div class="notice notice-alt inline"><p></p></div>' );
	$notice.addClass( 'notice-' + type );

	if ( type === 'success' ) {
		$notice.addClass( 'fade' );
	}

	jQuery( 'p', $notice ).html( message );

	this.options.messageContainer.append( $notice );
};

window.ITSECFileChangeScanner = ITSECFileChangeScanner;