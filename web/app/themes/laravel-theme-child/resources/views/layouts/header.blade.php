<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand" href="{{ get_home_url() }}">
      navbar
    </a>
    @if ( ! env('DISABLE_HAMBURGER'))
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-controls="bs-example-navbar-collapse-1" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php
    wp_nav_menu( array(
      'theme_location'    => 'primary_navigation',
      'depth'             => 2,
      'container'         => 'div',
      'container_class'   => 'collapse navbar-collapse',
      'container_id'      => 'bs-example-navbar-collapse-1',
      'menu_class'        => 'nav navbar-nav',
      'fallback_cb'       => 'WP_Bootstrap_Navwalker::fallback',
      'walker'            => new WP_Bootstrap_Navwalker(),
    ) );
    ?>
  @else
    <a class="d-sm-block d-md-none" href="@option('header_call_to_action_link')">
      Contact
    </a>
    <?php
    wp_nav_menu( array(
      'theme_location'    => 'primary_navigation',
      'depth'             => 1,
      'container'         => 'div',
      'container_class'   => '',
      'container_id'      => '',
      'menu_class'        => 'nav d-flex',
      'fallback_cb'       => 'WP_Bootstrap_Navwalker::fallback',
      'walker'            => new WP_Bootstrap_Navwalker(),
    ) );
    ?>
  @endif

	</div>
</nav>
