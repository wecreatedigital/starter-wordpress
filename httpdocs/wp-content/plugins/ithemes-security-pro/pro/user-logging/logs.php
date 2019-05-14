<?php

final class ITSEC_User_Logging_Logs {
	private $providers = null;


	public function __construct() {
		add_filter( 'itsec_logs_prepare_user_logging_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
	}

	public function filter_entry_for_list_display( $entry, $code, $data ) {
		$entry['module_display'] = esc_html__( 'User Logging', 'it-l10n-ithemes-security-pro' );

		if ( 'post-status-changed' === $code ) {
			if ( empty( $data ) ) {
				$entry['description'] = esc_html__( 'Post Status Changed', 'it-l10n-ithemes-security-pro' );
			} else {
				// Older versions didn't include the user ID at index 0, so fake it.
				if ( 3 === count( $data ) ) {
					array_unshift( $data, 0 );
				}

				$post = get_post( $data[1], OBJECT, 'display' );
				$old_status = $this->get_post_status_label( $data[2] );
				$new_status = $this->get_post_status_label( $data[3] );

				/* translators: 1: old post status, 2: new post status */
				$entry['description'] = sprintf( esc_html__( 'Post Status Changed: %1$s &rarr; %2$s', 'it-l10n-ithemes-security-pro' ), $old_status, $new_status );

				if ( isset( $post ) && isset( $post->post_title ) ) {
					$post_type_object = get_post_type_object( $post->post_type );

					if ( isset( $post_type_object ) ) {
						$title_arg = sprintf( esc_html_x( '%1$s: %2$s', 'Post Type: Post Title', 'it-l10n-ithemes-security-pro' ), $post_type_object->labels->singular_name, $post->post_title );
					} else {
						$title_arg = sprintf( esc_html_x( 'Post: %1$s', 'Post Type: Post Title', 'it-l10n-ithemes-security-pro' ), $post->post_title );
					}

					$entry['description'] = '<span title="' . esc_attr( $title_arg ) . '">' . $entry['description'] . '</span>';
				}
			}
		} else if ( 'user-logged-out' === $code ) {
			if ( isset( $data[0] ) && 0 !== $data[0] ) {
				$entry['user_id'] = $data[0];
				if ( false !== ( $user = get_userdata( $data[0] ) ) ) {
					$username = $user->user_login;
				}
			}

			if ( ! isset( $username ) ) {
				$username = '<b>' . esc_html__( 'Unknown User', 'it-l10n-ithemes-security-pro' ) . '</b>';
			}

			/* translators: 1: Username */
			$entry['description'] = sprintf( esc_html__( '%1$s Logged Out', 'it-l10n-ithemes-security-pro' ), $username );
		} else if ( 'user-logged-in' === $code ) {
			if ( isset( $data[0] ) && 0 !== $data[0] ) {
				$entry['user_id'] = $data[0];
				if ( false !== ( $user = get_userdata( $data[0] ) ) ) {
					$username = $user->user_login;
				}
			}

			if ( ! isset( $username ) ) {
				$username = '<b>' . esc_html__( 'Unknown User', 'it-l10n-ithemes-security-pro' ) . '</b>';
			}

			if ( ! isset( $data[1] ) ) {
				/* translators: 1: Username */
				$entry['description'] = sprintf( esc_html__( '%1$s Logged In', 'it-l10n-ithemes-security-pro' ), $username );
			} else if ( 'two_factor' === $data[1] ) {
				if ( is_null( $this->providers ) ) {
					require_once( ITSEC_Core::get_plugin_dir() . '/pro/two-factor/class-itsec-two-factor-helper.php' );
					$two_factor_helper = ITSEC_Two_Factor_Helper::get_instance();

					$this->providers = $two_factor_helper->get_all_provider_instances();
				}

				if ( ! isset( $data[2] ) ) {
					/* translators: 1: Username */
					$entry['description'] = sprintf( esc_html__( '%1$s Logged In Using Two-Factor', 'it-l10n-ithemes-security-pro' ), $username );
				} else {
					if ( isset( $this->providers[$data[2]] ) ) {
						$provider = $this->providers[$data[2]]->get_label();
					} else {
						$provider = $data[2];
					}

					/* translators: 1: Username, 2: Two Factor provider */
					$entry['description'] = sprintf( esc_html__( '%1$s Logged In Using %2$s Two-Factor', 'it-l10n-ithemes-security-pro' ), $username, $provider );
				}
			}
		} elseif ( 'user-registered' === $code ) {
			$username = '<b>' . esc_html__( 'Unknown User', 'it-l10n-ithemes-security-pro' ) . '</b>';

			if ( isset( $data[0] ) && 0 !== $data[0] && false !== ( $user = get_userdata( $data[0] ) ) ) {
				$username = $user->user_login;
			}

			if ( isset( $data[1] ) && 'admin' === $data[1] ) {
				/* translators: 1: Username */
				$entry['description'] = sprintf( esc_html__( 'Created %s', 'it-l10n-ithemes-security-pro' ), $username );
			} else {
				/* translators: 1: Username */
				$entry['description'] = sprintf( esc_html__( 'Registered %s', 'it-l10n-ithemes-security-pro' ), $username );
			}
		} elseif ( 'plugin-activated' === $code ) {
			if ( empty( $data[1] ) ) {
				$entry['description'] = sprintf( esc_html__( 'Activated %s Plugin', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $data[0] ) );
			} else {
				$entry['description'] = sprintf( esc_html__( 'Network Activated %s Plugin', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $data[0] ) );
			}
		} elseif ( 'plugin-deactivated' === $code ) {
			if ( empty( $data[1] ) ) {
				$entry['description'] = sprintf( esc_html__( 'Deactivated %s Plugin', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $data[0] ) );
			} else {
				$entry['description'] = sprintf( esc_html__( 'Network Deactivated %s Plugin', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $data[0] ) );
			}
		} elseif ( 'plugin-deleted' === $code ) {
			$entry['description'] = sprintf( esc_html__( 'Deleted %s Plugin', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $data[0] ) );
		} elseif ( 'theme-switched' === $code ) {
			$entry['description'] = sprintf( esc_html__( 'Switched Theme to %s from %s', 'it-l10n-ithemes-security-pro' ), $this->get_theme_name( $data[0] ), $this->get_theme_name( $data[1] ) );
		}

		return $entry;
	}

	private function get_plugin_name( $file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			return "'{$file}'";
		}

		$path = WP_PLUGIN_DIR . '/' . $file;

		if ( ! file_exists( $path ) ) {
			return "'{$file}'";
		}

		$data = get_plugin_data( $path );

		return $data['Name'];
	}

	private function get_theme_name( $stylesheet ) {

		if ( ! ( $theme = wp_get_theme( $stylesheet ) ) || ! $theme->exists() ) {
			return "'{$stylesheet}'";
		}

		return $theme->get( 'Name' );
	}

	private function get_post_status_label( $status ) {
		$status_object = get_post_status_object( $status );

		if ( is_null( $status_object ) ) {
			return $status;
		}

		return $status_object->label;
	}
}
new ITSEC_User_Logging_Logs();
