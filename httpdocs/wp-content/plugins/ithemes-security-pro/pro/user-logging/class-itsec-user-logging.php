<?php

final class ITSEC_User_Logging {

	public function run() {
		add_action( 'wp_login', array( $this, 'wp_login' ), 11, 2 );
		add_action( 'itsec_login_interstitial_logged_in', array( $this, 'interstitial_login' ) );
		add_action( 'wp_logout', array( $this, 'wp_logout' ) );
		add_action( 'itsec-two-factor-successful-authentication', array( $this, 'log_two_factor_authentication' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
		add_action( 'user_register', array( $this, 'user_register' ) );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ), 10, 2 );
		add_action( 'deleted_plugin', array( $this, 'deleted_plugin' ), 10, 2 );
		add_action( 'switch_theme', array( $this, 'switch_theme' ), 10, 3 );
	}

	/**
	 * Determine if the current user should have actions logged per the settings.
	 *
	 * @param bool $user User to check. Optional.
	 *
	 * @return bool True if the user actions should be logged, false otherwise.
	 */
	public function should_log_for_current_user( $user = false ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		return ITSEC_Lib_Canonical_Roles::is_user_at_least( ITSEC_Modules::get_setting( 'user-logging', 'role' ), $user );
	}

	/**
	 * Log post status transition
	 *
	 * @since 4.2
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 *
	 * @return void
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status || in_array( $new_status, array( 'auto-draft', 'inherit' ) ) ) {
			// Don't log automated processes as they don't indicate user action.
			// transition_post_status() isn't always called with different post statuses.
			return;
		}

		if ( $this->should_log_for_current_user() ) {
			$user_id = get_current_user_id();
			$post_id = $post->ID;
			ITSEC_Log::add_notice( 'user_logging', "post-status-changed::$user_id,$post_id,$old_status,$new_status", compact( 'user_id', 'post_id', 'old_status', 'new_status' ) );
		}
	}

	/**
	 * Log successful user login
	 *
	 * @since 4.1
	 *
	 * @return void
	 */
	public function wp_login( $user_login, $user ) {
		if ( $this->should_log_for_current_user( $user->ID ) ) {
			$user_id = $user->ID;
			ITSEC_Log::add_notice( 'user_logging', "user-logged-in::$user_id", compact( 'user_id' ) );
		}
	}

	/**
	 * When the user is logged-in via the interstitial, record the login.
	 *
	 * Remove this when we figure out a way to fire later wp_login actions in the Login Interstitial.
	 *
	 * @param WP_User $user
	 */
	public function interstitial_login( $user ) {
		if ( ! did_action( 'itsec-two-factor-successful-authentication' ) && $this->should_log_for_current_user( $user ) ) {
			ITSEC_Log::add_notice( 'user_logging', "user-logged-in::{$user->ID}", array( 'user_id' => $user->ID ) );
		}
	}

	/**
	 * Log successful user logout
	 *
	 * @since 4.1
	 *
	 * @return void
	 */
	public function wp_logout() {
		if ( $this->should_log_for_current_user() ) {
			$user_id = get_current_user_id();
			ITSEC_Log::add_notice( 'user_logging', "user-logged-out::$user_id", compact( 'user_id' ) );
		}
	}

	/**
	 * Log successful two-factor login
	 *
	 * @since 4.7.4
	 *
	 * @param string $provider_class Type of two factor authentication.
	 */
	public function log_two_factor_authentication( $user_id, $provider_class ) {
		if ( $this->should_log_for_current_user( $user_id ) ) {
			ITSEC_Log::add_notice( 'user_logging', "user-logged-in::$user_id,two_factor,$provider_class", compact( 'user_id' ) );
		}
	}

	/**
	 * Log when a user is created with a role that we care about.
	 *
	 * @param int $user_id
	 */
	public function user_register( $user_id ) {
		if ( ! $this->should_log_for_current_user( $user_id ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			ITSEC_Log::add_notice( 'user_logging', "user-registered::{$user_id},admin", array( 'user_id' => $user_id, 'created_by' => get_current_user_id() ) );
		} else {
			ITSEC_Log::add_notice( 'user_logging', "user-registered::{$user_id}", compact( 'user_id' ) );
		}
	}

	/**
	 * Log when a plugin is activated.
	 *
	 * @param string $file
	 * @param bool   $network_wide
	 */
	public function activated_plugin( $file, $network_wide ) {
		ITSEC_Log::add_notice( 'user_logging', "plugin-activated::{$file},{$network_wide}" );
	}

	/**
	 * Log when a plugin is deactivated.
	 *
	 * @param string $file
	 * @param bool   $network_wide
	 */
	public function deactivated_plugin( $file, $network_wide ) {
		ITSEC_Log::add_notice( 'user_logging', "plugin-deactivated::{$file},{$network_wide}" );
	}

	/**
	 * Log when a plugin is deleted.
	 *
	 * @param string $file
	 * @param bool   $deleted
	 */
	public function deleted_plugin( $file, $deleted ) {
		if ( $deleted ) {
			ITSEC_Log::add_notice( 'user_logging', "plugin-deleted::{$file}" );
		}
	}

	/**
	 * Log when the theme is switched.
	 *
	 * @param string   $new_name
	 * @param WP_Theme $new_theme
	 * @param WP_Theme $old_theme
	 */
	public function switch_theme( $new_name, $new_theme, $old_theme ) {
		ITSEC_Log::add_notice( 'user_logging', "theme-switched::{$new_theme->get_stylesheet()},{$old_theme->get_stylesheet()}" );
	}
}
