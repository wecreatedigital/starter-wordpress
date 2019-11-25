<?php
/**
 * LearnDash class to handle GDPR requirements
 *
 * The following class handles integration with WordPress for new
 * Privacy Policy requirements per GDPR.
 *
 * @package LearnDash
 * @subpackage GDPR
 * @since 2.5.8
 */

if ( ! class_exists( 'LearnDash_GDPR' ) ) {
	class LearnDash_GDPR {

		/**
		 * Default per_page limit
		 *
		 * @since 2.5.8
		 */
		private $per_page_default = 20;

		/**
		 * Class Constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'learndash_add_privacy_policy_text' ) );	
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'learndash_add_personal_data_exporters' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'learndash_add_personal_data_erasers' ) );
		}

		/**
		 * Add LearnDash Privacy Policy text to new WordPress GDPR hooks.
		 *
		 * @since 2.5.8
		 */
		public function learndash_add_privacy_policy_text() {
			if ( is_admin() ) {
				// Check we are on the WP Privacy Policy Guide page.
				$is_privacy_guide = ( isset( $_GET['wp-privacy-policy-guide'] ) && current_user_can( 'manage_privacy_options' ) );
				if ( $is_privacy_guide ) {

					$pp_readme_file = LEARNDASH_LMS_PLUGIN_DIR . 'privacy_policy.txt';
					if ( file_exists( $pp_readme_file ) ) {
						$pp_readme_content = file_get_contents( $pp_readme_file );
						if ( ! empty( $pp_readme_content ) ) {
							$pp_readme_content = wpautop( stripcslashes( $pp_readme_content ) );
							wp_add_privacy_policy_content( 'LearnDash LMS', $pp_readme_content );
						}
					}
				}
			}
		}

		/**
		 * Add LearnDash Exporters to new WordPress GDPR hooks.
		 *
		 * @since 2.5.8
		 * @param array $exporters Array of Exporters.
		 * @return array $exporters Array of Exporters.
		 */
		public function learndash_add_personal_data_exporters( $exporters = array() ) {
			$exporters['learndash-transactions'] = array(
				'exporter_friendly_name' => 'LearnDash LMS Transactions',
				'callback'               => array( $this, 'learndash_do_personal_data_exporter_transactions' ),
			);

			$exporters['learndash-course-assignments'] = array(
				'exporter_friendly_name' => sprintf( esc_html_x( 'LearnDash LMS %s Assignments', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
				'callback'               => array( $this, 'learndash_do_personal_data_exporter_course_assignments' ),
			);

			$exporters['learndash-course-essays'] = array(
				'exporter_friendly_name' => sprintf( esc_html_x( 'LearnDash LMS %s Essays', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
				'callback'               => array( $this, 'learndash_do_personal_data_exporter_quiz_essays' ),
			);

			return $exporters;
		}

		/**
		 * Run LearnDash Export.
		 *
		 * @since 2.5.8
		 * @param string $email_address Email address of user to export.
		 * @param int    $page Paged number to export.
		 * @return array $return_data
		 */
		public function learndash_do_personal_data_exporter_transactions( $email_address, $page ) {
			$return_array = array(
				'data' => array(),
				'done' => true,
			);

			$transaction_to_export = array();

			$email_address = trim( $email_address );
			if ( ! empty( $email_address ) ) {

				$number = apply_filters('learndash_privacy_export_transactions_per_page', $this->per_page_default );
				$page   = (int) $page;

				$user_data = get_user_by( 'email', $email_address );
				if ( ! empty( $user_data ) ) {

					$transactions_query_args = array(
						'post_type' => 'sfwd-transactions',
						'author' => $user_data->ID,
						'posts_per_page' => $number,
						'paged' => $page,
					);

					$transactions_query = new WP_Query( $transactions_query_args );
					if ( ( isset( $transactions_query->posts ) ) && ( ! empty( $transactions_query->posts ) ) ) {

						foreach ( (array) $transactions_query->posts as $transaction ) {

							$transaction_meta_data = array();
							$transaction_meta_fields = array();

							if ( empty( $transaction_meta_fields ) ) {
								$transaction_type = get_post_meta( $transaction->ID, 'action', true );
								if ( 'stripe' === $transaction_type ) {

									$transaction_meta_data[] = array(
										'name' => __( 'Transaction Type', 'learndash' ),
										'value' => __( 'Stripe', 'learndash' ),
									);

									$transaction_meta_fields = array(
										'stripe_name' => array(
																'label' => __( 'Order Item', 'learndash' ),
																'format_type' => 'text',
															),
										'stripe_price' => array(
																'label' => __('Order Total', 'learndash' ),
																'format_type' => 'money_stripe',
															),
										'stripe_token_email' => array(
																'label' => __( 'Order Email', 'learndash' ),
																'format_type' => 'email',
															),
									);
								}
							} 

							if ( empty( $transaction_meta_fields ) ) {
								$transaction_type = get_post_meta( $transaction->ID, 'ipn_track_id', true );
								if ( ! empty( $transaction_type ) ) {

									$transaction_meta_data[] = array(
										'name' => __( 'Transaction Type', 'learndash' ),
										'value' => __( 'PayPal', 'learndash' ),
									);

									$transaction_meta_fields = array(
										'item_name'     => array(
															'label' => __( 'Order Item', 'learndash' ),
															'format_type' => 'text',
															),
										'mc_gross'      => array(
															'label' => __('Order Total', 'learndash' ),
															'format_type' => 'money',
															),
										'first_name'    => array(
															'label' => __( 'First Name', 'learndash' ),
															'format_type' => 'text',
															),
										'last_name'     => array(
															'label' => __( 'Last Name', 'learndash' ),
															'format_type' => 'text',
														),
										'payer_email'   => array(
											'label' => __( 'Order Email', 'learndash' ),
											'format_type' => 'email',
										),

									);
								}
							}

							if ( empty( $transaction_meta_fields ) ) {
								$transaction_type = get_post_meta( $transaction->ID, 'learndash-checkout', true );
								if ( $transaction_type == '2co' ) {
									$transaction_meta_data[] = array(
										'name' => __( 'Transaction Type', 'learndash' ),
										'value' => __( '2Checkout', 'learndash' )
									);

									$transaction_meta_fields = array(
										'invoice_id' => array(
														'label' => __('Invoice', 'learndash' ),
														'format_type' => 'text',
													),
										'li_0_name' => array(
														'label' => __( 'Order Item', 'learndash' ),
														'format_type' => 'text',
													),
										'total' => array(
														'label' => __('Order Total', 'learndash' ),
														'format_type' => 'money',
													),
										'card_holder_name' => array(
														'label' => __( 'Cardholder Name', 'learndash' ),
														'format_type' => 'text',
													),

										'first_name' => array(
														'label' => __('Last Name', 'learndash' ),
														'format_type' => 'text',
													),
										'middle_initial' => array(
														'label' => __( 'Middle Initial', 'learndash' ),
														'format_type' => 'text',
													),
										'last_name' => array(
														'label' => __('Last Name', 'learndash' ),
														'format_type' => 'text',
													),
										'email' => array(
														'label' => __( 'Order Email', 'learndash' ),
														'format_type' => 'email',
													),
										'street_address' => array(
														'label' => __('Street Address', 'learndash' ),
														'format_type' => 'text',
													),
										'street_address2' => array(
														'label' => __('Street Address', 'learndash' ),
														'format_type' => 'text',
													),			
										'city' => array(
														'label' => __('City', 'learndash' ),
														'format_type' => 'text',
													),
										'state'  => array(
														'label' => __( 'State', 'learndash' ),
														'format_type' => 'text',
													),
										'zip' => array(
														'label' => __('Zip', 'learndash' ),
														'format_type' => 'text',
													),
									);
								}
							}

							// SAMCART Transactions.
							if ( empty( $transaction_meta_fields ) ) {
								$order_ip_address = get_post_meta( $transaction->ID, 'order_ip_address', true );
								if ( !empty( $order_ip_address ) ) {
									$transaction_meta_data[] = array(
										'name' => __( 'Transaction Type', 'learndash' ),
										'value' => __( 'Samcart', 'learndash' )
									);

									$transaction_meta_fields = array(
										'customer_email' => array(
															'label' => __('Order Email', 'learndash' ),
															'format_type' => 'email',
														),
										'customer_first_name' => array(
																'label' => __('First Name', 'learndash' ),
																'format_type' => 'text',
														),
										'customer_last_name' => array(
															'label' => __('Last Name', 'learndash' ),
															'format_type' => 'text',
														),
										'customer_phone_number' => array(
														'label' => __('Phone #', 'learndash' ),
														'format_type' => 'text',
														),
										'order_ip_address' => array(
														'label' => __('IP Address', 'learndash' ),
														'format_type' => 'ip',
														),
										'customer_billing_address' => array(
														'label' => __('Billing Address', 'learndash' ),
														'format_type' => 'text',
														),
										'customer_billing_city' => array(
														'label' => __('Billing City', 'learndash' ),
														'format_type' => 'text',
														),
										'customer_billing_state'  => array(
														'label' => __('Billing State', 'learndash' ),
														'format_type' => 'text',
														),
										'customer_billing_zip' => array(
														'label' => __('Billing ZIP', 'learndash' ),
														'format_type' => 'text',
														),
									);
								}						
							}


							if ( ! empty( $transaction_meta_fields ) ) {
								$transaction_meta_data[] = array(
									'name' => __( 'Order ID', 'learndash' ),
									'value' => $transaction->ID,
								);
								$transaction_meta_data[] = array(
									'name' => __( 'Order Date', 'learndash' ),
									'value' => learndash_adjust_date_time_display( strtotime( $transaction->post_date ) ),
								);
								foreach ( $transaction_meta_fields as $meta_key => $meta_set ) {
									$meta_value = get_post_meta( $transaction->ID, $meta_key, true );
									if ( ! empty( $meta_value ) ) {
										$transaction_meta_data[] = array(
											'name' => $meta_set['label'],
											'value' => $this->format_value( $meta_value, $meta_set['format_type'], $transaction ),
										);
									}
								}

								if ( !empty( $transaction_meta_data ) ) {

									$transaction_to_export[] = array(
										'group_id'    => 'ld-transactions',
										'group_label' => __( 'LearnDash LMS Purchase Transactions', 'learndash' ),
										'item_id'     => "ld-transactions-{$transaction->ID}",
										'data'        => $transaction_meta_data,
									);
								}
							}
						}

						if ( $page >= $transactions_query->max_num_pages ) {
							$return_array['done'] = true;
						} else{
							$return_array['done'] = false;
						}
					}
				}
			}

			if ( !empty( $transaction_to_export  ) ) {
				$return_array['data'] = $transaction_to_export;
			}

			return $return_array;
		}

		/**
		 * Perform Privacy Data Export for Course Assignments
		 *
		 * @since 2.5.8
		 * @param string $email_address Email Address of user to epxort.
		 * @param int    $page          Page number of export.
		 * @return array $return_data
		 */
		public function learndash_do_personal_data_exporter_course_assignments( $email_address, $page ) {
			$return_array = array(
				'data' => array(),
				'done' => true,
			);

			$assignments_to_export = array();

			$email_address = trim( $email_address );
			if ( ! empty( $email_address ) ) {

				$number = apply_filters('learndash_privacy_export_assignments_per_page', $this->per_page_default );
				$page   = (int) $page;

				$user_data = get_user_by( 'email', $email_address );
				if ( ! empty( $user_data ) ) {

					$assignments_query_args = array(
						'post_type' => 'sfwd-assignment',
						'author' => $user_data->ID,
						'posts_per_page' => $number,
						'paged'	=> $page,
					);

					$assignments_query = new WP_Query( $assignments_query_args );
					if ( ( isset( $assignments_query->posts ) ) && ( ! empty( $assignments_query->posts ) ) ) {
						$wp_upload_dir = wp_upload_dir();
						$wp_upload_base_dir = str_replace( '\\', '/', $wp_upload_dir['basedir'] );

						foreach ( (array) $assignments_query->posts as $assignment ) {
							$assignment_meta_data = array();

							$assignment_url = get_permalink( $assignment->ID );
							$assignment_meta_data[] = array(
								'name' => __( 'Assignment URL', 'learndash' ),
								'value' => $assignment_url,
							);

							$assignment_meta_data[] = array(
								'name' => __( 'Date', 'learndash' ),
								'value' => learndash_adjust_date_time_display( strtotime( $assignment->post_date ) ),
							);

							$course_id = get_post_meta( $assignment->ID, 'course_id', true );
							if ( ! empty( $course_id ) ) {
								$course_title = get_the_title( $course_id );
								if ( ! empty( $course_title  ) ) {
									$assignment_meta_data[] = array(
										'name' => sprintf( esc_html_x( '%s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
										'value' => $course_title,
									);
								}
							}

							$lesson_id = get_post_meta( $assignment->ID, 'lesson_id', true );
							if ( ! empty( $lesson_id ) ) {
								$lesson_title = get_the_title( $lesson_id );
								if ( ! empty( $lesson_title  ) ) {
									$assignment_meta_data[] = array(
										'name' => sprintf( esc_html_x( '%s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
										'value' => $lesson_title,
									);
								}
							}

							$assignments_to_export[] = array(
								'group_id'    => 'ld-course-assignments',
								'group_label' => sprintf( esc_html_x( 'LearnDash LMS %s Assignments', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
								'item_id'     => "ld-course-assignments-{$assignment->ID}",
								'data'        => $assignment_meta_data,
							);
						}
					}

					if ( $page >= $assignments_query->max_num_pages ) {
						$return_array['done'] = true;
					} else {
						$return_array['done'] = false;
					}
				}
			}

			if ( !empty( $assignments_to_export  ) ) {
				$return_array['data'] = $assignments_to_export;
			}

			return $return_array;
		}

		/**
		 * Perform Privacy Data Export for Quiz Essays
		 *
		 * @since 2.5.8
		 * @param string $email_address Email Address of user to epxort.
		 * @param int    $page          Page number of export.
		 * @return array $return_data
		 */
		public function learndash_do_personal_data_exporter_quiz_essays( $email_address, $page ) {
			$return_array = array(
				'data' => array(),
				'done' => true,
			);

			$essays_to_export = array();

			$email_address = trim( $email_address );
			if ( ! empty( $email_address ) ) {

				$number = apply_filters( 'learndash_privacy_export_quiz_essays_per_page', $this->per_page_default );
				$page   = (int) $page;

				$user_data = get_user_by( 'email', $email_address );
				if ( ! empty( $user_data ) ) {

					$essays_query_args = array(
						'post_type' => 'sfwd-essays',
						'author' => $user_data->ID,
						'posts_per_page' => $number,
						'paged'	=> $page,
					);

					$essays_query = new WP_Query( $essays_query_args );
					if ( ( isset( $essays_query->posts ) ) && ( ! empty( $essays_query->posts ) ) ) {
						$wp_upload_dir = wp_upload_dir();
						$wp_upload_base_dir = str_replace( '\\', '/', $wp_upload_dir['basedir'] );

						foreach ( (array) $essays_query->posts as $essay ) {
							$essay_meta_data = array();

							$essay_url = get_permalink( $essay->ID );
							if ( ! empty( $essay_url ) ) {
								$essay_meta_data[] = array(
									'name' => __( 'Essay URL', 'learndash' ),
									'value' => $essay_url,
								);
							}

							$essay_meta_data[] = array(
								'name' => __( 'Date', 'learndash' ),
								'value' => learndash_adjust_date_time_display( strtotime( $essay->post_date ) ),
							);

							$course_id = get_post_meta( $essay->ID, 'course_id', true );
							if ( ! empty( $course_id ) ) {
								$course_title = get_the_title( $course_id );
								if ( ! empty( $course_title  ) ) {
									$assignment_meta_data[] = array(
										'name' => sprintf( esc_html_x( '%s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
										'value' => $course_title,
									);
								}
							}

							$lesson_id = get_post_meta( $essay->ID, 'lesson_id', true );
							if ( ! empty( $lesson_id ) ) {
								$lesson_title = get_the_title( $lesson_id );
								if ( ! empty( $lesson_title  ) ) {
									$assignment_meta_data[] = array(
										'name' => sprintf( esc_html_x( '%s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
										'value' => $lesson_title,
									);
								}
							}

							$essays_to_export[] = array(
								'group_id'    => 'ld-quiz-essays',
								'group_label' => sprintf( esc_html_x( 'LearnDash LMS %s Essays', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
								'item_id'     => "ld-quiz-essys-{$essay->ID}",
								'data'        => $essay_meta_data,
							);
						}
					}

					if ( $page >= $essays_query->max_num_pages ) {
						$return_array['done'] = true;
					} else {
						$return_array['done'] = false;
					}
				}
			}

			if ( !empty( $essays_to_export  ) ) {
				$return_array['data'] = $essays_to_export;
			}

			return $return_array;
		}

		/**
		 * Add LearnDash as an Eraser package for WordPress data.
		 *
		 * @since 2.5.8
		 * @param array $erasers Array of registered erasers.
		 * @return array $erasers Array of registered erasers
		 */
		public function learndash_add_personal_data_erasers( $erasers = array() ) {
			$erasers[] = array(
				'eraser_friendly_name' => 'LearnDash LMS',
				'callback'             => array( $this, 'learndash_do_personal_data_eraser_transactions' ),
			);

			return $erasers;
		}

		/**
		 * Perform data eraser.
		 *
		 * Called by WordPress when performing data cleanup for specific user by email. This
		 * functions anonimizes users data contained in transaction generated via PayPal and Stripe.
		 *
		 * @since 2.5.8
		 *
		 * @param string $email_address Email of WP User to perform cleanup on.
		 * @param int    $page Page number or actions to perform. This is controlled by
		 * the function below. See the $number variable.
		 * @return array $return_data
		 */
		public function learndash_do_personal_data_eraser_transactions( $email_address = '', $page = 1 ) {
			global $wpdb;

			$return_data = array(
				'items_removed' => 0,
				'items_retained' => 0,
				'messages' => array(),
				'done' => true,
			);

			if ( ! empty( $email_address ) ) {
				$number = apply_filters( 'learndash_privacy_transactions_erase', $this->per_page_default );
				$page = (int) $page;

				$user_data = get_user_by( 'email', $email_address );
				if ( ! empty( $user_data ) ) {
					$transactions_query_args = array(
						'post_type' => 'sfwd-transactions',
						'author'    => $user_data->ID,
						'posts_per_page' => $number,
						'paged'     => $page,
					);

					$transactions_query = new WP_Query( $transactions_query_args );
					if ( ( isset( $transactions_query->posts ) ) && ( ! empty( $transactions_query->posts ) ) ) {
						$deleted_email = wp_privacy_anonymize_data( 'email' );
						$deleted_text = wp_privacy_anonymize_data( 'text' );
						$deleted_ip = wp_privacy_anonymize_data( 'ip' );

						foreach ( (array) $transactions_query->posts as $transaction ) {
							$transaction_meta_fields = array();

							$transaction->post_title = str_ireplace( $email_address, $deleted_email, $transaction->post_title );
							$update_ret = $wpdb->update(
								$wpdb->posts,
								array(
									'post_title' => $transaction->post_title,
								),
								array(
									'ID' => $transaction->ID,
								),
								array( '%s' ),
								array( '%d' )
							);

							if ( false !== $update_ret ) {
								$return_data['items_removed'] += 1;

								$transaction_meta_data = array();

								// STRIPE Transactions.
								if ( empty( $transaction_meta_fields ) ) {
									$transaction_type = get_post_meta( $transaction->ID, 'action', true );
									if ( 'stripe' === $transaction_type ) {

										$transaction_meta_fields = array(
											'stripe_token_email' => array(
																		'format_type' => 'email',
																	),
											'stripe_email' => array(
																		'format_type' => 'email',
																	),
										);
									}
								} 

								// PAYPAL Transactions.
								if ( empty( $transaction_meta_fields ) ) {
									$transaction_type = get_post_meta( $transaction->ID, 'ipn_track_id', true );
									if ( ! empty( $transaction_type ) ) {

										$transaction_meta_fields = array(
											'first_name'    => array(
																'format_type' => 'text',
																),
											'last_name'     => array(
																'format_type' => 'text',
															),
											'payer_email'   => array(
																'format_type' => 'email',
											),

										);
									}
								}

								// 2CHECKOUT Transactions
								if ( empty( $transaction_meta_fields ) ) {
									$transaction_type = get_post_meta( $transaction->ID, 'learndash-checkout', true );
									if ( $transaction_type == '2co' ) {

										$transaction_meta_fields = array(
											'first_name' => array(
																'format_type' => 'text',
															),
											'middle_initial' => array(
																	'format_type' => 'text',
															),
											'last_name' => array(
																'format_type' => 'text',
															),
											'email' => array(
															'format_type' => 'email',
															),
											'street_address' => array(
															'format_type' => 'text',
															),
											'street_address2' => array(
															'format_type' => 'text',
															),
											'city' => array(
															'format_type' => 'text',
															),
											'state'  => array(
															'format_type' => 'text',
															),
											'zip' => array(
															'format_type' => 'text',
															),
											'card_holder_name' => array(
															'format_type' => 'text',
															),

										);
									}						
								}

								// SAMCART Transactions
								if ( empty( $transaction_meta_fields ) ) {
									$order_ip_address = get_post_meta( $transaction->ID, 'order_ip_address', true );
									if ( !empty( $order_ip_address ) ) {
										$transaction_type = 'samcart';

										$transaction_meta_fields = array(
											'customer_email' => array(
																'format_type' => 'email',
															),
											'customer_first_name' => array(
																	'format_type' => 'text',
															),
											'customer_last_name' => array(
																'format_type' => 'text',
															),
											'customer_phone_number' => array(
															'format_type' => 'text',
															),
											'order_ip_address' => array(
															'format_type' => 'ip',
															),
											'customer_billing_address' => array(
															'format_type' => 'text',
															),
											'customer_billing_city' => array(
															'format_type' => 'text',
															),
											'customer_billing_state'  => array(
															'format_type' => 'text',
															),
											'customer_billing_zip' => array(
															'format_type' => 'text',
															),
										);
									}						
								}
							}

							if ( ! empty( $transaction_meta_fields ) ) {

								foreach ( $transaction_meta_fields as $meta_key => $meta_set ) {
									$meta_value = get_post_meta( $transaction->ID, $meta_key, true );
									if ( ( ! is_null( $meta_value ) ) && ( !empty( $meta_value ) ) ) {
										switch ( $meta_set['format_type'] ) {
											case 'email':
												$meta_value_after = str_ireplace( $meta_value, $deleted_email, $meta_value );
												break;

											case 'ip':
												$meta_value_after = str_ireplace( $meta_value, $deleted_ip, $meta_value );
												break;

											case 'text':	
											default:
												$meta_value_after = str_ireplace( $meta_value, $deleted_text, $meta_value );
												break;
										}

										if ( $meta_value_after !== $meta_value ) {
											update_post_meta( $transaction->ID, $meta_key, $meta_value_after );
										}
									}
								}
							}
						}

						// $return_data['done'] is set to true by default.
						// If we not have reached the max_number_pages then we are not done.
						if ( $page >= $transactions_query->max_num_pages ) {
							$return_data['done'] = true;
						} else {
							$return_data['done'] = false;
						}
					}
				}
			}

			return $return_data;
		}

		/**
		 * Get post meta keys for processing based on action.
		 *
		 * @since 2.5.8
		 * @param string $action The value will be either export or erase.
		 * @param object $transaction The sfwd-transactions post object being processed.
		 *
		 * @return array of meta keys to process
		 */
		function get_meta_keys( $action = '', WP_Post $transaction ) {
			$transaction_meta_fields = array();

			$transaction_type = get_post_meta( $transaction->ID, 'action', true );
			if ( 'stripe' === $transaction_type ) {

				if ( 'export' === $action ) {
					$transaction_meta_fields = array(
						'stripe_name' => array(
											'label' => __( 'Order Item', 'learndash' ),
											'format_type' => 'text',
											),
						'stripe_price' => array(
											'label' =>  __('Order Total', 'learndash' ),
											'format_type' => 'money_stripe',
											),
						'stripe_token_email' => array(
											'label' => __( 'Order Email', 'learndash' ),
											'format_type' => 'email',
											),
					);
				} else if ( 'erase' === $action ) {
					$transaction_meta_fields = array(
						'stripe_token_email'
					);
				}
			}

			return $transaction_meta_fields;
		}

		/**
		 * Formats the output value based on variable type.
		 *
		 * @since 2.5.8
		 *
		 * @param mixed  $meta_value The meta value for reformat.
		 * @param string $meta_type Will be the type of the meta_value. test, date, money etc.
		 * @param object $transaction The sfwd-transactions post object being processed.
		 *
		 * @return mixed $meta_value
		 */
		public function format_value( $meta_value = '', $meta_type, $transaction ) {
			if ( ( ! empty( $meta_value ) ) && ( ! empty( $meta_type ) ) ) {
				switch ( $meta_type ) {
					case 'money_stripe':
						$meta_value = $meta_value / 100;
					case 'money':
						$meta_value = money_format('%.2n', $meta_value );
						break;

					case 'date_string':
						$meta_value = strtotime( $meta_value );
					case 'date_number':	
						$meta_value = learndash_adjust_date_time_display( $meta_value );
					break;

					case 'text':
					default: 
						break;
				}
			}

			return $meta_value;
		}

		// end of functions.
	}
}
new LearnDash_GDPR();
