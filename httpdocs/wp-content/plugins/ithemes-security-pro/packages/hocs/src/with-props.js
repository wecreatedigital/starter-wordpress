/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Higher-order component that applies props to the inner component.
 *
 * @param {Object} props
 *
 * @return {WPComponent} Debounced component.
 */
export default function withProps( props ) {
	return createHigherOrderComponent( ( WrappedComponent ) => {
		return class Wrapper extends Component {
			render() {
				return <WrappedComponent { ...this.props } { ...props } />;
			}
		};
	}, 'withProps' );
}
