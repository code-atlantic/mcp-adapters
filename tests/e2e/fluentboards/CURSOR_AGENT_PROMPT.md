# Cursor Agent Task: Implement FluentBoards Labels Test Suite

## 🎯 Objective
Implement a **complete, production-ready E2E test suite** for FluentBoards Labels ability (8 tools) with 100% parameter coverage, following the proven FluentCRM testing patterns.

## 📁 Files You Need

### Source File (READ THIS FIRST)
**Location**: `classes/Adapters/FluentBoards/Abilities/Labels.php`
- Contains all 8 label management tools
- Each tool has `wp_register_ability()` with complete input schemas
- Study the input_schema for EVERY parameter (required, optional, types, validation)
- Study the execute callbacks for expected return structures

### Test File to Implement
**Location**: `tests/e2e/fluentboards/abilities/labels.test.ts`
- Currently a scaffold with TODOs
- Replace ALL placeholder tests with comprehensive implementations

### Reference Pattern (STUDY THIS)
**Location**: `tests/e2e/fluentcrm/abilities/tags.test.ts`
- This is the GOLD STANDARD pattern to follow
- Shows how to structure tests, handle cleanup, test all parameters
- Copy this testing style exactly

### MCP Client Utils
**Location**: `tests/e2e/utils/mcp-client.ts`
- Contains MCPClient class for API calls
- `generateTestTitle()` helper for unique test data
- TEST_CONFIG with auth credentials

## 🔧 API Documentation

### Available Tools (from Labels.php)
Study each `wp_register_ability()` registration to find:

1. **fluentboards-create-label**
   - Parameters: board_id (required), title (required), bg_color, color
   - Returns: {success, message, data: {label object with id}}

2. **fluentboards-list-labels**
   - Parameters: board_id (required), used_only (optional boolean)
   - Returns: {success, data: {labels array}}

3. **fluentboards-update-label**
   - Parameters: board_id, label_id (both required), title, bg_color, color (optional)
   - Returns: {success, message, data: {updated label}}

4. **fluentboards-delete-label**
   - Parameters: board_id, label_id (both required)
   - Returns: {success, message}

5. **fluentboards-add-label-to-task**
   - Parameters: board_id, task_id, label_id (all required)
   - Returns: {success, message}

6. **fluentboards-remove-label-from-task**
   - Parameters: board_id, task_id, label_id (all required)
   - Returns: {success, message}

7. **fluentboards-get-task-labels**
   - Parameters: board_id, task_id (both required)
   - Returns: {success, data: {labels array}}

8. **fluentboards-get-label** (if exists - check the file)
   - Parameters: board_id, label_id
   - Returns: {success, data: {label details}}

**CRITICAL**: Read `Labels.php` input_schema for each tool to get:
- Exact parameter names
- Required vs optional parameters
- Data types (integer, string, boolean, array)
- Validation rules (minimum, maximum, enum values, patterns)

## ✅ What to Test (EVERYTHING)

### For EACH Tool, Test:

#### 1. **Happy Path (Required)**
```typescript
it("should create label with minimal required fields", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: generateTestTitle("Label"),
  });

  expect(result.success).toBe(true);
  expect(result.data).toHaveProperty("id");
  expect(result.data.title).toContain("Label");
  testLabelIds.push(result.data.id); // Track for cleanup
});
```

#### 2. **All Parameters (Optional Fields)**
```typescript
it("should create label with all optional parameters", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: generateTestTitle("Label"),
    bg_color: "#4bce97",
    color: "#000000",
  });

  expect(result.success).toBe(true);
  expect(result.data.bg_color).toBe("#4bce97");
  expect(result.data.color).toBe("#000000");
});
```

#### 3. **Validation Tests**
```typescript
// Missing required parameter
it("should reject missing board_id", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    title: "Test",
  });

  expect(result.success).toBe(false);
  expect(result.message).toContain("board_id");
});

// Invalid data type
it("should reject invalid board_id type", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: "not-a-number",
    title: "Test",
  });

  expect(result.success).toBe(false);
});

// Invalid color format (if validated)
it("should reject invalid color format", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: "Test",
    bg_color: "not-a-hex-color",
  });

  expect(result.success).toBe(false);
});
```

#### 4. **Edge Cases**
```typescript
it("should handle very long label title", async () => {
  const longTitle = "A".repeat(255);
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: longTitle,
  });

  expect(result.success).toBe(true);
});

it("should handle special characters in title", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: "Test™ & <Special> \"Chars\"",
  });

  expect(result.success).toBe(true);
});

it("should handle non-existent board_id", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: 999999,
    title: "Test",
  });

  expect(result.success).toBe(false);
});
```

