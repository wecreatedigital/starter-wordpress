/**
 * External dependencies
 */
import { isEmpty } from 'lodash';
import memize from 'memize';
/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { compose, withState, pure } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { withSelect, withDispatch, dispatch } from '@wordpress/data';
import { dateI18n } from '@wordpress/date';
import { Button, Spinner, Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Header, { Title } from '../../components/card/header';
import Footer from '../../components/card/footer';
import MasterDetail, { Back } from '../../components/master-detail';
import { CardHappy } from '../../components/empty-states';
import Detail from './Detail';
import lockoutController from './lockout-controller';
import { withDebounceHandler } from 'packages/hocs/src';
import './style.scss';

function MasterRender( { master } ) {
	return (
		<Fragment>
			<time className="itsec-card-active-lockouts__start-time" dateTime={ master.start_gmt } title={ dateI18n( 'M d, Y g:s A', master.start_gmt ) }>
				{ sprintf( __( '%s ago', 'ithemes-security-pro' ), master.start_gmt_relative ) }
			</time>
			<h3 className="itsec-card-active-lockouts__label">{ master.label }</h3>
			<p className="itsec-card-active-lockouts__description">{ master.description }</p>
		</Fragment>
	);
}

const withLinks = memize( function( lockouts, links ) {
	return lockouts.map( ( lockout ) => ( {
		...lockout,
		links,
	} ) );
} );

function ActiveLockouts( { card, config, isQuerying, query, selectedId, releasingIds, setState } ) {
	const select = ( id ) => {
		return setState( { selectedId: id } );
	};

	const onRelease = async ( e ) => {
		e.preventDefault();

		setState( { releasingIds: [ ...releasingIds, selectedId ] } );

		try {
			await lockoutController.release( card._links[ 'ithemes-security:release-lockout' ][ 0 ].href.replace( '{lockout_id}', selectedId ) );
		} catch ( e ) {
			console.warn( e );
		}

		await dispatch( 'ithemes-security/dashboard' ).refreshDashboardCard( card.id );
		setState( { selectedId: 0, releasingIds: releasingIds.filter( ( id ) => id !== selectedId ) } );
	};

	const isSmall = true;

	return (
		<div className="itsec-card--type-active-lockouts">
			<Header>
				<Back isSmall={ isSmall } select={ select } selectedId={ selectedId } />
				<Title card={ card } config={ config } />
			</Header>
			{ selectedId === 0 && (
				<div className="itsec-card-active-lockouts__search-container">
					<input type="search" onChange={ ( e ) => query( { search: e.target.value } ) } placeholder={ __( 'Search Lockouts', 'ithemes-security-pro' ) } />
					{ isQuerying ? <Spinner /> : <Dashicon icon="search" /> }
				</div>
			) }
			{ isEmpty( card.data.lockouts ) ?
				<CardHappy title={ __( 'All Clear!', 'ithemes-security-pro' ) } text={ __( 'No users are currently locked out of your site.', 'ithemes-security-pro' ) } /> :
				<MasterDetail masters={ withLinks( card.data.lockouts, card._links ) } detailRender={ Detail } masterRender={ MasterRender }
					mode="list" selectedId={ selectedId } select={ select } isSmall={ isSmall } />
			}
			{ selectedId > 0 && card._links[ 'ithemes-security:release-lockout' ] && (
				<Footer>
					<span className="itsec-card-footer__action">
						<Button
							isPrimary
							aria-disabled={ releasingIds.includes( selectedId ) }
							isBusy={ releasingIds.includes( selectedId ) }
							onClick={ onRelease }>
							{ __( 'Release Lockout', 'ithemes-security-pro' ) }
						</Button>
					</span>
				</Footer>
			) }
		</div>
	);
}

export const slug = 'active-lockouts';
export const settings = {
	render: compose( [
		withState( { selectedId: 0, releasingIds: [] } ),
		withSelect( ( select, ownProps ) => ( {
			isQuerying: select( 'ithemes-security/dashboard' ).isQueryingDashboardCard( ownProps.card.id ),
		} ) ),
		withDispatch( ( d, ownProps ) => ( {
			query( queryArgs ) {
				return d( 'ithemes-security/dashboard' ).queryDashboardCard( ownProps.card.id, queryArgs );
			},
		} ) ),
		withDebounceHandler( 'query', 500, { leading: true } ),
		pure,
	] )( ActiveLockouts ),
	elementQueries: [
		{
			type: 'width',
			dir: 'max',
			px: 500,
		},
	],
};
