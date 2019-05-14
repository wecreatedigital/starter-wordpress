/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { NoticeList } from '@wordpress/components';
import '@wordpress/notices';

/**
 * Internal dependencies
 */
import 'pro/dashboard/entries/dashboard/store';
import Static from 'pro/dashboard/entries/dashboard/components/header/static';
import Carousel from './components/carousel';
import './style.scss';

function App( { dashboardId, notices, removeNotice } ) {
	return (
		<div>
			<NoticeList notices={ notices } onRemove={ removeNotice } />
			<Static />
			<Carousel dashboardId={ dashboardId } />
		</div>
	);
}

export default compose( [
	withSelect( ( select ) => ( {
		dashboardId: select( 'ithemes-security/dashboard' ).getViewingDashboardId(),
		notices: select( 'core/notices' ).getNotices( 'ithemes-security' ),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		removeNotice( noticeId ) {
			return dispatch( 'core/notices' ).removeNotice( noticeId, 'ithemes-security' );
		},
	} ) ),
] )( App );
