# API Validation Guide

**Universal methodology for validating third-party plugin APIs before building MCP adapters**

---

## Overview

When integrating any WordPress plugin, **never assume** how its API works based on:
- Documentation (often outdated or incomplete)
- Logical inference ("it should work this way")
- Similar plugins (FluentCRM ≠ FluentBoards ≠ WooCommerce)
- Your adapter code expectations

**Always validate** against the actual plugin's models and database schema.

---

## The Validation Workflow

### 1. Discover Available Models

```bash
# Find all model classes in the plugin
find wp-content/plugins/[plugin-name] -name "*Model*.php" -o -name "*models*" -type d

# Or inspect the plugin's autoloader/bootstrap
grep -r "class.*Model" wp-content/plugins/[plugin-name]/app/Models/
```

### 2. Check Database Schema

```bash
# List all plugin tables
wp db query "SHOW TABLES LIKE 'wp_[plugin_prefix]%'"

# Describe each table structure
wp db query "DESCRIBE wp_[plugin_prefix]_[table_name]"

# Check indexes and relationships
wp db query "SHOW CREATE TABLE wp_[plugin_prefix]_[table_name]"
```

**Document:**
- Table names and prefixes
- Column names and types
- Relationships (foreign keys, join tables)
- Columns that DON'T exist (prevent SQL errors)

### 3. Test Model Operations

Create a validation script to interact with the plugin's models directly:

```php
<?php
// validate-[plugin-name].php
require_once __DIR__ . '/wp-load.php';

// Test creating an object
$model = new \PluginNamespace\App\Models\ModelName();
$created = $model->create([
    'field1' => 'value1',
    'field2' => 'value2'
]);

echo "Create Response:\n";
echo json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Test fetching with relationships
$with_relations = \PluginNamespace\App\Models\ModelName::with(['relation1', 'relation2'])
    ->find($created->id);

echo "With Relations:\n";
echo json_encode($with_relations->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Test update
$created->update(['field1' => 'new_value']);
$updated = $model->find($created->id);

echo "Update Response:\n";
echo json_encode($updated->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Cleanup
$created->delete();
```

**Run with WP-CLI:**
```bash
wp eval-file validate-[plugin-name].php
```

**Why WP-CLI?**
- ✅ Full WordPress environment (database, constants, autoloading)
- ✅ Direct model access without HTTP layer
- ✅ Can run complex multi-line PHP
- ✅ Faster than repeated API calls

### 4. Document Actual Shapes

Create a `VALIDATED_SHAPES.md` file for the plugin:

```markdown
# [Plugin Name] Validated API Shapes

**Last Updated:** YYYY-MM-DD
**Validation Method:** WP-CLI (`wp eval-file`)

## [Model Name]

### Create [Model] Response

**Validation Script:**
\`\`\`php
$model = new \PluginNamespace\App\Models\ModelName();
$created = $model->create([...]);
\`\`\`

**Actual Response:**
\`\`\`json
{
  "field1": "value",
  "field2": 123,
  "created_at": "2025-10-04T17:34:53+00:00"
}
\`\`\`

**Key Findings:**
- ✅ Field X exists and is type Y
- ❌ Field Z does NOT exist (assumed but missing)
- ⚠️ Field A is NULL by default (not empty array)
- ⚠️ Relations B are NOT populated on create

### Get [Model] with Relations

**Validation Script:**
\`\`\`php
$with_relations = \PluginNamespace\App\Models\ModelName::with(['relation'])
    ->find($id);
\`\`\`

**Actual Response:**
\`\`\`json
{
  "field1": "value",
  "relation": []
}
\`\`\`

**Key Findings:**
- ✅ Relation is included when explicitly requested
- ⚠️ Empty relations return empty array, not null
```

### 5. Test Edge Cases

```php
// Test NULL handling
$created = $model->create(['required_field' => 'value']); // optional fields?

// Test default values
echo "Defaults: " . json_encode($created->toArray()) . "\n";

// Test type coercion
$created = $model->create(['integer_field' => '123']); // String or int?
echo "Field type: " . gettype($created->integer_field) . "\n";

// Test relationship direction
$relation = new \PluginNamespace\App\Models\Relation();
$rel = $relation->create([
    'object_id' => $parent_id,
    'object_type' => 'parent',
    'foreign_id' => $child_id
]);
// Which is object, which is foreign? Document this!
```

### 6. Validate REST API Differences

Sometimes the REST API returns different structures than the model:

```bash
# Test the actual REST API
curl -X POST "http://localhost/wp-json/[plugin-prefix]/v1/[endpoint]" \
  -u "admin:app-password" \
  -H "Content-Type: application/json" \
  -d '{"field": "value"}' | jq '.'
```

**Compare:**
- Model response vs REST API response
- Field name differences (snake_case vs camelCase)
- Additional wrappers or metadata
- Error response formats

---

## Common Validation Discoveries

### Discovery 1: Relations NOT Included on Create

**Example:** FluentBoards boards don't include `stages` array on creation.

