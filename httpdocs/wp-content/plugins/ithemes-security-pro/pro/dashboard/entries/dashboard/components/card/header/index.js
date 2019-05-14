/**
 * Internal dependencies
 */
import './style.scss';

export { default as Date } from './date';
export { default as Status } from './status';
export { default as Title } from './title';

export default function Header( { children } ) {
	return (
		<header className="itsec-card-header itsec-card__util-padding">
			{ children }
		</header>
	);
}

