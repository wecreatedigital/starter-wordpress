<div class="flex flex-row font-bitter text-18">
  @php $terms = collect(get_the_terms($post->ID, 'category')); @endphp

  @if ( ! $terms->isEmpty())
    @php $term = $terms->shift(); @endphp
    <a href="{{ get_term_link($term->term_id) }}" class="text-teal underline font-bold">
      {{ $term->name }}
    </a>
  @endif

  <span class="mx-5 text-current">-</span>

  <time datetime="{{ get_post_time('c', true) }}" pubdate="updated">
    {{ gmdate('j F Y', get_post_timestamp()) }}
  </time>
</div>
