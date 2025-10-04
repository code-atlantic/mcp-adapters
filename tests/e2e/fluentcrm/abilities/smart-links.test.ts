/**
 * E2E Tests for FluentCRM Smart Links Abilities
 *
 * Tests all smart link management tools including CRUD operations,
 * click tracking, conversions, and URL generation.
 *
 * NOTE: Smart Links require FluentCRM Pro with FluentCampaign
 */

import {
  MCPClient,
  TEST_CONFIG,
  generateTestTitle,
} from "../../utils/mcp-client";

describe("FluentCRM Smart Links", () => {
  let mcp: MCPClient;
  const testLinkIds: number[] = [];

  beforeAll(() => {
    mcp = new MCPClient(
      TEST_CONFIG.baseURL,
      TEST_CONFIG.username,
      TEST_CONFIG.password,
    );
  });

  afterAll(async () => {
    // Cleanup test smart links
    if (testLinkIds.length > 0) {
      for (const linkId of testLinkIds) {
        await mcp.callTool("fluentcrm-delete-smart-link", {
          link_id: linkId,
          confirm_delete: true,
        });
      }
    }
  });

  describe("Create Smart Link", () => {
    it("should create smart link with minimal required fields", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com",
        title: generateTestTitle("Test Smart Link"),
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link).toHaveProperty("id");
      expect(result.data.smart_link.url).toBe("https://example.com");
      expect(result.data.smart_link).toHaveProperty("short_url");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with actions - lists", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/with-lists",
        title: generateTestTitle("Link with Lists"),
        actions: {
          lists: [1, 2],
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toHaveProperty("lists");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with actions - tags", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/with-tags",
        title: generateTestTitle("Link with Tags"),
        actions: {
          tags: [1, 2, 3],
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toHaveProperty("tags");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with webhook action", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/with-webhook",
        title: generateTestTitle("Link with Webhook"),
        actions: {
          webhook: "https://webhook.example.com/endpoint",
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toHaveProperty("webhook");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with redirect action", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/with-redirect",
        title: generateTestTitle("Link with Redirect"),
        actions: {
          redirect: true,
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toHaveProperty("redirect");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with redirect set to false", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/no-redirect",
        title: generateTestTitle("Link without Redirect"),
        actions: {
          redirect: false,
        },
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with all action types combined", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/all-actions",
        title: generateTestTitle("Link with All Actions"),
        actions: {
          lists: [1],
          tags: [1, 2],
          webhook: "https://webhook.example.com",
          redirect: true,
        },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toHaveProperty("lists");
      expect(result.data.smart_link.actions).toHaveProperty("tags");
      expect(result.data.smart_link.actions).toHaveProperty("webhook");
      expect(result.data.smart_link.actions).toHaveProperty("redirect");

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link with empty actions object", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/no-actions",
        title: generateTestTitle("Link No Actions"),
        actions: {},
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should create smart link without actions parameter", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/optional-actions",
        title: generateTestTitle("Link Optional Actions"),
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should reject missing required url field", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        title: generateTestTitle("Missing URL"),
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required title field", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid URL format", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "not-a-valid-url",
        title: generateTestTitle("Invalid URL"),
      });

      expect(result.success).toBe(false);
    });

    it("should handle http URLs", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "http://example.com",
        title: generateTestTitle("HTTP Link"),
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should handle URLs with query parameters", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/page?param1=value1&param2=value2",
        title: generateTestTitle("Link with Query Params"),
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });

    it("should handle URLs with fragments", async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/page#section",
        title: generateTestTitle("Link with Fragment"),
      });

      expect(result.success).toBe(true);

      testLinkIds.push(result.data.smart_link.id);
    });
  });

  describe("List Smart Links", () => {
    beforeAll(async () => {
      // Create some test links for listing
      for (let i = 0; i < 3; i++) {
        const result = await mcp.callTool("fluentcrm-create-smart-link", {
          url: `https://example.com/list-test-${i}`,
          title: generateTestTitle(`List Test ${i}`),
        });
        testLinkIds.push(result.data.smart_link.id);
      }
    });

    it("should list smart links with default pagination", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("smart_links");
      expect(result.data).toHaveProperty("total");
      expect(result.data).toHaveProperty("page");
      expect(result.data).toHaveProperty("per_page");
      expect(result.data).toHaveProperty("total_pages");
      expect(Array.isArray(result.data.smart_links)).toBe(true);
    });

    it("should respect page parameter", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        page: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.page).toBe(1);
    });

    it("should respect per_page parameter", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 5,
      });

      expect(result.success).toBe(true);
      expect(result.data.per_page).toBe(5);
      expect(result.data.smart_links.length).toBeLessThanOrEqual(5);
    });

    it("should handle pagination boundaries - minimum per_page", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_links.length).toBeLessThanOrEqual(1);
    });

    it("should handle pagination boundaries - maximum per_page", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 100,
      });

      expect(result.success).toBe(true);
    });

    it("should reject per_page above maximum", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 101,
      });

      expect(result.success).toBe(false);
    });

    it("should reject page below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should search by title", async () => {
      const uniqueTitle = generateTestTitle("Searchable Link");
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/searchable",
        title: uniqueTitle,
      });
      testLinkIds.push(createResult.data.smart_link.id);

      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        search: "Searchable",
      });

      expect(result.success).toBe(true);
      expect(
        result.data.smart_links.some((link: any) =>
          link.title.includes("Searchable"),
        ),
      ).toBe(true);
    });

    it("should search by URL", async () => {
      const uniqueUrl = `https://example.com/unique-${Date.now()}`;
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: uniqueUrl,
        title: generateTestTitle("URL Search Test"),
      });
      testLinkIds.push(createResult.data.smart_link.id);

      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        search: uniqueUrl,
      });

      expect(result.success).toBe(true);
    });

    it("should return empty results for non-matching search", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        search: "NonExistentSearchTerm12345",
      });

      expect(result.success).toBe(true);
    });

    it("should include short_url in response", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 1,
      });

      if (result.data.smart_links.length > 0) {
        expect(result.data.smart_links[0]).toHaveProperty("short_url");
      }
    });

    it("should include created_at and updated_at timestamps", async () => {
      const result = await mcp.callTool("fluentcrm-list-smart-links", {
        per_page: 1,
      });

      if (result.data.smart_links.length > 0) {
        expect(result.data.smart_links[0]).toHaveProperty("created_at");
        expect(result.data.smart_links[0]).toHaveProperty("updated_at");
      }
    });
  });

  describe("Get Smart Link", () => {
    let testLinkId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/get-test",
        title: generateTestTitle("Get Test Link"),
        actions: {
          lists: [1],
          tags: [1, 2],
        },
      });
      testLinkId = result.data.smart_link.id;
      testLinkIds.push(testLinkId);
    });

    it("should get smart link by ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.id).toBe(testLinkId);
      expect(result.data.smart_link).toHaveProperty("title");
      expect(result.data.smart_link).toHaveProperty("url");
      expect(result.data.smart_link).toHaveProperty("short_url");
      expect(result.data.smart_link).toHaveProperty("actions");
      expect(result.data.smart_link).toHaveProperty("stats");
      expect(result.data.smart_link).toHaveProperty("created_at");
      expect(result.data.smart_link).toHaveProperty("updated_at");
    });

    it("should include stats in response", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.stats).toHaveProperty("total_clicks");
      expect(result.data.smart_link.stats).toHaveProperty("unique_clicks");
    });

    it("should include actions in response", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toBeDefined();
    });

    it("should reject missing link_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {});

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: -1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link", {
        link_id: 999999,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Update Smart Link", () => {
    let testLinkId: number;

    beforeEach(async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/update-test",
        title: generateTestTitle("Update Test Link"),
      });
      testLinkId = result.data.smart_link.id;
      testLinkIds.push(testLinkId);
    });

    it("should update smart link title", async () => {
      const newTitle = generateTestTitle("Updated Title");
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        title: newTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.title).toBe(newTitle);
    });

    it("should update smart link URL", async () => {
      const newUrl = "https://example.com/updated-url";
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        url: newUrl,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.url).toBe(newUrl);
    });

    it("should update smart link actions", async () => {
      const newActions = {
        lists: [3, 4],
        tags: [5],
      };
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        actions: newActions,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toEqual(newActions);
    });

    it("should update multiple fields simultaneously", async () => {
      const newTitle = generateTestTitle("Multi Update");
      const newUrl = "https://example.com/multi-update";
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        title: newTitle,
        url: newUrl,
        actions: { lists: [1] },
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.title).toBe(newTitle);
      expect(result.data.smart_link.url).toBe(newUrl);
    });

    it("should update only title leaving other fields unchanged", async () => {
      const newTitle = generateTestTitle("Only Title Update");
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        title: newTitle,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.title).toBe(newTitle);
      expect(result.data.smart_link.url).toBe(
        "https://example.com/update-test",
      );
    });

    it("should update only URL leaving other fields unchanged", async () => {
      const newUrl = "https://example.com/only-url-update";
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        url: newUrl,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.url).toBe(newUrl);
    });

    it("should update only actions leaving other fields unchanged", async () => {
      const newActions = { webhook: "https://webhook.example.com" };
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        actions: newActions,
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link.actions).toEqual(newActions);
    });

    it("should reject missing required link_id", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: 0,
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: -1,
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: 999999,
        title: "Test",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid URL format", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        url: "not-a-valid-url",
      });

      expect(result.success).toBe(false);
    });

    it("should include updated_at timestamp in response", async () => {
      const result = await mcp.callTool("fluentcrm-update-smart-link", {
        link_id: testLinkId,
        title: generateTestTitle("Timestamp Test"),
      });

      expect(result.success).toBe(true);
      expect(result.data.smart_link).toHaveProperty("updated_at");
    });
  });

  describe("Delete Smart Link", () => {
    it("should delete smart link with confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/delete-test",
        title: generateTestTitle("Delete Test Link"),
      });
      const linkId = createResult.data.smart_link.id;

      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: linkId,
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("link_id");
      expect(result.data).toHaveProperty("link_title");
      expect(result.data).toHaveProperty("deleted_at");
    });

    it("should reject delete without confirmation", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/no-confirm",
        title: generateTestTitle("No Confirm Link"),
      });
      const linkId = createResult.data.smart_link.id;
      testLinkIds.push(linkId);

      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: linkId,
        confirm_delete: false,
      });

      expect(result.success).toBe(false);
    });

    it("should reject delete without confirm_delete parameter", async () => {
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/missing-confirm",
        title: generateTestTitle("Missing Confirm Link"),
      });
      const linkId = createResult.data.smart_link.id;
      testLinkIds.push(linkId);

      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: linkId,
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing link_id", async () => {
      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: 0,
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: -1,
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: 999999,
        confirm_delete: true,
      });

      expect(result.success).toBe(false);
    });

    it("should include link_title in delete response", async () => {
      const title = generateTestTitle("Title Check Link");
      const createResult = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/title-check",
        title,
      });
      const linkId = createResult.data.smart_link.id;

      const result = await mcp.callTool("fluentcrm-delete-smart-link", {
        link_id: linkId,
        confirm_delete: true,
      });

      expect(result.success).toBe(true);
      expect(result.data.link_title).toBe(title);
    });
  });

  describe("Get Smart Link Clicks", () => {
    let testLinkId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/clicks-test",
        title: generateTestTitle("Clicks Test Link"),
      });
      testLinkId = result.data.smart_link.id;
      testLinkIds.push(testLinkId);
    });

    it("should get clicks for smart link with minimal parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("link_id");
      expect(result.data).toHaveProperty("link_title");
      expect(result.data).toHaveProperty("clicks");
      expect(result.data).toHaveProperty("total");
      expect(result.data).toHaveProperty("page");
      expect(result.data).toHaveProperty("per_page");
      expect(result.data).toHaveProperty("total_pages");
      expect(result.data).toHaveProperty("date_range");
      expect(Array.isArray(result.data.clicks)).toBe(true);
    });

    it("should respect page parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        page: 2,
      });

      expect(result.success).toBe(true);
      expect(result.data.page).toBe(2);
    });

    it("should respect per_page parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        per_page: 25,
      });

      expect(result.success).toBe(true);
      expect(result.data.per_page).toBe(25);
    });

    it("should handle pagination boundaries - minimum per_page", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        per_page: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should handle pagination boundaries - maximum per_page", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        per_page: 100,
      });

      expect(result.success).toBe(true);
    });

    it("should reject per_page above maximum", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        per_page: 101,
      });

      expect(result.success).toBe(false);
    });

    it("should reject page below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        page: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should filter by start_date", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        start_date: "2025-01-01",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.start_date).toBe("2025-01-01");
    });

    it("should filter by end_date", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        end_date: "2025-12-31",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.end_date).toBe("2025-12-31");
    });

    it("should filter by date range", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        start_date: "2025-01-01",
        end_date: "2025-12-31",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.start_date).toBe("2025-01-01");
      expect(result.data.date_range.end_date).toBe("2025-12-31");
    });

    it("should reject invalid start_date format", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        start_date: "01/01/2025",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid end_date format", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
        end_date: "Dec 31, 2025",
      });

      expect(result.success).toBe(false);
    });

    it("should reject missing required link_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {});

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: -1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should show all_time when no dates provided", async () => {
      const result = await mcp.callTool("fluentcrm-get-smart-link-clicks", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.start_date).toBe("all_time");
      expect(result.data.date_range.end_date).toBe("all_time");
    });
  });

  describe("Get Smart Link Conversions", () => {
    let testLinkId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/conversions-test",
        title: generateTestTitle("Conversions Test Link"),
        actions: {
          lists: [1, 2],
          tags: [1, 2, 3],
          webhook: "https://webhook.example.com",
        },
      });
      testLinkId = result.data.smart_link.id;
      testLinkIds.push(testLinkId);
    });

    it("should get conversions for smart link with minimal parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("conversions");
      expect(result.data.conversions).toHaveProperty("link_id");
      expect(result.data.conversions).toHaveProperty("link_title");
      expect(result.data.conversions).toHaveProperty("total_clicks");
      expect(result.data.conversions).toHaveProperty("unique_clicks");
      expect(result.data.conversions).toHaveProperty("click_through");
      expect(result.data.conversions).toHaveProperty("actions_triggered");
      expect(result.data.conversions).toHaveProperty("date_range");
    });

    it("should include actions_triggered breakdown", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.conversions.actions_triggered).toHaveProperty(
        "lists_added",
      );
      expect(result.data.conversions.actions_triggered).toHaveProperty(
        "tags_applied",
      );
      expect(result.data.conversions.actions_triggered).toHaveProperty(
        "webhooks_fired",
      );
    });

    it("should filter by start_date", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
          start_date: "2025-01-01",
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.conversions.date_range.start_date).toBe("2025-01-01");
    });

    it("should filter by end_date", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
          end_date: "2025-12-31",
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.conversions.date_range.end_date).toBe("2025-12-31");
    });

    it("should filter by date range", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
          start_date: "2025-01-01",
          end_date: "2025-12-31",
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.conversions.date_range.start_date).toBe("2025-01-01");
      expect(result.data.conversions.date_range.end_date).toBe("2025-12-31");
    });

    it("should reject invalid start_date format", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
          start_date: "2025/01/01",
        },
      );

      expect(result.success).toBe(false);
    });

    it("should reject invalid end_date format", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
          end_date: "invalid-date",
        },
      );

      expect(result.success).toBe(false);
    });

    it("should reject missing required link_id", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {},
      );

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: 0,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: -1,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: 999999,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should show all_time when no dates provided", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-smart-link-conversions",
        {
          link_id: testLinkId,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.conversions.date_range.start_date).toBe("all_time");
      expect(result.data.conversions.date_range.end_date).toBe("all_time");
    });
  });

  describe("Generate Short URL", () => {
    let testLinkId: number;

    beforeAll(async () => {
      const result = await mcp.callTool("fluentcrm-create-smart-link", {
        url: "https://example.com/short-url-test",
        title: generateTestTitle("Short URL Test Link"),
      });
      testLinkId = result.data.smart_link.id;
      testLinkIds.push(testLinkId);
    });

    it("should generate short URL for smart link", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("link_id");
      expect(result.data).toHaveProperty("link_title");
      expect(result.data).toHaveProperty("original_url");
      expect(result.data).toHaveProperty("short_url");
    });

    it("should return correct link_id in response", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.link_id).toBe(testLinkId);
    });

    it("should return original_url in response", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.original_url).toBe(
        "https://example.com/short-url-test",
      );
    });

    it("should return short_url containing link_id", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      expect(result.success).toBe(true);
      expect(result.data.short_url).toContain(testLinkId.toString());
    });

    it("should reject missing required link_id", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {});

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (zero)", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid link_id (negative)", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: -1,
      });

      expect(result.success).toBe(false);
    });

    it("should handle non-existent link_id", async () => {
      const result = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should generate consistent short URLs for same link", async () => {
      const result1 = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      const result2 = await mcp.callTool("fluentcrm-generate-short-url", {
        link_id: testLinkId,
      });

      expect(result1.success).toBe(true);
      expect(result2.success).toBe(true);
      expect(result1.data.short_url).toBe(result2.data.short_url);
    });
  });
});
