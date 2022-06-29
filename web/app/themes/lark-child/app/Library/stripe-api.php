<?php

use Stripe\StripeClient;

function handleStripeRequest(WP_REST_Request $request)
{
    $stripe = new StripeClient(
        getenv('STRIPE_PRIVATE')
    );

    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'gbp',
                'product_data' => [
                    'name' => htmlspecialchars_decode(get_bloginfo('name', 'display')).' donation',
                ],
                'unit_amount' => $request->get_param('amount'),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => get_field('success_page', 'option') ?? get_home_url(),
        'cancel_url' => get_field('cancel_page', 'option') ?? get_home_url(),
    ]);

    return ['id' => $session->id];
}

add_action('rest_api_init', function () {
    register_rest_route('stripe/v1', '/payment/(?P<amount>\d+)', array(
        'methods' => 'POST',
        'args' => [
            'amount' => [
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ],
        ],
        'callback' => 'handleStripeRequest',
        'permission_callback' => '__return_true',
    ));
});
