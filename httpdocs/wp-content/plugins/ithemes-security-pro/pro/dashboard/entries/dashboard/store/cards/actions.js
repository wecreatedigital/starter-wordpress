/**
 * External dependencies
 */
import { set } from 'lodash';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { apiFetch, createNotice } from '../controls';

export function receiveDashboardCards( dashboardId, cards ) {
	return {
		type: RECEIVE_DASHBOARD_CARDS,
		dashboardId,
		cards,
	};
}

export function receiveDashboardCard( dashboardId, card ) {
	return {
		type: RECEIVE_DASHBOARD_CARD,
		dashboardId,
		card,
	};
}

export function receiveDashboardCardQueryArgs( cardId, queryArgs ) {
	return {
		type: RECEIVE_DASHBOARD_CARD_QUERY_ARGS,
		cardId,
		queryArgs,
	};
}

export function receiveDashboardCardData( cardId, data ) {
	return {
		type: RECEIVE_DASHBOARD_CARD_DATA,
		cardId,
		data,
	};
}

export function dashboardCardRemoved( dashboardId, cardId ) {
	return {
		type: REMOVE_DASHBOARD_CARD,
		dashboardId,
		cardId,
	};
}

export function startDashboardCardRemove( dashboardId, cardId ) {
	return {
		type: START_REMOVE_DASHBOARD_CARD,
		dashboardId,
		cardId,
	};
}

export function startAddDashboardCard( collectionEP, card, context ) {
	return {
		type: START_ADD_DASHBOARD_CARD,
		collectionEP,
		card,
		context,
	};
}

export function finishAddDashboardCard( collectionEP, card, context ) {
	return {
		type: FINISH_ADD_DASHBOARD_CARD,
		collectionEP,
		card,
		context,
	};
}

export function failedAddDashboardCard( collectionEP, card, context, error ) {
	return {
		type: FAILED_ADD_DASHBOARD_CARD,
		collectionEP,
		card,
		context,
		error,
	};
}

export function startDashboardCardRpc( cardId, href, data ) {
	return {
		type: START_DASHBOARD_CARD_RPC,
		cardId,
		href,
		data,
	};
}

export function finishDashboardCardRpc( cardId, href, data, response ) {
	return {
		type: FINISH_DASHBOARD_CARD_RPC,
		cardId,
		href,
		data,
		response,
	};
}

export function failedDashboardCardRpc( cardId, href, data, error ) {
	return {
		type: FAILED_DASHBOARD_CARD_RPC,
		cardId,
		href,
		data,
		error,
	};
}

export function startRefreshDashboardCards( dashboardId ) {
	return {
		type: START_REFRESH_DASHBOARD_CARDS,
		dashboardId,
	};
}

export function finishRefreshDashboardCards( dashboardId ) {
	return {
		type: FINISH_REFRESH_DASHBOARD_CARDS,
		dashboardId,
	};
}

export function failedRefreshDashboardCards( dashboardId, error ) {
	return {
		type: FAILED_REFRESH_DASHBOARD_CARDS,
		dashboardId,
		error,
	};
}

export function startRefreshDashboardCard( cardId ) {
	return {
		type: START_REFRESH_DASHBOARD_CARD,
		cardId,
	};
}

export function finishRefreshDashboardCard( cardId ) {
	return {
		type: FINISH_REFRESH_DASHBOARD_CARD,
		cardId,
	};
}

export function failedRefreshDashboardCard( cardId, error ) {
	return {
		type: FAILED_REFRESH_DASHBOARD_CARD,
		cardId,
		error,
	};
}

export function startQueryDashboardCard( cardId ) {
	return {
		type: START_QUERY_DASHBOARD_CARD,
		cardId,
	};
}

export function finishQueryDashboardCard( cardId ) {
	return {
		type: FINISH_QUERY_DASHBOARD_CARD,
		cardId,
	};
}

export function failedQueryDashboardCard( cardId, error ) {
	return {
		type: FAILED_QUERY_DASHBOARD_CARD,
		cardId,
		error,
	};
}

