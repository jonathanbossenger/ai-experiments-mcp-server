<?php
/**
 * MCP Server Demo Category Registration
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the mcp-server-demo category
add_action( 'abilities_api_categories_init', function () {
	wp_register_ability_category( 'mcp-server-demo', array(
		'label'       => __( 'MCP Server Demo', 'mcp-server' ),
		'description' => __( 'Demo abilities for the WordPress MCP Server implementation using the Abilities API and MCP Adapter.', 'mcp-server' ),
	) );
} );
