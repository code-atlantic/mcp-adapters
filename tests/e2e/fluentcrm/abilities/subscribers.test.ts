/**
 * E2E Tests for FluentCRM Subscribers Abilities
 *
 * Tests all subscriber (contact) management tools including CRUD operations,
 * bulk operations, relationship management (lists/tags), status updates,
 * merging, and advanced search functionality.
 *
 * Target: 100% tool coverage with comprehensive edge case testing
 */

import {
  MCPClient,
  TEST_CONFIG,
  generateTestEmail,
  generateTestTitle,
} from "../../utils/mcp-client";

describe("FluentCRM Subscribers", () => {
  let mcp: MCPClient;
  const testSubscriberIds: number[] = [];
  const testListIds: number[] = [];
  const testTagIds: number[] = [];

  beforeAll(() => {
    mcp = new MCPClient(
      TEST_CONFIG.baseURL,
      TEST_CONFIG.username,
      TEST_CONFIG.password,
    );
  });

  afterAll(async () => {
    // Cleanup test subscribers
    if (testSubscriberIds.length > 0) {
      try {
        await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
          subscriber_ids: testSubscriberIds,
          confirm_delete: true,
        });
      } catch (error) {
        // Subscribers may already be deleted, ignore errors
      }
    }

    // Cleanup test lists
    for (const listId of testListIds) {
      try {
        await mcp.callTool("fluentcrm-delete-list", {
          list_id: listId,
          confirm_delete: true,
          delete_subscribers: false,
        });
      } catch (error) {
        // List may already be deleted, ignore errors
      }
    }

    // Cleanup test tags
    for (const tagId of testTagIds) {
      try {
        await mcp.callTool("fluentcrm-delete-tag", {
          tag_id: tagId,
          confirm_delete: true,
        });
      } catch (error) {
        // Tag may already be deleted, ignore errors
      }
    }
  });

  describe("Create Subscriber", () => {
    it("should create subscriber with minimal required fields", async () => {
      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toBeDefined();
      expect(result.data.subscriber.id).toBeDefined();
      expect(result.data.subscriber.email).toBe(email);
      expect(result.data.subscriber.status).toBe("subscribed"); // Default status

      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should create subscriber with full profile data", async () => {
      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "John",
        last_name: "Doe",
        status: "pending",
        phone: "+1-555-0123",
        address_line_1: "123 Main Street",
        address_line_2: "Apt 4B",
        city: "New York",
        state: "NY",
        postal_code: "10001",
        country: "US",
        timezone: "America/New_York",
        date_of_birth: "1990-05-15",
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.first_name).toBe("John");
      expect(result.data.subscriber.last_name).toBe("Doe");
      expect(result.data.subscriber.status).toBe("pending");

      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should create subscriber with custom field values", async () => {
      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Jane",
        custom_values: {
          company: "Acme Corp",
          website: "https://example.com",
          job_title: "Developer",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.id).toBeDefined();

      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should create subscriber with tags", async () => {
      // Create test tag first
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Subscriber Test Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Tagged",
        tags: [tagResult.data.tag.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.id).toBeDefined();

      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should create subscriber with lists", async () => {
      // Create test list first
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Subscriber Test List"),
      });
      testListIds.push(listResult.data.list.id);

      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Listed",
        lists: [listResult.data.list.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.id).toBeDefined();

      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should create subscriber with both tags and lists", async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Multi Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Multi List"),
      });
      testListIds.push(listResult.data.list.id);

      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Multi",
        tags: [tagResult.data.tag.id],
        lists: [listResult.data.list.id],
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle all status enum values", async () => {
      const statuses = [
        "subscribed",
        "pending",
        "unsubscribed",
        "bounced",
        "complained",
      ];

      for (const status of statuses) {
        const email = generateTestEmail();
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email,
          status,
        });

        expect(result.success).toBe(true);
        expect(result.data.subscriber.status).toBe(status);

        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should reject missing required email field", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        first_name: "No Email",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid email format", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: "invalid-email",
      });

      expect(result.success).toBe(false);
    });

    it("should reject duplicate email address", async () => {
      const email = generateTestEmail();

      // Create first subscriber
      const firstResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
      });
      expect(firstResult.success).toBe(true);
      testSubscriberIds.push(firstResult.data.subscriber.id);

      // Try to create duplicate
      const duplicateResult = await mcp.callTool(
        "fluentcrm-create-subscriber",
        {
          email,
        },
      );

      expect(duplicateResult.success).toBe(false);
    });

    it("should handle international addresses", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "François",
        last_name: "Müller",
        city: "São Paulo",
        country: "BR",
        postal_code: "01310-100",
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle special characters in name", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "O'Brien",
        last_name: "Smith-Jones",
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle unicode characters in name", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "李明",
        last_name: "王",
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle very long name", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "A".repeat(100),
        last_name: "B".repeat(100),
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });
  });

  describe("List Subscribers", () => {
    beforeAll(async () => {
      // Create test subscribers with different attributes for filtering
      for (let i = 0; i < 5; i++) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          first_name: `ListTest${i}`,
          status: i % 2 === 0 ? "subscribed" : "pending",
        });
        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should list subscribers with default pagination", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("subscribers");
      expect(result.data).toHaveProperty("total");
      expect(Array.isArray(result.data.subscribers)).toBe(true);
    });

    it("should respect pagination parameters", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        page: 1,
        per_page: 3,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeLessThanOrEqual(3);
      expect(result.data.page).toBe(1);
      expect(result.data.per_page).toBe(3);
    });

    it("should filter by status", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        status: "subscribed",
      });

      expect(result.success).toBe(true);
      result.data.subscribers.forEach((subscriber: any) => {
        expect(subscriber.status).toBe("subscribed");
      });
    });

    it("should handle all status enum values", async () => {
      const statuses = [
        "subscribed",
        "pending",
        "unsubscribed",
        "bounced",
        "complained",
      ];

      for (const status of statuses) {
        const result = await mcp.callTool("fluentcrm-list-subscribers", {
          status,
          per_page: 1,
        });

        expect(result.success).toBe(true);
      }
    });

    it("should search by email", async () => {
      const email = generateTestEmail();
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "SearchTest",
      });
      testSubscriberIds.push(createResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        search: email.split("@")[0], // Search by email prefix
      });

      expect(result.success).toBe(true);
      const found = result.data.subscribers.some((s: any) => s.email === email);
      expect(found).toBe(true);
    });

    it("should search by name", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        search: "ListTest",
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeGreaterThan(0);
    });

    it("should filter by tags", async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Filter Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        tags: [tagResult.data.tag.id],
      });
      testSubscriberIds.push(subResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        tags: [tagResult.data.tag.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBeGreaterThan(0);
    });

    it("should filter by lists", async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Filter List"),
      });
      testListIds.push(listResult.data.list.id);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        lists: [listResult.data.list.id],
      });
      testSubscriberIds.push(subResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        lists: [listResult.data.list.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBeGreaterThan(0);
    });

    it("should handle all orderby options", async () => {
      const orderbyFields = [
        "id",
        "email",
        "first_name",
        "last_name",
        "created_at",
        "updated_at",
      ];

      for (const orderby of orderbyFields) {
        const result = await mcp.callTool("fluentcrm-list-subscribers", {
          orderby,
          per_page: 5,
        });

        expect(result.success).toBe(true);
      }
    });

    it("should handle ASC and DESC order", async () => {
      const ascResult = await mcp.callTool("fluentcrm-list-subscribers", {
        order: "ASC",
        per_page: 5,
      });
      expect(ascResult.success).toBe(true);

      const descResult = await mcp.callTool("fluentcrm-list-subscribers", {
        order: "DESC",
        per_page: 5,
      });
      expect(descResult.success).toBe(true);
    });

    it("should handle pagination boundaries", async () => {
      const minResult = await mcp.callTool("fluentcrm-list-subscribers", {
        page: 1,
        per_page: 1,
      });
      expect(minResult.success).toBe(true);

      const maxResult = await mcp.callTool("fluentcrm-list-subscribers", {
        page: 1,
        per_page: 100,
      });
      expect(maxResult.success).toBe(true);
    });

    it("should combine multiple filters", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        status: "subscribed",
        search: "ListTest",
        order: "ASC",
      });

      expect(result.success).toBe(true);
    });
  });

  describe("Get Subscriber", () => {
    let testSubscriberId: number;
    let testEmail: string;

    beforeAll(async () => {
      testEmail = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: testEmail,
        first_name: "GetTest",
        last_name: "User",
      });
      testSubscriberId = result.data.subscriber.id;
      testSubscriberIds.push(testSubscriberId);
    });

    it("should get subscriber by ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toBeDefined();
      expect(result.data.subscriber.id).toBe(testSubscriberId);
      expect(result.data.subscriber.email).toBe(testEmail);
    });

    it("should get subscriber by email", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        email: testEmail,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.id).toBe(testSubscriberId);
      expect(result.data.subscriber.email).toBe(testEmail);
    });

    it("should include tags when requested", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["tags"],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toHaveProperty("tags");
      expect(Array.isArray(result.data.subscriber.tags)).toBe(true);
    });

    it("should include lists when requested", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["lists"],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toHaveProperty("lists");
      expect(Array.isArray(result.data.subscriber.lists)).toBe(true);
    });

    it("should include stats when requested", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["stats"],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toHaveProperty("stats");
      expect(result.data.subscriber.stats).toHaveProperty("total_emails_sent");
    });

    it("should include custom_fields when requested", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["custom_fields"],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toHaveProperty("custom_fields");
    });

    it("should include multiple relations when requested", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["tags", "lists", "stats", "custom_fields"],
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber).toHaveProperty("tags");
      expect(result.data.subscriber).toHaveProperty("lists");
      expect(result.data.subscriber).toHaveProperty("stats");
      expect(result.data.subscriber).toHaveProperty("custom_fields");
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent email", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        email: "nonexistent-" + generateTestEmail(),
      });

      expect(result.success).toBe(false);
    });

    it("should reject when both ID and email are missing", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        with: ["tags"],
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Update Subscriber", () => {
    let subscriberId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "Original",
        last_name: "Name",
      });
      subscriberId = result.data.subscriber.id;
      testSubscriberIds.push(subscriberId);
    });

    it("should update first name", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        first_name: "Updated",
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.first_name).toBe("Updated");
    });

    it("should update last name", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        last_name: "NewLastName",
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.last_name).toBe("NewLastName");
    });

    it("should update email", async () => {
      const newEmail = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        email: newEmail,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.email).toBe(newEmail);
    });

    it("should update phone", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        phone: "+1-555-9999",
      });

      expect(result.success).toBe(true);
    });

    it("should update address fields", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        address_line_1: "456 New Street",
        address_line_2: "Suite 100",
        city: "Boston",
        state: "MA",
        postal_code: "02101",
        country: "US",
      });

      expect(result.success).toBe(true);
    });

    it("should update timezone", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        timezone: "America/Los_Angeles",
      });

      expect(result.success).toBe(true);
    });

    it("should update date of birth", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        date_of_birth: "1985-12-25",
      });

      expect(result.success).toBe(true);
    });

    it("should update custom field values", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        custom_values: {
          company: "New Company",
          role: "Manager",
        },
      });

      expect(result.success).toBe(true);
    });

    it("should update multiple fields at once", async () => {
      const newEmail = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        email: newEmail,
        first_name: "Multi",
        last_name: "Update",
        phone: "+1-555-1111",
        city: "Chicago",
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber.email).toBe(newEmail);
      expect(result.data.subscriber.first_name).toBe("Multi");
      expect(result.data.subscriber.last_name).toBe("Update");
    });

    it("should reject update without subscriber_id", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        first_name: "NoID",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid email format", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: subscriberId,
        email: "invalid-email",
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: 999999,
        first_name: "Test",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Delete Subscriber", () => {
    it("should delete subscriber with confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const id = createResult.data.subscriber.id;

      const result = await mcp.callTool("fluentcrm-delete-subscriber", {
        subscriber_id: id,
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber_id).toBe(id);
    });

    it("should reject delete without confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const id = createResult.data.subscriber.id;
      testSubscriberIds.push(id);

      const result = await mcp.callTool("fluentcrm-delete-subscriber", {
        subscriber_id: id,
        confirm_delete: false,
      });

      expect(result.success).toBe(false);
    });

    it("should reject delete without confirm parameter", async () => {
      const result = await mcp.callTool("fluentcrm-delete-subscriber", {
        subscriber_id: 1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-delete-subscriber", {
        subscriber_id: 999999,
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Bulk Import Subscribers", () => {
    it("should import multiple subscribers", async () => {
      const subscribers = [
        {
          email: generateTestEmail(),
          first_name: "Bulk1",
          status: "subscribed",
        },
        { email: generateTestEmail(), first_name: "Bulk2", status: "pending" },
        {
          email: generateTestEmail(),
          first_name: "Bulk3",
          status: "subscribed",
        },
      ];

      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers,
      });

      expect(result.success).toBe(true);
      expect(result.data.imported).toBe(3);
      expect(result.data.failed).toBe(0);
    });

    it("should import with tags assignment", async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Bulk Import Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const subscribers = [
        { email: generateTestEmail(), first_name: "Tagged1" },
        { email: generateTestEmail(), first_name: "Tagged2" },
      ];

      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers,
        tags: [tagResult.data.tag.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.imported).toBe(2);
    });

    it("should import with lists assignment", async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Bulk Import List"),
      });
      testListIds.push(listResult.data.list.id);

      const subscribers = [
        { email: generateTestEmail(), first_name: "Listed1" },
        { email: generateTestEmail(), first_name: "Listed2" },
      ];

      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers,
        lists: [listResult.data.list.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.imported).toBe(2);
    });

    it("should handle update_existing false (skip duplicates)", async () => {
      const email = generateTestEmail();

      // First import
      const firstResult = await mcp.callTool(
        "fluentcrm-bulk-import-subscribers",
        {
          subscribers: [{ email, first_name: "First" }],
          update_existing: false,
        },
      );
      expect(firstResult.success).toBe(true);

      // Second import with same email should fail
      const secondResult = await mcp.callTool(
        "fluentcrm-bulk-import-subscribers",
        {
          subscribers: [{ email, first_name: "Second" }],
          update_existing: false,
        },
      );

      expect(secondResult.success).toBe(true);
      expect(secondResult.data.failed).toBe(1);
    });

    it("should handle update_existing true (update duplicates)", async () => {
      const email = generateTestEmail();

      // First import
      const firstResult = await mcp.callTool(
        "fluentcrm-bulk-import-subscribers",
        {
          subscribers: [{ email, first_name: "First" }],
        },
      );
      expect(firstResult.success).toBe(true);

      // Second import with update_existing
      const secondResult = await mcp.callTool(
        "fluentcrm-bulk-import-subscribers",
        {
          subscribers: [{ email, first_name: "Updated" }],
          update_existing: true,
        },
      );

      expect(secondResult.success).toBe(true);
      expect(secondResult.data.updated).toBe(1);
    });

    it("should reject invalid email formats", async () => {
      const subscribers = [
        { email: "invalid-email", first_name: "Invalid" },
        { email: generateTestEmail(), first_name: "Valid" },
      ];

      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers,
      });

      expect(result.success).toBe(true);
      expect(result.data.failed).toBe(1);
      expect(result.data.imported).toBe(1);
    });

    it("should reject subscribers without email", async () => {
      const subscribers = [
        { first_name: "NoEmail" },
        { email: generateTestEmail(), first_name: "HasEmail" },
      ];

      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers,
      });

      expect(result.success).toBe(true);
      expect(result.data.failed).toBe(1);
      expect(result.data.imported).toBe(1);
    });

    it("should handle empty subscribers array", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-import-subscribers", {
        subscribers: [],
      });

      expect(result.success).toBe(false);
    });

    it("should handle missing subscribers parameter", async () => {
      const result = await mcp.callTool(
        "fluentcrm-bulk-import-subscribers",
        {},
      );

      expect(result.success).toBe(false);
    });
  });

  describe("Bulk Update Subscribers", () => {
    let bulkUpdateIds: number[] = [];

    beforeAll(async () => {
      for (let i = 0; i < 3; i++) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          first_name: `BulkUpdate${i}`,
        });
        bulkUpdateIds.push(result.data.subscriber.id);
        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should bulk update status", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: { status: "pending" },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(3);
      expect(result.data.failed).toBe(0);
    });

    it("should bulk update timezone", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: { timezone: "Europe/London" },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(3);
    });

    it("should bulk update country", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: { country: "GB" },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(3);
    });

    it("should bulk update custom values", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: {
          custom_values: {
            bulk_field: "bulk_value",
          },
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(3);
    });

    it("should bulk update multiple fields", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: {
          status: "subscribed",
          timezone: "America/Chicago",
          country: "US",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(3);
    });

    it("should handle non-existent IDs gracefully", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: [999999, bulkUpdateIds[0]],
        update_data: { status: "pending" },
      });

      expect(result.success).toBe(true);
      expect(result.data.updated).toBe(1);
      expect(result.data.failed).toBe(1);
    });

    it("should reject empty subscriber_ids", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: [],
        update_data: { status: "pending" },
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty update_data", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-update-subscribers", {
        subscriber_ids: bulkUpdateIds,
        update_data: {},
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Bulk Delete Subscribers", () => {
    it("should bulk delete with confirmation", async () => {
      const ids: number[] = [];
      for (let i = 0; i < 3; i++) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
        });
        ids.push(result.data.subscriber.id);
      }

      const result = await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
        subscriber_ids: ids,
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.deleted).toBe(3);
      expect(result.data.failed).toBe(0);
    });

    it("should reject bulk delete without confirmation", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
        subscriber_ids: [1, 2, 3],
        confirm_delete: false,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent IDs gracefully", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const validId = createResult.data.subscriber.id;

      const result = await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
        subscriber_ids: [999999, validId],
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.deleted).toBe(1);
      expect(result.data.failed).toBe(1);
    });

    it("should reject empty subscriber_ids", async () => {
      const result = await mcp.callTool("fluentcrm-bulk-delete-subscribers", {
        subscriber_ids: [],
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Add Subscriber to List", () => {
    let subscriberId: number;
    let listId: number;

    beforeAll(async () => {
      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      subscriberId = subResult.data.subscriber.id;
      testSubscriberIds.push(subscriberId);

      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Relationship List"),
      });
      listId = listResult.data.list.id;
      testListIds.push(listId);
    });

    it("should add subscriber to list", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: subscriberId,
        list_id: listId,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber_id).toBe(subscriberId);
      expect(result.data.list_id).toBe(listId);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: 999999,
        list_id: listId,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent list ID", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: subscriberId,
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing subscriber_id", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        list_id: listId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing list_id", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-to-list", {
        subscriber_id: subscriberId,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Remove Subscriber from List", () => {
    let subscriberId: number;
    let listId: number;

    beforeAll(async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Remove Test List"),
      });
      listId = listResult.data.list.id;
      testListIds.push(listId);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        lists: [listId],
      });
      subscriberId = subResult.data.subscriber.id;
      testSubscriberIds.push(subscriberId);
    });

    it("should remove subscriber from list", async () => {
      const result = await mcp.callTool(
        "fluentcrm-remove-subscriber-from-list",
        {
          subscriber_id: subscriberId,
          list_id: listId,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.subscriber_id).toBe(subscriberId);
      expect(result.data.list_id).toBe(listId);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool(
        "fluentcrm-remove-subscriber-from-list",
        {
          subscriber_id: 999999,
          list_id: listId,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should reject missing parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-remove-subscriber-from-list",
        {
          subscriber_id: subscriberId,
        },
      );

      expect(result.success).toBe(false);
    });
  });

  describe("Add Subscriber Tag", () => {
    let subscriberId: number;
    let tagId: number;

    beforeAll(async () => {
      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      subscriberId = subResult.data.subscriber.id;
      testSubscriberIds.push(subscriberId);

      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Relationship Tag"),
      });
      tagId = tagResult.data.tag.id;
      testTagIds.push(tagId);
    });

    it("should add tag to subscriber", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-tag", {
        subscriber_id: subscriberId,
        tag_id: tagId,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber_id).toBe(subscriberId);
      expect(result.data.tag_id).toBe(tagId);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-tag", {
        subscriber_id: 999999,
        tag_id: tagId,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent tag ID", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-tag", {
        subscriber_id: subscriberId,
        tag_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing parameters", async () => {
      const result = await mcp.callTool("fluentcrm-add-subscriber-tag", {
        subscriber_id: subscriberId,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Remove Subscriber Tag", () => {
    let subscriberId: number;
    let tagId: number;

    beforeAll(async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Remove Tag Test"),
      });
      tagId = tagResult.data.tag.id;
      testTagIds.push(tagId);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        tags: [tagId],
      });
      subscriberId = subResult.data.subscriber.id;
      testSubscriberIds.push(subscriberId);
    });

    it("should remove tag from subscriber", async () => {
      const result = await mcp.callTool("fluentcrm-remove-subscriber-tag", {
        subscriber_id: subscriberId,
        tag_id: tagId,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscriber_id).toBe(subscriberId);
      expect(result.data.tag_id).toBe(tagId);
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-remove-subscriber-tag", {
        subscriber_id: 999999,
        tag_id: tagId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing parameters", async () => {
      const result = await mcp.callTool("fluentcrm-remove-subscriber-tag", {
        subscriber_id: subscriberId,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Update Subscriber Status", () => {
    let subscriberId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        status: "subscribed",
      });
      subscriberId = result.data.subscriber.id;
      testSubscriberIds.push(subscriberId);
    });

    it("should update status to pending", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: subscriberId,
        status: "pending",
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("pending");
    });

    it("should update status to unsubscribed", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: subscriberId,
        status: "unsubscribed",
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("unsubscribed");
    });

    it("should update status to bounced", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: subscriberId,
        status: "bounced",
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("bounced");
    });

    it("should update status to complained", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: subscriberId,
        status: "complained",
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("complained");
    });

    it("should handle all status transitions", async () => {
      const statuses = [
        "pending",
        "subscribed",
        "unsubscribed",
        "bounced",
        "complained",
      ];

      for (const status of statuses) {
        const result = await mcp.callTool(
          "fluentcrm-update-subscriber-status",
          {
            subscriber_id: subscriberId,
            status,
          },
        );

        expect(result.success).toBe(true);
        expect(result.data.status).toBe(status);
      }
    });

    it("should handle non-existent subscriber ID", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: 999999,
        status: "pending",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing parameters", async () => {
      const result = await mcp.callTool("fluentcrm-update-subscriber-status", {
        subscriber_id: subscriberId,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Merge Subscribers", () => {
    it("should merge two subscribers", async () => {
      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "Primary",
      });
      const merge = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "Merge",
      });

      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: [merge.data.subscriber.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.primary_subscriber_id).toBe(
        primary.data.subscriber.id,
      );
      expect(result.data.merged_count).toBe(1);
    });

    it("should merge multiple subscribers", async () => {
      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "Primary",
      });

      const mergeIds: number[] = [];
      for (let i = 0; i < 3; i++) {
        const merge = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          first_name: `Merge${i}`,
        });
        mergeIds.push(merge.data.subscriber.id);
      }

      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: mergeIds,
      });

      expect(result.success).toBe(true);
      expect(result.data.merged_count).toBe(3);
    });

    it("should merge tags from merge subscribers to primary", async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Merge Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const merge = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        tags: [tagResult.data.tag.id],
      });

      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: [merge.data.subscriber.id],
      });

      expect(result.success).toBe(true);

      // Verify primary has the tag
      const getResult = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: primary.data.subscriber.id,
        with: ["tags"],
      });

      expect(getResult.success).toBe(true);
      const hasTag = getResult.data.subscriber.tags.some(
        (t: any) => t.id === tagResult.data.tag.id,
      );
      expect(hasTag).toBe(true);
    });

    it("should merge lists from merge subscribers to primary", async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Merge List"),
      });
      testListIds.push(listResult.data.list.id);

      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const merge = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        lists: [listResult.data.list.id],
      });

      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: [merge.data.subscriber.id],
      });

      expect(result.success).toBe(true);

      // Verify primary has the list
      const getResult = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: primary.data.subscriber.id,
        with: ["lists"],
      });

      expect(getResult.success).toBe(true);
      const hasList = getResult.data.subscriber.lists.some(
        (l: any) => l.id === listResult.data.list.id,
      );
      expect(hasList).toBe(true);
    });

    it("should handle non-existent primary subscriber", async () => {
      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: 999999,
        merge_subscriber_ids: [1],
      });

      expect(result.success).toBe(false);
    });

    it("should handle empty merge_subscriber_ids", async () => {
      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: [],
      });

      expect(result.success).toBe(false);
    });

    it("should skip non-existent merge IDs gracefully", async () => {
      const primary = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });
      const merge = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
      });

      testSubscriberIds.push(primary.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-merge-subscribers", {
        primary_subscriber_id: primary.data.subscriber.id,
        merge_subscriber_ids: [999999, merge.data.subscriber.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.merged_count).toBe(1);
    });
  });

  describe("Search Subscribers", () => {
    beforeAll(async () => {
      // Create diverse test data for search
      await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "SearchTest",
        last_name: "Alpha",
        country: "US",
      });
      await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "SearchTest",
        last_name: "Beta",
        country: "GB",
      });
    });

    it("should search by email contains", async () => {
      const email = generateTestEmail();
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "EmailSearch",
      });
      testSubscriberIds.push(createResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          email_contains: email.split("@")[0],
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeGreaterThan(0);
    });

    it("should search by name contains", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          name_contains: "SearchTest",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeGreaterThan(0);
    });

    it("should search by country", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          country: "US",
        },
      });

      expect(result.success).toBe(true);
    });

    it("should search by has_tags", async () => {
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Search Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        tags: [tagResult.data.tag.id],
      });
      testSubscriberIds.push(subResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          has_tags: [tagResult.data.tag.id],
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBeGreaterThan(0);
    });

    it("should search by has_lists", async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Search List"),
      });
      testListIds.push(listResult.data.list.id);

      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        lists: [listResult.data.list.id],
      });
      testSubscriberIds.push(subResult.data.subscriber.id);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          has_lists: [listResult.data.list.id],
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBeGreaterThan(0);
    });

    it("should search by status_in", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          status_in: ["subscribed", "pending"],
        },
      });

      expect(result.success).toBe(true);
    });

    it("should search by created_after", async () => {
      const yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          created_after: yesterday.toISOString().split("T")[0],
        },
      });

      expect(result.success).toBe(true);
    });

    it("should search by created_before", async () => {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          created_before: tomorrow.toISOString().split("T")[0],
        },
      });

      expect(result.success).toBe(true);
    });

    it("should search by last_activity_after", async () => {
      const yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);

      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          last_activity_after: yesterday.toISOString().split("T")[0],
        },
      });

      expect(result.success).toBe(true);
    });

    it("should combine multiple search filters", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          name_contains: "SearchTest",
          status_in: ["subscribed", "pending"],
          country: "US",
        },
      });

      expect(result.success).toBe(true);
    });

    it("should respect pagination parameters", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          status_in: ["subscribed"],
        },
        page: 1,
        per_page: 5,
      });

      expect(result.success).toBe(true);
      expect(result.data.subscribers.length).toBeLessThanOrEqual(5);
      expect(result.data.page).toBe(1);
      expect(result.data.per_page).toBe(5);
    });

    it("should handle empty search results", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {
        filters: {
          email_contains: "nonexistent-" + Date.now(),
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.total).toBe(0);
      expect(result.data.subscribers).toEqual([]);
    });

    it("should reject missing filters parameter", async () => {
      const result = await mcp.callTool("fluentcrm-search-subscribers", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Complete Field Coverage - All Subscriber Data Returned", () => {
    /**
     * Tests that all subscriber fields are returned (not just manually selected subset).
     * Previously only 6-10 fields were returned; now returns complete model data (30+ fields).
     */
    it("should return all subscriber fields from create-subscriber (30+ fields)", async () => {
      const email = generateTestEmail();
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Complete",
        last_name: "Test",
        phone: "+1-555-0100",
        address_line_1: "123 Test St",
        city: "Test City",
        state: "CA",
        postal_code: "90210",
        country: "US",
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;
      testSubscriberIds.push(subscriber.id);

      // Core fields (always present)
      expect(subscriber).toHaveProperty("id");
      expect(subscriber).toHaveProperty("email");
      expect(subscriber).toHaveProperty("first_name");
      expect(subscriber).toHaveProperty("last_name");
      expect(subscriber).toHaveProperty("status");
      expect(subscriber).toHaveProperty("created_at");
      expect(subscriber).toHaveProperty("updated_at");

      // Extended fields (NOW included via toArray())
      expect(subscriber).toHaveProperty("prefix");
      expect(subscriber).toHaveProperty("phone");
      expect(subscriber).toHaveProperty("address_line_1");
      expect(subscriber).toHaveProperty("address_line_2");
      expect(subscriber).toHaveProperty("city");
      expect(subscriber).toHaveProperty("state");
      expect(subscriber).toHaveProperty("postal_code");
      expect(subscriber).toHaveProperty("country");
      expect(subscriber).toHaveProperty("ip");
      expect(subscriber).toHaveProperty("timezone");
      expect(subscriber).toHaveProperty("date_of_birth");
      expect(subscriber).toHaveProperty("source");
      expect(subscriber).toHaveProperty("avatar");
      expect(subscriber).toHaveProperty("contact_type");
      expect(subscriber).toHaveProperty("life_time_value");
      expect(subscriber).toHaveProperty("total_points");
      expect(subscriber).toHaveProperty("last_activity");

      // Verify data correctness
      expect(subscriber.first_name).toBe("Complete");
      expect(subscriber.phone).toBe("+1-555-0100");
      expect(subscriber.city).toBe("Test City");
    });

    it("should return all fields from list-subscribers", async () => {
      const result = await mcp.callTool("fluentcrm-list-subscribers", {
        limit: 1,
      });

      expect(result.success).toBe(true);
      if (result.data.subscribers.length > 0) {
        const subscriber = result.data.subscribers[0];

        // Verify extended fields are present
        expect(subscriber).toHaveProperty("id");
        expect(subscriber).toHaveProperty("email");
        expect(subscriber).toHaveProperty("status");
        expect(subscriber).toHaveProperty("phone");
        expect(subscriber).toHaveProperty("address_line_1");
        expect(subscriber).toHaveProperty("city");
        expect(subscriber).toHaveProperty("state");
        expect(subscriber).toHaveProperty("country");
        expect(subscriber).toHaveProperty("created_at");
      }
    });

    it("should return all fields from update-subscriber", async () => {
      const email = generateTestEmail();
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
      });
      testSubscriberIds.push(createResult.data.subscriber.id);

      const updateResult = await mcp.callTool("fluentcrm-update-subscriber", {
        subscriber_id: createResult.data.subscriber.id,
        first_name: "Updated",
        phone: "+1-555-9999",
        city: "New City",
      });

      expect(updateResult.success).toBe(true);
      const subscriber = updateResult.data.subscriber;

      // Verify all fields returned
      expect(subscriber).toHaveProperty("id");
      expect(subscriber).toHaveProperty("email");
      expect(subscriber).toHaveProperty("first_name");
      expect(subscriber).toHaveProperty("phone");
      expect(subscriber).toHaveProperty("city");
      expect(subscriber).toHaveProperty("address_line_1");
      expect(subscriber).toHaveProperty("state");
      expect(subscriber).toHaveProperty("country");
      expect(subscriber).toHaveProperty("life_time_value");
      expect(subscriber).toHaveProperty("total_points");

      // Verify updates applied
      expect(subscriber.first_name).toBe("Updated");
      expect(subscriber.phone).toBe("+1-555-9999");
      expect(subscriber.city).toBe("New City");
    });

    it("should return all fields from search-subscribers", async () => {
      const email = generateTestEmail();
      const createResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "SearchTest",
        last_name: "Fields",
      });
      testSubscriberIds.push(createResult.data.subscriber.id);

      const searchResult = await mcp.callTool("fluentcrm-search-subscribers", {
        search: "SearchTest",
      });

      expect(searchResult.success).toBe(true);
      expect(searchResult.data.subscribers.length).toBeGreaterThan(0);

      const foundSubscriber = searchResult.data.subscribers.find(
        (s: any) => s.email === email,
      );
      expect(foundSubscriber).toBeDefined();

      // Verify extended fields in search results
      expect(foundSubscriber).toHaveProperty("id");
      expect(foundSubscriber).toHaveProperty("email");
      expect(foundSubscriber).toHaveProperty("phone");
      expect(foundSubscriber).toHaveProperty("city");
      expect(foundSubscriber).toHaveProperty("life_time_value");
      expect(foundSubscriber).toHaveProperty("total_points");
    });
  });

  describe("Relationship Loading - Tags and Lists via 'with' Parameter", () => {
    let testSubscriberId: number;
    let testListId: number;
    let testTagId: number;

    beforeAll(async () => {
      // Create test list
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Relationship Test List"),
      });
      testListId = listResult.data.list.id;
      testListIds.push(testListId);

      // Create test tag
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Relationship Test Tag"),
      });
      testTagId = tagResult.data.tag.id;
      testTagIds.push(testTagId);

      // Create subscriber with list and tag
      const email = generateTestEmail();
      const subResult = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
        first_name: "Relationship",
        last_name: "Test",
      });
      testSubscriberId = subResult.data.subscriber.id;
      testSubscriberIds.push(testSubscriberId);

      // Attach list and tag
      await mcp.callTool("fluentcrm-attach-list-to-subscriber", {
        subscriber_id: testSubscriberId,
        list_id: testListId,
      });
      await mcp.callTool("fluentcrm-attach-tag-to-subscriber", {
        subscriber_id: testSubscriberId,
        tag_id: testTagId,
      });
    });

    /**
     * BEFORE: get-subscriber didn't support relationship loading
     * AFTER: Supports 'with' parameter to eager-load tags/lists
     */
    it("should NOT include relationships by default", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;

      // Relations should NOT be included by default
      expect(subscriber.tags).toBeUndefined();
      expect(subscriber.lists).toBeUndefined();
    });

    it("should load tags relationship when with=['tags']", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["tags"],
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;

      // Tags should be loaded
      expect(subscriber.tags).toBeDefined();
      expect(Array.isArray(subscriber.tags)).toBe(true);
      expect(subscriber.tags.length).toBeGreaterThan(0);
      expect(subscriber.tags[0]).toHaveProperty("id");
      expect(subscriber.tags[0]).toHaveProperty("title");

      // Lists should NOT be loaded
      expect(subscriber.lists).toBeUndefined();
    });

    it("should load lists relationship when with=['lists']", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["lists"],
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;

      // Lists should be loaded
      expect(subscriber.lists).toBeDefined();
      expect(Array.isArray(subscriber.lists)).toBe(true);
      expect(subscriber.lists.length).toBeGreaterThan(0);
      expect(subscriber.lists[0]).toHaveProperty("id");
      expect(subscriber.lists[0]).toHaveProperty("title");

      // Tags should NOT be loaded
      expect(subscriber.tags).toBeUndefined();
    });

    it("should load BOTH relationships when with=['tags','lists']", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["tags", "lists"],
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;

      // BOTH should be loaded
      expect(subscriber.tags).toBeDefined();
      expect(subscriber.lists).toBeDefined();
      expect(Array.isArray(subscriber.tags)).toBe(true);
      expect(Array.isArray(subscriber.lists)).toBe(true);
      expect(subscriber.tags.length).toBeGreaterThan(0);
      expect(subscriber.lists.length).toBeGreaterThan(0);
    });

    it("should ignore invalid relationship names in 'with'", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber", {
        subscriber_id: testSubscriberId,
        with: ["invalid", "tags", "fake_relation"],
      });

      expect(result.success).toBe(true);
      const subscriber = result.data.subscriber;

      // Only valid relationship (tags) should be loaded
      expect(subscriber.tags).toBeDefined();
      expect(subscriber.invalid).toBeUndefined();
      expect(subscriber.fake_relation).toBeUndefined();
    });
  });

  describe("Edge Cases and Boundary Conditions", () => {
    it("should handle empty tags array", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        tags: [],
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle empty lists array", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        lists: [],
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle very long email", async () => {
      const longLocal = "a".repeat(50);
      const email = `${longLocal}@example.com`;

      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email,
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle phone number formats", async () => {
      const phoneFormats = [
        "+1-555-0123",
        "(555) 123-4567",
        "555.123.4567",
        "+44 20 1234 5678",
      ];

      for (const phone of phoneFormats) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          phone,
        });

        expect(result.success).toBe(true);
        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should handle various date of birth formats", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        date_of_birth: "1990-01-01",
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle nested custom field values", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        custom_values: {
          preferences: {
            newsletter: true,
            updates: false,
          },
          metadata: {
            source: "api",
            campaign: "test",
          },
        },
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });

    it("should handle international postal codes", async () => {
      const postalCodes = [
        { country: "US", code: "90210" },
        { country: "GB", code: "SW1A 1AA" },
        { country: "CA", code: "K1A 0B1" },
        { country: "JP", code: "100-0001" },
      ];

      for (const { country, code } of postalCodes) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          country,
          postal_code: code,
        });

        expect(result.success).toBe(true);
        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should handle all timezone identifiers", async () => {
      const timezones = [
        "America/New_York",
        "Europe/London",
        "Asia/Tokyo",
        "Australia/Sydney",
        "UTC",
      ];

      for (const timezone of timezones) {
        const result = await mcp.callTool("fluentcrm-create-subscriber", {
          email: generateTestEmail(),
          timezone,
        });

        expect(result.success).toBe(true);
        testSubscriberIds.push(result.data.subscriber.id);
      }
    });

    it("should handle subscriber with all optional fields empty", async () => {
      const result = await mcp.callTool("fluentcrm-create-subscriber", {
        email: generateTestEmail(),
        first_name: "",
        last_name: "",
        phone: "",
      });

      expect(result.success).toBe(true);
      testSubscriberIds.push(result.data.subscriber.id);
    });
  });
});
