/**
 * E2E Tests for FluentCRM Templates Abilities
 *
 * Tests all template management tools including CRUD operations,
 * duplication, campaign integration, and parameter variations.
 */

import { MCPClient, TEST_CONFIG, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Templates', () => {
	let mcp: MCPClient;
	const testTemplateIds: number[] = [];
	const testCampaignIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test templates
		for (const templateId of testTemplateIds) {
			await mcp.callTool('fluentcrm-delete-template', {
				template_id: templateId,
			});
		}

		// Cleanup test campaigns (if any were created)
		for (const campaignId of testCampaignIds) {
			try {
				await mcp.callTool('fluentcrm-delete-campaign', {
					campaign_id: campaignId,
				});
			} catch (error) {
				// Campaign may not exist or may not have delete tool
			}
		}
	});

	describe('Create Template', () => {
		it('should create template with minimal required fields', async () => {
			const title = generateTestTitle('Minimal Template');
			const content = '<p>Test email content</p>';

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: content,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('template_id');
			expect(result.data.template.title).toBe(title);

			testTemplateIds.push(result.data.template_id);
		});

		it('should create template with email subject', async () => {
			const title = generateTestTitle('Template with Subject');
			const subject = 'Welcome to our newsletter!';

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: '<p>Content</p>',
				email_subject: subject,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_subject).toBe(subject);

			testTemplateIds.push(result.data.template_id);
		});

		it('should create template with email pre-header', async () => {
			const title = generateTestTitle('Template with Pre-header');
			const preHeader = 'Preview text for email client';

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: '<p>Content</p>',
				email_pre_header: preHeader,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_pre_header).toBe(preHeader);

			testTemplateIds.push(result.data.template_id);
		});

		it('should create template with all optional fields', async () => {
			const title = generateTestTitle('Full Template');
			const content = '<h1>{{contact.first_name}}</h1><p>Email body content</p>';
			const subject = 'Hello {{contact.first_name}}!';
			const preHeader = 'See what\'s new this month';
			const config = {
				backgroundColor: '#ffffff',
				textColor: '#333333',
				linkColor: '#0073aa',
			};

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: content,
				email_subject: subject,
				email_pre_header: preHeader,
				template_config: config,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.title).toBe(title);
			expect(result.data.template.email_subject).toBe(subject);
			expect(result.data.template.email_pre_header).toBe(preHeader);
			expect(result.data.template).toHaveProperty('template_config');

			testTemplateIds.push(result.data.template_id);
		});

		it('should create template with complex HTML content', async () => {
			const title = generateTestTitle('Complex HTML Template');
			const content = `
				<!DOCTYPE html>
				<html>
				<head><style>body { font-family: Arial; }</style></head>
				<body>
					<table width="100%">
						<tr><td>Header</td></tr>
						<tr><td>{{contact.email}}</td></tr>
						<tr><td><a href="{{unsubscribe_url}}">Unsubscribe</a></td></tr>
					</table>
				</body>
				</html>
			`;

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: content,
			});

			expect(result.success).toBe(true);

			testTemplateIds.push(result.data.template_id);
		});

		it('should create template with template config object', async () => {
			const title = generateTestTitle('Template with Config');
			const config = {
				layout: 'two-column',
				maxWidth: '600px',
				customCss: '.header { padding: 20px; }',
				socialLinks: {
					facebook: 'https://facebook.com/example',
					twitter: 'https://twitter.com/example',
				},
			};

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: '<p>Content</p>',
				template_config: config,
			});

			expect(result.success).toBe(true);
			expect(result.data.template).toHaveProperty('template_config');

			testTemplateIds.push(result.data.template_id);
		});

		it('should reject create without required post_title', async () => {
			const result = await mcp.callTool('fluentcrm-create-template', {
				post_content: '<p>Content</p>',
			});

			expect(result.success).toBe(false);
		});

		it('should reject create without required post_content', async () => {
			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: 'Title Only',
			});

			expect(result.success).toBe(false);
		});

		it('should handle empty string for optional fields', async () => {
			const title = generateTestTitle('Empty Optionals');

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: '<p>Content</p>',
				email_subject: '',
				email_pre_header: '',
			});

			expect(result.success).toBe(true);

			testTemplateIds.push(result.data.template_id);
		});

		it('should handle special characters in title', async () => {
			const title = generateTestTitle('Special & Chars "Test" <Template>');

			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: title,
				post_content: '<p>Content</p>',
			});

			expect(result.success).toBe(true);

			testTemplateIds.push(result.data.template_id);
		});
	});

	describe('List Templates', () => {
		beforeAll(async () => {
			// Create some templates for listing tests
			for (let i = 0; i < 5; i++) {
				const result = await mcp.callTool('fluentcrm-create-template', {
					post_title: generateTestTitle(`List Test Template ${i}`),
					post_content: `<p>Content ${i}</p>`,
				});
				if (result.success) {
					testTemplateIds.push(result.data.template_id);
				}
			}
		});

		it('should list templates with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('templates');
			expect(result.data).toHaveProperty('pagination');
			expect(Array.isArray(result.data.templates)).toBe(true);
		});

		it('should list templates with custom per_page', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 3,
			});

			expect(result.success).toBe(true);
			expect(result.data.templates.length).toBeLessThanOrEqual(3);
			expect(result.data.pagination.per_page).toBe(3);
		});

		it('should list templates with page parameter', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				page: 2,
				per_page: 5,
			});

			expect(result.success).toBe(true);
			expect(result.data.pagination.current_page).toBe(2);
		});

		it('should search templates by title', async () => {
			const uniqueTitle = generateTestTitle('Searchable Template');
			const createResult = await mcp.callTool('fluentcrm-create-template', {
				post_title: uniqueTitle,
				post_content: '<p>Content</p>',
			});
			testTemplateIds.push(createResult.data.template_id);

			const searchResult = await mcp.callTool('fluentcrm-list-templates', {
				search: 'Searchable',
			});

			expect(searchResult.success).toBe(true);
			const found = searchResult.data.templates.some(
				(t: any) => t.title === uniqueTitle
			);
			expect(found).toBe(true);
		});

		it('should handle minimum per_page boundary (1)', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.templates.length).toBeLessThanOrEqual(1);
		});

		it('should handle maximum per_page boundary (100)', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.pagination.per_page).toBe(100);
		});

		it('should reject per_page below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject per_page above maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject page below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle search with no matches', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				search: 'NonExistentTemplateSearch123456789',
			});

			expect(result.success).toBe(true);
			expect(result.data.templates.length).toBe(0);
			expect(result.data.pagination.total).toBe(0);
		});

		it('should return pagination metadata correctly', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				page: 1,
				per_page: 10,
			});

			expect(result.success).toBe(true);
			expect(result.data.pagination).toHaveProperty('total');
			expect(result.data.pagination).toHaveProperty('per_page');
			expect(result.data.pagination).toHaveProperty('current_page');
			expect(result.data.pagination).toHaveProperty('total_pages');
		});

		it('should include template excerpt in list view', async () => {
			const result = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 1,
			});

			expect(result.success).toBe(true);
			if (result.data.templates.length > 0) {
				expect(result.data.templates[0]).toHaveProperty('excerpt');
			}
		});
	});

	describe('Get Template', () => {
		let templateId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Get Test Template'),
				post_content: '<h1>Test Content</h1><p>Body text</p>',
				email_subject: 'Test Subject',
				email_pre_header: 'Test Pre-header',
			});
			templateId = result.data.template_id;
			testTemplateIds.push(templateId);
		});

		it('should get template by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.id).toBe(templateId);
		});

		it('should include full content in get template', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.template).toHaveProperty('content');
			expect(result.data.template.content).toContain('<h1>Test Content</h1>');
		});

		it('should include email_subject if set', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_subject).toBe('Test Subject');
		});

		it('should include email_pre_header if set', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_pre_header).toBe('Test Pre-header');
		});

		it('should handle non-existent template ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid template ID (0)', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative template ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing required template_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {});

			expect(result.success).toBe(false);
		});

		it('should include timestamps in response', async () => {
			const result = await mcp.callTool('fluentcrm-get-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.template).toHaveProperty('created_at');
			expect(result.data.template).toHaveProperty('updated_at');
		});
	});

	describe('Update Template', () => {
		let templateId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Update Test'),
				post_content: '<p>Original content</p>',
				email_subject: 'Original Subject',
			});
			templateId = result.data.template_id;
			testTemplateIds.push(templateId);
		});

		it('should update template title only', async () => {
			const newTitle = generateTestTitle('Updated Title');

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				post_title: newTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.title).toBe(newTitle);
		});

		it('should update template content only', async () => {
			const newContent = '<h1>Updated Content</h1><p>New body</p>';

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				post_content: newContent,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.content).toContain('Updated Content');
		});

		it('should update email subject only', async () => {
			const newSubject = 'Updated Email Subject';

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				email_subject: newSubject,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_subject).toBe(newSubject);
		});

		it('should update email pre-header only', async () => {
			const newPreHeader = 'Updated pre-header text';

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				email_pre_header: newPreHeader,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.email_pre_header).toBe(newPreHeader);
		});

		it('should update template config only', async () => {
			const newConfig = {
				theme: 'dark',
				fontSize: '16px',
			};

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				template_config: newConfig,
			});

			expect(result.success).toBe(true);
			expect(result.data.template).toHaveProperty('template_config');
		});

		it('should update multiple fields at once', async () => {
			const newTitle = generateTestTitle('Multi Update');
			const newContent = '<p>Multi updated content</p>';
			const newSubject = 'Multi Updated Subject';

			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				post_title: newTitle,
				post_content: newContent,
				email_subject: newSubject,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.title).toBe(newTitle);
			expect(result.data.template.email_subject).toBe(newSubject);
		});

		it('should update all fields at once', async () => {
			const updates = {
				template_id: templateId,
				post_title: generateTestTitle('All Fields'),
				post_content: '<p>All fields updated</p>',
				email_subject: 'All Fields Subject',
				email_pre_header: 'All Fields Pre-header',
				template_config: { allFields: true },
			};

			const result = await mcp.callTool('fluentcrm-update-template', updates);

			expect(result.success).toBe(true);
			expect(result.data.template.title).toBe(updates.post_title);
			expect(result.data.template.email_subject).toBe(updates.email_subject);
			expect(result.data.template.email_pre_header).toBe(updates.email_pre_header);
		});

		it('should handle empty string updates', async () => {
			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
				email_subject: '',
			});

			expect(result.success).toBe(true);
		});

		it('should reject update without required template_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-template', {
				post_title: 'New Title',
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent template ID', async () => {
			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: 999999,
				post_title: 'Updated',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid template ID (0)', async () => {
			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: 0,
				post_title: 'Updated',
			});

			expect(result.success).toBe(false);
		});

		it('should handle update with no field changes', async () => {
			const result = await mcp.callTool('fluentcrm-update-template', {
				template_id: templateId,
			});

			expect(result.success).toBe(true);
		});
	});

	describe('Delete Template', () => {
		it('should delete template successfully', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Delete Test'),
				post_content: '<p>To be deleted</p>',
			});
			const id = createResult.data.template_id;

			const result = await mcp.callTool('fluentcrm-delete-template', {
				template_id: id,
			});

			expect(result.success).toBe(true);
			expect(result.data.deleted).toBe(true);
			expect(result.data.template_id).toBe(id);
		});

		it('should verify template no longer exists after delete', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Verify Delete'),
				post_content: '<p>Content</p>',
			});
			const id = createResult.data.template_id;

			await mcp.callTool('fluentcrm-delete-template', {
				template_id: id,
			});

			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: id,
			});

			expect(getResult.success).toBe(false);
		});

		it('should reject delete without template_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-template', {});

			expect(result.success).toBe(false);
		});

		it('should reject delete with invalid template_id (0)', async () => {
			const result = await mcp.callTool('fluentcrm-delete-template', {
				template_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject delete with negative template_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-template', {
				template_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent template ID', async () => {
			const result = await mcp.callTool('fluentcrm-delete-template', {
				template_id: 999999,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Duplicate Template', () => {
		let originalTemplateId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Original Template'),
				post_content: '<h1>Original Content</h1><p>Body</p>',
				email_subject: 'Original Subject',
				email_pre_header: 'Original Pre-header',
				template_config: { color: 'blue' },
			});
			originalTemplateId = result.data.template_id;
			testTemplateIds.push(originalTemplateId);
		});

		it('should duplicate template with default title', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.original_template_id).toBe(originalTemplateId);
			expect(result.data).toHaveProperty('new_template_id');
			expect(result.data.template.title).toContain('Copy of');

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should duplicate template with custom title', async () => {
			const customTitle = generateTestTitle('Custom Duplicate');

			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
				new_title: customTitle,
			});

			expect(result.success).toBe(true);
			expect(result.data.template.title).toBe(customTitle);

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should duplicate template content correctly', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});

			expect(result.success).toBe(true);

			const duplicateId = result.data.new_template_id;
			testTemplateIds.push(duplicateId);

			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: duplicateId,
			});

			expect(getResult.success).toBe(true);
			expect(getResult.data.template.content).toContain('Original Content');
		});

		it('should copy email_subject meta field', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});

			expect(result.success).toBe(true);

			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: result.data.new_template_id,
			});

			expect(getResult.data.template.email_subject).toBe('Original Subject');

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should copy email_pre_header meta field', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});

			expect(result.success).toBe(true);

			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: result.data.new_template_id,
			});

			expect(getResult.data.template.email_pre_header).toBe('Original Pre-header');

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should copy template_config meta field', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});

			expect(result.success).toBe(true);

			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: result.data.new_template_id,
			});

			expect(getResult.data.template).toHaveProperty('template_config');

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should create independent copy (changes do not affect original)', async () => {
			const dupResult = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
			});
			const duplicateId = dupResult.data.new_template_id;
			testTemplateIds.push(duplicateId);

			await mcp.callTool('fluentcrm-update-template', {
				template_id: duplicateId,
				email_subject: 'Modified Duplicate Subject',
			});

			const originalGet = await mcp.callTool('fluentcrm-get-template', {
				template_id: originalTemplateId,
			});

			expect(originalGet.data.template.email_subject).toBe('Original Subject');
		});

		it('should reject duplicate without required template_id', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent template ID', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid template ID (0)', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should handle empty new_title (uses default)', async () => {
			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
				new_title: '',
			});

			expect(result.success).toBe(true);
			expect(result.data.template.title).toContain('Copy of');

			testTemplateIds.push(result.data.new_template_id);
		});

		it('should handle special characters in new_title', async () => {
			const specialTitle = generateTestTitle('Duplicate & "Special" <Chars>');

			const result = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: originalTemplateId,
				new_title: specialTitle,
			});

			expect(result.success).toBe(true);

			testTemplateIds.push(result.data.new_template_id);
		});
	});

	describe('Apply Template to Campaign', () => {
		let templateId: number;
		let campaignId: number;

		beforeAll(async () => {
			// Create template
			const templateResult = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Campaign Template'),
				post_content: '<h1>Campaign Content</h1><p>Email body</p>',
				email_subject: 'Campaign Subject',
				email_pre_header: 'Campaign Pre-header',
			});
			templateId = templateResult.data.template_id;
			testTemplateIds.push(templateId);

			// Create campaign (if create-campaign tool exists)
			try {
				const campaignResult = await mcp.callTool('fluentcrm-create-campaign', {
					title: generateTestTitle('Test Campaign'),
					email_subject: 'Original Campaign Subject',
					email_body: '<p>Original campaign body</p>',
					status: 'draft',
				});

				if (campaignResult.success) {
					campaignId = campaignResult.data.campaign.id;
					testCampaignIds.push(campaignId);
				}
			} catch (error) {
				// Campaign creation may not be available
			}
		});

		it('should apply template to campaign', async () => {
			if (!campaignId) {
				console.log('Skipping: Campaign not created');
				return;
			}

			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: campaignId,
				template_id: templateId,
			});

			expect(result.success).toBe(true);
			expect(result.data.campaign_id).toBe(campaignId);
			expect(result.data.template_id).toBe(templateId);
			expect(result.data.applied).toBe(true);
		});

		it('should reject without required campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				template_id: templateId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject without required template_id', async () => {
			if (!campaignId) {
				console.log('Skipping: Campaign not created');
				return;
			}

			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: campaignId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid campaign_id (0)', async () => {
			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: 0,
				template_id: templateId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid template_id (0)', async () => {
			if (!campaignId) {
				console.log('Skipping: Campaign not created');
				return;
			}

			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: campaignId,
				template_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: -1,
				template_id: templateId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative template_id', async () => {
			if (!campaignId) {
				console.log('Skipping: Campaign not created');
				return;
			}

			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: campaignId,
				template_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent campaign_id', async () => {
			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: 999999,
				template_id: templateId,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent template_id', async () => {
			if (!campaignId) {
				console.log('Skipping: Campaign not created');
				return;
			}

			const result = await mcp.callTool('fluentcrm-apply-template-to-campaign', {
				campaign_id: campaignId,
				template_id: 999999,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Template CRUD Integration', () => {
		it('should complete full lifecycle: create → get → update → duplicate → delete', async () => {
			// Create
			const createResult = await mcp.callTool('fluentcrm-create-template', {
				post_title: generateTestTitle('Lifecycle Template'),
				post_content: '<p>Original</p>',
			});
			expect(createResult.success).toBe(true);
			const id = createResult.data.template_id;

			// Get
			const getResult = await mcp.callTool('fluentcrm-get-template', {
				template_id: id,
			});
			expect(getResult.success).toBe(true);

			// Update
			const updateResult = await mcp.callTool('fluentcrm-update-template', {
				template_id: id,
				post_title: generateTestTitle('Updated Lifecycle'),
			});
			expect(updateResult.success).toBe(true);

			// Duplicate
			const dupResult = await mcp.callTool('fluentcrm-duplicate-template', {
				template_id: id,
			});
			expect(dupResult.success).toBe(true);
			const dupId = dupResult.data.new_template_id;

			// Delete both
			const deleteOriginal = await mcp.callTool('fluentcrm-delete-template', {
				template_id: id,
			});
			expect(deleteOriginal.success).toBe(true);

			const deleteDuplicate = await mcp.callTool('fluentcrm-delete-template', {
				template_id: dupId,
			});
			expect(deleteDuplicate.success).toBe(true);
		});

		it('should handle multiple templates in list after creating several', async () => {
			const ids = [];

			for (let i = 0; i < 3; i++) {
				const result = await mcp.callTool('fluentcrm-create-template', {
					post_title: generateTestTitle(`Batch ${i}`),
					post_content: `<p>Content ${i}</p>`,
				});
				expect(result.success).toBe(true);
				ids.push(result.data.template_id);
			}

			const listResult = await mcp.callTool('fluentcrm-list-templates', {
				per_page: 100,
			});
			expect(listResult.success).toBe(true);
			expect(listResult.data.pagination.total).toBeGreaterThanOrEqual(3);

			// Cleanup
			for (const id of ids) {
				await mcp.callTool('fluentcrm-delete-template', {
					template_id: id,
				});
			}
		});
	});
});
