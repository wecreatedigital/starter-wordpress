<?php
/**
 * Notification Center Validator.
 */

class ITSEC_Notification_Center_Validator extends ITSEC_Validator {

	private $current_tags = array();
	private $tag_errors = array();

	public function get_id() {
		return 'notification-center';
	}

	protected function sanitize_settings() {
		$this->vars_to_skip_validate_matching_fields = array( 'last_sent', 'data', 'resend_at', 'admin_emails', 'last_mail_error' );
		$this->set_previous_if_empty( array( 'last_sent', 'data', 'resend_at', 'admin_emails' ) );

		if ( ! isset( $this->settings['last_mail_error'] ) ) {
			$this->settings['last_mail_error'] = $this->previous_settings['last_mail_error'];
		}

		// We allow an empty email string.
		if ( ! empty( $this->settings['from_email'] ) ) {
			$this->sanitize_setting( 'email', 'from_email', __( 'Admin Email', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( $this->sanitize_setting( 'array', 'default_recipients', esc_html__( 'Default Recipients', 'it-l10n-ithemes-security-pro' ) ) ) {
			if ( empty( $this->settings['default_recipients']['user_list'] ) ) {
				$this->add_error( new WP_Error(
					'itsec-validator-notification-center-invalid-type-default_recipients[user_list]-non-empty',
					esc_html__( 'Selecting "Default Recipients" is required.', 'it-l10n-ithemes-security-pro' )
				) );

				if ( ITSEC_Core::is_interactive() ) {
					$this->set_can_save( false );
				}
			} else {
				$users_and_roles = $this->get_available_admin_users_and_roles();
				$valid_contacts  = $users_and_roles['users'] + $users_and_roles['roles'];

				$contact_errors = array();

				foreach ( $this->settings['default_recipients']['user_list'] as $contact ) {
					if ( ! isset( $valid_contacts[ $contact ] ) ) {
						$contact_errors[] = $contact;
					}
				}

				if ( $contact_errors ) {
					$this->add_error( new WP_Error(
						'itsec-validator-notification-center-invalid-type-default_recipients[user_list]-invalid-contacts',
						wp_sprintf( esc_html__( 'Unknown Default Recipients contacts, %l.', 'it-l10n-ithemes-security-pro' ), $contact_errors )
					) );

					if ( ITSEC_Core::is_interactive() ) {
						$this->set_can_save( false );
					}
				}
			}
		}

		if ( ! $this->sanitize_setting( 'array', 'notifications', esc_html__( 'Notifications', 'it-l10n-ithemes-security-pro' ) ) ) {
			return;
		}

		$notifications = $this->settings['notifications'];

		foreach ( $notifications as $notification => $settings ) {
			$config = ITSEC_Core::get_notification_center()->get_notification( $notification );

			if ( ! $config ) {
				continue;
			}

			$strings = ITSEC_Core::get_notification_center()->get_notification_strings( $notification );

			if ( ITSEC_Notification_Center::R_USER_LIST !== $config['recipient'] && ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE !== $config['recipient'] ) {
				unset( $settings['user_list'], $settings['recipient_type'] );
			} else {
				if ( ! is_array( $settings['user_list'] ) ) {
					$settings['user_list'] = array();
				}

				$users_and_roles = $this->get_available_admin_users_and_roles();
				$valid_contacts  = $users_and_roles['users'] + $users_and_roles['roles'];

				$contact_errors = array();

				foreach ( $settings['user_list'] as $contact ) {
					if ( ! isset( $valid_contacts[ $contact ] ) ) {
						$contact_errors[] = $contact;
					}
				}

				if ( ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE === $config['recipient'] && isset( $settings['previous_emails'] ) ) {
					foreach ( $settings['previous_emails'] as $previous_email ) {
						if ( ! in_array( $previous_email, $this->settings['admin_emails'], true ) ) {
							$contact_errors[] = $previous_email;
						}
					}
				}

				if ( $contact_errors ) {
					$this->add_error( new WP_Error(
						'itsec-validator-notification-center-invalid-type-notifications[user_list]-invalid-contacts',
						wp_sprintf( esc_html__( 'Unknown contacts for %1$s, %2$l.', 'it-l10n-ithemes-security-pro' ), $strings['label'], $contact_errors )
					) );

					if ( ITSEC_Core::is_interactive() ) {
						$this->set_can_save( false );
					}
				}

				if ( ! isset( $settings['recipient_type'] ) ) {
					$settings['recipient_type'] = 'default';
				} elseif ( ! in_array( $settings['recipient_type'], array( 'default', 'custom' ), true ) ) {
					$this->add_error( new WP_Error(
						'itsec-validator-notification-center-invalid-type-notifications[recipient_type]-array',
						wp_sprintf( esc_html__( 'Unknown recipient type for %s.', 'it-l10n-ithemes-security-pro' ), $strings['label'] )
					) );

					if ( ITSEC_Core::is_interactive() ) {
						$this->set_can_save( false );
					}
				}
			}

			if ( ITSEC_Notification_Center::R_EMAIL_LIST !== $config['recipient'] ) {
				unset( $settings['email_list'] );
			} else {

				if ( ! isset( $settings['email_list'] ) ) {
					$settings['email_list'] = '';
				}

				$settings['email_list'] = $this->convert_string_to_array( $settings['email_list'] );

				$email_list_error = null; // Safety

				if ( ! is_array( $settings['email_list'] ) ) {
					$email_list_error = sprintf( __( 'The %1$s email list must be a string with each entry separated by a new line.', 'it-l10n-ithemes-security-pro' ), $strings['label'] );
				} else {
					$invalid_emails = array();

					foreach ( $settings['email_list'] as $index => $email ) {
						$email                            = sanitize_text_field( trim( $email ) );
						$settings['email_list'][ $index ] = $email;

						if ( empty( $email ) ) {
							unset( $settings['email_list'][ $index ] );
						} elseif ( ! is_email( $email ) ) {
							$invalid_emails[] = $email;
						}
					}

					$settings['email_list'] = array_unique( $settings['email_list'] );

					if ( ! empty( $invalid_emails ) ) {
						$email_list_error = wp_sprintf( _n( 'The following email in %1$s is invalid: %2$l', 'The following emails in %1$s are invalid: %2$l', count( $invalid_emails ), 'it-l10n-ithemes-security-pro' ), $strings['label'], $invalid_emails );
					}
				}

				if ( $email_list_error ) {
					$this->add_error( new WP_Error(
						'itsec-validator-notification-center-invalid-type-notifications[email_list]-invalid-emails',
						$email_list_error
					) );

					if ( ITSEC_Core::is_interactive() ) {
						$this->set_can_save( false );
					}
				}
			}

			if ( empty( $config['optional'] ) ) {
				unset( $settings['enabled'] );
			} else {
				if ( 'false' === $settings['enabled'] ) {
					$settings['enabled'] = false;
				} elseif ( 'true' === $settings['enabled'] ) {
					$settings['enabled'] = true;
				} else {
					$settings['enabled'] = (bool) $settings['enabled'];
				}
			}

			if ( ! is_array( $config['schedule'] ) ) {
				unset( $settings['schedule'] );
			} else {
				$options = $this->get_schedule_options( $config['schedule'] );

				if ( ! isset( $options[ $settings['schedule'] ] ) ) {
					$this->add_error( new WP_Error(
						'itsec-validator-notification-center-invalid-type-notifications[schedule]-unknown-schedule',
						sprintf( esc_html__( 'Unknown schedule for %1$s, %2$s.', 'it-l10n-ithemes-security-pro' ), $strings['label'], $settings['schedule'] )
					) );

					if ( ITSEC_Core::is_interactive() ) {
						$this->set_can_save( false );
					}
				}
			}

			if ( empty( $config['subject_editable'] ) ) {
				unset( $settings['subject'] );
			} elseif ( ! empty( $settings['subject'] ) ) {
				$subject = trim( wp_strip_all_tags( $settings['subject'], true ) );

				if ( $subject === $strings['subject'] ) {
					$subject = null;
				}

				$settings['subject'] = $subject;
			}

			if ( empty( $config['message_editable'] ) ) {
				unset( $settings['message'] );
			} else {
				$message = isset( $settings['message'] ) ? trim( wp_kses( $settings['message'], $this->get_allowed_html() ) ) : '';

				if ( ! empty( $message ) ) {
					$this->check_unknown_tags( $message, $config['tags'], $strings['label'] );
				}

				if ( $message === $strings['message'] ) {
					$message = null;
				}

				$settings['message'] = $message;
			}

			$notifications[ $notification ] = $settings;
		}

		$this->settings['notifications'] = $notifications;
	}

	/**
	 * Check whether any unknown tags are being used in the content.
	 *
	 * @param string   $content
	 * @param string[] $tags
	 * @param string   $label
	 */
	private function check_unknown_tags( $content, $tags, $label ) {
		$this->current_tags = $tags;

		preg_replace_callback( '/{{ \$(\w+) }}/', array( $this, 'is_known_tag' ), $content );

		if ( $this->tag_errors ) {
			$this->add_error( new WP_Error(
				'itsec-validator-notification-center-invalid-type-notifications[message]-unknown-tags',
				/* translators: %1$s notification label, %2$l list of unknown tags. */
				wp_sprintf( esc_html__( 'Unknown tags for %1$s, %2$l.', 'it-l10n-ithemes-security-pro' ), $label, $this->tag_errors )
			) );

			if ( ITSEC_Core::is_interactive() ) {
				$this->set_can_save( false );
			}
		}

		$this->tag_errors   = array();
		$this->current_tags = array();
	}

	/**
	 * Check if a tag is known.
	 *
	 * @param array $matches
	 */
	public function is_known_tag( $matches ) {
		if ( empty( $matches[1] ) ) {
			return; // Sanity check
		}

		if ( ! in_array( $matches[1], $this->current_tags, true ) ) {
			$this->tag_errors[] = $matches[1];
		}
	}

	/**
	 * Get the list of available users and roles that can be selected as a notification email contact.
	 *
	 * @return array
	 */
	public function get_available_admin_users_and_roles() {
		if ( is_callable( 'wp_roles' ) ) {
			$roles = wp_roles();
		} else {
			$roles = new WP_Roles();
		}

		$available_roles = array();
		$available_users = array();

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		foreach ( $roles->roles as $role => $details ) {
			if ( 'administrator' === ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( $role ) ) {
				$available_roles["role:$role"] = translate_user_role( $details['name'] );

				$users = get_users( array( 'role' => $role ) );

				foreach ( $users as $user ) {
					/* translators: 1: user display name, 2: user login */
					$available_users[ $user->ID ] = sprintf( __( '%1$s (%2$s)', 'it-l10n-ithemes-security-pro' ), $user->display_name, $user->user_login );
				}
			}
		}

		natcasesort( $available_users );

		return array(
			'users' => $available_users,
			'roles' => $available_roles,
		);
	}

	/**
	 * Get the available schedule options.
	 *
	 * @param array $schedule_config
	 *
	 * @return array
	 */
	public function get_schedule_options( $schedule_config ) {
		$labels  = ITSEC_Notification_Center::get_schedule_labels();
		$ordered = ITSEC_Notification_Center::get_schedule_order();
		$min     = array_search( $schedule_config['min'], $ordered, true );
		$max     = array_search( $schedule_config['max'], $ordered, true );

		$options = array();

		foreach ( $ordered as $i => $schedule ) {
			if ( $min <= $i && $max >= $i ) {
				$options[ $schedule ] = $labels[ $schedule ];
			}
		}

		return $options;
	}

	/**
	 * Get the allowed HTML tags for messages.
	 *
	 * @return array
	 */
	public function get_allowed_html() {
		return array(
			'a'  => array(
				'href'  => array(),
				'title' => array(),
			),
			'i'  => array(),
			'b'  => array(),
			'h2' => array(),
			'h3' => array(),
			'h4' => array(),
			'h5' => array(),
			'h6' => array(),
			'p'  => array(),
		);
	}
}

ITSEC_Modules::register_validator( new ITSEC_Notification_Center_Validator() );