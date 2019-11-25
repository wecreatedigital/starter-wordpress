<?php
class WpProQuiz_View_QuizEdit extends WpProQuiz_View_View {
	
	/**
	 * @var WpProQuiz_Model_Quiz
	 */
	public $quiz;

	public function show_advanced( $get = null ) {
		?>
		<input name="name" id="wpProQuiz_title" type="hidden" class="regular-text" value="<?php echo $this->quiz->getName(); ?>">
		<input name="text" type="hidden" value="AAZZAAZZ" />
		<div class="wrap wpProQuiz_quizEdit">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Hide %s title', 'Hide quiz title', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide title', 'learndash'); ?></span>
								</legend>
								<label for="title_hidden">
									<input type="checkbox" id="title_hidden" value="1" name="titleHidden" <?php echo $this->quiz->isTitleHidden() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('The title serves as %s heading.', 'The title serves as quiz heading.', 'learndash'), learndash_get_custom_label_lower( 'quiz' )); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Hide "Restart %s" button', 'Hide "Restart quiz" button', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x('Hide "Restart %s" button', 'Hide "Restart quiz" button', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?></span>
								</legend>
								<label for="btn_restart_quiz_hidden">
									<input type="checkbox" id="btn_restart_quiz_hidden" value="1" name="btnRestartQuizHidden" <?php echo $this->quiz->isBtnRestartQuizHidden() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('Hide the "Restart %s" button in the Frontend.', 'Hide the "Restart quiz" button in the Frontend.', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide "View question" button', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide "View question" button', 'learndash'); ?></span>
								</legend>
								<label for="btn_view_question_hidden">
									<input type="checkbox" id="btn_view_question_hidden" value="1" name="btnViewQuestionHidden" <?php echo $this->quiz->isBtnViewQuestionHidden() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Hide the "View question" button in the Frontend.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Display question randomly', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Display question randomly', 'learndash'); ?></span>
								</legend>
								<label for="question_random">
									<input type="checkbox" id="question_random" value="1" name="questionRandom" <?php echo $this->quiz->isQuestionRandom() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Display answers randomly', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Display answers randomly', 'learndash'); ?></span>
								</legend>
								<label for="answer_random">
									<input type="checkbox" id="answer_random" value="1" name="answerRandom" <?php echo $this->quiz->isAnswerRandom() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Sort questions by category', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Sort questions by category', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="sortCategories" <?php $this->checked($this->quiz->isSortCategories()); ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Also works in conjunction with the "display randomly question" option.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Time limit', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Time limit', 'learndash'); ?></span>
								</legend>
								<label for="time_limit">
									<input type="number" min="0" class="small-text" id="time_limit" value="<?php echo $this->quiz->getTimeLimit(); ?>" name="timeLimit"> <?php esc_html_e('Seconds', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('0 = no limit', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Protect %s Answers in Browser Cookie', 'Protect Quiz Answers in Browser Cookie', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x('Use cookies for %s Answers', 'Use cookies for Quiz Answers', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></span>
								</legend>
								<label for="time_limit_cookie">
									<input type="number" min="0" class="small-text" id="time_limit_cookie" value="<?php echo intval($this->quiz->getTimeLimitCookie()); ?>" name="timeLimitCookie"> <?php esc_html_e('Seconds', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x("0 = Don't save answers. This option will save the user's answers into a browser cookie until the %s is submitted.", 'placeholders: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Statistics', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Statistics', 'learndash'); ?></span>
								</legend>
								<label for="statistics_on">
									<input type="checkbox" id="statistics_on" value="1" name="statisticsOn" <?php echo ( !isset( $_GET["post"] ) || $this->quiz->isStatisticsOn() ) ? 'checked="checked"' : ''; ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('Statistics about right or wrong answers. Statistics will be saved by completed %s, not after every question. The statistics is only visible over administration menu. (internal statistics)', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr id="statistics_ip_lock_tr" style="display: none;">
						<th scope="row">
							<?php esc_html_e('Statistics IP-lock', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Statistics IP-lock', 'learndash'); ?></span>
								</legend>
								<label for="statistics_ip_lock">
									<input type="number" min="0" class="small-text" id="statistics_ip_lock" value="<?php echo ($this->quiz->getStatisticsIpLock() === null) ? 0 : $this->quiz->getStatisticsIpLock(); ?>" name="statisticsIpLock">
									<?php esc_html_e('in minutes (recommended 1440 minutes = 1 day)', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Protect the statistics from spam. Result will only be saved every X minutes from same IP. (0 = deactivated)', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr id="statistics_show_profile_tr" style="display: none;">
						<th scope="row">
							<?php esc_html_e('View Profile Statistics', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('View Profile Statistics', 'learndash'); ?></span>
								</legend>
								<label for="statistics_on">
									<input type="checkbox" id="view_profile_statistics_on" value="1" name="viewProfileStatistics" <?php echo ( !isset( $_GET["post"] ) || $this->quiz->getViewProfileStatistics() ) ? 'checked="checked"' : ''; ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('Enable user to view statistics for this %s on their profile.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>


					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Execute %s only once', 'Execute quiz only once', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
							
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x('Execute %s only once', 'Execute quiz only once', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?></span>
								</legend>
								
								<label>
									<input type="checkbox" value="1" name="quizRunOnce" <?php echo $this->quiz->isQuizRunOnce() ? 'checked="checked"' : '' ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you activate this option, the user can complete the %1$s only once. Afterwards the %2$s is blocked for this user.', 'placeholders: quiz, quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
								
								<div id="wpProQuiz_quiz_run_once_type" style="margin-bottom: 5px; display: none;">
									<?php esc_html_e('This option applies to:', 'learndash');
									
									$quizRunOnceType = $this->quiz->getQuizRunOnceType();
									$quizRunOnceType = ($quizRunOnceType == 0) ? 1: $quizRunOnceType; 
									
									?>		
									<label>
										<input name="quizRunOnceType" type="radio" value="1" <?php echo ($quizRunOnceType == 1) ? 'checked="checked"' : ''; ?>>
										<?php esc_html_e('all users', 'learndash'); ?>
									</label>
									<label>
										<input name="quizRunOnceType" type="radio" value="2" <?php echo ($quizRunOnceType == 2) ? 'checked="checked"' : ''; ?>>
										<?php esc_html_e('registered useres only', 'learndash'); ?>
									</label>
									<label>
										<input name="quizRunOnceType" type="radio" value="3" <?php echo ($quizRunOnceType == 3) ? 'checked="checked"' : ''; ?>>
										<?php esc_html_e('anonymous users only', 'learndash'); ?>
									</label>
									
									<div id="wpProQuiz_quiz_run_once_cookie" style="margin-top: 10px;">
										<label>
											<input type="checkbox" value="1" name="quizRunOnceCookie" <?php echo $this->quiz->isQuizRunOnceCookie() ? 'checked="checked"' : '' ?>>
											<?php esc_html_e('user identification by cookie', 'learndash'); ?>
										</label>
										<p class="description">
											<?php esc_html_e('If you activate this option, a cookie is set additionally for unregistrated (anonymous) users. This ensures a longer assignment of the user than the simple assignment by the IP address.', 'learndash'); ?>
										</p>
									</div>
									
									<div style="margin-top: 15px;">
										<input class="button-secondary" type="button" name="resetQuizLock" value="<?php esc_html_e('Reset the user identification', 'learndash'); ?>">
										<span id="resetLockMsg" style="display:none; background-color: rgb(255, 255, 173); border: 1px solid rgb(143, 143, 143); padding: 4px; margin-left: 5px; "><?php esc_html_e('User identification has been reset.', 'learndash'); ?></span>
										<p class="description">
											<?php esc_html_e('Resets user identification for all users.', 'learndash'); ?>
										</p>
									</div>
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Show only specific number of questions', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Show only specific number of questions', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="showMaxQuestion" <?php echo $this->quiz->isShowMaxQuestion() ? 'checked="checked"' : '' ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, maximum number of displayed questions will be X from X questions. (The output of questions is random)', 'learndash'); ?>
								</p>
								<div id="wpProQuiz_showMaxBox" style="display: none;">
									<label>
										<?php esc_html_e('How many questions should be displayed simultaneously:', 'learndash'); ?>
										<input class="small-text" type="text" name="showMaxQuestionValue" value="<?php echo $this->quiz->getShowMaxQuestionValue(); ?>">
									</label>
									<label>
										<input type="checkbox" value="1" name="showMaxQuestionPercent" <?php echo $this->quiz->isShowMaxQuestionPercent() ? 'checked="checked"' : '' ?>>
										<?php esc_html_e('in percent', 'learndash'); ?>
									</label>
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Prerequisites', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Prerequisites', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="prerequisite" <?php $this->checked($this->quiz->isPrerequisite()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, you can choose %1$s, which user have to finish before he can start this %2$s.', 'placeholders: quiz, quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
								<p class="description">
									<?php echo sprintf( esc_html_x('In all selected %s statistic function have to be active. If it is not it will be activated automatically.', 'placeholders: quizzes', 'learndash'), learndash_get_custom_label_lower( 'quizzes' ) ); ?>
								</p>
								<div id="prerequisiteBox" style="display: none;">
									<table id="learndash-prerequisite-table">
										<tr>
											<th class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-left"><?php echo sprintf( esc_html_x('%s', 'Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></th>
											<th class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-center"></th>
											<th class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-right"><?php echo sprintf( esc_html_x('Prerequisites (This %s has to be finished)', 'Prerequisites (This quiz has to be finished)', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?></th>
										</tr>
										<tr>
											<td class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-left">
												<select class="learndash-quiz-prerequisite-list" multiple="multiple" size="8" name="quizList">
													<?php foreach($this->quizList as $list) {
														if(in_array($list['id'], $this->prerequisiteQuizList))
															continue;
														
															echo '<option value="'.$list['id'].'" title="'.$list['name'].'">'.$list['name'].'</option>';
													} ?>
												</select>
											</td>
											<td class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-center" style="text-align: center;">
												<div>
													<input type="button" id="btnPrerequisiteAdd" value="&gt;&gt;">
												</div>
												<div>
													<input type="button" id="btnPrerequisiteDelete" value="&lt;&lt;">
												</div>
											</td>
											<td class="learndash-quiz-prerequisite-list learndash-quiz-prerequisite-list-right">
												<select class="learndash-quiz-prerequisite-list" multiple="multiple" size="8" name="prerequisiteList[]">
													<?php foreach($this->quizList as $list) {
														if(!in_array($list['id'], $this->prerequisiteQuizList))
															continue;
														
															echo '<option value="'.$list['id'].'" title="'.$list['name'].'">'.$list['name'].'</option>';
													} ?>
												</select>
											</td>
										</tr>
									</table>
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Question overview', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Question overview', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="showReviewQuestion" <?php $this->checked($this->quiz->isShowReviewQuestion()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Add at the top of the quiz a question overview, which allows easy navigation. Additional questions can be marked "to review".', 'learndash'); ?>
								</p>
								<p class="description">
									<?php echo sprintf( esc_html_x('Additional %s overview will be displayed, before %s is finished.', 'placeholders: quiz, quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )); ?>
								</p>
								
							</fieldset>
						</td>
					</tr>
					<tr class="wpProQuiz_reviewQuestionOptions" style="display: none;">
						<th scope="row">
							<?php echo sprintf( esc_html_x( '%s-summary', 'Quiz-summary', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x( '%s-summary', 'Quiz-summary', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="quizSummaryHide" <?php $this->checked($this->quiz->isQuizSummaryHide()); ?>>
									<?php esc_html_e('Deactivate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, no %1$s overview will be displayed, before finishing %2$s.', 'placeholders: quiz, quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr class="wpProQuiz_reviewQuestionOptions" style="display: none;">
						<th scope="row">
							<?php esc_html_e('Skip question', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Skip question', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="skipQuestionDisabled" <?php $this->checked($this->quiz->isSkipQuestionDisabled()); ?>>
									<?php esc_html_e('Deactivate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, user won\'t be able to skip question. (only in "Overview -> next" mode). User still will be able to navigate over "Question-Overview"', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Admin e-mail notification', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Admin e-mail notification', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="radio" name="emailNotification" value="<?php echo WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_NONE; ?>" <?php $this->checked($this->quiz->getEmailNotification(), WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_NONE); ?>>
									<?php esc_html_e('Deactivate', 'learndash'); ?>
								</label>
								<label>
									<input type="radio" name="emailNotification" value="<?php echo WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER; ?>" <?php $this->checked($this->quiz->getEmailNotification(), WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER); ?>>
									<?php esc_html_e('for registered users only', 'learndash'); ?>
								</label>
								<label>
									<input type="radio" name="emailNotification" value="<?php echo WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL; ?>" <?php $this->checked($this->quiz->getEmailNotification(), WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL); ?>>
									<?php esc_html_e('for all users', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, you will be informed if a user completes this %s.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
								<p class="description">
									<?php esc_html_e('E-Mail settings can be edited in global settings.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('User e-mail notification', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('User e-mail notification', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="userEmailNotification" value="1" <?php $this->checked($this->quiz->isUserEmailNotification()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, an email is sent with his %s result to the user. (only registered users)', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'course' ) ); ?>
								</p>
								<p class="description">
									<?php esc_html_e('E-Mail settings can be edited in global settings.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Autostart', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Autostart', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="autostart" value="1" <?php $this->checked($this->quiz->isAutostart()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, the %s will start automatically after the page is loaded.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Only registered users are allowed to start the %s', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x('Only registered users are allowed to start the %s', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="startOnlyRegisteredUser" value="1" <?php $this->checked($this->quiz->isStartOnlyRegisteredUser()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, only registered users allowed start the %s.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function show_templates($get = null) {
		$template_loaded_id = 0;
		if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) {
			$template_loaded_id = intval( $_GET['templateLoadId'] );
		} 
		?>
		<div class="wrap wpProQuiz_quizEdit">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php _e('Use Template', 'learndash' ); ?>
						</th>
						<td>
							<select name="templateLoadId">
								<option value=""><?php _e('Select Template', 'learndash' ); ?></option>
								<?php 
									foreach($this->templates as $template) {
										echo '<option ' . selected( $template_loaded_id, $template->getTemplateId() ) . ' value="', $template->getTemplateId(), '">', esc_html($template->getName()), '</option>';
									}
								?>
							</select>
							<input type="submit" name="templateLoad" value="<?php esc_html_e('load template', 'learndash'); ?>" class="button-primary">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php _e('Save as Template', 'learndash' ); ?>
						</th>
						<td>
							<input type="text" placeholder="<?php esc_html_e('template name', 'learndash'); ?>" class="regular-text" name="templateName" style="border: 1px solid rgb(255, 134, 134);">
							<select name="templateSaveList">
								<option value="0">=== <?php esc_html_e('Create new template', 'learndash'); ?> === </option>
								<?php 
									foreach($this->templates as $template) {
										echo '<option value="', $template->getTemplateId(), '">', esc_html($template->getName()), '</option>';
									}
								?>
							</select>
			
							<input type="submit" name="template" class="button-primary" id="wpProQuiz_saveTemplate" value="<?php esc_html_e('Save as template', 'learndash'); ?>">
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function resultOptions() {
		?>
		<div class="wrap wpProQuiz_quizEdit">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e('Show average points', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Show average points', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="showAverageResult" <?php $this->checked($this->quiz->isShowAverageResult()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('Statistics-function must be enabled.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Show category score', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Show category score', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="showCategoryScore" value="1" <?php $this->checked($this->quiz->isShowCategoryScore()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, the results of each category is displayed on the results page.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide correct questions - display', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide correct questions - display', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="hideResultCorrectQuestion" value="1" <?php $this->checked($this->quiz->isHideResultCorrectQuestion()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you select this option, no longer the number of correctly answered questions are displayed on the results page.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo sprintf( esc_html_x('Hide %s time - display', 'Hide quiz time - display', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php echo sprintf( esc_html_x('Hide %s time - display', 'Hide quiz time - display', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="hideResultQuizTime" value="1" <?php $this->checked($this->quiz->isHideResultQuizTime()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('If you enable this option, the time for finishing the %s won\'t be displayed on the results page anymore.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide score - display', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide score - display', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" name="hideResultPoints" value="1" <?php $this->checked($this->quiz->isHideResultPoints()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, final score won\'t be displayed on the results page anymore.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php 
	}
	
	public function questionOptions() {
		?>
		<div class="wrap wpProQuiz_quizEdit">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e('Show points', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Show points', 'learndash'); ?></span>
								</legend>
								<label for="show_points">
									<input type="checkbox" id="show_points" value="1" name="showPoints" <?php echo $this->quiz->isShowPoints() ? 'checked="checked"' : '' ?> >
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php echo sprintf( esc_html_x('Shows in %s, how many points are reachable for respective question.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' )); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Number answers', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Number answers', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="numberedAnswer" <?php echo $this->quiz->isNumberedAnswer() ? 'checked="checked"' : '' ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If this option is activated, all answers are numbered (only single and multiple choice)', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide correct- and incorrect message', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide correct- and incorrect message', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="hideAnswerMessageBox" <?php echo $this->quiz->isHideAnswerMessageBox() ? 'checked="checked"' : '' ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, no correct- or incorrect message will be displayed.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Correct and incorrect answer mark', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Correct and incorrect answer mark', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="disabledAnswerMark" <?php echo $this->quiz->isDisabledAnswerMark() ? 'checked="checked"' : '' ?>>
									<?php esc_html_e('Deactivate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, answers won\'t be color highlighted as correct or incorrect. ', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Force user to answer each question', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Force user to answer each question', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="forcingQuestionSolve" <?php $this->checked($this->quiz->isForcingQuestionSolve()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, the user is forced to answer each question.', 'learndash'); ?> <br>
									<?php esc_html_e('If the option "Question overview" is activated, this notification will appear after end of the quiz, otherwise after each question.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide question position overview', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide question position overview', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="hideQuestionPositionOverview" <?php $this->checked($this->quiz->isHideQuestionPositionOverview()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, the question position overview is hidden.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Hide question numbering', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Hide question numbering', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="hideQuestionNumbering" <?php $this->checked($this->quiz->isHideQuestionNumbering()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, the question numbering is hidden.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Display category', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Display category', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" value="1" name="showCategory" <?php $this->checked($this->quiz->isShowCategory()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, category will be displayed in the question.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<?php 
	}
	
	public function leaderboardOptions() {
		?>
		<div class="wrap wpProQuiz_quizEdit">
			<p><?php esc_html_e('The leaderboard allows users to enter results in public list and to share the result this way.', 'learndash'); ?></p>
			<p><?php esc_html_e('The leaderboard works independent from internal statistics function.', 'learndash'); ?></p>
			<table class="form-table">
				<tbody id="toplistBox">
					<tr>
						<th scope="row">
							<?php esc_html_e('Leaderboard', 'learndash'); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="toplistActivated" value="1" <?php echo $this->quiz->isToplistActivated() ? 'checked="checked"' : ''; ?>> 
								<?php esc_html_e('Activate', 'learndash'); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Who can sign up to the list', 'learndash'); ?>
						</th>
						<td>
							<label>
								<input name="toplistDataAddPermissions" type="radio" value="1" <?php echo $this->quiz->getToplistDataAddPermissions() == 1 ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('all users', 'learndash'); ?>
							</label>
							<label>
								<input name="toplistDataAddPermissions" type="radio" value="2" <?php echo $this->quiz->getToplistDataAddPermissions() == 2 ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('registered users only', 'learndash'); ?>
							</label>
							<label>
								<input name="toplistDataAddPermissions" type="radio" value="3" <?php echo $this->quiz->getToplistDataAddPermissions() == 3 ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('anonymous users only', 'learndash'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('Not registered users have to enter name and e-mail (e-mail won\'t be displayed)', 'learndash'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('insert automatically', 'learndash'); ?>
						</th>
						<td>
							<label>
								<input name="toplistDataAddAutomatic" type="checkbox" value="1" <?php $this->checked($this->quiz->isToplistDataAddAutomatic()); ?>>
								<?php esc_html_e('Activate', 'learndash'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('If you enable this option, logged in users will be automatically entered into leaderboard', 'learndash'); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('display captcha', 'learndash'); ?>
						</th>
						<td>
							<label>
								<input type="checkbox" name="toplistDataCaptcha" value="1" <?php echo $this->quiz->isToplistDataCaptcha() ? 'checked="checked"' : ''; ?> <?php echo $this->captchaIsInstalled ? '' : 'disabled="disabled"'; ?>> 
								<?php esc_html_e('Activate', 'learndash'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('If you enable this option, additional captcha will be displayed for users who are not registered.', 'learndash'); ?>
							</p>
							<p class="description" style="color: red;">
								<?php esc_html_e('This option requires additional plugin:', 'learndash'); ?>
									<a href="http://wordpress.org/extend/plugins/really-simple-captcha/" target="_blank">Really Simple CAPTCHA</a>
							</p>
							<?php if($this->captchaIsInstalled) { ?>
							<p class="description" style="color: green;">
								<?php esc_html_e('Plugin has been detected.', 'learndash'); ?>
							</p>
							<?php } else { ?>
							<p class="description" style="color: red;">
								<?php esc_html_e('Plugin is not installed.', 'learndash'); ?>
							</p>
							<?php } ?>
							
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Sort list by', 'learndash'); ?>
						</th>
						<td>
							<label>
								<input name="toplistDataSort" type="radio" value="1" <?php echo ($this->quiz->getToplistDataSort() == 1) ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('best user', 'learndash'); ?>
							</label>
							<label>
								<input name="toplistDataSort" type="radio" value="2" <?php echo ($this->quiz->getToplistDataSort() == 2) ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('newest entry', 'learndash'); ?>
							</label>
							<label>
								<input name="toplistDataSort" type="radio" value="3" <?php echo ($this->quiz->getToplistDataSort() == 3) ? 'checked="checked"' : ''; ?>>
								<?php esc_html_e('oldest entry', 'learndash'); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Users can apply multiple times', 'learndash'); ?>
						</th>
						<td>
							<div>
								<label>
									<input type="checkbox" name="toplistDataAddMultiple" value="1" <?php echo $this->quiz->isToplistDataAddMultiple() ? 'checked="checked"' : ''; ?>> 
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
							</div>
							<div id="toplistDataAddBlockBox" style="display: none;">
								<label>
									<?php esc_html_e('User can apply after:', 'learndash'); ?>
									<input type="number" min="0" class="small-text" name="toplistDataAddBlock" value="<?php echo $this->quiz->getToplistDataAddBlock(); ?>"> 
										<?php esc_html_e('minute', 'learndash'); ?>
								</label>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('How many entries should be displayed', 'learndash'); ?>
						</th>
						<td>
							<div>
								<label>
									<input type="number" min="0" class="small-text" name="toplistDataShowLimit" value="<?php echo $this->quiz->getToplistDataShowLimit(); ?>"> 
									<?php esc_html_e('Entries', 'learndash'); ?>
								</label>
							</div>
						</td>
					</tr>
					<tr id="AutomaticallyDisplayLeaderboard">
						<th scope="row">
							<?php echo sprintf( esc_html_x('Automatically display leaderboard in %s result', 'Automatically display leaderboard in quiz result', 'learndash'), learndash_get_custom_label_lower( 'quiz' )); ?>
						</th>
						<td>
							<div style="margin-top: 6px;">
								<?php esc_html_e('Where should leaderboard be displayed:', 'learndash'); ?><br>
								<label style="margin-right: 5px; margin-left: 5px;">
									<input type="radio" name="toplistDataShowIn" value="0" <?php echo ($this->quiz->getToplistDataShowIn() == 0) ? 'checked="checked"' : ''; ?>> 
									<?php esc_html_e('don\'t display', 'learndash'); ?>
								</label>
								<label>
									<input type="radio" name="toplistDataShowIn" value="1" <?php echo ($this->quiz->getToplistDataShowIn() == 1) ? 'checked="checked"' : ''; ?>> 
									<?php esc_html_e('below the "result text"', 'learndash'); ?>
								</label>
								
								<label>
									<input type="radio" name="toplistDataShowIn" value="2" <?php echo ($this->quiz->getToplistDataShowIn() == 2) ? 'checked="checked"' : ''; ?>> 
									<?php esc_html_e('in a button', 'learndash'); ?>
								</label>
								
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php 
	}
	
	public function quizMode() {
		?>
		<style>
.wpProQuiz_quizModus th, .wpProQuiz_quizModus td {
	border-right: 1px solid #A0A0A0;
	padding: 5px;
}
</style>

		<div class="wrap wpProQuiz_quizEdit">
			<table style="width: 100%; border-collapse: collapse; border: 1px solid #A0A0A0;" class="wpProQuiz_quizModus">
				<thead>
					<tr>
						<th style="width: 25%;"><?php esc_html_e('Normal', 'learndash'); ?></th>
						<th style="width: 25%;"><?php esc_html_e('Normal + Back-Button', 'learndash'); ?></th>
						<th style="width: 25%;"><?php esc_html_e('Check -> continue', 'learndash'); ?></th>
						<th style="width: 25%;"><?php esc_html_e('Questions below each other', 'learndash'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><label><input type="radio" name="quizModus" value="0" <?php $this->checked($this->quiz->getQuizModus(), WpProQuiz_Model_Quiz::QUIZ_MODUS_NORMAL); ?>> <?php esc_html_e('Activate', 'learndash'); ?></label></td>
						<td><label><input type="radio" name="quizModus" value="1" <?php $this->checked($this->quiz->getQuizModus(), WpProQuiz_Model_Quiz::QUIZ_MODUS_BACK_BUTTON); ?>> <?php esc_html_e('Activate', 'learndash'); ?></label></td>
						<td><label><input type="radio" name="quizModus" value="2" <?php $this->checked($this->quiz->getQuizModus(), WpProQuiz_Model_Quiz::QUIZ_MODUS_CHECK); ?>> <?php esc_html_e('Activate', 'learndash'); ?></label></td>
						<td><label><input type="radio" name="quizModus" value="3" <?php $this->checked($this->quiz->getQuizModus(), WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE); ?>> <?php esc_html_e('Activate', 'learndash'); ?></label></td>
					</tr>
					<tr>
						<td>
							<?php echo sprintf( esc_html_x('Displays all questions sequentially, "right" or "false" will be displayed at the end of the %s.', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
						</td>
						<td>
							<?php esc_html_e('Allows to use the back button in a question.', 'learndash'); ?>
						</td>
						<td>
							<?php esc_html_e('Shows "right or wrong" after each question.', 'learndash'); ?>
						</td>
						<td>
							<?php esc_html_e('If this option is activated, all answers are displayed below each other, i.e. all questions are on a single page.', 'learndash'); ?>
						</td>
					</tr>
					<tr>
						<td>
							
						</td>
						<td>
							
						</td>
						<td>
							
						</td>
						<td>
							
						</td>
					</tr>
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td>
							<?php esc_html_e('How many questions to be displayed on a page:', 'learndash'); ?><br>
							<input type="number" name="questionsPerPage" value="<?php echo $this->quiz->getQuestionsPerPage(); ?>" min="0">
							<span class="description">
								<?php esc_html_e('(0 = All on one page)', 'learndash'); ?>
							</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
	
    public function form() {
		$forms = $this->forms;
		$index = 0;
		
		if ( !is_array( $forms ) ) $forms = array();
		
		if(!count($forms))
			$forms = array(new WpProQuiz_Model_Form(), new WpProQuiz_Model_Form());
		else
			array_unshift($forms, new WpProQuiz_Model_Form());
		
    	?>
		<div class="wrap wpProQuiz_quizEdit">
					
			<p class="description">
				<?php esc_html_e('You can create custom fields, e.g. to request the name or the e-mail address of the users.', 'learndash'); ?>
			</p>
			<p class="description">
				<?php esc_html_e('The statistic function have to be enabled.', 'learndash'); ?>
			</p>
			
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e('Custom fields enable', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Custom fields enable', 'learndash'); ?></span>
								</legend>
								<label>
									<input type="checkbox" id="formActivated" value="1" name="formActivated" <?php $this->checked($this->quiz->isFormActivated()); ?>>
									<?php esc_html_e('Activate', 'learndash'); ?>
								</label>
								<p class="description">
									<?php esc_html_e('If you enable this option, custom fields are enabled.', 'learndash'); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e('Display position', 'learndash'); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e('Display position', 'learndash'); ?></span>
								</legend>
								<?php esc_html_e('Where should the fields be displayed:', 'learndash'); ?><br>
								<label>
									<input type="radio" value="<?php echo WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START; ?>" name="formShowPosition" <?php $this->checked($this->quiz->getFormShowPosition(), WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_START); ?>>
									<?php echo sprintf( esc_html_x('On the %s startpage', 'On the quiz startpage', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
									
									
									
								</label>
								<label>
									<input type="radio" value="<?php echo WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_END; ?>" name="formShowPosition" <?php $this->checked($this->quiz->getFormShowPosition(), WpProQuiz_Model_Quiz::QUIZ_FORM_POSITION_END); ?> >
									<?php echo sprintf( esc_html_x('At the end of the %s (before the %s result)', 'At the end of the quiz (before the quiz result)', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), learndash_get_custom_label_lower( 'quiz' ) ); ?>
									
									
									
									
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			
			<div style="margin-top: 10px; padding: 10px; border: 1px solid #C2C2C2;">
				<table style=" width: 100%; text-align: left; " id="form_table">
					<thead>
						<tr>
							<th><?php esc_html_e('Field name', 'learndash'); ?></th>
							<th><?php esc_html_e('Type', 'learndash'); ?></th>
							<th><?php esc_html_e('Required?', 'learndash'); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($forms as $form) {
							$checkType = $this->selectedArray($form->getType(), array(
								WpProQuiz_Model_Form::FORM_TYPE_TEXT, WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA, 
								WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX, WpProQuiz_Model_Form::FORM_TYPE_SELECT,
								WpProQuiz_Model_Form::FORM_TYPE_RADIO, WpProQuiz_Model_Form::FORM_TYPE_NUMBER,
								WpProQuiz_Model_Form::FORM_TYPE_EMAIL, WpProQuiz_Model_Form::FORM_TYPE_YES_NO,
								WpProQuiz_Model_Form::FORM_TYPE_DATE
							));
						?>
						<tr <?php echo $index++ == 0 ? 'style="display: none;"' : '' ?>>
							<td>
								<input type="text" name="form[][fieldname]" value="<?php echo esc_attr($form->getFieldname()); ?>" class="regular-text"/>
							</td>
							<td style="position: relative;">
								<select name="form[][type]">
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_TEXT; ?>" <?php echo $checkType[0]; ?>><?php esc_html_e('Text', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA; ?>" <?php echo $checkType[1]; ?>><?php esc_html_e('TextArea', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX; ?>" <?php echo $checkType[2]; ?>><?php esc_html_e('Checkbox', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_SELECT; ?>" <?php echo $checkType[3]; ?>><?php esc_html_e('Drop-Down menu', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_RADIO; ?>" <?php echo $checkType[4]; ?>><?php esc_html_e('Radio', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_NUMBER; ?>" <?php echo $checkType[5]; ?>><?php esc_html_e('Number', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_EMAIL; ?>" <?php echo $checkType[6]; ?>><?php esc_html_e('Email', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_YES_NO; ?>" <?php echo $checkType[7]; ?>><?php esc_html_e('Yes/No', 'learndash'); ?></option>
									<option value="<?php echo WpProQuiz_Model_Form::FORM_TYPE_DATE; ?>" <?php echo $checkType[8]; ?>><?php esc_html_e('Date', 'learndash'); ?></option>
								</select>
								
								<a href="#" class="editDropDown"><?php esc_html_e('Edit list', 'learndash'); ?></a>
									
								<div class="dropDownEditBox" style="position: absolute; border: 1px solid #AFAFAF; background: #EBEBEB; padding: 5px; bottom: 0;right: 0;box-shadow: 1px 1px 1px 1px #AFAFAF; display: none;">
									<h4><?php esc_html_e('One entry per line', 'learndash'); ?></h4>
									<div>
										<textarea rows="5" cols="50" name="form[][data]"><?php echo $form->getData() === null ? '' : esc_textarea(implode("\n", $form->getData())); ?></textarea>
									</div>
									
									<input type="button" value="<?php esc_html_e('OK', 'learndash'); ?>" class="button-primary">
								</div>
							</td>
							<td>
								<input type="checkbox" name="form[][required]" value="1" <?php $this->checked($form->isRequired()); ?>>
							</td>
							<td>
								<input type="button" name="form_delete" value="<?php esc_html_e('Delete', 'learndash'); ?>" class="button-secondary">
								<a class="form_move button-secondary" href="#" style="cursor:move;"><?php esc_html_e('Move', 'learndash'); ?></a>
								
								<input type="hidden" name="form[][form_id]" value="<?php echo $form->getFormId(); ?>">
								<input type="hidden" name="form[][form_delete]" value="0">
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				
				<div style="margin-top: 10px;">
					<input type="button" name="form_add" id="form_add" value="<?php esc_html_e('Add field', 'learndash'); ?>" class="button-secondary">
				</div>
			</div>
		</div>
		<?php
	}
	
	public function resultText() {
		return;
		?>
		<div class="wrap wpProQuiz_quizEdit">
			<h3 class="hndle"><?php esc_html_e('Results text', 'learndash'); ?> <?php esc_html_e('(optional)', 'learndash'); ?></h3>
			<div class="inside">
				<p class="description">
					<?php echo sprintf( esc_html_x('This text will be displayed at the end of the %s (in results). (this text is optional)', 'placeholders: quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ); ?>
				</p>
				<div style="padding-top: 10px; padding-bottom: 10px;">
					<label for="wpProQuiz_resultGradeEnabled">
						<?php esc_html_e('Activate graduation', 'learndash'); ?>
						<input type="checkbox" name="resultGradeEnabled" id="wpProQuiz_resultGradeEnabled" value="1" <?php echo $this->quiz->isResultGradeEnabled() ? 'checked="checked"' : ''; ?>>
					</label>
				</div>
				<div style="display: none;" id="resultGrade">
					<div>
						<strong><?php esc_html_e('Hint:', 'learndash'); ?></strong>
						<ul style="list-style-type: square; padding: 5px; margin-left: 20px; margin-top: 0;">
							<li><?php esc_html_e('Maximal 15 levels', 'learndash'); ?></li>
							<li>
								<?php echo sprintf( esc_html_x('Percentages refer to the total score of the %1$s. (Current total %2d points in %3$d questions.)', 'placeholders: quiz, question points, question count', 'learndash'), learndash_get_custom_label_lower( 'quiz' ), $this->quiz->fetchSumQuestionPoints(), $this->quiz->fetchCountQuestions()); ?>
								</li>
							<li><?php esc_html_e('Values can also be mixed up', 'learndash'); ?></li>
							<li><?php esc_html_e('10,15% or 10.15% allowed (max. two digits after the decimal point)', 'learndash'); ?></li>
						</ul>
							
					</div>
					<div>
						<ul id="resultList">
						<?php
							$resultText = $this->quiz->getResultText();
							
							for($i = 0; $i < 15; $i++) {

								if($this->quiz->isResultGradeEnabled() && isset($resultText['text'][$i])) {
						?>
							<li style="padding: 5px; border: 1; border: 1px dotted;">
								<div style="margin-bottom: 5px;"><?php wp_editor($resultText['text'][$i], 'resultText_'.$i, array('textarea_rows' => 3, 'textarea_name' => 'resultTextGrade[text][]')); ?></div>
								<div style="margin-bottom: 5px;background-color: rgb(207, 207, 207);padding: 10px;">
									<?php esc_html_e('from:', 'learndash'); ?> <input type="text" name="resultTextGrade[prozent][]" class="small-text" value="<?php echo $resultText['prozent'][$i]?>"> <?php esc_html_e('percent', 'learndash'); ?> <?php printf(__('(Will be displayed, when result-percent is >= <span class="resultProzent">%s</span>%%)', 'learndash'), $resultText['prozent'][$i]); ?>
									<input type="button" style="float: right;" class="button-primary deleteResult" value="<?php esc_html_e('Delete graduation', 'learndash'); ?>">
									<div style="clear: right;"></div>
									<input type="hidden" value="1" name="resultTextGrade[activ][]">
								</div>
							</li>
						
						<?php } else { ?>
							<li style="padding: 5px; border: 1; border: 1px dotted; <?php echo $i ? 'display:none;' : '' ?>">
								<div style="margin-bottom: 5px;"><?php wp_editor('', 'resultText_'.$i, array('textarea_rows' => 3, 'textarea_name' => 'resultTextGrade[text][]')); ?></div>
								<div style="margin-bottom: 5px;background-color: rgb(207, 207, 207);padding: 10px;">
									<?php esc_html_e('from:', 'learndash'); ?> <input type="text" name="resultTextGrade[prozent][]" class="small-text" value="0"> <?php esc_html_e('percent', 'learndash'); ?> <?php printf(__('(Will be displayed, when result-percent is >= <span class="resultProzent">%s</span>%%)', 'learndash'), '0'); ?>
									<input type="button" style="float: right;" class="button-primary deleteResult" value="<?php esc_html_e('Delete graduation', 'learndash'); ?>">
									<div style="clear: right;"></div>
									<input type="hidden" value="<?php echo $i ? '0' : '1' ?>" name="resultTextGrade[activ][]">
								</div>
							</li>
						<?php } } ?>
						</ul>
						<input type="button" class="button-primary addResult" value="<?php esc_html_e('Add graduation', 'learndash'); ?>">
					</div>
				</div>
				<div id="resultNormal">
					<?php
					
						$resultText = is_array($resultText) ? '' : $resultText;
						wp_editor($resultText, 'resultText', array('textarea_rows' => 10));
					?>
				</div>
			</div>
		</div>
		<?php
	}
}