@component('components.container')

  <div class="max-w-1100 flex flex-col mx-auto bg-@sub('box_background_colour')">
    @include('flexible.partials.heading', [
      'alignmentClasses' => 'text-center mx-auto',
    ])

    @include('flexible.partials.text', [
      'classes' => 'max-w-545 mx-auto',
    ])

    @if(isset(get_sub_field('text')['text']) && ! empty(get_sub_field('text')['text']))
      @php $marginTop = 'mt-50'; @endphp
    @elseif(isset(get_sub_field('heading')['heading']) && ! empty(get_sub_field('heading')['heading']))
      @php $marginTop = 'mt-0'; @endphp
    @endif

    <div class="{{ $marginTop }}">
      {!! do_shortcode('[contact-form-7 id="'.get_sub_field('contact_form').'" title="Contact"]') !!}
    </div>
  </div>

@endcomponent



