<?php

use Stripe\StripeClient;

/**
 * Route that listens to Stripe's webook:
 *
 * case: customer.subscription.created:
 *    where we can then store the user's
 *    Stripe customer ID in the 'subscription' custom post type.
 *
 * case: customer.subscription.deleted:
 *    where we can then delete the user susbcription custom post type
 *    by quering the ACF `stripe_id` field.
 *
 * @author Christopher Kelker
 * @date   2021-10-11T13:49:39+010
 * @param  WP_REST_Request         $request
 * @return string
 */
function manageStripeWebook(WP_REST_Request $request)
{
    $data = $request->get_params();

    if ( ! isset($data['data']['object']['customer'])) {
        return;
    }

    $stripeClient = new StripeClient(getenv('STRIPE_PRIVATE'));

    switch ($data['type']) {
      case 'customer.subscription.created':
        $customer = $stripeClient->customers->retrieve(
            $data['data']['object']['customer'],
            []
        );

        $subscriptionId = wp_insert_post(array(
            'post_type' => 'subscription',
            'post_title' => $customer->email,
            'post_status' => 'publish',
        ));

        add_post_meta($subscriptionId, 'stripe_id', $data['data']['object']['customer']);

        $to = $customer->email;
        $subject = 'Thank you for supporting us';
        $body = 'Hello,<br><br>';
        $body .= 'Thank you for signing up.';
        $body .= '<br><br>';
        $body .= 'If you would like to make any changes to your subscription at any point, you can do this by clicking on the following link:';
        $body .= '<br><br>';
        $body .= get_field('manage_customer_subscription_page', 'option')['url'];
        $body .= '<br><br>';
        $body .= 'Furthermore, should you have any questions about your subscription, then please do not hesitate to get in touch by emailing us at email@email.com.';
        $body .= '<br><br>';
        $body .= 'Thanks again for your support';
        $body .= '<br><br>';
        $body .= 'Alex';
        $body .= '<br><br>';
        $body .= '<br><br>';
        $body .= '<img src="'.get_field('logo', 'option')->guid.'" width="95.9px" height="77.2px" alt="" style="font-size:10px;line-height:77.2px;color:#000000;text-align:center;border:0;display:inline-block;" />';

        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From:'.'email@email.com';

        wp_mail($to, $subject, $body, $headers);

        break;

      case 'customer.subscription.deleted':
        $customer = $stripeClient->customers->retrieve(
            $data['data']['object']['customer'],
            []
        );

        $customerSubscriptions = get_posts(array(
            'numberposts' => -1,
            'post_type' => 'subscription',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'stripe_id',
                    'value' => $data['data']['object']['customer'],
                    'compare' => '=',
                ),
            ),
        ));

        if (count($customerSubscriptions) === 1) {
            wp_update_post(array(
                'ID' => $customerSubscriptions[0]->ID,
                'post_status' => 'draft',
            ));
        }

        $to = $customer->email;
        $subject = 'Changes to your subscription';
        $body = 'Hello,<br><br>';
        $body .= 'We’re sorry that you have decided to cancel your subscription with us.';
        $body .= '<br><br>';
        $body .= 'We always want to hear feedback about what we are doing so if you would like to tell us anything then please email us at email@email.com.';
        $body .= '<br><br>';
        $body .= 'Thanks again for your support';
        $body .= '<br><br>';
        $body .= 'Alex';
        $body .= '<br><br>';
        $body .= '<br><br>';
        $body .= '<img src="'.get_field('logo', 'option')->guid.'" width="95.9px" height="77.2px" alt="" style="font-size:10px;line-height:77.2px;color:#000000;text-align:center;border:0;display:inline-block;" />';

        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From:'.'email@email.com';

        wp_mail($to, $subject, $body, $headers);

        break;
    }

    return 'Done';
}

function registerStripeWebhookRoute()
{
    // route url: domain.com/wp-json/$namespace/$route
    $namespace = 'api-stripe/v1';
    $route = 'subscribed';

    register_rest_route($namespace, $route, array(
        'methods' => 'POST',
        'callback' => 'manageStripeWebook',
    ));
}

