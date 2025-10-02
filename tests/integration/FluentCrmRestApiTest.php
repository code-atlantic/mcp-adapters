<?php
/**
 * FluentCRM MCP REST API Integration Tests
 *
 * Tests FluentCRM MCP tools through WordPress REST API endpoint
 *
 * @package MCP\Adapters\Tests\Integration
 */

declare(strict_types=1);

namespace MCP\Adapters\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Test FluentCRM MCP Tools via REST API
 */
class FluentCrmRestApiTest extends TestCase {

	/**
	 * Base URL for MCP endpoint
	 */
	private const BASE_URL = 'http://mcp.local/wp-json/mcp-adapters/v1/fluentcrm';

	/**
	 * WordPress credentials
	 */
	private const USERNAME = 'admin';
	private const PASSWORD = 'JvL0 sQrw Sis1 cKH9 7v43 Ta22';

	/**
	 * Test data IDs for cleanup
	 *
	 * @var array<string, array<int>>
	 */
	private static array $test_ids = [
		'subscribers' => [],
		'lists'       => [],
		'tags'        => [],
	];

	/**
	 * Call MCP tool via JSON-RPC
	 *
	 * @param string               $tool Tool name
	 * @param array<string, mixed> $args Tool arguments
	 * @return array<string, mixed> Structured content response
	 */
	private function call_tool( string $tool, array $args ): array {
		$payload = [
			'jsonrpc' => '2.0',
			'method'  => 'tools/call',
			'params'  => [
				'name'      => $tool,
				'arguments' => $args,
			],
			'id'      => 1,
		];

		$ch = curl_init( self::BASE_URL );
		curl_setopt_array(
			$ch,
			[
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => json_encode( $payload ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER     => [ 'Content-Type: application/json' ],
				CURLOPT_USERPWD        => self::USERNAME . ':' . self::PASSWORD,
				CURLOPT_TIMEOUT        => 30,
			]
		);

		$response = curl_exec( $ch );
		$this->assertNotFalse( $response, 'curl_exec failed: ' . curl_error( $ch ) );

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		$this->assertEquals( 200, $http_code, "HTTP {$http_code}: {$response}" );

		$data = json_decode( $response, true );
		$this->assertIsArray( $data, 'Invalid JSON response' );
		$this->assertArrayHasKey( 'result', $data, 'Missing result key in response' );

		return $data['result']['structuredContent'];
	}

	/**
	 * Clean up test data after all tests
	 */
	public static function tearDownAfterClass(): void {
		// Note: Cleanup would happen here if needed
		// For now, leaving test data for inspection
	}

	/**
	 * Test subscriber creation with minimal fields
	 */
	public function test_create_subscriber_minimal(): void {
		$email  = 'test-' . time() . '@example.com';
		$result = $this->call_tool(
			'fluentcrm-create-subscriber',
			[ 'email' => $email ]
		);

		$this->assertTrue( $result['success'] );
		$this->assertEquals( $email, $result['data']['subscriber']['email'] );

		self::$test_ids['subscribers'][] = $result['data']['subscriber']['id'];
	}

	/**
	 * Test subscriber creation with full profile
	 */
	public function test_create_subscriber_full(): void {
		$result = $this->call_tool(
			'fluentcrm-create-subscriber',
			[
				'email'      => 'full-' . time() . '@example.com',
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'status'     => 'pending',
				'phone'      => '+1-555-0123',
				'city'       => 'New York',
				'state'      => 'NY',
				'country'    => 'US',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 'John', $result['data']['subscriber']['first_name'] );
		$this->assertEquals( 'pending', $result['data']['subscriber']['status'] );

		self::$test_ids['subscribers'][] = $result['data']['subscriber']['id'];
	}

	/**
	 * Test listing subscribers
	 */
	public function test_list_subscribers(): void {
		$result = $this->call_tool(
			'fluentcrm-list-subscribers',
			[
				'page'     => 1,
				'per_page' => 5,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'subscribers', $result['data'] );
		$this->assertArrayHasKey( 'total', $result['data'] );
	}

	/**
	 * Test campaign creation
	 */
	public function test_create_campaign(): void {
		$result = $this->call_tool(
			'fluentcrm-create-campaign',
			[
				'title'      => 'Test Campaign ' . time(),
				'subject'    => 'Test Subject',
				'email_body' => '<p>Test email body</p>',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 'draft', $result['data']['campaign']['status'] );
	}

	/**
	 * Test listing campaigns by status
	 *
	 * @dataProvider campaign_statuses
	 */
	public function test_list_campaigns( string $status ): void {
		$result = $this->call_tool(
			'fluentcrm-list-campaigns',
			[
				'status'   => $status,
				'per_page' => 5,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'campaigns', $result['data'] );
	}

	/**
	 * Campaign status data provider
	 *
	 * @return array<array<string>>
	 */
	public static function campaign_statuses(): array {
		return [
			[ 'draft' ],
			[ 'scheduled' ],
			[ 'paused' ],
			[ 'archived' ],
		];
	}

	/**
	 * Test list creation
	 */
	public function test_create_list(): void {
		$result = $this->call_tool(
			'fluentcrm-create-list',
			[
				'title'       => 'Test List ' . time(),
				'description' => 'Integration test list',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'list', $result['data'] );

		self::$test_ids['lists'][] = $result['data']['list']['id'];
	}

	/**
	 * Test tag creation
	 */
	public function test_create_tag(): void {
		$result = $this->call_tool(
			'fluentcrm-create-tag',
			[ 'title' => 'Test Tag ' . time() ]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'tag', $result['data'] );

		self::$test_ids['tags'][] = $result['data']['tag']['id'];
	}

	/**
	 * Test dashboard stats
	 */
	public function test_dashboard_stats(): void {
		$result = $this->call_tool( 'fluentcrm-get-dashboard-stats', [] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'subscribers', $result['data'] );
		$this->assertArrayHasKey( 'campaigns', $result['data'] );
	}

	/**
	 * Test subscriber growth analytics
	 */
	public function test_subscriber_growth(): void {
		$result = $this->call_tool(
			'fluentcrm-get-subscriber-growth',
			[
				'start_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				'end_date'   => gmdate( 'Y-m-d' ),
				'group_by'   => 'day',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'growth', $result['data'] );
	}

	/**
	 * Test engagement metrics
	 */
	public function test_engagement_metrics(): void {
		$result = $this->call_tool(
			'fluentcrm-get-engagement-metrics',
			[ 'days_back' => 30 ]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'metrics', $result['data'] );
	}

	/**
	 * Test bulk subscriber import
	 */
	public function test_bulk_import(): void {
		$timestamp = time();
		$result    = $this->call_tool(
			'fluentcrm-bulk-import-subscribers',
			[
				'subscribers' => [
					[
						'email'      => "bulk1-{$timestamp}@example.com",
						'first_name' => 'Bulk1',
					],
					[
						'email'      => "bulk2-{$timestamp}@example.com",
						'first_name' => 'Bulk2',
					],
					[
						'email'      => "bulk3-{$timestamp}@example.com",
						'first_name' => 'Bulk3',
					],
				],
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThanOrEqual( 3, $result['data']['imported'] );
	}

	/**
	 * Test invalid email validation
	 */
	public function test_invalid_email(): void {
		$result = $this->call_tool(
			'fluentcrm-create-subscriber',
			[ 'email' => 'not-an-email' ]
		);

		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'message', $result );
	}

	/**
	 * Test missing required parameter
	 */
	public function test_missing_required_param(): void {
		$result = $this->call_tool(
			'fluentcrm-create-subscriber',
			[ 'first_name' => 'Test' ]
		);

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test invalid enum value
	 */
	public function test_invalid_enum_value(): void {
		$result = $this->call_tool(
			'fluentcrm-list-campaigns',
			[ 'status' => 'invalid_status' ]
		);

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test pagination boundaries
	 */
	public function test_pagination_limits(): void {
		// Test minimum
		$result = $this->call_tool(
			'fluentcrm-list-subscribers',
			[
				'page'     => 1,
				'per_page' => 1,
			]
		);
		$this->assertTrue( $result['success'] );

		// Test maximum
		$result = $this->call_tool(
			'fluentcrm-list-subscribers',
			[
				'page'     => 1,
				'per_page' => 100,
			]
		);
		$this->assertTrue( $result['success'] );
	}
}
