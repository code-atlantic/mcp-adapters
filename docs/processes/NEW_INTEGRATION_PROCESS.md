# New Integration Process - Step-by-Step Guide

**Purpose**: Standardized methodology for integrating new WordPress plugins with MCP adapters
**Based on**: FluentBoards & FluentCRM validation successes
**Success Rate**: 86%+ test pass rate, complete API coverage

---

## Overview

This process uses **validation-first, test-driven development** with parallel agent execution to rapidly understand and integrate WordPress plugins.

**Timeline**: 2-5 days for complete integration (depending on plugin complexity)
**Quality Metric**: ≥80% test pass rate on first run
**Coverage Goal**: Complete API surface with type-safe operations

---

## Phase 1: Discovery & Planning (Day 1, Morning)

### 1.1 Initial Plugin Analysis

**Goal**: Understand plugin architecture and identify all models/features

**Steps**:
```bash
# 1. Locate plugin directory
cd wp-content/plugins/[plugin-name]

# 2. Find models/database tables
find . -name "*.php" -type f | xargs grep -l "class.*Model"
find . -name "*.php" -type f | xargs grep -l "wpdb->prefix"

# 3. Identify main features
ls -la includes/  # or app/, src/, classes/
cat readme.txt    # Feature list
```

**Checklist**:
- [ ] Plugin directory located
- [ ] All models identified (list them)
- [ ] Database tables documented (get prefixes)
- [ ] Main features listed
- [ ] Plugin dependencies noted (Pro features, add-ons)

**Output**: Create `INTEGRATION_PLAN.md`:
```markdown
# [Plugin Name] Integration Plan

## Models Identified
1. Model Name - Table: wp_prefix_table - Purpose: ...
2. ...

## Features to Integrate
1. Feature Name - Models Used: ... - Priority: High/Medium/Low
2. ...

## Dependencies
- WordPress: X.X+
- PHP: X.X+
- Required Plugins: ...
- Pro Features: ...
```

### 1.2 Capability Assessment

**Goal**: Determine integration scope and complexity

**Questions to Answer**:
- How many models/tables? (Simple: 1-3, Moderate: 4-7, Complex: 8+)
- Relationships between models? (None, Simple, Polymorphic)
- Custom post types or standard tables?
- REST API already exists?
- Complex workflows/automations?

**Complexity Score**:
```
Simple (2-3 days):
- 1-3 models
- Basic CRUD only
- Existing REST API
- No complex relationships

Moderate (3-4 days):
- 4-7 models
- Standard relationships
- Some custom logic
- Mix of CPT and tables

Complex (5+ days):
- 8+ models
- Polymorphic relationships
- Workflow engines
- Custom post types + tables
- No REST API
```

**Output**: Add to INTEGRATION_PLAN.md:
```markdown
## Complexity Assessment
**Score**: Simple/Moderate/Complex
**Estimated Timeline**: X days
**Risk Factors**: ...
**Dependencies**: ...
```

---

## Phase 2: Parallel Model Validation (Day 1, Afternoon)

### 2.1 Spawn Validation Agents

**Goal**: Comprehensively validate ALL model structures in parallel

**Agent Allocation Strategy**:
```yaml
# Group related models together
simple_plugins:
  agents: 3
  models_per_agent: 1-2

moderate_plugins:
  agents: 5
  models_per_agent: 1-2

complex_plugins:
  agents: 5-8
  models_per_agent: 1-3 (group by feature area)
```

**Agent Task Template**:
```bash
# Spawn agents using Task tool with task-implementor
# Example for 5 model groups:

/sc:spawn 5 agents to validate [Plugin] structures:

Agent 1: Validate Core Models (User, Profile, etc.)
- Create validation script: validate-[plugin]-core.php
- Document in: tests/e2e/[plugin]/core-validation.md
- Include: DB schema, CRUD ops, relationships, types, edge cases

Agent 2: Validate Content Models (Post, Comment, etc.)
[Same pattern]

Agent 3: Validate Taxonomy Models (Tag, Category, etc.)
[Same pattern]

Agent 4: Validate Analytics Models (Stats, Reports, etc.)
[Same pattern]

Agent 5: Validate Settings/Meta Models
[Same pattern]
```

**Validation Script Requirements**:

Each agent creates a PHP script that:
```php
<?php
/**
 * Validation script for [Model Name]
 * Run via: cd "/path/to/wp" && php /path/to/script.php
 */

// 1. Database Schema Validation
echo "=== Database Schema ===\n";
// Show CREATE TABLE statement
// Verify all columns, types, indexes

// 2. Create Operation Test
echo "\n=== Create Operation ===\n";
// Minimal creation (required fields only)
// Full creation (all fields)
// Verify auto-generated fields (ID, hash, timestamps)

// 3. Read Operation Test
echo "\n=== Read Operation ===\n";
// Basic read
// Read with relationships (eager loading)
// Query methods (findBy, where, etc.)

// 4. Update Operation Test
echo "\n=== Update Operation ===\n";
// Partial field updates
// Timestamp updates

// 5. Relationship Tests
echo "\n=== Relationships ===\n";
// Load all relationships
// Test pivot tables if polymorphic
// Verify relationship data structure

// 6. Type Consistency Tests
echo "\n=== Type Consistency ===\n";
// ID types (int vs string)
// Numeric fields (int vs string)
// Timestamp formats
// NULL vs empty string handling

// 7. Edge Cases
echo "\n=== Edge Cases ===\n";
// Minimal data
// Maximum data
// Special characters
// Duplicate detection
// Default values

// 8. Delete/Archive Test
echo "\n=== Delete/Archive ===\n";
// Soft delete (if applicable)
// Hard delete
// Cascade behavior

// Cleanup
echo "\n=== Cleanup ===\n";
// Remove all test data

// Summary
echo "\n=== VALIDATION SUMMARY ===\n";
// Report pass/fail counts
```

