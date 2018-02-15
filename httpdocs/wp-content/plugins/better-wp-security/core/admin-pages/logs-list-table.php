<?php

/**
 * List table for logs
 *
 * @package    iThemes-Security
 * @since      3.9
 */
final class ITSEC_Logs_List_Table extends ITSEC_WP_List_Table {
	private $user_cache = array();
	private $raw_filters = array();
	private $current_filters = array();
	private $types = array();


	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'itsec-log-entry',
				'plural'   => 'itsec-log-entries',
				'ajax'     => true
			)
		);

		$this->types = ITSEC_Log::get_types_for_display();
	}

	public function column_default( $entry, $field ) {
		return esc_html( $entry[$field] );
	}

	public function column_description( $entry ) {
		return $entry['description'];
	}

	public function column_type( $entry ) {
		return $this->types[$entry['type']];
	}

	public function column_timestamp( $entry ) {
		$timestamp = strtotime( $entry['timestamp'] );
		$datetime = date( 'Y-m-d H:i:s', $timestamp + ITSEC_Core::get_time_offset() );
		/* translators: 1: date and time, 2: time difference */
		return sprintf( __( '%1$s - %2$s ago', 'better-wp-security' ), $datetime, human_time_diff( $timestamp, time() ) );
	}

	public function column_remote_ip( $entry ) {
		if ( empty( $entry['remote_ip'] ) ) {
			return '';
		}

		return '<code><a href="' . esc_url( ITSEC_Lib::get_trace_ip_link( $entry['remote_ip'] ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $entry['remote_ip'] ) . '</a></code>';
	}

	/**
	 * Define username column
	 *
	 * @param array $item array of row data
	 *
	 * @return string formatted output
	 *
	 **/
	public function column_user_id( $item ) {
		if ( $item['user_id'] > 0 ) {
			if ( ! isset( $this->user_cache[$item['user_id']] ) ) {
				$this->user_cache[$item['user_id']] = get_userdata( $item['user_id'] );
			}

			if ( false === $this->user_cache[$item['user_id']] ) {
				return '';
			}

			return '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $item['user_id'] ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $this->user_cache[$item['user_id']]->user_login ) . '</a>';
		}

		return '';
	}

	public function column_details( $entry ) {
		echo '<a class="itsec-logs-view-details" href="' . self::get_self_link( array( 'id' => $entry['id'] ), array() ) . '">' . esc_html__( 'View Details', 'better-wp-security' ) . '</a>';
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * @since 4.3.0
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		$columns = $this->get_columns();
		$column_header = $columns[$column_name];

		if ( 'module_display' === $column_name ) {
			$column_name = 'module';
		} else if ( 'description' === $column_name ) {
			$column_name = 'code';
		}

		if ( 'details' === $column_name || 'id' === $column_name ) {
			return;
		} else if ( 'timestamp' === $column_name ) {
			list( $date, $time ) = explode( ' ', $item[$column_name] );
			$url = self::get_self_link( array( 'filters' => "$column_name|$date%" ) );
			return '&nbsp;<a class="dashicons dashicons-filter" href="' . esc_url( $url ) . '" title="' . esc_attr__( 'Show only entries for this day', 'better-wp-security' ) . '">&nbsp;</a>';
		} else if ( empty( $item[$column_name] ) ) {
			return;
		}

		if ( 'four_oh_four' === $item['module'] && 'code' === $column_name ) {
			$url = self::get_self_link( array( 'filters[10]' => "url|{$item['url']}", 'filters[11]' => 'module|four_oh_four' ) );
		} else {
			$url = self::get_self_link( array( 'filters' => "$column_name|{$item[$column_name]}" ) );
		}

		$out = '&nbsp;<a class="dashicons dashicons-filter" href="' . esc_url( $url ) . '" title="' . sprintf( esc_attr__( 'Show only entries for this %s', 'better-wp-security' ), strtolower( $column_header ) ) . '">&nbsp;</a>';

		if ( 'module' === $column_name ) {
			$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';
		}

		return $out;
	}

	private function get_self_link( $vars = array(), $current_vars = false ) {
		if ( ! is_array( $current_vars ) ) {
			$current_vars = $_GET;
			unset( $current_vars['id'] );
			unset( $current_vars['paged'] );
		}

		$vars = array_merge_recursive( $current_vars, $vars );

		if ( ! isset( $vars['page'] ) ) {
			$vars = array_merge( array( 'page' => $_GET['page'] ), $vars );
		}

		return network_admin_url( 'admin.php?' . http_build_query( $vars, null, '&' ) );
	}

	/**
	 * Define Columns
	 *
	 * @return array array of column titles
	 */
	public function get_columns() {
		return array(
			'id'             => esc_html__( 'ID', 'better-wp-security' ),
			'module_display' => esc_html__( 'Module', 'better-wp-security' ),
			'type'           => esc_html__( 'Type', 'better-wp-security' ),
			'description'    => esc_html__( 'Description', 'better-wp-security' ),
			'timestamp'      => esc_html__( 'Time', 'better-wp-security' ),
			'remote_ip'      => esc_html__( 'Host', 'better-wp-security' ),
			'user_id'        => esc_html__( 'User', 'better-wp-security' ),
			'details'        => esc_html__( 'Details', 'better-wp-security' ),
		);
	}

	/**
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'timestamp' => array( 'timestamp', false ),
			'remote_ip' => array( 'remote_ip', false ),
		);
	}

	private function get_raw_filters() {
		if ( ! empty( $this->raw_filters ) ) {
			return $this->raw_filters;
		}

		if ( empty( $_GET['filters'] ) ) {
			$raw_filters = array();
		} else {
			$raw_filters = $_GET['filters'];
		}

		$filters = array();

		foreach ( (array) $raw_filters as $var ) {
			list( $field, $value ) = explode( '|', $var, 2 );

			$filters[$field] = $value;
		}

		if ( ! isset( $filters['type'] ) ) {
			if ( ! isset( $filters['type_not'] ) ) {
				$options = ITSEC_Log_Util::get_logs_page_screen_options();

				$filters['type'] = $options['default_view'];
			} else {
				$filters['type'] = 'all';
			}
		}

		$this->raw_filters = $filters;

		return $this->raw_filters;
	}

	private function get_current_view() {
		$raw_filters = $this->get_raw_filters();

		return $raw_filters['type'];
	}

	private function get_current_filters( $options ) {
		if ( ! empty( $this->current_filters ) ) {
			return $this->current_filters;
		}

		$filters = $this->get_raw_filters();

		if ( 'process' === $filters['type'] ) {
			$filters['type'] = 'process-start';
		}

		if ( 'all' === $filters['type'] ) {
			$type_not = array();

			if ( ! $options['show_debug'] ) {
				$type_not[] = 'debug';
			}
			if ( ! $options['show_process'] ) {
				$type_not[] = 'process-start';
			}

			unset( $filters['type'] );
		} else if ( 'important' === $filters['type'] ) {
			$type_not = array( 'action', 'notice', 'debug', 'process-start' );

			unset( $filters['type'] );
		}

		if ( ! empty( $type_not ) ) {
			if ( isset( $filters['type_not'] ) && is_array( $filters['type_not'] ) ) {
				$filters['type_not'] = array_merge( $filters['type_not'], $type_not );
				$filters['type_not'] = array_unique( $filters['type_not'] );
			} else {
				$filters['type_not'] = $type_not;
			}
		}

		$this->current_filters = $filters;

		return $this->current_filters;
	}

	/**
	 * Prepare data for table
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );
		$options = ITSEC_Log_Util::get_logs_page_screen_options();

		$filters          = $this->get_current_filters( $options );
		$columns          = $this->get_columns();
		$hidden_fields    = array( 'id' );
		$sortable_columns = $this->get_sortable_columns();

		if ( isset( $_GET['orderby'], $_GET['order'] ) ) {
			$sort_by_column = $_GET['orderby'];
			$sort_direction = $_GET['order'];
		} else {
			$sort_by_column = 'timestamp';
			$sort_direction = 'DESC';
		}

		if ( $options['last_seen'] > 0 ) {
			$filters['__min_timestamp'] = $options['last_seen'];
		}

		$total_items = ITSEC_Log::get_number_of_entries( $filters );
		$items       = ITSEC_Log::get_entries( $filters, $options['per_page'], $this->get_pagenum(), $sort_by_column, $sort_direction );

		ITSEC_Modules::load_module_file( 'logs.php' );

		foreach ( $items as $item ) {
			if ( false === strpos( $item['code'], '::' ) ) {
				$code = $item['code'];
				$data = array();
			} else {
				list( $code, $data ) = explode( '::', $item['code'], 2 );
				$data = explode( ',', $data );
			}

			$item['description'] = $item['code'];
			$item['module_display'] = $item['module'];
			$item = apply_filters( "itsec_logs_prepare_{$item['module']}_entry_for_list_display", $item, $code, $data );

			$this->items[$item['id']] = $item;
		}

		$this->_column_headers = array( $columns, $hidden_fields, $sortable_columns, 'module_display' );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $options['per_page'],
			)
		);
	}

	protected function get_views() {
		$options = ITSEC_Log_Util::get_logs_page_screen_options();
		$counts = ITSEC_Log::get_type_counts( $options['last_seen'] );

		$views = array(
			'important'      => esc_html__( 'Important Events (%s)', 'better-wp-security' ),
			'all'            => esc_html__( 'All Events (%s)', 'better-wp-security' ),
			'critical-issue' => esc_html__( 'Critical Issues (%s)', 'better-wp-security' ),
			'fatal-error'    => esc_html__( 'Fatal Errors (%s)', 'better-wp-security' ),
			'error'          => esc_html__( 'Errors (%s)', 'better-wp-security' ),
			'warning'        => esc_html__( 'Warnings (%s)', 'better-wp-security' ),
			'action'         => esc_html__( 'Actions (%s)', 'better-wp-security' ),
			'notice'         => esc_html__( 'Notices (%s)', 'better-wp-security' ),
			'debug'          => esc_html__( 'Debug (%s)', 'better-wp-security' ),
			'process'        => esc_html__( 'Process (%s)', 'better-wp-security' ),
		);

		if ( ! $options['show_debug'] ) {
			unset( $views['debug'] );
		}
		if ( ! $options['show_process'] ) {
			unset( $views['process'] );
		}

		$important_count = 0;
		$all_count = 0;

		foreach ( $views as $type => $description ) {
			if ( in_array( $type, array( 'important', 'all' ) ) ) {
				continue;
			}

			if ( empty( $counts[$type] ) ) {
				unset( $views[$type] );
				continue;
			}

			$views[$type] = sprintf( $description, $counts[$type] );

			if ( in_array( $type, array( 'critical-issue', 'fatal-error', 'error', 'warning' ) ) ) {
				$important_count += $counts[$type];
			}

			$all_count += $counts[$type];
		}

		$views['important'] = sprintf( $views['important'], $important_count );
		$views['all'] = sprintf( $views['all'], $all_count );
		$formatted_views = array();
		$current = $this->get_current_view();

		foreach ( $views as $type => $description ) {
			$url = self::get_self_link( array( 'filters' => "type|$type" ), array() );

			if ( $current === $type ) {
				$description = '<a href="' . esc_url( $url ) . '" class="current" aria-current="page">' . $description . '</a>';
			} else {
				$description = '<a href="' . esc_url( $url ) . '">' . $description . '</a>';
			}

			$formatted_views["itsec-$type"] = $description;
		}

		return $formatted_views;
	}

	public function single_row( $item ) {
		echo "<tr class='itsec-log-type-{$item['type']}'>";
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	protected function get_table_classes() {
		$options = ITSEC_Log_Util::get_logs_page_screen_options();

		$classes = array(
			'widefat',
			'striped',
			$this->_args['plural']
		);

		if ( $options['color'] ) {
			$classes[] = 'itsec-logs-color';
		}

		return $classes;
	}

	protected function extra_tablenav( $which ) {
		echo '<div class="alignleft actions">';

		if ( 'top' === $which ) {
/*
			ob_start();

			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output;
				submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			}*/
		}

/*		if ( $this->is_trash && current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_others_posts ) && $this->has_items() ) {
			submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
		}*/

		echo '</div>';
	}

	public function no_items() {
		esc_html_e( 'No events.', 'better-wp-security' );
	}
}
