# E2E Testing Tips for WordPress MCP Adapters

> **📚 Part of the Integration Guide Series:**
> - [API Validation Guide](../../docs/API_VALIDATION_GUIDE.md) - Universal validation methodology
> - [Test Development Guide](../../docs/TEST_DEVELOPMENT_GUIDE.md) - TDD approach for integrations
> - [Integration Workflow](../../docs/INTEGRATION_WORKFLOW.md) - Complete step-by-step process
> - **Testing Tips** (this document) - Common pitfalls and solutions

## Validating API Response Structures

**Golden Rule:** Always validate against the actual plugin's data structures, not just what our wrapper code expects.

### Problem

When writing E2E tests, it's easy to make assumptions about:
- Response structure formats
- Database column names
- Relationship table schemas
- Default values on object creation

These assumptions often come from:
- Documentation (which may be outdated)
- Similar plugins (FluentCRM ≠ FluentBoards)
- Logical inference (what "should" happen)
- Our own adapter code (which may be wrong)

### Solution: Direct Model Validation

Use WP-CLI to run PHP code that directly interacts with the target plugin's models to see actual structures.

#### Step 1: Create a Validation Script

Create a temporary PHP file to test the actual plugin models:

```php
<?php
// validate-plugin.php
require_once __DIR__ . '/wp-load.php';

// Test creating objects and inspect their structure
$board = new \FluentBoards\App\Models\Board();
$created = $board->create([
    'title' => 'Test Board',
    'type' => 'to-do'
]);
echo "Board Structure:\n";
echo json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Test relationships
$board_with_relations = \FluentBoards\App\Models\Board::with(['stages'])->find($created->id);
echo "Board with Relations:\n";
echo json_encode($board_with_relations->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Cleanup
$created->delete();
```

#### Step 2: Run via WP-CLI

```bash
cd /path/to/wordpress
./wp-cli-direct.sh wp eval-file validate-plugin.php
```

**Why WP-CLI?**
- Proper WordPress environment (database connection, constants, autoloading)
- Can run complex multi-line PHP
- Access to all plugin models and functions
- No HTTP layer interference

#### Step 3: Compare with Your Expectations

The output will show you:
- ✅ Actual column names
- ✅ Actual response structures  
- ✅ What gets populated on create vs. what requires explicit fetch
- ✅ Relationship table schemas
- ✅ Default values vs. null values

### Real Example: FluentBoards Labels

**Our Assumption:** Creating a board returns stages in the response.

**Reality Check:**
```bash
./wp-cli-direct.sh wp eval-file validate-fluentboards.php
```

**Output:**
```json
{
    "id": 51,
    "title": "Test Board",
    "stages": []  // ❌ Empty, not populated!
}
```

**Impact:** Tests trying to use `boardResult.data.board.stages[0].id` would fail with "Cannot read properties of undefined".

**Fix:** Explicitly fetch board details with `get-board` tool after creation, which includes stages.

### Real Example: FluentBoards Relations Table

**Our Assumption (from debug log errors):** Relations table has `foreign_type` column.

**Reality Check:**
```php
$relation = new \FluentBoards\App\Models\Relation();
$relation_created = $relation->create([
    'board_id' => $board_id,
    'object_id' => $task_id,
    'object_type' => 'task',
    'foreign_id' => $label_id,
    'type' => 'label'  // This might fail!
]);
```

**Output:**
```json
{
    "object_id": 77,
    "object_type": "task",
    "foreign_id": 547,
    "id": 138
    // ❌ No 'type', 'foreign_type', or 'board_id' columns!
}
```

**Impact:** Our delete code was querying `where('foreign_type', 'label')` causing SQL errors.

**Fix:** Use correct schema: `object_type='task'`, `object_id=task_id`, `foreign_id=label_id`.

## Testing Best Practices

### 1. Test Data Isolation

**✅ DO:** Create your own test data in `beforeAll`:
```typescript
beforeAll(async () => {
  const boardResult = await mcp.callTool("fluentboards-create-board", {
    title: generateTestTitle("Test Board"),
  });
  testBoardId = boardResult.data.board.id;
});
```

**❌ DON'T:** Use hardcoded existing IDs:
```typescript
const testBoardId = 123; // Fragile, environment-dependent
```

### 2. Clean Up After Yourself

**✅ DO:** Delete all created resources in `afterAll`:
```typescript
afterAll(async () => {
  // Clean up in reverse order (dependencies first)
  for (const labelId of testLabelIds) {
    await mcp.callTool("fluentboards-delete-label", {
      board_id: testBoardId,
      label_id: labelId
    });
  }
  
  await mcp.callTool("fluentboards-delete-board", {
    board_id: testBoardId,
    confirm_delete: true
  });
});
```

### 3. Environment Variables for Auth

**✅ DO:** Use environment variables:
```typescript
const WP_TEST_PASSWORD = process.env.WP_TEST_PASSWORD || '';
```

**Run tests with:**
```bash
WP_TEST_PASSWORD="your-app-password" npm test
```

**✅ ALSO DO:** Read from `.mcp.json` for consistency:
- Tests use same credentials as MCP servers
- Single source of truth
- Easy to update

### 4. Validate Actual API Behavior, Not Assumptions

