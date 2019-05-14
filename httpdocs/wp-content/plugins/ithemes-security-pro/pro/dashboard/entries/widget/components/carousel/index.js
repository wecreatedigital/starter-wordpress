/**
 * External dependencies
 */
import classnames from 'classnames';
import { TransitionGroup, CSSTransition } from 'react-transition-group';

/**
 * WordPress dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { withWidth } from 'packages/hocs/src';
import Card from 'pro/dashboard/entries/dashboard/components/card';
import { sortCardsToMatchApiLayout } from 'pro/dashboard/entries/dashboard/utils';
import Loader from 'packages/components/src/loader';
import './style.scss';

class Carousel extends Component {
	render() {
		const { cards, layout, dashboardId, width } = this.props;

		const isLoaded = cards.length > 0 && layout !== undefined;
		const offset = width < 400 ? 100 : 140;
		const style = {
			width: `${ width - offset }px`,
			height: '400px',
		};

		return (
			<div className={ classnames( 'itsec-dashboard-widget-carousel', { 'itsec-dashboard-widget-carousel--loaded': isLoaded } ) }>
				<TransitionGroup component={ null }>
					{ ! isLoaded && (
						<CSSTransition key={ 'loader' } timeout={ 300 } classNames="itsec-carousel-load-cards-">
							<div className="itsec-dashboard-widget-carousel__loader" style={ style }>
								<Loader />
							</div>
						</CSSTransition>
					) }
				</TransitionGroup>

				<TransitionGroup component={ null }>
					{ isLoaded && sortCardsToMatchApiLayout( cards, layout ).map( ( card ) => (
						<CSSTransition key={ card.id } timeout={ 500 } classNames="itsec-carousel-load-cards-">
							<Card id={ card.id } dashboardId={ dashboardId } style={ style } />
						</CSSTransition>
					) ) }
				</TransitionGroup>
			</div>
		);
	}
}

export default compose( [
	withSelect( ( select, props ) => ( {
		cards: select( 'ithemes-security/dashboard' ).getDashboardCards( props.dashboardId ),
		layout: select( 'ithemes-security/dashboard' ).getDashboardLayout( props.dashboardId ),
	} ) ),
	withWidth,
] )( Carousel );

