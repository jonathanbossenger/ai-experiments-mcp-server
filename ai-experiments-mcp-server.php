<?php
/**
 * Plugin Name: AI Experiments MCP Server
 * Description: A server for AI experiments using the MCP (Model Context Protocol) architecture.
 * Version: 0.0.1
 * Requires Plugins: plugin-check
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define debug constant for conditional logging
if ( ! defined( 'AI_EXPERIMENTS_DEBUG' ) ) {
	define( 'AI_EXPERIMENTS_DEBUG', false );
}

// Include ability files
require_once __DIR__ . '/includes/ability-create-post.php';
require_once __DIR__ . '/includes/ability-get-plugins.php';
require_once __DIR__ . '/includes/ability-check-security.php';
require_once __DIR__ . '/includes/ability-debug-log.php';
require_once __DIR__ . '/includes/ability-site-info.php';

/*
 * Hook into the MCP adapter initialization to create a custom MCP server.
 * mcp_adapter_init accepts a single parameter: the MCP adapter instance.
 */
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
            \WP\MCP\Transport\Http\RestTransport::class,
		),
        \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,         // Error handler
        \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,     // Observability handler
		// Abilities to expose as tools
		array(
			'site/site-info',
			'plugin-list/get-plugins',
			'plugin-security/check-security',
			'mcp-server/create-post',
			'debug-log/read-log',
			'debug-log/clear-log'
		),
	);
} );