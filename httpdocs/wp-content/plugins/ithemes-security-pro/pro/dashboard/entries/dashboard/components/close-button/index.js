/**
 * WordPress dependencies
 */
import { IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

export default function CloseButton( { close } ) {
	return (
		<IconButton className="itsec-close-button" icon="no-alt" onClick={ ( e ) => {
			e.preventDefault();
			close();
		} } tooltip={ false } label={ __( 'Close', 'ithemes-security-pro' ) } />
	);
}
