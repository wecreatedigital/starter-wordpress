/**
 * External dependencies
 */
import { has, keyBy, isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import { RECEIVE_AVAILABLE_CARDS } from './actions';
import { RECEIVE_DASHBOARD_CARDS } from '../cards/actions';

export default function availableCards( state = {}, action ) {
	switch ( action.type ) {
		case RECEIVE_AVAILABLE_CARDS:
			return {
				...state,
				...keyBy( action.cards, 'slug' ),
			};
		case RECEIVE_DASHBOARD_CARDS:
			const add = {};
			for ( const card of action.cards ) {
				if ( has( card, [ '_embedded', 'about', 0 ] ) ) {
					const slug = card._embedded.about[ 0 ].slug;

					if ( ! state[ slug ] ) {
						add[ slug ] = card._embedded.about[ 0 ];
					}
				}
			}

			if ( isEmpty( add ) ) {
				return state;
			}

			return {
				...state,
				...add,
			};
		default:
			return state;
	}
}
