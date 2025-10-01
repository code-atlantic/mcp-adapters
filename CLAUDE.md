# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MCP Adapters is a WordPress plugin that bridges WordPress plugins with AI models through the Model Context Protocol (MCP). It uses the WordPress Abilities API to expose plugin functionality as MCP tools, resources, and prompts that AI agents can discover and execute.

**Key Dependencies:**
- `wordpress/abilities-api` (^0.1.0) - Central capability registry
- `wordpress/mcp-adapter` (^0.1.0) - MCP protocol translation layer
- PHP 8.0+, WordPress 6.4+

## Development Commands

### Setup
```bash
composer install              # Install PHP dependencies (required before anything else)
```

### Code Quality (Run before committing)
```bash
composer format              # Auto-fix coding standards (phpcbf)
composer lint                # Check coding standards (phpcs)
composer phpstan             # Static analysis
```

### Testing
```bash
composer tests               # Run all tests (no coverage)
composer coverage            # Run tests with HTML coverage report
vendor/bin/phpunit tests/phpunit/SpecificTest.php  # Run single test file
```

## Architecture

### Plugin Initialization Flow
1. **mcp-adapters.php** - Entry point, loads Composer autoloader
2. **Plugin.php** - Main coordinator, detects active plugins at `plugins_loaded` priority 25
3. **{Plugin}Adapter.php** - Plugin-specific adapter (e.g., FluentBoardsAdapter)
   - Hooks `abilities_api_init` (priority 10) to register abilities
   - Hooks `mcp_adapter_init` (priority 10) to register MCP servers

### Adapter Architecture Pattern

Each WordPress plugin gets its own adapter in `classes/Adapters/{PluginName}/`:

```
FluentBoards/
├── FluentBoardsAdapter.php          # Main coordinator
├── BaseAbility.php                  # Shared permission/validation methods
├── Abilities/                       # WordPress abilities (tools)
│   ├── Boards.php                   # Board CRUD operations
│   ├── Tasks.php                    # Task management
│   └── ...
├── Prompts/                         # MCP prompts (structured guidance)
│   ├── ProjectOverview.php
│   └── ...
└── Servers/                         # MCP server configurations
    ├── BoardCrudServer.php          # Focused server (10 abilities)
    └── FullFluentBoardsServer.php   # Complete server (80+ abilities)
```

**Adapter Responsibilities:**
1. Detect if target plugin is active (check constants, classes)
2. Register WordPress abilities on `abilities_api_init` hook
3. Register MCP servers on `mcp_adapter_init` hook with ability references
4. Provide shared permission callbacks and utilities in BaseAbility

### Hook Timing Critical Path

**CRITICAL:** Abilities must exist in registry BEFORE MCP servers try to reference them.

```
WordPress Load
  ↓
plugins_loaded (priority 20) → abilities-api initializes
  ↓
plugins_loaded (priority 25) → mcp-adapters initializes adapters
  ↓
abilities_api_init (priority 10) → Adapters register abilities via wp_register_ability()
  ↓
mcp_adapter_init (priority 10) → Adapters register MCP servers with ability references
  ↓
rest_api_init → MCP endpoints become available
```

## Critical Rules for Abilities API

### 1. Ability Naming Convention (MANDATORY)

**All ability names MUST follow this exact pattern:**
```
namespace/ability-name
```

**Rules:**
- Use forward slash `/` to separate namespace from ability name
- Use lowercase letters, numbers, and dashes ONLY
- NO underscores allowed anywhere
- Pattern validation: `/^[a-z0-9-]+\/[a-z0-9-]+$/`

**Examples:**
```php
// ✅ CORRECT
'fluentboards/list-boards'
'fluentboards/create-task'
'fluentboards/update-board-permissions'

// ❌ WRONG - Will fail silently
'fluentboards_list_boards'      // Underscores
'fluentboards-list-boards'      // No namespace separator
'FluentBoards/list-boards'      // Uppercase
'fluentboards/list_boards'      // Underscore in ability name
```

**Why This Matters:**
- The Abilities API validates names with regex and returns `null` (silent failure) for invalid names
- Invalid abilities won't appear in registry but won't throw errors
- MCP servers will report "ability not found" errors

### 2. Permission Callbacks Must Be Public

**All `permission_callback` and `execute_callback` methods MUST be `public`:**

```php
// ✅ CORRECT
public function can_view_boards( ?int $board_id = null ): bool {
    return current_user_can( 'manage_options' );
}

// ❌ WRONG - Will fail is_callable() check
protected function can_view_boards( ?int $board_id = null ): bool {
    return current_user_can( 'manage_options' );
}
```

**Why This Matters:**
- WordPress Abilities API validates callbacks with `is_callable()`
- Protected methods fail this check and cause `wp_register_ability()` to return `null`
- This is a silent failure - no exceptions thrown

### 3. Ability Registration Return Value

Always check both `null` and `false` when verifying registration:

