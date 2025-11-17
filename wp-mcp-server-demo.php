<?php
/**
 * Plugin Name: WP MCP Server Demo
 * Description: A demo plugin to showcase the WordPress MCP Server functionality.
 * Version: 1.0.0
 * Requires Plugins: wp-abilities-api-demo
 * Author: Jonathan Bossenger
 * Author URI: https://jonathanbossenger.com
 * License: GPL-2.0+
 *
 * @package wp-mcp-server-demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	// Composer dependencies are missing.
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'WP Abilities API Demo plugin requires Composer dependencies. Please run "composer install" in the plugin directory.', 'wp-abilities-api-demo' ); ?></p>
			</div>
			<?php
		}
	);
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
	return;
}

// Initialize the MCP adapter and activate the default server.
\WP\MCP\Core\McpAdapter::instance();


/**
 * Register a custom MCP server with specific tools, resources and prompts.
 * Requires Abilities API Demo plugin https://github.com/jonathanbossenger/wp-abilities-api-demo.
 */
// Check if Abilities API demo plugin is installed and active.
if ( is_plugin_active( 'wp-abilities-api-demo/wp-abilities-api-demo.php' ) ) {
	// Create custom MCP server using Abilities API Demo abilities.
	add_action(
		'mcp_adapter_init',
		function ( $adapter ) {
			$abilities = array(
				'site/site-info',
				'debug/read-log',
				'debug/clear-log',
				'plugins/get-plugins',
				'plugins/install-plugin',
				'plugins/delete-plugin',
				'post/create-post',
				'security/check-security',
			);
			$adapter->create_server(
				'mcp-demo-server', // Unique server identifier.
				'mcp-demo-server', // REST API namespace.
				'mcp',             // REST API route.
				'MCP Demo Server', // Server name.
				'MCP Demo Server', // Server description.
				'v1.0.0',          // Server version.
				array(             // Transport methods.
					\WP\MCP\Transport\HttpTransport::class,  // Recommended: MCP 2025-06-18 compliant.
				),
				\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler.
				\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler.
				$abilities,        // Abilities to expose as tools, registered in https://github.com/jonathanbossenger/wp-abilities-api-demo.
				array(),           // Resources (optional).
				array(),           // Prompts (optional).
			);
		}
	);
}
