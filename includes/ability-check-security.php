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
	 * Constructor.
	 *
	 * @param string $plugin_slug Plugin slug to check.
	 */
	public function __construct( $plugin_slug ) {
		parent::__construct();
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
		return array( Check_Categories::CATEGORY_SECURITY );
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
								'severity' => array( 'type' => 'string' ),
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
	// Validate input
	if ( empty( $input['plugin_slug'] ) ) {
		return array(
			'success' => false,
			'error'   => 'Plugin slug is required.',
		);
	}

	$plugin_slug = sanitize_text_field( $input['plugin_slug'] );

	// Check if Plugin Check plugin is available and active
	if ( ! is_plugin_active( 'plugin-check/plugin.php' ) ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin Check plugin is not installed or not active. Please install and activate the Plugin Check plugin.',
		);
	}

	// Check if Plugin Check classes are available
	if ( ! class_exists( 'WordPress\\Plugin_Check\\Checker\\Abstract_Check_Runner' ) ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin Check classes are not available. Please ensure Plugin Check plugin is properly loaded.',
		);
	}

	// Verify the target plugin exists
	if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) && ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug . '.php' ) ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin "' . $plugin_slug . '" not found in plugins directory.',
		);
	}

	try {
		// Define the security check runner class if needed
		if ( ! ai_experiments_define_security_check_runner() ) {
			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Plugin Check classes are not available. Please ensure Plugin Check plugin is properly loaded.',
			);
		}

		// Create security check runner
		$runner = new AI_Experiments_Security_Check_Runner( $plugin_slug );
		
		// Set up the runner
		$runner->set_plugin( $plugin_slug );
		$runner->set_slug( $plugin_slug );
		$runner->set_categories( array( WordPress\Plugin_Check\Checker\Check_Categories::CATEGORY_SECURITY ) );
		$runner->set_experimental_flag( false );
		$runner->set_check_slugs( array() ); // Run all available checks
		$runner->set_check_exclude_slugs( array() ); // No exclusions

		// Prepare environment
		$cleanup = $runner->prepare();

		// Run security checks
		$check_result = $runner->run();

		// Clean up
		if ( is_callable( $cleanup ) ) {
			$cleanup();
		}

		// Transform Plugin Check results to our expected format
		$security_findings = ai_experiments_transform_check_results( $check_result );

		// Calculate summary
		$error_count = $check_result->get_error_count();
		$warning_count = $check_result->get_warning_count();
		$total_issues = $error_count + $warning_count;

		// Count unique files checked
		$files_checked = array();
		foreach ( $security_findings as $finding ) {
			if ( ! empty( $finding['file'] ) ) {
				$files_checked[ $finding['file'] ] = true;
			}
		}
		$total_files_checked = count( $files_checked );

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