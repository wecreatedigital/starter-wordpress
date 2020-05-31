{{--
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.1.0
 */
--}}
@php
  defined('ABSPATH') || exit;

	do_action('woocommerce_before_customer_login_form');
@endphp

@if ('yes' === get_option('woocommerce_enable_myaccount_registration'))

<div class="row" id="customer_login">

	<div class="col-md-6">

@endif

		<h2>
      @php
        esc_html_e('Login', 'woocommerce');
      @endphp
    </h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post">

			@php
        do_action('woocommerce_login_form_start');
      @endphp

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username">@php esc_html_e('Username or email address', 'woocommerce'); @endphp&nbsp;<span class="required">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="@php echo ( ! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; @endphp" /><?php // @codingStandardsIgnoreLine?>
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password">@php esc_html_e('Password', 'woocommerce'); @endphp&nbsp;<span class="required">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
			</p>

			@php
        do_action('woocommerce_login_form');
      @endphp

			<p class="form-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span>@php esc_html_e('Remember me', 'woocommerce'); @endphp</span>
				</label>
				@php
          wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce');
        @endphp
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="@php esc_attr_e('Log in', 'woocommerce'); @endphp">@php esc_html_e('Log in', 'woocommerce'); @endphp</button>
			</p>
			<p class="woocommerce-LostPassword lost_password">
				<a href="@php echo esc_url(wp_lostpassword_url()); @endphp">@php esc_html_e('Lost your password?', 'woocommerce'); @endphp</a>
			</p>

			@php
        do_action('woocommerce_login_form_end');
      @endphp

		</form>

@if ('yes' === get_option('woocommerce_enable_myaccount_registration'))

	</div>

	<div class="col-md-6">

		<h2>
      @php
        esc_html_e('Register', 'woocommerce');
      @endphp
    </h2>

		<form method="post" class="woocommerce-form woocommerce-form-register register" @php do_action('woocommerce_register_form_tag'); @endphp>

			@php
        do_action('woocommerce_register_form_start');
      @endphp

			@if ('no' === get_option('woocommerce_registration_generate_username'))

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username">@php esc_html_e('Username', 'woocommerce'); @endphp&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="@php echo ( ! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; @endphp" /><?php // @codingStandardsIgnoreLine?>
				</p>

			@endif

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email">@php esc_html_e('Email address', 'woocommerce'); @endphp&nbsp;<span class="required">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="@php echo ( ! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; @endphp" /><?php // @codingStandardsIgnoreLine?>
			</p>

			@if ('no' === get_option('woocommerce_registration_generate_password'))

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password">@php esc_html_e('Password', 'woocommerce'); @endphp&nbsp;<span class="required">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
				</p>

			@else

				<p>@php esc_html_e('A password will be sent to your email address.', 'woocommerce'); @endphp</p>

			@endif

			@php
        do_action('woocommerce_register_form');
      @endphp

			<p class="woocommerce-form-row form-row">
				@php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); @endphp
				<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="@php esc_attr_e('Register', 'woocommerce'); @endphp">@php esc_html_e('Register', 'woocommerce'); @endphp</button>
			</p>

			@php
        do_action('woocommerce_register_form_end');
      @endphp

		</form>

	</div>

</div>
@endif

@php
  do_action('woocommerce_after_customer_login_form');
@endphp
