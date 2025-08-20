<?php
/**
 * Plugin Security Check Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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

	// Check if WP-CLI is available
	if ( ! defined( 'WP_CLI' ) || ! class_exists( 'WP_CLI' ) ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'WP-CLI is not available. This functionality requires WP-CLI to execute Plugin Check commands.',
		);
	}

	// Check if Plugin Check plugin is available
	if ( ! is_plugin_active( 'plugin-check/plugin.php' ) && ! file_exists( WP_PLUGIN_DIR . '/plugin-check/plugin.php' ) ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Plugin Check plugin is not installed or not available.',
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
		// Execute Plugin Check command for security category in JSON format
		$command = sprintf(
			'plugin check %s --categories=security --format=json --fields=file,line,column,type,severity,message,source',
			escapeshellarg( $plugin_slug )
		);

		// Capture output using WP-CLI's internal runner
		ob_start();
		$exit_code = 0;

		try {
			WP_CLI::runcommand( $command, array(
				'return'     => 'all',
				'parse'      => 'json',
				'launch'     => false,
				'exit_error' => false,
			) );
		} catch ( Exception $e ) {
			$exit_code = 1;
			$output    = ob_get_clean();

			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Plugin Check command failed: ' . $e->getMessage(),
			);
		}

		$output = ob_get_clean();

		// Parse JSON output
		$check_results = json_decode( $output, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array(
				'success'     => false,
				'plugin_slug' => $plugin_slug,
				'error'       => 'Failed to parse Plugin Check output as JSON: ' . json_last_error_msg(),
			);
		}

		// Process results
		$security_findings   = array();
		$error_count         = 0;
		$warning_count       = 0;
		$total_files_checked = 0;

		if ( is_array( $check_results ) ) {
			foreach ( $check_results as $result ) {
				if ( isset( $result['type'] ) ) {
					if ( $result['type'] === 'ERROR' ) {
						$error_count ++;
					} elseif ( $result['type'] === 'WARNING' ) {
						$warning_count ++;
					}
				}
				$security_findings[] = $result;
			}
		}

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
				'total_issues'        => count( $security_findings ),
				'error_count'         => $error_count,
				'warning_count'       => $warning_count,
			),
		);

	} catch ( Exception $e ) {
		return array(
			'success'     => false,
			'plugin_slug' => $plugin_slug,
			'error'       => 'Unexpected error during security check: ' . $e->getMessage(),
		);
	}
}