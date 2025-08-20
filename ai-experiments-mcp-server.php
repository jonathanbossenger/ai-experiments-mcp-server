<?php
/**
 * Plugin Name: AI Experiments MCP Server
 * Description: A server for AI experiments using the MCP (Model Context Protocol) architecture.
 * Version: 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/vendor/autoload.php'; // Ensure you have the autoloader for dependencies

use WP\MCP\Core\McpAdapter;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\Http\RestTransport;

// Register an ability to create a post
add_action( 'abilities_api_init', function () {
	wp_register_ability( 'mcp-server/create-post', array(
		'label'               => __( 'Create Post', 'mcp-server' ),
		'description'         => __( 'Creates a new blog post with the provided content', 'mcp-server' ),
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'title'   => array(
					'type'        => 'string',
					'description' => __( 'The title of the post', 'mcp-server' )
				),
				'content' => array(
					'type'        => 'string',
					'description' => __( 'The content of the post. Must be valid block editor markup', 'mcp-server' )
				),
				'status'  => array(
					'type'        => 'string',
					'description' => __( 'The status of the post', 'mcp-server' ),
					'default'     => 'draft',
					'enum'        => array( 'draft', 'publish' )
				)
			),
			'required'   => array( 'title', 'content' )
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'url' => array(
					'type'        => 'string',
					'description' => __( 'The URL of the created post', 'mcp-server' )
				)
			)
		),
		'execute_callback'    => 'mcp_server_create_post',
		'permission_callback' => function ( $input ) {
			return current_user_can( 'publish_posts' );
		}
	) );
} );

/**
 * Callback function to create a post.
 *
 * @param array $input Input data for the post.
 *
 * @return array Result containing post ID and URL.
 */
function mcp_server_create_post( $input ) {
	// Validate input
	if ( ! isset( $input['title'], $input['content'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Invalid input data', 'mcp-server' ), array( 'status' => 400 ) );
	}
	// Create the post
	$post_data = array(
		'post_title'   => sanitize_text_field( $input['title'] ),
		'post_content' => wp_kses_post( $input['content'] ),
		'post_status'  => ( isset( $input['status'] ) && in_array( sanitize_text_field( $input['status'] ), array(
				'draft',
				'publish'
			), true ) )
			? sanitize_text_field( $input['status'] )
			: 'draft',
		'post_author'  => get_current_user_id(),
	);
	$post_id   = wp_insert_post( $post_data );
	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'post_creation_failed', __( 'Failed to create post', 'mcp-server' ), array( 'status' => 500 ) );
	}

	// Return the result
	return array(
		'url' => get_permalink( $post_id ),
	);
}

// Get the adapter instance
$adapter = McpAdapter::instance();

// Register the Plugin List ability
add_action( 'abilities_api_init', function () {
	wp_register_ability(
		'plugin-list/get-plugins',
		array(
			'label'               => __( 'Plugin List', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Retrieves a list of all installed WordPress plugins with their names and slugs.', 'ai-experiments-mcp-server' ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the plugin list retrieval completed successfully.', 'ai-experiments-mcp-server' ),
					),
					'plugins' => array(
						'type'        => 'array',
						'description' => __( 'List of installed plugins.', 'ai-experiments-mcp-server' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'name'    => array(
									'type'        => 'string',
									'description' => __( 'Plugin name.', 'ai-experiments-mcp-server' )
								),
								'slug'    => array(
									'type'        => 'string',
									'description' => __( 'Plugin slug/directory.', 'ai-experiments-mcp-server' )
								),
								'file'    => array(
									'type'        => 'string',
									'description' => __( 'Main plugin file path.', 'ai-experiments-mcp-server' )
								),
								'status'  => array(
									'type'        => 'string',
									'description' => __( 'Plugin status (active/inactive).', 'ai-experiments-mcp-server' )
								),
								'version' => array(
									'type'        => 'string',
									'description' => __( 'Plugin version.', 'ai-experiments-mcp-server' )
								),
							),
						),
					),
					'error'   => array(
						'type'        => 'string',
						'description' => __( 'Error message if the retrieval failed.', 'ai-experiments-mcp-server' ),
					),
				),
			),
			'execute_callback'    => 'ai_experiments_get_plugin_list',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		)
	);
} );

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
 * Retrieve list of all installed WordPress plugins.
 *
 * @param array $input Input parameters (unused).
 *
 * @return array JSON response with plugin list or error.
 */
function ai_experiments_get_plugin_list( $input ) {
	try {
		// Check if get_plugins function is available
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins
		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		$network_active = array();

		// Get network active plugins if multisite
		if ( is_multisite() ) {
			$network_active = get_site_option( 'active_sitewide_plugins', array() );
		}

		$plugins = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			// Extract slug from plugin file path
			$plugin_slug = dirname( $plugin_file );
			if ( $plugin_slug === '.' ) {
				// Single file plugin
				$plugin_slug = basename( $plugin_file, '.php' );
			}

			// Determine status
			$status = 'inactive';
			if ( in_array( $plugin_file, $active_plugins ) || array_key_exists( $plugin_file, $network_active ) ) {
				$status = 'active';
			}

			$plugins[] = array(
				'name'    => $plugin_data['Name'],
				'slug'    => $plugin_slug,
				'file'    => $plugin_file,
				'status'  => $status,
				'version' => $plugin_data['Version'],
			);
		}

		return array(
			'success' => true,
			'plugins' => $plugins,
		);

	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'error'   => 'Failed to retrieve plugin list: ' . $e->getMessage(),
		);
	}
}

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

// Hook into the initialization
add_action( 'mcp_adapter_init', function ( $adapter ) {
	// MCP Server configuration
	$adapter->create_server(
		'ai-experiments',                       // Unique server identifier
		'ai-experiments',                       // REST API namespace
		'mcp',                                  // REST API route
		'AI Experiments MCP Server',            // Server name
		'Custom AI Experiments MCP Server',     // Server description
		'v1.0.0',                               // Server version
		array(                                  // Transport methods
			RestTransport::class,
		),
		ErrorLogMcpErrorHandler::class,         // Error handler
		NullMcpObservabilityHandler::class,     // Observability handler
		// Abilities to expose as tools
		array(
			'plugin-list/get-plugins',
			'plugin-security/check-security',
			'mcp-server/create-post'
		),
	);
} );