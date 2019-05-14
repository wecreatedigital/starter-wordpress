/**
 * External dependencies
 */
import { sortBy } from 'lodash';
import memize from 'memize';

/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { withSelect, select as coreSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import AddCard from './add-card';
import RemoveCard from './remove-card';
import CloseButton from '../close-button';
import { getCardTitle } from '../../utils';
import './style.scss';

const sorted = memize( ( cards ) => sortBy( cards, [ ( card ) => {
	const config = coreSelect( 'ithemes-security/dashboard' ).getAvailableCard( card.card );

	return getCardTitle( card, config );
} ] ) );

/**
 * View to Edit Cards
 *
 * @param {number} dashboardId
 * @param {Array<Object>} cards
 * @param {Array<Object>} availableCardLDOs
 * @param {Function} close
 * @return {*} React element.
 * @constructor
 */
function EditCards( { dashboardId, cards, availableCardLDOs, close } ) {
	return (
		<div className="itsec-edit-cards">
			<CloseButton close={ close } />
			<header>
				<h3>{ __( 'Edit Cards', 'ithemes-security-pro' ) }</h3>
				<p>{ __( 'Add or remove cards on your dashboard.', 'ithemes-security-pro' ) }</p>
			</header>
			<section>
				<ul className="itsec-edit-cards__card-choices">
					{ availableCardLDOs.map( ( ldo ) => (
						<AddCard ldo={ ldo } key={ ldo.href } dashboardId={ dashboardId } />
					) ) }
					{ sorted( cards ).map( ( card ) => (
						<RemoveCard key={ card.id } card={ card } dashboardId={ dashboardId } />
					) ) }
				</ul>
			</section>
		</div>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		cards: select( 'ithemes-security/dashboard' ).getDashboardCards( props.dashboardId ),
		isAdding: select( 'ithemes-security/dashboard' ).isAddingCard( props.selected, {} ),
		availableCardLDOs: select( 'ithemes-security/dashboard' ).getDashboardAddableCardLDOs( props.dashboardId ),
	} ) ),
] )( EditCards );
