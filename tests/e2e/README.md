# FluentCRM MCP E2E Test Suite

Comprehensive TypeScript/Jest end-to-end tests for FluentCRM MCP tools via WordPress REST API.

## Overview

This test suite provides complete coverage of all FluentCRM MCP tools through actual REST API endpoints, validating the entire integration stack including:
- WordPress MCP adapter
- FluentCRM abilities
- Parameter validation
- Error handling
- Response formatting

## Test Structure

Tests are organized to match the FluentCRM Abilities class structure:

```
tests/e2e/
├── utils/
│   └── mcp-client.ts          # Shared test utilities
├── fluentcrm/
│   └── abilities/
│       ├── subscribers.test.ts        # ~50 tests - Subscriber CRUD & management
│       ├── campaigns.test.ts          # ~65 tests - Campaign lifecycle & operations
│       ├── lists.test.ts              # ~107 tests - List CRUD & statistics
│       ├── tags.test.ts               # ~90 tests - Tag CRUD & bulk operations
│       ├── templates.test.ts          # ~144 tests - Template management
│       ├── sequences.test.ts          # ~80 tests - Sequence automation
│       ├── campaign-analytics.test.ts # ~91 tests - Campaign metrics & tracking
│       ├── funnels.test.ts            # ~100 tests - Funnel automation
│       ├── reporting.test.ts          # ~117 tests - Analytics & reports
│       ├── smart-links.test.ts        # ~99 tests - Smart link tracking
│       └── companies.test.ts          # ~91 tests - Company management
└── jest.config.js
```

**Total: 1000+ comprehensive test cases**

## Setup

```bash
# Install dependencies
npm install

# Ensure WordPress site is accessible at http://mcp.local
# Ensure FluentCRM is active and configured
```

## Running Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run specific test file
npm test -- subscribers.test.ts

# Run specific ability tests
npm test -- fluentcrm/abilities/campaigns.test.ts

# Watch mode for development
npm run test:e2e:watch

# With coverage report
npm run test:e2e:coverage
```

## Test Coverage by Ability

### Subscribers (51 tests)
- ✅ Create with minimal/full profile/custom fields
- ✅ Get by ID/email with relations
- ✅ List with pagination/filtering/search
- ✅ Update profile/status/custom fields
- ✅ Delete with confirmation
- ✅ Bulk import/update/delete
- ✅ List/tag assignment
- ✅ Merge duplicates
- ✅ Advanced search with filters
- ✅ All status enum values tested
- ✅ All parameter variations tested

### Campaigns (65 tests)
- ✅ Create with all parameters
- ✅ List with status/type filters
- ✅ Get with statistics
- ✅ Update draft campaigns
- ✅ Delete with confirmation
- ✅ Duplicate with custom title
- ✅ Schedule/send/pause/resume/cancel
- ✅ Test send to email
- ✅ Preview rendered HTML
- ✅ State transition validation
- ✅ All enum values tested

### Lists (107 tests)
- ✅ Create with slug generation
- ✅ List with pagination/search
- ✅ Get with subscriber counts
- ✅ Update with duplicate validation
- ✅ Delete with subscriber options
- ✅ Get subscribers with filtering
- ✅ Statistics by status
- ✅ Duplicate with subscriber copy
- ✅ Merge multiple lists
- ✅ All boundary conditions tested

### Tags (90 tests)
- ✅ Create with auto-slug
- ✅ List with pagination/search
- ✅ Get with details
- ✅ Update with validation
- ✅ Delete with confirmation
- ✅ Get subscribers with filtering
- ✅ Statistics breakdown
- ✅ Bulk apply/remove tags
- ✅ All status filters tested

### Templates (144 tests)
- ✅ Create with all fields
- ✅ List with pagination/search
- ✅ Get full content
- ✅ Update individual/multiple fields
- ✅ Delete
- ✅ Duplicate with independence
- ✅ Apply to campaign
- ✅ Full lifecycle integration
- ✅ Complex HTML handling

### Sequences (80 tests)
- ✅ Create with settings
- ✅ List with status filtering
- ✅ Get with details
- ✅ Update settings/status
- ✅ Delete with confirmation
- ✅ Add/remove subscribers
- ✅ Restart enrollment
- ✅ Performance metrics
- ✅ Business rule validation

### Campaign Analytics (91 tests)
- ✅ Campaign metrics & rates
- ✅ Contact filtering by status
- ✅ Click tracking data
- ✅ Open tracking data
- ✅ Subject line performance
- ✅ Send time optimization
- ✅ Campaign comparison
- ✅ All status enum values
- ✅ All limit boundaries

### Funnels (100 tests)
- ✅ Create with triggers/conditions
- ✅ List with status filtering
- ✅ Get with sequences
- ✅ Update settings/conditions
- ✅ Delete/duplicate
- ✅ Activate/deactivate
- ✅ Get subscribers with filtering
- ✅ Metrics with date ranges
- ✅ Test conditions against subscribers

### Reporting (117 tests)
- ✅ Dashboard statistics
- ✅ Subscriber growth trends
- ✅ Engagement metrics
- ✅ Revenue attribution
- ✅ List/tag growth
- ✅ Export analytics
- ✅ Campaign comparison
- ✅ Automation performance
- ✅ Lifecycle analysis
- ✅ Email/device/geo stats
- ✅ Unsubscribe reasons
- ✅ Deliverability report

### Smart Links (99 tests)
- ✅ Create with actions
- ✅ List with pagination/search
- ✅ Get with statistics
- ✅ Update actions
- ✅ Delete with confirmation
- ✅ Click tracking with dates
- ✅ Conversion tracking
- ✅ Generate short URLs

### Companies (91 tests)
- ✅ Create with full profile
- ✅ List with pagination/search
- ✅ Get with subscribers
- ✅ Update all fields
- ✅ Delete
- ✅ Add/remove subscribers
- ✅ Get company subscribers
- ✅ All boundary testing

## Configuration

The test suite connects to:
- **Endpoint:** `http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm`
- **Auth:** Basic authentication with WordPress credentials
- **Timeout:** 30 seconds per test

Update credentials in `tests/e2e/utils/mcp-client.ts`:

```typescript
export const TEST_CONFIG = {
	baseURL: 'http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm',
	username: 'admin',
	password: 'your-app-password',
};
```

## Test Utilities

### MCPClient

```typescript
import { MCPClient, TEST_CONFIG } from './utils/mcp-client';

const mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);

// Call a tool
const result = await mcp.callTool('fluentcrm-create-subscriber', {
	email: 'test@example.com'
});

// List all tools
const tools = await mcp.listTools();
```

### Helper Functions

```typescript
import { generateTestEmail, generateTestTitle } from './utils/mcp-client';

const email = generateTestEmail();        // test-1234567890@example.com
const title = generateTestTitle('List');  // List 1234567890
```

## Adding New Tests

1. Create test file matching Ability class structure
2. Import utilities from `../../utils/mcp-client`
3. Test ALL parameters including optional ones
4. Test ALL enum values
5. Test boundary conditions
6. Test error cases (missing required, invalid values)
7. Add cleanup in `afterAll()` hook

Example:

```typescript
import { MCPClient, TEST_CONFIG, generateTestEmail } from '../../utils/mcp-client';

describe('FluentCRM Feature', () => {
	let mcp: MCPClient;
	const testIds: number[] = [];

	beforeAll(() => {
		mcp = new MCPClient(TEST_CONFIG.baseURL, TEST_CONFIG.username, TEST_CONFIG.password);
	});

	afterAll(async () => {
		// Cleanup test data
		if (testIds.length > 0) {
			await mcp.callTool('fluentcrm-bulk-delete-subscribers', {
				subscriber_ids: testIds,
				confirm_delete: true,
			});
		}
	});

	it('should test required parameters', async () => {
		const result = await mcp.callTool('tool-name', { required_param: 'value' });
		expect(result.success).toBe(true);
		testIds.push(result.data.id);
	});

	it('should test optional parameters', async () => {
		const result = await mcp.callTool('tool-name', {
			required_param: 'value',
			optional_param: 'test',
		});
		expect(result.success).toBe(true);
	});

	it('should test all enum values', async () => {
		const enumValues = ['value1', 'value2', 'value3'];
		for (const value of enumValues) {
			const result = await mcp.callTool('tool-name', { enum_param: value });
			expect(result.success).toBe(true);
		}
	});

	it('should reject invalid values', async () => {
		const result = await mcp.callTool('tool-name', { param: 'invalid' });
		expect(result.success).toBe(false);
	});
});
```

## CI/CD Integration

```yaml
# .github/workflows/e2e-tests.yml
name: E2E Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - run: npm ci
      - run: npm run test:e2e
```

## Troubleshooting

**Tests failing with "Cannot read properties of undefined":**
- Check WordPress REST API is accessible
- Verify FluentCRM plugin is active
- Confirm authentication credentials are correct
- Verify response.data.structuredContent structure

**500 errors:**
- Check WordPress error logs
- Verify FluentCRM database tables exist
- Ensure required FluentCRM settings are configured
- Check for database schema issues (e.g., missing columns)

**Timeout errors:**
- Increase timeout in jest.config.js (default: 30000ms)
- Check WordPress site performance
- Verify network connectivity to test site
- Consider running fewer tests in parallel

**Import path errors:**
- Ensure utils path is correct: `../../utils/mcp-client` from abilities tests
- Check TypeScript compilation with `npx tsc --noEmit`

## Coverage Goals

- ✅ **100% tool coverage** - Every registered tool has tests
- ✅ **100% parameter coverage** - Every parameter tested with valid/invalid values
- ✅ **100% enum coverage** - All enum values tested
- ✅ **Boundary testing** - Min/max values for all numeric parameters
- ✅ **Error handling** - Missing required, invalid types, non-existent resources
- ✅ **Edge cases** - Empty arrays, special characters, unicode, long strings
