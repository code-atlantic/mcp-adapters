# Cursor Agent Task: Implement FluentBoards Tasks Test Suite

## 🎯 Objective
Implement a **complete, production-ready E2E test suite** for FluentBoards Tasks ability (13 tools) with 100% parameter coverage, following the proven FluentCRM testing patterns.

**Complexity Level**: HIGH - Tasks is the largest and most complex FluentBoards ability with task creation, updates, movement, cloning, archiving, and assignment operations.

## 📁 Files You Need

### Source File (READ THIS FIRST)
**Location**: `classes/Adapters/FluentBoards/Abilities/Tasks.php`
- Contains all 13 task management tools
- Each tool has `wp_register_ability()` with complete input schemas
- Study the input_schema for EVERY parameter (required, optional, types, validation)
- Study the execute callbacks for expected return structures

### Test File to Implement
**Location**: `tests/e2e/fluentboards/abilities/tasks.test.ts`
- Currently a scaffold with TODOs
- Replace ALL placeholder tests with comprehensive implementations

### Reference Pattern (STUDY THIS)
**Location**: `tests/e2e/fluentcrm/abilities/campaigns.test.ts`
- Similar complexity level with CRUD operations
- Shows how to structure tests, handle cleanup, test all parameters
- Copy this testing style exactly

### MCP Client Utils
**Location**: `tests/e2e/utils/mcp-client.ts`
- Contains MCPClient class for API calls
- `generateTestTitle()` helper for unique test data
- FLUENTBOARDS_CONFIG with auth credentials

## 🔧 API Documentation

### Available Tools (from Tasks.php)
Study each `wp_register_ability()` registration to find:

1. **fluentboards-create-task**
   - Parameters: board_id (required), title (required), stage_id (required), description, assignees (array), labels (array), due_at (datetime), started_at, priority, lead_value, remind_at, reminder_type, status, type, scope, source, crm_contact_id, is_template, settings (object)
   - Returns: {success, message, data: {task object with id}}
   - **CRITICAL**: Many optional parameters with specific formats

2. **fluentboards-list-tasks**
   - Parameters: board_id (required), stage_id, search
   - Returns: {success, data: {tasks array}}

3. **fluentboards-get-task**
   - Parameters: board_id (required), task_id (required)
   - Returns: {success, data: {complete task details with comments, attachments}}

4. **fluentboards-update-task**
   - Parameters: board_id, task_id (both required), title, description, assignees, due_at, started_at, priority, lead_value, remind_at, reminder_type, stage_id, status, scope, source, crm_contact_id, log_minutes, settings
   - Returns: {success, message, data: {updated task}}

5. **fluentboards-delete-task**
   - Parameters: board_id, task_id (both required), confirm_delete (required boolean)
   - Returns: {success, message}

6. **fluentboards-clone-task**
   - Parameters: board_id, task_id, title, stage_id (all required), target_board_id, assignee (boolean), label (boolean), subtask (boolean), attachment (boolean), comment (boolean)
   - Returns: {success, data: {cloned task}}

7. **fluentboards-archive-task**
   - Parameters: board_id, task_id (both required)
   - Returns: {success, message}

8. **fluentboards-restore-task**
   - Parameters: board_id, task_id (both required)
   - Returns: {success, message}

9. **fluentboards-move-task**
   - Parameters: board_id, task_id, newStageId, newIndex (all required), newBoardId (optional)
   - Returns: {success, message}

10. **fluentboards-change-task-status**
    - Parameters: board_id, task_id, stage_id (all required)
    - Returns: {success, message}

11. **fluentboards-assign-yourself-to-task**
    - Parameters: board_id, task_id (both required)
    - Returns: {success, message}

12. **fluentboards-detach-yourself-from-task**
    - Parameters: board_id, task_id (both required)
    - Returns: {success, message}

13. **fluentboards-get-stage-positions**
    - Parameters: board_id, stage_id (both required)
    - Returns: {success, data: {available positions array}}

