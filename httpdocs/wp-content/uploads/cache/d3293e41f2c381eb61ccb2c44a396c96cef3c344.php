<?php $__env->startSection('content'); ?>
  <?php while(have_posts()): ?> <?php the_post() ?>
    <?php echo $__env->make('partials.page-header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <h2 class="btn btn">TEST</h2>
    <div class="slider center slick-initialized slick-slider"><button type="button" data-role="none" class="slick-prev slick-arrow" aria-label="Previous" role="button" style="display: block;">Previous</button>
				<div aria-live="polite" class="slick-list draggable" style="padding: 0px 60px;"><div class="slick-track" style="opacity: 1; width: 2058px; transform: translate3d(-441px, 0px, 0px);" role="listbox"><div class="slick-slide slick-cloned" data-slick-index="-4" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>3</h3>
				</div><div class="slick-slide slick-cloned" data-slick-index="-3" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>4</h3>
				</div><div class="slick-slide slick-cloned" data-slick-index="-2" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>5</h3>
				</div><div class="slick-slide slick-cloned slick-active" data-slick-index="-1" aria-hidden="false" style="width: 147px;" tabindex="-1">
					<h3>6</h3>
				</div><div class="slick-slide slick-current slick-active slick-center" data-slick-index="0" aria-hidden="false" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide60">
					<h3>1</h3>
				</div><div class="slick-slide slick-active" data-slick-index="1" aria-hidden="false" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide61">
					<h3>2</h3>
				</div><div class="slick-slide" data-slick-index="2" aria-hidden="true" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide62">
					<h3>3</h3>
				</div><div class="slick-slide" data-slick-index="3" aria-hidden="true" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide63">
					<h3>4</h3>
				</div><div class="slick-slide" data-slick-index="4" aria-hidden="true" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide64">
					<h3>5</h3>
				</div><div class="slick-slide" data-slick-index="5" aria-hidden="true" style="width: 147px;" tabindex="-1" role="option" aria-describedby="slick-slide65">
					<h3>6</h3>
				</div><div class="slick-slide slick-cloned slick-center" data-slick-index="6" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>1</h3>
				</div><div class="slick-slide slick-cloned" data-slick-index="7" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>2</h3>
				</div><div class="slick-slide slick-cloned" data-slick-index="8" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>3</h3>
				</div><div class="slick-slide slick-cloned" data-slick-index="9" aria-hidden="true" style="width: 147px;" tabindex="-1">
					<h3>4</h3>
				</div></div></div>
			<button type="button" data-role="none" class="slick-next slick-arrow" aria-label="Next" role="button" style="display: block;">Next</button></div>
    <?php echo $__env->make('partials.content-page', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
  <?php endwhile; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>