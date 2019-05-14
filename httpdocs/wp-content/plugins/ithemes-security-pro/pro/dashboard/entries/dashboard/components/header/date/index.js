/**
 * WordPress dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import './style.scss';

function Date() {
	return (
		<div className="itsec-header-date">
			<span className="itsec-header-date__day">
				{ format( 'd' ) }
			</span>
			<span className="itsec-header-date__month">
				{ format( 'F' ) }
			</span>
		</div>
	);
}

export default Date;
