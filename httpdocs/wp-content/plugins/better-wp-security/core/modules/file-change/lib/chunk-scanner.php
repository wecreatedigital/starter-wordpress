<?php

require_once( ABSPATH . 'wp-admin/includes/file.php' );

class ITSEC_File_Change_Chunk_Scanner {

	/** @var array */
	private $chunks;

	/** @var string */
	private $directory;

	/** @var array */
	private $excludes;

	/** @var array */
	private $settings;

	/** @var array */
	private $file_list;

	/**
	 * ITSEC_File_Change_Chunk_Scanner constructor.
	 *
	 * @param array $settings
	 * @param array $chunks
	 */
	public function __construct( $settings, $chunks = array() ) {

		$home = get_home_path();

		if ( ! $chunks ) {
			$upload = ITSEC_Core::get_wp_upload_dir();

			$chunks = array(
				ITSEC_File_Change_Scanner::C_ADMIN    => ABSPATH . 'wp-admin',
				ITSEC_File_Change_Scanner::C_INCLUDES => ABSPATH . WPINC,
				ITSEC_File_Change_Scanner::C_CONTENT  => WP_CONTENT_DIR,
				ITSEC_File_Change_Scanner::C_UPLOADS  => $upload['basedir'],
				ITSEC_File_Change_Scanner::C_THEMES   => WP_CONTENT_DIR . '/themes',
				ITSEC_File_Change_Scanner::C_PLUGINS  => WP_PLUGIN_DIR,
				ITSEC_File_Change_Scanner::C_OTHERS   => untrailingslashit( $home ),
			);
		}

		$this->chunks   = $chunks;
		$this->settings = $settings;

		$this->excludes[] = ITSEC_Modules::get_setting( 'backup', 'location' );
		$this->excludes[] = ITSEC_Modules::get_setting( 'global', 'log_location' );

		foreach ( $settings['file_list'] as $file ) {
			$cleaned                     = untrailingslashit( $home . ltrim( $file, '/' ) );
			$this->file_list[ $cleaned ] = 1;
		}
	}

	/**
	 * Scan and get a list of all files in the given directory.
	 *
	 * @param string $chunk               Chunk to scan.
	 * @param int    $limit               Top level directory limit.
	 * @param array  $additional_excludes Additional exclusion rules for this scan. Must already be a full path.
	 *
	 * @return array
	 */
	public function scan( $chunk, $limit = - 1, $additional_excludes = array() ) {

		if ( ! isset( $this->chunks[ $chunk ] ) ) {
			return array();
		}

		$excludes = $this->excludes;
		$chunks   = $this->chunks;
		$file_list = $this->file_list;

		$this->directory = $chunks[ $chunk ];
		unset( $chunks[ $chunk ] );
		$this->excludes = array_merge( $this->excludes, array_values( $chunks ) );

		foreach ( $additional_excludes as $exclude ) {
			$this->file_list[ untrailingslashit( $exclude ) ] = 1;
		}

		do_action( 'itsec-file-change-start-scan' );
		$current_files = $this->get_files( $this->directory, $limit );
		do_action( 'itsec-file-change-end-scan' );

		$this->excludes  = $excludes;
		$this->directory = null;
		$this->file_list = $file_list;

		return $current_files;
	}

	/**
	 * Recursively find files in a given directory and calculate their checksums.
	 *
	 * @param string $path Path to search in.
	 * @param int    $limit
	 *
	 * @return array
	 */
	private function get_files( $path, $limit = - 1 ) {

		if ( in_array( $path, $this->excludes, true ) ) {
			return array();
		}

		if ( false === ( $dh = @opendir( $path ) ) ) {
			return array();
		}

		$data = array();
		$dirs = array();

		while ( false !== ( $item = @readdir( $dh ) ) ) {

			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$filename = "{$path}/{$item}";

			if ( isset( $this->file_list[ $filename ] ) ) {
				continue;
			}

			if ( is_dir( $filename ) && 'dir' === filetype( $filename ) ) {
				if ( $nested = $this->get_files( $filename ) ) {
					$dirs[] = $nested;
				}
			} elseif ( ! in_array( '.' . pathinfo( $item, PATHINFO_EXTENSION ), $this->settings['types'], true ) ) {
				$data[ $filename ] = array(
					'd' => @filemtime( $filename ),
					'h' => @md5_file( $filename ),
				);
			}

			if ( $limit === count( $dirs ) ) {
				break;
			}
		}

		if ( $dirs ) {
			$dirs[] = $data;
			$data   = call_user_func_array( 'array_merge', $dirs );
		}

		@closedir( $dh );

		return $data;
	}
}