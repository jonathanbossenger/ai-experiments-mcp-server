# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that implements a Model Context Protocol (MCP) server using the WordPress MCP Adapter package. The plugin serves as an AI experiments server, specifically configured to expose WordPress abilities through MCP tools, resources, and prompts.

## Architecture

The plugin follows a minimal architecture leveraging the `wordpress/mcp-adapter` package:

- **Main Plugin File**: `ai-experiments-mcp-server.php` - Contains the plugin bootstrap and server configuration
- **Dependencies**: Managed via Composer with the `wordpress/mcp-adapter` package as the core dependency
- **Namespace Structure**: Uses `WP\MCP\*` namespaces from the adapter package
- **Server Configuration**: Single MCP server with REST transport, error handling, and observability

## Key Components

### MCP Server Configuration
The plugin creates one MCP server (`ai-experiments-mpc-server`) with:
- REST API namespace: `ai-experiments`  
- Route: `mcp`
- Transport: `RestTransport` (HTTP-based MCP communication)
- Error Handler: `ErrorLogMcpErrorHandler` (logs to WordPress error log)
- Observability: `NullMcpObservabilityHandler` (minimal overhead)

### Abilities Integration
This plugin will expose WordPress abilities as MCP components. The abilities are registered using the WordPress Abilities API.

#### Plugin Security Check Ability
A Tool that performs security checks on WordPress plugins using the Plugin Check functionality.

**Purpose**: Analyzes WordPress plugins for security vulnerabilities and issues using the security category checks from the Plugin Check plugin.

**Input Parameters**:
- `plugin_slug` (required, string): The plugin slug/name to check (e.g., "akismet", "hello-dolly")

**Functionality**:
- Executes security-specific checks from Plugin Check's security category
- Leverages Plugin Check's `wp plugin check` command with `--categories=security` filter
- Uses JSON output format for structured data return
- Runs all available security checks with default settings for WordPress.org plugin repository inclusion
- No additional configuration options - uses standard security validation rules
- Performs checks including but not limited to:
  - Input validation and sanitization issues
  - Output escaping problems
  - Nonce verification issues
  - SQL injection vulnerabilities
  - XSS vulnerabilities
  - File inclusion/execution security
  - Authentication and authorization flaws

**Output Format**: Returns JSON-formatted results containing security check findings, including error details, file locations, line numbers, and severity levels.

**Error Handling**: All error cases return JSON responses with error information:
- Invalid or non-existent plugin slug
- Plugin Check plugin not available or not installed
- Permission issues accessing plugin files
- WP-CLI command execution failures
- Any other system-level errors
Error responses include descriptive error messages and appropriate error codes in JSON format.

**Integration**: Registered as a Tool in the MCP server's tools array to provide executable security analysis functionality. 

#### Plugin List Ability
A Tool that retrieves a list of all installed WordPress plugins with their names and slugs.

**Purpose**: Provides a comprehensive list of installed WordPress plugins to enable users to select plugins by name or slug for further operations.

**Input Parameters**: None (no input required)

**Functionality**:
- Retrieves all installed plugins using WordPress `get_plugins()` function
- Returns both active and inactive plugins
- Extracts plugin name and slug/directory information
- Provides essential plugin metadata for identification
- No filtering or pagination - returns complete plugin inventory
- Uses WordPress core functions for reliable plugin detection

**Output Format**: Returns JSON-formatted array containing plugin information with the following structure for each plugin:
- `name`: Human-readable plugin name from plugin header
- `slug`: Plugin directory/slug identifier  
- `file`: Main plugin file path
- `status`: Whether plugin is active or inactive
- `version`: Plugin version number

**Error Handling**: All error cases return JSON responses with error information:
- WordPress plugin functions not available
- File system access issues
- Permission problems reading plugin directories
- Any other system-level errors accessing plugin data
Error responses include descriptive error messages in JSON format.

**Integration**: Registered as a Tool in the MCP server's tools array to provide plugin discovery functionality.

## Development Commands

Since this is a Composer-managed WordPress plugin, the following commands are available from the MCP Adapter dependency:

### Testing
```bash
# Run all tests (from vendor/wordpress/mcp-adapter)
vendor/bin/phpunit --configuration vendor/wordpress/mcp-adapter/phpunit.xml.dist

# Run specific test suites
vendor/bin/phpunit --configuration vendor/wordpress/mcp-adapter/phpunit.xml.dist --testsuite mcp-adapter
```

### Code Standards
```bash
# Run PHPCS linting (from vendor/wordpress/mcp-adapter)
vendor/bin/phpcs --standard=vendor/wordpress/mcp-adapter/phpcs.xml.dist src/

# Auto-fix coding standards
vendor/bin/phpcbf --standard=vendor/wordpress/mcp-adapter/phpcs.xml.dist src/
```

### Dependency Management
```bash
# Install/update dependencies
composer install
composer update

# Dump autoloader after changes
composer dump-autoload
```

## Server Registration Pattern

The plugin uses WordPress action hooks for MCP server initialization:

1. Main adapter instance retrieved via `McpAdapter::instance()`
2. Server configuration happens in `mcp_adapter_init` action hook
3. Server created with `create_server()` method providing all configuration

## Transport and Error Handling

- **REST Transport**: Automatically registers WordPress REST API endpoints under `/wp-json/{namespace}/{route}/`
- **Error Logging**: Uses WordPress `error_log()` function via `ErrorLogMcpErrorHandler`
- **Observability**: Null handler provides minimal performance overhead

## Adding New Abilities

To expose additional WordPress abilities as MCP components:

1. Register the ability using WordPress Abilities API
2. Add ability identifier to the appropriate array in `create_server()` call:
   - Tools array (4th parameter) for executable actions
   - Resources array (5th parameter) for data access
   - Prompts array (6th parameter) for AI guidance