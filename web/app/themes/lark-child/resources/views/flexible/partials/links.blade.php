@php
  $linksCount = count(get_sub_field('links') ?: []);

  if ( ! isset($linksAlignment)) {
    switch (get_sub_field('links_alignment')) {
      case 'left':
        $linksAlignment = 'start';

        break;
      case 'center':
        $linksAlignment = 'center';

        break;
      case 'right':
        $linksAlignment = 'end';

        break;
      default:
        // LEFT
        $linksAlignment = 'start';

        break;
    }
  }
@endphp

@if ($linksCount)
  <ul class="flex flex-wrap flex-row items-center justify-{{ $linksAlignment }} -mx-15 {{ $spacing ?? '-mb-15 mt-10' }} text-16 font-spectral">
    @fields('links')
      @group('link')
        @hassub('link')
          <li class="m-15">
            <x-link href="{{ get_sub_field('link')['url'] }}"
                    style="{{ get_sub_field('link_type') }}"
                    target="{{ get_sub_field('link')['target'] }}"
                    colour="{{ get_sub_field('colour') }}"
            >
              @sub('link', 'title')
            </x-link>
          </li>
        @endsub
      @endgroup
    @endfields
  </ul>
@endif
