<?php

final class ITSEC_Version_Management_Settings_Page extends ITSEC_Module_Settings_Page {
	private $version = 3;


	public function __construct() {
		$this->id = 'version-management';
		$this->title = __( 'Version Management', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'Protect your site when outdated software is not updated quickly enough.', 'it-l10n-ithemes-security-pro' );
		$this->type = 'recommended';
		$this->pro = true;

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {

		$config = ITSEC_Modules::get_setting( 'version-management', 'packages' );

		$packages = array();

		foreach ( get_plugins() as $file => $plugin ) {
			$packages[] = array(
				'id'    => "plugin:{$file}",
				'name'  => $plugin['Name'],
				'file'  => $file,
				'kind'  => 'plugin',
				'type'  => isset( $config["plugin:{$file}"]['type'] ) ? $config["plugin:{$file}"]['type'] : 'enabled',
				'delay' => isset( $config["plugin:{$file}"]['delay'] ) ? $config["plugin:{$file}"]['delay'] : 3,
			);
		}

		foreach ( wp_get_themes() as $file => $theme ) {
			$packages[] = array(
				'id'    => "theme:{$file}",
				'name'  => $theme->get( 'Name' ),
				'file'  => $file,
				'kind'  => 'theme',
				'type'  => isset( $config["theme:{$file}"]['type'] ) ? $config["theme:{$file}"]['type'] : 'enabled',
				'delay' => isset( $config["theme:{$file}"]['delay'] ) ? $config["theme:{$file}"]['delay'] : 3,
			);
		}

		wp_enqueue_style( 'itsec-version-management-style', plugins_url( 'css/settings-page.css', __FILE__ ), array(), $this->version );
		wp_enqueue_script( 'itsec-version-management-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'wp-backbone', 'underscore' ), $this->version );
		wp_localize_script( 'itsec-version-management-script', 'ITSECVersionManagement', array(
			/* translators: %s is the plugin or theme name. */
			'bulkLabel' => __( 'Select %s', 'it-l10n-ithemes-security-pro' ),
			'packages'  => $packages,
		) );
	}

	protected function render_description( $form ) {

?>
	<p><?php _e( 'Even with recommended security settings, running vulnerable software on your site can give an attacker an entry point into your site. These settings help protect your site with options to automatically update to new versions or to increase use security when the site\'s software is outdated.', 'it-l10n-ithemes-security-pro' ); ?></p>
<?php

	}

	protected function render_settings( $form ) {

		require_once( dirname( __FILE__ ) . '/js/templates.php' );

		/** @var ITSEC_Version_Management_Validator $validator */
		$validator = ITSEC_Modules::get_validator( $this->id );

		$this->add_automatic_update_status_errors();
?>

	<table class="form-table">
		<tr>
			<th scope="row"><label for="itsec-version-management-wordpress_automatic_updates"><?php esc_html_e( 'WordPress Updates', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<p>
					<?php $form->add_checkbox( 'wordpress_automatic_updates' ); ?>
					<label for="itsec-version-management-wordpress_automatic_updates"><?php esc_html_e( 'Automatically install the latest WordPress release.', 'it-l10n-ithemes-security-pro' ); ?></label>
					<?php $this->render_tooltip( __( 'This should be enabled unless you actively maintain this site on a daily basis and install the updates manually shortly after they are released.', 'it-l10n-ithemes-security-pro' ) ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-version-management-plugin_automatic_updates"><?php esc_html_e( 'Plugin Updates', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_select( 'plugin_automatic_updates', $validator->get_update_types() ); ?>
				<p class="description">
					<?php esc_html_e( 'Automatically install the latest plugin updates.', 'it-l10n-ithemes-security-pro' ); ?>
					<?php esc_html_e( 'This should be enabled unless you actively maintain this site on a daily basis and install the updates manually shortly after they are released.', 'it-l10n-ithemes-security-pro' ); ?>
				</p>
			</td>
		</tr>
		<?php $this->render_packages( $form, 'plugin' ); ?>
		<tr>
			<th scope="row"><label for="itsec-version-management-theme_automatic_updates"><?php esc_html_e( 'Theme Updates', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_select( 'theme_automatic_updates', $validator->get_update_types() ); ?>
				<p class="description">
					<?php esc_html_e( 'Automatically install the latest theme updates.', 'it-l10n-ithemes-security-pro' ); ?>
					<?php esc_html_e( 'This should be enabled unless your theme has file customizations.', 'it-l10n-ithemes-security-pro' ); ?>
				</p>
			</td>
		</tr>
		<?php $this->render_packages( $form, 'theme' ); ?>
		<tr>
			<th scope="row"><label for="itsec-version-management-strengthen_when_outdated"><?php esc_html_e( 'Strengthen Site When Running Outdated Software', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<p>
					<?php $form->add_checkbox( 'strengthen_when_outdated' ); ?>
					<label for="itsec-version-management-strengthen_when_outdated"><?php esc_html_e( 'Automatically add extra protections to the site when an available update has not been installed for a month.', 'it-l10n-ithemes-security-pro' ); ?>
					<?php
						$tooltip = esc_html__( 'This will harden your website security in a couple of key ways:', 'it-l10n-ithemes-security-pro' ) . '<br/><br/>';
						$tooltip .= esc_html__( 'It will force all users that do not have two-factor enabled to provide a login code sent to their email address before logging back in.', 'it-l10n-ithemes-security-pro' ) . '<br/><br/>';
						$tooltip .= esc_html__( 'Additionally, it will disable the WP File Editor (which blocks people from editing plugin or theme code), XML-RPC ping backs, and block multiple authentication attempts per XML-RPC request (both of which will make XML-RPC stronger against attacks without having to completely turn it off).', 'it-l10n-ithemes-security-pro' );

						$this->render_tooltip( $tooltip );
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-version-management-scan_for_old_wordpress_sites"><?php esc_html_e( 'Scan For Old WordPress Sites', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<p>
					<?php $form->add_checkbox( 'scan_for_old_wordpress_sites' ); ?>
					<label for="itsec-version-management-scan_for_old_wordpress_sites"><?php esc_html_e( 'Run a daily scan of the hosting account for old WordPress sites that could allow an attacker to compromise the server.', 'it-l10n-ithemes-security-pro' ); ?></label>
					<?php $this->render_tooltip( __( 'This feature will check for outdated WordPress installs on your hosting account. A single outdated WordPress site with a vulnerability could allow attackers to compromise all the other sites on the same hosting account.', 'it-l10n-ithemes-security-pro' ) ); ?>
				</p>
			</td>
		</tr>
	</table>
<?php

	}

	/**
	 * Render the packages form.
	 *
	 * @param ITSEC_Form $form
	 * @param string     $type Either 'plugin' or 'theme'.
	 *
	 * @return void
	 */
	private function render_packages( $form, $type ) {

		if ( $form->get_option( $type === 'plugin' ? 'plugin_automatic_updates' : 'theme_automatic_updates' ) === 'custom' ) {
			$hidden = '';
		} else {
			$hidden = ' hidden';
		}

		$form->add_input_group( 'packages' );
		?>
		<tr id="itsec-version-management-<?php echo esc_attr( $type ); ?>-container" class="itsec-version-management-packages-container<?php echo esc_attr( $hidden ); ?>">
			<td colspan="2">
				<h4><?php $type === 'plugin' ? esc_html_e( 'Select Plugins', 'it-l10n-ithemes-security-pro' ) : esc_html_e( 'Select Themes', 'it-l10n-ithemes-security-pro' ); ?></h4>
				<table class="itsec-vm-app" id="itsec-vm-app--<?php echo esc_attr( $type ); ?>"></table>
			</td>
		</tr>
		<?php

		$form->remove_input_group();
	}

	private function render_tooltip( $text ) {
		/* translators: hover over this text to see the tooltip. */
		$placeholder = __( '?', 'it-l10n-ithemes-security-pro' );

		printf( '<!-- Tooltip --><span class="tooltip"><span class="tooltip-container">%1$s<span class="info"><span class="text">%2$s</span></span></span></span><!-- /Tooltip -->', $placeholder, $text );
	}

	private function add_automatic_update_status_errors() {
		require_once( dirname( __FILE__ ) . '/utility.php' );
		$statuses = ITSEC_VM_Utility::get_automatic_update_statuses();

		$types = array(
			'all'    => esc_html__( 'All Automatic Updates', 'it-l10n-ithemes-security-pro' ),
			'core'   => esc_html__( 'WordPress Automatic Updates', 'it-l10n-ithemes-security-pro' ),
			'plugin' => esc_html__( 'Plugin Automatic Updates', 'it-l10n-ithemes-security-pro' ),
			'theme'  => esc_html__( 'Theme Automatic Updates', 'it-l10n-ithemes-security-pro' ),
		);

		$details = '';

		foreach ( $types as $var => $description ) {
			if ( empty( $statuses[$var] ) ) {
				continue;
			}

			$error_strings = ITSEC_Response::get_error_strings( $statuses[$var] );

			$details .= "<h4>$description</h4>\n";
			$details .= "<ul>\n";

			foreach ( $error_strings as $error_string ) {
				$details .= "<li>$error_string</li>\n";
			}

			$details .= "</ul>\n";
		}

		if ( ! empty( $details ) ) {
			ITSEC_Settings_Page::show_details_toggle( esc_html__( 'Warning: Due to server or site configuration, automatic updates may fail to install automatically if enabled.', 'it-l10n-ithemes-security-pro' ), $details );
		}
	}
}

new ITSEC_Version_Management_Settings_Page();
