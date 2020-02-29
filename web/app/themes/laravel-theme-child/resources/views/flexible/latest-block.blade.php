@query([
  'post_type' => get_sub_field('post_type'),
  'numberposts' => 1
])

@posts
<section @hassub('id') id="@sub('id')" @endsub class="fcb @hassub('padding_override') fcb-@sub('padding_override')100 @endsub fcb-latest">
  <div class="row text-center">
    <div class="offset-sm-2 col-sm-8">
      @include('flexible.content')
    </div>
  </div>
  <div class="row">
    <div class="col-sm-4 offset-sm-1">
      @thumbnail('medium')
    </div>
    <div class="col-sm-6">
      <h4 class="mb-4">
        <a href="@permalink">
          @title
        </a>
      </h4>
      @excerpt
    </div>
  </div>
</section>
@endposts
