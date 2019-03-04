<?php

/**
 * Class ITSEC_Lib_Distributed_Storage
 */
class ITSEC_Lib_Distributed_Storage {

	/* --- Config --- */

	/** @var string */
	private $name;

	/** @var array */
	private $config = array();

	/* --- Instance --- */

	/** @var array */
	private $data = array();

	/**
	 * ITSEC_Lib_Distributed_Storage constructor.
	 *
	 * @param string $name
	 * @param array  $config
	 */
	public function __construct( $name, array $config ) {
		$this->name = $name;

		foreach ( $config as $key => $value ) {
			$valid = false;

			if ( array_key_exists( 'serialize', $value ) || array_key_exists( 'unserialize', $value ) ) {
				if ( ! isset( $value['serialize'] ) ) {
					_doing_it_wrong( __CLASS__, 'iThemes Security: Serialize function required when using unserialize.', '4.5.0' );
				} elseif ( ! is_callable( $value['serialize'] ) ) {
					_doing_it_wrong( __CLASS__, 'iThemes Security: Serialize function must be callable.', '4.5.0' );
				} else {
					$valid = true;
				}

				if ( ! isset( $value['unserialize'] ) ) {
					_doing_it_wrong( __CLASS__, 'iThemes Security: Unserialize function required when using serialize.', '4.5.0' );
				} elseif ( ! is_callable( $value['unserialize'] ) ) {
					_doing_it_wrong( __CLASS__, 'iThemes Security: Unserialize function must be callable.', '4.5.0' );
				} else {
					$valid = true;
				}
			} else {
				$valid = true;
			}

			if ( $valid ) {
				$this->config[ $key ] = wp_parse_args( $value, array(
					'split'       => false,
					'default'     => null,
					'serialize'   => 'serialize',
					'unserialize' => 'unserialize',
					'chunk'       => false,
				) );
			}
		}
	}

