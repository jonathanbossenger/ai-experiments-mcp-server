<?php
/**
 * Plugin Name: WP MCP Server Demo
 * Description: Implements a basic MCP server using Abilities API and the MCP Adapter.
 * Version: 1.0.0
 * Requires Plugins: plugin-check
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	wp_die( 'The Composer autoloader is not present, please run "composer install" from the plugin directory.' );
}

// Define debug constant for conditional logging
if ( ! defined( 'WP_MCP_SERVER_DEBUG' ) ) {
	define( 'WP_MCP_SERVER_DEBUG', false );
}

// Include ability files
require_once __DIR__ . '/includes/ability-site-info.php';
require_once __DIR__ . '/includes/ability-get-plugins.php';
require_once __DIR__ . '/includes/ability-debug-log.php';
require_once __DIR__ . '/includes/ability-create-post.php';
require_once __DIR__ . '/includes/ability-check-security.php';

// Get the adapter instance
$adapter = WP\MCP\Core\McpAdapter::instance();
/*
 * Hook into the MCP adapter initialization to create a custom MCP server.
 * mcp_adapter_init accepts a single parameter: the MCP adapter instance.
 */
add_action( 'mcp_adapter_init', function ( $adapter ) {
	// MCP Server configuration
	$adapter->create_server(
		'wp-mcp-server',                       // Unique server identifier
		'wp-mcp-server',                       // REST API namespace
		'mcp',                                  // REST API route
		'AI Experiments MCP Server',            // Server name
		'Custom AI Experiments MCP Server',     // Server description
		'v1.0.0',                               // Server version
		array(                                  // Transport methods
            \WP\MCP\Transport\Http\RestTransport::class,
		),
        \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,         // Error handler
        \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,     // Observability handler
		// Abilities to expose as tools
		array(
            'site/site-info',
            'plugins/get-plugins',
            'post/create-post',
            'plugin-check/check-security',
            'debug/read-log',
            'debug/clear-log'
		),
        // Abilities to expose as resources
        array(
            'site/site-info',
        )
	);
} );