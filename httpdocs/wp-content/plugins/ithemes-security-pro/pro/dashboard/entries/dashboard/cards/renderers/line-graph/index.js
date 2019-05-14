/**
 * External dependencies
 */
import { ResponsiveContainer, LineChart, XAxis, YAxis, CartesianAxis, Tooltip, Legend, Line } from 'recharts';
import { isEmpty } from 'lodash';

/**
 * WordPress dependencies
 */
import { dateI18n } from '@wordpress/date';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { PRIMARYS } from 'packages/style-guide/src/colors';
import Header, { Title, Date } from '../../../components/card/header';
import { CardNoData } from '../../../components/empty-states';
import './style.scss';

function LineGraph( { card, config, period } ) {
	period = period || ( config.query_args.period && config.query_args.period.default );

	const data = [],
		lines = [];

	let empty = true;

	if ( ! isEmpty( card.data ) ) {
		for ( const key in card.data ) {
			if ( ! card.data.hasOwnProperty( key ) ) {
				continue;
			}

			for ( let i = 0; i < card.data[ key ].data.length; i++ ) {
				const datum = card.data[ key ].data[ i ];

				if ( datum.y > 0 ) {
					empty = false;
				}

				if ( data[ i ] ) {
					data[ i ][ key ] = datum.y;
				} else {
					const format = period === '24-hours' ? 'g A' : 'M j';

					data.push( {
						name: datum.t ? dateI18n( format, datum.t ) : datum.x,
						[ key ]: datum.y,
					} );
				}
			}

			lines.push( {
				name: card.data[ key ].label,
				dataKey: key,
			} );
		}
	}

	return (
		<div className="itsec-card--type-line-graph">
			<Header>
				<Title card={ card } config={ config } />
				<Date card={ card } config={ config } />
			</Header>
			{ empty ? <CardNoData /> : (
				<ResponsiveContainer width="100%" height="100%">
					<LineChart margin={ { top: 10, left: -15, right: 50, bottom: 10 } } data={ data }>
						<XAxis dataKey="name" />
						<YAxis allowDecimals={ false } />
						<CartesianAxis strokeDasharray="3 3" />
						<Tooltip />
						<Legend />
						{ lines.map( ( line, i ) => <Line type="monotone" dataKey={ line.dataKey } name={ line.name } key={ line.dataKey } stroke={ PRIMARYS[ i ] } /> ) }
					</LineChart>
				</ResponsiveContainer>
			) }
		</div>
	);
}
export default compose( [
	withSelect( ( select, { card } ) => ( {
		period: ( select( 'ithemes-security/dashboard' ).getDashboardCardQueryArgs( card.id ) || {} ).period,
	} ) ),
] )( LineGraph );
