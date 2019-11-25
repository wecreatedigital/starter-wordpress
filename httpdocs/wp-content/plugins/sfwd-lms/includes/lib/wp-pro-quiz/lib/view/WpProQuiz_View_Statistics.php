<?php
class WpProQuiz_View_Statistics extends WpProQuiz_View_View {
	/**
	 * @var WpProQuiz_Model_Quiz
	 */
	public $quiz;
	
	
	public function show() {

?>

<style>
.wpProQuiz_blueBox {
	padding: 20px; 
	background-color: rgb(223, 238, 255); 
	border: 1px dotted;
	margin-top: 10px;
}
.categoryTr th {
	background-color: #F1F1F1;
}
</style>


<div class="wrap wpProQuiz_statistics">
	<input type="hidden" id="quizId" value="<?php echo $this->quiz->getId(); ?>" name="quizId">
	<h2><?php echo sprintf( esc_html_x('%1$s: %2$s - Statistics', 'placeholders: Quiz, Quiz Name/Title', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), $this->quiz->getName() ); ?></h2>
	<p><a class="button-secondary" href="admin.php?page=ldAdvQuiz"><?php esc_html_e('back to overview', 'learndash'); ?></a></p>
	
	<?php if(!$this->quiz->isStatisticsOn()) { ?>
	<p style="padding: 30px; background: #F7E4E4; border: 1px dotted; width: 300px;">
		<span style="font-weight: bold; padding-right: 10px;"><?php esc_html_e('Stats not enabled', 'learndash'); ?></span>
		<a class="button-secondary" href="admin.php?page=ldAdvQuiz&action=addEdit&quizId=<?php echo $this->quiz->getId(); ?>&post_id=<?php echo @$_GET['post_id']; ?>"><?php esc_html_e('Activate statistics', 'learndash'); ?></a>
	</p>
	<?php return; } ?>
	
	<div style="padding: 10px 0px;">
		<a class="button-primary wpProQuiz_tab" id="wpProQuiz_typeUser" href="#"><?php esc_html_e('Users', 'learndash'); ?></a>
		<a class="button-secondary wpProQuiz_tab" id="wpProQuiz_typeOverview" href="#"><?php esc_html_e('Overview', 'learndash'); ?></a>
		<a class="button-secondary wpProQuiz_tab" id="wpProQuiz_typeForm" href="#"><?php esc_html_e('Custom fields', 'learndash'); ?></a>
	</div>
	
	<div id="wpProQuiz_loadData" class="wpProQuiz_blueBox" style="background-color: #F8F5A8; display: none;">
		<img alt="load" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" />
		<?php esc_html_e('Loading', 'learndash'); ?>
	</div>
	
	<div id="wpProQuiz_content" style="display: none;">
		
		<?php $this->tabUser(); ?>
		<?php $this->tabOverview(); ?>
		<?php $this->tabForms(); ?>
		
	</div>
	
</div>

<?php 		
	}
	
