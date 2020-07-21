<div aria-labelledby="megamneu" class="dropdown-menu border-0 p-0 m-0" style="display:block;">
  <div class="container p-md-0">
    <div class="row bg-white rounded-0 m-0 shadow-sm">
      <div class="col-12 col-md-12">
        <div class="row">
          @php
          switch (count(get_field('columns', $post_id))) {
              case 1:
                  $cols = 12;
                  break;
              case 2:
                  $cols = 6;
                  break;
              case 3:
                  $cols = 4;
                  break;
              case 4:
                  $cols = 3;
                  break;
          }
          @endphp

          @fields('columns', $post_id)
            <div class="col-12 col-md-{{ $cols }}">
              @include('mega-menu.types.'.get_sub_field('column_type'))
            </div>
          @endfields
        </div>
      </div>
    </div>
  </div>
</div>
