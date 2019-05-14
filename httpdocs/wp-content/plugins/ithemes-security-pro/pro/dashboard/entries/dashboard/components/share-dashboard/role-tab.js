/**
 * External dependencies
 */
import memize from 'memize';

/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getConfigValue } from '../../utils';

const includesRoles = memize( ( sharing, role ) => sharing.some( ( share ) => share.type === 'role' && share.roles.includes( role ) ) );

function RoleTab( { dashboard, share = { type: 'role', roles: [] }, onChange } ) {
	const roles = getConfigValue( 'roles' ).filter( ( role ) => ! includesRoles( dashboard.sharing, role.slug ) );

	return (
		roles.length > 0 ?
			<ul>
				{ roles.map( ( role ) => (
					<li key={ role.slug }>
						<CheckboxControl label={ role.name } checked={ share.roles.includes( role.slug ) } onChange={ ( checked ) => checked ?
							onChange( { ...share, roles: [ ...share.roles, role.slug ] } ) :
							onChange( { ...share, roles: share.roles.filter( ( maybeRole ) => role.slug !== maybeRole ) } )
						} />
					</li>
				) ) }
			</ul> :
			<p>{ __( 'All roles already selected.', 'ithemes-security-pro' ) }</p>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		dashboard: select( 'ithemes-security/dashboard' ).getDashboardForEdit( props.dashboardId ),
	} ) ),
] )( RoleTab );

