/**
 * Internal dependencies
 */
import { getCardTitle } from '../../../utils';

export default function Title( { card, config } ) {
	return (
		<h2 className="itsec-card-header-title">
			{ getCardTitle( card, config ) }
		</h2>
	);
}
