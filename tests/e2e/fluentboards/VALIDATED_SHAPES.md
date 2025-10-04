# FluentBoards Validated API Shapes

> **📚 Part of the Integration Guide Series:**
> - [API Validation Guide](../../../docs/API_VALIDATION_GUIDE.md) - How to validate any plugin
> - [Test Development Guide](../../../docs/TEST_DEVELOPMENT_GUIDE.md) - TDD methodology
> - [Integration Workflow](../../../docs/INTEGRATION_WORKFLOW.md) - Complete process
> - **Validated Shapes** (this document) - FluentBoards actual API structures

**Last Updated:** October 4, 2025
**Validation Method:** Direct model testing via WP-CLI (`wp eval-file`)

This document contains the **actual** response structures from FluentBoards models, validated against real database operations. Use this as the source of truth when writing tests or adapters.

## Validation Commands

```bash
# Run validation script
./wp-cli-direct.sh wp eval-file validate-fluentboards.php

# Check database schema
./wp-cli-direct.sh wp db query "DESCRIBE wp_fbs_boards"
./wp-cli-direct.sh wp db query "DESCRIBE wp_fbs_labels"
./wp-cli-direct.sh wp db query "DESCRIBE wp_fbs_relations"
./wp-cli-direct.sh wp db query "DESCRIBE wp_fbs_tasks"
```

## Board

### Create Board Response

```php
$board = new \FluentBoards\App\Models\Board();
$created = $board->create([
    'title' => 'Test Board',
    'description' => 'Test Description',
    'type' => 'to-do'
]);
```

**Actual Response:**
```json
{
    "title": "Test Board for Validation",
    "description": "Testing response structure",
    "type": "to-do",
    "created_by": 0,
    "background": {
        "id": "solid_15",
        "is_image": false,
        "image_url": null,
        "color": "#10ac84"
    },
    "id": 51,
    "meta": [],
    "isUserOnlyViewer": false
}
```

**Key Findings:**
- ❌ **NO `stages` array** - stages are NOT included in create response
- ✅ Has `background` object with default values
- ✅ `meta` is an empty array by default
- ✅ `created_by` is 0 (not null) when no user context

### Get Board with Relations

```php
$board = \FluentBoards\App\Models\Board::with(['stages'])->find($board_id);
```

**Actual Response:**
```json
{
    "id": 51,
    "parent_id": null,
    "title": "Test Board for Validation",
    "description": "Testing response structure",
    "type": "to-do",
    "currency": null,
    "background": {
        "id": "solid_15",
        "is_image": false,
        "image_url": null,
        "color": "#10ac84"
    },
    "settings": null,
    "created_by": "0",
    "archived_at": null,
    "meta": [],
    "isUserOnlyViewer": false,
    "stages": []
}
```

**Key Findings:**
- ⚠️ **`stages` is EMPTY ARRAY** - newly created boards don't have default stages
- ✅ Includes additional fields: `parent_id`, `currency`, `settings`, `archived_at`
- ⚠️ `created_by` changes from `0` (int) to `"0"` (string) when fetched

**Implication for Tests:**
```typescript
// ❌ WRONG - This will fail!
const boardResult = await mcp.callTool("fluentboards-create-board", {...});
const stageId = boardResult.data.board.stages[0].id; // Cannot read properties of undefined

// ✅ CORRECT - Fetch board details separately
const boardResult = await mcp.callTool("fluentboards-create-board", {...});
const boardDetails = await mcp.callTool("fluentboards-get-board", {
    board_id: boardResult.data.board.id
});
const stageId = boardDetails.data.board.stages[0]?.id;
```

## Label

### Create Label Response

```php
$label = new \FluentBoards\App\Models\Label();
$created = $label->create([
    'board_id' => 51,
    'title' => 'Test Label',
    'bg_color' => '#ff5733',
    'color' => '#ffffff'
]);
```

**Actual Response:**
```json
{
    "board_id": 51,
    "title": "Test Label",
    "bg_color": "#ff5733",
    "color": "#ffffff",
    "type": "label",
    "updated_at": "2025-10-04T17:34:53+00:00",
    "created_at": "2025-10-04T17:34:53+00:00",
    "id": 547
}
```

