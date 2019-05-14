(function ( $, itsecUtil, codeEditor ) {
	$( function () {
		var $messages = $( "#itsec-messages" );

		$( '#itsec-scheduler-events' ).on( 'click', '.button', function () {

			var $btn = $( this );
			$btn.prop( 'disabled', true );

			// We are purposely using attr() so as not to parse the data string as json.
			itsecUtil.sendAJAXRequest( '', 'run_event', { id: $btn.data( 'id' ), data: $btn.attr( 'data-data' ) }, function ( result ) {

				$btn.prop( 'disabled', false );

				if ( result.success ) {
					$( 'table', '#itsec-scheduler-events' ).replaceWith( result.response );
				}

				itsecUtil.displayNotices( result, $messages );
			} );
		} );

		$( document ).on( 'click', '#itsec-events-data-toggle', function () {
			$( '.itsec-events-data' ).toggleClass( 'hidden' );
		} );

		$( '#itsec-scheduler-reset' ).on( 'click', function () {

			var $btn = $( this );
			$btn.prop( 'disabled', true );

			itsecUtil.sendAJAXRequest( '', 'reset_scheduler', {}, function ( result ) {

				$btn.prop( 'disabled', false );

				if ( result.success ) {
					$( 'table', '#itsec-scheduler-events' ).replaceWith( result.response );
				}

				itsecUtil.displayNotices( result, $messages );
			} );
		} );

		var $saveBtn = $( '#itsec-settings-save' ), $loadBtn = $( "#itsec-settings-load" );

		$loadBtn.on( 'click', function () {
			$loadBtn.prop( 'disabled', true );

			itsecUtil.sendAJAXRequest( $( '#itsec-settings-module' ).val(), 'load_settings', {}, function ( result ) {
				itsecUtil.displayNotices( result, $messages );

				$loadBtn.prop( 'disabled', false );
				$saveBtn.prop( 'disabled', false );
				setEditorContent( JSON.stringify( result.response, null, 4 ) );
			} );
		} );

		$saveBtn.on( 'click', function () {

			$loadBtn.prop( 'disabled', true );
			$saveBtn.prop( 'disabled', true );

			itsecUtil.sendAJAXRequest( $( '#itsec-settings-module' ).val(), 'save_settings', getEditorContent(), function ( result ) {
				itsecUtil.displayNotices( result, $messages );

				$loadBtn.prop( 'disabled', false );
				$saveBtn.prop( 'disabled', false );

				if ( result.success ) {
					setEditorContent( JSON.stringify( result.response, null, 4 ) );
				}
			} );
		} );

		var $editor = $( "#itsec-settings-editor" ), editor;

		function setEditorContent( content ) {
			if ( codeEditor ) {
				if ( !editor ) editor = codeEditor.initialize( $editor );
				editor.codemirror.setValue( content );
			} else {
				$editor.val( content );
			}
		}

		function getEditorContent() {
			return editor ? editor.codemirror.getValue() : $editor.val();
		}
	} );
})( jQuery, window.itsecUtil, wp.codeEditor );