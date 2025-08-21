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
This plugin exposes WordPress abilities as MCP components. The abilities are registered using the WordPress Abilities API and exposed as Tools in the MCP server.

#### Create Post Ability
A Tool that creates new WordPress blog posts with the provided content.

**Ability ID**: `mcp-server/create-post`

**Purpose**: Creates a new blog post with specified title, content, and status using WordPress post creation functions.

**Input Parameters**:
- `title` (required, string): The title of the post
- `content` (required, string): The content of the post. Must be valid block editor markup
- `status` (optional, string): The status of the post (default: 'draft', enum: ['draft', 'publish'])

**Functionality**:
- Creates posts using WordPress `wp_insert_post()` function
- Sanitizes input data for security (title sanitization, content filtering)
- Sets post author to current user
- Supports draft and publish status options
- Returns post URL upon successful creation

**Output Format**: Returns JSON object containing:
- `url`: The URL of the created post

**Error Handling**: Returns WP_Error objects for:
- Invalid input data (missing title or content)
- Post creation failures
- Permission issues

**Permission Requirements**: User must have `publish_posts` capability

**Integration**: Registered as a Tool in the MCP server's tools array.

#### Plugin List Ability
A Tool that retrieves a list of all installed WordPress plugins with their names and slugs.

**Ability ID**: `plugin-list/get-plugins`

**Purpose**: Provides a comprehensive list of installed WordPress plugins to enable users to select plugins by name or slug for further operations.

**Input Parameters**: None (no input required)

**Functionality**:
- Retrieves all installed plugins using WordPress `get_plugins()` function
- Returns both active and inactive plugins
- Extracts plugin name and slug/directory information
- Provides essential plugin metadata for identification
- No filtering or pagination - returns complete plugin inventory
- Uses WordPress core functions for reliable plugin detection
- Handles both single-file and directory-based plugins
- Detects multisite network-active plugins

**Output Format**: Returns JSON object containing:
- `success`: Boolean indicating operation success
- `plugins`: Array of plugin objects with:
  - `name`: Human-readable plugin name from plugin header
  - `slug`: Plugin directory/slug identifier  
  - `file`: Main plugin file path
  - `status`: Whether plugin is active or inactive
  - `version`: Plugin version number
- `error`: Error message if retrieval failed

**Error Handling**: All error cases return JSON responses with error information:
- WordPress plugin functions not available
- File system access issues
- Permission problems reading plugin directories
- Any other system-level errors accessing plugin data

**Permission Requirements**: User must have `manage_options` capability

**Integration**: Registered as a Tool in the MCP server's tools array.

#### Plugin Security Check Ability
A Tool that performs security checks on WordPress plugins using the Plugin Check functionality.

**Ability ID**: `plugin-security/check-security`

**Purpose**: Analyzes WordPress plugins for security vulnerabilities and issues using the security category checks from the Plugin Check plugin.

**Input Parameters**:
- `plugin_slug` (required, string): The plugin slug/name to check (e.g., "akismet", "hello-dolly")

**Functionality**:
- Executes security-specific checks from Plugin Check's security category using Plugin Check's internal PHP APIs
- Uses Plugin Check's `Abstract_Check_Runner` with custom `AI_Experiments_Security_Check_Runner` class
- Filters checks to security category only using `Check_Categories::CATEGORY_SECURITY`
- Runs all available security checks directly through Plugin Check's check runner system
- No additional configuration options - uses standard security validation rules
- Performs checks including but not limited to:
  - Input validation and sanitization issues
  - Output escaping problems
  - Nonce verification issues
  - SQL injection vulnerabilities
  - XSS vulnerabilities
  - File inclusion/execution security
  - Authentication and authorization flaws
- Requires Plugin Check plugin to be installed and active (no WP-CLI dependency)

**Output Format**: Returns JSON object containing:
- `success`: Boolean indicating check completion
- `plugin_slug`: The plugin that was checked
- `security_findings`: Array of security issues found with:
  - `file`: File path where issue was found
  - `line`: Line number of issue
  - `column`: Column number of issue
  - `type`: Issue type (ERROR/WARNING)
  - `severity`: Issue severity level
  - `message`: Descriptive error message
  - `source`: Source of the check rule
- `summary`: Summary object with:
  - `total_files_checked`: Number of files analyzed
  - `total_issues`: Total number of issues found
  - `error_count`: Number of error-level issues
  - `warning_count`: Number of warning-level issues
- `error`: Error message if check failed

**Error Handling**: All error cases return JSON responses with error information:
- Invalid or non-existent plugin slug
- Plugin Check plugin not available or not active
- Plugin Check classes not properly loaded
- Permission issues accessing plugin files
- Plugin Check runner initialization failures
- Check execution errors
- Any other system-level errors

**Permission Requirements**: User must have `manage_options` capability

**Integration**: Registered as a Tool in the MCP server's tools array.

**Technical Implementation**:
- **Custom Check Runner**: `AI_Experiments_Security_Check_Runner` class extends Plugin Check's `Abstract_Check_Runner`
- **Dynamic Class Loading**: Class is defined only when Plugin Check dependencies are available using `ai_experiments_define_security_check_runner()`
- **Security Category Filtering**: Uses `Check_Categories::CATEGORY_SECURITY` constant to filter checks
- **Result Transformation**: `ai_experiments_transform_check_results()` converts Plugin Check's `Check_Result` objects to MCP-compatible JSON format
- **Dependency Management**: Validates Plugin Check availability and class loading before execution
- **No WP-CLI Dependency**: Completely removed WP-CLI requirement, uses direct PHP API calls for better performance and reliability

#### Site Info Ability
A Tool that retrieves comprehensive information about the WordPress site.

**Ability ID**: `site/site-info`

**Purpose**: Provides essential information about the WordPress site including site details, active theme, plugins, and system versions for diagnostic and informational purposes.

**Input Parameters**: None (no input required)

**Functionality**:
- Retrieves site name and URL using WordPress `get_bloginfo()` function
- Gets active theme information using `wp_get_theme()` function
- Lists all active plugins using `get_option('active_plugins')` function
- Returns PHP version using `phpversion()` function
- Returns WordPress version using `get_bloginfo('version')` function
- Provides comprehensive site overview for diagnostic and informational purposes
- No filtering or pagination - returns complete site information
- Uses WordPress core functions for reliable data retrieval

**Output Format**: Returns JSON object containing:
- `site_name`: Site name from WordPress settings
- `site_url`: Site URL from WordPress settings
- `active_theme`: Name of the currently active theme
- `active_plugins`: Array of active plugin file paths
- `php_version`: Current PHP version string
- `wordpress_version`: Current WordPress version string

**Error Handling**: All error cases return JSON responses with error information:
- WordPress functions not available
- Permission issues accessing site data
- Any other system-level errors accessing site information

**Permission Requirements**: User must have `manage_options` capability

**Integration**: Registered as a Tool in the MCP server's tools array.

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