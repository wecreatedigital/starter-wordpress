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
import { transformGridLayoutToApi } from 'pro/dashboard/entries/dashboard/utils';

export function receiveDashboardQuery( queryId, dashboards ) {
	return {
		type: RECEIVE_DASHBOARD_QUERY,
		queryId,
		dashboards,
	};
}

export function receiveDashboard( dashboard ) {
	return {
		type: RECEIVE_DASHBOARD,
		dashboard,
	};
}

export function receiveDashboardLayout( dashboardId, layout ) {
	return {
		type: RECEIVE_DASHBOARD_LAYOUT,
		dashboardId,
		layout,
	};
}

export function savingDashboardLayout( dashboardId, layout ) {
	return {
		type: SAVING_DASHBOARD_LAYOUT,
		dashboardId,
		layout,
	};
}

export function startAddDashboard( dashboard, context ) {
	return {
		type: START_ADD_DASHBOARD,
		dashboard,
		context,
	};
}

export function finishAddDashboard( dashboard, created, context ) {
	return {
		type: FINISH_ADD_DASHBOARD,
		dashboard,
		created,
		context,
	};
}

export function failedAddDashboard( dashboard, context, error ) {
	return {
		type: FAILED_ADD_DASHBOARD,
		dashboard,
		context,
		error,
	};
}

export function receiveDashboardCollectionHeaders( headers ) {
	return {
		type: RECEIVE_DASHBOARD_COLLECTION_HEADERS,
		headers,
	};
}

export function startSaveDashboard( dashboardId ) {
	return {
		type: START_SAVE_DASHBOARD,
		dashboardId,
	};
}

export function finishSaveDashboard( dashboardId ) {
	return {
		type: FINISH_SAVE_DASHBOARD,
		dashboardId,
	};
}

export function failedSaveDashboard( dashboardId, error ) {
	return {
		type: FAILED_SAVE_DASHBOARD,
		dashboardId,
		error,
	};
}

export function startDeleteDashboard( dashboardId ) {
	return {
		type: START_DELETE_DASHBOARD,
		dashboardId,
	};
}

export function finishDeleteDashboard( dashboardId ) {
	return {
		type: FINISH_DELETE_DASHBOARD,
		dashboardId,
	};
}

export function failedDeleteDashboard( dashboardId, error ) {
	return {
		type: FAILED_DELETE_DASHBOARD,
		dashboardId,
		error,
	};
}

/**
 * Action triggered to create a dashboard.
 *
 * Triggers additional actions as opposed to just saving the dashboard.
 *
 * @param {Object} dashboard Dashboard data.
 * @param {*} [context] Additional context about this request for use in subsequent actions.
 */
export function* addDashboard( dashboard, context ) {
	yield startAddDashboard( dashboard, context );

	let created;

	try {
		created = yield persistDashboard( dashboard );
	} catch ( e ) {
		yield failedAddDashboard( dashboard, context, e );
		yield createNotice( 'error', sprintf( __( 'Error when creating dashboard: %s', 'ithemes-security-pro' ), e.message ) );

		return;
	}

	yield receiveDashboard( created );
	yield finishAddDashboard( dashboard, created, context );
	yield createNotice( 'success', __( 'Dashboard Created', 'ithemes-security-pro' ), { autoDismiss: 10000 } );
}

/**
 * Action triggered to save a dashboard.
 *
 * @param {Object} dashboard  Record to be saved.
 *
 * @return {Object|Error} Updated record or error.
 */
export function* saveDashboard( dashboard ) {
	yield startSaveDashboard( dashboard.id );

	let updatedRecord;

	try {
		updatedRecord = yield persistDashboard( dashboard );
	} catch ( e ) {
		yield failedSaveDashboard( dashboard.id, e );
		yield createNotice( 'error', sprintf( __( 'Error when saving dashboard: %s', 'ithemes-security-pro' ), e.message ) );

		return e;
	}

	yield receiveDashboard( updatedRecord );
	yield finishSaveDashboard( dashboard.id );

	return updatedRecord;
}

function* persistDashboard( dashboard ) {
	return yield apiFetch( {
		path: addQueryArgs( `/ithemes-security/v1/dashboards${ dashboard.id ? '/' + dashboard.id : '' }`, { _embed: 1 } ),
		method: dashboard.id ? 'PUT' : 'POST',
		data: dashboard,
	} );
}

export function* saveDashboardLayoutFromGrid( dashboardId, layouts ) {
	const layout = transformGridLayoutToApi( dashboardId, layouts );

	yield receiveDashboardLayout( dashboardId, layout );

	if ( ! select( 'ithemes-security/dashboard' ).canEditDashboard( dashboardId ) ) {
		return;
	}

	return yield saveDashboardLayout( dashboardId, layout, false );
}

export function* saveDashboardLayout( dashboardId, layout, updateState = true ) {
	yield savingDashboardLayout( dashboardId, layout );

	let updated;

	try {
		updated = yield apiFetch( {
			path: `/ithemes-security/v1/dashboards/${ dashboardId }/layout`,
			method: 'PUT',
			data: layout,
		} );
	} catch ( e ) {
		return false;
	}

	if ( updateState ) {
		yield receiveDashboardLayout( dashboardId, updated );
	}

	return updated;
}

export function* deleteDashboard( dashboardId ) {
	yield startDeleteDashboard( dashboardId );

	try {
		yield apiFetch( {
			path: `/ithemes-security/v1/dashboards/${ dashboardId }`,
			method: 'DELETE',
		} );
	} catch ( e ) {
		yield failedDeleteDashboard( dashboardId, e );
		yield createNotice( 'error', sprintf( __( 'Error when deleting dashboard: %s', 'ithemes-security-pro' ), e.message ) );

		return e;
	}

	yield finishDeleteDashboard( dashboardId );

	return true;
}

export const RECEIVE_DASHBOARD_QUERY = 'RECEIVE_DASHBOARD_QUERY';
export const RECEIVE_DASHBOARD = 'RECEIVE_DASHBOARD';
export const RECEIVE_DASHBOARD_LAYOUT = 'RECEIVE_DASHBOARD_LAYOUT';
export const SAVING_DASHBOARD_LAYOUT = 'SAVING_DASHBOARD_LAYOUT';
export const START_ADD_DASHBOARD = 'START_ADD_DASHBOARD';
export const FINISH_ADD_DASHBOARD = 'FINISH_ADD_DASHBOARD';
export const FAILED_ADD_DASHBOARD = 'FAILED_ADD_DASHBOARD';
export const START_SAVE_DASHBOARD = 'START_SAVE_DASHBOARD';
export const FINISH_SAVE_DASHBOARD = 'FINISH_SAVE_DASHBOARD';
export const FAILED_SAVE_DASHBOARD = 'FAILED_SAVE_DASHBOARD';
export const START_DELETE_DASHBOARD = 'START_DELETE_DASHBOARD';
export const FINISH_DELETE_DASHBOARD = 'FINISH_DELETE_DASHBOARD';
export const FAILED_DELETE_DASHBOARD = 'FAILED_DELETE_DASHBOARD';
export const RECEIVE_DASHBOARD_COLLECTION_HEADERS = 'RECEIVE_DASHBOARD_COLLECTION_HEADERS';
