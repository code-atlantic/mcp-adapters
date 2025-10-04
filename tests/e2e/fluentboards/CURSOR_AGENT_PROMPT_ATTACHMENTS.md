# Cursor Agent Task: Implement FluentBoards Attachments Test Suite

## 🎯 Objective
Implement a **complete, production-ready E2E test suite** for FluentBoards Attachments ability (6 tools) with 100% parameter coverage, following the proven FluentCRM testing patterns.

**Complexity Level**: MEDIUM - Attachments is a focused ability with file/link management operations.

## 📁 Files You Need

### Source File (READ THIS FIRST)
**Location**: `classes/Adapters/FluentBoards/Abilities/Attachments.php`
- Contains all 6 attachment management tools
- Each tool has `wp_register_ability()` with complete input schemas
- Study the input_schema for EVERY parameter (required, optional, types, validation)
- Study the execute callbacks for expected return structures

### Test File to Implement
**Location**: `tests/e2e/fluentboards/abilities/attachments.test.ts`
- Currently a scaffold with TODOs
- Replace ALL placeholder tests with comprehensive implementations

### Reference Pattern (STUDY THIS)
**Location**: `tests/e2e/fluentcrm/abilities/tags.test.ts`
- Similar complexity level with CRUD operations
- Shows how to structure tests, handle cleanup, test all parameters
- Copy this testing style exactly

### MCP Client Utils
**Location**: `tests/e2e/utils/mcp-client.ts`
- Contains MCPClient class for API calls
- `generateTestTitle()` helper for unique test data
- FLUENTBOARDS_CONFIG with auth credentials

## 🔧 API Documentation

### Available Tools (from Attachments.php)
Study each `wp_register_ability()` registration to find:

1. **fluentboards-add-task-attachment**
   - Parameters: board_id (required), task_id (required), title (required), url (required), type (optional: 'file' or 'link', default: 'link'), description (optional)
   - Returns: {success, message, data: {attachment object with id}}
   - **CRITICAL**: Type determines if it's a file upload or external link

2. **fluentboards-get-task-attachments**
   - Parameters: board_id (required), task_id (required)
   - Returns: {success, data: {attachments array}}

3. **fluentboards-get-attachment-files**
   - Parameters: board_id (required), task_id (required)
   - Returns: {success, data: {files array}}
   - **NOTE**: May require FluentBoards Pro - test for graceful handling

4. **fluentboards-update-attachment**
   - Parameters: board_id (required), task_id (required), attachment_id (required), title, url, description (all optional)
   - Returns: {success, message, data: {updated attachment}}

5. **fluentboards-delete-attachment**
   - Parameters: board_id (required), task_id (required), attachment_id (required), confirm_delete (required boolean)
   - Returns: {success, message}

6. **fluentboards-get-attachment** (if exists - verify in Attachments.php)
   - Parameters: board_id, task_id, attachment_id (all required)
   - Returns: {success, data: {attachment details}}

**CRITICAL**: Read `Attachments.php` input_schema for each tool to get:
- Exact parameter names
- Required vs optional parameters
- Data types (integer, string, boolean)
- Validation rules (enum values for type, URL format validation)
- Default values (type defaults to 'link')

## ✅ What to Test (EVERYTHING)

### For EACH Tool, Test:

#### 1. **Happy Path (Required)**
```typescript
it("should add link attachment with minimal required fields", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: generateTestTitle("Attachment"),
    url: "https://example.com/document.pdf",
  });

  expect(result.success).toBe(true);
  expect(result.data).toHaveProperty("id");
  expect(result.data.title).toContain("Attachment");
  expect(result.data.url).toBe("https://example.com/document.pdf");
  expect(result.data.type).toBe("link"); // Default type
  testAttachmentIds.push(result.data.id); // Track for cleanup
});
```

#### 2. **All Parameters (Optional Fields)**
```typescript
it("should add attachment with all optional parameters", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: generateTestTitle("Attachment"),
    url: "https://example.com/file.zip",
    type: "file",
    description: "This is a detailed description of the attachment",
  });

  expect(result.success).toBe(true);
  expect(result.data.type).toBe("file");
  expect(result.data.description).toBe("This is a detailed description of the attachment");
  testAttachmentIds.push(result.data.id);
});
```

