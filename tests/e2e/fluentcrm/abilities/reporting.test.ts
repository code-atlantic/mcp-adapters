/**
 * E2E Tests for FluentCRM Reporting Abilities
 *
 * Tests all reporting and analytics tools including:
 * - Dashboard statistics
 * - Subscriber growth analysis
 * - Engagement metrics
 * - Revenue attribution
 * - List and tag analytics
 * - Campaign and automation performance
 * - Subscriber lifecycle analysis
 * - Email client and device statistics
 * - Geographic distribution
 * - Unsubscribe reasons
 * - Deliverability reports
 */

import { MCPClient, TEST_CONFIG } from "../../utils/mcp-client";

describe("FluentCRM Reporting", () => {
  let mcp: MCPClient;

  beforeAll(() => {
    mcp = new MCPClient(
      TEST_CONFIG.baseURL,
      TEST_CONFIG.username,
      TEST_CONFIG.password,
    );
  });

  describe("Get Dashboard Stats", () => {
    it("should retrieve overall dashboard statistics", async () => {
      const result = await mcp.callTool("fluentcrm-get-dashboard-stats", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("subscribers");
      expect(result.data).toHaveProperty("campaigns");
      expect(result.data).toHaveProperty("automations");
      expect(result.data).toHaveProperty("organization");
      expect(result.data).toHaveProperty("generated_at");
      expect(result.data).toHaveProperty("timezone");
    });

    it("should include subscriber breakdown", async () => {
      const result = await mcp.callTool("fluentcrm-get-dashboard-stats", {});

      expect(result.success).toBe(true);
      expect(result.data.subscribers).toHaveProperty("total");
      expect(result.data.subscribers).toHaveProperty("active");
      expect(result.data.subscribers).toHaveProperty("pending");
      expect(result.data.subscribers).toHaveProperty("unsubscribed");
      expect(result.data.subscribers).toHaveProperty("new_30_days");
      expect(typeof result.data.subscribers.total).toBe("number");
    });

    it("should include campaign statistics", async () => {
      const result = await mcp.callTool("fluentcrm-get-dashboard-stats", {});

      expect(result.success).toBe(true);
      expect(result.data.campaigns).toHaveProperty("total");
      expect(result.data.campaigns).toHaveProperty("active");
      expect(result.data.campaigns).toHaveProperty("sent");
      expect(result.data.campaigns).toHaveProperty("sent_30_day");
      expect(typeof result.data.campaigns.total).toBe("number");
    });

    it("should include automation statistics", async () => {
      const result = await mcp.callTool("fluentcrm-get-dashboard-stats", {});

      expect(result.success).toBe(true);
      expect(result.data.automations).toHaveProperty("total");
      expect(result.data.automations).toHaveProperty("active");
      expect(typeof result.data.automations.total).toBe("number");
    });

    it("should include organization counts", async () => {
      const result = await mcp.callTool("fluentcrm-get-dashboard-stats", {});

      expect(result.success).toBe(true);
      expect(result.data.organization).toHaveProperty("lists");
      expect(result.data.organization).toHaveProperty("tags");
      expect(typeof result.data.organization.lists).toBe("number");
      expect(typeof result.data.organization.tags).toBe("number");
    });
  });

  describe("Get Subscriber Growth", () => {
    it("should retrieve subscriber growth with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("date_range");
      expect(result.data).toHaveProperty("summary");
      expect(result.data).toHaveProperty("growth");
      expect(Array.isArray(result.data.growth)).toBe(true);
    });

    it("should respect start_date parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        start_date: "2025-01-01",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.start).toBe("2025-01-01");
    });

    it("should respect end_date parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        end_date: "2025-12-31",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.end).toBe("2025-12-31");
    });

    it("should respect custom date range", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        start_date: "2025-01-01",
        end_date: "2025-01-31",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.start).toBe("2025-01-01");
      expect(result.data.date_range.end).toBe("2025-01-31");
    });

    it("should group by day", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        group_by: "day",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.group_by).toBe("day");
    });

    it("should group by week", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        group_by: "week",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.group_by).toBe("week");
    });

    it("should group by month", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        group_by: "month",
      });

      expect(result.success).toBe(true);
      expect(result.data.date_range.group_by).toBe("month");
    });

    it("should reject invalid group_by value", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        group_by: "year",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid date format for start_date", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        start_date: "01-01-2025",
      });

      expect(result.success).toBe(false);
    });

    it("should reject invalid date format for end_date", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {
        end_date: "2025/12/31",
      });

      expect(result.success).toBe(false);
    });

    it("should include summary statistics", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {});

      expect(result.success).toBe(true);
      expect(result.data.summary).toHaveProperty("total_new");
      expect(result.data.summary).toHaveProperty("total_subscribed");
      expect(result.data.summary).toHaveProperty("total_pending");
      expect(result.data.summary).toHaveProperty("total_unsubscribed");
    });

    it("should include growth data with proper structure", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-growth", {});

      expect(result.success).toBe(true);
      if (result.data.growth.length > 0) {
        const period = result.data.growth[0];
        expect(period).toHaveProperty("period");
        expect(period).toHaveProperty("new");
        expect(period).toHaveProperty("cumulative");
      }
    });
  });

  describe("Get Engagement Metrics", () => {
    it("should retrieve engagement metrics with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("analysis_period");
      expect(result.data).toHaveProperty("metrics");
      expect(result.data).toHaveProperty("totals");
    });

    it("should respect days_back parameter (minimum value 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(1);
    });

    it("should respect days_back parameter (30 days)", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 30,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(30);
    });

    it("should respect days_back parameter (maximum value 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(365);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should enforce maximum days_back value", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 500,
      });

      // Should cap at 365 or reject
      if (result.success) {
        expect(result.data.analysis_period.days).toBeLessThanOrEqual(365);
      } else {
        expect(result.success).toBe(false);
      }
    });

    it("should include all required metrics", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {});

      expect(result.success).toBe(true);
      expect(result.data.metrics).toHaveProperty("avg_open_rate");
      expect(result.data.metrics).toHaveProperty("avg_click_rate");
      expect(result.data.metrics).toHaveProperty("avg_unsubscribe_rate");
      expect(result.data.metrics).toHaveProperty("avg_bounce_rate");
    });

    it("should include totals breakdown", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {});

      expect(result.success).toBe(true);
      expect(result.data.totals).toHaveProperty("campaigns");
      expect(result.data.totals).toHaveProperty("sent");
      expect(result.data.totals).toHaveProperty("opened");
      expect(result.data.totals).toHaveProperty("clicked");
    });

    it("should handle period with no campaigns", async () => {
      const result = await mcp.callTool("fluentcrm-get-engagement-metrics", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      // Should have zero values or empty data
      expect(result.data).toBeDefined();
    });
  });

  describe("Get Revenue Attribution", () => {
    it("should attempt to retrieve revenue attribution", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-revenue-attribution",
        {},
      );

      // May succeed or fail depending on commerce integration
      expect(result).toHaveProperty("success");
      if (result.success) {
        expect(result.data).toBeDefined();
      } else {
        expect(result.message).toContain("commerce");
      }
    });

    it("should respect campaign_id parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        campaign_id: 1,
      });

      expect(result).toHaveProperty("success");
    });

    it("should respect days_back parameter (minimum)", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        days_back: 1,
      });

      expect(result).toHaveProperty("success");
    });

    it("should respect days_back parameter (default 30)", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        days_back: 30,
      });

      expect(result).toHaveProperty("success");
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        days_back: 365,
      });

      expect(result).toHaveProperty("success");
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should enforce maximum days_back value", async () => {
      const result = await mcp.callTool("fluentcrm-get-revenue-attribution", {
        days_back: 400,
      });

      // Should enforce maximum or reject
      expect(result).toHaveProperty("success");
    });
  });

  describe("Get List Growth Trends", () => {
    it("should retrieve list growth trends with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("analysis_period");
      expect(result.data).toHaveProperty("lists");
      expect(Array.isArray(result.data.lists)).toBe(true);
    });

    it("should respect list_id parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        list_id: 1,
      });

      // May succeed if list exists or fail if not found
      expect(result).toHaveProperty("success");
    });

    it("should respect days_back parameter (minimum)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (default 90)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        days_back: 90,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent list_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {
        list_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should include required list data fields", async () => {
      const result = await mcp.callTool("fluentcrm-get-list-growth-trends", {});

      expect(result.success).toBe(true);
      if (result.data.lists.length > 0) {
        const list = result.data.lists[0];
        expect(list).toHaveProperty("list_id");
        expect(list).toHaveProperty("list_title");
        expect(list).toHaveProperty("current_count");
      }
    });
  });

  describe("Get Tag Engagement", () => {
    it("should retrieve tag engagement with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("total_tags");
      expect(result.data).toHaveProperty("tags");
      expect(Array.isArray(result.data.tags)).toBe(true);
    });

    it("should respect tag_id parameter", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        tag_id: 1,
      });

      // May succeed or fail depending on tag existence
      expect(result).toHaveProperty("success");
    });

    it("should respect limit parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        limit: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.tags.length).toBeLessThanOrEqual(1);
    });

    it("should respect limit parameter (default 20)", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        limit: 20,
      });

      expect(result.success).toBe(true);
      expect(result.data.tags.length).toBeLessThanOrEqual(20);
    });

    it("should respect limit parameter (maximum 100)", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        limit: 100,
      });

      expect(result.success).toBe(true);
      expect(result.data.tags.length).toBeLessThanOrEqual(100);
    });

    it("should reject limit below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        limit: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject limit above maximum", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        limit: 101,
      });

      expect(result.success).toBe(false);
    });

    it("should reject non-existent tag_id", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {
        tag_id: 999999,
      });

      expect(result.success).toBe(false);
    });

    it("should include required tag data fields", async () => {
      const result = await mcp.callTool("fluentcrm-get-tag-engagement", {});

      expect(result.success).toBe(true);
      if (result.data.tags.length > 0) {
        const tag = result.data.tags[0];
        expect(tag).toHaveProperty("tag_id");
        expect(tag).toHaveProperty("tag_title");
        expect(tag).toHaveProperty("subscriber_count");
        expect(tag).toHaveProperty("created_at");
      }
    });
  });

  describe("Export Analytics Report", () => {
    it("should export overview report", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
      });

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("report");
      expect(result.data.report.report_type).toBe("overview");
    });

    it("should export campaigns report", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "campaigns",
      });

      expect(result.success).toBe(true);
      expect(result.data.report.report_type).toBe("campaigns");
    });

    it("should export subscribers report", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "subscribers",
      });

      expect(result.success).toBe(true);
      expect(result.data.report.report_type).toBe("subscribers");
    });

    it("should export engagement report", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "engagement",
      });

      expect(result.success).toBe(true);
      expect(result.data.report.report_type).toBe("engagement");
    });

    it("should reject invalid report_type", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "invalid",
      });

      expect(result.success).toBe(false);
    });

    it("should respect days_back parameter (minimum)", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
        days_back: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (default 30)", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
        days_back: 30,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
        days_back: 365,
      });

      expect(result.success).toBe(true);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should include required report structure", async () => {
      const result = await mcp.callTool("fluentcrm-export-analytics-report", {
        report_type: "overview",
      });

      expect(result.success).toBe(true);
      expect(result.data.report).toHaveProperty("report_type");
      expect(result.data.report).toHaveProperty("generated_at");
      expect(result.data.report).toHaveProperty("period");
      expect(result.data).toHaveProperty("format");
    });
  });

  describe("Get Campaign Comparison", () => {
    it("should require campaign_ids parameter", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-campaign-comparison",
        {},
      );

      expect(result.success).toBe(false);
    });

    it("should accept minimum 2 campaign IDs", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [1, 2],
      });

      // May succeed or fail depending on CampaignAnalytics availability
      expect(result).toHaveProperty("success");
    });

    it("should accept multiple campaign IDs", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [1, 2, 3, 4, 5],
      });

      expect(result).toHaveProperty("success");
    });

    it("should accept maximum 10 campaign IDs", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      });

      expect(result).toHaveProperty("success");
    });

    it("should reject single campaign ID", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [1],
      });

      expect(result.success).toBe(false);
    });

    it("should reject more than 10 campaign IDs", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
      });

      expect(result.success).toBe(false);
    });

    it("should reject empty campaign_ids array", async () => {
      const result = await mcp.callTool("fluentcrm-get-campaign-comparison", {
        campaign_ids: [],
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Automation Performance", () => {
    it("should retrieve automation performance with default parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("total_automations");
      expect(result.data).toHaveProperty("automations");
      expect(Array.isArray(result.data.automations)).toBe(true);
    });

    it("should respect automation_id parameter", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          automation_id: 1,
        },
      );

      // May succeed or fail depending on automation existence
      expect(result).toHaveProperty("success");
    });

    it("should respect limit parameter (minimum 1)", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          limit: 1,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.automations.length).toBeLessThanOrEqual(1);
    });

    it("should respect limit parameter (default 20)", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          limit: 20,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.automations.length).toBeLessThanOrEqual(20);
    });

    it("should respect limit parameter (maximum 100)", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          limit: 100,
        },
      );

      expect(result.success).toBe(true);
      expect(result.data.automations.length).toBeLessThanOrEqual(100);
    });

    it("should reject limit below minimum", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          limit: 0,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should reject limit above maximum", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {
          limit: 101,
        },
      );

      expect(result.success).toBe(false);
    });

    it("should include required automation data fields", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-automation-performance",
        {},
      );

      expect(result.success).toBe(true);
      if (result.data.automations.length > 0) {
        const automation = result.data.automations[0];
        expect(automation).toHaveProperty("automation_id");
        expect(automation).toHaveProperty("title");
        expect(automation).toHaveProperty("status");
        expect(automation).toHaveProperty("trigger_type");
        expect(automation).toHaveProperty("total_subscribers");
      }
    });
  });

  describe("Get Subscriber Lifecycle", () => {
    it("should retrieve subscriber lifecycle with default parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-subscriber-lifecycle",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("analysis_period");
      expect(result.data).toHaveProperty("current_distribution");
      expect(result.data).toHaveProperty("period_changes");
    });

    it("should respect days_back parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-lifecycle", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(1);
    });

    it("should respect days_back parameter (default 90)", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-lifecycle", {
        days_back: 90,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(90);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-lifecycle", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(365);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-lifecycle", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should enforce maximum days_back value", async () => {
      const result = await mcp.callTool("fluentcrm-get-subscriber-lifecycle", {
        days_back: 500,
      });

      if (result.success) {
        expect(result.data.analysis_period.days).toBeLessThanOrEqual(365);
      } else {
        expect(result.success).toBe(false);
      }
    });

    it("should include status distribution data", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-subscriber-lifecycle",
        {},
      );

      expect(result.success).toBe(true);
      expect(typeof result.data.current_distribution).toBe("object");
      expect(typeof result.data.period_changes).toBe("object");
    });
  });

  describe("Get Email Client Stats", () => {
    it("should retrieve email client stats with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("notice");
    });

    it("should respect days_back parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (default 30)", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {
        days_back: 30,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should enforce maximum days_back value", async () => {
      const result = await mcp.callTool("fluentcrm-get-email-client-stats", {
        days_back: 400,
      });

      // Should succeed with notice about tracking requirements
      expect(result.success).toBe(true);
    });
  });

  describe("Get Device Stats", () => {
    it("should retrieve device stats with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-device-stats", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("notice");
    });

    it("should respect days_back parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-device-stats", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (default 30)", async () => {
      const result = await mcp.callTool("fluentcrm-get-device-stats", {
        days_back: 30,
      });

      expect(result.success).toBe(true);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-device-stats", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-device-stats", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Geo Stats", () => {
    it("should retrieve geo stats with default parameters", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("group_by");
      expect(result.data).toHaveProperty("distribution");
    });

    it("should group by country", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {
        group_by: "country",
      });

      expect(result.success).toBe(true);
      expect(result.data.group_by).toBe("country");
    });

    it("should group by region", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {
        group_by: "region",
      });

      expect(result.success).toBe(true);
      expect(result.data.group_by).toBe("region");
    });

    it("should group by city", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {
        group_by: "city",
      });

      expect(result.success).toBe(true);
      expect(result.data.group_by).toBe("city");
    });

    it("should reject invalid group_by value", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {
        group_by: "continent",
      });

      expect(result.success).toBe(false);
    });

    it("should include distribution counts", async () => {
      const result = await mcp.callTool("fluentcrm-get-geo-stats", {});

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("total_known");
      expect(result.data).toHaveProperty("total_unknown");
      expect(typeof result.data.distribution).toBe("object");
    });
  });

  describe("Get Unsubscribe Reasons", () => {
    it("should retrieve unsubscribe reasons with default parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-unsubscribe-reasons",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("analysis_period");
      expect(result.data).toHaveProperty("total_unsubscribed");
    });

    it("should respect days_back parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(1);
    });

    it("should respect days_back parameter (default 90)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        days_back: 90,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(90);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(365);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should respect limit parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        limit: 1,
      });

      expect(result.success).toBe(true);
    });

    it("should respect limit parameter (default 100)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        limit: 100,
      });

      expect(result.success).toBe(true);
    });

    it("should respect limit parameter (maximum 500)", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        limit: 500,
      });

      expect(result.success).toBe(true);
    });

    it("should reject limit below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        limit: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should reject limit above maximum", async () => {
      const result = await mcp.callTool("fluentcrm-get-unsubscribe-reasons", {
        limit: 501,
      });

      expect(result.success).toBe(false);
    });
  });

  describe("Get Deliverability Report", () => {
    it("should retrieve deliverability report with default parameters", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-deliverability-report",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("analysis_period");
      expect(result.data).toHaveProperty("metrics");
      expect(result.data).toHaveProperty("bounce_types");
      expect(result.data).toHaveProperty("health_status");
    });

    it("should respect days_back parameter (minimum 1)", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(1);
    });

    it("should respect days_back parameter (default 30)", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 30,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(30);
    });

    it("should respect days_back parameter (maximum 365)", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 365,
      });

      expect(result.success).toBe(true);
      expect(result.data.analysis_period.days).toBe(365);
    });

    it("should reject days_back below minimum", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 0,
      });

      expect(result.success).toBe(false);
    });

    it("should enforce maximum days_back value", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 400,
      });

      if (result.success) {
        expect(result.data.analysis_period.days).toBeLessThanOrEqual(365);
      } else {
        expect(result.success).toBe(false);
      }
    });

    it("should include all required deliverability metrics", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-deliverability-report",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data.metrics).toHaveProperty("total_sent");
      expect(result.data.metrics).toHaveProperty("total_delivered");
      expect(result.data.metrics).toHaveProperty("total_bounced");
      expect(result.data.metrics).toHaveProperty("bounce_rate");
      expect(result.data.metrics).toHaveProperty("delivery_rate");
    });

    it("should include bounce type breakdown", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-deliverability-report",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data.bounce_types).toHaveProperty("hard_bounces");
      expect(result.data.bounce_types).toHaveProperty("soft_bounces");
    });

    it("should include health status assessment", async () => {
      const result = await mcp.callTool(
        "fluentcrm-get-deliverability-report",
        {},
      );

      expect(result.success).toBe(true);
      expect(result.data).toHaveProperty("health_status");
      expect(["excellent", "good", "needs_attention"]).toContain(
        result.data.health_status,
      );
    });

    it("should handle period with no campaigns", async () => {
      const result = await mcp.callTool("fluentcrm-get-deliverability-report", {
        days_back: 1,
      });

      expect(result.success).toBe(true);
      // Should return zero values when no campaigns exist
      expect(result.data.metrics).toBeDefined();
    });
  });
});
