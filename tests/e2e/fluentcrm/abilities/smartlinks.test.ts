/**
 * E2E Tests for FluentCRM SmartLinks Abilities
 *
 * Tests all smart link management tools including CRUD operations,
 * click tracking, conversion analytics, and short URL generation.
 *
 * NOTE: SmartLinks requires FluentCRM Pro with FluentCampaign.
 * Tests will be skipped if Pro is not available.
 */

import { MCPClient, TEST_CONFIG, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM SmartLinks', () => {
	let mcp: MCPClient;
	const testSmartLinkIds: number[] = [];
	let isProAvailable = false;

	beforeAll(async () => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);

		// Check if SmartLinks (Pro feature) is available
		try {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {});
			isProAvailable = result.success && result.data && !result.message?.includes('FluentCampaign Pro required');
		} catch (error) {
			isProAvailable = false;
		}

		if (!isProAvailable) {
			console.log('⚠️  FluentCRM Pro (FluentCampaign) not available - skipping SmartLinks tests');
		}
	});

	afterAll(async () => {
		if (!isProAvailable) return;

		// Cleanup test smart links
		for (const linkId of testSmartLinkIds) {
			try {
				await mcp.callTool('fluentcrm-delete-smart-link', {
					link_id: linkId,
					confirm_delete: true,
				});
			} catch (error) {
				// Link may already be deleted, ignore errors
			}
		}
	});

	// Skip all tests if Pro is not available
	const describeIfPro = isProAvailable ? describe : describe.skip;

	describeIfPro('Create Smart Link', () => {
		it('should create smart link with minimal required fields', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/landing-page',
				title: generateTestTitle('Minimal Smart Link'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link).toBeDefined();
			expect(result.data.smart_link.id).toBeDefined();
			expect(result.data.smart_link.url).toBe('https://example.com/landing-page');
			expect(result.data.smart_link.title).toContain('Minimal Smart Link');
			expect(result.data.smart_link.short_url).toBeDefined();
			expect(result.data.smart_link.short_url).toContain('/fluent-crm/v2/s/');

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should create smart link with actions configuration', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/product-page',
				title: generateTestTitle('Smart Link with Actions'),
				actions: {
					lists: [1, 2],
					tags: [3, 4],
					redirect: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.id).toBeDefined();
			expect(result.data.smart_link.actions).toBeDefined();
			expect(result.data.smart_link.actions.lists).toEqual([1, 2]);
			expect(result.data.smart_link.actions.tags).toEqual([3, 4]);
			expect(result.data.smart_link.actions.redirect).toBe(true);

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should create smart link with webhook action', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/webhook-trigger',
				title: generateTestTitle('Smart Link with Webhook'),
				actions: {
					webhook: 'https://api.example.com/track',
					redirect: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.webhook).toBe('https://api.example.com/track');
			expect(result.data.smart_link.actions.redirect).toBe(false);

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should create smart link with empty actions object', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/simple',
				title: generateTestTitle('Simple Tracking Link'),
				actions: {},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.id).toBeDefined();

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should create smart link without actions parameter', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/no-actions',
				title: generateTestTitle('No Actions Link'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.id).toBeDefined();

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should reject missing required url field', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				title: 'Missing URL',
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required title field', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com',
			});

			expect(result.success).toBe(false);
		});

		it('should handle very long URL (2000+ chars)', async () => {
			const longUrl = 'https://example.com/path?' + 'param=value&'.repeat(200);
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: longUrl,
				title: generateTestTitle('Long URL Link'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle URL with query parameters', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/page?utm_source=email&utm_campaign=test',
				title: generateTestTitle('URL with Query Params'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.url).toContain('utm_source=email');

			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle URL with fragment', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/page#section-2',
				title: generateTestTitle('URL with Fragment'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle special characters in title', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/special',
				title: generateTestTitle('Link with "quotes" & <symbols>'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle unicode characters in title', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/unicode',
				title: generateTestTitle('Link with émojis 🎉 and ñ'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle very long title', async () => {
			const longTitle = generateTestTitle('A'.repeat(200));
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/long-title',
				title: longTitle,
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});
	});

	describeIfPro('List Smart Links', () => {
		beforeAll(async () => {
			// Create test smart links with different properties
			const link1 = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/list-test-1',
				title: generateTestTitle('List Test Link 1'),
			});
			testSmartLinkIds.push(link1.data.smart_link.id);

			const link2 = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/list-test-2',
				title: generateTestTitle('List Test Link 2'),
			});
			testSmartLinkIds.push(link2.data.smart_link.id);
		});

		it('should list smart links with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('smart_links');
			expect(result.data).toHaveProperty('total');
			expect(Array.isArray(result.data.smart_links)).toBe(true);
			expect(result.data.total).toBeGreaterThan(0);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				page: 1,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_links.length).toBeLessThanOrEqual(5);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(5);
		});

		it('should handle search by title', async () => {
			const searchTerm = 'List Test Link 1';
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				search: searchTerm,
			});

			expect(result.success).toBe(true);
			if (result.data.smart_links.length > 0) {
				const found = result.data.smart_links.some((link: any) => link.title.includes(searchTerm));
				expect(found).toBe(true);
			}
		});

		it('should handle search by URL', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				search: 'example.com',
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_links.length).toBeGreaterThan(0);
		});

		it('should handle pagination boundaries - minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				page: 1,
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_links.length).toBeLessThanOrEqual(1);
		});

		it('should handle pagination boundaries - maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				page: 1,
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.per_page).toBe(100);
		});

		it('should include short_url in list results', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			if (result.data.smart_links.length > 0) {
				expect(result.data.smart_links[0]).toHaveProperty('short_url');
				expect(result.data.smart_links[0].short_url).toContain('/fluent-crm/v2/s/');
			}
		});

		it('should include created_at and updated_at timestamps', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			if (result.data.smart_links.length > 0) {
				expect(result.data.smart_links[0]).toHaveProperty('created_at');
				expect(result.data.smart_links[0]).toHaveProperty('updated_at');
			}
		});

		it('should handle empty search results', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				search: 'nonexistent-link-xyz-12345',
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_links).toEqual([]);
			expect(result.data.total).toBe(0);
		});

		it('should calculate total_pages correctly', async () => {
			const result = await mcp.callTool('fluentcrm-list-smart-links', {
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.total_pages).toBe(Math.ceil(result.data.total / 5));
		});
	});

	describeIfPro('Get Smart Link', () => {
		let smartLinkId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/get-test',
				title: generateTestTitle('Get Test Smart Link'),
				actions: {
					lists: [1],
					tags: [2],
				},
			});
			smartLinkId = result.data.smart_link.id;
			testSmartLinkIds.push(smartLinkId);
		});

		it('should get smart link by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.id).toBe(smartLinkId);
			expect(result.data.smart_link.title).toBeDefined();
			expect(result.data.smart_link.url).toBe('https://example.com/get-test');
			expect(result.data.smart_link.short_url).toBeDefined();
		});

		it('should include actions configuration', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions).toBeDefined();
			expect(result.data.smart_link.actions.lists).toEqual([1]);
			expect(result.data.smart_link.actions.tags).toEqual([2]);
		});

		it('should include click statistics', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.stats).toBeDefined();
			expect(result.data.smart_link.stats).toHaveProperty('total_clicks');
			expect(result.data.smart_link.stats).toHaveProperty('unique_clicks');
		});

		it('should include timestamps', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link).toHaveProperty('created_at');
			expect(result.data.smart_link).toHaveProperty('updated_at');
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required link_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {});

			expect(result.success).toBe(false);
		});

		it('should handle zero link_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle negative link_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link', {
				link_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	describeIfPro('Update Smart Link', () => {
		let smartLinkId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/update-test',
				title: generateTestTitle('Update Test Smart Link'),
				actions: {
					lists: [1],
				},
			});
			smartLinkId = result.data.smart_link.id;
			testSmartLinkIds.push(smartLinkId);
		});

		it('should update smart link title', async () => {
			const newTitle = generateTestTitle('Updated Title');
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				title: newTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.title).toBe(newTitle);
		});

		it('should update smart link URL', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				url: 'https://example.com/new-destination',
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.url).toBe('https://example.com/new-destination');
		});

		it('should update smart link actions', async () => {
			const newActions = {
				lists: [2, 3],
				tags: [4, 5],
				webhook: 'https://api.example.com/webhook',
			};

			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				actions: newActions,
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.lists).toEqual([2, 3]);
			expect(result.data.smart_link.actions.tags).toEqual([4, 5]);
			expect(result.data.smart_link.actions.webhook).toBe('https://api.example.com/webhook');
		});

		it('should update multiple fields at once', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				title: generateTestTitle('Multi Update'),
				url: 'https://example.com/multi-update',
				actions: {
					lists: [10],
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.title).toContain('Multi Update');
			expect(result.data.smart_link.url).toBe('https://example.com/multi-update');
			expect(result.data.smart_link.actions.lists).toEqual([10]);
		});

		it('should update only title when other fields omitted', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				title: generateTestTitle('Title Only Update'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.title).toContain('Title Only Update');
		});

		it('should reject invalid URL during update', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				url: 'not-a-valid-url',
			});

			expect(result.success).toBe(false);
		});

		it('should reject update without link_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				title: 'Updated Title',
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: 999999,
				title: 'Updated Title',
			});

			expect(result.success).toBe(false);
		});

		it('should include short_url in update response', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				title: generateTestTitle('Short URL Test'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.short_url).toBeDefined();
			expect(result.data.smart_link.short_url).toContain(`/fluent-crm/v2/s/${smartLinkId}`);
		});

		it('should update actions to empty object', async () => {
			const result = await mcp.callTool('fluentcrm-update-smart-link', {
				link_id: smartLinkId,
				actions: {},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions).toEqual({});
		});
	});

	describeIfPro('Delete Smart Link', () => {
		it('should delete smart link with confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/delete-test',
				title: generateTestTitle('Delete Test Smart Link'),
			});
			const linkId = createResult.data.smart_link.id;

			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: linkId,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.link_id).toBe(linkId);
			expect(result.data.link_title).toBeDefined();
			expect(result.data.deleted_at).toBeDefined();
		});

		it('should reject delete without confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/no-delete',
				title: generateTestTitle('No Delete Smart Link'),
			});
			const linkId = createResult.data.smart_link.id;
			testSmartLinkIds.push(linkId);

			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: linkId,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete without confirm_delete parameter', async () => {
			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: 1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: 999999,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete with zero link_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: 0,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete with negative link_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-smart-link', {
				link_id: -1,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});
	});

	describeIfPro('Get Smart Link Clicks', () => {
		let smartLinkId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/click-test',
				title: generateTestTitle('Click Test Smart Link'),
			});
			smartLinkId = result.data.smart_link.id;
			testSmartLinkIds.push(smartLinkId);
		});

		it('should get click data for smart link', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.link_id).toBe(smartLinkId);
			expect(result.data.link_title).toBeDefined();
			expect(result.data.clicks).toBeDefined();
			expect(Array.isArray(result.data.clicks)).toBe(true);
			expect(result.data.total).toBeDefined();
		});

		it('should include pagination in click data', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				page: 1,
				per_page: 20,
			});

			expect(result.success).toBe(true);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(20);
			expect(result.data.total_pages).toBeDefined();
		});

		it('should filter clicks by start_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				start_date: '2025-01-01',
			});

			expect(result.success).toBe(true);
			expect(result.data.date_range.start_date).toBe('2025-01-01');
		});

		it('should filter clicks by end_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.date_range.end_date).toBe('2025-12-31');
		});

		it('should filter clicks by date range', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				start_date: '2025-01-01',
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.date_range.start_date).toBe('2025-01-01');
			expect(result.data.date_range.end_date).toBe('2025-12-31');
		});

		it('should handle pagination boundaries - minimum', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				page: 1,
				per_page: 1,
			});

			expect(result.success).toBe(true);
		});

		it('should handle pagination boundaries - maximum', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				page: 1,
				per_page: 100,
			});

			expect(result.success).toBe(true);
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required link_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {});

			expect(result.success).toBe(false);
		});

		it('should reject invalid date format for start_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				start_date: 'invalid-date',
			});

			// Schema validation should reject this
			expect(result.success).toBe(false);
		});

		it('should reject invalid date format for end_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-clicks', {
				link_id: smartLinkId,
				end_date: '2025/01/01',
			});

			// Schema validation should reject this
			expect(result.success).toBe(false);
		});
	});

	describeIfPro('Get Smart Link Conversions', () => {
		let smartLinkId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/conversion-test',
				title: generateTestTitle('Conversion Test Smart Link'),
				actions: {
					lists: [1, 2],
					tags: [3],
					webhook: 'https://api.example.com/track',
				},
			});
			smartLinkId = result.data.smart_link.id;
			testSmartLinkIds.push(smartLinkId);
		});

		it('should get conversion metrics for smart link', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions).toBeDefined();
			expect(result.data.conversions.link_id).toBe(smartLinkId);
			expect(result.data.conversions.link_title).toBeDefined();
		});

		it('should include click metrics in conversions', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions).toHaveProperty('total_clicks');
			expect(result.data.conversions).toHaveProperty('unique_clicks');
			expect(result.data.conversions).toHaveProperty('click_through');
		});

		it('should include actions_triggered metrics', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions.actions_triggered).toBeDefined();
			expect(result.data.conversions.actions_triggered).toHaveProperty('lists_added');
			expect(result.data.conversions.actions_triggered).toHaveProperty('tags_applied');
			expect(result.data.conversions.actions_triggered).toHaveProperty('webhooks_fired');
		});

		it('should filter conversions by start_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
				start_date: '2025-01-01',
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions.date_range.start_date).toBe('2025-01-01');
		});

		it('should filter conversions by end_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions.date_range.end_date).toBe('2025-12-31');
		});

		it('should filter conversions by date range', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
				start_date: '2025-01-01',
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions.date_range.start_date).toBe('2025-01-01');
			expect(result.data.conversions.date_range.end_date).toBe('2025-12-31');
		});

		it('should handle smart link with no actions', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/no-actions-conversion',
				title: generateTestTitle('No Actions Conversion Test'),
			});
			testSmartLinkIds.push(createResult.data.smart_link.id);

			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: createResult.data.smart_link.id,
			});

			expect(result.success).toBe(true);
			expect(result.data.conversions.actions_triggered.lists_added).toBe(0);
			expect(result.data.conversions.actions_triggered.tags_applied).toBe(0);
			expect(result.data.conversions.actions_triggered.webhooks_fired).toBe(0);
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required link_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {});

			expect(result.success).toBe(false);
		});

		it('should reject invalid date format for start_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
				start_date: 'invalid-date',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid date format for end_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-smart-link-conversions', {
				link_id: smartLinkId,
				end_date: '2025/01/01',
			});

			expect(result.success).toBe(false);
		});
	});

	describeIfPro('Generate Short URL', () => {
		let smartLinkId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/short-url-test',
				title: generateTestTitle('Short URL Test Smart Link'),
			});
			smartLinkId = result.data.smart_link.id;
			testSmartLinkIds.push(smartLinkId);
		});

		it('should generate short URL for smart link', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.link_id).toBe(smartLinkId);
			expect(result.data.link_title).toBeDefined();
			expect(result.data.original_url).toBe('https://example.com/short-url-test');
			expect(result.data.short_url).toBeDefined();
		});

		it('should include /fluent-crm/v2/s/ in short URL', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.short_url).toContain('/fluent-crm/v2/s/');
		});

		it('should include smart link ID in short URL path', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: smartLinkId,
			});

			expect(result.success).toBe(true);
			expect(result.data.short_url).toContain(`/fluent-crm/v2/s/${smartLinkId}`);
		});

		it('should generate consistent short URL for same link', async () => {
			const result1 = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: smartLinkId,
			});

			const result2 = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: smartLinkId,
			});

			expect(result1.success).toBe(true);
			expect(result2.success).toBe(true);
			expect(result1.data.short_url).toBe(result2.data.short_url);
		});

		it('should handle non-existent smart link ID', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required link_id', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {});

			expect(result.success).toBe(false);
		});

		it('should reject zero link_id', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative link_id', async () => {
			const result = await mcp.callTool('fluentcrm-generate-short-url', {
				link_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	describeIfPro('Edge Cases and Boundary Conditions', () => {
		it('should handle smart link with only lists in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/lists-only',
				title: generateTestTitle('Lists Only Actions'),
				actions: {
					lists: [1, 2, 3],
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.lists).toEqual([1, 2, 3]);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle smart link with only tags in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/tags-only',
				title: generateTestTitle('Tags Only Actions'),
				actions: {
					tags: [4, 5, 6],
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.tags).toEqual([4, 5, 6]);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle smart link with only webhook in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/webhook-only',
				title: generateTestTitle('Webhook Only Actions'),
				actions: {
					webhook: 'https://api.example.com/webhook',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.webhook).toBe('https://api.example.com/webhook');
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle international domain names', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://münchen.de/page',
				title: generateTestTitle('International Domain'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle URL with subdomain', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://blog.example.com/post',
				title: generateTestTitle('Subdomain URL'),
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.url).toContain('blog.example.com');
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle URL with port number', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com:8080/page',
				title: generateTestTitle('URL with Port'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle URL with authentication', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://user:pass@example.com/page',
				title: generateTestTitle('URL with Auth'),
			});

			expect(result.success).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle redirect=false in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/no-redirect',
				title: generateTestTitle('No Redirect Link'),
				actions: {
					redirect: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.redirect).toBe(false);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle redirect=true in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/with-redirect',
				title: generateTestTitle('With Redirect Link'),
				actions: {
					redirect: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.redirect).toBe(true);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle very large lists array', async () => {
			const largeLists = Array.from({ length: 50 }, (_, i) => i + 1);
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/large-lists',
				title: generateTestTitle('Large Lists Array'),
				actions: {
					lists: largeLists,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.lists).toHaveLength(50);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle very large tags array', async () => {
			const largeTags = Array.from({ length: 50 }, (_, i) => i + 100);
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/large-tags',
				title: generateTestTitle('Large Tags Array'),
				actions: {
					tags: largeTags,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.tags).toHaveLength(50);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle empty lists array in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/empty-lists',
				title: generateTestTitle('Empty Lists'),
				actions: {
					lists: [],
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.lists).toEqual([]);
			testSmartLinkIds.push(result.data.smart_link.id);
		});

		it('should handle empty tags array in actions', async () => {
			const result = await mcp.callTool('fluentcrm-create-smart-link', {
				url: 'https://example.com/empty-tags',
				title: generateTestTitle('Empty Tags'),
				actions: {
					tags: [],
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.smart_link.actions.tags).toEqual([]);
			testSmartLinkIds.push(result.data.smart_link.id);
		});
	});
});
