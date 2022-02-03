@component('components.container')
  'classes' => 'fcb-newsletter',
  'overridePadding' => 'py-50',
])

  <form action="https://btinternet.us6.list-manage.com/subscribe/post?u=a933001675ea93da78b34161f&amp;id=959e3f03f9"
        method="post"
        id="mc-embedded-subscribe-form"
        name="mc-embedded-subscribe-form"
        class="flex flex-col lg:flex-row items-start mx-auto max-w-1060 text-center lg:text-left"
        target="_blank"
        novalidate
  >
    @include('flexible.partials.heading', [
      'default' => 'h3',
      'alignmentClasses' => 'text-center lg:text-left',
      'classes' => 'mx-auto lg:ml-0 lg:mr-auto lg:mt-15',
    ])

    <div class="mx-auto lg:ml-auto lg:mr-0 max-w-540 w-full">
      <div class="flex flex-row items-start">
        <div class="flex-1">
          <input type="email"
                 name="EMAIL"
                 placeholder="Enter your email address"
                 class="bg-grey-mid border-0 rounded-full w-full font-cardo py-12 px-25 max-w-540 text-blue-dark"
                 required
          />

          <div class="max-w-485 lg:mx-25 mt-20 mb-20 lg:mb-0">
            @include('flexible.partials.text', [
              'removeDefaultStyling' => true,
              'classes' => 'text-16',
            ])
          </div>
        </div>

        @group('sign_up_button')
          @hassub('text')
            <button type="submit"
                    name="button"
                    class="hidden lg:block button py-15 px-25 rounded-full border-@sub('background_colour') text-@sub('text_colour') bg-@sub('background_colour') flex-shrink-0 mx-auto ml-30"
            >
              @sub('text')
            </button>
          @endsub
        @endgroup
      </div>
    </div>

    <div class="mx-auto lg:ml-30 lg:mr-0">
      @group('link')
        <x-link button="true"
                type="submit"
                style="{{ get_sub_field('link_type') }}"
                colour="{{ get_sub_field('colour') }}"
        >
          @sub('text')
        </x-link>
      @endgroup
    </div>
  </form>

  <div id="mce-responses" class="field-text clear text-16 max-w-1060 mx-auto text-{{ $textColour }}">
    <div class="response mt-30" id="mce-error-response" style="display:none"></div>
    <div class="response mt-30" id="mce-success-response" style="display:none"></div>
  </div>
@endcomponent

<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script>
