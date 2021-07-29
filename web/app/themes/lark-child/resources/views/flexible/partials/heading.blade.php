@group($fieldName ?? 'heading')
  @if (get_sub_field('heading') || isset($overrideText))
    <x-heading size="{{ $overrideSize ?? get_sub_field('heading_size') }}"
               alignment="{{ $overrideAlignment ?? get_sub_field('heading_alignment') }}"
               colour="{{ get_sub_field('heading_colour') }}"
               additional-classes="{{ $classes }}"
               :size-options="$sizeOptions ?? []"
               default="{{ $default ?? 'h2' }}"
    >
      {!! $overrideText ?? strip_tags(get_sub_field('heading'), '<mark><span>') !!}
    </x-heading>
  @endif
@endgroup
