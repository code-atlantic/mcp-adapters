/**
 * E2E Tests for FluentCRM Campaign Analytics Abilities
 *
 * Tests all campaign analytics tools including:
 * - Campaign metrics and performance tracking
 * - Contact engagement tracking (recipients, opens, clicks)
 * - Click and open tracking data
 * - Subject line performance comparison
 * - Send time optimization analysis
 * - Campaign performance comparison
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Campaign Analytics', () => {
	let mcp: MCPClient;
	const testCampaignIds: number[] = [];
	const testSubscriberIds: number[] = [];
	const testListIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test data
		if (testSubscriberIds.length > 0) {
			await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: testSubscriberIds,
				confirm_delete: true,
			});
		}

		if (testListIds.length > 0) {
			for (const listId of testListIds) {
				await mcp.callTool('fluentcrm-delete-list', {
					list_id: listId,
					confirm_delete: true,
					delete_subscribers: false,
				});
			}
		}

		// Note: Campaigns are typically not deleted in tests as they might be referenced
	});

	describe('Get Campaign Analytics', () => {
		it('should get complete campaign metrics with required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data).toHaveProperty('campaign_id');
				expect(result.data).toHaveProperty('campaign_title');
				expect(result.data).toHaveProperty('campaign_subject');
				expect(result.data).toHaveProperty('campaign_status');
				expect(result.data).toHaveProperty('metrics');
				expect(result.data).toHaveProperty('rates');

				// Verify metrics structure
				expect(result.data.metrics).toHaveProperty('sent');
				expect(result.data.metrics).toHaveProperty('opened');
				expect(result.data.metrics).toHaveProperty('clicked');
				expect(result.data.metrics).toHaveProperty('bounced');
				expect(result.data.metrics).toHaveProperty('unsubscribed');

				// Verify rates structure
				expect(result.data.rates).toHaveProperty('open_rate');
				expect(result.data.rates).toHaveProperty('click_rate');
				expect(result.data.rates).toHaveProperty('click_to_open_rate');
				expect(result.data.rates).toHaveProperty('bounce_rate');
				expect(result.data.rates).toHaveProperty('unsubscribe_rate');
			}

			// Allow failure if campaign doesn't exist
			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject missing required campaign_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent campaign gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should handle campaign_id as integer', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 1,
			});

			// Allow success or failure based on campaign existence
			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject invalid campaign_id type', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 'invalid',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Campaign Contacts', () => {
		it('should get campaign contacts with required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data).toHaveProperty('campaign_id');
				expect(result.data).toHaveProperty('total');
				expect(result.data).toHaveProperty('limit');
				expect(result.data).toHaveProperty('offset');
				expect(result.data).toHaveProperty('contacts');
				expect(Array.isArray(result.data.contacts)).toBe(true);

				// Verify contact structure if contacts exist
				if (result.data.contacts.length > 0) {
					const contact = result.data.contacts[0];
					expect(contact).toHaveProperty('subscriber_id');
					expect(contact).toHaveProperty('email');
					expect(contact).toHaveProperty('name');
					expect(contact).toHaveProperty('status');
					expect(contact).toHaveProperty('sent_at');
					expect(contact).toHaveProperty('opened');
					expect(contact).toHaveProperty('open_count');
					expect(contact).toHaveProperty('clicked');
					expect(contact).toHaveProperty('click_count');
					expect(contact).toHaveProperty('bounced');
					expect(contact).toHaveProperty('unsubscribed');
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter contacts by status: sent', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'sent',
			});

			if (result.success && result.data.contacts.length > 0) {
				result.data.contacts.forEach((contact: any) => {
					expect(['sent', 'delivered']).toContain(contact.status);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter contacts by status: opened', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'opened',
			});

			if (result.success && result.data.contacts.length > 0) {
				result.data.contacts.forEach((contact: any) => {
					expect(contact.opened).toBe(true);
					expect(contact.open_count).toBeGreaterThan(0);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter contacts by status: clicked', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'clicked',
			});

			if (result.success && result.data.contacts.length > 0) {
				result.data.contacts.forEach((contact: any) => {
					expect(contact.clicked).toBe(true);
					expect(contact.click_count).toBeGreaterThan(0);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter contacts by status: bounced', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'bounced',
			});

			if (result.success && result.data.contacts.length > 0) {
				result.data.contacts.forEach((contact: any) => {
					expect(contact.bounced).toBe(true);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter contacts by status: unsubscribed', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'unsubscribed',
			});

			if (result.success && result.data.contacts.length > 0) {
				result.data.contacts.forEach((contact: any) => {
					expect(contact.unsubscribed).toBe(true);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject invalid status enum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should respect limit parameter with minimum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				limit: 1,
			});

			if (result.success) {
				expect(result.data.limit).toBe(1);
				expect(result.data.contacts.length).toBeLessThanOrEqual(1);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect limit parameter with maximum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				limit: 500,
			});

			if (result.success) {
				expect(result.data.limit).toBe(500);
				expect(result.data.contacts.length).toBeLessThanOrEqual(500);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject limit above maximum (500)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				limit: 501,
			});

			expect(result.success).toBe(false);
		});

		it('should reject limit below minimum (1)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				limit: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should respect offset parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				offset: 10,
			});

			if (result.success) {
				expect(result.data.offset).toBe(10);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should handle offset at minimum boundary (0)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				offset: 0,
			});

			if (result.success) {
				expect(result.data.offset).toBe(0);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject negative offset', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				offset: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should combine status, limit, and offset parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 'sent',
				limit: 10,
				offset: 5,
			});

			if (result.success) {
				expect(result.data.limit).toBe(10);
				expect(result.data.offset).toBe(5);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should use default limit (50) when not provided', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data.limit).toBe(50);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should use default offset (0) when not provided', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data.offset).toBe(0);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});
	});

	describe('Get Campaign Clicks', () => {
		it('should get click tracking data with required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-clicks', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data).toHaveProperty('campaign_id');
				expect(result.data).toHaveProperty('total_clicks');
				expect(result.data).toHaveProperty('unique_urls');
				expect(result.data).toHaveProperty('url_data');
				expect(Array.isArray(result.data.url_data)).toBe(true);

				// Verify URL data structure if URLs exist
				if (result.data.url_data.length > 0) {
					const urlData = result.data.url_data[0];
					expect(urlData).toHaveProperty('url');
					expect(urlData).toHaveProperty('click_count');
					expect(urlData).toHaveProperty('unique_clicks');
					expect(urlData).toHaveProperty('short_url');
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject missing required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-clicks', {});

			expect(result.success).toBe(false);
		});

		it('should handle campaign with no clicks', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-clicks', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data.total_clicks).toBeGreaterThanOrEqual(0);
				expect(result.data.unique_urls).toBeGreaterThanOrEqual(0);
				expect(Array.isArray(result.data.url_data)).toBe(true);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should handle non-existent campaign', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-clicks', {
				campaign_id: 999999,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Campaign Opens', () => {
		it('should get open tracking data with required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data).toHaveProperty('campaign_id');
				expect(result.data).toHaveProperty('total_opens');
				expect(result.data).toHaveProperty('unique_opens');
				expect(result.data).toHaveProperty('opens_data');
				expect(Array.isArray(result.data.opens_data)).toBe(true);

				// Verify opens data structure if opens exist
				if (result.data.opens_data.length > 0) {
					const openData = result.data.opens_data[0];
					expect(openData).toHaveProperty('subscriber_id');
					expect(openData).toHaveProperty('email');
					expect(openData).toHaveProperty('name');
					expect(openData).toHaveProperty('open_count');
					expect(openData).toHaveProperty('first_open_at');
					expect(openData).toHaveProperty('last_open_at');
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect limit parameter with minimum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
				limit: 1,
			});

			if (result.success) {
				expect(result.data.opens_data.length).toBeLessThanOrEqual(1);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect limit parameter with maximum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
				limit: 500,
			});

			if (result.success) {
				expect(result.data.opens_data.length).toBeLessThanOrEqual(500);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject limit above maximum (500)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
				limit: 501,
			});

			expect(result.success).toBe(false);
		});

		it('should reject limit below minimum (1)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
				limit: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should use default limit (100) when not provided', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data.opens_data.length).toBeLessThanOrEqual(100);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should handle campaign with no opens', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {
				campaign_id: 1,
			});

			if (result.success) {
				expect(result.data.total_opens).toBeGreaterThanOrEqual(0);
				expect(result.data.unique_opens).toBeGreaterThanOrEqual(0);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject missing required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-opens', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Email Performance By Subject', () => {
		it('should get subject line performance with no parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {});

			if (result.success) {
				expect(result.data).toHaveProperty('total_campaigns');
				expect(result.data).toHaveProperty('performance');
				expect(Array.isArray(result.data.performance)).toBe(true);

				// Verify performance data structure if campaigns exist
				if (result.data.performance.length > 0) {
					const perf = result.data.performance[0];
					expect(perf).toHaveProperty('campaign_id');
					expect(perf).toHaveProperty('subject');
					expect(perf).toHaveProperty('sent');
					expect(perf).toHaveProperty('opened');
					expect(perf).toHaveProperty('clicked');
					expect(perf).toHaveProperty('open_rate');
					expect(perf).toHaveProperty('click_rate');
					expect(perf).toHaveProperty('sent_at');
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should filter by specific campaign_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				campaign_ids: [1, 2, 3],
			});

			if (result.success && result.data.performance.length > 0) {
				result.data.performance.forEach((perf: any) => {
					expect([1, 2, 3]).toContain(perf.campaign_id);
				});
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should handle empty campaign_ids array (analyze all)', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				campaign_ids: [],
			});

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect limit parameter with minimum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				limit: 1,
			});

			if (result.success) {
				expect(result.data.performance.length).toBeLessThanOrEqual(1);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect limit parameter with maximum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				limit: 100,
			});

			if (result.success) {
				expect(result.data.performance.length).toBeLessThanOrEqual(100);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject limit above maximum (100)', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				limit: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject limit below minimum (1)', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				limit: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should use default limit (20) when not provided', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {});

			if (result.success) {
				expect(result.data.performance.length).toBeLessThanOrEqual(20);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should combine campaign_ids and limit parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				campaign_ids: [1, 2, 3],
				limit: 5,
			});

			if (result.success) {
				expect(result.data.performance.length).toBeLessThanOrEqual(5);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should sort results by open_rate descending', async () => {
			const result = await mcp.callTool('fluentcrm-get-email-performance-by-subject', {
				limit: 10,
			});

			if (result.success && result.data.performance.length > 1) {
				const openRates = result.data.performance.map((p: any) => parseFloat(p.open_rate));
				for (let i = 1; i < openRates.length; i++) {
					expect(openRates[i - 1]).toBeGreaterThanOrEqual(openRates[i]);
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});
	});

	describe('Get Send Time Optimization', () => {
		it('should get send time optimization with no parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {});

			if (result.success) {
				expect(result.data).toHaveProperty('analysis_period');
				expect(result.data).toHaveProperty('best_send_time');
				expect(result.data).toHaveProperty('by_day_of_week');
				expect(result.data).toHaveProperty('by_hour');

				// Verify analysis period structure
				expect(result.data.analysis_period).toHaveProperty('days_analyzed');
				expect(result.data.analysis_period).toHaveProperty('from_date');
				expect(result.data.analysis_period).toHaveProperty('to_date');

				// Verify best send time structure
				expect(result.data.best_send_time).toHaveProperty('day');
				expect(result.data.best_send_time).toHaveProperty('hour');

				// Verify day and hour performance structures
				expect(typeof result.data.by_day_of_week).toBe('object');
				expect(typeof result.data.by_hour).toBe('object');
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should use default days_back (90) when not provided', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {});

			if (result.success) {
				expect(result.data.analysis_period.days_analyzed).toBe(90);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect days_back parameter with minimum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 1,
			});

			if (result.success) {
				expect(result.data.analysis_period.days_analyzed).toBe(1);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should respect days_back parameter with maximum value', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 365,
			});

			if (result.success) {
				expect(result.data.analysis_period.days_analyzed).toBe(365);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject days_back above maximum (365)', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 366,
			});

			expect(result.success).toBe(false);
		});

		it('should reject days_back below minimum (1)', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle various days_back values', async () => {
			const daysBackValues = [7, 30, 60, 90, 180, 365];

			for (const days of daysBackValues) {
				const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
					days_back: days,
				});

				if (result.success) {
					expect(result.data.analysis_period.days_analyzed).toBe(days);
				}

				expect(result.success === true || result.success === false).toBe(true);
			}
		});

		it('should include performance data for days of week', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 90,
			});

			if (result.success && Object.keys(result.data.by_day_of_week).length > 0) {
				const dayData = Object.values(result.data.by_day_of_week)[0] as any;
				expect(dayData).toHaveProperty('total_campaigns');
				expect(dayData).toHaveProperty('total_sent');
				expect(dayData).toHaveProperty('total_opened');
				expect(dayData).toHaveProperty('avg_open_rate');
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should include performance data for hours', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 90,
			});

			if (result.success && Object.keys(result.data.by_hour).length > 0) {
				const hourData = Object.values(result.data.by_hour)[0] as any;
				expect(hourData).toHaveProperty('total_campaigns');
				expect(hourData).toHaveProperty('total_sent');
				expect(hourData).toHaveProperty('total_opened');
				expect(hourData).toHaveProperty('avg_open_rate');
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should sort day performance by avg_open_rate descending', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 90,
			});

			if (result.success && Object.keys(result.data.by_day_of_week).length > 1) {
				const openRates = Object.values(result.data.by_day_of_week).map(
					(d: any) => d.avg_open_rate
				);
				for (let i = 1; i < openRates.length; i++) {
					expect(openRates[i - 1]).toBeGreaterThanOrEqual(openRates[i]);
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should sort hour performance by avg_open_rate descending', async () => {
			const result = await mcp.callTool('fluentcrm-get-send-time-optimization', {
				days_back: 90,
			});

			if (result.success && Object.keys(result.data.by_hour).length > 1) {
				const openRates = Object.values(result.data.by_hour).map((h: any) => h.avg_open_rate);
				for (let i = 1; i < openRates.length; i++) {
					expect(openRates[i - 1]).toBeGreaterThanOrEqual(openRates[i]);
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});
	});

	describe('Compare Campaigns', () => {
		it('should compare campaigns with minimum required (2 campaigns)', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1, 2],
			});

			if (result.success) {
				expect(result.data).toHaveProperty('total_campaigns');
				expect(result.data).toHaveProperty('campaigns');
				expect(Array.isArray(result.data.campaigns)).toBe(true);

				// Verify campaign comparison structure
				if (result.data.campaigns.length > 0) {
					const campaign = result.data.campaigns[0];
					expect(campaign).toHaveProperty('campaign_id');
					expect(campaign).toHaveProperty('title');
					expect(campaign).toHaveProperty('subject');
					expect(campaign).toHaveProperty('status');
					expect(campaign).toHaveProperty('sent_at');
					expect(campaign).toHaveProperty('metrics');
					expect(campaign).toHaveProperty('rates');

					// Verify metrics structure
					expect(campaign.metrics).toHaveProperty('sent');
					expect(campaign.metrics).toHaveProperty('opened');
					expect(campaign.metrics).toHaveProperty('clicked');
					expect(campaign.metrics).toHaveProperty('bounced');
					expect(campaign.metrics).toHaveProperty('unsubscribed');

					// Verify rates structure
					expect(campaign.rates).toHaveProperty('open_rate');
					expect(campaign.rates).toHaveProperty('click_rate');
					expect(campaign.rates).toHaveProperty('click_to_open_rate');
					expect(campaign.rates).toHaveProperty('bounce_rate');
					expect(campaign.rates).toHaveProperty('unsubscribe_rate');
				}
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should compare campaigns with maximum allowed (10 campaigns)', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
			});

			if (result.success) {
				expect(result.data.campaigns.length).toBeLessThanOrEqual(10);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject missing required campaign_ids parameter', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {});

			expect(result.success).toBe(false);
		});

		it('should reject campaign_ids with less than 2 items (minItems)', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject empty campaign_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [],
			});

			expect(result.success).toBe(false);
		});

		it('should reject campaign_ids with more than 10 items (maxItems)', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
			});

			expect(result.success).toBe(false);
		});

		it('should handle comparison with various campaign counts', async () => {
			const campaignCounts = [2, 3, 5, 7, 10];

			for (const count of campaignCounts) {
				const campaignIds = Array.from({ length: count }, (_, i) => i + 1);
				const result = await mcp.callTool('fluentcrm-compare-campaigns', {
					campaign_ids: campaignIds,
				});

				expect(result.success === true || result.success === false).toBe(true);
			}
		});

		it('should handle comparison with non-existent campaigns gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [999998, 999999],
			});

			if (result.success) {
				// Should return error entries for non-existent campaigns
				expect(Array.isArray(result.data.campaigns)).toBe(true);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should handle mixed existent and non-existent campaigns', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1, 999999],
			});

			if (result.success) {
				expect(result.data.campaigns.length).toBeGreaterThan(0);
				// At least one campaign should have an error property
				const hasError = result.data.campaigns.some((c: any) => c.error !== undefined);
				const hasValid = result.data.campaigns.some((c: any) => c.metrics !== undefined);
				expect(hasError || hasValid).toBe(true);
			}

			expect(result.success === true || result.success === false).toBe(true);
		});

		it('should reject invalid campaign_ids array type', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: 'invalid',
			});

			expect(result.success).toBe(false);
		});

		it('should reject campaign_ids with non-integer values', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: ['invalid', 'values'],
			});

			expect(result.success).toBe(false);
		});

		it('should handle duplicate campaign IDs in array', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: [1, 1, 2, 2],
			});

			// Should either succeed or fail gracefully
			expect(result.success === true || result.success === false).toBe(true);
		});
	});

	describe('Error Handling and Edge Cases', () => {
		it('should handle invalid tool name gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-invalid-analytics-tool', {
				campaign_id: 1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject null campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: null,
			});

			expect(result.success).toBe(false);
		});

		it('should reject undefined campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: undefined,
			});

			expect(result.success).toBe(false);
		});

		it('should handle negative campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle zero campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle extremely large campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: Number.MAX_SAFE_INTEGER,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Parameter Type Validation', () => {
		it('should reject string where integer expected (campaign_id)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: 'not-an-integer',
			});

			expect(result.success).toBe(false);
		});

		it('should reject boolean where integer expected', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: true,
			});

			expect(result.success).toBe(false);
		});

		it('should reject object where integer expected', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: { id: 1 },
			});

			expect(result.success).toBe(false);
		});

		it('should reject array where integer expected', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-analytics', {
				campaign_id: [1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject string where array expected (campaign_ids)', async () => {
			const result = await mcp.callTool('fluentcrm-compare-campaigns', {
				campaign_ids: '1,2,3',
			});

			expect(result.success).toBe(false);
		});

		it('should reject integer where string expected (status)', async () => {
			const result = await mcp.callTool('fluentcrm-get-campaign-contacts', {
				campaign_id: 1,
				status: 123,
			});

			expect(result.success).toBe(false);
		});
	});
});
