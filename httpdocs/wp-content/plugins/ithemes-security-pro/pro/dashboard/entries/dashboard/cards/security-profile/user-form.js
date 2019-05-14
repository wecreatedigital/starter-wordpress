/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { compose, withState } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { AsyncSelect } from 'packages/components/src';

const loadUsers = ( search ) => new Promise( ( resolve, reject ) => {
	apiFetch( {
		path: addQueryArgs( '/wp/v2/users', { search, per_page: 100 } ),
	} )
		.then( ( response ) => resolve( response.map( ( user ) => ( { value: user.id, label: user.name } ) ) ) )
		.catch( reject );
} );

function UserForm( { card, save, userInput, setState } ) {
	return (
		<section className="itsec-card-security-profile__select-user">
			<label htmlFor={ `itsec-card-security-profile__select-user-dropdown--${ card.id }` }>
				{ __( 'Select a User', 'ithemes-security-pro' ) }
			</label>
			<form className="itsec-card-security-profile__select-form">
				<AsyncSelect
					addErrorBoundary={ false }
					className="itsec-card-security-profile__select-user-dropdown"
					classNamePrefix="itsec-card-security-profile__select-user-dropdown"
					inputId={ `itsec-card-security-profile__select-user-dropdown--${ card.id }` }
					cacheOptions defaultOptions loadOptions={ loadUsers }
					value={ userInput } onChange={ ( option ) => setState( { userInput: option } ) }
					maxMenuHeight={ 150 }
				/>
				<div className="itsec-card-security-profile__select-user-save-container">
					<Button isLarge onClick={ () => save( {
						...card,
						settings: {
							...( card.settings || {} ),
							user: userInput.value,
						},
					} ) }>
						{ __( 'Select', 'ithemes-security-pro' ) }
					</Button>
				</div>
			</form>
			<p className="description">
				{ __( 'Select a user to monitor with this card.', 'ithemes-security-pro' ) }
			</p>
		</section>
	);
}

export default compose( [
	withState( { userInput: 0 } ),
	withDispatch( ( dispatch, ownProps ) => ( {
		unPin() {
			return dispatch( 'ithemes-security/dashboard' ).removeDashboardCard( ownProps.dashboardId, ownProps.card );
		},
		save( card ) {
			return dispatch( 'ithemes-security/dashboard' ).saveDashboardCard( ownProps.dashboardId, card );
		},
	} ) ),
] )( UserForm );
