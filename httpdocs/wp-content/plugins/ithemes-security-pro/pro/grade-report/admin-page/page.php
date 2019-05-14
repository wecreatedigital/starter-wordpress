<?php


final class ITSEC_Grade_Report_Page {
	private $self_url = '';
	private $modules = array();
	private $translations = array();


	public function __construct() {
		add_action( 'itsec-page-show', array( $this, 'handle_page_load' ) );
		add_action( 'itsec-page-ajax', array( $this, 'handle_ajax_request' ) );
		add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'add_styles' ) );

		add_filter( 'screen_settings', array( $this, 'filter_screen_settings' ) );

		$this->set_translation_strings();


		require_once( ITSEC_Core::get_core_dir() . '/admin-pages/module-settings.php' );
		require_once( ITSEC_Core::get_core_dir() . '/admin-pages/sidebar-widget.php' );
		require_once( ITSEC_Core::get_core_dir() . '/lib/form.php' );


		do_action( 'itsec-settings-page-init' );


		if ( ! empty( $_POST ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$this->handle_post();
		}
	}

	public function add_scripts() {
		foreach ( $this->modules as $id => $module ) {
			$module->enqueue_scripts_and_styles();
		}

		$vars = array(
			'ajax_action'   => 'itsec_grade_report_page',
			'ajax_nonce'    => wp_create_nonce( 'itsec-grade-report-nonce' ),
			'translations'  => $this->translations,
		);

		wp_enqueue_script( 'itsec-grade-donut', plugins_url( 'js/grade-donut.js', __FILE__ ), array( 'jquery' ), ITSEC_Core::get_plugin_build(), true );
		wp_enqueue_script( 'itsec-grade-report-page-script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery-ui-dialog', 'itsec-grade-donut' ), ITSEC_Core::get_plugin_build(), true );
		wp_localize_script( 'itsec-grade-report-page-script', 'itsec_page', $vars );
	}

	public function add_styles() {
		wp_enqueue_style( 'itsec-settings-page-style', plugins_url( 'css/style.css', ITSEC_Core::get_core_dir() . '/admin-pages/init.php' ), array(), ITSEC_Core::get_plugin_build() );
		wp_enqueue_style( 'itsec-grade-report-page-style', plugins_url( 'css/style.css', __FILE__ ), array( 'itsec-settings-page-style' ), ITSEC_Core::get_plugin_build() );
		wp_enqueue_style( 'open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans', array(), ITSEC_Core::get_plugin_build() );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	private function set_translation_strings() {
		$this->translations = array(
			'loading'      => esc_html__( 'Loading...', 'it-l10n-ithemes-security-pro' ),
			'num_criteria' => esc_html__( '%d Grading Criteria', 'it-l10n-ithemes-security-pro' ),

			'ajax_invalid'            => new WP_Error( 'itsec-settings-page-invalid-ajax-response', __( 'An "invalid format" error prevented the request from completing as expected. The format of data returned could not be recognized. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_forbidden'          => new WP_Error( 'itsec-settings-page-forbidden-ajax-response: %1$s "%2$s"',  __( 'A "request forbidden" error prevented the request from completing as expected. The server returned a 403 status code, indicating that the server configuration is prohibiting this request. This could be due to a plugin/theme conflict or a server configuration issue. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings or server configuration that could account for this AJAX request being blocked.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_not_found'          => new WP_Error( 'itsec-settings-page-not-found-ajax-response: %1$s "%2$s"', __( 'A "not found" error prevented the request from completing as expected. The server returned a 404 status code, indicating that the server was unable to find the requested admin-ajax.php file. This could be due to a plugin/theme conflict, a server configuration issue, or an incomplete WordPress installation. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings, alter server configurations, or reinstall WordPress.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_server_error'       => new WP_Error( 'itsec-settings-page-server-error-ajax-response: %1$s "%2$s"', __( 'A "internal server" error prevented the request from completing as expected. The server returned a 500 status code, indicating that the server was unable to complete the request due to a fatal PHP error or a server problem. This could be due to a plugin/theme conflict, a server configuration issue, a temporary hosting issue, or invalid custom PHP modifications. Please check your server\'s error logs for details about the source of the error and contact your hosting company for assistance if required.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_unknown'            => new WP_Error( 'itsec-settings-page-ajax-error-unknown: %1$s "%2$s"', __( 'An unknown error prevented the request from completing as expected. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_timeout'            => new WP_Error( 'itsec-settings-page-ajax-error-timeout: %1$s "%2$s"', __( 'A timeout error prevented the request from completing as expected. The site took too long to respond. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_parsererror'        => new WP_Error( 'itsec-settings-page-ajax-error-parsererror: %1$s "%2$s"', __( 'A parser error prevented the request from completing as expected. The site sent a response that jQuery could not process. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),
		);

		foreach ( $this->translations as $key => $message ) {
			if ( is_wp_error( $message ) ) {
				$messages = ITSEC_Response::get_error_strings( $message );
				$this->translations[$key] = $messages[0];
			}
		}
	}

	private function handle_post() {
		if ( ITSEC_Core::is_ajax_request() ) {
			return;
		}
	}

	public function handle_ajax_request() {
		if ( WP_DEBUG ) {
			ini_set( 'display_errors', 1 );
		}

		check_admin_referer( 'itsec-grade-report-nonce' );

		if ( 'resolve_selected_issues' === $_POST['method'] ) {
			$this->resolve_selected_issues();
		}

		ITSEC_Response::send_json();
	}

	private function resolve_selected_issues() {
		require_once( dirname( dirname ( __FILE__ ) ) . '/report.php' );

		if ( empty( $_POST['selected'] ) ) {
			ITSEC_Response::add_info( __( 'No issues were selected. No changes were made.', 'it-l10n-ithemes-security-pro' ) );
			return;
		}

		ITSEC_Grading_System::resolve_issues( $_POST['selected'] );

		$form = new ITSEC_Form();
		$report = ITSEC_Grading_System::get_report();

		ob_start();
		$this->render_modal_content_main( $form, $report );
		$modal_content_main = ob_get_clean();

		ob_start();
		$this->render_modal_title( $report );
		$modal_title = ob_get_clean();

		ob_start();
		$this->render_cards( $form, $report );
		$cards = ob_get_clean();

		$data = array(
			'modalContentMain' => $modal_content_main,
			'modalTitle'       => $modal_title,
			'cards'            => $cards,
		);

		ITSEC_Response::add_js_function_call( 'updatePageAfterFixes', $data );
	}

	public function filter_screen_settings( $settings ) {
	}

	public function handle_page_load( $self_url ) {
		$this->self_url = $self_url;

		$this->show_grade_report();
	}

	private function show_grade_report() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-wp-list-table.php' );
		require_once( ITSEC_Core::get_core_dir() . '/admin-pages/logs-list-table.php' );
		require_once( dirname( dirname ( __FILE__ ) ) . '/report.php' );

		$form = new ITSEC_Form();
		$report = ITSEC_Grading_System::get_report();

?>
	<div id="itsec-grade-report" class="wrap">
		<h1>
			<?php _e( 'iThemes Security', 'it-l10n-ithemes-security-pro' ); ?>
			<a href="<?php echo esc_url( ITSEC_Core::get_settings_page_url() ); ?>" class="page-title-action"><?php _e( 'Manage Settings', 'it-l10n-ithemes-security-pro' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'itsec_support_url', 'https://wordpress.org/support/plugin/better-wp-security' ) ); ?>" class="page-title-action"><?php _e( 'Support', 'it-l10n-ithemes-security-pro' ); ?></a>
		</h1>

		<div id="itsec-settings-messages-container">
			<?php
				foreach ( ITSEC_Response::get_errors() as $error ) {
					ITSEC_Lib::show_error_message( $error );
				}

				foreach ( ITSEC_Response::get_messages() as $message ) {
					ITSEC_Lib::show_status_message( $message );
				}
			?>
		</div>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1 hide-if-no-js">
				<div id="postbox-container-2" class="postbox-container">
					<?php $form->start_form(); ?>
						<div class="itsec-cards-container hide-if-no-js">
							<ul id="itsec-grade-report-cards">
								<?php $this->render_cards( $form, $report ); ?>
							</ul>
						</div>
					<?php $form->end_form(); ?>
				</div>
				<div class="itsec-modal-background"></div>
				<div id="itsec-resolve-issues-container" class="grid">
					<div class="itsec-module-settings-container">
						<?php $form->start_form(); ?>
							<div class="itsec-modal-navigation">
								<button class="dashicons itsec-close-modal"></button>
								<div class="itsec-modal-title">
									<?php $this->render_modal_title( $report ); ?>
								</div>
							</div>
							<div class="itsec-module-settings-content-container">
								<div class="itsec-module-settings-content">
									<div class="itsec-module-messages-container"></div>
									<div class="itsec-module-settings-content-main">
										<?php $this->render_modal_content_main( $form, $report ); ?>
									</div>
								</div>
							</div>
							<?php if ( $report['fixable_issues'] ) : ?>
								<div class="itsec-modal-footer">
									<label for="itsec-select-all-issues">
										<?php $form->add_checkbox( 'select-all-issues' ); ?>
										<?php esc_html_e( 'Select All Resolvable Issues', 'it-l10n-ithemes-security-pro' ); ?>
									</label>
									<?php $form->add_submit( 'resolve-issues', esc_html__( 'Resolve Selected Issues', 'it-l10n-ithemes-security-pro' ) ); ?>
								</div>
							<?php endif; ?>
						<?php $form->end_form(); ?>
					</div>
				</div>
			</div>

			<div class="hide-if-js">
				<p class="itsec-warning-message"><?php _e( 'iThemes Security requires Javascript in order to create the grading report. Please enable Javascript.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</div>

			<div class="hidden" id="itsec-grade-report-cache"></div>
		</div>
	</div>
<?php

	}

	private function render_modal_title( $report ) {
		$this->render_card_grade( $report['grade']['real'] );
		$this->render_card_header_text( 'Grade Report Issues', 'it-l10n-ithemes-security-pro' );
		$this->render_card_subheader_text( sprintf( esc_html( _n( '%d Total Issue', '%d Total Issues', $report['issues'], 'it-l10n-ithemes-security-pro' ) ), $report['issues'] ) );
	}

	private function render_modal_content_main( $form, $report ) {

?>
	<ul id="itsec-resolve-issues-sections">
		<?php foreach ( $report['sections'] as $section ) : ?>
			<?php $this->render_resolve_issues_section( $form, $section ); ?>
		<?php endforeach; ?>
	</ul>
<?php

	}

	private function render_cards( $form, $report ) {
		$this->render_security_score_card( $form, $report );
		$this->render_summary_card( $form, $report );

		foreach ( $report['sections'] as $section ) {
			$this->render_section_card( $form, $section );
		}
	}

	private function render_security_score_card( $form, $report ) {
		$this->open_card( 'security-score' );

		$this->open_card_header();

		$this->render_card_header_text( esc_html__( 'Grade', 'it-l10n-ithemes-security-pro' ) );

		$subheader = '';

		if ( $report['grade']['real'] !== $report['grade']['potential'] ) {
			if ( in_array( substr( $report['grade']['potential'], 0, 1 ), array( 'A', 'F' ) ) ) {
				$subheader = sprintf( wp_kses( _n( 'Resolve <a href="#">1 issue</a> to raise the grade to an "%2$s".', 'Resolve <a href="#">%1$s issues</a> to raise the grade to an "%2$s".', $report['issues'], 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), $report['issues'], $report['grade']['potential'] );
			} else {
				$subheader = sprintf( wp_kses( _n( 'Resolve <a href="#">1 issue</a> to raise the grade to a "%2$s".', 'Resolve <a href="#">%1$s issues</a> to raise the grade to a "%2$s".', $report['issues'], 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), $report['issues'], $report['grade']['potential'] );
			}
		} else {
			$subheader = wp_kses( __( 'View grade report <a href="#">details</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) );
		}

		if ( ! empty( $subheader ) ) {
			$this->render_card_subheader_text( $subheader );
		}

		$this->close_card_header();

?>
	<div id="itsec-security-score-graph">
		<div class="itsec-grade itsec-grade-<?php echo esc_html( strtolower( substr( $report['grade']['real'], 0, 1 ) ) ); ?>"><?php echo esc_html( strtoupper( $report['grade']['real'] ) ); ?></div>
		<svg id="itsec-grade-donut" viewBox="-1 -1 2 2"></svg>
		<div id="itsec-grade-donut-data">
			<?php foreach ( $report['sections'] as $section ) : ?>
				<div data-value="<?php echo intval( $section['weight_percent'] ); ?>" data-grade="<?php echo esc_attr( strtolower( substr( $section['grade']['current'], 0, 1 ) ) ); ?>"></div>
			<?php endforeach; ?>
		</div>
	</div>

	<div id="itsec-security-score-graph-legend">
		<ul>
			<?php foreach ( $report['sections'] as $section ) : ?>
				<li><span class="itsec-grade itsec-grade-<?php echo esc_attr( strtolower( substr( $section['grade']['current'], 0, 1 ) ) ); ?>">◯</span> <?php printf( esc_html__( '%1$d%% %2$s', 'it-l10n-ithemes-security-pro' ), intval( $section['weight_percent'] ), $section['name'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php

		$this->open_card_footer();

?>
	<div class="itsec-letter-grade-legend">
		<span class="itsec-grade itsec-grade-a">■</span>
		<span class="itsec-letter">A</span>
		<span class="itsec-grade itsec-grade-b">■</span>
		<span class="itsec-letter">B</span>
		<span class="itsec-grade itsec-grade-c">■</span>
		<span class="itsec-letter">C</span>
		<span class="itsec-grade itsec-grade-d">■</span>
		<span class="itsec-letter">D</span>
		<span class="itsec-grade itsec-grade-f">■</span>
		<span class="itsec-letter">F</span>
	</div>
<?php



		$this->close_card_footer();

		$this->close_card();
	}

	private function render_summary_card( $form, $report ) {
		$this->open_card( 'summary' );
		$this->open_card_header();

		if ( $report['issues'] ) {
			$this->render_card_header_button( 'itsec-resolve-issues', esc_html__( 'Resolve Issues', 'it-l10n-ithemes-security-pro' ) );
		} else {
			$this->render_card_header_button( 'itsec-resolve-issues', esc_html__( 'View Grade Report Details', 'it-l10n-ithemes-security-pro' ) );
		}

		$this->render_card_header_text( esc_html__( 'Summary', 'it-l10n-ithemes-security-pro' ) );

		$report_datetime = date( _x( 'l, F j, Y', 'security score assessment date format' ), $report['timestamp'] );
		$subheader = sprintf( wp_kses( __( '<span class="itsec-assessed-on-label">Assessed on: </span>%s', 'it-l10n-ithemes-security-pro' ), array( 'span' => array( 'class' => array() ) ) ), $report_datetime );
		$this->render_card_subheader_text( $subheader );
		$this->close_card_header();

?>
	<div id="itsec-summary">
		<ul id="itsec-summary-canvas-captions">
			<?php foreach ( $report['sections'] as $section ) : ?>
				<li><?php echo esc_html( $section['name'] ); ?></li>
			<?php endforeach; ?>
		</ul>

		<div id="itsec-summary-canvas-container">
			<canvas width="200" height="200">
				<?php foreach ( $report['sections'] as $section ) : ?>
				<div data-id="<?php echo esc_attr( $section['id'] ); ?>" data-name="<?php echo esc_attr( $section['name'] ); ?>" data-current="<?php echo intval( $section['score']['current'] ); ?>" data-potential="<?php echo intval( $section['score']['potential'] ); ?>" data-max="<?php echo intval( $section['score']['max'] ); ?>"></div>
				<?php endforeach; ?>
			</canvas>
		</div>
	</div>
<?php

		$this->open_card_footer();

?>
	<div class="itsec-bar-chart-legend">
		<span class="itsec-bar-chart-color itsec-bar-chart-color-current">■</span>
		<span class="itsec-bar-chart-label"><?php esc_html_e( 'Current Score', 'it-l10n-ithemes-security-pro' ); ?></span>
		<span class="itsec-bar-chart-color itsec-bar-chart-color-potential">■</span>
		<span class="itsec-bar-chart-label"><?php esc_html_e( 'Potential Score', 'it-l10n-ithemes-security-pro' ); ?></span>
	</div>
<?php



		$this->close_card_footer();

		$this->close_card();
	}

	private function render_resolve_issues_section( $form, $section ) {
		$this->open_card( $section['id'], 'itsec-section-card' );
		$this->open_card_header();

		$this->render_card_grade( $section['grade']['current'] );
		$this->render_card_header_text( $section['name'] );

		$this->close_card_header();

?>
	<div class="itsec-section-icon"></div>
	<div class="itsec-section-list">
		<table>
			<thead>
				<tr>
					<th><?php esc_html_e( 'Entry', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th><?php esc_html_e( 'Explanation', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th><?php esc_html_e( 'Grade', 'it-l10n-ithemes-security-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $section['criteria'] as $id => $criterion ) : ?>
					<tr id="itsec-criterion-<?php echo esc_attr( $section['id'] . '__' . str_replace( ':', '_', $id ) ); ?>">
						<td class="itsec-criterion-entry">
							<?php if ( $criterion['issue'] && $criterion['fixable'] ) : ?>
								<label>
							<?php endif; ?>
							<span class="itsec-criterion-fix-container">
								<?php if ( $criterion['issue'] && $criterion['fixable'] ) : ?>
									<input type="checkbox" data-id="<?php echo esc_html( "{$section['id']}::$id" ); ?>" />
								<?php endif; ?>
							</span>
							<span class="itsec-criterion-name">
								<?php echo esc_html( $criterion['name'] ); ?>
							</span>
							<?php if ( $criterion['issue'] && $criterion['fixable'] ) : ?>
								</label>
							<?php endif; ?>
						</td>
						<td class="itsec-criterion-explanation"><?php echo wp_kses( $criterion['details'], array( 'a' => array( 'href' => array() ) ) ); ?></td>
						<td class="itsec-criterion-grade itsec-grade-<?php echo esc_attr( strtolower( substr( $criterion['grade'], 0, 1 ) ) ); ?>">
							<?php echo esc_html( $criterion['grade'] ); ?>&nbsp;<span class="itsec-grade itsec-grade-<?php echo strtolower( substr( $criterion['grade'], 0, 1 ) ); ?>"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php

		$this->close_card();
	}

	private function render_section_card( $form, $section ) {
		$this->open_card( $section['id'], 'itsec-section-card' );
		$this->open_card_header();

		$this->render_card_grade( $section['grade']['current'] );
		$this->render_card_header_text( $section['name'] );
		$this->render_card_subheader_text( $section['description'] );

		$this->close_card_header();

?>
	<div class="itsec-section-icon"></div>
	<div class="itsec-section-list">
		<ul>
			<?php foreach ( $section['criteria'] as $id => $criterion ) : ?>
				<li id="itsec-section-list-<?php echo esc_attr( $section['id'] . '_' . str_replace( ':', '_', $id ) ); ?>">
					<?php echo $criterion['name']; ?>
					<span class="itsec-grade itsec-grade-<?php echo strtolower( substr( $criterion['grade'], 0, 1 ) ); ?>"></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php


/*		$this->open_card_footer();
		$this->render_card_button( 'view-report', esc_html__( 'View Report', 'it-l10n-ithemes-security-pro' ) );
		$this->close_card_footer();*/

		$this->close_card();
	}

	private function render_card_header_text( $text ) {
		echo "<h2>$text</h2>\n";
	}

	private function render_card_subheader_text( $text, $class = '' ) {
		echo "<div class='itsec-card-subheading $class'>$text</div>\n";
	}

	private function render_card_header_button( $class, $text ) {
		echo "<button type='button' class='$class button-primary'>" . esc_html( $text ) . "</button>\n";
	}

	private function render_card_button( $class, $text ) {
		echo "<button type='button' class='itsec-$class button-secondary'>" . esc_html( $text ) . "</button>\n";
	}

	private function render_card_grade( $grade ) {
		$grade = strtoupper( $grade );

		if ( 1 == strlen( $grade ) ) {
			$letter = $grade;
			$modifier = '';
		} else {
			list( $letter, $modifier ) = str_split( $grade );
		}

		if ( ! in_array( $letter, array( 'A', 'B', 'C', 'D', 'F' ) ) ) {
			$letter = 'F';
		}
		if ( '+' === $modifier ) {
			$class = 'itsec-card-grade-modifier-plus';
			$display_modifier = '＋';
		} else if ( '-' === $modifier ) {
			$class = 'itsec-card-grade-modifier-minus';
			$display_modifier = '–';
		}

		echo "<span class='itsec-card-grade itsec-grade-" . strtolower( $letter ) . "'>$letter";

		if ( isset( $display_modifier ) ) {
			echo "<span class='itsec-card-grade-modifier $class'>$display_modifier</span>";
		}

		echo "</span>\n";
	}

	private function open_card_header() {
		echo "<div class='itsec-card-header'>\n";
	}

	private function close_card_header() {
		echo "</div>\n";
	}

	private function open_card_footer() {
		echo "<div class='itsec-card-footer'>\n";
	}

	private function close_card_footer() {
		echo "</div>\n";
	}

	private function open_card( $id, $class = '' ) {
		echo "<li class='itsec-card itsec-card-$id $class'>\n";
	}

	private function close_card() {
		echo "</li>\n";
	}
}

new ITSEC_Grade_Report_Page();
