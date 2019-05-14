<?php

final class ITSEC_VM_Old_Site_Scanner {
	private static $instance;

	private $delayed_dirs = array();
	private $base_dirs = array();
	private $sites = array();

	public static function run_scan() {
		if ( self::$instance ) {
			// Only allow a single scan per page load.
			return;
		}

		self::$instance = new self;
		$self = self::$instance;

		require_once( dirname( __FILE__ ) . '/utility.php' );

		$root = $self->get_web_root();
		$self->base_dirs = array( $root );
		$self->find_sites( $root );

		while ( ! empty( $self->delayed_dirs ) ) {
			$self->process_delayed_dirs();
		}

		$old_sites = array();

		foreach ( $self->sites as $path => $details ) {
			if ( $details['is_outdated'] ) {
				$old_sites[$path] = $details;
			}
		}

		$old_site_details = array(
			'last_scan' => time(),
			'sites'     => $old_sites,
		);
		ITSEC_Modules::set_setting( 'version-management', 'old_site_details', $old_site_details );

		$nc = ITSEC_Core::get_notification_center();

		foreach ( $old_sites as $path => $details ) {
			$details['path'] = $path;

			$nc->enqueue_data( 'old-site-scan', $details );
		}
	}

	private function process_delayed_dirs() {
		foreach ( $this->delayed_dirs as $path => $details ) {
			foreach ( $this->base_dirs as $base_dir ) {
				if ( 0 === strpos( $details['realpath'], $base_dir ) ) {
					unset( $this->delayed_dirs[$path] );
				}
			}

			if ( ! isset( $lowest_count ) || $details['count'] < $lowest_count ) {
				$lowest_count = $details['count'];
				$shortest_path = $path;
			}
		}

		if ( empty( $this->delayed_dirs ) || empty( $shortest_path ) ) {
			return array();
		}

		$this->base_dirs[] = $this->delayed_dirs[$shortest_path]['realpath'];

		unset( $this->delayed_dirs[$shortest_path] );

		$this->find_sites( $shortest_path, true );
	}

	private function find_sites( $path, $skip_link_check = false ) {
		if ( ! is_readable( $path ) ) {
			return;
		}

		if ( is_link( $path ) && ! $skip_link_check ) {
			$realpath = realpath( $path );
			$realpath = str_replace( '\\', '/', $realpath );
			$count = count( explode( '/', $realpath ) );

			$this->delayed_dirs[$path] = array(
				'realpath' => $realpath,
				'count'    => $count,
			);

			return;
		}

		if ( file_exists( "$path/version.php" ) ) {
			$version = ITSEC_VM_Utility::get_wordpress_version( "$path/version.php" );

			if ( false !== $version ) {
				$clean_path = preg_replace( '|/' . preg_quote( WPINC, '|' ) . '/?$|', '', $path );

				$this->sites[$clean_path] = array(
					'version'     => $version,
					'is_outdated' => ITSEC_VM_Utility::is_wordpress_version_outdated( $version ),
				);
			}
		}

		$dh = opendir( $path );
		$dirs = array();

		if ( $dh ) {
			while ( false !== ( $file = readdir( $dh ) ) ) {
				if ( '.' !== $file && '..' !== $file && is_dir( "$path/$file" ) ) {
					$dirs[] = $file;
				}
			}
		}

		foreach ( $dirs as $dir ) {
			$this->find_sites( "$path/$dir" );
		}
	}

	private function get_version( $path ) {
		$fh = fopen( "$path/version.php", 'r' );

		if ( false === $fh || feof( $fh ) ) {
			return false;
		}

		$content = fread( $fh, 2048 );
		fclose( $fh );

		if ( preg_match( '/$wp_version = \'([^\']+)\';/', $content, $match ) ) {
			return $match[1];
		}

		return false;
	}

	private function get_web_root() {
		$path = ABSPATH;

		$path = str_replace( '\\', '/', $path );
		$path = rtrim( $path, '/' ) . '/';

		$patterns = array(
			'public_html',
			'httpdocs',
			'MAMP/htdocs/sites',
			'htdocs',
			'wwwroot',
			'html',
			'www',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( '|^(.+?' . preg_quote( $pattern, '|' ) . ')/|', $path, $match ) ) {
				return $match[1];
			}
		}

		// Since matching known patterns didn't work, we need to search for a logical place to start searching from.
		// If the parent directory has the same owner as ABSPATH, we'll use that. Otherwise, we'll just use the current
		// directory. It isn't ideal, but it should be safe while at the same time giving a decent chance to catch other
		// sites installed at the same directory level.

		$path = rtrim( $path, '/' );
		$owner = fileowner( $path );
		$parent = dirname( $path );

		if ( fileowner( $parent ) !== $owner ) {
			return $path;
		}

		if ( empty( $parent ) || in_array( $parent, array( '.', '/', '\\' ) ) ) {
			return $path;
		}

		return $parent;
	}
}
