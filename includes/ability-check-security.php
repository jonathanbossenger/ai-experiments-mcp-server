<?php
/**
 * Plugin Security Check Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define the custom Security Check Runner class if not already defined.
 * This ensures Plugin Check classes are available before extending them.
 */
function ai_experiments_define_security_check_runner() {
	if ( class_exists( 'AI_Experiments_Security_Check_Runner' ) ) {
		return true;
	}

	// Check if required Plugin Check classes are available
	if ( ! class_exists( 'WordPress\\Plugin_Check\\Checker\\Abstract_Check_Runner' ) ||
		 ! class_exists( 'WordPress\\Plugin_Check\\Checker\\Check_Categories' ) ) {
		return false;
	}

	/**
	 * Custom Security Check Runner for Plugin Check integration.
	 *
	 * Extends Plugin Check's Abstract_Check_Runner to run security-only checks.
	 */
	class AI_Experiments_Security_Check_Runner extends WordPress\Plugin_Check\Checker\Abstract_Check_Runner {

	/**
	 * Plugin slug to check.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Set the plugin slug to check.
	 *
	 * @param string $plugin_slug Plugin slug to check.
	 */
	public function set_plugin_slug( $plugin_slug ) {
		
		$this->plugin_slug = $plugin_slug;
	}

	/**
	 * Returns the plugin parameter based on the request.
	 *
	 * @return string The plugin parameter from the request.
	 */
	protected function get_plugin_param() {
		return $this->plugin_slug;
	}

	/**
	 * Returns an array of Check slugs to run based on the request.
	 * Returns empty array to run all available checks.
	 *
	 * @return array An array of Check slugs.
	 */
	protected function get_check_slugs_param() {
		return array();
	}

	/**
	 * Returns an array of Check slugs to exclude based on the request.
	 *
	 * @return array An array of Check slugs.
	 */
	protected function get_check_exclude_slugs_param() {
		return array();
	}

	/**
	 * Returns the include experimental parameter based on the request.
	 *
	 * @return bool Returns false to exclude experimental checks.
	 */
	protected function get_include_experimental_param() {
		return false;
	}

	/**
	 * Returns an array of categories for filtering the checks.
	 * Returns security category only.
	 *
	 * @return array An array of categories.
	 */
	protected function get_categories_param() {
		return array( WordPress\Plugin_Check\Checker\Check_Categories::CATEGORY_SECURITY );
	}

	/**
	 * Returns plugin slug parameter.
	 *
	 * @return string Plugin slug.
	 */
	protected function get_slug_param() {
		return $this->plugin_slug;
	}

		/**
		 * Determines if the current request is intended for the plugin checker.
		 *
		 * @return boolean Returns true since we're always checking plugins.
		 */
		public static function is_plugin_check() {
			return true;
		}
	}

	return true;
}

// Register the Plugin Security Check ability
add_action( 'abilities_api_init', function () {
	wp_register_ability(
		'plugin-security/check-security',
		array(
			'label'               => __( 'Plugin Security Check', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Analyzes WordPress plugins for security vulnerabilities and issues using Plugin Check security category checks.', 'ai-experiments-mcp-server' ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'plugin_slug' => array(
						'type'        => 'string',
						'description' => __( 'The plugin slug/name to check (e.g., "akismet", "hello-dolly").', 'ai-experiments-mcp-server' ),
					),
				),
				'required'   => array( 'plugin_slug' ),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success'           => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the security check completed successfully.', 'ai-experiments-mcp-server' ),
					),
					'plugin_slug'       => array(
						'type'        => 'string',
						'description' => __( 'The plugin that was checked.', 'ai-experiments-mcp-server' ),
					),
					'security_findings' => array(
						'type'        => 'array',
						'description' => __( 'Security issues found in the plugin.', 'ai-experiments-mcp-server' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'file'     => array( 'type' => 'string' ),
								'line'     => array( 'type' => 'integer' ),
								'column'   => array( 'type' => 'integer' ),
								'type'     => array( 'type' => 'string' ),
								'severity' => array( 'type' => 'integer' ),
								'message'  => array( 'type' => 'string' ),
								'source'   => array( 'type' => 'string' ),
							),
						),
					),
					'summary'           => array(
						'type'        => 'object',
						'description' => __( 'Summary of security check results.', 'ai-experiments-mcp-server' ),
						'properties'  => array(
							'total_files_checked' => array( 'type' => 'integer' ),
							'total_issues'        => array( 'type' => 'integer' ),
							'error_count'         => array( 'type' => 'integer' ),
							'warning_count'       => array( 'type' => 'integer' ),
						),
					),
					'error'             => array(
						'type'        => 'string',
						'description' => __( 'Error message if the check failed.', 'ai-experiments-mcp-server' ),
					),
				),
			),
			'execute_callback'    => 'ai_experiments_plugin_security_check',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		)
	);
} );

