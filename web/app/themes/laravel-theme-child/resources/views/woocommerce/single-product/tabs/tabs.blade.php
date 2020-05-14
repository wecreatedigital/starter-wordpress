{{--

@php
  defined('ABSPATH') || exit;

  global $product;

  // Ensure visibility.
  if (empty($product) || ! $product->is_visible()) {
      return;
  }

  $default_columns = str_replace('.', '-', esc_attr(12 / wc_get_loop_prop('columns')));
@endphp
/**
 * Single Product tabs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */
--}}

@php
  if ( ! defined('ABSPATH')) {
      exit;
  }
@endphp

@php

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
  $product_tabs = apply_filters('woocommerce_product_tabs', array());
@endphp


@if ( ! empty($product_tabs))

<div class="container product-tabs">
  <ul class="nav nav-tabs" role="tablist">
    @php
      $t = 1;
    @endphp

    @foreach ($product_tabs as $key => $product_tab)
      <li class="nav-item">
        <a href="#pane-{{ $key }}" class="nav-link @if($t == 1) active @endif" data-toggle="tab" role="tab" aria-controls="tab-{{ $key }}">
          {!! wp_kses_post(apply_filters('woocommerce_product_'.$key.'_tab_title', $product_tab['title'], $key)) !!}
        </a>
      </li>

      @php
        $t++;
      @endphp

    @endforeach
  </ul>

  @php
    $t = 1;
  @endphp

  <div id="content" class="tab-content" role="tablist">
    @foreach ($product_tabs as $key => $product_tab)
      <div id="pane-{{ $key }}" class="card tab-pane fade @if($t == 1) show active @endif" role="tabpanel" aria-labelledby="tab-{{ $key }}">
        <div class="card-header" role="tab" id="heading-{{ $key }}">
          <h5 class="mb-0">
            <a data-toggle="collapse" href="#collapse-{{ $key }}" aria-expanded="true" aria-controls="collapse-{{ $key }}">
              {!! wp_kses_post(apply_filters('woocommerce_product_'.$key.'_tab_title', $product_tab['title'], $key)) !!}
            </a>
          </h5>
        </div>
        <div id="collapse-{{ $key }}" class="collapse @if($t == 1) show @endif" data-parent="#content" role="tabpanel" aria-labelledby="heading-{{ $key }}">
          <div class="card-body">
            @if (isset($product_tab['callback']))
              {!! call_user_func($product_tab['callback'], $key, $product_tab) !!}
            @endif
          </div>
        </div>
      </div>

      @php
        $t++;
      @endphp

    @endforeach
  </div>

  @php
    do_action('woocommerce_product_after_tabs');
  @endphp
</div>

@endif
