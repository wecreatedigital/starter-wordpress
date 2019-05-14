<?php

final class ITSEC_Magic_Links_Settings_Page extends ITSEC_Module_Settings_Page {

	public function __construct() {
		$this->id          = 'magic-links';
		$this->title       = __( 'Magic Links', 'it-l10n-ithemes-security-pro' );
		$this->description = __( 'Send an email with a Magic Link that bypasses a username lockout.', 'it-l10n-ithemes-security-pro' );
		$this->pro         = true;

		parent::__construct();
	}

	protected function render_description( $form ) {

?>
	<p><?php esc_html_e( 'The Magic Links feature allows you to log in while your username is locked out by the Local Brute Force Protection feature. When your username is locked out, you can request an email with a special login link. Using the emailed link will bypass the username lockout for you while brute force attackers are still locked out.', 'it-l10n-ithemes-security-pro' ); ?></p>
<?php

	}

	protected function render_settings( $form ) {}
}

new ITSEC_Magic_Links_Settings_Page();
