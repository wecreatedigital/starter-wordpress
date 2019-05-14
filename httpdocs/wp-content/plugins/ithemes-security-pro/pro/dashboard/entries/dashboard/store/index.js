/**
 * WordPress dependencies
 */
import { registerStore, combineReducers } from '@wordpress/data';

/**
 * Internal Dependencies.
 */
import { getConfigValue } from '../utils';
import controls from './controls';
import dashboard from './dashboard/reducer';
import * as dashboardActions from './dashboard/actions';
import * as dashboardSelectors from './dashboard/selectors';
import * as dashboardResolvers from './dashboard/resolvers';

import availableCards from './available-cards/reducer';
import * as availableCardsActions from './available-cards/actions';
import * as availableCardsSelectors from './available-cards/selectors';
import * as availableCardsResolvers from './available-cards/resolvers';

import cards from './cards/reducer';
import * as cardsActions from './cards/actions';
import * as cardsSelectors from './cards/selectors';
import * as cardsResolvers from './cards/resolvers';

import app from './app/reducer';
import * as appActions from './app/actions';
import * as appSelectors from './app/selectors';
import * as appResolvers from './app/resolvers';

import user from './user/reducer';
import * as userActions from './user/actions';
import * as userSelectors from './user/selectors';
import * as userResolvers from './user/resolvers';

const config = {
	reducer: combineReducers( {
		dashboard,
		availableCards,
		cards,
		app,
		user,
	} ),
	controls: {
		...controls,
	},
	actions: {
		...dashboardActions,
		...availableCardsActions,
		...cardsActions,
		...appActions,
		...userActions,
	},
	selectors: {
		...dashboardSelectors,
		...availableCardsSelectors,
		...cardsSelectors,
		...appSelectors,
		...userSelectors,
	},
	resolvers: {
		...dashboardResolvers,
		...availableCardsResolvers,
		...cardsResolvers,
		...appResolvers,
		...userResolvers,
	},
};

const store = registerStore( 'ithemes-security/dashboard', config );

if ( parseInt( getConfigValue( 'primary_dashboard' ) ) ) {
	store.dispatch( appActions.viewDashboard( parseInt( getConfigValue( 'primary_dashboard' ) ) ) );
} else {
	store.dispatch( appActions.viewCreateDashboard() );
}

export default store;
