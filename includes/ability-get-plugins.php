<?php
/**
 * Plugin List Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the Plugin List ability
add_action( 'abilities_api_init', function () {
	wp_register_ability(
		'plugins/get-plugins',
		array(
			'label'               => __( 'Plugin List', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Retrieves a list of all installed WordPress plugins with their names and slugs.', 'ai-experiments-mcp-server' ),
			'category'            => 'mcp-server-demo',
			'input_schema'        => array(),
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

/**
 * Retrieve list of all installed WordPress plugins.
 *
 * @param array $input Input parameters (unused).
 *
 * @return array JSON response with plugin list or error.
 */
function ai_experiments_get_plugin_list( $input ) {
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Starting plugin list retrieval with input: ' . print_r( $input, true ) );
	}

	try {
		// Check if get_plugins function is available
		$get_plugins_exists = function_exists( 'get_plugins' );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: get_plugins function exists: ' . ( $get_plugins_exists ? 'true' : 'false' ) );
		}

		if ( ! $get_plugins_exists ) {
			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Loading plugin.php from wp-admin/includes' );
			}
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get all installed plugins
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Calling get_plugins()' );
		}
		$all_plugins = get_plugins();
		$plugin_count = count( $all_plugins );
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Found ' . $plugin_count . ' total plugins' );
		}

		$active_plugins = get_option( 'active_plugins', array() );
		$active_count = count( $active_plugins );
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Found ' . $active_count . ' active plugins' );
		}

		$network_active = array();
		$is_multisite = is_multisite();

		// Get network active plugins if multisite
		if ( $is_multisite ) {
			$network_active = get_site_option( 'active_sitewide_plugins', array() );
			$network_count = count( $network_active );
			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Multisite detected, found ' . $network_count . ' network active plugins' );
			}
		} else {
			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Single site installation' );
			}
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

			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Processed plugin: ' . $plugin_data['Name'] . ' (' . $plugin_slug . ') - ' . $status );
			}
		}

		$final_count = count( $plugins );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Plugin list retrieval completed successfully with ' . $final_count . ' plugins' );
		}

		return array(
			'success' => true,
			'plugins' => $plugins,
		);

	} catch ( Exception $e ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Exception caught: ' . $e->getMessage() );
			error_log( 'AI_EXPERIMENTS_PLUGIN_LIST: Exception trace: ' . $e->getTraceAsString() );
		}
		return array(
			'success' => false,
			'error'   => 'Failed to retrieve plugin list: ' . $e->getMessage(),
		);
	}
}