/**
 * External dependencies
 */
import { isString, isBoolean, isNumber, isArray, isPlainObject, keys, forEach, toString, cloneDeep, size } from 'lodash';

/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

export default function PrintR( { json } ) {
	return (
		<pre className="itsec-component-print-r">
			{ inspectDive( cloneDeep( json ) ) }
		</pre>
	);
}

function inspectDive( data, maxDepth = 10, depth = 0, showArrayHeader = true ) {
	if ( isString( data ) ) {
		if ( data.length === 0 ) {
			return <strong>[empty string]</strong>;
		}

		return data;
	}

	if ( isNumber( data ) ) {
		return <strong>{ `[number] ${ data }` }</strong>;
	}

	if ( isBoolean( data ) ) {
		return <strong>{ data ? '[boolean] true' : '[boolean] false' }</strong>;
	}

	if ( data === null || data === undefined ) {
		return <strong>null</strong>;
	}

	if ( isArray( data ) || isPlainObject( data ) ) {
		const retval = [];

		if ( showArrayHeader ) {
			retval.push( <strong key="header">{ 'Array' }</strong> );
		}

		if ( 0 === size( data ) ) {
			retval.push( '()' );

			return retval;
		}

		if ( depth === maxDepth ) {
			retval.push( `(${ data.length })` );

			return retval;
		}

		let maxLength = 0;

		for ( const key of keys( data ) ) {
			if ( key.length > maxLength ) {
				maxLength = key.length;
			}
		}

		const padding = pad( depth );
		forEach( data, ( value, key ) => {
			retval.push(
				<Fragment key={ key }>
					{ '\n' }
					{ padding }
					{ key }
					{ pad( maxLength - toString( key ).length, ' ' ) }
					{ '  ' }
					<strong>=&gt;</strong>
					{ ' ' }
					{ inspectDive( value, maxDepth, depth + 1 ) }
				</Fragment>
			);
		} );

		return retval;
	}

	return <strong>[*]</strong>;
}

function pad( depth, padding = '    ' ) {
	let ret = '';

	for ( let i = 0; i <= depth; i++ ) {
		ret += padding;
	}

	return ret;
}
