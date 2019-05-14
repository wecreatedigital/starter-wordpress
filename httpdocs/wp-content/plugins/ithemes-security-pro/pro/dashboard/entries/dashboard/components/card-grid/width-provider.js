/**
 * External dependencies
 */
import { Responsive } from 'react-grid-layout';

/**
 * WordPress dependencies
 */
import { Component, findDOMNode } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { BREAKPOINTS } from '../../utils';

/*
 * A simple HOC that provides facility for listening to container resizes.
 */
const widthProvider = createHigherOrderComponent( ( WrappedComponent ) => {
	return class WidthProvider extends Component {
		static defaultProps = {
			measureBeforeMount: false,
		};

		state = {
			width: 1280,
		};

		mounted = false;
		ref = null;

		componentDidMount() {
			this.mounted = true;

			window.addEventListener( 'resize', this.onWindowResize );
			document.getElementById( 'collapse-button' ).addEventListener( 'click', this.onWindowResize );
			// Call to properly set the breakpoint and resize the elements.
			// Note that if you're doing a full-width element, this can get a little wonky if a scrollbar
			// appears because of the grid. In that case, fire your own resize event, or set `overflow: scroll` on your body.
			this.onWindowResize();
		}

		componentWillUnmount() {
			this.mounted = false;
			window.removeEventListener( 'resize', this.onWindowResize );
			document.getElementById( 'collapse-button' ).removeEventListener( 'click', this.onWindowResize );
		}

		onWindowResize = () => {
			if ( ! this.mounted ) {
				return;
			}

			// eslint-disable-next-line react/no-find-dom-node
			const node = findDOMNode( this );

			if ( node instanceof window.HTMLElement ) {
				const width = node.offsetWidth;
				this.setState( { width } );

				this.props.onWidthBreakpoint( Responsive.utils.getBreakpointFromWidth( BREAKPOINTS, width ) );
			}
		};

		render() {
			const { measureBeforeMount, ...rest } = this.props;
			if ( measureBeforeMount && ! this.mounted ) {
				return (
					<div className={ this.props.className } style={ this.props.style } />
				);
			}

			return <WrappedComponent { ...rest } width={ this.state.width + 20 } />;
		}
	};
}, 'widthProvider' );

export default widthProvider;
