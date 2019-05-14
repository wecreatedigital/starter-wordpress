/**
 * WordPress dependencies
 */
import { IconButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Back( { isSmall, select, selectedId } ) {
	return (
		<IconButton
			icon="arrow-left-alt"
			className="itsec-component-master-detail__back"
			onClick={ () => select( 0 ) }
			style={ ( ! selectedId || ! isSmall ) ? { display: 'none' } : { } }
			label={ __( 'Back to List', 'ithemes-security-pro' ) } tooltip={ false } />
	);
}
