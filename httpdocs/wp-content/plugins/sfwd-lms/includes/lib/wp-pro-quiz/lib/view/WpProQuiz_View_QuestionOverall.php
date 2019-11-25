<?php
class WpProQuiz_View_QuestionOverall extends WpProQuiz_View_View {
	
	public function show() {
		global $learndash_question_types;
?>
<style>
.wpProQuiz_questionCopy {
	padding: 20px; 
	background-color: rgb(223, 238, 255); 
	border: 1px dotted;
	margin-top: 10px;
	display: none;
}
</style>
<div class="wrap wpProQuiz_questionOverall">
	<h1><?php echo LearnDash_Custom_Label::get_label( 'quiz' ) ?>: <?php echo $this->quiz->getName(); ?></h1>
	<div id="sortMsg" class="updated" style="display: none;"><p><strong><?php 
		printf(
			// translators: placeholder: Questions.
			esc_html_x( '%s sorted', 'placeholder: Questions', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'questions' )
		);
		?></strong></p></div>
	<br>
	<p>
		<?php if(current_user_can('wpProQuiz_edit_quiz')) { ?>
		<a class="button-secondary" href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo $this->quiz->getId(); ?>&post_id=<?php echo @$_GET['post_id']; ?>"><?php
			printf(
				// translators: placeholder: Question.
				esc_html_x( 'Add %s', 'placeholder: Question', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'questions' )
			);
			?></a>
		<?php } ?>
	</p>
	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th scope="col" style="width: 50px;"></th>
				<th scope="col"><?php esc_html_e('Name', 'learndash'); ?></th>
				<th scope="col"><?php esc_html_e('Type', 'learndash'); ?></th>
				<th scope="col"><?php esc_html_e('Category', 'learndash'); ?></th>
				<th scope="col"><?php esc_html_e('Points', 'learndash'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$index = 1;
			$points = 0;

			if(count($this->question)) {

				foreach ($this->question as $question) {				
					$points += $question->getPoints();
				
				?>
				<tr id="wpProQuiz_questionId_<?php echo $question->getId(); ?>">
					<th><?php echo $index++; ?></th>
					<td>
						<strong><?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { 
							$edit_link = add_query_arg(
								array(
									'page'			=>	'ldAdvQuiz',
									'module'		=>	'question',
									'action'		=>	'addEdit',
									'quiz_id'		=> 	$this->quiz->getId(),
									'questionId'	=>	$question->getId(),
									'post_id'		=>	@$_GET['post_id']
								),
								admin_url('admin.php')
							);
							?><a href="<?php echo $edit_link ?>"><?php } ?><?php echo $question->getTitle(); ?><?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?></a><?php } ?></strong>
							<div class="row-actions">
							<?php if ( current_user_can( 'wpProQuiz_edit_quiz' ) ) { ?>
								<span><a href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo $this->quiz->getId(); ?>&questionId=<?php echo $question->getId(); ?>&post_id=<?php echo @$_GET['post_id']; ?>"><?php esc_html_e('Edit', 'learndash'); ?></a> |
							</span>
							<?php } if(current_user_can('wpProQuiz_delete_quiz')) { ?>
							<span>
								<a style="color: red;" class="wpProQuiz_delete" href="admin.php?page=ldAdvQuiz&module=question&action=delete&quiz_id=<?php echo $this->quiz->getId(); ?>&id=<?php echo $question->getId(); ?>&post_id=<?php echo @$_GET['post_id']; ?>"><?php esc_html_e('Delete', 'learndash'); ?></a> |
							</span>
							<?php } if(current_user_can('wpProQuiz_edit_quiz')) { ?>
							<span>
								<a class="wpProQuiz_move" href="#" style="cursor:move;"><?php esc_html_e('Move', 'learndash'); ?></a>
							</span>
							<?php } ?>
						</div>
					</td>
					<td>
						<?php 
							$question_type = $question->getAnswerType(); 
							if (isset($learndash_question_types[$question_type])) {
								echo $learndash_question_types[$question_type];
							}
						?>
					</td>
					<td>
						<?php echo $question->getCategoryName(); ?>
					</td>
					<td><?php echo $question->getPoints(); ?></td>
				</tr>
				<?php 
				} 
			} else { 
				?>
				<tr>
					<td colspan="5" style="text-align: center; font-weight: bold; padding: 10px;"><?php esc_html_e('No data available', 'learndash'); ?></td>
				</tr>
				<?php 
			} 
			?>
		</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th style="font-weight: bold;"><?php esc_html_e('Total', 'learndash'); ?></th>
				<th></th>
				<th></th>
				<th style="font-weight: bold;"><?php echo $points; ?></th>
			</tr>
		</tfoot>
	</table>
	<p>
		<?php do_action( 'learndash_questions_buttons_before' ); ?>
		<?php if(current_user_can('wpProQuiz_edit_quiz')) { ?>
		<a class="button-secondary" href="admin.php?page=ldAdvQuiz&module=question&action=addEdit&quiz_id=<?php echo $this->quiz->getId(); ?>&post_id=<?php echo @$_GET['post_id']; ?>"><?php esc_html_e('Add question', 'learndash'); ?></a>
		<a class="button-secondary" href="#" id="wpProQuiz_saveSort"><?php esc_html_e('Save order', 'learndash'); ?></a>
		<a class="button-secondary" href="#" id="wpProQuiz_questionCopy"><?php echo sprintf( esc_html_x('Copy questions from another %s', 'Copy questions from another Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></a>
		<?php } ?>
		<?php do_action( 'learndash_questions_buttons_after' ); ?>
	</p>
	<?php do_action( 'learndash_questions_toolbox_before' ); ?>
	<div class="wpProQuiz_questionCopy">
		<form action="admin.php?page=ldAdvQuiz&module=question&quiz_id=<?php echo $this->quiz->getId(); ?>&action=copy_question" method="POST">
			<h2 style="margin-top: 0;"><?php echo sprintf( esc_html_x('Copy questions from another %s', 'Copy questions from another Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></h2>
			<p><?php echo sprintf( esc_html_x('Here you can copy questions from another %s into this %s. (Multiple selection enabled)', 'placeholders: quiz, quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) ); ?></p>
			
			<div style="padding: 20px; display: none;" id="loadDataImg">
				<img alt="load" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" />
				<?php esc_html_e('Loading', 'learndash'); ?>
			</div>
			
			<div style="padding: 10px;">
				<select name="copyIds[]" size="15" multiple="multiple" style="min-width: 200px; display: none;" id="questionCopySelect">
				</select>
			</div>
			
			<input class="button-primary" name="questionCopy" value="<?php esc_html_e('Copy questions', 'learndash'); ?>" type="submit">
		</form>
	</div>
</div>
<?php 
	}
}