**Key Findings:**
- ✅ Has `type` field (value: "label")
- ✅ Includes timestamp fields in ISO 8601 format
- ✅ All provided fields are returned as-is

## Stage

### Create Stage Response

```php
$stage = new \FluentBoards\App\Models\Stage();
$created = $stage->create([
    'board_id' => 54,
    'title' => 'To Do',
    'position' => 1
]);
```

**Actual Response:**
```json
{
    "board_id": 54,
    "title": "To Do",
    "position": 1,
    "type": "stage",
    "settings": {
        "default_task_status": "open",
        "is_template": false
    },
    "updated_at": "2025-10-04T18:01:26+00:00",
    "created_at": "2025-10-04T18:01:26+00:00",
    "id": 550
}
```

**Key Findings:**
- ✅ Has `type` field (value: "stage")
- ✅ Auto-generates `settings` object with defaults if not provided
- ✅ Default `settings.default_task_status` is "open"
- ✅ Default `settings.is_template` is false
- ✅ `position` is required and determines order
- ⚠️ **Boards do NOT create default stages** - stages must be created explicitly
- ⚠️ `description` field is optional and not returned if not set
- ⚠️ `bg_color` field is optional and not returned if not set

### Create Stage with All Fields

```php
$stage = new \FluentBoards\App\Models\Stage();
$created = $stage->create([
    'board_id' => 54,
    'title' => 'In Progress',
    'description' => 'Work in progress',
    'position' => 2,
    'bg_color' => '#3498db',
    'settings' => json_encode(['default_task_status' => 'active']),
    'created_by' => 1
]);
```

**Actual Response:**
```json
{
    "board_id": 54,
    "title": "In Progress",
    "position": 2,
    "bg_color": "#3498db",
    "settings": {
        "default_task_status": "open",
        "is_template": false
    },
    "type": "stage",
    "updated_at": "2025-10-04T18:01:26+00:00",
    "created_at": "2025-10-04T18:01:26+00:00",
    "id": 551
}
```

**Key Findings:**
- ⚠️ **Settings are OVERRIDDEN with defaults** - custom settings may not persist
- ⚠️ `description` field is still NOT in response even when set
- ✅ `bg_color` appears when set
- ⚠️ `created_by` is not included in response

### Stage Archive (Soft Delete)

Stages use soft deletion with the `archived_at` timestamp:

```php
$stage->archived_at = date('Y-m-d H:i:s');
$stage->save();
```

**Listing Active Stages:**
```php
$active = $stage_model->where('board_id', $board_id)
                      ->whereNull('archived_at')
                      ->orderBy('position', 'ASC')
                      ->get();
```

**Listing Archived Stages:**
```php
$archived = $stage_model->where('board_id', $board_id)
                        ->whereNotNull('archived_at')
                        ->get();
```

**Key Findings:**
- ✅ Stages use soft delete pattern with `archived_at`
- ✅ Must filter by `whereNull('archived_at')` to get active stages
- ✅ Archived stages can be restored by setting `archived_at = null`

## Task

### Create Task Response

```php
$task = new \FluentBoards\App\Models\Task();
$created = $task->create([
    'board_id' => 51,
    'stage_id' => 123,  // Can be null!
    'title' => 'Test Task'
]);
```

**Actual Response:**
```json
{
    "board_id": 51,
    "stage_id": null,
    "title": "Test Task",
    "created_by": 0,
    "type": "task",
    "slug": "test-task",
    "settings": {
        "cover": {
            "backgroundColor": ""
        },
        "subtask_count": 0,
        "attachment_count": 0,
        "subtask_completed_count": 0
    },
    "position": 1,
    "updated_at": "2025-10-04T17:34:53+00:00",
    "created_at": "2025-10-04T17:34:53+00:00",
    "id": 77,
    "meta": [],
    "repeat_task_meta": null,
    "stage": null
}
```

**Key Findings:**
- ⚠️ **`stage_id` can be NULL** - tasks can exist without stages
- ✅ Auto-generates `slug` from title
- ✅ Has `settings` object with default structure
- ✅ Has `position` field (auto-incremented)
- ✅ Has `type` field (value: "task")
- ✅ `meta` is empty array by default
- ⚠️ `stage` relation is null in create response

