/**
 * External dependencies
 */
import { uniqueId } from 'lodash';

/**
 * WordPress dependencies
 */
import { select as selectData, dispatch as dispatchData } from '@wordpress/data';
import { default as triggerApiFetch } from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import responseToError from 'packages/utils/src';

/**
 * Trigger an API Fetch request.
 *
 * @param {Object} request API Fetch Request Object.
 * @return {Object} control descriptor.
 */
export function apiFetch( request ) {
	return {
		type: 'API_FETCH',
		request,
	};
}

/**
 * Calls a selector using the current state.
 * @param {string} selectorName Selector name.
 * @param  {Array} args         Selector arguments.
 *
 * @return {Object} control descriptor.
 */
export function select( selectorName, ...args ) {
	return {
		type: 'SELECT',
		selectorName,
		args,
	};
}

/**
 * Yields action objects used in signalling that a notice is to be created.
 *
 * @see @wordpress/notices#createNotice()
 *
 * @param {?string}                status                Notice status.
 *                                                       Defaults to `info`.
 * @param {string}                 content               Notice message.
 * @param {?Object}                options               Notice options.
 * @param {?string}                options.context       Context under which to
 *                                                       group notice.
 * @param {?string}                options.id            Identifier for notice.
 *                                                       Automatically assigned
 *                                                       if not specified.
 * @param {?boolean}               options.isDismissible Whether the notice can
 *                                                       be dismissed by user.
 *                                                       Defaults to `true`.
 * @param {?number}                options.autoDismiss   Whether the notice should
 *                                                       by automatically dismissed
 *                                                       after x milliseconds.
 *                                                       Defaults to `false`.
 * @param {?Array<WPNoticeAction>} options.actions       User actions to be
 *                                                       presented with notice.
 *
 * @return {Object} control descriptor.
 */
export function createNotice( status = 'info', content, options = {} ) {
	return {
		type: 'CREATE_NOTICE',
		status,
		content,
		options: {
			context: 'ithemes-security',
			...options,
		},
	};
}

const controls = {
	API_FETCH( { request } ) {
		return triggerApiFetch( request ).catch( responseToError );
	},

	SELECT( { selectorName, args } ) {
		return selectData( 'ithemes-security/dashboard' )[ selectorName ]( ...args );
	},
	CREATE_NOTICE( { status, content, options } ) {
		if ( options.autoDismiss ) {
			options.id = options.id || uniqueId( 'itsec-auto-dismiss-' );
			setTimeout( () => dispatchData( 'core/notices' ).removeNotice( options.id, options.context ), options.autoDismiss );
		}

		dispatchData( 'core/notices' ).createNotice( status, content, options );
	},
};

export default controls;
