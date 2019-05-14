/**
 * External dependencies
 */
import { get, negate } from 'lodash';

/**
 * WordPress dependencies
 */
import { Tooltip } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { isApiError } from 'packages/utils/src';
import { getAvatarUrl } from '../../utils';

function Share( { dashboard } ) {
	const author = get( dashboard, [ '_embedded', 'author', 0 ] );
	const recipients = get( dashboard, [ '_embedded', 'ithemes-security:shared-with' ], [] ).filter( negate( isApiError ) );

	if ( ( ! author || isApiError( author ) ) && ! recipients.length ) {
		return null;
	}

	return (
		<div className="itsec-admin-bar__share">
			{ author && ! isApiError( author ) && (
				<div className="itsec-admin-bar-share__owner">
					<Tooltip text={ author.name }>
						<span className="itsec-admin-bar-share__recipient">
							<img className="itsec-admin-bar-share__user-avatar" src={ getAvatarUrl( author ) } alt="" />
						</span>
					</Tooltip>
				</div>
			) }
			<div className="itsec-admin-bar-share__recipients">
				{ recipients.map( ( user ) => (
					<Tooltip text={ user.name } key={ user.id }>
						<span className="itsec-admin-bar-share__recipient itsec-admin-bar-share__recipient--user">
							<img className="itsec-admin-bar-share__user-avatar" src={ getAvatarUrl( user ) } alt="" />
						</span>
					</Tooltip>
				) ) }
			</div>
		</div>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		dashboard: select( 'ithemes-security/dashboard' ).getDashboard( props.dashboardId ),
	} ) ),
] )( Share );
