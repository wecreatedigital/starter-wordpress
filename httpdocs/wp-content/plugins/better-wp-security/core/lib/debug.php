<?php

final class ITSEC_Debug {
	public static function print_r( $data, $args = array() ) {
		echo "<style>.wp-admin .it-debug-print-r { margin-left: 170px; } .wp-admin #wpcontent .it-debug-print-r { margin-left: 0; }</style>\n";
		echo "<pre style='color:black;background:white;padding:15px;font-family:\"Courier New\",Courier,monospace;font-size:12px;white-space:pre-wrap;text-align:left;max-width:100%;' class='it-debug-print-r'>";
		echo self::get_print_r( $data, $args );
		echo "</pre>\n";
	}

	public static function get_print_r( $data, $args = array() ) {
		if ( is_bool( $args ) ) {
			$args = array( 'expand_objects' => $args );
		} else if ( is_numeric( $args ) ) {
			$args = array( 'max_depth' => $args );
		} else if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Create a deep copy so that variables aren't needlessly manipulated.
		$data = unserialize( serialize( $data ) );


		$default_args = array(
			'expand_objects' => true,
			'max_depth'      => 10,
		);
		$args = array_merge( $default_args, $args );

		if ( $args['max_depth'] < 1 ) {
			$args['max_depth'] = 100;
		}


		return self::inspect_dive( $data, $args['expand_objects'], $args['max_depth'] );
	}

	public static function backtrace( $args = array() ) {
		if ( is_string( $args ) ) {
			$args = array( 'description' => $args );
		} else if ( is_bool( $args ) ) {
			$args = array( 'expand_objects' => $args );
		} else if ( is_numeric( $args ) ) {
			$args = array( 'max_depth' => $args );
		} else if ( ! is_array( $args ) ) {
			$args = array();
		}

		$default_args = array(
			'description'    => '',
			'expand_objects' => false,
			'max_depth'      => 3,
			'type'           => '',
		);
		$args = array_merge( $default_args, $args );


		if ( isset( $args['offset'] ) ) {
			$args['offset']++;
		} else {
			$args['offset'] = 1;
		}

		$backtrace = self::get_backtrace( $args );

		if ( 'string' == $args['type'] ) {
			echo $backtrace;
		} else {
			$args['max_depth']++;
			self::print_r( $backtrace, $args );
		}
	}

	public static function get_backtrace( $args = array() ) {
		if ( is_bool( $args ) ) {
			$args = array( 'expand_objects' => $args );
		} else if ( ! is_array( $args ) ) {
			$args = array();
		}

		$default_args = array(
			'expand_objects' => false,
			'limit'          => 0,
			'offset'         => 0,
			'type'           => 'array',  // 'array' or 'string'
		);
		$args = array_merge( $default_args, $args );


		$backtrace = debug_backtrace();
		unset( $backtrace[0] );

		if ( $args['offset'] > 0 ) {
			$backtrace = array_slice( $backtrace, $args['offset'] );
		}
		if ( $args['limit'] > 0 ) {
			$backtrace = array_slice( $backtrace, 0, $args['limit'] );
		}

		$backtrace = array_values( $backtrace );


		if ( 'string' == $args['type'] ) {
			$string_backtrace = '';

			foreach ( $backtrace as $trace ) {
				$string_backtrace .= self::get_backtrace_description( $trace, $args ) . "\n";
			}

			$backtrace = $string_backtrace;
		}


		return $backtrace;
	}

	private static function get_backtrace_description( $backtrace, $backtrace_args = array() ) {
		$default_backtrace_args = array(
			'remove_abspath' => true,
		);
		$backtrace_args = array_merge( $default_backtrace_args, $backtrace_args );


		extract( $backtrace );


		$args = self::flatten_backtrace_description_args( $args );

		if ( $backtrace_args['remove_abspath'] && isset( $file ) ) {
			$file = preg_replace( '/^' . preg_quote( ABSPATH, '/' ) . '/', '', $file );
		}


		if ( isset( $class ) && isset( $type ) && isset( $function ) && isset( $args ) ) {
			return "<strong>$class$type$function(</strong>$args<strong>)</strong>";
		} else if ( isset( $function ) && isset( $args ) ) {
			if ( isset( $file ) && isset( $line ) ) {
				return "<strong>$function(</strong>$args<strong>)</strong>  on line $line of $file";
			}

			return "<strong>$function(</strong>$args<strong>)</strong>";
		}


		return 'String!';
	}

