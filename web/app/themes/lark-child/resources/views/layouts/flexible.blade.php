@layouts('page_content_block', $object)
  @layout(get_row_layout())
    @php
      $blockFilename = str_of(get_row_layout())
      ->beforeLast('_block')
      ->replaceMatches('/_/', '-');
    @endphp

    @include("flexible.blocks.{$blockFilename}")

    @php $previousBlock = get_row_layout(); @endphp
  @endlayout

  @php $h++; @endphp
  @php $uniqueId++; @endphp
@endlayouts
