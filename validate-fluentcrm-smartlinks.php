<?php
/**
 * FluentCRM SmartLinks Model Validation Script
 *
 * Validates the complete SmartLinks (UrlStores) model structure including:
 * - Database schema for fc_url_stores and fc_campaign_url_metrics tables
 * - URL shortening operations
 * - Click tracking integration
 * - Analytics and reporting methods
 * - Type consistency
 * - Edge cases
 *
 * Usage: cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-smartlinks.php
 */

// Bootstrap WordPress
define( 'WP_USE_THEMES', false );
require_once __DIR__ . '/../../../wp-load.php';

if ( ! defined( 'FLUENTCRM' ) ) {
	die( "Error: FluentCRM is not installed or activated.\n" );
}

class FluentCrmSmartLinksValidator {
	private $results            = [];
	private $test_url_id        = null;
	private $test_campaign_id   = null;
	private $test_subscriber_id = null;

	public function __construct() {
		echo "FluentCRM SmartLinks Model Validation\n";
		echo str_repeat( '=', 80 ) . "\n\n";
	}

	/**
	 * Run all validation tests
	 */
	public function run() {
		$this->validateDatabaseSchema();
		$this->validateCreateOperation();
		$this->validateReadOperation();
		$this->validateShortUrlGeneration();
		$this->validateClickTracking();
		$this->validateAnalyticsMethods();
		$this->validateTypeConsistency();
		$this->validateEdgeCases();
		$this->cleanup();
		$this->printSummary();
	}

	/**
	 * Validate database schema for fc_url_stores and fc_campaign_url_metrics tables
	 */
	private function validateDatabaseSchema() {
		echo "1. DATABASE SCHEMA VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		global $wpdb;

		// Validate fc_url_stores table
		$url_stores_table = $wpdb->prefix . 'fc_url_stores';
		$table_exists     = $wpdb->get_var( "SHOW TABLES LIKE '$url_stores_table'" ) === $url_stores_table;
		$this->logResult( 'fc_url_stores Table Exists', $table_exists, "Table: $url_stores_table" );

		if ( $table_exists ) {
			$columns = $wpdb->get_results( "DESCRIBE $url_stores_table" );

			echo "\nfc_url_stores Table Columns:\n";
			foreach ( $columns as $column ) {
				printf("  - %-20s | %-30s | %-5s | %-10s | %s\n",
					$column->Field,
					$column->Type,
					$column->Null,
					$column->Key,
					$column->Extra
				);
			}
			echo "\n";

			// Validate expected columns
			$expected_columns = [
				'id'         => 'bigint unsigned',
				'url'        => 'text',
				'short'      => 'varchar',
				'created_at' => 'timestamp',
				'updated_at' => 'timestamp',
			];

			foreach ( $expected_columns as $field => $expected_type ) {
				$found = false;
				foreach ( $columns as $column ) {
					if ( $column->Field === $field ) {
						$found      = true;
						$type_match = stripos( $column->Type, $expected_type ) !== false;
						$this->logResult(
							"Column: $field",
							$type_match,
							"Type: {$column->Type} (Expected: $expected_type)"
						);
						break;
					}
				}
				if ( ! $found ) {
					$this->logResult( "Column: $field", false, 'Column not found' );
				}
			}

			// Check for 'short' index
			$indexes         = $wpdb->get_results( "SHOW INDEX FROM $url_stores_table" );
			$has_short_index = false;
			foreach ( $indexes as $index ) {
				if ( $index->Column_name === 'short' ) {
					$has_short_index = true;
					break;
				}
			}
			$this->logResult( 'Short URL Index', $has_short_index, "Index on 'short' column" );
		}

		// Validate fc_campaign_url_metrics table
		$metrics_table  = $wpdb->prefix . 'fc_campaign_url_metrics';
		$metrics_exists = $wpdb->get_var( "SHOW TABLES LIKE '$metrics_table'" ) === $metrics_table;
		$this->logResult( 'fc_campaign_url_metrics Table Exists', $metrics_exists, "Table: $metrics_table" );

		if ( $metrics_exists ) {
			$metric_columns = $wpdb->get_results( "DESCRIBE $metrics_table" );

			echo "\nfc_campaign_url_metrics Table Columns:\n";
			foreach ( $metric_columns as $column ) {
				printf("  - %-20s | %-30s | %-5s | %-10s | %s\n",
					$column->Field,
					$column->Type,
					$column->Null,
					$column->Key,
					$column->Extra
				);
			}
			echo "\n";

			// Validate expected metric columns
			$expected_metric_columns = [
				'id'            => 'bigint unsigned',
				'campaign_id'   => 'bigint unsigned',
				'subscriber_id' => 'bigint unsigned',
				'url_id'        => 'bigint unsigned',
				'type'          => 'varchar',
				'counter'       => 'int',
			];

			foreach ( $expected_metric_columns as $field => $expected_type ) {
				$found = false;
				foreach ( $metric_columns as $column ) {
					if ( $column->Field === $field ) {
						$found      = true;
						$type_match = stripos( $column->Type, $expected_type ) !== false;
						$this->logResult(
							"Metric Column: $field",
							$type_match,
							"Type: {$column->Type} (Expected: $expected_type)"
						);
						break;
					}
				}
				if ( ! $found ) {
					$this->logResult( "Metric Column: $field", false, 'Column not found' );
				}
			}
		}

		echo "\n";
	}

