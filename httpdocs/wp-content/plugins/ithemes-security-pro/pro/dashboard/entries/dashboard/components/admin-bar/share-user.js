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
import { getAvatarUrl } from '../../utils';

function ShareUser( { userId, user, remove } ) {
	const label = user ? user.name : sprintf( __( 'User #%d', 'ithemes-security-pro' ), userId ),
		avatar = getAvatarUrl( user );

	return (
		<Dropdown
			className="itsec-admin-bar-share__recipient itsec-admin-bar-share__recipient--user"
			contentClassName="itsec-admin-bar-share__recipient-content itsec-admin-bar-share__recipient-content--user"
			headerTitle={ sprintf( __( 'Share Settings for %s', 'ithemes-security-pro' ), label ) }
			expandOnMobile
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Tooltip text={ label }>
					<Button
						aria-pressed={ isOpen }
						aria-label={ label }
						onClick={ onToggle }
						className="itsec-admin-bar-share__recipient-trigger"
						style={ { backgroundImage: `url(${ avatar })` } }
					/>
				</Tooltip>
			) }
			renderContent={ ( { onClose } ) => (
				<Fragment>
					<header>
						<img src={ avatar } alt="" />
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
				if ( share.type !== 'user' ) {
					sharing.push( share );
				} else if ( ! share.users.includes( props.userId ) ) {
					sharing.push( share );
				} else {
					const without = {
						...share,
						users: share.users.filter( ( id ) => id !== props.userId ),
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
] )( ShareUser );
