/**
 * External dependencies.
 */
import { take, isEmpty } from 'lodash';

/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Header, { Title } from '../../components/card/header';
import { FooterSchemaActions } from '../../components/card/footer';
import { shortenNumber } from 'packages/utils/src';
import './style.scss';

function DatabaseBackup( { card, config, addNotice } ) {
	const onComplete = ( href, response ) => {
		if ( href.endsWith( '/backup' ) ) {
			addNotice( response.message, 'backup-complete' );
		}
	};

	if ( isEmpty( card.data ) ) {
		return (
			<div className="itsec-card--type-database-backup itsec-card-database-backup--no-data">
				<Header>
					<Title card={ card } config={ config } />
				</Header>
				<FooterSchemaActions card={ card } onComplete={ onComplete } />
			</div>
		);
	}

	return (
		<div className={ `itsec-card--type-database-backup itsec-card-database-backup--source-${ card.data.source }` }>
			<Header>
				<Title card={ card } config={ config } />
			</Header>
			<section className="itsec-card-database-backup__total">
				<span className="itsec-card-database-backup__total-count">
					{ shortenNumber( card.data.total ) }
					{ card.data.total === 100 && <sup>+</sup> }
				</span>
				<span className="itsec-card-database-backup__total-label">{ __( 'Backups', 'ithemes-security-pro' ) }</span>
			</section>
			{ card.data.backups.length > 0 && (
				<section className="itsec-card-database-backup__recent-backups-section" aria-label={ __( 'Recent Backups', 'ithemes-security-pro' ) }>
					<table className="itsec-card-database-backup__recent-backups">
						<thead>
							<tr>
								<th scope="column" className="itsec-card-database-backup__col-date">
									{ __( 'Date', 'ithemes-security-pro' ) }
								</th>
								<th scope="column" className="itsec-card-database-backup__col-size">
									{ __( 'Size', 'ithemes-security-pro' ) }
								</th>
								{ card.data.source === 'files' && (
									<th scope="column" className="itsec-card-database-backup__col-actions">
										<span className="screen-reader-text">
											{ __( 'Download', 'ithemes-security-pro' ) }
										</span>
									</th>
								) }
							</tr>
						</thead>
						<tbody>
							{ take( card.data.backups, 50 ).map( ( backup ) => (
								<tr key={ backup.url }>
									<th scope="row" className="itsec-card-database-backup__col-date">
										<span className="itsec-card-database-backup__backup-date">
											{ dateI18n( 'M d, Y', backup.time ) }
										</span>
										<span className="itsec-card-database-backup__backup-time">
											&nbsp;{ dateI18n( 'g:i A', backup.time ) }
										</span>
									</th>
									<td className="itsec-card-database-backup__col-size">
										{ backup.size_format }
									</td>
									{ card.data.source === 'files' && (
										<td className="itsec-card-database-backup__col-actions">
											{ backup.url && <a href={ backup.url } download>{ __( 'Download', 'ithemes-security-pro' ) }</a> }
										</td>
									) }
								</tr>
							) ) }
						</tbody>
					</table>
				</section>
			) }
			<FooterSchemaActions card={ card } onComplete={ onComplete } />
		</div>
	);
}

export const slug = 'database-backup';
export const settings = {
	render: withDispatch( ( dispatch ) => ( {
		addNotice( message, id ) {
			dispatch( 'core/notices' ).createSuccessNotice( message, { id, context: 'ithemes-security' } );
			setTimeout( () => dispatch( 'core/notices' ).removeNotice( id, 'ithemes-security' ), 10000 );
		},
	} ) )( DatabaseBackup ),
	elementQueries: [
		{
			type: 'width',
			dir: 'max',
			px: 300,
		},
		{
			type: 'width',
			dir: 'max',
			px: 250,
		},
		{
			type: 'height',
			dir: 'max',
			px: 300,
		},
	],
};