	/**
	 * Get the value for a given key.
	 *
	 * @param string $key
	 *
	 * @return mixed|false
	 */
	public function get( $key ) {

		if ( ! isset( $this->config[ $key ] ) ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Unsupported key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		if ( array_key_exists( $key, $this->data ) ) {
			return $this->data[ $key ];
		}

		$this->load( $key );

		return $this->data[ $key ];
	}

	/**
	 * Get a cursor to paginate over a chunked resource.
	 *
	 * @param string $key
	 *
	 * @return ITSEC_Lib_Distributed_Storage_Cursor|null
	 */
	public function get_cursor( $key ) {

		if ( ! isset( $this->config[ $key ] ) ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Unsupported key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return null;
		}

		if ( ! $this->config[ $key ]['chunk'] ) {
			return null;
		}

		$data = $this->_load_chunk( $key, 0 );
		$data = null === $data ? array() : $data;

		return new ITSEC_Lib_Distributed_Storage_Cursor( $this, $key, $data );
	}

	/**
	 * Set the value for a given key.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function set( $key, $value ) {

		global $wpdb;

		if ( ! isset( $this->config[ $key ] ) ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Unsupported key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		$this->data[ $key ] = $value;

		$config = $this->config[ $key ];

		if ( ! $config['split'] ) {
			$update = array();

			foreach ( $this->config as $config_key => $config_value ) {
				if ( ! $config_value['split'] ) {
					if ( $key === $config_key ) {
						$update[ $key ] = $value;
					} else {
						$update[ $config_key ] = $this->get( $config_key );
					}
				}
			}

			return $this->update_row( serialize( $update ) );
		}

		if ( $value === $config['default'] ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s",
				$this->name, $key
			) );

			return $wpdb->last_error ? false : true;
		}

		if ( ! $config['chunk'] ) {
			return $this->update_row( call_user_func( $config['serialize'], $value ), $key );
		}

		$r       = true;
		$highest = 0;

		foreach ( array_chunk( $value, $config['chunk'], true ) as $i => $chunk ) {
			$r_ = $this->update_row( call_user_func( $config['serialize'], $chunk ), $key, $i );

			$highest = $i;
			$r       = $r && $r_;
		}

		$this->clean_chunk_options( $key, $highest );

		return $r;
	}

	/**
	 * Append values to the end of a chunked storage key.
	 *
	 * @param string $key
	 * @param array  $value
	 *
	 * @return bool
	 */
	public function append( $key, $value ) {

		if ( ! isset( $this->config[ $key ] ) ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Unsupported key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		$config = $this->config[ $key ];

		if ( ! $config['chunk'] ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Cannot append to non-chunked key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		if ( array_key_exists( $key, $this->data ) ) {
			$this->data[ $key ] = array_merge( $this->data[ $key ], $value );
		}

		global $wpdb;

		$last_chunk = $wpdb->get_results( $wpdb->prepare(
			"SELECT `storage_chunk`, `storage_data` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s ORDER BY `storage_chunk` DESC LIMIT 1",
			$this->name, $key
		) );

		if ( empty( $last_chunk ) ) {
			return $this->update_row( call_user_func( $config['serialize'], $value ), $key );
		}

		$last_chunk_num  = $last_chunk[0]->storage_chunk;
		$last_chunk_data = call_user_func( $config['unserialize'], $last_chunk[0]->storage_data );

		if ( count( $last_chunk_data ) === $config['chunk'] ) {
			return $this->update_row( call_user_func( $config['serialize'], $value ), $key, $last_chunk_num + 1 );
		}

		$to_fill = $config['chunk'] - count( $last_chunk_data );

		$append = array_slice( $value, 0, $to_fill, true );
		$merged = array_merge( $last_chunk_data, $append );

		if ( ! $this->update_row( call_user_func( $config['serialize'], $merged ), $key, $last_chunk_num ) ) {
			return false;
		}

		if ( ! $new = array_slice( $value, $to_fill, null, true ) ) {
			return true;
		}

		$r = true;

		foreach ( array_chunk( $new, $config['chunk'], true ) as $i => $chunk ) {
			$r_ = $this->update_row( call_user_func( $config['serialize'], $chunk ), $key, $last_chunk_num + 1 + $i );
			$r  = $r && $r_;
		}

		return $r;
	}

	/**
	 * Update a chunked option from an iterator.
	 *
	 * This will be more performant than using ::set() and iterator_to_array() as the whole
	 * array won't be loaded into memory. Instead, it will continuously iterate over the values
	 * and persist the data to the database whenever it hits the chunk size.
	 *
	 * @param string   $key
	 * @param iterable $iterator
	 *
	 * @return bool
	 */
	public function set_from_iterator( $key, $iterator ) {
		if ( ! isset( $this->config[ $key ] ) ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Unsupported key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		$config = $this->config[ $key ];

		if ( ! $config['chunk'] ) {
			_doing_it_wrong( __METHOD__, "iThemes Security: Cannot set from iterator to non-chunked key '{$key}' for '{$this->name}' storage.", '4.5.0' );

			return false;
		}

		unset( $this->data[ $key ] );

		$i       = 0;
		$chunk   = 0;
		$chunked = array();

		$r = true;

		foreach ( $iterator as $item => $value ) {
			$i ++;

			$chunked[ $item ] = $value;

			if ( $i === $config['chunk'] ) {
				$r_      = $this->update_row( call_user_func( $config['serialize'], $chunked ), $key, $chunk );
				$r       = $r && $r_;
				$chunked = array();
				$chunk ++;
				$i = 0;
			}
		}

		if ( $chunked ) {
			$this->update_row( call_user_func( $config['serialize'], $chunked ), $key, $chunk );
		} else {
			// The last chunk allocated was not used.
			$chunk --;
		}

		$this->clean_chunk_options( $key, $chunk );

		return $r;
	}

	/**
	 * Get the most recent time any key in this storage set has been updated.
	 *
	 * @return int|false
	 */
	public function health_check() {

		global $wpdb;

		$date = $wpdb->get_var( $wpdb->prepare(
			"SELECT `storage_updated` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s ORDER BY `storage_updated` DESC LIMIT 1",
			$this->name
		) );

		if ( $date ) {
			return strtotime( $date );
		}

		return false;
	}

	/**
	 * Clear the entire storage bucket.
	 *
	 * @return bool
	 */
	public function clear() {
		if ( self::clear_group( $this->name ) ) {
			$this->data = array();

			return true;
		}

		return false;
	}

	/**
	 * check if there are any recorded values in storage.
	 *
	 * @return bool
	 */
	public function is_empty() {
		global $wpdb;

		return ! $wpdb->get_var( $wpdb->prepare(
			"SELECT `storage_id` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s LIMIT 1",
			$this->name
		) );
	}

	/**
	 * Perform an insert or update to the distributed storage data.
	 *
	 * @param string $serialized
	 * @param string $key
	 * @param int    $chunk
	 *
	 * @return bool
	 */
	private function update_row( $serialized, $key = '', $chunk = 0 ) {

		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->base_prefix}itsec_distributed_storage (`storage_group`, `storage_key`, `storage_chunk`, `storage_data`, `storage_updated`) VALUES (%s, %s, %d, %s, %s) " .
			'ON DUPLICATE KEY UPDATE `storage_group` = %s, `storage_key` = %s, `storage_chunk` = %d, `storage_data` = %s, `storage_updated` = %s',
			$this->name, $key, $chunk, $serialized, date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() ),
			$this->name, $key, $chunk, $serialized, date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() )
		) );

