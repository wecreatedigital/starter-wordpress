@php
  $count = 1;
  $max_images = get_field('amount_of_instagram_posts', 'option');
  $cache_name = 'instagram_images_'.get_current_blog_id();

  $result = get_transient( $cache_name );
  if ( false === $result ) {
    $instagram = instagramData($instagram_account);
    $result = json_decode($instagram);
    set_transient($cache_name, $result, 60);
  }
@endphp

@if (
  is_object($result)
  && ! isset($result->error)
  && $result->data
)

  @component('components.blocks.container', [
    'classes' => 'fcb-instagram',
  ])
    <div class="row instagram-feed fcb-y40">
      @foreach($result->data as $post)
        @if(
          (
            $post->media_type == "IMAGE" ||
            $post->media_type == "CAROUSEL_ALBUM"
          )
          && $count <= $max_images
        )
          <a href="{{ $post->permalink }}" class="col-md-6 col-lg-4" target="_blank" rel="noopener noreferrer">
            <div class="col-image" style="background-image: url({{ $post->media_url }})">
              {{ $post->caption }}
            </div>
          </a>
          @php
            $count++;
          @endphp
        @else
          @php
            continue;
          @endphp
        @endif
      @endforeach
    </div>

  @endcomponent

@endif
