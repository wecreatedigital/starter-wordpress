<?php

/**
 * Class ITSEC_Login_Interstitial
 */
abstract class ITSEC_Login_Interstitial {

	/**
	 * Should this interstitial be shown to the given user.
	 *
	 * @param WP_User $user
	 * @param bool    $is_requested
	 *
	 * @return bool
	 */
	public function show_to_user( WP_User $user, $is_requested ) {
		return true;
	}

	/**
	 * Only show this interstitial if the user logged-in via wp-login.php.
	 *
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function show_on_wp_login_only( WP_User $user ) {
		return false;
	}

	/**
	 * Render the interstitial.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param array                            $args
	 *
	 * @return string
	 */
	abstract public function render( ITSEC_Login_Interstitial_Session $session, array $args );

	/**
	 * Run code before any HTML it outputted for rendering an interstitial.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 */
	public function pre_render( ITSEC_Login_Interstitial_Session $session ) { }

	/**
	 * Must this interstitial be completed by the given user.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 *
	 * @return bool
	 */
	public function is_completion_forced( ITSEC_Login_Interstitial_Session $session ) {
		return true;
	}

	/**
	 * Is there a submit handler.
	 *
	 * @return bool
	 */
	public function has_submit() {
		return false;
	}

	/**
	 * Handle submitting the interstitial.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param array                            $data
	 */
	public function submit( ITSEC_Login_Interstitial_Session $session, array $data ) { }

	/**
	 * Does the interstitial have async GET actions.
	 *
	 * @return bool
	 */
	public function has_async_action() {
		return false;
	}

	/**
	 * Handle an async action.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param string                           $action
	 * @param array                            $args
	 *
	 * @return true|array|WP_Error|void
	 *      True if success.
	 *      Array if success with output customizations.
	 *      WP_Error if error.
	 *      Void/null if action not processed.
	 *      Or display custom HTML and die.
	 */
	public function handle_async_action( ITSEC_Login_Interstitial_Session $session, $action, array $args ) { }

	/**
	 * Does the interstitial have ajax handlers.
	 *
	 * @return bool
	 */
	public function has_ajax_handlers() {
		return false;
	}

	/**
	 * Handle an ajax request.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param array                            $data
	 */
	public function handle_ajax( ITSEC_Login_Interstitial_Session $session, array $data ) { }

	/**
	 * Get an info message to display above the interstitial form.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 *
	 * @return string
	 */
	public function get_info_message( ITSEC_Login_Interstitial_Session $session ) {
		return '';
	}

	/**
	 * Execute code after the interstitial has been submitted.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param array                            $data
	 */
	public function after_submit( ITSEC_Login_Interstitial_Session $session, array $data ) { }

	/**
	 * Get the priority. A higher priority number is displayed later.
	 *
	 * @return int
	 */
	public function get_priority() {
		return 5;
	}
}
