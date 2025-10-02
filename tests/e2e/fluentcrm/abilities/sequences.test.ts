/**
 * E2E Tests for FluentCRM Sequences Abilities
 *
 * Tests all sequence management tools including CRUD operations,
 * subscriber enrollment, and performance analytics.
 *
 * Note: FluentCRM Pro (FluentCampaign) is required for sequences.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Sequences', () => {
	let mcp: MCPClient;
	const testSequenceIds: number[] = [];
	const testSubscriberIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test sequences
		if (testSequenceIds.length > 0) {
			for (const id of testSequenceIds) {
				await mcp.callTool('fluentcrm-delete-sequence', {
					sequence_id: id,
					confirm_delete: true,
				});
			}
		}

		// Cleanup test subscribers
		if (testSubscriberIds.length > 0) {
			await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: testSubscriberIds,
				confirm_delete: true,
			});
		}
	});

	describe('Create Sequence', () => {
		it('should create sequence with minimal required fields', async () => {
			const title = generateTestTitle('Test Sequence');
			const result = await mcp.callTool('fluentcrm-create-sequence', { title });

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toBe(title);
			expect(result.data.sequence).toHaveProperty('id');
			expect(result.data.sequence.status).toBe('draft'); // Default status

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with full configuration', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Full Config Sequence'),
				description: 'This is a test sequence with full configuration',
				status: 'published',
				settings: {
					email_subject_prefix: '[Newsletter]',
					unsubscribe_on_complete: true,
					allow_re_enrollment: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toContain('Full Config Sequence');
			expect(result.data.sequence.description).toBe('This is a test sequence with full configuration');
			expect(result.data.sequence.status).toBe('published');
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[Newsletter]');
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(false);

			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with each status value', async () => {
			const statuses = ['draft', 'published', 'archived'];

			for (const status of statuses) {
				const result = await mcp.callTool('fluentcrm-create-sequence', {
					title: generateTestTitle(`Sequence ${status}`),
					status,
				});

				expect(result.success).toBe(true);
				expect(result.data.sequence.status).toBe(status);
				testSequenceIds.push(result.data.sequence.id);
			}
		});

		it('should create sequence with only title and description', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Minimal Sequence'),
				description: 'Just title and description',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.description).toBe('Just title and description');
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with individual settings', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Settings Test'),
				settings: {
					email_subject_prefix: '[Test]',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[Test]');
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with unsubscribe_on_complete=false', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('No Unsubscribe'),
				settings: {
					unsubscribe_on_complete: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(false);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should create sequence with allow_re_enrollment=true', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Re-enrollment Allowed'),
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
			testSequenceIds.push(result.data.sequence.id);
		});

		it('should reject missing required title', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				description: 'No title provided',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Invalid Status'),
				status: 'invalid_status',
			});

			expect(result.success).toBe(false);
		});

		it('should reject empty title', async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: '',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('List Sequences', () => {
		beforeAll(async () => {
			// Create test sequences for list filtering
			await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('List Test Draft'),
				status: 'draft',
			});
			await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('List Test Published'),
				status: 'published',
			});
			await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('List Test Archived'),
				status: 'archived',
			});
		});

		it('should list all sequences with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('sequences');
			expect(result.data).toHaveProperty('total');
			expect(result.data).toHaveProperty('page');
			expect(result.data).toHaveProperty('per_page');
			expect(result.data).toHaveProperty('total_pages');
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
			result.data.sequences.forEach((seq: any) => {
				expect(seq.status).toBe('draft');
			});
		});

		it('should filter by published status', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'published',
			});

			expect(result.success).toBe(true);
			result.data.sequences.forEach((seq: any) => {
				expect(seq.status).toBe('published');
			});
		});

		it('should filter by archived status', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'archived',
			});

			expect(result.success).toBe(true);
			result.data.sequences.forEach((seq: any) => {
				expect(seq.status).toBe('archived');
			});
		});

		it('should search sequences by title', async () => {
			const uniqueTitle = generateTestTitle('SearchableSequence');
			const created = await mcp.callTool('fluentcrm-create-sequence', {
				title: uniqueTitle,
			});
			testSequenceIds.push(created.data.sequence.id);

			const result = await mcp.callTool('fluentcrm-list-sequences', {
				search: uniqueTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequences.some((s: any) => s.title === uniqueTitle)).toBe(true);
		});

		it('should handle pagination boundaries', async () => {
			const minResult = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 1,
			});
			expect(minResult.success).toBe(true);

			const maxResult = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 100,
			});
			expect(maxResult.success).toBe(true);
		});

		it('should reject per_page above maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject page below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should combine status filter and search', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				status: 'published',
				search: 'Test',
			});

			expect(result.success).toBe(true);
		});

		it('should combine all optional parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-sequences', {
				page: 1,
				per_page: 10,
				status: 'draft',
				search: 'Test',
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Get Sequence', () => {
		let sequenceId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Get Test Sequence'),
				description: 'Test sequence for retrieval',
				status: 'published',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		it('should get sequence by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.id).toBe(sequenceId);
			expect(result.data.sequence).toHaveProperty('title');
			expect(result.data.sequence).toHaveProperty('description');
			expect(result.data.sequence).toHaveProperty('status');
			expect(result.data.sequence).toHaveProperty('settings');
			expect(result.data.sequence).toHaveProperty('created_at');
			expect(result.data.sequence).toHaveProperty('updated_at');
			expect(result.data.sequence).toHaveProperty('emails_count');
			expect(result.data.sequence).toHaveProperty('emails');
		});

		it('should include emails array', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(Array.isArray(result.data.sequence.emails)).toBe(true);
		});

		it('should reject invalid sequence ID (negative)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid sequence ID (zero)', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {
				sequence_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Sequence', () => {
		let sequenceId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Update Test'),
				description: 'Original description',
				status: 'draft',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		it('should update sequence title', async () => {
			const newTitle = generateTestTitle('Updated Title');
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				title: newTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toBe(newTitle);
		});

		it('should update sequence description', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				description: 'Updated description',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.description).toBe('Updated description');
		});

		it('should update status to published', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'published',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('published');
		});

		it('should update status to archived', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'archived',
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.status).toBe('archived');
		});

		it('should update settings email_subject_prefix', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					email_subject_prefix: '[Updated]',
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[Updated]');
		});

		it('should update settings unsubscribe_on_complete', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					unsubscribe_on_complete: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
		});

		it('should update settings allow_re_enrollment', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
		});

		it('should update multiple fields at once', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				title: generateTestTitle('Multi Update'),
				description: 'Multi-field update test',
				status: 'published',
				settings: {
					email_subject_prefix: '[Multi]',
					unsubscribe_on_complete: false,
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.title).toContain('Multi Update');
			expect(result.data.sequence.description).toBe('Multi-field update test');
			expect(result.data.sequence.status).toBe('published');
		});

		it('should update all settings together', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				settings: {
					email_subject_prefix: '[All]',
					unsubscribe_on_complete: true,
					allow_re_enrollment: false,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.email_subject_prefix).toBe('[All]');
			expect(result.data.sequence.settings.unsubscribe_on_complete).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(false);
		});

		it('should reject invalid sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: -1,
				title: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				title: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid status value', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: sequenceId,
				status: 'invalid',
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: 999999,
				title: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should preserve existing settings when updating partial settings', async () => {
			// Create with initial settings
			const created = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Preserve Settings Test'),
				settings: {
					email_subject_prefix: '[Original]',
					unsubscribe_on_complete: true,
				},
			});
			testSequenceIds.push(created.data.sequence.id);

			// Update only one setting
			const result = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: created.data.sequence.id,
				settings: {
					allow_re_enrollment: true,
				},
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence.settings.allow_re_enrollment).toBe(true);
		});
	});

	describe('Delete Sequence', () => {
		it('should delete sequence with confirmation', async () => {
			const created = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('To Delete'),
			});
			const id = created.data.sequence.id;

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(id);
			expect(result.data).toHaveProperty('sequence_title');
			expect(result.data).toHaveProperty('deleted_at');
		});

		it('should reject delete without confirmation', async () => {
			const created = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('No Delete'),
			});
			const id = created.data.sequence.id;
			testSequenceIds.push(id);

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: id,
				confirm_delete: false,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete with missing confirm_delete', async () => {
			const created = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Missing Confirm'),
			});
			testSequenceIds.push(created.data.sequence.id);

			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: created.data.sequence.id,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: -1,
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});

		it('should reject zero sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				sequence_id: 0,
				confirm_delete: true,
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

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-sequence', {
				confirm_delete: true,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Add Subscriber to Sequence', () => {
		let sequenceId: number;
		let subscriberId: number;

		beforeAll(async () => {
			// Create published sequence
			const seqResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Enrollment Test'),
				status: 'published', // Must be published to enroll
			});
			sequenceId = seqResult.data.sequence.id;
			testSequenceIds.push(sequenceId);

			// Create subscriber
			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
				status: 'subscribed',
			});
			subscriberId = subResult.data.subscriber.id;
			testSubscriberIds.push(subscriberId);
		});

		it('should enroll subscriber in sequence', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(sequenceId);
			expect(result.data.subscriber_id).toBe(subscriberId);
			expect(result.data).toHaveProperty('enrolled_at');
			expect(result.data.restarted).toBe(false);
		});

		it('should enroll with restart=false', async () => {
			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			testSubscriberIds.push(subResult.data.subscriber.id);

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: subResult.data.subscriber.id,
				restart: false,
			});

			expect(result.success).toBe(true);
			expect(result.data.restarted).toBe(false);
		});

		it('should enroll with restart=true', async () => {
			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			testSubscriberIds.push(subResult.data.subscriber.id);

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: subResult.data.subscriber.id,
				restart: true,
			});

			expect(result.success).toBe(true);
			expect(result.data.restarted).toBe(true);
		});

		it('should reject invalid sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: -1,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: 999999,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject enrollment in draft sequence', async () => {
			const draftSeq = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Draft Sequence'),
				status: 'draft',
			});
			testSequenceIds.push(draftSeq.data.sequence.id);

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: draftSeq.data.sequence.id,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject enrollment in archived sequence', async () => {
			const archivedSeq = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Archived Sequence'),
				status: 'archived',
			});
			testSequenceIds.push(archivedSeq.data.sequence.id);

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: archivedSeq.data.sequence.id,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Remove Subscriber from Sequence', () => {
		let sequenceId: number;
		let subscriberId: number;

		beforeAll(async () => {
			// Create published sequence
			const seqResult = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Unenrollment Test'),
				status: 'published',
			});
			sequenceId = seqResult.data.sequence.id;
			testSequenceIds.push(sequenceId);

			// Create and enroll subscriber
			const subResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId = subResult.data.subscriber.id;
			testSubscriberIds.push(subscriberId);

			await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: sequenceId,
				subscriber_id: subscriberId,
			});
		});

		it('should remove subscriber from sequence', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(sequenceId);
			expect(result.data.subscriber_id).toBe(subscriberId);
			expect(result.data).toHaveProperty('unenrolled_at');
		});

		it('should reject invalid sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: -1,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject zero sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: 0,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject zero subscriber ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
				subscriber_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: 999999,
				subscriber_id: subscriberId,
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

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Get Sequence Performance', () => {
		let sequenceId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Performance Test'),
				status: 'published',
			});
			sequenceId = result.data.sequence.id;
			testSequenceIds.push(sequenceId);
		});

		it('should get sequence performance metrics', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequenceId,
			});

			expect(result.success).toBe(true);
			expect(result.data.sequence_id).toBe(sequenceId);
			expect(result.data).toHaveProperty('sequence_title');
			expect(result.data).toHaveProperty('enrolled_count');
			expect(result.data).toHaveProperty('active_count');
			expect(result.data).toHaveProperty('completed_count');
			expect(result.data).toHaveProperty('email_count');
			expect(result.data).toHaveProperty('email_stats');
			expect(Array.isArray(result.data.email_stats)).toBe(true);
		});

		it('should show zero counts for new sequence', async () => {
			const newSeq = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('New Performance Test'),
			});
			testSequenceIds.push(newSeq.data.sequence.id);

			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: newSeq.data.sequence.id,
			});

			expect(result.success).toBe(true);
			expect(result.data.enrolled_count).toBe(0);
			expect(result.data.active_count).toBe(0);
			expect(result.data.completed_count).toBe(0);
		});

		it('should reject invalid sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject zero sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent sequence ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing sequence_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-sequence-performance', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Integration: Full Sequence Workflow', () => {
		it('should complete full sequence lifecycle', async () => {
			// 1. Create sequence
			const sequence = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Full Workflow'),
				description: 'Complete workflow test',
				status: 'draft',
				settings: {
					email_subject_prefix: '[Workflow]',
					allow_re_enrollment: true,
				},
			});
			expect(sequence.success).toBe(true);
			const seqId = sequence.data.sequence.id;
			testSequenceIds.push(seqId);

			// 2. Update to published
			const updated = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: seqId,
				status: 'published',
			});
			expect(updated.success).toBe(true);

			// 3. Create subscriber
			const subscriber = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			const subId = subscriber.data.subscriber.id;
			testSubscriberIds.push(subId);

			// 4. Enroll subscriber
			const enrolled = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
				sequence_id: seqId,
				subscriber_id: subId,
			});
			expect(enrolled.success).toBe(true);

			// 5. Check performance
			const performance = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: seqId,
			});
			expect(performance.success).toBe(true);
			expect(performance.data.enrolled_count).toBeGreaterThanOrEqual(1);

			// 6. Unenroll subscriber
			const unenrolled = await mcp.callTool('fluentcrm-remove-subscriber-from-sequence', {
				sequence_id: seqId,
				subscriber_id: subId,
			});
			expect(unenrolled.success).toBe(true);

			// 7. Archive sequence
			const archived = await mcp.callTool('fluentcrm-update-sequence', {
				sequence_id: seqId,
				status: 'archived',
			});
			expect(archived.success).toBe(true);
		});

		it('should handle multiple subscribers in sequence', async () => {
			// Create sequence
			const sequence = await mcp.callTool('fluentcrm-create-sequence', {
				title: generateTestTitle('Multi Subscriber'),
				status: 'published',
			});
			testSequenceIds.push(sequence.data.sequence.id);

			// Create and enroll multiple subscribers
			const subIds = [];
			for (let i = 0; i < 3; i++) {
				const sub = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
				});
				subIds.push(sub.data.subscriber.id);
				testSubscriberIds.push(sub.data.subscriber.id);

				const enrolled = await mcp.callTool('fluentcrm-add-subscriber-to-sequence', {
					sequence_id: sequence.data.sequence.id,
					subscriber_id: sub.data.subscriber.id,
				});
				expect(enrolled.success).toBe(true);
			}

			// Check performance
			const performance = await mcp.callTool('fluentcrm-get-sequence-performance', {
				sequence_id: sequence.data.sequence.id,
			});
			expect(performance.success).toBe(true);
			expect(performance.data.enrolled_count).toBeGreaterThanOrEqual(3);
		});
	});
});
