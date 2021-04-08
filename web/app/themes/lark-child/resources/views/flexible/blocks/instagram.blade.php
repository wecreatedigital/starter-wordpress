@php
  if(function_exists('fetchInstagramPosts')):
    $instagram = fetchInstagramPosts();
    if($instagram):
@endphp
<section @hassub('id')id="{{ str_replace(' ', '-', preg_replace('/\s+/', ' ', strtolower(get_sub_field('id')))) }}"@endsub class="fcb fcb-y75 fcb-background-light-grey fcb-instagram">
  <div class="container">
    <div class="row">
      <div class="col">
        <{{ $heading }} class="fcb-b50 h2">
        Follow us on instagram
      </{{ $heading }}>
    </div>
  </div>
  <div class="row instagram-carousel">
    @foreach (json_decode($instagram) as $post)
      @foreach($post as $item)
        <a href="{{$item->permalink}}" target="_blank" rel="noopener noreferrer">
          <img src="{{$item->media_url}}" class="instagram-fluid" alt="{{ $item->caption }}">
        </a>
      @endforeach
    @endforeach
  </div>
</section>
@php
  endif;
endif;
@endphp
