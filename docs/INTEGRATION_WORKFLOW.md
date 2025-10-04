# WordPress Plugin Integration Workflow

**Complete step-by-step process for integrating WordPress plugins with MCP Adapters**

---

## Overview

This workflow combines API validation and test-driven development to create robust, reliable MCP integrations for WordPress plugins.

**Process Summary:**
1. **Discovery** - Explore and validate the plugin's API
2. **Planning** - Define scope and test coverage
3. **Development** - Build abilities using TDD
4. **Verification** - Ensure quality and completeness
5. **Documentation** - Create guides for users

---

## Phase 1: Discovery & Validation

**Goal:** Understand the plugin's actual API before writing any code

### Step 1.1: Plugin Analysis

```bash
# Find plugin directory
ls wp-content/plugins/ | grep [plugin-name]

# Explore structure
tree -L 3 wp-content/plugins/[plugin-name]

# Find models
find wp-content/plugins/[plugin-name] -name "*Model*.php"
find wp-content/plugins/[plugin-name] -name "*models*" -type d

# Check for REST API
grep -r "register_rest_route" wp-content/plugins/[plugin-name]
```

**Document:**
- Plugin version
- Namespace/prefix
- Model locations
- Existing REST API endpoints
- Database table prefix

### Step 1.2: Database Schema Discovery

```bash
# List plugin tables
wp db query "SHOW TABLES LIKE 'wp_[plugin_prefix]%'"

# Describe each table
wp db query "DESCRIBE wp_[prefix]_[table1]"
wp db query "DESCRIBE wp_[prefix]_[table2]"
# ... for all tables

# Check relationships
wp db query "SHOW CREATE TABLE wp_[prefix]_relations"
```

**Document in `VALIDATED_SHAPES.md`:**
- All table names
- Column names and types
- Primary/foreign keys
- Indexes
- **Columns that DON'T exist** (critical!)

### Step 1.3: Model Validation

Create `validate-[plugin-name].php` in WordPress root:

```php
<?php
require_once __DIR__ . '/wp-load.php';

echo "=== [PLUGIN NAME] API VALIDATION ===\n\n";

// Test each model
$models_to_test = [
    'ModelName1' => '\PluginNamespace\App\Models\ModelName1',
    'ModelName2' => '\PluginNamespace\App\Models\ModelName2',
];

foreach ($models_to_test as $name => $class) {
    echo "--- Testing $name ---\n";

    // Create
    $model = new $class();
    $created = $model->create([
        'field1' => 'test value',
        'field2' => 123,
    ]);
    echo "Create:\n" . json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    // Fetch with relations
    $with_relations = $class::with(['relation1', 'relation2'])->find($created->id);
    echo "With Relations:\n" . json_encode($with_relations->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    // Update
    $created->update(['field1' => 'updated']);
    echo "Updated:\n" . json_encode($model->find($created->id)->toArray(), JSON_PRETTY_PRINT) . "\n\n";

    // Cleanup
    $created->delete();
    echo "Cleaned up\n\n";
}

echo "=== VALIDATION COMPLETE ===\n";
```

Run validation:
```bash
wp eval-file validate-[plugin-name].php > validation-output.json
```

**Document in `tests/e2e/[plugin-name]/VALIDATED_SHAPES.md`:**
- Actual response structures for each operation
- Fields that exist vs assumptions
- Type inconsistencies
- NULL vs empty array patterns
- Relations included/excluded on create

### Step 1.4: Edge Case Validation

```php
// Test optional fields
$created = $model->create(['required_only' => 'value']);
echo "With defaults:\n" . json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n";

// Test NULL handling
$created = $model->create(['nullable_field' => null]);
echo "NULL field:\n" . json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n";

// Test type coercion
$created = $model->create(['int_field' => '123']);
echo "Type: " . gettype($created->int_field) . "\n";

// Test relationship direction
$relation = new \PluginNamespace\App\Models\Relation();
$rel = $relation->create([
    'object_id' => $parent_id,
    'object_type' => 'parent',
    'foreign_id' => $child_id
]);
echo "Relation structure:\n" . json_encode($rel->toArray(), JSON_PRETTY_PRINT) . "\n";
```