**CRITICAL**: Read `Tasks.php` input_schema for each tool to get:
- Exact parameter names
- Required vs optional parameters
- Data types (integer, string, boolean, array, object)
- Validation rules (minimum, maximum, enum values, patterns)
- DateTime formats (Y-m-d H:i:s)
- Priority enum values (low, medium, high)
- Status enum values (open, closed)
- Type enum values (task, milestone, roadmap)

## ✅ What to Test (EVERYTHING)

### For EACH Tool, Test:

#### 1. **Happy Path (Required)**
```typescript
it("should create task with minimal required fields", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: generateTestTitle("Task"),
    stage_id: testStageId,
  });

  expect(result.success).toBe(true);
  expect(result.data).toHaveProperty("id");
  expect(result.data.title).toContain("Task");
  testTaskIds.push(result.data.id); // Track for cleanup
});
```

#### 2. **All Parameters (Optional Fields)**
```typescript
it("should create task with all optional parameters", async () => {
  const dueDate = "2025-12-31 23:59:59";
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: generateTestTitle("Task"),
    stage_id: testStageId,
    description: "Test description with <b>HTML</b>",
    assignees: [1], // Current user ID
    labels: testLabelIds.length > 0 ? [testLabelIds[0]] : [],
    due_at: dueDate,
    started_at: "2025-01-01 00:00:00",
    priority: "high",
    lead_value: 1500.50,
    remind_at: "2025-12-30 09:00:00",
    reminder_type: "email",
    status: "open",
    type: "task",
    scope: "feature",
    source: "api",
  });

  expect(result.success).toBe(true);
  expect(result.data.description).toContain("HTML");
  expect(result.data.priority).toBe("high");
  expect(result.data.lead_value).toBe(1500.50);
  testTaskIds.push(result.data.id);
});
```

#### 3. **Validation Tests**
```typescript
// Missing required parameter
it("should reject missing stage_id", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
  });

  expect(result.success).toBe(false);
  expect(result.message).toContain("stage_id");
});

// Invalid enum value
it("should reject invalid priority value", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
    stage_id: testStageId,
    priority: "urgent", // Invalid: must be low, medium, high
  });

  expect(result.success).toBe(false);
});

// Invalid datetime format
it("should reject invalid due_at format", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
    stage_id: testStageId,
    due_at: "2025-12-31", // Invalid: needs time component
  });

  expect(result.success).toBe(false);
});

// Invalid array type
it("should reject invalid assignees type", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
    stage_id: testStageId,
    assignees: "not-an-array",
  });

  expect(result.success).toBe(false);
});
```

#### 4. **Edge Cases**
```typescript
it("should handle very long task description", async () => {
  const longDesc = "A".repeat(10000);
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
    stage_id: testStageId,
    description: longDesc,
  });

  expect(result.success).toBe(true);
});

it("should handle HTML and special characters in description", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test™ & <Special> \"Chars\"",
    stage_id: testStageId,
    description: "<script>alert('xss')</script><p>Normal text</p>",
  });

  expect(result.success).toBe(true);
});

it("should handle non-existent board_id", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: 999999,
    title: "Test",
    stage_id: testStageId,
  });

  expect(result.success).toBe(false);
});

it("should handle maximum lead_value", async () => {
  const result = await mcp.callTool("fluentboards-create-task", {
    board_id: testBoardId,
    title: "Test",
    stage_id: testStageId,
    lead_value: 9999999.99, // Maximum allowed
  });

  expect(result.success).toBe(true);
  expect(result.data.lead_value).toBe(9999999.99);
});
```

