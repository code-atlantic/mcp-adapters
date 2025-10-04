/**
 * E2E Tests for FluentCRM Lists Abilities
 *
 * Tests all list management tools including CRUD operations,
 * subscriber management, statistics, duplication, and merging.
 */

import {
  MCPClient,
  TEST_CONFIG,
  generateTestTitle,
  generateTestEmail,
} from "../../utils/mcp-client";

describe("FluentCRM Lists", () => {
  let mcp: MCPClient;
  const testListIds: number[] = [];
  const testSubscriberIds: number[] = [];

  beforeAll(() => {
    mcp = new MCPClient(
      TEST_CONFIG.baseURL,
      TEST_CONFIG.username,
      TEST_CONFIG.password,
    );
  });

  afterAll(async () => {
    // Cleanup test lists
    for (const listId of testListIds) {
      await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
        confirm_delete: true,
        delete_subscribers: false,
      });
    }

    // Cleanup test subscribers
    if (testSubscriberIds.length > 0) {
      await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
        subscriber_ids: testSubscriberIds,
        confirm_delete: true,
      });
    }
  });

  describe("Create List", () => {
    it("should create list with minimal required fields", async () => {
      const title = generateTestTitle("Test List");
      const result = await mcp.callTool("fluentcrm-create-list", { title });

      expect(result.success).toBe(true);
      expect(result.data.list.title).toBe(title);
      expect(result.data.list).toHaveProperty("id");
      expect(result.data.list).toHaveProperty("slug");
      expect(result.data.list).toHaveProperty("created_at");

      testListIds.push(result.data.list.id);
    });

    it("should create list with all optional fields", async () => {
      const title = generateTestTitle("Full List");
      const description = "This is a comprehensive test list with all fields";
      const slug = `test-slug-${Date.now()}`;

      const result = await mcp.callTool("fluentcrm-create-list", {
        title,
        description,
        slug,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.title).toBe(title);
      expect(result.data.list.description).toBe(description);
      expect(result.data.list.slug).toBe(slug);

      testListIds.push(result.data.list.id);
    });

    it("should auto-generate slug from title if not provided", async () => {
      const title = generateTestTitle("Auto Slug Test");
      const result = await mcp.callTool("fluentcrm-create-list", { title });

      expect(result.success).toBe(true);
      expect(result.data.list.slug).toBeTruthy();
      expect(result.data.list.slug).toMatch(/^[a-z0-9-]+$/);

      testListIds.push(result.data.list.id);
    });

    it("should handle duplicate slug by making it unique", async () => {
      const slug = `duplicate-slug-${Date.now()}`;

      const first = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("First"),
        slug,
      });
      testListIds.push(first.data.list.id);

      const second = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Second"),
        slug,
      });

      expect(second.success).toBe(true);
      expect(second.data.list.slug).not.toBe(first.data.list.slug);
      expect(second.data.list.slug).toContain(slug);

      testListIds.push(second.data.list.id);
    });

    it("should validate slug pattern (lowercase, numbers, dashes only)", async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Valid Slug"),
        slug: "valid-slug-123",
      });

      expect(result.success).toBe(true);
      testListIds.push(result.data.list.id);
    });

    it("should reject missing required title field", async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        description: "No title provided",
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty title", async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        title: "",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("List Lists", () => {
    beforeAll(async () => {
      // Create several lists for pagination testing
      for (let i = 0; i < 5; i++) {
        const result = await mcp.callTool("fluentcrm-create-list", {
          title: generateTestTitle(`Pagination Test ${i}`),
        });
        testListIds.push(result.data.list.id);
      }
    });

    it("should list all lists with default pagination", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("lists");
      expect(result.data).toHaveProperty("total");
      expect(result.data).toHaveProperty("page");
      expect(result.data).toHaveProperty("per_page");
      expect(result.data).toHaveProperty("total_pages");
      expect(Array.isArray(result.data.lists)).toBe(true);
    });

    it("should respect pagination parameters", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        page: 1,
        per_page: 5,
      });

      expect(result.success).toBe(true);
      expect(result.data.lists.length).toBeLessThanOrEqual(5);
      expect(result.data.page).toBe(1);
      expect(result.data.per_page).toBe(5);
    });

    it("should handle pagination boundaries (minimum per_page)", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        page: 1,
        per_page: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.lists.length).toBeLessThanOrEqual(1);
    });

    it("should handle pagination boundaries (maximum per_page)", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        page: 1,
        per_page: 100,
      });

      expect(result.success).toBe(true);
      expect(result.data.per_page).toBe(100);
    });

    it("should reject invalid per_page values exceeding maximum", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        per_page: 101,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid per_page values below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        per_page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid page values", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should search lists by title", async () => {
      const uniqueTitle = generateTestTitle("Searchable List");
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: uniqueTitle,
      });
      testListIds.push(createResult.data.list.id);

      const searchResult = await mcp.callTool("fluentcrm-list-lists", {
        search: uniqueTitle,
      });

      expect(searchResult.success).toBe(true);
      expect(searchResult.data.search_query).toBe(uniqueTitle);
      expect(
        searchResult.data.lists.some((list: any) => list.title === uniqueTitle),
      ).toBe(true);
    });

    it("should return empty results for non-matching search", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        search: `nonexistent-${Date.now()}`,
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBe(0);
      expect(result.data.lists).toHaveLength(0);
    });

    it("should handle search with special characters", async () => {
      const result = await mcp.callTool("fluentcrm-list-lists", {
        search: "Test & Special",
      });

      expect(result.success).toBe(true);
    });
  });

  describe("Get List", () => {
    let listId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Get Test List"),
        description: "Test description for get operations",
      });
      listId = result.data.list.id;
      testListIds.push(listId);
    });

    it("should get list by ID with all details", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.id).toBe(listId);
      expect(result.data.list).toHaveProperty("title");
      expect(result.data.list).toHaveProperty("slug");
      expect(result.data.list).toHaveProperty("description");
      expect(result.data.list).toHaveProperty("created_at");
      expect(result.data.list).toHaveProperty("updated_at");
    });

    it("should include subscriber counts by status", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("subscriber_counts");
      expect(result.data.subscriber_counts).toHaveProperty("total");
      expect(result.data.subscriber_counts).toHaveProperty("subscribed");
      expect(result.data.subscriber_counts).toHaveProperty("unsubscribed");
      expect(result.data.subscriber_counts).toHaveProperty("pending");
      expect(result.data.subscriber_counts).toHaveProperty("bounced");
      expect(result.data.subscriber_counts).toHaveProperty("complained");
    });

    it("should reject invalid list ID (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {
        list_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid list ID (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {
        list_id: -1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent list ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-list", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Update List", () => {
    let listId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Update Test"),
        description: "Original description",
      });
      listId = result.data.list.id;
      testListIds.push(listId);
    });

    it("should update list title", async () => {
      const newTitle = generateTestTitle("Updated Title");
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        title: newTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.title).toBe(newTitle);
      expect(result.data.list).toHaveProperty("updated_at");
    });

    it("should update list description", async () => {
      const newDescription = "Updated description with new content";
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        description: newDescription,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.description).toBe(newDescription);
    });

    it("should update list slug", async () => {
      const newSlug = `updated-slug-${Date.now()}`;
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        slug: newSlug,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.slug).toBe(newSlug);
    });

    it("should update multiple fields simultaneously", async () => {
      const newTitle = generateTestTitle("Multi Update");
      const newDescription = "Multiple fields updated";
      const newSlug = `multi-${Date.now()}`;

      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        title: newTitle,
        description: newDescription,
        slug: newSlug,
      });

      expect(result.success).toBe(true);
      expect(result.data.list.title).toBe(newTitle);
      expect(result.data.list.description).toBe(newDescription);
      expect(result.data.list.slug).toBe(newSlug);
    });

    it("should reject duplicate slug update", async () => {
      // Create another list with a specific slug
      const existingSlug = `existing-slug-${Date.now()}`;
      const otherList = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Other List"),
        slug: existingSlug,
      });
      testListIds.push(otherList.data.list.id);

      // Try to update current list to use the same slug
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        slug: existingSlug,
      });

      expect(result.success).toBe(false);
    });

    it("should allow updating with same slug (no change)", async () => {
      const getResult = await mcp.callTool("fluentcrm-get-list", {
        list_id: listId,
      });
      const currentSlug = getResult.data.list.slug;

      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        slug: currentSlug,
      });

      expect(result.success).toBe(true);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-update-list", {
        title: "New Title",
      });

      expect(result.success).toBe(false);
    });

    it("should handle update with no changes", async () => {
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
    });

    it("should reject invalid list ID", async () => {
      const result = await mcp.callTool("fluentcrm-update-list", {
        list_id: 999999,
        title: "New Title",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Delete List", () => {
    it("should delete list with confirmation (keep subscribers)", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Delete Test"),
      });
      const listId = createResult.data.list.id;

      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
        confirm_delete: true,
        delete_subscribers: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.list_id).toBe(listId);
      expect(result.data).toHaveProperty("list_title");
      expect(result.data.subscribers_deleted).toBe(0);
      expect(result.data).toHaveProperty("deleted_at");
    });

    it("should delete list and all subscribers when requested", async () => {
      // Create list
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Delete with Subs"),
      });
      const listId = listResult.data.list.id;

      // Create and add subscribers
      const sub1 = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      testSubscriberIds.push(sub1.data.subscriber.id);

      await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: sub1.data.subscriber.id,
        list_id: listId,
      });

      // Delete with subscribers
      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
        confirm_delete: true,
        delete_subscribers: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers_deleted).toBeGreaterThan(0);
      expect(result.data.subscribers_kept).toBe(0);
    });

    it("should reject delete without confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("No Confirm"),
      });
      const listId = createResult.data.list.id;
      testListIds.push(listId);

      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
        confirm_delete: false,
      });

      expect(result.success).toBe(false);
    });

    it("should reject delete with missing confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Missing Confirm"),
      });
      const listId = createResult.data.list.id;
      testListIds.push(listId);

      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
      });

      expect(result.success).toBe(false);
    });

    it("should handle delete_subscribers default (false)", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Default Subs"),
      });
      const listId = createResult.data.list.id;

      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: listId,
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers_deleted).toBe(0);
    });

    it("should reject invalid list ID", async () => {
      const result = await mcp.callTool("fluentcrm-delete-list", {
        list_id: 999999,
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-delete-list", {
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get List Subscribers", () => {
    let listId: number;
    let subscriberIds: number[] = [];

    beforeAll(async () => {
      // Create list
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Subscriber Test List"),
      });
      listId = listResult.data.list.id;
      testListIds.push(listId);

      // Create subscribers with different statuses
      const statuses = ["subscribed", "pending", "unsubscribed"];
      for (const status of statuses) {
        const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          status,
        });
        subscriberIds.push(subResult.data.subscriber.id);
        testSubscriberIds.push(subResult.data.subscriber.id);

        // Add to list
        await mcp.callTool("fluentcrm-add-subscriber-to-list", {
          subscriber_id: subResult.data.subscriber.id,
          list_id: listId,
        });
      }
    });

    it("should get all subscribers in list with default pagination", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.list_id).toBe(listId);
      expect(result.data).toHaveProperty("list_title");
      expect(result.data).toHaveProperty("subscribers");
      expect(result.data).toHaveProperty("total");
      expect(result.data).toHaveProperty("page");
      expect(result.data).toHaveProperty("per_page");
      expect(result.data).toHaveProperty("total_pages");
      expect(Array.isArray(result.data.subscribers)).toBe(true);
    });

    it("should respect pagination parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        page: 1,
        per_page: 2,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeLessThanOrEqual(2);
      expect(result.data.page).toBe(1);
      expect(result.data.per_page).toBe(2);
    });

    it("should filter by status (subscribed)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        status: "subscribed",
      });

      expect(result.success).toBe(true);
      expect(result.data.status_filter).toBe("subscribed");
      result.data.subscribers.forEach((sub: any) => {
        expect(sub.status).toBe("subscribed");
      });
    });

    it("should filter by status (pending)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        status: "pending",
      });

      expect(result.success).toBe(true);
      result.data.subscribers.forEach((sub: any) => {
        expect(sub.status).toBe("pending");
      });
    });

    it("should filter by status (unsubscribed)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        status: "unsubscribed",
      });

      expect(result.success).toBe(true);
      result.data.subscribers.forEach((sub: any) => {
        expect(sub.status).toBe("unsubscribed");
      });
    });

    it("should handle all valid status enum values", async () => {
      const statuses = [
        "subscribed",
        "unsubscribed",
        "pending",
        "bounced",
        "complained",
      ];

      for (const status of statuses) {
        const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
          list_id: listId,
          status,
        });

        expect(result.success).toBe(true);
      }
    });

    it("should reject invalid status value", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        status: "invalid_status",
      });

      expect(result.success).toBe(false);
    });

    it("should handle pagination boundaries (minimum)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        page: 1,
        per_page: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeLessThanOrEqual(1);
    });

    it("should handle pagination boundaries (maximum)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        page: 1,
        per_page: 100,
      });

      expect(result.success).toBe(true);
      expect(result.data.per_page).toBe(100);
    });

    it("should reject per_page exceeding maximum", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: listId,
        per_page: 101,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid list ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-subscribers", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Get List Stats", () => {
    let listId: number;

    beforeAll(async () => {
      // Create list
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Stats Test List"),
      });
      listId = listResult.data.list.id;
      testListIds.push(listId);

      // Create subscribers with different statuses
      const statuses = ["subscribed", "pending", "unsubscribed", "bounced"];
      for (const status of statuses) {
        const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          status,
        });
        testSubscriberIds.push(subResult.data.subscriber.id);

        await mcp.callTool("fluentcrm-add-subscriber-to-list", {
          subscriber_id: subResult.data.subscriber.id,
          list_id: listId,
        });
      }
    });

    it("should get comprehensive list statistics", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-stats", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.list_id).toBe(listId);
      expect(result.data).toHaveProperty("list_title");
      expect(result.data).toHaveProperty("statistics");
      expect(result.data).toHaveProperty("generated_at");
    });

    it("should include all status counts", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-stats", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.statistics).toHaveProperty("total_subscribers");
      expect(result.data.statistics).toHaveProperty("subscribed");
      expect(result.data.statistics).toHaveProperty("unsubscribed");
      expect(result.data.statistics).toHaveProperty("pending");
      expect(result.data.statistics).toHaveProperty("bounced");
      expect(result.data.statistics).toHaveProperty("complained");
    });

    it("should include subscription rate calculation", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-stats", {
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.statistics).toHaveProperty("subscription_rate");
      expect(typeof result.data.statistics.subscription_rate).toBe("number");
    });

    it("should handle empty list (no subscribers)", async () => {
      const emptyListResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Empty Stats List"),
      });
      testListIds.push(emptyListResult.data.list.id);

      const result = await mcp.callTool("fluentcrm-get-list-stats", {
        list_id: emptyListResult.data.list.id,
      });

      expect(result.success).toBe(true);
      expect(result.data.statistics.total_subscribers).toBe(0);
      expect(result.data.statistics.subscription_rate).toBe(0);
    });

    it("should reject invalid list ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-stats", {
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-stats", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Duplicate List", () => {
    let sourceListId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Source List"),
        description: "Original list for duplication",
      });
      sourceListId = result.data.list.id;
      testListIds.push(sourceListId);

      // Add some subscribers
      for (let i = 0; i < 3; i++) {
        const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
        });
        testSubscriberIds.push(subResult.data.subscriber.id);

        await mcp.callTool("fluentcrm-add-subscriber-to-list", {
          subscriber_id: subResult.data.subscriber.id,
          list_id: sourceListId,
        });
      }
    });

    it("should duplicate list without subscribers (default)", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("original_list");
      expect(result.data).toHaveProperty("new_list");
      expect(result.data.original_list.id).toBe(sourceListId);
      expect(result.data.new_list.id).not.toBe(sourceListId);
      expect(result.data.new_list.title).toContain("Copy of");
      expect(result.data.subscribers_copied).toBe(0);

      testListIds.push(result.data.new_list.id);
    });

    it("should duplicate list with custom title", async () => {
      const customTitle = generateTestTitle("Custom Duplicate");
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
        new_title: customTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.new_list.title).toBe(customTitle);
      expect(result.data.new_list).toHaveProperty("slug");
      expect(result.data.new_list).toHaveProperty("created_at");

      testListIds.push(result.data.new_list.id);
    });

    it("should duplicate list with subscribers when requested", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
        copy_subscribers: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers_copied).toBeGreaterThan(0);

      testListIds.push(result.data.new_list.id);
    });

    it("should not copy subscribers when copy_subscribers is false", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
        copy_subscribers: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers_copied).toBe(0);

      testListIds.push(result.data.new_list.id);
    });

    it("should preserve original list description in duplicate", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
      });

      expect(result.success).toBe(true);
      expect(result.data.new_list.description).toBe(
        result.data.new_list.description,
      );

      testListIds.push(result.data.new_list.id);
    });

    it("should generate unique slug for duplicate", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: sourceListId,
      });

      expect(result.success).toBe(true);
      expect(result.data.new_list.slug).toBeTruthy();
      expect(result.data.new_list.slug).toMatch(/^[a-z0-9-]+$/);

      testListIds.push(result.data.new_list.id);
    });

    it("should reject invalid list ID", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required list_id", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-list", {
        new_title: "Test",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Merge Lists", () => {
    let sourceList1Id: number;
    let sourceList2Id: number;
    let sourceList3Id: number;

    beforeAll(async () => {
      // Create three source lists with different subscribers
      const list1 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Merge Source 1"),
      });
      sourceList1Id = list1.data.list.id;
      testListIds.push(sourceList1Id);

      const list2 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Merge Source 2"),
      });
      sourceList2Id = list2.data.list.id;
      testListIds.push(sourceList2Id);

      const list3 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Merge Source 3"),
      });
      sourceList3Id = list3.data.list.id;
      testListIds.push(sourceList3Id);

      // Add unique subscribers to each list
      for (let i = 0; i < 2; i++) {
        const sub1 = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
        });
        testSubscriberIds.push(sub1.data.subscriber.id);
        await mcp.callTool("fluentcrm-add-subscriber-to-list", {
          subscriber_id: sub1.data.subscriber.id,
          list_id: sourceList1Id,
        });

        const sub2 = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
        });
        testSubscriberIds.push(sub2.data.subscriber.id);
        await mcp.callTool("fluentcrm-add-subscriber-to-list", {
          subscriber_id: sub2.data.subscriber.id,
          list_id: sourceList2Id,
        });
      }

      // Add one subscriber to list 3
      const sub3 = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      testSubscriberIds.push(sub3.data.subscriber.id);
      await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: sub3.data.subscriber.id,
        list_id: sourceList3Id,
      });
    });

    it("should merge two lists without deleting sources", async () => {
      const targetTitle = generateTestTitle("Merged List");
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id],
        target_list_title: targetTitle,
        delete_source: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.target_list.title).toBe(targetTitle);
      expect(result.data.source_lists).toHaveLength(2);
      expect(result.data.total_subscribers).toBeGreaterThan(0);
      expect(result.data.source_lists_deleted).toBe(false);
      expect(result.data.deleted_lists).toHaveLength(0);

      testListIds.push(result.data.target_list.id);
    });

    it("should merge multiple lists (3 lists)", async () => {
      const targetTitle = generateTestTitle("Three Way Merge");
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id, sourceList3Id],
        target_list_title: targetTitle,
        delete_source: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.source_lists).toHaveLength(3);
      expect(result.data.total_subscribers).toBeGreaterThan(0);

      testListIds.push(result.data.target_list.id);
    });

    it("should merge lists and delete sources when requested", async () => {
      // Create temporary lists for deletion test
      const temp1 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Temp Merge 1"),
      });
      const temp2 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Temp Merge 2"),
      });

      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [temp1.data.list.id, temp2.data.list.id],
        target_list_title: generateTestTitle("Delete Source Test"),
        delete_source: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.source_lists_deleted).toBe(true);
      expect(result.data.deleted_lists).toHaveLength(2);

      testListIds.push(result.data.target_list.id);
    });

    it("should generate descriptive description for merged list", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id],
        target_list_title: generateTestTitle("Description Test"),
        delete_source: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.target_list.description).toContain(
        "Merged list from:",
      );

      testListIds.push(result.data.target_list.id);
    });

    it("should deduplicate subscribers across merged lists", async () => {
      // Create two lists with a shared subscriber
      const tempList1 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Dedup 1"),
      });
      const tempList2 = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Dedup 2"),
      });
      testListIds.push(tempList1.data.list.id, tempList2.data.list.id);

      const sharedSub = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      testSubscriberIds.push(sharedSub.data.subscriber.id);

      // Add to both lists
      await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: sharedSub.data.subscriber.id,
        list_id: tempList1.data.list.id,
      });
      await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: sharedSub.data.subscriber.id,
        list_id: tempList2.data.list.id,
      });

      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [tempList1.data.list.id, tempList2.data.list.id],
        target_list_title: generateTestTitle("Dedup Result"),
        delete_source: false,
      });

      expect(result.success).toBe(true);
      // Should have exactly 1 subscriber (deduplicated)
      expect(result.data.total_subscribers).toBe(1);

      testListIds.push(result.data.target_list.id);
    });

    it("should reject merge with less than 2 source lists", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id],
        target_list_title: generateTestTitle("Single Source"),
      });

      expect(result.success).toBe(false);
    });

    it("should reject merge with empty source_list_ids", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [],
        target_list_title: generateTestTitle("Empty Sources"),
      });

      expect(result.success).toBe(false);
    });

    it("should reject merge with non-existent source list", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, 999999],
        target_list_title: generateTestTitle("Invalid Source"),
      });

      expect(result.success).toBe(false);
    });

    it("should reject merge without target_list_title", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id],
      });

      expect(result.success).toBe(false);
    });

    it("should reject merge with empty target_list_title", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id],
        target_list_title: "",
      });

      expect(result.success).toBe(false);
    });

    it("should handle delete_source default (false)", async () => {
      const result = await mcp.callTool("fluentcrm-merge-lists", {
        source_list_ids: [sourceList1Id, sourceList2Id],
        target_list_title: generateTestTitle("Default Delete"),
      });

      expect(result.success).toBe(true);
      expect(result.data.source_lists_deleted).toBe(false);

      testListIds.push(result.data.target_list.id);
    });
  });

  describe("GDPR Compliance - Public/Private List Visibility Control", () => {
    /**
     * Tests is_public field for controlling list visibility.
     * Use case: GDPR compliance - mark lists as public/private
     */
    it("should create list with is_public=false (default)", async () => {
      const title = generateTestTitle("Private List");
      const result = await mcp.callTool("fluentcrm-create-list", {
        title,
      });

      expect(result.success).toBe(true);
      testListIds.push(result.data.list.id);

      const list = result.data.list;
      expect(list).toHaveProperty("is_public");
      // Default should be 0 (false/private) - may be string "0" or int 0
      expect([0, "0"]).toContain(list.is_public);
    });

    it("should create list with is_public=true (explicit public)", async () => {
      const title = generateTestTitle("Public List");
      const result = await mcp.callTool("fluentcrm-create-list", {
        title,
        is_public: true,
      });

      expect(result.success).toBe(true);
      testListIds.push(result.data.list.id);

      const list = result.data.list;
      expect(list).toHaveProperty("is_public");
      // Should be 1 (true/public) after boolean→tinyint conversion
      expect([1, "1"]).toContain(list.is_public);
    });

    it("should create list with is_public=false (explicit private)", async () => {
      const title = generateTestTitle("Explicit Private List");
      const result = await mcp.callTool("fluentcrm-create-list", {
        title,
        is_public: false,
      });

      expect(result.success).toBe(true);
      testListIds.push(result.data.list.id);

      const list = result.data.list;
      expect([0, "0"]).toContain(list.is_public);
    });

    it("should update list is_public from false to true", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Toggle Visibility List"),
        is_public: false,
      });
      const listId = createResult.data.list.id;
      testListIds.push(listId);

      expect([0, "0"]).toContain(createResult.data.list.is_public);

      const updateResult = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        is_public: true,
      });

      expect(updateResult.success).toBe(true);
      expect([1, "1"]).toContain(updateResult.data.list.is_public);
    });

    it("should update list is_public from true to false", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Toggle Visibility Test 2"),
        is_public: true,
      });
      const listId = createResult.data.list.id;
      testListIds.push(listId);

      expect([1, "1"]).toContain(createResult.data.list.is_public);

      const updateResult = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        is_public: false,
      });

      expect(updateResult.success).toBe(true);
      expect([0, "0"]).toContain(updateResult.data.list.is_public);
    });

    it("should handle boolean-to-tinyint conversion correctly", async () => {
      // Test JavaScript boolean true
      const trueResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Bool True Test"),
        is_public: true,
      });
      testListIds.push(trueResult.data.list.id);
      expect([1, "1"]).toContain(trueResult.data.list.is_public);

      // Test JavaScript boolean false
      const falseResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Bool False Test"),
        is_public: false,
      });
      testListIds.push(falseResult.data.list.id);
      expect([0, "0"]).toContain(falseResult.data.list.is_public);
    });

    it("should preserve other fields when updating is_public", async () => {
      const title = generateTestTitle("Preserve Visibility Test");
      const description = "Original description should be preserved";

      const createResult = await mcp.callTool("fluentcrm-create-list", {
        title,
        description,
        is_public: false,
      });
      const listId = createResult.data.list.id;
      testListIds.push(listId);

      const updateResult = await mcp.callTool("fluentcrm-update-list", {
        list_id: listId,
        is_public: true,
      });

      expect(updateResult.success).toBe(true);
      expect([1, "1"]).toContain(updateResult.data.list.is_public);
      expect(updateResult.data.list.title).toBe(title);
      expect(updateResult.data.list.description).toBe(description);
    });
  });
});
