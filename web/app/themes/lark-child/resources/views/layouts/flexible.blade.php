@layouts('page_content_block', $object)
  @layout(get_row_layout())
    @php
      $blockFilename = str_of(get_row_layout())
      ->beforeLast('_block')
      ->replaceMatches('/_/', '-');
    @endphp

    @if(viewExists("flexible.blocks.{$blockFilename}"))
      @include("flexible.blocks.{$blockFilename}")
    @else
      @php dd("flexible.blocks.{$blockFilename}" . ' does not exist.'); @endphp
    @endif
  @endlayout

  @php $h++; @endphp
  @php $uniqueId++; @endphp
@endlayouts
