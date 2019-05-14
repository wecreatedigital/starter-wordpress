/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import { getConfigValue } from '../../../utils';

function Protected() {
	return (
		<div className="itsec-header-protected">
			<span className="itsec-header-protected__text">
				{ __( 'Your site is protected.', 'ithemes-security-pro' ) }
			</span>

			<span className="itsec-header-protected__url">
				{ getConfigValue( 'site_url_pretty' ) }
			</span>
		</div>
	);
}

export default Protected;
