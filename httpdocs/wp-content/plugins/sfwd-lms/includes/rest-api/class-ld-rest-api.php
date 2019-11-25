<?php
if ( ! defined( 'LEARNDASH_REST_API_NAMESPACE' ) ) {
	define( 'LEARNDASH_REST_API_NAMESPACE', 'ldlms' );
}

define( 'LEARNDASH_REST_API_DIR', dirname( __FILE__ ) );

require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/gutenberg/lib/class-ld-rest-gutenberg-posts-controller.php';

if ( ! class_exists( 'LearnDash_REST_API' ) ) {
	class LearnDash_REST_API {

		/**
		 * @var The reference to *Singleton* instance of this class
		 */
		private static $instance;

		private $controllers = array();

		function __construct() {
			$this->controllers = array(
				// v1 controllers.
				'LD_REST_Echo_Controller_V1'          => array(
					'register_routes' => true,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-echo-controller.php',
				),
				'LD_REST_Courses_Controller_V1'       => array(
					'register_routes' => false,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-courses-controller.php',
				),
				'LD_REST_Lessons_Controller_V1'       => array(
					'register_routes' => false,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-lessons-controller.php',
				),
				'LD_REST_Topics_Controller_V1'        => array(
					'register_routes' => false,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-topics-controller.php',
				),
				'LD_REST_Quizzes_Controller_V1'       => array(
					'register_routes' => false,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-quizzes-controller.php',
				),
				'LD_REST_Groups_Controller_V1'        => array(
					'register_routes' => false,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-groups-controller.php',
				),

				'LD_REST_Users_Groups_Controller_V1'  => array(
					'register_routes' => true,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-users-groups-controller.php',
				),
				'LD_REST_Users_Courses_Controller_V1' => array(
					'register_routes' => true,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-users-courses-controller.php',
				),
				'LD_REST_Questions_Controller_V1'     => array(
					'register_routes' => true,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-questions-controller.php',
				),
				'LD_REST_Sections_Controller_V1'      => array(
					'register_routes' => true,
					'file'            => LEARNDASH_REST_API_DIR . '/v1/class-ld-rest-sections-controller.php',
				),
			);

			add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 10 );
		}

		/**
		 * Init function to all the LearnDash REST API namespace and endpoints.
		 */
		public function rest_api_init() {

			if ( self::enabled() ) {

				$this->controllers = apply_filters( 'learndash-rest-api-controllers', $this->controllers );
				if ( ! empty( $this->controllers ) ) {
					include_once dirname( __FILE__ ) . '/v1/class-ld-rest-posts-controller.php';
					include_once dirname( __FILE__ ) . '/v1/class-ld-rest-users-controller.php';

					//include_once( dirname( __FILE__ ) . '/v1/class-ld-rest-terms-controller.php' );

					foreach ( $this->controllers as $controller_class => $set ) {

						if ( ( isset( $set['file'] ) ) && ( ! empty( $set['file'] ) ) && ( file_exists( $set['file'] ) ) ) {
							include_once $set['file'];

							if ( ( isset( $set['register_routes'] ) ) && ( $set['register_routes'] === true ) ) {
								$this->$controller_class = new $controller_class();
								$this->$controller_class->register_routes();
							}
						}
					}
				}
			}
		}

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === static::$instance ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		* Override class function for 'this'.
		* This function handles out Singleton logic.
		* @return reference to current instance
		*/
		static function this() {
			return self::$instance;
		}

		static function enabled( $post_type = '' ) {
			$return = false;

			if ( ( defined( 'LEARNDASH_REST_API_ENABLED' ) ) && ( true === LEARNDASH_REST_API_ENABLED ) ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'enabled' ) === 'yes' ) {
					$return = true;
				}
			}

			return apply_filters( 'learndash_rest_api_enabled', $return, $post_type );
		}

		static function gutenberg_enabled( $post_type = '' ) {
			$return = false;

			if ( ( defined( 'LEARNDASH_GUTENBERG' ) ) && ( LEARNDASH_GUTENBERG === true ) ) {
				$return = true;
			}

			return apply_filters( 'learndash_gutenberg_enabled', $return, $post_type );
		}

		static function get_controller( $post_type = '' ) {
			$rest_controller = '';

			if ( ! empty( $post_type ) ) {
				switch ( $post_type ) {
					case 'sfwd-courses':
						if ( self::enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Courses_Controller_V1';
						} elseif ( self::gutenberg_enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Posts_Gutenberg_Controller';
						}
						break;

					case 'sfwd-lessons':
						if ( self::enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Lessons_Controller_V1';
						} elseif ( self::gutenberg_enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Posts_Gutenberg_Controller';
						}
						break;

					case 'sfwd-topic':
						if ( self::enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Topics_Controller_V1';
						} elseif ( self::gutenberg_enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Posts_Gutenberg_Controller';
						}
						break;

					case 'sfwd-quiz':
						if ( self::enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Quizzes_Controller_V1';
						} elseif ( self::gutenberg_enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Posts_Gutenberg_Controller';
						}
						break;

					case 'groups':
						if ( self::enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Groups_Controller_V1';
						} elseif ( self::gutenberg_enabled( $post_type ) ) {
							$rest_controller = 'LD_REST_Posts_Gutenberg_Controller';
						}
						break;

					default:
						break;
				}
			}
			return $rest_controller;
		}
	}
}
LearnDash_REST_API::get_instance();
