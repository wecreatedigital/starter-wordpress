/**
 * External dependencies
 */
import { get, find } from 'lodash';

/**
 * WordPress dependencies
 */
import { Tooltip, Dropdown, Dashicon, IconButton } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShareUser from './share-user';
import ShareRole from './share-role';
import ShareDashboard from '../share-dashboard';
import { getAvatarUrl } from '../../utils';

function Share( { dashboard, dashboardId } ) {
	const author = get( dashboard, [ '_embedded', 'author', 0 ] );
	const getUser = ( id ) => find( get( dashboard, [ '_embedded', 'ithemes-security:shared-with' ], [] ), { id } );

	return (
		<div className="itsec-admin-bar__share">
			{ author && (
				<div className="itsec-admin-bar-share__owner">
					<Tooltip text={ author.name }>
						<span className="itsec-admin-bar-share__recipient">
							<img className="itsec-admin-bar-share__user-avatar" src={ getAvatarUrl( author ) } alt="" />
						</span>
					</Tooltip>
				</div>
			) }
			<div className="itsec-admin-bar-share__recipients">
				{ get( dashboard, 'sharing', [] ).filter( ( share ) => share.type === 'user' ).map( ( share ) => (
					share.users.map( ( userId ) => (
						<ShareUser share={ share } userId={ userId } user={ getUser( userId ) } dashboardId={ dashboardId } key={ userId } />
					) )
				) ) }
				{ get( dashboard, 'sharing', [] ).filter( ( share ) => share.type === 'role' ).map( ( share ) => (
					share.roles.map( ( role ) => (
						<ShareRole share={ share } role={ role } dashboardId={ dashboardId } key={ role } />
					) )
				) ) }
			</div>
			<div className="itsec-admin-bar-share__add-share-container">
				<Dropdown
					className="itsec-admin-bar-share__add-share"
					contentClassName="itsec-admin-bar-share__add-share-content"
					position="bottom"
					headerTitle={ __( 'Share with User', 'ithemes-security-pro' ) } expandOnMobile
					renderToggle={ ( { isOpen, onToggle } ) => (
						<IconButton label={ __( 'Share Dashboard', 'ithemes-security-pro' ) } aria-pressed={ isOpen } onClick={ onToggle }
							icon={ <Dashicon icon="plus-alt" size={ 40 } /> }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<ShareDashboard dashboardId={ dashboardId } close={ onClose } />
					) }
				/>
			</div>
		</div>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		dashboard: select( 'ithemes-security/dashboard' ).getDashboardForEdit( props.dashboardId ),
	} ) ),
] )( Share );
