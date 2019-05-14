/**
 * WordPress dependencies.
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Header, { Title, Date } from '../../components/card/header';
import { FooterSchemaActions } from '../../components/card/footer';
import { shortenNumber } from 'packages/utils/src';
import './style.scss';

function VersionManagement( { card, config } ) {
	const boxes = [
		{
			label: __( 'WordPress Updates', 'ithemes-security-pro' ),
			value: card.data.counts.core,
		},
		{
			label: __( 'Plugin Updates', 'ithemes-security-pro' ),
			value: card.data.counts.plugin,
		},
		{
			label: __( 'Theme Updates', 'ithemes-security-pro' ),
			value: card.data.counts.theme,
		},
		{
			label: __( 'Total Updates', 'ithemes-security-pro' ),
			value: card.data.all,
		},
	];

	return (
		<div className="itsec-card--type-version-management">
			<Header>
				<Title card={ card } config={ config } />
				<Date card={ card } config={ config } />
			</Header>
			<section className="itsec-card-version-management__updates-section">
				<ul className="itsec-card-version-management__updates">
					{ boxes.map( ( box, i ) => (
						<li className="itsec-card-version-management__update" key={ i }>
							<span className="itsec-card-version-management__update-label">
								{ box.label }
							</span>
							<span className="itsec-card-version-management__update-count">
								{ shortenNumber( box.value ) }
							</span>
						</li>
					) ) }
				</ul>
			</section>
			<FooterSchemaActions card={ card } />
		</div>
	);
}

export const slug = 'version-management';
export const settings = {
	render: VersionManagement,
	elementQueries: [
		{
			type: 'width',
			dir: 'max',
			px: 355,
		},
	],
};
