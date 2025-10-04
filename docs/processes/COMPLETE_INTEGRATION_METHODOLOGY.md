# Complete Integration Methodology - Production-Ready Process

**Version**: 2.0 (Enhanced with Discovery-to-Analysis)
**Date**: 2025-10-04
**Based On**: FluentBoards (86% pass rate) + FluentCRM (10 models validated) success
**Status**: ✅ Production-Ready

---

## Executive Summary

This methodology combines **validation-first integration** with **systematic tooling discovery** to create complete, high-quality MCP adapters for WordPress plugins.

**Two Integration Modes**:
1. **New Integration** (2-5 days) - Complete plugin integration from scratch
2. **Existing Enhancement** (1-2 days) - Discover missing tools and improve quality

**Success Metrics**:
- ≥80% test pass rate on first run
- ≥75% API coverage (models/relationships/fields)
- ≥8.5/10 tool description quality
- Complete validation documentation

**Proven Results**:
- FluentBoards: 86% test pass, 18 tools, discovered 29 missing opportunities
- FluentCRM: 10 models validated in 1 day, complete gap analysis

---

## Table of Contents

### Part 1: New Integration Process
- [Phase 1: Discovery & Planning](#phase-1-discovery--planning)
- [Phase 2: Parallel Validation](#phase-2-parallel-validation)
- [Phase 3: Gap Analysis](#phase-3-gap-analysis)
- [Phase 4: Implementation](#phase-4-implementation)
- [Phase 5: Testing](#phase-5-testing)
- [Phase 6: Quality Refinement](#phase-6-quality-refinement)

### Part 2: Enhancement Process (Existing Integrations)
- [Shape Discovery & Validation](#shape-discovery--validation)
- [Tool Discovery & Gap Analysis](#tool-discovery--gap-analysis)
- [Description Quality Audit](#description-quality-audit)
- [Business Value Prioritization](#business-value-prioritization)

### Part 3: Supporting Materials
- [Templates & Checklists](#templates--checklists)
- [Success Metrics](#success-metrics)
- [Troubleshooting](#troubleshooting)

---

# Part 1: New Integration Process

## Phase 1: Discovery & Planning

**Timeline**: Day 1, Morning (2-3 hours)
**Goal**: Understand plugin completely before writing any code

### 1.1 Initial Plugin Analysis

**WP-CLI Direct Model Access** (Most Important!)

```bash
# List all models in plugin
wp eval "
foreach (glob('wp-content/plugins/[plugin]/app/Models/*.php') as \$file) {
    echo basename(\$file, '.php') . \"\\n\";
}
"

# Inspect a model's structure
wp eval "
\$model = new \\Plugin\\Models\\ModelName();
\$instance = \$model->first();
if (\$instance) {
    echo json_encode(\$instance->toArray(), JSON_PRETTY_PRINT);
}
"
```

**Why This Matters**:
- ✅ Reveals actual field names and types
- ✅ Shows relationships via toArray() output
- ✅ Bypasses incomplete REST API wrappers
- ✅ Discovers hidden/undocumented fields

### 1.2 Model Inspection Checklist

For EACH model, systematically document:

#### ✅ Relations (Highest Value!)
```php
// Read model class file
class Task extends Model {
    public function assignees() { ... }    // → 4 tools (add, remove, list, bulk)
    public function watchers() { ... }     // → 3 tools (add, remove, list)
    public function comments() { ... }     // → 5 tools (CRUD + list)
    public function attachments() { ... }  // → 4 tools (add, remove, list, get)
    public function labels() { ... }       // → 3-5 tools
}
```

**Key Insight**: **Each relation = 3-5 potential tools**. Relations are gold mines!

#### ✅ Fillable Fields
```php
protected $fillable = [
    'title',      // Required
    'status',     // Enum: open, closed → Dedicated tool opportunity
    'priority',   // Enum: low, medium, high → Dedicated tool
    'due_at',     // DateTime → Due date tools
    'parent_id',  // Hierarchy → Subtask tools
];
```

**Missing fields = Missing tool parameters or dedicated tools**

#### ✅ Computed Fields & Accessors
```php
public function getFullNameAttribute() {
    return $this->first_name . ' ' . $this->last_name;
}

public function getSettingsAttribute() {
    return [
        'subtask_count' => $this->subtasks()->count(),
        'attachment_count' => $this->attachments()->count(),
    ];
}
```

**Computed counts indicate underlying features needing tools**

#### ✅ Scopes & Query Methods
```php
public function scopeOverdue($query) {
    return $query->where('due_at', '<', now())
                 ->where('status', 'open');
}

public function scopeByPriority($query, $priority) {
    return $query->where('priority', $priority);
}
```

**Scopes should become list tool filters or dedicated query tools**

### 1.3 Create INTEGRATION_PLAN.md

```markdown
# [Plugin] Integration Plan

## Models Discovered (N total)

### Model 1: ModelName
**Table**: wp_prefix_table
**Purpose**: Brief description

**Relations**:
- relationName() - Type - Related Model - Tool Opportunities (N)

**Key Fields**:
- field1: type - Required/Optional - Purpose
- field2: type - Required/Optional - Purpose

**Scopes Available**:
- scopeName: Description - Tool opportunity?

**Computed Fields**:
- fieldName: How computed - Impact on tools

**Estimated Tools**: N (CRUD) + N (relations) + N (custom) = N total

### Model 2: AnotherModel
[Same structure]

## Complexity Assessment
- **Score**: Simple/Moderate/Complex
- **Models**: N
- **Relationships**: N
- **Estimated Timeline**: X days
- **Total Tool Potential**: N tools

## Integration Priority
1. Model 1 (Priority: High) - Core functionality
2. Model 2 (Priority: Medium) - Secondary features
3. Model 3 (Priority: Low) - Advanced features
```

---

## Phase 2: Parallel Validation

**Timeline**: Day 1, Afternoon (3-4 hours)
**Goal**: Complete validation of ALL models simultaneously

### 2.1 Spawn Validation Agents

Use the **Task tool** to spawn 3-8 parallel task-implementor agents:

```
/sc:spawn 5 agents to validate [Plugin] structures:

Agent 1: Core Models (User, Profile)
- Create: validate-[plugin]-core.php
- Document: tests/e2e/[plugin]/core-validation.md
- Include: DB schema, CRUD, relationships, types, edges

Agent 2: Content Models (Post, Comment)
[Same pattern]

Agent 3: Taxonomy Models (Tag, Category)
[Same pattern]

Agent 4: Analytics Models (Stats, Reports)
[Same pattern]

Agent 5: Settings/Meta Models
[Same pattern]
```

**Agent Allocation**:
- Simple (1-3 models): 3 agents, 1 model each
- Moderate (4-7 models): 5 agents, 1-2 models each
- Complex (8+ models): 8 agents, 1-3 models grouped by feature

### 2.2 Validation Script Template

Each agent creates comprehensive validation:

```php
<?php
/**
 * [Model Name] Validation Script
 * Run: cd "/path/to/wp" && php validate-[plugin]-[model].php
 */

require_once __DIR__ . '/wp-load.php';

echo "=== [Model] Validation ===\n\n";

// 1. Database Schema
global $wpdb;
$table = $wpdb->prefix . 'plugin_table';
$schema = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_A);
echo "DATABASE SCHEMA:\n" . $schema[0]['Create Table'] . "\n\n";

// 2. Create Tests
$model_class = '\\Plugin\\Models\\ModelName';

// Minimal creation
$minimal = $model_class::create(['required_field' => 'test']);
echo "MINIMAL CREATE:\n" . json_encode($minimal->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Full creation
$full = $model_class::create([
    'required_field' => 'test',
    'optional_field1' => 'value1',
    'optional_field2' => 'value2',
    // ... all fields
]);
echo "FULL CREATE:\n" . json_encode($full->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 3. Read with Relationships
$loaded = $model_class::with(['relation1', 'relation2'])->find($full->id);
echo "WITH RELATIONS:\n" . json_encode($loaded->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 4. Update Test
$loaded->update(['optional_field1' => 'updated']);
$updated = $model_class::find($full->id);
echo "AFTER UPDATE:\n" . json_encode($updated->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 5. Type Consistency Tests
echo "TYPE CHECKS:\n";
echo "ID type: " . gettype($updated->id) . "\n";
echo "Numeric field type: " . gettype($updated->numeric_field) . "\n";
echo "Timestamp format: " . $updated->created_at . "\n\n";

// 6. Edge Cases
// Test NULL vs empty string
$edge = $model_class::create([
    'required_field' => 'edge',
    'optional_field1' => '', // Empty string
]);
echo "EDGE CASE (empty string):\n";
echo "optional_field1: " . var_export($edge->optional_field1, true) . "\n\n";

// 7. Cleanup
$minimal->delete();
$full->delete();
$edge->delete();

echo "=== VALIDATION COMPLETE ===\n";
```

### 2.3 Documentation Template

Each agent creates markdown following this structure:

```markdown
# [Model Name] - Validated Structure

**Validation Date**: YYYY-MM-DD
**Model Class**: `\\Namespace\\ModelName`
**Database Table**: `wp_prefix_table`

---

## Database Schema

```sql
CREATE TABLE wp_prefix_table (
  id bigint unsigned NOT NULL AUTO_INCREMENT,
  field1 varchar(255) NOT NULL,
  field2 text NULL,
  created_at timestamp NULL,
  PRIMARY KEY (id),
  KEY index_name (field1)
);
```

## CRUD Operations

### Create

**Minimal (Required Only)**:
```json
{
  "required_field": "value"
}
```

**Response**:
```json
{
  "id": 123,
  "required_field": "value",
  "auto_field": "auto-generated",
  "created_at": "2025-10-04 18:15:42"
}
```

**Notes**:
- ⚠️ Relations NOT included on create
- ✅ Auto-generated: id, hash, created_at
- ✅ Defaults: status="open"

### Read

**Without Relations**:
```php
Model::find($id)->toArray();
```

**With Relations** (IMPORTANT!):
```php
Model::with(['relation1', 'relation2'])->find($id)->toArray();
```

**Response**:
```json
{
  ...all base fields,
  "relation1": [...],
  "relation2": [...]
}
```

### Update

**Partial Update Supported**:
```json
{
  "optional_field1": "new value"
}
```

### Delete

**Behavior**: Soft delete (archived_at) / Hard delete
**Cascade**: Yes/No - Explanation

## Relationships

### relation1()
**Type**: HasMany / BelongsTo / BelongsToMany
**Related**: `\\Namespace\\RelatedModel`
**Foreign Key**: field_name
**Pivot Table**: wp_prefix_pivot (if many-to-many)

**Pivot Structure** (if applicable):
```json
{
  "object_id": "123",      // String!
  "object_type": "Class",
  "foreign_id": "456",
  "created_at": "2025-10-04 18:15:42"
}
```

## Type Consistency

### Integer Fields
- `id`: Always integer in model
- Pivot IDs: **Always strings** (known issue)

### Numeric String Fields
- `total_points`: Returned as "0" not 0 (database int → response string)

### Timestamps
- Format: Y-m-d H:i:s (consistent)
- NULL when not set (never empty string)

## Settings/Meta Objects

### settings Field
```json
{
  "key1": "value",
  "nested": {
    "subkey": "value"
  }
}
```

**Purpose**: Description
**Required Keys**: key1, key2
**Optional Keys**: key3, key4

## Edge Cases & Gotchas

1. **Relations Not Included on Create**
   - What: Model::create() returns minimal fields
   - Why: Performance optimization
   - Impact: Must fetch separately with ->with()
   - Solution: Document in all create abilities

2. **Type Coercion Issues**
   - What: Numeric fields returned as strings
   - Why: Database driver behavior
   - Impact: Assertions must handle both
   - Solution: Document expected types

## Tool Opportunities from This Model

**CRUD Operations** (4 tools):
- create-model
- list-models
- get-model
- update-model
- delete-model

**Relationship Management** (N×4 tools per relation):
- add-model-relation1
- remove-model-relation1
- list-model-relation1
- bulk-sync-relation1

**Custom Operations**:
- operation1-model (based on scope/method)

**Total Estimated**: N tools
```

### 2.4 Consolidate VALIDATED_SHAPES.md

```bash
cd tests/e2e/[plugin]

# Combine all validation docs
cat core-validation.md \
    content-validation.md \
    taxonomy-validation.md \
    > VALIDATED_SHAPES.md
```

---

## Phase 3: Gap Analysis

**Timeline**: Day 2, Morning (2-3 hours)
**Goal**: Identify what exists vs. what's possible

### 3.1 For New Integrations

Create implementation plan from validation:

```markdown
# [Plugin] Implementation Plan

## Models to Implement (Priority Order)

### 1. Model1 (Priority: High)
**Why First**: Core functionality, no dependencies

**Abilities Needed** (12 tools):
- CRUD: create, list, get, update, delete (5)
- Relations: relation1 (4), relation2 (3) (7)
- Custom: special-operation (1)

**Complexity**: Moderate
**Estimated Time**: 1 day
**Dependencies**: None

### 2. Model2 (Priority: Medium)
[Same structure]

## Tool Breakdown by Category

### Core CRUD: 25 tools
- Model1: 5
- Model2: 5
- Model3: 5
...

### Relationship Management: 35 tools
- Model1 relations: 10
- Model2 relations: 8
...

### Custom Operations: 15 tools
- Bulk operations: 5
- Status management: 3
...

**Total Planned**: 75 tools
```

### 3.2 For Existing Integrations

**Create Gap Matrix**:

| Model | Relations Found | Relations Exposed | Fields Found | Fields Exposed | Coverage |
|-------|-----------------|-------------------|--------------|----------------|----------|
| Model1 | 5 | 2 (40%) | 30 | 18 (60%) | 50% |
| Model2 | 3 | 0 (0%) | 20 | 12 (60%) | 30% |
| Model3 | 2 | 2 (100%) | 15 | 15 (100%) | 100% |

**Relation-to-Tool Mapping**:

```
Found Relation: Task::assignees()
  Type: BelongsToMany (User pivot)
  ↓
Missing Tools (4):
  1. add-task-assignee       (attach)
  2. remove-task-assignee    (detach)
  3. list-task-assignees     (with pivot)
  4. bulk-assign-users       (sync)

Business Value: ⭐⭐⭐⭐⭐ (Critical - team collaboration)
Effort: Medium (4 hours for 4 tools)
Priority: P0 (High value, reasonable effort)
```

### 3.3 Tool Discovery Categories

Systematically check for:

**1. Unused Relations** (Highest ROI):
- Each relation = 3-5 tools
- Usually high business value (collaboration features)
- Standard patterns (add, remove, list, bulk)

**2. Unused Fields**:
- Dedicated tools vs. adding to create/update
- Enum fields → validation + dedicated tools
- DateTime fields → reminder/scheduling tools

**3. Missing List Filters**:
- Scopes → list tool parameters
- Common queries → dedicated list variations

**4. Bulk Operations**:
- Any single operation → bulk version
- Status changes, assignments, updates

**5. Workflow Operations**:
- State transitions (draft → published)
- Approval flows
- Automation triggers

---

## Phase 4: Implementation

**Timeline**: Days 2-3 (varies by complexity)
**Goal**: Build abilities following validated patterns

### 4.1 Critical Pattern: Use toArray()

**❌ WRONG (Manual Selection)**:
```php
public function execute_get_model( array $args ): array {
    $model = Model::find( $args['id'] );

    return [
        'id' => $model->id,
        'name' => $model->name,
        'email' => $model->email,
        // ❌ Missing 40+ fields!
        // ❌ Breaks when model adds fields
        // ❌ Doesn't include computed fields
    ];
}
```

**✅ RIGHT (Use toArray())**:
```php
public function execute_get_model( array $args ): array {
    $model = Model::find( $args['id'] );

    // ✅ All fields automatically
    // ✅ Includes computed fields
    // ✅ Future-proof against model changes
    return $model->toArray();
}
```

### 4.2 Critical Pattern: Document Relationship Loading

**In Ability Description**:
```php
'description' => 'Get model by ID. Relations NOT included by default - use "with" parameter to load relationships (e.g., ["relation1", "relation2"]).',
```

**In Input Schema**:
```php
'with' => [
    'type' => 'array',
    'description' => 'Relationships to eager load',
    'items' => [
        'type' => 'string',
        'enum' => ['relation1', 'relation2', 'relation3'], // From validation
    ],
],
```

**In Execute Callback**:
```php
$query = Model::query();

if ( ! empty( $args['with'] ) && is_array( $args['with'] ) ) {
    $query->with( $args['with'] );
}

$model = $query->find( $args['id'] );
return $model->toArray();
```

### 4.3 Ability Class Template

See NEW_INTEGRATION_PROCESS.md Phase 4.1 for complete template.

**Key Points**:
- Use VALIDATED_SHAPES.md for exact field types
- Document auto-generated fields (don't accept as input)
- List all relationships with enum of valid names
- Use toArray() not manual selection
- Document that relations not included on create

---

## Phase 5: Testing

**Timeline**: Days 3-4
**Goal**: ≥80% pass rate on first run

### 5.1 Test Against VALIDATED_SHAPES.md

Every test assertion should reference validation:

```typescript
test('create returns complete structure', async () => {
  const response = await client.callTool('plugin/create-model', {
    required_field: 'value',
  });

  // From VALIDATED_SHAPES.md: "Auto-generated fields"
  expect(response).toHaveProperty('id');
  expect(response).toHaveProperty('hash');
  expect(response.hash).toMatch(/^[a-f0-9]{32}$/); // MD5 format

  // From VALIDATED_SHAPES.md: "Type Consistency - Integer Fields"
  expect(typeof response.id).toBe('number');

  // From VALIDATED_SHAPES.md: "Computed Fields"
  expect(response).toHaveProperty('full_name');

  // From VALIDATED_SHAPES.md: "Relations NOT included on create"
  expect(response).not.toHaveProperty('relation1');
});
```

### 5.2 Type Consistency Tests

```typescript
test('types match validation documentation', () => {
  // From VALIDATED_SHAPES.md: "Numeric String Fields"
  expect(typeof response.total_points).toBe('string');
  expect(response.total_points).toBe('0'); // NOT number 0

  // From VALIDATED_SHAPES.md: "Pivot IDs are strings"
  const pivotId = response.relation1[0].pivot.object_id;
  expect(typeof pivotId).toBe('string');

  // From VALIDATED_SHAPES.md: "Timestamps - Y-m-d H:i:s"
  expect(response.created_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
});
```

### 5.3 Edge Case Tests from Validation

```typescript
test('edge cases from VALIDATED_SHAPES.md', async () => {
  // Edge Case 1: "Minimal data creation"
  const minimal = await client.callTool('plugin/create-model', {
    required_field: 'minimal',
  });

  expect(minimal.computed_field).toBe(''); // Empty string, not NULL
  expect(minimal.optional_field).toBeNull();

  // Edge Case 2: "NULL vs empty string handling"
  const empty = await client.callTool('plugin/create-model', {
    required_field: 'test',
    optional_field: '',
  });

  // From validation: "Empty strings become NULL" or stay ""
  expect(empty.optional_field).toBe(''); // or null, per validation
});
```

---

## Phase 6: Quality Refinement

**Timeline**: Day 5
**Goal**: Polish to production standards

### 6.1 Tool Description Quality Audit

**Scoring Rubric** (1-10):

| Criterion | Weight | 0 Points | 1 Point | 2 Points |
|-----------|--------|----------|---------|----------|
| **Clarity** | 2× | Ambiguous | Somewhat clear | Crystal clear |
| **Completeness** | 2× | Missing info | Has basics | Fully describes |
| **Succinctness** | 1× | Too verbose | Acceptable | Perfect brevity |
| **Return Value** | 2× | Not mentioned | Vague | Explicit |
| **Parameters** | 2× | Not explained | Basic | With examples |

**Target**: ≥8.5/10 average across all tools

### 6.2 Description Improvement Patterns

**Add Return Value Details**:
```diff
- "Update an existing model"
+ "Update model properties. Supports partial updates. Returns updated model with all fields and timestamps."
```

**Document Optional Parameters**:
```diff
- "List all models in the system"
+ "Get all models with optional pagination (page, per_page) and filtering (status, type). Returns models with metadata counts."
```

**Clarify Partial Updates**:
```diff
- "Update model"
+ "Update model title, status, or settings. Supports partial updates (any combination of fields)."
```

**Shorten Redundant Labels**:
```diff
- "List FluentBoards labels"
+ "List board labels"
```

### 6.3 Parameter Documentation Standards

**Enum Values**:
```php
'status' => [
    'type' => 'string',
    'enum' => ['open', 'closed', 'archived'],
    'description' => 'Model status',
    'default' => 'open',
],
```

**Examples for Complex Types**:
```php
'bg_color' => [
    'type' => 'string',
    'description' => 'Background color (hex) - e.g., #4bce97',
    'pattern' => '^#[0-9a-fA-F]{6}$',
],
```

**Object Properties Documented**:
```php
'settings' => [
    'type' => 'object',
    'description' => 'Settings object. Supported: field1 (type), field2 (enum: a|b|c)',
    'properties' => [
        'field1' => ['type' => 'string'],
        'field2' => ['type' => 'string', 'enum' => ['a', 'b', 'c']],
    ],
],
```

---

# Part 2: Enhancement Process (Existing Integrations)

Use this when you have existing abilities and want to discover missing tools or improve quality.

## Shape Discovery & Validation

**Timeline**: 2-3 hours
**Goal**: Understand what the plugin ACTUALLY does vs. what's exposed

### WP-CLI Validation Approach

```bash
# Test ACTUAL model behavior
wp eval "
\$model = new \\Plugin\\Models\\Model();
\$test = \$model->create(['field' => 'test']);

echo 'CREATE RESPONSE:' . PHP_EOL;
echo json_encode(\$test->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

echo PHP_EOL . 'WITH RELATIONS:' . PHP_EOL;
\$loaded = \$model->with(['relation1', 'relation2'])->find(\$test->id);
echo json_encode(\$loaded->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;

\$test->delete();
"
```

### Compare API vs. Model

```php
// What API returns (via tool)
$api_response = call_tool('plugin/create-model', ['field' => 'test']);

// What model actually has
$model_response = Model::create(['field' => 'test'])->toArray();

// Find differences
$api_fields = array_keys($api_response);
$model_fields = array_keys($model_response);
$missing = array_diff($model_fields, $api_fields);

echo "MISSING FIELDS: " . implode(', ', $missing);
```

**Common Findings**:
- ❌ API uses manual field selection (missing 30-50% of fields)
- ❌ Relations never included
- ❌ Computed fields missing
- ❌ Type field missing (polymorphic indicator)

---

## Tool Discovery & Gap Analysis

**Timeline**: 2-3 hours
**Goal**: Find what's possible but not exposed

### Systematic Model Inspection

For each ability class, read corresponding model:

```php
// In model file
class Task extends Model {
    // Check ALL relations
    public function assignees() { ... }    // ❌ No tools!
    public function watchers() { ... }     // ❌ No tools!
    public function comments() { ... }     // ❌ No tools!
    public function labels() { ... }       // ✅ Has 3 tools

    // Check fillable fields
    protected $fillable = [
        'priority',      // ❌ Not in create/update schemas
        'due_at',        // ❌ No due date tools
        'parent_id',     // ❌ No subtask tools
    ];

    // Check scopes
    public function scopeOverdue() { ... } // ❌ Not a list filter
}
```

### Create Gap Matrix

| Capability | Model Has | Tools Exist | Gap | Business Value | Priority |
|------------|-----------|-------------|-----|----------------|----------|
| Assignees | ✅ BelongsToMany | ❌ None | 4 tools | ⭐⭐⭐⭐⭐ Critical | P0 |
| Watchers | ✅ BelongsToMany | ❌ None | 3 tools | ⭐⭐⭐⭐ High | P1 |
| Comments | ✅ HasMany | ❌ None | 5 tools | ⭐⭐⭐⭐⭐ Critical | P0 |
| Priority Field | ✅ Enum | 🟡 In update | 2 dedicated | ⭐⭐⭐ Medium | P2 |
| Label Position | ✅ Integer | ❌ None | 1 tool | ⭐⭐ Low | P3 |

---

## Description Quality Audit

**Timeline**: 1-2 hours
**Goal**: Improve existing tool clarity

### Audit Each Tool

```markdown
#### Tool: `plugin-update-model`

**Current**:
- Label: "Update FluentPlugin model"
- Description: "Update an existing model"

**Rating**: 🟠 6/10

**Issues**:
- ❌ "FluentPlugin" redundant (namespace already clear)
- ❌ Doesn't mention partial update support
- ❌ No return value description
- ❌ Doesn't list what can be updated

**Recommended**:
- Label: "Update model properties"
- Description: "Update model title, status, or settings. Supports partial updates (any combination of fields). Returns updated model with all fields."

**Expected Score**: 🟢 9/10
```

### Best Practice Examples

Document 10/10 tools as templates:

```markdown
### Perfect Label Tools (10/10)

**Example**: `fluentboards-move-all-tasks`
- Label: "Move all tasks between stages"
- Description: "Move all tasks from one stage to another"

**Why Perfect**:
- ✅ Verb-noun pattern
- ✅ Explains source and destination
- ✅ Concise (8 words)
- ✅ Immediately actionable
```

---

## Business Value Prioritization

**Timeline**: 1 hour
**Goal**: ROI-based implementation order

### Value Assessment (1-5 ⭐)

| Stars | Description | Example |
|-------|-------------|---------|
| ⭐⭐⭐⭐⭐ | Critical - Core workflow | Assignees, Comments |
| ⭐⭐⭐⭐ | High - Important feature | Watchers, Attachments |
| ⭐⭐⭐ | Medium - Nice to have | Bulk operations |
| ⭐⭐ | Low - Convenience | Positioning |
| ⭐ | Minimal - Rare use | Advanced filters |

### Effort Estimation (XS-XL)

| Size | Time | Complexity |
|------|------|------------|
| **XS** | 1-2h | Field add, description fix |
| **S** | 2-4h | Simple CRUD, basic relation |
| **M** | 4-8h | Many-to-many, pivot data |
| **L** | 1-2d | Complex logic, multiple models |
| **XL** | 2+d | New subsystem |

### Priority Matrix

```
         │ Low Effort  │ Med Effort  │ High Effort
─────────┼─────────────┼─────────────┼────────────
High     │             │             │
Value    │     P0      │     P1      │     P2
         │ Quick wins  │ Strategic   │ Long-term
─────────┼─────────────┼─────────────┼────────────
Medium   │             │             │
Value    │     P1      │     P2      │     P3
         │ Easy value  │ Balanced    │ Low ROI
─────────┼─────────────┼─────────────┼────────────
Low      │             │             │
Value    │     P3      │     P3      │     P4
         │ Nice to have│ Low priority│ Avoid
```

### ROI Calculation

```
ROI Score = (Business Value × Adoption Rate) / Implementation Hours

Example:
  Assignees: (5 stars × 0.95 adoption) / 6 hours = 0.79/hour
  Positioning: (2 stars × 0.20 adoption) / 1.5 hours = 0.27/hour

  → Assignees have 3× better ROI, confirming P0 priority
```

---

# Part 3: Supporting Materials

## Templates & Checklists

### Quick Start Checklist

**New Integration**:
- [ ] Day 1 AM: Model discovery via WP-CLI
- [ ] Day 1 AM: Create INTEGRATION_PLAN.md
- [ ] Day 1 PM: Spawn 3-8 validation agents
- [ ] Day 1 PM: Consolidate VALIDATED_SHAPES.md
- [ ] Day 2 AM: Gap analysis or implementation plan
- [ ] Day 2-3: Implement abilities (use toArray()!)
- [ ] Day 3-4: Create test suite (≥80% pass rate)
- [ ] Day 5: Quality audit and polish

**Existing Enhancement**:
- [ ] Shape validation (2-3 hours)
- [ ] Tool discovery (2-3 hours)
- [ ] Quality audit (1-2 hours)
- [ ] Prioritization (1 hour)
- [ ] Implement P0 (varies)

### Validation Script Quickstart

```php
<?php
// Minimal validation template
require_once '/path/to/wp-load.php';

$class = '\\Plugin\\Model';
$test = $class::create(['field' => 'test']);

echo "CREATE:\n" . json_encode($test->toArray(), JSON_PRETTY_PRINT) . "\n\n";

$loaded = $class::with(['relation'])->find($test->id);
echo "WITH RELATIONS:\n" . json_encode($loaded->toArray(), JSON_PRETTY_PRINT) . "\n\n";

$test->delete();
```

---

## Success Metrics

### Coverage Metrics

**Before Enhancement**:
```
Tools: 18
Relations Exposed: 20% (1/5)
Fields Exposed: 50% (15/30)
Overall Coverage: ~35%
```

**After P0-P1 Implementation**:
```
Tools: 38 (+111%)
Relations Exposed: 80% (4/5)
Fields Exposed: 83% (25/30)
Overall Coverage: ~75%
```

### Quality Metrics

**Target Scores**:
- Description Quality: ≥8.5/10
- Parameters with Examples: ≥85%
- Enum Values Documented: ≥90%
- Test Pass Rate: ≥80%

### Time Metrics

**Process Time** (Enhancement):
- Shape Discovery: 2h
- Gap Analysis: 2h
- Quality Audit: 2h
- Prioritization: 1h
- Documentation: 2h
- **Total**: ~9 hours

**Implementation Time** (varies by priority):
- Description fixes: 1h
- Parameter improvements: 2h
- P0 tools (13): 15-20h
- P1 tools (11): 12-18h
- **Total**: ~30-40h

**ROI**: 9h analysis → 40h implementation → 29 new tools + quality improvements

---

## Troubleshooting

### Validation Scripts Fail

**Problem**: PHP errors when running validation

**Solutions**:
1. Check plugin active: `wp plugin list`
2. Verify namespaces: `grep -r "namespace" plugin/`
3. Use wp eval-file instead of direct PHP
4. Check database tables exist

### Tests Failing on Type Assertions

**Problem**: Expected number, got string

**Solution**: Check VALIDATED_SHAPES.md Type Consistency section
```typescript
// Numeric fields often returned as strings
expect(typeof response.count).toBe('string'); // Not 'number'!
expect(response.count).toBe('0'); // Not 0
```

### Missing Fields in Responses

**Problem**: Tool returns fewer fields than validation showed

**Solution**: Check if using manual selection instead of toArray()
```php
// ❌ WRONG
return ['id' => $model->id, 'name' => $model->name];

// ✅ RIGHT
return $model->toArray();
```

---

## Conclusion

This methodology provides:

✅ **Systematic discovery** - No guesswork, validation-driven
✅ **Parallel efficiency** - 95% time reduction via agents
✅ **Quality standards** - ≥8.5/10 descriptions, ≥80% test pass
✅ **ROI prioritization** - Business value drives implementation
✅ **Reusable process** - Apply to any WordPress plugin

**Proven Results**:
- FluentBoards: 18 → 47 tools possible (+161%)
- FluentCRM: 10 models validated in 1 day
- 8.2 → 8.9/10 quality improvement
- 86% test pass rate

**Ready for production use on new integrations and existing enhancements.**

---

**Version**: 2.0
**Last Updated**: 2025-10-04
**Next Review**: After 3 more integration applications
