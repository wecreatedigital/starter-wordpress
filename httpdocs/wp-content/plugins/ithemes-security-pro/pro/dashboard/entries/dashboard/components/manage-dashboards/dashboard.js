/**
 * External dependencies
 */
import { get } from 'lodash';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { Button } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

function Dashboard( { dashboard, currentDashboard, isPrimary, isDeleting, currentUserId, setPrimary, select, deleteDashboard, close } ) {
	const title = decodeEntities( dashboard.label.rendered );

	return (
		<li className={ classnames( 'itsec-manage-dashboards__dashboard', {
			'itsec-manage-dashboard__dashboard--deleting': isDeleting,
		} ) }>
			<header className="itsec-manage-dashboards__dashboard-header">
				<h4>
					{ currentDashboard === dashboard.id ? title : <Button isLink onClick={ () => [ select( dashboard.id ), close() ] }>{ title }</Button> }
					{ isPrimary && <span className="itsec-manage-dashboards__primary">{ __( 'Primary', 'ithemes-security-pro' ) }</span> }
				</h4>
				{ currentUserId !== dashboard.created_by && (
					<span className="itsec-manage-dashboards__dashboard-meta itsec-manage-dashboards__dashboard-meta--author">
						{ sprintf(
							__( 'Shared by %s', 'ithemes-security-pro' ),
							get( dashboard, [ '_embedded', 'author', 0, 'name' ], sprintf( __( 'User #%d', 'ithemes-security-pro' ), dashboard.created_by ) )
						) }
					</span>
				) }
				<span className="itsec-manage-dashboards__dashboard-meta itsec-manage-dashboards__dashboard-meta--date">
					{ sprintf( __( 'Created on %s', 'ithemes-security-pro' ), dateI18n( 'M j, Y', dashboard.created_at ) ) }
				</span>
			</header>
			<div className="itsec-manage-dashboards__dashboard-actions">
				{ ! isPrimary && (
					<Button isLink onClick={ setPrimary } className="itsec-manage-dashboards__dashboard-action">
						{ __( 'Make Primary', 'ithemes-security-pro' ) }
					</Button>
				) }
				{ dashboard.id !== currentDashboard && ! isPrimary && currentUserId === dashboard.created_by && (
					<Button isLink isDestructive onClick={ deleteDashboard } className="itsec-manage-dashboards__dashboard-action">
						{ __( 'Delete', 'ithemes-security-pro' ) }
					</Button>
				) }
			</div>
		</li>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		currentDashboard: select( 'ithemes-security/dashboard' ).getViewingDashboardId(),
		isPrimary: select( 'ithemes-security/dashboard' ).getPrimaryDashboard() === props.dashboard.id,
		isDeleting: select( 'ithemes-security/dashboard' ).isDeletingDashboard( props.dashboard.id ),
	} ) ),
	withDispatch( ( dispatch, props ) => ( {
		select() {
			return dispatch( 'ithemes-security/dashboard' ).viewDashboard( props.dashboard.id );
		},
		setPrimary() {
			return dispatch( 'ithemes-security/dashboard' ).setPrimaryDashboard( props.dashboard.id );
		},
		deleteDashboard() {
			return dispatch( 'ithemes-security/dashboard' ).deleteDashboard( props.dashboard.id );
		},
	} ) ),
] )( Dashboard );
