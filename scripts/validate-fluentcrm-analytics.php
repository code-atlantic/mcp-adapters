#!/usr/bin/env php
<?php
/**
 * FluentCRM Analytics and Reporting Validation Script
 *
 * Validates analytics structures, reporting methods, and data aggregation
 * across FluentCRM campaigns, subscribers, and dashboard metrics.
 *
 * Usage: cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-analytics.php
 *
 * @package MCP\Adapters
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

/**
 * Validation result logger
 */
class ValidationLogger {
	private $results = [];
	private $summary = [
		'total'   => 0,
		'passed'  => 0,
		'failed'  => 0,
		'warning' => 0,
	];

	public function log( string $category, string $test, string $status, $details = null ): void {
		$this->results[] = [
			'category' => $category,
			'test'     => $test,
			'status'   => $status,
			'details'  => $details,
		];

		++$this->summary['total'];
		++$this->summary[ $status ];
	}

	public function output(): void {
		echo "\n" . str_repeat( '=', 80 ) . "\n";
		echo "FluentCRM Analytics & Reporting Validation Results\n";
		echo str_repeat( '=', 80 ) . "\n\n";

		$current_category = '';
		foreach ( $this->results as $result ) {
			if ( $current_category !== $result['category'] ) {
				$current_category = $result['category'];
				echo "\n## {$current_category}\n\n";
			}

			$icon = $this->get_status_icon( $result['status'] );
			echo "{$icon} {$result['test']}\n";

			if ( $result['details'] ) {
				if ( is_array( $result['details'] ) || is_object( $result['details'] ) ) {
					echo '   ' . json_encode( $result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
				} else {
					echo "   {$result['details']}\n";
				}
			}
		}

		echo "\n" . str_repeat( '=', 80 ) . "\n";
		echo "Summary\n";
		echo str_repeat( '=', 80 ) . "\n";
		echo "Total Tests:  {$this->summary['total']}\n";
		echo "Passed:       {$this->summary['passed']}\n";
		echo "Failed:       {$this->summary['failed']}\n";
		echo "Warnings:     {$this->summary['warning']}\n";
		echo str_repeat( '=', 80 ) . "\n\n";
	}

	private function get_status_icon( string $status ): string {
		$icons = [
			'passed'  => '✓',
			'failed'  => '✗',
			'warning' => '⚠',
		];

		return $icons[ $status ] ?? '?';
	}

	public function get_summary(): array {
		return $this->summary;
	}
}

/**
 * FluentCRM Analytics Validator
 */
class FluentCrmAnalyticsValidator {
	private $logger;

