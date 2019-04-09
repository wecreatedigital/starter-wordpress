<?php $__env->startSection('content'); ?>
  <?php while(have_posts()): ?> <?php the_post() ?>
    <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('partials.content-page', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>




<hr>
<p class="display-4">Slick Slider Example</p>
<div class="main">
  <div class="slider slick slider-for">
    <div><h3>1</h3></div>
    <div><h3>2</h3></div>
    <div><h3>3</h3></div>
    <div><h3>4</h3></div>
    <div><h3>5</h3></div>
  </div>
  <div class="slider slick slider-nav">
    <div><h3>1</h3></div>
    <div><h3>2</h3></div>
    <div><h3>3</h3></div>
    <div><h3>4</h3></div>
    <div><h3>5</h3></div>
  </div>
</div>
<hr>
<p class="display-4">Default Variables Example</p>
<p>Set in test.scss, defined in _variables.scss</p>
<div class="main">
    <p class="font-weight-bold">test</p>
    <p class="p1">Default</p>
    <p class="p2">black</p>
    <p class="p3">White</p>
    <p class="p4">light grey</p>
    <p class="p5">lime</p>
    <p class="p6">bright red</p>
    <p class="p7">dark blue</p>
    <p class="p8"> deep cerulean</p>
    <p class="p9"> bonid blue</p>
    <p class="p10">Cyan</p>
    <p class="p11">heliotrope</p>
    <p class="p12">silver chalice</p>
    <p class="p13">scorpion</p>
    <p class="p14">tundora</p>
    <p class="p15">mine shaft</p>
    <p class="p16">cod grey</p>
</div>
<p class="display-4">Google Maps Example</p>
<?php echo $__env->make('partials.google-maps', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
<hr>
<?php endwhile; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>