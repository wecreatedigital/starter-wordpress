@component('components.blocks.container', [
  'classes' => 'fcb-contact',
])

  <div class="max-w-1140 flex flex-col mx-auto bg-@sub('box_background_colour') text-@sub('text_colour')">
    <div class="mx-auto px-50 py-60">
      @include('flexible.partials.heading')

      @if(isset(get_sub_field('text')['text']) && ! empty(get_sub_field('text')['text']))
        @php $marginTop = 'mt-45'; @endphp
      @elseif(isset(get_sub_field('heading')['heading']) && ! empty(get_sub_field('heading')['heading']))
        @php $marginTop = 'mt-0'; @endphp
      @endif

      <div class="{{ $marginTop }} text-white">
        {!! do_shortcode('[contact-form-7 id="'. get_sub_field('contact_form')->ID .'"]') !!}
      </div>
    </div>
  </div>

@endcomponent
