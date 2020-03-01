@query([
  'post_type' => get_sub_field('post_type'),
  'posts_per_page' => get_sub_field('number_of_posts')
])

@include('flexible._start', [
  'classes' => 'fcb-latest',
  'padding' => $default_padding,
])

<div class="row text-center">
  <div class="offset-sm-2 col-sm-8">
    @include('flexible.content', [
      'classes' => ''
    ])
  </div>
</div>

@posts
  <article class="row fcb-t40">
    <div class="offset-sm-2 offset-md-2 col-sm-8 col-md-4 fcb-t20">
      @thumbnail('medium')
    </div>
    <div class="offset-sm-2 offset-md-0 col-sm-8 col-md-4 fcb-t20">
      <h4 class="mb-4">
        <a class="link" href="@permalink">
          @title
        </a>
      </h4>
      @excerpt
      <a class="btn" href="@permalink">
        Read more
      </a>
    </div>
  </article>
@endposts

@include('flexible._end')