**Document:**
- Default values for optional fields
- NULL vs empty string/array behavior
- Type casting inconsistencies
- Relationship table structure (object vs foreign direction)

---

## Phase 2: Planning

**Goal:** Define integration scope and test coverage

### Step 2.1: Define Abilities

Create ability checklist in `docs/[plugin-name]-abilities.md`:

```markdown
# [Plugin Name] MCP Abilities

## Model1 (CRUD)
- [ ] create-model1
- [ ] list-model1
- [ ] get-model1
- [ ] update-model1
- [ ] delete-model1

## Model2 (CRUD)
- [ ] create-model2
- [ ] list-model2
- [ ] get-model2
- [ ] update-model2
- [ ] delete-model2

## Relationships
- [ ] associate-model1-model2
- [ ] dissociate-model1-model2
- [ ] get-model1-model2s

## Advanced Features
- [ ] bulk-operation
- [ ] reporting
- [ ] analytics

**Total:** X abilities planned
```

### Step 2.2: Plan Test Coverage

```markdown
# Test Coverage Plan

## Core Operations (tests/e2e/[plugin-name]/abilities/)
- [ ] model1.test.ts - CRUD + permissions
- [ ] model2.test.ts - CRUD + validations
- [ ] relationships.test.ts - Associations
- [ ] advanced.test.ts - Complex workflows

## Edge Cases
- [ ] Null handling
- [ ] Type coercion
- [ ] Empty relations
- [ ] Error responses

## Integration Tests
- [ ] Multi-step workflows
- [ ] Permission boundaries
- [ ] Data cleanup

**Estimated:** ~100 test cases
```

### Step 2.3: Set Up Project Structure

```bash
# Create ability classes directory
mkdir -p classes/Adapters/[PluginName]/Abilities

# Create test directory
mkdir -p tests/e2e/[plugin-name]/abilities

# Create documentation
touch tests/e2e/[plugin-name]/VALIDATED_SHAPES.md
touch tests/e2e/[plugin-name]/README.md
```

---

## Phase 3: Test-Driven Development

**Goal:** Build abilities following TDD red-green-refactor cycle

### Step 3.1: Write Failing Tests (RED)

Based on `VALIDATED_SHAPES.md`, write tests for the first ability:

```typescript
// tests/e2e/[plugin-name]/abilities/model1.test.ts
import { MCPClient, generateTestTitle } from "../../utils/mcp-client";
import { PLUGIN_CONFIG } from "../../utils/test-config";

describe("[Plugin Name] Model1", () => {
  let mcp: MCPClient;
  const testIds: number[] = [];

  beforeAll(() => {
    mcp = new MCPClient(
      PLUGIN_CONFIG.baseURL,
      PLUGIN_CONFIG.username,
      PLUGIN_CONFIG.password
    );
  });

  afterAll(async () => {
    for (const id of testIds) {
      await mcp.callTool("[plugin]-delete-model1", {
        id,
        confirm_delete: true,
      });
    }
  });

  describe("Create Model1", () => {
    it("should create with required fields", async () => {
      const result = await mcp.callTool("[plugin]-create-model1", {
        field1: "value1",
        field2: "value2",
      });

      // Based on VALIDATED_SHAPES.md
      expect(result.success).toBe(true);
      expect(result.data.model1).toMatchObject({
        id: expect.any(Number),
        field1: "value1",
        field2: "value2",
        created_at: expect.any(String),
      });

      testIds.push(result.data.model1.id);
    });
  });
});
```

Run tests (expect failure):
```bash
npm run test:e2e -- tests/e2e/[plugin-name]/abilities/model1.test.ts
```

### Step 3.2: Implement Ability (GREEN)

