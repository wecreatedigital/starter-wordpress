import 'packages/webpack/src/public-path';
/**
 * WordPress dependencies
 */
import { setLocaleData } from '@wordpress/i18n';
import { render } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';

setLocaleData( { '': {} }, 'ithemes-security-pro' );

/**
 * Internal dependencies
 */
import App from './dashboard/app.js';

domReady( () => render( <App />, document.getElementById( 'itsec-dashboard-root' ) ) );
