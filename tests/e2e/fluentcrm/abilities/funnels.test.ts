/**
 * E2E Tests for FluentCRM Funnels Abilities
 *
 * Tests all funnel automation management tools including CRUD operations,
 * lifecycle management, analytics, and condition testing.
 *
 * NOTE: Requires FluentCRM Pro with automation funnels enabled.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Funnels', () => {
	let mcp: MCPClient;
	const testFunnelIds: number[] = [];
	const testSubscriberIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test funnels
		for (const funnelId of testFunnelIds) {
			await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: funnelId,
				confirm_delete: true,
			});
		}

		// Cleanup test subscribers
		if (testSubscriberIds.length > 0) {
			await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: testSubscriberIds,
				confirm_delete: true,
			});
		}
	});

	describe('Create Funnel', () => {
		it('should create funnel with minimal required fields', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Test Funnel'),
				trigger_name: 'user_register',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel).toHaveProperty('id');
			expect(result.data.funnel.title).toContain('Test Funnel');
			expect(result.data.funnel.trigger_name).toBe('user_register');
			expect(result.data.funnel.status).toBe('draft'); // Default status

			testFunnelIds.push(result.data.funnel.id);
		});

		it('should create funnel with draft status', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Draft Funnel'),
				trigger_name: 'tag_applied',
				status: 'draft',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.status).toBe('draft');

			testFunnelIds.push(result.data.funnel.id);
		});

		it('should create funnel with published status', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Published Funnel'),
				trigger_name: 'list_applied',
				status: 'published',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.status).toBe('published');

			testFunnelIds.push(result.data.funnel.id);
		});

		it('should create funnel with subscription status setting', async () => {
			const subscriptionStatuses = ['subscribed', 'pending', 'unsubscribed'];

			for (const status of subscriptionStatuses) {
				const result = await mcp.callTool('fluentcrm-create-funnel', {
					title: generateTestTitle(`Funnel ${status}`),
					trigger_name: 'user_register',
					settings: {
						subscription_status: status,
					},
				});

				expect(result.success).toBe(true);
				testFunnelIds.push(result.data.funnel.id);
			}
		});

		it('should create funnel with run_only_once condition', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Once Funnel'),
				trigger_name: 'tag_applied',
				conditions: {
					run_only_once: true,
				},
			});

			expect(result.success).toBe(true);
			testFunnelIds.push(result.data.funnel.id);
		});

		it('should create funnel with full settings and conditions', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Full Config Funnel'),
				trigger_name: 'list_applied',
				status: 'draft',
				settings: {
					subscription_status: 'subscribed',
				},
				conditions: {
					run_only_once: false,
				},
			});

			expect(result.success).toBe(true);
			testFunnelIds.push(result.data.funnel.id);
		});

		it('should reject funnel without title', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				trigger_name: 'user_register',
			});

			expect(result.success).toBe(false);
		});

		it('should reject funnel without trigger_name', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Invalid Status'),
				trigger_name: 'user_register',
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should test different trigger types', async () => {
			const triggers = [
				'user_register',
				'tag_applied',
				'list_applied',
				'user_login',
				'contact_added',
			];

			for (const trigger of triggers) {
				const result = await mcp.callTool('fluentcrm-create-funnel', {
					title: generateTestTitle(`Trigger ${trigger}`),
					trigger_name: trigger,
				});

				expect(result.success).toBe(true);
				testFunnelIds.push(result.data.funnel.id);
			}
		});
	});

	describe('List Funnels', () => {
		beforeAll(async () => {
			// Create test funnels with different statuses
			await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('List Test Draft'),
				trigger_name: 'user_register',
				status: 'draft',
			});

			await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('List Test Published'),
				trigger_name: 'user_register',
				status: 'published',
			});
		});

		it('should list all funnels with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('funnels');
			expect(result.data).toHaveProperty('total');
			expect(Array.isArray(result.data.funnels)).toBe(true);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				page: 1,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnels.length).toBeLessThanOrEqual(5);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(5);
		});

		it('should filter by published status', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				status: 'published',
			});

			expect(result.success).toBe(true);
			result.data.funnels.forEach((funnel: any) => {
				expect(funnel.status).toBe('published');
			});
		});

		it('should filter by draft status', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				status: 'draft',
			});

			expect(result.success).toBe(true);
			result.data.funnels.forEach((funnel: any) => {
				expect(funnel.status).toBe('draft');
			});
		});

		it('should filter by archived status', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				status: 'archived',
			});

			expect(result.success).toBe(true);
		});

		it('should search by title', async () => {
			const searchTerm = generateTestTitle('Searchable');
			await mcp.callTool('fluentcrm-create-funnel', {
				title: searchTerm,
				trigger_name: 'user_register',
			});

			const result = await mcp.callTool('fluentcrm-list-funnels', {
				search: searchTerm,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnels.some((f: any) => f.title.includes(searchTerm))).toBe(true);
		});

		it('should handle minimum pagination boundary', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				page: 1,
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnels.length).toBeLessThanOrEqual(1);
		});

		it('should handle maximum pagination boundary', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				page: 1,
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnels.length).toBeLessThanOrEqual(100);
		});

		it('should reject invalid per_page value exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid per_page value below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				per_page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid page number', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should include subscriber counts in list', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {});

			expect(result.success).toBe(true);
			if (result.data.funnels.length > 0) {
				expect(result.data.funnels[0]).toHaveProperty('subscriber_count');
			}
		});
	});

	describe('Get Funnel', () => {
		let testFunnelId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Get Test'),
				trigger_name: 'user_register',
			});
			testFunnelId = result.data.funnel.id;
			testFunnelIds.push(testFunnelId);
		});

		it('should get funnel by ID with full details', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.id).toBe(testFunnelId);
			expect(result.data.funnel).toHaveProperty('title');
			expect(result.data.funnel).toHaveProperty('trigger_name');
			expect(result.data.funnel).toHaveProperty('status');
			expect(result.data.funnel).toHaveProperty('settings');
			expect(result.data.funnel).toHaveProperty('conditions');
			expect(result.data.funnel).toHaveProperty('sequences');
			expect(result.data.funnel).toHaveProperty('subscriber_stats');
		});

		it('should include sequences in response', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel).toHaveProperty('sequences');
			expect(Array.isArray(result.data.funnel.sequences)).toBe(true);
			expect(result.data.funnel).toHaveProperty('sequences_count');
		});

		it('should include subscriber statistics', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.subscriber_stats).toHaveProperty('total');
			expect(result.data.funnel.subscriber_stats).toHaveProperty('active');
			expect(result.data.funnel.subscriber_stats).toHaveProperty('completed');
			expect(result.data.funnel.subscriber_stats).toHaveProperty('cancelled');
		});

		it('should reject invalid funnel ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid funnel ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {
				funnel_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing funnel_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Funnel', () => {
		let testFunnelId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Update Test'),
				trigger_name: 'user_register',
			});
			testFunnelId = result.data.funnel.id;
			testFunnelIds.push(testFunnelId);
		});

		it('should update funnel title', async () => {
			const newTitle = generateTestTitle('Updated');
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				title: newTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.title).toBe(newTitle);
		});

		it('should update funnel trigger_name', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				trigger_name: 'tag_applied',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.trigger_name).toBe('tag_applied');
		});

		it('should update funnel status to draft', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				status: 'draft',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.status).toBe('draft');
		});

		it('should update funnel status to published', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				status: 'published',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.status).toBe('published');
		});

		it('should update funnel status to archived', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				status: 'archived',
			});

			expect(result.success).toBe(true);
			expect(result.data.funnel.status).toBe('archived');
		});

		it('should update funnel settings', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				settings: {
					subscription_status: 'pending',
				},
			});

			expect(result.success).toBe(true);
		});

		it('should update funnel conditions', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				conditions: {
					run_only_once: true,
				},
			});

			expect(result.success).toBe(true);
		});

		it('should update multiple fields at once', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				title: generateTestTitle('Multi Update'),
				trigger_name: 'list_applied',
				status: 'published',
				settings: { subscription_status: 'subscribed' },
				conditions: { run_only_once: false },
			});

			expect(result.success).toBe(true);
		});

		it('should reject update without funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				title: 'New Title',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: testFunnelId,
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-funnel', {
				funnel_id: 0,
				title: 'Test',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Delete Funnel', () => {
		it('should delete funnel with confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Delete Test'),
				trigger_name: 'user_register',
			});
			const funnelId = createResult.data.funnel.id;

			const result = await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: funnelId,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('funnel_id');
			expect(result.data).toHaveProperty('funnel_title');
		});

		it('should reject delete without confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('No Confirm'),
				trigger_name: 'user_register',
			});
			const funnelId = createResult.data.funnel.id;
			testFunnelIds.push(funnelId);

			const result = await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: funnelId,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete without confirm_delete parameter', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Missing Confirm'),
				trigger_name: 'user_register',
			});
			const funnelId = createResult.data.funnel.id;
			testFunnelIds.push(funnelId);

			const result = await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: funnelId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: 0,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-delete-funnel', {
				funnel_id: 999999,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Duplicate Funnel', () => {
		let originalFunnelId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Original'),
				trigger_name: 'user_register',
				settings: { subscription_status: 'subscribed' },
				conditions: { run_only_once: true },
			});
			originalFunnelId = result.data.funnel.id;
			testFunnelIds.push(originalFunnelId);
		});

		it('should duplicate funnel with default title', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {
				funnel_id: originalFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.new_funnel).toHaveProperty('id');
			expect(result.data.new_funnel.title).toContain('Copy of');
			expect(result.data.new_funnel.status).toBe('draft'); // Always draft
			expect(result.data.original_funnel.id).toBe(originalFunnelId);

			testFunnelIds.push(result.data.new_funnel.id);
		});

		it('should duplicate funnel with custom title', async () => {
			const customTitle = generateTestTitle('Custom Duplicate');
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {
				funnel_id: originalFunnelId,
				new_title: customTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.new_funnel.title).toBe(customTitle);
			expect(result.data.new_funnel.status).toBe('draft');

			testFunnelIds.push(result.data.new_funnel.id);
		});

		it('should include sequences_duplicated count', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {
				funnel_id: originalFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.new_funnel).toHaveProperty('sequences_duplicated');
			expect(typeof result.data.new_funnel.sequences_duplicated).toBe('number');

			testFunnelIds.push(result.data.new_funnel.id);
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {
				funnel_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing funnel_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-funnel', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Activate Funnel', () => {
		let draftFunnelId: number;
		let publishedFunnelId: number;

		beforeAll(async () => {
			const draftResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Activate Draft'),
				trigger_name: 'user_register',
				status: 'draft',
			});
			draftFunnelId = draftResult.data.funnel.id;
			testFunnelIds.push(draftFunnelId);

			const publishedResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Already Active'),
				trigger_name: 'user_register',
				status: 'published',
			});
			publishedFunnelId = publishedResult.data.funnel.id;
			testFunnelIds.push(publishedFunnelId);
		});

		it('should activate draft funnel', async () => {
			const result = await mcp.callTool('fluentcrm-activate-funnel', {
				funnel_id: draftFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.status).toBe('published');
			expect(result.data.action).toBe('activated');
			expect(result.data).toHaveProperty('activated_at');
		});

		it('should handle already active funnel gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-activate-funnel', {
				funnel_id: publishedFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.status).toBe('published');
			expect(result.data.action).toBe('already_active');
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-activate-funnel', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-activate-funnel', {
				funnel_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing funnel_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-activate-funnel', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Deactivate Funnel', () => {
		let publishedFunnelId: number;
		let draftFunnelId: number;

		beforeAll(async () => {
			const publishedResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Deactivate Published'),
				trigger_name: 'user_register',
				status: 'published',
			});
			publishedFunnelId = publishedResult.data.funnel.id;
			testFunnelIds.push(publishedFunnelId);

			const draftResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Already Inactive'),
				trigger_name: 'user_register',
				status: 'draft',
			});
			draftFunnelId = draftResult.data.funnel.id;
			testFunnelIds.push(draftFunnelId);
		});

		it('should deactivate published funnel', async () => {
			const result = await mcp.callTool('fluentcrm-deactivate-funnel', {
				funnel_id: publishedFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.status).toBe('draft');
			expect(result.data.action).toBe('deactivated');
			expect(result.data).toHaveProperty('deactivated_at');
		});

		it('should handle already inactive funnel gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-deactivate-funnel', {
				funnel_id: draftFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.status).toBe('draft');
			expect(result.data.action).toBe('already_inactive');
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-deactivate-funnel', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-deactivate-funnel', {
				funnel_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing funnel_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-deactivate-funnel', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Funnel Subscribers', () => {
		let testFunnelId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Subscriber Test'),
				trigger_name: 'user_register',
			});
			testFunnelId = result.data.funnel.id;
			testFunnelIds.push(testFunnelId);
		});

		it('should list funnel subscribers with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('subscribers');
			expect(result.data).toHaveProperty('total');
			expect(result.data.funnel_id).toBe(testFunnelId);
			expect(Array.isArray(result.data.subscribers)).toBe(true);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				page: 1,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(5);
		});

		it('should filter by active status', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				status: 'active',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by completed status', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				status: 'completed',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by cancelled status', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				status: 'cancelled',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				sequence_id: 1,
			});

			expect(result.success).toBe(true);
		});

		it('should handle pagination boundaries', async () => {
			const minResult = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				page: 1,
				per_page: 1,
			});
			expect(minResult.success).toBe(true);

			const maxResult = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				page: 1,
				per_page: 100,
			});
			expect(maxResult.success).toBe(true);
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid per_page exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-subscribers', {
				funnel_id: testFunnelId,
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Funnel Metrics', () => {
		let testFunnelId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Metrics Test'),
				trigger_name: 'user_register',
			});
			testFunnelId = result.data.funnel.id;
			testFunnelIds.push(testFunnelId);
		});

		it('should get funnel metrics without date range', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.metrics).toHaveProperty('funnel_id');
			expect(result.data.metrics).toHaveProperty('funnel_title');
			expect(result.data.metrics).toHaveProperty('status');
			expect(result.data.metrics).toHaveProperty('subscribers');
			expect(result.data.metrics).toHaveProperty('sequences');
		});

		it('should include subscriber statistics breakdown', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(result.data.metrics.subscribers).toHaveProperty('total');
			expect(result.data.metrics.subscribers).toHaveProperty('active');
			expect(result.data.metrics.subscribers).toHaveProperty('completed');
			expect(result.data.metrics.subscribers).toHaveProperty('cancelled');
		});

		it('should filter metrics by start_date only', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
				start_date: '2025-01-01',
			});

			expect(result.success).toBe(true);
			expect(result.data.metrics.date_range.start_date).toBe('2025-01-01');
		});

		it('should filter metrics by end_date only', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.metrics.date_range.end_date).toBe('2025-12-31');
		});

		it('should filter metrics by date range', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
				start_date: '2025-01-01',
				end_date: '2025-12-31',
			});

			expect(result.success).toBe(true);
			expect(result.data.metrics.date_range.start_date).toBe('2025-01-01');
			expect(result.data.metrics.date_range.end_date).toBe('2025-12-31');
		});

		it('should reject invalid date format for start_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
				start_date: '2025/01/01',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid date format for end_date', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
				end_date: 'invalid-date',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should include sequence statistics', async () => {
			const result = await mcp.callTool('fluentcrm-get-funnel-metrics', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(true);
			expect(Array.isArray(result.data.metrics.sequences)).toBe(true);
		});
	});

	describe('Test Funnel Conditions', () => {
		let testFunnelId: number;
		let testSubscriberId: number;
		let subscribedSubscriberId: number;

		beforeAll(async () => {
			// Create test funnel with conditions
			const funnelResult = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Condition Test'),
				trigger_name: 'user_register',
				settings: {
					subscription_status: 'subscribed',
				},
				conditions: {
					run_only_once: true,
				},
			});
			testFunnelId = funnelResult.data.funnel.id;
			testFunnelIds.push(testFunnelId);

			// Create test subscribers with different statuses
			const pendingResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'pending',
			});
			testSubscriberId = pendingResult.data.subscriber.id;
			testSubscriberIds.push(testSubscriberId);

			const subscribedResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'subscribed',
			});
			subscribedSubscriberId = subscribedResult.data.subscriber.id;
			testSubscriberIds.push(subscribedSubscriberId);
		});

		it('should test funnel conditions for valid subscriber', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: subscribedSubscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.test_results).toHaveProperty('funnel_id');
			expect(result.data.test_results).toHaveProperty('subscriber_id');
			expect(result.data.test_results).toHaveProperty('can_enter');
			expect(result.data.test_results).toHaveProperty('conditions');
			expect(result.data.test_results).toHaveProperty('reasons');
		});

		it('should include subscriber details in test results', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.test_results.subscriber).toHaveProperty('email');
			expect(result.data.test_results.subscriber).toHaveProperty('status');
		});

		it('should test subscription status condition', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.test_results.conditions).toHaveProperty('subscription_status');
		});

		it('should test run_only_once condition', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(true);
			if (result.data.test_results.conditions.run_only_once) {
				expect(result.data.test_results.conditions.run_only_once).toHaveProperty('enabled');
				expect(result.data.test_results.conditions.run_only_once).toHaveProperty('already_processed');
				expect(result.data.test_results.conditions.run_only_once).toHaveProperty('passed');
			}
		});

		it('should provide failure reasons when conditions not met', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(true);
			if (!result.data.test_results.can_enter) {
				expect(Array.isArray(result.data.test_results.reasons)).toBe(true);
				expect(result.data.test_results.reasons.length).toBeGreaterThan(0);
			}
		});

		it('should reject invalid funnel_id', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: 0,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent funnel', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: 999999,
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent subscriber', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing funnel_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				subscriber_id: testSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-test-funnel-conditions', {
				funnel_id: testFunnelId,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Error Handling and Edge Cases', () => {
		it('should handle missing required parameters gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {});

			expect(result.success).toBe(false);
		});

		it('should validate enum values strictly', async () => {
			const result = await mcp.callTool('fluentcrm-list-funnels', {
				status: 'not_a_valid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should handle concurrent funnel operations', async () => {
			const promises = Array(5)
				.fill(null)
				.map((_, i) =>
					mcp.callTool('fluentcrm-create-funnel', {
						title: generateTestTitle(`Concurrent ${i}`),
						trigger_name: 'user_register',
					})
				);

			const results = await Promise.all(promises);
			results.forEach((result) => {
				expect(result.success).toBe(true);
				if (result.success) {
					testFunnelIds.push(result.data.funnel.id);
				}
			});
		});

		it('should handle funnel with empty settings object', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Empty Settings'),
				trigger_name: 'user_register',
				settings: {},
			});

			expect(result.success).toBe(true);
			testFunnelIds.push(result.data.funnel.id);
		});

		it('should handle funnel with empty conditions object', async () => {
			const result = await mcp.callTool('fluentcrm-create-funnel', {
				title: generateTestTitle('Empty Conditions'),
				trigger_name: 'user_register',
				conditions: {},
			});

			expect(result.success).toBe(true);
			testFunnelIds.push(result.data.funnel.id);
		});
	});
});
