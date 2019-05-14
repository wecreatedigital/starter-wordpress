/**
 * External dependencies
 */
import { once } from 'lodash';

/**
 * WordPress dependencies.
 */
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { shortenNumber } from 'packages/utils/src';
import { getConfigValue } from '../../../utils';
import IconActivitiesBlocked from './icons/activities-blocked.svg';
import IconActivitiesDetected from './icons/activities-detected.svg';
import IconActivitiesMonitored from './icons/activities-monitored.svg';
import IconIPMonitored from './icons/ip-monitored.svg';
import './style.scss';

const getStats = once( () => ( [
	getConfigValue( 'db_logs' ) && {
		key: 'events',
		Icon: IconActivitiesMonitored,
		label: __( 'Events Tracked', 'ithemes-security-pro' ),
	},
	{
		key: 'suspicious',
		Icon: IconActivitiesDetected,
		label: __( 'Suspicious Activities', 'ithemes-security-pro' ),
	},
	{
		key: 'blocked',
		Icon: IconActivitiesBlocked,
		label: __( 'Activities Blocked', 'ithemes-security-pro' ),
	},
	getConfigValue( 'db_logs' ) && {
		key: 'ips',
		Icon: IconIPMonitored,
		label: __( 'IPs Monitored', 'ithemes-security-pro' ),
	},
] ) );

function Static( { stats = {} } ) {
	return (
		<div className="itsec-static-bar">
			<div className="itsec-static-bar__stats-container">
				{ getStats().filter( ( stat ) => !! stat ).map( ( Stat ) => (
					<div key={ Stat.key } className="itsec-static-bar__stat">
						<Stat.Icon height={ 50 } />
						<span className="itsec-static-bar__stat-data">
							{ stats[ Stat.key ] ? shortenNumber( stats[ Stat.key ] ) : '–' }
						</span>
						<h3 className="itsec-static-bar__stat-label">{ Stat.label }</h3>
					</div>
				) ) }
			</div>
		</div>
	);
}

export default compose( [
	withSelect( ( select ) => ( {
		stats: select( 'ithemes-security/dashboard' ).getStaticStats(),
	} ) ),
] )( Static );
