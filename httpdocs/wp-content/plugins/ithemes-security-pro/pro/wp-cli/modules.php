<?php

/**
 * Enable and disable iThemes Security modules.
 */
class ITSEC_Modules_Command extends WP_CLI_Command {

	/**
	 * List all available modules.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$modules   = ITSEC_Modules::get_available_modules();
		$formatted = array();

		foreach ( $modules as $module ) {
			$formatted[] = array(
				'id'     => $module,
				'status' => $this->get_module_status( $module ),
			);
		}

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$fields = wp_parse_slug_list( \WP_CLI\Utils\get_flag_value( $assoc_args, 'fields', array( 'id', 'status' ) ) );

		\WP_CLI\Utils\format_items( $format, $formatted, $fields );
	}

	/**
	 * Get details about a module.
	 *
	 * <module>
	 * : The module id.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 */
	public function get( $args, $assoc_args ) {
		$this->assert_module_available( $args[0] );

		$formatted = array(
			'id'     => $args[0],
			'status' => $this->get_module_status( $args[0] ),
		);

		$fields = wp_parse_slug_list( \WP_CLI\Utils\get_flag_value( $assoc_args, 'fields', array( 'id', 'status' ) ) );

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_item( $formatted );
	}

	/**
	 * Activate a module.
	 *
	 * <module>
	 * : The module id.
	 *
	 * @alias enable
	 */
	public function activate( $args, $assoc_args ) {
		$this->assert_module_available( $args[0] );

		if ( ITSEC_Modules::is_always_active( $args[0] ) ) {
			WP_CLI::warning( 'Module is always active and cannot have its status changed.' );

			return;
		}

		if ( ITSEC_Modules::is_active( $args[0] ) ) {
			WP_CLI::warning( 'Module is already active.' );

			return;
		}

		ITSEC_Modules::activate( $args[0] );

		WP_CLI::success( 'Module activated.' );
	}

	/**
	 * Deactivate a module.
	 *
	 * <module>
	 * : The module id.
	 *
	 * @alias disable
	 */
	public function deactivate( $args, $assoc_args ) {
		$this->assert_module_available( $args[0] );

		if ( ITSEC_Modules::is_always_active( $args[0] ) ) {
			WP_CLI::warning( 'Module is always active and cannot have its status changed.' );

			return;
		}

		if ( ! ITSEC_Modules::is_active( $args[0] ) ) {
			WP_CLI::warning( 'Module is already deactivated.' );

			return;
		}

		ITSEC_Modules::deactivate( $args[0] );

		WP_CLI::success( 'Module deactivated.' );
	}

	private function assert_module_available( $id ) {
		if ( ! \in_array( $id, ITSEC_Modules::get_available_modules(), true ) ) {
			WP_CLI::error( 'Invalid module id.' );
		}
	}

	private function get_module_status( $module ) {
		if ( ITSEC_Modules::is_always_active( $module ) ) {
			return 'Always Active';
		}

		if ( ITSEC_Modules::is_active( $module ) ) {
			return 'Active';
		}

		return 'Inactive';
	}

}

WP_CLI::add_command( 'itsec modules', 'ITSEC_Modules_Command' );