#### 3. **Validation Tests**
```typescript
// Missing required parameter
it("should reject missing url", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Test",
  });

  expect(result.success).toBe(false);
  expect(result.message).toContain("url");
});

// Invalid enum value
it("should reject invalid type value", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Test",
    url: "https://example.com/file.pdf",
    type: "video", // Invalid: must be 'file' or 'link'
  });

  expect(result.success).toBe(false);
});

// Invalid URL format
it("should reject invalid URL format", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Test",
    url: "not-a-valid-url",
  });

  expect(result.success).toBe(false);
});

// Invalid data type
it("should reject invalid task_id type", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: "not-a-number",
    title: "Test",
    url: "https://example.com/file.pdf",
  });

  expect(result.success).toBe(false);
});

// Missing confirm_delete for deletion
it("should reject delete without confirmation", async () => {
  const result = await mcp.callTool("fluentboards-delete-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    attachment_id: testAttachmentId,
  });

  expect(result.success).toBe(false);
});
```

#### 4. **Edge Cases**
```typescript
it("should handle very long attachment title", async () => {
  const longTitle = "A".repeat(255);
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: longTitle,
    url: "https://example.com/file.pdf",
  });

  expect(result.success).toBe(true);
});

it("should handle very long description", async () => {
  const longDesc = "A".repeat(5000);
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Test",
    url: "https://example.com/file.pdf",
    description: longDesc,
  });

  expect(result.success).toBe(true);
});

it("should handle special characters in title", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Test™ & <Special> \"Chars\"",
    url: "https://example.com/file.pdf",
  });

  expect(result.success).toBe(true);
});

it("should handle various URL protocols", async () => {
  const protocols = [
    "https://example.com/file.pdf",
    "http://example.com/file.pdf",
    "ftp://example.com/file.pdf",
  ];

  for (const url of protocols) {
    const result = await mcp.callTool("fluentboards-add-task-attachment", {
      board_id: testBoardId,
      task_id: testTaskId,
      title: `Test ${url.split(":")[0]}`,
      url: url,
    });

    expect(result.success).toBe(true);
    testAttachmentIds.push(result.data.id);
  }
});

it("should handle non-existent task_id", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: 999999,
    title: "Test",
    url: "https://example.com/file.pdf",
  });

  expect(result.success).toBe(false);
});

it("should handle non-existent board_id", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: 999999,
    task_id: testTaskId,
    title: "Test",
    url: "https://example.com/file.pdf",
  });

  expect(result.success).toBe(false);
});
```

#### 5. **Type-Specific Tests**
```typescript
it("should add link type attachment", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "External Link",
    url: "https://github.com/anthropics/claude-code",
    type: "link",
  });

  expect(result.success).toBe(true);
  expect(result.data.type).toBe("link");
  testAttachmentIds.push(result.data.id);
});

it("should add file type attachment", async () => {
  const result = await mcp.callTool("fluentboards-add-task-attachment", {
    board_id: testBoardId,
    task_id: testTaskId,
    title: "Uploaded File",
    url: "https://example.com/uploads/document.pdf",
    type: "file",
  });

  expect(result.success).toBe(true);
  expect(result.data.type).toBe("file");
  testAttachmentIds.push(result.data.id);
});
```

#### 6. **Response Structure Validation**
```typescript
it("should return complete attachment object structure", async () => {
  const result = await mcp.callTool("fluentboards-get-task-attachments", {
    board_id: testBoardId,
    task_id: testTaskId,
  });

  expect(result.success).toBe(true);
  expect(result.data).toHaveProperty("attachments");
  expect(Array.isArray(result.data.attachments)).toBe(true);

  if (result.data.attachments.length > 0) {
    const attachment = result.data.attachments[0];
    expect(attachment).toHaveProperty("id");
    expect(attachment).toHaveProperty("title");
    expect(attachment).toHaveProperty("url");
    expect(attachment).toHaveProperty("type");
    expect(typeof attachment.id).toBe("number");
  }
});
```

## 🏗️ Test Structure Template

