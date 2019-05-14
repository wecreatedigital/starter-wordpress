/**
 * WordPress dependencies
 */
import { NoticeList, SlotFillProvider } from '@wordpress/components';
import { compose, pure, withGlobalEvents } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { Component } from '@wordpress/element';
import '@wordpress/notices';

/**
 * Internal dependencies
 */
import './api-fetch';
import './store';
import './cards';
import Header from './components/header';
import AdminBar from './components/admin-bar';
import CardGrid from './components/card-grid';
import CreateDashboard from './components/create-dashboard';
import './style.scss';

const Page = pure( ( { page, dashboardId } ) => {
	switch ( page ) {
		case 'view-dashboard':
			return <CardGrid dashboardId={ dashboardId } />;
		case 'create-dashboard':
			return <CreateDashboard />;
		default:
			return null;
	}
} );

class App extends Component {
	handleTouchStart() {
		if ( ! this.props.isUsingTouch ) {
			this.props.usingTouch();
		}
	}

	render() {
		const { dashboardId, page, notices, removeNotice } = this.props;
		return (
			<SlotFillProvider>
				<div className={ `wrap itsec-app-page--${ page }` } style={ { marginTop: 20 } }>
					<NoticeList notices={ notices } onRemove={ removeNotice } />
					<Header />
					<AdminBar dashboardId={ dashboardId } />
					<Page page={ page } dashboardId={ dashboardId } />
				</div>
			</SlotFillProvider>
		);
	}
}

export default compose( [
	withSelect( ( select ) => ( {
		page: select( 'ithemes-security/dashboard' ).getCurrentPage(),
		dashboardId: select( 'ithemes-security/dashboard' ).getViewingDashboardId(),
		isUsingTouch: select( 'ithemes-security/dashboard' ).isUsingTouch(),
		notices: select( 'core/notices' ).getNotices( 'ithemes-security' ),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		removeNotice( noticeId ) {
			return dispatch( 'core/notices' ).removeNotice( noticeId, 'ithemes-security' );
		},
		usingTouch: dispatch( 'ithemes-security/dashboard' ).usingTouch,
	} ) ),
	withGlobalEvents( {
		touchstart: 'handleTouchStart',
	} ),
] )( App );
