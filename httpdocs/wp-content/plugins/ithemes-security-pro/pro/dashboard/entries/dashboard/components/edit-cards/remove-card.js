/**
 * WordPress dependencies
 */
import { IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCardTitle } from 'pro/dashboard/entries/dashboard/utils';

function RemoveCard( { card, config, remove } ) {
	return (
		<li className="itsec-edit-cards__card-choice itsec-edit-cards__card-choice--remove">
			<IconButton className="itsec-edit-cards__action itsec-edit-cards__action--remove" label={ __( 'Remove', 'ithemes-security-pro' ) }
				icon="no" tooltip={ false } onClick={ remove } />
			<span className="itsec-edit-cards__card-choice-title">
				{ getCardTitle( card, config ) }
			</span>
		</li>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		config: select( 'ithemes-security/dashboard' ).getAvailableCard( props.card.card ),
	} ) ),
	withDispatch( ( dispatch, props ) => ( {
		remove() {
			return dispatch( 'ithemes-security/dashboard' ).removeDashboardCard( props.dashboardId, props.card );
		},
	} ) ),
] )( RemoveCard );
