/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Header, { Title } from '../card/header';
import Icon from './icons/card-crash.svg';

function CardCrash( { card, config } ) {
	return (
		<div className="itsec-empty-state-card itsec-empty-state-card--error">
			{ config && (
				<Header>
					<Title card={ card } config={ config } />
				</Header>
			) }
			<h3>{ __( 'Unexpected Error', 'ithemes-security-pro' ) }</h3>
			<Icon />
			<p>{ __( 'An error occurred while rendering this card.', 'ithemes-security-pro' ) }</p>
			<p>{ __( 'Try refreshing your browser. If the error persists, please contact support.', 'ithemes-security-pro' ) }</p>
		</div>
	);
}

export default CardCrash;
