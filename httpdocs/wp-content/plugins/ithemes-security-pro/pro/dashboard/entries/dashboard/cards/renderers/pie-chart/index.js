/**
 * External dependencies
 */
import { ResponsiveContainer, PieChart as Chart, Pie, Cell, Sector } from 'recharts';

/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header, { Title, Date } from '../../../components/card/header';
import { shortenNumber } from 'packages/utils/src';
import { PRIMARYS } from 'packages/style-guide/src/colors';
import { CardNoData } from '../../../components/empty-states';
import './style.scss';

export default function PieChart( { card, config } ) {
	const data = [];
	let total = 0;

	if ( card.data && card.data.data ) {
		for ( const key in card.data.data ) {
			if ( ! card.data.data.hasOwnProperty( key ) ) {
				continue;
			}

			total += card.data.data[ key ].sum;
			data.push( {
				name: card.data.data[ key ].label,
				value: card.data.data[ key ].sum,
			} );
		}
	}

	const renderActiveShape = ( props ) => {
		const { cx, cy, innerRadius, outerRadius, startAngle, endAngle, fill } = props;

		return (
			<g>
				<text x={ cx } y={ cy + 10 } dy={ 8 } textAnchor="middle" fill={ fill } className="itsec-card-pie-chart__circle-sum">
					{ shortenNumber( card.data.circle_sum ) }
				</text>
				<text x={ cx } y={ cy + 30 } dy={ 8 } textAnchor="middle" fill={ fill } className="itsec-card-pie-chart__circle-label">
					{ card.data.circle_label }
				</text>
				<Sector
					cx={ cx }
					cy={ cy }
					innerRadius={ innerRadius }
					outerRadius={ outerRadius }
					startAngle={ startAngle }
					endAngle={ endAngle }
					fill={ fill }
				/>

			</g>
		);
	};

	return (
		<div className="itsec-card--type-pie-chart">
			<Header>
				<Title card={ card } config={ config } />
				{ config.query_args.period && <Date card={ card } config={ config } /> }
			</Header>
			{ ( data.length > 0 && total > 0 ) ? <Fragment>
				<ResponsiveContainer width="100%" height={ 200 }>
					<Chart>
						<Pie
							data={ data }
							dataKey="value"
							innerRadius={ 60 }
							outerRadius={ 80 }
							fill="#8884d8"
							paddingAngle={ 5 }
							activeShape={ renderActiveShape }
							activeIndex={ 0 }
						>
							{ data.map( ( entry, index ) => <Cell key={ index } fill={ PRIMARYS[ index % PRIMARYS.length ] } /> ) }
						</Pie>
					</Chart>
				</ResponsiveContainer>
				<table className="itsec-card-pie-chart__values">
					<tbody>
						{ data.map( ( datum, i ) => (
							<tr key={ datum.name }>
								<th scope="row">{ datum.name }</th>
								<td style={ { color: PRIMARYS[ i ] } }>{ ( ( datum.value / total ) * 100 ).toFixed( 0 ) }%</td>
							</tr>
						) ) }
					</tbody>
				</table>
			</Fragment> : <CardNoData /> }
		</div>
	);
}
