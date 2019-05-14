/**
 * WordPress dependencies
 */
import { Button, Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';

function EmptyState( { openEditCards } ) {
	return (
		<div className="itsec-card-grid-empty-state">
			<Button onClick={ openEditCards }>
				<Dashicon icon="plus-alt" size={ 60 } />
				<span>{ __( 'Add Security Cards', 'ithemes-security-pro' ) }</span>
			</Button>
		</div>
	);
}

export default compose( [
	withDispatch( ( dispatch ) => ( {
		openEditCards: dispatch( 'ithemes-security/dashboard' ).openEditCards,
	} ) ),
] )( EmptyState );
