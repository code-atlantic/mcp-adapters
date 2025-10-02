<?php
/**
 * FluentCRM MCP Tools Integration Tests
 *
 * Tests all FluentCRM MCP tools through the WordPress Abilities API
 *
 * @package MCP\Adapters\Tests\Integration
 */

declare(strict_types=1);

namespace MCP\Adapters\Tests\Integration;

use WP_UnitTestCase;

/**
 * Test FluentCRM MCP Tools
 */
class FluentCrmToolsTest extends WP_UnitTestCase {

	/**
	 * Test subscriber ID for cleanup
	 *
	 * @var array<int>
	 */
	protected static $test_subscriber_ids = [];

	/**
	 * Test list ID for cleanup
	 *
	 * @var array<int>
	 */
	protected static $test_list_ids = [];

	/**
	 * Test tag ID for cleanup
	 *
	 * @var array<int>
	 */
	protected static $test_tag_ids = [];

	/**
	 * Set up before class
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// Ensure FluentCRM is active
		if ( ! defined( 'FLUENTCRM' ) ) {
			self::markTestSkipped( 'FluentCRM is not active' );
		}
	}

	/**
	 * Clean up after tests
	 */
	public static function tear_down_after_class(): void {
		// Cleanup test data
		if ( class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			foreach ( self::$test_subscriber_ids as $id ) {
				\FluentCrm\App\Models\Subscriber::where( 'id', $id )->delete();
			}
		}

		if ( class_exists( '\FluentCrm\App\Models\Lists' ) ) {
			foreach ( self::$test_list_ids as $id ) {
				\FluentCrm\App\Models\Lists::where( 'id', $id )->delete();
			}
		}

		if ( class_exists( '\FluentCrm\App\Models\Tag' ) ) {
			foreach ( self::$test_tag_ids as $id ) {
				\FluentCrm\App\Models\Tag::where( 'id', $id )->delete();
			}
		}

		parent::tear_down_after_class();
	}

	/**
	 * Test subscriber creation with minimal fields
	 */
	public function test_create_subscriber_minimal(): void {
		$email = 'test-' . time() . '@example.com';

		$result = wp_execute_ability(
			'fluentcrm/create-subscriber',
			[ 'email' => $email ]
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'subscriber', $result['data'] );
		$this->assertEquals( $email, $result['data']['subscriber']['email'] );

		// Track for cleanup
		self::$test_subscriber_ids[] = $result['data']['subscriber']['id'];
	}

	/**
	 * Test subscriber creation with full profile
	 */
	public function test_create_subscriber_full_profile(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-subscriber',
			[
				'email'      => 'full-' . time() . '@example.com',
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'status'     => 'pending',
				'city'       => 'New York',
				'country'    => 'US',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 'John', $result['data']['subscriber']['first_name'] );
		$this->assertEquals( 'pending', $result['data']['subscriber']['status'] );

		self::$test_subscriber_ids[] = $result['data']['subscriber']['id'];
	}

	/**
	 * Test listing subscribers with pagination
	 */
	public function test_list_subscribers(): void {
		$result = wp_execute_ability(
			'fluentcrm/list-subscribers',
			[
				'page'     => 1,
				'per_page' => 5,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'subscribers', $result['data'] );
		$this->assertArrayHasKey( 'total', $result['data'] );
		$this->assertEquals( 1, $result['data']['page'] );
	}

	/**
	 * Test campaign creation
	 */
	public function test_create_campaign(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-campaign',
			[
				'title'      => 'Test Campaign ' . time(),
				'subject'    => 'Test Subject',
				'email_body' => '<p>Test email body</p>',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'campaign', $result['data'] );
		$this->assertEquals( 'draft', $result['data']['campaign']['status'] );
	}

	/**
	 * Test listing campaigns with status filter
	 *
	 * @dataProvider campaign_status_provider
	 */
	public function test_list_campaigns_by_status( string $status ): void {
		$result = wp_execute_ability(
			'fluentcrm/list-campaigns',
			[
				'status'   => $status,
				'per_page' => 5,
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'campaigns', $result['data'] );
	}

	/**
	 * Data provider for campaign statuses
	 *
	 * @return array<array<string>>
	 */
	public function campaign_status_provider(): array {
		return [
			'draft'     => [ 'draft' ],
			'scheduled' => [ 'scheduled' ],
			'paused'    => [ 'paused' ],
			'archived'  => [ 'archived' ],
		];
	}

	/**
	 * Test list creation
	 */
	public function test_create_list(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-list',
			[
				'title'       => 'Test List ' . time(),
				'description' => 'Test description',
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'list', $result['data'] );

		self::$test_list_ids[] = $result['data']['list']['id'];
	}

	/**
	 * Test tag creation
	 */
	public function test_create_tag(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-tag',
			[ 'title' => 'Test Tag ' . time() ]
		);

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'tag', $result['data'] );

		self::$test_tag_ids[] = $result['data']['tag']['id'];
	}

	/**
	 * Test dashboard statistics
	 */
	public function test_dashboard_stats(): void {
		$result = wp_execute_ability( 'fluentcrm/get-dashboard-stats', [] );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'subscribers', $result['data'] );
		$this->assertArrayHasKey( 'campaigns', $result['data'] );
		$this->assertArrayHasKey( 'total', $result['data']['subscribers'] );
	}

	/**
	 * Test subscriber growth analytics
	 */
	public function test_subscriber_growth(): void {
		$result = wp_execute_ability(
			'fluentcrm/get-subscriber-growth',
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
	 * Test bulk subscriber import
	 */
	public function test_bulk_import_subscribers(): void {
		$timestamp = time();
		$result    = wp_execute_ability(
			'fluentcrm/bulk-import-subscribers',
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
				],
			]
		);

		$this->assertTrue( $result['success'] );
		$this->assertGreaterThanOrEqual( 2, $result['data']['imported'] );
	}

	/**
	 * Test invalid email validation
	 */
	public function test_invalid_email_rejected(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-subscriber',
			[ 'email' => 'not-an-email' ]
		);

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test missing required field
	 */
	public function test_missing_required_field(): void {
		$result = wp_execute_ability(
			'fluentcrm/create-subscriber',
			[ 'first_name' => 'Test' ]
		);

		$this->assertFalse( $result['success'] );
	}
}
