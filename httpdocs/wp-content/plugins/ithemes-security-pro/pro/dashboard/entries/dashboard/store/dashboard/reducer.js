/**
 * External dependencies
 */
import { keyBy, map, omit } from 'lodash';

/**
 * Internal dependencies
 */
import {
	FAILED_ADD_DASHBOARD, FAILED_SAVE_DASHBOARD,
	FINISH_ADD_DASHBOARD, FINISH_SAVE_DASHBOARD,
	RECEIVE_DASHBOARD, RECEIVE_DASHBOARD_COLLECTION_HEADERS,
	RECEIVE_DASHBOARD_LAYOUT,
	RECEIVE_DASHBOARD_QUERY,
	SAVING_DASHBOARD_LAYOUT,
	START_ADD_DASHBOARD, START_SAVE_DASHBOARD,
	FAILED_DELETE_DASHBOARD, FINISH_DELETE_DASHBOARD, START_DELETE_DASHBOARD,
} from './actions';

const DEFAULT_STATE = {
	byId: {},
	queries: {},
	layouts: {},
	savingLayouts: {},
	adding: [],
	saving: [],
	deleting: [],
	collectionHeaders: {},
};

export default function dashboard( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case RECEIVE_DASHBOARD_QUERY:
			return {
				...state,
				byId: {
					...state.byId,
					...keyBy( action.dashboards, 'id' ),
				},
				queries: {
					...state.queries,
					[ action.queryId ]: map( action.dashboards, ( newDashboard ) => newDashboard.id ),
				},
			};
		case RECEIVE_DASHBOARD:
			return {
				...state,
				byId: {
					...state.byId,
					[ action.dashboard.id ]: action.dashboard,
				},
				queries: {
					...state.queries,
					available: state.queries.available.includes( action.dashboard.id ) ? state.queries.available : [
						...state.queries.available,
						action.dashboard.id,
					],
				},
			};
		case RECEIVE_DASHBOARD_LAYOUT:
			return {
				...state,
				layouts: {
					...state.layouts,
					[ action.dashboardId ]: action.layout,
				},
				savingLayouts: {
					...state.savingLayouts,
					[ action.dashboardId ]: false,
				},
			};
		case SAVING_DASHBOARD_LAYOUT:
			return {
				...state,
				savingLayouts: {
					...state.savingLayouts,
					[ action.dashboardId ]: true,
				},
			};
		case START_ADD_DASHBOARD:
			return action.context ? {
				...state,
				adding: [
					...state.adding,
					action.context,
				],
			} : state;
		case FINISH_ADD_DASHBOARD:
		case FAILED_ADD_DASHBOARD:
			return {
				...state,
				adding: state.adding.filter( ( add ) => add !== action.context ),
			};
		case START_SAVE_DASHBOARD:
			return {
				...state,
				saving: [
					...state.saving,
					action.dashboardId,
				],
			};
		case FINISH_SAVE_DASHBOARD:
		case FAILED_SAVE_DASHBOARD:
			return {
				...state,
				saving: state.saving.filter( ( dashboardId ) => dashboardId !== action.dashboardId ),
			};
		case RECEIVE_DASHBOARD_COLLECTION_HEADERS:
			return {
				...state,
				collectionHeaders: action.headers,
			};
		case START_DELETE_DASHBOARD:
			return {
				...state,
				deleting: [
					...state.deleting,
					action.dashboardId,
				],
			};
		case FINISH_DELETE_DASHBOARD:
			return {
				...state,
				deleting: state.deleting.filter( ( dashboardId ) => dashboardId !== action.dashboardId ),
				byId: omit( state.byId, [ action.dashboardId ] ),
			};
		case FAILED_DELETE_DASHBOARD:
			return {
				...state,
				deleting: state.deleting.filter( ( dashboardId ) => dashboardId !== action.dashboardId ),
			};
		default:
			return state;
	}
}
