<?php

if (!class_exists('Learndash_Course_Video' ) ) {
	class Learndash_Course_Video {
	
		private static $instance;
 
		private $video_data = array(
			'videos_found_provider' => false, 
			'videos_found_type' => false,
			'videos_auto_start'	=> false,
			'videos_show_controls' => false,
			'videos_auto_complete' => true,
			'videos_auto_complete_delay' => 0,
			'videos_auto_complete_delay_message' => '',
			'videos_hide_complete_button' => false,
			'videos_shown' => false
		);

		private $video_content = '';
		
		function __construct() {
			add_action( 'wp_footer', array( $this, 'action_wp_footer' ), 1 );
			add_filter( 'learndash_post_args', array(  $this, 'filter_post_args' ) );
			add_filter( 'learndash_process_mark_complete', array( $this, 'process_mark_complete' ), 99, 3 );
			add_action( 'save_post', array( $this, 'save_post_data') );
		}
		
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new static();
			}

			return self::$instance;
		}
		
		function filter_post_args( $post_args = array() ) {
			if ( isset( $post_args['sfwd-lessons']['fields'] ) ) {
				$post_args['sfwd-lessons']['fields'] = array_merge(
					$post_args['sfwd-lessons']['fields'],
					array(
						'lesson_video_enabled' => array( 
							'name' => esc_html__( 'Enable Video Progression', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want to show a video as part of the progression.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_url' => array( 
							'name' => esc_html__( 'Video URL', 'learndash' ),
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'URL to video. The video will be added above the %s content. Use the shortcode %s to position the player within content. Supported URL formats are YouTube (youtu.be, youtube.com), Vimeo (vimeo.com), Wistia (wistia.com), or Local videos. The value for this field can be a simple URL to the video, an iframe or either [video] or [embed] shortcodes.', 'placeholder: Lesson, admin URL to [ld_video] shortcode.', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), '<a href="'. admin_url('admin.php?page=courses-shortcodes#shortcode_ld_video' ) .'">[ld_video]</a>' ),
							'default' => '',
						),
						'lesson_video_auto_start' => array( 
							'name' => esc_html__( 'Auto Start Video', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want the video to auto-start on page load.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_show_controls' => array( 
							'name' => esc_html__( 'Show Video Controls', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Show Video Controls. By default controls are disabled. Only used for YouTube and local videos.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_shown' => array(
							'name' => esc_html__( 'When to show video', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	
								'BEFORE' => esc_html__( 'Before  (default) - Video is shown before completing sub-steps', 'learndash' ),
								'AFTER'	=> esc_html__( 'After - Video is shown after completing sub-steps', 'learndash' ),
							),
							'default' => 'BEFORE',
							'help_text' => esc_html__( 'Select when to show video in relation to sub-steps.', 'learndash' )
						),
						'lesson_video_auto_complete' => array( 
							'name' => sprintf( esc_html_x( 'Auto Complete %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type' => 'checkbox', 
							'help_text' => sprintf( esc_html_x( 'Check this if you want the %s to auto-complete after the video completes.', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default' => 0,
						),
						'lesson_video_auto_complete_delay' => array( 
							'name' => esc_html__( 'Auto Complete Delay', 'learndash' ),
							'type' => 'number', 
							'class' => 'small-text',
							'min' => '0',
							'help_text' => esc_html__( 'Time delay in second between the time the video finishes and the auto complete occurs. Example 0 no delay, 5 for five seconds.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_hide_complete_button' => array( 
							'name' => esc_html__( 'Hide Complete Button', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this to hide the complete button.', 'learndash' ),
							'default' => 0,
						),
					)
				);				
			}
			
			if ( isset( $post_args['sfwd-topic']['fields'] ) ) {
				$post_args['sfwd-topic']['fields'] = array_merge(
					$post_args['sfwd-topic']['fields'],
					array(
						'lesson_video_enabled' => array( 
							'name' => esc_html__( 'Enable Video Progression', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want to show a video as part of the progression.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_url' => array( 
							'name' => esc_html__( 'Video URL', 'learndash' ),
							'type' => 'text', 
							'help_text' => sprintf( esc_html_x( 'URL to video. The video will be added above the %s content. Use the shortcode %s to position the player within content. Supported URL formats are YouTube (youtu.be, youtube.com), Vimeo (vimeo.com), Wistia (wistia.com), or Local videos. The value for this field can be a simple URL to the video, an iframe or either [video] or [embed] shortcodes.', 'placeholder: Topic, admin URL to [ld_video] shortcode.', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ), '<a href="'. admin_url('admin.php?page=courses-shortcodes#shortcode_ld_video' ) .'">[ld_video]</a>' ),
							'default' => '',
						),
						'lesson_video_auto_start' => array( 
							'name' => esc_html__( 'Auto Start Video', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this if you want the video to auto-start on page load.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_show_controls' => array( 
							'name' => esc_html__( 'Show Video Controls', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Show Video Controls. By default controls are disabled. Only used for YouTube and local videos.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_shown' => array(
							'name' => esc_html__( 'When to show video', 'learndash' ),
							'type' => 'select',
							'initial_options' => array(	
								'AFTER'	=> esc_html__( 'After (default) - Video is shown after completing sub-steps', 'learndash' ),
								'BEFORE' => esc_html__( 'Before - Video is shown before completing sub-steps', 'learndash' ),
							),
							'default' => '',
							'help_text' => esc_html__( 'Select when to show video in relation to sub-steps.', 'learndash' )
						),
						
						'lesson_video_auto_complete' => array( 
							'name' => sprintf( esc_html_x( 'Auto Complete %s', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'type' => 'checkbox', 
							'help_text' => sprintf( esc_html_x( 'Check this if you want the %s to auto-complete after the video completes.', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'default' => 0,
						),
						'lesson_video_auto_complete_delay' => array( 
							'name' => esc_html__( 'Auto Complete Delay', 'learndash' ),
							'type' => 'number', 
							'class' => 'small-text',
							'min' => '0',
							'help_text' => esc_html__( 'Time delay in second between the time the video finishes and the auto complete occurs. Example 0 no delay, 5 for five seconds.', 'learndash' ),
							'default' => 0,
						),
						'lesson_video_hide_complete_button' => array( 
							'name' => esc_html__( 'Hide Complete Button', 'learndash' ),
							'type' => 'checkbox', 
							'help_text' => esc_html__( 'Check this to hide the complete button.', 'learndash' ),
							'default' => 0,
						),
					)
				);
			}
			
			return $post_args;			
		}
				
		function add_video_to_content( $content = '', $post, $settings = array() ) {			
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = 0;
			}
			
			// Do we show the video. In some cases we do. But in others like when the setting is to show AFTER completing other steps then we set to false.
			$show_video = false;
			
			// In the initial flow we do apply the video restiction logic. But then in other if the user is an admin or the student has completed the lesson
			// we don't apply the video logic. 
			$logic_video = false;
			
			if ( ( isset( $settings['lesson_video_enabled'] ) ) && ( $settings['lesson_video_enabled'] == 'on' ) ) {
				if ( ( isset( $settings['lesson_video_url'] ) ) && ( !empty( $settings['lesson_video_url'] ) ) ) {
					// Because some copy/paste can result in leading whitespace. LEARNDASH-3819
					$settings['lesson_video_url'] = trim( $settings['lesson_video_url'] );
					$settings['lesson_video_url'] = html_entity_decode( $settings['lesson_video_url'] );
					
					// Just to ensure the proper settings are available
					if ( ( !isset( $settings['lesson_video_shown'] ) ) || ( empty( $settings['lesson_video_shown'] ) ) ) {
						$settings['lesson_video_shown'] = 'BEFORE';
					}
					
					
					
					$bypass_course_limits_admin_users = false;
					if ( !empty( $user_id ) ) {
						if ( learndash_is_admin_user( $user_id ) ) {
							$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings _Section_General_Admin_User', 'bypass_course_limits_admin_users' );
							if ( $bypass_course_limits_admin_users == 'yes' ) $bypass_course_limits_admin_users = true;
							else $bypass_course_limits_admin_users = false;
							
						} else {
							$bypass_course_limits_admin_users = false;
						}
	
						// For logged in users to allow an override filter. 
						$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );
					}

					if ( !$bypass_course_limits_admin_users ) {

						if ( $post->post_type == 'sfwd-lessons' ) {
							$progress = learndash_get_course_progress( null, $post->ID );
							
							if ( ( !empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( $progress['this']->completed == true ) ) {
								// The student has completes this step so we show the video but don't apply the logic
								$show_video = true;
								$logic_video = false;
							} else {
								if ( $settings['lesson_video_shown'] == 'BEFORE' ) {
									$show_video = true;
									$logic_video = true;
									

									$topics = learndash_get_topic_list( $post->ID );
									if ( !empty( $topics ) ) {
										$progress = learndash_get_course_progress( null, $topics[0]->ID );
										if ( !empty( $progress ) ) {
											$topics_completed = 0;
											foreach ( $progress['posts'] as $topic ) {
												if ( $topic->completed == true ) {
													$topics_completed += 1;
													break;
												}
											}
											
											if ( !empty( $topics_completed ) ) {
												$logic_video = false;
											}
										} 
									}
								} else if ( $settings['lesson_video_shown'] == 'AFTER' ) {
									if ( learndash_lesson_topics_completed( $post->ID ) ) {
										$quizzes_completed = true;

										$lesson_quizzes_list = learndash_get_lesson_quiz_list( $post->ID ); 
										if ( !empty( $lesson_quizzes_list ) ) {
											foreach( $lesson_quizzes_list as $quiz ) {
												if ( $quiz['status'] != 'completed') {
													$quizzes_completed = false;
													break;
												}
											}
										} 
										
										if ( $quizzes_completed == true ) {
											$show_video = true;
											$logic_video = true;
										}
									} else {
										$show_video =  false;
										$logic_video = false;
									}
								}
							}
						} else if ( $post->post_type == 'sfwd-topic' ) {
							$progress = learndash_get_course_progress( null, $post->ID );
							
							if ( ( !empty( $progress['this'] ) ) && ( $progress['this'] instanceof WP_Post ) && ( $progress['this']->completed == true ) ) {
								// The student has completes this step so we show the video but don't apply the logic
								$show_video = true;
								$logic_video = false;
							} else {
								if ( $settings['lesson_video_shown'] == 'BEFORE' ) {
									$show_video = true;
									$logic_video = true;
								} else if ( $settings['lesson_video_shown'] == 'AFTER' ) {
									$quizzes_completed = true;

									$lesson_quizzes_list = learndash_get_lesson_quiz_list( $post->ID ); 
									if ( !empty( $lesson_quizzes_list ) ) {
										foreach( $lesson_quizzes_list as $quiz ) {
											if ( $quiz['status'] != 'completed') {
												$quizzes_completed = false;
												break;
											}
										}
									} 
										
									if ( $quizzes_completed == true ) {
										$show_video = true;
										$logic_video = true;
									}
									
								} else {
									$show_video =  false;
									$logic_video = false;
								}
							}
							
							
							/*
							// Lessons are always 'BEFORE'
							$settings['lesson_video_shown'] = 'AFTER';
							
							$progress = learndash_get_course_progress( null, $post->ID );

							if ( ! empty( $progress['this']->completed ) ) {
								$show_video = true;
								$logic_video = false;
							} else {
								// are we the first item in the list. No prev
								if ( ( empty( $progress['prev'] ) ) && ( $progress['this']->ID == $progress['posts'][0]->ID ) ) {
									$show_video = true;
									$logic_video = true;
									
									// Should not be here.
								} else if ( ( ! empty( $progress['prev'] ) ) && ( $progress['prev']->completed == true ) ) {
									$show_video = true;
									$logic_video = true;
								}
							}
							*/
						}
					} else {
						$progress = learndash_get_course_progress( null, $post->ID );

						if ( ! empty( $progress['this']->completed ) ) {
							//return str_replace( '[ld_video]', '', $content );
							$show_video = true;
							$logic_video = false;
						}
					}
					
					if ( $show_video == true ) {
					
						if ( ( isset( $settings['lesson_video_shown'] ) ) && ( !empty( $settings['lesson_video_shown'] ) ) ) {
							$this->video_data['videos_shown'] = $settings['lesson_video_shown'];
						} else {
							$this->video_data['videos_shown'] = 'AFTER';
						}

						if (( strpos( $settings['lesson_video_url'], 'youtu.be' ) !== false ) || ( strpos( $settings['lesson_video_url'], 'youtube.com' ) !== false )) {
							$this->video_data['videos_found_provider'] = 'youtube';
						} else if ( strpos( $settings['lesson_video_url'], 'vimeo.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'vimeo';
						} else if ( ( strpos( $settings['lesson_video_url'], 'wistia.com' ) !== false ) || ( strpos( $settings['lesson_video_url'], 'wistia.net' ) !== false ) ) {
							$this->video_data['videos_found_provider'] = 'wistia';
						} else if ( strpos( $settings['lesson_video_url'], 'amazonaws.com' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						} else if ( strpos( $settings['lesson_video_url'], 'vooplayer' ) !== false ) {
							$this->video_data['videos_found_provider'] = 'vooplayer';
						} else if ( strpos( $settings['lesson_video_url'], trailingslashit( get_home_url() ) ) !== false ) {
							$this->video_data['videos_found_provider'] = 'local';
						} else {
							$this->video_data['videos_found_provider'] = apply_filters('ld_video_provider', '', $settings );
						}

						if ( ( substr( $settings['lesson_video_url'], 0, strlen('http://') ) == 'http://' ) || ( substr( $settings['lesson_video_url'], 0, strlen('https://') ) == 'https://' ) )  {
							if ( $this->video_data['videos_found_provider'] == 'local' ) {
								$this->video_data['videos_found_type'] = 'video_shortcode';
								$settings['lesson_video_url'] = '[video src="'. $settings['lesson_video_url'] .'"][/video]';
								
							} else if ( ( $this->video_data['videos_found_provider'] == 'youtube' ) || ( $this->video_data['videos_found_provider'] == 'vimeo' ) ) {
								$this->video_data['videos_found_type'] = 'embed_shortcode';
								$settings['lesson_video_url'] = '[embed]'. $settings['lesson_video_url'] .'[/embed]';
							} else if ( $this->video_data['videos_found_provider'] == 'wistia' ) {
								$this->video_data['videos_found_type'] = 'embed_shortcode';
								$settings['lesson_video_url'] = '[embed]'. $settings['lesson_video_url'] .'[/embed]';
							} 
							
						} else if ( substr( $settings['lesson_video_url'], 0, strlen('[embed')  ) == '[embed' ) {
							$this->video_data['videos_found_type'] = 'embed_shortcode';
						} else if ( substr( $settings['lesson_video_url'], 0, strlen('[video')  ) == '[video' ) {
							$this->video_data['videos_found_type'] = 'video_shortcode';
						}  else if ( substr( $settings['lesson_video_url'], 0, strlen('<iframe')  ) == '<iframe' ) {
							$this->video_data['videos_found_type'] = 'iframe';
						} else {
							if ( $this->video_data['videos_found_provider'] == 'vooplayer' ) {
								if ( substr( $settings['lesson_video_url'], 0, strlen('[vooplayer')  ) == '[vooplayer' ) {
									$this->video_data['videos_found_type'] = 'vooplayer_shortcode';
								} else {
									$this->video_data['videos_found_type'] = 'iframe';
								}
							}
						}
					
						if ( ( $this->video_data['videos_found_provider'] !== false ) && ( $this->video_data['videos_found_type'] !== false ) ) {
							if ( $this->video_data['videos_found_provider'] == 'local' ) {
								if ( $this->video_data['videos_found_type'] == 'video_url' ) {
									//$this->video_content = wp_video_shortcode(
									//	apply_filters(
									//		'ld_video_shortcode_args', 
									//		array(
									//			'src' => $settings['lesson_video_url'],
									//		),
									//		$post->ID, $settings
									//	)
									//);
								} else if ( $this->video_data['videos_found_type'] == 'embed_shortcode' ) {
									global $wp_embed;
									$video_content = $wp_embed->run_shortcode( $settings['lesson_video_url'] );
									$this->video_content = do_shortcode( $video_content );
									
								} else if ( $this->video_data['videos_found_type'] == 'video_shortcode' ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} else if ( $this->video_data['videos_found_type'] == 'iframe' ) {
									$this->video_content = $settings['lesson_video_url'];
								}
							} else if ( ( $this->video_data['videos_found_provider'] == 'youtube' ) || ( $this->video_data['videos_found_provider'] == 'vimeo' ) || ( $this->video_data['videos_found_provider'] == 'wistia' ) ) {
								//$this->video_content =  wp_oembed_get( $settings['lesson_video_url'], apply_filters( 'learndash_video_oembed_args', array(), $settings['lesson_video_url'],  $post->ID, $settings ) );
							
								if ( $this->video_data['videos_found_type'] == 'embed_shortcode' ) {
									global $wp_embed;
									$this->video_content = $wp_embed->run_shortcode( $settings['lesson_video_url'] );
								} else if ( $this->video_data['videos_found_type'] == 'video_shortcode' ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} else if ( $this->video_data['videos_found_type'] == 'iframe' ) {
									$this->video_content = $settings['lesson_video_url'];
								}
							} else if ( $this->video_data['videos_found_provider'] == 'vooplayer' ) {
								if ( $this->video_data['videos_found_type'] == 'vooplayer_shortcode' ) {
									$this->video_content = do_shortcode( $settings['lesson_video_url'] );
								} else if ( $this->video_data['videos_found_type'] == 'iframe' ) {
									//if ( strpos( $settings['lesson_video_url'], '</script>' ) === false ) {
									//	$settings['lesson_video_url'] = '<script src="https://codehooligans.cdn.vooplayer.com/assets/vooplayer.js"></script>' . $settings['lesson_video_url'];
									//}
									$this->video_content = $settings['lesson_video_url'];
								}
							}
															
							if ( !empty( $this->video_content ) ) {
								if ( $logic_video ) {
									
									if (( isset( $settings['lesson_video_show_controls'] ) ) && ( $settings['lesson_video_show_controls'] == 'on' )) {
										$this->video_data['videos_show_controls'] = 1;
									} else {
										$this->video_data['videos_show_controls'] = 0;
									}
									
									if (( isset( $settings['lesson_video_auto_start'] ) ) && ( $settings['lesson_video_auto_start'] == 'on' )) {
										$this->video_data['videos_auto_start'] = 1;
									} else {
										$this->video_data['videos_auto_start'] = 0;
									}
									
									$video_preg_pattern = '';

									if ( strstr( $this->video_content, ' src="' ) ) {
										$video_preg_pattern = '/<iframe.*src=\"(.*)\".*><\/iframe>/isU';
									} else if ( strstr( $this->video_content, " src='" ) ) {
										$video_preg_pattern = "/<iframe.*src=\'(.*)\'.*><\/iframe>/isU";
									} 
									if ( ! empty( $video_preg_pattern ) ) {
										preg_match( $video_preg_pattern, $this->video_content, $matches );
										if ( ( is_array( $matches ) ) && ( isset( $matches[1] ) ) && ( !empty( $matches[1] ) ) ) {
					
											// Next we need to check if the video is YouTube, Vimeo, etc. so we check the matches[1]
											if ( $this->video_data['videos_found_provider'] == 'youtube' ) {
												$ld_video_params = apply_filters( 
													'ld_video_params', 
													array( 
														'controls' => $this->video_data['videos_show_controls'],
														'autoplay' => $this->video_data['videos_auto_start'],
														'modestbranding' => 1,
														'showinfo' => 0,
														'rel' => 0
													), 
													'youtube', $this->video_content, $post, $settings 
												);
						
												// Regardless of the filter we set this param because we need it!	
												$ld_video_params['enablejsapi'] = '1';
												
												$matches_1_new = add_query_arg( $ld_video_params, $matches[1] );
												$this->video_content = str_replace( $matches[1], $matches_1_new, $this->video_content );
					
												//$this->video_content = str_replace('<iframe ', '<iframe id="ld-video-player" ', $this->video_content );

											} else if ( $this->video_data['videos_found_provider'] == 'vimeo' ) {
												
												//$matches_1_new = add_query_arg('api', '1', $matches[1] );
												//$return = str_replace( $matches[1], $matches_1_new, $return );
					
												//$return = str_replace('<iframe ', '<iframe id="ld-video-player" ', $return );
												//$this->video_content = str_replace('<iframe ', '<iframe id="ld-video-player" ', $this->video_content );
											} else if ( $this->video_data['videos_found_provider'] == 'wistia' ) {

											} else if ( $this->video_data['videos_found_provider'] == 'local' ) {
												//$ld_video_params = apply_filters( 
												//	'ld_video_params', 
												//	array( 
												//		'controls' => $this->video_data['videos_show_controls'],
												//	), 
												//	'local', $this->video_content, $post, $settings 
												//);
											}
										}
									}
									
									$this->video_content = '<div class="ld-video" data-video-progression="true" data-video-provider="'. $this->video_data['videos_found_provider'] .'">'. $this->video_content .'</div>';
									
									if ( $this->video_data['videos_found_provider'] == 'local' ) {
										if ( $this->video_data['videos_found_provider'] == 'local' ) {
											$ld_video_params = apply_filters( 
												'ld_video_params', 
												array( 
													'controls' => $this->video_data['videos_show_controls'],
												), 
												'local', $this->video_content, $post, $settings 
											);
										}
										
										if ( $ld_video_params['controls'] != true ) {
											$this->video_content .="<style>.ld-video .mejs-controls { display: none !important; visibility: hidden !important;}</style>";
										}
									}
									
									$this->video_data['videos_auto_complete'] = false;
									if (( isset( $settings['lesson_video_shown'] ) ) && ( $settings['lesson_video_shown'] == 'AFTER' )) {
										if ( ( isset( $settings['lesson_video_auto_complete'] ) ) && ( $settings['lesson_video_auto_complete'] == 'on' ) ) {
											$this->video_data['videos_auto_complete'] = true;
											
											if ( ( isset( $settings['lesson_video_hide_complete_button'] ) ) &&  ( $settings['lesson_video_hide_complete_button'] == 'on' ) ) {
												$this->video_data['videos_hide_complete_button'] = true;
											}
											
											if ( isset( $settings['lesson_video_auto_complete_delay'] ) ) {
												$this->video_data['videos_auto_complete_delay'] = intval( $settings['lesson_video_auto_complete_delay'] );
												
												$post_type_obj = get_post_type_object( $post->post_type );
												$post_type_name = $post_type_obj->labels->name;
												$this->video_data['videos_auto_complete_delay_message'] = 
												sprintf( wp_kses_post( _x('<p class="ld-video-delay-message">%s will auto complete in %s seconds</p>', 'placeholders: 1. Lesson or Topic label, 2. span for counter', 'learndash' ) ), $post_type_obj->labels->singular_name, '<span class="time-countdown">'. $this->video_data['videos_auto_complete_delay'] . '</span>'
												);
											}
										}
									}
									
								} else {
									$this->video_data['videos_found_provider'] = false;

									$this->video_content = '<div class="ld-video" data-video-progression="false">'. $this->video_content .'</div>';

								}
							}
						}
					}
				}
			}

			if ( !empty( $this->video_content ) ) {
				$this->video_data = apply_filters('learndash_lesson_video_data', $this->video_data, $settings );
			}

			$content = SFWD_LMS::get_template( 
				'learndash_lesson_video', 
				array(
					'content' => $content,
					'video_content' => $this->video_content,
					'video_settings' => $settings,
					'video_data' => $this->video_data
				)
			);
			
			return $content;
		}

		function action_wp_footer() {
			if ( $this->video_data['videos_found_provider'] !== false ) {

				wp_enqueue_script( 
					'learndash_video_script_js', 
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash_video_script'. leardash_min_asset() .'.js', 
					array( 'jquery' ), 
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true 
				);
				$learndash_assets_loaded['scripts']['learndash_video_script_js'] = __FUNCTION__;	

				//error_log('local: video_data<pre>'. print_r($this->video_data, true) .'</pre>');
				
				wp_localize_script( 'learndash_video_script_js', 'learndash_video_data', $this->video_data );

				if ( $this->video_data['videos_found_provider'] == 'youtube' ) {
					wp_enqueue_script( 'youtube_iframe_api', 'https://www.youtube.com/iframe_api', array( 'learndash_video_script_js' ), '1.0', true );
				} else if ( $this->video_data['videos_found_provider'] == 'vimeo' ) {
					wp_enqueue_script( 'vimeo_iframe_api', 'https://player.vimeo.com/api/player.js', array( 'learndash_video_script_js' ), null, true );
				}
			}
		}

		function process_mark_complete( $process_complete = true, $post, $current_user ) {
			if ( ( isset( $_GET['quiz_redirect'] ) ) && ( !empty( $_GET['quiz_redirect'] ) ) && ( isset( $_GET['quiz_type'] ) ) && ( $_GET['quiz_type'] == 'lesson' ) ) {
				$lesson_id = 0;
				$quiz_id = 0;

				if ( isset( $_GET['lesson_id'] ) ) $lesson_id = intval( $_GET['lesson_id'] );
				if ( isset( $_GET['quiz_id'] ) ) $quiz_id = intval( $_GET['quiz_id'] );
				
				if ( ( !empty( $lesson_id ) ) && ( !empty( $quiz_id ) ) ) {
					$lesson_settings = learndash_get_setting( $lesson_id );
					if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( $lesson_settings['lesson_video_enabled'] == 'on' ) ) {
						if ( ( isset( $lesson_settings['lesson_video_shown'] ) ) && ( $lesson_settings['lesson_video_shown'] == 'AFTER' ) ) {
							$process_complete = false;
							
							add_filter( 'learndash_completion_redirect', array( $this, 'learndash_completion_redirect' ), 99 );
						}
					}
				}
			}
			
			return $process_complete;
			
		}

		function learndash_completion_redirect( $link ) {
			if ( ( isset( $_GET['quiz_redirect'] ) ) && ( !empty( $_GET['quiz_redirect'] ) ) && ( isset( $_GET['quiz_type'] ) ) && ( $_GET['quiz_type'] == 'lesson' ) ) {
				$lesson_id = 0;
				$quiz_id = 0;

				if ( isset( $_GET['lesson_id'] ) ) $lesson_id = intval( $_GET['lesson_id'] );
				if ( isset( $_GET['quiz_id'] ) ) $quiz_id = intval( $_GET['quiz_id'] );
				
				if ( ( !empty( $lesson_id ) ) && ( !empty( $quiz_id ) ) ) {
					$lesson_settings = learndash_get_setting( $lesson_id );
					if ( ( isset( $lesson_settings['lesson_video_enabled'] ) ) && ( $lesson_settings['lesson_video_enabled'] == 'on' ) ) {
						if ( ( isset( $lesson_settings['lesson_video_shown'] ) ) && ( $lesson_settings['lesson_video_shown'] == 'AFTER' ) ) {
							$link = get_permalink( $lesson_id );
							
							remove_filter( 'learndash_completion_redirect', array( $this, 'learndash_completion_redirect' ), 99 );
						}
					}
				}
			}
			
			return $link;
			
		}
		
		function save_post_data( $post_id = 0 ) {
			if ( !empty( $post_id ) ) {
				if ( ( isset( $_POST['post_type'] ) ) && ( ( $_POST['post_type'] === 'sfwd-lessons') || ( $_POST['post_type'] === 'sfwd-topic') ) ) {
					$post_type = esc_attr( $_POST['post_type'] );
					if ( ( isset( $_POST[ $post_type . '_lesson_video_enabled'] ) ) && ( $_POST[ $post_type . '_lesson_video_enabled'] === 'on' ) ) {
						if ( ( isset( $_POST[ $post_type . '_lesson_video_url'] ) ) && ( !empty( $_POST[ $post_type . '_lesson_video_url'] ) ) ) {
							global $wpdb;
							
							$sql_str = $wpdb->prepare( "DELETE FROM " . $wpdb->postmeta ." WHERE post_id=%d AND meta_key LIKE %s", intval( $post_id ), '_oembed_%' );
							$wpdb->query( $sql_str );
						}
					}
				}
			}
		}
	}
}


add_action( 'learndash_init', function() {
	Learndash_Course_Video::get_instance();
} );
	