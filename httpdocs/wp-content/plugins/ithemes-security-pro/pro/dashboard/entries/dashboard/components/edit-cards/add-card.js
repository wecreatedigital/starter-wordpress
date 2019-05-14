/**
 * WordPress dependencies
 */
import { IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { makeApiUrlRelative } from '../../utils';

function AddCard( { ldo, cardAtLimit, isAdding, add } ) {
	return ! cardAtLimit && (
		<li className="itsec-edit-cards__card-choice itsec-edit-cards__card-choice--add">
			<span className="itsec-edit-cards__card-choice-title">{ ldo.title }</span>
			<IconButton disabled={ isAdding } onClick={ () => add( ldo.href ) }
				className="itsec-edit-cards__action itsec-edit-cards__action--add" label={ __( 'Add', 'ithemes-security-pro' ) }
				icon="plus" tooltip={ false } />
		</li>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		cardAtLimit: select( 'ithemes-security/dashboard' ).isCardAtDashboardLimit( props.dashboardId, props.ldo.aboutLink ),
		isAdding: select( 'ithemes-security/dashboard' ).isAddingCard( `edit-cards-add-${ props.ldo.aboutLink }-to-${ props.dashboardId }` ),
	} ) ),
	withDispatch( ( dispatch, props ) => ( {
		add( ep, card = {} ) {
			return dispatch( 'ithemes-security/dashboard' ).addDashboardCard(
				makeApiUrlRelative( ep ),
				card,
				`edit-cards-add-${ props.ldo.aboutLink }-to-${ props.dashboardId }`
			);
		},
	} ) ),
] )( AddCard );
