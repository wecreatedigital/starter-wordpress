( function( element, components, editPost, compose, data, plugins, l10n ) {
	const el = element.createElement;
	const META_KEY = 'itsec_enable_ssl';

	function EnableSSL( props ) {
		return el( editPost.PluginPostStatusInfo, { className: 'itsec-ssl' }, [
			el( wp.components.CheckboxControl, {
				checked : props.isEnabled,
				label   : l10n.enableSSL,
				onChange: props.update,
				key     : 'enable-ssl',
			} ),
		] );
	}

	plugins.registerPlugin( 'itsec-ssl', {
		icon  : 'hidden',
		render: compose.compose( [
			data.withSelect( function( select ) {
				return {
					isEnabled: ( select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} )[ META_KEY ],
				};
			} ),
			data.withDispatch( function( dispatch ) {
				return {
					update: function( isEnabled ) {
						const edit = { meta: {} };
						edit.meta[ META_KEY ] = isEnabled;

						dispatch( 'core/editor' ).editPost( edit );
					},
				};
			} ),
		] )( EnableSSL ),
	} );

} )( wp.element, wp.components, wp.editPost, wp.compose, wp.data, wp.plugins, ITSECSSLBlockEditor );
