/**
 * External dependencies
 */
import { get, flatten } from 'lodash';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch, withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Footer from './';

function FooterSchemaActions( { card, callingRpcs, onComplete, children } ) {
	const onClick = async ( href ) => {
		const response = await dispatch( 'ithemes-security/dashboard' ).callDashboardCardRpc( card.id, href );

		if ( onComplete ) {
			onComplete( href, response );
		}
	};

	const rpcs = get( card, [ '_links', 'ithemes-security:rpc' ], [] ),
		links = flatten( Object.values( get( card, '_links', {} ) ) ).filter( ( link ) => link.media === 'text/html' );

	if ( ! rpcs.length && ! links.length && ! children ) {
		return null;
	}

	return (
		<Footer>
			{ rpcs.map( ( link, i ) => (
				<span className="itsec-card-footer__action" key={ link.href }>
					<Button isPrimary={ i === 0 } isDefault={ i !== 0 }
						onClick={ () => ! callingRpcs.includes( link.href ) && onClick( link.href ) } isBusy={ callingRpcs.includes( link.href ) } aria-disabled={ callingRpcs.includes( link.href ) }>
						{ link.title }
					</Button>
				</span>
			) ) }
			{ links.map( ( link ) => (
				<span className="itsec-card-footer__action" key={ link.href }>
					<a href={ link.href }>
						{ link.title }
					</a>
				</span>
			) ) }
			{ children }
		</Footer>
	);
}

export default compose( [
	withSelect( ( select, props ) => ( {
		callingRpcs: select( 'ithemes-security/dashboard' ).getCallingDashboardCardRpcs( props.card.id ),
	} ) ),
] )( FooterSchemaActions );
