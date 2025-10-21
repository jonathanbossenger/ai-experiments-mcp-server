# GitHub Copilot Instructions for WP MCP Server Demo

## Project Overview

This is an **experimental WordPress plugin** that implements a Model Context Protocol (MCP) server using the WordPress Abilities API and MCP Adapter. The plugin exposes WordPress functionality through MCP tools, enabling AI systems to interact with WordPress content.

**⚠️ Important:** This is a demonstration/workshop plugin. It prioritizes functionality over production best practices. Do not use in production environments.

## Architecture

### Core Components

- **Main Plugin File**: `wp-mcp-server-demo.php` - Bootstrap and server configuration
- **Abilities**: Located in `includes/` directory, each file registers WordPress abilities
- **Dependencies**: Managed via Composer
  - `wordpress/mcp-adapter` - Core MCP functionality
  - `wordpress/abilities-api` - Ability registration framework
  - `automattic/jetpack-autoloader` - Autoloading support

### MCP Server Setup

The plugin creates a single MCP server (`wp-mcp-server`) with:
- **REST API Endpoint**: `/wp-json/wp-mcp-server/mcp/`
- **Transport**: REST-based HTTP communication
- **Error Handler**: WordPress error log integration
- **Observability**: Null handler (minimal overhead)

## Code Style & Conventions

### WordPress Standards

- Follow WordPress PHP coding standards
- Use WordPress core functions whenever possible
- Implement proper capability checks for security
- Sanitize all input, escape all output
- Use WordPress error handling (`WP_Error`)

### Naming Conventions

- **Ability Files**: `ability-{name}.php` (e.g., `ability-create-post.php`)
- **Ability IDs**: Use format `{category}/{action}` (e.g., `post/create-post`)
- **Functions**: Use descriptive names with `ai_experiments_` prefix
- **Variables**: Use snake_case for PHP variables
- **Constants**: Use SCREAMING_SNAKE_CASE

### File Organization

```
wp-mcp-server-demo/
├── wp-mcp-server-demo.php     # Main plugin file, server configuration
├── includes/                   # Ability implementations
│   ├── ability-*.php           # Individual ability files
└── vendor/                     # Composer dependencies (gitignored)
```

## Development Workflow

### Setup

1. Clone the repository into WordPress plugins directory
2. Run `composer install` to install dependencies
3. Activate the plugin via WordPress admin or WP-CLI
4. Install Plugin Check plugin (required for security checks)

### Adding New Abilities

To add a new ability:

1. Create a new file in `includes/ability-{name}.php`
2. Register the ability using WordPress Abilities API
3. Add the ability ID to the appropriate array in `wp-mcp-server-demo.php`:
   - `tools` array (4th param) for executable actions
   - `resources` array (5th param) for data access
   - `prompts` array (6th param) for AI guidance

Example structure:
```php
<?php
use WP\Abilities\Abilities;

Abilities::register( 'category/ability-name', array(
    'label' => 'Human-readable label',
    'callback' => 'callback_function_name',
    'capabilities' => array( 'required_capability' ),
) );
```

### Code Quality

This is an experimental plugin without formal linting/testing setup. However:

- Test manually after making changes
- Verify REST API endpoints work correctly
- Check WordPress error logs for issues
- Ensure proper capability checks are in place

## Security Considerations

### Permission Checks

All abilities must implement proper capability checks:
- `publish_posts` - For content creation
- `manage_options` - For administrative functions
- Custom capabilities as needed

### Input Validation

- Always sanitize user input using WordPress functions
- Validate all parameters before processing
- Use `wp_unslash()` for slashed data
- Use `sanitize_text_field()`, `sanitize_textarea_field()`, etc.

### Output Escaping

- Escape output in HTML contexts
- Use `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- JSON responses should use proper encoding

## Available Abilities

### Current Tools

1. **Create Post** (`post/create-post`)
   - Creates WordPress posts with title, content, status
   - Requires: `publish_posts` capability

2. **Plugin List** (`plugins/get-plugins`)
   - Lists all installed plugins with metadata
   - Requires: `manage_options` capability

3. **Plugin Security Check** (`plugin-check/check-security`)
   - Analyzes plugins for security issues using Plugin Check
   - Requires: `manage_options` capability, Plugin Check plugin

4. **Site Info** (`site/site-info`)
   - Returns WordPress site information
   - Requires: `manage_options` capability

5. **Debug Log Management** (`debug/read-log`, `debug/clear-log`)
   - Read/clear WordPress debug logs
   - Requires: `manage_options` capability

## Dependencies

### Required WordPress Plugins

- **Plugin Check** - Required for `plugin-check/check-security` ability

### Composer Packages

All managed via `composer.json`:
- `wordpress/abilities-api: ^0.2.0`
- `wordpress/mcp-adapter: ^0.1.0`

Run `composer install` after cloning or when dependencies change.

## Common Patterns

### Ability Registration Pattern

```php
use WP\Abilities\Abilities;

Abilities::register( 'category/action', array(
    'label' => 'Action Label',
    'callback' => 'callback_function',
    'capabilities' => array( 'required_cap' ),
) );

function callback_function( $params ) {
    // Validate capabilities
    if ( ! current_user_can( 'required_cap' ) ) {
        return new WP_Error( 'permission_denied', 'Insufficient permissions' );
    }
    
    // Validate input
    if ( empty( $params['required_field'] ) ) {
        return new WP_Error( 'invalid_input', 'Required field missing' );
    }
    
    // Process and return result
    return array(
        'success' => true,
        'data' => $result,
    );
}
```

### Error Handling Pattern

```php
// Return WP_Error for failures
if ( $error_condition ) {
    return new WP_Error( 'error_code', 'Error message' );
}

// Return success array for success
return array(
    'success' => true,
    'field' => $value,
);
```

## Testing

This is an experimental plugin without formal test infrastructure. Manual testing:

1. Activate the plugin in WordPress
2. Test REST API endpoints using tools like Postman or curl
3. Verify abilities return expected responses
4. Check WordPress debug log for errors
5. Test with different user capabilities

## API Endpoints

### Base URL
```
/wp-json/wp-mcp-server/mcp/
```

### Testing Endpoints

Use WordPress REST API authentication:
- Application passwords
- OAuth tokens
- Cookie authentication (with nonce)

Example curl request:
```bash
curl -X POST https://your-site.com/wp-json/wp-mcp-server/mcp/ \
  -H "Content-Type: application/json" \
  -u username:application_password \
  -d '{"tool":"post/create-post","params":{"title":"Test","content":"Content"}}'
```

## References

- [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)
- [WordPress Abilities API](https://github.com/WordPress/abilities-api)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

## Tips for AI Assistants

- This is an experimental plugin - prioritize working code over perfection
- Use WordPress core functions over custom implementations
- Always check capabilities before executing privileged operations
- Sanitize input and validate all parameters
- Return consistent error/success response formats
- Test changes manually as there's no automated test suite
- Refer to existing ability files for patterns and conventions
