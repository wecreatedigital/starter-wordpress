<?php

/**
 * Class ITSEC_Password_Requirements_Settings_Page
 */
class ITSEC_Password_Requirements_Settings_Page extends ITSEC_Module_Settings_Page {

	/**
	 * ITSEC_Password_Requirements_Settings_Page constructor.
	 */
	public function __construct() {
		$this->id          = 'password-requirements';
		$this->title       = __( 'Password Requirements', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'Manage and configure Password Requirements for users.', 'it-l10n-ithemes-security-pro' );
		$this->can_save    = true;

		parent::__construct();
	}

	protected function render_description( $form ) {
		?>
		<p><?php esc_html_e( 'Manage and configure Password Requirements for users.', 'it-l10n-ithemes-security-pro' ); ?></p>
		<?php
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'itsec-password-requirements-settings-page', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), ITSEC_Core::get_plugin_build() );
		wp_enqueue_style( 'itsec-password-requirements-settings-page', plugins_url( 'css/settings-page.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );
		
		do_action( 'itsec_password_requirements_enqueue_scripts_and_styles' );
	}

	public function handle_ajax_request( $data ) {
		if ( ! isset( $data['password_requirement'] ) ) {
			return;
		}

		/**
		 * Fires when Password Requirement ajax request is incoming.
		 *
		 * The dynamic portion of the hook, $data['password_requirement'] refers to the reason code of the requirement.
		 *
		 * @param array $data
		 */
		do_action( 'itsec_password_requirements_ajax_' . $data['password_requirement'], $data );
	}

	/**
	 * Render settings.
	 *
	 * @param ITSEC_Form $form
	 */
	protected function render_settings( $form ) {

		$requirements = ITSEC_Lib_Password_Requirements::get_registered();
		?>

		<?php do_action( 'itsec_password_requirements_settings_before', $form ); ?>

		<div class="itsec-password-requirements-settings">
			<?php do_action( 'itsec_password_requirements_settings_begin', $form ); ?>
			<?php foreach ( $requirements as $code => $requirement ):

				if ( null === $requirement['settings_config'] ) {
					continue;
				}

				$config = call_user_func( $requirement['settings_config'] );

				$form->add_input_group( 'enabled_requirements' );
				?>
				<div class="itsec-settings-section itsec-password-requirements-container itsec-password-requirements-container--<?php echo esc_attr( $code ); ?>"
					 data-code="<?php echo esc_attr( $code ) ?>">
					<h4><?php echo esc_html( isset( $config['label'] ) ? $config['label'] : $code ); ?></h4>

					<?php if ( ! empty( $config['description'] ) ): ?>
						<p class="description"><?php echo $config['description']; ?></p>
					<?php endif; ?>

					<table class="form-table">
						<thead class="itsec-password-requirements-container__enabled-wrap itsec-password-requirements-container__enabled-wrap--<?php echo esc_attr( $code ); ?>">
						<tr>
							<th scope="row">
								<label for="itsec-password-requirements-enabled_requirements-<?php echo esc_attr( $code ); ?>">
									<?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ); ?>
								</label>
							</th>
							<td><?php $form->add_checkbox( $code ); ?></td>
						</tr>
						</thead>
						<?php
						$form->remove_input_group();

						if ( ! empty( $config['render'] ) ) :
							$form->add_input_group( 'requirement_settings', $code );
							?>
							<tbody class="itsec-password-requirements-container__settings-wrap itsec-password-requirements-container__settings-wrap--<?php echo esc_attr( $code ); ?>">
							<?php call_user_func( $config['render'], $form ) ?>
							</tbody>
							<?php
							$form->remove_input_group();
							$form->remove_input_group();
						endif; ?>
					</table>
				</div>
			<?php endforeach; ?>
			<?php do_action( 'itsec_password_requirements_settings_end', $form ); ?>
		</div>
		<?php do_action( 'itsec_password_requirements_settings_after', $form ); ?>
		<?php

	}
}

new ITSEC_Password_Requirements_Settings_Page();