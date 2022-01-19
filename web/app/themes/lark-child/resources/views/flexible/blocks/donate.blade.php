@component('components.blocks.container')

  <div class="content max-w-850 mx-auto">
    @include('flexible.partials.heading', [
      'alignmentClasses' => 'text-center',
    ])

    @include('flexible.partials.text', [
      'alignmentClasses' => 'text-center',
    ])

    <div x-data="{ count: 0 }">
      <div class="flex flex-row flex-wrap mt-20 mx-auto justify-center -mx-15 -mb-15">
        <x-link colour="{{ get_sub_field('payment_button')['colour'] }}" button="true" x-on:click="count = 1" class="m-15 md:m-25">
          Credit/debit card
        </x-link>

        <form target="_blank"
              action="https://www.paypal.com/donate"
              method="post"
              target="_top"
              class="m-15 md:m-25"
        >
          <input type="hidden"
                 name="cmd"
                 value="_s-xclick"
          />
          <input type="hidden"
                 name="hosted_button_id"
                 value="NHE4YAZ87U6JL"
          />

          <x-link colour="{{ get_sub_field('payment_button')['colour'] }}" button="true" name="submit" x-on:click="count = 1">
            PayPal
          </x-link>
        </form>

        <x-link colour="{{ get_sub_field('payment_button')['colour'] }}" button="true" x-on:click="count = 2" class="m-15 md:m-25">
          BACs
        </x-link>
      </div>

      <div x-show="count === 1" class="mt-30">
        @php
          $amounts = [
            '5' => '500',
            '10' => '1000',
            '50' => '5000',
            '100' => '10000',
            '250' => '25000',
          ];
        @endphp

        <div class="flex flex-row flex-wrap justify-center -m-15 -md:m-25">
          @foreach ($amounts as $amount => $value)
            <div class="m-15 md:m-25">
              <input x-on:change="$refs.stripeBtn.click();"
                     type="radio"
                     id="{{ $amount }}"
                     name="amount"
                     value="{{ $value }}"
                     class="hidden"
                     hidden
              />
              <label for="{{ $amount }}"
                     class="cursor-pointer rounded-full w-72 h-72 flex items-center justify-center bg-pink text-white"
              >
                <span>£{{ $amount }}</span>
              </label>
            </div>
          @endforeach
        </div>

        <button id="checkout-button"
                x-ref="stripeBtn"
                class="hidden"
        >
          Checkout
        </button>
      </div>

      <ul x-show="count === 2" class="space-y-20 mx-auto text-center mt-30">
        @options('bacs')
          <p>
            @sub('text')
          </p>

          <p>
            Sort code: @sub('sort_code')
          </p>

          <p>
            Account number: @sub('account_number')
          </p>
        @endoptions
      </ul>
    </div>
  </div>

  {{-- @once
    @push('scripts')
      <script src="https://js.stripe.com/v3/"></script>
      <script type="text/javascript">
        // Create an instance of the Stripe object with your publishable API key
        var stripe = Stripe('{{ getenv('STRIPE_PUBLIC') }}');
        var checkoutButton = document.getElementById('checkout-button');

        checkoutButton.addEventListener('click', function() {

          var $checkedAmount = document.querySelector("input[name=amount]:checked").value;

          fetch('/wp-json/stripe/v1/payment/' + $checkedAmount, {
            method: 'POST',
          })
          .then(function(response) {
            return response.json();
          })
          .then(function(session) {
            return stripe.redirectToCheckout({ sessionId: session.id });
          })
          .then(function(result) {
            // If `redirectToCheckout` fails due to a browser or network
            // error, you should display the localized error message to your
            // customer using `error.message`.
            if (result.error) {
              alert(result.error.message);
            }
          })
          .catch(function(error) {
            console.error('Error:', error);
          });
        });
      </script>
    @endpush
  @endonce --}}

@endcomponent
