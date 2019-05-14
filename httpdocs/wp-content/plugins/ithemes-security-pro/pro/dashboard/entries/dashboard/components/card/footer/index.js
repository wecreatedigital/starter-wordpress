/**
 * Internal dependencies
 */
import './style.scss';

function Footer( { children } ) {
	return (
		<footer className="itsec-card-footer__actions">
			{ children }
		</footer>
	);
}

export default Footer;
export { default as FooterSchemaActions } from './schema-actions';
