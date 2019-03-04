<?php

final class ITSEC_Privacy_Util {
	public static function get_privacy_policy_content() {
		ITSEC_Modules::load_module_file( 'privacy.php', ':active' );


		$sections = array(
			'collection'        => array(
				'heading'     => __( 'What personal data we collect and why we collect it' ),
				'subheadings' => array(
					'comments'      => __( 'Comments' ),
					'media'         => __( 'Media' ),
					'contact_forms' => __( 'Contact Forms' ),
					'cookies'       => __( 'Cookies' ),
					'embeds'        => __( 'Embedded content from other websites' ),
					'analytics'     => __( 'Analytics' ),
					'security_logs' => __( 'Security Logs' ),
				),
			),
			'sharing'           => __( 'Who we share your data with' ),
			'retention'         => __( 'How long we retain your data' ),
			'rights'            => __( 'What rights you have over your data' ),
			'sending'           => __( 'Where we send your data' ),
			'additional'        => __( 'Additional information' ),
			'protection'        => __( 'How we protect your data' ),
			'breach_procedures' => __( 'What data breach procedures we have in place' ),
			'third_parties'     => __( 'What third parties we receive data from' ),
			'profiling'         => __( 'What automated decision making and/or profiling we do with user data' ),
		);

		$sections = apply_filters( 'itsec_get_privacy_policy_sections', $sections );


		$policy = '';

		foreach ( $sections as $section => $details ) {
			$section_text = apply_filters( "itsec_get_privacy_policy_for_$section", '' );

			if ( is_string( $details ) ) {
				$section_heading = $details;
			} else {
				$section_heading = $details['heading'];

				foreach ( $details['subheadings'] as $id => $heading ) {
					$text = apply_filters( "itsec_get_privacy_policy_for_$id", '' );

					if ( ! empty( $text ) ) {
						$section_text .= "<h3>$heading</h3>\n$text\n";
					}
				}
			}

			if ( ! empty( $section_text ) ) {
				$policy .= "<h2>$section_heading</h2>\n$section_text\n";
			}
		}

		if ( ! empty( $policy ) ) {
			$policy = "<div class=\"wp-suggested-text\">\n$policy\n</div>\n";
		}

		return $policy;
	}

	public static function export( $email, $page ) {
		global $wpdb;

		$limit = 500;
		$offset = ( $page - 1 ) * $limit;

		$user = get_user_by( 'email', $email );
		$user_id = false === $user ? false : $user->ID;
		$escaped_email = '%%' . $wpdb->esc_like( $email ) . '%%';

		if ( false === $user ) {
			$query = "SELECT id, module, code, type, timestamp, user_id, url FROM {$wpdb->base_prefix}itsec_logs WHERE data LIKE %s OR url LIKE %s LIMIT $offset,$limit";
			$query = $wpdb->prepare( $query, $escaped_email, $escaped_email );
		} else {
			$query = "SELECT id, module, code, type, timestamp, user_id, url FROM {$wpdb->base_prefix}itsec_logs WHERE data LIKE %s OR url LIKE %s OR user_id=%d LIMIT $offset,$limit";
			$query = $wpdb->prepare( $query, $escaped_email, $escaped_email, $user_id );
		}

		$logs = $wpdb->get_results( $query, ARRAY_A );
		$export_items = array();

		foreach ( (array) $logs as $log ) {
			$group_id = 'security-logs';
			$group_label = __( 'Security Logs', 'better-wp-security' );
			$item_id = "security-log-{$log['id']}";

			$data = self::get_data_from_log_entry( $log, $email, $user_id );

			$export_items[] = compact( 'group_id', 'group_label', 'item_id', 'data' );
		}


		$done = count( $logs ) < $limit;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	public static function erase( $email, $page ) {
		global $wpdb;

		$limit = 500;
		$offset = ( $page - 1 ) * $limit;

		$user = get_user_by( 'email', $email );
		$user_id = false === $user ? false : $user->ID;
		$escaped_email = '%%' . $wpdb->esc_like( $email ) . '%%';

		if ( false === $user ) {
			$query = "SELECT COUNT(id) AS count FROM {$wpdb->base_prefix}itsec_logs WHERE data LIKE %s OR url LIKE %s LIMIT $offset,$limit";
			$query = $wpdb->prepare( $query, $escaped_email, $escaped_email );
		} else {
			$query = "SELECT COUNT(id) AS count FROM {$wpdb->base_prefix}itsec_logs WHERE data LIKE %s OR url LIKE %s OR user_id=%d LIMIT $offset,$limit";
			$query = $wpdb->prepare( $query, $escaped_email, $escaped_email, $user_id );
		}

		$count = (int) $wpdb->get_var( $query );
		$done = $count < $limit;

		return array(
			'items_removed'  => false,
			'items_retained' => true,
			'messages'       => array(
				__( 'The security logs are retained since they may be required as part of analysis of a site compromise.', 'better-wp-security' ),
			),
			'done'           => $done,
		);
	}

	private static function get_data_from_log_entry( $log, $email, $user_id ) {
		$data = array(
			array(
				'name'  => __( 'Timestamp', 'better-wp-security' ),
				'value' => $log['timestamp'],
			),
		);

		if ( false === strpos( $log['code'], '::' ) ) {
			$code = $log['code'];
		} else {
			list( $code, $junk ) = explode( '::', $log['code'], 2 );
		}

		if ( 'lockout' === $log['module'] ) {
			$event = __( 'Failed login', 'better-wp-security' );
		} else if ( 'four_oh_four' === $log['module'] ) {
			$event = __( 'Requested suspicious URL', 'better-wp-security' );
		} else if ( 'ipcheck' === $log['module'] ) {
			$event = __( 'Failed check by network brute force protection', 'better-wp-security' );
		} else if ( 'brute_force' === $log['module'] ) {
			if ( 'auto-ban-admin-username' === $code ) {
				$event = __( 'Attempted to log in as admin', 'better-wp-security' );
			} else {
				$event = __( 'Failed login', 'better-wp-security' );
			}
		} else if ( 'away_mode' === $log['module'] ) {
			$event = __( 'Access while site in away mode', 'better-wp-security' );
		} else if ( 'recaptcha' === $log['module'] ) {
			$event = __( 'Failed reCAPTCHA validation', 'better-wp-security' );
		} else if ( 'two_factor' === $log['module'] ) {
			if ( 'failed_authentication' === $code ) {
				$event = __( 'Failed two-factor authentication validation', 'better-wp-security' );
			} else if ( 'successful_authentication' === $code ) {
				$event = __( 'Two-factor authentication validated successfully', 'better-wp-security' );
			} else if ( 'sync_override' === $code ) {
				$event = __( 'Overrode two-factor authentication using iThemes Sync', 'better-wp-security' );
			}
		} else if ( 'user_logging' === $log['module'] ) {
			if ( 'post-status-changed' === $code ) {
				$event = __( 'Changed content', 'better-wp-security' );
			} else if ( 'user-logged-in' === $code ) {
				$event = __( 'Logged in', 'better-wp-security' );
			} else if ( 'user-logged-out' === $code ) {
				$event = __( 'Logged out', 'better-wp-security' );
			}
		}

		if ( empty( $event ) ) {
			$event = __( 'Unknown event or action', 'better-wp-security' );
		}

		$data[] = array(
			'name'  => __( 'Event', 'better-wp-security' ),
			'value' => $event,
		);

		return $data;
	}
}
