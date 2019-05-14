<?php

/**
 * Class ITSEC_Recaptcha_API
 */
class ITSEC_Recaptcha_API {

	/** @var ITSEC_Recaptcha */
	private static $recaptcha;

	/** @var array */
	private static $defaults = array(
		'margin' => array(
			'top'    => 10,
			'right'  => 0,
			'bottom' => 10,
			'left'   => 0,
		)
	);

	/**
	 * Initialize the API.
	 *
	 * @param ITSEC_Recaptcha $recaptcha
	 */
	public static function init( ITSEC_Recaptcha $recaptcha ) {
		self::$recaptcha = $recaptcha;

		/**
		 * Fires when the Recaptcha API is ready to be used.
		 */
		do_action( 'itsec_recaptcha_api_ready' );
	}

	/**
	 * Is the API available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return (bool) self::$recaptcha;
	}

	/**
	 * Display the Recaptcha field.
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public static function display( array $args ) {
		echo self::render( $args );
	}

	/**
	 * Render the Recaptcha field.
	 *
	 * This will enqueue a couple of JavaScript files which will be printed in the footer.
	 * If you are loading this over Ajax, you may need to manually call wp_print_scripts() to
	 * add the <script> tags to your response.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public static function render( array $args ) {

		if ( ! self::is_available() ) {
			return '';
		}

		$args = array_merge( self::$defaults, $args );

		if ( null === $args['margin'] ) {
			$args['margin'] = array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
			);
		} else {
			$args['margin'] = array_merge( self::$defaults['margin'], $args['margin'] );
		}

		return self::$recaptcha->get_recaptcha( $args );
	}

	/**
	 * Validate the submitted Recaptcha.
	 *
	 * The results of this function are cached for the duration of the request.
	 *
	 * @param array $args
	 *
	 * @return bool|WP_Error
	 */
	public static function validate( array $args = array() ) {
		return self::is_available() && self::$recaptcha->validate_captcha( $args );
	}
}
