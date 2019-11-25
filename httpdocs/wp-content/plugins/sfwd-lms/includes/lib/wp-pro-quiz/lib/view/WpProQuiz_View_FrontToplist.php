<?php
class WpProQuiz_View_FrontToplist extends WpProQuiz_View_View {
	
	public function show() {
?>
<div style="margin-bottom: 30px; margin-top: 10px;" class="wpProQuiz_toplist" data-quiz_id="<?php echo $this->quiz->getId(); ?>">
	<?php if(!$this->inQuiz) { ?>
	<h2><?php esc_html_e('Leaderboard', 'learndash'); ?>: <?php echo $this->quiz->getName(); ?></h2>
	<?php } ?>
	<table class="wpProQuiz_toplistTable">
		<caption><?php printf(__('maximum of %s points', 'learndash'), '<span class="wpProQuiz_max_points">'. $this->points .'</span>'); ?></caption>
		<thead>
			<tr>
				<th class="col-pos"><?php esc_html_e('Pos.', 'learndash'); ?></th>
				<th class="col-name"><?php esc_html_e('Name', 'learndash'); ?></th>
				<th class="col-date"><?php esc_html_e('Entered on', 'learndash'); ?></th>
				<th class="col-points"><?php esc_html_e('Points', 'learndash'); ?></th>
				<th class="col-results"><?php esc_html_e('Result', 'learndash'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="5"><?php esc_html_e('Table is loading', 'learndash'); ?></td>
			</tr>
			<tr style="display: none;">
				<td colspan="5"><?php esc_html_e('No data available', 'learndash'); ?></td>
			</tr>
			<tr style="display: none;">
				<td class="col-pos"></td>
				<td class="col-name"></td>
				<td class="col-date"></td>
				<td class="col-points"></td>
				<td class="col-results"></td>
			</tr>
		</tbody>
	</table>
</div>

<?php 
	}
}