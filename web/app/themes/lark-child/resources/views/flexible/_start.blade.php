<section
  @if( isset($style) )style="{{ $style }}"@endif
  @hassub('id')id="{{ str_replace(' ', '-', preg_replace('/\s+/', ' ', strtolower(get_sub_field('id')))) }}"@endsub
  class="fcb @if( isset($classes) ){{ $classes }}@endif
  @hassub('padding_override'){{ 'fcb-' }}@sub('padding_override'){{ $padding }}@endsub
  @hassub('background_colour'){{ 'fcb-' }}@sub('background_colour')@endsub
">
  @hassub('container_type')
    <div class="@sub('container_type')">
  @else
    <div class="container">
  @endsub