**Documentation Requirements**:

Each agent creates markdown with:
```markdown
# [Model Name] - Validated Structure

**Validation Date**: YYYY-MM-DD
**Model Class**: \Namespace\Class
**Database Table**: wp_prefix_table

## Summary
Brief overview of model purpose and key findings.

## Database Schema
```sql
CREATE TABLE ... (actual schema from validation)
```

### Indexes
- index_name (columns)

## Model Structure

### Required Fields
- field_name: type - Description

### Optional Fields
- field_name: type - Description

### Auto-Generated Fields
- field_name: type - Description (how generated)

### Computed/Accessor Fields
- field_name: Description (not in DB, computed how)

## CRUD Operations

### Create
**Request**:
```json
{minimal required fields}
```

**Response**:
```json
{actual response with all fields}
```

**Notes**:
- Relationships NOT included on create
- Auto-generated: field1, field2
- Defaults: field3=value

### Read
**Without Relationships**:
```json
{base object}
```

**With Relationships**:
```php
Model::with(['relation1', 'relation2'])->find($id);
```

**Response**:
```json
{object with relations}
```

### Update
**Request**:
```json
{partial update}
```

**Response**:
```json
{updated object}
```

### Delete
**Behavior**: Soft delete / Hard delete
**Cascade**: Yes/No - Description

## Relationships

### relationName()
**Type**: HasMany / BelongsTo / BelongsToMany
**Related Model**: \Class
**Foreign Key**: field_name
**Pivot Table**: table_name (if applicable)

**Structure**:
```json
{relationship data example}
```

## Type Consistency

### Integer Fields
- field: Always int / Sometimes string (explain)

### String Fields
- field: Always string / Can be NULL

### Numeric String Fields (Known Issue)
- field: Returned as "0" not 0 (database type vs response type)

### Timestamp Fields
- Format: Y-m-d H:i:s
- NULL when: ...

## Settings/Meta Object Structures

### settingsField
```json
{
  "nested": "structure",
  "arrays": [],
  "types": "documented"
}
```

**Purpose**: Description
**Required Keys**: key1, key2
**Optional Keys**: key3, key4

## Edge Cases & Gotchas

1. **Issue Description**
   - What: Explanation
   - Why: Reason
   - Impact: How it affects integration
   - Solution: Workaround or handling

2. **Another Issue**
   [Same pattern]

## Query Patterns

### Common Queries
```php
// Pattern 1
Model::where('field', 'value')->get();

// Pattern 2
Model::ofType('type')->active()->paginate(20);
```

### Scopes Available
- scopeName: Description

## Key Findings

**Strengths**:
- Well-structured model
- Type-safe operations
- Clear relationships

**Limitations**:
- Known issue 1
- Known issue 2

**Recommendations for MCP Adapter**:
1. Use toArray() for complete field coverage
2. Document that relations not included on create
3. Add 'with' parameter for eager loading
4. Handle type coercion for numeric string fields
```

### 2.2 Monitor Agent Progress

**While Agents Run**:
```bash
# Check agent outputs as they complete
ls -la tests/e2e/[plugin]/
ls -la validate-*.php

# Verify validation scripts
php validate-[plugin]-[model].php  # Run one to test
```

**Agent Completion Checklist**:
- [ ] All agents completed successfully
- [ ] All validation scripts created
- [ ] All markdown docs created
- [ ] Scripts execute without errors
- [ ] Documentation complete (schema, CRUD, types, edges)

### 2.3 Consolidate Validation Results

**Goal**: Single source of truth for all structures

```bash
cd tests/e2e/[plugin]

# Combine all validation docs
cat core-validation.md \
    content-validation.md \
    taxonomy-validation.md \
    analytics-validation.md \
    meta-validation.md \
    > VALIDATED_SHAPES.md
```

**VALIDATED_SHAPES.md Structure**:
```markdown
# [Plugin Name] - Validated API Structures

**Complete Validation Date**: YYYY-MM-DD
**Plugin Version**: X.X.X
**Total Models Validated**: N

---

## Table of Contents
1. [Model 1](#model-1)
2. [Model 2](#model-2)
...

---

[Content from all validation docs]

---

## Cross-Model Patterns

### Common Type Issues
- Issue observed across models
- Consistent handling approach

### Relationship Patterns
- Polymorphic pivot structure
- Eager loading patterns

### Validation Summary
**Total Models**: N
**Total Fields**: N
**Relationships Mapped**: N
**Type Issues Documented**: N
**Edge Cases Identified**: N
```

