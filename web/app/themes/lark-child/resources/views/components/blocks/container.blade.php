<section class="fcb

  @isset($classes)
    {{ $classes }}
  @endisset

  @hassub('background_colour')
    bg-@sub('background_colour')
  @endsub

  @if ($overridePadding)
    {{ $overridePadding }}
  @else
    @if (in_array(get_sub_field('background_colour'), ['default', 'white']))
      {{-- TODO:  default padding for when the default colour is selected --}}
    @else
      {{ $defaultPadding }}
    @endif
  @endif

  @if(get_sub_field('has_overlay') == true )
    blend
  @endif
"
@if( isset($style) )
  style="{{ $style }}"
@endif

@hassub('background_image')
  style="background-image: url('{{ wp_get_attachment_image_src(get_sub_field('background_image')['ID'], 'full')[0] }}');"
@endsub
>
  {{ $beforeSlot }}

  <div class="
  @isset($overrideContainerClasses)
    {{ $overrideContainerClasses}}
  @else
    container max-w-screen-xl
  @endisset

  @isset($containerClasses)
    {{ $containerClasses}}
  @endisset">
    {!! $slot !!}
  </div>

  {{ $afterSlot }}
</section>
