@group($fieldName ?? 'text')
  @if (get_sub_field('text') || isset($overrideText))

    @if( ! isset($alignmentClasses))
      @php
        switch (get_sub_field('text_alignment')) {
          case 'left':
            $alignmentClasses = 'text-left mr-auto';

            break;
          case 'right':
            $alignmentClasses = 'text-right ml-auto';

            break;
          case 'center':
            $alignmentClasses = 'text-center mx-auto';

            break;
          default:
            // LEFT
            $alignmentClasses = '';

            break;
        }
      @endphp
    @endif

    <div class="{{ $removeDefaultStyling ? '' : 'content' }}
      @isset($spacing)
        {{ $spacing }}
      @else
        space-y-25
      @endisset
      {{ $classes }}
      text-@sub('text_colour')
      {{ $alignmentClasses }}"
    >
      @hassub('text')
      @php
        $text = \Illuminate\Support\Str::of(get_sub_field('text'))
        ->replace('<table', '<div class="overflow-x-scroll"><table')
        ->replace('</table>', '</table></div>');
      @endphp
        {!! $text !!}
      @endsub
    </div>


  @endif
@endgroup
