<?php
/**
 * Plugin Name: WP MCP Server Demo
 * Description: A demo plugin to showcase the WordPress MCP Server functionality.
 * Version: 1.0.0
 */

// Check if the MCP Adapter plugin is installed and active
if ( class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
    // activate the default server
    \WP\MCP\Core\McpAdapter::instance();
}

add_action('mcp_adapter_init', function($adapter) {
    $adapter->create_server(
        'mcp-demo-server',                    // Unique server identifier
        'mcp-demo-server',                    // REST API namespace
        'mcp',                            // REST API route
        'MCP Demo Server',                  // Server name
        'MCP Demo Server',       // Server description
        'v1.0.0',                        // Server version
        [                                 // Transport methods
            \WP\MCP\Transport\HttpTransport::class,  // Recommended: MCP 2025-06-18 compliant
        ],
        \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler
        \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler
        [
            'debug/read-log'

        ],         // Abilities to expose as tools
        [],                              // Resources (optional)
        [],                              // Prompts (optional)
    );
});
