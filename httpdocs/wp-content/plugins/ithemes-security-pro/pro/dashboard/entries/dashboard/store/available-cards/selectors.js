/**
 * External dependencies.
 */
import createSelector from 'rememo';
import { find, get } from 'lodash';

export const getAvailableCards = createSelector(
	( state ) => Object.values( state.availableCards ),
	( state ) => [ state.availableCards ]
);

export function getAvailableCard( state, cardSlug ) {
	return state.availableCards[ cardSlug ];
}

export const getAvailableCardBySelf = createSelector(
	( state, self ) => find( state.availableCards, ( card ) => get( card, [ '_links', 'self', 0, 'href' ] ) === self ),
	( state ) => [ state.availableCards ]
);
