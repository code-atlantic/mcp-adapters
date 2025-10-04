/**
 * E2E Tests for FluentBoards Labels Abilities
 *
 * Tests all label management tools including CRUD operations,
 * task-label associations, and filtering.
 * 
 */

import { MCPClient, generateTestTitle } from "../../utils/mcp-client";

const FLUENTBOARDS_CONFIG = {
  baseURL: "http://mcp.local/wp-json/mcp-adapters/v1/fluentboards",
  username: "admin",
  password: "JvL0 sQrw Sis1 cKH9 7v43 Ta22",
};

describe("FluentBoards Labels", () => {
  let mcp: MCPClient;
  const testLabelIds: number[] = [];
  const testBoardIds: number[] = [];
  const testTaskIds: number[] = [];
  let testBoardId: number;
  let testTaskId: number;
  let testStageId: number;

  beforeAll(async () => {
    mcp = new MCPClient(
      FLUENTBOARDS_CONFIG.baseURL,
      FLUENTBOARDS_CONFIG.username,
      FLUENTBOARDS_CONFIG.password,
    );

    // Create test board for all label tests
    const boardResult = await mcp.callTool("fluentboards-create-board", {
      title: generateTestTitle("Labels Test Board"),
    });

    // Debug: log the response if it fails
    if (!boardResult.success || !boardResult.data) {
      console.error("Board creation failed:", JSON.stringify(boardResult, null, 2));
      throw new Error(`Failed to create test board: ${boardResult.message || "Unknown error"}`);
    }

    testBoardId = boardResult.data.board.id;
    testBoardIds.push(testBoardId);

    // Get first stage ID from created board
    if (!boardResult.data.board.stages || boardResult.data.board.stages.length === 0) {
      throw new Error("Created board has no stages");
    }
    testStageId = boardResult.data.board.stages[0].id;

    // Create test task for task-label association tests
    const taskResult = await mcp.callTool("fluentboards-create-task", {
      board_id: testBoardId,
      title: "Test Task for Labels",
      stage_id: testStageId,
    });
    
    if (!taskResult.success || !taskResult.data) {
      console.error("Task creation failed:", JSON.stringify(taskResult, null, 2));
      throw new Error(`Failed to create test task: ${taskResult.message || "Unknown error"}`);
    }
    
    testTaskId = taskResult.data.task.id;
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
    it("should create label with all required fields", async () => {
      const title = generateTestTitle("Label");
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: title,
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(true);
      expect(result.data.label).toHaveProperty("id");
      expect(result.data.label.title).toBe(title);
      expect(result.data.label.bg_color).toBe("#4bce97");
      expect(result.data.label.color).toBe("#000000");
      expect(result.data.label.board_id).toBe(testBoardId);
      expect(result.message).toContain("created successfully");

      testLabelIds.push(result.data.label.id);
    });

    it("should create label with different color combinations", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Blue Label"),
        bg_color: "#0079bf",
        color: "#ffffff",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.bg_color).toBe("#0079bf");
      expect(result.data.label.color).toBe("#ffffff");

      testLabelIds.push(result.data.label.id);
    });

    it("should create label with uppercase hex colors", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Uppercase Color"),
        bg_color: "#FF5733",
        color: "#FFFFFF",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.bg_color).toBeTruthy();
      expect(result.data.label.color).toBeTruthy();

      testLabelIds.push(result.data.label.id);
    });

    it("should return complete label object structure", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Structure Test"),
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(true);
      expect(result).toHaveProperty("message");
      expect(result).toHaveProperty("data");
      expect(result.data).toHaveProperty("label");
      expect(result.data.label).toHaveProperty("id");
      expect(result.data.label).toHaveProperty("title");
      expect(result.data.label).toHaveProperty("bg_color");
      expect(result.data.label).toHaveProperty("color");
      expect(result.data.label).toHaveProperty("board_id");
      expect(result.data.label).toHaveProperty("created_at");
      expect(typeof result.data.label.id).toBe("number");

      testLabelIds.push(result.data.label.id);
    });

    it("should handle special characters in title", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: 'Test™ & <Special> "Chars"',
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.title).toBeTruthy();

      testLabelIds.push(result.data.label.id);
    });

    it("should handle very long label title", async () => {
      const longTitle = "A".repeat(255);
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: longTitle,
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(true);

      testLabelIds.push(result.data.label.id);
    });

    it("should reject missing required board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        title: "Test",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required title", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required bg_color", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "Test",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required color", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "Test",
        bg_color: "#4bce97",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id type", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: "not-a-number",
        title: "Test",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid bg_color format (no hash)", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "Test",
        bg_color: "4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid bg_color format (too short)", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "Test",
        bg_color: "#fff",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid color format (invalid chars)", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "Test",
        bg_color: "#4bce97",
        color: "#gggggg",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: 999999,
        title: "Test",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject zero board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: 0,
        title: "Test",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject negative board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: -1,
        title: "Test",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty title", async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: "",
        bg_color: "#4bce97",
        color: "#000000",
      });

      expect(result.success).toBe(false);
    });

    it("should reject duplicate label title in same board", async () => {
      const title = generateTestTitle("Duplicate");

      const firstResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: title,
        bg_color: "#4bce97",
        color: "#000000",
      });
      testLabelIds.push(firstResult.data.label.id);

      const secondResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: title,
        bg_color: "#0079bf",
        color: "#ffffff",
      });

      expect(secondResult.success).toBe(false);
      expect(secondResult.message).toContain("already exists");
    });
  });

  describe("List Labels", () => {
    beforeAll(async () => {
      // Create multiple labels for listing tests
      for (let i = 0; i < 5; i++) {
        const result = await mcp.callTool("fluentboards-create-label", {
          board_id: testBoardId,
          title: generateTestTitle(`List Label ${i}`),
          bg_color: "#4bce97",
          color: "#000000",
        });
        testLabelIds.push(result.data.label.id);
      }
    });

    it("should list all labels in a board", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("board_id");
      expect(result.data).toHaveProperty("labels");
      expect(result.data).toHaveProperty("total_labels");
      expect(result.data).toHaveProperty("used_only_filter");
      expect(Array.isArray(result.data.labels)).toBe(true);
      expect(result.data.labels.length).toBeGreaterThan(0);
      expect(result.data.board_id).toBe(testBoardId);
    });

    it("should include label details in list", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.labels.length > 0) {
        const label = result.data.labels[0];
        expect(label).toHaveProperty("id");
        expect(label).toHaveProperty("title");
        expect(label).toHaveProperty("bg_color");
        expect(label).toHaveProperty("color");
        expect(label).toHaveProperty("created_at");
      }
    });

    it("should list labels with used_only filter false", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: testBoardId,
        used_only: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.used_only_filter).toBe(false);
      expect(result.data.labels.length).toBeGreaterThan(0);
    });

    it("should list labels with used_only filter true", async () => {
      // First, create and attach a label to a task
      const labelResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Used Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      const usedLabelId = labelResult.data.label.id;
      testLabelIds.push(usedLabelId);

      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: usedLabelId,
      });

      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: testBoardId,
        used_only: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.used_only_filter).toBe(true);
      expect(result.data.labels.length).toBeGreaterThan(0);
    });

    it("should return sorted labels by title", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.labels.length > 1) {
        const titles = result.data.labels.map((l: any) => l.title);
        const sortedTitles = [...titles].sort();
        expect(titles).toEqual(sortedTitles);
      }
    });

    it("should reject missing required board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {});

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id type", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: "not-a-number",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject zero board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-labels", {
        board_id: 0,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Update Label", () => {
    let labelId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Update Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      labelId = result.data.label.id;
      testLabelIds.push(labelId);
    });

    it("should update label title", async () => {
      const newTitle = generateTestTitle("Updated");
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        title: newTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.label.title).toBe(newTitle);
      expect(result.data.label).toHaveProperty("updated_at");
      expect(result.message).toContain("updated successfully");
    });

    it("should update label bg_color", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        bg_color: "#0079bf",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.bg_color).toBe("#0079bf");
    });

    it("should update label text color", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        color: "#ffffff",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.color).toBe("#ffffff");
    });

    it("should update all fields at once", async () => {
      const newTitle = generateTestTitle("All Updated");
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        title: newTitle,
        bg_color: "#ff5733",
        color: "#ffffff",
      });

      expect(result.success).toBe(true);
      expect(result.data.label.title).toBe(newTitle);
      expect(result.data.label.bg_color).toBe("#ff5733");
      expect(result.data.label.color).toBe("#ffffff");
    });

    it("should reject update without label_id", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        title: "New Title",
      });

      expect(result.success).toBe(false);
    });

    it("should reject update without board_id", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        label_id: labelId,
        title: "New Title",
      });

      expect(result.success).toBe(false);
    });

    it("should reject update with no data to update", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("No fields to update");
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: 0,
        label_id: labelId,
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid label_id", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: 0,
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent label_id", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: 999999,
        title: "Test",
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });

    it("should reject empty title", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        title: "",
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("cannot be empty");
    });

    it("should reject invalid bg_color format", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        bg_color: "invalid",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid color format", async () => {
      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        color: "#xyz",
      });

      expect(result.success).toBe(false);
    });

    it("should reject duplicate title in same board", async () => {
      const existingTitle = generateTestTitle("Existing");

      const otherResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: existingTitle,
        bg_color: "#4bce97",
        color: "#000000",
      });
      testLabelIds.push(otherResult.data.label.id);

      const result = await mcp.callTool("fluentboards-update-label", {
        board_id: testBoardId,
        label_id: labelId,
        title: existingTitle,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("already exists");
    });
  });

  describe("Delete Label", () => {
    it("should delete label successfully", async () => {
      const createResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Delete Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      const labelId = createResult.data.label.id;

      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("label_id");
      expect(result.data).toHaveProperty("label_title");
      expect(result.data).toHaveProperty("board_id");
      expect(result.data).toHaveProperty("deleted_at");
      expect(result.data.label_id).toBe(labelId);
      expect(result.message).toContain("deleted successfully");
    });

    it("should remove label from all tasks when deleted", async () => {
      // Create label and attach to task
      const labelResult = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Task Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      const labelId = labelResult.data.label.id;

      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      // Delete label
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);

      // Verify label is removed from task
      const taskLabels = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      const hasDeletedLabel = taskLabels.data.labels.some(
        (l: any) => l.id === labelId,
      );
      expect(hasDeletedLabel).toBe(false);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        label_id: 123,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing label_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: 0,
        label_id: 123,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid label_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent label_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: testBoardId,
        label_id: 999999,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-label", {
        board_id: 999999,
        label_id: 123,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Add Label to Task", () => {
    let labelId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Task Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      labelId = result.data.label.id;
      testLabelIds.push(labelId);
    });

    it("should add label to task successfully", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("task_id");
      expect(result.data).toHaveProperty("label");
      expect(result.data).toHaveProperty("relation_id");
      expect(result.data).toHaveProperty("action");
      expect(result.data.task_id).toBe(testTaskId);
      expect(result.data.label.id).toBe(labelId);
      expect(result.data.action).toBe("assigned");
      expect(result.message).toContain("added to task successfully");
    });

    it("should handle adding already assigned label gracefully", async () => {
      // Add label first time
      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      // Try to add same label again
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data.action).toBe("already_assigned");
      expect(result.message).toContain("already assigned");
    });

    it("should return complete label details", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data.label).toHaveProperty("id");
      expect(result.data.label).toHaveProperty("title");
      expect(result.data.label).toHaveProperty("bg_color");
      expect(result.data.label).toHaveProperty("color");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing task_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing label_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: 0,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid task_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: 0,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid label_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent task_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: 999999,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });

    it("should reject non-existent label_id", async () => {
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: 999999,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });

    it("should reject label from different board", async () => {
      // Create another board with a label
      const otherBoardResult = await mcp.callTool(
        "fluentboards-create-board",
        {
          title: generateTestTitle("Other Board"),
        },
      );
      const otherBoardId = otherBoardResult.data.board.id;
      testBoardIds.push(otherBoardId);

      const otherLabelResult = await mcp.callTool(
        "fluentboards-create-label",
        {
          board_id: otherBoardId,
          title: generateTestTitle("Other Label"),
          bg_color: "#4bce97",
          color: "#000000",
        },
      );
      const otherLabelId = otherLabelResult.data.label.id;

      // Try to add label from other board
      const result = await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: otherLabelId,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });
  });

  describe("Remove Label from Task", () => {
    let labelId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Remove Label"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      labelId = result.data.label.id;
      testLabelIds.push(labelId);
    });

    it("should remove label from task successfully", async () => {
      // First add the label
      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      // Then remove it
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("task_id");
      expect(result.data).toHaveProperty("label");
      expect(result.data).toHaveProperty("action");
      expect(result.data).toHaveProperty("removed_at");
      expect(result.data.task_id).toBe(testTaskId);
      expect(result.data.label.id).toBe(labelId);
      expect(result.data.action).toBe("removed");
      expect(result.message).toContain("removed from task successfully");
    });

    it("should handle removing unassigned label gracefully", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data.action).toBe("not_assigned");
      expect(result.message).toContain("not assigned");
    });

    it("should return complete label details", async () => {
      // Add label first
      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(true);
      expect(result.data.label).toHaveProperty("id");
      expect(result.data.label).toHaveProperty("title");
      expect(result.data.label).toHaveProperty("bg_color");
      expect(result.data.label).toHaveProperty("color");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing task_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing label_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: 0,
        task_id: testTaskId,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid task_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: 0,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid label_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent task_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: 999999,
        label_id: labelId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent label_id", async () => {
      const result = await mcp.callTool("fluentboards-remove-label-from-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Task Labels", () => {
    let labelId1: number;
    let labelId2: number;
    let labelId3: number;

    beforeAll(async () => {
      // Create multiple labels
      const label1 = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Task Label 1"),
        bg_color: "#4bce97",
        color: "#000000",
      });
      labelId1 = label1.data.label.id;
      testLabelIds.push(labelId1);

      const label2 = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Task Label 2"),
        bg_color: "#0079bf",
        color: "#ffffff",
      });
      labelId2 = label2.data.label.id;
      testLabelIds.push(labelId2);

      const label3 = await mcp.callTool("fluentboards-create-label", {
        board_id: testBoardId,
        title: generateTestTitle("Task Label 3"),
        bg_color: "#ff5733",
        color: "#ffffff",
      });
      labelId3 = label3.data.label.id;
      testLabelIds.push(labelId3);

      // Add labels to task
      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId1,
      });

      await mcp.callTool("fluentboards-add-label-to-task", {
        board_id: testBoardId,
        task_id: testTaskId,
        label_id: labelId2,
      });
    });

    it("should get all labels assigned to task", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("task_id");
      expect(result.data).toHaveProperty("board_id");
      expect(result.data).toHaveProperty("labels");
      expect(result.data).toHaveProperty("total_labels");
      expect(Array.isArray(result.data.labels)).toBe(true);
      expect(result.data.task_id).toBe(testTaskId);
      expect(result.data.board_id).toBe(testBoardId);
      expect(result.data.labels.length).toBeGreaterThan(0);
      expect(result.message).toContain("retrieved successfully");
    });

    it("should include complete label details", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(true);
      if (result.data.labels.length > 0) {
        const label = result.data.labels[0];
        expect(label).toHaveProperty("id");
        expect(label).toHaveProperty("title");
        expect(label).toHaveProperty("bg_color");
        expect(label).toHaveProperty("color");
        expect(label).toHaveProperty("assigned_at");
        expect(label).toHaveProperty("assigned_by");
      }
    });

    it("should return correct total_labels count", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(true);
      expect(result.data.total_labels).toBe(result.data.labels.length);
    });

    it("should return empty array for task with no labels", async () => {
      // Create new task without labels
      const taskResult = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        title: "Task Without Labels",
        stage_id: testStageId,
      });
      const unlabeledTaskId = taskResult.data.task.id;
      testTaskIds.push(unlabeledTaskId);

      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: unlabeledTaskId,
      });

      expect(result.success).toBe(true);
      expect(result.data.labels).toEqual([]);
      expect(result.data.total_labels).toBe(0);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        task_id: testTaskId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing task_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: 0,
        task_id: testTaskId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid task_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: 999999,
        task_id: testTaskId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent task_id", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: 999999,
      });

      expect(result.success).toBe(false);
      expect(result.message).toContain("not found");
    });

    it("should only return labels from the specified board", async () => {
      const result = await mcp.callTool("fluentboards-get-task-labels", {
        board_id: testBoardId,
        task_id: testTaskId,
      });

      expect(result.success).toBe(true);
      result.data.labels.forEach((label: any) => {
        expect([labelId1, labelId2, labelId3]).toContain(label.id);
      });
    });
  });
});
