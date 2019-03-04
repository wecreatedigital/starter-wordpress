<?php

/**
 * Class ITSEC_Notification_Center_Debug
 */
class ITSEC_Notification_Center_Debug {

	public function __construct() {
		add_action( 'itsec_debug_page', array( $this, 'render' ) );
		add_action( 'itsec_debug_page_enqueue', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'itsec_debug_module_request_notification-center', array( $this, 'handle_ajax_request' ) );
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'itsec-notification-center-debug', plugins_url( 'js/debug.js', __FILE__ ), array( 'itsec-util' ), ITSEC_Core::get_plugin_build() );
	}

	public function handle_ajax_request( $data ) {
		if ( empty( $data['id'] ) ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-debug-page-notification-center-missing-id', __( 'The server did not receive a valid request. The notification id is missing.', 'better-wp-security' ) ) );

			return;
		}

		$result = ITSEC_Core::get_notification_center()->send_scheduled_notifications( array( $data['id'] ), ! empty( $data['silent'] ) );

		if ( is_wp_error( $result ) ) {
			ITSEC_Response::add_error( $result );
		} elseif ( ! $result ) {
			ITSEC_Response::add_error( new WP_Error( 'itsec-debug-page-notification-center-send-failed', __( 'The server could not send the requested notification.', 'better-wp-security' ) ) );
		} else {
			ITSEC_Response::set_response( $this->get_table() );
			ITSEC_Response::set_success( true );
			ITSEC_Response::add_message( __( 'Notification sent.', 'better-wp-security' ) );
		}
	}

	/**
	 * Render our data to the Debug Page.
	 */
	public function render() {
		?>

		<div id="itsec-notification-center-notifications">
			<h2><?php esc_html_e( 'Notification Center', 'better-wp-security' ); ?></h2>
			<?php echo $this->get_table(); ?>
		</div>

		<?php
	}

	private function get_table() {
		$nc = ITSEC_Core::get_notification_center();
		ob_start();
		?>
		<table class="widefat striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'better-wp-security' ) ?></th>
				<th><?php esc_html_e( 'Last Sent', 'better-wp-security' ) ?></th>
				<th><?php esc_html_e( 'Next Send', 'better-wp-security' ) ?></th>
				<th><?php esc_html_e( 'Schedule', 'better-wp-security' ) ?></th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $nc->get_notifications() as $slug => $notification ) : $scheduled = ITSEC_Notification_Center::S_NONE !== $notification['schedule']; ?>
				<tr>
					<td><?php echo esc_html( $slug ); ?></td>
					<td><?php echo $scheduled ? date( 'Y-m-d H:i:s', $nc->get_last_sent( $slug ) ) : '–'; ?></td>
					<td><?php echo $scheduled && ( $next = $nc->get_next_send_time( $slug ) ) ? date( 'Y-m-d H:i:s', $next ) : '–'; ?></td>
					<td><?php echo $nc->get_schedule( $slug ); ?></td>
					<td>
						<?php if ( $scheduled && ( ! is_array( $notification['schedule'] ) || empty( $notification['schedule']['setting_only'] ) ) ): ?>
							<button class="button itsec__send-notification itsec__send-notification--force" data-id="<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Force', 'better-wp-security' ) ?>
							</button>
							<button class="button itsec__send-notification itsec__send-notification--silent" data-id="<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Silent', 'better-wp-security' ) ?>
							</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}
}

new ITSEC_Notification_Center_Debug();