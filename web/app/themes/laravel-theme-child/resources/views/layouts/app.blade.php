<!doctype html>
<html {!! get_language_attributes() !!}>
  @include('layouts.head')
  @if (getenv('WP_ENV') !== 'production')
    <body @php body_class('development-mode') @endphp>
  @else
    <body @php body_class() @endphp>
  @endif
    @php do_action('get_header') @endphp
    @include('layouts.header')
    <div role="document">
      <main class="main">
        <div class="container">
          @include('layouts.breadcrumbs')
          @yield('content')
        </div>
        @include('flexible._main')
      </main>
      @if (App\display_sidebar())
        <aside class="sidebar">
          @include('partials.sidebar')
        </aside>
      @endif
    </div>
    @php do_action('get_footer') @endphp
    @include('layouts.footer')
    @include('plugins.cf7')
    @wpfoot()
  </body>
</html>
