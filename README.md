# AI Experiments MCP Server

> **⚠️ EXPERIMENTAL PLUGIN WARNING**  
> This is an experimental plugin created for workshop demonstration purposes only. It showcases how WordPress Abilities and the MCP Adapter work together. No care has been put into coding standards, comprehensive security checks, or production best practices. **DO NOT USE IN PRODUCTION ENVIRONMENTS.**

A WordPress plugin that implements a Model Context Protocol (MCP) server using the WordPress MCP Adapter package. This plugin exposes WordPress abilities through MCP tools, enabling AI systems to interact with WordPress content and functionality.

## Overview

This plugin serves as an AI experiments server, specifically configured to expose WordPress abilities through MCP (Model Context Protocol) tools, resources, and prompts. It leverages the `wordpress/mcp-adapter` package to provide a seamless integration between WordPress and AI systems.

## Features

### MCP Tools Available

- **Create Post** - Create new WordPress blog posts with title, content, and status
- **Plugin List** - Retrieve a comprehensive list of installed WordPress plugins
- **Plugin Security Check** - Perform security analysis on WordPress plugins using Plugin Check
- **Debug Log Management** - Read and clear WordPress debug logs

### Architecture

- **Transport**: REST API-based MCP communication
- **Error Handling**: WordPress error logging integration
- **Security**: Permission-based access control for all tools
- **Observability**: Minimal overhead observability handling

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Composer for dependency management
- Plugin Check plugin (for security checks)

## Installation

1. Clone this repository into your WordPress plugins directory:
```bash
cd wp-content/plugins/
git clone https://github.com/jonathanbossenger/ai-experiments-mcp-server.git
```

2. Install dependencies:
```bash
cd ai-experiments-mcp-server
composer install
```

3. Activate the plugin through the WordPress admin or WP-CLI:
```bash
wp plugin activate ai-experiments-mcp-server
```

## API Endpoints

Once activated, the plugin exposes MCP endpoints at:
```
/wp-json/ai-experiments/mcp/
```

## Available Tools

### 1. Create Post (`mcp-server/create-post`)

Creates new WordPress blog posts with specified content.

**Parameters:**
- `title` (required, string): The post title
- `content` (required, string): Post content in block editor markup
- `status` (optional, string): Post status - `draft` or `publish` (default: `draft`)

**Permissions:** Requires `publish_posts` capability

**Response:**
```json
{
  "success": true,
  "url": "https://example.com/post-url/"
}
```

### 2. Plugin List (`plugin-list/get-plugins`)

Retrieves a list of all installed WordPress plugins with metadata.

**Parameters:** None

**Permissions:** Requires `manage_options` capability

**Response:**
```json
{
  "success": true,
  "plugins": [
    {
      "name": "Plugin Name",
      "slug": "plugin-slug",
      "file": "plugin-slug/plugin-file.php",
      "status": "active",
      "version": "1.0.0"
    }
  ]
}
```

### 3. Plugin Security Check (`plugin-security/check-security`)

Performs security analysis on WordPress plugins using Plugin Check functionality.

**Parameters:**
- `plugin_slug` (required, string): Plugin slug to analyze (e.g., "akismet", "hello-dolly")

**Permissions:** Requires `manage_options` capability

**Dependencies:** Plugin Check plugin must be installed and active

**Response:**
```json
{
  "success": true,
  "plugin_slug": "example-plugin",
  "security_findings": [
    {
      "file": "/path/to/file.php",
      "line": 42,
      "column": 10,
      "type": "ERROR",
      "severity": "high",
      "message": "Security issue description",
      "source": "check_name"
    }
  ],
  "summary": {
    "total_files_checked": 15,
    "total_issues": 3,
    "error_count": 1,
    "warning_count": 2
  }
}
```

### 4. Debug Log Management

**Read Log (`debug-log/read-log`)**
- Reads WordPress debug log entries

**Clear Log (`debug-log/clear-log`)**  
- Clears the WordPress debug log

**Permissions:** Requires `manage_options` capability

## Development

### Project Structure

```
ai-experiments-mcp-server/
├── ai-experiments-mcp-server.php    # Main plugin file
├── composer.json                     # Dependencies
├── includes/                         # Ability implementations
│   ├── ability-create-post.php       # Post creation ability
│   ├── ability-get-plugins.php       # Plugin listing ability
│   ├── ability-check-security.php    # Security checking ability
│   └── ability-debug-log.php         # Debug log management
├── vendor/                           # Composer dependencies
└── CLAUDE.md                         # AI assistant instructions
```

### Adding New Abilities

To add new WordPress abilities as MCP tools:

1. Create a new ability file in the `includes/` directory
2. Register the ability using the WordPress Abilities API
3. Add the ability identifier to the tools array in the main plugin file

Example ability registration:
```php
add_action( 'abilities_api_init', function () {
    wp_register_ability( 'your-ability/action-name', array(
        'label' => __( 'Your Ability', 'ai-experiments-mcp-server' ),
        'description' => __( 'Description of what this ability does', 'ai-experiments-mcp-server' ),
        'input_schema' => array(/* JSON schema for input */),
        'output_schema' => array(/* JSON schema for output */),
        'execute_callback' => 'your_callback_function',
        'permission_callback' => function() {
            return current_user_can( 'required_capability' );
        }
    ));
});
```

### Development Commands

**Install/Update Dependencies:**
```bash
composer install
composer update
```

**Code Standards (from MCP Adapter):**
```bash
# Check coding standards
vendor/bin/phpcs --standard=vendor/wordpress/mcp-adapter/phpcs.xml.dist src/

# Auto-fix coding standards
vendor/bin/phpcbf --standard=vendor/wordpress/mcp-adapter/phpcs.xml.dist src/
```

**Testing (from MCP Adapter):**
```bash
# Run all tests
vendor/bin/phpunit --configuration vendor/wordpress/mcp-adapter/phpunit.xml.dist

# Run specific test suites
vendor/bin/phpunit --configuration vendor/wordpress/mcp-adapter/phpunit.xml.dist --testsuite mcp-adapter
```

## Security

All abilities implement permission-based access control:
- Post creation requires `publish_posts` capability
- Plugin management requires `manage_options` capability
- Input sanitization and validation on all endpoints
- WordPress nonce verification for state-changing operations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following WordPress coding standards
4. Test your changes thoroughly
5. Submit a pull request

## License

This plugin is licensed under the GPL-2.0-or-later license, same as WordPress.

## Author

**Jonathan Bossenger**  
Email: jonathanbossenger@gmail.com

## Dependencies

- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) - Core MCP functionality for WordPress

## Support

For issues and feature requests, please use the GitHub issue tracker.