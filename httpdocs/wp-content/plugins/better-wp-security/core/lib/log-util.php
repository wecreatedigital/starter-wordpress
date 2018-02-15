<?php

final class ITSEC_Log_Util {
	public static function get_type_counts( $min_timestamp = 0 ) {
		global $wpdb;


		$where = 'parent_id=0';
		$prepare_args = array();

		if ( $min_timestamp > 0 ) {
			$where .= ' AND init_timestamp>%s';
			$prepare_args[] = date( 'Y-m-d H:i:s', $min_timestamp );
		}

		$query = "SELECT type, COUNT(*) AS count FROM `{$wpdb->base_prefix}itsec_logs` WHERE $where GROUP BY type";

		if ( ! empty( $prepare_args ) ) {
			$query = $wpdb->prepare( $query, $prepare_args );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		$counts = array();

		foreach ( $results as $result ) {
			if ( 'process-start' === $result['type'] ) {
				$result['type'] = 'process';
			}

			if ( isset( $counts[$result['type']] ) ) {
				$counts[$result['type']] += $result['count'];
			} else {
				$counts[$result['type']] = $result['count'];
			}
		}

		return $counts;
	}

	public static function get_entries( $filters = array(), $limit = 100, $page = 1, $sort_by_column = 'timestamp', $sort_direction = 'DESC', $columns = false ) {
		global $wpdb;


		$get_count = false;
		$min_timestamp = false;

		if ( isset( $filters['__get_count'] ) ) {
			if ( $filters['__get_count'] ) {
				$get_count = true;
			}

			unset( $filters['__get_count'] );
		}

		if ( isset( $filters['__min_timestamp'] ) ) {
			$min_timestamp = $filters['__min_timestamp'];
			unset( $filters['__min_timestamp'] );
		}

		$limit = max( 0, min( 100, intval( $limit ) ) );
		$page = max( 1, intval( $page ) );

		$sort_direction = strtoupper( $sort_direction );
		if ( ! in_array( $sort_direction, array( 'DESC', 'ASC' ) ) ) {
			$sort_direction = 'DESC';
		}


		$valid_columns = array(
			'id',
			'parent_id',
			'module',
			'type',
			'code',
			'timestamp',
			'init_timestamp',
			'remote_ip',
			'user_id',
			'url',
			'memory_current',
			'memory_peak',
		);

		if ( false === $columns ) {
			$columns = $valid_columns;
		} else if ( 'all' === $columns ) {
			$columns = array_merge( $valid_columns, array( 'data' ) );
		}


		if ( $get_count ) {
			$query = "SELECT COUNT(*) FROM `{$wpdb->base_prefix}itsec_logs`";
		} else {
			$query = "SELECT " . implode( ', ', $columns ) . " FROM `{$wpdb->base_prefix}itsec_logs`";
		}

		$prepare_args = array();


		$where_entries = array();

		if ( ! isset( $filters['parent_id'] ) ) {
			$filters['parent_id'] = 0;
		}

		foreach ( (array) $filters as $column => $value ) {
			if ( preg_match( '/^(.+)_not$/', $column, $match ) ) {
				$not = true;
				$column = $match[1];
			} else {
				$not = false;
			}

			if ( preg_match( '/^(.+)_(min|max)$/', $column, $match ) ) {
				if ( ! in_array( $match[1], $valid_columns ) ) {
					continue;
				}

				if ( 'min' === $match[2] ) {
					$where_entries[] = "'$column'>=%s";
					$prepare_args[] = $value;
				} else {
					$where_entries[] = "'column'<=%s";
					$prepare_args[] = $value;
				}
			} else if ( ! in_array( $column, $valid_columns ) ) {
				continue;
			} else if ( is_array( $value ) ) {
				if ( ! empty( $value ) ) {
					if ( $not ) {
						$where_entries[] = "$column NOT IN (" . implode( ', ', array_fill( 0, count( $value ), '%s' ) ) . ")";
					} else {
						$where_entries[] = "$column IN (" . implode( ', ', array_fill( 0, count( $value ), '%s' ) ) . ")";
					}
					$prepare_args = array_merge( $prepare_args, $value );
				}
			} else if ( false !== strpos( $value, '%' ) ) {
				if ( $not ) {
					$where_entries[] = "$column NOT LIKE %s";
				} else {
					$where_entries[] = "$column LIKE %s";
				}
				$prepare_args[] = $value;
			} else {
				if ( $not ) {
					$where_entries[] = "$column<>%s";
				} else {
					$where_entries[] = "$column=%s";
				}
				$prepare_args[] = $value;
			}
		}

		if ( false !== $min_timestamp ) {
			$where_entries[] = 'init_timestamp>%s';
			$prepare_args[] = date( 'Y-m-d H:i:s', $min_timestamp );
		}

		$query .= ' WHERE ' . implode( ' AND ', $where_entries );


		if ( ! $get_count ) {
			if ( ! is_array( $sort_by_column ) ) {
				$sort_by_column = array( "$sort_by_column $sort_direction" );
			}

			$query .= ' ORDER BY ' . implode( ', ', $sort_by_column );


			if ( $limit > 0 ) {
				$offset = ( $page - 1 ) * $limit;
				$query .= " LIMIT $offset,$limit";
			}
		}

		$query = $wpdb->prepare( $query, $prepare_args );


		if ( $get_count ) {
			return intval( $wpdb->get_var( $query ) );
		}

		$rows = $wpdb->get_results( $query, ARRAY_A );

		if ( is_null( $rows ) ) {
			return new WP_Error( 'itsec-log-util-failed-query', sprintf( esc_html__( 'A query failure prevented the log data from being accessed: %s', 'better-wp-security' ), $wpdb->last_error ) );
		}

		foreach ( $rows as $index => $row ) {
			if ( ! isset( $row['data'] ) ) {
				break;
			}

			$data = unserialize( $row['data'] );

			if ( false !== $data || 'b:0;' === $row['data'] ) {
				$rows[$index]['data'] = $data;
			}
		}

		return $rows;
	}

	public static function get_logs_page_screen_options() {
		$defaults = array(
			'per_page'     => 20,
			'default_view' => 'important',
			'color'        => true,
			'show_debug'   => false,
			'show_process' => false,
			'last_seen'    => 0,
		);

		$options = get_user_option( 'itsec_logs_page_screen_options' );

		if ( is_array( $options ) ) {
			$options = array_merge( $defaults, $options );
		} else {
			$options = $defaults;

			if ( $user = wp_get_current_user() ) {
				update_user_option( $user->ID, 'itsec_logs_page_screen_options', $options, true );
			}
		}

		return $options;
	}

	public static function set_logs_page_screen_options( $options ) {
		if ( ! $user = wp_get_current_user() ) {
			return;
		}

		if ( isset( $options['per_page'] ) && ( $options['per_page'] < 1 || $options['per_page'] > 999 ) ) {
			unset( $options['per_page'] );
		}
		if ( isset( $options['default_view'] ) && ! in_array( $options['default_view'], array( 'important', 'all', 'critical-issue' ) ) ) {
			unset( $options['default_view'] );
		}
		if ( isset( $options['last_seen'] ) ) {
			$options['last_seen'] = intval( $options['last_seen'] );

			if ( $options['last_seen'] < 0 || $options['last_seen'] > ITSEC_Core::get_current_time_gmt() ) {
				unset( $options['last_seen'] );
			}
		}

		$options = array_merge( self::get_logs_page_screen_options(), $options );

		update_user_option( $user->ID, 'itsec_logs_page_screen_options', $options, true );
	}

	public static function has_old_log_entries() {
		global $wpdb;

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}itsec_log'" ) ) {
			return false;
		}

