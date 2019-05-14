<?php

class ITSEC_Dashboard_Settings_Page extends ITSEC_Module_Settings_Page {

	public function __construct() {
		parent::__construct();

		$this->id          = 'dashboard';
		$this->title       = __( 'Security Dashboard', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'See a real-time overview of the security activity on your website with this dynamic dashboard.', 'it-l10n-ithemes-security-pro' );
		$this->pro         = true;
	}

	public function register() {
		if ( current_user_can( 'itsec_create_dashboards' ) || ! ITSEC_Modules::is_active( $this->id ) ) {
			parent::register();
		}
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'itsec-dashboard-admin', plugins_url( 'css/settings-page.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );
	}

	protected function render_description( $form ) {
		echo '<p>' . $this->description . '</p>';
	}

	protected function render_settings( $form ) {

		$users = ITSEC_Modules::get_validator( $this->id )->get_users();
		?>

		<p><a href="<?php echo esc_url( network_admin_url( 'index.php?page=itsec-dashboard' ) ) ?>"><?php esc_html_e( 'View Security Dashboard', 'it-l10n-ithemes-security-pro' ); ?></a></p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="itsec-dashboard-disabled_users">
						<?php esc_html_e( 'Disable Creating Dashboards for Users', 'it-l10n-ithemes-security-pro' ) ?>
					</label>
				</th>
				<td>
					<p class="description">
						<?php esc_html_e( 'By default, any user who can manage iThemes Security can create dashboards.', 'it-l10n-ithemes-security-pro' ) ?>&nbsp;
						<?php esc_html_e( 'Prevent the selected users below from creating dashboards and from viewing/editing Security Dashboard settings.', 'it-l10n-ithemes-security-pro' ); ?>
					</p>
					<ul>
						<?php foreach ( $users as $id => $name ): ?>
							<li>
								<label>
									<?php $form->add_multi_checkbox( 'disabled_users', $id ); ?>
									<?php echo esc_html( $name ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</td>
			</tr>
		</table>

		<?php
	}
}

new ITSEC_Dashboard_Settings_Page();