```php
<?php
// classes/Adapters/[PluginName]/Abilities/Model1.php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\PluginName\Abilities;

use MCP\Adapters\Adapters\PluginName\BaseAbility;

class Model1 extends BaseAbility {

    protected function register_abilities(): void {
        $this->register_create_model1();
        // ... other abilities
    }

    private function register_create_model1(): void {
        wp_register_ability(
            '[plugin]/create-model1',
            [
                'label'               => 'Create [Plugin] Model1',
                'description'         => 'Create a new model1',
                'input_schema'        => [
                    'type'       => 'object',
                    'properties' => [
                        'field1' => [
                            'type'        => 'string',
                            'description' => 'Field 1',
                        ],
                        'field2' => [
                            'type'        => 'string',
                            'description' => 'Field 2',
                        ],
                    ],
                    'required'   => [ 'field1' ],
                ],
                'execute_callback'    => [ $this, 'execute_create_model1' ],
                'permission_callback' => [ $this, 'can_manage' ],
                'meta'                => [
                    'category'    => '[plugin-name]',
                    'subcategory' => 'model1',
                ],
            ]
        );
    }

    public function execute_create_model1( array $args ): array {
        try {
            $model = new \PluginNamespace\App\Models\Model1();
            $created = $model->create([
                'field1' => sanitize_text_field( $args['field1'] ),
                'field2' => sanitize_text_field( $args['field2'] ?? '' ),
            ]);

            return $this->get_success_response(
                [ 'model1' => $created->toArray() ],
                'Model1 created successfully'
            );
        } catch ( \Exception $e ) {
            return $this->get_error_response(
                'Failed to create model1: ' . $e->getMessage(),
                'create_failed'
            );
        }
    }

    // Permission callback
    public function can_manage( array $args = [] ): bool {
        if ( 0 === get_current_user_id() ) {
            return false;
        }
        return current_user_can( 'manage_options' );
    }
}
```

Register the ability:
```php
// In adapter bootstrap
$model1_abilities = new \MCP\Adapters\Adapters\PluginName\Abilities\Model1();
$model1_abilities->register();
```

Run tests (expect success):
```bash
npm run test:e2e -- tests/e2e/[plugin-name]/abilities/model1.test.ts
```

### Step 3.3: Refactor (GREEN)

- Extract common logic to `BaseAbility`
- Add helper methods
- Improve error messages
- Add PHPDoc comments
- Run linters

```bash
composer lint
composer format
npm run test:e2e  # Ensure still passing
```

### Step 3.4: Repeat for All Abilities

For each ability:
1. Write failing test based on `VALIDATED_SHAPES.md`
2. Implement ability to make test pass
3. Refactor while keeping tests green
4. Move to next ability

---

## Phase 4: Verification

**Goal:** Ensure quality and completeness

### Step 4.1: Run Full Test Suite

```bash
# All tests
npm run test:e2e

# With coverage
npm run test:e2e:coverage

# Check coverage report
open coverage/lcov-report/index.html
```

**Quality Gates:**
- [ ] All tests passing
- [ ] Coverage > 80%
- [ ] No hardcoded test data
- [ ] All test resources cleaned up
- [ ] No skipped/disabled tests

### Step 4.2: Code Quality Checks

```bash
# PHP linting
composer lint

# PHP formatting
composer format

# TypeScript linting (if configured)
npm run lint
```

### Step 4.3: Manual Testing

```bash
# Test actual MCP server
npx @automattic/mcp-wordpress-remote

# Or test via curl
curl -X POST "http://localhost/wp-json/mcp-adapters/v1/[plugin]" \
  -u "admin:password" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "[plugin]-create-model1",
      "arguments": {"field1": "test"}
    },
    "id": 1
  }' | jq '.'
```

### Step 4.4: Cross-Reference Validation

Compare implementation with validation docs:

```bash
# Re-run validation script
wp eval-file validate-[plugin-name].php

# Compare with VALIDATED_SHAPES.md
# Verify all documented behaviors are tested
# Check for any new fields or changes
```

---

## Phase 5: Documentation

**Goal:** Create comprehensive guides for users

### Step 5.1: Update Ability Checklist

```markdown
# [Plugin Name] MCP Abilities

## Model1 (CRUD)
- [x] create-model1 ✅ Tested
- [x] list-model1 ✅ Tested
- [x] get-model1 ✅ Tested
- [x] update-model1 ✅ Tested
- [x] delete-model1 ✅ Tested

**Status:** 5/5 implemented, 100% tested
```

