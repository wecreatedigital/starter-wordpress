const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const ManifestPlugin = require( 'webpack-manifest-plugin' );
const FilterWarningsPlugin = require( 'webpack-filter-warnings-plugin' );
const debug = process.env.NODE_ENV !== 'production';
const glob = require( 'glob' );
const path = require( 'path' );
const autoprefixer = require( 'autoprefixer' );
const spawn = require( 'child_process' ).spawnSync;
const crypto = require( 'crypto' );
const webpack = require( 'webpack' );

/*
Convert the wildcard entry points into an entry object suitable for Webpack consumption.

This requires an object where the key is the path to the destination file without a file extension
and the value is the path to the source file.

For Example:
[ 'pro/dashboard/entries/dashboard.js' ]
{ 'dashboard/dashboard': './pro/dashboard/entries/dashboard.js' }
*/
const entries = glob.sync( 'pro/**/entries/*.js' ).reduce( function( acc, entry ) {
	const baseName = path.basename( entry, '.js' );
	let out = path.join( entry, '..', '..', baseName );
	out = out.replace( /^pro\//, '' );

	acc[ out ] = './' + entry; // The entry needs to be marked as relative to the current directory.

	return acc;
}, {} );

function camelCaseDash( string ) {
	return string.replace(
		/-([a-z])/g,
		( match, letter ) => letter.toUpperCase(),
	);
}

const formatRequest = ( request ) => {
	// '@wordpress/api-fetch' -> [ '@wordpress', 'api-fetch' ]
	const [ , name ] = request.split( '/' );

	// { this: [ 'wp', 'apiFetch' ] }
	return {
		this: [ 'wp', camelCaseDash( name ) ],
	};
};

const wordpressExternals = ( context, request, callback ) => {
	if ( /^@wordpress\//.test( request ) ) {
		callback( null, formatRequest( request ), 'this' );
	} else {
		callback();
	}
};

const externals = [
	{
		react: 'React',
		'react-dom': 'ReactDOM',
		tinymce: 'tinymce',
		moment: 'moment',
		jquery: 'jQuery',
		lodash: 'lodash',
		'lodash-es': 'lodash',
	},
	wordpressExternals,
];

const config = {
	context: __dirname,
	devtool: debug ? 'inline-sourcemap' : false,
	mode: debug ? 'development' : 'production',
	entry: entries,
	output: {
		path: path.join( __dirname, 'dist' ),
		filename: debug ? '[name].js' : '[name].min.js',
		jsonpFunction: 'itsecWebpackJsonP',
	},
	externals,
	module: {
		rules: [
			{ parser: { amd: false } },
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: [
					{
						loader: 'babel-loader',
					},
				],
			},
			{
				test: /\.s?css$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							url: false,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							plugins: [
								autoprefixer,
							],
						},
					},
					{
						loader: 'sass-loader',
						options: {
							outputStyle: debug ? 'nested' : 'compressed',
							sourceMap: debug ? 'inline' : false,
							includePaths: [
								'packages/style-guide/src',
							],
						},
					},
				],
			},
			{
				test: /\.svg$/,
				exclude: /node_modules/,
				use: [
					{
						loader: 'svg-react-loader',
						query: {
							classIdPrefix: 'itsec-icon-[name]-[hash:5]__',
						},
					},
				],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin( {
			filename: debug ? '[name].css' : '[name].min.css',
		} ),
		new FilterWarningsPlugin( {
			exclude: /mini-css-extract-plugin[^]*Conflicting order between:/,
		} ),
	],
	resolve: {
		modules: [
			path.resolve( './' ),
			path.resolve( './node_modules' ),
		],
	},
	optimization: {},
};

