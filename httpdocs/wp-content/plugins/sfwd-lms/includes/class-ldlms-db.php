<?php
/**
 * Utility class to contain all the custom databases used within LearnDash.
 *
 * @since 2.6.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_DB' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LDLMS_DB {

		/**
		 * Collection of all tables by section.
		 *
		 * @var array $table_sections.
		 */
		private static $tables_base = array(
			'activity'  => array(
				'user_activity'      => 'user_activity',
				'user_activity_meta' => 'user_activity_meta',
			),
			'wpproquiz' => array(
				'quiz_category'      => 'category',
				'quiz_form'          => 'form',
				'quiz_lock'          => 'lock',
				'quiz_master'        => 'master',
				'quiz_prerequisite'  => 'prerequisite',
				'quiz_question'      => 'question',
				'quiz_statistic'     => 'statistic',
				'quiz_statistic_ref' => 'statistic_ref',
				'quiz_template'      => 'template',
				'quiz_toplist'       => 'toplist',
			),
		);

		private static $tables = array();

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
		}

		/**
		 * Public Initialize function for class
		 *
		 * @since 2.6.0
		 */
		public static function init( $force_reload = false ) {

			$blog_id = get_current_blog_id();

			if ( ( true === $force_reload ) || ( ! isset( self::$tables[ $blog_id ] ) ) || ( empty( self::$tables[ $blog_id ] ) ) ) {
				self::$tables[ $blog_id ] = array();
				/**
				 * Fitler the list of custom database tables.
				 *
				 * @since 2.6.0
				 */
				self::$tables_base = apply_filters( 'learndash_custom_database_tables', self::$tables_base );

				if ( ! empty( self::$tables_base ) ) {
					foreach ( self::$tables_base as $section_key  => $section_tables ) {
						if ( ( ! empty( $section_tables ) ) && ( is_array( $section_tables ) ) ) {
							foreach ( $section_tables as $table_key => $table_name ) {
								self::$tables[ $blog_id ][ $section_key ][ $table_key ] = self::get_table_prefix( $section_key ) . $table_name;
							}
						}
					}
				}
			}
		}

		public static function get_tables_base( $table_section = '', $return_sections = false ) {
			$tables_return = array();

			if ( ! empty( $table_section ) ) {
				if ( isset( self::$tables_base[ $table_section ] ) ) {
					if ( true === $return_sections ) {
						$tables_return[ $table_section ] = self::$tables_base[ $table_section ];
					} else {
						$tables_return = self::$tables_base[ $table_section ];
					}
				}
			} else {
				if ( true === $return_sections ) {
					$tables_return = self::$tables_base;
				} else {
					foreach ( self::$tables_base as $section_key  => $section_tables ) {
						$tables_return = array_merge( $tables_return, $section_tables );
					}
				}
			}

			return $tables_return;
		}

		/**
		 * Get an array of all custom tables.
		 *
		 * @since 2.6.0
		 *
		 * @param string  $table_section Table section prefix.
		 * @param boolean $return_sections Default false returns flat array. True to return table names array with sections.
		 *
		 * @return array of table names.
		 */
		public static function get_tables( $table_section = '', $return_sections = false ) {
			$tables_return = array();

			$blog_id = get_current_blog_id();

			self::init();

			if ( ( isset( self::$tables[ $blog_id ] ) ) && ( ! empty( self::$tables[ $blog_id ] ) ) ) {
				if ( ! empty( $table_section ) ) {
					if ( isset( self::$tables[ $blog_id ][ $table_section ] ) ) {
						if ( true === $return_sections ) {
							$tables_return[ $table_section ] = self::$tables[$blog_id ][ $table_section ];
						} else {
							$tables_return = self::$tables[ $blog_id ][ $table_section ];
						}
					}
				} else {
					if ( true === $return_sections ) {
						$tables_return = self::$table[ $blog_id ];
					} else {
						foreach ( self::$tables[ $blog_id ] as $section_key  => $section_tables ) {
							$tables_return = array_merge( $tables_return, $section_tables );
						}
					}
				}
			}

			return $tables_return;
		}

		/**
		 * Get the WPProQuiz table name prefix. This is appended to the
		 * default WP prefix.
		 *
		 * @since 2.6.0
		 *
		 * @param string $table_section Table section prefix.
		 * @return string table prefix.
		 */
		public static function get_table_prefix( $table_section = '' ) {
			global $wpdb;

			$table_prefix = $wpdb->prefix;

			switch ( $table_section ) {

				case 'wpproquiz':
					$table_prefix = $wpdb->prefix . self::get_table_sub_prefix( $table_section ) . 'pro_quiz_';
					break;

				case 'activity':
					$table_prefix = $wpdb->prefix . self::get_table_sub_prefix( $table_section );
					break;

				default:
					break;
			}

			return apply_filters( 'learndash_table_prefix', $table_prefix, $table_section );
		}

		public static function get_table_sub_prefix( $table_section = '' ) {
			$table_sub_prefix = '';

			switch ( $table_section ) {

				case 'wpproquiz':
					if ( ( defined( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB' ) ) && ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB ) ) {
						$table_sub_prefix = esc_attr( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB );
					} else {
						if ( ! class_exists( 'Learndash_Admin_Data_Upgrades' ) ) {
							require_once( LEARNDASH_LMS_PLUGIN_DIR .'includes/admin/class-learndash-admin-data-upgrades.php' );
						}
						$data_upgrade_proquiz_tables = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Rename_WPProQuiz_Tables' );
						$data_settings = $data_upgrade_proquiz_tables->init_settings();
						if ( isset( $data_settings['prefixes']['current'] ) ) {
							$table_sub_prefix = $data_settings['prefixes']['current'];
						} else {
							if ( ( defined( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT' ) ) && ( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT ) ) {
								$table_sub_prefix = esc_attr( LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT );
							} else {
								$table_sub_prefix = 'wp_';
							}
						}
					}
					
					break;

				case 'activity':
					if ( ( defined( 'LEARNDASH_LMS_DATABASE_PREFIX_SUB' ) ) && ( LEARNDASH_LMS_DATABASE_PREFIX_SUB ) ) {
						$table_sub_prefix = esc_attr( LEARNDASH_LMS_DATABASE_PREFIX_SUB );
					} else {
						$table_sub_prefix = 'learndash_';
					}
					break;

				default:
					break;
			}

			return apply_filters( 'learndash_table_sub_prefix', $table_sub_prefix, $table_section );		
		}

		/**
		 * Utility function to return the table name. This is to prevent hard-coding
		 * of the table names throughout the code files.
		 *
		 * @since 2.6.0
		 *
		 * @param string $table_name Name of table to return full table name.
		 * @param string $table_section Table section prefix.
		 * @return string Table Name if found.
		 */
		public static function get_table_name( $table_name = '', $table_section = '' ) {
			$tables = self::get_tables( $table_section );
			if ( isset( $tables[ $table_name ] ) ) {
				return $tables[ $table_name ];
			}
		}

		// End of functions.
	}
}

// These are the base table names WITHOUT the $wpdb->prefix.
global $learndash_db_tables;
$learndash_db_tables = LDLMS_DB::get_tables();


/*
add_action( 'switch_blog', function( $new_blog, $prev_blog_id ) {
	if ( $new_blog !== $prev_blog_id ) {
		LDLMS_DB::init(true);
	}
}, 10, 2 );
*/