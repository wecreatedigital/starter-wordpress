<?php

final class ITSEC_File_Change_Settings_Page extends ITSEC_Module_Settings_Page {
	private $script_version = 4;


	public function __construct() {
		$this->id = 'file-change';
		$this->title = __( 'File Change Detection', 'better-wp-security' );
		$this->description = __( 'Monitor the site for unexpected file changes.', 'better-wp-security' );
		$this->type = 'recommended';

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {

		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$vars = array(
			'ABSPATH' => get_home_path(),
			'nonce'   => wp_create_nonce( 'itsec_do_file_check' ),
		);

		if ( ! class_exists( 'ITSEC_File_Change_Admin' ) ) {
			require_once( dirname( __FILE__ ) . '/admin.php' );
		}

		ITSEC_Lib::enqueue_util();
		ITSEC_File_Change_Admin::enqueue_scanner();
		wp_enqueue_script( 'itsec-file-change-settings-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'itsec-file-change-scanner', 'itsec-util' ), $this->script_version, true );
		wp_localize_script( 'itsec-file-change-settings-script', 'itsec_file_change_settings', $vars );


		$vars = array(
			'nonce' => wp_create_nonce( 'itsec_jquery_filetree' ),
		);

		wp_enqueue_script( 'itsec-file-change-admin-filetree-script', plugins_url( 'js/filetree/jqueryFileTree.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'itsec-file-change-admin-filetree-script', 'itsec_jquery_filetree', $vars );


		wp_enqueue_style( 'itsec-file-change-admin-filetree-style', plugins_url( 'js/filetree/jqueryFileTree.css', __FILE__ ), array(), $this->script_version );
		wp_enqueue_style( 'itsec-file-change-admin-style', plugins_url( 'css/settings.css', __FILE__ ), array(), $this->script_version );
	}

	public function handle_ajax_request( $data ) {
		if ( 'one-time-scan' === $data['method'] ) {
			require_once( dirname( __FILE__ ) . '/scanner.php' );

			$results = ITSEC_File_Change_Scanner::schedule_start();

			if ( is_wp_error( $results ) ) {
				ITSEC_Response::add_error( $results );
			} else {
				ITSEC_Response::set_success( true );
			}
		} elseif ( 'abort' === $data['method'] ) {
			require_once( dirname( __FILE__ ) . '/scanner.php' );
			ITSEC_File_Change_Scanner::abort( true );

			ITSEC_Response::set_success( true );
		} else if ( 'get-filetree-data' === $data['method'] ) {
			ITSEC_Response::set_response( $this->get_filetree_data( $data ) );
		}
	}

	protected function render_description( $form ) {

?>
	<p><?php _e( 'Even the best security solutions can fail. How do you know if someone gets into your site? You will know because they will change something. File Change detection will tell you what files have changed in your WordPress installation alerting you to changes not made by yourself. Unlike other solutions, this plugin will look only at your installation and compare files to the last check instead of comparing them with a remote installation thereby taking into account whether or not you modify the files yourself.', 'better-wp-security' ); ?></p>
<?php

	}

	protected function render_settings( $form ) {

		$file_list = $form->get_option( 'file_list' );

		if ( is_array( $file_list ) ) {
			$file_list = implode( "\n", $file_list );
		} else {
			$file_list = '';
		}

		$form->set_option( 'file_list', $file_list );

		require_once( dirname( __FILE__ ) . '/scanner.php' );

		if ( $is_running = ITSEC_File_Change_Scanner::is_running() ) {
			$status = ITSEC_File_Change_Scanner::get_status();

			$button = array(
				'value'    => empty( $status['message'] ) ? __( 'Scan in Progress', 'better-wp-security' ) : $status['message'],
				'disabled' => 'disabled',
				'class'    => 'button-secondary',
			);
		} else {
			$button = array(
				'value' => __( 'Scan Files Now', 'better-wp-security' ),
				'class' => 'button-primary',
			);
		}
?>
	<div class="hide-if-no-js">
		<p><?php _e( "Press the button below to scan your site's files for changes. Note that if changes are found this will take you to the logs page for details.", 'better-wp-security' ); ?></p>
		<p>
			<?php $form->add_button( 'one_time_check', $button ); ?>
			<?php if ( $is_running ) : ?>
				<?php $form->add_button( 'abort', array( 'value' => _x( 'Cancel', 'Cancel File Change scan.', 'better-wp-security' ), 'class' => 'button' ) ); ?>
			<?php endif; ?>
		</p>
		<div id="itsec_file_change_status"></div>
	</div>

	<table class="form-table itsec-settings-section">
		<tr>
			<th scope="row"><?php _e( 'Files and Folders List', 'better-wp-security' ); ?></th>
			<td>
				<p class="description"><?php _e( 'Exclude files or folders by clicking the red minus next to the file or folder name.', 'better-wp-security' ); ?></p>
				<div class="file_list">
					<div class="file_chooser"><div class="jquery_file_tree"></div></div>
					<div class="list_field"><?php $form->add_textarea( 'file_list', array( 'wrap' => 'off' ) ); ?></div>
				</div>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-file-change-types"><?php _e( 'Ignore File Types', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_textarea( 'types', array( 'wrap' => 'off', 'cols' => 20, 'rows' => 10 ) ); ?>
				<br />
				<label for="itsec-file-change-types"><?php _e( 'File types listed here will not be checked for changes. While it is possible to change files such as images it is quite rare and nearly all known WordPress attacks exploit php, js and other text files.', 'better-wp-security' ); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-file-change-notify_admin"><?php _e( 'Display File Change Admin Warning', 'better-wp-security' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'notify_admin' ); ?>
				<label for="itsec-file-change-notify_admin"><?php _e( 'Display file change admin warning', 'better-wp-security' ); ?></label>
				<p class="description"><?php _e( 'Disabling this feature will prevent the file change warning from displaying to the site administrator in the WordPress Dashboard. Note that disabling both the error message and the email notification will result in no notifications of file changes. The only way you will be able to tell is by manually checking the log files.', 'better-wp-security' ); ?></p>
			</td>
		</tr>
		<?php do_action( 'itsec-file-change-settings-form', $form ); ?>
	</table>
<?php

	}

	/**
	 * Gets file list for tree.
	 *
	 * Processes the ajax request for retreiving the list of files and folders that can later either
	 * excluded or included.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_filetree_data( $data ) {

		$directory = sanitize_text_field( $data['dir'] );
		$directory = urldecode( $directory );
		$directory = realpath( $directory );

		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$base_directory = get_home_path();

		// Ensure that requests cannot traverse arbitrary directories.
		if ( 0 !== strpos( $directory, $base_directory ) ) {
			$directory = $base_directory;
		}

		$directory .= '/';

		ob_start();

		if ( file_exists( $directory ) ) {

			$files = scandir( $directory );

			natcasesort( $files );

			if ( 2 < count( $files ) ) { /* The 2 accounts for . and .. */

				echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

				//two loops keep directories sorted before files

				// All files and directories (alphabetical sorting)
				foreach ( $files as $file ) {

					if ( '.' === $file || '..' === $file ) {
						continue;
					}

					if ( ! file_exists( $directory . $file ) ) {
						continue;
					}

					if ( is_dir( $directory . $file ) ) {

						echo '<li class="directory collapsed"><a href="#" rel="' . htmlentities( $directory . $file ) . '/">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . plugins_url( 'images/redminus.png', __FILE__ ) . '" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';

					} else {
						$ext = pathinfo( $file, PATHINFO_EXTENSION );
						echo '<li class="file ext_' . $ext . '"><a href="#" rel="' . htmlentities( $directory . $file ) . '">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . plugins_url( 'images/redminus.png', __FILE__ ) . '" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';

					}

				}

				echo "</ul>";

			}

		}

		return ob_get_clean();

	}
}

new ITSEC_File_Change_Settings_Page();
