/**
 * External dependencies
 */
import { get, keyBy, times, zipObject } from 'lodash';
import memize from 'memize';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { applyFilters } from './hooks';
import { makeUrlRelative, MYSTERY_MAN_AVATAR } from 'packages/utils/src';

export const BREAKPOINTS = Object.freeze( {
	huge: 1440,
	wide: 1280,
	large: 960,
	medium: 782,
	small: 600,
	mobile: 480,
} );
export const BREAKPOINT_ORDER = Object.freeze( [
	'huge',
	'wide',
	'large',
	'medium',
	'small',
	'mobile',
] );

export const GRID_COLUMNS = Object.freeze( {
	huge: 8,
	wide: 6,
	large: 4,
	medium: 2,
	small: 2,
	mobile: 1,
} );

const OPTIONAL_LAYOUT_KEYS = [ 'minW', 'minH', 'maxW', 'maxH' ];

export function transformGridLayoutToApi( dashboardId, layouts ) {
	const store = select( 'ithemes-security/dashboard' ),
		cards = {};

	for ( const breakpoint in layouts ) {
		if ( ! layouts.hasOwnProperty( breakpoint ) ) {
			continue;
		}

		const layout = layouts[ breakpoint ];

		for ( const item of layout ) {
			const card = store.getDashboardCard( parseInt( item.i ) );

			if ( ! card ) {
				continue;
			}

			if ( cards[ card.id ] ) {
				cards[ card.id ].size[ breakpoint ] = {
					w: item.w,
					h: item.h,
				};
				cards[ card.id ].position[ breakpoint ] = {
					x: ( item.x && item.x !== Infinity ) ? item.x : 0,
					y: ( item.y && item.y !== Infinity ) ? item.y : 0,
				};
			} else {
				cards[ card.id ] = {
					id: card.id,
					card: card.card,
					size: {
						[ breakpoint ]: {
							w: item.w,
							h: item.h,
						},
					},
					position: {
						[ breakpoint ]: {
							x: ( item.x && item.x !== Infinity ) ? item.x : 0,
							y: ( item.y && item.y !== Infinity ) ? item.y : 0,
						},
					},
				};
			}
		}
	}

	return { cards: Object.values( cards ) };
}

export function transformApiLayoutToGrid( dashboardId, cards, { cards: layout = {} } ) {
	const layouts = zipObject( Object.keys( BREAKPOINTS ), times( Object.keys( BREAKPOINTS ).length, () => [] ) ),
		seen = {};

	for ( const card of layout ) {
		seen[ card.id ] = true;

		const config = select( 'ithemes-security/dashboard' ).getAvailableCard( card.card );

		if ( config || card.card === 'unknown' ) {
			for ( const bp of BREAKPOINT_ORDER ) {
				layouts[ bp ].push( transformApiLayoutToGridForCard( dashboardId, card, config, bp, layouts[ bp ] ) );
			}
		}
	}

	// If there is a new card that isn't in the layout yet, we need to add it manually so it can be in a better spot.
	if ( Object.keys( seen ).length < cards.length ) {
		for ( const card of cards ) {
			if ( ! seen[ card.id ] ) {
				const config = select( 'ithemes-security/dashboard' ).getAvailableCard( card.card );

				if ( config ) {
					for ( const bp of BREAKPOINT_ORDER ) {
						layouts[ bp ].push( transformApiLayoutToGridForCard( dashboardId, card, config, bp, layouts[ bp ] ) );
					}
				}
			}
		}
	}

	return layouts;
}

/**
 * Transform the layout information from the API to a format compatible for the react-grid-layout library.
 *
 * @param {number} dashboardId
 * @param {Object} card
 * @param {Object} config
 * @param {string} breakpoint
 * @param {Array<Object>} [layout] Layout being inserted into. Used to better determine a slot for a card without a position.
 * @return {{i: string, x: *, y: *, w: *, h: *}} RGL layout for a single card.
 */
