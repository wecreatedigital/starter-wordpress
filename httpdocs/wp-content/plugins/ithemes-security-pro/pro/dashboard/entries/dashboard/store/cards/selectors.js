/**
 * External dependencies
 */
import createSelector from 'rememo';
import { map, get } from 'lodash';

/**
 * WordPress dependencies.
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isResolved, isResolving } from '../selectors';

export const getDashboardCards = createSelector(
	( state, dashboardId ) => map( state.cards.byDashboard[ dashboardId ], ( id ) => state.cards.byId[ id ] ),
	( state, dashboardId ) => [ state.cards.byDashboard[ dashboardId ], state.cards.byId ],
);

export function getDashboardCard( state, cardId ) {
	return state.cards.byId[ cardId ];
}

export function getDashboardCardSettings( state, cardId ) {
	return get( state, [ 'cards', 'byId', cardId, 'settings' ] );
}

export function getDashboardCardQueryArgs( state, cardId ) {
	return state.cards.queryArgs[ cardId ];
}

export function getDashboardCardConfig( state, cardId ) {
	const card = select( 'ithemes-security/dashboard' ).getDashboardCard( cardId );

	if ( ! card ) {
		return undefined;
	}

	return select( 'ithemes-security/dashboard' ).getAvailableCard( card.card );
}

export function getDashboardCardsByType( state, dashboardId, aboutLink ) {
	const cards = select( 'ithemes-security/dashboard' ).getDashboardCards( dashboardId );

	const ofType = [];

	for ( const card of cards ) {
		if ( get( card, [ '_links', 'about', 0, 'href' ] ) === aboutLink ) {
			ofType.push( card );
		}
	}

	return ofType;
}

export function canEditCard( state, dashboardId, cardId ) {
	return !! ( select( 'ithemes-security/dashboard' ).canEditDashboard( dashboardId ) && cardId );
}

export function isRequestingCards( state, dashboardId ) {
	return isResolving( 'getDashboardCards', dashboardId );
}

export function isRefreshingDashboardCards( state, dashboardId ) {
	return state.cards.refreshingDashboards.includes( dashboardId );
}

export function areCardsLoaded( state, dashboardId ) {
	return isResolved( 'getDashboardCards', dashboardId );
}

export function isRemovingCard( state, cardId ) {
	return state.cards.deleting.includes( cardId );
}

export function isAddingCard( state, context ) {
	return state.cards.adding.includes( context );
}

export function isCallingDashboardCardRpc( state, cardId, href ) {
	return state.cards.rpcs[ cardId ] && state.cards.rpcs[ cardId ].includes( href );
}

const DEFAULT_RPCS = [];

export function getCallingDashboardCardRpcs( state, cardId ) {
	return state.cards.rpcs[ cardId ] || DEFAULT_RPCS;
}

export function isQueryingDashboardCard( state, cardId ) {
	return state.cards.querying.includes( cardId );
}