---

## Phase 3: Gap Analysis (Day 2, Morning)

### 3.1 Compare Validated Structures to Existing Code

**Goal**: Identify what needs to be built or fixed

**If Starting Fresh (No Existing Integration)**:

Create `IMPLEMENTATION_PLAN.md`:
```markdown
# [Plugin Name] Implementation Plan

## Models to Implement
1. **Model Name**
   - Priority: High/Medium/Low
   - Abilities Needed:
     - create-[model]
     - list-[models]
     - get-[model]
     - update-[model]
     - delete-[model]
     - [custom operations]
   - Complexity: Simple/Moderate/Complex
   - Dependencies: [other models]

## Ability Classes to Create
1. `classes/Adapters/[Plugin]/Abilities/ModelName.php`
   - CRUD operations
   - Custom operations
   - Relationship management

## Test Files to Create
1. `tests/e2e/[plugin]/model-name.test.ts`
   - CRUD tests
   - Relationship tests
   - Type consistency tests
   - Edge case tests
```

**If Improving Existing Integration**:

Create `GAP_ANALYSIS.md` (see FLUENTCRM_VALIDATION_ANALYSIS.md as template):
```markdown
# [Plugin Name] Gap Analysis

## Model-by-Model Review

### Model Name (✅/⚠️/❌ Status)

**Current Abilities**:
- ✅ Implemented correctly
- ⚠️ Needs improvement
- ❌ Missing

**Validation Findings**:
- 📊 Database: N fields
- ⚠️ Current schema: Only N fields documented
- ❌ Missing fields: field1, field2, field3
- ✅/❌ Pattern: toArray() vs manual selection

**Required Changes**:
1. Specific change with priority
2. Another change

**Priority**: 🔴 High / 🟡 Medium / 🟢 Low
```

### 3.2 Prioritize Work

**Priority Matrix**:
```
🔴 HIGH (Do First):
- Security issues
- Data integrity issues
- Core CRUD missing
- Type safety problems
- Manual field selection (missing fields)

🟡 MEDIUM (Do Soon):
- Missing secondary features
- Relationship loading optimization
- Analytics/reporting gaps
- Advanced features

🟢 LOW (Future):
- Nice-to-have features
- Performance optimizations
- Pro-only features
- Edge case handling improvements
```

**Create Roadmap**:
```markdown
## Implementation Roadmap

### Phase 1: Critical (Week 1)
- [ ] Task 1 (2 hours)
- [ ] Task 2 (4 hours)
- [ ] Task 3 (1 day)

### Phase 2: Testing (Week 1-2)
- [ ] Create test infrastructure
- [ ] Implement CRUD tests
- [ ] Type consistency tests
- [ ] Achieve ≥80% pass rate

### Phase 3: Features (Week 2)
- [ ] Missing feature 1
- [ ] Missing feature 2

### Phase 4: Polish (Week 3)
- [ ] Performance optimization
- [ ] Documentation
- [ ] Advanced features
```

---

## Phase 4: Implementation (Days 2-3)

### 4.1 Create Ability Classes

**File Structure**:
```
classes/Adapters/[Plugin]/
├── BaseAbility.php          # Extend MCP base
├── Abilities/
│   ├── ModelName.php       # One per model
│   ├── AnotherModel.php
│   └── ...
└── Servers/
    └── AbilityRegistry.php  # Register all abilities
```

**BaseAbility.php Template**:
```php
<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\[Plugin];

use MCP\Adapters\Core\BaseAbility as CoreBaseAbility;

/**
 * Base class for [Plugin] abilities
 */
abstract class BaseAbility extends CoreBaseAbility {

    /**
     * Check if user can manage [plugin]
     */
    protected function can_manage_[plugin]( array $args ): bool {
        return current_user_can( 'manage_options' ); // Adjust capability
    }

    /**
     * Check if user can view [plugin] data
     */
    protected function can_view_[plugin]( array $args ): bool {
        return is_user_logged_in();
    }

    /**
     * Get [Plugin] model instance
     */
    protected function get_model_instance( string $class ) {
        if ( ! class_exists( $class ) ) {
            throw new \Exception( "[Plugin] plugin is not active" );
        }
        return new $class();
    }
}
```

