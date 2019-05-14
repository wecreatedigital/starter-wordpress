/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	receiveAvailableCards,
} from './actions';
import { apiFetch } from '../controls';

export function* getAvailableCards() {
	try {
		const cards = yield apiFetch( { path: '/ithemes-security/v1/dashboard-available-cards' } );
		yield receiveAvailableCards( cards );
	} catch ( e ) {
		yield receiveAvailableCards( [] );
	}
}

export function* getAvailableCard() {
	yield select( 'ithemes-security/dashboard' ).getAvailableCards();
}

export function* getAvailableCardBySelf() {
	yield select( 'ithemes-security/dashboard' ).getAvailableCards();
}
