/**
 * External dependencies
 */
import { isFunction } from 'lodash';
/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Higher-order component that allows for firing an action after certain props have changed.
 *
 * @param {...string|{prop: string, cb: Function}} prop Prop to listen to, or object with prop to listener to and callback to execute.
 * @param {Function} [cb] Function to call when prop changes.
 *
 * @return {WPComponent} Component with prop change listeners.
 */
export default function withPropChangeCallback( prop, cb ) {
	let listeners;

	if ( isFunction( cb ) ) {
		listeners = [ { prop, cb } ];
	} else {
		listeners = arguments;
	}

	return createHigherOrderComponent( ( WrappedComponent ) => {
		return class Wrapper extends Component {
			componentDidUpdate( prevProps ) {
				for ( const listener of listeners ) {
					if ( this.props[ listener.prop ] !== prevProps[ listener.prop ] ) {
						listener.cb( prevProps[ listener.prop ], this.props );
					}
				}
			}

			render() {
				return <WrappedComponent { ...this.props } />;
			}
		};
	}, 'withProps' );
}
