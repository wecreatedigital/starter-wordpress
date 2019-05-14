<?php

class ITSEC_Dashboard_Card_Security_Profile_Pinned extends ITSEC_Dashboard_Card_Security_Profile {

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'security-profile';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return __( 'User Security Profile', 'it-l10n-ithemes-security-pro');
	}

	/**
	 * @inheritDoc
	 */
	public function get_size() {
		return array(
			'minW'     => 1,
			'minH'     => 2,
			'maxW'     => 2,
			'maxH'     => 4,
			'defaultW' => 2,
			'defaultH' => 2,
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_max() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function query_for_data( array $query_args, array $settings ) {
		return array(
			'user'  => ! empty( $settings['user'] ) && ( $user = get_userdata( $settings['user'] ) ) ? $this->build_user_data( $user ) : null,
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'user' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			)
		);
	}
}
