/**
 * External dependencies
 */
import memize from 'memize';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose, withState } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { CheckboxControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { AsyncSelect } from 'packages/components/src';

const loadUsers = memize( ( exclude = [] ) => ( search ) => new Promise( ( resolve, reject ) => {
	apiFetch( {
		path: addQueryArgs( '/wp/v2/users', { search, per_page: 100, exclude } ),
	} )
		.then( ( response ) => resolve( response.map( ( user ) => ( { value: user.id, label: user.name, user } ) ) ) )
		.catch( reject );
} ) );

const AddedUser = compose( [
	withSelect( ( select, props ) => ( {
		user: select( 'ithemes-security/dashboard' ).getUser( props.userId ),
	} ) ),
] )( ( { user } ) => (
	<li>{ user.name }</li>
) );

const excludedUsers = memize( ( sharing = [] ) => [].concat( ...sharing.map( ( share ) => share.users || [] ) ) );

function UserTab( { suggested, dashboard, share = { type: 'user', users: [] }, onChange, selectSearch, selectedUser, setState, receiveUser } ) {
	const addUser = ( e ) => {
		e.preventDefault();

		if ( ! selectedUser ) {
			return;
		}

		receiveUser( selectedUser.user );
		onChange( { ...share, users: [ ...share.users, selectedUser.value ] } );
		setState( { selectedUser: false, selectSearch: '' } );
	};

	const exclude = excludedUsers( dashboard.sharing ).concat( dashboard.created_by );
	const suggestedFiltered = suggested.filter( ( user ) => ! exclude.includes( user.id ) );

	return (
		<Fragment>
			{ suggestedFiltered.length > 0 && (
				<fieldset className="itsec-share-dashboard__suggested-users">
					<legend>{ __( 'Suggested Users', 'ithemes-security-pro' ) }</legend>
					<ul>
						{ suggestedFiltered.map( ( user ) => (
							<li key={ user.id }>
								<CheckboxControl label={ user.name } checked={ share.users.includes( user.id ) }
									onChange={ ( checked ) => checked ?
										onChange( { ...share, users: [ ...share.users, user.id ] } ) :
										onChange( { ...share, users: share.users.filter( ( userId ) => userId !== user.id ) } )
									}
								/>
							</li>
						) ) }
					</ul>
				</fieldset>
			) }
			<fieldset className="itsec-share-dashboard__add-users">
				<legend>{ __( 'All Users', 'ithemes-security-pro' ) }</legend>
				<ul>
					{ share.users.filter( ( userId ) => ! suggested.some( ( suggestion ) => suggestion.id === userId ) ).map( ( userId ) => (
						<AddedUser key={ userId } userId={ userId } />
					) ) }
				</ul>

				<label className="itsec-share-dashboard__add-users-select" htmlFor="itsec-share-dashboard__add-users-select">
					{ __( 'Select a User', 'ithemes-security-pro' ) }
				</label>

				<div className="itsec-share-dashboard__add-users-fields">
					<AsyncSelect
						className="itsec-share-dashboard__add-users-select-dropdown"
						inputId="itsec-share-dashboard__add-users-select"
						cacheOptions defaultOptions loadOptions={ loadUsers( exclude ) }
						value={ selectedUser } onChange={ ( option ) => setState( { selectedUser: option } ) }
						inputValue={ selectSearch } onInputChange={ ( newSelect ) => setState( { selectSearch: newSelect } ) }
						maxMenuHeight={ 150 } menuPlacement="top"
					/>

					<div className="itsec-share-dashboard__add-users-trigger">
						<Button isLarge onClick={ addUser } disabled={ ! selectedUser }>
							{ __( 'Select', 'ithemes-security-pro' ) }
						</Button>
					</div>
				</div>
			</fieldset>
		</Fragment>
	);
}

export default compose( [
	withState( { selectedUser: undefined } ),
	withSelect( ( select, props ) => ( {
		suggested: select( 'ithemes-security/dashboard' ).getSuggestedShareUsers(),
		dashboard: select( 'ithemes-security/dashboard' ).getDashboardForEdit( props.dashboardId ),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		receiveUser: dispatch( 'ithemes-security/dashboard' ).receiveUser,
	} ) ),
] )( UserTab );
