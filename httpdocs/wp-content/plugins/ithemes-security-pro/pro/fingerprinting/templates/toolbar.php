<script id="tmpl-itsec-fingerprint-app" type="text/template">
	<ul class="itsec-fingerprint-cards"></ul>
	<div class="itsec-fingerprint-empty-state-container"></div>
</script>
<script id="tmpl-itsec-fingerprint-empty-state" type="text/template">
	<h3><?php esc_html_e( 'No Pending Devices', 'it-l10n-ithemes-security-pro' ); ?></h3>
	<p><?php esc_html_e( 'Any unrecognized login attempts will show up here for you to approve or block.', 'it-l10n-ithemes-security-pro' ); ?></p>
</script>

<script id="tmpl-itsec-fingerprint-card" type="text/template">
	<button type="button" class="itsec-fingerprint-card__launch-modal" aria-label="<?php esc_attr_e( 'More Info', 'it-l10n-ithemes-security-pro' ) ?>"></button>
	<div class="itsec-fingerprint-card__header"></div>

	<div class="itsec-fingerprint-card__body">
		<h3 class="itsec-fingerprint-card__title">{{ data.m.title }}</h3>
		<div class="itsec-fingerprint-card__info"></div>

		<# if ( data.m.credit ) { #>
			<p class="itsec-fingerprint-card__credit">{{{ data.m.credit }}}</p>
		<# } #>
	</div>

	<div class="itsec-fingerprint-card__footer"></div>
</script>

<script id="tmpl-itsec-fingerprint-modal" type="text/template">
	<div id="itsec-fingerprint-modal-{{ data.m.uuid }}" class="itsec-fingerprint-modal" aria-hidden="true">
		<div class="itsec-fingerprint-modal__overlay" tabindex="-1" data-micromodal-close>
			<div class="itsec-fingerprint-modal__container" role="dialog" aria-modal="true" aria-labelledby="itsec-fingerprint-modal-{{ data.m.uuid }}-title">
				<div class="itsec-fingerprint-modal__notices-container"></div>
				<div class="itsec-fingerprint-modal__header">
					<button role="button" data-micromodal-close class="itsec-fingerprint-modal__close" aria-label="<?php esc_attr_e( 'Close Modal', 'it-l10n-ithemes-security-pro' ); ?>"></button>
					<div class="itsec-fingerprint-modal__header-container"></div>
				</div>

				<header>
					<h2 class="itsec-fingerprint-modal__title" id="itsec-fingerprint-modal-{{ data.m.uuid }}-title">{{ data.m.title }}</h2>
				</header>

				<div class="itsec-fingerprint-modal__body">
					<main>
						<aside></aside>
						<article><?php echo $message_detail; ?></article>
					</main>

					<# if ( data.m.credit ) { #>
						<p class="itsec-fingerprint-modal__credit">{{{ data.m.credit }}}</p>
					<# } #>
				</div>

				<footer></footer>
			</div>
		</div>
	</div>
</script>

<script id="tmpl-itsec-fingerprint-header" type="text/template">
	<div class="itsec-fingerprint-header__info-container">
		<span class="itsec-fingerprint-header__date">{{ data.m.date }}</span>
		<span class="itsec-fingerprint-header__time">{{ data.m.time }}</span>
	</div>
</script>

<script id="tmpl-itsec-fingerprint-info" type="text/template">
	<dt><?php echo esc_html__( 'IP', 'it-l10n-ithemes-security-pro' ); ?></dt>
	<dd class="itsec-fingerprint-info__part--ip"></dd>
	<dt><?php echo esc_html__( 'Browser', 'it-l10n-ithemes-security-pro' ); ?></dt>
	<dd class="itsec-fingerprint-info__part--browser"></dd>
	<dt><?php echo esc_html__( 'Device', 'it-l10n-ithemes-security-pro' ); ?></dt>
	<dd class="itsec-fingerprint-info__part--platform"></dd>
	<dt><?php echo esc_html__( 'Time', 'it-l10n-ithemes-security-pro' ); ?></dt>
	<dd class="itsec-fingerprint-info__part--date-time"></dd>
</script>

<script id="tmpl-itsec-fingerprint-footer" type="text/template">
	<button type="button" class="itsec-fingerprint-footer__action itsec-fingerprint-footer__action-deny button">
		<?php esc_html_e( 'Block', 'it-l10n-ithemes-security-pro' ) ?>
	</button>
	<button type="button" class="itsec-fingerprint-footer__action itsec-fingerprint-footer__action-approve button button-primary">
		<?php esc_html_e( 'Approve', 'it-l10n-ithemes-security-pro' ) ?>
	</button>
</script>