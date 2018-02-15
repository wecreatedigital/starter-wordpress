<?php

class ITSEC_Settings_Page_Sidebar_Widget_Active_Lockouts extends ITSEC_Settings_Page_Sidebar_Widget {
	public function __construct() {
		$this->id = 'active-lockouts';
		$this->title = __( 'Active Lockouts', 'better-wp-security' );
		$this->priority = 9;

		parent::__construct();
	}

	public function render( $form ) {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$lockouts = $itsec_lockout->get_lockouts();
		$usernames = array();
		$users = array();
		$hosts = array();

		foreach ( $lockouts as $lockout ) {
			if ( empty( $lockout['lockout_expire_gmt'] ) ) {
				continue;
			}

			$expiration = strtotime( $lockout['lockout_expire_gmt'] );

			if ( $expiration < ITSEC_Core::get_current_time_gmt() ) {
				continue;
			}

			$data = array( $lockout['lockout_id'], $expiration );

			if ( ! empty( $lockout['lockout_user'] ) ) {
				$users[ $lockout['lockout_user'] ] = $data;
			} elseif ( ! empty( $lockout['lockout_username'] ) ) {
				$usernames[ $lockout['lockout_username'] ] = $data;
			} elseif ( ! empty( $lockout['lockout_host'] ) ) {
				$hosts[ $lockout['lockout_host'] ] = $data;
			}
		}


		if ( ! $users && ! $usernames && ! $hosts ) {
			echo '<p>' . __( 'There are no active lockouts at this time.', 'better-wp-security' ) . "</p>\n";
			return;
		}

		if ( ! empty( $users ) ) {
			echo '<p><strong>' . __( 'Users', 'better-wp-security' ) . "</strong></p>\n";
			echo "<ul>\n";

			foreach ( $users as $user_id => $data ) {
				$user = get_userdata( $user_id );

				if ( $user ) {
					$label = $user->user_login;
				} else {
					$label = sprintf( __( 'Deleted #%d', 'better-wp-security' ), $user_id );
				}

				/* translators: 1. Username 2. Expiration as human time diff */
				$label = sprintf( _x( '%1$s - Expires in %2$s', 'User lockout', 'better-wp-security' ), "<strong>{$label}</strong>", '<em>' . human_time_diff( $data[1] ) . '</em>' );
				echo '<li><label>';
				$form->add_multi_checkbox( 'users', $data[0] );
				echo " $label</label></li>\n";
			}

			echo "</ul>\n";
		}

		if ( ! empty( $usernames ) ) {
			echo '<p><strong>' . __( 'Usernames', 'better-wp-security' ) . "</strong></p>\n";
			echo "<ul>\n";

			foreach ( $usernames as $username => $data ) {
				/* translators: 1. Username 2. Expiration as human time diff */
				$label = sprintf( _x( '%1$s - Expires in %2$s', 'Username lockout', 'better-wp-security' ), '<strong>' . esc_html( $username ) . '</strong>', '<em>' . human_time_diff( $data[1] ) . '</em>' );
				echo '<li><label>';
				$form->add_multi_checkbox( 'usernames', $data[0] );
				echo " $label</label></li>\n";
			}

			echo "</ul>\n";
		}

		if ( ! empty( $hosts ) ) {
			echo '<p><strong>' . __( 'Hosts', 'better-wp-security' ) . "</strong></p>\n";
			echo "<ul>\n";

			foreach ( $hosts as $host => $data ) {
				/* translators: 1. IP Address 2. Expiration as human time diff */
				$label = sprintf( _x( '%1$s - Expires in %2$s', 'Host lockout', 'better-wp-security' ), '<strong>' . esc_html( strtoupper( $host ) ) . '</strong>', '<em>' . human_time_diff( $data[1] ) . '</em>' );
				echo '<li><label>';
				$form->add_multi_checkbox( 'hosts', $data[0] );
				echo " $label</label></li>\n";
			}

			echo "</ul>\n";
		}

		echo '<p>';
		$form->add_submit( 'release-lockouts', array( 'value' => __( 'Release Selected Lockouts', 'better-wp-security' ), 'class' => 'button-secondary' ) );
		echo "</p>\n";
	}

	protected function save( $data ) {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		$count = 0;

		if ( ! empty( $data['users'] ) && is_array( $data['users'] ) ) {
			foreach ( $data['users'] as $id ) {
				$result = $itsec_lockout->release_lockout( $id );
				$count++;

				if ( ! $result ) {
					$this->errors[] = sprintf( __( 'An unknown error prevented releasing the user lockout with a lockout ID of %d', 'better-wp-security' ), $id );
				}
			}
		}

		if ( ! empty( $data['usernames'] ) && is_array( $data['usernames'] ) ) {
			foreach ( $data['usernames'] as $id ) {
				$result = $itsec_lockout->release_lockout( $id );
				$count++;

				if ( ! $result ) {
					$this->errors[] = sprintf( __( 'An unknown error prevented releasing the username lockout with a lockout ID of %d', 'better-wp-security' ), $id );
				}
			}
		}

		if ( ! empty( $data['hosts'] ) && is_array( $data['hosts'] ) ) {
			foreach ( $data['hosts'] as $id ) {
				$result = $itsec_lockout->release_lockout( $id );
				$count++;

				if ( ! $result ) {
					$this->errors[] = sprintf( __( 'An unknown error prevented releasing the host lockout with a lockout ID of %d', 'better-wp-security' ), $id );
				}
			}
		}

		if ( empty( $this->errors ) ) {
			if ( $count > 0 ) {
				$this->messages[] = _n( 'Successfully removed the selected lockout.', 'Successfully remove the selected lockouts.', $count, 'better-wp-security' );
			} else {
				$this->errors[] = __( 'No lockouts were selected for removal.', 'better-wp-security' );
			}
		}
	}
}
new ITSEC_Settings_Page_Sidebar_Widget_Active_Lockouts();
