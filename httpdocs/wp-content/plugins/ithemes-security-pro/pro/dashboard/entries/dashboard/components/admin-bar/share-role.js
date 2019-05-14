/**
 * External dependencies
 */
import { get, find } from 'lodash';
import contrast from 'contrast';

/**
 * WordPress dependencies
 */
import { Button, Dropdown, Tooltip } from '@wordpress/components';
import { compose, withState } from '@wordpress/compose';
import { sprintf, __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CloseButton from '../../components/close-button';
import { getConfigValue } from '../../utils';
import { PRIMARYS } from 'packages/style-guide/src/colors';

const sumChars = ( str ) => {
	let sum = 0;

	for ( let i = 0; i < str.length; i++ ) {
		sum += str.charCodeAt( i );
	}

	return sum;
};

function ShareRole( { role, remove } ) {
	const label = get( find( getConfigValue( 'roles' ), [ 'slug', role ] ), 'name', role );

	const parts = label.split( ' ' );
	let abbr;

	if ( parts.length === 1 ) {
		abbr = label.substring( 0, 2 );
	} else {
		abbr = parts[ 0 ].substring( 0, 1 ).toUpperCase() + parts[ 1 ].substring( 0, 1 ).toUpperCase();
	}

	const backgroundColor = PRIMARYS[ sumChars( role ) % PRIMARYS.length ];

	return (
		<Dropdown
			className="itsec-admin-bar-share__recipient itsec-admin-bar-share__recipient--role"
			contentClassName="itsec-admin-bar-share__recipient-content itsec-admin-bar-share__recipient-content--role"
			headerTitle={ sprintf( __( 'Share Settings for %s', 'ithemes-security-pro' ), label ) }
			expandOnMobile
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Tooltip text={ label }>
					<Button
						aria-pressed={ isOpen }
						onClick={ onToggle }
						className="itsec-admin-bar-share__recipient-trigger"
						aria-label={ label }
						style={ { backgroundColor } }
					>
						<span className={ `itsec-admin-bar-share__role-abbr itsec-admin-bar-share__role-abbr--theme-${ contrast( backgroundColor ) }` }>{ abbr }</span>
					</Button>
				</Tooltip>
			) }
			renderContent={ ( { onClose } ) => (
				<Fragment>
					<header>
						<h3>{ label }</h3>
						<CloseButton close={ onClose } />
					</header>
					<footer>
						<Button isLink onClick={ remove }>
							{ __( 'Remove', 'ithemes-security-pro' ) }
						</Button>
					</footer>
				</Fragment>
			) }
		/>
	);
}

export default compose( [
	withState( { opened: false } ),
	withSelect( ( select, props ) => ( {
		dashboard: select( 'ithemes-security/dashboard' ).getDashboardForEdit( props.dashboardId ),
	} ) ),
	withDispatch( ( dispatch, props ) => ( {
		remove() {
			const sharing = [];

			for ( const share of props.dashboard.sharing ) {
				if ( share.type !== 'role' ) {
					sharing.push( share );
				} else if ( ! share.roles.includes( props.role ) ) {
					sharing.push( share );
				} else {
					const without = {
						...share,
						roles: share.roles.filter( ( role ) => role !== props.role ),
					};

					sharing.push( without );
				}
			}

			return dispatch( 'ithemes-security/dashboard' ).saveDashboard( {
				...props.dashboard,
				sharing,
			} );
		},
	} ) ),
] )( ShareRole );
