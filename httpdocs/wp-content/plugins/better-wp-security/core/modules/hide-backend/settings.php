<?php

final class ITSEC_Hide_Backend_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'hide-backend';
	}

	public function get_defaults() {
		return array(
			'enabled'           => false,
			'slug'              => 'wplogin',
			'register'          => 'wp-signup.php',
			'theme_compat'      => true,
			'theme_compat_slug' => 'not_found',
			'post_logout_slug'  => '',
		);
	}

	protected function handle_settings_changes( $old_settings ) {

		if ( $this->settings['enabled'] && ! $old_settings['enabled'] ) {
			$url      = get_site_url() . '/' . $this->settings['slug'];
			$enabling = true;
		} elseif ( ! $this->settings['enabled'] && $old_settings['enabled'] ) {
			$url      = get_site_url() . '/wp-login.php';
			$enabling = false;
		} elseif ( $this->settings['enabled'] && $this->settings['slug'] !== $old_settings['slug'] ) {
			$url      = get_site_url() . '/' . $this->settings['slug'];
			$enabling = false;
		} else {
			return;
		}

		$this->send_new_login_url( $url, $enabling );
	}

	private function send_new_login_url( $url, $enabling ) {
		if ( ITSEC_Core::doing_data_upgrade() ) {
			// Do not send emails when upgrading data. This prevents spamming users with notifications just because the
			// data was ported from an old version to a new version.
			return;
		}

		$nc = ITSEC_Core::get_notification_center();

		if ( $enabling ) {
			$nc->clear_notifications_cache();
			ITSEC_Modules::get_settings_obj( 'notification-center' )->load();
		}

		$mail = $nc->mail();

		$mail->add_header( esc_html__( 'New Login URL', 'better-wp-security' ), esc_html__( 'New Login URL', 'better-wp-security' ) );
		$mail->add_text( ITSEC_Lib::replace_tags( $nc->get_message( 'hide-backend' ), array(
			'login_url'  => '<code>' . esc_url( $url ) . '</code>',
			'site_title' => get_bloginfo( 'name', 'display' ),
			'site_url'   => $mail->get_display_url(),
		) ) );
		$mail->add_button( esc_html__( 'Login Now', 'better-wp-security' ), $url );
		$mail->add_footer();

		$subject = $mail->prepend_site_url_to_subject( $nc->get_subject( 'hide-backend' ) );
		$subject = apply_filters( 'itsec_hide_backend_email_subject', $subject );
		$mail->set_subject( $subject, false );
		$nc->send( 'hide-backend', $mail );
	}
}

ITSEC_Modules::register_settings( new ITSEC_Hide_Backend_Settings() );