/**
 * Execute plugin security check using Plugin Check's security category.
 *
 * @param array $input Input parameters containing plugin_slug.
 *
 * @return array JSON response with security check results or error.
 */
function ai_experiments_plugin_security_check( $input ) {
	if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_SECURITY: Starting security check with input: ' . print_r( $input, true) );
	}
	
	// Validate input
	if ( empty( $input['plugin_slug'] ) ) {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Plugin slug is empty or missing' );
		}
		return array(
			'success' => false,
			'error'   => 'Plugin slug is required.',
		);
	}

	$plugin_slug = sanitize_text_field( $input['plugin_slug'] );
	if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_SECURITY: Sanitized plugin slug: ' . $plugin_slug );
	}

	// Check if Plugin Check plugin is available and active
	$plugin_check_active = is_plugin_active( 'plugin-check/plugin.php' );
	if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_SECURITY: Plugin Check active status: ' . ( $plugin_check_active ? 'true' : 'false' ) );
	}
	
	if ( ! $plugin_check_active ) {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Plugin Check plugin is not active' );
		}
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin Check plugin is not installed or not active. Please install and activate the Plugin Check plugin.',
		);
	}

	// Check if Plugin Check classes are available
	$abstract_runner_exists = class_exists( 'WordPress\\Plugin_Check\\Checker\\Abstract_Check_Runner' );
	if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_SECURITY: Abstract_Check_Runner class exists: ' . ( $abstract_runner_exists ? 'true' : 'false' ) );
	}
	
	if ( ! $abstract_runner_exists ) {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Plugin Check classes are not available' );
		}
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin Check classes are not available. Please ensure Plugin Check plugin is properly loaded.',
		);
	}

	// Verify the target plugin exists
	$plugin_dir_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
	$plugin_file_path = WP_PLUGIN_DIR . '/' . $plugin_slug . '.php';
	$plugin_dir_exists = file_exists( $plugin_dir_path );
	$plugin_file_exists = file_exists( $plugin_file_path );
	
	if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_SECURITY: Checking plugin existence:' );
		error_log( 'AI_EXPERIMENTS_SECURITY: - Plugin directory path: ' . $plugin_dir_path );
		error_log( 'AI_EXPERIMENTS_SECURITY: - Plugin directory exists: ' . ( $plugin_dir_exists ? 'true' : 'false' ) );
		error_log( 'AI_EXPERIMENTS_SECURITY: - Plugin file path: ' . $plugin_file_path );
		error_log( 'AI_EXPERIMENTS_SECURITY: - Plugin file exists: ' . ( $plugin_file_exists ? 'true' : 'false' ) );
	}
	
	if ( ! $plugin_dir_exists && ! $plugin_file_exists ) {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Plugin not found in either location' );
		}
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin "' . $plugin_slug . '" not found in plugins directory.',
		);
	}

	try {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Entering try block for security check execution' );
		}
		
		// Define the security check runner class if needed
		$runner_defined = ai_experiments_define_security_check_runner();
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Security check runner definition result: ' . ( $runner_defined ? 'true' : 'false' ) );
		}
		
		if ( ! $runner_defined ) {
			if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_SECURITY: Failed to define security check runner' );
			}
			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Plugin Check classes are not available. Please ensure Plugin Check plugin is properly loaded.',
			);
		}

		// Create security check runner
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Creating AI_Experiments_Security_Check_Runner instance' );
		}
		$runner = new AI_Experiments_Security_Check_Runner();
		$runner->set_plugin_slug( $plugin_slug );
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Runner created and plugin slug set to: ' . $plugin_slug );
		}
		
		// Prepare environment
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Calling runner->prepare()' );
		}
		$cleanup = $runner->prepare();
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Prepare() result type: ' . gettype( $cleanup ) );
		}
		
		// Check if prepare() returned an error
		if ( is_wp_error( $cleanup ) ) {
			if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_SECURITY: prepare() returned WP_Error: ' . $cleanup->get_error_message() );
			}
			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Failed to prepare security check: ' . $cleanup->get_error_message(),
			);
		}

		// Run security checks
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Calling runner->run()' );
		}
		$check_result = $runner->run();
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: run() completed, result type: ' . gettype( $check_result ) );
		}
		
		// Check if run() returned an error
		if ( is_wp_error( $check_result ) ) {
			if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_SECURITY: run() returned WP_Error: ' . $check_result->get_error_message() );
			}
			// Clean up if possible
			if ( is_callable( $cleanup ) ) {
				$cleanup();
				if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
					error_log( 'AI_EXPERIMENTS_SECURITY: Cleanup function called after run() error' );
				}
			}
			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Security check failed: ' . $check_result->get_error_message(),
			);
		}

		// Clean up
		if ( is_callable( $cleanup ) ) {
			$cleanup();
			if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_SECURITY: Cleanup function called successfully' );
			}
		}

		// Transform Plugin Check results to our expected format
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Starting transformation of check results' );
		}
		$security_findings = ai_experiments_transform_check_results( $check_result );
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Transformation complete, findings count: ' . count( $security_findings ) );
			error_log( print_r( $security_findings, true ) );
		}

		// Calculate summary
		$error_count = $check_result->get_error_count();
		$warning_count = $check_result->get_warning_count();
		$total_issues = $error_count + $warning_count;
		
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Summary calculations:' );
			error_log( 'AI_EXPERIMENTS_SECURITY: - Error count: ' . $error_count );
			error_log( 'AI_EXPERIMENTS_SECURITY: - Warning count: ' . $warning_count );
			error_log( 'AI_EXPERIMENTS_SECURITY: - Total issues: ' . $total_issues );
		}

		// Count unique files checked
		$files_checked = array();
		foreach ( $security_findings as $finding ) {
			if ( ! empty( $finding['file'] ) ) {
				$files_checked[ $finding['file'] ] = true;
			}
		}
		$total_files_checked = count( $files_checked );
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Total files checked: ' . $total_files_checked );
			error_log( 'AI_EXPERIMENTS_SECURITY: Security check completed successfully' );
		}
		return array(
			'success'           => true,
			'plugin_slug'       => $plugin_slug,
			'security_findings' => $security_findings,
			'summary'           => array(
				'total_files_checked' => $total_files_checked,
				'total_issues'        => $total_issues,
				'error_count'         => $error_count,
				'warning_count'       => $warning_count,
			),
		);

	} catch ( Exception $e ) {
		if ( defined( 'AI_EXPERIMENTS_DEBUG' ) && AI_EXPERIMENTS_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_SECURITY: Exception caught: ' . $e->getMessage() );
			error_log( 'AI_EXPERIMENTS_SECURITY: Exception trace: ' . $e->getTraceAsString() );
		}
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Security check failed: ' . $e->getMessage(),
		);
	}
}

