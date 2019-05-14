/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	receiveDashboard, receiveDashboardCollectionHeaders,
	receiveDashboardLayout,
	receiveDashboardQuery,
} from './actions';
import { apiFetch } from '../controls';
import { entriesToObject } from 'packages/utils/src';

/**
 * Requests dashboards from the REST API.
 */
export function* getAvailableDashboards() {
	const dashboards = yield apiFetch( { path: addQueryArgs( '/ithemes-security/v1/dashboards', {
		_embed: 1,
	} ) } );
	yield receiveDashboardQuery( 'available', dashboards );
}

export const canCreateDashboards = {
	*fulfill() {
		const response = yield apiFetch( { path: '/ithemes-security/v1/dashboards', method: 'HEAD', parse: false } );

		yield receiveDashboardCollectionHeaders( entriesToObject( response.headers ) );
	},
	isFulfilled( state ) {
		return !! state.dashboard.collectionHeaders.allow;
	},
};

export function* getDashboard() {
	select( 'ithemes-security/dashboard' ).getAvailableDashboards();
}

export function* getDashboardForEdit( id ) {
	const dashboard = yield apiFetch( { path: addQueryArgs( `/ithemes-security/v1/dashboards/${ id }`, {
		_embed: 1,
		context: 'edit',
	} ) } );
	yield receiveDashboard( dashboard );
}

export function* getDashboardLayout( id ) {
	const layout = yield apiFetch( { path: `/ithemes-security/v1/dashboards/${ id }/layout` } );
	yield receiveDashboardLayout( id, layout );
}