if ( ! debug ) {
	/** @type {WeakMap<Chunk[], Record<string, string>>} */
	const splitChunkNameCache = new WeakMap();

	const hashFilename = ( name ) => {
		return crypto
			.createHash( 'md4' )
			.update( name )
			.digest( 'hex' )
			.slice( 0, 8 );
	};

	config.optimization.splitChunks = {
		chunks: 'all',
		maxInitialRequests: 10,
		hidePathInfo: true,
		cacheGroups: {
			recharts: {
				test: /[\\/]node_modules[\\/](recharts)[\\/]/,
				name: 'vendors/recharts',
				enforce: true,
			},
		},
		name( module, chunks, cacheGroup ) {
			const automaticNamePrefix = cacheGroup === 'vendors' ? 'vendors' : '',
				automaticNameDelimiter = '~';

			let cacheEntry = splitChunkNameCache.get( chunks );
			if ( cacheEntry === undefined ) {
				cacheEntry = {};
				splitChunkNameCache.set( chunks, cacheEntry );
			} else if ( cacheGroup in cacheEntry ) {
				return cacheEntry[ cacheGroup ];
			}
			const names = chunks.filter( ( c ) => !! c.name ).map( ( c ) => c.name.split( '/' ) );
			if ( ! names.length || ! names.every( Boolean ) ) {
				cacheEntry[ cacheGroup ] = undefined;
				return;
			}
			names.sort( ( a, b ) => b.length - a.length );
			const prefix = typeof automaticNamePrefix === 'string' ? automaticNamePrefix : cacheGroup;
			const namePrefix = prefix ? prefix + '/' : '';

			/*
			[
				[ 'dashboard', 'dashboard' ]
				[ 'dashboard', 'widget' ],
			]
			-> 'dashboard/dashboard~widget'

			[
				[ 'dashboard', 'dashboard' ]
				[ 'fingerprinting', 'manager' ],
			]
			-> 'dist/dashboard-dist-dashboard~fingerprinting-dist-manager'
			 */

			let name = '';
			const max = names[ 0 ].length;

			for ( let i = 0; i < max; i++ ) {
				if ( allAtPosSame( names, i ) ) {
					name += names[ 0 ][ i ] + '/';
				} else {
					name = '';
					name += names[ 0 ].slice( 0, i ).join( '/' ) + '/';
					name += names.map( ( parts ) => parts[ i ] ).filter( ( part ) => typeof part === 'string' ).join( automaticNameDelimiter ) + '/';
				}
			}

			name = namePrefix + name;

			if ( '/' === name.charAt( name.length - 1 ) ) {
				name = name.substring( 0, name.length - 1 );
			}

			if ( name.length > 100 ) {
				name = name.slice( 0, 100 ) + automaticNameDelimiter + hashFilename( name );
			}

			cacheEntry[ cacheGroup ] = name;
			return name;
		},
	};

	function allAtPosSame( list, pos ) {
		let prev = null;

		for ( const item of list ) {
			if ( prev === null ) {
				prev = item[ pos ];
			} else if ( prev !== item[ pos ] ) {
				return false;
			}
		}

		return true;
	}

	config.plugins.push( new webpack.HashedModuleIdsPlugin() );

	config.plugins.push( new ManifestPlugin( {
		fileName: 'manifest.php',
		generate( seed, files ) {
			const manifest = {};
			const splitChunks = [];

			for ( const file of files ) {
				if ( ! file.chunk || ! file.chunk.name ) {
					continue;
				}

				if ( ! manifest[ file.chunk.name ] ) {
					manifest[ file.chunk.name ] = generateChunk( file.chunk );
				}

				manifest[ file.chunk.name ].files.push( file.name );

				if ( ! file.chunk.hasRuntime() ) {
					splitChunks.push( file );
				}
			}

			for ( const file of splitChunks ) {
				file.chunk.groupsIterable.forEach( ( group ) => {
					if ( manifest[ group.name ] && ! manifest[ group.name ].vendors.includes( file.chunk.name ) ) {
						manifest[ group.name ].vendors.push( file.chunk.name );
					}
				} );
			}

			return manifest;
		},
		serialize( data ) {
			const out = spawn( 'php', [
				path.join( __dirname, 'bin', 'json-to-php.php' ),
				JSON.stringify( data ),
			] );

			if ( out.status !== 0 ) {
				throw Error( 'Failed to generate PHP manifest.' );
			}

			return `<?php return ${ out.stdout };`;
		},
	} ) );
}

module.exports = config;

/**
 * Generate a chunk manifest entry.
 *
 * @param {Chunk} chunk
 * @return {{runtime: boolean, vendors: Array, hash: string, dependencies: Array}} Manifest object.
 */
function generateChunk( chunk ) {
	const chunkManifest = {
		runtime: chunk.hasRuntime(),
		files: [],
		hash: crypto.createHash( 'md4' ).update( JSON.stringify( chunk.contentHash ) ).digest( 'hex' ),
		contentHash: chunk.contentHash,
		vendors: [],
		dependencies: [],
	};

	chunk.getModules().forEach( ( module ) => {
		if ( module.external && module.userRequest ) {
			if ( module.userRequest.includes( '@wordpress/' ) ) {
				chunkManifest.dependencies.push( `wp-${ module.userRequest.replace( '@wordpress/', '' ) }` );
			} else {
				chunkManifest.dependencies.push( module.userRequest );
			}
		}
	} );

	chunkManifest.dependencies.sort();

	return chunkManifest;
}
