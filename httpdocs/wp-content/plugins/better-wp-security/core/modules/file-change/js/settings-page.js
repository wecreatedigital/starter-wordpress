jQuery( document ).ready( function ( $ ) {

	itsecSettingsPage.events.on( 'modulesReloaded', initializeFileTrees );

	function initializeFileTrees() {

		/**
		 * Show the file tree in the settings.
		 */
		$( '.jquery_file_tree' ).fileTree(
			{
				root         : itsec_file_change_settings.ABSPATH,
				script       : ajaxurl,
				expandSpeed  : -1,
				collapseSpeed: -1,
				multiFolder  : false

			}, function ( file ) {

				$( '#itsec-file-change-file_list' ).val( file.substring( itsec_file_change_settings.ABSPATH.length ) + "\n" + $( '#itsec-file-change-file_list' ).val() );

			}, function ( directory ) {

				$( '#itsec-file-change-file_list' ).val( directory.substring( itsec_file_change_settings.ABSPATH.length ) + "\n" + $( '#itsec-file-change-file_list' ).val() );

			}
		);
	}

	initializeFileTrees();

	/**
	 * Shows and hides the red selector icon on the file tree allowing users to select an
	 * individual element.
	 */
	jQuery( document ).on( 'mouseover mouseout', '.jqueryFileTree > li a', function ( event ) {

		if ( event.type == 'mouseover' ) {

			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'visible' );

		} else {

			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'hidden' );

		}

	} );

	itsecSettingsPage.events.on( 'modulesReloaded', initializeScan );

	function initializeScan() {
		var $button = $( '#itsec-file-change-one_time_check' );
		var scan = window.scan = new window.ITSECFileChangeScanner( $button, {
			classList       : 'button-secondary',
			messageContainer: $( '#itsec_file_change_status' ),
		} );

		$button.on( 'click', function () {
			scan.start();
		} );
	}

	initializeScan();

	$( document ).on( 'click', '#itsec-file-change-abort', function () {
		var $this = $( this );

		$this.prop( 'disabled', true );

		itsecUtil.sendModuleAJAXRequest( 'file-change', { method: 'abort' }, function ( results ) {
			var $button = $( '#itsec-file-change-one_time_check' );
			$button.prop( 'disabled', false );
			$button.prop( 'class', 'button-primary' );
			$button.val( ITSECFileChangeScannerl10n.button_text );

			$this.remove();
		} );
	} );
} );