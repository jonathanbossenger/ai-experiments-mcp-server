# WP MCP Server Demo

> **⚠️ EXPERIMENTAL PLUGIN WARNING**  
> This is an experimental plugin created for workshop demonstration purposes only. It showcases how WordPress Abilities and the MCP Adapter work together. No care has been put into coding standards, comprehensive security checks, any sort of architecture patterns, or production best practices. 
> **DO NOT USE IN PRODUCTION ENVIRONMENTS.**

A WordPress plugin that implements a Model Context Protocol (MCP) server using the WordPress Abilities API and the MCP Adapter. This plugin exposes WordPress abilities through MCP tools, enabling AI systems to interact with WordPress content and functionality.

## Overview

This plugin serves as an experimental MCP server, specifically configured to expose WordPress abilities through MCP (Model Context Protocol) tools, resources, and prompts. 

## Features

### MCP Tools Available

- **Create Post** - Create new WordPress blog posts with title, content, and status
- **Plugin List** - Retrieve a comprehensive list of installed WordPress plugins
- **Plugin Security Check** - Perform security analysis on WordPress plugins using Plugin Check
- **Site Info** - Retrieve comprehensive information about the WordPress site
- **Debug Log Management** - Read and clear WordPress debug logs

### Architecture

- **Abilities API**: Uses the WordPress Abilities API for tool registration
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
git clone https://github.com/jonathanbossenger/wp-mcp-server-demo.git
```

2. Install dependencies:
```bash
cd wp-mcp-server-demo
composer install
```

3. Install and activate the Plugin Check plugin (required for security checks):
```bash
wp plugin install plugin-check --activate
```

4. Activate this plugin through the WordPress admin or WP-CLI:
```bash
wp plugin activate wp-mcp-server-demo
```

**Note**: The WordPress Abilities API is included as a Composer dependency and will be automatically installed when you run `composer install`.

## API Endpoints

Once activated, the plugin exposes MCP endpoints at:
```
/wp-json/wp-mcp-server/mcp/
```

## Available Tools

### 1. Create Post (`post/create-post`)

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

### 2. Plugin List (`plugins/get-plugins`)

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

### 3. Plugin Security Check (`plugin-check/check-security`)

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

### 4. Site Info (`site/site-info`)

Retrieves comprehensive information about the WordPress site.

**Parameters:** None

**Permissions:** Requires `manage_options` capability

**Response:**
```json
{
  "site_name": "My WordPress Site",
  "site_url": "https://example.com",
  "active_theme": "Twenty Twenty-Four",
  "active_plugins": [
    "akismet/akismet.php",
    "hello-dolly/hello.php"
  ],
  "php_version": "8.1.0",
  "wordpress_version": "6.4.0"
}
```

### 5. Debug Log Management

**Read Log (`debug/read-log`)**
- Reads WordPress debug log entries

**Clear Log (`debug/clear-log`)**  
- Clears the WordPress debug log

**Permissions:** Requires `manage_options` capability

## Development

### Project Structure

```
wp-mcp-server-demo/
├── wp-mcp-server-demo.php           # Main plugin file
├── composer.json                     # Dependencies
├── includes/                         # Ability implementations
│   ├── ability-create-post.php       # Post creation ability
│   ├── ability-get-plugins.php       # Plugin listing ability
│   ├── ability-check-security.php    # Security checking ability
│   ├── ability-site-info.php         # Site information ability
│   └── ability-debug-log.php         # Debug log management
├── vendor/                           # Composer dependencies
└── CLAUDE.md                         # AI assistant instructions
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
- [WordPress Abilities API](https://github.com/WordPress/abilities-api) - Framework for defining and managing abilities in WordPress
- [Plugin Check](https://wordpress.org/plugins/plugin-check/) - Plugin for performing security checks on WordPress plugins

## Support

For issues and feature requests, please use the GitHub issue tracker.