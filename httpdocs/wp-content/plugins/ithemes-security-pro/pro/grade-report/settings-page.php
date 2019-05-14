<?php

/**
 * Class ITSEC_Grading_System_Settings_Page
 */
class ITSEC_Grading_System_Settings_Page extends ITSEC_Module_Settings_Page {

	private $version = 1;

	public function __construct() {
		$this->id          = 'grade-report';
		$this->title       = __( 'Grade Report', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'See your WordPress security grade and fix issues.', 'it-l10n-ithemes-security-pro' );
		$this->type        = 'recommended';
		$this->pro         = true;

		parent::__construct();
	}

	public function register() {

		if ( ! in_array( get_current_user_id(), ITSEC_Modules::get_setting( $this->id, 'disabled_users' ), false ) ) {
			parent::register();
		}
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'itsec-grade-report-admin', plugins_url( 'css/settings-page.css', __FILE__ ), array(), $this->version );
	}

	protected function render_description( $form ) {

		?>
		<p><?php echo $this->description; ?></p>
		<?php

	}

	protected function render_settings( $form ) {

		$users = ITSEC_Modules::get_validator( $this->id )->get_users();

		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="itsec-grade-report-disabled_users">
						<?php _e( 'Disable for Users', 'it-l10n-ithemes-security-pro' ); ?>
					</label>
				</th>
				<td>
					<p class="description"><?php esc_html_e( 'Disable the grade report for selected users.', 'it-l10n-ithemes-security-pro' ); ?></p>
					<ul>
						<?php foreach ( $users as $id => $name ) : ?>
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

new ITSEC_Grading_System_Settings_Page();