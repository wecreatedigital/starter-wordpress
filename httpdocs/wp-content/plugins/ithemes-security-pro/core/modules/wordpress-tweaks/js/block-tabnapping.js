document.addEventListener( 'DOMContentLoaded', function() {
	// Extract from https://github.com/lancedikson/bowser licensed MIT
	/**
	 * Get first matched item for a string
	 * @param {RegExp} regexp
	 * @param {String} ua
	 * @return {Array|{index: number, input: string}|*|boolean|string}
	 */
	function getFirstMatch( regexp, ua ) {
		var match = ua.match( regexp );
		return ( match && match.length > 0 && match[ 1 ] ) || '';
	}

	/**
	 * Get second matched item for a string
	 * @param regexp
	 * @param {String} ua
	 * @return {Array|{index: number, input: string}|*|boolean|string}
	 */
	function getSecondMatch( regexp, ua ) {
		var match = ua.match( regexp );
		return ( match && match.length > 1 && match[ 2 ] ) || '';
	}

	var commonVersionIdentifier = /version\/(\d+(\.?_?\d+)+)/i;

	var browsersList = [
		/* Googlebot */
		{
			test    : [ /googlebot/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Googlebot',
				};
				var version = getFirstMatch( /googlebot\/(\d+(\.\d+))/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		},
		/* Opera < 13.0 */
		{
			test    : [ /opera/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Opera',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:opera)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		},
		/* Opera > 13.0 */
		{
			test    : [ /opr\/|opios/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Opera',
				};
				var version = getFirstMatch( /(?:opr|opios)[\s/](\S+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /SamsungBrowser/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Samsung Internet for Android',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:SamsungBrowser)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /Whale/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'NAVER Whale Browser',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:whale)[\s/](\d+(?:\.\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /MZBrowser/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'MZ Browser',
				};
				var version = getFirstMatch( /(?:MZBrowser)[\s/](\d+(?:\.\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /focus/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Focus',
				};
				var version = getFirstMatch( /(?:focus)[\s/](\d+(?:\.\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /swing/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Swing',
				};
				var version = getFirstMatch( /(?:swing)[\s/](\d+(?:\.\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /coast/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Opera Coast',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:coast)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /yabrowser/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Yandex Browser',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:yabrowser)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /ucbrowser/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'UC Browser',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:ucbrowser)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /Maxthon|mxios/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Maxthon',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:Maxthon|mxios)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /epiphany/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Epiphany',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:epiphany)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /puffin/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Puffin',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:puffin)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /sleipnir/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Sleipnir',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:sleipnir)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /k-meleon/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'K-Meleon',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /(?:k-meleon)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /micromessenger/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'WeChat',
				};
				var version = getFirstMatch( /(?:micromessenger)[\s/](\d+(\.?_?\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /msie|trident/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Internet Explorer',
				};
				var version = getFirstMatch( /(?:msie |rv:)(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /edg([ea]|ios)/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Microsoft Edge',
				};
				var version = getSecondMatch( /edg([ea]|ios)\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /vivaldi/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Vivaldi',
				};
				var version = getFirstMatch( /vivaldi\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /seamonkey/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'SeaMonkey',
				};
				var version = getFirstMatch( /seamonkey\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /sailfish/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Sailfish',
				};
				var version = getFirstMatch( /sailfish\s?browser\/(\d+(\.\d+)?)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /silk/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Amazon Silk',
				};
				var version = getFirstMatch( /silk\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /phantom/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'PhantomJS',
				};
				var version = getFirstMatch( /phantomjs\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /slimerjs/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'SlimerJS',
				};
				var version = getFirstMatch( /slimerjs\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /blackberry|\bbb\d+/i, /rim\stablet/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'BlackBerry',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /blackberry[\d]+\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /(web|hpw)[o0]s/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'WebOS Browser',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua ) || getFirstMatch( /w(?:eb)?[o0]sbrowser\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /bada/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Bada',
				};
				var version = getFirstMatch( /dolfin\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /tizen/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Tizen',
				};
				var version = getFirstMatch( /(?:tizen\s?)?browser\/(\d+(\.?_?\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /qupzilla/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'QupZilla',
				};
				var version = getFirstMatch( /(?:qupzilla)[\s/](\d+(\.?_?\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /firefox|iceweasel|fxios/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Firefox',
				};
				var version = getFirstMatch( /(?:firefox|iceweasel|fxios)[\s/](\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /chromium/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Chromium',
				};
				var version = getFirstMatch( /(?:chromium)[\s/](\d+(\.?_?\d+)+)/i, ua ) || getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		}, {
			test    : [ /chrome|crios|crmo/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Chrome',
				};
				var version = getFirstMatch( /(?:chrome|crios|crmo)\/(\d+(\.?_?\d+)+)/i, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		},
		/* Android Browser */
		{
			test    : function test( ua ) {
				var notLikeAndroid = !/like android/i.test( ua );
				var butAndroid = /android/i.test( ua );
				return notLikeAndroid && butAndroid;
			},
			describe: function describe( ua ) {
				var browser = {
					name: 'Android Browser',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		},
		/* Safari */
		{
			test    : [ /safari|applewebkit/i ],
			describe: function describe( ua ) {
				var browser = {
					name: 'Safari',
				};
				var version = getFirstMatch( commonVersionIdentifier, ua );

				if ( version ) {
					browser.version = version;
				}

				return browser;
			},
		},
		/* Something else */
		{
			test    : [ /.*/i ],
			describe: function describe( ua ) {
				return {
					name   : getFirstMatch( /^(.*)\/(.*) /, ua ),
					version: getSecondMatch( /^(.*)\/(.*) /, ua ),
				};
			},
		},
	];

	function compareVersions( versionA, versionB ) {
		var isLoose = arguments.length > 2 && arguments[ 2 ] !== undefined ? arguments[ 2 ] : false;
		// 1) get common precision for both versions, for example for "10.0" and "9" it should be 2
		var versionAPrecision = getVersionPrecision( versionA );
		var versionBPrecision = getVersionPrecision( versionB );
		var precision = Math.max( versionAPrecision, versionBPrecision );
		var lastPrecision = 0;
		var chunks = map( [ versionA, versionB ], function( version ) {
			var delta = precision - getVersionPrecision( version ); // 2) "9" -> "9.0" (for precision = 2)

			var _version = version + new Array( delta + 1 ).join( '.0' ); // 3) "9.0" -> ["000000000"", "000000009"]

			return map( _version.split( '.' ), function( chunk ) {
				return new Array( 20 - chunk.length ).join( '0' ) + chunk;
			} ).reverse();
		} ); // adjust precision for loose comparison

		if ( isLoose ) {
			lastPrecision = precision - Math.min( versionAPrecision, versionBPrecision );
		} // iterate in reverse order by reversed chunks array

		precision -= 1;

		while ( precision >= lastPrecision ) {
			// 4) compare: "000000009" > "000000010" = false (but "9" > "10" = true)
			if ( chunks[ 0 ][ precision ] > chunks[ 1 ][ precision ] ) {
				return 1;
			}

			if ( chunks[ 0 ][ precision ] === chunks[ 1 ][ precision ] ) {
				if ( precision === lastPrecision ) {
					// all version chunks are same
					return 0;
				}

				precision -= 1;
			} else if ( chunks[ 0 ][ precision ] < chunks[ 1 ][ precision ] ) {
				return -1;
			}
		}
	}

	function getVersionPrecision( version ) {
		return version.split( '.' ).length;
	}

	function map( arr, iterator ) {
		var result = [];
		var i;

		if ( Array.prototype.map ) {
			return Array.prototype.map.call( arr, iterator );
		}

		for ( i = 0; i < arr.length; i += 1 ) {
			result.push( iterator( arr[ i ] ) );
		}

		return result;
	}

	function getBrowser() {

		if ( !window.navigator || !window.navigator.userAgent ) {
			return false;
		}

		var agent = window.navigator.userAgent;

		for ( var i = 0; i < browsersList.length; i++ ) {
			var _browser = browsersList[ i ];

			if ( typeof _browser.test === 'function' ) {
				if ( _browser.test( agent ) ) {
					return _browser.describe( agent );
				}

				continue;
			}

			if ( _browser.test instanceof Array ) {
				for ( var j = 0; j < _browser.test.length; j++ ) {
					var regex = _browser.test[ j ];

					if ( regex.test( agent ) ) {
						return _browser.describe( agent );
					}
				}
			}
		}

		return false;
	}

	var noOpenerSupport = {
		'Firefox'                     : '52',
		'Chrome'                      : '49',
		'Safari'                      : '10.3',
		'Opera'                       : '36',
		operaMobile                   : '46',
		'Android Browser'             : '67',
		chromeForAndroid              : '71',
		firefoxForAndroid             : '64',
		'UC Browser'                  : '11.8',
		'Samsung Internet for Android': '5',
		qcBrowser                     : '1.2',
		baiduBrowser                  : '7.12',
	};

	var browser = getBrowser();
	var hasNoOpener;

	if ( browser ) {
		switch ( browser.name ) {
			case 'Safari':
				hasNoOpener = browser.version && compareVersions( browser.version, noOpenerSupport.Safari ) !== -1;
				break;
			case 'Chrome':
				// Has it earlier, but chrome started blocking in this version
				// since chrome can appear in so many UAs be conservative and only use the noopener attr when
				// required
				hasNoOpener = browser.version && compareVersions( browser.version, '72' ) !== -1;
				break;
			case 'Opera':
				hasNoOpener = browser.version && compareVersions( browser.version, '46' ) !== -1;
				break;
			default:
				hasNoOpener = false;
				break;
		}
	}

	if ( hasNoOpener ) {
		var links = document.querySelectorAll( 'a[target=_blank]' );

		for ( var i = 0; i < links.length; i++ ) {
			var link = links[ i ];

			var rel = link.getAttribute( 'rel' );

			if ( typeof rel !== 'string' ) {
				rel = '';
			}

			if ( rel.indexOf( 'noopener' ) !== -1 ) {
				continue;
			}

			if ( rel.length > 0 ) {
				rel += ' noopener';
			} else {
				rel += 'noopener';
			}

			link.setAttribute( 'rel', rel );
		}

	} else {
		blankshield( document.querySelectorAll( 'a[target=_blank]' ) );
	}
} );