#### 5. **Complex Operations Tests**
```typescript
it("should clone task with all options enabled", async () => {
  const result = await mcp.callTool("fluentboards-clone-task", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: generateTestTitle("Cloned Task"),
    stage_id: testStageId,
    assignee: true,
    label: true,
    subtask: true,
    attachment: true,
    comment: true,
  });

  expect(result.success).toBe(true);
  expect(result.data.title).toContain("Cloned Task");
  testTaskIds.push(result.data.id);
});

it("should move task to different stage", async () => {
  const result = await mcp.callTool("fluentboards-move-task", {
    board_id: testBoardId,
    task_id: testTaskId,
    newStageId: testStageId2,
    newIndex: 0,
  });

  expect(result.success).toBe(true);
});

it("should archive and restore task", async () => {
  const archiveResult = await mcp.callTool("fluentboards-archive-task", {
    board_id: testBoardId,
    task_id: testTaskId,
  });

  expect(archiveResult.success).toBe(true);

  const restoreResult = await mcp.callTool("fluentboards-restore-task", {
    board_id: testBoardId,
    task_id: testTaskId,
  });

  expect(restoreResult.success).toBe(true);
});
```

#### 6. **Response Structure Validation**
```typescript
it("should return complete task object structure", async () => {
  const result = await mcp.callTool("fluentboards-get-task", {
    board_id: testBoardId,
    task_id: testTaskId,
  });

  expect(result.success).toBe(true);
  expect(result.data).toHaveProperty("id");
  expect(result.data).toHaveProperty("title");
  expect(result.data).toHaveProperty("description");
  expect(result.data).toHaveProperty("board_id");
  expect(result.data).toHaveProperty("stage_id");
  expect(result.data).toHaveProperty("priority");
  expect(result.data).toHaveProperty("status");
  expect(result.data).toHaveProperty("created_at");
  expect(result.data).toHaveProperty("updated_at");
  expect(typeof result.data.id).toBe("number");
});
```

## 🏗️ Test Structure Template

```typescript
describe("FluentBoards Tasks", () => {
  let mcp: MCPClient;
  const testTaskIds: number[] = [];
  const testBoardIds: number[] = [];
  const testLabelIds: number[] = [];
  let testBoardId: number;
  let testStageId: number;
  let testStageId2: number;
  let testTaskId: number;

  beforeAll(async () => {
    mcp = new MCPClient(
      FLUENTBOARDS_CONFIG.baseURL,
      FLUENTBOARDS_CONFIG.username,
      FLUENTBOARDS_CONFIG.password,
    );

    // Create test board with stages
    const boardResult = await mcp.callTool("fluentboards-create-board", {
      title: generateTestTitle("Test Board"),
    });
    testBoardId = boardResult.data.id;
    testBoardIds.push(testBoardId);
    testStageId = boardResult.data.stages[0].id;

    // Create second stage for move tests
    const stageResult = await mcp.callTool("fluentboards-create-stage", {
      board_id: testBoardId,
      title: "Second Stage",
    });
    testStageId2 = stageResult.data.id;

    // Create test labels for assignment tests
    const labelResult = await mcp.callTool("fluentboards-create-label", {
      board_id: testBoardId,
      title: "Test Label",
      bg_color: "#4bce97",
      color: "#000000",
    });
    testLabelIds.push(labelResult.data.id);

    // Create a base task for update/move/clone tests
    const taskResult = await mcp.callTool("fluentboards-create-task", {
      board_id: testBoardId,
      title: "Base Test Task",
      stage_id: testStageId,
    });
    testTaskId = taskResult.data.id;
    testTaskIds.push(testTaskId);
  });

  afterAll(async () => {
    // Cleanup tasks
    for (const taskId of testTaskIds) {
      await mcp.callTool("fluentboards-delete-task", {
        board_id: testBoardId,
        task_id: taskId,
        confirm_delete: true,
      });
    }

    // Cleanup labels
    for (const labelId of testLabelIds) {
      await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: labelId,
      });
    }

    // Cleanup boards (cascades to stages)
    for (const boardId of testBoardIds) {
      await mcp.callTool("fluentboards-delete-board", {
        board_id: boardId,
        confirm_delete: true,
      });
    }
  });

  describe("Create Task", () => {
    // 10-15 tests covering all parameters and edge cases
  });

  describe("List Tasks", () => {
    // 5-7 tests for listing with filters
  });

  describe("Get Task", () => {
    // 5-7 tests for retrieving specific task
  });

  describe("Update Task", () => {
    // 10-12 tests for updates
  });

  describe("Delete Task", () => {
    // 5-7 tests for deletion with confirmation
  });

  describe("Clone Task", () => {
    // 8-10 tests for cloning with various options
  });

  describe("Archive/Restore Task", () => {
    // 5-7 tests for archiving and restoring
  });

  describe("Move Task", () => {
    // 7-9 tests for moving between stages/boards
  });

  describe("Change Task Status", () => {
    // 5-7 tests for status changes
  });

  describe("Assign/Detach User", () => {
    // 5-7 tests for user assignments
  });

  describe("Stage Positions", () => {
    // 3-5 tests for position management
  });
});
```