/**
 * Add a card to the dashboard.
 *
 * @param {string} collectionEP
 * @param {Object} card
 * @param {*} [context] Additional context about this request for use in subsequent actions.
 */
export function* addDashboardCard( collectionEP, card, context ) {
	yield startAddDashboardCard( collectionEP, card, context );
	let inserted;

	try {
		inserted = yield apiFetch( {
			path: addQueryArgs( collectionEP, { _embed: 1 } ),
			method: 'POST',
			data: card,
		} );
	} catch ( e ) {
		yield failedAddDashboardCard( collectionEP, card, context, e );
		yield createNotice( 'error', sprintf( __( 'Error when adding card to dashboard: %s', 'ithemes-security-pro' ), e.message ) );

		return;
	}

	yield finishAddDashboardCard( collectionEP, card, context );
	yield receiveDashboardCard( inserted.dashboard, inserted );
}

export function* saveDashboardCard( dashboardId, card ) {
	const recordId = card.id;
	let updatedRecord;

	try {
		updatedRecord = yield apiFetch( {
			path: `/ithemes-security/v1/dashboards/${ dashboardId }/cards/${ card.card }${ recordId ? '/' + recordId : '' }`,
			method: recordId ? 'PUT' : 'POST',
			data: card,
		} );
	} catch ( e ) {
		yield createNotice( 'error', sprintf( __( 'Error when saving dashboard card: %s', 'ithemes-security-pro' ), e.message ) );

		return e;
	}

	yield receiveDashboardCard( dashboardId, updatedRecord );

	return updatedRecord;
}

export function* removeDashboardCard( dashboardId, card, optimistic = true ) {
	yield startDashboardCardRemove( dashboardId, card.id, optimistic );

	if ( optimistic ) {
		yield dashboardCardRemoved( dashboardId, card.id );
	}

	try {
		yield apiFetch( {
			path: `/ithemes-security/v1/dashboards/${ dashboardId }/cards/${ card.card }/${ card.id }`,
			method: 'DELETE',
			parse: false,
		} );
	} catch ( e ) {
		if ( optimistic ) {
			yield receiveDashboardCard( dashboardId, card );
		}

		yield createNotice( 'error', sprintf( __( 'Error when removing card from dashboard: %s', 'ithemes-security-pro' ), e.message ) );

		return;
	}

	if ( ! optimistic ) {
		yield dashboardCardRemoved( dashboardId, card.id );
	}
}

export function* refreshDashboardCards( dashboardId ) {
	yield startRefreshDashboardCards( dashboardId );

	const data = select( 'ithemes-security/dashboard' );
	const cards = data.getDashboardCards( dashboardId );

	const queryArgs = {
		_fields: [ 'id', 'data' ],
		cards: {},
	};

	for ( const card of cards ) {
		set( queryArgs, [ 'cards', card.card, card.id ], data.getDashboardCardQueryArgs( card.id ) );
	}

	let updated;

	try {
		updated = yield apiFetch( {
			path: addQueryArgs(
				`/ithemes-security/v1/dashboards/${ dashboardId }/cards`,
				queryArgs,
			),
		} );
	} catch ( e ) {
		yield failedRefreshDashboardCards( dashboardId, e );
		yield createNotice( 'warning', sprintf( __( 'Refreshing dashboard data failed: %s', 'ithemes-security-pro' ), e.message ), { autoDismiss: true } );

		return e;
	}

	const updates = {};

	for ( const update of updated ) {
		updates[ update.id ] = update.data;
		yield receiveDashboardCardData( update.id, update.data );
	}

	yield finishRefreshDashboardCards( dashboardId );

	return updates;
}

export function* refreshDashboardCard( cardId ) {
	const card = select( 'ithemes-security/dashboard' ).getDashboardCard( cardId );

	if ( ! card ) {
		throw new Error( 'No card loaded with that id.' );
	}

	yield startRefreshDashboardCard( cardId );
	const response = yield apiFetch( {
		path: addQueryArgs(
			`/ithemes-security/v1/dashboards/${ card.dashboard }/cards/${ card.card }/${ cardId }`,
			{
				...select( 'ithemes-security/dashboard' ).getDashboardCardQueryArgs( cardId ),
				_fields: [ 'data' ],
			},
		),
	} );

	yield receiveDashboardCardData( cardId, response.data );
	yield finishRefreshDashboardCard( cardId );

	return response.data;
}

