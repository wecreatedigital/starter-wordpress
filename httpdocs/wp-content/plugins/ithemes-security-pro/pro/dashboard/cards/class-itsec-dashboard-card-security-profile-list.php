<?php

require_once( dirname( __FILE__ ) . '/abstract-itsec-dashboard-card-security-profile.php' );

/**
 * Class ITSEC_Dashboard_Card_Security_Profile_List
 */
class ITSEC_Dashboard_Card_Security_Profile_List extends ITSEC_Dashboard_Card_Security_Profile {

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'security-profile-list';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return esc_html__( 'User Security Profiles', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_size() {
		return array(
			'minW'     => 3,
			'minH'     => 2,
			'maxW'     => 4,
			'maxH'     => 4,
			'defaultW' => 3,
			'defaultH' => 2,
		);
	}

	/**
	 * @inheritDoc
	 */
	public function query_for_data( array $query_args, array $settings ) {

		$users = array();

		$user_query_args = array(
			'number'   => 250,
			'role__in' => array(),
		);

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-canonical-roles.php' );

		foreach ( wp_roles()->roles as $role => $caps ) {
			if ( 'administrator' === $role || 'administrator' === ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( $role ) ) {
				$user_query_args['role__in'][] = $role;
			}
		}

		$user_query = new WP_User_Query( $user_query_args );

		foreach ( $user_query->get_results() as $user ) {
			$users[] = $this->build_user_data( $user );
		}

		return array(
			'users' => $users,
		);
	}
}
