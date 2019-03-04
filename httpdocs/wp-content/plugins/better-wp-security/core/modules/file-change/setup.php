<?php

if ( ! class_exists( 'ITSEC_File_Change_Setup' ) ) {

	class ITSEC_File_Change_Setup {

		private
			$defaults;

		public function __construct() {

			add_action( 'itsec_modules_do_plugin_activation', array( $this, 'execute_activate' ) );
			add_action( 'itsec_modules_do_plugin_deactivation', array( $this, 'execute_deactivate' ) );
			add_action( 'itsec_modules_do_plugin_uninstall', array( $this, 'execute_uninstall' ) );
			add_action( 'itsec_modules_do_plugin_upgrade', array( $this, 'execute_upgrade' ), null, 2 );

		}

		/**
		 * Execute module activation.
		 *
		 * @since 4.0
		 *
		 * @return void
		 */
		public function execute_activate() {
		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		public function execute_deactivate() {

			wp_clear_scheduled_hook( 'itsec_file_check' );

			ITSEC_Core::get_scheduler()->unschedule_single( 'file-change', null );
			ITSEC_Core::get_scheduler()->unschedule_single( 'file-change-fast', null );
		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		public function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_file_change' );
			delete_site_option( 'itsec_local_file_list' );
			delete_site_option( 'itsec_local_file_list_0' );
			delete_site_option( 'itsec_local_file_list_1' );
			delete_site_option( 'itsec_local_file_list_2' );
			delete_site_option( 'itsec_local_file_list_3' );
			delete_site_option( 'itsec_local_file_list_4' );
			delete_site_option( 'itsec_local_file_list_5' );
			delete_site_option( 'itsec_local_file_list_6' );
			delete_site_option( 'itsec_file_change_warning' );

			require_once( dirname( __FILE__ ) . '/scanner.php' );

			ITSEC_Lib_Distributed_Storage::clear_group( 'file-change-progress' );
			ITSEC_Lib_Distributed_Storage::clear_group( 'file-list' );
			delete_site_option( ITSEC_File_Change_Scanner::DESTROYED );
		}

		/**
		 * Execute module upgrade
		 *
		 * @return void
		 */
		public function execute_upgrade( $itsec_old_version ) {

			if ( $itsec_old_version < 4000 ) {

				global $itsec_bwps_options;

				$current_options = get_site_option( 'itsec_file_change' );

				// Don't do anything if settings haven't already been set, defaults exist in the module system and we prefer to use those
				if ( false !== $current_options ) {

					$current_options['enabled']      = isset( $itsec_bwps_options['id_fileenabled'] ) && $itsec_bwps_options['id_fileenabled'] == 1 ? true : false;
					$current_options['email']        = isset( $itsec_bwps_options['id_fileemailnotify'] ) && $itsec_bwps_options['id_fileemailnotify'] == 0 ? false : true;
					$current_options['notify_admin'] = isset( $itsec_bwps_options['id_filedisplayerror'] ) && $itsec_bwps_options['id_filedisplayerror'] == 0 ? false : true;
					$current_options['method']       = isset( $itsec_bwps_options['id_fileincex'] ) && $itsec_bwps_options['id_fileincex'] == 0 ? false : true;

					if ( isset( $itsec_bwps_options['id_specialfile'] ) && ! is_array( $itsec_bwps_options['id_specialfile'] ) && strlen( $itsec_bwps_options['id_specialfile'] ) > 1 ) {

						$current_options['file_list'] .= explode( PHP_EOL, $itsec_bwps_options['id_specialfile'] );

					}

					update_site_option( 'itsec_file_change', $current_options );

				}
			}

			if ( $itsec_old_version < 4028 ) {

				if ( ! is_multisite() ) {

					$options = array(
						'itsec_local_file_list',
						'itsec_local_file_list_0',
						'itsec_local_file_list_1',
						'itsec_local_file_list_2',
						'itsec_local_file_list_3',
						'itsec_local_file_list_4',
						'itsec_local_file_list_5',
						'itsec_local_file_list_6',
					);

					foreach ( $options as $option ) {

						$list = get_site_option( $option );

						if ( $list !== false ) {

							delete_site_option( $option );
							add_option( $option, $list, '', 'no' );

						}

					}

				}

			}

			if ( $itsec_old_version < 4041 ) {
				$current_options = get_site_option( 'itsec_file_change' );

				// If there are no current options, go with the new defaults by not saving anything
				if ( is_array( $current_options ) ) {
					// Make sure the new module is properly activated or deactivated
					if ( $current_options['enabled'] ) {
						ITSEC_Modules::activate( 'file-change' );
					} else {
						ITSEC_Modules::deactivate( 'file-change' );
					}

					// remove 'enabled' which isn't use in the new module
					unset( $current_options['enabled'] );

					// This used to be boolean. Attempt to migrate to new string, falling back to default
					if ( ! is_array( $current_options['method'] ) ) {
						$current_options['method'] = ( $current_options['method'] ) ? 'exclude' : 'include';
					} elseif ( ! in_array( $current_options['method'], array( 'include', 'exclude' ) ) ) {
						$current_options['method'] = 'exclude';
					}

					ITSEC_Modules::set_settings( 'file-change', $current_options );
				}
			}

			if ( $itsec_old_version < 4079 ) {
				wp_clear_scheduled_hook( 'itsec_execute_file_check_cron' );
			}

			if ( $itsec_old_version < 4088 ) {
				$types    = ITSEC_Modules::get_setting( 'file-change', 'types' );
				$defaults = array( '.jpg', '.jpeg', '.png', '.log', '.mo', '.po' );

				sort( $types );
				sort( $defaults );

				$update = false;

				if ( $types === $defaults ) {
					$update = true;
				} else {
					$defaults[] = '.lock';

					sort( $defaults );

					if ( $types === $defaults ) {
						$update = true;
					}
				}

				if ( $update ) {
					ITSEC_Modules::set_setting( 'file-change', 'types', ITSEC_Modules::get_default( 'file-change', 'types' ) );
				}

				require_once( dirname( __FILE__ ) . '/scanner.php' );

				$options   = array(
					'itsec_local_file_list',
					'itsec_local_file_list_0',
					'itsec_local_file_list_1',
					'itsec_local_file_list_2',
					'itsec_local_file_list_3',
					'itsec_local_file_list_4',
					'itsec_local_file_list_5',
					'itsec_local_file_list_6',
				);
				$file_list = array();

				$home = get_home_path();

				foreach ( $options as $option ) {
					$opt_list = get_site_option( $option );

					if ( $opt_list && is_array( $opt_list ) ) {
						foreach ( $opt_list as $file => $attr ) {
							$file_list[ $home . $file ] = $attr;
						}
					}
				}

				if ( $file_list ) {
					ITSEC_File_Change_Scanner::record_file_list( $file_list );
				}

				ITSEC_Core::get_scheduler()->unschedule( 'file-change' );
				ITSEC_File_Change_Scanner::schedule_start( false );
			}

			if ( $itsec_old_version < 4090 ) {
				require_once( dirname( __FILE__ ) . '/scanner.php' );

				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change', null );
				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change-fast', null );
				ITSEC_Lib_Distributed_Storage::clear_group( 'file-change-progress' );

				$file_list_option = get_site_option( 'itsec_file_list' );

				if ( $file_list_option && ! empty( $file_list_option['files'] ) ) {
					$files = end( $file_list_option['files'] );
					$home  = $file_list_option['home'];

					if ( $home !== get_home_path() ) {
						$new_home = get_home_path();

						foreach ( $files as $file => $attr ) {
							$files[ ITSEC_Lib::replace_prefix( $file, $home, $new_home ) ] = $attr;
						}
					}

					ITSEC_File_Change_Scanner::record_file_list( $this->migrate_file_attr( $files ) );
				}

				delete_site_option( 'itsec_file_list' );

				if ( $latest_changes = ITSEC_Modules::get_setting( 'file-change', 'latest_changes' ) ) {

					if ( ! empty( $latest_changes['added'] ) && is_array( $latest_changes['added'] ) ) {
						$latest_changes['added'] = $this->migrate_file_attr( $latest_changes['added'] );
					} else {
						$latest_changes['added'] = array();
					}

					if ( ! empty( $latest_changes['changed'] ) && is_array( $latest_changes['changed'] ) ) {
						$latest_changes['changed'] = $this->migrate_file_attr( $latest_changes['changed'] );
					} else {
						$latest_changes['changed'] = array();
					}

					if ( ! empty( $latest_changes['removed'] ) && is_array( $latest_changes['removed'] ) ) {
						$latest_changes['removed'] = $this->migrate_file_attr( $latest_changes['removed'] );
					} else {
						$latest_changes['removed'] = array();
					}

					update_site_option( 'itsec_file_change_latest', $latest_changes );
				}

				ITSEC_File_Change_Scanner::schedule_start( false );
			} elseif ( $itsec_old_version < 4091 ) {
				$settings = ITSEC_Modules::get_settings( 'file-change' );

				if ( array_key_exists( 'latest_changes', $settings ) ) {

					if ( $latest_changes = $settings['latest_changes'] ) {
						update_site_option( 'itsec_file_change_latest', $latest_changes );
					}

					unset( $settings['latest_changes'] );
					ITSEC_Modules::set_settings( 'file-change', $settings );
				}
			}

			if ( $itsec_old_version < 4093 ) {
				require_once( dirname( __FILE__ ) . '/scanner.php' );

				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change', null );
				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change-fast', null );
				ITSEC_File_Change_Scanner::schedule_start( false );
				delete_site_option( 'itsec_file_change_scan_progress' );
			}

			if ( $itsec_old_version < 4107 ) {
				$options = array(
					'itsec_file_list',
					'itsec_local_file_list',
					'itsec_local_file_list_0',
					'itsec_local_file_list_1',
					'itsec_local_file_list_2',
					'itsec_local_file_list_3',
					'itsec_local_file_list_4',
					'itsec_local_file_list_5',
					'itsec_local_file_list_6',
				);

				foreach ( $options as $option ) {
					delete_site_option( $option );
				}

				require_once( dirname( __FILE__ ) . '/class-itsec-file-change.php' );
				require_once( dirname( __FILE__ ) . '/scanner.php' );

				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change', null );
				ITSEC_Core::get_scheduler()->unschedule_single( 'file-change-fast', null );
				ITSEC_File_Change::make_progress_storage()->clear();
				ITSEC_File_Change_Scanner::schedule_start( false );
			}
		}

		/**
		 * Migrate file attributes to the shorter format.
		 *
		 * @param array $files
		 *
		 * @return array
		 */
		private function migrate_file_attr( $files ) {

			$changed = array();

			foreach ( $files as $file => $attr ) {
				$migrated = array(
					'h' => $attr['h'],
					'd' => $attr['d'],
				);

				if ( isset( $attr['s'] ) ) {
					$migrated['s'] = $attr['s'];
				} elseif ( isset( $attr['severity'] ) ) {
					$migrated['s'] = $attr['severity'];
				}

				if ( isset( $attr['t'] ) ) {
					$migrated['t'] = $attr['t'];
				} elseif ( isset( $attr['type'] ) ) {
					switch ( $attr['type'] ) {
						case 'added':
							$migrated['t'] = ITSEC_File_Change_Scanner::T_ADDED;
							break;
						case 'changed':
							$migrated['t'] = ITSEC_File_Change_Scanner::T_CHANGED;
							break;
						case 'removed':
							$migrated['t'] = ITSEC_File_Change_Scanner::T_REMOVED;
							break;
						default:
							$migrated['t'] = $attr['type'];
							break;
					}
				}

				if ( isset( $attr['p'] ) ) {
					$migrated['p'] = $attr['p'];
				} elseif ( isset( $attr['package'] ) ) {
					$migrated['p'] = $attr['package'];
				}

				$changed[ $file ] = $migrated;
			}

			return $changed;
		}
	}
}

new ITSEC_File_Change_Setup();
