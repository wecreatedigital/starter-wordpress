/**
 * WordPress dependencies
 */
import { RawHTML, Component } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

export default class LogModal extends Component {
	static #html = {};

	state = {
		html: null,
	};

	componentDidMount() {
		this.fetchHtml();
	}

	shouldComponentUpdate( nextProps, nextState ) {
		return this.props.id !== nextProps.id || this.state.html !== nextState.html;
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.id !== this.props.id ) {
			this.fetchHtml();
		}
	}

	fetchHtml = () => {
		if ( LogModal.#html[ this.props.id ] ) {
			this.setState( { html: LogModal.#html[ this.props.id ] } );
		}

		const form = new FormData();
		form.set( 'id', this.props.id );
		form.set( 'nonce', this.props.nonce );
		form.set( 'action', 'itsec_logs_page' );

		fetch( this.props.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: form,
		} ).then( ( response ) => response.json() ).then( ( response ) => {
			LogModal.#html[ this.props.id ] = response.response;
			this.setState( { html: response.response } );
		} );
	};

	render() {
		return (
			<Modal
				title={ __( 'Log Details', 'ithemes-security-pro' ) }
				overlayClassName="itsec-log-modal"
				onRequestClose={ this.props.onClose }>
				<div className="itsec-log-modal__content">
					{ this.state.html ?
						<RawHTML>{ this.state.html }</RawHTML> :
						<span>{ __( 'Loading', 'ithemes-security-pro' ) }</span> }
				</div>
			</Modal>
		);
	}
}
