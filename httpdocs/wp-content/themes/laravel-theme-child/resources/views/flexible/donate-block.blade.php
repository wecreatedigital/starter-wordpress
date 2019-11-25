<section @hassub('id') id="@sub('id')" @endsub class="fcb fcb-donate">
  <div class="row">
    <div class="offset-lg-2 col-lg-8">
      @sub('text')
    </div>
  </div>
  <div class="row text-center">
    <div class="offset-lg-2 col-lg-8">

      @if( env('ENV') == 'local' )
        {!! dontate([
          'sku_1' => 5,
          'sku_2' => 10,
          'sku_3' => 20,
          'sku_4' => 50,
          'sku_5' => 100,
          ]) !!}
      @else
        {!! dontate([
          'sku_6' => 5,
          'sku_7' => 10,
          'sku_8' => 20,
          'sku_9' => 50,
          'sku_10' => 100,
          ]) !!}
      @endif

      <div id="error-message"></div>

      <script>
        (function() {
          var stripe = Stripe('{{ env('STRIPE_PUBLIC') }}');

          jQuery(document).on('click', '.select-payment-amount', function () {
            stripe.redirectToCheckout({
              items: [{
                sku: jQuery(this).data('product'),
                quantity: 1
              }],
              successUrl: '{{ get_site_url(null, '/thank-you/', 'https') }}',
              cancelUrl: '{{ get_site_url(null, '/oops/', 'https') }}',
            })
            .then(function (result) {
              if (result.error) {
                var displayError = document.getElementById('error-message');
                displayError.textContent = result.error.message;
              }
            });
          });
        })();
      </script>

    </div>
  </div>
</section>
