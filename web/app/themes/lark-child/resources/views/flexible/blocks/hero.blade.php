@component('components.blocks.container', [
  'classes' => 'fcb-hero fcb-v-align',
  'padding' => $default_padding,
  'background_image' => get_sub_field('background'),
])

  <div class="row">
    <div class="fcb-col-@sub('column_offset') @hassub('align_text'){{ 'fcb-align-text' }}@endsub col-md-8">
      @include('flexible.content', [
        'classes' => ''
      ])
    </div>
  </div>

  <div class="overlay"></div>

@endcomponent