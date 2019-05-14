/**
 * External Dependencies.
 */
import { keyBy, map, get, omit, filter } from 'lodash';

/**
 * Internal Dependencies.
 */
import {
	RECEIVE_DASHBOARD_CARDS,
	RECEIVE_DASHBOARD_CARD,
	RECEIVE_DASHBOARD_CARD_QUERY_ARGS,
	RECEIVE_DASHBOARD_CARD_DATA,
	REMOVE_DASHBOARD_CARD,
	START_REMOVE_DASHBOARD_CARD,
	START_ADD_DASHBOARD_CARD,
	FINISH_ADD_DASHBOARD_CARD,
	START_DASHBOARD_CARD_RPC,
	FINISH_DASHBOARD_CARD_RPC,
	FAILED_DASHBOARD_CARD_RPC,
	FAILED_ADD_DASHBOARD_CARD,
	START_REFRESH_DASHBOARD_CARDS,
	FINISH_REFRESH_DASHBOARD_CARDS,
	FAILED_REFRESH_DASHBOARD_CARDS,
	START_QUERY_DASHBOARD_CARD, FINISH_QUERY_DASHBOARD_CARD,
} from './actions';

const DEFAULT_STATE = {
	byId: {}, // Card ID => Objects
	byDashboard: {}, // Dashboard ID => array of Card IDs
	queryArgs: {}, // Card ID => Query Args
	deleting: [], // Ids of cards being deleted.
	adding: [],
	rpcs: {},
	refreshingDashboards: [],
	querying: [],
};

export default function cards( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case RECEIVE_DASHBOARD_CARDS:
			return {
				...state,
				byId: {
					...state.byId,
					...keyBy( action.cards, 'id' ),
				},
				byDashboard: {
					...state.byDashboard,
					[ action.dashboardId ]: map( action.cards, ( card ) => card.id ),
				},
			};
		case RECEIVE_DASHBOARD_CARD:
			return {
				...state,
				byId: {
					...state.byId,
					[ action.card.id ]: action.card,
				},
				byDashboard: {
					...state.byDashboard,
					[ action.dashboardId ]: (
						state.byDashboard[ action.dashboardId ] &&
						state.byDashboard[ action.dashboardId ].includes( action.card.id )
					) ? state.byDashboard[ action.dashboardId ] :
						[ ...get( state, [ 'byDashboard', action.dashboardId ], [] ), action.card.id ],
				},
			};
		case RECEIVE_DASHBOARD_CARD_QUERY_ARGS:
			return {
				...state,
				queryArgs: {
					...state.queryArgs,
					[ action.cardId ]: action.queryArgs,
				},
			};
		case RECEIVE_DASHBOARD_CARD_DATA:
			return {
				...state,
				byId: {
					...state.byId,
					[ action.cardId ]: {
						...state.byId[ action.cardId ],
						data: action.data,
					},
				},
			};
		case START_REMOVE_DASHBOARD_CARD:
			return {
				...state,
				deleting: [
					...state.deleting,
					action.cardId,
				],
			};
		case REMOVE_DASHBOARD_CARD:
			return {
				...state,
				byId: omit( state.byId, action.cardId ),
				byDashboard: {
					...state.byDashboard,
					[ action.dashboardId ]: filter( state.byDashboard[ action.dashboardId ], ( cardId ) => cardId !== action.cardId ),
				},
				deleting: filter( state.deleting, ( id ) => id !== action.cardId ),
			};
		case START_ADD_DASHBOARD_CARD:
			return ! action.context ? state : {
				...state,
				adding: [
					...state.adding,
					action.context,
				],
			};
		case FINISH_ADD_DASHBOARD_CARD:
		case FAILED_ADD_DASHBOARD_CARD:
			return {
				...state,
				adding: state.adding.filter( ( add ) => add !== action.context ),
			};
		case START_DASHBOARD_CARD_RPC:
			return {
				...state,
				rpcs: {
					...state.rpcs,
					[ action.cardId ]: [
						...( state.rpcs[ action.cardId ] || [] ),
						action.href,
					],
				},
			};
		case FINISH_DASHBOARD_CARD_RPC:
		case FAILED_DASHBOARD_CARD_RPC:
			return {
				...state,
				rpcs: {
					...state.rpcs,
					[ action.cardId ]: ( state.rpcs[ action.cardId ] || [] ).filter( ( href ) => href !== action.href ),
				},
			};
		case START_REFRESH_DASHBOARD_CARDS:
			return {
				...state,
				refreshingDashboards: [
					...state.refreshingDashboards,
					action.dashboardId,
				],
			};
		case FINISH_REFRESH_DASHBOARD_CARDS:
		case FAILED_REFRESH_DASHBOARD_CARDS:
			return {
				...state,
				refreshingDashboards: state.refreshingDashboards.filter( ( dashboardId ) => dashboardId !== action.dashboardId ),
			};
		case START_QUERY_DASHBOARD_CARD:
			return {
				...state,
				querying: [
					...state.querying,
					action.cardId,
				],
			};
		case FINISH_QUERY_DASHBOARD_CARD:
			return {
				...state,
				querying: state.querying.filter( ( cardId ) => cardId !== action.cardId ),
			};
		default:
			return state;
	}
}
