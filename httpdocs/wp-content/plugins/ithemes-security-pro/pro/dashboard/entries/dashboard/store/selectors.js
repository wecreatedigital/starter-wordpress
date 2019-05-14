/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Returns true if resolution is in progress for the core selector of the given
 * name and arguments.
 *
 * @param {string} selectorName Core data selector name.
 * @param {...*}   args         Arguments passed to selector.
 *
 * @return {boolean} Whether resolution is in progress.
 */
export function isResolving( selectorName, ...args ) {
	return select( 'core/data' ).isResolving( 'ithemes-security/dashboard', selectorName, args );
}

export function isResolved( selectorName, ...args ) {
	return select( 'core/data' ).hasFinishedResolution( 'ithemes-security/dashboard', selectorName, args );
}