/**
 * Transform Plugin Check results to our expected JSON format.
 *
 * @param WordPress\Plugin_Check\Checker\Check_Result $check_result Plugin Check result object.
 *
 * @return array Transformed security findings array.
 */
function ai_experiments_transform_check_results( $check_result ) {
	$security_findings = array();

	// Process errors
	$errors = $check_result->get_errors();
	foreach ( $errors as $file => $lines ) {
		foreach ( $lines as $line_num => $columns ) {
			foreach ( $columns as $column_num => $messages ) {
				foreach ( $messages as $message_data ) {
					$security_findings[] = array(
						'file'     => $file,
						'line'     => intval( $line_num ),
						'column'   => intval( $column_num ),
						'type'     => 'ERROR',
						'severity' => isset( $message_data['severity'] ) ? intval( $message_data['severity'] ) : 5,
						'message'  => isset( $message_data['message'] ) ? $message_data['message'] : '',
						'source'   => isset( $message_data['code'] ) ? $message_data['code'] : '',
					);
				}
			}
		}
	}

	// Process warnings
	$warnings = $check_result->get_warnings();
	foreach ( $warnings as $file => $lines ) {
		foreach ( $lines as $line_num => $columns ) {
			foreach ( $columns as $column_num => $messages ) {
				foreach ( $messages as $message_data ) {
					$security_findings[] = array(
						'file'     => $file,
						'line'     => intval( $line_num ),
						'column'   => intval( $column_num ),
						'type'     => 'WARNING',
						'severity' => isset( $message_data['severity'] ) ? intval( $message_data['severity'] ) : 5,
						'message'  => isset( $message_data['message'] ) ? $message_data['message'] : '',
						'source'   => isset( $message_data['code'] ) ? $message_data['code'] : '',
					);
				}
			}
		}
	}

	return $security_findings;
}