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

### Bidirectional MCP Integration

The plugin supports both **outbound** (consuming external MCP servers) and **inbound** (exposing WordPress as MCP server) integration:

**Outbound: McpClient** - Connect to external MCP servers
- External MCP tools/resources/prompts become WordPress abilities
- Namespace: `mcp_{client_id}/tool-name`
- Hook: `mcp_client_init` for registration
- Permission filter: `mcp_client_permission`

**Inbound: MCP Servers** - Expose WordPress abilities via MCP
- WordPress abilities exposed via REST API endpoints
- Standard MCP JSON-RPC 2.0 protocol
- Hook: `mcp_adapter_init` for server registration

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
'fluentcrm/add-sequence-email'

// ❌ WRONG - Will fail silently
'fluentboards_list_boards'      // Underscores
'fluentboards-list-boards'      // No namespace separator
'FluentBoards/list-boards'      // Uppercase
'fluentboards/list_boards'      // Underscore in ability name
'fluentcrm-add-sequence-email'  // Dash instead of slash separator
```

**Why This Matters:**
- The Abilities API validates names with regex and returns `null` (silent failure) for invalid names
- Invalid abilities won't appear in registry but won't throw errors
- MCP servers will report "ability not found" errors
- **CRITICAL:** Abilities registered with wrong format (e.g., dashes instead of slashes) won't match AbilityRegistry references, causing tools to not appear in MCP servers even after registration

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

## MCP Client Usage (Consuming External MCP Servers)

### Basic Client Registration

Register external MCP servers on the `mcp_client_init` hook:

```php
add_action( 'mcp_client_init', function( $manager ) {
    $client = $manager->create_client(
        'my-service',                          // Client ID
        'https://api.example.com/mcp',         // Server URL
        [
            'auth' => [
                'type'  => 'bearer',           // bearer | api_key | basic
                'token' => 'your-token-here',
            ],
            'timeout' => 30,
        ]
    );
});
```

### Authentication Methods

**Bearer Token:**
```php
'auth' => [
    'type'  => 'bearer',
    'token' => 'your-api-token',
]
```

**API Key:**
```php
'auth' => [
    'type' => 'api_key',
    'key'  => 'your-api-key',
]
```

**Basic Auth:**
```php
'auth' => [
    'type'     => 'basic',
    'username' => 'username',
    'password' => 'password',
]
```

### Remote Ability Namespace

Remote tools are auto-registered as WordPress abilities with namespace:
- Tools: `mcp_{client_id}/tool-name`
- Resources: `mcp_{client_id}/resource/resource-uri`
- Prompts: `mcp_{client_id}/prompt/prompt-name`

### Using Remote Abilities

```php
$ability = wp_get_ability( 'mcp_my-service/check-domain' );

if ( $ability ) {
    $result = $ability->execute( [ 'domain' => 'example.com' ] );
}
```

### Permission Control

Control access to all MCP client abilities:

```php
add_filter( 'mcp_client_permission', function( $allowed, $client_id ) {
    return current_user_can( 'edit_others_posts' );
}, 10, 2 );
```

### Client Status

Check registered clients:

```php
$statuses = \MCP\Adapters\Core\McpClientManager::get_client_status();
// Returns: [ 'client-id' => [ 'url', 'connected', 'tools', 'resources', 'prompts' ] ]
```

### Example File

See complete examples in: `examples/client-example.php`

## Testing Standards & Discovered Issues

### Test Coverage Requirements

1. **Comprehensive Parameter Testing**
   - Every tool MUST have tests for all parameters
   - Test both valid and invalid values
   - Test missing required fields
   - Test edge cases (empty strings, boundary values)
   - Verify error messages and status codes

2. **Code Over Documentation Rule**
   - ALWAYS verify parameters against the actual source code, NOT documentation
   - Documentation is often outdated or incorrect - the code is the source of truth
   - For FluentCRM: Check `/wp-content/plugins/fluentcampaign-pro/app/Http/Controllers/`
   - For FluentBoards: Check `/wp-content/plugins/fluent-boards/app/Http/Controllers/`

3. **No Pro Feature Checks in Tests**
   - DO NOT check for Pro availability in test suites
   - Let tests fail naturally if Pro features aren't available
   - Remove all conditional `describe.skip()` or `test.skip()` based on Pro status
   - The actual WordPress abilities handle Pro checks internally

### Known Issues Fixed (October 2024)

#### FluentCRM Sequence Emails
**Issue 1: Non-existent delay_type parameter**
- Documentation showed `delay_type` parameter
- Actual implementation uses `settings['timings']['delay']` and `settings['timings']['delay_unit']`
- Fix: Updated Sequences.php to match actual FluentCRM structure

**Issue 2: Tool naming used hyphens instead of slashes**
- Tests called tools like `fluentcrm-add-sequence-email`
- Correct format: `fluentcrm/add-sequence-email`
- Impact: Tests were calling non-existent tools
- Fix: Updated all tool names to use slash format

**Issue 3: Empty email_body rejected**
- `wp_kses_post()` was rejecting empty strings
- FluentCRM allows empty email bodies
- Fix: Check for empty before sanitizing: `empty($args['email_body']) ? '' : wp_kses_post($args['email_body'])`

**Issue 4: No-change updates returned errors**
- Update operations required at least one field to change
- FluentCRM allows partial updates with no changes
- Fix: Return success with current data when `$update_data` is empty

### Field Mapping Verification Checklist

When implementing new abilities:

1. **Parameter Names**
   - [ ] Verified against actual controller method signatures
   - [ ] Checked actual database column names
   - [ ] Verified nested structure for serialized fields (like `settings`)

2. **Data Types**
   - [ ] Matched exact types used in source code
   - [ ] Verified enum values against actual validation logic
   - [ ] Checked if fields are nullable in database

3. **Update Operations**
   - [ ] Allow empty values where system permits
   - [ ] Support partial updates (no required fields in update)
   - [ ] Handle "no-change" updates gracefully

4. **Error Responses**
   - [ ] Match actual error messages from source
   - [ ] Use correct HTTP status codes
   - [ ] Provide helpful validation messages

### AbilityRegistry Pattern

FluentBoards uses a centralized `AbilityRegistry` class to manage tool lists:

```php
// classes/Adapters/FluentBoards/Servers/AbilityRegistry.php
class AbilityRegistry {
    public static function get_board_abilities(): array {
        return [
            'fluentboards/create-board',
            'fluentboards/list-boards',
            // ... all board tools
        ];
    }

