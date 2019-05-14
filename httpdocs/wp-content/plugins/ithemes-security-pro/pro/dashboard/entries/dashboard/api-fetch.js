/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getConfigValue } from './utils';

apiFetch.use( apiFetch.createPreloadingMiddleware( getConfigValue( 'preload' ) ) );