		$num_entries = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}itsec_log" );

		if ( empty( $num_entries ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}itsec_log" );
		}

		return true;
	}

	public static function migrate_old_log_entries() {
		global $wpdb;

		$max = 50;
		$num_entries = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}itsec_log" );
		$num_loops = min( $max, $num_entries );

		for ( $count = 1; $count <= $num_loops; $count++ ) {
			$old_entry = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}itsec_log ORDER BY log_date_gmt LIMIT 1", ARRAY_A );
			$entry = self::get_new_log_entry_from_old( $old_entry );

			$wpdb->insert( "{$wpdb->base_prefix}itsec_logs", $entry );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}itsec_log WHERE log_id=%d", $old_entry['log_id'] ) );
		}

		if ( $num_entries > $max ) {
			return false;
		}

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}itsec_log" );

		return true;
	}

	public static function get_new_log_entry_from_old( $old_entry ) {
		$old_entry['log_data'] = unserialize( $old_entry['log_data'] );

		$entry = array(
			'module'         => $old_entry['log_type'],
			'code'           => $old_entry['log_function'],
			'type'           => 'notice',
			'user_id'        => $old_entry['log_user'],
			'timestamp'      => $old_entry['log_date_gmt'],
			'init_timestamp' => $old_entry['log_date_gmt'],
			'remote_ip'      => $old_entry['log_host'],
			'url'            => $old_entry['log_url'],
		);

		if ( 'lockout' === $old_entry['log_type'] ) {
			if ( isset( $old_entry['log_data']['expires'] ) ) {
				$entry['type'] = 'action';

				if ( empty( $old_entry['log_host'] ) ) {
					$entry['code'] = 'user-lockout';
				} else {
					$entry['code'] = 'host-lockout';
				}
			} else {
				if ( empty( $old_entry['log_host'] ) ) {
					$entry['code'] = 'whitelisted-host-triggered-user-lockout';
				} else {
					$entry['code'] = 'whitelisted-host-triggered-host-lockout';
				}
			}
		} else if ( 'file_change' === $old_entry['log_type'] ) {
			$entry['type'] = 'warning';
			$entry['code'] = 'changes-found';
			$entry['data'] = $old_entry['log_data'];
		} else if ( 'malware' === $old_entry['log_type'] ) {
			$entry['code'] = 'scan';
			$entry['data'] = array( 'results' => $old_entry['log_data'] );
		} else if ( 'backup' === $old_entry['log_type'] ) {
			$entry['code'] = 'details';
		} else if ( 'four_oh_four' === $old_entry['log_type'] ) {
			$entry['code'] = 'found_404';
		} else if ( 'ipcheck' === $old_entry['log_type'] ) {
			if ( empty( $old_entry['log_data'] ) ) {
				$entry['code'] = 'failed-login-by-blocked-ip';
			} else {
				$entry['type'] = 'action';
				$entry['code'] = 'ip-blocked';
			}
		} else if ( 'brute_force' === $old_entry['log_type'] ) {
			if ( 'admin' === $old_entry['log_username'] ) {
				$entry['code'] = 'auto-ban-admin-username';
			} else {
				$entry['code'] = 'invalid-login';
			}
		} else if ( 'away_mode' === $old_entry['log_type'] ) {
			$entry['code'] = 'away-mode-active';
		} else if ( 'recaptcha' === $old_entry['log_type'] ) {
			$entry['code'] = 'failed-validation';
		} else if ( 'user_logging' === $old_entry['log_type'] ) {
			if ( isset( $old_entry['log_data']['post'] ) ) {
				$entry['code'] = 'post-status-changed';
			} else if ( empty( $old_entry['log_username'] ) ) {
				$entry['code'] = 'user-logged-out';
			} else {
				$entry['code'] = 'user-logged-in';
			}
		}

		if ( isset( $entry['data'] ) ) {
			$entry['data'] = serialize( $entry['data'] );
		}

		return $entry;
	}
}
