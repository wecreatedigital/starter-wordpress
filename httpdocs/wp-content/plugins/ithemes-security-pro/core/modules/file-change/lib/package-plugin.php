<?php

/**
 * Class ITSEC_File_Change_Package_Plugin
 */
class ITSEC_File_Change_Package_Plugin implements ITSEC_File_Change_Package {

	/** @var string */
	protected $file;

	/** @var array */
	protected $data;

	/**
	 * ITSEC_File_Change_Package_WPOrg_Plugin constructor.
	 *
	 * @param string $file The full plugin file. For example, askismet/akismet.php
	 * @param array  $data
	 */
	public function __construct( $file, array $data ) {
		$this->file = $file;
		$this->data = $data;
	}

	/**
	 * @inheritdoc
	 */
	public function get_root_path() {
		return trailingslashit( dirname( WP_PLUGIN_DIR . '/' . $this->file ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_version() {
		return $this->get_plugin_header( 'Version' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_type() {
		return 'plugin';
	}

	/**
	 * @inheritdoc
	 */
	public function get_identifier() {
		return $this->file;
	}

	/**
	 * Get a header value from the main plugin file.
	 *
	 * Both custom and default headers are supported. The results are internally cached.
	 *
	 * @param string $header The header as it appears in the file, for example "Plugin Name" or "Author URI".
	 *
	 * @return string
	 */
	public function get_plugin_header( $header ) {

		switch ( $header ) {
			case 'Plugin Name':
				if ( isset( $this->data['Name'] ) ) {
					return $this->data['Name'];
				}
				break;
			case 'Plugin URI':
				if ( isset( $this->data['PluginURI'] ) ) {
					return $this->data['PluginURI'];
				}
				break;
			case 'Author URI':
				if ( isset( $this->data['AuthorURI'] ) ) {
					return $this->data['AuthorURI'];
				}
				break;
			case 'Text Domain':
				if ( isset( $this->data['TextDomain'] ) ) {
					return $this->data['TextDomain'];
				}
				break;
			case 'Domain Path':
				if ( isset( $this->data['DomainPath'] ) ) {
					return $this->data['DomainPath'];
				}
				break;
		}

		if ( ! isset( $this->data[ $header ] ) ) {
			$headers = @get_file_data( $this->get_root_path() . basename( $this->file ), array( 'header' => $header ) );

			$this->data[ $header ] = isset( $headers['header'] ) ? $headers['header'] : '';
		}

		return $this->data[ $header ];
	}

	/**
	 * @inheritdoc
	 */
	public function __toString() {
		/* translators: 1. Plugin name 2. Plugin version */
		return sprintf( __( '%1$s plugin %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_header( 'Plugin Name' ), 'v' . $this->get_version() );
	}
}