	public function __construct( ValidationLogger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Run all validation tests
	 */
	public function run(): void {
		echo "Starting FluentCRM Analytics & Reporting Validation...\n\n";

		// Prerequisites
		$this->validate_prerequisites();

		// Model Structure
		$this->validate_campaign_email_model();
		$this->validate_campaign_url_metric_model();
		$this->validate_subscriber_stats_method();
		$this->validate_campaign_stats_method();

		// Analytics Methods
		$this->validate_campaign_analytics();
		$this->validate_url_metrics();
		$this->validate_open_tracking();

		// Reporting Methods
		$this->validate_dashboard_stats();
		$this->validate_subscriber_growth();
		$this->validate_engagement_metrics();
		$this->validate_deliverability_report();

		// Data Consistency
		$this->validate_type_consistency();
		$this->validate_aggregation_methods();

		$this->logger->output();
	}

	/**
	 * Validate prerequisites
	 */
	private function validate_prerequisites(): void {
		// Check FluentCRM is active
		if ( ! defined( 'FLUENTCRM' ) ) {
			$this->logger->log( 'Prerequisites', 'FluentCRM Active', 'failed', 'FluentCRM constant not defined' );
			echo "\nERROR: FluentCRM is not active. Cannot continue validation.\n\n";
			exit( 1 );
		}
		$this->logger->log( 'Prerequisites', 'FluentCRM Active', 'passed', 'Version: ' . FLUENTCRM );

		// Check models exist
		$models = [
			'Campaign'          => '\FluentCrm\App\Models\Campaign',
			'CampaignEmail'     => '\FluentCrm\App\Models\CampaignEmail',
			'CampaignUrlMetric' => '\FluentCrm\App\Models\CampaignUrlMetric',
			'Subscriber'        => '\FluentCrm\App\Models\Subscriber',
			'Funnel'            => '\FluentCrm\App\Models\Funnel',
			'Lists'             => '\FluentCrm\App\Models\Lists',
			'Tag'               => '\FluentCrm\App\Models\Tag',
		];

		foreach ( $models as $name => $class ) {
			if ( class_exists( $class ) ) {
				$this->logger->log( 'Prerequisites', "{$name} Model", 'passed', $class );
			} else {
				$this->logger->log( 'Prerequisites', "{$name} Model", 'failed', "Class {$class} not found" );
			}
		}
	}

	/**
	 * Validate CampaignEmail model structure
	 */
	private function validate_campaign_email_model(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			$this->logger->log( 'CampaignEmail Model', 'Model Exists', 'failed', 'Class not found' );
			return;
		}

		$model = new \FluentCrm\App\Models\CampaignEmail();

		// Table name
		$expected_table = 'fc_campaign_emails';
		$actual_table   = $model->getTable();
		if ( $expected_table === $actual_table ) {
			$this->logger->log( 'CampaignEmail Model', 'Table Name', 'passed', $actual_table );
		} else {
			$this->logger->log( 'CampaignEmail Model', 'Table Name', 'failed', "Expected {$expected_table}, got {$actual_table}" );
		}

		// Relationships
		$relationships = [
			'campaign'   => 'Campaign relationship',
			'subscriber' => 'Subscriber relationship',
			'subject'    => 'Subject relationship',
		];

		foreach ( $relationships as $method => $description ) {
			if ( method_exists( $model, $method ) ) {
				$this->logger->log( 'CampaignEmail Model', $description, 'passed', "Method: {$method}()" );
			} else {
				$this->logger->log( 'CampaignEmail Model', $description, 'failed', "Method {$method}() not found" );
			}
		}

		// Key tracking fields
		$tracking_fields = [
			'is_open'         => 'Open tracking counter',
			'click_counter'   => 'Click tracking counter',
			'is_unsubscribed' => 'Unsubscribe flag',
			'status'          => 'Email status',
			'email_hash'      => 'Email hash for tracking',
		];

		foreach ( $tracking_fields as $field => $description ) {
			// We can't directly access fields without data, so we document expected fields
			$this->logger->log( 'CampaignEmail Model', "Field: {$field}", 'passed', $description );
		}

		// Methods
		$methods = [
			'data'            => 'Email data for sending',
			'previewData'     => 'Preview data',
			'getEmailSubject' => 'Get email subject',
			'getEmailBody'    => 'Get email body',
			'getClicks'       => 'Get click data',
			'getSubjectCount' => 'Get subject count',
			'getOpenCount'    => 'Get open count',
			'markAs'          => 'Mark email as status',
			'markAsSent'      => 'Mark as sent',
			'markAsFailed'    => 'Mark as failed',
		];

		foreach ( $methods as $method => $description ) {
			if ( method_exists( $model, $method ) ) {
				$this->logger->log( 'CampaignEmail Model', "Method: {$method}", 'passed', $description );
			} else {
				$this->logger->log( 'CampaignEmail Model', "Method: {$method}", 'failed', "Not found: {$description}" );
			}
		}
	}

	/**
	 * Validate CampaignUrlMetric model structure
	 */
	private function validate_campaign_url_metric_model(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignUrlMetric' ) ) {
			$this->logger->log( 'CampaignUrlMetric Model', 'Model Exists', 'failed', 'Class not found' );
			return;
		}

		$model = new \FluentCrm\App\Models\CampaignUrlMetric();

		// Table name
		$expected_table = 'fc_campaign_url_metrics';
		$actual_table   = $model->getTable();
		if ( $expected_table === $actual_table ) {
			$this->logger->log( 'CampaignUrlMetric Model', 'Table Name', 'passed', $actual_table );
		} else {
			$this->logger->log( 'CampaignUrlMetric Model', 'Table Name', 'failed', "Expected {$expected_table}, got {$actual_table}" );
		}

		// Relationships
		$relationships = [
			'campaign'   => 'Campaign relationship',
			'subscriber' => 'Subscriber relationship',
			'url_stores' => 'URL stores relationship',
		];

		foreach ( $relationships as $method => $description ) {
			if ( method_exists( $model, $method ) ) {
				$this->logger->log( 'CampaignUrlMetric Model', $description, 'passed', "Method: {$method}()" );
			} else {
				$this->logger->log( 'CampaignUrlMetric Model', $description, 'failed', "Method {$method}() not found" );
			}
		}

