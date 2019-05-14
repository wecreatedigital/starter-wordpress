<?php

class ITSEC_SSL_Admin {
	function run() {
		$settings = ITSEC_Modules::get_settings( 'ssl' );

		if ( 'advanced' === $settings['require_ssl'] && 1 === $settings['frontend'] ) {
			add_action( 'init', array( $this, 'register_meta' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor' ) );
			add_action( 'post_submitbox_misc_actions', array( $this, 'ssl_enable_per_content' ) );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}
	}

	/**
	 * Register the "Enable SSL" meta key.
	 */
	public function register_meta() {
		register_meta( 'post', 'itsec_enable_ssl', array(
			'single'            => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'show_in_rest'      => array(
				'schema' => array(
					'type'    => 'boolean',
					'context' => array( 'edit' ),
				)
			)
		) );
	}

	/**
	 * Enqueue the JS for the block editor to add the "Enable SSL" checkbox.
	 */
	public function enqueue_block_editor() {
		wp_enqueue_script( 'itsec-ssl-block-editor', plugins_url( 'js/block-editor.js', __FILE__ ), array(
			'wp-components',
			'wp-compose',
			'wp-element',
			'wp-edit-post',
			'wp-data',
			'wp-plugins',
		), 1, true );
		wp_localize_script( 'itsec-ssl-block-editor', 'ITSECSSLBlockEditor', array(
			'enableSSL' => __( 'Enable SSL', 'it-l10n-ithemes-security-pro' ),
		) );
	}

	/**
	 * Add checkbox to post meta for SSL
	 *
	 * @return void
	 */
	function ssl_enable_per_content() {

		global $post;

		wp_nonce_field( 'ITSEC_Admin_Save', 'itsec_admin_save_wp_nonce' );

		$enabled = false;

		if ( $post->ID ) {
			$enabled = get_post_meta( $post->ID, 'itsec_enable_ssl', true );
		}

		$content = '<div id="itsec" class="misc-pub-section">';
		$content .= '<label for="enable_ssl">' . __( 'Enable SSL:', 'it-l10n-ithemes-security-pro' ) . '</label> ';
		$content .= '<input type="checkbox" value="1" name="enable_ssl" id="enable_ssl"' . checked( 1, $enabled, false ) . ' />';
		$content .= '</div>';

		echo $content;

	}

	/**
	 * Save post meta for SSL selection
	 *
	 * @param  int $id post id
	 *
	 * @return bool        value of itsec_enable_ssl
	 */
	function save_post( $id ) {

		if ( isset( $_POST['itsec_admin_save_wp_nonce'] ) ) {

			if ( ! wp_verify_nonce( $_POST['itsec_admin_save_wp_nonce'], 'ITSEC_Admin_Save' ) ) {
				return $id;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $id;
			}

			if ( ! current_user_can( 'edit_post', $id ) ) {
				return $id;
			}

			$itsec_enable_ssl = ( ( isset( $_POST['enable_ssl'] ) && $_POST['enable_ssl'] == true ) ? true : false );

			if ( $itsec_enable_ssl ) {
				update_post_meta( $id, 'itsec_enable_ssl', true );
			} else {
				delete_post_meta( $id, 'itsec_enable_ssl' );
			}

			return $itsec_enable_ssl;

		}

		return false;

	}
}
