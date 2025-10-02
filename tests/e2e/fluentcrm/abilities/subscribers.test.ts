/**
 * E2E Tests for FluentCRM Subscribers Abilities
 *
 * Tests all subscriber management tools including CRUD operations,
 * bulk operations, searching, and relationship management.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Subscribers', () => {
	let mcp: MCPClient;
	const testIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test subscribers
		if (testIds.length > 0) {
			await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: testIds,
				confirm_delete: true,
			});
		}
	});

	describe('Create Subscriber', () => {
		it('should create subscriber with minimal required fields', async () => {
			const email = generateTestEmail();
			const result = await mcp.callTool('fluentcrm-create-subscriber', { email });

			expect(result.success).toBe(true);
			expect(result.data.subscriber.email).toBe(email);
			expect(result.data.subscriber).toHaveProperty('id');

			testIds.push(result.data.subscriber.id);
		});

		it('should create subscriber with full profile data', async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				first_name: 'John',
				last_name: 'Doe',
				status: 'pending',
				phone: '+1234567890',
				address_line_1: '123 Main St',
				city: 'New York',
				state: 'NY',
				postal_code: '10001',
				country: 'US',
				timezone: 'America/New_York',
				date_of_birth: '1990-01-01',
			});

			expect(result.success).toBe(true);
			expect(result.data.subscriber.first_name).toBe('John');
			expect(result.data.subscriber.last_name).toBe('Doe');
			expect(result.data.subscriber.status).toBe('pending');

			testIds.push(result.data.subscriber.id);
		});

		it('should create subscriber with custom field values', async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				custom_values: {
					company: 'Test Company',
					position: 'Developer',
				},
			});

			expect(result.success).toBe(true);
			testIds.push(result.data.subscriber.id);
		});

		it('should reject invalid email format', async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: 'invalid-email',
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required email field', async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				first_name: 'John',
			});

			expect(result.success).toBe(false);
		});

		it('should handle all valid status values', async () => {
			const statuses = ['subscribed', 'pending', 'unsubscribed', 'bounced', 'complained'];

			for (const status of statuses) {
				const result = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
					status,
				});

				expect(result.success).toBe(true);
				expect(result.data.subscriber.status).toBe(status);
				testIds.push(result.data.subscriber.id);
			}
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Subscriber', () => {
		let subscriberId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				first_name: 'Test',
			});
			subscriberId = result.data.subscriber.id;
			testIds.push(subscriberId);
		});

		it('should get subscriber by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-subscriber', {
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscriber.id).toBe(subscriberId);
		});

		it('should get subscriber by email', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			const email = createResult.data.subscriber.email;
			testIds.push(createResult.data.subscriber.id);

			const result = await mcp.callTool('fluentcrm-get-subscriber', {
				email,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscriber.email).toBe(email);
		});

		it('should include tags when requested', async () => {
			const result = await mcp.callTool('fluentcrm-get-subscriber', {
				subscriber_id: subscriberId,
				with: ['tags'],
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('tags');
		});

		it('should include lists when requested', async () => {
			const result = await mcp.callTool('fluentcrm-get-subscriber', {
				subscriber_id: subscriberId,
				with: ['lists'],
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('lists');
		});

		it('should handle non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-subscriber', {
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('List Subscribers', () => {
		it('should list subscribers with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-subscribers', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('subscribers');
			expect(result.data).toHaveProperty('total');
			expect(Array.isArray(result.data.subscribers)).toBe(true);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-subscribers', {
				page: 1,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscribers.length).toBeLessThanOrEqual(5);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(5);
		});

		it('should filter by status', async () => {
			const result = await mcp.callTool('fluentcrm-list-subscribers', {
				status: 'subscribed',
			});

			expect(result.success).toBe(true);
			result.data.subscribers.forEach((sub: any) => {
				expect(sub.status).toBe('subscribed');
			});
		});

		it('should search by email', async () => {
			const email = generateTestEmail();
			await mcp.callTool('fluentcrm-create-subscriber', { email });

			const result = await mcp.callTool('fluentcrm-list-subscribers', {
				search: email,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscribers.some((s: any) => s.email === email)).toBe(true);
		});

		it('should handle all orderby options', async () => {
			const orderByOptions = ['id', 'email', 'first_name', 'last_name', 'created_at', 'updated_at'];

			for (const orderby of orderByOptions) {
				const result = await mcp.callTool('fluentcrm-list-subscribers', {
					orderby,
					order: 'DESC',
				});

				expect(result.success).toBe(true);
			}
		});

		it('should handle pagination boundaries', async () => {
			const minResult = await mcp.callTool('fluentcrm-list-subscribers', {
				page: 1,
				per_page: 1,
			});
			expect(minResult.success).toBe(true);

			const maxResult = await mcp.callTool('fluentcrm-list-subscribers', {
				page: 1,
				per_page: 100,
			});
			expect(maxResult.success).toBe(true);
		});

		it('should reject invalid per_page values', async () => {
			const result = await mcp.callTool('fluentcrm-list-subscribers', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Subscriber', () => {
		let subscriberId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId = result.data.subscriber.id;
			testIds.push(subscriberId);
		});

		it('should update subscriber profile fields', async () => {
			const result = await mcp.callTool('fluentcrm-update-subscriber', {
				subscriber_id: subscriberId,
				first_name: 'Updated',
				last_name: 'Name',
			});

			expect(result.success).toBe(true);
			expect(result.data.subscriber.first_name).toBe('Updated');
		});

		it('should update email address', async () => {
			const newEmail = generateTestEmail();
			const result = await mcp.callTool('fluentcrm-update-subscriber', {
				subscriber_id: subscriberId,
				email: newEmail,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscriber.email).toBe(newEmail);
		});

		it('should update custom field values', async () => {
			const result = await mcp.callTool('fluentcrm-update-subscriber', {
				subscriber_id: subscriberId,
				custom_values: {
					updated_field: 'new value',
				},
			});

			expect(result.success).toBe(true);
		});

		it('should reject update without subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-subscriber', {
				first_name: 'Test',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Subscriber Status', () => {
		let subscriberId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'subscribed',
			});
			subscriberId = result.data.subscriber.id;
			testIds.push(subscriberId);
		});

		it('should change status to pending', async () => {
			const result = await mcp.callTool('fluentcrm-update-subscriber-status', {
				subscriber_id: subscriberId,
				status: 'pending',
			});

			expect(result.success).toBe(true);
		});

		it('should handle all valid status transitions', async () => {
			const statuses = ['pending', 'subscribed', 'unsubscribed', 'bounced', 'complained'];

			for (const status of statuses) {
				const result = await mcp.callTool('fluentcrm-update-subscriber-status', {
					subscriber_id: subscriberId,
					status,
				});

				expect(result.success).toBe(true);
			}
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-update-subscriber-status', {
				subscriber_id: subscriberId,
				status: 'invalid',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Delete Subscriber', () => {
		it('should delete subscriber with confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			const id = createResult.data.subscriber.id;

			const result = await mcp.callTool('fluentcrm-delete-subscriber', {
				subscriber_id: id,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
		});

		it('should reject delete without confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			const id = createResult.data.subscriber.id;
			testIds.push(id);

			const result = await mcp.callTool('fluentcrm-delete-subscriber', {
				subscriber_id: id,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Bulk Import Subscribers', () => {
		it('should import multiple subscribers', async () => {
			const timestamp = Date.now();
			const result = await mcp.callTool('fluentcrm-bulk-import-subscribers', {
				subscribers: [
					{ email: `bulk1-${timestamp}@example.com`, first_name: 'Bulk1' },
					{ email: `bulk2-${timestamp}@example.com`, first_name: 'Bulk2' },
					{ email: `bulk3-${timestamp}@example.com`, first_name: 'Bulk3' },
				],
			});

			expect(result.success).toBe(true);
			expect(result.data.imported).toBeGreaterThanOrEqual(3);
		});

		it('should handle update_existing parameter', async () => {
			const email = generateTestEmail();
			await mcp.callTool('fluentcrm-create-subscriber', {
				email,
				first_name: 'Original',
			});

			const result = await mcp.callTool('fluentcrm-bulk-import-subscribers', {
				subscribers: [{ email, first_name: 'Updated' }],
				update_existing: true,
			});

			expect(result.success).toBe(true);
		});

		it('should reject empty subscribers array', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-import-subscribers', {
				subscribers: [],
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Bulk Update Subscribers', () => {
		it('should update multiple subscribers', async () => {
			const ids = [];
			for (let i = 0; i < 3; i++) {
				const result = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
				});
				ids.push(result.data.subscriber.id);
				testIds.push(result.data.subscriber.id);
			}

			const result = await mcp.callTool('fluentcrm-bulk-update-subscribers', {
				subscriber_ids: ids,
				update_data: {
					status: 'pending',
					country: 'US',
				},
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Bulk Delete Subscribers', () => {
		it('should delete multiple subscribers with confirmation', async () => {
			const ids = [];
			for (let i = 0; i < 3; i++) {
				const result = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
				});
				ids.push(result.data.subscriber.id);
			}

			const result = await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: ids,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
		});

		it('should reject bulk delete without confirmation', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: [1, 2, 3],
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('List and Tag Management', () => {
		let subscriberId: number;
		let listId: number;
		let tagId: number;

		beforeAll(async () => {
			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId = subResult.data.subscriber.id;
			testIds.push(subscriberId);

			const listResult = await mcp.callTool('fluentcrm-create-list', {
				title: generateTestTitle('Test List'),
			});
			listId = listResult.data.list.id;

			const tagResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Test Tag'),
			});
			tagId = tagResult.data.tag.id;
		});

		it('should add subscriber to list', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-list', {
				subscriber_id: subscriberId,
				list_id: listId,
			});

			expect(result.success).toBe(true);
		});

		it('should remove subscriber from list', async () => {
			await mcp.callTool('fluentcrm-add-subscriber-to-list', {
				subscriber_id: subscriberId,
				list_id: listId,
			});

			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-list', {
				subscriber_id: subscriberId,
				list_id: listId,
			});

			expect(result.success).toBe(true);
		});

		it('should add tag to subscriber', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-tag', {
				subscriber_id: subscriberId,
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
		});

		it('should remove tag from subscriber', async () => {
			await mcp.callTool('fluentcrm-add-subscriber-tag', {
				subscriber_id: subscriberId,
				tag_id: tagId,
			});

			const result = await mcp.callTool('fluentcrm-remove-subscriber-tag', {
				subscriber_id: subscriberId,
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Merge Subscribers', () => {
		it('should merge duplicate subscribers', async () => {
			const primary = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				first_name: 'Primary',
			});

			const duplicate = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				first_name: 'Duplicate',
			});

			testIds.push(primary.data.subscriber.id);

			const result = await mcp.callTool('fluentcrm-merge-subscribers', {
				primary_subscriber_id: primary.data.subscriber.id,
				merge_subscriber_ids: [duplicate.data.subscriber.id],
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Search Subscribers', () => {
		it('should search with email filter', async () => {
			const result = await mcp.callTool('fluentcrm-search-subscribers', {
				filters: {
					email_contains: '@example.com',
				},
			});

			expect(result.success).toBe(true);
		});

		it('should search with name filter', async () => {
			const result = await mcp.callTool('fluentcrm-search-subscribers', {
				filters: {
					name_contains: 'Test',
				},
			});

			expect(result.success).toBe(true);
		});

		it('should search with status filter', async () => {
			const result = await mcp.callTool('fluentcrm-search-subscribers', {
				filters: {
					status_in: ['subscribed', 'pending'],
				},
			});

			expect(result.success).toBe(true);
		});

		it('should search with date range', async () => {
			const result = await mcp.callTool('fluentcrm-search-subscribers', {
				filters: {
					created_after: '2025-01-01',
					created_before: '2025-12-31',
				},
			});

			expect(result.success).toBe(true);
		});

		it('should reject search without filters', async () => {
			const result = await mcp.callTool('fluentcrm-search-subscribers', {});

			expect(result.success).toBe(false);
		});
	});
});
