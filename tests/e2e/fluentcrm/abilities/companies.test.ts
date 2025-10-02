/**
 * E2E Tests for FluentCRM Companies Abilities
 *
 * Tests all company management tools including CRUD operations,
 * subscriber relationships, and advanced search capabilities.
 */

import { MCPClient, TEST_CONFIG, generateTestEmail, generateTestTitle } from '../../utils/mcp-client';

describe('FluentCRM Companies', () => {
	let mcp: MCPClient;
	const testCompanyIds: number[] = [];
	const testSubscriberIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test companies
		for (const companyId of testCompanyIds) {
			await mcp.callTool('fluentcrm-delete-company', {
				company_id: companyId,
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

	describe('Create Company', () => {
		it('should create company with minimal required fields', async () => {
			const name = generateTestTitle('Test Company');
			const result = await mcp.callTool('fluentcrm-create-company', { name });

			expect(result.success).toBe(true);
			expect(result.data.company.name).toBe(name);
			expect(result.data.company).toHaveProperty('id');

			testCompanyIds.push(result.data.company.id);
		});

		it('should create company with full profile data', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Full Data Company'),
				email: generateTestEmail(),
				phone: '+1-555-0100',
				address_line_1: '456 Business Blvd',
				address_line_2: 'Suite 200',
				city: 'San Francisco',
				state: 'CA',
				postal_code: '94102',
				country: 'US',
				website: 'https://example.com',
				industry: 'Technology',
				description: 'A comprehensive test company with all fields populated',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.name).toContain('Full Data Company');
			expect(result.data.company.email).toMatch(/@example\.com$/);
			expect(result.data.company.phone).toBe('+1-555-0100');
			expect(result.data.company.address_line_1).toBe('456 Business Blvd');
			expect(result.data.company.address_line_2).toBe('Suite 200');
			expect(result.data.company.city).toBe('San Francisco');
			expect(result.data.company.state).toBe('CA');
			expect(result.data.company.postal_code).toBe('94102');
			expect(result.data.company.country).toBe('US');
			expect(result.data.company.website).toBe('https://example.com');
			expect(result.data.company.industry).toBe('Technology');
			expect(result.data.company.description).toBe('A comprehensive test company with all fields populated');

			testCompanyIds.push(result.data.company.id);
		});

		it('should create company with email only', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Email Only Company'),
				email: generateTestEmail(),
			});

			expect(result.success).toBe(true);
			expect(result.data.company).toHaveProperty('email');

			testCompanyIds.push(result.data.company.id);
		});

		it('should create company with website URL', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Website Company'),
				website: 'https://test-company.example.com',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.website).toBe('https://test-company.example.com');

			testCompanyIds.push(result.data.company.id);
		});

		it('should create company with international address', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('International Company'),
				address_line_1: '10 Downing Street',
				city: 'London',
				postal_code: 'SW1A 2AA',
				country: 'GB',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.country).toBe('GB');
			expect(result.data.company.city).toBe('London');

			testCompanyIds.push(result.data.company.id);
		});

		it('should create company with industry classification', async () => {
			const industries = ['Technology', 'Healthcare', 'Finance', 'Education', 'Retail'];

			for (const industry of industries) {
				const result = await mcp.callTool('fluentcrm-create-company', {
					name: generateTestTitle(`${industry} Company`),
					industry,
				});

				expect(result.success).toBe(true);
				expect(result.data.company.industry).toBe(industry);
				testCompanyIds.push(result.data.company.id);
			}
		});

		it('should create company with long description', async () => {
			const longDescription =
				'This is a very detailed company description that spans multiple sentences. ' +
				'It contains information about the company history, mission, and values. ' +
				'This tests that the description field can handle longer text content.';

			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Detailed Company'),
				description: longDescription,
			});

			expect(result.success).toBe(true);
			expect(result.data.company.description).toBe(longDescription);

			testCompanyIds.push(result.data.company.id);
		});

		it('should reject missing required name field', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				email: generateTestEmail(),
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid email format', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Invalid Email Company'),
				email: 'not-a-valid-email',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid website URL format', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Invalid URL Company'),
				website: 'not-a-valid-url',
			});

			expect(result.success).toBe(false);
		});

		it('should handle empty name gracefully', async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: '',
			});

			expect(result.success).toBe(false);
		});
	});

	describe('List Companies', () => {
		beforeAll(async () => {
			// Create test companies for listing
			for (let i = 0; i < 5; i++) {
				const result = await mcp.callTool('fluentcrm-create-company', {
					name: generateTestTitle(`List Test Company ${i}`),
					email: generateTestEmail(),
					industry: i % 2 === 0 ? 'Technology' : 'Healthcare',
				});
				testCompanyIds.push(result.data.company.id);
			}
		});

		it('should list companies with default pagination', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('companies');
			expect(result.data).toHaveProperty('total');
			expect(result.data).toHaveProperty('page');
			expect(result.data).toHaveProperty('per_page');
			expect(result.data).toHaveProperty('total_pages');
			expect(Array.isArray(result.data.companies)).toBe(true);
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				page: 1,
				per_page: 3,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.length).toBeLessThanOrEqual(3);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(3);
		});

		it('should search companies by name', async () => {
			const searchName = generateTestTitle('Searchable Company');
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: searchName,
			});
			testCompanyIds.push(createResult.data.company.id);

			const result = await mcp.callTool('fluentcrm-list-companies', {
				search: searchName,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.some((c: any) => c.name === searchName)).toBe(true);
		});

		it('should search companies by email', async () => {
			const email = generateTestEmail();
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Email Search Company'),
				email,
			});
			testCompanyIds.push(createResult.data.company.id);

			const result = await mcp.callTool('fluentcrm-list-companies', {
				search: email,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.some((c: any) => c.email === email)).toBe(true);
		});

		it('should search companies by partial name', async () => {
			const uniquePrefix = `Partial-${Date.now()}`;
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: `${uniquePrefix} Test Company`,
			});
			testCompanyIds.push(createResult.data.company.id);

			const result = await mcp.callTool('fluentcrm-list-companies', {
				search: uniquePrefix,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.some((c: any) => c.name.includes(uniquePrefix))).toBe(true);
		});

		it('should handle minimum per_page value', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				page: 1,
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.length).toBeLessThanOrEqual(1);
		});

		it('should handle maximum per_page value', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				page: 1,
				per_page: 100,
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.length).toBeLessThanOrEqual(100);
		});

		it('should reject per_page value exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject per_page value below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				per_page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid page number', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should return empty results for non-matching search', async () => {
			const result = await mcp.callTool('fluentcrm-list-companies', {
				search: 'ThisCompanyNameShouldNeverExist123456789',
			});

			expect(result.success).toBe(true);
			expect(result.data.companies.length).toBe(0);
			expect(result.data.total).toBe(0);
		});

		it('should include search_query in response', async () => {
			const searchTerm = 'test search';
			const result = await mcp.callTool('fluentcrm-list-companies', {
				search: searchTerm,
			});

			expect(result.success).toBe(true);
			expect(result.data.search_query).toBe(searchTerm);
		});
	});

	describe('Get Company', () => {
		let companyId: number;

		beforeAll(async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Get Test Company'),
				email: generateTestEmail(),
				phone: '+1-555-9999',
				industry: 'Technology',
			});
			companyId = result.data.company.id;
			testCompanyIds.push(companyId);
		});

		it('should get company by ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company.id).toBe(companyId);
			expect(result.data.company).toHaveProperty('name');
			expect(result.data.company).toHaveProperty('email');
			expect(result.data.company).toHaveProperty('phone');
		});

		it('should include subscribers array', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company).toHaveProperty('subscribers');
			expect(Array.isArray(result.data.company.subscribers)).toBe(true);
		});

		it('should include subscribers_count', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company).toHaveProperty('subscribers_count');
			expect(typeof result.data.company.subscribers_count).toBe('number');
		});

		it('should include all company fields', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company).toHaveProperty('id');
			expect(result.data.company).toHaveProperty('name');
			expect(result.data.company).toHaveProperty('email');
			expect(result.data.company).toHaveProperty('phone');
			expect(result.data.company).toHaveProperty('address_line_1');
			expect(result.data.company).toHaveProperty('address_line_2');
			expect(result.data.company).toHaveProperty('city');
			expect(result.data.company).toHaveProperty('state');
			expect(result.data.company).toHaveProperty('postal_code');
			expect(result.data.company).toHaveProperty('country');
			expect(result.data.company).toHaveProperty('website');
			expect(result.data.company).toHaveProperty('industry');
			expect(result.data.company).toHaveProperty('description');
			expect(result.data.company).toHaveProperty('created_at');
			expect(result.data.company).toHaveProperty('updated_at');
		});

		it('should reject invalid company ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative company ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent company ID', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {
				company_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing company_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-get-company', {});

			expect(result.success).toBe(false);
		});
	});

	describe('Update Company', () => {
		let companyId: number;

		beforeEach(async () => {
			const result = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Update Test Company'),
			});
			companyId = result.data.company.id;
			testCompanyIds.push(companyId);
		});

		it('should update company name', async () => {
			const newName = generateTestTitle('Updated Name');
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				name: newName,
			});

			expect(result.success).toBe(true);
			expect(result.data.company.name).toBe(newName);
		});

		it('should update company email', async () => {
			const newEmail = generateTestEmail();
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				email: newEmail,
			});

			expect(result.success).toBe(true);
			expect(result.data.company.email).toBe(newEmail);
		});

		it('should update company phone', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				phone: '+1-555-1234',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.phone).toBe('+1-555-1234');
		});

		it('should update full address', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				address_line_1: '789 Updated Ave',
				address_line_2: 'Floor 3',
				city: 'Boston',
				state: 'MA',
				postal_code: '02101',
				country: 'US',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.address_line_1).toBe('789 Updated Ave');
			expect(result.data.company.address_line_2).toBe('Floor 3');
			expect(result.data.company.city).toBe('Boston');
			expect(result.data.company.state).toBe('MA');
			expect(result.data.company.postal_code).toBe('02101');
			expect(result.data.company.country).toBe('US');
		});

		it('should update website', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				website: 'https://updated-website.example.com',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.website).toBe('https://updated-website.example.com');
		});

		it('should update industry', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				industry: 'Finance',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.industry).toBe('Finance');
		});

		it('should update description', async () => {
			const newDescription = 'This is an updated company description.';
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				description: newDescription,
			});

			expect(result.success).toBe(true);
			expect(result.data.company.description).toBe(newDescription);
		});

		it('should update multiple fields simultaneously', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				name: generateTestTitle('Multi-Update Company'),
				email: generateTestEmail(),
				phone: '+1-555-5555',
				industry: 'Retail',
			});

			expect(result.success).toBe(true);
			expect(result.data.company.name).toContain('Multi-Update Company');
			expect(result.data.company.phone).toBe('+1-555-5555');
			expect(result.data.company.industry).toBe('Retail');
		});

		it('should clear optional fields when set to empty string', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				phone: '',
				description: '',
			});

			expect(result.success).toBe(true);
		});

		it('should reject update without company_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				name: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid company_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: 0,
				name: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent company_id', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: 999999,
				name: 'Test',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid email format', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				email: 'invalid-email',
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid website URL', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				website: 'not-a-url',
			});

			expect(result.success).toBe(false);
		});

		it('should include updated_at timestamp', async () => {
			const result = await mcp.callTool('fluentcrm-update-company', {
				company_id: companyId,
				name: generateTestTitle('Timestamp Test'),
			});

			expect(result.success).toBe(true);
			expect(result.data.company).toHaveProperty('updated_at');
		});
	});

	describe('Delete Company', () => {
		it('should delete company by ID', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Delete Test Company'),
			});
			const id = createResult.data.company.id;

			const result = await mcp.callTool('fluentcrm-delete-company', {
				company_id: id,
			});

			expect(result.success).toBe(true);
			expect(result.data.company_id).toBe(id);
			expect(result.data).toHaveProperty('company_name');
			expect(result.data).toHaveProperty('deleted_at');
		});

		it('should return company name in response', async () => {
			const companyName = generateTestTitle('Named Delete Test');
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: companyName,
			});
			const id = createResult.data.company.id;

			const result = await mcp.callTool('fluentcrm-delete-company', {
				company_id: id,
			});

			expect(result.success).toBe(true);
			expect(result.data.company_name).toBe(companyName);
		});

		it('should reject invalid company_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-company', {
				company_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject negative company_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-company', {
				company_id: -1,
			});

			expect(result.success).toBe(false);
		});

		it('should handle non-existent company_id', async () => {
			const result = await mcp.callTool('fluentcrm-delete-company', {
				company_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing company_id parameter', async () => {
			const result = await mcp.callTool('fluentcrm-delete-company', {});

			expect(result.success).toBe(false);
		});

		it('should prevent getting deleted company', async () => {
			const createResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Delete Verification Test'),
			});
			const id = createResult.data.company.id;

			await mcp.callTool('fluentcrm-delete-company', {
				company_id: id,
			});

			const getResult = await mcp.callTool('fluentcrm-get-company', {
				company_id: id,
			});

			expect(getResult.success).toBe(false);
		});
	});

	describe('Add Subscriber to Company', () => {
		let companyId: number;
		let subscriberId: number;

		beforeEach(async () => {
			const companyResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Link Test Company'),
			});
			companyId = companyResult.data.company.id;
			testCompanyIds.push(companyId);

			const subscriberResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId = subscriberResult.data.subscriber.id;
			testSubscriberIds.push(subscriberId);
		});

		it('should link subscriber to company', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company_id).toBe(companyId);
			expect(result.data.subscriber_id).toBe(subscriberId);
			expect(result.data.action).toBe('linked');
		});

		it('should include company name in response', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('company_name');
		});

		it('should include subscriber email in response', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('subscriber_email');
		});

		it('should handle already linked subscriber', async () => {
			await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.action).toBe('already_linked');
		});

		it('should reject invalid company_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: 0,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent company', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: 999999,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent subscriber', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing company_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(false);
		});
	});

	describe('Remove Subscriber from Company', () => {
		let companyId: number;
		let subscriberId: number;

		beforeEach(async () => {
			const companyResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Unlink Test Company'),
			});
			companyId = companyResult.data.company.id;
			testCompanyIds.push(companyId);

			const subscriberResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			subscriberId = subscriberResult.data.subscriber.id;
			testSubscriberIds.push(subscriberId);

			await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});
		});

		it('should unlink subscriber from company', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.company_id).toBe(companyId);
			expect(result.data.subscriber_id).toBe(subscriberId);
			expect(result.data.action).toBe('unlinked');
		});

		it('should include subscriber email in response', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('subscriber_email');
		});

		it('should handle subscriber not linked to company', async () => {
			const otherSubscriberResult = await mcp.callTool('fluentcrm-create-subscriber', {
				email: generateTestEmail(),
			});
			const otherSubscriberId = otherSubscriberResult.data.subscriber.id;
			testSubscriberIds.push(otherSubscriberId);

			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: otherSubscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.action).toBe('not_linked');
		});

		it('should reject invalid company_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: 0,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent subscriber', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing company_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing subscriber_id', async () => {
			const result = await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
			});

			expect(result.success).toBe(false);
		});

		it('should allow re-linking after unlinking', async () => {
			await mcp.callTool('fluentcrm-remove-subscriber-from-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			const result = await mcp.callTool('fluentcrm-add-subscriber-to-company', {
				company_id: companyId,
				subscriber_id: subscriberId,
			});

			expect(result.success).toBe(true);
			expect(result.data.action).toBe('linked');
		});
	});

	describe('Get Company Subscribers', () => {
		let companyId: number;
		const subscriberIds: number[] = [];

		beforeAll(async () => {
			const companyResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Subscribers List Company'),
			});
			companyId = companyResult.data.company.id;
			testCompanyIds.push(companyId);

			// Create and link multiple subscribers
			for (let i = 0; i < 5; i++) {
				const subscriberResult = await mcp.callTool('fluentcrm-create-subscriber', {
					email: generateTestEmail(),
					first_name: `Subscriber${i}`,
					last_name: `Test`,
				});
				const subId = subscriberResult.data.subscriber.id;
				subscriberIds.push(subId);
				testSubscriberIds.push(subId);

				await mcp.callTool('fluentcrm-add-subscriber-to-company', {
					company_id: companyId,
					subscriber_id: subId,
				});
			}
		});

		it('should get all company subscribers', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('subscribers');
			expect(result.data).toHaveProperty('total');
			expect(result.data).toHaveProperty('company_id');
			expect(result.data).toHaveProperty('company_name');
			expect(Array.isArray(result.data.subscribers)).toBe(true);
			expect(result.data.total).toBeGreaterThanOrEqual(5);
		});

		it('should include pagination metadata', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			expect(result.data).toHaveProperty('page');
			expect(result.data).toHaveProperty('per_page');
			expect(result.data).toHaveProperty('total_pages');
		});

		it('should respect pagination parameters', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				page: 1,
				per_page: 2,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscribers.length).toBeLessThanOrEqual(2);
			expect(result.data.page).toBe(1);
			expect(result.data.per_page).toBe(2);
		});

		it('should include subscriber details', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
			});

			expect(result.success).toBe(true);
			if (result.data.subscribers.length > 0) {
				const subscriber = result.data.subscribers[0];
				expect(subscriber).toHaveProperty('id');
				expect(subscriber).toHaveProperty('email');
				expect(subscriber).toHaveProperty('first_name');
				expect(subscriber).toHaveProperty('last_name');
				expect(subscriber).toHaveProperty('full_name');
				expect(subscriber).toHaveProperty('status');
				expect(subscriber).toHaveProperty('created_at');
			}
		});

		it('should handle minimum per_page value', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				per_page: 1,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscribers.length).toBeLessThanOrEqual(1);
		});

		it('should handle maximum per_page value', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				per_page: 100,
			});

			expect(result.success).toBe(true);
		});

		it('should reject per_page exceeding maximum', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				per_page: 101,
			});

			expect(result.success).toBe(false);
		});

		it('should reject per_page below minimum', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				per_page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid page number', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				page: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject invalid company_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: 0,
			});

			expect(result.success).toBe(false);
		});

		it('should reject non-existent company', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: 999999,
			});

			expect(result.success).toBe(false);
		});

		it('should reject missing company_id', async () => {
			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {});

			expect(result.success).toBe(false);
		});

		it('should handle company with no subscribers', async () => {
			const emptyCompanyResult = await mcp.callTool('fluentcrm-create-company', {
				name: generateTestTitle('Empty Company'),
			});
			testCompanyIds.push(emptyCompanyResult.data.company.id);

			const result = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: emptyCompanyResult.data.company.id,
			});

			expect(result.success).toBe(true);
			expect(result.data.subscribers).toEqual([]);
			expect(result.data.total).toBe(0);
		});

		it('should navigate through pages correctly', async () => {
			const page1 = await mcp.callTool('fluentcrm-get-company-subscribers', {
				company_id: companyId,
				page: 1,
				per_page: 2,
			});

			expect(page1.success).toBe(true);

			if (page1.data.total_pages > 1) {
				const page2 = await mcp.callTool('fluentcrm-get-company-subscribers', {
					company_id: companyId,
					page: 2,
					per_page: 2,
				});

				expect(page2.success).toBe(true);
				expect(page2.data.page).toBe(2);
			}
		});
	});
});
