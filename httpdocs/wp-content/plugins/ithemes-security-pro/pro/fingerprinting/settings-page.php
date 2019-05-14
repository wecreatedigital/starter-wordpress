<?php

/**
 * Class ITSEC_Fingerprinting_Settings_Page
 */
class ITSEC_Fingerprinting_Settings_Page extends ITSEC_Module_Settings_Page {

	/**
	 * ITSEC_Fingerprinting_Settings_Page constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id          = 'fingerprinting';
		$this->title       = __( 'Trusted Devices (Beta)', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'Trusted Devices identifies the devices users use to login and can apply additional restrictions to unknown devices.', 'it-l10n-ithemes-security-pro' );
		$this->pro         = true;

		add_action( 'itsec_notification_center_unrecognized-login_notification_enabled', array( $this, 'maybe_refresh' ) );
		add_action( 'itsec_notification_center_unrecognized-login_notification_disabled', array( $this, 'maybe_refresh' ) );
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_script( 'itsec-fingerprinting-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'itsec-util' ), ITSEC_Core::get_plugin_build(), true );
		wp_enqueue_style( 'itsec-fingerprinting-style', plugins_url( 'css/settings-page.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );
	}

	public function handle_ajax_request( $data ) {
		switch ( isset( $data['method'] ) ? $data['method'] : '' ) {
			case 'download':
				require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );
				require_once( dirname( dirname( __FILE__ ) ) . '/geolocation/geolocators/class-itsec-geolocator-maxmind-db.php' );
				$response = ITSEC_Geolocator_MaxMind_DB::download();

				if ( is_wp_error( $response ) ) {
					ITSEC_Response::add_error( $response );
				} else {
					ITSEC_Response::add_message( esc_html__( 'MaxMind DB successfully downloaded.', 'it-l10n-ithemes-security-pro' ) );
					ITSEC_Response::set_success( true );
				}
				break;
			default:
				ITSEC_Response::add_error( esc_html__( 'Unknown Ajax Method', 'it-l10n-ithemes-security-pro' ) );
				break;
		}
	}

	public function maybe_refresh() {
		if ( ITSEC_Core::is_interactive() ) {
			ITSEC_Response::reload_module( $this->id );
		}
	}

	protected function render_description( $form ) {
		echo '<p>';
		echo esc_html( $this->description );
		echo '</p>';
		echo '<p>';
		printf( esc_html__( '%1$sNote:%2$s By default, users will receive a notification in the admin bar about pending unrecognized devices, but we strongly recommend also enabling the "Unrecognized Login Notification" email in the Notification Center. Trusted Devices also powers the "Remember Device" setting in Two-Factor Authentication.', 'it-l10n-ithemes-security-pro' ), '<strong>', '</strong>' );
		echo '</p>';
	}

	protected function render_settings( $form ) {
		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );
		require_once( dirname( dirname( __FILE__ ) ) . '/geolocation/geolocators/class-itsec-geolocator-maxmind-db.php' );

		$maxmind = new ITSEC_Geolocator_MaxMind_DB();
		$has_db  = $maxmind->is_available();

		$notification_enabled = ITSEC_Core::get_notification_center()->is_notification_enabled( 'unrecognized-login' );
		?>

		<table class="form-table itsec-settings-section">
			<tbody>
			<tr>
				<th><label for="itsec-fingerprinting-role"><?php esc_html_e( 'Minimum Role', 'it-l10n-ithemes-security-pro' ) ?></label></th>
				<td>
					<?php $form->add_canonical_roles( 'role' ); ?>
					<p class="description">
						<?php esc_html_e( 'Enable Trusted Devices for users with the selected minimum role.', 'it-l10n-ithemes-security-pro' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="itsec-fingerprinting-restrict_capabilities"><?php esc_html_e( 'Restrict Capabilities', 'it-l10n-ithemes-security-pro' ) ?></label></th>
				<td>
					<?php if ( $notification_enabled ): ?>
						<?php $form->add_checkbox( 'restrict_capabilities' ); ?>
					<?php else: ?>
						<span class="tooltip-trigger-only">
							<?php $form->add_checkbox( 'restrict_capabilities', array( 'disabled' => true ) ); ?>
							<span class="tooltip-container">
								<span class="info">
									<span class="text">
										<?php printf(
											esc_html__( 'Enabling "Restrict Capabilities" requires the %1$s"Unrecognized Login" notification%2$s to be enabled.', 'it-l10n-ithemes-security-pro' ),
											'<a href="#itsec-notification-center-notification-settings--unrecognized-login" data-module-link="notification-center">',
											'</a>'
										); ?>
									</span>
								</span>
							</span>
						</span>
					<?php endif; ?>
					<label for="itsec-fingerprinting-restrict_capabilities"><?php esc_html_e( 'Restrict Capabilities on Unrecognized Sessions', 'it-l10n-ithemes-security-pro' ) ?></label>
					<p class="description">
						<?php esc_html_e( 'When a user is logged-in on an unrecognized device, restrict their administrator-level capabilities and prevent them from editing their login details.', 'it-l10n-ithemes-security-pro' ); ?>
						<?php printf(
							esc_html__( 'Enabling "Restrict Capabilities" requires the %1$s"Unrecognized Login" notification%2$s to be enabled.', 'it-l10n-ithemes-security-pro' ),
							'<a href="#itsec-notification-center-notification-settings--unrecognized-login" data-module-link="notification-center">',
							'</a>'
						); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="itsec-fingerprinting-session_hijacking_protection"><?php esc_html_e( 'Session Hijacking Protection', 'it-l10n-ithemes-security-pro' ) ?></label></th>
				<td>
					<?php $form->add_checkbox( 'session_hijacking_protection' ); ?>
					<label for="itsec-fingerprinting-session_hijacking_protection"><?php esc_html_e( 'Session Hijacking Protection', 'it-l10n-ithemes-security-pro' ) ?></label>
					<p class="description">
						<?php esc_html_e( "Help protect against session hijacking by checking that a user's device does not change during a session.", 'it-l10n-ithemes-security-pro' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<hr>

		<h4><?php esc_html_e( 'Geolocation', 'it-l10n-ithemes-security-pro' ); ?></h4>
		<p class="description">
			<?php esc_html_e( 'iThemes Security uses geolocation to improve the accuracy of Trusted Device identification. By default, a number of free GeoIP services are used. We strongly recommend enabling one of the MaxMind APIs.', 'it-l10n-ithemes-security-pro' ); ?>
		</p>

		<h5><?php esc_html_e( 'MaxMind DB', 'it-l10n-ithemes-security-pro' ); ?></h5>
		<p><?php esc_html_e( 'The MaxMind DB is a free database provided by MaxMind that allows for Geolocation lookups without connecting to an external API.', 'it-l10n-ithemes-security-pro' ) ?></p>
		<p><?php printf(
				esc_html__( 'Click the button below to automatically download the database or %1$smanually download it%2$s and upload the entire zip\'s contents to the following directory via (S)FTP: %3$s. You may want to exclude this directory from your backups.', 'it-l10n-ithemes-security-pro' ),
				'<a href="' . esc_url( ITSEC_Geolocator_MaxMind_DB::URL ) . '">',
				'</a>',
				'<code>' . ITSEC_Geolocator_MaxMind_DB::get_db_path() . '</code>'
			); ?>
		</p>
		<p id="itsec-fingerprinting-maxmind-db-download-container" class="<?php $has_db and print( 'itsec-fingerprinting-maxmind-db-downloaded' ); ?>">
			<button type="button" id="itsec-fingerprinting-download" class="button" <?php $has_db and print( 'disabled' ); ?>><?php esc_html_e( 'Download DB', 'it-l10n-ithemes-security-pro' ) ?></button>
			<span class="description itsec-fingerprinting-maxmind-db-download-downloaded-message">
				<?php esc_html_e( 'The MaxMind DB has been downloaded.', 'it-l10n-ithemes-security-pro' ); ?>
			</span>
			<span class="description itsec-fingerprinting-maxmind-db-download-warning-message">
				<?php esc_html_e( 'The download may take a few moments (27MB).', 'it-l10n-ithemes-security-pro' ); ?>
			</span>
		</p>

		<div id="itsec-fingerprinting-maxmind-db-status"></div>

		<h5><?php esc_html_e( 'MaxMind API', 'it-l10n-ithemes-security-pro' ) ?></h5>
		<p><?php printf(
				esc_html__( 'Alternately, or for the highest degree of accuracy, sign up for a %1$sMaxMind GeoIP2 Precision: City%2$s account. Most users should find the lowest credit amount sufficient.', 'it-l10n-ithemes-security-pro' ),
				'<a href="https://www.maxmind.com/en/geoip2-precision-city-service">',
				'</a>'
			); ?>
		</p>

		<div class="itsec-fingerprinting-maxmind-api-settings">
			<div class="itsec-fingerprinting-maxmind-api-settings__field">
				<label for="itsec-fingerprinting-maxmind_api_user"><?php esc_html_e( 'MaxMind API User', 'it-l10n-ithemes-security-pro' ) ?></label>
				<?php $form->add_text( 'maxmind_api_user' ); ?>
			</div>

			<div class="itsec-fingerprinting-maxmind-api-settings__field">
				<label for="itsec-fingerprinting-maxmind_api_key"><?php esc_html_e( 'MaxMind API Key', 'it-l10n-ithemes-security-pro' ) ?></label>
				<?php $form->add_text( 'maxmind_api_key' ); ?>
			</div>
		</div>

		<p class="description">
			<?php printf(
				esc_html__( 'The MaxMind API User and API Key can be found in the "Services > My License Key" section of the %1$saccount area%2$s.', 'it-l10n-ithemes-security-pro' ),
				'<a href="https://www.maxmind.com/en/account">',
				'</a>'
			); ?>
		</p>

		<hr>

		<h4><?php esc_html_e( 'Static Image Map API', 'it-l10n-ithemes-security-pro' ); ?></h4>
		<p class="description">
			<?php printf(
				esc_html__( 'iThemes Security uses static image maps to display the approximate location of an unrecognized login. We recommend using either the %1$sMapbox%3$s or %2$sMapQuest%3$s APIs. The free plan for both services should be sufficient for most users.', 'it-l10n-ithemes-security-pro' ),
				'<a href="https://www.mapbox.com">',
				'<a href="https://developer.mapquest.com">',
				'</a>'
			); ?>
		</p>
		<table class="form-table itsec-settings-section itsec-fingerprinting-static-image-map-api-settings">
			<tbody>
			<tr>
				<th><label for="itsec-fingerprinting-mapbox_access_token"><?php esc_html_e( 'Mapbox API Key', 'it-l10n-ithemes-security-pro' ) ?></label></th>
				<td>
					<?php $form->add_text( 'mapbox_access_token' ); ?>
					<p class="description">
						<?php printf(
							esc_html__( 'The MapBox Access Token can be found on the %1$sMapBox account page%2$s. Either provide the "Default public token" or create a new token with the %3$s scope.', 'it-l10n-ithemes-security-pro' ),
							'<a href="https://www.mapbox.com/account/">',
							'</a>',
							'<code>styles:tiles</code>'
						) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="itsec-fingerprinting-mapquest_api_key"><?php esc_html_e( 'MapQuest API (Consumer) Key', 'it-l10n-ithemes-security-pro' ) ?></label></th>
				<td>
					<?php $form->add_text( 'mapquest_api_key' ); ?>
					<p class="description">
						<?php printf(
							esc_html__( 'The MapQuest API Key can typically be found on the %1$sMapQuest Profile Page%2$s.', 'it-l10n-ithemes-security-pro' ) . ' ' .
							esc_html__( 'If there is no key listed under the "My Keys" section, create a new application by clicking the "Manage Keys" button and then the "Create a New Key" button. Enter the name of your website for the "App Name" and leave the "Callback URL" blank.', 'it-l10n-ithemes-security-pro' ),
							'<a href="https://developer.mapquest.com/user/">',
							'</a>'
						); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}
}

new ITSEC_Fingerprinting_Settings_Page();