import 'packages/webpack/src/public-path';
/**
 * WordPress dependencies
 */
import { setLocaleData } from '@wordpress/i18n';
import { render } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';

// Silence warnings until JS i18n is stable.
setLocaleData( { '': {} }, 'ithemes-security-pro' );

/**
 * Internal dependencies
 */
import App from './widget/app.js';

const load = () => render( <App />, document.getElementById( 'itsec-widget-root' ) );

if ( window.MutationObserver ) {
	const observer = new MutationObserver( ( ) => {
		if ( document.getElementById( 'itsec-widget-root' ) ) {
			load();
			observer.disconnect();
		}
	} );
	observer.observe( document, {
		childList: true,
		subtree: true,
	} );
} else {
	domReady( load );
}