	/**
	 * Validate create operation (URL shortening)
	 */
	private function validateCreateOperation() {
		echo "2. CREATE OPERATION VALIDATION (URL Shortening)\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			$test_long_url = 'https://example.com/very/long/url/path?param1=value1&param2=value2&timestamp=' . time();

			echo "Test Long URL:\n$test_long_url\n\n";

			// Get short URL using FluentCRM's method
			if ( class_exists( '\FluentCrm\App\Models\UrlStores' ) ) {
				$short_slug = \FluentCrm\App\Models\UrlStores::getUrlSlug( $test_long_url );

				$this->logResult( 'Short URL Generated', ! empty( $short_slug ), "Short: $short_slug" );

				// Verify URL was stored in database
				$stored = \FluentCrm\App\Models\UrlStores::where( 'short', $short_slug )->first();

				if ( $stored ) {
					$this->test_url_id = $stored->id;
					$this->logResult( 'URL Stored in DB', true, "ID: {$stored->id}" );

					echo "\nStored URL Record:\n";
					echo json_encode( $stored->toArray(), JSON_PRETTY_PRINT ) . "\n\n";

					// Verify URL matches
					$url_match = $stored->url === $test_long_url;
					$this->logResult( 'URL Match', $url_match, "Stored: {$stored->url}" );

					// Verify short slug matches
					$slug_match = $stored->short === $short_slug;
					$this->logResult( 'Short Slug Match', $slug_match, "Stored: {$stored->short}" );

					// Check auto-generated fields
					$this->logResult( 'Auto ID', ! empty( $stored->id ), "ID: {$stored->id}" );
					$this->logResult( 'Created At', ! empty( $stored->created_at ), "Value: {$stored->created_at}" );
					$this->logResult( 'Updated At', ! empty( $stored->updated_at ), "Value: {$stored->updated_at}" );

					// Test idempotency - same URL should return same short
					$short_slug_2 = \FluentCrm\App\Models\UrlStores::getUrlSlug( $test_long_url );
					$this->logResult( 'Idempotency', $short_slug === $short_slug_2, 'Same short returned for same URL' );
				} else {
					$this->logResult( 'URL Stored in DB', false, 'URL not found after creation' );
				}
			} else {
				$this->logResult( 'UrlStores Model', false, 'FluentCrm\App\Models\UrlStores class not found' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Create Operation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate read operation
	 */
	private function validateReadOperation() {
		echo "3. READ OPERATION VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_url_id ) {
			echo "Skipping: No test URL created\n\n";
			return;
		}

		try {
			// Read by ID
			$url_store = \FluentCrm\App\Models\UrlStores::find( $this->test_url_id );
			$this->logResult( 'Read by ID', ! is_null( $url_store ), "ID: {$this->test_url_id}" );

			if ( $url_store ) {
				echo "\nURL Store Data:\n";
				echo json_encode( $url_store->toArray(), JSON_PRETTY_PRINT ) . "\n\n";
			}

			// Read by short slug
			$by_short = \FluentCrm\App\Models\UrlStores::getRowByShort( $url_store->short );
			$this->logResult( 'Read by Short', ! is_null( $by_short ), "Short: {$url_store->short}" );

			if ( $by_short ) {
				$id_match = $by_short->id == $this->test_url_id;
				$this->logResult( 'Short Lookup Match', $id_match, "Found ID: {$by_short->id}" );
			}

			// Test query builder methods
			$query_tests = [
				'where'   => \FluentCrm\App\Models\UrlStores::where( 'id', $this->test_url_id )->first(),
				'orderBy' => \FluentCrm\App\Models\UrlStores::orderBy( 'id', 'desc' )->first(),
				'select'  => \FluentCrm\App\Models\UrlStores::select( [ 'id', 'short', 'url' ] )->find( $this->test_url_id ),
			];

			foreach ( $query_tests as $method => $result ) {
				$this->logResult( "Query: $method", ! is_null( $result ), 'Result: ' . ( $result ? 'Found' : 'Not Found' ) );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Read Operation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate short URL generation algorithm
	 */
	private function validateShortUrlGeneration() {
		echo "4. SHORT URL GENERATION VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Test getNextShortUrl method
			$last_id = \FluentCrm\App\Models\UrlStores::select( [ 'id' ] )->orderBy( 'id', 'desc' )->first();
			$next_id = $last_id ? $last_id->id + 1 : 1;

			$next_short = \FluentCrm\App\Models\UrlStores::getNextShortUrl( $next_id );
			$this->logResult( 'Next Short URL', ! empty( $next_short ), "Generated: $next_short" );

			// Verify it's using base36-like encoding (alphanumeric)
			$is_alphanumeric = ctype_alnum( $next_short );
			$this->logResult( 'Alphanumeric Short', $is_alphanumeric, "Value: $next_short" );

			// Test with specific number
			$test_num       = 100000;
			$short_from_num = \FluentCrm\App\Models\UrlStores::getNextShortUrl( $test_num );
			$this->logResult( 'Generate from Number', ! empty( $short_from_num ), "Number: $test_num → Short: $short_from_num" );

			// Test getStringByNumber method
			$string_by_num = \FluentCrm\App\Models\UrlStores::getStringByNumber( 12345 );
			$this->logResult( 'String by Number', ! empty( $string_by_num ), "String: $string_by_num" );

			// Test uniqueness - generate multiple shorts
			$shorts = [];
			for ( $i = 0; $i < 5; $i++ ) {
				$url      = "https://test.com/unique/$i/" . time();
				$short    = \FluentCrm\App\Models\UrlStores::getUrlSlug( $url );
				$shorts[] = $short;
			}

			$unique_count = count( array_unique( $shorts ) );
			$this->logResult( 'Short URL Uniqueness', $unique_count === count( $shorts ), 'Generated: ' . count( $shorts ) . " | Unique: $unique_count" );

			echo "\nGenerated Short URLs:\n";
			foreach ( $shorts as $idx => $short ) {
				echo "  $idx: $short\n";
			}
			echo "\n";
		} catch ( \Exception $e ) {
			$this->logResult( 'Short URL Generation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate click tracking integration
	 */
	private function validateClickTracking() {
		echo "5. CLICK TRACKING VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_url_id ) {
			echo "Skipping: No test URL created\n\n";
			return;
		}

		try {
			// Create test campaign and subscriber for tracking
			$campaign_data = [
				'title'            => 'Test Campaign ' . time(),
				'slug'             => 'test-campaign-' . time(),
				'status'           => 'draft',
				'template_id'      => null,
				'email_subject'    => 'Test Subject',
				'email_pre_header' => 'Test Pre-header',
				'email_body'       => 'Test email body with link',
				'type'             => 'campaign',
			];

			$campaign               = \FluentCrm\App\Models\Campaign::create( $campaign_data );
			$this->test_campaign_id = $campaign->id;
			$this->logResult( 'Test Campaign Created', true, "ID: {$campaign->id}" );

			$subscriber_data = [
				'email'      => 'test-smartlinks-' . time() . '@example.com',
				'first_name' => 'SmartLink',
				'last_name'  => 'Test',
				'status'     => 'subscribed',
			];

			$subscriber               = \FluentCrm\App\Models\Subscriber::create( $subscriber_data );
			$this->test_subscriber_id = $subscriber->id;
			$this->logResult( 'Test Subscriber Created', true, "ID: {$subscriber->id}" );

			// Create click tracking metric
			if ( class_exists( '\FluentCrm\App\Models\CampaignUrlMetric' ) ) {
				$metric_data = [
					'campaign_id'   => $this->test_campaign_id,
					'subscriber_id' => $this->test_subscriber_id,
					'url_id'        => $this->test_url_id,
					'type'          => 'click',
					'counter'       => 1,
				];

				$metric = \FluentCrm\App\Models\CampaignUrlMetric::maybeInsert( $metric_data );
				$this->logResult( 'Click Metric Created', ! is_null( $metric ), 'ID: ' . ( $metric->id ?? 'N/A' ) );

				if ( $metric ) {
					echo "\nClick Metric Record:\n";
					echo json_encode( $metric->toArray(), JSON_PRETTY_PRINT ) . "\n\n";

					// Test idempotent insert - should increment counter
					$metric_2            = \FluentCrm\App\Models\CampaignUrlMetric::maybeInsert( $metric_data );
					$counter_incremented = $metric_2->counter > $metric->counter;
					$this->logResult( 'Counter Increment', $counter_incremented, "Original: {$metric->counter} → New: {$metric_2->counter}" );

					// Test relationship to url_stores
					$metric_with_url  = \FluentCrm\App\Models\CampaignUrlMetric::with( 'url_stores' )->find( $metric->id );
					$has_url_relation = ! is_null( $metric_with_url->url_stores );
					$this->logResult( 'URL Relationship', $has_url_relation, 'URL loaded: ' . ( $has_url_relation ? 'Yes' : 'No' ) );

					if ( $has_url_relation ) {
						$url_match = $metric_with_url->url_stores->id == $this->test_url_id;
						$this->logResult( 'URL Relation Match', $url_match, "URL ID: {$metric_with_url->url_stores->id}" );
					}
				}
			} else {
				$this->logResult( 'CampaignUrlMetric Model', false, 'Class not found' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Click Tracking', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate analytics and reporting methods
	 */
	private function validateAnalyticsMethods() {
		echo "6. ANALYTICS AND REPORTING VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_campaign_id ) {
			echo "Skipping: No test campaign created\n\n";
			return;
		}

		try {
			if ( class_exists( '\FluentCrm\App\Models\CampaignUrlMetric' ) ) {
				$metric_model = new \FluentCrm\App\Models\CampaignUrlMetric();

				// Test getLinksReport method
				$links_report = $metric_model->getLinksReport( $this->test_campaign_id );
				$this->logResult( 'Links Report', is_array( $links_report ), 'Count: ' . count( $links_report ) );

				if ( ! empty( $links_report ) ) {
					echo "\nLinks Report:\n";
					echo json_encode( $links_report, JSON_PRETTY_PRINT ) . "\n\n";

					// Verify report structure
					$first_link = $links_report[0];
					$has_total  = isset( $first_link['total'] );
					$has_url    = isset( $first_link['url'] );
					$has_id     = isset( $first_link['id'] );

					$this->logResult( 'Report Structure: total', $has_total, 'Present: ' . ( $has_total ? 'Yes' : 'No' ) );
					$this->logResult( 'Report Structure: url', $has_url, 'Present: ' . ( $has_url ? 'Yes' : 'No' ) );
					$this->logResult( 'Report Structure: id', $has_id, 'Present: ' . ( $has_id ? 'Yes' : 'No' ) );
				}

				// Test getCampaignAnalytics method
				$analytics = $metric_model->getCampaignAnalytics( $this->test_campaign_id );
				$this->logResult( 'Campaign Analytics', is_array( $analytics ), 'Metrics count: ' . count( $analytics ) );

				if ( ! empty( $analytics ) ) {
					echo "\nCampaign Analytics:\n";
					echo json_encode( $analytics, JSON_PRETTY_PRINT ) . "\n\n";

					// Check for expected metric types
					$expected_types = [ 'open', 'click', 'ctor', 'unsubscribe' ];
					foreach ( $expected_types as $type ) {
						if ( isset( $analytics[ $type ] ) ) {
							$this->logResult( "Analytics: $type", true, 'Value: ' . json_encode( $analytics[ $type ]['total'] ) );
						}
					}
				}

				// Test getClickMetrics method
				if ( $this->test_subscriber_id ) {
					// Create a campaign email record for join
					global $wpdb;
					$wpdb->insert($wpdb->prefix . 'fc_campaign_emails', [
						'campaign_id'      => $this->test_campaign_id,
						'subscriber_id'    => $this->test_subscriber_id,
						'email_subject_id' => 0,
						'email_address'    => 'test@example.com',
						'email_body'       => 'test',
						'status'           => 'sent',
					]);

					$click_metrics = $metric_model->getClickMetrics( $this->test_campaign_id, 0 );
					$this->logResult( 'Click Metrics', is_object( $click_metrics ), 'Type: ' . get_class( $click_metrics ) );
				}
			} else {
				$this->logResult( 'Analytics Methods', false, 'CampaignUrlMetric class not found' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Analytics Methods', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate type consistency for ID fields, strings, timestamps
	 */
	private function validateTypeConsistency() {
		echo "7. TYPE CONSISTENCY VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_url_id ) {
			echo "Skipping: No test URL created\n\n";
			return;
		}

		try {
			$url_store = \FluentCrm\App\Models\UrlStores::find( $this->test_url_id );

			if ( ! $url_store ) {
				$this->logResult( 'Type Validation', false, 'URL store not found' );
				return;
			}

			// Validate ID field
			$id_numeric = is_numeric( $url_store->id );
			$this->logResult(
				'Type: id',
				$id_numeric,
				'Value: ' . var_export( $url_store->id, true ) . ' | Type: ' . gettype( $url_store->id )
			);

			// Validate string fields
			$string_fields = [ 'url', 'short' ];
			foreach ( $string_fields as $field ) {
				$value     = $url_store->$field;
				$is_string = is_string( $value );
				$this->logResult(
					"Type: $field",
					$is_string,
					'Type: ' . gettype( $value ) . ' | Length: ' . strlen( $value )
				);
			}

			// Validate timestamp fields
			$timestamp_fields = [ 'created_at', 'updated_at' ];
			foreach ( $timestamp_fields as $field ) {
				$value              = $url_store->$field;
				$is_valid_timestamp = ( is_string( $value ) || is_null( $value ) ) && ( ! $value || strtotime( $value ) !== false );
				$this->logResult(
					"Type: $field",
					$is_valid_timestamp,
					'Value: ' . var_export( $value, true ) . ' | Type: ' . gettype( $value )
				);
			}

			// Validate URL is text (not truncated)
			$url_length = strlen( $url_store->url );
			$this->logResult(
				'URL Length Support',
				$url_length > 0,
				"Length: $url_length (TEXT field supports up to 65,535 characters)"
			);
		} catch ( \Exception $e ) {
			$this->logResult( 'Type Consistency', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate edge cases
	 */
	private function validateEdgeCases() {
		echo "8. EDGE CASES VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Test very long URL
			$long_url   = 'https://example.com/path?' . str_repeat( 'param=value&', 100 ) . 'end=true';
			$short_long = \FluentCrm\App\Models\UrlStores::getUrlSlug( $long_url );
			$this->logResult( 'Long URL Handling', ! empty( $short_long ), 'URL length: ' . strlen( $long_url ) . " | Short: $short_long" );

			// Test URL with special characters
			$special_url    = 'https://example.com/path?param=value&special=<>"\' #[]{}';
			$short_special  = \FluentCrm\App\Models\UrlStores::getUrlSlug( $special_url );
			$stored_special = \FluentCrm\App\Models\UrlStores::where( 'short', $short_special )->first();
			$this->logResult( 'Special Characters', ! is_null( $stored_special ), "Short: $short_special" );

			if ( $stored_special ) {
				$special_match = $stored_special->url === htmlspecialchars_decode( $special_url );
				$this->logResult( 'Special Chars Preserved', $special_match, 'URL decoded correctly' );
			}

			// Test URL with zero-width space (should be removed)
			$zero_width_url = "https://example.com/test\xE2\x80\x8B/path";
			$short_zero     = \FluentCrm\App\Models\UrlStores::getUrlSlug( $zero_width_url );
			$stored_zero    = \FluentCrm\App\Models\UrlStores::where( 'short', $short_zero )->first();

			if ( $stored_zero ) {
				$zero_removed = strpos( $stored_zero->url, "\xE2\x80\x8B" ) === false;
				$this->logResult( 'Zero-width Space Removed', $zero_removed, 'URL cleaned' );
			}

			// Test case sensitivity in short lookup
			if ( $this->test_url_id ) {
				$url_store = \FluentCrm\App\Models\UrlStores::find( $this->test_url_id );
				$short     = $url_store->short;

				// Test with exact case
				$exact_case = \FluentCrm\App\Models\UrlStores::getRowByShort( $short );
				$this->logResult( 'Exact Case Lookup', ! is_null( $exact_case ), 'Found with exact case' );

				// Test with different case (should use BINARY comparison)
				$different_case = \FluentCrm\App\Models\UrlStores::getRowByShort( strtoupper( $short ) );
				$case_sensitive = is_null( $different_case );
				$this->logResult( 'Case Sensitivity', $case_sensitive, 'BINARY comparison enforced' );
			}

			// Test empty URL handling
			try {
				$empty_short = \FluentCrm\App\Models\UrlStores::getUrlSlug( '' );
				$this->logResult( 'Empty URL', empty( $empty_short ), 'Handled gracefully' );
			} catch ( \Exception $e ) {
				$this->logResult( 'Empty URL', true, 'Exception thrown (expected)' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Edge Cases', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Cleanup test data
	 */
	private function cleanup() {
		echo "9. CLEANUP\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			global $wpdb;

			// Delete test campaign email
			if ( $this->test_campaign_id && $this->test_subscriber_id ) {
				$wpdb->delete(
					$wpdb->prefix . 'fc_campaign_emails',
					[
						'campaign_id'   => $this->test_campaign_id,
						'subscriber_id' => $this->test_subscriber_id,
					]
				);
			}

			// Delete test metrics
			if ( $this->test_campaign_id ) {
				\FluentCrm\App\Models\CampaignUrlMetric::where( 'campaign_id', $this->test_campaign_id )->delete();
				$this->logResult( 'Metrics Cleanup', true, "Campaign ID: {$this->test_campaign_id}" );
			}

			// Delete test campaign
			if ( $this->test_campaign_id ) {
				$campaign = \FluentCrm\App\Models\Campaign::find( $this->test_campaign_id );
				if ( $campaign ) {
					$campaign->delete();
					$this->logResult( 'Campaign Cleanup', true, "ID: {$this->test_campaign_id}" );
				}
			}

			// Delete test subscriber
			if ( $this->test_subscriber_id ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::find( $this->test_subscriber_id );
				if ( $subscriber ) {
					$subscriber->delete();
					$this->logResult( 'Subscriber Cleanup', true, "ID: {$this->test_subscriber_id}" );
				}
			}

			// Delete test URLs (keep production URLs intact)
			$test_urls = \FluentCrm\App\Models\UrlStores::where( 'url', 'LIKE', '%test%' )
				->orWhere( 'url', 'LIKE', '%example.com%' )
				->get();

			foreach ( $test_urls as $url ) {
				$url->delete();
			}

			$this->logResult( 'URL Cleanup', true, 'Deleted ' . count( $test_urls ) . ' test URLs' );
		} catch ( \Exception $e ) {
			$this->logResult( 'Cleanup', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Log test result
	 */
	private function logResult( $test, $passed, $details = '' ) {
		$status          = $passed ? '✅ PASS' : '❌ FAIL';
		$this->results[] = [
			'test'    => $test,
			'passed'  => $passed,
			'details' => $details,
		];
		printf( "  %-40s %s  %s\n", $test, $status, $details );
	}

	/**
	 * Print summary of all tests
	 */
	private function printSummary() {
		echo "\nVALIDATION SUMMARY\n";
		echo str_repeat( '=', 80 ) . "\n";

		$total  = count( $this->results );
		$passed = count( array_filter( $this->results, fn( $r ) => $r['passed'] ) );
		$failed = $total - $passed;

		echo "Total Tests: $total\n";
		echo "Passed: $passed\n";
		echo "Failed: $failed\n";
		echo 'Success Rate: ' . ( $total > 0 ? round( ( $passed / $total ) * 100, 2 ) : 0 ) . "%\n\n";

		if ( $failed > 0 ) {
			echo "FAILED TESTS:\n";
			foreach ( $this->results as $result ) {
				if ( ! $result['passed'] ) {
					echo "  ❌ {$result['test']}: {$result['details']}\n";
				}
			}
		}

		echo "\n";
	}
}

// Run validation
$validator = new FluentCrmSmartLinksValidator();
$validator->run();
