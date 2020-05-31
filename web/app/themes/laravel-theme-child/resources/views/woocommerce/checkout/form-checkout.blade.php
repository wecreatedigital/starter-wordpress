{{--
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 */
--}}
@php
  defined('ABSPATH') || exit;

	do_action( 'woocommerce_before_checkout_form', $checkout );

	// If checkout registration is disabled and not logged in, the user cannot checkout.
	if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
		echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
		return;
	}
@endphp

<form name="checkout" method="post" class="row checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	@if ( $checkout->get_checkout_fields() )

		@php 
			do_action( 'woocommerce_checkout_before_customer_details' );
		@endphp

		<div class="col-6" id="customer_details">
			<div class="col-12">
				@php 
					do_action( 'woocommerce_checkout_billing' );
				@endphp
			</div>

			<div class="col-12">
				@php 
					do_action( 'woocommerce_checkout_shipping' );
				@endphp
			</div>
		</div>

		@php 
			do_action( 'woocommerce_checkout_after_customer_details' );
		@endphp

	@endif

	@php 
		do_action( 'woocommerce_checkout_before_order_review_heading' );
	@endphp

	<div class="col-6">
		<div class="col-review-order">
			<h3 id="order_review_heading">
				@php 
					esc_html_e( 'Your order', 'woocommerce' );
				@endphp
			</h3>

			@php 
				do_action( 'woocommerce_checkout_before_order_review' );
			@endphp

			<div id="order_review" class="woocommerce-checkout-review-order">
				@php 
					do_action( 'woocommerce_checkout_order_review' );
				@endphp
			</div>

			@php 
				do_action( 'woocommerce_checkout_after_order_review' );
			@endphp
		</div>
	</div>

</form>

@php 
	do_action( 'woocommerce_after_checkout_form', $checkout );
@endphp
