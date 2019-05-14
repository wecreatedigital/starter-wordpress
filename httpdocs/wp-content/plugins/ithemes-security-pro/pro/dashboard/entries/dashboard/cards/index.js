/**
 * Internal dependencies
 */
import { LineGraph, PieChart } from './renderers';
import * as activeLockouts from './active-lockouts';
import * as databaseBackup from './database-backup';
import * as malwareScan from './malware-scan';
import * as securityProfile from './security-profile/pinned';
import * as securityProfileList from './security-profile/list';
import * as versionManagement from './version-management';

const CARDS = {};

function register( slug, settings ) {
	if ( ! CARDS[ slug ] ) {
		CARDS[ slug ] = settings;
	}
}

[
	activeLockouts,
	databaseBackup,
	malwareScan,
	securityProfileList,
	securityProfile,
	versionManagement,
].forEach( ( { slug, settings } ) => (
	register( slug, settings )
) );

export function getCardRenderer( config ) {
	if ( CARDS[ config.slug ] && CARDS[ config.slug ].render ) {
		return CARDS[ config.slug ].render;
	}

	switch ( config.type ) {
		case 'line':
			return LineGraph;
		case 'pie':
			return PieChart;
	}

	return null;
}

const EMPTY = [];

export function getCardElementQueries( config ) {
	return CARDS[ config.slug ] && ( CARDS[ config.slug ].elementQueries || EMPTY );
}
