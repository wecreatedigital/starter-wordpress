<?php $__env->startSection('content'); ?>
  <?php while(have_posts()): ?> <?php the_post() ?>
    <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
     <?php echo $__env->make('partials.content-page', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

     <p class="test">Here is some text</p>

     

  <?php endwhile; ?>
  <script>
    document.addEventListener( 'wpcf7mailsent', function( event ) {
        location = '<?php echo the_field('contact_form_redirect_url'); ?>' ;
    }, false );
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>