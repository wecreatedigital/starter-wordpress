<?php

function donate($donation_prices)
{
    $html = '';
    foreach ($donation_prices as $key => $price) {
        $html .= '<button
          class="btn btn-primary btn-xl select-payment-amount"
          id="checkout-button-'.$key.'"
          data-product="'.$key.'"
          role="link"
        >£'.$price.'</button>';
    }

    return $html;
}
