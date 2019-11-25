<?php
/**
 * Handles WordPress logic for Addon updates
 *
 * @since 2.5.4
 *
 * @package LearnDash
 * @subpackage Add-on Updates
 */

/**
 * BitBucket API
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/class-ld-bitbucket-api.php';

if ( ! class_exists( 'LearnDash_Addon_Updater' ) ) {
	/**
	 * Class for handling the LearnDash Add-on updates.
	 */
	class LearnDash_Addon_Updater {
		/**
		 * Holds the in process data. This is read in from the $option_key.
		 *
		 * @var array $data;
		 */
		private $data = null;

		/**
		 * Holds reference to BitBucket API object. This is how the updatero class
		 * retreives the repositories and add-on updates.
		 *
		 * @var object $pp_api
		 */
		private $bb_api = null;

		/**
		 * This is the wp_options key used to store the $data related to
		 * the repositories and status.
		 *
		 * @var string $options_key
		 */
		private $options_key = 'learndash-repositories';

		/**
		 * Hold temporary status when installing an add-on.
		 *
		 * @var boolean $_doing_install_upgrade_slug
		 */
		private $_doing_install_upgrade_slug = false;

		/**
		 * Setting for the number of minutes to cache repo check. This
		 * is to prevent too frequent checks again BitBucket servers.
		 *
		 * @var integer $repo_cache_time_limit value in minutes.
		 */
		private $repo_cache_time_limit = 5;

		/**
		 * Setting for the number of minutes to cache readme check. This
		 * is to prevent too frequent checks again BitBucket servers.
		 *
		 * @var integer $$readme_cache_time_limit value in minutes.
		 */
		private $readme_cache_time_limit = 8;


		/**
		 * LearnDash_Addon_Updater constructor.
		 */
		public function __construct() {
			$this->load_repositories_options();

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins' ) );
			add_filter( 'site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins' ) );

			add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 50, 3 );
			add_filter( 'upgrader_pre_download', array( $this, 'upgrader_pre_download_filter' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection_filter' ), 10, 3 );
			add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete_action' ), 10, 2 );
			add_filter( 'update_plugin_complete_actions', array( $this, 'update_plugin_complete_actions' ) );
			add_filter( 'install_plugin_complete_actions', array( $this, 'install_plugin_complete_actions' ), 10, 3 );
		}

		/**
		 * Called when the add-on plugin is upgraded. From the page the user is shown links at the
		 * bottom of the page to return to the plugin page or the WordPress updates page. If the user started
		 * as the LearnDash Add-ons page we will add the 'ld-return-addons' query string parameter. This is
		 * used to return the user.
		 *
		 * @since 2.5.5
		 *
		 * @param array $update_actions Array.
		 */
		public function update_plugin_complete_actions( $update_actions = array() ) {
			if ( ( isset( $_GET['ld-return-addons'] ) ) && ( ! empty( $_GET['ld-return-addons'] ) ) ) {
				// If we have the 'ld-return-addons' element this means we need to go back there only.
				$return_url = esc_attr( $_GET['ld-return-addons'] );

				// we clear out the actions because we only want to show our own.
				$update_actions = array();

				$update_actions['ld-addons-page'] = '<a href="' . $return_url . '" target="_parent">' . esc_html__( 'Return to LearnDash Add-ons Page', 'learndash' ) . '</a>';
			}

			return $update_actions;
		}


		/**
		 * Called when the add-on plugin is installed. From the page the user is shown links at the
		 * bottom of the page to return to the plugin page or the WordPress updates page. If the user started
		 * as the LearnDash Add-ons page we will add the 'ld-return-addons' query string parameter. This is
		 * used to return the user.
		 *
		 * @param array  $install_actions Array.
		 * @param object $api Object.
		 * @param string $plugin_file String.
		 *
		 * @return array
		 *
		 * @since 2.5.5
		 */
		public function install_plugin_complete_actions( $install_actions = array(), $api, $plugin_file = '' ) {
			if ( ( isset( $_GET['ld-return-addons'] ) ) && ( ! empty( $_GET['ld-return-addons'] ) ) ) {
				// If we have the 'ld-return-addons' element this means we need to go back there only.
				$return_url = esc_attr( $_GET['ld-return-addons'] );

				// we clear out the actions because we only want to show our own.
				$install_actions = array();

				if ( ! empty( $plugin_file ) ) {
					$install_actions['activate_plugin'] = '<a class="button button-primary" href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ) . '" target="_parent">' . __( 'Activate Plugin', 'learndash' ) . '</a>';
				}
				$install_actions['ld-addons-page'] = '<a href="' . $return_url . '" target="_parent">'  . esc_html__( 'Return to LearnDash Add-ons Page', 'learndash' ) . '</a>';
			}

			return $install_actions;
		}

		/**
		 * This is called from the plugin listing page. This hook lets of add supplemental information
		 * to the plugin row. Like the Upgrade Notice.
		 *
		 * @since 2.5.5
		 *
		 * @param object $current_plugin_metadata Current meta data.
		 * @param object $new_plugin_metadata New meta data.
		 */
		public function show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {
			// check "upgrade_notice".
			if ( ( isset( $new_plugin_metadata->upgrade_notice ) && ( strlen( trim( $new_plugin_metadata->upgrade_notice ) ) > 0 ) ) ) {
				echo '<br /><span style="display: block; background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px">' . esc_html( $new_plugin_metadata->upgrade_notice ) . '</span>';
			}
		}

		/**
		 * PluginAPI Filter.
		 *
		 * @param array  $data Array.
		 * @param string $action String.
		 * @param null   $args Null.
		 *
		 * @return array|mixed|object
		 */
		public function plugins_api_filter( $data, $action = '', $args = null ) {
			if ( ( 'plugin_information' !== $action ) || ( is_null( $args ) ) || ( ! isset( $args->slug ) ) ) {
				return $data;
			}

			$this->load_repositories_options();

			if ( isset( $this->data['plugins'][ $args->slug ] ) ) {
				$data = json_decode( json_encode( $this->data['plugins'][ $args->slug ] ), false );

				// We already have the obj but we update the BB download URL just in case.
				$data->download_link = $this->bb_api->get_bitbucket_repository_download_url( $data->slug );
				$data->download_link = $this->bb_api->setup_url_params( $data->download_link );
			}

			return $data;
		}

		/**
		 * Upgrader Pre-Download filter.
		 *
		 * @param string $action String.
		 * @param string $download_url String.
		 * @param object $updater updater instance.
		 *
		 * @return mixed
		 */
		public function upgrader_pre_download_filter( $action, $download_url, $updater ) {

			$this->load_repositories_options();

			$repo_download_base_url = $this->bb_api->get_download_base_url();
			if ( ( ! empty( $repo_download_base_url ) ) && ( strncasecmp( $repo_download_base_url, $download_url, strlen( $repo_download_base_url ) ) === 0 ) ) {

				$plugin_url = str_replace( trailingslashit( $this->bb_api->get_download_base_url() ), '', $download_url );
				$plugin_url_parts = explode( '/', $plugin_url );

				if ( isset( $this->data['plugins'][ $plugin_url_parts[0] ] ) ) {
					$this->_doing_install_upgrade_slug = $plugin_url_parts[0];
					add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection_filter' ), 10, 3 );
				}
			} else {
				$this->_doing_install_upgrade_slug = false;
				remove_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection_filter' ), 10, 3 );
			}

			return $action;
		}

		/**
		 * Upgrader Source Selection Filter.
		 *
		 * @param object $source instance.
		 * @param object $remote_source instance.
		 * @param object $upgrader nstance of WP_Upgrader_Skin.
		 *
		 * @return string|\WP_Error
		 */
		public function upgrader_source_selection_filter( $source, $remote_source, $upgrader ) {
			global $wp_filesystem;

			// Basic sanity checks.
			if ( ! isset( $source, $remote_source, $upgrader, $upgrader->skin, $wp_filesystem ) ) {
				return $source;
			}

			if ( ! empty( $this->_doing_install_upgrade_slug ) ) {
				// Rename the source to match the existing directory.
				$corrected_source = trailingslashit( $remote_source ) . $this->_doing_install_upgrade_slug . '/';
				if ( $source !== $corrected_source ) {

					$upgrader->skin->feedback(sprintf(
						'Renaming %s to %s&#8230;',
						'<span class="code">' . basename( $source ) . '</span>',
						'<span class="code">' . $this->_doing_install_upgrade_slug . '</span>'
					));

					if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
						$upgrader->skin->feedback( esc_html__( 'Directory successfully renamed.', 'learndash' ) );
						return $corrected_source;
					} else {
						return new WP_Error(
							'learndash-failed',
							__( 'Unable to rename the update to match the existing directory.', 'learndash' )
						);
					}
				}
			}

			return $source;
		}


		/**
		 * Upgrader Process Complete Action.
		 *
		 * @param object $upgrader Upgrader instance.
		 * @param array  $args Array.
		 *
		 * @return mixed
		 */
		public function upgrader_process_complete_action( $upgrader, $args = array() ) {
			if ( ( isset( $args['plugins'] ) ) && ( ! empty( $args['plugins'] ) ) ) {
				$all_plugins = get_plugins();
				$wp_installed_languages = get_available_languages();
				if ( ! in_array( 'en_US', $wp_installed_languages ) ) {
					$wp_installed_languages = array_merge( array( 'en_US' ), $wp_installed_languages );
				}

				if ( ! empty( $wp_installed_languages ) ) {
					foreach ( $args['plugins'] as $plugin_full_slug ) {
						$plugin_dir = dirname( $plugin_full_slug );

						// A little hack. For LD core and ProPanel we don't use the real slug internally.
						if ( 'learndash-propanel' === $plugin_dir ) {
							$plugin_dir = 'learndash-propanel-readme';
						} else if ( 'sfwd-lms' === $plugin_dir ) {
							$plugin_dir = 'learndash-core-readme';
						}

						if ( ( isset( $this->data['repositories'][ $plugin_dir ] ) ) && ( isset( $all_plugins[ $plugin_full_slug ] ) ) ) {
							if ( ( isset( $all_plugins[ $plugin_full_slug ]['TextDomain'] ) ) && ( ! empty( $all_plugins[ $plugin_full_slug ]['TextDomain'] ) ) ) {
								$plugin_text_domain = $all_plugins[ $plugin_full_slug ]['TextDomain'];

								LearnDash_Translations::get_available_translations( $plugin_text_domain );

								$update_messages = array();
								foreach ( $wp_installed_languages as $locale ) {
									$reply = LearnDash_Translations::install_translation( $plugin_text_domain, $locale );
									if ( ( isset( $reply['translation_set'] ) ) && ( ! empty( $reply['translation_set'] ) ) ) {

										$update_messages[ $locale ] = sprintf(
											wp_kses_post(
												// translators: placeholders: Translation Name, Translation Locale.
												_x( '<h2>Updating translations for %1$s (%2$s)...</h2>', 'placeholders: Translation Name, Translation Locale', 'learndash' )
											),
											$reply['translation_set']['english_name'],
											$reply['translation_set']['wp_locale']
										);
									}
								}

								if ( ! empty( $update_messages ) ) {
									$update_mess_name = sprintf(
										// translators: placeholder: plugin title.
										esc_html_x( '%s: Translations', 'placeholder: plugin title', 'learndash' ),
										$all_plugins[ $plugin_full_slug ]['Title']
									);
									$upgrader->skin->feedback( $update_mess_name );

									foreach ( $update_messages as $update_message ) {
										$upgrader->skin->feedback( $update_message );
									}
								}
							}
						}
					}
				}
			}

			$this->load_repositories_options();
			$this->get_addon_plugins();

			return $upgrader;
		}

		/**
		 * Intercept WordPress updater data.
		 *
		 * @param array $_transient_data Updae transient data.
		 *
		 * return array.
		 */
		public function pre_set_site_transient_update_plugins( $_transient_data ) {
			global $pagenow;

			if ( ! is_object( $_transient_data ) ) {
				$_transient_data = new stdClass();
			}

			if ( ( 'plugins.php' == $pagenow ) && ( is_multisite() ) ) {
				return $_transient_data;
			}

			$this->load_repositories_options();

			if ( ! empty( $this->data['updates'] ) ) {
				foreach ( $this->data['updates'] as $plugin_wp_slug => $plugin_update_obj ) {
					remove_action( 'in_plugin_update_message-' . $plugin_wp_slug, array( $this, 'show_upgrade_notification' ), 10, 2 );
				}
			}

			$this->generate_plugin_updates();

			if ( ! empty( $this->data['updates'] ) ) {
				foreach ( $this->data['updates'] as $plugin_wp_slug => $plugin_update_obj ) {
					if ( ! isset( $_transient_data->response[ $plugin_wp_slug ] ) ) {
						// We already have the obj but we update the BB download URL just in case.
						$plugin_update_obj->package = $this->bb_api->get_bitbucket_repository_download_url( $plugin_update_obj->slug );
						$plugin_update_obj->package = $this->bb_api->setup_url_params( $plugin_update_obj->package );

						$_transient_data->response[ $plugin_wp_slug ] = $plugin_update_obj;
					}
					add_action( 'in_plugin_update_message-' . $plugin_wp_slug, array( $this, 'show_upgrade_notification' ), 10, 2 );
				}
			}

			return $_transient_data;
		}

		/**
		 * Function to reset repo data.
		 *
		 * @since 2.5.9
		 */
		public function reset_repo() {
			delete_option( $this->options_key );
		}

		/**
		 * Load Respository options.
		 */
		public function load_repositories_options() {

			if ( isset( $_GET['repo_reset'] ) ) {
				$this->reset_repo();
				$admin_url = remove_query_arg( 'repo_reset' );
				wp_redirect( $admin_url );
				die();
			}

			if ( is_null( $this->data ) ) {
				$this->data = get_option( $this->options_key, array() );
				if ( ! is_array( $this->data ) ) {
					$this->data = array();
				}
				if ( empty( $this->data ) ) {
					$this->data['last_check'] = 0;
					$this->data['repositories'] = array();
					$this->data['plugins'] = array();
					$this->data['tags'] = array();
				}
			}

			if ( is_null( $this->bb_api ) ) {
				$this->bb_api = new LearnDash_BitBucket_API();
			}

			// Alternate option to define the following in wp-config.php but it MUST be greater then 5 (minutes).
			if ( ( defined( 'LEARNDASH_REPO_CACHE_TIME_LIMIT' ) ) && ( LEARNDASH_REPO_CACHE_TIME_LIMIT > 5 ) ) {
				$this->repo_cache_time_limit = LEARNDASH_REPO_CACHE_TIME_LIMIT;
			}

			if ( ( defined( 'LEARNDASH_README_CACHE_TIME_LIMIT' ) ) && ( LEARNDASH_README_CACHE_TIME_LIMIT > 5 ) ) {
				$this->readme_cache_time_limit = LEARNDASH_README_CACHE_TIME_LIMIT;
			}
		}

		/**
		 * Update repositories options.
		 */
		public function update_repositories_options() {
			return update_option( $this->options_key, $this->data, false );
		}

		/**
		 * Get Add-on Plugins
		 *
		 * @param boolean $override_cache Flag to ignore cache timeout.
		 */
		public function get_addon_plugins( $override_cache = false ) {
			$this->load_repositories_options();
			$time_current = time();
			$time_diff = $time_current - $this->data['last_check'];
			$time_cache = $this->repo_cache_time_limit * MINUTE_IN_SECONDS;

			if ( ( 1 == $override_cache ) || ( empty( $this->data['last_check'] ) ) || ( $time_diff > $time_cache ) ) {

				$repositories = $this->bb_api->get_bitbucket_repositories();

				if ( ! empty( $repositories ) ) {

					// Update our last check timestamp for later caching.
					$this->data['last_check'] = time();

					foreach ( $repositories as $bb_slug => $bb_repo ) {
						if ( ( ! isset( $this->data['repositories'][ $bb_slug ] ) ) || ( strtotime( $bb_repo->updated_on ) > strtotime( $this->data['repositories'][ $bb_slug ]->updated_on ) ) ) {
							$this->data['repositories'][ $bb_slug ] = $bb_repo;
							$this->update_plugin_readme( $bb_slug, true );
						}
					}
				}
			} else {
				foreach ( $this->data['repositories'] as $bb_slug => $bb_repo ) {
					if ( ( ! isset( $this->data['plugins'][ $bb_slug ] ) ) || ( strtotime( $bb_repo->updated_on ) > strtotime( $this->data['plugins'][ $bb_slug ]['last_updated'] ) ) ) {
						$this->update_plugin_readme( $bb_slug, true );
					}
				}
			}

			$this->order_plugins();
			$this->generate_plugin_updates();

			// Then commit the changes to wp_options.
			$this->update_repositories_options();

			return $this->data['plugins'];
		}

		/**
		 * Order plugins by last update timestamp.
		 *
		 * @param string $order_field How to order the plugins.
		 */
		public function order_plugins( $order_field = 'updated_on' ) {
			// Before we return we reorder the items to sort by last change date.
			if ( ( isset( $this->data['repositories'] ) ) && ( ! empty( $this->data['repositories'] ) ) && ( isset( $this->data['plugins'] ) ) && ( ! empty( $this->data['plugins'] ) ) ) {
				$repos_updated_on = array();
				foreach ( $this->data['repositories'] as $repo_slug => $repo ) {
					if ( ( is_object( $repo ) ) && ( property_exists( $repo, 'updated_on' ) ) ) {
						$update_on_timestamp = strtotime( $repo->updated_on );
						$repos_updated_on[ $update_on_timestamp . '-' . $repo_slug ] = $repo_slug;
					}
				}
				krsort( $repos_updated_on );

				$repos_data_sorted = array();
				foreach ( $repos_updated_on as $repo_slug ) {
					$repos_data_sorted[ $repo_slug ] = $this->data['repositories'][ $repo_slug ];
				}
				$this->data['repositories'] = $repos_data_sorted;

				$plugins_data_sorted = array();
				foreach ( $repos_updated_on as $repo_slug ) {
					if ( isset( $this->data['plugins'][ $repo_slug ] ) ) {
						$plugins_data_sorted[ $repo_slug ] = $this->data['plugins'][ $repo_slug ];
					}
				}
				$this->data['plugins'] = $plugins_data_sorted;
			}
		}

		/**
		 * Update plugin readme. Retreives update via BB API.
		 *
		 * @param string  $plugin_slug Slug of plugin to update.
		 * @param boolean $override_cache Flag to ignore cache.
		 */
		public function update_plugin_readme( $plugin_slug = '', $override_cache = false ) {
			if ( ! empty( $plugin_slug ) ) {
				if ( ( isset( $this->data['repositories'][ $plugin_slug ] ) ) && ( ! empty( $this->data['repositories'][ $plugin_slug ] ) ) ) {
					$bb_repo = $this->data['repositories'][ $plugin_slug ];

					if ( isset( $this->data['plugins'][ $plugin_slug ] ) ) {
						$plugin_item = $this->data['plugins'][ $plugin_slug ];

						if ( true !== $override_cache ) {
							if ( ( isset( $plugin_item['last_check'] ) ) && ( ( $plugin_item['last_check'] - time() ) < ( $this->readme_cache_time_limit * MINUTE_IN_SECONDS ) ) ) {
								return $plugin_item;
							}
						}
					}

					$plugin_readme = $this->bb_api->get_bitbucket_repository_readme( $plugin_slug );
					if ( ( ! empty( $plugin_readme ) ) && ( is_array( $plugin_readme ) ) ) {

						$plugin_readme['last_check'] = time();
						$plugin_readme['external'] = true;

						if ( ( property_exists( $bb_repo, 'slug' ) ) && ( ! empty( $bb_repo->slug ) ) ) {
							$plugin_readme['bb_slug'] = $bb_repo->slug;
						}

						if ( ! isset( $plugin_readme['slug'] ) ) {
							if ( ( property_exists( $bb_repo, 'slug' ) ) && ( ! empty( $bb_repo->slug ) ) ) {
								$plugin_readme['slug'] = $bb_repo->slug;
							}
						}

						if ( ( ! isset( $plugin_readme['plugin_uri'] ) ) || ( empty( $plugin_readme['plugin_uri'] ) ) ) {
							if ( ( property_exists( $bb_repo, 'website' ) ) && ( ! empty( $bb_repo->website ) ) ) {
								$plugin_readme['plugin_uri'] = esc_url( $bb_repo->website );
							}
						}

						if ( ( ! isset( $plugin_readme['homepage'] ) ) || ( empty( $plugin_readme['homepage'] ) ) ) {
							if ( ( isset( $plugin_readme['plugin_uri'] ) ) && ( ! empty( $plugin_readme['plugin_uri'] ) ) ) {
								$plugin_readme['homepage'] = $plugin_readme['plugin_uri'];
							}
						}

						if ( ( ! isset( $plugin_readme['last_updated'] ) ) || ( empty( $plugin_readme['last_updated'] ) ) ) {
							if ( ( property_exists( $bb_repo, 'updated_on' ) ) && ( ! empty( $bb_repo->updated_on ) ) ) {
								$plugin_readme['last_updated'] = $bb_repo->updated_on;
							}
						}

						$readme_array = $this->convert_readme( $plugin_readme );
						if ( ( ! empty( $readme_array ) ) && ( is_array( $readme_array ) ) ) {
							if ( ! isset( $readme_array['show-add-on'] ) ) {
								if ( ( 'learndash-core-readme' === $plugin_slug ) || ( 'learndash-propanel-readme' === $plugin_slug ) ) {
									$readme_array['show-add-on'] = 'no';
								}
							}

							$this->data['plugins'][ $plugin_slug ] = $readme_array;
							return $readme_array;
						}
					}
				}
			}
		}

		/**
		 * Generate plugin updates.
		 */
		public function generate_plugin_updates() {
			$this->data['updates'] = array();
			$all_plugins = get_plugins();

			// Then from the 'plugins' node. This lets us remove items we didn't retreive from 'repositories'.
			if ( ! empty( $this->data['plugins'] ) ) {
				foreach ( $this->data['plugins'] as $plugin_slug => &$plugin_readme ) {

					if ( ( 'learndash-core-readme' === $plugin_slug ) || ( 'learndash-propanel-readme' === $plugin_slug ) ) {
						continue;
					}

					if ( ( ! isset( $plugin_readme['bb_slug'] ) ) || ( empty( $plugin_readme['bb_slug'] ) ) || ( ! isset( $this->data['repositories'][ $plugin_readme['bb_slug'] ] ) ) ) {
						unset( $this->data['plugins'][ $plugin_slug ] );
					} else {
						$plugin_found = false;
						foreach ( $all_plugins as $all_plugin_slug => $all_plugin_data ) {
							if ( strncasecmp( $plugin_readme['slug'], $all_plugin_slug, strlen( $plugin_readme['slug'] ) ) === 0 ) {
								$plugin_found = true;

								$plugin_readme['wp_slug'] = $all_plugin_slug;
								$plugin_readme['plugin_status'] = array();
								$plugin_readme['plugin_status']['status'] = 'latest_installed';
								$plugin_readme['plugin_status']['url'] = false;
								$plugin_readme['plugin_status']['version'] = false;
								$plugin_readme['plugin_status']['file'] = $all_plugin_slug;

								if ( version_compare( $plugin_readme['version'], $all_plugin_data['Version'], '>' ) ) {
									$plugin_readme['plugin_status']['status'] = 'update_available';
									$plugin_readme['plugin_status']['version'] = $plugin_readme['version'];
									$plugin_readme['plugin_status']['file'] = $all_plugin_slug;
									if ( current_user_can( 'update_plugins' ) ) {
										$plugin_readme['plugin_status']['url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $all_plugin_slug ), 'upgrade-plugin_' . $all_plugin_slug );
										$plugin_readme['plugin_status']['url'] = add_query_arg(
											'ld-return-addons',
											$_SERVER['REQUEST_URI'],
											$plugin_readme['plugin_status']['url']
										);
									}

									$obj = new stdClass();
									$obj->slug = $plugin_readme['slug'];
									$obj->plugin = $all_plugin_slug;
									$obj->new_version = $plugin_readme['version'];

									$obj->package = $this->bb_api->get_bitbucket_repository_download_url( $plugin_readme['slug'] );
									$obj->package = $this->bb_api->setup_url_params( $obj->package );

									if ( ( isset( $plugin_readme['plugin_uri'] ) ) && ( ! empty( $plugin_readme['plugin_uri'] ) ) ) {
										$obj->url = $plugin_readme['plugin_uri'];
									}

									if ( ( isset( $plugin_readme['tested'] ) ) && ( ! empty( $plugin_readme['tested'] ) ) ) {
										$obj->tested = $plugin_readme['tested'];
									}

									if ( ( isset( $plugin_readme['icons'] ) ) && ( ! empty( $plugin_readme['icons'] ) ) ) {
										$obj->icons = $plugin_readme['icons'];
									}

									if ( ( isset( $plugin_readme['banners'] ) ) && ( ! empty( $plugin_readme['banners'] ) ) ) {
										$obj->banners = $plugin_readme['banners'];
									}

									if ( ( isset( $plugin_readme['upgrade_notice']['content'][ $obj->new_version ] ) ) && ( ! empty( $plugin_readme['upgrade_notice']['content'][ $obj->new_version ] ) ) ) {
										$obj->upgrade_notice = $plugin_readme['upgrade_notice']['content'][ $obj->new_version ];
									}

									$this->data['updates'][ $all_plugin_slug ] = $obj;
									break;
								}
							}
						}

						// We didn't find out plugin in the installed. So mark it to install.
						if ( false === $plugin_found ) {
							$plugin_readme['plugin_status'] = array();
							$plugin_readme['plugin_status']['status'] = 'install';
							$plugin_readme['plugin_status']['version'] = $plugin_readme['version'];
							$plugin_readme['plugin_status']['file'] = false;
							if ( current_user_can( 'install_plugins' ) ) {
								$plugin_readme['plugin_status']['url'] = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
								$plugin_readme['plugin_status']['url'] = add_query_arg( 'ld-return-addons', $_SERVER['REQUEST_URI'], $plugin_readme['plugin_status']['url'] );
							}
						}
					}
				}
			}
		}

		/**
		 * Add Readme Tags.
		 *
		 * @param array $readme_array Readme Array.
		 */
		public function add_readme_tags( $readme_array = array() ) {
			if ( ( isset( $readme_array['tags'] ) ) && ( ! empty( $readme_array['tags'] ) ) ) {
				foreach ( $readme_array['tags'] as $tag ) {
					$tag = trim( $tag );
					if ( ! empty( $tag ) ) {
						if ( ! empty( $this->data['tags'][ $tag ] ) ) {
							$this->data['tags'][ $tag ] = array();
						}
						$this->data['tags'][ $tag ][] = $readme_array['slug'];
					}
				}
			}
		}

		/**
		 * Convert Readme
		 *
		 * @param array $readme Readme array of elements.
		 */
		public function convert_readme( $readme = array() ) {
			if ( ( ! isset( $readme['author'] ) ) || ( empty( $readme['author'] ) ) ) {
				$readme['author'] = 'LearnDash';
			}

			if ( ( ! isset( $readme['version'] ) ) || ( empty( $readme['version'] ) ) ) {
				if ( ( isset( $readme['stable_tag'] ) ) && ( ! empty( $readme['stable_tag'] ) ) ) {
					$readme['version'] = $readme['stable_tag'];
				} else {
					$readme['version'] = '0.0';
				}
			}

			if ( ( isset( $readme['author_uri'] ) ) && ( ! empty( $readme['author_uri'] ) ) ) {
				if ( ( isset( $readme['author'] ) ) && ( ! empty( $readme['author'] ) ) ) {
					$readme['author'] = '<a href="' . $readme['author_uri'] . '">' . $readme['author'] . '</a>';
				}
			}

			if ( ( ! isset( $readme['requires_at_least'] ) ) || ( empty( $readme['requires'] ) ) ) {
				if ( ( isset( $readme['requires_at_least'] ) ) && ( ! empty( $readme['requires_at_least'] ) ) ) {
					$readme['requires'] = $readme['requires_at_least'];
				}
			}

			if ( ( ! isset( $readme['tested'] ) ) || ( empty( $readme['tested'] ) ) ) {
				if ( ( isset( $readme['tested_up_to'] ) ) && ( ! empty( $readme['tested_up_to'] ) ) ) {
					$readme['tested'] = $readme['tested_up_to'];
				}
			}

			if ( ( ! isset( $readme['short_description'] ) ) || ( empty( $readme['short_description'] ) ) ) {
				if ( ( isset( $readme['description']['content_raw'] ) ) && ( ! empty( $readme['description']['content_raw'] ) ) ) {
					$readme['short_description'] = $readme['description']['content_raw'];
				}
			}

			if ( ! isset( $readme['icons'] ) ) {
				$readme['icons'] = array();
				if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . 'assets/images-add-ons/' . $readme['slug'] . '_256x256.jpg' ) ) {
					$readme['icons']['1x'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_256x256.jpg';
					$readme['icons']['2x'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_256x256.jpg';
					$readme['icons']['default'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_256x256.jpg';
				} else if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . 'assets/images-add-ons/learndash-default_256x256.jpg' ) ) {
					$readme['icons']['1x'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_256x256.jpg';
					$readme['icons']['2x'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_256x256.jpg';
					$readme['icons']['default'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_256x256.jpg';
				}
			}

			if ( ! isset( $readme['banners'] ) ) {
				$readme['banners'] = array();
				if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . 'assets/images-add-ons/' . $readme['slug'] . '_banner.jpg' ) ) {
					$readme['banners']['low'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_banner.jpg';
					$readme['banners']['high'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_banner.jpg';
					$readme['banners']['default'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/' . $readme['slug'] . '_banner.jpg';
				} else if ( file_exists( LEARNDASH_LMS_PLUGIN_DIR . 'assets/images-add-ons/learndash-default_banner.jpg' ) ) {
					$readme['banners']['low'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_banner.jpg';
					$readme['banners']['high'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_banner.jpg';
					$readme['banners']['default'] = LEARNDASH_LMS_PLUGIN_URL . 'assets/images-add-ons/learndash-default_banner.jpg';
				}
			}

			return $readme;
		}

		/**
		 * Get plugin information
		 *
		 * @param string $plugin_slug Slug of plugin.
		 */
		public function get_plugin_information( $plugin_slug = '' ) {
			if ( ! empty( $plugin_slug ) ) {
				$this->load_repositories_options();
				return $this->update_plugin_readme( $plugin_slug );
			}
		}		

		// End of functions.
	}
}

global $LearnDash_Addon_Updater;
add_action( 'learndash_admin_init', function() {
	$LearnDash_Addon_Updater = new LearnDash_Addon_Updater();
} );
