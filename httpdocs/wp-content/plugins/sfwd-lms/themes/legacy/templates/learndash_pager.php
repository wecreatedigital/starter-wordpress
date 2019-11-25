<?php
/**
 * This file contains the code that displays the pager.
 * 
 * @since 2.5.4
 * 
 * @package LearnDash
 */

/**
* Available Variables:
* $pager_context	: (string) value defining context of pager output. For example 'course_lessons' would be the course template lessons listing.   
* $pager_results    : (array) query result details containing 
* $href_query_arg	: query string parameter to use. 
* $href_val_prefix  : prefix added to value. default is empty ''.
* results<pre>Array
* (
*    [paged] => 1
*    [total_items] => 30
*    [total_pages] => 2
* )
*/
?>
<?php
if ( ( isset( $pager_results ) ) && ( !empty( $pager_results ) ) ) {
	if ( !isset( $pager_context ) ) $pager_context = '';
	if ( !isset( $href_val_prefix ) ) $href_val_prefix = '';

	// Generic wrappers. These can be changes via the switch below
	$wrapper_before = '<div class="learndash-pager learndash-pager-'. $pager_context .'">';
	$wrapper_after = '</div>';

	if ( $pager_results['total_pages'] > 1 ) {
		if ( ( ! isset( $href_query_arg ) ) || ( empty( $href_query_arg ) ) ) {
			switch( $pager_context ) {
				case 'course_lessons':
					$href_query_arg = 'ld-lesson-page';
					break;

				case 'course_lesson_topics':
					$href_query_arg = 'ld-topic-page';
					break;

				case 'profile':
					$href_query_arg = 'ld-profile-page';
					break;

				case 'course_content':
					$href_query_arg = 'ld-courseinfo-lesson-page';
					break;
				
				// These are just here to show the existing different context items. 	
				case 'course_info_registered':
				case 'course_info_courses':
				case 'course_info_quizzes':
				case 'course_navigation_widget':
				case 'course_navigation_admin':
				case 'course_list':
				default:
					break;
			}
		}
		
		$pager_left_disabled = '';
		$pager_left_class = '';
		if ( $pager_results['paged'] == 1 ) {
			$pager_left_disabled = ' disabled="disabled" ';
			$pager_left_class = 'disabled';
		} 
		$prev_page_number = ( $pager_results['paged'] > 1 ) ? $pager_results['paged'] - 1 : 1;

		$pager_right_disabled = '';
		$pager_right_class = '';
		if ( $pager_results['paged'] == $pager_results['total_pages'] ) {
			$pager_right_disabled = ' disabled="disabled" ';
			$pager_right_class = 'disabled';
		}
		$next_page_number = ( $pager_results['paged'] < $pager_results['total_pages'] ) ? $pager_results['paged'] + 1 : $pager_results['total_pages'];

		echo $wrapper_before;
		?>
		<span class="pager-left">
			<a 
			<?php if ( ( isset( $href_query_arg ) ) && ( !empty( $href_query_arg ) ) ) { ?>
				href="<?php echo add_query_arg( $href_query_arg, $href_val_prefix . 1 ) ?>" 
			<?php } ?> 
			data-paged="<?php echo $href_val_prefix; ?>1" class="<?php echo $pager_left_class ?>" <?php echo $pager_left_disabled; ?> title="<?php esc_attr_e( 'First Page', 'learndash' ); ?>">&laquo;</a>
			<a <?php if ( ( isset( $href_query_arg ) ) && ( !empty( $href_query_arg ) ) ) { ?>
				href="<?php echo add_query_arg( $href_query_arg, $href_val_prefix . $prev_page_number ) ?>" 
			<?php } ?> data-paged="<?php echo $href_val_prefix . $prev_page_number; ?>" class="<?php echo $pager_left_class ?>" <?php echo $pager_left_disabled; ?> title="<?php esc_attr_e( 'Previous Page', 'learndash' ); ?>">&lsaquo;</a>
		</span>
		<span class="pager-right">
			<a <?php if ( ( isset( $href_query_arg ) ) && ( !empty( $href_query_arg ) ) ) { ?>
				href="<?php echo add_query_arg( $href_query_arg, $href_val_prefix . $next_page_number ) ?>" 
			<?php } ?>	data-paged="<?php echo $href_val_prefix . $next_page_number; ?>" class="<?php echo $pager_right_class ?>" <?php echo $pager_right_disabled; ?> title="<?php esc_attr_e( 'Next Page', 'learndash' ); ?>">&rsaquo;</a>
			
			<a <?php if ( ( isset( $href_query_arg ) ) && ( !empty( $href_query_arg ) ) ) { ?>
				href="<?php echo add_query_arg( $href_query_arg, $href_val_prefix . $pager_results['total_pages'] ) ?>" 
			<?php } ?> data-paged="<?php echo $href_val_prefix . $pager_results['total_pages'] ?>" class="<?php echo $pager_right_class ?>" <?php echo $pager_right_disabled; ?> title="<?php esc_attr_e( 'Last Page', 'learndash' ); ?>">&raquo;</a>
		</span>
		<span class="pager-legend">
			<span class="pagedisplay"><?php _e('page', 'learndash') ?> <span class="current_page"><?php echo $pager_results['paged'] ?></span> / <span class="total_pages"><?php echo $pager_results['total_pages'] ?></span></span>
		</span>
		<?php
		echo $wrapper_after;
		
	}
}