**❌ DON'T:**
```typescript
// Assuming API returns error for duplicates
expect(result.data.action).toBe("already_assigned");
```

**✅ DO:**
```typescript
// Check what API actually returns
const result = await mcp.callTool("fluentboards-add-label-to-task", {...});
console.log("Actual API response:", result);

// Then write test based on reality
expect(result.data.action).toBe("assigned"); // API treats as idempotent
```

### 5. Handle Test Interdependencies

**Problem:** Tests in different suites can interfere with each other.

**Example:** "Remove Label from Task" suite removes labels, then "Get Task Labels" suite expects labels to exist.

**Solution:** Re-add test data in specific tests that need it:
```typescript
it("should get all labels assigned to task", async () => {
  // Ensure test data exists (may have been removed by previous test)
  await mcp.callTool("fluentboards-add-label-to-task", {
    board_id: testBoardId,
    task_id: testTaskId,
    label_id: labelId1,
  });

  const result = await mcp.callTool("fluentboards-get-task-labels", {...});
  expect(result.success).toBe(true);
});
```

## Quick Validation Commands

### Check Database Schema
```bash
./wp-cli-direct.sh wp db query "DESCRIBE wp_fbs_relations"
```

### Inspect Model Methods
```bash
./wp-cli-direct.sh wp eval "
\$model = new \FluentBoards\App\Models\Relation();
print_r(get_class_methods(\$model));
"
```

### Test API Directly
```bash
curl -X POST "http://mcp.local/wp-json/mcp-adapters/v1/fluentboards/mcp" \
  -u "admin:your-app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "tools/call",
    "params": {
      "name": "fluentboards-create-board",
      "arguments": {"title": "Test"}
    },
    "id": 1
  }' | jq '.'
```

## Common Pitfalls

### 1. PHP Opcache
**Symptom:** Changes to PHP files don't take effect.

**Fix:**
```bash
./wp-cli-direct.sh wp eval "if (function_exists('opcache_reset')) { opcache_reset(); }"
```

### 2. Import Paths in TypeScript
**Symptom:** `Cannot find module '../../../utils/test-config'`

**Fix:** Count the directory levels correctly:
```typescript
// From: tests/e2e/fluentboards/abilities/labels.test.ts
// To:   tests/e2e/utils/test-config.ts
import { FLUENTBOARDS_CONFIG } from "../../utils/test-config"; // ✅
import { FLUENTBOARDS_CONFIG } from "../../../utils/test-config"; // ❌
```

### 3. Authentication in REST API Context
**Symptom:** `is_user_logged_in()` returns false even with valid Application Password.

**Why:** `is_user_logged_in()` checks session cookies, not REST API authentication.

**Fix:** Use `get_current_user_id() !== 0` instead:
```php
public function can_manage_boards( array $args = [] ): bool {
    // ✅ Works for both regular login AND REST API
    if ( 0 === get_current_user_id() ) {
        return false;
    }
    return current_user_can( 'manage_options' );
}
```

### 4. Permission Callback Signatures
**Symptom:** `TypeError: Argument #1 ($board_id) must be of type ?int, array given`

**Why:** Abilities API passes the full arguments array to permission callbacks, not individual parameters.

**Fix:**
```php
// ❌ WRONG
public function can_manage_boards( ?int $board_id = null ): bool

// ✅ CORRECT  
public function can_manage_boards( array $args = [] ): bool {
    $board_id = $args['board_id'] ?? null;
    // ...
}
```

## Adapter Code Patterns

### Use `toArray()` Instead of Manual Field Selection

**❌ BAD - Manual field selection:**
```php
return $this->get_success_response([
    'stage' => [
        'id' => $stage->id,
        'title' => $stage->title,
        'position' => $stage->position,
        // ... manually listing every field
    ]
]);
```

**Issues:**
- Missing fields (e.g., `type` field)
- Not future-proof
- More code to maintain
- Inconsistent with model's actual structure

**✅ GOOD - Use model's toArray():**
```php
return $this->get_success_response([
    'stage' => $stage->toArray()
]);
```

**Benefits:**
- ✅ All model fields automatically included
- ✅ Future-proof when plugin adds new fields
- ✅ Less code to maintain
- ✅ Matches what validation discovers
- ✅ Consistent across adapters

**Real Example:**
- `Tasks.php` uses `$task->toArray()` ✅
- `Stages.php` manually selects fields ❌ (missing `type`)
- `Labels.php` manually selects fields ❌ (should check)

### When to Use Manual Selection

Only manually select fields when you need to:
1. **Transform data** (e.g., format dates differently)
2. **Hide sensitive data** (e.g., password hashes)
3. **Rename fields** for consistency
4. **Add computed fields** not in the model

Otherwise, prefer `->toArray()` for completeness.

## Summary

1. **Never assume** - always validate against actual plugin behavior
2. **Use WP-CLI** for quick model structure validation  
3. **Create test data** - don't rely on existing data
4. **Clean up** - delete all test resources
5. **Check the code** - but verify with actual API responses
6. **Use `toArray()`** - don't manually select fields unless necessary
7. **Document learnings** - save others from the same mistakes

The 5 minutes spent validating structures saves hours of debugging failing tests!