	private static function flatten_backtrace_description_args( $args, $max_depth = 2, $depth = 0 ) {
		if ( is_string( $args ) ) {
			return "'$args'";
		} else if ( is_int( $args ) ) {
			return "(int) $args";
		} else if ( is_float( $args ) ) {
			return "(float) $args";
		} else if ( is_bool( $args ) ) {
			return '(bool) ' . ( $args ? 'true' : 'false' );
		} else if ( is_object( $args ) ) {
			return '(object) ' . get_class( $args );
		} else if ( ! is_array( $args ) ) {
			return '[unknown]';
		}

		if ( $depth === $max_depth ) {
			if ( empty( $args ) ) {
				return 'array()';
			} else {
				return 'array( ' . count( $args ) . ' )';
			}
		}


		$flat_args = array();

		foreach ( $args as $arg ) {
			$flat_args[] = self::flatten_backtrace_description_args( $arg, $max_depth, $depth + 1 );
		}

		$args = implode( ', ', $flat_args );

		if ( ! empty( $args ) ) {
			$args = " $args ";
		}

		if ( 0 === $depth ) {
			return $args;
		}

		return "array($args)";
	}

	private static function pad( $depth, $pad = '    ' ) {
		$retval = '';

		for ( $x = 0; $x <= $depth; $x++ ) {
			$retval .= $pad;
		}

		return $retval;
	}

	private static function is_callable_function( $function ) {
		if ( ! is_callable( $function ) ) {
			return false;
		}

		if ( ! isset( $GLOBALS['itsec_debug_cached_values'] ) ) {
			$GLOBALS['itsec_debug_cached_values'] = array();
		}

		if ( ! isset( $GLOBALS['itsec_debug_cached_values']['ini_get:disable_functions'] ) ) {
			$GLOBALS['itsec_debug_cached_values']['var:disable_functions'] = preg_split( '/\s*,\s*/', (string) ini_get( 'disable_functions' ) );
		}

		if ( in_array( $function, $GLOBALS['itsec_debug_cached_values']['var:disable_functions'] ) ) {
			return false;
		}

		if ( ! isset( $GLOBALS['itsec_debug_cached_values']['ini_get:suhosin.executor.func.blacklist'] ) ) {
			$GLOBALS['itsec_debug_cached_values']['ini_get:suhosin.executor.func.blacklist'] = preg_split( '/\s*,\s*/', (string) ini_get( 'suhosin.executor.func.blacklist' ) );
		}

		if ( in_array( $function, $GLOBALS['itsec_debug_cached_values']['ini_get:suhosin.executor.func.blacklist'] ) ) {
			return false;
		}

		return true;
	}

	private static function inspect_dive( $data, $expand_objects, $max_depth, $depth = 0, $show_array_header = true ) {
		$pad = self::pad( $depth, '    ' );

		if ( is_string( $data ) ) {
			if ( empty( $data ) ) {
				return "<strong>[empty string]</strong>";
			} else {
				if ( self::is_callable_function( 'mb_detect_encoding' ) && ( 'UTF-8' !== mb_detect_encoding( $data, 'UTF-8', true ) ) && self::is_callable_function( 'utf8_encode' ) ) {
					$data = utf8_encode( $data );
				}

				$flags = ENT_COMPAT;

				if ( defined( 'ENT_HTML401' ) ) {
					$flags |= ENT_HTML401;
				}

				return htmlspecialchars( $data, $flags, 'UTF-8', false );
			}
		}

		if ( is_bool( $data ) ) {
			return ( $data ) ? '<strong>[boolean] true</strong>' : '<strong>[boolean] false</strong>';
		}

		if ( is_null( $data ) ) {
			return '<strong>null</strong>';
		}

		if ( is_object( $data ) ) {
			$class_name = get_class( $data );
			$retval = "<strong>Object</strong> $class_name";

			if ( ! $expand_objects || ( $depth == $max_depth ) ) {
				return $retval;
			}

			$vars = get_object_vars( $data );

			if ( empty( $vars ) ) {
				$vars = '';
			} else {
				$vars = self::inspect_dive( $vars, $expand_objects, $max_depth, $depth, false );
			}

			$retval .= "$vars";

			return $retval;
		}

		if ( is_array( $data ) ) {
			$retval = ( $show_array_header ) ? '<strong>Array</strong>' : '';

			if ( empty( $data ) ) {
				return "$retval()";
			}
			if ( $depth == $max_depth ) {
				return "$retval( " . count( $data ) . " )";
			}

			$max = 0;

			foreach ( array_keys( $data ) as $index ) {
				if ( strlen( $index ) > $max ) {
					$max = strlen( $index );
				}
			}

			foreach ( $data as $index => $val ) {
				$spaces = self::pad( $max - strlen( $index ), ' ' );
				$retval .= "\n$pad" . htmlspecialchars( $index ) . "$spaces  <strong>=&gt;</strong> " . self::inspect_dive( $val, $expand_objects, $max_depth, $depth + 1 );
			}

			return $retval;
		}

		return '<strong>[' . gettype( $data ) . ']</strong> ' . $data;
	}
}
