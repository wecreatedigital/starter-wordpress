/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import { getConfigValue } from '../../../utils';

function User() {
	return (
		<div className="itsec-header-user">
			<img
				className="itsec-header-user__avatar"
				src={ getConfigValue( [ 'user', 'avatar' ] ) }
				alt=""
			/>
			<span className="itsec-header-user__greeting">
				{ sprintf( __( 'Hello, %s', 'ithemes-security-pro' ), getConfigValue( [ 'user', 'name' ] ) ) }
			</span>
		</div>
	);
}

export default User;
