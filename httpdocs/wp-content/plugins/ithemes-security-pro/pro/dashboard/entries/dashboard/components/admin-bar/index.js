/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon, Button, Dropdown, Popover } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import EditCards from '../edit-cards';
import Share from './share';
import ReadOnlyShare from './read-only-share';
import ManageDashboards from '../manage-dashboards';
import './style.scss';

function AdminBar( { dashboard, dashboardId, canEdit, requesting, editingCards, openCards, closeCards, canCreate, dashboards } ) {
	const title = <h1>{ dashboard ? decodeEntities( dashboard.label.rendered ) : __( 'No Dashboard Selected', 'ithemes-security-pro' ) }</h1>;

	return ! requesting && (
		<div className="itsec-admin-bar">
			<div className="itsec-admin-bar__title">
				{ ! canCreate && dashboards.length <= 1 && dashboardId ? title : (
					<Dropdown
						className="itsec-admin-bar-manage-dashboards__trigger"
						contentClassName="itsec-admin-bar-manage-dashboards__content"
						position="bottom right"
						headerTitle={ __( 'Manage Dashboards', 'ithemes-security-pro' ) }
						expandOnMobile
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button aria-expanded={ isOpen } onClick={ onToggle }>
								{ title }
								<Dashicon icon={ isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2' } size={ 15 } />
							</Button>
						) }
						renderContent={ ( { onClose } ) => (
							<ManageDashboards dashboardId={ dashboardId } close={ onClose } />
						) }
					/> ) }
			</div>
			{ dashboard && ( canEdit ? <Share dashboardId={ dashboardId } /> : <ReadOnlyShare dashboardId={ dashboardId } /> ) }
			{ canEdit && (
				<div className="itsec-admin-bar__edit-cards">
					<div className="itsec-admin-bar-edit-cards__trigger">
						<Button aria-expanded={ editingCards } onClick={ editingCards ? closeCards : openCards }>
							<Dashicon icon="layout" size={ 15 } />
							{ __( 'Edit Cards', 'ithemes-security-pro' ) }
						</Button>
					</div>
					{ editingCards && (
						<Popover className="itsec-admin-bar-edit-cards__content"
							position="bottom"
							headerTitle={ __( 'Edit Cards', 'ithemes-security-pro' ) } expandOnMobile
							onClickOutside={ closeCards } onClose={ closeCards }>
							<EditCards dashboardId={ dashboardId } close={ closeCards } />
						</Popover>
					) }
				</div>
			) }
		</div>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		canEdit: select( 'ithemes-security/dashboard' ).canEditDashboard( props.dashboardId ),
		dashboard: select( 'ithemes-security/dashboard' ).getDashboard( props.dashboardId ),
		requesting: select( 'ithemes-security/dashboard' ).isRequestingDashboards(),
		editingCards: select( 'ithemes-security/dashboard' ).isEditingCards(),
		canCreate: select( 'ithemes-security/dashboard' ).canCreateDashboards(),
		dashboards: select( 'ithemes-security/dashboard' ).getAvailableDashboards(),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		openCards: dispatch( 'ithemes-security/dashboard' ).openEditCards,
		closeCards: dispatch( 'ithemes-security/dashboard' ).closeEditCards,
	} ) ),
] )( AdminBar );