**Impact:**
```typescript
// ❌ FAILS - stages is undefined
const board = await createBoard();
const stageId = board.data.board.stages[0].id;

// ✅ WORKS - fetch details separately
const board = await createBoard();
const details = await getBoard(board.data.board.id);
const stageId = details.data.board.stages[0].id;
```

### Discovery 2: Missing Database Columns

**Example:** FluentBoards `wp_fbs_relations` table has NO `foreign_type` column.

**Impact:**
```php
// ❌ SQL ERROR - Column doesn't exist
$relation->where('foreign_type', 'label')->delete();

// ✅ WORKS - Use correct schema
$relation->where('object_type', 'task')
         ->where('foreign_id', $label_id)
         ->delete();
```

### Discovery 3: Type Inconsistencies

**Example:** IDs alternate between int and string.

**Impact:**
```typescript
// Create returns: {"created_by": 0}
// Fetch returns: {"created_by": "0"}

// ❌ FAILS with strict equality
expect(board.created_by).toBe(0);

// ✅ WORKS with loose equality or parseInt
expect(parseInt(board.created_by)).toBe(0);
```

### Discovery 4: NULL vs Empty Values

**Example:** Some fields are `null`, others are `[]` or `""`.

**Impact:**
```typescript
// Task with no stage
{"stage": null}  // null, not {}

// Board with no stages
{"stages": []}  // empty array, not null

// Check both!
if (data.stage?.id) { ... }
if (data.stages?.length > 0) { ... }
```

---

## Validation Checklist

For each model in the plugin:

- [ ] **Database Schema**
  - [ ] Table name and prefix
  - [ ] All column names and types
  - [ ] Indexes and foreign keys
  - [ ] Columns that DON'T exist

- [ ] **Create Operation**
  - [ ] Required fields
  - [ ] Optional fields and defaults
  - [ ] Auto-generated fields (slug, timestamps, etc.)
  - [ ] Relations included or excluded

- [ ] **Read Operation**
  - [ ] Fields in basic fetch
  - [ ] Fields with `->with()` relationships
  - [ ] Difference between model and REST API response

- [ ] **Update Operation**
  - [ ] Which fields are updatable
  - [ ] Validation rules
  - [ ] Changed fields in response

- [ ] **Delete Operation**
  - [ ] Hard delete or soft delete
  - [ ] Cascade behavior
  - [ ] Relations cleanup

- [ ] **Relationships**
  - [ ] Relationship table structure
  - [ ] Direction (object vs foreign)
  - [ ] Query patterns for adding/removing

---

## Integration-Specific Notes

### For Existing Integrations

**Goal:** Ensure tools use everything available and nothing else

1. Run validation script for all models
2. Compare with existing ability code
3. Update abilities to match reality:
   - Add missing fields to responses
   - Remove fields that don't exist
   - Fix incorrect database queries
4. Update tests to match actual behavior

### For New Integrations (TDD Approach)

**Goal:** Build tools based on validated shapes from the start

1. **Discovery Phase**
   - Run validation scripts
   - Document all shapes in `VALIDATED_SHAPES.md`
   - Create quick reference for model relationships

2. **Test-First Development**
   - Write tests based on validated shapes
   - Tests will fail (tools don't exist yet)
   - Implement abilities to make tests pass

3. **Verification Phase**
   - Run full test suite
   - Compare test expectations with validation docs
   - Fix any discrepancies

---

## Best Practices

### DO

- ✅ **Validate before coding** - 5 minutes of validation saves hours of debugging
- ✅ **Document everything** - Create VALIDATED_SHAPES.md with actual responses
- ✅ **Test edge cases** - NULL values, empty arrays, type coercion
- ✅ **Check schema** - Database structure is source of truth
- ✅ **Use WP-CLI** - Direct model access, no HTTP layer interference

### DON'T

- ❌ **Trust documentation** - It's often outdated or incomplete
- ❌ **Assume similarity** - Each plugin has unique quirks
- ❌ **Skip validation** - "It should work" is not a validation method
- ❌ **Validate only once** - Plugins update, re-validate after major updates

---

## Quick Reference Commands

```bash
# List all plugin tables
wp db query "SHOW TABLES LIKE 'wp_[prefix]%'"

# Describe table structure
wp db query "DESCRIBE wp_[prefix]_[table]"

# Test model directly
wp eval-file validate-script.php

# Check class methods
wp eval "
\$model = new \Namespace\ModelName();
print_r(get_class_methods(\$model));
"

# Test REST API
curl -X POST "http://localhost/wp-json/[prefix]/v1/[endpoint]" \
  -u "admin:password" \
  -H "Content-Type: application/json" \
  -d '{"field": "value"}' | jq '.'
```

---

## See Also

- [Test Development Guide](./TEST_DEVELOPMENT_GUIDE.md) - TDD approach for integrations
- [Integration Workflow](./INTEGRATION_WORKFLOW.md) - Step-by-step process
- [FluentBoards Validated Shapes](../tests/e2e/fluentboards/VALIDATED_SHAPES.md) - Real example

---

**Remember:** The 5 minutes spent validating structures saves hours of debugging failing tests and broken adapters!
