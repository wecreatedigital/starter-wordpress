<?php

final class ITSEC_WordPress_Tweaks_Settings_Page extends ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'wordpress-tweaks';
		$this->title = __( 'WordPress Tweaks', 'better-wp-security' );
		$this->description = __( 'Advanced settings that improve security by changing default WordPress behavior.', 'better-wp-security' );
		$this->type = 'recommended';

		parent::__construct();
	}

	protected function render_description( $form ) {

?>
	<p><?php esc_html_e( 'These are advanced settings that may be utilized to further strengthen the security of your WordPress site.', 'better-wp-security' ); ?></p>
<?php

	}

	protected function render_settings( $form ) {
		$settings = $form->get_options();


		$xmlrpc_options = array(
			'2' => __( 'Disable XML-RPC (recommended)', 'better-wp-security' ),
			'1' => __( 'Disable Pingbacks', 'better-wp-security' ),
			'0' => __( 'Enable XML-RPC', 'better-wp-security' ),
		);

		$allow_xmlrpc_multiauth_options = array(
			false => __( 'Block (recommended)', 'better-wp-security' ),
			true  => __( 'Allow', 'better-wp-security' ),
		);

		$rest_api_options = array(
			'restrict-access' => esc_html__( 'Restricted Access (recommended)', 'better-wp-security' ),
			'default-access'  => esc_html__( 'Default Access', 'better-wp-security' ),
		);

		$valid_user_login_types = array(
			'both'     => esc_html__( 'Email Address and Username (default)', 'better-wp-security' ),
			'email'    => esc_html__( 'Email Address Only', 'better-wp-security' ),
			'username' => esc_html__( 'Username Only', 'better-wp-security' ),
		);

?>
	<p><?php esc_html_e( 'Note: These settings are listed as advanced because they block common forms of attacks but they can also block legitimate plugins and themes that rely on the same techniques. When activating the settings below, we recommend enabling them one by one to test that everything on your site is still working as expected.', 'better-wp-security' ); ?></p>
	<p><?php esc_html_e( 'Remember, some of these settings might conflict with other plugins or themes, so test your site after enabling each setting.', 'better-wp-security' ); ?></p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-wlwmanifest_header"><?php esc_html_e( 'Windows Live Writer Header', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'wlwmanifest_header' ); ?>
				<label for="itsec-wordpress-tweaks-wlwmanifest_header"><?php esc_html_e( 'Remove the Windows Live Writer header.', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'This is not needed if you do not use Windows Live Writer or other blogging clients that rely on this file.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-edituri_header"><?php esc_html_e( 'EditURI Header', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'edituri_header' ); ?>
				<label for="itsec-wordpress-tweaks-edituri_header"><?php esc_html_e( 'Remove the RSD (Really Simple Discovery) header.', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'Removes the RSD (Really Simple Discovery) header. If you don\'t integrate your blog with external XML-RPC services such as Flickr then the "RSD" function is pretty much useless to you.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-comment_spam"><?php esc_html_e( 'Comment Spam', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'comment_spam' ); ?>
				<label for="itsec-wordpress-tweaks-comment_spam"><?php esc_html_e( 'Reduce Comment Spam', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'This option will cut down on comment spam by denying comments from bots with no referrer or without a user-agent identified.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-file_editor"><?php esc_html_e( 'File Editor', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'file_editor' ); ?>
				<label for="itsec-wordpress-tweaks-file_editor"><?php esc_html_e( 'Disable File Editor', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-disable_xmlrpc"><?php esc_html_e( 'XML-RPC', 'better-wp-security' ); ?></label></th>
			<td>
				<p><?php printf( wp_kses( __( 'WordPress\' XML-RPC feature allows external services to access and modify content on the site. Common example of services that make use of XML-RPC are <a href="%1$s">the Jetpack plugin</a>, <a href="%2$s">the WordPress mobile app</a>, and <a href="%3$s">pingbacks</a>. If the site does not use a service that requires XML-RPC, select the "Disable XML-RPC" setting as disabling XML-RPC prevents attackers from using the feature to attack the site.', 'better-wp-security' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://jetpack.me/' ), esc_url( 'https://apps.wordpress.org/' ), esc_url( 'https://make.wordpress.org/support/user-manual/building-your-wordpress-community/trackbacks-and-pingbacks/#pingbacks' ) ); ?></p>
				<?php $form->add_select( 'disable_xmlrpc', $xmlrpc_options ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Disable XML-RPC</strong> - XML-RPC is disabled on the site. This setting is highly recommended if Jetpack, the WordPress mobile app, pingbacks, and other services that use XML-RPC are not used.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Disable Pingbacks</strong> - Only disable pingbacks. Other XML-RPC features will work as normal. Select this setting if you require features such as Jetpack or the WordPress Mobile app.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Enable XML-RPC</strong> - XML-RPC is fully enabled and will function as normal. Use this setting only if the site must have unrestricted use of XML-RPC.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-allow_xmlrpc_multiauth"><?php esc_html_e( 'Multiple Authentication Attempts per XML-RPC Request', 'better-wp-security' ); ?></label></th>
			<td>
				<p><?php esc_html_e( 'WordPress\' XML-RPC feature allows hundreds of username and password guesses per request. Use the recommended "Block" setting below to prevent attackers from exploiting this feature.', 'better-wp-security' ); ?></p>
				<?php $form->add_select( 'allow_xmlrpc_multiauth', $allow_xmlrpc_multiauth_options ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Block</strong> - Blocks XML-RPC requests that contain multiple login attempts. This setting is highly recommended.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Allow</strong> - Allows XML-RPC requests that contain multiple login attempts. Only use this setting if a service requires it.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-rest_api"><?php esc_html_e( 'REST API', 'better-wp-security' ); ?></label></th>
			<td>
				<p><?php printf( wp_kses( __( 'The <a href="%1$s">WordPress REST API</a> is part of WordPress and provides developers with new ways to manage WordPress. By default, it could give public access to information that you believe is private on your site. For more details, see our post about the WordPress REST API <a href="%1$s">here</a>.', 'better-wp-security' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://ithemes.com/security/wordpress-rest-api-restrict-access' ) ); ?></p>
				<?php $form->add_select( 'rest_api', $rest_api_options ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Restricted Access</strong> - Restrict access to most REST API data. This means that most requests will require a logged in user or a user with specific privileges, blocking public requests for potentially-private data. We recommend selecting this option.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Default Access</strong> - Access to REST API data is left as default. Information including published posts, user details, and media library entries is available for public access.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-login_errors"><?php esc_html_e( 'Login Error Messages', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'login_errors' ); ?>
				<label for="itsec-wordpress-tweaks-login_errors"><?php esc_html_e( 'Disable login error messages', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'Prevents error messages from being displayed to a user upon a failed login attempt.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-force_unique_nicename"><?php esc_html_e( 'Force Unique Nickname', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'force_unique_nicename' ); ?>
				<label for="itsec-wordpress-tweaks-force_unique_nicename"><?php esc_html_e( 'Force users to choose a unique nickname', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'This forces users to choose a unique nickname when updating their profile or creating a new account which prevents bots and attackers from easily harvesting user\'s login usernames from the code on author pages. Note this does not automatically update existing users as it will affect author feed urls if used.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-disable_unused_author_pages"><?php esc_html_e( 'Disable Extra User Archives', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'disable_unused_author_pages' ); ?>
				<label for="itsec-wordpress-tweaks-disable_unused_author_pages"><?php esc_html_e( 'Disables a user\'s author page if their post count is 0.', 'better-wp-security' ); ?></label>
				<p class="description"><?php esc_html_e( 'This makes it harder for bots to determine usernames by disabling post archives for users that don\'t post to your site.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-block_tabnapping"><?php esc_html_e( 'Protect Against Tabnapping', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'block_tabnapping' ); ?>
				<label for="itsec-wordpress-tweaks-block_tabnapping"><?php esc_html_e( 'Alter target="_blank" links to protect against tabnapping', 'better-wp-security' ); ?></label>
				<p class="description"><?php printf( wp_kses( __( 'Enabling this feature helps protect visitors to this site (including logged in users) from phishing attacks launched by a linked site. Details on tabnapping via target="_blank" links can be found in <a href="%s">this article</a>.', 'better-wp-security' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://www.jitbit.com/alexblog/256-targetblank---the-most-underestimated-vulnerability-ever/' ) ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-wordpress-tweaks-valid_user_login_type"><?php esc_html_e( 'Login with Email Address or Username', 'better-wp-security' ); ?></label></th>
			<td>
				<p><?php esc_html_e( 'By default, WordPress allows users to log in using either an email address or username. This setting allows you to restrict logins to only accept email addresses or usernames.', 'better-wp-security' ); ?></p>
				<?php $form->add_select( 'valid_user_login_type', $valid_user_login_types ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Email Address and Username (Default)</strong> - Allow users to log in using their user\'s email address or username. This is the default WordPress behavior.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Email Address Only</strong> - Users can only log in using their user\'s email address. This disables logging in using a username.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Username Only</strong> - Users can only log in using their user\'s username. This disables logging in using an email address.', 'better-wp-security' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</td>
		</tr>
	</table>
<?php

	}
}

new ITSEC_WordPress_Tweaks_Settings_Page();