**Model Ability Template** (from VALIDATED_SHAPES.md):
```php
<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\[Plugin]\Abilities;

use MCP\Adapters\Adapters\[Plugin]\BaseAbility;

/**
 * [Model Name] Abilities
 */
class ModelName extends BaseAbility {

    protected function register_abilities(): void {
        $this->register_create_model();
        $this->register_list_models();
        $this->register_get_model();
        $this->register_update_model();
        $this->register_delete_model();
        // Add custom operations
    }

    /**
     * Create [model]
     *
     * VALIDATED FIELDS (from VALIDATED_SHAPES.md):
     * - All N fields from validation documented
     * - Auto-generated: field1, field2
     * - Computed: field3, field4
     * - Relations: NOT included on create (must fetch separately)
     */
    private function register_create_model(): void {
        wp_register_ability(
            '[plugin]/create-[model]',
            [
                'label' => 'Create [Plugin] [model]',
                'description' => 'Create a new [model]. Relations not included in response - use get-[model] with "with" parameter to load.',
                'input_schema' => [
                    'type' => 'object',
                    'required' => [ 'required_field1' ], // From validation
                    'properties' => [
                        // ALL fields from VALIDATED_SHAPES.md
                        'field1' => [
                            'type' => 'string', // Exact type from validation
                            'description' => 'Field description',
                        ],
                        // ... all other fields
                    ],
                ],
                'execute_callback' => [ $this, 'execute_create_model' ],
                'permission_callback' => [ $this, 'can_manage_[plugin]' ],
                'meta' => [
                    'category' => '[plugin]',
                    'subcategory' => '[models]',
                ],
            ]
        );
    }

    /**
     * Execute create [model]
     *
     * CRITICAL: Use toArray() NOT manual field selection
     */
    public function execute_create_model( array $args ): array {
        // Validate required fields
        if ( empty( $args['required_field1'] ) ) {
            throw new \Exception( 'required_field1 is required' );
        }

        // Get model class
        $model_class = '\Plugin\Namespace\Models\ModelName';

        // Create instance
        $model = $model_class::create( [
            'field1' => $args['field1'] ?? null,
            'field2' => $args['field2'] ?? null,
            // ... map all input fields
        ] );

        // ✅ CORRECT: Use toArray() for complete field coverage
        return $model->toArray();

        // ❌ WRONG: Manual field selection (misses fields)
        // return [
        //     'id' => $model->id,
        //     'field1' => $model->field1,
        //     // Missing 40+ fields!
        // ];
    }

    /**
     * Get [model]
     *
     * SUPPORTS EAGER LOADING: Use 'with' parameter to load relationships
     */
    private function register_get_model(): void {
        wp_register_ability(
            '[plugin]/get-[model]',
            [
                'label' => 'Get [Plugin] [model]',
                'description' => 'Get [model] by ID. Use "with" parameter to load relationships (e.g., ["relation1", "relation2"]).',
                'input_schema' => [
                    'type' => 'object',
                    'required' => [ 'id' ],
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => '[Model] ID',
                        ],
                        'with' => [
                            'type' => 'array',
                            'description' => 'Relationships to eager load',
                            'items' => [
                                'type' => 'string',
                                'enum' => [ 'relation1', 'relation2', 'relation3' ], // From validation
                            ],
                        ],
                    ],
                ],
                'execute_callback' => [ $this, 'execute_get_model' ],
                'permission_callback' => [ $this, 'can_view_[plugin]' ],
            ]
        );
    }

    public function execute_get_model( array $args ): array {
        $model_class = '\Plugin\Namespace\Models\ModelName';

        // Build query with optional eager loading
        $query = $model_class::query();

        if ( ! empty( $args['with'] ) && is_array( $args['with'] ) ) {
            $query->with( $args['with'] );
        }

        $model = $query->find( $args['id'] );

        if ( ! $model ) {
            throw new \Exception( '[Model] not found' );
        }

        // ✅ Use toArray() for complete structure
        return $model->toArray();
    }

    // Implement remaining CRUD operations following same pattern
    // Reference VALIDATED_SHAPES.md for exact field types
}
```

### 4.2 Register Abilities

**AbilityRegistry.php**:
```php
<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\[Plugin]\Servers;

use MCP\Adapters\Adapters\[Plugin]\Abilities;

class AbilityRegistry {

    public static function get_all_abilities(): array {
        return array_merge(
            self::get_model1_abilities(),
            self::get_model2_abilities(),
            // ... all models
        );
    }

    public static function get_model1_abilities(): array {
        return [
            '[plugin]/create-model1',
            '[plugin]/list-model1s',
            '[plugin]/get-model1',
            '[plugin]/update-model1',
            '[plugin]/delete-model1',
        ];
    }

    // ... repeat for each model

    /**
     * Register all abilities
     */
    public static function register(): void {
        $abilities = [
            new Abilities\Model1(),
            new Abilities\Model2(),
            // ... all ability classes
        ];

        foreach ( $abilities as $ability ) {
            $ability->register();
        }
    }
}
```

**Hook Registration** (in main plugin file or init):
```php
add_action( 'init', function() {
    if ( class_exists( '\Plugin\Namespace\Models\Model1' ) ) {
        \MCP\Adapters\Adapters\[Plugin]\Servers\AbilityRegistry::register();
    }
}, 20 ); // After plugin loads
```

### 4.3 Implementation Checklist

For each model:
- [ ] Ability class created
- [ ] All CRUD operations implemented
- [ ] Custom operations implemented
- [ ] Uses `toArray()` NOT manual field selection
- [ ] All fields from VALIDATED_SHAPES.md in input schema
- [ ] Relationships documented (not included on create)
- [ ] `with` parameter for eager loading
- [ ] Type consistency notes in descriptions
- [ ] Edge cases handled
- [ ] Permission callbacks appropriate
- [ ] Registered in AbilityRegistry