add_action('rest_api_init', 'registerStripeWebhookRoute');

/**
 * Route that requires a user subscription email address.
 * The user then recieves an email where they can manage their subscription
 * through Stripe.
 *
 * @author Christopher Kelker
 * @date   2021-10-11T13:49:39+010
 * @param  WP_REST_Request         $request
 * @return array
 */
function manageCustomerSubscription(WP_REST_Request $request)
{
    $data = $request->get_params();

    $customerSubscription = get_page_by_title($data['email'], null, 'subscription');

    if (is_null($customerSubscription)) {
        return rest_ensure_response([
            'message' => 'Customer susbcription was not found with email address: '.$data['email'],
            'data' => $data,
        ]);
    }

    $stripeClient = new StripeClient(getenv('STRIPE_PRIVATE'));

    $billingPortal = $stripeClient->billingPortal->sessions->create([
        'customer' => get_field('stripe_id', $customerSubscription->ID),
        'return_url' => get_field('manage_customer_subscription_return', 'option') ? get_field('manage_customer_subscription_return', 'option')['url'] : get_home_url(),
    ]);

    $to = $data['email'];
    $subject = '';
    $body = 'Hello,<br><br>';
    $body .= 'If you would like to make any changes to your subscription, please go to:';
    $body .= '<br><br>';
    $body .= $billingPortal->url;
    $body .= '<br><br>';
    $body .= 'Furthermore, should you have any questions about your subscription, then please do not hesitate to get in touch by emailing us at email@email.com.';
    $body .= '<br><br>';
    $body .= 'Thanks again for your support';
    $body .= '<br><br>';
    $body .= 'Alex';
    $body .= '<br><br>';
    $body .= '<br><br>';
    $body .= '<img src="'.get_field('logo', 'option')->guid.'" width="95.9px" height="77.2px" alt="" style="font-size:10px;line-height:77.2px;color:#000000;text-align:center;border:0;display:inline-block;" />';

    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From:'.'email@email.com';

    wp_mail($to, $subject, $body, $headers);

    return [
        'message' => get_field('send_customer_manage_subscription_email', 'option') ?? 'Please check your emails',
        'data' => $data,
    ];
}

function registerCustomerManageSubscriptionRoute()
{
    // route url: domain.com/wp-json/$namespace/$route
    $namespace = 'api-stripe/v1';
    $route = 'manage-customer-subscription';

    register_rest_route($namespace, $route, array(
        'methods' => 'POST',
        'callback' => 'manageCustomerSubscription',
    ));
}

add_action('rest_api_init', 'registerCustomerManageSubscriptionRoute');

/**
 * Route that requires a Stripe Product Price ID, then user is redirected to
 * intended URL where they can complete their checkout.
 *
 * @author Christopher Kelker
 * @date   2021-10-11T13:49:39+010
 * @param  WP_REST_Request         $request
 * @return redirect
 */
function manageCustomerCheckoutSession(WP_REST_Request $request)
{
    $data = $request->get_params();

    $stripeClient = new StripeClient(getenv('STRIPE_PRIVATE'));

    $session = $stripeClient->checkout->sessions->create([
        'success_url' => get_field('subscribed_checkout_return', 'option') ? get_field('subscribed_checkout_return', 'option')['url'] : get_home_url(),
        'cancel_url' => get_field('cancelled_checkout_return', 'option') ? get_field('cancelled_checkout_return', 'option')['url'] : get_home_url(),
        'payment_method_types' => ['card'],
        'mode' => 'subscription',
        'line_items' => [[
            'price' => $data['stripe_product_price_id'],
            'quantity' => 1,
        ]],
    ]);

    wp_redirect($session->url);

    exit();
}

function registerCustomerCheckoutSessionRoute()
{
    // route url: domain.com/wp-json/$namespace/$route
    $namespace = 'api-stripe/v1';
    $route = 'customer-checkout';

    register_rest_route($namespace, $route, array(
        'methods' => 'GET',
        'callback' => 'manageCustomerCheckoutSession',
    ));
}

add_action('rest_api_init', 'registerCustomerCheckoutSessionRoute');
