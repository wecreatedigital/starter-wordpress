@component('components.blocks.container', [
  'classes' => 'fcb-testimonial',
])

  <div class="splide" id="splide">
    <div class="splide__track">
      <ul class="splide__list">

        @fields('testimonials')
          @php $testimonial = get_sub_field('testimonial'); @endphp

          <li class="splide__slide h-full">
           <img src="@thumbnail($testimonial->ID, 'full', false)"
                class="w-full mx-auto max-h-60 object-contain mb-35"
                alt="{{ $testimonial->post_title }}"
            />

            <div class="flex flex-row justify-between items-center max-w-900 mx-auto">
              <svg class="icon fill-current text-teal mr-auto" viewBox="0 0 56 41" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                  <g transform="matrix(1.02857,0,0,1.18956,-3294.21,-2024.85)">
                      <g>
                          <g transform="matrix(0.759549,0,0,0.656754,-598.871,379.735)">
                              <circle cx="5023" cy="2049" r="16" />
                          </g>
                          <g transform="matrix(0.962078,0.12112,-0.140234,0.832801,-1325.9,-572.03)">
                              <path d="M5019,2001C5009.62,2001 5001.95,2009.96 5002,2021C5002.06,2033.75 5009.44,2042.77 5019,2041C5013.09,2038.54 5008.29,2030.42 5008.29,2021C5008.29,2011.58 5013.09,2003.47 5019,2001Z" />
                          </g>
                      </g>
                      <g transform="matrix(1,0,0,1,27.7383,0)">
                          <g transform="matrix(0.759549,0,0,0.656754,-598.871,379.735)">
                              <circle cx="5023" cy="2049" r="16" />
                          </g>
                          <g transform="matrix(0.962078,0.12112,-0.140234,0.832801,-1325.9,-572.03)">
                              <path d="M5019,2001C5009.62,2001 5001.95,2009.96 5002,2021C5002.06,2033.75 5009.44,2042.77 5019,2041C5013.09,2038.54 5008.29,2030.42 5008.29,2021C5008.29,2011.58 5013.09,2003.47 5019,2001Z" />
                          </g>
                      </g>
                  </g>
              </svg>

              <div class="text-center max-w-240 md:max-w-950 mx-auto">
                <h3 class="text-18 leading-30 font-bitter md:text-26 -tracking-0.4 md:-tracking-0.6 italic mb-50 md:mb-20">
                  {!! get_post_field('post_content', $testimonial->ID) !!}
                </h3>

                <p class="text-18 leading-25 md:leading-30">
                  {{ $testimonial->post_title }}
                </p>
              </div>

              <svg class="icon fill-current text-teal ml-auto" viewBox="0 0 56 41" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
                  <g transform="matrix(-1.02857,-1.25964e-16,1.45679e-16,-1.18956,3349.27,2065)">
                      <g>
                          <g transform="matrix(0.759549,0,0,0.656754,-598.871,379.735)">
                              <circle cx="5023" cy="2049" r="16" />
                          </g>
                          <g transform="matrix(0.962078,0.12112,-0.140234,0.832801,-1325.9,-572.03)">
                              <path d="M5019,2001C5009.62,2001 5001.95,2009.96 5002,2021C5002.06,2033.75 5009.44,2042.77 5019,2041C5013.09,2038.54 5008.29,2030.42 5008.29,2021C5008.29,2011.58 5013.09,2003.47 5019,2001Z" />
                          </g>
                      </g>
                      <g transform="matrix(1,0,0,1,27.7383,0)">
                          <g transform="matrix(0.759549,0,0,0.656754,-598.871,379.735)">
                              <circle cx="5023" cy="2049" r="16" />
                          </g>
                          <g transform="matrix(0.962078,0.12112,-0.140234,0.832801,-1325.9,-572.03)">
                              <path d="M5019,2001C5009.62,2001 5001.95,2009.96 5002,2021C5002.06,2033.75 5009.44,2042.77 5019,2041C5013.09,2038.54 5008.29,2030.42 5008.29,2021C5008.29,2011.58 5013.09,2003.47 5019,2001Z" />
                          </g>
                      </g>
                  </g>
              </svg>
            </div>
          </li>
        @endfields
      </ul>
    </div>
  </div>

@endcomponent

@once
  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>
    <script>
      const splide = new Splide( '#splide', {
        type: 'fade',
        pagination: true,
        rewind: true,
        arrows: false,
        classes: {
      		pagination: 'splide__pagination mt-40 relative top-0 space-x-10',
      		page      : 'splide__pagination__page w-22 h-22 border-2 border-solid border-current border-grey-dark transform scale-100',
        },
      } )

      splide.on( 'pagination:updated', function ( data ) {

        // `items` contains all dot items
        data.items.forEach( function ( item ) {
          item.button.classList.remove('bg-{{ $testimonialsColour }}');

          if (item.button.classList.contains('is-active')) {
            item.button.classList.add('bg-{{ $testimonialsColour }}');
          }
        } );
      } );

      splide.mount();
    </script>
  @endpush
@endonce