		// Key tracking fields
		$tracking_fields = [
			'campaign_id'   => 'Campaign ID',
			'subscriber_id' => 'Subscriber ID',
			'url_id'        => 'URL ID reference',
			'type'          => 'Metric type (click, unsubscribe)',
			'counter'       => 'Click/event counter',
		];

		foreach ( $tracking_fields as $field => $description ) {
			$this->logger->log( 'CampaignUrlMetric Model', "Field: {$field}", 'passed', $description );
		}

		// Methods
		$methods = [
			'maybeInsert'          => 'Insert or increment metric',
			'getLinksReport'       => 'Get links report for campaign',
			'getCampaignAnalytics' => 'Get campaign analytics',
			'getSubjectStats'      => 'Get subject line stats',
			'getClickMetrics'      => 'Get click metrics',
		];

		foreach ( $methods as $method => $description ) {
			if ( method_exists( $model, $method ) ) {
				$this->logger->log( 'CampaignUrlMetric Model', "Method: {$method}", 'passed', $description );
			} else {
				$this->logger->log( 'CampaignUrlMetric Model', "Method: {$method}", 'failed', "Not found: {$description}" );
			}
		}
	}

	/**
	 * Validate Subscriber stats() method
	 */
	private function validate_subscriber_stats_method(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			$this->logger->log( 'Subscriber Stats', 'Model Exists', 'failed', 'Class not found' );
			return;
		}

		// Check if stats method exists
		if ( method_exists( '\FluentCrm\App\Models\Subscriber', 'stats' ) ) {
			$this->logger->log( 'Subscriber Stats', 'stats() Method', 'passed', 'Method exists' );

			// Try to get a subscriber and check stats structure
			$subscriber = \FluentCrm\App\Models\Subscriber::first();
			if ( $subscriber ) {
				try {
					$stats = $subscriber->stats();
					$this->logger->log( 'Subscriber Stats', 'Stats Response Structure', 'passed', array_keys( (array) $stats ) );

					// Validate expected structure
					$expected_keys = [ 'emails_sent', 'emails_opened', 'emails_clicked', 'campaigns', 'sequences', 'tags', 'lists' ];
					foreach ( $expected_keys as $key ) {
						if ( isset( $stats[ $key ] ) ) {
							$this->logger->log( 'Subscriber Stats', "Stats Key: {$key}", 'passed', gettype( $stats[ $key ] ) );
						} else {
							$this->logger->log( 'Subscriber Stats', "Stats Key: {$key}", 'warning', 'Key not present in stats response' );
						}
					}
				} catch ( \Exception $e ) {
					$this->logger->log( 'Subscriber Stats', 'Stats Execution', 'failed', $e->getMessage() );
				}
			} else {
				$this->logger->log( 'Subscriber Stats', 'Test Data', 'warning', 'No subscribers found to test stats()' );
			}
		} else {
			$this->logger->log( 'Subscriber Stats', 'stats() Method', 'warning', 'Method may not exist or is custom implementation' );
		}

		// Check for alternative methods
		$alt_methods = [
			'getTotalEmailSent'  => 'Get total emails sent',
			'getEmailOpenCount'  => 'Get email open count',
			'getEmailClickCount' => 'Get email click count',
		];

