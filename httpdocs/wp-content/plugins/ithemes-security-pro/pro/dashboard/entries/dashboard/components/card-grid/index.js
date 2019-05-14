/**
 * External dependencies
 */
import 'react-grid-layout/css/styles.css';
import 'react-resizable/css/styles.css';
import { Responsive } from 'react-grid-layout';
import { debounce } from 'lodash';

/**
 * WordPress dependencies
 */
import { compose, ifCondition, pure } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { withInterval } from 'packages/hocs/src';
import widthProvider from './width-provider';
import Card from '../card';
import EmptyState from '../card-grid/empty-state';
import { GRID_COLUMNS, BREAKPOINTS, areGridLayoutsEqual, transformApiLayoutToGrid, transformGridLayoutToApi, sortCardsToMatchLayout } from '../../utils';
import './style.scss';

const Grid = widthProvider( Responsive );

class CardGrid extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			layout: transformApiLayoutToGrid( props.dashboardId, props.cards, props.layout ),
			isMoving: false,
			breakpoint: 'wide',
			breakpointInitialized: false,
		};

		this.onLayoutChange = debounce( this._onLayoutChange, 1000 );
	}

	componentDidUpdate( prevProps ) {
		if ( this.props.layout !== prevProps.layout && ( this.props.dashboardId !== prevProps.dashboardId || this.props.cards.length !== prevProps.cards.length ) ) {
			const transformed = transformApiLayoutToGrid( this.props.dashboardId, this.props.cards, this.props.layout );

			this.setState( { layout: transformed } );
		}

		if ( this.props.dashboardId === prevProps.dashboardId && this.props.cards.length > prevProps.cards.length ) {
			// Converting between the two layouts allows us to detect the new card and add it to the layout. At this point in time, the card
			// is in the local state layout from RGL, but not yet in the API layout.
			const layout = transformApiLayoutToGrid( this.props.dashboardId, this.props.cards, transformGridLayoutToApi( this.props.dashboardId, this.state.layout ) );
			this.setState( { layout } );
			this.props.saveLayout( this.props.dashboardId, transformGridLayoutToApi( this.props.dashboardId, layout ) );
		}
	}

	componentWillUnmount() {
		this.onLayoutChange.cancel();
	}

	_onLayoutChange = ( _, newLayout ) => {
		if ( ! areGridLayoutsEqual( newLayout, this.state.layout ) ) {
			this.setState( { layout: newLayout } );
			const transformed = transformGridLayoutToApi( this.props.dashboardId, newLayout );
			this.props.saveLayout( this.props.dashboardId, transformed );
		}
	};

	onBreakpointChange = ( newBreakpoint ) => {
		this.setState( { breakpoint: newBreakpoint } );
	};

	onWidthBreakpoint = ( newBreakpoint ) => {
		if ( ! this.state.breakpointInitialized ) {
			this.setState( { breakpoint: newBreakpoint, breakpointInitialized: true } );
		}
	};

	onStartMove = () => {
		this.setState( { isMoving: true } );
	};

	onStopMove = () => {
		this.setState( { isMoving: false } );
	};

	render() {
		const { cards, dashboardId, usingTouch } = this.props;

		if ( ! cards.length ) {
			return <EmptyState />;
		}

		return (
			<Grid breakpoints={ BREAKPOINTS } cols={ GRID_COLUMNS } rowHeight={ 200 } draggableHandle=".itsec-card-header, .itsec-card--unknown, .itsec-card__drag-handle" measureBeforeMount
				layouts={ this.state.layout } onLayoutChange={ this.onLayoutChange } onBreakpointChange={ this.onBreakpointChange } onWidthBreakpoint={ this.onWidthBreakpoint }
				margin={ [ 20, 20 ] } isDraggable={ ! usingTouch } isResizable={ ! usingTouch } className={ this.state.isMoving ? 'itsec-card-grid--moving' : '' }
				onDragStart={ this.onStartMove } onDragStop={ this.onStopMove } onResizeStart={ this.onStartMove } onResizeStop={ this.onStopMove }>
				{ sortCardsToMatchLayout( cards, this.state.layout, this.state.breakpoint ).map( ( card ) => (
					<Card id={ card.id } dashboardId={ dashboardId } key={ card.id.toString() } />
				) ) }
			</Grid>
		);
	}
}

export default compose( [
	withSelect( ( select, props ) => ( {
		cards: select( 'ithemes-security/dashboard' ).getDashboardCards( props.dashboardId ),
		layout: select( 'ithemes-security/dashboard' ).getDashboardLayout( props.dashboardId ),
		usingTouch: select( 'ithemes-security/dashboard' ).isUsingTouch(),
		cardsLoaded: select( 'ithemes-security/dashboard' ).areCardsLoaded( props.dashboardId ),
		layoutLoaded: select( 'ithemes-security/dashboard' ).isLayoutLoaded( props.dashboardId ),
	} ) ),
	ifCondition( ( { cardsLoaded, layoutLoaded } ) => cardsLoaded && layoutLoaded ),
	pure,
	withDispatch( ( dispatch, props ) => ( {
		openEditCards: dispatch( 'ithemes-security/dashboard' ).openEditCards,
		saveLayout: dispatch( 'ithemes-security/dashboard' ).saveDashboardLayout,
		refresh() {
			dispatch( 'ithemes-security/dashboard' ).refreshDashboardCards( props.dashboardId );
		},
	} ) ),
	withInterval( 120 * 1000, ( { refresh } ) => refresh() ),
] )( CardGrid );
