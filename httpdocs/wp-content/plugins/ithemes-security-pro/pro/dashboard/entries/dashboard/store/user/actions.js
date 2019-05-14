/**
 * Internal dependencies
 */
import { apiFetch } from '../controls';

export function receivePrimaryDashboard( dashboardId ) {
	return {
		type: RECEIVE_PRIMARY_DASHBOARD,
		dashboardId,
	};
}

export function* setPrimaryDashboard( dashboardId ) {
	const user = yield apiFetch( {
		path: '/wp/v2/users/me',
		method: 'PUT',
		data: {
			meta: {
				_itsec_primary_dashboard: dashboardId,
			},
		},
	} );
	yield receivePrimaryDashboard( user.meta._itsec_primary_dashboard );
}

export const RECEIVE_PRIMARY_DASHBOARD = 'RECEIVE_PRIMARY_DASHBOARD';
