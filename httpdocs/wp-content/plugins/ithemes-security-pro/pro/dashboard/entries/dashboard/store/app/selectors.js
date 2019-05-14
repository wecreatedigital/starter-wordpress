export function getCurrentPage( state ) {
	return state.app.view.page;
}

export function getCurrentPageAttr( state ) {
	return state.app.view.attr;
}

export function getViewingDashboardId( state ) {
	return state.app.view.page === 'view-dashboard' && state.app.view.attr.id;
}

export function getSuggestedShareUsers( state ) {
	return state.app.suggestedShareUsers;
}

export function getUser( state, userId ) {
	return state.app.users.byId[ userId ];
}

export function isEditingCards( state ) {
	return state.app.editingCards;
}

export function getStaticStats( state ) {
	return state.app.staticStats.data;
}

export function isUsingTouch( state ) {
	return state.app.usingTouch;
}