		return $wpdb->last_error ? false : true;
	}

	/**
	 * Remove unused chunks.
	 *
	 * @param string $key         The chunked key to clean.
	 * @param int    $after_chunk Delete all rows with a chunk value higher than this.
	 */
	private function clean_chunk_options( $key, $after_chunk ) {

		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s AND `storage_chunk` > %d",
			$this->name, $key, $after_chunk
		) );
	}

	/**
	 * Load the values into memory for a given key.
	 *
	 * @param string $key
	 */
	private function load( $key ) {

		$config = $this->config[ $key ];

		if ( $config['split'] ) {
			$this->load_split_option( $key );

			return;
		}

		global $wpdb;

		$option = $wpdb->get_var( $wpdb->prepare(
			"SELECT `storage_data` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s",
			$this->name, ''
		) );

		if ( is_serialized( $option ) ) {
			$option = unserialize( $option );
		} else {
			$option = array();
		}

		foreach ( $this->config as $config_key => $config_value ) {
			if ( ! $config_value['split'] ) {
				if ( is_array( $option ) && array_key_exists( $config_key, $option ) ) {
					$this->data[ $config_key ] = $option[ $config_key ];
				} elseif ( ! array_key_exists( $config_key, $this->data ) ) {
					$this->data[ $config_key ] = $config_value['default'];
				}
			}
		}
	}

	/**
	 * Load a split option into memory.
	 *
	 * Will automatically iterate all chunks into memory as well.
	 *
	 * @param string $key
	 * @param int    $chunk
	 */
	private function load_split_option( $key, $chunk = 0 ) {

		global $wpdb;

		$config = $this->config[ $key ];

		if ( $chunk ) {
			$option = $wpdb->get_var( $wpdb->prepare(
				"SELECT `storage_data` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s AND `storage_chunk` = %d",
				$this->name, $key, $chunk
			) );
		} else {
			$option = $wpdb->get_var( $wpdb->prepare(
				"SELECT `storage_data` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s",
				$this->name, $key
			) );
		}

		if ( null === $option ) {
			if ( ! array_key_exists( $key, $this->data ) ) {
				$this->data[ $key ] = $config['default'];
			}

			return;
		}

		$option = call_user_func( $config['unserialize'], $option );

		if ( ! $config['chunk'] ) {
			$this->data[ $key ] = $option;

			return;
		}

		if ( ! is_array( $option ) ) {
			trigger_error( "iThemes Security: Non-array value encountered for chunked key '{$key}' in storage '{$this->name}'." );

			return;
		}

		if ( array_key_exists( $key, $this->data ) ) {
			$this->data[ $key ] = array_merge( $this->data[ $key ], $option );
		} else {
			$this->data[ $key ] = $option;
		}

		// Greater than should never occur, bu to be safe
		if ( count( $option ) >= $config['chunk'] ) {
			$this->load_split_option( $key, $chunk + 1 );
		}
	}

	/**
	 * Load data for a specific chunk.
	 *
	 * Ideally this would be replaced with a closure passed to the storage cursor.
	 *
	 * @param string $key
	 * @param int    $chunk
	 *
	 * @return mixed|null
	 */
	public function _load_chunk( $key, $chunk ) {
		global $wpdb;

		$option = $wpdb->get_var( $wpdb->prepare(
			"SELECT `storage_data` FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s AND `storage_key` = %s AND `storage_chunk` = %d",
			$this->name, $key, $chunk
		) );

		if ( null === $option ) {
			return null;
		}

		$config = $this->config[ $key ];

		return call_user_func( $config['unserialize'], $option );
	}

	/**
	 * Clear all the storage for a given group name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function clear_group( $name ) {

		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->base_prefix}itsec_distributed_storage WHERE `storage_group` = %s",
			$name
		) );

		return $wpdb->last_error ? false : true;
	}
}

class ITSEC_Lib_Distributed_Storage_Cursor implements Iterator {

	/** @var ITSEC_Lib_Distributed_Storage */
	private $storage;

	/** @var string */
	private $key;

	/** @var int */
	private $chunk = 0;

	/** @var array */
	private $data;

	/** @var int */
	private $iterated_count = 0;

	/**
	 * ITSEC_Lib_Distributed_Storage_Cursor constructor.
	 *
	 * @param ITSEC_Lib_Distributed_Storage $storage
	 * @param string                        $key
	 * @param array                         $data
	 */
	public function __construct( ITSEC_Lib_Distributed_Storage $storage, $key, array $data ) {
		$this->storage = $storage;
		$this->key     = $key;
		$this->data    = $data;
	}

	/**
	 * @inheritDoc
	 */
	public function current() {
		return current( $this->data );
	}

	/**
	 * @inheritDoc
	 */
	public function next() {

		if ( $this->iterated_count === count( $this->data ) - 1 ) {
			$data = $this->storage->_load_chunk( $this->key, $this->chunk + 1 );

			if ( null !== $data ) {
				$this->data           = $data;
				$this->iterated_count = 0;
				$this->chunk ++;

				return;
			}
		}

		$this->iterated_count ++;
		next( $this->data );
	}

	/**
	 * @inheritDoc
	 */
	public function key() {
		return key( $this->data );
	}

	/**
	 * @inheritDoc
	 */
	public function valid() {
		return $this->iterated_count < count( $this->data );
	}

	/**
	 * @inheritDoc
	 */
	public function rewind() {

		$this->iterated_count = 0;

		if ( 0 === $this->chunk ) {
			reset( $this->data );
		} else {
			$data        = $this->storage->_load_chunk( $this->key, 0 );
			$this->data  = null === $data ? array() : $data;
			$this->chunk = 0;
		}
	}
}