---

## Phase 5: Test Development (Days 3-4)

### 5.1 Create Test Infrastructure

**Test Configuration** (if not exists):
```typescript
// tests/e2e/utils/test-config.ts
export interface TestConfig {
  baseURL: string;
  username: string;
  password: string;
}

const WP_TEST_USER = process.env.WP_TEST_USER || 'admin';
const WP_TEST_PASSWORD = process.env.WP_TEST_PASSWORD || '';
const WP_BASE_URL = process.env.WP_BASE_URL || 'http://mcp.local';

export const [PLUGIN]_CONFIG: TestConfig = {
  baseURL: `${WP_BASE_URL}/wp-json/mcp-adapters/v1/[plugin]`,
  username: WP_TEST_USER,
  password: WP_TEST_PASSWORD,
};
```

**Test Client**:
```typescript
// tests/e2e/utils/mcp-client.ts
import { [PLUGIN]_CONFIG } from "./test-config";

export class MCPClient {
  private config: TestConfig;

  constructor(config: TestConfig) {
    this.config = config;
  }

  async callTool(toolName: string, params: any): Promise<any> {
    const auth = Buffer.from(
      `${this.config.username}:${this.config.password}`
    ).toString('base64');

    const response = await fetch(`${this.config.baseURL}/tools/${toolName}`, {
      method: 'POST',
      headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(params),
    });

    if (!response.ok) {
      throw new Error(`Tool call failed: ${response.statusText}`);
    }

    return response.json();
  }
}
```

### 5.2 Implement Test Suites