```php
$result = wp_register_ability( 'namespace/ability-name', $args );

// ✅ CORRECT
if ( $result === null || $result === false ) {
    error_log( 'Registration failed: ' . var_export( $result, true ) );
}

// ❌ WRONG - Misses null returns
if ( $result === false ) {
    error_log( 'Registration failed' );
}
```

**Why This Matters:**
- Validation failures return `null`, not `false`
- Using `=== false` check misses the actual failures
- This led to "Successfully registered" logs for abilities that actually failed

### 4. Plugin Detection in Different Contexts

CLI context (WP-CLI) has different plugin loading than web context:

```php
// ✅ CORRECT - Works in both contexts
protected function is_fluent_boards_active(): bool {
    return defined( 'FLUENT_BOARDS' ) &&
           class_exists( '\FluentBoards\App\Models\Board' );
}

// ❌ WRONG - Fails in WP-CLI context
protected function is_fluent_boards_active(): bool {
    return is_plugin_active( 'fluent-boards/fluent-boards.php' );
}
```

### 5. Schema Validation

Avoid deeply nested objects in `input_schema` - they can cause validation failures:

```php
// ⚠️ PROBLEMATIC - Nested objects without additionalProperties
'settings' => [
    'type' => 'object',
    'properties' => [
        'cover' => [
            'type' => 'object',
            'properties' => [...]  // Nested object may fail validation
        ],
    ],
],

// ✅ BETTER - Flatten or use simple types
'cover_image_id' => [
    'type' => 'integer',
    'description' => 'Cover image attachment ID',
],
```

## Common Debugging Patterns

### Check Ability Registration
```bash
# Via WP-CLI
wp eval "
\$registry = WP_Abilities_Registry::get_instance();
\$abilities = \$registry->get_all_registered();
echo 'Total: ' . count(\$abilities) . PHP_EOL;
foreach (array_keys(\$abilities) as \$name) echo '  - ' . \$name . PHP_EOL;
"
```

### Check MCP Server Tools
```bash
# Via REST API
curl -s "http://yoursite.local/wp-json/namespace/mcp" | jq '.tools | length'
curl -s "http://yoursite.local/wp-json/namespace/mcp" | jq '.tools[].name'
```

### Debug Hook Execution Order
```bash
# Check if abilities_api_init already fired
wp eval "echo 'abilities_api_init fired: ' . did_action('abilities_api_init') . PHP_EOL;"

# Check registered callbacks on a hook
wp eval "
global \$wp_filter;
if (isset(\$wp_filter['abilities_api_init'])) {
    foreach (\$wp_filter['abilities_api_init']->callbacks as \$priority => \$callbacks) {
        echo 'Priority ' . \$priority . ': ' . count(\$callbacks) . ' callbacks' . PHP_EOL;
    }
}
"
```

## MCP Server Registration Pattern

Servers reference abilities by name (strings), not objects:

```php
// In FluentBoardsAdapter.php
public function register_all_servers( $adapter ): void {
    $this->register_board_crud_server( $adapter );
    $this->register_full_server( $adapter );
}

// In BoardCrudServer.php
public function register_with_adapter( $adapter ): void {
    $adapter->create_server(
        'fluentboards-board-crud',           // Server ID
        'fluentboards-board-crud',           // Route namespace
        'mcp',                               // Route
        'FluentBoards Board CRUD',           // Name
        'Board management operations only',  // Description
        '1.0.0',                            // Version
        [ 'WP\MCP\Transport\Http\RestTransport' ], // Transports
        'WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler',
        'WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler',
        [
            // Reference abilities by name (strings)
            'fluentboards/list-boards',
            'fluentboards/get-board',
            'fluentboards/create-board',
            // ...
        ]
    );
}
```

## Namespace & Autoloading

- **Namespace Root:** `MCP\Adapters\`
- **PSR-4 Mapping:** `classes/` directory
- **Example:** `MCP\Adapters\Adapters\FluentBoards\Abilities\Boards` → `classes/Adapters/FluentBoards/Abilities/Boards.php`

## Code Standards

- **PHP:** WordPress Coding Standards + strict typing (`declare(strict_types=1);`)
- **Prefix:** `mcp_adapters` for functions, `MCP_ADAPTERS_` for constants
- **Text Domain:** `mcp-adapters`
- **Documentation:** PHPDoc blocks required for all public methods

## Adding New Plugin Adapters

1. Create adapter structure in `classes/Adapters/{PluginName}/`
2. Create `{PluginName}Adapter.php` that hooks `abilities_api_init` and `mcp_adapter_init`
3. Create `BaseAbility.php` with shared permission callbacks (MUST be public)
4. Create ability classes in `Abilities/` directory
5. Register adapter in `Plugin.php::initialize_adapters()`
6. Follow naming convention: `namespace/ability-name` (no underscores)

## Important File Locations

- Abilities API docs: `vendor/wordpress/mcp-adapter/docs/guides/creating-abilities.md`
- MCP Adapter docs: `vendor/wordpress/mcp-adapter/README.md`
- Test configuration: `tests/phpunit.xml`
- Coding standards: `.phpcs.xml.dist`