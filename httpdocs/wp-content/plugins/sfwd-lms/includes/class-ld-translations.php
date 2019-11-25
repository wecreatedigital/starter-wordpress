<?php
/*
WordPress language functions
*/
/**
 * LearnDash Admin Translations handler.
 * This class connects to a remote GlotPress server to retreive needed translations for LearnDash core and related add-ons.
 *
 * @package LearnDash
 * @subpackage Admin
 */

if ( ! class_exists( 'LearnDash_Translations' ) ) {
	/**
	 * Class for LearnDash Translations.
	 */
	class LearnDash_Translations {

		/**
		 * Project slug for this instance.
		 *
		 * @var string $project_slug Project Slug.
		 */
		private $project_slug = '';

		/**
		 * Available Translations for project.
		 *
		 * @var array $available_translations Available Translations.
		 */
		private $available_translations = array();

		/**
		 * Installed Translations for project.
		 *
		 * @var array $installed_translations Installed Translations.
		 */
		private $installed_translations = array();

		/**
		 * Array of All Project Slugs.
		 *
		 * @var array $project_slugs.
		 */
		static private $project_slugs = array();

		/**
		 * Translations Directory on server.
		 *
		 * @var string $translations_dir.
		 */
		static private $translations_dir = '';

		/**
		 * Options Key for storing translations data.
		 *
		 * @var string $options_key.
		 */
		static private $options_key = 'ld-translations';

		/**
		 * Debug capture variable.
		 *
		 * @var array $debug_log_array.
		 */
		static public $debug_log_array = array();

		/**
		 * Public constructor for class.
		 *
		 * @param string $project_slug Project slug for instance.
		 */
		public function __construct( $project_slug = '' ) {
			if ( ! empty( $project_slug ) ) {
				$this->project_slug = $project_slug;
			}
		}

		/**
		 * Interface function to register new translation slug.
		 *
		 * @param string $project_slug Project Slug.
		 * @param string $project_language_dir Project Language Directory.
		 */
		public static function register_translation_slug( $project_slug = '', $project_language_dir = '' ) {
			//self::log_debug_message( 'in :' . __FUNCTION__ );
			//self::log_debug_message( 'project_slug :' . $project_slug );

			if ( ( ! empty( $project_slug ) ) && ( ! isset( self::$project_slugs[ $project_slug ] ) ) ) {
				self::$project_slugs[ $project_slug ] = trailingslashit( $project_language_dir );
			}
		}

		/**
		 * Get last update information.
		 */
		public static function get_last_update() {
			$ld_translations = get_option( self::$options_key );
			if ( isset( $ld_translations['last_check'] ) ) {
				return $ld_translations['last_check'];
			}
		}

		/**
		 * Get Language Directory for Project.
		 *
		 * @param string  $project_slug Project Slug.
		 * @param boolean $relative_to_home if true relative directory is returned.
		 * @return string directory path.
		 */
		public static function get_language_directory( $project_slug = '', $relative_to_home = true ) {
			if ( ( ! empty( $project_slug ) ) && ( isset( self::$project_slugs [ $project_slug ] ) ) ) {

				if ( true !== $relative_to_home ) {
					return trailingslashit( self::$project_slugs[ $project_slug ] );
				} else {
					$abspath_tmp = str_replace( '\\', '/', ABSPATH );
					return str_replace( $abspath_tmp, '/', trailingslashit( self::$project_slugs[ $project_slug ] ) );
				}
			}
		}

		/**
		 * Check if language directory is writable for Project Slug.
		 *
		 * @param string $project_slug Project Slug.
		 * @return boolean true if directory is writable.
		 */
		public static function is_language_directory_writable( $project_slug = '' ) {
			if ( ! empty( $project_slug ) ) {
				$translations_dir = self::get_language_directory( $project_slug, false );
				if ( ( ! empty( $translations_dir ) ) && ( is_writable( $translations_dir ) ) ) {
					return true;
				}
			}
		}

		/**
		 * Check if project has available translations.
		 *
		 * @param string $project_slug Project Slug.
		 * @return boolean true.
		 */
		public static function project_has_available_translations( $project_slug = '' ) {
			$ld_translations = get_option( self::$options_key );
			if ( isset( $ld_translations['translation_sets'][ $project_slug ] ) ) {
				return true;
			}
		}

		/** Return available translations for project slug.
		 *
		 * @param string $project_slug Project Slug.
		 * @param string $locale Locale for translations.
		 * @return mixed Translation set array.
		 */
		public static function project_get_available_translations( $project_slug = '', $locale = '' ) {
			$ld_translations = get_option( self::$options_key );
			if ( isset( $ld_translations['translation_sets'][ $project_slug ] ) ) {
				if ( ! empty( $locale ) ) {
					foreach ( $ld_translations['translation_sets'][ $project_slug ] as $translation_set ) {
						if ( $translation_set['wp_locale'] == $locale ) {
							return $translation_set;
						}
					}
				}
				return $ld_translations['translation_sets'][ $project_slug ];
			}
		}

		/**
		 * Get Action URL.
		 *
		 * @param string $action Action.
		 * @param string $project Project Slug.
		 * @param string $locale Locale.
		 * @return string action URL.
		 */
		public static function get_action_url( $action = '', $project = '', $locale = '' ) {
			if ( ! empty( $action ) ) {
				$action_url = remove_query_arg( array( 'action', 'project', 'locale', 'ld-translation-nonce' ) );

				$nonce_key  = 'ld-translation-' . $action;
				$action_url = add_query_arg( array( 'action' => $action ), $action_url );

				if ( ! empty( $project ) ) {
					$nonce_key .= '-' . $project;
					$action_url = add_query_arg( array( 'project' => $project ), $action_url );
				}
				if ( ! empty( $locale ) ) {
					$nonce_key .= '-' . $locale;
					$action_url = add_query_arg( array( 'locale' => $locale ), $action_url );
				}

				$action_nonce = wp_create_nonce( $nonce_key );
				$action_url   = add_query_arg( array( 'ld-translation-nonce' => $action_nonce ), $action_url );

				return $action_url;
			}
		}

		/**
		 * Install Translations
		 *
		 * @param string $project Project Slug.
		 * @param string $locale Translation Locale.
		 */
		public static function install_translation( $project = '', $locale = '' ) {
			$reply_data = array();

			if ( ( ! empty( $project ) ) && ( ! empty( $locale ) ) ) {

				if ( self::is_language_directory_writable( $project ) ) {
					$translation_set = self::project_get_available_translations( $project, $locale );

					if ( ( isset( $translation_set['links'] ) ) && ( ! empty( $translation_set['links'] ) ) ) {
						foreach ( $translation_set['links'] as $link_key => $link_url ) {
							$url_args = apply_filters( 'learndash_translations_url_args', array('timeout' => LEARNDASH_HTTP_REMOTE_GET_TIMEOUT) );

							$dest_filename = self::get_language_directory( $project, false ) . $project . '-' . $locale . '.' . $link_key;
							if ( file_exists( $dest_filename ) ) {
								unlink( $dest_filename );
							}

							$response = wp_remote_get( $link_url, $url_args );
							if ( ( is_array( $response ) ) && ( wp_remote_retrieve_response_code( $response ) == '200' ) ) {
								$response_body = wp_remote_retrieve_body( $response );
								if ( ! empty( $response_body ) ) {
									$fp = fopen( $dest_filename, 'w+' );
									if ( false !== $fp ) {
										fwrite( $fp, $response_body );
										fclose( $fp );
										$reply_data['status']  = true;
										$reply_data['message'] = '<p>' . sprintf(
											// translators: placeholders: Language Name, Language code.
											esc_html_x( 'Translation installed: %1$s (%2$s)', 'placeholders: Language Name, Language code', 'learndash' ),
											$translation_set['english_name'],
											$translation_set['wp_locale']
										) . '</p>';
										$reply_data['translation_set'] = $translation_set;
									}
								}
							}
						}
					}
				}
			}

			return $reply_data;
		}

		/**
		 * Update Translations for Project
		 *
		 * @param string $project Project Slug.
		 * @param string $locale Locale.
		 */
		public static function update_translation( $project = '', $locale = '' ) {
			$reply_data = array();

			if ( ( ! empty( $project ) ) && ( ! empty( $locale ) ) ) {

				if ( self::is_language_directory_writable( $project ) ) {
					$translation_set = self::project_get_available_translations( $project, $locale );

					if ( ( isset( $translation_set['links'] ) ) && ( ! empty( $translation_set['links'] ) ) ) {
						foreach ( $translation_set['links'] as $link_key => $link_url ) {
							$url_args = apply_filters( 'learndash_translations_url_args', array('timeout' => LEARNDASH_HTTP_REMOTE_GET_TIMEOUT) );

							$dest_filename = self::get_language_directory( $project, false ) . $project . '-' . $locale . '.' . $link_key;
							if ( file_exists( $dest_filename ) ) {
								unlink( $dest_filename );
							}

							//self::log_debug_message( 'in ' . __FUNCTION__ );
							//self::log_debug_message( 'link_url ' . $link_url );

							$response = wp_remote_get( $link_url, $url_args );
							if ( ( is_array( $response ) ) && ( wp_remote_retrieve_response_code( $response ) == '200' ) ) {
								$response_body = wp_remote_retrieve_body( $response );
								if ( ! empty( $response_body ) ) {
									$fp = fopen( $dest_filename, 'w+' );
									if ( false !== $fp ) {
										fwrite( $fp, $response_body );
										fclose( $fp );
										$reply_data['status']  = true;
										$reply_data['message'] = '<p>' . sprintf(
											// translators: placeholders: Language Name, Language code.
											esc_html_x( 'Translation updated: %1$s (%2$s)', 'placeholders: Language Name, Language code', 'learndash' ),
											$translation_set['english_name'],
											$translation_set['wp_locale']
										) . '</p>';
									}
								}
							}
						}
					}
				}
			}

			return $reply_data;
		}

		/**
		 * Remove translations for Project.
		 *
		 * @param string $project Project Slug.
		 * @param string $locale Locale for translation.
		 */
		public static function remove_translation( $project = '', $locale = '' ) {
			$reply_data = array();

			if ( ( ! empty( $project ) ) && ( ! empty( $locale ) ) ) {

				if ( self::is_language_directory_writable( $project ) ) {
					$translation_set = self::project_get_available_translations( $project, $locale );

					if ( ( isset( $translation_set['links'] ) ) && ( ! empty( $translation_set['links'] ) ) ) {
						foreach ( $translation_set['links'] as $link_key => $link_url ) {
							$url_args = apply_filters( 'learndash_translations_url_args', array() );

							$dest_filename = self::get_language_directory( $project, false ) . $project . '-' . $locale . '.' . $link_key;
							if ( file_exists( $dest_filename ) ) {
								unlink( $dest_filename );
								$reply_data['status']  = true;
								$reply_data['message'] = '<p>' . sprintf(
									// translators: placeholders: Language Name, Language code.
									esc_html_x( 'Translation removed: %1$s (%2$s)', 'placeholders: Language Name, Language code', 'learndash' ),
									$translation_set['english_name'],
									$translation_set['wp_locale']
								) . '</p>';
							}
						}
					}
				}
			}

			return $reply_data;
		}

		/**
		 * Refresh Translations
		 */
		public static function refresh_translations() {
			$ld_translations               = get_option( self::$options_key );
			$ld_translations['last_check'] = 0;
			update_option( self::$options_key, $ld_translations );
			self::get_available_translations();
		}

		/**
		 * Show translations metabox for Project Slug.
		 */
		public function show_meta_box() {
			if ( ! empty( $this->project_slug ) ) {
				$this->installed_translations = $this->get_installed_translations();
				$this->available_translations = self::get_available_translations( $this->project_slug );
				?>
				<div id="wrap-ld-translations-<?php echo $this->project_slug; ?>" class="wrap wrap-ld-translations">
					<?php
					if ( ( ! empty( $this->available_translations ) ) || ( ! empty( $this->installed_translations ) ) ) {
						$this->show_installed_translations();
						$this->show_available_translations();
					} else {
						?>
						<p><?php esc_html_e( 'No translations available for this plugin.', 'learndash' ); ?></p>
						<?php
					}
					?>
				</div>
				<?php
			}
		}

		/**
		 * Get available translations for project.
		 *
		 * @param string  $project Project Slig.
		 * @param boolean $force Force update.
		 */
		public static function get_available_translations( $project = '', $force = false ) {
			if ( ! empty( self::$project_slugs ) ) {
				$ld_translations = get_option( self::$options_key, null );
				if ( ! isset( $ld_translations['last_check'] ) ) {
					$ld_translations['last_check'] = time() - ( LEARNDASH_TRANSLATIONS_URL_CACHE + 1 );
				} elseif ( ( isset( $_GET['action'] ) ) && ( 'refresh' === $_GET['action'] ) ) {
					if ( ( isset( $_GET['ld-translation-nonce'] ) ) && ( ! empty( $_GET['ld-translation-nonce'] ) ) && ( wp_verify_nonce( $_GET['ld-translation-nonce'], 'ld-translation-refresh' ) ) ) {
						$ld_translations['last_check'] = time() - ( LEARNDASH_TRANSLATIONS_URL_CACHE + 1 );
					}
				}

				$time_diff = abs( time() - intval( $ld_translations['last_check'] ) );

				if ( ( true === $force ) || ( $time_diff > LEARNDASH_TRANSLATIONS_URL_CACHE ) ) {

					$project_slugs = implode( ',', array_keys( self::$project_slugs ) );

					$url      = add_query_arg(
						array(
							'ldlms-glotpress' => 1,
							'action'          => 'translation_sets',
							'project'         => $project_slugs,
						),
						LEARNDASH_TRANSLATIONS_URL_BASE
					);
					$url_args = array( 'timeout' => 10 );
					$url_args = apply_filters( 'learndash_translations_url_args', $url_args );

					//self::log_debug_message( 'in :' . __FUNCTION__ );
					//self::log_debug_message( 'url :' . $url );

					$response = wp_remote_get( $url, $url_args );
					//self::log_debug_message( 'wp_remote_get response:<pre>' . print_r($response, true) .'</pre>' );
					if ( ( is_array( $response ) ) && ( wp_remote_retrieve_response_code( $response ) == '200' ) ) {
						$response_body = wp_remote_retrieve_body( $response );

						if ( ! empty( $response_body ) ) {
							$ld_translation_sets = json_decode( $response_body, true );

							$ld_translation = array(
								'last_check'       => time(),
								'translation_sets' => $ld_translation_sets,
							);
							update_option( self::$options_key, $ld_translation );
						}
					}
				}

				if ( ! empty( $project ) ) {
					if ( ( isset( $ld_translations['translation_sets'][ $project ] ) ) && ( ! empty( $ld_translations['translation_sets'][ $project ] ) ) ) {
						return $ld_translations['translation_sets'][ $project ];
					}
				}
			}
		}

		/**
		 * Show installed translations
		 */
		public function show_installed_translations() {

			$pot_file = $this->get_language_directory( $this->project_slug, false ) . '' . $this->project_slug . '.pot';
			if ( file_exists( $pot_file ) ) {
				$pot_file = $this->get_language_directory( $this->project_slug ) . '' . $this->project_slug . '.pot';
				?>
				<p style="float:right"><?php esc_html_e( 'Download the original strings (POT) File.', 'learndash' ); ?> <a target="_blank" id="learndash-translations-pot-file-<?php echo $this->project_slug; ?>" class="button button-secondary learndash-translations-pot-file" href="<?php echo $pot_file; ?>" title="<?php esc_html_e( 'Download POT File from your server.', 'learndash' ); ?>"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'POT', 'learndash' ); ?></a></p><div style="clear:both"></div>
				<?php
			}
			?>

			<h4><?php esc_html_e( 'Installed Translations', 'learndash' ); ?></h4>
			<table class="ld-installed-translations wp-list-table widefat fixed striped posts">
				<tr>
					<th class="column-locale"><?php esc_html_e( 'Locale', 'learndash' ); ?></th>
					<th class="column-title"><?php esc_html_e( 'Name / Native', 'learndash' ); ?></th>
					<th class="column-actions-local"><?php esc_html_e( 'Download', 'learndash' ); ?></th>
					<th class="column-action-remote"><?php esc_html_e( 'Actions', 'learndash' ); ?></th>
				</tr>
				<?php
				if ( ( is_array( $this->available_translations ) ) && ( ! empty( $this->available_translations ) ) && ( is_array( $this->installed_translations ) ) && ( ! empty( $this->installed_translations ) ) ) {
					foreach ( $this->available_translations as $idx => $translation_set ) {
						$translation_locale = $translation_set['wp_locale'];
						if ( isset( $this->installed_translations[ $translation_locale ] ) ) {
							$installed_set = $this->installed_translations[ $translation_locale ];
							$this->show_installed_translation_row( $translation_locale, $translation_set, $installed_set );
						}
					}

					foreach ( $this->installed_translations as $installed_locale => $installed_set ) {
						$install_matched = false;
						foreach ( $this->available_translations as $idx => $translation_set ) {
							$translation_locale = $translation_set['wp_locale'];
							if ( $translation_locale == $installed_locale ) {
								$install_matched = true;
								break;
							}
						}

						if ( ! $install_matched ) {
							$this->show_installed_translation_row( $installed_locale, null, $installed_set );
						}
					}
				} else {
					?>
					<tr>
						<td colspan="4"><?php echo esc_html__( 'No Translations installed', 'learndash' ); ?></td>
						</tr>
						<?php
				}
				?>
			</table>
			<p>
			<?php
			echo sprintf(
				// translators: placeholder: Language directory.
				esc_html_x( 'All translations are stored into the directory: %s', 'placeholder: Language directory', 'learndash' ),
				'<code>' . esc_attr( '<site root>' ) . $this->get_language_directory( $this->project_slug, true ) . '</code>'
			);
			?>
			<?php
			if ( ! self::is_language_directory_writable( $this->project_slug ) ) {
				?>
					<br /><span class="error"><?php esc_html_e( 'The language directory is not writable', 'learndash' ); ?></span>
					<?php
			}
			?>
			</p>
			<?php
		}

		/**
		 * Show installed translations row.
		 *
		 * @param string $locale Locale.
		 * @param mixed  $translation_set Array of translations.
		 * @param mixed  $installed_set Array of installed translations.
		 */
		public function show_installed_translation_row( $locale = '', $translation_set = null, $installed_set = null ) {
			if ( ! empty( $locale ) ) {
				?>
				<tr>
					<td class="column-locale"><?php echo $locale; ?></td>
					<td class="column-title">
					<?php
					if ( ! is_null( $translation_set ) ) {
						echo $translation_set['english_name'] . '/' . $translation_set['native_name'];
					} else {
						esc_html_e( 'Not from LearnDash', 'learndash' );
					}
						?>
					</td>
					<td class="column-actions-local">
						<?php
						if ( isset( $installed_set['po'] ) ) {
							?>
							<a id="learndash-translations-po-file-<?php echo $locale; ?>" target="_blank" class="button button-secondary learndash-translations-po-file" href="<?php echo $this->get_language_directory( $this->project_slug ) . '/' . $installed_set['po']; ?>" title="<?php esc_html_e( 'Download PO File from your server.', 'learndash' ); ?>"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'PO', 'learndash' ); ?></a>
							<?php
						}
						?>
					</td>
					<td class="column-actions-remote">
						<a id="learndash-translations-<?php echo $this->project_slug; ?>-<?php echo $locale; ?>-remove" class="button button-secondary learndash-translations-remove" href="<?php echo self::get_action_url( 'remove', $this->project_slug, $locale ); ?>" title="<?php esc_html_e( 'Remove translation from server', 'learndash' ); ?>"><span class="dashicons dashicons-trash"></span></a>
																	<?php

																	if ( ! is_null( $translation_set ) ) {
																		$last_updated_time = learndash_get_timestamp_from_date_string( $translation_set['last_modified_gmt'] );
																		if ( ( $installed_set['mo_mtime'] < $last_updated_time ) && ( $installed_set['po_mtime'] < $last_updated_time ) ) {
																			//esc_html_e('Up to date', 'learndash');
																		} else {
																			?>
																			<a href="<?php echo self::get_action_url( 'update', $this->project_slug, $locale ); ?>" class="button button-primary learndash-translations-update" title="<?php esc_html_e( 'Update translation from LearnDash', 'learndash' ); ?>"><?php esc_html_e( 'Update', 'learndash' ); ?></a>
									<?php
																		}
																	}
						?>
					</td>
				</tr>
				<?php
			}
		}

		/**
		 * Show available translations.
		 */
		public function show_available_translations() {
			$wp_languages = get_available_languages();

			if ( empty( $wp_languages ) ) {
				$wp_languages = array();
			}

			if ( ! in_array( 'en_US', $wp_languages ) ) {
				$wp_languages = array_merge( array( 'en_US' ), $wp_languages );
			}

			// Taken from options-general.php.
			if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG && ! in_array( WPLANG, $languages ) ) {
				$wp_languages[] = WPLANG;
			}

			$wp_locale = get_locale();
			if ( ( ! empty( $wp_locale ) ) && ( ! in_array( $wp_locale, $wp_languages ) ) ) {
				$wp_languages[] = $wp_locale;
			}

			if ( ( is_array( $this->available_translations ) ) && ( ! empty( $this->available_translations ) ) ) {

				$available_translations                = array();
				$available_translations['recommended'] = array();
				$available_translations['available']   = array();

				// First we split into buckets based on languages installed to WP.
				foreach ( $this->available_translations as $translation_set ) {
					if ( ! isset( $this->installed_translations[ $translation_set['wp_locale'] ] ) ) {
						if ( in_array( $translation_set['wp_locale'], $wp_languages ) === true ) {
							$available_translations['recommended'][ $translation_set['wp_locale'] ] = $translation_set;
						} else {
							$available_translations['available'][ $translation_set['wp_locale'] ] = $translation_set;
						}
					}
				}

				if ( ( ! empty( $available_translations['recommended'] ) ) || ( ! empty( $available_translations['available'] ) ) ) {
					?>
					<div id="learndash-translations-available">
						<h4><?php esc_html_e( 'Available Translations', 'learndash' ); ?></h4>
						<select id="ld-translation-install-locale-<?php echo $this->project_slug; ?>" class="ld-translation-install-locale" data-project="<?php echo $this->project_slug; ?>">
							<option value=""><?php esc_html_e( '-- Install Translation --', 'learndash' ); ?></option>
							<?php
							$show_opt_group = false;
							if ( ( ! empty( $available_translations['recommended'] ) ) && ( ! empty( $available_translations['available'] ) ) ) {
								$show_opt_group = true;
							}
							if ( ! empty( $available_translations['recommended'] ) ) {
								if ( $show_opt_group ) {
									?>
									<optgroup label="<?php esc_html_e( 'Recommended', 'learndash' ); ?>">
									<?php
								}
								foreach ( $available_translations['recommended'] as $translation_set ) {
									?>
									<option value="<?php echo self::get_action_url( 'install', $this->project_slug, $translation_set['wp_locale'] ); ?>"><?php echo $translation_set['english_name'] . ' / ' . $translation_set['native_name'] . ' (' . $translation_set['wp_locale'] . ')'; ?></option>
									<?php
								}
								if ( $show_opt_group ) {
									?>
									</optgroup>
									<?php
								}
							}

							if ( ! empty( $available_translations['available'] ) ) {
								if ( $show_opt_group ) {
									?>
									<optgroup label="<?php esc_html_e( 'Available', 'learndash' ); ?>">
									<?php
								}
								foreach ( $available_translations['available'] as $translation_set ) {
									?>
									<option value="<?php echo self::get_action_url( 'install', $this->project_slug, $translation_set['wp_locale'] ); ?>"><?php echo $translation_set['english_name'] . ' / ' . $translation_set['native_name'] . ' (' . $translation_set['wp_locale'] . ')'; ?></option>
									<?php
								}
								if ( $show_opt_group ) {
									?>
									</optgroup>
									<?php
								}
							}
						?>
						</select> 
						<a id="learndash-translation-install-<?php echo $this->project_slug; ?>" class="button button-primary learndash-translations-install" href="#"><?php esc_html_e( 'Install', 'learndash' ); ?></a>
					</div>
					<?php
				}
			}
		}

		/**
		 * Get installed trnalsations.
		 */
		public function get_installed_translations() {
			$translation_files = array();

			if ( ! empty( $this->project_slug ) ) {

				$languages_plugins_dir    = $translations_dir = self::get_language_directory( $this->project_slug, false );
				$languages_plugins_dir_mo = $languages_plugins_dir . $this->project_slug . '-*.mo';

				$mo_files = glob( $languages_plugins_dir_mo );
				if ( ! empty( $mo_files ) ) {
					foreach ( $mo_files as $mo_file ) {
						$mo_file       = basename( $mo_file );
						$mo_file_local = str_replace( array( $this->project_slug . '-', '.mo' ), '', $mo_file );
						if ( ! empty( $mo_file_local ) ) {

							if ( ! isset( $translation_files[ $mo_file_local ] ) ) {
								$translation_files[ $mo_file_local ]             = array();
								$translation_files[ $mo_file_local ]['mo']       = $mo_file;
								$translation_files[ $mo_file_local ]['mo_mtime'] = filemtime( $languages_plugins_dir . $mo_file );

								$po_file                  = str_replace( '.mo', '.po', $mo_file );
								$languages_plugins_dir_po = $languages_plugins_dir . $po_file;
								if ( file_exists( $languages_plugins_dir_po ) ) {
									$translation_files[ $mo_file_local ]['po']       = $po_file;
									$translation_files[ $mo_file_local ]['po_mtime'] = filemtime( $languages_plugins_dir . $po_file );
								}
							}
						}
					}
				}
			}
			return $translation_files;
		}

		static function log_debug_message( $message = '' ) {
			self::$debug_log_array[] = $message;
		}

		static function show_debug_log_output() {
			if ( ! empty( self::$debug_log_array ) ) {
				foreach ( self::$debug_log_array as $message ) {
					echo '<br />' . $message;
				}
			}
		}
		// End of functions
	}
}