export function transformApiLayoutToGridForCard( dashboardId, card, config, breakpoint, layout ) {
	if ( ! BREAKPOINTS[ breakpoint ] ) {
		return undefined;
	}

	const item = {
		i: card.id.toString(),
		x: get( card, [ 'position', breakpoint, 'x' ] ),
		y: get( card, [ 'position', breakpoint, 'y' ] ),
		w: get( card, [ 'size', breakpoint, 'w' ], get( config, [ 'size', 'defaultW' ], 1 ) ),
		h: get( card, [ 'size', breakpoint, 'h' ], get( config, [ 'size', 'defaultH' ], 2 ) ),
	};

	if ( config && config.size ) {
		for ( const key of OPTIONAL_LAYOUT_KEYS ) {
			if ( config.size.hasOwnProperty( key ) ) {
				item[ key ] = config.size[ key ];
			}
		}
	}

	if ( item.minW && item.minW > item.w ) {
		item.w = item.minW;
	}

	if ( item.minH && item.minH > item.h ) {
		item.h = item.minH;
	}

	if ( item.maxW && item.w > item.maxW ) {
		item.w = item.maxW;
	}

	if ( item.maxH && item.h > item.maxH ) {
		item.h = item.maxH;
	}

	if ( layout && typeof item.x === 'undefined' && typeof item.x === 'undefined' ) {
		const slot = findSlot( GRID_COLUMNS[ breakpoint ], layout, { w: item.w, h: item.h } );

		if ( slot ) {
			item.x = slot.x;
			item.y = slot.y;
		}
	}

	item.x = ( typeof item.x === 'undefined' || item.x === null ) ? 0 : item.x;
	item.y = ( typeof item.y === 'undefined' || item.y === null ) ? Infinity : item.y;

	if ( ! select( 'ithemes-security/dashboard' ).canEditDashboard( dashboardId ) ) {
		item.static = true;
	}

	if ( card.card === 'unknown' ) {
		item.isResizable = false;
	}

	if ( item.minW === item.maxW && item.minH === item.maxH ) {
		item.isResizable = false;
	}

	return item;
}

/**
 * Find the first slot available from left-to-right, top-to-bottom for a card of given size.
 * This is preferable to letting the react-grid-layout handle it because it only does vertical
 * packing.
 *
 * @param {number} numColumns Number of columns wide that are supported.
 * @param {Array<Object>} layout The full layout of other items.
 * @param {{w: number, h: number}} size The size of the item.
 * @return {{x: number, y: number}} Slot position if found, or null if none available.
 */
export function findSlot( numColumns, layout, size ) {
	/*		  0  1  2  3
			  ↓  ↓  ↓  ↓
		[
	0 ->	[ 1, 1, 1, 0 ]
	1 ->	[ 1, 1, 1, 0 ]
		]
	 */
	const grid = [];

	for ( const item of layout ) {
		const { x, y, w, h } = item;

		for ( let iH = 0; iH < h; iH++ ) {
			if ( ! grid[ iH ] ) {
				grid[ iH ] = new Array( numColumns ).fill( 0 );
			}

			for ( let iW = 0; iW < w; iW++ ) {
				if ( ! grid[ iH + y ] ) {
					grid[ iH + y ] = new Array( numColumns ).fill( 0 );
				}

				grid[ iH + y ][ iW + x ] = 1;
			}
		}
	}

	const { w, h } = size;

	for ( let gH = 0; gH < grid.length; gH++ ) {
		widthLoop: for ( let gW = 0; gW < numColumns; gW++ ) {
			if ( grid[ gH ] && 1 === grid[ gH ][ gW ] ) {
				continue;
			}

			for ( let iH = 0; iH < h; iH++ ) {
				for ( let iW = 0; iW < w; iW++ ) {
					if ( gH + iH > grid.length || gW + iW >= numColumns || ( grid[ gH + iH ] && 1 === grid[ gH + iH ][ gW + iW ] ) ) {
						continue widthLoop;
					}
				}
			}

			return { x: gW, y: gH };
		}
	}

	return {
		x: 0,
		y: grid.length,
	};
}

/**
 * Compare two grid item's layout properties.
 *
 * We already assume that the IDs are the same. We ignore any properties
 * that couldn't be changed by the Grid.
 *
 * @param {Object} a
 * @param {Object} b
 * @return {boolean} If they are equal
 */
function areGridLayoutItemsEqual( a, b ) {
	return (
		a.x === b.x &&
		a.y === b.y &&
		a.w === b.w &&
		a.h === b.h
	);
}

function _areGridLayoutsEqual( a, b ) {
	if ( a === b ) {
		return true;
	}

	if ( Object.keys( a ).length !== Object.keys( b ).length ) {
		return false;
	}

	const map = new Map();

	for ( let i = 0; i < BREAKPOINT_ORDER.length; i++ ) {
		const breakpoint = BREAKPOINT_ORDER[ i ];

		// If neither layout has this breakpoint, then skip it.
		if ( ! a[ breakpoint ] && ! b[ breakpoint ] ) {
			continue;
		}

		// If only one of the layouts has this breakpoint, then the layouts can't be equal.
		if ( ( ! a[ breakpoint ] && b[ breakpoint ] ) || ( a[ breakpoint ] && ! b[ breakpoint ] ) ) {
			return false;
		}

		// If the number of items in each breakpoint are different, then the layouts can't be equal.
		if ( a[ breakpoint ].length !== b[ breakpoint ].length ) {
			return false;
		}

		// Build a map keyed by all the item's "i" property in layout A.
		for ( let j = 0; j < a[ breakpoint ].length; j++ ) {
			map.set( a[ breakpoint ][ j ].i, a[ breakpoint ][ j ] );
		}

		// Loop over all items in layout B.
		for ( let k = 0; k < b[ breakpoint ].length; k++ ) {
			const aItem = map.get( b[ breakpoint ][ k ].i );

			// If there is no corresponding aItem, then the layouts can't be equal.
			if ( ! aItem ) {
				return false;
			}

			// If the two items aren't equal, then the layouts aren't equal.
			if ( ! areGridLayoutItemsEqual( aItem, b[ breakpoint ][ k ] ) ) {
				return false;
			}

			// Remove the item from the map since we've seen it and there can't be duplicate "i"s.
			map.delete( aItem.i );
		}

		// If the map isn't empty, then there are a items without a b counterpart.
		if ( map.size > 0 ) {
			return false;
		}
	}

	return true;
}

