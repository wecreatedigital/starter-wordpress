@php
  global $layoutCount;
@endphp

<section class="fcb

  @isset($classes)
    {{ $classes }}
  @endisset

  @if ( ! isset($noBackgroundColour) && $noBackgroundColour != true)
    @hassub('background_colour')
      bg-@sub('background_colour')
    @endsub
  @endif

  @if ($overridePaddingFieldValue)
    {{ $overridePaddingFieldValue }}
  @elseif ($overridePadding)
    {{ $overridePadding }}
  @elseif ($paddingOverride)
    {{ $paddingOverride }}
  @else
    @if (in_array(get_sub_field('background_colour'), ['default', 'white', false]))
      py-25 md:py-50 my-25 md:my-50
    @else
      {{ $defaultPadding }}
    @endif
  @endif

  @if(get_sub_field('has_overlay') == true )
    blend
  @endif

  @isset(get_sub_field('background_image')['ID'] )
    bg-center bg-cover bg-no-repeat
  @endisset
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
  @if(isset($overrideContainerClasses) && ! empty($overrideContainerClasses))
    {{ $overrideContainerClasses}}
  @else
    container max-w-screen-xl
  @endif

  @isset($containerClasses)
    {{ $containerClasses}}
  @endisset">
    {!! $slot !!}
  </div>

  {{ $afterSlot }}
</section>
