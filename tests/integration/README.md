# FluentCRM MCP Integration Tests

PHPUnit integration tests for FluentCRM MCP tools.

## Setup

Ensure WordPress test environment is configured:

```bash
# Install WordPress test suite (if not already done)
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Or for Local by Flywheel:
bash bin/install-wp-tests.sh wordpress_test root root localhost:10012 latest
```

## Running Tests

```bash
# Run all integration tests
vendor/bin/phpunit tests/integration/

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/ tests/integration/

# Run specific test
vendor/bin/phpunit --filter test_create_subscriber tests/integration/

# Run with verbose output
vendor/bin/phpunit -v tests/integration/
```

## Test Coverage

✅ Subscriber CRUD operations
✅ Campaign management
✅ Lists and tags
✅ Bulk operations
✅ Analytics and reporting
✅ Parameter validation
✅ Error handling

## Requirements

- WordPress test suite installed
- FluentCRM plugin active in test environment
- PHPUnit 9.5+
