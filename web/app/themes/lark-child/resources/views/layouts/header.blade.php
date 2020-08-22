<header>
  <nav class="navbar navbar-expand-md navbar-light bg-light">
    <div class="container">
      <a class="navbar-brand" href="{{ get_home_url() }}">
        {{ $siteName }}
      </a>
      <button class="btn skip-to-content-link" data-target="#main">
        Skip to content
      </button>
      @if ( ! env('DISABLE_HAMBURGER'))
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#hamburger-menu" aria-controls="hamburger-menu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        {!! wp_nav_menu( array(
          'theme_location'    => 'primary_navigation',
          'depth'             => 2,
          'container'         => 'div',
          'container_class'   => 'collapse navbar-collapse',
          'container_id'      => 'hamburger-menu',
          'menu_class'        => 'nav navbar-nav',
          'fallback_cb'       => 'WP_Bootstrap_Navwalker::fallback',
          'walker'            => new WP_Bootstrap_Navwalker(),
        ) ); !!}
      @else
        @php
          $header_cta = get_field('header_call_to_action_link', 'option');
        @endphp
        @if( $header_cta )
          <a class="d-block btn btn-dark order-1 order-sm-1 order-md-2" href="{{ $header_cta['url'] }}">
            {{ $header_cta['title'] }}
          </a>
        @endif
        {!! wp_nav_menu( array(
          'theme_location'    => 'primary_navigation',
          'depth'             => 1,
          'container'         => 'div',
          'container_class'   => 'mobile-first-menu',
          'container_id'      => '',
          'menu_class'        => 'nav d-flex',
          'fallback_cb'       => 'WP_Bootstrap_Navwalker::fallback',
          'walker'            => new WP_Bootstrap_Navwalker(),
        ) ); !!}
      @endif
  	</div>
  </nav>
</header>
