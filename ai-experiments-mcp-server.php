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

// Include ability files
require_once __DIR__ . '/includes/ability-create-post.php';
require_once __DIR__ . '/includes/ability-get-plugins.php';
require_once __DIR__ . '/includes/ability-check-security.php';
require_once __DIR__ . '/includes/ability-debug-log.php';

// Get the adapter instance
$adapter = McpAdapter::instance();

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
			'mcp-server/create-post',
			'debug-log/read-log',
			'debug-log/clear-log'
		),
	);
} );