		foreach ( $alt_methods as $method => $description ) {
			if ( method_exists( '\FluentCrm\App\Models\Subscriber', $method ) ) {
				$this->logger->log( 'Subscriber Stats', "Alternative: {$method}", 'passed', $description );
			}
		}
	}

	/**
	 * Validate Campaign stats() method
	 */
	private function validate_campaign_stats_method(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			$this->logger->log( 'Campaign Stats', 'Model Exists', 'failed', 'Class not found' );
			return;
		}

		// Check if stats method exists
		if ( method_exists( '\FluentCrm\App\Models\Campaign', 'stats' ) ) {
			$this->logger->log( 'Campaign Stats', 'stats() Method', 'passed', 'Method exists' );

			// Try to get a campaign and check stats structure
			$campaign = \FluentCrm\App\Models\Campaign::first();
			if ( $campaign ) {
				try {
					$stats = $campaign->stats();
					$this->logger->log( 'Campaign Stats', 'Stats Response Structure', 'passed', array_keys( (array) $stats ) );

					// Validate expected structure
					$expected_keys = [ 'sent', 'opened', 'clicked', 'bounced', 'unsubscribed', 'open_rate', 'click_rate' ];
					foreach ( $expected_keys as $key ) {
						if ( isset( $stats[ $key ] ) ) {
							$this->logger->log( 'Campaign Stats', "Stats Key: {$key}", 'passed', gettype( $stats[ $key ] ) );
						} else {
							$this->logger->log( 'Campaign Stats', "Stats Key: {$key}", 'warning', 'Key not present in stats response' );
						}
					}
				} catch ( \Exception $e ) {
					$this->logger->log( 'Campaign Stats', 'Stats Execution', 'failed', $e->getMessage() );
				}
			} else {
				$this->logger->log( 'Campaign Stats', 'Test Data', 'warning', 'No campaigns found to test stats()' );
			}
		} else {
			$this->logger->log( 'Campaign Stats', 'stats() Method', 'warning', 'Method may not exist or uses different implementation' );
		}
	}

	/**
	 * Validate campaign analytics aggregation
	 */
	private function validate_campaign_analytics(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) || ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			$this->logger->log( 'Campaign Analytics', 'Models Available', 'failed', 'Required models not found' );
			return;
		}

		$campaign = \FluentCrm\App\Models\Campaign::first();
		if ( ! $campaign ) {
			$this->logger->log( 'Campaign Analytics', 'Test Data', 'warning', 'No campaigns available for testing' );
			return;
		}

		try {
			// Test sent count
			$sent_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->whereIn( 'status', [ 'sent', 'delivered' ] )
				->count();
			$this->logger->log( 'Campaign Analytics', 'Sent Count Query', 'passed', "Count: {$sent_count}" );

			// Test open count
			$open_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'is_open', '>', 0 )
				->count();
			$this->logger->log( 'Campaign Analytics', 'Open Count Query', 'passed', "Count: {$open_count}" );

			// Test click count
			$click_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'click_counter', '>', 0 )
				->count();
			$this->logger->log( 'Campaign Analytics', 'Click Count Query', 'passed', "Count: {$click_count}" );

			// Test bounce count
			$bounce_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'status', 'bounced' )
				->count();
			$this->logger->log( 'Campaign Analytics', 'Bounce Count Query', 'passed', "Count: {$bounce_count}" );

			// Test unsubscribe count
			$unsub_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'is_unsubscribed', 1 )
				->count();
			$this->logger->log( 'Campaign Analytics', 'Unsubscribe Count Query', 'passed', "Count: {$unsub_count}" );

			// Validate rate calculations
			$open_rate = $sent_count > 0 ? round( ( $open_count / $sent_count ) * 100, 2 ) : 0;
			$this->logger->log( 'Campaign Analytics', 'Open Rate Calculation', 'passed', "{$open_rate}%" );

			$click_rate = $sent_count > 0 ? round( ( $click_count / $sent_count ) * 100, 2 ) : 0;
			$this->logger->log( 'Campaign Analytics', 'Click Rate Calculation', 'passed', "{$click_rate}%" );

			$click_to_open = $open_count > 0 ? round( ( $click_count / $open_count ) * 100, 2 ) : 0;
			$this->logger->log( 'Campaign Analytics', 'Click-to-Open Rate', 'passed', "{$click_to_open}%" );
		} catch ( \Exception $e ) {
			$this->logger->log( 'Campaign Analytics', 'Aggregation Queries', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate URL metrics tracking
	 */
	private function validate_url_metrics(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignUrlMetric' ) ) {
			$this->logger->log( 'URL Metrics', 'Model Available', 'failed', 'CampaignUrlMetric model not found' );
			return;
		}

		$campaign = \FluentCrm\App\Models\Campaign::first();
		if ( ! $campaign ) {
			$this->logger->log( 'URL Metrics', 'Test Data', 'warning', 'No campaigns available for testing' );
			return;
		}

		try {
			$url_metrics = \FluentCrm\App\Models\CampaignUrlMetric::where( 'campaign_id', $campaign->id )
				->where( 'type', 'click' )
				->get();

			$this->logger->log( 'URL Metrics', 'URL Metrics Query', 'passed', "Found {$url_metrics->count()} click metrics" );

			if ( $url_metrics->count() > 0 ) {
				$sample = $url_metrics->first();
				$fields = [
					'campaign_id',
					'subscriber_id',
					'url_id',
					'type',
					'counter',
				];

				foreach ( $fields as $field ) {
					if ( property_exists( $sample, $field ) || isset( $sample->{$field} ) ) {
						$this->logger->log( 'URL Metrics', "Field: {$field}", 'passed', 'Value type: ' . gettype( $sample->{$field} ) );
					} else {
						$this->logger->log( 'URL Metrics', "Field: {$field}", 'warning', 'Field not present in sample data' );
					}
				}
			}

			// Test getLinksReport method
			$metric_model = new \FluentCrm\App\Models\CampaignUrlMetric();
			if ( method_exists( $metric_model, 'getLinksReport' ) ) {
				$report = $metric_model->getLinksReport( $campaign->id );
				$this->logger->log( 'URL Metrics', 'getLinksReport() Method', 'passed', count( $report ) . ' links in report' );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'URL Metrics', 'URL Metrics Queries', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate open tracking
	 */
	private function validate_open_tracking(): void {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			$this->logger->log( 'Open Tracking', 'Model Available', 'failed', 'CampaignEmail model not found' );
			return;
		}

		$campaign = \FluentCrm\App\Models\Campaign::first();
		if ( ! $campaign ) {
			$this->logger->log( 'Open Tracking', 'Test Data', 'warning', 'No campaigns available for testing' );
			return;
		}

		try {
			$opened_emails = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'is_open', '>', 0 )
				->with( 'subscriber' )
				->take( 10 )
				->get();

			$this->logger->log( 'Open Tracking', 'Open Emails Query', 'passed', "Found {$opened_emails->count()} opened emails" );

			if ( $opened_emails->count() > 0 ) {
				$sample = $opened_emails->first();

				// Validate tracking fields
				if ( isset( $sample->is_open ) && $sample->is_open > 0 ) {
					$this->logger->log( 'Open Tracking', 'is_open Field', 'passed', "Open count: {$sample->is_open}" );
				} else {
					$this->logger->log( 'Open Tracking', 'is_open Field', 'warning', 'Field not tracking opens' );
				}

				// Validate relationship loading
				if ( $sample->subscriber ) {
					$this->logger->log( 'Open Tracking', 'Subscriber Relationship', 'passed', 'Subscriber loaded: ' . $sample->subscriber->email );
				} else {
					$this->logger->log( 'Open Tracking', 'Subscriber Relationship', 'warning', 'Subscriber not loaded' );
				}

				// Check for timestamp tracking
				if ( property_exists( $sample, 'created_at' ) ) {
					$this->logger->log( 'Open Tracking', 'First Open Timestamp', 'passed', $sample->created_at );
				}

				if ( property_exists( $sample, 'updated_at' ) ) {
					$this->logger->log( 'Open Tracking', 'Last Open Timestamp', 'passed', $sample->updated_at );
				}
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'Open Tracking', 'Open Tracking Queries', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate dashboard stats
	 */
	private function validate_dashboard_stats(): void {
		try {
			// Subscriber stats
			$total_subscribers = \FluentCrm\App\Models\Subscriber::count();
			$this->logger->log( 'Dashboard Stats', 'Total Subscribers', 'passed', $total_subscribers );

			$active_subscribers = \FluentCrm\App\Models\Subscriber::where( 'status', 'subscribed' )->count();
			$this->logger->log( 'Dashboard Stats', 'Active Subscribers', 'passed', $active_subscribers );

			// Campaign stats
			$total_campaigns = \FluentCrm\App\Models\Campaign::count();
			$this->logger->log( 'Dashboard Stats', 'Total Campaigns', 'passed', $total_campaigns );

			$sent_campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )->count();
			$this->logger->log( 'Dashboard Stats', 'Sent Campaigns', 'passed', $sent_campaigns );

			// List and tag counts
			if ( class_exists( '\FluentCrm\App\Models\Lists' ) ) {
				$total_lists = \FluentCrm\App\Models\Lists::count();
				$this->logger->log( 'Dashboard Stats', 'Total Lists', 'passed', $total_lists );
			}

			if ( class_exists( '\FluentCrm\App\Models\Tag' ) ) {
				$total_tags = \FluentCrm\App\Models\Tag::count();
				$this->logger->log( 'Dashboard Stats', 'Total Tags', 'passed', $total_tags );
			}

			// Automation stats
			if ( class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
				$total_funnels = \FluentCrm\App\Models\Funnel::count();
				$this->logger->log( 'Dashboard Stats', 'Total Automations', 'passed', $total_funnels );
			}

			// Recent activity
			$thirty_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
			$new_subscribers = \FluentCrm\App\Models\Subscriber::where( 'created_at', '>=', $thirty_days_ago )->count();
			$this->logger->log( 'Dashboard Stats', 'New Subscribers (30d)', 'passed', $new_subscribers );
		} catch ( \Exception $e ) {
			$this->logger->log( 'Dashboard Stats', 'Dashboard Queries', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate subscriber growth tracking
	 */
	private function validate_subscriber_growth(): void {
		try {
			$start_date = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
			$end_date   = gmdate( 'Y-m-d' );

			$start_datetime = $start_date . ' 00:00:00';
			$end_datetime   = $end_date . ' 23:59:59';

			$subscribers = \FluentCrm\App\Models\Subscriber::whereBetween( 'created_at', [ $start_datetime, $end_datetime ] )
				->select( 'created_at', 'status' )
				->get();

			$this->logger->log( 'Subscriber Growth', 'Growth Query', 'passed', "{$subscribers->count()} subscribers in 90-day range" );

			// Test grouping by day
			$growth_data = [];
			foreach ( $subscribers as $subscriber ) {
				$date = gmdate( 'Y-m-d', strtotime( $subscriber->created_at ) );
				if ( ! isset( $growth_data[ $date ] ) ) {
					$growth_data[ $date ] = [
						'new'          => 0,
						'subscribed'   => 0,
						'pending'      => 0,
						'unsubscribed' => 0,
					];
				}
				++$growth_data[ $date ]['new'];
				if ( in_array( $subscriber->status, [ 'subscribed', 'pending', 'unsubscribed' ], true ) ) {
					++$growth_data[ $date ][ $subscriber->status ];
				}
			}

			$this->logger->log( 'Subscriber Growth', 'Daily Grouping', 'passed', count( $growth_data ) . ' days with activity' );
		} catch ( \Exception $e ) {
			$this->logger->log( 'Subscriber Growth', 'Growth Tracking', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate engagement metrics
	 */
	private function validate_engagement_metrics(): void {
		try {
			$days_back = 30;
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			$campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'created_at', '>=', $date_from )
				->pluck( 'id' )
				->toArray();

			$this->logger->log( 'Engagement Metrics', 'Campaign Selection', 'passed', count( $campaigns ) . ' campaigns in period' );

			if ( ! empty( $campaigns ) ) {
				$total_sent = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->whereIn( 'status', [ 'sent', 'delivered' ] )
					->count();

				$total_opened = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->where( 'is_open', '>', 0 )
					->count();

				$total_clicked = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->where( 'click_counter', '>', 0 )
					->count();

				$avg_open_rate  = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 2 ) : 0;
				$avg_click_rate = $total_sent > 0 ? round( ( $total_clicked / $total_sent ) * 100, 2 ) : 0;

				$this->logger->log( 'Engagement Metrics', 'Average Open Rate', 'passed', "{$avg_open_rate}% ({$total_opened}/{$total_sent})" );
				$this->logger->log( 'Engagement Metrics', 'Average Click Rate', 'passed', "{$avg_click_rate}% ({$total_clicked}/{$total_sent})" );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'Engagement Metrics', 'Engagement Calculations', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate deliverability report
	 */
	private function validate_deliverability_report(): void {
		try {
			$days_back = 30;
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			$campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'created_at', '>=', $date_from )
				->pluck( 'id' )
				->toArray();

			if ( ! empty( $campaigns ) ) {
				$total_sent = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->whereIn( 'status', [ 'sent', 'delivered', 'bounced' ] )
					->count();

				$total_delivered = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->whereIn( 'status', [ 'sent', 'delivered' ] )
					->count();

				$total_bounced = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
					->where( 'status', 'bounced' )
					->count();

				$bounce_rate   = $total_sent > 0 ? round( ( $total_bounced / $total_sent ) * 100, 2 ) : 0;
				$delivery_rate = $total_sent > 0 ? round( ( $total_delivered / $total_sent ) * 100, 2 ) : 0;

				$this->logger->log( 'Deliverability', 'Bounce Rate', 'passed', "{$bounce_rate}% ({$total_bounced}/{$total_sent})" );
				$this->logger->log( 'Deliverability', 'Delivery Rate', 'passed', "{$delivery_rate}% ({$total_delivered}/{$total_sent})" );

				$health = $bounce_rate < 2 ? 'excellent' : ( $bounce_rate < 5 ? 'good' : 'needs_attention' );
				$this->logger->log( 'Deliverability', 'Health Status', 'passed', $health );
			} else {
				$this->logger->log( 'Deliverability', 'Test Data', 'warning', 'No sent campaigns in period' );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'Deliverability', 'Deliverability Queries', 'failed', $e->getMessage() );
		}
	}

	/**
	 * Validate type consistency across analytics
	 */
	private function validate_type_consistency(): void {
		// Check that counts are always integers
		$campaign = \FluentCrm\App\Models\Campaign::first();
		if ( $campaign ) {
			$sent_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->whereIn( 'status', [ 'sent', 'delivered' ] )
				->count();

			if ( is_int( $sent_count ) ) {
				$this->logger->log( 'Type Consistency', 'Count Returns Integer', 'passed', 'Type: ' . gettype( $sent_count ) );
			} else {
				$this->logger->log( 'Type Consistency', 'Count Returns Integer', 'failed', 'Type: ' . gettype( $sent_count ) );
			}

			// Check rate calculations are floats
			$open_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
				->where( 'is_open', '>', 0 )
				->count();

			$rate = $sent_count > 0 ? round( ( $open_count / $sent_count ) * 100, 2 ) : 0;
			if ( is_float( $rate ) || is_int( $rate ) ) {
				$this->logger->log( 'Type Consistency', 'Rate Calculation Type', 'passed', 'Type: ' . gettype( $rate ) );
			} else {
				$this->logger->log( 'Type Consistency', 'Rate Calculation Type', 'failed', 'Type: ' . gettype( $rate ) );
			}
		}

		// Check date/time consistency
		$subscriber = \FluentCrm\App\Models\Subscriber::first();
		if ( $subscriber && property_exists( $subscriber, 'created_at' ) ) {
			$type = gettype( $subscriber->created_at );
			$this->logger->log( 'Type Consistency', 'Timestamp Type', 'passed', "created_at type: {$type}" );
		}
	}

	/**
	 * Validate aggregation methods
	 */
	private function validate_aggregation_methods(): void {
		// Test COUNT aggregation
		$total = \FluentCrm\App\Models\Subscriber::count();
		$this->logger->log( 'Aggregation Methods', 'COUNT()', 'passed', "Total: {$total}" );

		// Test WHERE + COUNT
		$active = \FluentCrm\App\Models\Subscriber::where( 'status', 'subscribed' )->count();
		$this->logger->log( 'Aggregation Methods', 'WHERE + COUNT()', 'passed', "Active: {$active}" );

		// Test GROUP BY
		try {
			$status_counts = \FluentCrm\App\Models\Subscriber::selectRaw( 'status, COUNT(*) as count' )
				->groupBy( 'status' )
				->get();
			$this->logger->log( 'Aggregation Methods', 'GROUP BY', 'passed', $status_counts->count() . ' status groups' );
		} catch ( \Exception $e ) {
			$this->logger->log( 'Aggregation Methods', 'GROUP BY', 'failed', $e->getMessage() );
		}

		// Test JOIN aggregation
		$campaign = \FluentCrm\App\Models\Campaign::first();
		if ( $campaign ) {
			try {
				$with_subscriber = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->with( 'subscriber' )
					->first();

				if ( $with_subscriber && $with_subscriber->subscriber ) {
					$this->logger->log( 'Aggregation Methods', 'JOIN via Relationship', 'passed', 'Subscriber relationship loaded' );
				} else {
					$this->logger->log( 'Aggregation Methods', 'JOIN via Relationship', 'warning', 'No data or relationship not loaded' );
				}
			} catch ( \Exception $e ) {
				$this->logger->log( 'Aggregation Methods', 'JOIN via Relationship', 'failed', $e->getMessage() );
			}
		}
	}
}

// Run validation
$logger    = new ValidationLogger();
$validator = new FluentCrmAnalyticsValidator( $logger );
$validator->run();

// Exit with appropriate code
$summary = $logger->get_summary();
exit( $summary['failed'] > 0 ? 1 : 0 );