```typescript
describe("FluentBoards Attachments", () => {
  let mcp: MCPClient;
  const testAttachmentIds: number[] = [];
  const testTaskIds: number[] = [];
  const testBoardIds: number[] = [];
  let testBoardId: number;
  let testStageId: number;
  let testTaskId: number;
  let testAttachmentId: number;

  beforeAll(async () => {
    mcp = new MCPClient(
      FLUENTBOARDS_CONFIG.baseURL,
      FLUENTBOARDS_CONFIG.username,
      FLUENTBOARDS_CONFIG.password,
    );

    // Create test board
    const boardResult = await mcp.callTool("fluentboards-create-board", {
      title: generateTestTitle("Test Board"),
    });
    testBoardId = boardResult.data.id;
    testBoardIds.push(testBoardId);
    testStageId = boardResult.data.stages[0].id;

    // Create test task for attachments
    const taskResult = await mcp.callTool("fluentboards-create-task", {
      board_id: testBoardId,
      title: "Test Task for Attachments",
      stage_id: testStageId,
    });
    testTaskId = taskResult.data.id;
    testTaskIds.push(testTaskId);

    // Create a base attachment for update/delete tests
    const attachmentResult = await mcp.callTool("fluentboards-add-task-attachment", {
      board_id: testBoardId,
      task_id: testTaskId,
      title: "Base Attachment",
      url: "https://example.com/base.pdf",
    });
    testAttachmentId = attachmentResult.data.id;
    testAttachmentIds.push(testAttachmentId);
  });

  afterAll(async () => {
    // Cleanup attachments
    for (const attachmentId of testAttachmentIds) {
      await mcp.callTool("fluentboards-delete-attachment", {
        board_id: testBoardId,
        task_id: testTaskId,
        attachment_id: attachmentId,
        confirm_delete: true,
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

  describe("Add Task Attachment", () => {
    // 10-12 tests covering all parameters and edge cases
  });

  describe("Get Task Attachments", () => {
    // 5-7 tests for listing attachments
  });

  describe("Get Attachment Files", () => {
    // 3-5 tests (may require Pro, test graceful handling)
  });

  describe("Update Attachment", () => {
    // 7-9 tests for updates
  });

  describe("Delete Attachment", () => {
    // 5-7 tests for deletion with confirmation
  });

  describe("Get Single Attachment", () => {
    // 3-5 tests for retrieving specific attachment (if exists)
  });
});
```

## 🧪 How to Run Tests

### Run Single Suite
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/attachments.test.ts --no-coverage
```

### Watch Mode (for development)
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/attachments.test.ts --watch
```

### Run with Coverage
```bash
cd tests/e2e
npx jest --config jest.config.js fluentboards/abilities/attachments.test.ts
```

### Expected Output
```
PASS fluentboards/abilities/attachments.test.ts
  FluentBoards Attachments
    Add Task Attachment
      ✓ should add link attachment with minimal required fields
      ✓ should add attachment with all optional parameters
      ✓ should reject missing url
      ... (40-50 tests total)

Test Suites: 1 passed, 1 total
Tests:       47 passed, 47 total
```

## ✅ Success Criteria

Your implementation is complete when:

1. **All 6 tools have comprehensive tests** (7-10 tests per tool minimum)
2. **Every parameter is tested** (required, optional, validation)
3. **Type enum values tested** (file and link types)
4. **URL format validation tested** (valid and invalid URLs)
5. **All edge cases covered** (long strings, special chars, invalid IDs, various protocols)
6. **Response structures validated** (check all expected fields)
7. **Cleanup works properly** (afterAll removes all test data)
8. **Tests pass 100%** (run the test command and get all green)
9. **Target: 40-50 tests total** for Attachments ability
10. **Pro features handled gracefully** (get-attachment-files may require Pro)

## 🚫 Common Mistakes to Avoid

1. **DON'T skip parameter validation tests** - Test EVERY parameter
2. **DON'T forget cleanup** - Track all created IDs and delete in afterAll
3. **DON'T hardcode IDs** - Use dynamic test data generation
4. **DON'T assume success** - Test both success AND failure cases
5. **DON'T skip response validation** - Check structure, not just success flag
6. **DON'T test in isolation** - Attachments need boards and tasks to exist first
7. **DON'T ignore type validation** - Test both 'file' and 'link' types
8. **DON'T skip URL validation** - Test various protocols and invalid formats
9. **DON'T forget confirm_delete** - Delete requires confirmation boolean
10. **DON'T assume Pro features work** - Test graceful handling of Pro-only tools

## 📚 Resources

- **FluentCRM Reference**: `tests/e2e/fluentcrm/abilities/tags.test.ts` (STUDY THIS)
- **Source Code**: `classes/Adapters/FluentBoards/Abilities/Attachments.php`
- **Jest Docs**: https://jestjs.io/docs/expect
- **MCP Client**: `tests/e2e/utils/mcp-client.ts`

## 🎯 Your Task

1. Read `Attachments.php` completely - understand all 6 tools
2. Study `tags.test.ts` - learn the testing pattern
3. Implement comprehensive tests in `attachments.test.ts`
4. Run tests until 100% pass
5. Aim for 40-50 high-quality tests covering everything

**Special Focus Areas**:
- Type enum validation (file vs link)
- URL format validation
- confirm_delete requirement for deletions
- Pro feature graceful handling (get-attachment-files)
- Various URL protocols (http, https, ftp)

Good luck! Focus on thorough parameter and type validation testing.
