<?php
/**
 * LearnDash Settings Page Orderview.
 *
 * @since 3.0.0
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Overview' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Overview extends LearnDash_Settings_Page {

		/**
		 * License information
		 *
		 * @var array
		 */
		protected $license_info = [];

		/**
		 * Announcement posts feed
		 *
		 * @var array
		 */
		protected $rss_announcements_posts = [];

		/**
		 * License information
		 *
		 * @var array
		 */
		protected $rss_tips_posts = [];

		/**
		 * License information
		 *
		 * @var array
		 */
		protected $rss_sell_posts = [];


		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_overview';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_lms_overview';
			$this->settings_page_title   = esc_html__( 'LearnDash Overview', 'learndash' );
			$this->settings_tab_title    = esc_html__( 'Overview', 'learndash' );
			$this->settings_tab_priority = 0;

			// Override action with custom plugins function for add-ons.
			// add_action( 'install_plugins_pre_plugin-information', array( $this, 'shows_addon_plugin_information' ) );
			add_filter( 'learndash_submenu', array( $this, 'submenu_item' ), 200 );

			add_filter( 'learndash_admin_tab_sets', array( $this, 'learndash_admin_tab_sets' ), 10, 3 );
			add_filter( 'learndash_header_data', array( $this, 'admin_header' ), 40, 3 );
			add_action( 'wp_ajax_save_bootcamp_toggle_state', array( $this, 'save_bootcamp_toggle_state' ) );
			add_action( 'wp_ajax_save_bootcamp_mark_complete_state', array( $this, 'save_bootcamp_mark_complete_state' ) );

			parent::__construct();
		}

		/**
		 * Control visibility of submenu items based on lisence status
		 *
		 * @since 3.0.0
		 *
		 * @param array $submenu Submenu item to check.
		 * @return array $submenu
		 */
		public function submenu_item( $submenu ) {
			if ( ! isset( $submenu[ $this->settings_page_id ] ) ) {
				$submenu_save = $submenu;
				$submenu      = array();

				$submenu[ $this->settings_page_id ] = array(
					'name'  => $this->settings_tab_title,
					'cap'   => $this->menu_page_capability,
					'link'  => $this->parent_menu_page_url,
					'class' => 'submenu-ldlms-overview',
				);

				$submenu = array_merge( $submenu, $submenu_save );
			}

			return $submenu;
		}

		/**
		 * Filter the admin header data. We don't want to show the header panel on the Overview page.
		 *
		 * @since 3.0
		 * @param array $header_data Array of header data used by the Header Panel React app.
		 * @param string $menu_key The menu key being displayed.
		 * @param array $menu_items Array of menu/tab items.
		 *
		 * @return array $header_data.
		 */
		public function admin_header( $header_data = array(), $menu_key = '', $menu_items = array() ) {
			// Clear out $header_data if we are showing our page.
			if ( $menu_key === $this->parent_menu_page_url ) {
				$header_data = array();
			}

			return $header_data;
		}

		/**
		 * Filter for page title wrapper.
		 *
		 * @since 3.0.0
		 */
		public function get_admin_page_title() {
			return apply_filters( 'learndash_admin_page_title', '<h1>' . $this->settings_page_title . '</h1>' );
		}

		/**
		 * Action function called when Add-ons page is loaded.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_page() {

			global $learndash_assets_loaded;

			wp_enqueue_style(
				'learndash-admin-overview-page-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-overview-page' . leardash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash-admin-overview-page-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash-admin-overview-page-style'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash-admin-overview-page-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-overview-page' . leardash_min_asset() . '.js',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['learndash-admin-overview-page-script'] = __FUNCTION__;

			$learndash_admin_overview_page_strings = [
				'mark_complete'   => esc_html__( 'Mark Complete', 'learndash' ),
				'mark_incomplete' => esc_html__( 'Mark Incomplete', 'learndash' ),
			];

			wp_localize_script( 'learndash-admin-overview-page-script', 'LearnDashOverviewPageData', $learndash_admin_overview_page_strings );
		}

		/**
		 * Hide the tab menu items if on add-on page.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $tab_set Tab Set.
		 * @param string $tab_key Tab Key.
		 * @param string $current_page_id ID of shown page.
		 *
		 * @return array $tab_set
		 */
		public function learndash_admin_tab_sets( $tab_set = array(), $tab_key = '', $current_page_id = '' ) {
			if ( ( ! empty( $tab_set ) ) && ( ! empty( $tab_key ) ) && ( ! empty( $current_page_id ) ) ) {
				if ( 'admin_page_learndash_lms_overview' === $current_page_id ) {
					?>
					<style> h1.nav-tab-wrapper { display: none; }</style>
					<?php
				}
			}
			return $tab_set;
		}

		/**
		 * Save toggle state of the LearnDash Bootcamp to an option.
		 *
		 * @since 3.0.0
		 */
		public function save_bootcamp_toggle_state() {
			if ( isset( $_POST['action'], $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'learndash-bootcamp-toggle' ) &&
				current_user_can( 'edit_posts' ) && 'save_bootcamp_toggle_state' === $_POST['action'] ) {
				if ( ! empty( $_POST['state'] ) ) {
					update_option( 'learndash_bootcamp_toggle_state', sanitize_text_field( $_POST['state'] ) );
				}
			}
		}

		/**
		 * Save 'mark complete' state of LearnDash Bootcamp sections to an option.
		 *
		 * @since 3.0.0
		 */
		public function save_bootcamp_mark_complete_state() {
			if ( isset( $_POST['action'], $_POST['nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'learndash-bootcamp-mark-complete' ) &&
				current_user_can( 'edit_posts' ) && 'save_bootcamp_mark_complete_state' === $_POST['action'] ) {
				if ( ( ! empty( $_POST['state'] ) ) && ( ! empty( $_POST['id'] ) ) ) {
					update_option( sanitize_text_field( $_POST['id'] ), sanitize_text_field( $_POST['state'] ) );
				}
			}
		}

		/**
		 * Get feeds from learndash.com and the support site.
		 *
		 * @since 3.0.0
		 */
		public function get_feeds() {
			include_once ABSPATH . WPINC . '/feed.php';

			$rss_announcements = fetch_feed( 'https://www.learndash.com/category/learndash/feed' );
			if ( ! is_wp_error( $rss_announcements ) ) {
				$this->rss_announcements_posts = $rss_announcements->get_items( 0, $rss_announcements->get_item_quantity( 4 ) );
			}

			$rss_sell = fetch_feed( 'https://www.learndash.com/category/sell-online-courses/feed' );
			if ( ! is_wp_error( $rss_sell ) ) {
				$this->rss_sell_posts = $rss_sell->get_items( 0, $rss_sell->get_item_quantity( 4 ) );
			}

			$rss_tips = fetch_feed( 'https://www.learndash.com/category/learndash-tips/feed' );
			if ( ! is_wp_error( $rss_tips ) ) {
				$this->rss_tips_posts = $rss_tips->get_items( 0, $rss_tips->get_item_quantity( 4 ) );
			}
		}

		/**
		 * Check and update license information
		 *
		 * @since 3.0.0
		 */
		private function check_and_update_license() {
			$updater = learndash_get_updater_instance();
			if ( ( $updater ) && ( is_a( $updater, 'nss_plugin_updater_sfwd_lms' ) ) ) {
				// Check if we have new user input.
				if ( ( isset( $_POST['update_nss_plugin_license_sfwd_lms'], $_POST['ld_bootcamp_license_form_nonce'] ) )
					&& ( wp_verify_nonce( sanitize_key( $_POST['ld_bootcamp_license_form_nonce'] ), 'ld_bootcamp_license_form_nonce' ) ) ) {
					// Read their posted value.
					$license = isset( $_POST['nss_plugin_license_sfwd_lms'] ) ? sanitize_text_field( wp_unslash( $_POST['nss_plugin_license_sfwd_lms'] ) ) : '';
					$email   = isset( $_POST['nss_plugin_license_email_sfwd_lms'] ) ? sanitize_email( wp_unslash( $_POST['nss_plugin_license_email_sfwd_lms'] ) ) : '';

					// Save the posted value in the database.
					update_option( 'nss_plugin_license_sfwd_lms', $license );
					update_option( 'nss_plugin_license_email_sfwd_lms', $email );

					$updater->reset();
					?>
					<script>window.location.reload()</script>
					<?php
				} else {

					/**
					 * @TODO : All this logic needs to be encapsulated within the ld-qutoupdate.php
					 * code. We should not be exposing settings keys like 'nss_plugin_license_sfwd_lms'
					 * and 'nss_plugin_license_email_sfwd_lms' spread all over the LD code.
					 * There should be an interface function that simply returns the license
					 * details and status.
					 */

					// Get values from the database.
					$license = get_option( 'nss_plugin_license_sfwd_lms' );
					$email   = get_option( 'nss_plugin_license_email_sfwd_lms' );

					// Make sure there are values.
					if ( empty( $license ) || empty( $email ) ) {
						?>
						<p class="notice notice-error">
							<?php
							echo sprintf(
								// translators: placeholder: Link to purchase LearnDash.
								esc_html_x( 'Please enter your email and a valid license or %s a license now.', 'placeholder: link to purchase LearnDash', 'learndash' ),
								"<a href='http://www.learndash.com/' target='_blank' rel='noreferrer noopener'>" . esc_html__( 'buy', 'learndash' ) . '</a>'
							);
							?>
						</p>
						<?php
					}

					// Check the license.
					if ( ! empty( $license ) && ! empty( $email ) ) {
						$license_status = is_learndash_license_valid();

						if ( ! $license_status ) {
							// Clear just to be sure.

							$license_status = false;

							/**
							 * We don't want to call getRemote_license() on every page
							 * load. So we use the time_to_recheck() logic.
							 */
							if ( $updater->time_to_recheck() ) {
								$license_status = $updater->getRemote_license();

								/**
								 * NOTE: The getRemote_license() does not update the option.
								 * So we need to do it. And it needs to be set as an array structure.
								 */
								update_option( 'nss_plugin_remote_license_sfwd_lms', array( 'value' => $license_status ) );

								// Then re-update the licens using new utility function.
								// Plus this provides simpler true/false boolean.
								$license_status = is_learndash_license_valid();
							}
						}

						$this->license_info['license'] = $license;
						$this->license_info['email']   = $email;
						$this->license_info['status']  = $license_status;
					}
				}
			}
		}


		/**
		 * Utility function to maybe display the Bootcamp
		 *
		 * @since 3.0.0
		 *
		 * @param $toggle_state string Option value.
		 *
		 * @return string
		 */
		public function maybe_display_bootcamp( $toggle_state ) {
			if ( ! $toggle_state || 'show' === $toggle_state ) {
				return 'block';
			} else {
				return 'none';
			}
		}

		/**
		 * Custom display function for page content.
		 *
		 * @since 3.0.0
		 */
		public function show_settings_page() {
			$toggle_state = get_option( 'learndash_bootcamp_toggle_state' );
			$this->get_feeds();
			if ( learndash_is_admin_user() ) :
				$this->check_and_update_license();
			endif;
			?>
			<div class="wrap learndash-settings-page-wrap learndash-overview-page-wrap">
				<div class="ld-bootview">
					<h1><?php echo $this->settings_tab_title; ?></h1>

					<div class="ld-bootcamp" style="display:<?php echo isset( $toggle_state ) ? esc_attr( $this->maybe_display_bootcamp( $toggle_state ) ) : 'block'; ?>;">
						<div class="ld-bootcamp__widget">
							<div class="ld-bootcamp__widget--header">
								<h2><?php echo esc_html_x( 'LearnDash Bootcamp', 'LearnDash Bootcamp Title', 'learndash' ); ?></h2>
								<button class="ld-bootcamp--toggle" id="ld-bootcamp--hide" type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-toggle' ) ); ?>"><?php esc_html_e( 'Hide LearnDash Bootcamp', 'learndash' ); ?></button>
							</div>

							<div class="ld-bootcamp__widget--body">
								<div class="ld-bootcamp__accordion" role="tablist">
									<div class="ld-bootcamp__accordion--single <?php echo is_learndash_license_valid() ? '-completed' : ''; ?>">
										<h3>
											<span class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true"></span>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-1" role="tab">
											<?php echo esc_html_x( 'Enter Your License', 'Bootcamp headline', 'learndash' ); ?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-1" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p><strong><?php esc_html_e( 'Welcome to LearnDash!', 'learndash' ); ?></strong><br/>
											<?php esc_html_e( 'We know you are excited to get started, but before you do it is very important that you first add your license details below!', 'learndash' ); ?></p>
											<ul>
												<li><?php esc_html_e( 'Your active license gives you access to product support and updates that we push out.', 'learndash' ); ?></li>
												<li><?php esc_html_e( 'Your license details were emailed to you after purchase.', 'learndash' ); ?></li>
												<li><?php
												echo sprintf(
													// translators: placeholder: Link to the license page on the LearnDash website.
													esc_html_x( 'You can also find them listed %1$s', 'Link to the license page on the LearnDash website', 'learndash' ),
													"<a href='https://support.learndash.com/account/' target='_blank' rel='noreferrer noopener'>" . esc_html__( 'on your account.', 'learndash' ) . '</a>'
												);
												?></li>
											</ul>

											<?php
											if ( learndash_is_admin_user() ) :
												?>
												<div class="ld-bootcamp__license">
													<form method="post" action="">
													<?php
													if ( ! is_learndash_license_valid() ) :
														?>
														<p class="notice notice-error is-dismissible">
														<?php
														echo sprintf(
															// translators: placeholder: Link to purchase LearnDash.
															esc_html_x( 'Please enter your email and a valid license or %s a license now.', 'placeholder: link to purchase LearnDash', 'learndash' ),
															"<a href='http://www.learndash.com/' target='_blank' rel='noreferrer noopener'>" . esc_html__( 'buy', 'learndash' ) . '</a>'
														);
														?>
														</p>
													<?php else : ?>
														<p class="notice notice-success is-dismissible"><?php esc_html_e( 'Your license is valid.', 'learndash' ); ?></p>
														<?php
													endif;
													?>

													<div class="ld-bootcamp__license--fields">
															<label for="ld-bootcamp__email"><?php echo esc_html_x( 'Enter your Email here', 'License email', 'learndash' ); ?></label>
															<input type="email" value="<?php echo empty( $this->license_info['email'] ) ? '' : esc_html( $this->license_info['email'] ); ?>" id="ld-bootcamp__email" name="nss_plugin_license_email_sfwd_lms" />
														</div>
														<div class="ld-bootcamp__license--fields">
															<label for="ld-bootcamp__license-key"><?php echo esc_html_x( 'Enter your license key here', 'License key', 'learndash' ); ?></label>
															<input type="text" value="<?php echo empty( $this->license_info['license'] ) ? '' : esc_html( $this->license_info['license'] ); ?>" id="ld-bootcamp__license-key" name="nss_plugin_license_sfwd_lms" />
														</div>

														<input type="submit" value="<?php esc_html_e( 'Save license', 'learndash' ); ?>" name="update_nss_plugin_license_sfwd_lms" class="button button-primary" />
														<?php wp_nonce_field( 'ld_bootcamp_license_form_nonce', 'ld_bootcamp_license_form_nonce' ); ?>
													</form>
												</div>
											<?php else : ?>
												<p class="notice notice-error">
													<?php esc_html_e( 'You do not have sufficient permissions to change the license information.', 'learndash' ); ?>
												</p>
											<?php endif; ?>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_2' ) ? '-completed' : ''; ?>">
										<h3>
											<button class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_2" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-2" role="tab">
											<?php esc_html_e( 'LearnDash Overview', 'learndash' ); ?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-2" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
										<p>
										<?php esc_html_e( 'In this video we will briefly explain the layout of LearnDash, our free add-ons, and where you can go to read more details about our features.', 'learndash' ); ?>
										</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/yX5tr5gU_KE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Documentation', 'learndash' ); ?></a></li>
														<li><a href="https://www.learndash.com/support/docs/getting-started/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting Started [Guide]', 'learndash' ); ?></a></li>
														<li><a href="https://support.learndash.com/contact-support" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact Support', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button role="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_2" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>

									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_3' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_3" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-3" role="tab">
											<?php
											echo sprintf(
												// translators: placeholder: Courses, Course.
												esc_html_x( 'Creating %1$s with the %2$s Builder', 'placeholder: Courses, Course', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'courses' ),
												LearnDash_Custom_Label::get_label( 'course' )
											)
												?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-3" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: Course.
												esc_html_x( 'In this video we will demonstrate how you can create a course using the LearnDash %s Builder.', 'placeholder: Course.', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'course' )
											);
												?>
												</p>
											<div class="ld-bootcamp__embed">
													<iframe width="560" height="315" data-src="https://www.youtube.com/embed/cZ61RgRUXnw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">
														<?php
														echo sprintf(
															// translators: placeholder: Course.
															esc_html_x( '%s Builder [Article]', 'placeholder: Course', 'learndash' ),
															LearnDash_Custom_Label::get_label( 'course' )
														);
														?>
														</a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
												<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_3" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>

									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_4' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_4" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-4" role="tab">
											<?php
											echo sprintf(
												// translators: placeholders: Lessons, Topics
												esc_html_x( 'Adding Content Using %1$s & %2$s', 'placeholders: Lessons, Topics', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'lessons' ),
												LearnDash_Custom_Label::get_label( 'topics' )
											);
											?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-4" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholders: Course, Lessons, Topics
												esc_html_x( 'Now that you have your %1$s created, it is time to start adding content via %2$s and %3$s. In this video we will show how to do this and explain the various settings.', 'placeholders: Course, Lessons, Topics', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'course' ),
												LearnDash_Custom_Label::get_label( 'lessons' ),
												LearnDash_Custom_Label::get_label( 'topics' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/PD1KKzdakHw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/core/lessons/" target="_blank" rel="noopener noreferrer">
														<?php
														echo sprintf(
															// translators: placeholder: Lessons.
															esc_html_x( '%s Documentation', 'placeholder: Lessons', 'learndash' ),
															LearnDash_Custom_Label::get_label( 'lessons' )
														);
														?>
														</a></li>
														<li><a href="https://www.learndash.com/support/docs/core/topics/" target="_blank" rel="noopener noreferrer">
														<?php
														echo sprintf(
															// translators: placeholder: Topics.
															esc_html_x( '%s Documentation', 'placeholder: Topics', 'learndash' ),
															LearnDash_Custom_Label::get_label( 'topics' )
														);
														?>
														</a></li>
													</ul>
													</div>
												<div class="ld-bootcamp__mark_complete">
												<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_4" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>

									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_5' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_5" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-5" role="tab">
											<?php
											echo sprintf(
												// translators: placeholder: Quizzes
												esc_html_x( 'Creating %s', 'placeholder: Quizzes', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'quizzes' )
											);
											?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-5" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: Quizzes, course, quizzes, course, Quiz, Questions
												esc_html_x( '%1$s are a great way to check if your learners are understanding the %2$s content. You can have one or more %3$s throughout a %4$s, or you can put it at the end. In this video we demonstrate how to create a %5$s and how to add %6$s.', 'placeholder: Quizzes, course, quizzes, course, Quiz, Questions', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'quizzes' ),
												learndash_get_custom_label_lower( 'course' ),
												learndash_get_custom_label_lower( 'quizzes' ),
												learndash_get_custom_label_lower( 'course' ),
												LearnDash_Custom_Label::get_label( 'quiz' ),
												LearnDash_Custom_Label::get_label( 'questions' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/eqH-gSum-qA" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/sr24gWa1SbE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/core/quizzes/" target="_blank" rel="noopener noreferrer"><?php
														echo sprintf(
															// translators: placeholder: Quizzes.
															esc_html_x( '%s Documentation', 'placeholder: Quizzes.', 'learndash' ),
															LearnDash_Custom_Label::get_label( 'quizzes' )
														);
														?></a></li>
														<li><a href="https://www.learndash.com/support/docs/core/certificates/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Certificate Documentation', 'learndash' ); ?></a></li>
													</ul>
													</div>
												<div class="ld-bootcamp__mark_complete">
												<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_5" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_6' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_6" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-6" role="tab">
											<?php esc_html_e( 'Setting-up User Registration', 'learndash' ); ?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-6" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: Courses
												esc_html_x( 'Once you have finished creating your %s it is time to configure user registration so that people can access them! In this video we explain how to create an attractive login and registration form.', 'placeholder: Courses', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'courses' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/4PJKUIUsurs" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/guides/login-registration/learndash/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Login & Registration [Guide]', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_6" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_7' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_7" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-7" role="tab">
											<?php
											echo sprintf(
												// translators: placeholder: Courses.
												esc_html_x( 'Selling Your %s', 'placeholder: Courses.', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'courses' )
											);
											?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-7" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translatord: placeholders: Courses, courses.
												esc_html_x( 'If you are selling your %1$s then you have many options available to you! In the first video we demonstrate how you can quickly start accepting payments with PayPal and Stripe. In the second video we will show you how to sell %2$s using the popular WordPress shopping cart WooCommerce.', 'placeholders: Courses, courses', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'courses' ),
												learndash_get_custom_label_lower( 'courses' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/gzGt9pd0eOM" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/38X3Pst5b64" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/core/courses/course-access/" target="_blank" rel="noopener noreferrer"><?php
														echo sprintf(
															// translators: placeholder: Course.
															esc_html_x( '%s Access Settings [Article]', 'placeholder: Course.', 'learndash' ),
															LearnDash_Custom_Label::get_label( 'course' )
														);
														?></a></li>
														<li><a href="https://www.learndash.com/support/docs/core/settings/paypal-settings/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'PayPal Settings [Article]', 'learndash' ); ?></a></li>
														<li><a href="https://www.learndash.com/support/docs/add-ons/stripe/#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Stripe Integration [Article]', 'learndash' ); ?></a></li>
														<li><a href="https://www.learndash.com/support/docs/add-ons/woocommerce/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WooCommerce Integration [Article]', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_7" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_8' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_8" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-8" role="tab">
											<?php
											echo sprintf(
												// translators: placeholder: Course.
												esc_html_x( 'Creating a %s Listing', 'placeholder: Course', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'course' )
											);
											?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-8" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: Course, Courses, Course.
												esc_html_x( 'Your %1$s is created and you have also configured registration/login and how you will accept payment (in the event that you are selling your %2$s). It is now time to create a %3$s Listing which is easy to do using the Course Grid Add-on.', 'placeholder: Course, Courses, Course', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'course' ),
												LearnDash_Custom_Label::get_label( 'courses' ),
												LearnDash_Custom_Label::get_label( 'course' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/ZJm7l3vUNRU" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/add-ons/course-grid/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Course Grid Add-on [Article]', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button role="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_8" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_9' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_9" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-9" role="tab">
											<?php esc_html_e( 'Adding a User Profile Page', 'learndash' ); ?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-9" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: courses.
												esc_html_x( 'The final step is to create a User Profile so that your users can instantly see which %s they have access to, their progress, performance, and earned certificates!', 'placeholder: courses', 'learndash' ),
												learndash_get_custom_label_lower( 'courses' )
											);
											?>
											</p>
											<div class="ld-bootcamp__embed">
												<iframe width="560" height="315" data-src="https://www.youtube.com/embed/Vn-Lf638UXU" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
											</div>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<p><?php esc_html_e( 'Additional Resources:', 'learndash' ); ?></p>
													<ul>
														<li><a href="https://www.learndash.com/support/docs/guides/user-profiles/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'User Profiles [Guide]', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_9" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
									<div class="ld-bootcamp__accordion--single <?php echo 'true' === get_option( 'learndash_bootcamp_mark_complete_section_10' ) ? '-completed' : ''; ?>">
										<h3>
											<button type="button" class="ld-bootcamp__mark-complete--toggle-indicator" aria-hidden="true" data-id="learndash_bootcamp_mark_complete_section_10" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"></button>
											<button class="ld-bootcamp__accordion--toggle" type="button" aria-selected="false" aria-expanded="false" aria-controls="ld-bootcamp__accordion--content-10" role="tab">
											<?php esc_html_e( 'Important Resources', 'learndash' ); ?>
											<span class="ld-bootcamp__accordion--toggle-indicator"></span>
											</button>
										</h3>

										<div id="ld-bootcamp__accordion--content-10" class="ld-bootcamp__accordion--content" aria-hidden="true" role="tabpanel">
											<p>
											<?php
											echo sprintf(
												// translators: placeholder: courses
												esc_html_x( 'Setting up a learning site is no small task – but you are not alone! Below are some resources available to you so that you can get the most out of your LearnDash powered %s!', 'placeholder: courses', 'learndash' ),
												learndash_get_custom_label_lower( 'courses' )
											);
											?>
											</p>
											<div class="ld-bootcamp__resources-box">
												<div class="ld-bootcamp__resources">
													<ul>
														<li><a href="https://www.facebook.com/groups/1020920397944393" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Community Facebook Group', 'learndash' ); ?></a></li>
														<li><a href="https://www.youtube.com/channel/UC1e38G3RVbTDHQrGPe1aVHw" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash YouTube Channel', 'learndash' ); ?></a></li>
														<li><a href="https://www.learndash.com/support/docs/getting-started/help/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to Get Help [Article]', 'learndash' ); ?></a></li>
													</ul>
												</div>
												<div class="ld-bootcamp__mark_complete">
													<button type="button" class="ld-bootcamp__mark-complete--toggle button-primary" data-id="learndash_bootcamp_mark_complete_section_10" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-mark-complete' ) ); ?>"><?php esc_html_e( 'Mark Complete', 'learndash' ); ?></button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="ld-overview">
						<div class="ld-overview--columns">
							<div class="ld-overview--column ld-overview--widget">
								<h2><?php esc_html_e( 'Tips and Tricks', 'learndash' ); ?></h2>

								<div class="ld-overview--columns -half">
									<div class="ld-overview--column">
										<h3>
											<svg width:"22" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
											<g fill="#000" fill-rule="evenodd">
												<path d="M496 384H64V80c0-8.84-7.16-16-16-16H16C7.16 64 0 71.16 0 80v336c0 17.67 14.33 32 32 32h464c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16zM464 96H345.94c-21.38 0-32.09 25.85-16.97 40.97l32.4 32.4L288 242.75l-73.37-73.37c-12.5-12.5-32.76-12.5-45.25 0l-68.69 68.69c-6.25 6.25-6.25 16.38 0 22.63l22.62 22.62c6.25 6.25 16.38 6.25 22.63 0L192 237.25l73.37 73.37c12.5 12.5 32.76 12.5 45.25 0l96-96 32.4 32.4c15.12 15.12 40.97 4.41 40.97-16.97V112c.01-8.84-7.15-16-15.99-16z"/>
											</g>
											</svg>


											<span>
											<?php
											echo sprintf(
												// translators: placeholder: Courses.
												esc_html_x( 'Sell Online %s', 'placeholder: Courses', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'courses' )
											);
											?>
											</span>
										</h3>
										<?php
										if ( ! $this->rss_sell_posts ) {
											esc_html_e( 'Something went wrong connecting to www.learndash.com. Please reload the page.', 'learndash' );
										} else {
											echo '<ul>';
											foreach ( $this->rss_sell_posts as $sell_post ) {
												echo '<li><a href="' . esc_url( $sell_post->get_permalink() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $sell_post->get_title() ) . '</a></li>';
											};
											echo '</ul>';
										}
										?>
										<p class="ld-overview--more">
											<a href="https://www.learndash.com/category/sell-online-courses/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
										</p>
									</div>

									<div class="ld-overview--column">
										<h3>
											<svg width="22" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 516">
											<g fill="#000" fill-rule="evenodd">
												<path d="M176 80c-52.94 0-96 43.06-96 96 0 8.84 7.16 16 16 16s16-7.16 16-16c0-35.3 28.72-64 64-64 8.84 0 16-7.16 16-16s-7.16-16-16-16zM96.06 459.17c0 3.15.93 6.22 2.68 8.84l24.51 36.84c2.97 4.46 7.97 7.14 13.32 7.14h78.85c5.36 0 10.36-2.68 13.32-7.14l24.51-36.84c1.74-2.62 2.67-5.7 2.68-8.84l.05-43.18H96.02l.04 43.18zM176 0C73.72 0 0 82.97 0 176c0 44.37 16.45 84.85 43.56 115.78 16.64 18.99 42.74 58.8 52.42 92.16v.06h48v-.12c-.01-4.77-.72-9.51-2.15-14.07-5.59-17.81-22.82-64.77-62.17-109.67-20.54-23.43-31.52-53.15-31.61-84.14-.2-73.64 59.67-128 127.95-128 70.58 0 128 57.42 128 128 0 30.97-11.24 60.85-31.65 84.14-39.11 44.61-56.42 91.47-62.1 109.46a47.507 47.507 0 0 0-2.22 14.3v.1h48v-.05c9.68-33.37 35.78-73.18 52.42-92.16C335.55 260.85 352 220.37 352 176 352 78.8 273.2 0 176 0z"/>
											</g>
											</svg>

											<span><?php esc_html_e( 'LearnDash Tips', 'learndash' ); ?></span>
										</h3>

										<?php
										if ( ! $this->rss_tips_posts ) {
											esc_html_e( 'Something went wrong connecting to www.learndash.com. Please reload the page.', 'learndash' );
										} else {
											echo '<ul>';
											foreach ( $this->rss_tips_posts as $tip_post ) {
												echo '<li><a href="' . esc_url( $tip_post->get_permalink() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $tip_post->get_title() ) . '</a></li>';
											};
											echo '</ul>';
										}
										?>

										<p class="ld-overview--more">
											<a href="https://www.learndash.com/category/learndash-tips/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
										</p>
									</div>
								</div>
							</div>

							<div class="ld-overview--widget">
								<h2><?php esc_html_e( 'LearnDash News', 'learndash' ); ?></h2>

								<h3>
									<svg width="22" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
									<g fill="#000" fill-rule="evenodd">
										<path d="M552 64H112c-20.858 0-38.643 13.377-45.248 32H24c-13.255 0-24 10.745-24 24v272c0 30.928 25.072 56 56 56h496c13.255 0 24-10.745 24-24V88c0-13.255-10.745-24-24-24zM48 392V144h16v248c0 4.411-3.589 8-8 8s-8-3.589-8-8zm480 8H111.422c.374-2.614.578-5.283.578-8V112h416v288zM172 280h136c6.627 0 12-5.373 12-12v-96c0-6.627-5.373-12-12-12H172c-6.627 0-12 5.373-12 12v96c0 6.627 5.373 12 12 12zm28-80h80v40h-80v-40zm-40 140v-24c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v24c0 6.627-5.373 12-12 12H172c-6.627 0-12-5.373-12-12zm192 0v-24c0-6.627 5.373-12 12-12h104c6.627 0 12 5.373 12 12v24c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0-144v-24c0-6.627 5.373-12 12-12h104c6.627 0 12 5.373 12 12v24c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0 72v-24c0-6.627 5.373-12 12-12h104c6.627 0 12 5.373 12 12v24c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12z"/>
									</g>
									</svg>
									<span><?php esc_html_e( 'Announcements', 'learndash' ); ?></span>
								</h3>

								<?php
								if ( ! $this->rss_announcements_posts ) {
									esc_html_e( 'Something went wrong connecting to www.learndash.com. Please reload the page.', 'learndash' );
								} else {
									echo '<ul>';
									foreach ( $this->rss_announcements_posts as $announcement_post ) {
										echo '<li><a href="' . esc_url( $announcement_post->get_permalink() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $announcement_post->get_title() ) . '</a></li>';
									};
									echo '</ul>';
								}
								?>
								<p class="ld-overview--more">
									<a href="https://www.learndash.com/category/learndash/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
								</p>
							</div>
						</div>

						<div class="ld-overview--columns -support">
							<div class="ld-overview--widget -doc">
								<h2><?php esc_html_e( 'Documentation', 'learndash' ); ?></h2>

								<div class="ld-overview--search">
									<form id="ld-overview--search-documentation-form">
										<label for="ld-overview--search-term" class="screen-reader-text"><?php esc_html_e( 'Search the Documentation website. A new tab will be opened on submission.', 'learndash' ); ?></label>
										<input type="text" id="ld-overview--search-term" name="search" value="" placeholder="<?php esc_html_e( 'Search documentation', 'learndash' ); ?>" />
										<input type="submit" value="<?php esc_html_e( 'Search', 'learndash' ); ?>" class="button button-primary" />
									</form>
								</div>

								<div class="ld-overview--columns">
									<div class="ld-overview--column">
										<h4><?php esc_html_e( 'Getting Started', 'learndash' ); ?></h4>

										<ul>
											<li><a href="https://www.learndash.com/support/docs/getting-started/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Getting Started Guide', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/core/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Core Docs', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/core/courses/course-builder/" target="_blank" rel="noopener noreferrer">
											<?php
											echo sprintf(
												// translators: placeholder: Course.
												esc_html_x( '%s Builder', 'placeholder: Course.', 'learndash' ),
												LearnDash_Custom_Label::get_label( 'course' )
											);
											?>
											</a></li>
											<li><a href="https://www.learndash.com/support/docs/add-ons/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Add-ons', 'learndash' ); ?></a></li>
										</ul>
										<p class="ld-overview--more">
											<a href="https://www.learndash.com/support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
										</p>
									</div>

									<div class="ld-overview--column">
										<h4><?php esc_html_e( 'Popular Articles', 'learndash' ); ?></h4>

										<ul>
											<li><a href="https://www.learndash.com/support/docs/guides/login-registration/learndash/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Registration & Login', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/guides/focus-mode/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Focus Mode', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/guides/user-profiles/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'User Profiles', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/add-ons/course-grid/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Course Grid Add-on', 'learndash' ); ?></a></li>
										</ul>
										<p class="ld-overview--more">
											<a href="https://www.learndash.com/support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
										</p>
									</div>

									<div class="ld-overview--column">
										<h4><?php esc_html_e( 'FAQ', 'learndash' ); ?></h4>
										<ul>
											<li><a href="https://www.learndash.com/support/docs/getting-started/themes/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Recommended WordPress Themes', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/account/license/#why_won8217t_my_license_validate" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Invalid License Notice', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/faqs/design/hide-post-meta-data/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Hiding Post Meta Data', 'learndash' ); ?></a></li>
											<li><a href="https://www.learndash.com/support/docs/troubleshooting/404-errors-learndash-pages/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '404 Error on LearnDash Content', 'learndash' ); ?></a></li>
										</ul>
										<p class="ld-overview--more">
											<a href="https://www.learndash.com/support/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View more', 'learndash' ); ?></a>
										</p>
									</div>
								</div>

								<div class="ld-overview--topics">
									<h4><?php esc_html_e( 'Popular Support Topics', 'learndash' ); ?></h4>

									<div class="ld-overview--columns">
										<div class="ld-overview--column">
											<ul>
												<li><a href="https://www.learndash.com/support/docs/core/courses/" target="_blank" rel="noopener noreferrer">
												<?php
												echo sprintf(
													// translators: placeholder: Courses.
													esc_html_x( '%s', 'placeholder: Courses', 'learndash' ),
													LearnDash_Custom_Label::get_label( 'courses' )
												);
												?>
												</a></li>
												<li><a href="https://www.learndash.com/support/docs/core/lessons/" target="_blank" rel="noopener noreferrer">
												<?php
												echo sprintf(
													// translators: placeholder: Lessons.
													esc_html_x( '%s', 'placeholder: Lessons', 'learndash' ),
													LearnDash_Custom_Label::get_label( 'lessons' )
												);
												?>
												</a></li>
												<li><a href="https://www.learndash.com/support/docs/core/quizzes/" target="_blank" rel="noopener noreferrer">
												<?php
												echo sprintf(
													// translators: placeholder: Quizzes.
													esc_html_x( '%s', 'placeholder: Quizzes', 'learndash' ),
													LearnDash_Custom_Label::get_label( 'quizzes' )
												);
												?>
												</a></li>
												<li><a href="https://www.learndash.com/support/docs/core/certificates/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Certificates', 'learndash' ); ?></a></li>
											</ul>
										</div>

										<div class="ld-overview--column">
											<ul>
												<li><a href="https://www.learndash.com/support/docs/core/shortcodes-blocks/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Shortcodes', 'learndash' ); ?></a></li>
												<li><a href="https://www.learndash.com/support/docs/reporting/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Reporting', 'learndash' ); ?></a></li>
												<li><a href="https://www.learndash.com/support/docs/users-groups/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Users & Groups', 'learndash' ); ?></a></li>
												<li><a href="https://www.learndash.com/support/docs/add-ons/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Add-ons', 'learndash' ); ?></a></li>
											</ul>
										</div>
									</div>
								</div>
							</div>

							<div class="ld-overview--widget -support">
								<h2><?php esc_html_e( 'Support', 'learndash' ); ?></h2>

								<p><?php esc_html_e( 'Have some questions or need a helping hand? The LearnDash support team is standing by, ready to assist you!', 'learndash' ); ?></p>

								<a href="https://support.learndash.com/contact-support/" class="button button-primary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contact Support', 'learndash' ); ?></a>

								<ul>
									<li><a href="https://www.facebook.com/groups/1020920397944393" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash Facebook Group', 'learndash' ); ?></a></li>
									<li><a href="https://www.youtube.com/channel/UC1e38G3RVbTDHQrGPe1aVHw?sub_confirmation=1" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LearnDash YouTube', 'learndash' ); ?></a></li>
									<li><a href="https://www.learndash.com/changelog" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Changelog', 'learndash' ); ?></a></li>
									<li><button class="ld-bootcamp--toggle button button-orange" id="ld-bootcamp--show" type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'learndash-bootcamp-toggle' ) ); ?>"><?php echo esc_html_x( 'Show LearnDash Bootcamp', 'Toggles visibility of the LearnDash Bootcamp section', 'learndash' ); ?></button></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Overview::add_page_instance();
	}
);
