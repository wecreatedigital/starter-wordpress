<?php

final class ITSEC_Hide_Backend_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'hide-backend';
	}

	protected function sanitize_settings() {
		$this->sanitize_setting( 'bool', 'enabled', __( 'Hide Backend', 'it-l10n-ithemes-security-pro' ) );

		if ( ! $this->settings['enabled'] ) {
			// Ignore all non-enabled settings changes when enabled is not checked.
			foreach ( $this->previous_settings as $name => $val ) {
				if ( 'enabled' !== $name ) {
					$this->settings[$name] = $val;
				}
			}

			return;
		}

		if ( ! isset( $this->settings['register'] ) ) {
			$this->settings['register'] = $this->previous_settings['register'];
		} else if ( 'wp-signup.php' !== $this->settings['register'] ) {
			$this->sanitize_setting( 'non-empty-title', 'register', __( 'Register Slug', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->sanitize_setting( 'non-empty-title', 'slug', __( 'Login Slug', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'bool', 'theme_compat', __( 'Enable Redirection', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'non-empty-title', 'theme_compat_slug', __( 'Redirection Slug', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'title', 'post_logout_slug', __( 'Custom Login Action', 'it-l10n-ithemes-security-pro' ) );
	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}


		$forbidden_slugs = array( 'admin', 'login', 'wp-login.php', 'dashboard', 'wp-admin' );

		if ( in_array( $this->settings['slug'], $forbidden_slugs ) ) {
			$this->add_error( __( 'The Login Slug cannot be "%1$s" as WordPress uses that slug.', 'it-l10n-ithemes-security-pro' ) );
			$this->set_can_save( false );
			return;
		}


		if ( $this->settings['enabled'] && $this->settings['slug'] !== $this->previous_settings['slug'] ) {
			$url = get_site_url() . '/' . $this->settings['slug'];
			ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now active. Your new login URL is <strong><code>%1$s</code></strong>. Please note this may be different than what you sent as the URL was sanitized to meet various requirements. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Notification Center.', 'it-l10n-ithemes-security-pro' ), esc_url( $url ) ) );
		} else if ( $this->settings['enabled'] && ! $this->previous_settings['enabled'] ) {
			$url = get_site_url() . '/' . $this->settings['slug'];
			ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now active. Your new login URL is <strong><code>%1$s</code></strong>. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Notification Center.', 'it-l10n-ithemes-security-pro' ), esc_url( $url ) ) );
		} else if ( ! $this->settings['enabled'] && $this->previous_settings['enabled'] ) {
			$url = get_site_url() . '/wp-login.php';
			ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now disabled. Your new login URL is <strong><code>%1$s</code></strong>. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Notification Center.', 'it-l10n-ithemes-security-pro' ), esc_url( $url ) ) );
		}

		if ( isset( $url ) ) {
			ITSEC_Response::prevent_modal_close();
		}


		ITSEC_Response::reload_module( $this->get_id() );
	}

	/**
	 * Set HTML content type for email
	 *
	 * @return string html content type
	 */
	public function get_html_content_type() {
		return 'text/html';
	}
}

ITSEC_Modules::register_validator( new ITSEC_Hide_Backend_Validator() );
