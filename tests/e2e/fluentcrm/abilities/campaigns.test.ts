/**
 * E2E Tests for FluentCRM Campaigns Abilities
 *
 * Tests all campaign management tools including CRUD operations,
 * lifecycle management (schedule, send, pause, resume, cancel),
 * testing, and preview functionality.
 */

import {
  MCPClient,
  TEST_CONFIG,
  generateTestEmail,
  generateTestTitle,
} from "../../utils/mcp-client";

describe("FluentCRM Campaigns", () => {
  let mcp: MCPClient;
  const testCampaignIds: number[] = [];
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
    // Cleanup test campaigns
    for (const campaignId of testCampaignIds) {
      try {
        await mcp.callTool("fluentcrm-delete-campaign", {
          campaign_id: campaignId,
          confirm: true,
        });
      } catch (error) {
        // Campaign may already be deleted, ignore errors
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

  describe("Create Campaign", () => {
    it("should create campaign with minimal required fields", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Minimal Campaign"),
        subject: "Test Email Subject",
        email_body: "<p>This is a test email body</p>",
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();
      expect(result.data.title).toContain("Minimal Campaign");
      expect(result.data.subject).toBe("Test Email Subject");
      expect(result.data.status).toBe("draft");

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should create campaign with sender information", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign with Sender"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        sender_name: "Test Sender",
        sender_email: "sender@example.com",
        reply_to_name: "Reply Name",
        reply_to_email: "reply@example.com",
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should create campaign with template ID", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign with Template"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        template_id: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should create campaign with list targeting", async () => {
      // Create test list first
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Campaign Test List"),
      });
      testListIds.push(listResult.data.list.id);

      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign with List"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        list_ids: [listResult.data.list.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should create campaign with tag targeting", async () => {
      // Create test tag first
      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Campaign Test Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign with Tag"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        tag_ids: [tagResult.data.tag.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should create campaign with both lists and tags", async () => {
      const listResult = await mcp.callTool("fluentcrm-create-list", {
        title: generateTestTitle("Multi Target List"),
      });
      testListIds.push(listResult.data.list.id);

      const tagResult = await mcp.callTool("fluentcrm-create-tag", {
        title: generateTestTitle("Multi Target Tag"),
      });
      testTagIds.push(tagResult.data.tag.id);

      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign Multi Target"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        list_ids: [listResult.data.list.id],
        tag_ids: [tagResult.data.tag.id],
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should reject missing required title field", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required subject field", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: "Test Campaign",
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required email_body field", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: "Test Campaign",
        subject: "Test Subject",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("List Campaigns", () => {
    beforeAll(async () => {
      // Create test campaigns with different statuses
      const draftResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Draft Campaign"),
        subject: "Draft Subject",
        email_body: "<p>Draft body</p>",
      });
      testCampaignIds.push(draftResult.data.campaign_id);
    });

    it("should list campaigns with default pagination", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("campaigns");
      expect(result.data).toHaveProperty("total");
      expect(Array.isArray(result.data.campaigns)).toBe(true);
    });

    it("should respect pagination parameters", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {
        page: 1,
        per_page: 5,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaigns.length).toBeLessThanOrEqual(5);
      expect(result.data.page).toBe(1);
      expect(result.data.per_page).toBe(5);
    });

    it("should filter by draft status", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {
        status: "draft",
      });

      expect(result.success).toBe(true);
      result.data.campaigns.forEach((campaign: any) => {
        expect(campaign.status).toBe("draft");
      });
    });

    it("should handle all status enum values", async () => {
      const statuses = [
        "draft",
        "scheduled",
        "pending-scheduled",
        "working",
        "processing",
        "paused",
        "archived",
      ];

      for (const status of statuses) {
        const result = await mcp.callTool("fluentcrm-list-campaigns", {
          status,
          per_page: 1,
        });

        expect(result.success).toBe(true);
      }
    });

    it("should filter by campaign type", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {
        type: "campaign",
      });

      expect(result.success).toBe(true);
    });

    it("should filter by recurring type", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {
        type: "recurring",
      });

      expect(result.success).toBe(true);
    });

    it("should handle pagination boundaries", async () => {
      const minResult = await mcp.callTool("fluentcrm-list-campaigns", {
        page: 1,
        per_page: 1,
      });
      expect(minResult.success).toBe(true);

      const maxResult = await mcp.callTool("fluentcrm-list-campaigns", {
        page: 1,
        per_page: 100,
      });
      expect(maxResult.success).toBe(true);
    });

    it("should combine status and type filters", async () => {
      const result = await mcp.callTool("fluentcrm-list-campaigns", {
        status: "draft",
        type: "campaign",
      });

      expect(result.success).toBe(true);
    });
  });

  describe("Get Campaign", () => {
    let campaignId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Get Test Campaign"),
        subject: "Get Test Subject",
        email_body: "<p>Get test body</p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should get campaign by ID without stats", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.id).toBe(campaignId);
      expect(result.data.title).toBeDefined();
      expect(result.data.subject).toBeDefined();
      expect(result.data.email_body).toBeDefined();
      expect(result.data.status).toBe("draft");
    });

    it("should get campaign with stats included", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign", {
        campaign_id: campaignId,
        include_stats: true,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("stats");
      expect(result.data.stats).toHaveProperty("total_recipients");
      expect(result.data.stats).toHaveProperty("sent");
      expect(result.data.stats).toHaveProperty("opened");
      expect(result.data.stats).toHaveProperty("clicked");
      expect(result.data.stats).toHaveProperty("bounced");
      expect(result.data.stats).toHaveProperty("unsubscribed");
    });

    it("should get campaign with stats set to false", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign", {
        campaign_id: campaignId,
        include_stats: false,
      });

      expect(result.success).toBe(true);
      expect(result.data.stats).toBeUndefined();
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign", {});

      expect(result.success).toBe(false);
    });
  });

  describe("Update Campaign", () => {
    let campaignId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Update Test Campaign"),
        subject: "Original Subject",
        email_body: "<p>Original body</p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should update campaign title", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        title: "Updated Title",
      });

      expect(result.success).toBe(true);
      expect(result.data.title).toBe("Updated Title");
    });

    it("should update campaign subject", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        subject: "Updated Subject",
      });

      expect(result.success).toBe(true);
      expect(result.data.subject).toBe("Updated Subject");
    });

    it("should update campaign email body", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        email_body: "<p>Updated body</p>",
      });

      expect(result.success).toBe(true);
    });

    it("should update sender name", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        sender_name: "Updated Sender",
      });

      expect(result.success).toBe(true);
    });

    it("should update sender email", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        sender_email: "updated@example.com",
      });

      expect(result.success).toBe(true);
    });

    it("should update multiple fields at once", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        title: "Multi Update Title",
        subject: "Multi Update Subject",
        email_body: "<p>Multi update body</p>",
        sender_name: "Multi Sender",
        sender_email: "multi@example.com",
      });

      expect(result.success).toBe(true);
      expect(result.data.title).toBe("Multi Update Title");
      expect(result.data.subject).toBe("Multi Update Subject");
    });

    it("should reject update without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        title: "Updated Title",
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: 999999,
        title: "Updated Title",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Delete Campaign", () => {
    it("should delete campaign with confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Delete Test Campaign"),
        subject: "Delete Test",
        email_body: "<p>Delete test</p>",
      });
      const id = createResult.data.campaign_id;

      const result = await mcp.callTool("fluentcrm-delete-campaign", {
        campaign_id: id,
        confirm: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBe(id);
    });

    it("should reject delete without confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("No Delete Campaign"),
        subject: "No Delete",
        email_body: "<p>No delete</p>",
      });
      const id = createResult.data.campaign_id;
      testCampaignIds.push(id);

      const result = await mcp.callTool("fluentcrm-delete-campaign", {
        campaign_id: id,
        confirm: false,
      });

      expect(result.success).toBe(false);
    });

    it("should reject delete without confirm parameter", async () => {
      const result = await mcp.callTool("fluentcrm-delete-campaign", {
        campaign_id: 1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-delete-campaign", {
        campaign_id: 999999,
        confirm: true,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Duplicate Campaign", () => {
    let originalCampaignId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Original Campaign"),
        subject: "Original Subject",
        email_body: "<p>Original body</p>",
      });
      originalCampaignId = result.data.campaign_id;
      testCampaignIds.push(originalCampaignId);
    });

    it("should duplicate campaign with default title", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-campaign", {
        campaign_id: originalCampaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBeDefined();
      expect(result.data.campaign_id).not.toBe(originalCampaignId);
      expect(result.data.title).toContain("Copy of");
      expect(result.data.status).toBe("draft");

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should duplicate campaign with custom title", async () => {
      const customTitle = generateTestTitle("Custom Duplicate");
      const result = await mcp.callTool("fluentcrm-duplicate-campaign", {
        campaign_id: originalCampaignId,
        new_title: customTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.title).toBe(customTitle);
      expect(result.data.status).toBe("draft");

      testCampaignIds.push(result.data.campaign_id);
    });

    it("should reject duplicate without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-duplicate-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Schedule Campaign", () => {
    let campaignId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Schedule Test Campaign"),
        subject: "Schedule Test",
        email_body: "<p>Schedule test</p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should schedule campaign for future date", async () => {
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      const result = await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
        scheduled_at: scheduledAt,
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("scheduled");
      expect(result.data.scheduled_at).toBeDefined();
    });

    it("should reject invalid datetime format", async () => {
      const result = await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
        scheduled_at: "2025-01-01",
      });

      expect(result.success).toBe(false);
    });

    it("should reject scheduling without scheduled_at", async () => {
      const result = await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject scheduling non-existent campaign", async () => {
      const result = await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: 999999,
        scheduled_at: "2025-12-31 23:59:59",
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Send Campaign", () => {
    let campaignId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Send Test Campaign"),
        subject: "Send Test",
        email_body: "<p>Send test</p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should initiate immediate campaign send", async () => {
      const result = await mcp.callTool("fluentcrm-send-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBe(campaignId);
      expect(result.data.status).toBe("sending");
    });

    it("should reject send without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-send-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-send-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Pause Campaign", () => {
    let scheduledCampaignId: number;

    beforeEach(async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Pause Test Campaign"),
        subject: "Pause Test",
        email_body: "<p>Pause test</p>",
      });
      scheduledCampaignId = createResult.data.campaign_id;
      testCampaignIds.push(scheduledCampaignId);

      // Schedule it first
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: scheduledCampaignId,
        scheduled_at: scheduledAt,
      });
    });

    it("should pause scheduled campaign", async () => {
      const result = await mcp.callTool("fluentcrm-pause-campaign", {
        campaign_id: scheduledCampaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("paused");
    });

    it("should reject pause without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-pause-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-pause-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Resume Campaign", () => {
    let pausedCampaignId: number;

    beforeEach(async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Resume Test Campaign"),
        subject: "Resume Test",
        email_body: "<p>Resume test</p>",
      });
      pausedCampaignId = createResult.data.campaign_id;
      testCampaignIds.push(pausedCampaignId);

      // Schedule and pause it
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: pausedCampaignId,
        scheduled_at: scheduledAt,
      });

      await mcp.callTool("fluentcrm-pause-campaign", {
        campaign_id: pausedCampaignId,
      });
    });

    it("should resume paused campaign", async () => {
      const result = await mcp.callTool("fluentcrm-resume-campaign", {
        campaign_id: pausedCampaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("working");
    });

    it("should reject resume without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-resume-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-resume-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Cancel Campaign", () => {
    let scheduledCampaignId: number;

    beforeEach(async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Cancel Test Campaign"),
        subject: "Cancel Test",
        email_body: "<p>Cancel test</p>",
      });
      scheduledCampaignId = createResult.data.campaign_id;
      testCampaignIds.push(scheduledCampaignId);

      // Schedule it first
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: scheduledCampaignId,
        scheduled_at: scheduledAt,
      });
    });

    it("should cancel scheduled campaign", async () => {
      const result = await mcp.callTool("fluentcrm-cancel-campaign", {
        campaign_id: scheduledCampaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.status).toBe("draft");
    });

    it("should reject cancel without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-cancel-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-cancel-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Test Send Campaign", () => {
    let campaignId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Test Send Campaign"),
        subject: "Test Send Subject",
        email_body: "<p>Test send body</p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should send test email to valid address", async () => {
      const result = await mcp.callTool("fluentcrm-test-send-campaign", {
        campaign_id: campaignId,
        test_email: generateTestEmail(),
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBe(campaignId);
      expect(result.data.test_email).toBeDefined();
    });

    it("should reject invalid email format", async () => {
      const result = await mcp.callTool("fluentcrm-test-send-campaign", {
        campaign_id: campaignId,
        test_email: "invalid-email",
      });

      expect(result.success).toBe(false);
    });

    it("should reject test send without test_email", async () => {
      const result = await mcp.callTool("fluentcrm-test-send-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject test send without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-test-send-campaign", {
        test_email: generateTestEmail(),
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-test-send-campaign", {
        campaign_id: 999999,
        test_email: generateTestEmail(),
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Preview Campaign", () => {
    let campaignId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Preview Test Campaign"),
        subject: "Preview Subject",
        email_body: "<p>Preview body with <strong>HTML</strong></p>",
      });
      campaignId = result.data.campaign_id;
      testCampaignIds.push(campaignId);
    });

    it("should generate campaign preview", async () => {
      const result = await mcp.callTool("fluentcrm-preview-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.campaign_id).toBe(campaignId);
      expect(result.data.subject).toBe("Preview Subject");
      expect(result.data.html).toBeDefined();
      expect(result.data.plain_text).toBeDefined();
    });

    it("should include HTML content in preview", async () => {
      const result = await mcp.callTool("fluentcrm-preview-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.html).toContain("Preview body");
    });

    it("should include plain text version in preview", async () => {
      const result = await mcp.callTool("fluentcrm-preview-campaign", {
        campaign_id: campaignId,
      });

      expect(result.success).toBe(true);
      expect(result.data.plain_text).toBeDefined();
      expect(typeof result.data.plain_text).toBe("string");
    });

    it("should reject preview without campaign_id", async () => {
      const result = await mcp.callTool("fluentcrm-preview-campaign", {});

      expect(result.success).toBe(false);
    });

    it("should handle non-existent campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-preview-campaign", {
        campaign_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Campaign Lifecycle State Transitions", () => {
    it("should enforce draft-only updates", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Lifecycle Campaign"),
        subject: "Lifecycle Test",
        email_body: "<p>Lifecycle test</p>",
      });
      const campaignId = createResult.data.campaign_id;
      testCampaignIds.push(campaignId);

      // Schedule the campaign
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
        scheduled_at: scheduledAt,
      });

      // Try to update a scheduled campaign (should fail)
      const updateResult = await mcp.callTool("fluentcrm-update-campaign", {
        campaign_id: campaignId,
        title: "Should Not Update",
      });

      expect(updateResult.success).toBe(false);
    });

    it("should enforce draft-only scheduling", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Double Schedule Campaign"),
        subject: "Double Schedule Test",
        email_body: "<p>Test</p>",
      });
      const campaignId = createResult.data.campaign_id;
      testCampaignIds.push(campaignId);

      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 7);
      const scheduledAt = futureDate
        .toISOString()
        .slice(0, 19)
        .replace("T", " ");

      // Schedule once
      await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
        scheduled_at: scheduledAt,
      });

      // Try to schedule again (should fail)
      const secondSchedule = await mcp.callTool("fluentcrm-schedule-campaign", {
        campaign_id: campaignId,
        scheduled_at: scheduledAt,
      });

      expect(secondSchedule.success).toBe(false);
    });

    it("should enforce scheduled-only cancellation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Draft Cancel Campaign"),
        subject: "Draft Cancel Test",
        email_body: "<p>Test</p>",
      });
      const campaignId = createResult.data.campaign_id;
      testCampaignIds.push(campaignId);

      // Try to cancel a draft campaign (should fail)
      const cancelResult = await mcp.callTool("fluentcrm-cancel-campaign", {
        campaign_id: campaignId,
      });

      expect(cancelResult.success).toBe(false);
    });

    it("should enforce paused-only resumption", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Draft Resume Campaign"),
        subject: "Draft Resume Test",
        email_body: "<p>Test</p>",
      });
      const campaignId = createResult.data.campaign_id;
      testCampaignIds.push(campaignId);

      // Try to resume a draft campaign (should fail)
      const resumeResult = await mcp.callTool("fluentcrm-resume-campaign", {
        campaign_id: campaignId,
      });

      expect(resumeResult.success).toBe(false);
    });
  });

  describe("Edge Cases and Boundary Conditions", () => {
    it("should handle campaign with empty lists array", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Empty Lists Campaign"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        list_ids: [],
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle campaign with empty tags array", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Empty Tags Campaign"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        tag_ids: [],
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle very long campaign title", async () => {
      const longTitle = generateTestTitle("A".repeat(200));
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: longTitle,
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle very long email subject", async () => {
      const longSubject = "B".repeat(200);
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Long Subject Campaign"),
        subject: longSubject,
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle complex HTML in email body", async () => {
      const complexHTML = `
				<html>
					<body>
						<table><tr><td>Test</td></tr></table>
						<div style="color: red;">Styled content</div>
						<img src="http://example.com/image.jpg" alt="Test" />
					</body>
				</html>
			`;
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Complex HTML Campaign"),
        subject: "Test Subject",
        email_body: complexHTML,
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle special characters in title", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle('Campaign with "quotes" & <symbols>'),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle unicode characters in title", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Campaign with émojis 🎉 and ñ"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle invalid list IDs gracefully", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Invalid List Campaign"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        list_ids: [999999],
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });

    it("should handle invalid tag IDs gracefully", async () => {
      const result = await mcp.callTool("fluentcrm-create-campaign", {
        title: generateTestTitle("Invalid Tag Campaign"),
        subject: "Test Subject",
        email_body: "<p>Test body</p>",
        tag_ids: [999999],
      });

      expect(result.success).toBe(true);
      testCampaignIds.push(result.data.campaign_id);
    });
  });
});