## Relation (Label-Task Association)

### Create Relation Response

```php
$relation = new \FluentBoards\App\Models\Relation();
$created = $relation->create([
    'object_id' => 77,      // Task ID
    'object_type' => 'task',
    'foreign_id' => 547,    // Label ID
]);
```

**Actual Response:**
```json
{
    "object_id": 77,
    "object_type": "task",
    "foreign_id": 547,
    "id": 138
}
```

**⚠️ CRITICAL Schema Findings:**

The Relations table has a **DIFFERENT STRUCTURE** than what debug logs suggested:

**Actual Columns:**
- `id` - Primary key
- `object_id` - The task ID (the thing being labeled)
- `object_type` - Always 'task' for label associations
- `foreign_id` - The label ID (the label being applied)

**Columns that DON'T exist:**
- ❌ `foreign_type` - **Does NOT exist** (caused SQL errors in debug log)
- ❌ `board_id` - **Does NOT exist**
- ❌ `type` - **Does NOT exist** (object_type serves this purpose)

**Correct Query Pattern:**
```php
// ✅ CORRECT - Add label to task
$relation = new \FluentBoards\App\Models\Relation();
$relation->create([
    'object_id' => $task_id,
    'object_type' => 'task',
    'foreign_id' => $label_id,
]);

// ✅ CORRECT - Remove label from task
$relation->where('object_type', 'task')
         ->where('object_id', $task_id)
         ->where('foreign_id', $label_id)
         ->delete();

// ✅ CORRECT - Remove label from ALL tasks
$relation->where('object_type', 'task')
         ->where('foreign_id', $label_id)
         ->delete();
```

**Incorrect Query Pattern:**
```php
// ❌ WRONG - Causes SQL error
$relation->where('foreign_type', 'label')  // Column doesn't exist!
         ->where('object_id', $label_id)
         ->delete();
```

## Database Schema

### wp_fbs_relations Table

**Validated Structure:**
```sql
CREATE TABLE wp_fbs_relations (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    object_id BIGINT(20) UNSIGNED NOT NULL,     -- Task ID
    object_type VARCHAR(50) NOT NULL,            -- 'task'
    foreign_id BIGINT(20) UNSIGNED NOT NULL,     -- Label ID
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_object (object_type, object_id),
    INDEX idx_foreign (foreign_id)
);
```

**Relationship Direction:**
- **Task** is the "object" (has labels)
- **Label** is the "foreign" entity (belongs to tasks)

## Common Patterns

### Creating Test Data

```typescript
// 1. Create board
const boardResult = await mcp.callTool("fluentboards-create-board", {
  title: "Test Board"
});
const boardId = boardResult.data.board.id;

// 2. Get board details (needed for stages)
const boardDetails = await mcp.callTool("fluentboards-get-board", {
  board_id: boardId
});
const stageId = boardDetails.data.board.stages[0]?.id;

// 3. Create task (stage_id is optional!)
const taskResult = await mcp.callTool("fluentboards-create-task", {
  board_id: boardId,
  title: "Test Task",
  stage_id: stageId  // Can be omitted
});
const taskId = taskResult.data.task.id;

// 4. Create label
const labelResult = await mcp.callTool("fluentboards-create-label", {
  board_id: boardId,
  title: "Test Label",
  bg_color: "#ff5733",
  color: "#ffffff"
});
const labelId = labelResult.data.label.id;

// 5. Associate label with task
await mcp.callTool("fluentboards-add-label-to-task", {
  board_id: boardId,
  task_id: taskId,
  label_id: labelId
});
```

## Response Wrapper Structure

All MCP adapter responses follow this structure:

```typescript
{
  success: boolean,
  message: string,
  data: {
    // The actual response object(s)
  }
}
```

### Success Response Example
```json
{
  "success": true,
  "message": "Label created successfully",
  "data": {
    "label": {
      "id": 547,
      "board_id": 51,
      "title": "Test Label",
      "bg_color": "#ff5733",
      "color": "#ffffff",
      "type": "label",
      "created_at": "2025-10-04T17:34:53+00:00",
      "updated_at": "2025-10-04T17:34:53+00:00"
    }
  }
}
```

