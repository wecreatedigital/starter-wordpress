<?php

/**
 * Interface ITSEC_Two_Factor_Provider_On_Boardable
 *
 * Interface for Two Factor providers that are configured to be on-boarded in the Login Interstitial.
 */
interface ITSEC_Two_Factor_Provider_On_Boardable {

	/**
	 * Get the dashicon.
	 *
	 * @return string
	 */
	public function get_on_board_dashicon();

	/**
	 * Get the label to show for this provider.
	 *
	 * @return string
	 */
	public function get_on_board_label();

	/**
	 * Get the short 1 sentence description of how the provider operates.
	 *
	 * @return string
	 */
	public function get_on_board_description();

	/**
	 * Whether the user needs to perform steps to configure the provider beyond enabling it.
	 *
	 * @return bool
	 */
	public function has_on_board_configuration();

	/**
	 * Get data to provide to the On Board app.
	 *
	 * @param WP_User $user
	 *
	 * @return array
	 */
	public function get_on_board_config( WP_User $user );

	/**
	 * Handle an ajax request from the on-board module.
	 *
	 * @param WP_User $user
	 * @param array   $data
	 *
	 * @return void This should directly die using wp_send_json functions.
	 */
	public function handle_ajax_on_board( WP_User $user, array $data );
}