export function* queryDashboardCard( cardId, queryArgs ) {
	const card = select( 'ithemes-security/dashboard' ).getDashboardCard( cardId );

	if ( ! card ) {
		throw new Error( 'No card loaded with that id.' );
	}

	yield startQueryDashboardCard( cardId );
	const response = yield apiFetch( {
		path: addQueryArgs(
			`/ithemes-security/v1/dashboards/${ card.dashboard }/cards/${ card.card }/${ cardId }`,
			{
				...queryArgs,
				_fields: [ 'data' ],
			},
		),
	} );

	yield receiveDashboardCardData( cardId, response.data );
	yield receiveDashboardCardQueryArgs( cardId, queryArgs );
	yield finishQueryDashboardCard( cardId );

	return response.data;
}

export function* callDashboardCardRpc( cardId, href, data ) {
	yield startDashboardCardRpc( cardId, href, data );
	let response;

	try {
		response = yield apiFetch( {
			url: href,
			method: 'POST',
			data,
		} );
	} catch ( e ) {
		yield failedDashboardCardRpc( cardId, href, data, e );
		yield createNotice( 'error', sprintf( __( 'Error when performing card action: %s', 'ithemes-security-pro' ), e.message ) );

		return e;
	}

	yield finishDashboardCardRpc( cardId, href, data, response );
	yield refreshDashboardCard( cardId );

	return response;
}

export const RECEIVE_DASHBOARD_CARDS = 'RECEIVE_DASHBOARD_CARDS';
export const RECEIVE_DASHBOARD_CARD = 'RECEIVE_DASHBOARD_CARD';
export const RECEIVE_DASHBOARD_CARD_QUERY_ARGS = 'RECEIVE_DASHBOARD_CARD_QUERY_ARGS';
export const RECEIVE_DASHBOARD_CARD_DATA = 'RECEIVE_DASHBOARD_CARD_DATA';
export const REMOVE_DASHBOARD_CARD = 'REMOVE_DASHBOARD_CARD';
export const START_REMOVE_DASHBOARD_CARD = 'START_REMOVE_DASHBOARD_CARD';
export const START_ADD_DASHBOARD_CARD = 'START_ADD_DASHBOARD_CARD';
export const FINISH_ADD_DASHBOARD_CARD = 'FINISH_ADD_DASHBOARD_CARD';
export const FAILED_ADD_DASHBOARD_CARD = 'FAILED_ADD_DASHBOARD_CARD';
export const START_DASHBOARD_CARD_RPC = 'START_DASHBOARD_CARD_RPC';
export const FINISH_DASHBOARD_CARD_RPC = 'FINISH_DASHBOARD_CARD_RPC';
export const FAILED_DASHBOARD_CARD_RPC = 'FAILED_DASHBOARD_CARD_RPC';
export const START_REFRESH_DASHBOARD_CARDS = 'START_REFRESH_DASHBOARD_CARDS';
export const FINISH_REFRESH_DASHBOARD_CARDS = 'FINISH_REFRESH_DASHBOARD_CARDS';
export const FAILED_REFRESH_DASHBOARD_CARDS = 'FAILED_REFRESH_DASHBOARD_CARDS';
export const START_REFRESH_DASHBOARD_CARD = 'START_REFRESH_DASHBOARD_CARD';
export const FINISH_REFRESH_DASHBOARD_CARD = 'FINISH_REFRESH_DASHBOARD_CARD';
export const FAILED_REFRESH_DASHBOARD_CARD = 'FAILED_REFRESH_DASHBOARD_CARD';
export const START_QUERY_DASHBOARD_CARD = 'START_QUERY_DASHBOARD_CARD';
export const FINISH_QUERY_DASHBOARD_CARD = 'FINISH_QUERY_DASHBOARD_CARD';
export const FAILED_QUERY_DASHBOARD_CARD = 'FAILED_QUERY_DASHBOARD_CARD';
