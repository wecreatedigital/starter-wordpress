<?php

abstract class ITSEC_REST_Dashboard_Controller extends WP_REST_Controller {

	public function filter_response_by_context( $data, $context ) {

		$schema = $this->get_item_schema();

		foreach ( $data as $key => &$value ) {
			if ( empty( $schema['properties'][ $key ] ) ) {
				continue;
			}

			if ( ! $this->filter_response_property_by_context( $value, $context, $schema['properties'][ $key ] ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}

	/**
	 * Filter an individual property in the response.
	 *
	 * @param mixed  $data
	 * @param string $context
	 * @param array  $schema
	 *
	 * @return bool False to omit the property from the response.
	 */
	protected function filter_response_property_by_context( &$data, $context, $schema ) {

		if ( ! empty( $schema['context'] ) && ! in_array( $context, $schema['context'], true ) ) {
			return false;
		}

		if ( isset( $schema['oneOf'] ) ) {
			foreach ( $schema['oneOf'] as $oneOf ) {
				if ( true === self::_validate_value_from_schema( $data, $oneOf, '' ) ) {
					return $this->filter_response_property_by_context( $data, $context, $oneOf );
				}
			}

			return true;
		}

		if ( isset( $schema['type'] ) ) {
			if ( 'object' === $schema['type'] && ! empty( $schema['properties'] ) ) {
				foreach ( $data as $property => $property_data ) {
					if ( empty( $schema['properties'][ $property ] ) ) {
						continue;
					}

					$property_schema = $schema['properties'][ $property ];

					if ( ! $this->filter_response_property_by_context( $property_data, $context, $property_schema ) ) {
						unset( $data[ $property ] );
					}
				}
			}

			if ( 'array' === $schema['type'] && ! empty( $schema['items'] ) ) {
				foreach ( $data as $i => $item_data ) {
					if ( ! $this->filter_response_property_by_context( $item_data, $context, $schema['items'] ) ) {
						unset( $data[ $i ] );
					}
				}
			}
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {

		$schema            = $this->get_item_schema();
		$schema_properties = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
		$endpoint_args     = array();

		foreach ( $schema_properties as $field_id => $params ) {

			// Arguments specified as `readonly` are not allowed to be set.
			if ( ! empty( $params['readonly'] ) ) {
				continue;
			}

			$endpoint_args[ $field_id ] = $params;
			unset( $endpoint_args[ $field_id ]['arg_options'] );

			$endpoint_args[ $field_id ]['validate_callback'] = array( __CLASS__, '_validate_request_arg' );
			$endpoint_args[ $field_id ]['sanitize_callback'] = array( __CLASS__, '_sanitize_request_arg' );

			if ( WP_REST_Server::CREATABLE !== $method ) {
				unset(
					$endpoint_args[ $field_id ]['default'],
					$endpoint_args[ $field_id ]['required']
				);
			}

			// Merge in any options provided by the schema property.
			if ( isset( $params['arg_options'] ) ) {
				// Only use required / default from arg_options on CREATABLE endpoints.
				if ( WP_REST_Server::CREATABLE !== $method ) {
					$params['arg_options'] = array_diff_key(
						$params['arg_options'],
						array(
							'required' => '',
							'default'  => '',
						)
					);
				}

				$endpoint_args[ $field_id ] = wp_parse_args( $params['arg_options'], $endpoint_args[ $field_id ] );
			}
		}

		return $endpoint_args;
	}

	/**
	 * Build the allow data for Target Hints.
	 *
	 * @param string $route
	 * @param array  $url_params
	 *
	 * @return string[]
	 */
	protected function build_allow_target_hints( $route, $url_params ) {

		$request = new WP_REST_Request( '', $route );
		$request->set_url_params( $url_params );

		$allow = array();

		$request->set_method( 'GET' );
		if ( true === $this->get_item_permissions_check( $request ) ) {
			$allow[] = 'GET';
		}

		$request->set_method( 'PUT' );
		if ( true === $this->update_item_permissions_check( $request ) ) {
			$allow[] = 'PUT';
		}

		$request->set_method( 'DELETE' );
		if ( true === $this->delete_item_permissions_check( $request ) ) {
			$allow[] = 'DELETE';
		}

		return $allow;
	}

	/**
	 * Validate the request arg.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return bool|WP_Error
	 */
	public static function _validate_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();

		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return true;
		}

		$args = $attributes['args'][ $param ];

		return self::_validate_value_from_schema( $value, $args, $param );
	}

	/**
	 * Validate the value based on its schema definition.
	 *
	 * @param mixed  $value
	 * @param array  $args
	 * @param string $param
	 *
	 * @return true|WP_Error
	 */
	private static function _validate_value_from_schema( $value, $args, $param ) {
		if ( isset( $args['oneOf'] ) ) {
			$match = 0;

			foreach ( $args['oneOf'] as $oneOf ) {
				if ( true === self::_validate_value_from_schema( $value, $oneOf, $param ) ) {
					$match ++;
				}
			}

			if ( 1 === $match ) {
				return true;
			}

			// Todo improve error message
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s does not match exactly one schema.', 'it-l10n-ithemes-security-pro' ), $param ) );
		}

		if ( 'array' === $args['type'] ) {
			if ( ! is_array( $value ) ) {
				$value = preg_split( '/[\s,]+/', $value );
			}
			if ( ! wp_is_numeric_array( $value ) ) {
				/* translators: 1: parameter, 2: type name */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $param, 'array' ) );
			}
			foreach ( $value as $index => $v ) {
				$is_valid = self::_validate_value_from_schema( $v, $args['items'], $param . '[' . $index . ']' );
				if ( is_wp_error( $is_valid ) ) {
					return $is_valid;
				}
			}
		}

		if ( 'object' === $args['type'] ) {
			if ( $value instanceof stdClass ) {
				$value = (array) $value;
			}
			if ( ! is_array( $value ) ) {
				/* translators: 1: parameter, 2: type name */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $param, 'object' ) );
			}

			if ( isset( $args['properties'] ) ) {
				$missing_props = array();

				foreach ( $args['properties'] as $property => $prop_schema ) {
					if ( isset( $prop_schema['required'] ) && true === $prop_schema['required'] && ! array_key_exists( $property, $value ) ) {
						$missing_props[] = "{$param}[{$property}]";
					}
				}

				if ( $missing_props ) {
					return new WP_Error(
						'rest_missing_callback_param',
						sprintf( __( 'Missing parameter(s): %s' ), implode( ', ', $missing_props ) ),
						array(
							'status' => 400,
							'params' => $missing_props,
						)
					);
				}
			}

			foreach ( $value as $property => $v ) {
				if ( isset( $args['properties'][ $property ] ) ) {
					$is_valid = self::_validate_value_from_schema( $v, $args['properties'][ $property ], $param . '[' . $property . ']' );
					if ( is_wp_error( $is_valid ) ) {
						return $is_valid;
					}
				} elseif ( isset( $args['additionalProperties'] ) ) {
					if ( false === $args['additionalProperties'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not a valid property of Object.' ), $property ) );
					}

					if ( is_array( $args['additionalProperties'] ) ) {
						$is_valid = self::_validate_value_from_schema( $v, $args['additionalProperties'], "{$param}[{$property}]" );

						if ( is_wp_error( $is_valid ) ) {
							return $is_valid;
						}
					}
				}
			}
		}

		if ( ! empty( $args['enum'] ) ) {
			if ( ! in_array( $value, $args['enum'], true ) ) {
				/* translators: 1: parameter, 2: list of valid values */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not one of %2$s.' ), $param, implode( ', ', $args['enum'] ) ) );
			}
		}

		if ( in_array( $args['type'], array( 'integer', 'number' ) ) && ! is_numeric( $value ) ) {
			/* translators: 1: parameter, 2: type name */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $param, $args['type'] ) );
		}

		if ( 'integer' === $args['type'] && round( (float) $value ) !== (float) $value ) {
			/* translators: 1: parameter, 2: type name */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $param, 'integer' ) );
		}

		if ( 'boolean' === $args['type'] && ! rest_is_boolean( $value ) ) {
			/* translators: 1: parameter, 2: type name */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $value, 'boolean' ) );
		}

		if ( 'string' === $args['type'] && ! is_string( $value ) ) {
			/* translators: 1: parameter, 2: type name */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s.' ), $param, 'string' ) );
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time':
					if ( ! rest_parse_date( $value ) ) {
						return new WP_Error( 'rest_invalid_date', __( 'Invalid date.' ) );
					}
					break;

				case 'email':
					if ( ! is_email( $value ) ) {
						return new WP_Error( 'rest_invalid_email', __( 'Invalid email address.' ) );
					}
					break;
				case 'ip':
					if ( ! rest_is_ip_address( $value ) ) {
						/* translators: %s: IP address */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not a valid IP address.' ), $value ) );
					}
					break;
			}
		}

		if ( in_array( $args['type'], array( 'number', 'integer' ), true ) && ( isset( $args['minimum'] ) || isset( $args['maximum'] ) ) ) {
			if ( isset( $args['minimum'] ) && ! isset( $args['maximum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && $value <= $args['minimum'] ) {
					/* translators: 1: parameter, 2: minimum number */
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be greater than %2$d' ), $param, $args['minimum'] ) );
				} elseif ( empty( $args['exclusiveMinimum'] ) && $value < $args['minimum'] ) {
					/* translators: 1: parameter, 2: minimum number */
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be greater than or equal to %2$d' ), $param, $args['minimum'] ) );
				}
			} elseif ( isset( $args['maximum'] ) && ! isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMaximum'] ) && $value >= $args['maximum'] ) {
					/* translators: 1: parameter, 2: maximum number */
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be less than %2$d' ), $param, $args['maximum'] ) );
				} elseif ( empty( $args['exclusiveMaximum'] ) && $value > $args['maximum'] ) {
					/* translators: 1: parameter, 2: maximum number */
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be less than or equal to %2$d' ), $param, $args['maximum'] ) );
				}
			} elseif ( isset( $args['maximum'] ) && isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value <= $args['minimum'] ) {
						/* translators: 1: parameter, 2: minimum number, 3: maximum number */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (exclusive) and %3$d (exclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value < $args['minimum'] ) {
						/* translators: 1: parameter, 2: minimum number, 3: maximum number */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (inclusive) and %3$d (exclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( ! empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value <= $args['minimum'] ) {
						/* translators: 1: parameter, 2: minimum number, 3: maximum number */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (exclusive) and %3$d (inclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value < $args['minimum'] ) {
						/* translators: 1: parameter, 2: minimum number, 3: maximum number */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (inclusive) and %3$d (inclusive)' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				}
			}
		}

		if ( 'string' === $args['type'] ) {
			if ( isset( $args['minLength'] ) && mb_strlen( $value ) < $args['minLength'] ) {
				/* translators: 1: parameter, 2: minimum number */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be longer than %2$d characters.', 'it-l10n-ithemes-security-pro' ), $param, $args['minLength'] ) );
			}

			if ( isset( $args['maxLength'] ) && mb_strlen( $value ) > $args['maxLength'] ) {
				/* translators: 1: parameter, 2: minimum number */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be shorter than %2$d characters.', 'it-l10n-ithemes-security-pro' ), $param, $args['maxLength'] ) );
			}
		}

		return true;
	}

	/**
	 * Sanitize the request arg.
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return mixed|WP_Error
	 */
	public static function _sanitize_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();

		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return true;
		}

		$args = $attributes['args'][ $param ];

		return self::_sanitize_value_from_schema( $value, $args, $param );
	}

	/**
	 * Sanitize the value based on its schema definition.
	 *
	 * @param mixed  $value
	 * @param array  $args
	 * @param string $param
	 *
	 * @return mixed|WP_Error
	 */
	private static function _sanitize_value_from_schema( $value, $args, $param ) {

		if ( isset( $args['oneOf'] ) ) {
			foreach ( $args['oneOf'] as $oneOf ) {
				if ( true === self::_validate_value_from_schema( $value, $oneOf, $param ) ) {
					return self::_sanitize_value_from_schema( $value, $oneOf, $param );
				}
			}

			return $value;
		}

		if ( 'array' === $args['type'] ) {
			if ( empty( $args['items'] ) ) {
				return (array) $value;
			}

			if ( ! is_array( $value ) ) {
				$value = preg_split( '/[\s,]+/', $value );
			}

			foreach ( $value as $index => $v ) {
				$value[ $index ] = self::_sanitize_value_from_schema( $v, $args['items'], $param . '[' . $index . ']' );
			}
			// Normalize to numeric array so nothing unexpected
			// is in the keys.
			$value = array_values( $value );

			return $value;
		}

		if ( 'object' === $args['type'] ) {
			if ( $value instanceof stdClass ) {
				$value = (array) $value;
			}

			if ( ! is_array( $value ) ) {
				return array();
			}

			foreach ( $value as $property => $v ) {
				if ( isset( $args['properties'][ $property ] ) ) {
					$value[ $property ] = self::_sanitize_value_from_schema( $v, $args['properties'][ $property ], $param . '[' . $property . ']' );
				} elseif ( isset( $args['additionalProperties'] ) && false === $args['additionalProperties'] ) {
					unset( $value[ $property ] );
				}
			}

			return $value;
		}

		if ( 'integer' === $args['type'] ) {
			return (int) $value;
		}

		if ( 'number' === $args['type'] ) {
			return (float) $value;
		}

		if ( 'boolean' === $args['type'] ) {
			return rest_sanitize_boolean( $value );
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time':
					return sanitize_text_field( $value );

				case 'email':
					/*
					 * sanitize_email() validates, which would be unexpected.
					 */
					return sanitize_text_field( $value );

				case 'uri':
					return esc_url_raw( $value );

				case 'ip':
					return sanitize_text_field( $value );
			}
		}

		if ( 'string' === $args['type'] ) {
			return (string) $value;
		}

		return $value;
	}

	/**
	 * Apply the validation and sanitization callbacks.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected static function apply_validation_callbacks( $args ) {
		foreach ( $args as $arg => $schema ) {
			if ( ! isset( $schema['validate_callback'] ) ) {
				$args[ $arg ]['validate_callback'] = array( __CLASS__, '_validate_request_arg' );
			}

			if ( ! isset( $schema['sanitize_callback'] ) ) {
				$args[ $arg ]['sanitize_callback'] = array( __CLASS__, '_sanitize_request_arg' );
			}
		}

		return $args;
	}
}
