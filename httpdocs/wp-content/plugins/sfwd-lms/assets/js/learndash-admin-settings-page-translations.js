jQuery(document).ready(function() {
	console.log('in translations');

	jQuery( '.wrap-ld-translations select.ld-translation-install-locale' ).change(function ( event ) {
		var locale_url = jQuery( event.target ).val();
		if ( typeof locale_url !== 'undefined' ) {
			
			var project = jQuery( event.target ).data( 'project' );
			if ( jQuery( '.wrap-ld-translations a#learndash-translation-install-'+project ).length ) {
				
				if ( locale_url != '' ) {
					var a_href = jQuery( '.wrap-ld-translations a#learndash-translation-install-'+project ).attr( 'href', locale_url );
					jQuery( '.wrap-ld-translations a#learndash-translation-install-'+project ).show();
					
				} else {
					jQuery( '.wrap-ld-translations a#learndash-translation-install-'+project ).hide();
					var a_href = jQuery( '.wrap-ld-translations a#learndash-translation-install-'+project ).attr( 'href', '#' );
				}
			}
		}	
	});	
});
