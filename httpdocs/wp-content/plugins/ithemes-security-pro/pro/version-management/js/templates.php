<script type="text/template" id="tmpl-itsec-vm-package">
	<td headers="itsec-vm-bulk-select itsec-vm-package__name-{{ data.m.id }}" class="itsec-vm-package__bulk itsec-vm-column__bulk">
		<label for="itsec-vm-package__bulk-{{ data.m.id }}" class="screen-reader-text">{{ data.d.bulkLabel }}</label>
		<input type="checkbox" id="itsec-vm-package__bulk-{{ data.m.id }}" {{ data.d.bulkChecked }}>
	</td>
	<th scope="row" id="itsec-vm-package__name-{{ data.m.id }}" class="itsec-vm-package__name itsec-vm-column__name">
		<label for="itsec-vm-package__bulk-{{ data.m.id }}">{{ data.m.name }}</label>
	</th>

	<td class="itsec-vm-package__type itsec-vm-package__type--enabled itsec-vm-column__enabled">
		<input type="radio" name="version-management[packages][{{ data.m.id }}][type]" value="enabled" id="itsec-vm-package__type--enabled-{{ data.m.id }}" <# print( data.m.type === 'enabled' ? 'checked' : '' ) #>>
		<label for="itsec-vm-package__type--enabled-{{ data.m.id }}"><span class="screen-reader-text"><?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ) ?></span></label>
	</td>

	<td class="itsec-vm-package__type itsec-vm-package__type--disabled itsec-vm-column__disabled">
		<input type="radio" name="version-management[packages][{{ data.m.id }}][type]" value="disabled" id="itsec-vm-package__type--disabled-{{ data.m.id }}" <# print( data.m.type === 'disabled' ? 'checked' : '' ) #>>
		<label for="itsec-vm-package__type--disabled-{{ data.m.id }}"><span class="screen-reader-text"><?php esc_html_e( 'Disabled', 'it-l10n-ithemes-security-pro' ) ?></span></label>
	</td>

	<td class="itsec-vm-package__type itsec-vm-package__type--delay itsec-vm-column__delay">
		<input type="radio" name="version-management[packages][{{ data.m.id }}][type]" value="delay" id="itsec-vm-package__type--delay-{{ data.m.id }}" <# print( data.m.type === 'delay' ? 'checked' : '' ) #>>
		<label for="itsec-vm-package__type--delay-{{ data.m.id }}"><span class="screen-reader-text"><?php esc_html_e( 'Delay', 'it-l10n-ithemes-security-pro' ) ?></span></label>
	</td>

	<td class="itsec-vm-package__delay itsec-vm-column__days">
		<div class="<# print( data.m.type === 'delay' ? '' : 'hidden' ) #>">
			<label for="itsec-vm-package__delay-{{ data.m.id }}" class="screen-reader-text"><?php esc_html_e( 'Delay Update for Days', 'it-l10n-ithemes-security-pro' ) ?></label>
			<input type="number" name="version-management[packages][{{ data.m.id }}][delay]" value="{{ data.d.delay }}" id="itsec-vm-package__delay-{{ data.m.id }}" placeholder="<?php esc_attr_e( '3 Days', 'it-l10n-ithemes-security-pro' ) ?>" min="1">
		</div>
	</td>
</script>

<script type="text/template" id="tmpl-itsec-vm-header">
	<th class="itsec-vm-column__bulk">
		<input type="checkbox" id="itsec-vm-header__bulk-{{ data.d.kind }}">
	</th>
	<th class="itsec-vm-column__name">
		<label for="itsec-vm-header__bulk-{{ data.d.kind }}"><?php esc_html_e( 'Select All', 'it-l10n-ithemes-security-pro' ); ?></label>
	</th>
	<th class="itsec-vm-column__enabled">
		<button class="itsec-vm-header__button itsec-vm-header__button--enable"><?php esc_html_e( 'Enable', 'it-l10n-ithemes-security-pro' ); ?></button>
	</th>
	<th class="itsec-vm-column__disabled">
		<button class="itsec-vm-header__button itsec-vm-header__button--disable"><?php esc_html_e( 'Disable', 'it-l10n-ithemes-security-pro' ); ?></button>
	</th>
	<th class="itsec-vm-column__delay">
		<button class="itsec-vm-header__button itsec-vm-header__button--delay"><?php esc_html_e( 'Delay', 'it-l10n-ithemes-security-pro' ); ?></button>
	</th>
	<th class="itsec-vm-column__days">
		<label for="itsec-vm-header__delay-{{ data.d.kind }}" class="screen-reader-text"><?php esc_html_e( 'Delay', 'it-l10n-ithemes-security-pro' ); ?></label>
		<input type="number" id="itsec-vm-header__delay-{{ data.d.kind }}" class="itsec-vm-header__delay" placeholder="<?php esc_attr_e( '3 Days', 'it-l10n-ithemes-security-pro' ) ?>" min="1">
	</th>
</script>

<script type="text/template" id="tmpl-itsec-vm-app">
	<thead></thead>
	<tbody></tbody>
</script>