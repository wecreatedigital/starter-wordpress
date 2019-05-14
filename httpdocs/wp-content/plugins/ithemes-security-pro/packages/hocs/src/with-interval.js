/**
 * External dependencies
 */
import { isFunction } from 'lodash';

/**
 * WordPress Dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

export default function withInterval( delay, cb ) {
	let intervals;

	if ( isFunction( cb ) ) {
		intervals = [ { delay, cb } ];
	} else {
		intervals = delay;
	}

	return createHigherOrderComponent( ( WrappedComponent ) => {
		return class Wrapper extends Component {
			constructor() {
				super( ...arguments );

				this.intervalIds = [];
			}

			componentDidMount() {
				for ( const interval of intervals ) {
					( ( callback ) => {
						this.intervalIds.push( setInterval( () => callback( this.props ), interval.delay ) );
					} )( interval.cb );
				}
			}

			componentWillUnmount() {
				this.intervalIds.forEach( clearInterval );
			}

			render() {
				return <WrappedComponent { ...this.props } />;
			}
		};
	}, 'withInterval' );
}
