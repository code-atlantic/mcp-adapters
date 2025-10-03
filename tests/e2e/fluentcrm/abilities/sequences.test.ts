/**
 * E2E Tests for FluentCRM Sequences Abilities
 *
 * Tests all sequence management tools including CRUD operations,
 * enrollment operations, and performance analytics.
 *
 * NOTE: Sequences are a FluentCRM Pro feature. Tests will be skipped
 * if FluentCRM Pro is not installed.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Sequences', () => {
	let mcp: MCPClient;
	const testSequenceIds: number[] = [];
	const testSubscriberIds: number[] = [];
	let isProAvailable = false;

	beforeAll(async () => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);

		// Check if Pro is available by attempting to create a sequence
		const proCheckResult = await mcp.callTool('fluentcrm-create-sequence', {
			title: generateTestTitle('Pro Check Sequence'),
		});

		isProAvailable = proCheckResult.success === true;

		if (!isProAvailable) {
			console.log('⚠️  FluentCRM Pro not detected - skipping Sequence tests');
			return;
		}

		// Clean up the check sequence if it was created
		if (proCheckResult.data?.sequence?.id) {
			await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: proCheckResult.data.sequence.id,
				confirm_delete: true,
			});
		}

		// Create test subscribers for enrollment tests
		for (let i = 0; i < 2; i++) {
			const result = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				first_name: `Test${i}`,
				last_name: 'Subscriber',
				status: 'subscribed',
			});
			if (result.success && result.data?.subscriber?.id) {
				testSubscriberIds.push(result.data.subscriber.id);
			}
		}
	});

	afterAll(async () => {
		if (!isProAvailable) return;

		// Cleanup test sequences
		for (const sequenceId of testSequenceIds) {
			try {
				await mcp.callTool('fluentcrm-delete-sequence', {
					sequence_id: sequenceId,
					confirm_delete: true,
				});
			} catch (error) {
				// Sequence may already be deleted, ignore errors
			}
		}

		// Cleanup test subscribers
		for (const subscriberId of testSubscriberIds) {
			try {
				await mcp.callTool('fluentcrm-delete-subscriber', {
					subscriber_id: subscriberId,
					confirm_delete: true,
				});
			} catch (error) {
				// Subscriber may already be deleted, ignore errors
			}
		}
	});

	(isProAvailable ? describe : describe.skip)('Create Sequence', () => {
		it('should create sequence with minimal required fields', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Minimal Sequence'),
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence).toBeDefined();
			expect(result.data.sequence.id).toBeDefined();
			expect(result.data.sequence.title).toContain('Minimal Sequence');
			expect(result.data.sequence.status).toBe('draft');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with description', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with Description'),
				description: 'This is a test sequence description',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.description).toBe('This is a test sequence description');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with draft status', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Draft Sequence'),
				status: 'draft',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('draft');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with published status', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Published Sequence'),
				status: 'published',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('published');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with archived status', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Archived Sequence'),
				status: 'archived',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('archived');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with email_subject_prefix setting', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with Prefix'),
				settings: {
					email_subject_prefix: '[PROMO]',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings).toBeDefined();
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[PROMO]');

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with unsubscribe_on_complete setting', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with Unsubscribe'),
				settings: {
					unsubscribe_on_complete: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with allow_re_enrollment setting', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with Re-enrollment'),
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with all settings combined', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence All Settings'),
				description: 'Complete sequence configuration',
				status: 'draft',
				settings: {
					email_subject_prefix: '[TEST]',
					unsubscribe_on_complete: true,
					allow_re_enrollment: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[TEST]');
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(false);

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should reject sequence creation without title', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {});

			expect(result.success).toBe(false);
		});

		it('should handle empty title gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: '',
			});

			expect(result.success).toBe(false);
		});

		it('should handle very long title', async () => {
			const longTitle = generateTestTitle('A'.repeat(200));
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: longTitle,
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle special characters in title', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with "quotes" & <symbols>'),
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle unicode characters in title', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Sequence with émojis 🎉 and ñ'),
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});
	});

	(isProAvailable ? describe : describe.skip)('List Sequences', () => {
		beforeAll(async () => {
			if (!isProAvailable) return;

			// Create sequences with different statuses for filtering tests
			const statuses = ['draft', 'published', 'archived'];
			for (const status of statuses) {
				const result = await mcp.callTool('fluentcrm-create-sequence', {
					title: generateTestTitle(`List Test ${status}`),
					status,
				});
				if (result.success) {
					testSequenceIds.push(result.data.sequence.id);
				}
			}
		});

		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should list sequences with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('sequences');
			expect(result.data).toHaveProperty('total');
			expect(Array.isArray(result.data.sequences)).toBe(true);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.length).toBeLessThanOrEqual(5);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(5);
		});

		it('should filter by draft status', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'draft',
			});

			expect(result.success).toBe(true);
			result.data.sequences.forEach((sequence: any) => {
				expect(sequence.status).toBe('draft');
			});
		});

		it('should filter by published status', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'published',
			});

			expect(result.success).toBe(true);
			result.data.sequences.forEach((sequence: any) => {
				expect(sequence.status).toBe('published');
			});
		});

		it('should filter by archived status', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'archived',
			});

			expect(result.success).toBe(true);
			result.data.sequences.forEach((sequence: any) => {
				expect(sequence.status).toBe('archived');
			});
		});

		it('should handle search by title', async () => {
			const searchTerm = 'List Test';
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				search: searchTerm,
			});

			expect(result.success).toBe(true);
			if (result.data.sequences.length > 0) {
				result.data.sequences.forEach((sequence: any) => {
					expect(sequence.title.toLowerCase()).toContain(searchTerm.toLowerCase());
				});
			}
		});

		it('should handle search with no results', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				search: 'NonExistentSequenceTitle123456789',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.length).toBe(0);
			expect(result.data.total).toBe(0);
		});

		it('should handle pagination boundaries (min)', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.length).toBeLessThanOrEqual(1);
		});

		it('should handle pagination boundaries (max)', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.length).toBeLessThanOrEqual(100);
		});

		it('should combine status and search filters', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'draft',
				search: 'List Test',
			});

			expect(result.success).toBe(true);
		});

		it('should handle page beyond available results', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 9999,
				per_page: 20,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.length).toBe(0);
		});
	});

	(isProAvailable ? describe : describe.skip)('Get Sequence', () => {
		let sequenceId: number;

		beforeAll(async () => {
			if (!isProAvailable) return;

			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Get Test Sequence'),
				description: 'Test description for get operation',
				status: 'draft',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should get sequence by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.id).toBe(sequenceId);
			expect(result.data.sequence.title).toBeDefined();
			expect(result.data.sequence.description).toBeDefined();
			expect(result.data.sequence.status).toBe('draft');
		});

		it('should include emails in sequence details', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence).toHaveProperty('emails');
			expect(result.data.sequence).toHaveProperty('emails_count');
			expect(Array.isArray(result.data.sequence.emails)).toBe(true);
		});

		it('should include settings in sequence details', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence).toHaveProperty('settings');
		});

		it('should include timestamps', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence).toHaveProperty('created_at');
			expect(result.data.sequence).toHaveProperty('updated_at');
		});

		it('should reject get without sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	(isProAvailable ? describe : describe.skip)('Update Sequence', () => {
		let sequenceId: number;

		beforeEach(async () => {
			if (!isProAvailable) {
				pending('FluentCRM Pro required');
				return;
			}

			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Update Test Sequence'),
				description: 'Original description',
				status: 'draft',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		it('should update sequence title', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				title: 'Updated Title',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toBe('Updated Title');
		});

		it('should update sequence description', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				description: 'Updated description',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.description).toBe('Updated description');
		});

		it('should update sequence status from draft to published', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'published',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('published');
		});

		it('should update sequence status from published to archived', async () => {
			// First publish it
			await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'published',
			});

			// Then archive it
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'archived',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('archived');
		});

		it('should update sequence status from archived to draft', async () => {
			// First archive it
			await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'archived',
			});

			// Then move to draft
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'draft',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('draft');
		});

		it('should update email_subject_prefix setting', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					email_subject_prefix: '[UPDATED]',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[UPDATED]');
		});

		it('should update unsubscribe_on_complete setting', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					unsubscribe_on_complete: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
		});

		it('should update allow_re_enrollment setting', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
		});

		it('should update multiple settings at once', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					email_subject_prefix: '[MULTI]',
					unsubscribe_on_complete: false,
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[MULTI]');
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(false);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
		});

		it('should update multiple fields at once', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				title: 'Multi Update Title',
				description: 'Multi update description',
				status: 'published',
				settings: {
					email_subject_prefix: '[MULTI]',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toBe('Multi Update Title');
			expect(result.data.sequence.description).toBe('Multi update description');
			expect(result.data.sequence.status).toBe('published');
		});

		it('should reject update without sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				title: 'Updated Title',
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: 999999,
				title: 'Updated Title',
			});

			expect(result.success).toBe(false);
		});

		it('should handle update with no changes', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
		});
	});

	(isProAvailable ? describe : describe.skip)('Delete Sequence', () => {
		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should delete sequence with confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Delete Test Sequence'),
			});
			const id = createResult.data.sequence.id;

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(id);
		});

		it('should reject delete without confirmation', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('No Delete Sequence'),
			});
			const id = createResult.data.sequence.id;
			testSequenceIds.push(id);

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete without confirm parameter', async () => {
			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: 1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: 999999,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should include sequence_title in delete response', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Title Check Delete'),
			});
			const id = createResult.data.sequence.id;

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_title).toBeDefined();
		});

		it('should include deleted_at timestamp', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Timestamp Delete'),
			});
			const id = createResult.data.sequence.id;

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.deleted_at).toBeDefined();
		});
	});

	(isProAvailable ? describe : describe.skip)('Add Subscriber to Sequence', () => {
		let publishedSequenceId: number;
		let draftSequenceId: number;

		beforeAll(async () => {
			if (!isProAvailable) return;

			// Create a published sequence for enrollment tests
			const publishedResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Enrollment Test Sequence'),
				status: 'published',
			});
			publishedSequenceId = publishedResult.data.sequence.id;
			testSequenceIds.push(publishedSequenceId);

			// Create a draft sequence to test enrollment restrictions
			const draftResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Draft Enrollment Test'),
				status: 'draft',
			});
			draftSequenceId = draftResult.data.sequence.id;
			testSequenceIds.push(draftSequenceId);
		});

		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should enroll subscriber in published sequence', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: testSubscriberIds[0],
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(publishedSequenceId);
			expect(result.data.subscriber_id).toBe(testSubscriberIds[0]);
			expect(result.data.enrolled_at).toBeDefined();
		});

		it('should enroll with restart flag false', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: testSubscriberIds[1],
				restart: false,
			});

			expect(result.success).toBe(true);
			expect(result.data.restarted).toBe(false);
		});

		it('should enroll with restart flag true', async () => {
			const subscriberId = testSubscriberIds[0];

			// First enrollment
			await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: subscriberId,
			});

			// Re-enroll with restart
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: subscriberId,
				restart: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.restarted).toBe(true);
		});

		it('should reject enrollment in draft sequence', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: draftSequenceId,
				subscriber_id: testSubscriberIds[0],
			});

			expect(result.success).toBe(false);
		});

		it('should reject enrollment without sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				subscriber_id: testSubscriberIds[0],
			});

			expect(result.success).toBe(false);
		});

		it('should reject enrollment without subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: 999999,
				subscriber_id: testSubscriberIds[0],
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: 0,
				subscriber_id: testSubscriberIds[0],
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid subscriber ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: publishedSequenceId,
				subscriber_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	(isProAvailable ? describe : describe.skip)('Remove Subscriber from Sequence', () => {
		let sequenceId: number;
		let enrolledSubscriberId: number;

		beforeAll(async () => {
			if (!isProAvailable) return;

			// Create a published sequence
			const sequenceResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Removal Test Sequence'),
				status: 'published',
			});
			sequenceId = sequenceResult.data.sequence.id;
			testSequenceIds.push(sequenceId);

			// Enroll a subscriber
			enrolledSubscriberId = testSubscriberIds[0];
			await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: enrolledSubscriberId,
			});
		});

		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should remove subscriber from sequence', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: enrolledSubscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(sequenceId);
			expect(result.data.subscriber_id).toBe(enrolledSubscriberId);
			expect(result.data.unenrolled_at).toBeDefined();
		});

		it('should reject removal without sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				subscriber_id: enrolledSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject removal without subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: 999999,
				subscriber_id: enrolledSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: 0,
				subscriber_id: enrolledSubscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid subscriber ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	(isProAvailable ? describe : describe.skip)('Get Sequence Performance', () => {
		let sequenceId: number;

		beforeAll(async () => {
			if (!isProAvailable) return;

			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Performance Test Sequence'),
				description: 'Sequence for performance testing',
				status: 'published',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should get sequence performance metrics', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(sequenceId);
			expect(result.data.sequence_title).toBeDefined();
		});

		it('should include enrolled count', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('enrolled_count');
			expect(typeof result.data.enrolled_count).toBe('number');
		});

		it('should include active count', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('active_count');
			expect(typeof result.data.active_count).toBe('number');
		});

		it('should include completed count', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('completed_count');
			expect(typeof result.data.completed_count).toBe('number');
		});

		it('should include email count', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('email_count');
			expect(typeof result.data.email_count).toBe('number');
		});

		it('should include email stats array', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('email_stats');
			expect(Array.isArray(result.data.email_stats)).toBe(true);
		});

		it('should handle sequence with no enrollments', async () => {
			const emptyResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Empty Performance Sequence'),
				status: 'published',
			});
			const emptyId = emptyResult.data.sequence.id;
			testSequenceIds.push(emptyId);

			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: emptyId,
			});

			expect(result.success).toBe(true);
			expect(result.data.enrolled_count).toBe(0);
			expect(result.data.active_count).toBe(0);
			expect(result.data.completed_count).toBe(0);
		});

		it('should reject performance request without sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle invalid sequence ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: -1,
			});

			expect(result.success).toBe(false);
		});
	});

	(isProAvailable ? describe : describe.skip)('Edge Cases and Boundary Conditions', () => {
		beforeEach(() => {
			if (!isProAvailable) {
				return;  // Jest will skip tests when Pro not available
			}
		});

		it('should handle sequence with very long description', async () => {
			const longDescription = 'A'.repeat(5000);
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Long Description'),
				description: longDescription,
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle empty settings object', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Empty Settings'),
				settings: {},
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle settings with only one property', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Single Setting'),
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle very long email subject prefix', async () => {
			const longPrefix = '[' + 'X'.repeat(100) + ']';
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Long Prefix'),
				settings: {
					email_subject_prefix: longPrefix,
				},
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle special characters in email subject prefix', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Special Prefix'),
				settings: {
					email_subject_prefix: '[<TEST> & "QUOTES"]',
				},
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle unicode in email subject prefix', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Unicode Prefix'),
				settings: {
					email_subject_prefix: '[🎉 Émojis & ñ]',
				},
			});

			expect(result.success).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should handle rapid status transitions', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Rapid Transitions'),
				status: 'draft',
			});
			const id = createResult.data.sequence.id;
			testSequenceIds.push(id);

			// Draft -> Published
			const published = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: id,
				status: 'published',
			});
			expect(published.success).toBe(true);

			// Published -> Archived
			const archived = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: id,
				status: 'archived',
			});
			expect(archived.success).toBe(true);

			// Archived -> Draft
			const backToDraft = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: id,
				status: 'draft',
			});
			expect(backToDraft.success).toBe(true);
		});

		it('should handle update that clears description', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Clear Description'),
				description: 'Original description',
			});
			const id = createResult.data.sequence.id;
			testSequenceIds.push(id);

			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: id,
				description: '',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.description).toBe('');
		});

		it('should handle simultaneous setting updates', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Simultaneous Settings'),
				settings: {
					email_subject_prefix: '[OLD]',
					allow_re_enrollment: false,
					unsubscribe_on_complete: false,
				},
			});
			const id = createResult.data.sequence.id;
			testSequenceIds.push(id);

			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: id,
				settings: {
					email_subject_prefix: '[NEW]',
					allow_re_enrollment: true,
					unsubscribe_on_complete: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[NEW]');
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
		});
	});
});