**Test File Template**:
```typescript
// tests/e2e/[plugin]/model-name.test.ts
import { MCPClient } from "../../utils/mcp-client";
import { [PLUGIN]_CONFIG } from "../../utils/test-config";

describe('[Plugin] Model CRUD Operations', () => {
  let client: MCPClient;
  let testModel: any;

  beforeAll(async () => {
    client = new MCPClient([PLUGIN]_CONFIG);
  });

  afterAll(async () => {
    // Cleanup: Delete test data
    if (testModel?.id) {
      await client.callTool('[plugin]/delete-model', { id: testModel.id });
    }
  });

  /**
   * CREATE TESTS
   * Validate against VALIDATED_SHAPES.md create operation
   */
  describe('Create Operations', () => {

    test('create with minimal fields returns complete structure', async () => {
      const response = await client.callTool('[plugin]/create-model', {
        required_field: 'value',
      });

      testModel = response;

      // Validate structure from VALIDATED_SHAPES.md
      expect(response).toHaveProperty('id');
      expect(typeof response.id).toBe('number'); // Type from validation

      // Auto-generated fields (from validation docs)
      expect(response).toHaveProperty('hash');
      expect(response.hash).toMatch(/^[a-f0-9]{32}$/); // MD5 format

      // Computed fields (from validation docs)
      expect(response).toHaveProperty('computed_field');

      // Default values (from validation docs)
      expect(response.status).toBe('default_value');

      // Timestamps
      expect(response).toHaveProperty('created_at');
      expect(response.created_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);

      // Relations NOT included on create
      expect(response).not.toHaveProperty('relation1');
      expect(response).not.toHaveProperty('relation2');
    });

    test('create with all fields populates correctly', async () => {
      const fullData = {
        required_field: 'value',
        optional_field1: 'value1',
        optional_field2: 'value2',
        // ... all fields from validation
      };

      const response = await client.callTool('[plugin]/create-model', fullData);

      // Verify all fields present
      expect(response.optional_field1).toBe('value1');
      expect(response.optional_field2).toBe('value2');

      // Cleanup this one
      await client.callTool('[plugin]/delete-model', { id: response.id });
    });
  });

  /**
   * READ TESTS
   * Validate relationship loading and field completeness
   */
  describe('Read Operations', () => {

    test('get without relationships returns base structure', async () => {
      const response = await client.callTool('[plugin]/get-model', {
        id: testModel.id,
      });

      // All base fields present (from VALIDATED_SHAPES.md)
      expect(response.id).toBe(testModel.id);
      expect(response).toHaveProperty('required_field');

      // Relations NOT included without 'with' parameter
      expect(response).not.toHaveProperty('relation1');
    });

    test('get with relationships loads eagerly', async () => {
      const response = await client.callTool('[plugin]/get-model', {
        id: testModel.id,
        with: ['relation1', 'relation2'],
      });

      // Relations now included
      expect(response).toHaveProperty('relation1');
      expect(Array.isArray(response.relation1)).toBe(true);

      expect(response).toHaveProperty('relation2');
      expect(Array.isArray(response.relation2)).toBe(true);
    });

    test('list with pagination', async () => {
      const response = await client.callTool('[plugin]/list-models', {
        page: 1,
        per_page: 10,
      });

      expect(response).toHaveProperty('data');
      expect(Array.isArray(response.data)).toBe(true);
      expect(response).toHaveProperty('total');
      expect(response).toHaveProperty('per_page');
      expect(response).toHaveProperty('current_page');
    });
  });

  /**
   * UPDATE TESTS
   */
  describe('Update Operations', () => {

    test('partial update modifies only specified fields', async () => {
      const response = await client.callTool('[plugin]/update-model', {
        id: testModel.id,
        optional_field1: 'updated_value',
      });

      expect(response.optional_field1).toBe('updated_value');
      expect(response.required_field).toBe(testModel.required_field); // Unchanged

      // Timestamp updated
      expect(response.updated_at).not.toBe(testModel.updated_at);
    });
  });

  /**
   * DELETE TESTS
   */
  describe('Delete Operations', () => {

    test('delete removes model', async () => {
      // Create temporary model
      const temp = await client.callTool('[plugin]/create-model', {
        required_field: 'temp',
      });

      // Delete it
      await client.callTool('[plugin]/delete-model', { id: temp.id });

      // Verify deleted (should throw)
      await expect(
        client.callTool('[plugin]/get-model', { id: temp.id })
      ).rejects.toThrow();
    });
  });

  /**
   * TYPE CONSISTENCY TESTS
   * Validate types match VALIDATED_SHAPES.md
   */
  describe('Type Consistency', () => {

    test('IDs are correct type', () => {
      // Direct model ID: integer
      expect(typeof testModel.id).toBe('number');
    });

    test('numeric fields match validation', () => {
      // Known issue: some numeric fields returned as strings
      // Document expected types from VALIDATED_SHAPES.md
      expect(typeof testModel.numeric_field).toBe('string'); // Expected!
      expect(testModel.numeric_field).toBe('0');
    });

    test('timestamps are consistent format', () => {
      expect(testModel.created_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
      expect(testModel.updated_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
    });
  });

  /**
   * RELATIONSHIP TESTS
   * Validate polymorphic pivot structures if applicable
   */
  describe('Relationship Management', () => {

    test('attach relationship creates pivot entry', async () => {
      // Create related model
      const related = await client.callTool('[plugin]/create-related', {
        name: 'related',
      });

      // Attach relationship
      await client.callTool('[plugin]/attach-relation', {
        model_id: testModel.id,
        related_id: related.id,
      });

      // Fetch with relationship
      const response = await client.callTool('[plugin]/get-model', {
        id: testModel.id,
        with: ['relation1'],
      });

      // Validate pivot structure (from VALIDATED_SHAPES.md)
      expect(response.relation1).toHaveLength(1);
      expect(response.relation1[0].pivot).toMatchObject({
        object_id: expect.any(String), // Pivot IDs are strings!
        object_type: expect.any(String),
        created_at: expect.stringMatching(/^\d{4}-\d{2}-\d{2}/),
      });

      // Cleanup
      await client.callTool('[plugin]/delete-related', { id: related.id });
    });
  });

  /**
   * EDGE CASE TESTS
   * From VALIDATED_SHAPES.md edge cases section
   */
  describe('Edge Cases', () => {

    test('minimal data creation', async () => {
      const minimal = await client.callTool('[plugin]/create-model', {
        required_field: 'minimal',
      });

      // Computed fields return defaults, not NULL
      expect(minimal.computed_field).toBe(''); // Empty string, not null

      // Optional fields are NULL
      expect(minimal.optional_field1).toBeNull();

      await client.callTool('[plugin]/delete-model', { id: minimal.id });
    });

    test('NULL vs empty string handling', async () => {
      const response = await client.callTool('[plugin]/create-model', {
        required_field: 'test',
        optional_field1: '', // Empty string
      });

      // Verify how plugin handles empty strings (from validation)
      expect(response.optional_field1).toBe(''); // or null, depends on validation

      await client.callTool('[plugin]/delete-model', { id: response.id });
    });
  });
});
```

### 5.3 Run Test Suite

```bash
# Run all plugin tests
npm test tests/e2e/[plugin]/

# Run specific model tests
npm test tests/e2e/[plugin]/model-name.test.ts

# Run with coverage
npm test -- --coverage tests/e2e/[plugin]/
```

**Success Criteria**:
- ≥80% pass rate on first run
- All CRUD operations working
- Type consistency validated
- Relationship loading verified
- Edge cases handled

### 5.4 Fix Failures Iteratively

**Debugging Process**:
1. Read test failure message
2. Check VALIDATED_SHAPES.md for expected behavior
3. Verify ability implementation
4. Check plugin model source code
5. Fix issue
6. Re-run test
7. Document any discovered quirks

**Common Issues**:
- Missing fields → Add to input schema, use toArray()
- Type mismatches → Document in VALIDATED_SHAPES.md, adjust assertions
- Relationships not loading → Check 'with' parameter, verify eager loading
- Auto-generated fields → Document in validation, don't send in create

---

## Phase 6: Documentation & Refinement (Day 5)

### 6.1 Update Integration Documentation

