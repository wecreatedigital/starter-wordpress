/**
 * Internal dependencies
 */
import User from './user';
import Protected from './protected';
import Date from './date';
import Static from './static';
import './style.scss';

function Header() {
	return (
		<header className="itsec-header">
			<div className="itsec-header__welcome">
				<User />
				<Date />
				<Protected />
			</div>
			<Static />
		</header>
	);
}

export default Header;
