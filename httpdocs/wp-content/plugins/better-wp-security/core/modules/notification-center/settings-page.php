<?php

class ITSEC_Notification_Center_Settings_Page extends ITSEC_Module_Settings_Page {

	private $version = 3;

	/** @var ITSEC_Notification_Center_Validator */
	private $validator;

	/** @var array */
	private $last_sent = array();

	public function __construct() {
		$this->id          = 'notification-center';
		$this->title       = __( 'Notification Center', 'better-wp-security' );
		$this->description = __( 'Manage and configure email notifications sent by iThemes Security related to various settings modules.', 'better-wp-security' );
		$this->type        = 'recommended';
		$this->can_save    = true;

		$this->validator = ITSEC_Modules::get_validator( 'notification-center' );

		if ( ITSEC_Modules::get_setting( 'notification-center', 'last_mail_error' ) ) {
			$this->status = 'warning';
		}

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'itsec-notification-center-admin', plugins_url( 'css/settings-page.css', __FILE__ ), array(), $this->version );
		wp_enqueue_script( 'itsec-notification-center-admin', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'itsec-settings-page-script' ), $this->version );
	}

	public function handle_ajax_request( $data ) {

		if ( empty( $data['method'] ) ) {
			return;
		}

		switch ( $data['method'] ) {
			case 'dismiss-mail-error':
				ITSEC_Modules::set_setting( 'notification-center', 'last_mail_error', '' );
				ITSEC_Response::set_success( true );
				break;
		}
	}

	protected function render_description( $form ) {

		?>
		<p><?php esc_html_e( 'Manage and configure email notifications sent by iThemes Security related to various settings modules. If errors are encountered while sending notification emails, they will be reported here..', 'better-wp-security' ); ?></p>
		<?php

	}

	/**
	 * @param ITSEC_Form $form
	 */
	protected function render_settings( $form ) {

		$this->last_sent = ITSEC_Modules::get_setting( 'notification-center', 'last_sent' );

		$this->render_mail_errors();
		?>

		<table class="form-table itsec-settings-section">
			<tbody>
			<tr>
				<th><label for="itsec-notification-center-from_email"><?php esc_html_e( 'From Email', 'better-wp-security' ); ?></label></th>
				<td>
					<?php $form->add_text( 'from_email' ); ?>
					<p class="description">
						<?php esc_html_e( 'iThemes Security will send notifications from this email address. Leave blank to use the WordPress default.', 'better-wp-security' ); ?>
					</p>
				</td>
			</tr>
			<tr class="itsec-email-contacts-setting">
				<th><label for="itsec-notification-center-default_recipients"><?php esc_html_e( 'Default Recipients', 'better-wp-security' ); ?></label></th>
				<td>
					<?php
					$form->add_input_group( 'default_recipients' );
					$this->render_user_list_fieldset( $form, ITSEC_Notification_Center::R_USER_LIST );
					$form->remove_input_group();
					?>
					<p class="description">
						<?php esc_html_e( 'Set the default recipients for any admin-facing notifications.', 'better-wp-security' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<?php

		$notifications = ITSEC_Core::get_notification_center()->get_notifications();
		usort( $notifications, array( $this, 'sort_notifications' ) );

		$form->add_input_group( 'notifications' );
		foreach ( $notifications as $notification ) {
			$this->render_notification_setting( $form, $notification['slug'], $notification );
		}
		$form->remove_input_group();
	}

	protected function render_mail_errors() {
		if ( ! $message = ITSEC_Modules::get_setting( 'notification-center', 'last_mail_error' ) ) {
			return;
		}

		$link = esc_url( ITSEC_Core::get_logs_page_url( 'notification_center', 'error' ) );
		?>
		<div class="itsec-notification-center-mail-errors-container">
			<div class="notice notice-alt notice-error below-h2 itsec-is-dismissible itsec-notification-center-mail-error">
				<?php if ( 'file' !== ITSEC_Modules::get_setting( 'global', 'log_type' ) ): ?>
					<p><?php printf( esc_html__( 'Error while sending notification: %1$s. %2$sView All%3$s.', 'better-wp-security' ), $message, "<a href=\"{$link}\">", '</a>' ); ?></p>
				<?php else: ?>
					<p><?php printf( esc_html__( 'Error while sending notification: %1$s.', 'better-wp-security' ), $message ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * @param ITSEC_Form $form
	 * @param string     $slug
	 * @param array      $config
	 */
	protected function render_notification_setting( $form, $slug, $config ) {
		$strings = ITSEC_Core::get_notification_center()->get_notification_strings( $slug );

		$form->add_input_group( $slug );
		?>

		<div class="itsec-notification-center-notification-settings" id="itsec-notification-center-notification-settings--<?php echo esc_attr( $slug ); ?>">
			<h4><?php echo $strings['label']; ?></h4>
			<?php if ( ! empty( $strings['description'] ) ): ?>
				<p class="description"><?php echo $strings['description']; ?></p>
			<?php endif; ?>

			<table class="form-table itsec-settings-section" id="itsec-notification-center-notification-<?php echo esc_attr( $slug ); ?>">

				<?php if ( ! empty( $config['optional'] ) ): ?>
					<tr class="itsec-notification-center-enable-notification">
						<th><label for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-enabled"><?php esc_html_e( 'Enabled', 'better-wp-security' ); ?></label></th>
						<td><?php $form->add_checkbox( 'enabled', array( 'data-slug' => $slug ) ); ?></td>
					</tr>
				<?php endif; ?>

				<?php if ( ! empty( $config['subject_editable'] ) ) :
					$form->get_option( 'subject' ) ? '' : $form->set_option( 'subject', $strings['subject'] ); ?>
					<tr>
						<th><label for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-subject"><?php esc_html_e( 'Subject', 'better-wp-security' ); ?></label></th>
						<td><?php $form->add_text( 'subject' ); ?></td>
					</tr>
				<?php endif; ?>

				<?php if ( ! empty( $config['message_editable'] ) ) :
					$form->get_option( 'message' ) ? '' : $form->set_option( 'message', $strings['message'] ); ?>
					<tr>
						<th><label for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-message"><?php esc_html_e( 'Message', 'better-wp-security' ); ?></label></th>
						<td>
							<?php $form->add_textarea( 'message' ); ?>

							<p class="description">
								<?php echo wp_sprintf( esc_html__( 'You can use HTML in your message. Allowed HTML includes: %l.', 'better-wp-security' ), array_keys( $this->validator->get_allowed_html() ) ); ?>

								<?php if ( ! empty( $config['tags'] ) ) : ?>
										<?php printf( esc_html__( 'This notification supports email tags. Tags are formatted as follows %s.', 'better-wp-security' ), '<code>{{ $tag_name }}</code>' ); ?>
								<?php endif; ?>
							</p>

							<?php if ( ! empty( $config['tags'] ) ) : ?>
								<dl class="itsec-notification-center-tags">
									<?php foreach( $strings['tags'] as $tag => $description ): ?>
										<dt><?php echo esc_html( $tag ); ?></dt>
										<dd><?php echo $description; // Already escaped. ?></dd>
									<?php endforeach; ?>
								</dl>
							<?php endif; ?>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( is_array( $config['schedule'] ) ): ?>
					<tr>
						<th><label for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-schedule"><?php esc_html_e( 'Schedule', 'better-wp-security' ); ?></label></th>
						<td>
							<?php $form->add_select( 'schedule', $this->validator->get_schedule_options( $config['schedule'] ) ); ?>
							<p class="description">
								<?php if ( empty( $this->last_sent[ $slug ] ) ): ?>
									<?php esc_html_e( 'Not yet sent.', 'better-wp-security' ); ?>
								<?php else: ?>
									<?php printf( esc_html__( 'Last sent on %s', 'better-wp-security' ), ITSEC_Lib::date_format_i18n_and_local_timezone( $this->last_sent[ $slug ] ) ); ?>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				<?php endif; ?>

				<?php switch( $config['recipient'] ) :
					case ITSEC_Notification_Center::R_USER: ?>
						<tr>
							<th><?php esc_html_e( 'Recipient', 'better-wp-security' ); ?></th>
							<td><em><?php esc_html_e( 'Site Users', 'better-wp-security' ); ?></em></td>
						</tr>
					<?php break; ?>

					<?php case ITSEC_Notification_Center::R_ADMIN: ?>
						<tr>
							<th><?php esc_html_e( 'Recipient', 'better-wp-security' ); ?></th>
							<td><em><?php esc_html_e( 'Admin Emails', 'better-wp-security' ); ?></em></td>
						</tr>
					<?php break; ?>

					<?php case ITSEC_Notification_Center::R_PER_USE: ?>
						<tr>
							<th><?php esc_html_e( 'Recipient', 'better-wp-security' ); ?></th>
							<td><em><?php esc_html_e( 'Specified when sending', 'better-wp-security' ); ?></em></td>
						</tr>
					<?php break; ?>

					<?php case ITSEC_Notification_Center::R_EMAIL_LIST: ?>
						<tr>
							<th><label for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-email_list"><?php esc_html_e( 'Recipient', 'better-wp-security' ); ?></label></th>
							<td>
								<?php $form->add_textarea( 'email_list', array( 'class' => 'textarea-small' ) ); ?>
								<p class="description"><?php _e( 'The email address(es) this notification will be sent to. One address per line.', 'better-wp-security' ); ?></p>
							</td>
						</tr>
						<?php break; ?>

					<?php case ITSEC_Notification_Center::R_USER_LIST: case ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE: ?>
						<?php $this->render_user_list( $slug, $form, $config['recipient'] ); ?>
					<?php break; ?>

				<?php endswitch; ?>
			</table>
		</div>
		<?php
		$form->remove_input_group();
	}

	/**
	 * Render the User List form.
	 *
	 * @param string     $slug Notification slug.
	 * @param ITSEC_Form $form
	 * @param string     $type
	 */
	protected function render_user_list( $slug, $form, $type ) {
		?>

		<tr class="itsec-email-contacts-setting">
			<th><?php esc_html_e( 'Recipient', 'better-wp-security' ); ?></th>
			<td>
				<?php $form->add_select( 'recipient_type', array(
						'class' => 'itsec-notification-center-user-list-type',
						'value' => array(
							'default' => esc_html__( 'Default Recipients', 'better-wp-security' ),
							'custom'  => esc_html__( 'Custom', 'better-wp-security' )
						),
					)
				); ?>
				<div class="<?php 'default' === $form->get_option( 'recipient_type' ) ? print( 'hidden' ) : null; ?>">
					<?php $this->render_user_list_fieldset( $form, $type ); ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render the User List fieldset control.
	 *
	 * @param ITSEC_Form $form
	 * @param string $type
	 */
	private function render_user_list_fieldset( $form, $type ) {

		$users_and_roles = $this->validator->get_available_admin_users_and_roles();

		$users = $users_and_roles['users'];
		$roles = $users_and_roles['roles'];

		natcasesort( $users );

		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Recipients for this email.', 'better-wp-security' ); ?></legend>
			<p><?php esc_html_e( 'Select which users should be emailed.', 'better-wp-security' ); ?></p>

			<ul>
				<?php foreach ( $roles as $role => $name ) : ?>
					<li>
						<label>
							<?php $form->add_multi_checkbox( 'user_list', $role ); ?>
							<?php echo esc_html( sprintf( _x( 'All %s users', 'role', 'better-wp-security' ), $name ) ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<ul>
				<?php foreach ( $users as $id => $name ) : ?>
					<li>
						<label>
							<?php $form->add_multi_checkbox( 'user_list', $id ); ?>
							<?php echo esc_html( $name ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE === $type && $form->get_option( 'previous_emails' ) ): ?>

				<div class="itsec-notification-center--deprecated-recipients">
					<span><?php esc_html_e( 'Deprecated Recipients', 'better-wp-security' ); ?></span>
					<p class="description">
						<?php esc_html_e( 'The following email recipients are deprecated. Please create new users for these email addresses or remove them.', 'better-wp-security' ); ?>
					</p>
					<ul>
						<?php foreach ( $form->get_option( 'previous_emails' ) as $email ): ?>
							<li>
								<label>
									<?php $form->add_multi_checkbox( 'previous_emails', $email ); ?>
									<?php echo esc_html( $email ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</fieldset>

		<?php
	}

	private function sort_notifications( $a, $b ) {

		$a_s = ITSEC_Core::get_notification_center()->get_notification_strings( $a['slug'] );
		$b_s = ITSEC_Core::get_notification_center()->get_notification_strings( $b['slug'] );

		return strcmp( $a_s['label'], $b_s['label'] );
	}
}

new ITSEC_Notification_Center_Settings_Page();