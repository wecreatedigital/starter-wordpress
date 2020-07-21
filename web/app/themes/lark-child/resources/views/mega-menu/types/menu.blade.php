@hassub('menu_items')
  <ul class="list-unstyled">
    @fields('menu_items')
      <li class="nav-item">
        <a href="@sub('item_link', 'url')" class="nav-link text-small px-0 py-1">
          @sub('item_link', 'title')
        </a>
      </li>
    @endfields
  </ul>
@endsub
