/**
 * External dependencies
 */
import { isPlainObject } from 'lodash';

/**
 * Internal dependencies
 */
import WPError from './wp-error';
import ErrorResponse from './error-response';

export function makeUrlRelative( baseUrl, target ) {
	let rel = target.replace( baseUrl, '' );

	if ( rel.charAt( 0 ) !== '/' ) {
		rel = '/' + rel;
	}

	return rel;
}

export function shortenNumber( number ) {
	if ( number <= 999 ) {
		return number.toString();
	}

	if ( number <= 9999 ) {
		const dec = ( number / 1000 ),
			fixed = dec.toFixed( 1 );

		if ( fixed.charAt( fixed.length - 1 ) === '0' ) {
			return fixed.replace( '.0', 'k' );
		}

		return `${ fixed }k`;
	}

	if ( number <= 99999 ) {
		return number.toString().substring( 0, 2 ) + 'k';
	}

	if ( number <= 999999 ) {
		return number.toString().substring( 0, 3 ) + 'k';
	}

	if ( number <= 9999999 ) {
		const dec = ( number / 1000000 ),
			fixed = dec.toFixed( 1 );

		if ( fixed.charAt( fixed.length - 1 ) === '0' ) {
			return fixed.replace( '.0', 'm' );
		}

		return `${ fixed }m`;
	}

	if ( number <= 99999999 ) {
		return number.toString().substring( 0, 2 ) + 'm';
	}

	if ( number <= 999999999 ) {
		return number.toString().substring( 0, 3 ) + 'm';
	}

	if ( number <= 9999999999 ) {
		const dec = ( number / 1000000000 ),
			fixed = dec.toFixed( 1 );

		if ( fixed.charAt( fixed.length - 1 ) === '0' ) {
			return fixed.replace( '.0', 'b' );
		}

		return `${ fixed }b`;
	}

	return number;
}

/**
 * Is the given value likely a WP Error object.
 *
 * @param {*} object
 * @return {boolean} Whether it was an error.
 */
export function isWPError( object ) {
	if ( ! isPlainObject( object ) ) {
		return false;
	}

	const keys = Object.keys( object );

	if ( keys.length !== 2 ) {
		return false;
	}

	return keys.includes( 'errors' ) && keys.includes( 'error_data' );
}

export function isApiError( object ) {
	if ( ! isPlainObject( object ) ) {
		return false;
	}

	const keys = Object.keys( object );

	if ( keys.length !== 3 && keys.length !== 4 ) {
		return false;
	}

	if ( keys.length === 4 && ! keys.includes( 'additional_errors' ) ) {
		return false;
	}

	return keys.includes( 'code' ) && keys.includes( 'message' ) && keys.includes( 'data' );
}

/**
 * Cast to a WPError instance.
 *
 * @param {*} object
 * @return {WPError} WPError instance.
 */
export function castWPError( object ) {
	if ( isWPError( object ) ) {
		return WPError.fromPHPObject( object );
	}

	if ( isApiError( object ) ) {
		return WPError.fromApiError( object );
	}

	return new WPError();
}

/**
 * Convert an entries iterator to an object.
 *
 * @param {iterator} entries
 *
 * @return {Object} Object with entry[0] as the key and entry[1] as the value.
 */
export function entriesToObject( entries ) {
	const obj = {};

	for ( const [ key, val ] of entries ) {
		obj[ key ] = val;
	}

	return obj;
}

/**
 * Convert a response object from @wordpress/apiFetch to an Error object.
 *
 * @param {Object} response
 */
export default function responseToError( response ) {
	if ( response instanceof Error ) {
		throw response;
	}

	throw new ErrorResponse( response );
}

export const MYSTERY_MAN_AVATAR = 'https://secure.gravatar.com/avatar/d7a973c7dab26985da5f961be7b74480?s=96&d=mm&f=y&r=g';