### Error Response Example
```json
{
  "success": false,
  "message": "Label not found",
  "data": {
    "error_code": "label_not_found"
  }
}
```

## Type Inconsistencies

### String vs Integer IDs

Some fields alternate between string and integer representations:

```typescript
// Created board
{
  "created_by": 0  // Number
}

// Fetched board
{
  "created_by": "0"  // String!
}
```

**Recommendation:** Always use loose equality or parseInt() when comparing IDs.

### Null vs Empty Array

```typescript
// New board
{
  "stages": []  // Empty array
}

// Task with no stage
{
  "stage": null  // Null, not empty object!
}
```

**Recommendation:** Check both `array.length` and null-ish values.

## Validation Script

Save this to `validate-fluentboards.php` in the WordPress root:

```php
<?php
require_once __DIR__ . '/wp-load.php';

// Create board
$board = new \FluentBoards\App\Models\Board();
$created = $board->create(['title' => 'Validation Test', 'type' => 'to-do']);
echo "Board: " . json_encode($created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Get board with stages
$with_stages = \FluentBoards\App\Models\Board::with(['stages'])->find($created->id);
echo "With Stages: " . json_encode($with_stages->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Create label
$label = new \FluentBoards\App\Models\Label();
$label_created = $label->create([
    'board_id' => $created->id,
    'title' => 'Test',
    'bg_color' => '#ff0000',
    'color' => '#ffffff'
]);
echo "Label: " . json_encode($label_created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Create task
$task = new \FluentBoards\App\Models\Task();
$task_created = $task->create([
    'board_id' => $created->id,
    'title' => 'Test Task'
]);
echo "Task: " . json_encode($task_created->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Create relation
$relation = new \FluentBoards\App\Models\Relation();
$rel = $relation->create([
    'object_id' => $task_created->id,
    'object_type' => 'task',
    'foreign_id' => $label_created->id
]);
echo "Relation: " . json_encode($rel->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Cleanup
$rel->delete();
$task_created->delete();
$label_created->delete();
$with_stages->delete();
```

Run with:
```bash
./wp-cli-direct.sh wp eval-file validate-fluentboards.php
```

## Testing Implications

### 1. Board Creation

**Don't assume stages exist immediately:**
```typescript
// ❌ BAD
const board = await createBoard();
const stage = board.data.board.stages[0]; // undefined!

// ✅ GOOD
const board = await createBoard();
const details = await getBoard(board.data.board.id);
const stage = details.data.board.stages[0];
```

### 2. Relations Cleanup

**Use correct query structure:**
```php
// ✅ Remove all labels from a task
$relation->where('object_type', 'task')
         ->where('object_id', $task_id)
         ->delete();

// ✅ Remove specific label from task  
$relation->where('object_type', 'task')
         ->where('object_id', $task_id)
         ->where('foreign_id', $label_id)
         ->delete();

// ✅ Remove label from all tasks (when deleting label)
$relation->where('object_type', 'task')
         ->where('foreign_id', $label_id)
         ->delete();
```

### 3. Test Data Isolation

**Always create fresh test data:**
```typescript
let testBoardId: number;
let testTaskId: number;

beforeAll(async () => {
  const board = await mcp.callTool("fluentboards-create-board", {
    title: generateTestTitle("Test Board")
  });
  testBoardId = board.data.board.id;
  
  const task = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test Task"
  });
  testTaskId = task.data.task.id;
});

afterAll(async () => {
  // Cleanup in reverse dependency order
  await mcp.callTool("fluentboards-delete-board", {
    board_id: testBoardId,
    confirm_delete: true
  });
});
```

## Contributing

When you discover new shape information:

1. **Validate** using the WP-CLI script method
2. **Document** the actual response with JSON
3. **Note** any discrepancies from expected behavior
4. **Update** this file with your findings
5. **Include** the validation date and method used

**Format:**
```markdown
## [Model Name]

### [Operation] Response

**Validation Date:** YYYY-MM-DD
**Method:** Direct model / REST API / Other

```php
// Code to reproduce
```

**Actual Response:**
```json
{
  // Actual JSON output
}
```

**Key Findings:**
- ✅ Expected behavior confirmed
- ❌ Missing expected field
- ⚠️ Unexpected behavior
```

