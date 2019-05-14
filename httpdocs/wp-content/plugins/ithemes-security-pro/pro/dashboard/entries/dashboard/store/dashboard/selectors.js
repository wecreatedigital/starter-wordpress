/**
 * External dependencies
 */
import createSelector from 'rememo';
import { map, get, filter, isObject } from 'lodash';
import { parse } from 'li';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isResolved, isResolving } from '../selectors';
import { transformApiLayoutToGrid } from '../../utils';

/**
 * Returns all available dashboards.
 *
 * @param {Object} state Data state.
 *
 * @return {Array} Authors list.
 */
export function getAvailableDashboards( state ) {
	return getDashboardsQueryResult( state, 'available' );
}

/**
 * Returns all the dashboards returned by a query ID.
 *
 * @param {Object} state   Data state.
 * @param {string} queryId Query ID.
 *
 * @return {Array} Users list.
 */
export const getDashboardsQueryResult = createSelector(
	( state, queryId ) => filter( map( state.dashboard.queries[ queryId ], ( id ) => state.dashboard.byId[ id ] ), isObject ),
	( state, queryId ) => [ state.dashboard.queries[ queryId ], state.dashboard.byId ]
);

export function getDashboard( state, id ) {
	return state.dashboard.byId[ id ];
}

export function getDashboardForEdit( state, id ) {
	return state.dashboard.byId[ id ];
}

export function canEditDashboard( state, id ) {
	return getTargetHints( state, id, 'allow' ).includes( 'PUT' );
}

export function canCreateDashboards( state ) {
	return state.dashboard.collectionHeaders.allow && state.dashboard.collectionHeaders.allow.split( ', ' ).includes( 'POST' );
}

function getTargetHints( state, id, hint ) {
	return get( state, [ 'dashboard', 'byId', id, '_links', 'self', 0, 'targetHints', hint ], [] );
}

export const getDashboardAddableCardLDOs = createSelector(
	( state, id ) => filter(
		get( state, [ 'dashboard', 'byId', id, '_links', 'create-form' ], [] ),
		( ldo ) => get( ldo, [ 'targetHints', 'allow' ], [] ).includes( 'POST' )
	).map( ( ldo ) => ( {
		...ldo,
		aboutLink: ldo.targetHints.link.map( parse ).filter( ( link ) => !! link.about ).map( ( link ) => link.about )[ 0 ],
	} ) ),
	( state, id ) => [ state.dashboard.byId[ id ] ]
);

export function isCardAtDashboardLimit( state, dashboardId, aboutLink ) {
	const availableCard = select( 'ithemes-security/dashboard' ).getAvailableCardBySelf( aboutLink );

	if ( ! availableCard || ! availableCard.max ) {
		return false;
	}

	const cardCount = select( 'ithemes-security/dashboard' ).getDashboardCardsByType( dashboardId, aboutLink ).length;

	return cardCount >= availableCard.max;
}

export function isRequestingDashboards() {
	return isResolving( 'getAvailableDashboards' );
}

export function isRequestingDashboardLayout( state, id ) {
	return isResolving( 'getDashboardLayout', id );
}

export function isLayoutLoaded( state, dashboardId ) {
	return isResolved( 'getDashboardLayout', dashboardId );
}

export function isCanCreateDashboardsLoaded() {
	return isResolved( 'canCreateDashboards' );
}

export function isSavingDashboardLayout( state, id ) {
	return state.dashboard.savingLayouts[ id ];
}

export function getDashboardLayout( state, id ) {
	return state.dashboard.layouts[ id ];
}

export const getDashboardLayoutForGrid = createSelector(
	( state, id ) => {
		return transformApiLayoutToGrid(
			id,
			get( select( 'ithemes-security/dashboard' ).getDashboardLayout( id ), 'cards', [] ),
			select( 'ithemes-security/dashboard' ).getDashboardCards( id ) || [],
		);
	},
	// todo: When selector cache clearing ships, remove the byDashboard dependent.
	( state, id ) => [ state.dashboard.layouts[ id ], state.availableCards, state.cards.byDashboard[ id ] ],
);

export function isAddingDashboard( state, context ) {
	return state.dashboard.adding.includes( context );
}

export function isSavingDashboard( state, dashboardId ) {
	return state.dashboard.saving.includes( dashboardId );
}

export function isDeletingDashboard( state, dashboardId ) {
	return state.dashboard.deleting.includes( dashboardId );
}