## 🧪 How to Run Tests

### Run Single Suite
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/tasks.test.ts --no-coverage
```

### Watch Mode (for development)
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/tasks.test.ts --watch
```

### Run with Coverage
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/tasks.test.ts
```

### Expected Output
```
PASS fluentboards/abilities/tasks.test.ts
  FluentBoards Tasks
    Create Task
      ✓ should create task with minimal required fields
      ✓ should create task with all optional parameters
      ✓ should reject missing stage_id
      ... (100-120 tests total)

Test Suites: 1 passed, 1 total
Tests:       112 passed, 112 total
```

## ✅ Success Criteria

Your implementation is complete when:

1. **All 13 tools have comprehensive tests** (8-12 tests per tool minimum)
2. **Every parameter is tested** (required, optional, validation)
3. **All enum values tested** (priority: low/medium/high, status: open/closed, type: task/milestone/roadmap)
4. **DateTime formats validated** (Y-m-d H:i:s format)
5. **All edge cases covered** (long strings, special chars, invalid IDs, maximum values)
6. **Complex operations tested** (clone with options, move between stages, archive/restore)
7. **Response structures validated** (check all expected fields)
8. **Cleanup works properly** (afterAll removes all test data)
9. **Tests pass 100%** (run the test command and get all green)
10. **Target: 100-120 tests total** for Tasks ability

## 🚫 Common Mistakes to Avoid

1. **DON'T skip parameter validation tests** - Tasks has the most parameters
2. **DON'T forget cleanup** - Track all created IDs and delete in afterAll
3. **DON'T hardcode IDs** - Use dynamic test data generation
4. **DON'T assume success** - Test both success AND failure cases
5. **DON'T skip response validation** - Check structure, not just success flag
6. **DON'T test in isolation** - Tasks need boards and stages to exist first
7. **DON'T forget datetime format** - Use "Y-m-d H:i:s" format (2025-12-31 23:59:59)
8. **DON'T ignore enum validation** - Test all valid enum values and reject invalid ones
9. **DON'T skip complex operations** - Clone, move, archive/restore are critical
10. **DON'T forget confirm_delete** - Delete requires confirmation boolean

## 📚 Resources

- **FluentCRM Reference**: `tests/e2e/fluentcrm/abilities/campaigns.test.ts` (STUDY THIS)
- **Source Code**: `classes/Adapters/FluentBoards/Abilities/Tasks.php`
- **Jest Docs**: https://jestjs.io/docs/expect
- **MCP Client**: `tests/e2e/utils/mcp-client.ts`

## 🎯 Your Task

1. Read `Tasks.php` completely - understand all 13 tools with their schemas
2. Study `campaigns.test.ts` - learn the complex testing pattern
3. Implement comprehensive tests in `tasks.test.ts`
4. Run tests until 100% pass
5. Aim for 100-120 high-quality tests covering everything

**Special Focus Areas**:
- DateTime format validation (Y-m-d H:i:s)
- Enum validation (priority, status, type, reminder_type)
- Array parameters (assignees, labels)
- Complex operations (clone, move, archive/restore)
- Maximum value constraints (lead_value: 9999999.99)

Good luck! Tasks is the most complex ability - thorough testing is critical.