export const areGridLayoutsEqual = memize( _areGridLayoutsEqual );

/**
 * Sort card objects to be in the same order as their layout defines.
 *
 * This is so that the tab order can match the visual order.
 *
 * @param {Array<Object>} cards Card array.
 * @param {Object} layout Grid Layout
 * @param {string} breakpoint The breakpoint we are displaying.
 *
 * @return {Array<Object>} New array with card objects sorted by layout.
 */
function _sortCardsToMatchLayout( cards, layout, breakpoint ) {
	const keyedLayout = keyBy( layout[ breakpoint ], 'i' );

	const toSort = [ ...cards ];

	toSort.sort( function( a, b ) {
		const aId = a.id.toString(),
			bId = b.id.toString();

		if ( ! keyedLayout[ aId ] && ! keyedLayout[ bId ] ) {
			return 0;
		}

		if ( keyedLayout[ aId ] && ! keyedLayout[ bId ] ) {
			return 1;
		}

		if ( ! keyedLayout[ aId ] && keyedLayout[ bId ] ) {
			return -1;
		}

		const
			aY = keyedLayout[ aId ].y,
			bY = keyedLayout[ bId ].y,
			aX = keyedLayout[ aId ].x,
			bX = keyedLayout[ bId ].x;

		if ( aY > bY ) {
			return 1;
		} else if ( aY < bY ) {
			return -1;
		} else if ( aX > bX ) {
			return 1;
		} else if ( aX < bX ) {
			return -1;
		}

		return 0;
	} );

	return toSort;
}

export const sortCardsToMatchLayout = memize( _sortCardsToMatchLayout );

function _sortCardsToMatchApiLayout( cards, layout ) {
	const keyedLayout = keyBy( layout.cards, 'id' );
	const toSort = [ ...cards ];

	toSort.sort( function( a, b ) {
		const aId = a.id,
			bId = b.id;

		if ( ! keyedLayout[ aId ] && ! keyedLayout[ bId ] ) {
			return 0;
		}

		if ( keyedLayout[ aId ] && ! keyedLayout[ bId ] ) {
			return 1;
		}

		if ( ! keyedLayout[ aId ] && keyedLayout[ bId ] ) {
			return -1;
		}

		const
			aY = keyedLayout[ aId ].position.mobile.y,
			bY = keyedLayout[ bId ].position.mobile.y,
			aX = keyedLayout[ aId ].position.mobile.x,
			bX = keyedLayout[ bId ].position.mobile.x;

		if ( aY > bY ) {
			return 1;
		} else if ( aY < bY ) {
			return -1;
		} else if ( aX > bX ) {
			return 1;
		} else if ( aX < bX ) {
			return -1;
		}

		return 0;
	} );

	return toSort;
}

export const sortCardsToMatchApiLayout = memize( _sortCardsToMatchApiLayout );

/**
 * Get the title for a card.
 *
 * @param {Object} card
 * @param {Object} config
 *
 * @return {string} The card title.
 */
export function getCardTitle( card, config ) {
	if ( ! config ) {
		return __( 'Unknown Card', 'ithemes-security-pro' );
	}

	let title = config.label;

	/**
	 * Filter the card title for a particular config.
	 *
	 * @param {string} title The card title.
	 * @param {Object} card The card instance object.
	 */
	title = applyFilters( `dashboard.getCardTitle.${ config.slug }`, title, card );

	/**
	 * Filter the card title.
	 *
	 * @param {string} title The card title.
	 * @param {Object} card The card instance object.
	 * @param {Object} config The card configuration object.
	 */
	return applyFilters( 'dashboard.getCardTitle', title, card, config );
}

export function getConfigValue( path ) {
	return get( window.iThemesSecurityDashboard, path );
}

export function makeApiUrlRelative( url ) {
	return makeUrlRelative( getConfigValue( 'rootURL' ), url );
}

export function debugChange( prevProps, prevState, thisProps, thisState ) {
	Object.entries( thisProps ).forEach( ( [ key, val ] ) =>
		// eslint-disable-next-line no-console
		prevProps[ key ] !== val && console.log( `Prop '${ key }' changed` )
	);
	Object.entries( thisState ).forEach( ( [ key, val ] ) =>
		// eslint-disable-next-line no-console
		prevState[ key ] !== val && console.log( `State '${ key }' changed` )
	);
}

export function getAvatarUrl( user ) {
	return get( user, [ 'avatar_urls', 96 ], MYSTERY_MAN_AVATAR );
}
