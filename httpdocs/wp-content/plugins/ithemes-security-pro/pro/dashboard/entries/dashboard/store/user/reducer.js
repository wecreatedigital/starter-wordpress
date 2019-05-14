import { RECEIVE_PRIMARY_DASHBOARD } from 'pro/dashboard/entries/dashboard/store/user/actions';

const DEFAULT_STATE = {
	primaryDashboard: 0,
};

export default function user( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case RECEIVE_PRIMARY_DASHBOARD:
			return {
				...state,
				primaryDashboard: action.dashboardId,
			};
		default:
			return state;
	}
}