**Create/Update INTEGRATION_COMPLETE.md**:
```markdown
# [Plugin Name] MCP Integration - Complete

**Integration Date**: YYYY-MM-DD
**Plugin Version**: X.X.X
**Test Pass Rate**: XX%
**Models Integrated**: N
**Total Abilities**: N

## Integration Summary

[Brief overview of what was integrated and key findings]

## Models Integrated

### Model 1
- **Abilities**: N operations
- **Test Coverage**: XX%
- **Key Features**: ...
- **Known Issues**: ...

[Repeat for each model]

## Validation Documentation

- **VALIDATED_SHAPES.md**: Complete API structures for all N models
- **Validation Scripts**: N PHP scripts in plugin root
- **Test Suite**: N test files with XX% pass rate

## Known Issues & Workarounds

1. **Issue Name**
   - **Problem**: Description
   - **Impact**: Severity
   - **Workaround**: Solution
   - **Tracked**: Issue link or "documented"

## Usage Examples

### Example 1: Create [Model]
```typescript
const model = await client.callTool('[plugin]/create-model', {
  required_field: 'value',
  optional_field: 'value',
});
```

### Example 2: Load with Relationships
```typescript
const model = await client.callTool('[plugin]/get-model', {
  id: 123,
  with: ['relation1', 'relation2'],
});
```

## Performance Notes

- Query optimization: [notes]
- Caching: [strategy]
- Bulk operations: [availability]

## Future Enhancements

- [ ] Feature 1 (Priority: Low)
- [ ] Feature 2 (Priority: Medium)
- [ ] Pro features integration
```

### 6.2 Code Quality Review

**Checklist**:
- [ ] All abilities use `toArray()` not manual selection
- [ ] All input schemas document complete field sets
- [ ] All descriptions mention "Relations not included on create"
- [ ] All get operations support `with` parameter
- [ ] Type consistency documented for all models
- [ ] Edge cases handled appropriately
- [ ] Permission callbacks appropriate
- [ ] Error handling comprehensive
- [ ] Code follows WordPress/PHP standards
- [ ] PHPDoc comments complete

**Run Linters**:
```bash
# PHP
npm run lint:php
vendor/bin/phpstan analyze classes/Adapters/[Plugin]

# TypeScript
npm run lint:js tests/e2e/[plugin]

# Fix auto-fixable issues
npm run lint:php:fix
```

### 6.3 Final Validation Run

**Execute All Validation Scripts**:
```bash
cd "/path/to/wordpress"

for script in wp-content/plugins/mcp-adapters/validate-[plugin]-*.php; do
  echo "=== Running $(basename $script) ==="
  php "$script"
  echo ""
done
```

**Success Criteria**:
- All validation scripts pass
- No unexpected errors
- All models validate correctly
- All relationships work
- All edge cases handled

---

## Phase 7: Integration Testing & Deployment (Ongoing)

### 7.1 Integration Testing

**Test in Real Scenarios**:
1. Create realistic data through abilities
2. Perform complex workflows
3. Test bulk operations with real volume
4. Verify data integrity
5. Check cascade behaviors
6. Test error recovery

### 7.2 Performance Testing

**Measure & Optimize**:
```php
// Add to abilities for performance monitoring
$start = microtime(true);
$result = Model::complexQuery()->get();
$duration = microtime(true) - $start;

if ($duration > 1.0) {
    error_log("Slow query in [ability]: {$duration}s");
}
```

**Optimization Checklist**:
- [ ] Indexes on queried fields
- [ ] Eager loading prevents N+1 queries
- [ ] Pagination on large datasets
- [ ] Caching for expensive operations
- [ ] Bulk operations for batch processing

### 7.3 Deployment

**Pre-Deployment Checklist**:
- [ ] All tests passing (≥80%)
- [ ] Code linted and standards-compliant
- [ ] Documentation complete
- [ ] Validation scripts successful
- [ ] No debug code left in
- [ ] Error logging appropriate
- [ ] Security review complete

**Git Workflow**:
```bash
# Feature branch
git checkout -b feature/[plugin]-integration

# Commit with meaningful messages
git add classes/Adapters/[Plugin]
git commit -m "feat([plugin]): Complete [Model] CRUD abilities

- Implement create/read/update/delete operations
- Use toArray() for complete field coverage
- Add relationship eager loading with 'with' parameter
- Test coverage: XX%

Refs: VALIDATED_SHAPES.md"

# Push and create PR
git push -u origin feature/[plugin]-integration
```

---

## Success Metrics

### Quantitative Metrics
- ✅ **Test Pass Rate**: ≥80% on first run
- ✅ **Model Coverage**: 100% of identified models
- ✅ **Field Coverage**: ≥95% of validated fields
- ✅ **Relationship Coverage**: 100% of documented relationships
- ✅ **Timeline**: Complete within estimated days

### Qualitative Metrics
- ✅ **Code Quality**: Follows all standards, no linter errors
- ✅ **Type Safety**: All type inconsistencies documented
- ✅ **Documentation**: Complete and accurate
- ✅ **Maintainability**: Clear, readable, well-structured
- ✅ **Reusability**: Patterns applicable to future integrations

---

## Lessons Learned & Process Improvements

### After Each Integration

**Document**:
1. What worked well?
2. What was challenging?
3. What would you do differently?
4. New patterns discovered?
5. Process improvements identified?

**Update This Guide**:
- Add new patterns to templates
- Update timelines based on actual duration
- Add common issues to troubleshooting
- Refine agent task templates
- Improve test patterns

