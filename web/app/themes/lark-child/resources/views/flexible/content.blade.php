@php
  if( $h == 1 ) {
    $heading = 'h1';
    $sub_heading = 'h2';
  } elseif( $h >= 2 && $h <= 4 ) {
    $heading = 'h2';
    $sub_heading = 'h3';
  } else {
    $heading = 'h3';
    $sub_heading = 'h4';
  }
@endphp

<div class="content @if( isset($classes) ) {{ $classes }} @else fcb-b20 @endif">
  @hassub('heading')
    <{{ $heading }} class="fcb-b20">
      @sub('heading')
    </{{ $heading }}>
  @endsub

  @hassub('sub_heading')
    <{{ $sub_heading }} class="fcb-b20">
      @sub('sub_heading')
    </{{ $sub_heading }}>
  @endsub

  @hassub('text')
    @sub('text')
  @endsub

  @if( get_sub_field('primary_call_to_action') || get_sub_field('secondary_call_to_action') )
    <p class="lead">
      @hassub('primary_call_to_action')
        <a target="@sub('primary_call_to_action', 'target')" class="btn btn-lg" href="@sub('primary_call_to_action', 'url')">@sub('primary_call_to_action', 'title')</a>
      @endsub
      @hassub('secondary_call_to_action')
        <a target="@sub('secondary_call_to_action', 'target')" class="btn-link btn-lg" href="@sub('secondary_call_to_action', 'url')">@sub('secondary_call_to_action', 'title')</a>
      @endsub
    </p>
  @endif
</div>
