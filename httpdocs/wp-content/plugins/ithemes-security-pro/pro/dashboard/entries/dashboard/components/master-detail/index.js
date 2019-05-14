/**
 * External dependencies
 */
import classnames from 'classnames';
import { curry, find } from 'lodash';

/**
 * WordPress dependencies
 */
import { DOWN, UP, ENTER, SPACE } from '@wordpress/keycodes';
import { compose, withInstanceId, pure } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Detail from './Detail';
import './style.scss';

function MasterDetail( {
	masters,
	masterRender: MasterRender,
	detailRender: DetailRender,
	selectedId,
	select,
	instanceId,
	mode = 'table',
	idProp = 'id',
	isSmall = false,
	children,
} ) {
	const selected = find( masters, [ idProp, selectedId || masters[ 0 ][ idProp ] ] );

	const masterRefs = {};
	let containerRef;

	const onKeyDown = curry( ( pos, e ) => {
		const { keyCode } = e;

		let newPos;

		switch ( keyCode ) {
			case UP:
				if ( pos === 0 ) {
					newPos = masters.length - 1;
				} else {
					newPos = pos - 1;
				}
				break;
			case DOWN:
				if ( pos === masters.length - 1 ) {
					newPos = 0;
				} else {
					newPos = pos + 1;
				}
				break;
			case ENTER:
			case SPACE:
				e.preventDefault();
				e.stopPropagation();
				select( masters[ pos ][ idProp ] );
				return;
			default:
				return;
		}

		const ref = masterRefs[ masters[ newPos ][ idProp ] ];

		if ( ref ) {
			e.stopPropagation();
			e.preventDefault();
			ref.focus();

			if ( newPos === 0 ) {
				e.nativeEvent.stopImmediatePropagation();
				containerRef.scrollTop = 0;
			}
		}
	} );

	let ListEl,
		MasterEl;

	switch ( mode ) {
		case 'list':
			ListEl = 'ul';
			MasterEl = 'li';
			break;
		case 'table':
		default:
			ListEl = 'table';
			MasterEl = 'tr';
			break;
	}

	const masterList = masters.map( ( master, i ) => {
		const isSelected = selectedId === master[ idProp ];

		return (
			<MasterEl
				key={ master[ idProp ] }
				id={ `itsec-component-master-detail-${ instanceId }__master--${ master[ idProp ] }` }
				tabIndex={ ( isSelected || ( ! selectedId && i === 0 ) ) ? 0 : -1 }
				role="tab"
				aria-selected={ isSelected }
				aria-controls={ `itsec-component-master-detail-${ instanceId }__detail--${ master[ idProp ] }` }
				onFocus={ () => ! isSmall && select( master[ idProp ] ) }
				onClick={ () => select( master[ idProp ] ) }
				onKeyDown={ onKeyDown( i ) }
				ref={ ( ref ) => masterRefs[ master[ idProp ] ] = ref }
				className={ classnames( 'itsec-component-master-detail__master', {
					'itsec-component-master-detail__master--selected': isSelected,
					'itsec-component-master-detail__master--selected-default': ! selectedId && i === 0,
				} ) }
			>
				<MasterRender master={ master } />
			</MasterEl>
		);
	} );

	return (
		<section className={ classnames( 'itsec-component-master-detail', {
			'itsec-component-master-detail--is-small': isSmall,
			'itsec-component-master-detail--has-detail': selectedId,
		} ) }>
			<section className="itsec-component-master-detail__master-list-container" ref={ ( ref ) => containerRef = ref }>
				{ /* eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role */ }
				<ListEl className="itsec-component-master-detail__master-list" role="tablist">
					{ children }
					{ mode === 'table' ? <tbody>{ masterList }</tbody> : masterList }
				</ListEl>
			</section>
			{ masters.map( ( master ) => (
				<Detail key={ master[ idProp ] } master={ master } idProp={ idProp }
					parentInstanceId={ instanceId } isSelected={ master === selected } DetailRender={ DetailRender } />
			) ) }
		</section>
	);
}

export default compose( [
	withInstanceId,
	pure,
] )( MasterDetail );
export { default as Back } from './Back';
