/**
 * WordPress dependencies
 */
import { compose, pure } from '@wordpress/compose';

function Detail( { master, idProp, parentInstanceId, isSelected, DetailRender } ) {
	return (
		<section
			key={ master[ idProp ] }
			role="tabpanel"
			className="itsec-component-master-detail__detail-container"
			id={ `itsec-component-master-detail-${ parentInstanceId }__detail--${ master[ idProp ] }` }
			style={ isSelected ? {} : { display: 'none' } }
		>
			<DetailRender master={ master } isVisible={ isSelected } />
		</section>
	);
}

export default compose( [
	pure,
] )( Detail );
