/**
 * WordPress dependencies
 */
import { Component, findDOMNode } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/*
 * A simple HOC that provides facility for listening to container resizes.
 */
const withWidth = createHigherOrderComponent( ( WrappedComponent ) => {
	return class WithWidth extends Component {
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
}, 'withWidth' );

export default withWidth;
