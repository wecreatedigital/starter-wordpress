<?php

final class ITSEC_SSL_Settings_Page extends ITSEC_Module_Settings_Page {
	private $script_version = 3;


	public function __construct() {
		$this->id = 'ssl';
		$this->title = __( 'SSL', 'better-wp-security' );
		$this->description = __( 'Configure use of SSL to ensure that communications between browsers and the server are secure.', 'better-wp-security' );
		$this->type = 'recommended';

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		$vars = array(
			'translations' => array(
				'ssl_warning' => __( 'Are you sure you want to enable SSL? If your server does not support SSL you will be locked out of your WordPress Dashboard.', 'better-wp-security' ),
			),
		);

		wp_enqueue_script( 'itsec-ssl-admin-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'itsec-ssl-admin-script', 'itsec_ssl', $vars );
	}

	protected function render_description( $form ) {

?>
	<p><?php echo wp_kses( __( 'SSL is an important feature for every site. It protects user accounts from being compromised, protects the content from modifications by ISPs and attackers, protects potentially-sensitive information submitted to the site from <a href="https://en.wikipedia.org/wiki/Packet_analyzer">network sniffing</a>, could speed up performance of your site (depending on server configuration), and could improve your site\'s <a href="https://webmasters.googleblog.com/2014/08/https-as-ranking-signal.html">search engine rankings</a>.', 'better-wp-security' ), array( 'a' => array( 'href' => array() ) ) ); ?></p>
<?php

	}

	protected function render_settings( $form ) {
		$ssl_support_probability = ITSEC_Lib::get_ssl_support_probability();

		$settings = ITSEC_Modules::get_settings( 'ssl' );
		$ssl_is_enabled = false;

		if ( 'enabled' === $settings['require_ssl'] || ( 'advanced' === $settings['require_ssl'] && $settings['admin'] ) ) {
			$ssl_is_enabled = true;
		}

		$require_ssl_options = array(
			'disabled' => esc_html__( 'Disabled', 'better-wp-security' ),
			'enabled'  => esc_html__( 'Enabled', 'better-wp-security' ),
			'advanced' => esc_html__( 'Advanced', 'better-wp-security' ),
		);

		if ( 100 === $ssl_support_probability ) {
			$require_ssl_options['enabled'] = esc_html( 'Enabled (recommended)', 'better-wp-security' );
		}

		$frontend_modes = array(
			0 => esc_html__( 'Off', 'better-wp-security' ),
			1 => esc_html__( 'Per Content', 'better-wp-security' ),
			2 => esc_html__( 'Whole Site', 'better-wp-security' ),
		);

		if ( 'advanced' === $settings['require_ssl'] ) {
			$hide_advanced_setting = '';
		} else {
			$hide_advanced_setting = ' style="display:none;"';
		}

?>
	<?php if ( 100 === $ssl_support_probability ) : ?>
		<div class="inline notice notice-success notice-alt"><p><?php esc_html_e( 'Your site appears to support SSL. It is highly recommended that you select the "Enabled" setting below. This redirects all http traffic to your site to the https address, thus requiring everyone to access the site via SSL. In other words, it will force everyone to use a secure connection to the site.', 'better-wp-security' ); ?></p></div>
	<?php elseif ( $ssl_support_probability > 0 ) : ?>
		<div class="inline notice notice-warning notice-alt"><p><?php esc_html_e( 'Your site might support SSL. If the site is configured with a valid certificate that is not self-signed, it is highly recommended that you select the "Enabled" setting below. This redirects all http traffic to your site to the https address, thus requiring everyone to access the site via SSL. In other words, it will force everyone to use a secure connection to the site.', 'better-wp-security' ); ?></p></div>
	<?php else : ?>
		<div class="inline notice notice-error notice-alt"><p><?php esc_html_e( 'Your site does not appear to support SSL. Only enable SSL if you know that the site properly supports SSL since enabling it on a site that does not properly support it will block all access to the site.', 'better-wp-security' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $ssl_is_enabled && ! is_ssl() ) : ?>
		<div class="inline notice notice-info notice-alt"><p><?php esc_html_e( 'Note: After enabling this feature, you will be logged out and you will have to log back in. This is to prevent possible cookie conflicts that could make it more difficult to get in otherwise.', 'better-wp-security' ); ?></p></div>
	<?php endif; ?>

	<table class="form-table itsec-settings-section">
		<tr>
			<th scope="row"><label for="itsec-ssl-require_ssl"><?php esc_html_e( 'Redirect All HTTP Page Requests to HTTPS', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_select( 'require_ssl', $require_ssl_options ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Disabled</strong> - Use the site\'s default handling of page requests.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Enabled</strong> - Redirect all http page requests to https.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Advanced</strong> - Choose different settings for front-end and dashboard page requests.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</td>
		</tr>
		<tr class="itsec-ssl-advanced-setting"<?php echo $hide_advanced_setting; ?>>
			<th scope="row"><label for="itsec-ssl-frontend"><?php esc_html_e( 'Front End SSL Mode', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_select( 'frontend', $frontend_modes ); ?>
				<p class="description"><?php esc_html_e( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you to turn on SSL for selected pages or posts. Selecting "Whole Site" will force the whole site to use SSL.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr class="itsec-ssl-advanced-setting"<?php echo $hide_advanced_setting; ?>>
			<th scope="row"><label for="itsec-ssl-admin"><?php esc_html_e( 'SSL for Dashboard', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'admin' ); ?>
				<label for="itsec-ssl-admin"><?php esc_html_e( 'Force SSL for Dashboard', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'Forces all dashboard access to be served only over an SSL connection.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
	</table>
<?php

	}
}

new ITSEC_SSL_Settings_Page();
