/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { apiFetch } from '../controls';
import { receiveDashboardCards } from './actions';

export function* getDashboardCards( dashboardId ) {
	const cards = yield apiFetch( { path: `/ithemes-security/v1/dashboards/${ dashboardId }/cards?_embed=1` } );
	yield receiveDashboardCards( dashboardId, cards );
}

export function* getDashboardCardConfig( cardId ) {
	const card = select( 'ithemes-security/dashboard' ).getDashboardCard( cardId );

	if ( card && ! select( 'ithemes-security/dashboard' ).getAvailableCard( card.card ) ) {
		select( 'ithemes-security/dashboard' ).getAvailableCards();
	}
}
