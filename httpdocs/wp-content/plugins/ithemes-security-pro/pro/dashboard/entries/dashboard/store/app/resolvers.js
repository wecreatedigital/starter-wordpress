/**
 * External dependencies
 */
import { isEmpty } from 'lodash';

/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import { apiFetch } from '../controls';
import { receiveStaticStats, receiveSuggestedShareUsers, receiveUser } from './actions';

export function* getSuggestedShareUsers( ) {
	const users = yield apiFetch( {
		path: addQueryArgs( '/wp/v2/users', { roles: 'administrator' } ),
	} );

	yield receiveSuggestedShareUsers( users );
}

export function* getUser( userId ) {
	const user = yield apiFetch( {
		path: `/wp/v2/users/${ userId }`,
	} );

	yield receiveUser( user );
}

export const getStaticStats = {
	*fulfill( query ) {
		const path = '/ithemes-security/v1/dashboard-static';
		const stats = yield apiFetch( {
			path: isEmpty( query ) ? path : addQueryArgs( path, query ),
		} );

		yield receiveStaticStats( stats, query );
	},
	isFulfilled( state, query ) {
		return isShallowEqual( state.app.staticStats.query, query );
	},
};
