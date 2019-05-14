/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * Internal dependencies
 */
import { RECEIVE_SUGGESTED_SHARE_USERS, RECEIVE_USER, NAVIGATE, OPEN_EDIT_CARDS, CLOSE_EDIT_CARDS, RECEIVE_STATIC_STATS, USING_TOUCH } from './actions';
import { FINISH_ADD_DASHBOARD } from '../dashboard/actions';

const DEFAULT_STATE = {
	view: {
		page: '',
		attr: {},
	},
	editingCards: false,
	suggestedShareUsers: [],
	users: {
		byId: {},
	},
	staticStats: {
		data: {},
		query: {},
	},
	usingTouch: false,
};

export default function app( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case NAVIGATE:
			return {
				...state,
				view: {
					page: action.page,
					attr: action.attr || {},
				},
			};
		case RECEIVE_SUGGESTED_SHARE_USERS:
			return {
				...state,
				suggestedShareUsers: action.users,
				users: {
					...state.users,
					byId: {
						...state.users.byId,
						...keyBy( action.users, 'id' ),
					},
				},
			};
		case RECEIVE_USER:
			return {
				...state,
				users: {
					...state.users,
					byId: {
						...state.users.byId,
						[ action.user.id ]: action.user,
					},
				},
			};
		case FINISH_ADD_DASHBOARD:
			return ! [ 'create-dashboard-scratch', 'create-dashboard-default' ].includes( action.context ) ? state : {
				...state,
				editingCards: true,
				view: {
					page: 'view-dashboard',
					attr: { id: action.created.id },
				},
			};
		case OPEN_EDIT_CARDS:
			return {
				...state,
				editingCards: true,
			};
		case CLOSE_EDIT_CARDS:
			return {
				...state,
				editingCards: false,
			};
		case RECEIVE_STATIC_STATS:
			return {
				...state,
				staticStats: {
					data: action.stats,
					query: action.query,
				},
			};
		case USING_TOUCH:
			return {
				...state,
				usingTouch: action.isUsing,
			};
		default:
			return state;
	}
}
