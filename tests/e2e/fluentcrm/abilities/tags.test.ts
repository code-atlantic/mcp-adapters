/**
 * E2E Tests for FluentCRM Tags Abilities
 *
 * Tests all tag management tools including CRUD operations,
 * subscriber relationships, statistics, and bulk operations.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Tags', () => {
	let mcp: MCPClient;
	const testTagIds: number[] = [];
	const testSubscriberIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test tags
		for (const tagId of testTagIds) {
			await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: tagId,
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

	describe('Create Tag', () => {
		it('should create tag with minimal required fields', async () => {
			const title = generateTestTitle('Tag');
			const result = await mcp.callTool('fluentcrm-create-tag', { title });

			expect(result.success).toBe(true);
			expect(result.data.tag.title).toBe(title);
			expect(result.data.tag).toHaveProperty('id');
			expect(result.data.tag).toHaveProperty('slug');

			testTagIds.push(result.data.tag.id);
		});

		it('should create tag with all fields', async () => {
			const title = generateTestTitle('Full Tag');
			const description = 'This is a test tag with full details';
			const slug = `custom-slug-${Date.now()}`;

			const result = await mcp.callTool('fluentcrm-create-tag', {
				title,
				description,
				slug,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.title).toBe(title);
			expect(result.data.tag.description).toBe(description);
			expect(result.data.tag.slug).toBe(slug);

			testTagIds.push(result.data.tag.id);
		});

		it('should auto-generate slug from title if not provided', async () => {
			const title = generateTestTitle('Auto Slug Tag');
			const result = await mcp.callTool('fluentcrm-create-tag', { title });

			expect(result.success).toBe(true);
			expect(result.data.tag.slug).toBeTruthy();
			expect(result.data.tag.slug).toContain('auto-slug-tag');

			testTagIds.push(result.data.tag.id);
		});

		it('should create tag with description only', async () => {
			const title = generateTestTitle('Desc Tag');
			const description = 'Tag with only description, no slug';

			const result = await mcp.callTool('fluentcrm-create-tag', {
				title,
				description,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.description).toBe(description);

			testTagIds.push(result.data.tag.id);
		});

		it('should reject missing required title field', async () => {
			const result = await mcp.callTool('fluentcrm-create-tag', {
				description: 'No title provided',
			});

			expect(result.success).toBe(false);
		});

		it('should reject empty title', async () => {
			const result = await mcp.callTool('fluentcrm-create-tag', {
				title: '',
			});

			expect(result.success).toBe(false);
		});

		it('should reject duplicate slug', async () => {
			const slug = `duplicate-slug-${Date.now()}`;

			const firstResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('First'),
				slug,
			});

			testTagIds.push(firstResult.data.tag.id);

			const secondResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Second'),
				slug,
			});

			expect(secondResult.success).toBe(false);
		});
	});

	describe('List Tags', () => {
		beforeAll(async () => {
			// Create some test tags for listing
			for (let i = 0; i < 5; i++) {
				const result = await mcp.callTool('fluentcrm-create-tag', {
					title: generateTestTitle(`List Tag ${i}`),
					description: `Test description ${i}`,
				});
				testTagIds.push(result.data.tag.id);
			}
		});

		it('should list tags with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('tags');
			expect(result.data).toHaveProperty('total');
			expect(result.data).toHaveProperty('page');
			expect(result.data).toHaveProperty('per_page');
			expect(result.data).toHaveProperty('total_pages');
			expect(Array.isArray(result.data.tags)).toBe(true);
		});

		it('should respect page parameter', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				page: 1,
				per_page: 2,
			});

			expect(result.success).toBe(true);
			expect(result.data.page).toBe(1);
			expect(result.data.tags.length).toBeLessThanOrEqual(2);
		});

		it('should respect per_page parameter', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 3,
			});

			expect(result.success).toBe(true);
			expect(result.data.per_page).toBe(3);
			expect(result.data.tags.length).toBeLessThanOrEqual(3);
		});

		it('should handle per_page minimum boundary', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.tags.length).toBeLessThanOrEqual(1);
		});

		it('should handle per_page maximum boundary', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.per_page).toBe(100);
		});

		it('should reject per_page exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject per_page below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should search tags by title', async () => {
			const uniqueTitle = generateTestTitle('Searchable');
			const createResult = await mcp.callTool('fluentcrm-create-tag', {
				title: uniqueTitle,
			});
			testTagIds.push(createResult.data.tag.id);

			const result = await mcp.callTool('fluentcrm-list-tags', {
				search: 'Searchable',
			});

			expect(result.success).toBe(true);
			expect(result.data.tags.some((t: any) => t.title.includes('Searchable'))).toBe(true);
		});

		it('should search tags by slug', async () => {
			const slug = `search-slug-${Date.now()}`;
			const createResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Slug Search'),
				slug,
			});
			testTagIds.push(createResult.data.tag.id);

			const result = await mcp.callTool('fluentcrm-list-tags', {
				search: slug,
			});

			expect(result.success).toBe(true);
			expect(result.data.tags.some((t: any) => t.slug === slug)).toBe(true);
		});

		it('should return empty results for non-existent search', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				search: `nonexistent-${Date.now()}`,
			});

			expect(result.success).toBe(true);
			expect(result.data.tags.length).toBe(0);
		});

		it('should include subscriber count for each tag', async () => {
			const result = await mcp.callTool('fluentcrm-list-tags', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			if (result.data.tags.length > 0) {
				expect(result.data.tags[0]).toHaveProperty('subscribers_count');
			}
		});
	});

	describe('Get Tag', () => {
		let tagId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Get Tag'),
				description: 'Tag for retrieval testing',
			});
			tagId = result.data.tag.id;
			testTagIds.push(tagId);
		});

		it('should get tag by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag', {
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.id).toBe(tagId);
			expect(result.data.tag).toHaveProperty('title');
			expect(result.data.tag).toHaveProperty('slug');
			expect(result.data.tag).toHaveProperty('description');
			expect(result.data.tag).toHaveProperty('created_at');
			expect(result.data.tag).toHaveProperty('updated_at');
			expect(result.data.tag).toHaveProperty('subscribers_count');
		});

		it('should reject invalid tag ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag', {
				tag_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid tag ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag', {
				tag_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag', {
				tag_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required tag_id field', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Tag', () => {
		let tagId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Update Tag'),
				description: 'Original description',
			});
			tagId = result.data.tag.id;
			testTagIds.push(tagId);
		});

		it('should update tag title', async () => {
			const newTitle = generateTestTitle('Updated');
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				title: newTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.title).toBe(newTitle);
		});

		it('should update tag description', async () => {
			const newDescription = 'Updated description';
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				description: newDescription,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.description).toBe(newDescription);
		});

		it('should update tag slug', async () => {
			const newSlug = `updated-slug-${Date.now()}`;
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				slug: newSlug,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.slug).toBe(newSlug);
		});

		it('should update all fields at once', async () => {
			const newTitle = generateTestTitle('All Updated');
			const newDescription = 'All fields updated';
			const newSlug = `all-updated-${Date.now()}`;

			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				title: newTitle,
				description: newDescription,
				slug: newSlug,
			});

			expect(result.success).toBe(true);
			expect(result.data.tag.title).toBe(newTitle);
			expect(result.data.tag.description).toBe(newDescription);
			expect(result.data.tag.slug).toBe(newSlug);
		});

		it('should reject update without tag_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-tag', {
				title: 'New Title',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: 0,
				title: 'New Title',
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: 999999,
				title: 'New Title',
			});

			expect(result.success).toBe(false);
		});

		it('should reject update with no data', async () => {
			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject duplicate slug', async () => {
			const existingSlug = `existing-${Date.now()}`;

			const otherResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Other'),
				slug: existingSlug,
			});
			testTagIds.push(otherResult.data.tag.id);

			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				slug: existingSlug,
			});

			expect(result.success).toBe(false);
		});

		it('should allow updating to same slug', async () => {
			const getResult = await mcp.callTool('fluentcrm-get-tag', {
				tag_id: tagId,
			});
			const currentSlug = getResult.data.tag.slug;

			const result = await mcp.callTool('fluentcrm-update-tag', {
				tag_id: tagId,
				slug: currentSlug,
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Delete Tag', () => {
		it('should delete tag with confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Delete Tag'),
			});
			const tagId = createResult.data.tag.id;

			const result = await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: tagId,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('tag_id');
			expect(result.data).toHaveProperty('tag_title');
			expect(result.data).toHaveProperty('affected_subscribers_count');
			expect(result.data).toHaveProperty('deleted_at');
		});

		it('should reject delete without confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('No Delete'),
			});
			const tagId = createResult.data.tag.id;
			testTagIds.push(tagId);

			const result = await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: tagId,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete without confirm_delete field', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Missing Confirm'),
			});
			const tagId = createResult.data.tag.id;
			testTagIds.push(tagId);

			const result = await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: tagId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: 0,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-tag', {
				tag_id: 999999,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Tag Subscribers', () => {
		let tagId: number;
		let subscriberId: number;

		beforeAll(async () => {
			const tagResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Sub Tag'),
			});
			tagId = tagResult.data.tag.id;
			testTagIds.push(tagId);

			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'subscribed',
			});
			subscriberId = subResult.data.subscriber.id;
			testSubscriberIds.push(subscriberId);

			await mcp.callTool('fluentcrm-add-subscriber-tag', {
				subscriber_id: subscriberId,
				tag_id: tagId,
			});
		});

		it('should get subscribers with tag', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('tag');
			expect(result.data).toHaveProperty('subscribers');
			expect(result.data).toHaveProperty('total');
			expect(result.data).toHaveProperty('page');
			expect(result.data).toHaveProperty('per_page');
			expect(result.data).toHaveProperty('total_pages');
			expect(Array.isArray(result.data.subscribers)).toBe(true);
		});

		it('should respect page parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.page).toBe(1);
		});

		it('should respect per_page parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.per_page).toBe(5);
		});

		it('should handle per_page boundaries', async () => {
			const minResult = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				per_page: 1,
			});
			expect(minResult.success).toBe(true);

			const maxResult = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				per_page: 100,
			});
			expect(maxResult.success).toBe(true);
		});

		it('should reject per_page exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should filter by status: subscribed', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'subscribed',
			});

			expect(result.success).toBe(true);
			result.data.subscribers.forEach((sub: any) => {
				expect(sub.status).toBe('subscribed');
			});
		});

		it('should filter by status: unsubscribed', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'unsubscribed',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by status: pending', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'pending',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by status: bounced', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'bounced',
			});

			expect(result.success).toBe(true);
		});

		it('should filter by status: complained', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'complained',
			});

			expect(result.success).toBe(true);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: tagId,
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {
				tag_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing tag_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-subscribers', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Tag Stats', () => {
		let tagId: number;

		beforeAll(async () => {
			const tagResult = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Stats Tag'),
			});
			tagId = tagResult.data.tag.id;
			testTagIds.push(tagId);

			// Create subscribers with different statuses
			const statuses = ['subscribed', 'pending', 'unsubscribed', 'bounced', 'complained'];
			for (const status of statuses) {
				const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
					status,
				});
				testSubscriberIds.push(subResult.data.subscriber.id);

				await mcp.callTool('fluentcrm-add-subscriber-tag', {
					subscriber_id: subResult.data.subscriber.id,
					tag_id: tagId,
				});
			}
		});

		it('should get tag statistics', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('tag');
			expect(result.data).toHaveProperty('total_subscribers');
			expect(result.data).toHaveProperty('by_status');
			expect(result.data.tag).toHaveProperty('id');
			expect(result.data.tag).toHaveProperty('title');
			expect(result.data.tag).toHaveProperty('slug');
		});

		it('should include all status breakdowns', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
			expect(result.data.by_status).toHaveProperty('subscribed');
			expect(result.data.by_status).toHaveProperty('unsubscribed');
			expect(result.data.by_status).toHaveProperty('pending');
			expect(result.data.by_status).toHaveProperty('bounced');
			expect(result.data.by_status).toHaveProperty('complained');
		});

		it('should have numeric counts', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {
				tag_id: tagId,
			});

			expect(result.success).toBe(true);
			expect(typeof result.data.total_subscribers).toBe('number');
			expect(typeof result.data.by_status.subscribed).toBe('number');
			expect(typeof result.data.by_status.pending).toBe('number');
		});

		it('should reject invalid tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {
				tag_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {
				tag_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing tag_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-tag-stats', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Bulk Apply Tags', () => {
		let tagId1: number;
		let tagId2: number;
		let subscriberId1: number;
		let subscriberId2: number;
		let subscriberId3: number;

		beforeAll(async () => {
			const tag1 = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Bulk Tag 1'),
			});
			tagId1 = tag1.data.tag.id;
			testTagIds.push(tagId1);

			const tag2 = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Bulk Tag 2'),
			});
			tagId2 = tag2.data.tag.id;
			testTagIds.push(tagId2);

			const sub1 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId1 = sub1.data.subscriber.id;
			testSubscriberIds.push(subscriberId1);

			const sub2 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId2 = sub2.data.subscriber.id;
			testSubscriberIds.push(subscriberId2);

			const sub3 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId3 = sub3.data.subscriber.id;
			testSubscriberIds.push(subscriberId3);
		});

		it('should apply single tag to multiple subscribers', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1, subscriberId2],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('applied_to_subscribers');
			expect(result.data).toHaveProperty('total_subscribers');
			expect(result.data).toHaveProperty('tag_ids');
			expect(result.data).toHaveProperty('subscriber_ids');
			expect(result.data.applied_to_subscribers).toBe(2);
		});

		it('should apply multiple tags to multiple subscribers', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1, subscriberId2, subscriberId3],
				tag_ids: [tagId1, tagId2],
			});

			expect(result.success).toBe(true);
			expect(result.data.applied_to_subscribers).toBe(3);
			expect(result.data.total_subscribers).toBe(3);
		});

		it('should handle single subscriber single tag', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(true);
			expect(result.data.applied_to_subscribers).toBe(1);
		});

		it('should reject empty subscriber_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject empty tag_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [],
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_ids', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing tag_ids', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [999999],
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [999999],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Bulk Remove Tags', () => {
		let tagId1: number;
		let tagId2: number;
		let subscriberId1: number;
		let subscriberId2: number;
		let subscriberId3: number;

		beforeAll(async () => {
			const tag1 = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Remove Tag 1'),
			});
			tagId1 = tag1.data.tag.id;
			testTagIds.push(tagId1);

			const tag2 = await mcp.callTool('fluentcrm-create-tag', {
				title: generateTestTitle('Remove Tag 2'),
			});
			tagId2 = tag2.data.tag.id;
			testTagIds.push(tagId2);

			const sub1 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId1 = sub1.data.subscriber.id;
			testSubscriberIds.push(subscriberId1);

			const sub2 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId2 = sub2.data.subscriber.id;
			testSubscriberIds.push(subscriberId2);

			const sub3 = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId3 = sub3.data.subscriber.id;
			testSubscriberIds.push(subscriberId3);

			// Apply tags first
			await mcp.callTool('fluentcrm-bulk-apply-tags', {
				subscriber_ids: [subscriberId1, subscriberId2, subscriberId3],
				tag_ids: [tagId1, tagId2],
			});
		});

		it('should remove single tag from multiple subscribers', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1, subscriberId2],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('removed_from_subscribers');
			expect(result.data).toHaveProperty('total_subscribers');
			expect(result.data).toHaveProperty('tag_ids');
			expect(result.data).toHaveProperty('subscriber_ids');
			expect(result.data.removed_from_subscribers).toBe(2);
		});

		it('should remove multiple tags from multiple subscribers', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1, subscriberId2, subscriberId3],
				tag_ids: [tagId1, tagId2],
			});

			expect(result.success).toBe(true);
			expect(result.data.removed_from_subscribers).toBe(3);
			expect(result.data.total_subscribers).toBe(3);
		});

		it('should handle single subscriber single tag', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(true);
			expect(result.data.removed_from_subscribers).toBe(1);
		});

		it('should reject empty subscriber_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject empty tag_ids array', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [],
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_ids', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing tag_ids', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1],
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent tag ID', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [subscriberId1],
				tag_ids: [999999],
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-bulk-remove-tags', {
				subscriber_ids: [999999],
				tag_ids: [tagId1],
			});

			expect(result.success).toBe(false);
		});
	});
});
