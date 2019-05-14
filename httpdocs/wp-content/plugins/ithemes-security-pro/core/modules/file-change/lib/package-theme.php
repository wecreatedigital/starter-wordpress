<?php

/**
 * Class ITSEC_File_Change_Package_Theme
 */
class ITSEC_File_Change_Package_Theme implements ITSEC_File_Change_Package, Serializable {

	/** @var WP_Theme */
	private $theme;

	/** @var array */
	private $custom_headers = array();

	/**
	 * ITSEC_File_Change_Package_Theme constructor.
	 *
	 * @param WP_Theme $theme
	 */
	public function __construct( WP_Theme $theme ) { $this->theme = $theme; }

	/**
	 * @inheritDoc
	 */
	public function get_root_path() {
		return trailingslashit( $this->theme->get_stylesheet_directory() );
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() {
		return $this->theme->get( 'Version' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_type() {
		return 'theme';
	}

	/**
	 * @inheritDoc
	 */
	public function get_identifier() {
		return $this->theme->get_stylesheet();
	}

	/**
	 * Get a header value from the theme's stylesheet.
	 *
	 * Both custom and default headers are supported. The results are internally cached.
	 *
	 * @param string $header The header as it appears in the file, for example "Theme Name" or "Author URI".
	 *
	 * @return string
	 */
	public function get_theme_header( $header ) {

		switch ( $header ) {
			case 'Theme Name':
				return $this->theme->get( 'Name' );
			case 'Theme URI':
				return $this->theme->get( 'ThemeURI' );
			case 'Author URI':
				return $this->theme->get( 'AuthorURI' );
			case 'Text Domain':
				return $this->theme->get( 'TextDomain' );
			case 'Domain Path':
				return $this->theme->get( 'DomainPath' );
			default:
				if ( $value = $this->theme->get( $header ) ) {
					return $value;
				}
				break;
		}

		if ( ! isset( $this->custom_headers[ $header ] ) ) {
			$file    = "{$this->theme->get_theme_root()}/{$this->theme->get_stylesheet()}/style.css";
			$headers = @get_file_data( $file, array( 'header' => $header ) );

			$this->custom_headers[ $header ] = isset( $headers['header'] ) ? $headers['header'] : '';
		}

		return $this->custom_headers[ $header ];
	}

	/**
	 * @inheritDoc
	 */
	public function __toString() {
		/* translators: 1. Theme name 2. Theme version */
		return sprintf( __( '%1$s theme %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_theme_header( 'Theme Name' ), 'v' . $this->get_version() );
	}

	/**
	 * @inheritDoc
	 */
	public function serialize() {
		return serialize( array(
			'theme_dir'  => $this->theme->get_stylesheet(),
			'theme_root' => $this->theme->get_theme_root(),
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function unserialize( $serialized ) {
		$data = unserialize( $serialized );

		$this->theme = wp_get_theme( $data['theme_dir'], $data['theme_root'] );
	}
}