<?php

class ITSEC_Dashboard_Card_Active_Lockouts extends ITSEC_Dashboard_Card {
	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'active-lockouts';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return __( 'Active Lockouts', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_size() {
		return array(
			'minW'     => 1,
			'minH'     => 2,
			'maxW'     => 2,
			'maxH'     => 3,
			'defaultW' => 1,
			'defaultH' => 2,
		);
	}

	/**
	 * @inheritDoc
	 */
	public function query_for_data( array $query_args, array $settings ) {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$lockout_query = array(
			'limit'   => 100,
			'current' => true,
			'order'   => 'DESC',
			'orderby' => 'lockout_start',
		);

		if ( ! empty( $query_args['search'] ) ) {
			$lockout_query['search'] = $query_args['search'];
		}

		$lockouts = $itsec_lockout->get_lockouts( 'all', $lockout_query );

		$data = array( 'lockouts' => array() );

		foreach ( $lockouts as $lockout ) {
			$data['lockouts'][] = $this->prepare_lockout( $lockout );
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function get_query_args() {
		$args = parent::get_query_args();

		$args['search'] = array(
			'type'      => 'string',
			'minLength' => 1,
		);

		return $args;
	}

	public function get_links() {
		return array(
			array(
				'rel'      => 'item',
				'route'    => 'lockout/(?P<lockout_id>[\d]+)',
				'title'    => __( 'Lockout Details', 'it-l10n-ithemes-security-pro' ),
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_lockout_details' ),
			),
			array(
				'rel'      => ITSEC_Dashboard_REST::LINK_REL . 'release-lockout',
				'route'    => 'lockout/(?P<lockout_id>[\d]+)',
				'title'    => __( 'Release Lockout', 'it-l10n-ithemes-security-pro' ),
				'methods'  => WP_REST_Server::DELETABLE,
				'cap'      => ITSEC_Core::get_required_cap(),
				'callback' => array( $this, 'release_lockout' ),
			),
		);
	}

	/**
	 * Get the lockout details.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 */
	public function get_lockout_details( $request ) {
		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$lockout_id = (int) $request['lockout_id'];

		if ( ! $lockout_id || ! $lockout = $itsec_lockout->get_lockout( $lockout_id ) ) {
			return new WP_Error( 'not_found', __( 'Lockout Not Found', 'it-l10n-ithemes-security-pro' ) );
		}

		return $this->prepare_lockout( $lockout, true );
	}

	/**
	 * Release the lockout.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return null|WP_Error
	 */
	public function release_lockout( $request ) {
		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$lockout_id = (int) $request['lockout_id'];

		if ( ! $lockout_id || ! $lockout = $itsec_lockout->get_lockout( $lockout_id ) ) {
			return new WP_Error( 'not_found', __( 'Lockout Not Found', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! $itsec_lockout->release_lockout( $lockout_id ) ) {
			return new WP_Error( 'release_lockout_failed', __( 'Failed to release lockout.', 'it-l10n-ithemes-security-pro' ) );
		}

		return null;
	}

	private function prepare_lockout( $lockout, $detail = false ) {
		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$modules = $itsec_lockout->get_lockout_modules();

		$data = array();

		foreach ( $lockout as $key => $value ) {
			$data[ str_replace( 'lockout_', '', $key ) ] = $value;
		}

		$data['active'] = (bool) $data['active'];

		foreach ( array( 'start', 'start_gmt', 'expire', 'expire_gmt' ) as $date_prop ) {
			$data[ $date_prop ] = ITSEC_Lib::to_rest_date( $data[ $date_prop ] );

			$data["{$date_prop}_relative"] = human_time_diff( strtotime( $data[ $date_prop ] ) );
		}

		if ( ! empty( $data['host'] ) ) {
			$data['label'] = $data['host'];
		} elseif ( ! empty( $data['username'] ) ) {
			$data['label'] = $data['username'];
		} elseif ( ! empty( $data['user'] ) ) {
			$user = get_userdata( $data['user'] );

			$data['label'] = $user ? $user->display_name : sprintf( __( 'Deleted User %d', 'it-l10n-ithemes-security-pro' ), $data['user'] );
		} else {
			$data['label'] = __( 'Unknown', 'it-l10n-ithemes-security-pro' );
		}

		$data['description'] = isset( $modules[ $data['type'] ] ) ? $modules[ $data['type'] ]['reason'] : __( 'unknown reason.', 'it-l10n-ithemes-security-pro' );

		if ( $detail ) {
			if ( ! empty( $data['host'] ) ) {
				$entries = ITSEC_Log::get_entries( array(
					'init_timestamp' => $data['start_gmt'],
					'module'         => 'lockout',
					'code'           => "host-lockout::{$data['host']}",
				), 1, 1, 'timestamp', 'DESC', 'all' );
			} elseif ( ! empty( $data['user'] ) ) {
				$entries = ITSEC_Log::get_entries( array(
					'init_timestamp' => $data['start_gmt'],
					'module'         => 'lockout',
					'code'           => "user-lockout::{$data['user']}",
				), 1, 1, 'timestamp', 'DESC', 'all' );
			} elseif ( ! empty( $data['username'] ) ) {
				$entries = ITSEC_Log::get_entries( array(
					'init_timestamp' => $data['start_gmt'],
					'module'         => 'lockout',
					'code'           => "username-lockout::{$data['username']}",
				), 1, 1, 'timestamp', 'DESC', 'all' );
			} else {
				$entries = array();
			}

			if ( ! empty( $entries[0] ) ) {
				$lockout_log = array(
					'id'            => (int) $entries[0]['id'],
					'time'          => ITSEC_Lib::to_rest_date( $entries[0]['init_timestamp'] ),
					'time_relative' => human_time_diff( strtotime( $entries[0]['init_timestamp'] ) ),
					'remote_ip'     => $entries[0]['remote_ip'],
					'url'           => $entries[0]['url'],
					'data'          => $entries[0]['data'],
				);
			} else {
				$lockout_log = array();
			}

			$data['detail'] = array(
				'log'     => $lockout_log,
				'history' => array(),
			);

			switch ( $data['type'] ) {
				case 'four_oh_four':
					$logs = ITSEC_Log::get_entries( array(
						'module'          => 'four_oh_four',
						'code'            => 'found_404',
						'remote_ip'       => $data['host'],
						'__max_timestamp' => strtotime( $data['start_gmt'] ),
					), 100, 1, 'timestamp' );

					if ( is_array( $logs ) ) {
						foreach ( $logs as $log ) {
							$data['detail']['history'][] = array(
								'id'            => (int) $log['id'],
								'time'          => ITSEC_Lib::to_rest_date( $log['init_timestamp'] ),
								'time_relative' => human_time_diff( strtotime( $log['init_timestamp'] ) ),
								'url'           => $log['url'],
								'label'         => $log['url'],
							);
						}
					}
					break;
				case 'recaptcha':
					$logs = ITSEC_Log::get_entries( array(
						'module'          => 'recaptcha',
						'code'            => 'failed-validation',
						'remote_ip'       => $data['host'],
						'__max_timestamp' => strtotime( $data['start_gmt'] ),
					), 100, 1, 'timestamp', 'DESC', 'all' );

					if ( is_array( $logs ) ) {
						foreach ( $logs as $log ) {
							if ( is_wp_error( $log['data'] ) ) {
								$label = $log['data']->get_error_code() === 'itsec-recaptcha-incorrect' ? __( 'Invalid Recaptcha', 'it-l10n-ithemes-security-pro' ) : __( 'Skipped Recaptcha', 'it-l10n-ithemes-security-pro' );
							} else {
								$label = __( 'Unknown', 'it-l10n-ithemes-security-pro' );
							}

							$data['detail']['history'][] = array(
								'id'            => (int) $log['id'],
								'time'          => ITSEC_Lib::to_rest_date( $log['init_timestamp'] ),
								'time_relative' => human_time_diff( strtotime( $log['init_timestamp'] ) ),
								'url'           => $log['url'],
								'label'         => $label,
								'error'         => is_wp_error( $log['data'] ) ? array(
									'code'    => $log['data']->get_error_code(),
									'message' => $log['data']->get_error_message(),
								) : null,
							);
						}
					}
					break;
				case 'brute_force':
					$log_query = array(
						'module'          => 'brute_force',
						'__max_timestamp' => strtotime( $data['start_gmt'] ),
					);

					if ( ! empty( $data['host'] ) ) {
						$log_query['remote_ip'] = $data['host'];
					} elseif ( ! empty( $data['user'] ) ) {
						$log_query['code'] = "invalid-login::user-{$data['user']}";
					} elseif ( ! empty( $data['username'] ) ) {
						$log_query['code'] = "invalid-login::username-{$data['username']}";
					} else {
						break;
					}

					$logs = ITSEC_Log::get_entries( $log_query, 100, 1, 'timestamp', 'DESC', 'all' );

					if ( is_array( $logs ) ) {
						foreach ( $logs as $log ) {
							if ( ! empty( $data['host'] ) ) {
								$label = $log['data']['username'];
							} elseif ( ! empty( $data['username'] ) || ! empty( $data['user'] ) ) {
								$label = $log['remote_ip'];
							} else {
								$label = '';
							}

							$data['detail']['history'][] = array(
								'id'            => (int) $log['id'],
								'time'          => ITSEC_Lib::to_rest_date( $log['init_timestamp'] ),
								'time_relative' => human_time_diff( strtotime( $log['init_timestamp'] ) ),
								'url'           => $log['url'],
								'remote_ip'     => $log['remote_ip'],
								'data'          => array(
									'details'  => $log['data']['details'],
									'user'     => $log['data']['user'],
									'username' => $log['data']['username'],
									'user_id'  => $log['data']['user_id'],
								),
								'label'         => $label,
							);
						}
					}
					break;
			}
		}

		return $data;
	}
}