    public static function get_task_abilities(): array {
        return [
            'fluentboards/create-task',
            // ... all task tools
        ];
    }
}
```

This eliminates duplication across server classes and ensures consistency.

### Tool Naming Architecture (Hyphen vs Slash)

**CRITICAL**: WordPress MCP uses dual-format naming with automatic normalization between layers.

#### Format by Layer

| Component | Format | Example | Why |
|-----------|--------|---------|-----|
| **Ability Registration** | `namespace/ability-name` | `'fluentcrm/create-subscriber'` | WordPress Abilities API requirement (enforced by regex) |
| **AbilityRegistry Arrays** | `namespace/ability-name` | `['fluentcrm/create-subscriber']` | Passed to `register_tools()` which expects ability names |
| **MCP Tool Storage** | `namespace-tool-name` | `'fluentcrm-create-subscriber'` | Automatic conversion by MCP adapter |
| **Test Tool Calls** | `namespace-tool-name` | `callTool('fluentcrm-create-subscriber')` | Must match MCP tool array index |

#### Architecture Flow

```
Ability Registration (slash)
  'fluentcrm/create-subscriber'
    ↓
MCP Adapter Conversion
  str_replace('/', '-', $name)  // RegisterAbilityAsMcpTool.php:72
    ↓
Tool Storage (hyphen)
  $tools['fluentcrm-create-subscriber']
    ↓
Test Calls (hyphen)
  callTool('fluentcrm-create-subscriber')
    ↓
Tool Lookup
  $tools[$tool_name] // Direct array access - expects hyphen format
```

#### Rules

✅ **DO**:
- Register abilities with slash format: `wp_register_ability('fluentcrm/create-subscriber', $args)`
- Use slash format in AbilityRegistry arrays (they're ability names)
- Call tools with hyphen format in tests: `callTool('fluentcrm-create-subscriber')`

❌ **DON'T**:
- Register abilities with hyphens (validation fails)
- Use hyphens in AbilityRegistry arrays (breaks tool registration)
- Call tools with slash format in tests (tool lookup fails - causes 421 test failures)

#### Technical Details

**Why Both Formats Exist**:
- **Abilities API** (slash): PHP namespace conventions, WordPress registry patterns
- **MCP Protocol** (hyphen): JSON-RPC compatibility, CLI conventions, WordPress REST API patterns
- **Conversion**: `RegisterAbilityAsMcpTool::make()` at `mcp-adapter/RegisterAbilityAsMcpTool.php:72`

**Tool Lookup** (no normalization):
```php
// File: mcp-adapter/McpServer.php:502-503
public function get_tool( string $tool_name ): ?McpTool {
    return $this->tools[ $tool_name ] ?? null;  // Direct array access
}
```

### Tool Registration Validation

When registering tools, verify:

```php
// ✅ CORRECT: Match AbilityRegistry exactly
$tools = AbilityRegistry::get_board_abilities();
// Returns: ['fluentboards/create-board', 'fluentboards/list-boards', ...]

// ❌ WRONG: Hardcoded list that gets out of sync
$tools = [
    'fluentboards/create-board',
    'fluentboards/list-boards',
];
```

## Important File Locations

- Abilities API docs: `vendor/wordpress/mcp-adapter/docs/guides/creating-abilities.md`
- MCP Adapter docs: `vendor/wordpress/mcp-adapter/README.md`
- MCP Client examples: `examples/client-example.php`
- Test configuration: `tests/phpunit.xml`
- Coding standards: `.phpcs.xml.dist`
- E2E test suite: `tests/e2e/` (Jest with axios)