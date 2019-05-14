export default class WPError {
	#errors = {};
	#errorData = {};

	/**
	 * WP Error object.
	 *
	 * Close to the WordPress PHP WP Error object. Really only meant to be used for interfacing
	 * with server generated PHP errors, not for JS programming.
	 *
	 * @param {string} [code]
	 * @param {string} [message]
	 * @param {*} [data]
	 */
	constructor( code = undefined, message = undefined, data = undefined ) {
		if ( ! code ) {
			return;
		}

		if ( message ) {
			this.#errors[ code ] = [ message ];
		}

		if ( data ) {
			this.#errorData[ code ] = data;
		}
	}

	/**
	 * Create a WPError from a PHP object.
	 *
	 * @param {Object} object WPError like object. {@see isWPError}
	 * @return {WPError} WPError instance.
	 */
	static fromPHPObject( object ) {
		const error = new WPError();
		error.#errors = object.errors;
		error.#errorData = object.error_data;

		return error;
	}

	/**
	 * Create a WPError from a REST API error.
	 *
	 * @param {Object} object Api WP Error like object. {@see isApiError}
	 * @return {WPError} WPError instance.
	 */
	static fromApiError( object ) {
		const error = new WPError();
		error.#errors[ object.code ] = [ object.message ];
		error.#errorData[ object.code ] = object.data;

		if ( object.additional_errors ) {
			for ( const additional of object.additional_errors ) {
				error.#errors[ additional.code ] = [ additional.message ];
				error.#errorData[ additional.code ] = additional.data;
			}
		}

		return error;
	}

	/**
	 * Get all the codes.
	 *
	 * @return {string[]} Array of error codes.
	 */
	getErrorCodes = () => {
		return Object.keys( this.#errors );
	};

	/**
	 *Get the main error code.
	 *
	 * @return {string|undefined} Primary error code or undefined if no errors.
	 */
	getErrorCode = () => {
		return this.getErrorCodes()[ 0 ];
	};

	/**
	 * Get all the error messages.
	 *
	 * @param {string} [code] Optionally limit to errors from a specific code.
	 * @return {Array<string>} Array of error messages.
	 */
	getErrorMessages = ( code = undefined ) => {
		if ( code ) {
			return this.#errors[ code ];
		}

		const messages = [];

		for ( const errorCode in this.#errors ) {
			if ( this.#errors.hasOwnProperty( errorCode ) ) {
				messages.concat( this.#errors[ errorCode ] );
			}
		}

		return messages;
	};

	/**
	 * Get the error message.
	 *
	 * @param {string} [code] Optionally specify the code.
	 * @return {*|undefined} Primary error message.
	 */
	getErrorMessage = ( code = undefined ) => {
		code = code || this.getErrorCode();

		return this.getErrorMessages( code )[ 0 ];
	};

	/**
	 * Get error data.
	 *
	 * @param {string} [code]
	 * @return {*|undefined} Error data for this code, or undefined.
	 */
	getErrorData = ( code = undefined ) => {
		code = code || this.getErrorCode();

		return this.#errorData[ code ];
	};
}