### Step 5.2: Create User Guide

```markdown
# [Plugin Name] MCP Integration Guide

## Available Tools

### create-model1
Creates a new model1 instance.

**Parameters:**
- `field1` (string, required) - Description
- `field2` (string, optional) - Description

**Example:**
\`\`\`json
{
  "name": "[plugin]-create-model1",
  "arguments": {
    "field1": "value",
    "field2": "value"
  }
}
\`\`\`

**Response:**
\`\`\`json
{
  "success": true,
  "message": "Model1 created successfully",
  "data": {
    "model1": {
      "id": 123,
      "field1": "value",
      "created_at": "2025-10-04T..."
    }
  }
}
\`\`\`
```

### Step 5.3: Update README

```markdown
# MCP Adapters

## Supported Plugins

### [Plugin Name]
**Version:** X.Y.Z
**Abilities:** X total
**Test Coverage:** 95%
**Status:** ✅ Stable

**Quick Start:**
\`\`\`bash
npx @automattic/mcp-wordpress-remote
\`\`\`

See [Plugin Name Integration Guide](./docs/[plugin-name]-guide.md) for details.
```

---

## Quality Checklist

### Pre-Release Verification

- [ ] **API Validation**
  - [ ] All models validated with WP-CLI
  - [ ] Database schema documented
  - [ ] Edge cases tested
  - [ ] VALIDATED_SHAPES.md complete

- [ ] **Code Quality**
  - [ ] All abilities registered
  - [ ] Permission callbacks correct
  - [ ] Error handling comprehensive
  - [ ] PHPCS/WPCS compliant
  - [ ] PHPDoc complete

- [ ] **Test Coverage**
  - [ ] All CRUD operations tested
  - [ ] Relationships tested
  - [ ] Error cases covered
  - [ ] Edge cases handled
  - [ ] Coverage > 80%

- [ ] **Documentation**
  - [ ] User guide created
  - [ ] API reference complete
  - [ ] Examples provided
  - [ ] README updated

- [ ] **Integration**
  - [ ] Works with MCP remote server
  - [ ] Authentication functional
  - [ ] Error messages clear
  - [ ] Performance acceptable

---

## Maintenance

### Updating for Plugin Changes

When the plugin updates:

1. **Re-validate API**
   ```bash
   wp eval-file validate-[plugin-name].php
   ```

2. **Compare with VALIDATED_SHAPES.md**
   - New fields?
   - Changed types?
   - Removed fields?

3. **Update Tests**
   - Add tests for new features
   - Update expectations for changes
   - Remove tests for deprecated features

4. **Update Abilities**
   - Add new fields to schemas
   - Update execute methods
   - Maintain backward compatibility when possible

5. **Re-run Test Suite**
   ```bash
   npm run test:e2e
   ```

---

## Common Issues & Solutions

### Issue: Tests Fail After Plugin Update

**Solution:**
1. Re-run validation script
2. Check VALIDATED_SHAPES.md for changes
3. Update test expectations
4. Update ability implementations

### Issue: Permission Errors in REST API

**Solution:**
```php
// ❌ WRONG - doesn't work in REST API
if ( ! is_user_logged_in() ) {
    return false;
}

// ✅ CORRECT - works everywhere
if ( 0 === get_current_user_id() ) {
    return false;
}
```

### Issue: Type Inconsistencies

**Solution:**
```typescript
// Use loose equality or parseInt()
expect(parseInt(result.data.field)).toBe(expected);
```

### Issue: SQL Errors with Relations

**Solution:**
1. Check VALIDATED_SHAPES.md for actual schema
2. Verify column names exist in database
3. Use correct object/foreign direction

---

## See Also

- [API Validation Guide](./API_VALIDATION_GUIDE.md) - Validation methodology
- [Test Development Guide](./TEST_DEVELOPMENT_GUIDE.md) - TDD approach
- [Testing Tips](../tests/e2e/TESTING_TIPS.md) - Common pitfalls

---

**Remember:** Validate first, test first, implement last - this order prevents hours of debugging!
