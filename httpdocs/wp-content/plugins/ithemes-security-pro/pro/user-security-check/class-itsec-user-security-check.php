<?php

class ITSEC_User_Security_Check {
	public function run() {
		add_filter( 'manage_toplevel_page_itsec_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_users_custom_column', array( $this, 'column_content' ), null, 3);
		add_action( 'wp_ajax_itsec-user-security-check-user-search', array( $this, 'user_search' ) );
		add_action( 'wp_ajax_itsec-set-user-role', array( $this, 'set_role' ) );
		add_action( 'wp_ajax_itsec-destroy-sessions', array( $this, 'destroy_sessions' ) );
		add_action( 'wp_ajax_itsec-send-2fa-email-reminder', array( $this, 'send_2fa_email_reminder' ) );
		add_filter( 'itsec_send_notification_inactive-users', array( $this, 'check_inactive_accounts' ), 10, 2 );
		add_filter( 'itsec_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'itsec_two-factor-reminder_notification_strings', array( $this, 'two_factor_reminder_strings' ) );
		add_filter( 'itsec_inactive-users_notification_strings', array( $this, 'inactive_users_strings' ) );
	}

	/**
	 * Register columns for the user security check table.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	function add_columns( $columns ) {
		require_once( ITSEC_Core::get_plugin_dir() . 'pro/two-factor/class-itsec-two-factor.php' );
		require_once( ITSEC_Core::get_plugin_dir() . 'pro/two-factor/class-itsec-two-factor-helper.php' );

		$columns = array(
			'username' => __( 'Username' ), // Uses core translation
		);

		if ( class_exists( 'ITSEC_Two_Factor' ) && class_exists( 'ITSEC_Two_Factor_Helper' ) ) {
			$two_factor_helper = ITSEC_Two_Factor_Helper::get_instance();
			if ( $two_factor_helper->get_enabled_providers() ) {
				$columns['itsec-two-factor'] = __( 'Two-Factor', 'it-l10n-ithemes-security-pro' );
			}
		}
		$columns['itsec-password'] = __( 'Password', 'it-l10n-ithemes-security-pro' );
		if ( class_exists( 'ITSEC_Lib_User_Activity' ) ) {
			$columns['itsec-last-active'] = __( 'Last Active', 'it-l10n-ithemes-security-pro' );
		}
		$columns['itsec-user-sessions'] = __( 'Sessions', 'it-l10n-ithemes-security-pro' );
		if ( current_user_can( 'promote_users' ) ) {
			$columns['itsec-user-role'] = __( 'Role', 'it-l10n-ithemes-security-pro' );
		}
		return $columns;
	}

	/**
	 * Render each column's content.
	 *
	 * @param string $value
	 * @param string $column_name
	 * @param int    $user_id
	 *
	 * @return string
	 */
	function column_content( $value, $column_name, $user_id ) {

		$user = get_userdata( $user_id );

		switch ( $column_name ) {
			case 'itsec-last-active':
				return $this->get_last_active_cell_contents( $user_id );
			case 'itsec-two-factor':
				$itsec_two_factor = ITSEC_Two_Factor::get_instance();
				if ( $itsec_two_factor->get_available_providers_for_user( $user, false ) ) {
					return '<span class="dashicons dashicons-lock" title="' . esc_attr__( 'Two Factor Enabled', 'it-l10n-ithemes-security-pro' ) . '"></span>';
				} elseif ( $itsec_two_factor->get_available_providers_for_user( $user, true ) ) {
					return '<span class="dashicons dashicons-lock not-configured" title="' . esc_attr__( 'Two Factor Enforced, Not Configured', 'it-l10n-ithemes-security-pro' ) . '"></span>';
				} else {
					$return = '<span class="dashicons dashicons-unlock" title="' . esc_attr__( 'Two Factor Not Enabled', 'it-l10n-ithemes-security-pro' ) . '"></span>';
					if ( current_user_can( 'edit_users', $user_id ) ) {
						$return .= sprintf( '<div class="row-actions"><span class="send-email"><a href="" data-nonce="%1$s" data-user_id="%2$d">%3$s</a></span></div>', esc_attr( wp_create_nonce( 'itsec-send-2fa-reminder-email-' . $user_id ) ), absint( $user_id ), __( 'Send E-Mail Reminder', 'it-l10n-ithemes-security-pro' ) );
					}
					return $return;
				}
			case 'itsec-password':
				return $this->get_password_cell_contents( $user_id );
			case 'itsec-user-sessions':
				return $this->get_user_session_cell_contents( $user_id );
			case 'itsec-user-role':
				if ( ! current_user_can( 'promote_users' ) ) {
					return '';
				}
				$user = get_userdata( $user_id );

				if ( empty( $user->roles ) ) {
					$role = '';
				} else {
					$role = current( $user->roles );
				}

				ob_start();
				?>
				<label class="screen-reader-text" for="<?php echo esc_attr( 'change_role-' . $user_id ); ?>"><?php _e( 'Change role to&hellip;' ) ?></label>
				<select name="<?php echo esc_attr( 'change_role-' . $user_id ); ?>" id="<?php echo esc_attr( 'change_role-' . $user_id ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?> " data-nonce="<?php echo esc_attr( wp_create_nonce( 'itsec-user-security-check-set-role-' . $user_id ) ); ?>">
					<option value="" disabled><?php _e( 'Change role to&hellip;' ) ?></option>
					<?php wp_dropdown_roles( $role ); ?>
				</select>
				<?php
				return ob_get_clean();
		}
		return $value;
	}

	/**
	 * Display the number of locations the user is logged-in at and a button to log out those locations.
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	private function get_user_session_cell_contents( $user_id ) {
		$wp_sessions = WP_Session_Tokens::get_instance( $user_id );
		$sessions = $wp_sessions->get_all();
		if ( empty( $sessions ) ) {
			return __( 'Not currently logged in anywhere.', 'it-l10n-ithemes-security-pro' );
		} elseif( $user_id === get_current_user_id() && 1 === count( $sessions ) ) {
			return __( 'You are only logged in at this location.' );
		} else {
			$label = ( $user_id === get_current_user_id() )? __( 'Log Out Everywhere Else' ) : __( 'Log Out Everywhere' );// Uses code translation
			$return = sprintf( _n( 'Currently logged in at one location.', 'Currently logged in at %d locations.', count( $sessions ), 'it-l10n-ithemes-security-pro' ), count( $sessions ) );
			$return .= '<p><button type="button" class="destroy-sessions button button-secondary" data-nonce="' . esc_attr( wp_create_nonce( 'update-user_' . $user_id ) ) . '" data-user_id="' . esc_attr( $user_id ) . '">' . $label . '</button></p>';
			return $return;
		}
	}

	/**
	 * Display the time that the user has last been logged-in.
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function get_last_active_cell_contents( $user_id ) {
		$ITSEC_Lib_User_Activity = ITSEC_Lib_User_Activity::get_instance();

		$time = intval( $ITSEC_Lib_User_Activity->get_last_seen( $user_id ) );

		$time_diff = time() - $time;
		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			return sprintf( __( '%s ago' ), human_time_diff( $time ) ); // Uses core translation
		} elseif ( empty( $time ) ) {
			return __( 'Unknown', 'it-l10n-ithemes-security-pro' );
		} else {
			return mysql2date( __( 'Y/m/d' ), date( 'Y-m-d H:i:s', $time ) ); // Uses core translation
		}

	}

	/**
	 * Display a notice about the strength of the user's password.
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function get_password_cell_contents( $user_id ) {
		$password_strength = get_user_meta( $user_id, 'itsec-password-strength', true );

		// If the password strength wasn't retrieved or isn't 0-4, set it to -1 for "Unknown"
		if ( false === $password_strength || '' === $password_strength || ! in_array( $password_strength, range( 0, 4 ) ) ) {
			$password_strength = -1;
		}
		switch ( $password_strength ) {
			case 0:
			case 1:
				$strength_class = 'short';
				$strength_text = _x( 'Very weak', 'password strength' );
				break;
			case 2:
				$strength_class = 'bad';
				$strength_text = _x( 'Weak', 'password strength' );
				break;
			case 3:
				$strength_class = 'good';
				$strength_text = _x( 'Medium', 'password strength' );
				break;
			case 4:
				$strength_class = 'strong';
				$strength_text = _x( 'Strong', 'password strength' );
				break;
			default:
				$strength_class = '';
				$strength_text = _x( 'Unknown', 'password strength', 'it-l10n-ithemes-security-pro' );
		}

		$password_updated_time = ITSEC_Lib_Password_Requirements::password_last_changed( $user_id );

		if ( 0 === $password_updated_time ) {
			$age = __( 'Unknown', 'it-l10n-ithemes-security-pro' );
		} else {
			$age = human_time_diff( $password_updated_time );
		}

		return sprintf(
			__( '<strong>Strength:</strong> <span class="itsec-password-strength %1$s">%2$s</span></br><strong>Age:</strong> <span class="itsec-password-age">%3$s</span>' ),
			$strength_class,
			$strength_text,
			$age
		);

	}

	/**
	 * Ajax callback to display a table that has been filtered by the "search" input.
	 */
	public function user_search() {
		if ( wp_verify_nonce( $_POST['_nonce'], 'itsec-user-security-check-user-search' ) ) {
			$return = new stdClass();
			require_once( 'class-itsec-wp-users-list-table.php' );
			$wp_list_table = new ITSEC_WP_Users_List_Table( array( 'screen' => 'toplevel_page_itsec' ) );

			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->views();
			$return->views = ob_get_clean();
			ob_start();
			$wp_list_table->search_box( __( 'Search Users' ), 'user' );
			$return->search_box = ob_get_clean();
			$return->search_nonce = wp_create_nonce( 'itsec-user-security-check-user-search' );
			ob_start();
			$wp_list_table->display();
			$return->users_table = ob_get_clean();
			wp_send_json_success( $return );
		}
		wp_send_json_error( array( 'message' => __( 'There was a problem searching.', 'it-l10n-ithemes-security-pro' ) ) );
	}

	/**
	 * Ajax callback to update a user's role.
	 */
	public function set_role() {
		$user_id = absint( $_POST['user_id'] );
		if ( wp_verify_nonce( $_POST['_nonce'], 'itsec-user-security-check-set-role-' . $user_id ) && ! empty( $_REQUEST['new_role'] ) ) {
			$user = get_userdata( $user_id );
			$user->set_role( $_REQUEST['new_role'] );

			$return = new stdClass();
			require_once( 'class-itsec-wp-users-list-table.php' );
			$wp_list_table = new ITSEC_WP_Users_List_Table( array( 'screen' => 'toplevel_page_itsec' ) );

			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->views();
			$return->views = ob_get_clean();
			$return->message = __( 'Successfully updated role.', 'it-l10n-ithemes-security-pro' );

			wp_send_json_success( $return );
		} else {
			wp_send_json_error( array( 'message' => __( 'There was a problem updaing the user role.', 'it-l10n-ithemes-security-pro' ) ) );
		}
	}

	/**
	 * Ajax handler for destroying multiple open sessions for a user.
	 *
	 * Based on wp_ajax_destroy_sessions()
	 */
	public function destroy_sessions() {
		$user = get_userdata( (int) $_POST['user_id'] );
		if ( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) ) {
				$user = false;
			} elseif ( ! wp_verify_nonce( $_POST['nonce'], 'update-user_' . $user->ID ) ) {
				$user = false;
			}
		}

