# Test-Driven Development Guide for MCP Integrations

**Build WordPress plugin integrations using Test-Driven Development (TDD) principles**

---

## Overview

This guide outlines a TDD approach for building MCP adapters where:
1. Tests are written **first** based on validated API shapes
2. Tests fail initially (tools don't exist)
3. Abilities are implemented to make tests pass
4. Tests serve as living documentation

**Benefits:**
- ✅ Ensures tools match actual plugin behavior
- ✅ Prevents regressions during development
- ✅ Creates comprehensive test coverage from day one
- ✅ Documentation that never goes stale (it's tested!)

---

## The TDD Cycle for MCP Integrations

### Phase 1: Discovery & Validation (RED)

**Goal:** Understand the plugin's actual API before writing any code

1. **Validate API Shapes** using [API Validation Guide](./API_VALIDATION_GUIDE.md)
   ```bash
   # Create validation script
   wp eval-file validate-[plugin-name].php
   ```

2. **Document Validated Shapes** in `tests/e2e/[plugin-name]/VALIDATED_SHAPES.md`
   - Actual model responses
   - Database schema
   - Relationship structures
   - Type inconsistencies
   - Edge cases and gotchas

3. **Plan Test Coverage**
   - List all abilities needed (CRUD operations per model)
   - Identify relationships and complex operations
   - Plan test data setup/teardown strategy

### Phase 2: Write Tests (RED)

**Goal:** Write tests based on validated shapes, expect them to fail

#### 2.1 Create Test Structure

```bash
tests/e2e/
├── [plugin-name]/
│   ├── abilities/
│   │   ├── model1.test.ts      # One file per model/feature
│   │   ├── model2.test.ts
│   │   └── relationships.test.ts
│   ├── VALIDATED_SHAPES.md     # Source of truth
│   └── README.md               # Test suite overview
└── utils/
    ├── test-config.ts          # Shared config
    └── mcp-client.ts           # Shared MCP client
```

#### 2.2 Write Test Template

```typescript
/**
 * E2E Tests for [Plugin Name] [Model] Abilities
 *
 * Tests all CRUD operations for [Model] based on validated API shapes.
 * See ../VALIDATED_SHAPES.md for response structure reference.
 *
 * Coverage: X abilities from [AbilityClass].php
 */

import { MCPClient, generateTestTitle } from "../../utils/mcp-client";
import { PLUGIN_CONFIG } from "../../utils/test-config";

describe("[Plugin Name] [Model]", () => {
  let mcp: MCPClient;
  const testResourceIds: number[] = [];

  beforeAll(() => {
    mcp = new MCPClient(
      PLUGIN_CONFIG.baseURL,
      PLUGIN_CONFIG.username,
      PLUGIN_CONFIG.password
    );
  });

  afterAll(async () => {
    // Cleanup all created resources
    for (const id of testResourceIds) {
      await mcp.callTool("[plugin-name]-delete-[model]", {
        id,
        confirm_delete: true,
      });
    }
  });

  describe("Create [Model]", () => {
    it("should create a new [model] with required fields", async () => {
      const result = await mcp.callTool("[plugin-name]-create-[model]", {
        field1: "value1",
        field2: "value2",
      });

      expect(result.success).toBe(true);
      expect(result.data.[model]).toBeDefined();

      // Validate based on VALIDATED_SHAPES.md
      expect(result.data.[model]).toMatchObject({
        field1: "value1",
        field2: "value2",
        id: expect.any(Number),
        created_at: expect.any(String),
      });

      testResourceIds.push(result.data.[model].id);
    });

    // More create tests based on validated shapes...
  });

  describe("Read [Model]", () => {
    // Read tests...
  });

  describe("Update [Model]", () => {
    // Update tests...
  });

  describe("Delete [Model]", () => {
    // Delete tests...
  });
});
```

#### 2.3 Write Tests Based on Validated Shapes

**Reference your VALIDATED_SHAPES.md:**

```typescript
// From VALIDATED_SHAPES.md:
// Create Board Response: { "id": 51, "title": "Test", "stages": [] }
// Key Finding: stages NOT populated on create

it("should create board but NOT include stages", async () => {
  const result = await mcp.callTool("plugin-create-board", {
    title: "Test Board",
  });

  expect(result.success).toBe(true);
  expect(result.data.board.id).toBeDefined();

  // Based on validated shape - stages is empty array
  expect(result.data.board.stages).toEqual([]);
});

it("should fetch board WITH stages using get-board", async () => {
  // Create board first
  const createResult = await mcp.callTool("plugin-create-board", {
    title: "Test Board",
  });
  const boardId = createResult.data.board.id;

  // Get board details (includes stages)
  const getResult = await mcp.callTool("plugin-get-board", {
    board_id: boardId,
  });

  expect(getResult.success).toBe(true);
  expect(getResult.data.board.stages).toBeDefined();

  // Based on validated shape - now has stages array
  expect(Array.isArray(getResult.data.board.stages)).toBe(true);
});
```

#### 2.4 Test Edge Cases from Validation

```typescript
// From VALIDATED_SHAPES.md:
// Type Inconsistency: created_by is 0 (int) on create, "0" (string) on fetch

it("should handle type inconsistency in created_by field", async () => {
  const createResult = await mcp.callTool("plugin-create-model", {...});
  const fetchResult = await mcp.callTool("plugin-get-model", {
    id: createResult.data.model.id,
  });

  // Both should be truthy when converted to number
  expect(parseInt(createResult.data.model.created_by)).toBe(0);
  expect(parseInt(fetchResult.data.model.created_by)).toBe(0);
});
```

#### 2.5 Run Tests (Expect Failures)

```bash
npm run test:e2e -- tests/e2e/[plugin-name]/abilities/model.test.ts
```

**Expected Output:**
```
 FAIL  tests/e2e/[plugin-name]/abilities/model.test.ts
  ✕ should create a new model (X ms)
    Error: Tool 'plugin-create-model' not found
```

**This is GOOD!** Tests are failing because we haven't implemented the abilities yet.

### Phase 3: Implement Abilities (GREEN)

**Goal:** Make tests pass by implementing abilities

#### 3.1 Create Ability Class

```php
<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\PluginName\Abilities;

use MCP\Adapters\Adapters\PluginName\BaseAbility;

/**
 * [Plugin Name] [Model] Abilities
 *
 * Registers WordPress abilities for [model] management operations.
 */
class ModelName extends BaseAbility {

    protected function register_abilities(): void {
        $this->register_create_model();
        $this->register_get_model();
        $this->register_update_model();
        $this->register_delete_model();
    }

    private function register_create_model(): void {
        wp_register_ability(
            '[plugin-name]/create-[model]',
            [
                'label'               => 'Create [Plugin] [Model]',
                'description'         => 'Create a new [model]',
                'input_schema'        => [
                    'type'       => 'object',
                    'properties' => [
                        'field1' => [
                            'type'        => 'string',
                            'description' => 'Field 1 description',
                        ],
                        'field2' => [
                            'type'        => 'string',
                            'description' => 'Field 2 description',
                        ],
                    ],
                    'required'   => [ 'field1' ],
                ],
                'execute_callback'    => [ $this, 'execute_create_model' ],
                'permission_callback' => [ $this, 'can_manage_plugin' ],
                'meta'                => [
                    'category'    => '[plugin-name]',
                    'subcategory' => '[models]',
                ],
            ]
        );
    }

    public function execute_create_model( array $args ): array {
        try {
            // Use the plugin's actual model class
            $model = new \PluginNamespace\App\Models\ModelName();
            $created = $model->create([
                'field1' => sanitize_text_field( $args['field1'] ),
                'field2' => sanitize_text_field( $args['field2'] ?? '' ),
            ]);

            return $this->get_success_response(
                [
                    'model' => $created->toArray(),
                ],
                'Model created successfully'
            );
        } catch ( \Exception $e ) {
            return $this->get_error_response(
                'Failed to create model: ' . $e->getMessage(),
                'create_failed'
            );
        }
    }

    // Implement other execute methods...
}
```

#### 3.2 Register Ability Class

```php
// In your adapter's bootstrap file
$model_abilities = new \MCP\Adapters\Adapters\PluginName\Abilities\ModelName();
$model_abilities->register();
```

#### 3.3 Run Tests Again

```bash
npm run test:e2e -- tests/e2e/[plugin-name]/abilities/model.test.ts
```

**Expected Output:**
```
 PASS  tests/e2e/[plugin-name]/abilities/model.test.ts
  ✓ should create a new model (45 ms)
  ✓ should fetch model with details (32 ms)
```

**Success!** Tests are now passing.

### Phase 4: Refactor (GREEN)

**Goal:** Improve code quality while keeping tests green

- Extract common logic to BaseAbility
- Add helper methods for data transformation
- Improve error handling
- Add PHPDoc comments
- Run `composer lint` and `composer format`

**After each refactor:**
```bash
npm run test:e2e  # Ensure tests still pass
```

---

## Test Patterns

### Pattern 1: Setup-Execute-Verify

```typescript
it("should [do something]", async () => {
  // Setup
  const testData = { field: "value" };

  // Execute
  const result = await mcp.callTool("plugin-do-something", testData);

  // Verify
  expect(result.success).toBe(true);
  expect(result.data).toMatchObject({
    // Expected shape from VALIDATED_SHAPES.md
  });
});
```

### Pattern 2: Test Data Isolation

```typescript
describe("Feature Tests", () => {
  let testResourceId: number;

  beforeAll(async () => {
    // Create test data
    const result = await mcp.callTool("plugin-create-resource", {...});
    testResourceId = result.data.resource.id;
  });

  afterAll(async () => {
    // Cleanup test data
    await mcp.callTool("plugin-delete-resource", {
      id: testResourceId,
      confirm_delete: true,
    });
  });

  it("should use isolated test data", async () => {
    // Test uses testResourceId
  });
});
```

### Pattern 3: Testing Relationships

```typescript
it("should associate child with parent", async () => {
  // Create parent
  const parent = await mcp.callTool("plugin-create-parent", {...});

  // Create child
  const child = await mcp.callTool("plugin-create-child", {...});

  // Associate them
  const result = await mcp.callTool("plugin-associate", {
    parent_id: parent.data.parent.id,
    child_id: child.data.child.id,
  });

  expect(result.success).toBe(true);

  // Verify relationship from validated schema
  // From VALIDATED_SHAPES.md: Relations use object_id and foreign_id
  expect(result.data.relation).toMatchObject({
    object_id: expect.any(Number),
    object_type: "parent",
    foreign_id: expect.any(Number),
  });
});
```

### Pattern 4: Testing Error Cases

```typescript
it("should return error for invalid ID", async () => {
  const result = await mcp.callTool("plugin-get-model", {
    id: 999999,  // Non-existent ID
  });

  expect(result.success).toBe(false);
  expect(result.message).toContain("not found");
});

it("should return error for missing required field", async () => {
  const result = await mcp.callTool("plugin-create-model", {
    // Missing required field
  });

  expect(result.success).toBe(false);
  expect(result.message).toContain("required");
});
```

---

## Test Organization

### By Feature Area

```
tests/e2e/[plugin-name]/abilities/
├── core-models.test.ts         # Main CRUD operations
├── relationships.test.ts       # Association tests
├── advanced-features.test.ts   # Complex workflows
└── edge-cases.test.ts          # Error handling, validation
```

### By Model (Recommended)

```
tests/e2e/[plugin-name]/abilities/
├── boards.test.ts              # Board CRUD + permissions
├── tasks.test.ts               # Task CRUD + assignments
├── labels.test.ts              # Label CRUD + associations
└── reporting.test.ts           # Analytics and reports
```

---

## Test Data Management

### Generating Unique Data

```typescript
// From utils/mcp-client.ts
export function generateTestTitle(prefix: string): string {
  return `${prefix} ${Date.now()}-${Math.random()}`;
}

export function generateTestEmail(): string {
  return `test-${Date.now()}@example.com`;
}

// Usage in tests
it("should create unique resource", async () => {
  const result = await mcp.callTool("plugin-create-model", {
    title: generateTestTitle("Test Model"),
    email: generateTestEmail(),
  });
  // ...
});
```

### Tracking Test Resources

```typescript
describe("Model Tests", () => {
  const testModelIds: number[] = [];
  const testRelatedIds: number[] = [];

  afterAll(async () => {
    // Delete in reverse dependency order
    for (const id of testRelatedIds) {
      await mcp.callTool("plugin-delete-related", { id });
    }
    for (const id of testModelIds) {
      await mcp.callTool("plugin-delete-model", { id, confirm_delete: true });
    }
  });
});
```

---

## Best Practices

### DO

- ✅ **Validate first** - Write validation script before tests
- ✅ **Document shapes** - Create VALIDATED_SHAPES.md
- ✅ **Test reality** - Base tests on actual API behavior
- ✅ **Isolate data** - Create fresh test data in beforeAll
- ✅ **Clean up** - Delete all test resources in afterAll
- ✅ **Test errors** - Cover error cases and edge cases
- ✅ **Keep tests fast** - Use parallel execution where possible

### DON'T

- ❌ **Assume behavior** - Always validate against plugin's models
- ❌ **Use hardcoded IDs** - Create test data programmatically
- ❌ **Skip cleanup** - Always delete test resources
- ❌ **Test implementation** - Test API behavior, not internal code
- ❌ **Write brittle tests** - Use loose equality for type-inconsistent fields

---

## Running Tests

### Local Development

```bash
# Run all tests
npm run test:e2e

# Run specific suite
npm run test:e2e -- tests/e2e/[plugin-name]/abilities/model.test.ts

# Watch mode
npm run test:e2e:watch

# With coverage
npm run test:e2e:coverage
```

### CI/CD

```bash
# Set environment variables
export WP_TEST_PASSWORD="secure-app-password"
export WP_BASE_URL="http://localhost:8080"

# Run tests with CI flag
npm run test:e2e -- --ci --coverage
```

---

## Debugging Failing Tests

### 1. Check Validation

```bash
# Re-run validation script
wp eval-file validate-[plugin-name].php

# Compare with VALIDATED_SHAPES.md
# Has the plugin been updated?
```

### 2. Inspect Actual Response

```typescript
it("should [do something]", async () => {
  const result = await mcp.callTool("plugin-do-something", {...});

  // Add console.log to see actual response
  console.log("Actual response:", JSON.stringify(result, null, 2));

  expect(result.success).toBe(true);
});
```

### 3. Test Against REST API Directly

```bash
# Test the endpoint directly
curl -X POST "http://localhost/wp-json/[prefix]/v1/[endpoint]" \
  -u "admin:password" \
  -H "Content-Type: application/json" \
  -d '{"field": "value"}' | jq '.'
```

### 4. Check WordPress Debug Log

```bash
tail -f wp-content/debug.log
```

---

## See Also

- [API Validation Guide](./API_VALIDATION_GUIDE.md) - Validate before building
- [Integration Workflow](./INTEGRATION_WORKFLOW.md) - Complete integration process
- [Testing Tips](../tests/e2e/TESTING_TIPS.md) - Common pitfalls and solutions

---

**Remember:** Tests are not just validation - they're living documentation of how the plugin actually works!
