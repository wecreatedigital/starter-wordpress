/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Higher-order component that debounces an action.
 *
 * @Link https://github.com/deepsweet/hocs/tree/master/packages/debounce-handler (MIT)
 *
 * @param {string} handlerName
 * @param {number|Function} wait
 * @param {Object} [options]
 * @return {WPComponent} Debounced component.
 */
export default function withDebounceHandler( handlerName, wait, options = {} ) {
	return createHigherOrderComponent( ( WrappedComponent ) => {
		return class Wrapper extends Component {
			constructor() {
				super( ...arguments );

				this.debouncedPropInvoke = debounce(
					( ...args ) => this.props[ handlerName ]( ...args ),
					typeof wait === 'function' ? wait( this.props ) : wait,
					options
				);

				this.handler = ( e, ...rest ) => {
					if ( e && typeof e.persist === 'function' ) {
						e.persist();
					}

					return this.debouncedPropInvoke( e, ...rest );
				};
			}

			componentWillUnmount() {
				this.debouncedPropInvoke.cancel();
			}

			render() {
				const props = {
					...this.props,
					[ handlerName ]: this.handler,
				};

				return <WrappedComponent { ...props } />;
			}
		};
	}, 'withDebounceHandler' );
}
