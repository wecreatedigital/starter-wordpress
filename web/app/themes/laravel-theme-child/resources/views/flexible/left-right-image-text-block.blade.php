@php
  $position = get_sub_field('image_position');
@endphp
<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-left-image-right-text">
  <div class="container">
    <div class="row fcb-v-align">
      <div class="col-md-6 col-sm-12 @if( $position == "left" ) order-2 @else order-2 order-sm-2 order-md-1 @endif">
        @include('flexible.content')
      </div>
      <div class="col-md-6 col-sm-12 @if( $position == "left" ) order-1 @else order-1 order-sm-1 order-md-2 @endif">
        <img src="@sub('image', 'url')" alt="@sub('image', 'alt')" class="img-fluid mb-sm-4 mb-4">
      </div>
    </div>
  </div>
</section>
