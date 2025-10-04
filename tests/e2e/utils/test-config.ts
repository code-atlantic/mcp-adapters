/**
 * Centralized test configuration
 *
 * Credentials come from environment variables for CI/CD compatibility.
 * For local development, these can be set in .env or via test runner config.
 */

export interface TestConfig {
  baseURL: string;
  username: string;
  password: string;
}

const WP_TEST_USER = process.env.WP_TEST_USER || 'admin';
const WP_TEST_PASSWORD = process.env.WP_TEST_PASSWORD || 'JvL0 sQrw Sis1 cKH9 7v43 Ta22';
const WP_BASE_URL = process.env.WP_BASE_URL || 'http://mcp.local';

if (!WP_TEST_PASSWORD && process.env.CI) {
  throw new Error('WP_TEST_PASSWORD must be set in CI environment');
}

export const FLUENTBOARDS_CONFIG: TestConfig = {
  baseURL: `${WP_BASE_URL}/wp-json/mcp-adapters/v1/fluentboards`,
  username: WP_TEST_USER,
  password: WP_TEST_PASSWORD,
};

export const FLUENTCRM_CONFIG: TestConfig = {
  baseURL: `${WP_BASE_URL}/wp-json/mcp-adapters/v1/fluentcrm`,
  username: WP_TEST_USER,
  password: WP_TEST_PASSWORD,
};
