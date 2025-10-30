# WP MCP Server Demo

A lightweight WordPress plugin that configures a custom Model Context Protocol (MCP) server using the WordPress MCP Adapter and Abilities API. This plugin acts as a configuration wrapper that creates an MCP server exposing abilities defined in the [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) plugin.

## Overview

This plugin serves as a thin configuration layer that:
- Initializes the WordPress MCP Adapter
- Creates a custom MCP server named "MCP Demo Server"
- Exposes specific abilities from the WP Abilities API Demo plugin as MCP tools
- Demonstrates how to configure a custom MCP server in WordPress 

## How It Works

This plugin does not define any abilities itself. Instead, it:

1. **Checks for required plugins** - Verifies that MCP Adapter and WP Abilities API Demo plugins are active
2. **Initializes MCP Adapter** - Activates the MCP Adapter framework
3. **Creates a custom MCP server** - Configures a server named "MCP Demo Server" 
4. **Maps abilities to the server** - Links abilities from WP Abilities API Demo plugin to the MCP server

### MCP Tools Available

The following abilities from the [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) plugin are exposed through this MCP server:

- **Site Info** (`site/site-info`) - Retrieve comprehensive WordPress site information
- **Debug Log Management** (`debug/read-log`, `debug/clear-log`) - Read and clear WordPress debug logs
- **Plugin List** (`plugins/get-plugins`) - Retrieve a list of installed WordPress plugins
- **Create Post** (`post/create-post`) - Create new WordPress blog posts
- **Security Check** (`security/check-security`) - Perform security analysis on WordPress plugins

**Note:** The actual implementation of these abilities is in the WP Abilities API Demo plugin, not in this repository.

### Architecture

- **Server Configuration**: Lightweight wrapper that configures an MCP server
- **Abilities Source**: All abilities are defined in the WP Abilities API Demo plugin
- **Transport**: REST API-based HTTP communication (MCP 2025-06-18 compliant)
- **Error Handling**: WordPress error logging integration
- **Observability**: Null handler for minimal overhead

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- **Required WordPress Plugins:**
  - [MCP Adapter](https://github.com/WordPress/mcp-adapter) - Core MCP functionality
  - [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) - Provides the actual abilities

## Installation

1. **Install Required Plugins**

First, install and activate the required plugins:

```bash
# Clone and activate MCP Adapter
cd wp-content/plugins/
git clone https://github.com/WordPress/mcp-adapter.git
wp plugin activate mcp-adapter

# Clone and activate WP Abilities API Demo
git clone https://github.com/jonathanbossenger/wp-abilities-api-demo.git
cd wp-abilities-api-demo
composer install
wp plugin activate wp-abilities-api-demo
```

2. **Install This Plugin**

Clone this repository and activate it:

```bash
cd wp-content/plugins/
git clone https://github.com/jonathanbossenger/wp-mcp-server-demo.git
wp plugin activate wp-mcp-server-demo
```

**Note:** This plugin does not require `composer install` as it has no Composer dependencies of its own. It only requires WPCS for development.

## API Endpoints

Once activated, the plugin exposes MCP endpoints at:
```
/wp-json/mcp-demo-server/mcp/
```

## Available Tools

The following tools are available through the MCP server. For detailed parameter information and implementation details, refer to the [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) repository.

### 1. Site Info (`site/site-info`)

Retrieves comprehensive information about the WordPress site.

**Permissions:** Requires `manage_options` capability

### 2. Debug Log Management

**Read Log (`debug/read-log`)** - Reads WordPress debug log entries

**Clear Log (`debug/clear-log`)** - Clears the WordPress debug log

**Permissions:** Requires `manage_options` capability

### 3. Plugin List (`plugins/get-plugins`)

Retrieves a list of all installed WordPress plugins with metadata.

**Permissions:** Requires `manage_options` capability

### 4. Create Post (`post/create-post`)

Creates new WordPress blog posts with specified content.

**Permissions:** Requires `publish_posts` capability

### 5. Security Check (`security/check-security`)

Performs security analysis on WordPress plugins.

**Permissions:** Requires `manage_options` capability

**Note:** The Plugin Check plugin must be installed and active for this tool to work.

## Development

### Project Structure

```
wp-mcp-server-demo/
├── wp-mcp-server-demo.php           # Main plugin file - MCP server configuration
├── composer.json                     # Development dependencies (WPCS)
├── .github/
│   └── copilot-instructions.md      # AI assistant instructions
└── README.md                        # This file
```

**Note:** This plugin does not contain an `includes/` directory. All abilities are defined in the [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) plugin.

### What This Plugin Does

This plugin is a configuration wrapper that:

1. Checks if the MCP Adapter plugin is active and initializes it
2. Checks if the WP Abilities API Demo plugin is active
3. Creates a custom MCP server called "MCP Demo Server"
4. Maps the following abilities from WP Abilities API Demo to the MCP server:
   - `site/site-info`
   - `debug/read-log`
   - `debug/clear-log`
   - `plugins/get-plugins`
   - `post/create-post`
   - `security/check-security`

### Adding Custom MCP Servers

This plugin demonstrates how to create a custom MCP server in WordPress. To create your own server:

1. Hook into the `mcp_adapter_init` action
2. Call `$adapter->create_server()` with your configuration
3. Specify which abilities to expose from available registered abilities

See `wp-mcp-server-demo.php` for the implementation example.

## Security

All abilities implement permission-based access control. Security features are implemented in the [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) plugin:
- Post creation requires `publish_posts` capability
- Administrative functions require `manage_options` capability
- Input sanitization and validation on all endpoints
- Proper WordPress capability checks

## Testing

This is an experimental plugin. To test:

1. Ensure all required plugins are installed and activated
2. Use tools like Postman or curl to test the MCP API endpoints
3. Verify the server responds at `/wp-json/mcp-demo-server/mcp/`
4. Test with proper WordPress authentication (application passwords recommended)

Example curl request:
```bash
curl -X POST https://your-site.com/wp-json/mcp-demo-server/mcp/ \
  -H "Content-Type: application/json" \
  -u username:application_password \
  -d '{"method":"tools/list"}'
```

## License

This plugin is licensed under the GPL-2.0-or-later license, same as WordPress.

## Author

**Jonathan Bossenger**  
Email: jonathanbossenger@gmail.com

## Dependencies

### Required WordPress Plugins

- **[MCP Adapter](https://github.com/WordPress/mcp-adapter)** - Core MCP functionality for WordPress
- **[WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo)** - Provides the abilities exposed by this MCP server

### Development Dependencies (Composer)

- `wp-coding-standards/wpcs: ^3.0` - WordPress Coding Standards for PHPCS

## Related Projects

- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) - Core MCP functionality for WordPress
- [WordPress Abilities API](https://github.com/WordPress/abilities-api) - Framework for defining abilities
- [WP Abilities API Demo](https://github.com/jonathanbossenger/wp-abilities-api-demo) - Demo abilities used by this plugin
- [Model Context Protocol](https://modelcontextprotocol.io/) - MCP specification

## Support

For issues and feature requests, please use the [GitHub issue tracker](https://github.com/jonathanbossenger/wp-mcp-server-demo/issues).

## Contributing

This is an experimental demonstration plugin. If you'd like to contribute:

1. Fork the repository
2. Create a feature branch
3. Make your changes following WordPress coding standards
4. Test your changes thoroughly
5. Submit a pull request
