@if( get_field('contact_form_redirect') && App\page_template('contact') )
<script>
  document.addEventListener( 'wpcf7mailsent', function( event ) {
    location = '@field('contact_form_redirect')' ;
  }, false );
</script>
@endif
