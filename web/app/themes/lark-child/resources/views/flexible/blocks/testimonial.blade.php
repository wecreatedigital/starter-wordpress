@component('components.container')
  'classes' => 'fcb-testimonial',
])

  @php $testimonialsColour = get_sub_field('quotes_colour'); @endphp
  @php $textColour = get_sub_field('text_colour'); @endphp

  <div class="splide text-{{ $textColour }}  max-w-953 md:max-w-953 lg:max-w-953 mx-auto" id="splide">
    <div class="splide__track">
      <ul class="splide__list">
        @fields('testimonials')
        <?php
        $featured_post = get_sub_field('testimonial');
        if( $featured_post ): ?>

            <li class="splide__slide">
                 <div class="flex flex-col md:flex-row justify-between items-start mx-auto">
                   @include('svgs.quote-left')

                   <div class="text-center mx-auto">
                     <h3 class="text-current text-24 md:text-26 font-jost italic md:-tracking-0.8 md:word-spacing-0.9 md:leading-50 mb-20">
                           <?php echo $featured_post->post_content; ?>
                     </h3>

                     <div class="text-18 md:text-20">
                           <h3><?php echo esc_html( $featured_post->post_title ); ?></h3>
                     </div>
                   </div>

                   @include('svgs.quote-right')
                 </div>
               </li>

        <?php endif; ?>
        @endfields
      </ul>
    </div>
  </div>

  @once
    @push('scripts')
      <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>
      <script>
        const splide = new Splide( '#splide', {
          type: 'scroll',
          pagination: true,
          rewind: true,
          arrows: false,
          classes: {
        		pagination: 'splide__pagination mt-50 relative top-0 space-x-10',
        		page      : 'splide__pagination__page w-22 h-22 border-2 border-solid border-current transform scale-100 bg-transparent',
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

@endcomponent
