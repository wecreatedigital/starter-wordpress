function itsecRecaptchav2Load() {
	var captchas = document.querySelectorAll( '.g-recaptcha' );

	for ( var i = 0; i < captchas.length; i++ ) {

		var captcha = captchas[i];

		grecaptcha.render( captcha, {
			'sitekey': captcha.dataset.sitekey,
			'theme'  : captcha.dataset.theme
		} );
	}
}