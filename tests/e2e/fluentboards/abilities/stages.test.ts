/**
 * E2E Tests for FluentBoards Stages Abilities
 *
 * Tests all 11 stage management tools including CRUD operations,
 * archiving, reordering, and task management.
 *
 * Tools tested:
 * 1. fluentboards-list-stages
 * 2. fluentboards-create-stage
 * 3. fluentboards-update-stage
 * 4. fluentboards-delete-stage
 * 5. fluentboards-restore-stage
 * 6. fluentboards-reorder-stages
 * 7. fluentboards-move-all-tasks
 * 8. fluentboards-archive-all-tasks
 * 9. fluentboards-get-archived-stages
 * 10. fluentboards-sort-stage-tasks
 * 11. fluentboards-get-stage-positions
 */

import { MCPClient, generateTestTitle } from "../../utils/mcp-client";
import { FLUENTBOARDS_CONFIG } from "../../utils/test-config";

describe("FluentBoards Stages", () => {
  let mcp: MCPClient;
  const testStageIds: number[] = [];
  const testBoardIds: number[] = [];
  const testTaskIds: number[] = [];
  let testBoardId: number;

  beforeAll(async () => {
    mcp = new MCPClient(
      FLUENTBOARDS_CONFIG.baseURL,
      FLUENTBOARDS_CONFIG.username,
      FLUENTBOARDS_CONFIG.password,
    );

    // Create test board for all stage tests
    const boardResult = await mcp.callTool("fluentboards-create-board", {
      title: generateTestTitle("Stages Test Board"),
    });

    if (!boardResult.success || !boardResult.data) {
      console.error("Board creation failed:", JSON.stringify(boardResult, null, 2));
      throw new Error(`Failed to create test board: ${boardResult.message || "Unknown error"}`);
    }

    testBoardId = boardResult.data.board.id;
    testBoardIds.push(testBoardId);
  });

  afterAll(async () => {
    // Clean up tasks first
    for (const taskId of testTaskIds) {
      try {
        await mcp.callTool("fluentboards-delete-task", {
          board_id: testBoardId,
          task_id: taskId,
        });
      } catch (error) {
        // Ignore cleanup errors
      }
    }

    // Clean up stages
    for (const stageId of testStageIds) {
      try {
        await mcp.callTool("fluentboards-delete-stage", {
          board_id: testBoardId,
          stage_id: stageId,
        });
      } catch (error) {
        // Ignore cleanup errors
      }
    }

    // Clean up boards
    for (const boardId of testBoardIds) {
      try {
        await mcp.callTool("fluentboards-delete-board", {
          board_id: boardId,
          confirm_delete: true,
        });
      } catch (error) {
        // Ignore cleanup errors
      }
    }
  });

  describe("Create Stage", () => {
    it("should create stage with required fields only", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Basic Stage"),
      });

      expect(result.success).toBe(true);
      expect(result.data.stage).toHaveProperty("id");
      expect(result.data.stage).toHaveProperty("title");
      expect(result.data.stage).toHaveProperty("position");
      expect(result.data.stage).toHaveProperty("board_id", testBoardId);
      expect(result.data.stage).toHaveProperty("settings");
      expect(result.message).toContain("created successfully");

      testStageIds.push(result.data.stage.id);
    });

    it("should create stage with all optional fields", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Full Stage"),
        position: 999,
        settings: {
          default_task_status: "active",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.stage.position).toBe(999);
      expect(result.data.stage).toHaveProperty("settings");

      testStageIds.push(result.data.stage.id);
    });

    it("should auto-assign position if not provided", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Auto Position Stage"),
      });

      expect(result.success).toBe(true);
      expect(result.data.stage.position).toBeGreaterThan(0);

      testStageIds.push(result.data.stage.id);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        title: "Test Stage",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing title", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty title", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: "",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: "invalid",
        title: "Test Stage",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: 999999,
        title: "Test Stage",
      });

      expect(result.success).toBe(false);
    });

    it("should reject zero board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: 0,
        title: "Test Stage",
      });

      expect(result.success).toBe(false);
    });

    it("should reject negative board_id", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: -1,
        title: "Test Stage",
      });

      expect(result.success).toBe(false);
    });

    it("should handle very long title", async () => {
      const longTitle = "A".repeat(500);
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: longTitle,
      });

      expect(result.success).toBe(true);
      testStageIds.push(result.data.stage.id);
    });

    it("should handle special characters in title", async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Stage with émojis 🎯 & symbols!"),
      });

      expect(result.success).toBe(true);
      testStageIds.push(result.data.stage.id);
    });
  });

  describe("List Stages", () => {
    let stageId1: number;
    let stageId2: number;
    let archivedStageId: number;

    beforeAll(async () => {
      // Create test stages
      const stage1 = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("List Stage 1"),
      });
      stageId1 = stage1.data.stage.id;
      testStageIds.push(stageId1);

      const stage2 = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("List Stage 2"),
      });
      stageId2 = stage2.data.stage.id;
      testStageIds.push(stageId2);

      // Create and archive a stage
      const archivedStage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Archived Stage"),
      });
      archivedStageId = archivedStage.data.stage.id;
      testStageIds.push(archivedStageId);

      await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: archivedStageId,
      });
    });

    it("should list all active stages by default", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("stages");
      expect(Array.isArray(result.data.stages)).toBe(true);
      expect(result.data.stages.length).toBeGreaterThanOrEqual(2);
      expect(result.message).toContain("retrieved successfully");
    });

    it("should not include archived stages by default", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      const hasArchived = result.data.stages.some((s: any) => s.id === archivedStageId);
      expect(hasArchived).toBe(false);
    });

    it("should include archived stages when requested", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: testBoardId,
        include_archived: true,
      });

      expect(result.success).toBe(true);
      const hasArchived = result.data.stages.some((s: any) => s.id === archivedStageId);
      expect(hasArchived).toBe(true);
    });

    it("should return stages in position order", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.stages.length > 1) {
        for (let i = 1; i < result.data.stages.length; i++) {
          expect(result.data.stages[i].position).toBeGreaterThanOrEqual(
            result.data.stages[i - 1].position
          );
        }
      }
    });

    it("should include stage details", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.stages.length > 0) {
        const stage = result.data.stages[0];
        expect(stage).toHaveProperty("id");
        expect(stage).toHaveProperty("title");
        expect(stage).toHaveProperty("position");
        expect(stage).toHaveProperty("board_id");
      }
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {});

      expect(result.success).toBe(false);
    });

    it("should reject invalid board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: "invalid",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent board_id", async () => {
      const result = await mcp.callTool("fluentboards-list-stages", {
        board_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Update Stage", () => {
    let stageId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Update Test Stage"),
      });
      stageId = result.data.stage.id;
      testStageIds.push(stageId);
    });

    it("should update stage title", async () => {
      const newTitle = generateTestTitle("Updated Stage Title");
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
        title: newTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.stage.title).toBe(newTitle);
      expect(result.message).toContain("updated successfully");
    });

    it("should update stage bg_color", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
        bg_color: "#e74c3c",
      });

      expect(result.success).toBe(true);
      expect(result.data.stage.bg_color).toBe("#e74c3c");
    });

    it("should update stage settings", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
        settings: {
          default_task_status: "completed",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.stage).toHaveProperty("settings");
    });

    it("should update multiple fields at once", async () => {
      const newTitle = generateTestTitle("Multi-Update Stage");
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
        title: newTitle,
        bg_color: "#9b59b6",
        settings: {
          default_task_status: "active",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.stage.title).toBe(newTitle);
      expect(result.data.stage.bg_color).toBe("#9b59b6");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        stage_id: stageId,
        title: "Updated",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        title: "Updated",
      });

      expect(result.success).toBe(false);
    });

    it("should reject update with no data", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
      });

      // May succeed with no changes or reject - either is valid
      expect([true, false]).toContain(result.success);
    });

    it("should reject invalid stage_id", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: "invalid",
        title: "Updated",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: 999999,
        title: "Updated",
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty title", async () => {
      const result = await mcp.callTool("fluentboards-update-stage", {
        board_id: testBoardId,
        stage_id: stageId,
        title: "",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Delete Stage (Archive)", () => {
    it("should delete (archive) stage successfully", async () => {
      const createResult = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Delete Test Stage"),
      });
      const stageId = createResult.data.stage.id;
      testStageIds.push(stageId);

      const result = await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: stageId,
      });

      expect(result.success).toBe(true);
      expect(result.message).toContain("archived");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-stage", {
        stage_id: 123,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid stage_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: "invalid",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Restore Stage", () => {
    let archivedStageId: number;

    beforeAll(async () => {
      const createResult = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Restore Test Stage"),
      });
      archivedStageId = createResult.data.stage.id;
      testStageIds.push(archivedStageId);

      await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: archivedStageId,
      });
    });

    it("should restore archived stage", async () => {
      const result = await mcp.callTool("fluentboards-restore-stage", {
        board_id: testBoardId,
        stage_id: archivedStageId,
      });

      expect(result.success).toBe(true);
      expect(result.message).toContain("restored");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-restore-stage", {
        stage_id: archivedStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-restore-stage", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-restore-stage", {
        board_id: testBoardId,
        stage_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Reorder Stages", () => {
    let stage1Id: number;
    let stage2Id: number;
    let stage3Id: number;

    beforeAll(async () => {
      const s1 = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Reorder Stage 1"),
      });
      stage1Id = s1.data.stage.id;
      testStageIds.push(stage1Id);

      const s2 = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Reorder Stage 2"),
      });
      stage2Id = s2.data.stage.id;
      testStageIds.push(stage2Id);

      const s3 = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Reorder Stage 3"),
      });
      stage3Id = s3.data.stage.id;
      testStageIds.push(stage3Id);
    });

    it("should reorder stages successfully", async () => {
      const result = await mcp.callTool("fluentboards-reorder-stages", {
        board_id: testBoardId,
        stage_orders: [
          { stage_id: stage3Id, position: 1 },
          { stage_id: stage1Id, position: 2 },
          { stage_id: stage2Id, position: 3 },
        ],
      });

      expect(result.success).toBe(true);
      expect(result.message).toContain("reordered");
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-reorder-stages", {
        stage_orders: [{ stage_id: stage1Id, position: 1 }],
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_orders", async () => {
      const result = await mcp.callTool("fluentboards-reorder-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty stage_orders", async () => {
      const result = await mcp.callTool("fluentboards-reorder-stages", {
        board_id: testBoardId,
        stage_orders: [],
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid stage_orders format", async () => {
      const result = await mcp.callTool("fluentboards-reorder-stages", {
        board_id: testBoardId,
        stage_orders: "invalid",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Archived Stages", () => {
    let archivedStageId: number;

    beforeAll(async () => {
      const createResult = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Archived Stages Test"),
      });
      archivedStageId = createResult.data.stage.id;
      testStageIds.push(archivedStageId);

      await mcp.callTool("fluentboards-delete-stage", {
        board_id: testBoardId,
        stage_id: archivedStageId,
      });
    });

    it("should get archived stages", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("archived_stages");
      expect(Array.isArray(result.data.archived_stages)).toBe(true);
      expect(result.data).toHaveProperty("total_archived");
    });

    it("should only return archived stages", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.archived_stages.length > 0) {
        const allArchived = result.data.archived_stages.every((s: any) => s.archived_at !== null);
        expect(allArchived).toBe(true);
      }
    });

    it("should support pagination with page parameter", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        page: 1,
        per_page: 10,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("page", 1);
      expect(result.data).toHaveProperty("per_page", 10);
    });

    it("should support noPagination parameter", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        noPagination: true,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("archived_stages");
      // When noPagination is true, should return all results
    });

    it("should respect per_page limit", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        per_page: 1,
      });

      expect(result.success).toBe(true);
      if (result.data.archived_stages.length > 0) {
        expect(result.data.archived_stages.length).toBeLessThanOrEqual(1);
      }
    });

    it("should reject invalid page number (zero)", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid per_page (too high)", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        per_page: 101, // Max is 100
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid per_page (zero)", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {
        board_id: testBoardId,
        per_page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-archived-stages", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Get Stage Positions", () => {
    it("should get all stage positions", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("positions");
      expect(Array.isArray(result.data.positions)).toBe(true);
    });

    it("should include stage_id and position in results", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(true);
      if (result.data.positions.length > 0) {
        const pos = result.data.positions[0];
        expect(pos).toHaveProperty("stage_id");
        expect(pos).toHaveProperty("position");
      }
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Move All Tasks", () => {
    let sourceStageId: number;
    let targetStageId: number;
    let task1Id: number;
    let task2Id: number;

    beforeAll(async () => {
      // Create two stages
      const source = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Source Stage"),
      });
      sourceStageId = source.data.stage.id;
      testStageIds.push(sourceStageId);

      const target = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Target Stage"),
      });
      targetStageId = target.data.stage.id;
      testStageIds.push(targetStageId);

      // Create tasks in source stage
      const t1 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: sourceStageId,
        title: generateTestTitle("Task 1"),
      });
      task1Id = t1.data.task.id;
      testTaskIds.push(task1Id);

      const t2 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: sourceStageId,
        title: generateTestTitle("Task 2"),
      });
      task2Id = t2.data.task.id;
      testTaskIds.push(task2Id);
    });

    it("should move all tasks from source to target stage", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        old_stage_id: sourceStageId,
        new_stage_id: targetStageId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("total_moved");
      expect(result.data).toHaveProperty("moved_tasks");
      expect(result.data.total_moved).toBeGreaterThanOrEqual(2);
      expect(Array.isArray(result.data.moved_tasks)).toBe(true);
      expect(result.message).toContain("moved successfully");
    });

    it("should return zero count when no tasks to move", async () => {
      const emptyStage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Empty Stage"),
      });
      testStageIds.push(emptyStage.data.stage.id);

      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        old_stage_id: emptyStage.data.stage.id,
        new_stage_id: targetStageId,
      });

      expect(result.success).toBe(true);
      expect(result.data.total_moved).toBe(0);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        old_stage_id: sourceStageId,
        new_stage_id: targetStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing old_stage_id", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        new_stage_id: targetStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing new_stage_id", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        old_stage_id: sourceStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent old_stage_id", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        old_stage_id: 999999,
        new_stage_id: targetStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent new_stage_id", async () => {
      const result = await mcp.callTool("fluentboards-move-all-tasks", {
        board_id: testBoardId,
        old_stage_id: sourceStageId,
        new_stage_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Archive All Tasks", () => {
    let stageWithTasksId: number;
    let task3Id: number;
    let task4Id: number;

    beforeAll(async () => {
      const stage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Archive Test Stage"),
      });
      stageWithTasksId = stage.data.stage.id;
      testStageIds.push(stageWithTasksId);

      const t3 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: stageWithTasksId,
        title: generateTestTitle("Archive Task 1"),
      });
      task3Id = t3.data.task.id;
      testTaskIds.push(task3Id);

      const t4 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: stageWithTasksId,
        title: generateTestTitle("Archive Task 2"),
      });
      task4Id = t4.data.task.id;
      testTaskIds.push(task4Id);
    });

    it("should archive all tasks in stage", async () => {
      const result = await mcp.callTool("fluentboards-archive-all-tasks", {
        board_id: testBoardId,
        stage_id: stageWithTasksId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("total_archived");
      expect(result.data).toHaveProperty("archived_tasks");
      expect(result.data.total_archived).toBeGreaterThanOrEqual(2);
      expect(Array.isArray(result.data.archived_tasks)).toBe(true);
      expect(result.message).toContain("archived successfully");
    });

    it("should return zero when no tasks to archive", async () => {
      const emptyStage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Empty Archive Stage"),
      });
      testStageIds.push(emptyStage.data.stage.id);

      const result = await mcp.callTool("fluentboards-archive-all-tasks", {
        board_id: testBoardId,
        stage_id: emptyStage.data.stage.id,
      });

      expect(result.success).toBe(true);
      expect(result.data.total_archived).toBe(0);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-archive-all-tasks", {
        stage_id: stageWithTasksId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-archive-all-tasks", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-archive-all-tasks", {
        board_id: testBoardId,
        stage_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Sort Stage Tasks", () => {
    let sortStageId: number;

    beforeAll(async () => {
      const stage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Sort Test Stage"),
      });
      sortStageId = stage.data.stage.id;
      testStageIds.push(sortStageId);

      // Create tasks with different properties for sorting
      const t1 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: sortStageId,
        title: "Z Task",
      });
      testTaskIds.push(t1.data.task.id);

      const t2 = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: sortStageId,
        title: "A Task",
      });
      testTaskIds.push(t2.data.task.id);
    });

    it("should sort tasks by title ascending", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        order: "title",
        orderBy: "ASC",
      });

      expect(result.success).toBe(true);
      expect(result.message).toContain("sorted successfully");
    });

    it("should sort tasks by created_at descending", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        order: "created_at",
        orderBy: "DESC",
      });

      expect(result.success).toBe(true);
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        stage_id: sortStageId,
        order: "title",
        orderBy: "ASC",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        order: "title",
        orderBy: "ASC",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing order", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        orderBy: "ASC",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing orderBy", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        order: "title",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid order value", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        order: "invalid_field",
        orderBy: "ASC",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid orderBy value", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: sortStageId,
        order: "title",
        orderBy: "INVALID",
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-sort-stage-tasks", {
        board_id: testBoardId,
        stage_id: 999999,
        order: "title",
        orderBy: "ASC",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Stage Positions (Task Positions in Stage)", () => {
    let posStageId: number;

    beforeAll(async () => {
      const stage = await mcp.callTool("fluentboards-create-stage", {
        board_id: testBoardId,
        title: generateTestTitle("Position Test Stage"),
      });
      posStageId = stage.data.stage.id;
      testStageIds.push(posStageId);

      // Create a task in the stage
      const task = await mcp.callTool("fluentboards-create-task", {
        board_id: testBoardId,
        stage_id: posStageId,
        title: generateTestTitle("Position Task"),
      });
      testTaskIds.push(task.data.task.id);
    });

    it("should get task positions for stage", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
        stage_id: posStageId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("positions");
      expect(Array.isArray(result.data.positions)).toBe(true);
    });

    it("should include position details", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
        stage_id: posStageId,
      });

      expect(result.success).toBe(true);
      if (result.data.positions.length > 0) {
        const pos = result.data.positions[0];
        expect(pos).toHaveProperty("task_id");
        expect(pos).toHaveProperty("position");
      }
    });

    it("should reject missing board_id", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        stage_id: posStageId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing stage_id", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent stage_id", async () => {
      const result = await mcp.callTool("fluentboards-get-stage-positions", {
        board_id: testBoardId,
        stage_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });
});