		if ( ! $user ) {
			wp_send_json_error( array(
				'message' => __( 'Could not log out user sessions. Please try again.' ),
			) );
		}

		$sessions = WP_Session_Tokens::get_instance( $user->ID );

		if ( $user->ID === get_current_user_id() ) {
			$sessions->destroy_others( wp_get_session_token() );
			$message = __( 'You are now logged out everywhere else.' );
		} else {
			$sessions->destroy_all();
			/* translators: 1: User's display name. */
			$message = sprintf( __( '%s has been logged out.' ), $user->display_name );
		}

		wp_send_json_success( array( 'message' => $message, 'session_cell_contents' => $this->get_user_session_cell_contents( $user->ID ) ) );
	}

	/**
	 * Ajax handler for sending a 2fa reminder E-Mail to a user.
	 */
	public function send_2fa_email_reminder() {
		$_POST['user_id'] = absint( $_POST['user_id'] );
		if ( ! current_user_can( 'edit_user', $_POST['user_id'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to send an E-Mail to that user.', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		if ( ! wp_verify_nonce( $_POST['nonce'], 'itsec-send-2fa-reminder-email-' . $_POST['user_id'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'There was a problem verifying your request. Please reload the page and try again.' ),
			) );
		}

		$requester = wp_get_current_user();
		$recipient = get_userdata( $_POST['user_id'] );

		$nc = ITSEC_Core::get_notification_center();
		$mail = $nc->mail();
		$mail->set_recipients( array( $recipient->user_email ) );

		$mail->add_header(
			esc_html__( 'Two Factor Reminder', 'it-l10n-ithemes-security-pro' ),
			sprintf( esc_html__( 'Two Factor Authentication Reminder for %s', 'it-l10n-ithemes-security-pro' ), '<b>' . get_bloginfo( 'name', 'display' ) . '</b>' ),
			true
		);

		$message = ITSEC_Core::get_notification_center()->get_message( 'two-factor-reminder' );
		$message = ITSEC_Lib::replace_tags( $message, array(
			'username'               => $recipient->user_login,
			'display_name'           => $recipient->display_name,
			'requester_username'     => $requester->user_login,
			'requester_display_name' => $requester->display_name,
			'site_title'             => get_bloginfo( 'name', 'display' ),
		) );
		$mail->add_text( $message );

		$configure_2fa_url = ITSEC_Mail::filter_admin_page_url( add_query_arg( ITSEC_Lib_Login_Interstitial::SHOW_AFTER_LOGIN, '2fa-on-board', wp_login_url() ) );

		$mail->add_button( esc_html__( 'Setup now', 'it-l10n-ithemes-security-pro' ), $configure_2fa_url );

		$mail->add_list( array(
			esc_html__( 'Enabling two-factor authentication greatly increases the security of your user account on this site.', 'it-l10n-ithemes-security-pro' ),
			esc_html__( 'With two-factor authentication enabled, after you login with your username and password, you will be asked for an authentication code before you can successfully log in.', 'it-l10n-ithemes-security-pro' ),
			sprintf(
				/* translators: %1$s and %2$s are opening link tags, %3$s is the closing link tag. */
				esc_html__( '%1$sLearn more about Two Factor Authentication%3$s, or %2$show to set it up%3$s.', 'it-l10n-ithemes-security-pro' ),
				'<a href="' . esc_url( 'https://ithemes.com/2015/07/28/two-factor-authentication/' ) . '">',
				'<a href="' . esc_url( 'https://ithemes.com/2016/07/26/two-factor-authentication-ithemes-security-pro-plugin/' ) . '">',
				'</a>'
			)
		), true );

		$mail->add_user_footer();

		if ( $nc->send( 'two-factor-reminder', $mail ) ) {
			wp_send_json_success( array(
				'message' => __( 'Reminder E-Mail has been sent.', 'it-l10n-ithemes-security-pro' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'There was a problem sending the E-Mail reminder. Please try again.', 'it-l10n-ithemes-security-pro' ),
			) );
		}
	}

	/**
	 * Iterate over all users who haven't been active in the last 30 days and email admins the results.
	 *
	 * @param bool $sent
	 * @param int  $last_sent
	 *
	 * @return bool
	 */
	public function check_inactive_accounts( $sent, $last_sent ) {
		if ( defined( 'ITSEC_DISABLE_INACTIVE_USER_CHECK' ) && ITSEC_DISABLE_INACTIVE_USER_CHECK ) {
			return false;
		}

		$max_days = apply_filters( 'itsec_inactive_user_days', 30 );
		$args = array(
			'meta_query' => array(
				'last-active' => array(
					'key'     => 'itsec_user_activity_last_seen',
					'value'   => time() - ( $max_days * DAY_IN_SECONDS ),
					'compare' => '<=',
				),
				'not-already-notified' => array(
					'key'     => 'itsec_user_activity_last_seen_notification_sent',
					'compare' => 'NOT EXISTS',
				),
			),
		);
		$users = get_users( $args );

		if ( empty( $users ) ) {
			return true;
		}

		$nc = ITSEC_Core::get_notification_center();
		$mail = $nc->mail();

		$mail->add_header( esc_html__( 'Inactive User Warning', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Inactive User Warning', 'it-l10n-ithemes-security-pro' ) );
		$mail->add_info_box( sprintf( _n( 'The following users have been inactive for more than %d day', 'The following users have been inactive for more than %d days', $max_days, 'it-l10n-ithemes-security-pro' ), $max_days ), 'warning' );
		$mail->add_text( esc_html__( 'Please take the time to review the users and demote or delete any where it makes sense.', 'it-l10n-ithemes-security-pro' ) );

		$table_rows = array();

		foreach ( $users as $user ) {
			update_user_meta( $user->ID, 'itsec_user_activity_last_seen_notification_sent', true );

			$roles = array_map( 'translate_user_role', $user->roles );
			$role  = wp_sprintf( '%l', $roles );

			$table_rows[] = array( $user->user_login, $role, $this->get_last_active_cell_contents( $user->ID ) );
		}

		$mail->add_table( array( esc_html__( 'Username', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Role', 'it-l10n-ithemes-security-pro' ), esc_html__( 'Last Active', 'it-l10n-ithemes-security-pro' ) ), $table_rows );
		$mail->add_button( esc_html__( 'Edit Users', 'it-l10n-ithemes-security-pro' ), ITSEC_Mail::filter_admin_page_url( admin_url( 'admin.php?page=itsec&module=user-security-check' ) ) );
		$mail->add_footer();

		return $nc->send( 'inactive-users', $mail );
	}

	/**
	 * Register Two Factor Reminder and Inactive Users notifications.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notifications( $notifications ) {

		$notifications['two-factor-reminder'] = array(
			'subject_editable' => true,
			'message_editable' => true,
			'schedule'         => ITSEC_Notification_Center::S_NONE,
			'recipient'        => ITSEC_Notification_Center::R_USER,
			'tags'			   => array( 'username', 'display_name', 'requester_username', 'requester_display_name', 'site_title' ),
			'module'		   => 'user-security-check',
		);

		$notifications['inactive-users'] = array(
			'subject_editable' => true,
			'schedule'         => ITSEC_Notification_Center::S_CONFIGURABLE,
			'recipient'        => ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'optional'		   => true,
			'module'		   => 'user-security-check',
		);

		return $notifications;
	}

	/**
	 * Get the translated strings for the Two Factor Reminder email.
	 *
	 * @return array
	 */
	public function two_factor_reminder_strings() {
		return array(
			'label'       => esc_html__( 'Two-Factor Reminder Notice', 'it-l10n-ithemes-security-pro' ),
			'description' => sprintf( esc_html__( 'The %1$sUser Security Check%2$s module allows you to remind users to setup two-factor authentication for their accounts.', 'it-l10n-ithemes-security-pro' ), '<a href="#" data-module-link="user-security-check">', '</a>' ),
			'subject'     => esc_html__( 'Please Set Up Two Factor Authentication', 'it-l10n-ithemes-security-pro' ),
			'tags'        => array(
				'username'               => esc_html__( "The recipient's WordPress username.", 'it-l10n-ithemes-security-pro' ),
				'display_name'           => esc_html__( "The recipient's WordPress display name.", 'it-l10n-ithemes-security-pro' ),
				'requester_username'     => esc_html__( "The requester's WordPress username.", 'it-l10n-ithemes-security-pro' ),
				'requester_display_name' => esc_html__( "The requester's WordPress display name.", 'it-l10n-ithemes-security-pro' ),
				'site_title'             => esc_html__( 'The WordPress Site Title. Can be changed under Settings -> General -> Site Title', 'it-l10n-ithemes-security-pro' )
			),
			'message'     => esc_html__( 'Hi {{ $display_name }},
			
{{ $requester_display_name }} from {{ $site_title }} has asked that you set up Two Factor Authentication.', 'it-l10n-ithemes-security-pro' ),
		);
	}

	/**
	 * Get the translated strings for the Inactive Users email.
	 *
	 * @return array
	 */
	public function inactive_users_strings() {
		return array(
			'label'       => esc_html__( 'Inactive Users', 'it-l10n-ithemes-security-pro' ),
			'description' => sprintf( esc_html__( 'The %1$sUser Security Check%2$s module sends a list of users who have not been active in the last 30 days so you can consider demoting or removing users.', 'it-l10n-ithemes-security-pro' ), '<a href="#" data-module-link="user-security-check">', '</a>' ),
			'subject'     => esc_html__( 'Inactive Users', 'it-l10n-ithemes-security-pro' ),
		);
	}
}