---

## Quick Reference Checklist

### Day 1 Morning: Discovery
- [ ] Plugin directory located
- [ ] All models identified and listed
- [ ] Database tables documented
- [ ] Complexity assessment complete
- [ ] INTEGRATION_PLAN.md created

### Day 1 Afternoon: Validation
- [ ] Validation agents spawned (3-8 agents)
- [ ] All validation scripts created
- [ ] All validation docs created
- [ ] VALIDATED_SHAPES.md compiled
- [ ] All scripts execute successfully

### Day 2 Morning: Analysis
- [ ] Gap analysis complete (existing code vs validation)
- [ ] IMPLEMENTATION_PLAN.md or GAP_ANALYSIS.md created
- [ ] Work prioritized (High/Medium/Low)
- [ ] Roadmap defined

### Day 2-3: Implementation
- [ ] BaseAbility.php created
- [ ] All ability classes created
- [ ] All CRUD operations implemented
- [ ] Custom operations implemented
- [ ] All abilities use toArray()
- [ ] All schemas complete
- [ ] AbilityRegistry updated
- [ ] Abilities registered in WordPress

### Day 3-4: Testing
- [ ] Test infrastructure created
- [ ] Test files for all models
- [ ] CRUD tests implemented
- [ ] Relationship tests implemented
- [ ] Type consistency tests implemented
- [ ] Edge case tests implemented
- [ ] ≥80% pass rate achieved

### Day 5: Documentation & Polish
- [ ] INTEGRATION_COMPLETE.md created
- [ ] Code quality review complete
- [ ] All linters pass
- [ ] Final validation run successful
- [ ] Documentation accurate and complete

### Deployment
- [ ] All tests passing
- [ ] Security review complete
- [ ] Feature branch created
- [ ] Commits made with clear messages
- [ ] PR created for review

---

## Troubleshooting Common Issues

### Validation Scripts Fail

**Problem**: PHP scripts error when running validation

**Solutions**:
1. Check plugin is active: `wp plugin list`
2. Verify class namespaces: `grep -r "namespace" plugin-dir/`
3. Check WordPress loaded: Scripts must use WordPress bootstrap or `wp eval-file`
4. Verify database tables exist: `wp db query "SHOW TABLES LIKE 'wp_%'"`

### Tests Fail on First Run

**Problem**: Low pass rate (<80%)

**Common Causes**:
1. **Type mismatches**: Check VALIDATED_SHAPES.md for expected types
2. **Missing fields**: Use toArray() in abilities, not manual selection
3. **Relationships not loading**: Add `with` parameter support
4. **Auto-generated fields**: Don't send in create, verify in response

### Agents Don't Complete

**Problem**: Validation agents stall or error

**Solutions**:
1. Check agent prompts are clear and complete
2. Verify file paths are correct (absolute paths)
3. Break complex models into smaller chunks
4. Re-spawn failed agent with clearer instructions

### Plugin Classes Not Found

**Problem**: `class_exists()` returns false

**Solutions**:
1. Verify plugin is active
2. Check namespace spelling
3. Ensure plugin fully loaded before ability registration
4. Use higher priority hook (20-30) for registration

---

## Appendix: Template Files

### A. Validation Script Minimal Template

```php
<?php
/**
 * Quick validation for [Model]
 * Run: cd "/path/to/wp" && php /path/to/script.php
 */

// WordPress bootstrap (if not using wp eval-file)
require_once '/path/to/wp-load.php';

echo "=== [Model] Validation ===\n\n";

// 1. Check model exists
$class = '\Plugin\Model';
if (!class_exists($class)) {
    die("Model class not found\n");
}

// 2. Create test instance
$model = $class::create(['field' => 'test']);
echo "Created: " . json_encode($model->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 3. Fetch with relationships
$loaded = $class::with(['relation'])->find($model->id);
echo "With Relations: " . json_encode($loaded->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// 4. Cleanup
$model->delete();
echo "Validation complete\n";
```

### B. Test File Minimal Template

```typescript
import { MCPClient } from "../../utils/mcp-client";
import { PLUGIN_CONFIG } from "../../utils/test-config";

describe('Plugin Model', () => {
  let client: MCPClient;
  let testModel: any;

  beforeAll(() => {
    client = new MCPClient(PLUGIN_CONFIG);
  });

  afterAll(async () => {
    if (testModel?.id) {
      await client.callTool('plugin/delete-model', { id: testModel.id });
    }
  });

  test('create and verify structure', async () => {
    testModel = await client.callTool('plugin/create-model', {
      required_field: 'value',
    });

    expect(testModel).toHaveProperty('id');
    expect(typeof testModel.id).toBe('number');
  });

  test('get with relationships', async () => {
    const response = await client.callTool('plugin/get-model', {
      id: testModel.id,
      with: ['relation'],
    });

    expect(response).toHaveProperty('relation');
  });
});
```

---

## Version History

- **v1.0** (2025-10-04): Initial process based on FluentBoards/FluentCRM integrations
- **Future**: Process refinements based on new integration experiences

---

**This process is a living document. Update it with every integration to improve future work.**
