/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { apiFetch } from '../controls';
import { receivePrimaryDashboard } from './actions';

export function* getPrimaryDashboard() {
	const user = yield apiFetch( { path: addQueryArgs( '/wp/v2/users/me', { context: 'edit' } ) } );
	yield receivePrimaryDashboard( user.meta._itsec_primary_dashboard );
}
