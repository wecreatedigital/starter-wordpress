<?php
/**
 * @var string $two_factor_info
 * @var string $confirm_email_message
 */
?>
<script type="text/template" id="tmpl-itsec-app">
	<div class="itsec-screen-container"></div>
</script>

<script type="text/template" id="tmpl-itsec-screen-intro">
	<div class="itsec-screen__content">
		<h2><?php esc_html_e( 'Setup Two-Factor', 'it-l10n-ithemes-security-pro' ); ?></h2>
		<?php echo $two_factor_info; ?>
	</div>
	<div class="itsec-screen__actions">
		<# if ( data.c.can_skip ) { #>
			<button class="button-link itsec-screen__actions--skip" name="itsec_skip" value="skip" type="submit">
				<?php esc_html_e( 'Skip', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>
		<button class="button button-primary itsec-screen__actions--continue">
			<?php esc_html_e( 'Continue', 'it-l10n-ithemes-security-pro' ); ?>
		</button>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-screen-providers">

	<div class="itsec-screen__content">
		<h2><?php esc_html_e( 'Select Methods', 'it-l10n-ithemes-security-pro' ); ?></h2>
		<p><?php esc_html_e( "Choose the Two-Factor methods you'd like to use when logging-in.", 'it-l10n-ithemes-security-pro' ); ?></p>

		<ul class="itsec-providers__list"></ul>
	</div>

	<div class="itsec-screen__actions">
		<# if ( data.c.can_skip ) { #>
			<button class="button-link itsec-screen__actions--skip" name="itsec_skip" value="skip" type="submit">
				<?php esc_html_e( 'Skip', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>
		<button class="button button-primary itsec-screen__actions--continue" {{ data.d.disabled }}>
			<?php esc_html_e( 'Continue', 'it-l10n-ithemes-security-pro' ); ?>
		</button>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-provider">
	<h3 class="dashicons-before dashicons-{{ data.m.dashicon }}">
		{{ data.m.label }}
	</h3>

	<div class="itsec-provider__status-actions-container">
		<span class="itsec-provider__status itsec-provider__status--{{ data.m.status }}">{{ data.d.status_label }}</span>

		<# if ( data.m.status === 'enabled' ) { #>
			<button class="button-link itsec-provider__action itsec-provider__action--disable">
				<?php esc_html_e( 'Disable', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>

		<# if ( data.m.status === 'disabled' && ! data.m.configurable ) { #>
			<button class="button-link itsec-provider__action itsec-provider__action--enable">
				<?php esc_html_e( 'Enable', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>
	</div>

	<p>{{ data.m.description }}</p>

	<# if ( data.m.configurable ) { #>
		<button class="itsec-provider__configure dashicons-before">
			<span class="screen-reader-text"><?php esc_html_e( 'Configure', 'it-l10n-ithemes-security-pro' ) ?></span>
		</button>
	<# } #>
</script>

<script type="text/template" id="tmpl-itsec-screen-provider-totp">
	<div class="itsec-screen__content">
		<h2>{{ data.m.label }}</h2>

		<p><?php esc_html_e( 'To generate Time-Based One-Time password codes, you need to install and configure an app on your mobile device.', 'it-l10n-ithemes-security-pro' ) ?></p>

		<# if ( data.d.device === 'ios' ) { #>
			<div aria-label="<?php esc_attr_e( 'Device Type', 'it-l10n-ithemes-security-pro' ) ?>" class="itsec-totp__device-switcher" role="group">
				<button aria-pressed="true" class="button button-primary button-large itsec-totp__device-switcher-button--ios">
					<?php esc_html_e( 'iOS', 'it-l10n-ithemes-security-pro' ); ?>
				</button><button aria-pressed="false" class="button button-large itsec-totp__device-switcher-button--android">
					<?php esc_html_e( 'Android', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			</div>
			<p>
				<?php printf(
					__( 'For iOS devices, the %2$s Authy%1$s, %3$s Google Authenticator%1$s, %4$s FreeOTP Authenticator%1$s, or %5$s Toopher%1$s apps are the most popular token generators.', 'it-l10n-ithemes-security-pro' ), '</a>',
					'<a href="https://itunes.apple.com/us/app/authy/id494168017?mt=8">',
					'<a href="https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8">',
					'<a href="https://itunes.apple.com/us/app/freeotp-authenticator/id872559395?mt=8">',
					'<a href="https://itunes.apple.com/us/app/toopher/id562592093?mt=8">'
				); ?>
			</p>
		<# } else { #>
			<div aria-label="<?php esc_attr_e( 'Device Type', 'it-l10n-ithemes-security-pro' ) ?>" class="itsec-totp__device-switcher" role="group">
				<button aria-pressed="false" class="button button-large itsec-totp__device-switcher-button--ios">
					<?php esc_html_e( 'iOS', 'it-l10n-ithemes-security-pro' ); ?>
				</button><button aria-pressed="true" class="button button-primary button-large itsec-totp__device-switcher-button--android">
					<?php esc_html_e( 'Android', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			</div>
			<p>
				<?php printf(
					__( 'For Android devices, the %2$s Authy%1$s, %3$s Google Authenticator%1$s, %4$s FreeOTP Authenticator%1$s, or %5$s Toopher%1$s apps are the most popular token generators.', 'it-l10n-ithemes-security-pro' ), '</a>',
					'<a href="https://play.google.com/store/apps/details?id=com.authy.authy&hl=en">',
					'<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en">',
					'<a href="https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp">',
					'<a href="https://play.google.com/store/apps/details?id=com.toopher.android&hl=en">'
				); ?>
			</p>
		<# } #>

		<img src="{{ data.m.config.qr }}" width="300">

		<# if ( data.d.show_secret ) { #>
			<code class="itsec-totp__secret">{{ data.m.config.secret }}</code>
		<# } else { #>
			<button class="button-link itsec-totp__view-secret">
				<?php esc_html_e( 'View Secret', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>

		<p><?php esc_html_e( 'Please scan the QR code or manually enter the secret into your Mobile App.', 'it-l10n-ithemes-security-pro' ); ?></p>
	</div>

	<div class="itsec-screen__actions">
		<div class="itsec-screen__actions">
			<# if ( data.m.status === 'enabled' ) { #>
				<button class="button-link itsec-screen__actions--back">
					<?php esc_html_e( 'Back', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } else { #>
				<button class="button-link itsec-screen__actions--cancel">
					<?php esc_html_e( 'Cancel', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } #>
			<button class="button button-primary itsec-screen__actions--continue" {{ data.d.disabled }}>
				<?php esc_html_e( 'Continue', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-screen-totp-confirm">
	<div class="itsec-screen__content">
		<h2>{{ data.m.label }}</h2>

		<p><?php esc_html_e( 'Please enter an authenticate code from your mobile app in order to complete setup.', 'it-l10n-ithemes-security-pro' ) ?></p>

		<label for="itsec-totp__confirm-code"><?php esc_html_e( 'Authentication Code', 'it-l10n-ithemes-security-pro' ); ?></label>
		<input type="text" id="itsec-totp__confirm-code" value="{{ data.d.code }}">
	</div>

	<div class="itsec-screen__actions">
		<div class="itsec-screen__actions">
			<# if ( data.m.status === 'enabled' ) { #>
				<button class="button-link itsec-screen__actions--back">
					<?php esc_html_e( 'Back', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } else { #>
				<button class="button-link itsec-screen__actions--cancel">
					<?php esc_html_e( 'Cancel', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } #>
			<button class="button button-primary itsec-screen__actions--continue" {{ data.d.disabled }}>
				<?php esc_html_e( 'Verify', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-screen-email-confirm">
	<div class="itsec-screen__content">
		<h2>{{ data.m.label }}</h2>

		<p><?php echo $confirm_email_message; ?></p>
		<p><?php esc_html_e( 'Then, enter the provided code below to complete setup.', 'it-l10n-ithemes-security-pro' ); ?></p>

		<label for="itsec-email__confirm-code"><?php esc_html_e( 'Authentication Code', 'it-l10n-ithemes-security-pro' ); ?></label>
		<input type="text" id="itsec-email__confirm-code" value="{{ data.d.code }}">

		<p class="description">
			<# if ( data.d.cannotFind ) { #>
				<?php esc_html_e( "If you can't find the email in your inbox, please first check your spam folder, then contact the website administrator if the problem persists.", 'it-l10n-ithemes-security-pro' ); ?>
			<# } else { #>
				<button class="button-link" id="itsec-email__cannot_find"><?php esc_html_e( "Can't find the email?", 'it-l10n-ithemes-security-pro' ); ?></button>
			<# } #>
		</p>
	</div>

	<div class="itsec-screen__actions">
		<div class="itsec-screen__actions">
			<# if ( data.m.status === 'enabled' ) { #>
				<button class="button-link itsec-screen__actions--back">
					<?php esc_html_e( 'Back', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } else { #>
				<button class="button-link itsec-screen__actions--cancel">
					<?php esc_html_e( 'Cancel', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } #>
			<button class="button button-primary itsec-screen__actions--continue" {{ data.d.disabled }}>
				<?php esc_html_e( 'Verify', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-screen-backup-codes">
	<div class="itsec-screen__content">
		<h2>{{ data.m.label }}</h2>

		<# if ( data.m.config.codes.length ) { #>
			<p><?php esc_html_e( 'Write	these down! Once you navigate away from this page, you will not be able to view these codes again.', 'it-l10n-ithemes-security-pro' ) ?></p>

			<ul class="itsec-backup-codes__code-list">
				<# for ( var i = 0; i < data.m.config.codes.length; i++ ) { #>
					<li>{{ data.m.config.codes[i] }}</li>
				<# } #>
			</ul>
		<# } else { #>
			<p><?php esc_html_e( 'You have %d unused codes remaining. If you no longer have access to your backup codes, you can generate new ones below.', 'it-l10n-ithemes-security-pro' ) ?></p>

			<button class="button itsec-backup-codes__generate-codes" {{ data.d.generateDisabled }}>
				<?php esc_html_e( 'Generate New Codes', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		<# } #>
	</div>

	<div class="itsec-screen__actions">
		<div class="itsec-screen__actions">
			<# if ( data.m.status === 'enabled' ) { #>
				<button class="button-link itsec-screen__actions--back">
					<?php esc_html_e( 'Back', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } else { #>
				<button class="button-link itsec-screen__actions--cancel">
					<?php esc_html_e( 'Cancel', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			<# } #>

			<div class="itsec-screen__actions-primary">

				<# if ( data.d.enabled && data.m.config.codes.length ) { #>
					<a href="data:text/plain;charset=utf-8,{{{ data.d.newlineCodes }}}" download="codes.txt" class="button itsec-screen__actions--download"
					   title="<?php esc_html_e( 'Download Codes', 'it-l10n-ithemes-security-pro' ) ?>">
						<?php esc_html_e( 'Download', 'it-l10n-ithemes-security-pro' ); ?>
					</a>
				<# } #>

				<button class="button button-primary itsec-screen__actions--continue"  {{ data.d.continueDisabled }}>
					<?php esc_html_e( 'Continue', 'it-l10n-ithemes-security-pro' ); ?>
				</button>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-itsec-screen-summary">
	<div class="itsec-screen__content">
		<h2><?php esc_html_e( 'Two-Factor Setup Complete', 'it-l10n-ithemes-security-pro' ); ?></h2>

		<p>{{ data.d.summary }}</p>
	</div>

	<div class="itsec-screen__actions">
		<div class="itsec-screen__actions">
			<button class="button button-primary itsec-screen__actions--continue">
				<?php esc_html_e( 'Complete', 'it-l10n-ithemes-security-pro' ); ?>
			</button>
		</div>
	</div>
</script>
