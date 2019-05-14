<?php

class ITSEC_Dashboard_Widget_Admin {

	const AJAX_DISMISS_NAG = 'itsec-dismiss-dashboard-widget-nag';

	public function run() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/**
	 * Execute all hooks on admin init
	 *
	 * All hooks on admin init to make certain user has the correct permissions
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function admin_init() {
		if ( ! ITSEC_Core::current_user_can_manage() ) {
			return;
		}

		if ( isset( $_GET['itsec_toggle_dashboard_widget'] ) ) {
			$this->handle_toggle_version();
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
		add_action( 'wp_network_dashboard_setup', array( $this, 'register_widgets' ) );
		add_action( 'wp_ajax_itsec_release_dashboard_lockout', array( $this, 'itsec_release_dashboard_lockout' ) );
		add_action( 'wp_ajax_' . self::AJAX_DISMISS_NAG, array( $this, 'ajax_dismiss_nag' ) );
	}

	/**
	 * Create dashboard widget
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function register_widgets() {

		switch ( $this->get_widget_version() ) {
			case 2:
				if ( ! ITSEC_Modules::is_active( 'dashboard' ) ) {
					return;
				}

				ITSEC_Modules::load_module_file( 'class-itsec-dashboard-util.php', 'dashboard' );

				if ( ! $primary_id = ITSEC_Dashboard_Util::get_primary_dashboard_id() ) {
					return;
				}

				if ( $this->can_toggle_version() ) {
					$url = esc_url( $this->get_toggle_version_url() );

					/* translators: 1. Plugin Name 2. Opening Link 3. Closing Link */
					$name = sprintf( esc_html__( '%1$s %2$sBack to Legacy%3$s', 'it-l10n-ithemes-security-pro' ), ITSEC_Core::get_plugin_name(), "<span class=\"postbox-title-action\"><a href=\"{$url}\">", '</a></span>' );
				} else {
					$name = ITSEC_Core::get_plugin_name();
				}

				wp_add_dashboard_widget( 'itsec-dashboard-widget', $name, array( $this, 'render_dashboard_widget' ) );
				break;
			case 1:
				if ( $this->can_toggle_version() && ITSEC_Modules::is_active( 'dashboard' ) ) {
					$url = esc_url( $this->get_toggle_version_url() );

					/* translators: 1. Plugin Name 2. Opening Link 3. Closing Link */
					$name = sprintf( esc_html__( '%1$s (Legacy Widget) %2$sTry New%3$s', 'it-l10n-ithemes-security-pro' ), ITSEC_Core::get_plugin_name(), "<span class=\"postbox-title-action\"><a href=\"{$url}\">", '</a></span>' );
				} else {
					/* translators: Dashboard Widget Name. %s is plugin name. */
					$name =	sprintf( esc_html__( '%s (Legacy Widget)', 'it-l10n-ithemes-security-pro' ), ITSEC_Core::get_plugin_name() );
				}

				wp_add_dashboard_widget(
					'itsec-dashboard-widget',
					$name,
					array( $this, 'legacy_dashboard_widget_content' )
				);
				break;
		}
	}

	/**
	 * Add malware scheduling admin Javascript
	 *
	 * @since 1.9
	 *
	 * @param string $hook
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {

		if ( 'index.php' !== $hook ) {
			return;
		}

		if ( 2 === $this->get_widget_version() ) {
			if ( ! ITSEC_Modules::is_active( 'dashboard' ) ) {
				return;
			}

			// Just in case we somehow reach here without the dashboard widget being registered.
			ITSEC_Modules::load_module_file( 'class-itsec-dashboard-util.php', 'dashboard' );

			$primary_id = ITSEC_Dashboard_Util::get_primary_dashboard_id();

			wp_enqueue_style( 'itsec-dashboard-widget' );
			wp_enqueue_script( 'itsec-dashboard-widget' );
			wp_localize_script( 'itsec-dashboard-widget', 'iThemesSecurityDashboard', array(
				'rootURL'           => rest_url(),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'primary_dashboard' => $primary_id,
				'db_logs'           => ITSEC_Modules::get_setting( 'global', 'log_type' ) !== 'file',
				'logs_nonce'        => wp_create_nonce( 'itsec-logs-nonce' ),
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			) );

			return;
		}

		$deps = array( 'jquery' );

		if ( ITSEC_Modules::is_active( 'file-change' ) ) {
			if ( ! class_exists( 'ITSEC_File_Change_Admin' ) ) {
				ITSEC_Modules::load_module_file( 'admin.php', 'file-change' );
			}

			ITSEC_File_Change_Admin::enqueue_scanner();
			$deps[] = 'itsec-file-change-scanner';
		}

		wp_enqueue_style( 'itsec_dashboard_widget_css', plugins_url( 'css/admin-dashboard-widget.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );
		wp_enqueue_script( 'itsec_dashboard_widget_js', plugins_url( 'js/admin-dashboard-widget.js', __FILE__ ), $deps, ITSEC_Core::get_plugin_build() );

		wp_localize_script( 'itsec_dashboard_widget_js', 'itsec_dashboard_widget_js', array(
			'host'          => '<p>' . __( 'Currently no hosts are locked out of this website.', 'it-l10n-ithemes-security-pro' ) . '</p>',
			'user'          => '<p>' . __( 'Currently no users are locked out of this website.', 'it-l10n-ithemes-security-pro' ) . '</p>',
			'scanning'      => __( 'Scanning files...', 'it-l10n-ithemes-security-pro' ),
			'scan_nonce'    => wp_create_nonce( 'itsec_dashboard_scan_files' ),
			'postbox_nonce' => wp_create_nonce( 'itsec_dashboard_summary_postbox_toggle' ),
			'dismiss_nonce' => wp_create_nonce( self::AJAX_DISMISS_NAG ),
		) );
	}

	/**
	 * Render the dashboard widget root.
	 */
	public function render_dashboard_widget() {
		echo '<div id="itsec-widget-root"></div>';
	}

	/**
	 * Echo dashboard widget content
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	public function legacy_dashboard_widget_content() {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout, $wpdb;

		$white_class = '';

		if ( function_exists( 'wp_get_current_user' ) ) {

			$current_user = wp_get_current_user();

			$meta = get_user_meta( $current_user->ID, 'itsec_dashboard_widget_status', true );

			if ( is_array( $meta ) ) {

				if ( isset( $meta['itsec_lockout_summary_postbox'] ) && $meta['itsec_lockout_summary_postbox'] == 'close' ) {
					$white_class = ' closed';
				}
			}

		}

		if ( ITSEC_Modules::get_setting( 'dashboard-widget', 'nag_dismissed' ) + 3 * DAY_IN_SECONDS < ITSEC_Core::get_current_time_gmt() ) {
			echo '<div class="notice notice-info notice-alt below-h2 is-dismissible">';
			echo '<p>';

			if ( ITSEC_Modules::is_active( 'dashboard' ) ) {
				$url = esc_url( $this->get_toggle_version_url() );
				echo sprintf( esc_html__( 'We have a new dashboard widget powered by the Security Dashboard. %1$sTry it now%2$s!', 'it-l10n-ithemes-security-pro' ), "<a href=\"{$url}\">", '</a>' );
			} else {
				$url = esc_url( add_query_arg( 'module', 'dashboard', ITSEC_Core::get_settings_page_url() ) );
				echo sprintf( esc_html__( 'We have a new dashboard widget powered by the Security Dashboard. %1$sActivate the "Security Dashboard" module%2$s to give it a try.', 'it-l10n-ithemes-security-pro' ), "<a href=\"{$url}\">", '</a>' );
			}

			echo ' ' . esc_html__( 'The current dashboard widget is deprecated, and will be removed in a future release.', 'it-l10n-ithemes-security-pro' );

			echo '</p>';
			echo '</div>';
		}

		//Access Logs
		echo '<div class="itsec_links widget-section clear">';
		echo '<ul>';
		echo '<li><a href="' . esc_url( ITSEC_Core::get_settings_page_url() ) . '">' . __( '> Plugin Settings', 'it-l10n-ithemes-security-pro' ) . '</a></li>';
		echo '<li><a href="' . esc_url( ITSEC_Core::get_logs_page_url() ) . '">' . __( '> View Security Logs', 'it-l10n-ithemes-security-pro' ) . '</a></li>';
		echo '</ul>';
		echo '</div>';

		//Whitelist
		echo '<div class="itsec_summary_widget widget-section clear postbox' . $white_class . '" id="itsec_lockout_summary_postbox">';

		$lockouts = $itsec_lockout->get_lockouts( 'all', array( 'current' => false ) );
		$current  = $itsec_lockout->get_lockouts( 'host', array( 'return' => 'count' ) ) + $itsec_lockout->get_lockouts( 'user', array( 'return' => 'count' ) );

		$total_users = (int) $wpdb->get_var( "SELECT count(`id`) FROM {$wpdb->users}" );

		$users_with_weak_password = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT count(`user_id`) AS count FROM {$wpdb->usermeta} WHERE `meta_key` = %s AND `meta_value` < 3",
			ITSEC_Strong_Passwords::STRENGTH_KEY
		) );

		if ( class_exists( 'ITSEC_Two_Factor' ) ) {
			$users_with_2fa = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT count(`user_id`) AS count FROM {$wpdb->usermeta} WHERE `meta_key` = %s AND `meta_value` != ''",
				'_two_factor_enabled_providers'
			) );
			$users_without_2fa = $total_users - $users_with_2fa;
		} else {
			$users_without_2fa = $total_users;
		}

		echo '<div class="handlediv" title="Click to toggle"><br /></div>';
		echo '<h4 class="dashicons-before dashicons-shield-alt">' . __( 'Security Summary', 'it-l10n-ithemes-security-pro' ) . '</h4>';
		echo '<div class="inside">';
		echo '<div class="summary-item">';
		echo '<h5>' . __( 'Times protected from attack.', 'it-l10n-ithemes-security-pro' ) . '</h5>';
		echo '<span class="summary-total">' . sizeof( $lockouts ) . '</span>';
		echo '</div>';
		echo '<div class="summary-item">';
		echo '<h5>' . __( 'Current Number of lockouts.', 'it-l10n-ithemes-security-pro' ) . '</h5>';
		echo '<span class="summary-total" id="current-itsec-lockout-summary-total">' . $current . '</span>';
		echo '</div>';

		echo '<div class="summary-item">';
		echo '<h5>' . __( 'Users without Two-Factor Authentication', 'it-l10n-ithemes-security-pro' ) . '</h5>';
		echo '<span class="summary-total">' . absint( $users_without_2fa ) . '</span>';
		echo '</div>';

		echo '<div class="summary-item">';
		echo '<h5>' . __( 'Users without strong password', 'it-l10n-ithemes-security-pro' ) . '</h5>';
		echo '<span class="summary-total">' . absint( $users_with_weak_password ) . '</span>';
		echo '</div>';

		echo '<a href="' . esc_url( admin_url( 'admin.php?page=itsec&module=user-security-check' ) ) . '" class="button-secondary itsec-widget-user-security-check">User Security Check</a>';
		echo '</div>';
		echo '</div>';

		//Run file-change Scan
		echo '<div class="itsec_file-change_widget widget-section">';
		$this->file_scan();
		echo '</div>';

		//Show lockouts table
		echo '<div class="itsec_lockouts_widget widget-section clear">';
		$this->lockout_metabox();
		echo '</div>';

	}

	/**
	 * Show file scan button
	 *
	 * @since 1.9
	 *
	 * @return void
	 */
	private function file_scan() {
		if ( ! ITSEC_Modules::is_active( 'file-change' ) ) {
			return;
		}

		ITSEC_Modules::load_module_file( 'scanner.php', 'file-change' );

		if ( ITSEC_File_Change_Scanner::is_running() ) {
			$text     = esc_attr__( 'Scan in Progress', 'it-l10n-ithemes-security-pro' );
			$disabled = 'disabled';
			$class    = 'button-secondary';
		} else {
			$text     = esc_attr__( 'Scan Files Now', 'it-l10n-ithemes-security-pro' );
			$disabled = '';
			$class    = 'button-primary';
		}

		echo "<p><input type=\"button\" id=\"itsec_dashboard_one_time_file_check\" {$disabled} class=\"{$class}\" value=\"{$text}\" /></p>";
		echo '<div id="itsec_dashboard_one_time_file_check_results"></div>';
	}

	/**
	 * Active lockouts table and form for dashboard.
	 *
	 * @Since 1.9
	 *
	 * @return void
	 */
	private function lockout_metabox() {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$host_class = '';
		$user_class = '';

		if ( function_exists( 'wp_get_current_user' ) ) {

			$current_user = wp_get_current_user();

			$meta = get_user_meta( $current_user->ID, 'itsec_dashboard_widget_status', true );

			if ( is_array( $meta ) ) {

				if ( isset( $meta['itsec_lockout_host_postbox'] ) && $meta['itsec_lockout_host_postbox'] == 'close' ) {
					$host_class = ' closed';
				}

				if ( isset( $meta['itsec_lockout_user_postbox'] ) && $meta['itsec_lockout_user_postbox'] == 'close' ) {
					$user_class = ' closed';
				}
			}

		}

		//get locked out hosts and users from database
		$host_locks = $itsec_lockout->get_lockouts( 'host', array( 'limit' => 100 ) );
		$user_locks = $itsec_lockout->get_lockouts( 'user', array( 'limit' => 100 ) );
		?>
		<div class="postbox<?php echo $host_class; ?>" id="itsec_lockout_host_postbox">
			<div class="handlediv" title="Click to toggle"><br/></div>
			<h4 class="dashicons-before dashicons-lock"><?php _e( 'Locked out hosts', 'it-l10n-ithemes-security-pro' ); ?></h4>

			<div class="inside">
				<?php if ( sizeof( $host_locks ) > 0 ) { ?>

					<ul>
						<?php foreach ( $host_locks as $host ) { ?>

							<li>
								<label for="lo_<?php echo $host['lockout_id']; ?>">
									<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( ITSEC_Lib::get_trace_ip_link( $host['lockout_host'] ) ); ?>"><?php esc_html_e( $host['lockout_host'] ); ?></a>
									<a href="<?php echo wp_create_nonce( 'itsec_reloease_dashboard_lockout' . $host['lockout_id'] ); ?>" id="<?php echo $host['lockout_id']; ?>" class="itsec_release_lockout locked_host">
										<span class="itsec-locked-out-remove">&mdash;</span>
									</a>
								</label>
							</li>

						<?php } ?>
					</ul>

				<?php } else { //no host is locked out ?>

					<p><?php _e( 'Currently no hosts are locked out of this website.', 'it-l10n-ithemes-security-pro' ); ?></p>

				<?php } ?>
			</div>
		</div>
		<div class="postbox<?php echo $user_class; ?>" id="itsec_lockout_user_postbox">
			<div class="handlediv" title="Click to toggle"><br/></div>
			<h4 class="dashicons-before dashicons-admin-users"><?php _e( 'Locked out users', 'it-l10n-ithemes-security-pro' ); ?></h4>

			<div class="inside">
				<?php if ( sizeof( $user_locks ) > 0 ) { ?>
					<ul>
						<?php foreach ( $user_locks as $user ) { ?>

							<?php $userdata = get_userdata( $user['lockout_user'] ); ?>

							<li>
								<label for="lo_<?php echo $user['lockout_id']; ?>">

									<a href="<?php echo wp_create_nonce( 'itsec_reloease_dashboard_lockout' . $user['lockout_id'] ); ?>"
									   id="<?php echo $user['lockout_id']; ?>"
									   class="itsec_release_lockout locked_user"><span
											class="itsec-locked-out-remove">&mdash;</span><?php echo isset( $userdata->user_login ) ? $userdata->user_login : ''; ?>
									</a>
								</label>
							</li>

						<?php } ?>
					</ul>
				<?php } else { //no user is locked out ?>

					<p><?php _e( 'Currently no users are locked out of this website.', 'it-l10n-ithemes-security-pro' ); ?></p>

				<?php } ?>
			</div>
		</div>
	<?php
	}

	/**
	 * Process the ajax call for releasing lockouts from the dashboard
	 *
	 * @since 1.9
	 *
	 * @return string json string for success or failure
	 */
	public function itsec_release_dashboard_lockout() {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'itsec_reloease_dashboard_lockout' . sanitize_text_field( $_POST['resource'] ) ) ) {
			die ( __( 'Security error', 'it-l10n-ithemes-security-pro' ) );
		}

		die( $itsec_lockout->release_lockout( absint( $_POST['resource'] ) ) );

	}

	public function ajax_dismiss_nag() {
		if ( ! ITSEC_Core::current_user_can_manage() ) {
			wp_send_json_error( array(
				'message' => esc_html__( "You don't have permission to do that.", 'it-l10n-ithemes-security-pro' ),
			) );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], self::AJAX_DISMISS_NAG ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Request expired. Please refresh and try again.', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		ITSEC_Modules::set_setting( 'dashboard-widget', 'nag_dismissed', ITSEC_Core::get_current_time_gmt() );

		wp_send_json_success( array(
			'message' => esc_html__( 'Notice dismissed.', 'it-l10n-ithemes-security-pro' ),
		) );
	}

	private function get_toggle_version_url() {
		return wp_nonce_url( add_query_arg( 'itsec_toggle_dashboard_widget', true, admin_url( 'index.php' ) ), 'itsec_toggle_dashboard_widget' );
	}

	private function handle_toggle_version() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'itsec_toggle_dashboard_widget' ) ) {
			wp_die( esc_html__( 'Request expired. Please go back and try again.', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->toggle_version();

		wp_redirect( admin_url() );
		die;
	}

	private function get_widget_version() {
		if ( ! $this->can_toggle_version() ) {
			return 2;
		}

		return ITSEC_Modules::get_setting( 'dashboard-widget', 'version' );
	}

	private function can_toggle_version() {
		return ITSEC_Modules::get_setting( 'global', 'initial_build' ) < 4113;
	}

	private function toggle_version() {
		if ( 2 === ITSEC_Modules::get_setting( 'dashboard-widget', 'version' ) ) {
			$new = 1;
		} else {
			$new = 2;
		}

		ITSEC_Modules::set_setting( 'dashboard-widget', 'version', $new );
	}
}