#### 5. **Response Structure Validation**
```typescript
it("should return complete label object structure", async () => {
  const result = await mcp.callTool("fluentboards-create-label", {
    board_id: testBoardId,
    title: "Test",
  });

  expect(result.success).toBe(true);
  expect(result).toHaveProperty("message");
  expect(result).toHaveProperty("data");
  expect(result.data).toHaveProperty("id");
  expect(result.data).toHaveProperty("title");
  expect(result.data).toHaveProperty("bg_color");
  expect(result.data).toHaveProperty("color");
  expect(typeof result.data.id).toBe("number");
});
```

## 🏗️ Test Structure Template

```typescript
describe("FluentBoards Labels", () => {
  let mcp: MCPClient;
  const testLabelIds: number[] = [];
  const testBoardIds: number[] = [];
  const testTaskIds: number[] = [];
  let testBoardId: number;
  let testTaskId: number;

  beforeAll(async () => {
    mcp = new MCPClient(
      FLUENTBOARDS_CONFIG.baseURL,
      FLUENTBOARDS_CONFIG.username,
      FLUENTBOARDS_CONFIG.password,
    );

    // Create test board for all label tests
    const boardResult = await mcp.callTool("fluentboards-create-board", {
      title: generateTestTitle("Test Board"),
    });
    testBoardId = boardResult.data.id;
    testBoardIds.push(testBoardId);

    // Create test task for task-label association tests
    const taskResult = await mcp.callTool("fluentboards-create-task", {
      board_id: testBoardId,
      title: "Test Task",
      stage_id: boardResult.data.stages[0].id,
    });
    testTaskId = taskResult.data.id;
    testTaskIds.push(testTaskId);
  });

  afterAll(async () => {
    // Cleanup labels
    for (const labelId of testLabelIds) {
      await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: labelId,
      });
    }

    // Cleanup tasks
    for (const taskId of testTaskIds) {
      await mcp.callTool("fluentboards-delete-task", {
        board_id: testBoardId,
        task_id: taskId,
        confirm_delete: true,
      });
    }

    // Cleanup boards
    for (const boardId of testBoardIds) {
      await mcp.callTool("fluentboards-delete-board", {
        board_id: boardId,
        confirm_delete: true,
      });
    }
  });

  describe("Create Label", () => {
    // 5-10 tests covering all parameters and edge cases
  });

  describe("List Labels", () => {
    // 3-5 tests for listing with filters
  });

  describe("Get Label", () => {
    // 3-5 tests for retrieving specific label
  });

  describe("Update Label", () => {
    // 5-8 tests for updates
  });

  describe("Delete Label", () => {
    // 3-5 tests for deletion
  });

  describe("Add Label to Task", () => {
    // 5-7 tests for task associations
  });

  describe("Remove Label from Task", () => {
    // 3-5 tests for removing associations
  });

  describe("Get Task Labels", () => {
    // 3-5 tests for retrieving task's labels
  });
});
```

## 🧪 How to Run Tests

### Run Single Suite
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/labels.test.ts --no-coverage
```

### Watch Mode (for development)
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/labels.test.ts --watch
```

### Run with Coverage
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/labels.test.ts
```

### Expected Output
```
PASS fluentboards/abilities/labels.test.ts
  FluentBoards Labels
    Create Label
      ✓ should create label with minimal required fields
      ✓ should create label with all optional parameters
      ✓ should reject missing board_id
      ... (60-70 tests total)

Test Suites: 1 passed, 1 total
Tests:       67 passed, 67 total
```

## ✅ Success Criteria

Your implementation is complete when:

1. **All 8 tools have comprehensive tests** (8-10 tests per tool minimum)
2. **Every parameter is tested** (required, optional, validation)
3. **All edge cases covered** (long strings, special chars, invalid IDs)
4. **Response structures validated** (check all expected fields)
5. **Cleanup works properly** (afterAll removes all test data)
6. **Tests pass 100%** (run the test command and get all green)
7. **Target: 60-70 tests total** for Labels ability

## 🚫 Common Mistakes to Avoid

1. **DON'T skip parameter validation tests** - Test EVERY parameter
2. **DON'T forget cleanup** - Track all created IDs and delete in afterAll
3. **DON'T hardcode IDs** - Use dynamic test data generation
4. **DON'T assume success** - Test both success AND failure cases
5. **DON'T skip response validation** - Check structure, not just success flag
6. **DON'T copy/paste without understanding** - Each tool is different
7. **DON'T test in isolation** - Labels need boards and tasks to exist first

## 📚 Resources

- **FluentCRM Reference**: `tests/e2e/fluentcrm/abilities/tags.test.ts` (STUDY THIS)
- **Source Code**: `classes/Adapters/FluentBoards/Abilities/Labels.php`
- **Jest Docs**: https://jestjs.io/docs/expect
- **MCP Client**: `tests/e2e/utils/mcp-client.ts`

## 🎯 Your Task

1. Read `Labels.php` completely - understand all 8 tools
2. Study `tags.test.ts` - learn the testing pattern
3. Implement comprehensive tests in `labels.test.ts`
4. Run tests until 100% pass
5. Aim for 60-70 high-quality tests covering everything

Good luck! The goal is production-ready, comprehensive test coverage.
