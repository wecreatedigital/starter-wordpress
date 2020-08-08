@component('components.blocks.container', [
  'classes' => 'fcb-text-block',
  'padding' => $default_padding,
])

<div class="row">
  <div class="fcb-col-@sub('column_offset') @hassub('align_text'){{ 'fcb-align-text' }}@endsub col-md-8">
    @include('flexible.content', [
      'classes' => ''
    ])
  </div>
</div>

@endcomponent
