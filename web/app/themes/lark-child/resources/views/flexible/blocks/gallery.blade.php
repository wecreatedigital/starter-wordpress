@component('components.blocks.container', [
  'classes' => 'fcb-gallery pb-0',
])

  <div class="row">
    <div class="offset-lg-2 col-lg-8 text-center">
      @include('flexible.content')
    </div>
  </div>

  <div class="row fcb-t40 no-gutters justify-content-md-center">
    @fields('gallery')
      <img srcset="{{ wp_get_attachment_image_srcset(get_sub_field('image'), 'large') }}" src="{{ wp_get_attachment_image_src(get_sub_field('image'), 'large')[0] }}" alt="{{ get_sub_field('image')['alt'] }}" class="col-6 col-sm-6 col-md-4 img-fluid">
    @endfields
  </div>

@endcomponent
