/**
 * External dependencies
 */
import { once, noop } from 'lodash';

/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { compose, withState } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { withPropChangeCallback } from 'packages/hocs/src';
import CloseButton from '../close-button';
import UserTab from './user-tab';
import RoleTab from './role-tab';
import './style.scss';

const getTabs = once( () => ( [
	{
		name: 'user',
		title: __( 'Users', 'ithemes-security-pro' ),
		Component: UserTab,
		type: 'button',
		count( share ) {
			return sprintf( _n( '%d user', '%d users', share.users.length, 'ithemes-security-pro' ), share.users.length );
		},
	},
	{
		name: 'role',
		title: __( 'Roles', 'ithemes-security-pro' ),
		type: 'button',
		Component: RoleTab,
		count( share ) {
			return sprintf( _n( '%d role', '%d roles', share.roles.length, 'ithemes-security-pro' ), share.roles.length );
		},
	},
] ) );

function ShareAdd( { dashboardId, dashboard, isSaving, save, shares, setState, close } ) {
	const onSubmit = ( e ) => {
		e.preventDefault();

		if ( ! isSaving ) {
			save( {
				...dashboard,
				sharing: [
					...dashboard.sharing,
					...Object.values( shares ).filter( ( share ) => share ),
				],
			} );
		}
	};

	const tabs = getTabs();
	const summary = tabs.reduce( ( acc, cur ) => {
		if ( shares[ cur.name ] ) {
			acc.push( cur.count( shares[ cur.name ] ) );
		}

		return acc;
	}, [] );

	return (
		<form className="itsec-share-dashboard-add" onSubmit={ ( e ) => e.preventDefault() }>
			<CloseButton close={ close } />
			<header className="itsec-share-dashboard-add__header">
				<h3>{ __( 'Share Dashboard', 'ithemes-security-pro' ) }</h3>
				<p>{ __( 'Give select users read-only access to this dashboard. Great for building client portals.', 'ithemes-security-pro' ) }</p>
			</header>
			<TabPanel className="itsec-share-dashboard-add__tab-panel" tabs={ tabs }>
				{
					( { name, Component = noop } ) => <Component dashboardId={ dashboardId }
						share={ shares[ name ] } onChange={ ( share ) => setState( { shares: { ...shares, [ name ]: share } } ) } />
				}
			</TabPanel>
			<footer className="itsec-share-dashboard-add__footer">
				{ summary.length > 0 && <span className="itsec-share-dashboard-add__summary">{ sprintf( __( '%s selected', 'ithemes-security-pro' ), summary.join( ', ' ) ) }</span> }
				<Button isPrimary type="submit" onClick={ onSubmit } isBusy={ isSaving } aria-disabled={ isSaving }>
					{ __( 'Share', 'ithemes-security-pro' ) }
				</Button>
			</footer>
		</form>
	);
}

export default compose( [
	withState( { shares: {} } ),
	withSelect( ( select, props ) => ( {
		isSaving: select( 'ithemes-security/dashboard' ).isSavingDashboard( props.dashboardId ),
		dashboard: select( 'ithemes-security/dashboard' ).getDashboardForEdit( props.dashboardId ),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		save: dispatch( 'ithemes-security/dashboard' ).saveDashboard,
	} ) ),
	withPropChangeCallback( 'isSaving', ( prevIsSaving, { close, setState } ) => ( prevIsSaving && ( setState( { shares: [] } ), close() ) ) ),
] )( ShareAdd );
