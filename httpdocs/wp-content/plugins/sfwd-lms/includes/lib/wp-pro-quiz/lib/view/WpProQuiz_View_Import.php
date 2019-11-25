<?php
class WpProQuiz_View_Import extends WpProQuiz_View_View {
	
	public function show() {
?>
<style>
.wpProQuiz_importList {
	list-style: none;
	margin: 0;
	padding: 0;
}
.wpProQuiz_importList li {
	float: left;
	padding: 5px;
	border: 1px solid #B3B3B3;
	margin-right: 5px;
	background-color: #DAECFF;
}
</style>
<div class="wrap wpProQuiz_importOverall">
	<h2><?php esc_html_e('Import', 'learndash'); ?></h2>
	<br>
	<?php if($this->error) { ?>
	<div style="padding: 10px; background-color: rgb(255, 199, 199); margin-top: 20px; border: 1px dotted;">
		<h3 style="margin-top: 0;"><?php esc_html_e('Error', 'learndash'); ?></h3>
		<div>
			<?php echo $this->error; ?>
		</div>
	</div>
	<?php } else if($this->finish) { ?>
	<div style="padding: 10px; background-color: #C7E4FF; margin-top: 20px; border: 1px dotted;">
		<h3 style="margin-top: 0;"><?php esc_html_e('Successfully', 'learndash'); ?></h3>
		<div>
			<?php
				$edit_link = '';
				if ( $this->import_post_id ) {
					$edit_link = '<a href="'. get_edit_post_link( $this->import_post_id ) .'">' . 
					sprintf( 
						// translators: placeholder: Quiz Title.
						esc_html_x('%s', 'placeholder: Quiz Title', 'learndash'),
						get_the_title( $this->import_post_id )
					) . '</a>';
				}
			
				echo wpautop( 
					sprintf(
						// translators: placeholder: link to Imported Quiz
						esc_html_x( 'Import completed successfully: %s', 'placeholder: link to Imported Quiz.', 'learndash' ), 
						$edit_link
					)
				); 
			?>
		</div>
	</div>
	<?php
	/*
		echo wpautop( '<a href="'. admin_url( 'admin.php?page=ldAdvQuiz' ) .'">' . 
			sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( 'Return to %s Import/Export.', 'placeholder: Quiz', 'learndash' ), 
				LearnDash_Custom_Label::get_label( 'quiz' )
			) . '</a>'
		); 
	*/		
		?>
	<?php } else { ?>
	<form method="post">
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th scope="col" width="30px"></th>
					<th scope="col" width="40%"><?php echo sprintf( esc_html_x('%s name', 'Quiz name', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' )); ?></th>
					<th scope="col"><?php esc_html_e('Questions', 'learndash'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($this->import['master'] as $master) { ?>
				<tr>
					<th>
						<input type="checkbox" name="importItems[]" value="<?php echo $master->getId(); ?>" checked="checked">
					</th>
					<th><?php echo $master->getName(); ?></th>
					<th>
						<ul class="wpProQuiz_importList">
						<?php if(isset($this->import['question'][$master->getId()])) { ?>
						<?php foreach($this->import['question'][$master->getId()] as $question) { ?>
							<li><?php echo $question->getTitle(); ?></li>
						<?php } } ?>
						</ul>
						<div style="clear: both;"></div>
					</th>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<input name="importData" value="<?php echo $this->importData; ?>" type="hidden">
		<input name="importType" value="<?php echo $this->importType; ?>" type="hidden">
		<input style="margin-top: 20px;" class="button-primary" name="importSave" value="<?php esc_html_e('Start import', 'learndash'); ?>" type="submit">
	</form>
	<?php } ?>
</div>

<?php 	
	}
}