	private function tabUser() {
?>
	<div id="wpProQuiz_tabUsers" class="wpProQuiz_tabContent">
			<div class="wpProQuiz_blueBox" id="wpProQuiz_userBox" style="margin-bottom: 20px;">
				<div style="float: left;">
					<div style="padding-top: 6px;">
						<?php esc_html_e('Please select user name:', 'learndash'); ?>
					</div>
					
					<div style="padding-top: 6px;">
						<?php esc_html_e('Select a test:', 'learndash'); ?>
					</div>
					
				</div>
				
				<div style="float: left;">
					<div>
						<select name="userSelect" id="userSelect">
							<?php foreach($this->users as $user) { 
								if($user->ID == 0)
									echo '<option value="0">=== ', esc_html__('Anonymous user', 'learndash'),' ===</option>';
								else
									echo '<option value="', $user->ID, '">', $user->user_login, ' (', $user->display_name, ')</option>';
							} ?>
						</select>
					</div>
					
					<div>
						<select id="testSelect">
							<option value="0">=== <?php esc_html_e('average', 'learndash'); ?> ===</option>
						</select>
					</div>
				</div>
				<div style="clear: both;"></div>
			</div>
			
			<?php $this->formTable(); ?>
			
			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th scope="col" style="width: 50px;"></th>
						<th scope="col"><?php esc_html_e('Question', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Points', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Correct', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Incorrect', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Hints used', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Time', 'learndash'); ?> <span style="font-size: x-small;">(hh:mm:ss)</span></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Points scored', 'learndash'); ?></th>
						<th scope="col" style="width: 60px;"><?php esc_html_e('Results', 'learndash'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php 
				$gPoints = 0;
				foreach($this->questionList as $k => $ql) { 
					$index = 1;
					$cPoints = 0;
				?>
				
					<tr class="categoryTr">
						<th colspan="9">
							<span><?php esc_html_e('Category', 'learndash'); ?>:</span>
							<span style="font-weight: bold;"><?php echo $this->categoryList[$k]->getCategoryName(); ?></span>
						</th>
					</tr>
				
					<?php foreach($ql as $q) { 
						$gPoints += $q->getPoints();
						$cPoints += $q->getPoints();
					?>
						<tr id="wpProQuiz_tr_<?php echo $q->getId(); ?>">
							<th><?php echo $index++; ?></th>
							<th><?php echo $q->getTitle(); ?></th>
							<th class="wpProQuiz_points"><?php echo $q->getPoints(); ?></th>
							<th class="wpProQuiz_cCorrect" style="color: green;"></th>
							<th class="wpProQuiz_cIncorrect" style="color: red;"></th>
							<th class="wpProQuiz_cTip"></th>
							<th class="wpProQuiz_cTime"></th>
							<th class="wpProQuiz_cPoints"></th>
							<th></th>
						</tr>
					<?php } ?>
					
					<tr class="categoryTr" id="wpProQuiz_ctr_<?php echo $k; ?>">
						<th colspan="2">
							<span><?php esc_html_e('Sub-Total: ', 'learndash'); ?></span>
						</th>
						<th class="wpProQuiz_points"><?php echo $cPoints; ?></th>
						<th class="wpProQuiz_cCorrect" style="color: green;"></th>
						<th class="wpProQuiz_cIncorrect" style="color: red;"></th>
						<th class="wpProQuiz_cTip"></th>
						<th class="wpProQuiz_cTime"></th>
						<th class="wpProQuiz_cPoints"></th>
						<th class="wpProQuiz_cResult" style="font-weight: bold;"></th>
					</tr>
					
					<tr>
						<th colspan="9"></th>
					</tr>
					
				<?php } ?>
				</tbody>
				
				<tfoot>
					<tr id="wpProQuiz_tr_0">
						<th></th>
						<th><?php esc_html_e('Total', 'learndash'); ?></th>
						<th class="wpProQuiz_points"><?php echo $gPoints; ?></th>
						<th class="wpProQuiz_cCorrect" style="color: green;"></th>
						<th class="wpProQuiz_cIncorrect" style="color: red;"></th>
						<th class="wpProQuiz_cTip"></th>
						<th class="wpProQuiz_cTime"></th>
						<th class="wpProQuiz_cPoints"></th>
						<th class="wpProQuiz_cResult" style="font-weight: bold;"></th>
					</tr>
				</tfoot>
			</table>
		
			<div style="margin-top: 10px;">
				<div style="float: left;">
					<a class="button-secondary wpProQuiz_update" href="#"><?php esc_html_e('Refresh', 'learndash'); ?></a>
				</div>
				<div style="float: right;">
					<?php if(current_user_can('wpProQuiz_reset_statistics')) { ?>
						<a class="button-secondary" href="#" id="wpProQuiz_reset"><?php esc_html_e('Reset statistics', 'learndash'); ?></a>
						<a class="button-secondary" href="#" id="wpProQuiz_resetUser"><?php esc_html_e('Reset user statistics', 'learndash'); ?></a>
						<a class="button-secondary wpProQuiz_resetComplete" href="#"><?php esc_html_e('Reset entire statistic', 'learndash'); ?></a>
					<?php } ?>
				</div>
				<div style="clear: both;"></div>
			</div>
		</div>

<?php
	}
	
	private function tabOverview() {

?>

		<div id="wpProQuiz_tabOverview" class="wpProQuiz_tabContent" style="display: none;">
		
			<input type="hidden" value="<?php echo 0; ?>" name="gPoints" id="wpProQuiz_gPoints">
			
			<div id="poststuff">
				<div class="postbox">
					<h3 class="hndle"><?php esc_html_e('Filter', 'learndash'); ?></h3>
					<div class="inside">
						<ul>
							<li>
								<label>
									<?php echo sprintf( esc_html_x('Show only users, who solved the %s:', 'Show only users, who solved the quiz:', 'learndash'), learndash_get_custom_label_lower( 'quiz' )); ?>
									<input type="checkbox" value="1" id="wpProQuiz_onlyCompleted">
								</label>
							</li>
							<li>
								<label>
									<?php esc_html_e('How many entries should be shown on one page:', 'learndash'); ?>
									<select id="wpProQuiz_pageLimit">
										<option>1</option>
										<option>10</option>
										<option>50</option>
										<option selected="selected">100</option>
										<option>500</option>
										<option>1000</option>
									</select>
								</label>
							</li>
						</ul>
					</div>
				</div>
			</div>
			
			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e('User', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Points', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Correct', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Incorrect', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Hints used', 'learndash'); ?></th>
						<th scope="col" style="width: 100px;"><?php esc_html_e('Time', 'learndash'); ?> <span style="font-size: x-small;">(hh:mm:ss)</span></th>
						<th scope="col" style="width: 60px;"><?php esc_html_e('Results', 'learndash'); ?></th>
					</tr>
				</thead>
				<tbody id="wpProQuiz_statistics_overview_data">
					<tr style="display: none;">
						<th><a href="#"></a></th>
						<th class="wpProQuiz_cPoints"></th>
						<th class="wpProQuiz_cCorrect" style="color: green;"></th>
						<th class="wpProQuiz_cIncorrect" style="color: red;"></th>
						<th class="wpProQuiz_cTip"></th>
						<th class="wpProQuiz_cTime"></th>
						<th class="wpProQuiz_cResult" style="font-weight: bold;"></th>
					</tr>
				</tbody>
			</table>
		
			<div style="margin-top: 10px;">
				<div style="float: left;">
					<input style="font-weight: bold;" class="button-secondary" value="&lt;" type="button" id="wpProQuiz_pageLeft">
					<select id="wpProQuiz_currentPage"><option value="1">1</option></select>
					<input style="font-weight: bold;" class="button-secondary"value="&gt;" type="button" id="wpProQuiz_pageRight">
				</div>
				<div style="float: right;">
					<a class="button-secondary wpProQuiz_update" href="#"><?php esc_html_e('Refresh', 'learndash'); ?></a>
					<?php if(current_user_can('wpProQuiz_reset_statistics')) { ?>
					<a class="button-secondary wpProQuiz_resetComplete" href="#"><?php esc_html_e('Reset entire statistic', 'learndash'); ?></a>
					<?php } ?>
				</div>
				<div style="clear: both;"></div>
			</div>
		
		</div>

<?php 
	}
	
	private function tabForms() {
	?>
	
		<div id="wpProQuiz_tabFormOverview" class="wpProQuiz_tabContent" style="display: none;">
			
			<div id="poststuff">
				<div class="postbox">
					<h3 class="hndle"><?php esc_html_e('Filter', 'learndash'); ?></h3>
					<div class="inside">
						<ul>
							<li>
								<label>
									<?php esc_html_e('Which users should be displayed:', 'learndash'); ?>
									<select id="wpProQuiz_formUser">
										<option value="0"><?php esc_html_e('all', 'learndash'); ?></option>
										<option value="1"><?php esc_html_e('only registered users', 'learndash'); ?></option>
										<option value="2"><?php esc_html_e('only anonymous users', 'learndash'); ?></option>
									</select>
								</label>
							</li>
							<li>
								<label>
									<?php esc_html_e('How many entries should be shown on one page:', 'learndash'); ?>
									<select id="wpProQuiz_fromPageLimit">
										<option>1</option>
										<option>10</option>
										<option>50</option>
										<option selected="selected">100</option>
										<option>500</option>
										<option>1000</option>
									</select>
								</label>
							</li>
						</ul>
					</div>
				</div>
			</div>
			
			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e('Username', 'learndash'); ?></th>
						<th scope="col" style="width: 200px;"><?php esc_html_e('Date', 'learndash'); ?></th>
						<th scope="col" style="width: 60px;"><?php esc_html_e('Results', 'learndash'); ?></th>
					</tr>
				</thead>
				<tbody id="wpProQuiz_statistics_form_data">
					<tr style="display: none;">
						<th><a href="#" class="wpProQuiz_cUsername"></a></th>
						<th class="wpProQuiz_cCreateTime"></th>
						<th class="wpProQuiz_cResult" style="font-weight: bold;"></th>
					</tr>
				</tbody>
			</table>
			
			<div style="margin-top: 10px;">
				<div style="float: left;">
					<input style="font-weight: bold;" class="button-secondary" value="&lt;" type="button" id="wpProQuiz_formPageLeft">
					<select id="wpProQuiz_formCurrentPage"><option value="1">1</option></select>
					<input style="font-weight: bold;" class="button-secondary"value="&gt;" type="button" id="wpProQuiz_formPageRight">
				</div>
				<div style="float: right;">
					<a class="button-secondary wpProQuiz_update" href="#"><?php esc_html_e('Refresh', 'learndash'); ?></a>
					<?php if(current_user_can('wpProQuiz_reset_statistics')) { ?>
					<a class="button-secondary wpProQuiz_resetComplete" href="#"><?php esc_html_e('Reset entire statistic', 'learndash'); ?></a>
					<?php } ?>
				</div>
				<div style="clear: both;"></div>
			</div>
			
		</div>

	
	<?php 
	}
	
	private function formTable() {
		if(!$this->quiz->isFormActivated())
			return;
	?>
		<div id="wpProQuiz_form_box">
			<div id="poststuff">
				<div class="postbox">
					<h3 class="hndle"><?php esc_html_e('Custom fields', 'learndash'); ?></h3>
					<div class="inside">
						<table>
							<tbody>
								<?php foreach($this->forms as $form) { 
									/* @var $form WpProQuiz_Model_Form */
								?>
									<tr>
										<td style="padding: 5px;"><?php echo esc_html($form->getFieldname()); ?></td>
										<td id="form_id_<?php echo $form->getFormId();?>">asdfffffffffffffffffffffsadfsdfa sf asd fas</td>
									</tr>
								<?php  } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	<?php 
	}
}