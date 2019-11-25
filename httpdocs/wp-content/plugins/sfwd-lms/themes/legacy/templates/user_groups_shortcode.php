<?php
/**
 * Displays a user group lists.
 * This template is called from the [user_groups] shortcode.
 *
 * @param array $admin_groups Array of admin group IDs. 
 * @param array $user_groups Array of user group IDs. 
 * @param boolean $has_admin_groups True if there are admin groups.
 * @param boolean $has_user_groups True if there are user groups.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Groups
 */

?>

<div class="learndash-user-groups">
<?php if ( $has_admin_groups ) { ?>
	<div class="learndash-user-groups-section learndash-user-groups-section-leader-list">
		<div class="learndash-user-groups-header"><?php esc_html_e( 'Group Leader in : ', 'learndash' ); ?></div>
    	<ul class="learndash-user-groups-items">
			<?php 
				foreach ( $admin_groups as $group_id ) {
					if ( ! empty( $group_id ) ) {
						$group = get_post( $group_id );
						if ( ( $group ) && ( is_a( $group, 'WP_Post' ) ) ) {
							?>
							<li class="learndash-user-groups-item">
								<span class="learndash-user-groups-item-title"><?php echo $group->post_title; ?></span>
								<?php 
									if ( ! empty( $group->post_content ) ) { 
										SFWD_LMS::content_filter_control( false );
										
										$group_content = apply_filters('the_content', $group->post_content);
										$group_content = str_replace(']]>', ']]&gt;', $group_content );
										echo $group_content;

										SFWD_LMS::content_filter_control( true );
									} 
								?>
								</li>
							<?php 
						} 
					}
				}
			?>
		</ul>
	</div>
<?php } ?>

<?php if ( $has_user_groups ) { ?>
	<div class="learndash-user-groups-section learndash-user-groups-section-assigned-list">
		<div class="learndash-user-groups-header"><?php esc_html_e( 'Assigned Group(s) : ', 'learndash' ); ?></div>
    	<ul class="learndash-user-groups-items">
			<?php 
				foreach ( $user_groups as $group_id ) {
					if ( ! empty( $group_id ) ) {
						$group = get_post( $group_id );
						if ( ( $group ) && ( is_a( $group, 'WP_Post' ) ) ) {
							?>
							<li class="learndash-user-groups-item">
								<span class="learndash-user-groups-item-title"><?php echo $group->post_title; ?></span>
								<?php 
									if ( ! empty( $group->post_content ) ) { 
										$group_content = apply_filters('the_excerpt', $group->post_content);
										$group_content = str_replace(']]>', ']]&gt;', $group_content );
										echo $group_content;
									} 
								?>
							</li>
							<?php 
						} 
					}
				}
			?>
		</ul>
	</div>
<?php } ?>
</div>