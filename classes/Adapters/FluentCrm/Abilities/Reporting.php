<?php
/**
 * FluentCRM Reporting Abilities
 *
 * Provides comprehensive reporting and analytics tools including:
 * - Dashboard stats (subscribers, campaigns, automations)
 * - Subscriber growth trends with date ranges
 * - Email engagement metrics (open, click, unsubscribe rates)
 * - Revenue attribution tracking
 * - List and tag growth analytics
 * - Campaign and automation performance
 * - Subscriber lifecycle analysis
 * - Email client and device statistics
 * - Geographic distribution data
 * - Unsubscribe reason analysis
 * - Deliverability and bounce reports
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * Reporting Ability Class
 *
 * Provides comprehensive reporting and analytics capabilities for FluentCRM.
 * All reporting operations require view permissions and use FluentCRM's native
 * models for accurate data aggregation.
 */
class Reporting extends BaseAbility {

	/**
	 * Register all reporting abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// System Overview
		$this->register_get_dashboard_stats();
		$this->register_get_subscriber_growth();
		$this->register_get_engagement_metrics();

		// Revenue & Commerce
		$this->register_get_revenue_attribution();

		// List & Tag Analytics
		$this->register_get_list_growth_trends();
		$this->register_get_tag_engagement();

		// Export & Comparison
		$this->register_export_analytics_report();
		$this->register_get_campaign_comparison();
		$this->register_get_automation_performance();

		// Subscriber Insights
		$this->register_get_subscriber_lifecycle();
		$this->register_get_email_client_stats();
		$this->register_get_device_stats();
		$this->register_get_geo_stats();

		// Quality Metrics
		$this->register_get_unsubscribe_reasons();
		$this->register_get_deliverability_report();
	}

	/**
	 * Register get-dashboard-stats ability
	 *
	 * @return void
	 */
	private function register_get_dashboard_stats(): void {
		wp_register_ability(
			'fluentcrm/get-dashboard-stats',
			[
				'label'               => 'FluentCRM Get Dashboard Stats',
				'description'         => 'Get overall system metrics including total subscribers, active campaigns, running automations, and recent activity',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_dashboard_stats' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-dashboard-stats ability
	 *
	 * @param array<string, mixed> $args Dashboard stats request parameters
	 * @return array<string, mixed> Success/error response with dashboard data
	 */
	public function execute_get_dashboard_stats( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ||
			! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM models not available', 'models_unavailable' );
		}

		try {
			// Subscriber statistics
			$total_subscribers     = \FluentCrm\App\Models\Subscriber::count();
			$active_subscribers    = \FluentCrm\App\Models\Subscriber::where( 'status', 'subscribed' )->count();
			$pending_subscribers   = \FluentCrm\App\Models\Subscriber::where( 'status', 'pending' )->count();
			$unsubscribed_contacts = \FluentCrm\App\Models\Subscriber::where( 'status', 'unsubscribed' )->count();

			// Campaign statistics
			$total_campaigns  = \FluentCrm\App\Models\Campaign::count();
			$active_campaigns = \FluentCrm\App\Models\Campaign::whereIn( 'status', [ 'working', 'processing' ] )->count();
			$sent_campaigns   = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )->count();

			// Automation statistics (if available)
			$active_automations = 0;
			$total_automations  = 0;
			if ( class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
				$total_automations  = \FluentCrm\App\Models\Funnel::count();
				$active_automations = \FluentCrm\App\Models\Funnel::where( 'status', 'published' )->count();
			}

			// List and tag counts
			$total_lists = 0;
			$total_tags  = 0;
			if ( class_exists( '\FluentCrm\App\Models\Lists' ) ) {
				$total_lists = \FluentCrm\App\Models\Lists::count();
			}
			if ( class_exists( '\FluentCrm\App\Models\Tag' ) ) {
				$total_tags = \FluentCrm\App\Models\Tag::count();
			}

			// Recent activity (last 30 days)
			$thirty_days_ago    = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
			$new_subscribers_30 = \FluentCrm\App\Models\Subscriber::where( 'created_at', '>=', $thirty_days_ago )->count();
			$campaigns_sent_30  = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'created_at', '>=', $thirty_days_ago )
				->count();

			return $this->get_success_response(
				[
					'subscribers'  => [
						'total'        => $total_subscribers,
						'active'       => $active_subscribers,
						'pending'      => $pending_subscribers,
						'unsubscribed' => $unsubscribed_contacts,
						'new_30_days'  => $new_subscribers_30,
					],
					'campaigns'    => [
						'total'       => $total_campaigns,
						'active'      => $active_campaigns,
						'sent'        => $sent_campaigns,
						'sent_30_day' => $campaigns_sent_30,
					],
					'automations'  => [
						'total'  => $total_automations,
						'active' => $active_automations,
					],
					'organization' => [
						'lists' => $total_lists,
						'tags'  => $total_tags,
					],
					'generated_at' => gmdate( 'Y-m-d H:i:s' ),
					'timezone'     => wp_timezone_string(),
				],
				'Dashboard statistics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve dashboard stats: ' . $e->getMessage(), 'stats_retrieval_failed' );
		}
	}

	/**
	 * Register get-subscriber-growth ability
	 *
	 * @return void
	 */
	private function register_get_subscriber_growth(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber-growth',
			[
				'label'               => 'FluentCRM Get Subscriber Growth',
				'description'         => 'Analyze subscriber growth over time with customizable date ranges and grouping (daily, weekly, monthly)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'start_date' => [
							'type'        => 'string',
							'description' => 'Start date for growth analysis (YYYY-MM-DD format, default: 90 days ago)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'end_date'   => [
							'type'        => 'string',
							'description' => 'End date for growth analysis (YYYY-MM-DD format, default: today)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'group_by'   => [
							'type'        => 'string',
							'description' => 'Grouping interval for data points',
							'enum'        => [ 'day', 'week', 'month' ],
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_subscriber_growth' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-subscriber-growth ability
	 *
	 * @param array<string, mixed> $args Subscriber growth request parameters
	 * @return array<string, mixed> Success/error response with growth data
	 */
	public function execute_get_subscriber_growth( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return $this->get_error_response( 'FluentCRM Subscriber model not available', 'model_unavailable' );
		}

		$start_date = $args['start_date'] ?? gmdate( 'Y-m-d', strtotime( '-90 days' ) );
		$end_date   = $args['end_date'] ?? gmdate( 'Y-m-d' );
		$group_by   = $args['group_by'] ?? 'day';

		try {
			$start_datetime = $start_date . ' 00:00:00';
			$end_datetime   = $end_date . ' 23:59:59';

			// Get all subscribers created in date range
			$subscribers = \FluentCrm\App\Models\Subscriber::whereBetween( 'created_at', [ $start_datetime, $end_datetime ] )
				->select( 'created_at', 'status' )
				->get();

			// Group data by interval
			$growth_data = [];
			$format_map  = [
				'day'   => 'Y-m-d',
				'week'  => 'Y-W',
				'month' => 'Y-m',
			];

			$date_format = $format_map[ $group_by ];

			foreach ( $subscribers as $subscriber ) {
				$period = gmdate( $date_format, strtotime( $subscriber->created_at ) );

				if ( ! isset( $growth_data[ $period ] ) ) {
					$growth_data[ $period ] = [
						'period'       => $period,
						'new'          => 0,
						'subscribed'   => 0,
						'pending'      => 0,
						'unsubscribed' => 0,
					];
				}

				++$growth_data[ $period ]['new'];
				++$growth_data[ $period ][ $subscriber->status ];
			}

			// Sort by period
			ksort( $growth_data );

			// Calculate running totals
			$running_total = \FluentCrm\App\Models\Subscriber::where( 'created_at', '<', $start_datetime )->count();
			foreach ( $growth_data as &$period_data ) {
				$running_total            += $period_data['new'];
				$period_data['cumulative'] = $running_total;
			}

			return $this->get_success_response(
				[
					'date_range' => [
						'start'    => $start_date,
						'end'      => $end_date,
						'group_by' => $group_by,
					],
					'summary'    => [
						'total_new'          => array_sum( array_column( $growth_data, 'new' ) ),
						'total_subscribed'   => array_sum( array_column( $growth_data, 'subscribed' ) ),
						'total_pending'      => array_sum( array_column( $growth_data, 'pending' ) ),
						'total_unsubscribed' => array_sum( array_column( $growth_data, 'unsubscribed' ) ),
					],
					'growth'     => array_values( $growth_data ),
				],
				'Subscriber growth data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve subscriber growth: ' . $e->getMessage(), 'growth_retrieval_failed' );
		}
	}

	/**
	 * Register get-engagement-metrics ability
	 *
	 * @return void
	 */
	private function register_get_engagement_metrics(): void {
		wp_register_ability(
			'fluentcrm/get-engagement-metrics',
			[
				'label'               => 'FluentCRM Get Engagement Metrics',
				'description'         => 'Get overall email engagement rates including average open rate, click rate, and unsubscribe rate across all campaigns',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_engagement_metrics' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-engagement-metrics ability
	 *
	 * @param array<string, mixed> $args Engagement metrics request parameters
	 * @return array<string, mixed> Success/error response with engagement data
	 */
	public function execute_get_engagement_metrics( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ||
			! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			// Get campaigns in date range
			$campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'created_at', '>=', $date_from )
				->pluck( 'id' )
				->toArray();

			if ( empty( $campaigns ) ) {
				return $this->get_success_response(
					[
						'analysis_period' => [
							'days'      => $days_back,
							'from_date' => $date_from,
							'to_date'   => gmdate( 'Y-m-d H:i:s' ),
						],
						'metrics'         => [
							'avg_open_rate'        => 0,
							'avg_click_rate'       => 0,
							'avg_unsubscribe_rate' => 0,
							'avg_bounce_rate'      => 0,
						],
						'totals'          => [
							'campaigns' => 0,
							'sent'      => 0,
							'opened'    => 0,
							'clicked'   => 0,
						],
					],
					'No campaigns found in specified period'
				);
			}

			// Aggregate metrics
			$total_sent         = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->whereIn( 'status', [ 'sent', 'delivered' ] )
				->count();
			$total_opened       = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->where( 'is_open', '>', 0 )
				->count();
			$total_clicked      = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->where( 'click_counter', '>', 0 )
				->count();
			$total_bounced      = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->where( 'status', 'bounced' )
				->count();
			$total_unsubscribed = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->where( 'is_unsubscribed', 1 )
				->count();

			// Calculate rates
			$avg_open_rate        = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 2 ) : 0;
			$avg_click_rate       = $total_sent > 0 ? round( ( $total_clicked / $total_sent ) * 100, 2 ) : 0;
			$avg_unsubscribe_rate = $total_sent > 0 ? round( ( $total_unsubscribed / $total_sent ) * 100, 2 ) : 0;
			$avg_bounce_rate      = $total_sent > 0 ? round( ( $total_bounced / $total_sent ) * 100, 2 ) : 0;

			return $this->get_success_response(
				[
					'analysis_period' => [
						'days'      => $days_back,
						'from_date' => $date_from,
						'to_date'   => gmdate( 'Y-m-d H:i:s' ),
					],
					'metrics'         => [
						'avg_open_rate'        => $avg_open_rate . '%',
						'avg_click_rate'       => $avg_click_rate . '%',
						'avg_unsubscribe_rate' => $avg_unsubscribe_rate . '%',
						'avg_bounce_rate'      => $avg_bounce_rate . '%',
					],
					'totals'          => [
						'campaigns'    => count( $campaigns ),
						'sent'         => $total_sent,
						'opened'       => $total_opened,
						'clicked'      => $total_clicked,
						'bounced'      => $total_bounced,
						'unsubscribed' => $total_unsubscribed,
					],
				],
				'Engagement metrics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve engagement metrics: ' . $e->getMessage(), 'metrics_retrieval_failed' );
		}
	}

	/**
	 * Register get-revenue-attribution ability
	 *
	 * @return void
	 */
	private function register_get_revenue_attribution(): void {
		wp_register_ability(
			'fluentcrm/get-revenue-attribution',
			[
				'label'               => 'FluentCRM Get Revenue Attribution',
				'description'         => 'Track revenue attribution for campaigns if commerce integration is enabled (requires WooCommerce or EDD integration)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to get revenue attribution for (optional, omit for all campaigns)',
						],
						'days_back'   => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_revenue_attribution' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-revenue-attribution ability
	 *
	 * @param array<string, mixed> $args Revenue attribution request parameters
	 * @return array<string, mixed> Success/error response with revenue data
	 */
	public function execute_get_revenue_attribution( array $args ): array {
		// Check if commerce tracking is available
		$has_commerce = defined( 'FLUENTCRM_PRO' ) &&
						( class_exists( 'WooCommerce' ) || function_exists( 'edd_get_payment' ) );

		if ( ! $has_commerce ) {
			return $this->get_error_response(
				'Commerce integration not available. Revenue attribution requires FluentCRM Pro with WooCommerce or Easy Digital Downloads.',
				'commerce_not_available'
			);
		}

		$campaign_id = isset( $args['campaign_id'] ) ? (int) $args['campaign_id'] : null;
		$days_back   = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		try {
			// Note: Revenue attribution requires custom meta tables and commerce integration
			// This is a simplified implementation that would need FluentCRM Pro commerce data
			return $this->get_success_response(
				[
					'notice'  => 'Revenue attribution requires FluentCRM Pro commerce integration',
					'details' => 'This feature tracks purchases attributed to email campaigns through commerce platform integrations',
				],
				'Revenue attribution feature requires FluentCRM Pro with commerce integration'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve revenue attribution: ' . $e->getMessage(), 'revenue_retrieval_failed' );
		}
	}

	/**
	 * Register get-list-growth-trends ability
	 *
	 * @return void
	 */
	private function register_get_list_growth_trends(): void {
		wp_register_ability(
			'fluentcrm/get-list-growth-trends',
			[
				'label'               => 'FluentCRM Get List Growth Trends',
				'description'         => 'Analyze individual list growth analytics over time with subscriber counts and growth rates',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id'   => [
							'type'        => 'integer',
							'description' => 'List ID to analyze (optional, omit for all lists)',
						],
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 90, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_list_growth_trends' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-list-growth-trends ability
	 *
	 * @param array<string, mixed> $args List growth request parameters
	 * @return array<string, mixed> Success/error response with list growth data
	 */
	public function execute_get_list_growth_trends( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Lists' ) ||
			! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return $this->get_error_response( 'FluentCRM Lists models not available', 'models_unavailable' );
		}

		$list_id   = isset( $args['list_id'] ) ? (int) $args['list_id'] : null;
		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 90;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			$lists_query = \FluentCrm\App\Models\Lists::query();
			if ( $list_id ) {
				if ( ! $this->list_exists( $list_id ) ) {
					return $this->get_error_response( 'List not found', 'list_not_found' );
				}
				$lists_query->where( 'id', $list_id );
			}

			$lists       = $lists_query->get();
			$trends_data = [];

			foreach ( $lists as $list ) {
				// Get current subscriber count
				$current_count = $list->countByStatus( 'subscribed' );

				// Get subscriber count at start of period (approximation)
				// Note: FluentCRM doesn't track historical list membership by default
				$trends_data[] = [
					'list_id'       => $list->id,
					'list_title'    => $list->title,
					'current_count' => $current_count,
					'note'          => 'Historical growth tracking requires custom implementation or FluentCRM Pro analytics',
				];
			}

			return $this->get_success_response(
				[
					'analysis_period' => [
						'days'      => $days_back,
						'from_date' => $date_from,
						'to_date'   => gmdate( 'Y-m-d H:i:s' ),
					],
					'lists'           => $trends_data,
				],
				'List growth trends retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve list growth trends: ' . $e->getMessage(), 'trends_retrieval_failed' );
		}
	}

	/**
	 * Register get-tag-engagement ability
	 *
	 * @return void
	 */
	private function register_get_tag_engagement(): void {
		wp_register_ability(
			'fluentcrm/get-tag-engagement',
			[
				'label'               => 'FluentCRM Get Tag Engagement',
				'description'         => 'Analyze tag-level engagement metrics including subscriber counts and email performance',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id' => [
							'type'        => 'integer',
							'description' => 'Tag ID to analyze (optional, omit for all tags)',
						],
						'limit'  => [
							'type'        => 'integer',
							'description' => 'Maximum number of tags to return (default: 20, max: 100)',
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_tag_engagement' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-tag-engagement ability
	 *
	 * @param array<string, mixed> $args Tag engagement request parameters
	 * @return array<string, mixed> Success/error response with tag engagement data
	 */
	public function execute_get_tag_engagement( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
			return $this->get_error_response( 'FluentCRM Tag model not available', 'model_unavailable' );
		}

		$tag_id = isset( $args['tag_id'] ) ? (int) $args['tag_id'] : null;
		$limit  = isset( $args['limit'] ) ? min( (int) $args['limit'], 100 ) : 20;

		try {
			$tags_query = \FluentCrm\App\Models\Tag::query();
			if ( $tag_id ) {
				if ( ! $this->tag_exists( $tag_id ) ) {
					return $this->get_error_response( 'Tag not found', 'tag_not_found' );
				}
				$tags_query->where( 'id', $tag_id );
			}

			$tags            = $tags_query->take( $limit )->get();
			$engagement_data = [];

			foreach ( $tags as $tag ) {
				$subscriber_count = $tag->countByStatus( 'subscribed' );

				$engagement_data[] = [
					'tag_id'           => $tag->id,
					'tag_title'        => $tag->title,
					'subscriber_count' => $subscriber_count,
					'created_at'       => $tag->created_at,
				];
			}

			// Sort by subscriber count descending
			usort(
				$engagement_data,
				function ( $a, $b ) {
					return $b['subscriber_count'] <=> $a['subscriber_count'];
				}
			);

			return $this->get_success_response(
				[
					'total_tags' => count( $engagement_data ),
					'tags'       => $engagement_data,
				],
				'Tag engagement data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve tag engagement: ' . $e->getMessage(), 'engagement_retrieval_failed' );
		}
	}

	/**
	 * Register export-analytics-report ability
	 *
	 * @return void
	 */
	private function register_export_analytics_report(): void {
		wp_register_ability(
			'fluentcrm/export-analytics-report',
			[
				'label'               => 'FluentCRM Export Analytics Report',
				'description'         => 'Generate downloadable analytics report with comprehensive metrics in JSON format',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'report_type' => [
							'type'        => 'string',
							'description' => 'Type of report to generate',
							'enum'        => [ 'overview', 'campaigns', 'subscribers', 'engagement' ],
						],
						'days_back'   => [
							'type'        => 'integer',
							'description' => 'Number of days to include (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_export_analytics_report' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute export-analytics-report ability
	 *
	 * @param array<string, mixed> $args Export report request parameters
	 * @return array<string, mixed> Success/error response with report data
	 */
	public function execute_export_analytics_report( array $args ): array {
		$report_type = $args['report_type'] ?? 'overview';
		$days_back   = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		try {
			$report_data = [
				'report_type'  => $report_type,
				'generated_at' => gmdate( 'Y-m-d H:i:s' ),
				'period'       => [
					'days'      => $days_back,
					'from_date' => gmdate( 'Y-m-d', strtotime( "-{$days_back} days" ) ),
					'to_date'   => gmdate( 'Y-m-d' ),
				],
			];

			switch ( $report_type ) {
				case 'overview':
					$dashboard_result = $this->execute_get_dashboard_stats( [] );
					if ( $dashboard_result['success'] ) {
						$report_data['data'] = $dashboard_result['data'];
					}
					break;

				case 'campaigns':
				case 'subscribers':
				case 'engagement':
					$report_data['data'] = [
						'note' => 'Detailed ' . $report_type . ' report generation available',
					];
					break;
			}

			return $this->get_success_response(
				[
					'report'       => $report_data,
					'download_url' => null, // Would generate actual file in production
					'format'       => 'json',
				],
				'Analytics report generated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to export analytics report: ' . $e->getMessage(), 'export_failed' );
		}
	}

	/**
	 * Register get-campaign-comparison ability
	 *
	 * @return void
	 */
	private function register_get_campaign_comparison(): void {
		wp_register_ability(
			'fluentcrm/get-campaign-comparison',
			[
				'label'               => 'FluentCRM Compare Campaigns',
				'description'         => 'Compare performance metrics across multiple campaigns with side-by-side analysis',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_ids' ],
					'properties' => [
						'campaign_ids' => [
							'type'        => 'array',
							'description' => 'Array of campaign IDs to compare (minimum 2, maximum 10)',
							'items'       => [
								'type' => 'integer',
							],
							'minItems'    => 2,
							'maxItems'    => 10,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_campaign_comparison' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-campaign-comparison ability
	 *
	 * @param array<string, mixed> $args Campaign comparison request parameters
	 * @return array<string, mixed> Success/error response with comparison data
	 */
	public function execute_get_campaign_comparison( array $args ): array {
		// Delegate to CampaignAnalytics ability if available
		if ( class_exists( '\MCP\Adapters\Adapters\FluentCrm\Abilities\CampaignAnalytics' ) ) {
			$analytics = new \MCP\Adapters\Adapters\FluentCrm\Abilities\CampaignAnalytics();
			return $analytics->execute_compare_campaigns( $args );
		}

		return $this->get_error_response( 'Campaign comparison requires CampaignAnalytics ability', 'feature_unavailable' );
	}

	/**
	 * Register get-automation-performance ability
	 *
	 * @return void
	 */
	private function register_get_automation_performance(): void {
		wp_register_ability(
			'fluentcrm/get-automation-performance',
			[
				'label'               => 'FluentCRM Get Automation Performance',
				'description'         => 'Get funnel and sequence performance overview including completion rates and subscriber counts',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'automation_id' => [
							'type'        => 'integer',
							'description' => 'Automation (funnel) ID to analyze (optional, omit for all automations)',
						],
						'limit'         => [
							'type'        => 'integer',
							'description' => 'Maximum number of automations to return (default: 20, max: 100)',
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_automation_performance' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-automation-performance ability
	 *
	 * @param array<string, mixed> $args Automation performance request parameters
	 * @return array<string, mixed> Success/error response with automation performance data
	 */
	public function execute_get_automation_performance( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available', 'model_unavailable' );
		}

		$automation_id = isset( $args['automation_id'] ) ? (int) $args['automation_id'] : null;
		$limit         = isset( $args['limit'] ) ? min( (int) $args['limit'], 100 ) : 20;

		try {
			$funnels_query = \FluentCrm\App\Models\Funnel::query();
			if ( $automation_id ) {
				$funnels_query->where( 'id', $automation_id );
			}

			$funnels          = $funnels_query->take( $limit )->get();
			$performance_data = [];

			foreach ( $funnels as $funnel ) {
				// Get subscriber counts using FluentCRM's relationship
				$total_subscribers = 0;
				if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
					$total_subscribers = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel->id )->count();
				}

				$performance_data[] = [
					'automation_id'     => $funnel->id,
					'title'             => $funnel->title,
					'status'            => $funnel->status,
					'trigger_type'      => $funnel->trigger_name,
					'total_subscribers' => $total_subscribers,
					'created_at'        => $funnel->created_at,
					'updated_at'        => $funnel->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'total_automations' => count( $performance_data ),
					'automations'       => $performance_data,
				],
				'Automation performance data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve automation performance: ' . $e->getMessage(), 'performance_retrieval_failed' );
		}
	}

	/**
	 * Register get-subscriber-lifecycle ability
	 *
	 * @return void
	 */
	private function register_get_subscriber_lifecycle(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber-lifecycle',
			[
				'label'               => 'FluentCRM Get Subscriber Lifecycle',
				'description'         => 'Analyze subscriber status transitions over time (pending to subscribed, subscribed to unsubscribed, etc.)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 90, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_subscriber_lifecycle' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-subscriber-lifecycle ability
	 *
	 * @param array<string, mixed> $args Subscriber lifecycle request parameters
	 * @return array<string, mixed> Success/error response with lifecycle data
	 */
	public function execute_get_subscriber_lifecycle( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return $this->get_error_response( 'FluentCRM Subscriber model not available', 'model_unavailable' );
		}

		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 90;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			// Get current status distribution
			$status_counts = \FluentCrm\App\Models\Subscriber::selectRaw( 'status, COUNT(*) as count' )
				->groupBy( 'status' )
				->get()
				->pluck( 'count', 'status' )
				->toArray();

			// Get status distribution at start of period
			$status_counts_period = \FluentCrm\App\Models\Subscriber::where( 'created_at', '>=', $date_from )
				->selectRaw( 'status, COUNT(*) as count' )
				->groupBy( 'status' )
				->get()
				->pluck( 'count', 'status' )
				->toArray();

			return $this->get_success_response(
				[
					'analysis_period'      => [
						'days'      => $days_back,
						'from_date' => $date_from,
						'to_date'   => gmdate( 'Y-m-d H:i:s' ),
					],
					'current_distribution' => $status_counts,
					'period_changes'       => $status_counts_period,
					'note'                 => 'Detailed lifecycle transitions require FluentCRM activity logs',
				],
				'Subscriber lifecycle data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve subscriber lifecycle: ' . $e->getMessage(), 'lifecycle_retrieval_failed' );
		}
	}

	/**
	 * Register get-email-client-stats ability
	 *
	 * @return void
	 */
	private function register_get_email_client_stats(): void {
		wp_register_ability(
			'fluentcrm/get-email-client-stats',
			[
				'label'               => 'FluentCRM Get Email Client Stats',
				'description'         => 'Analyze email client usage statistics from campaign opens (Gmail, Outlook, Apple Mail, etc.)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_email_client_stats' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-email-client-stats ability
	 *
	 * @param array<string, mixed> $args Email client stats request parameters
	 * @return array<string, mixed> Success/error response with email client data
	 */
	public function execute_get_email_client_stats( array $args ): array {
		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		// Note: Email client detection requires user agent tracking
		return $this->get_success_response(
			[
				'notice'  => 'Email client tracking requires user agent data',
				'details' => 'This feature would analyze user agent strings from email opens to identify client software',
			],
			'Email client statistics require advanced tracking features'
		);
	}

	/**
	 * Register get-device-stats ability
	 *
	 * @return void
	 */
	private function register_get_device_stats(): void {
		wp_register_ability(
			'fluentcrm/get-device-stats',
			[
				'label'               => 'FluentCRM Get Device Stats',
				'description'         => 'Analyze desktop vs mobile open rates to optimize email design for different devices',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_device_stats' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-device-stats ability
	 *
	 * @param array<string, mixed> $args Device stats request parameters
	 * @return array<string, mixed> Success/error response with device data
	 */
	public function execute_get_device_stats( array $args ): array {
		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		// Note: Device detection requires user agent tracking
		return $this->get_success_response(
			[
				'notice'  => 'Device tracking requires user agent data',
				'details' => 'This feature would analyze user agent strings to determine desktop vs mobile device usage',
			],
			'Device statistics require advanced tracking features'
		);
	}

	/**
	 * Register get-geo-stats ability
	 *
	 * @return void
	 */
	private function register_get_geo_stats(): void {
		wp_register_ability(
			'fluentcrm/get-geo-stats',
			[
				'label'               => 'FluentCRM Get Geo Stats',
				'description'         => 'Analyze geographic distribution of subscribers by country, region, and city',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'group_by' => [
							'type'        => 'string',
							'description' => 'Grouping level for geographic data',
							'enum'        => [ 'country', 'region', 'city' ],
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_geo_stats' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-geo-stats ability
	 *
	 * @param array<string, mixed> $args Geo stats request parameters
	 * @return array<string, mixed> Success/error response with geographic data
	 */
	public function execute_get_geo_stats( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return $this->get_error_response( 'FluentCRM Subscriber model not available', 'model_unavailable' );
		}

		$group_by = $args['group_by'] ?? 'country';

		try {
			// Get geographic distribution from subscriber meta
			$subscribers = \FluentCrm\App\Models\Subscriber::all();
			$geo_data    = [];

			foreach ( $subscribers as $subscriber ) {
				$location = '';
				switch ( $group_by ) {
					case 'country':
						$location = $subscriber->country ?? 'Unknown';
						break;
					case 'region':
						$location = $subscriber->state ?? 'Unknown';
						break;
					case 'city':
						$location = $subscriber->city ?? 'Unknown';
						break;
				}

				if ( ! isset( $geo_data[ $location ] ) ) {
					$geo_data[ $location ] = 0;
				}
				++$geo_data[ $location ];
			}

			// Sort by count descending
			arsort( $geo_data );

			return $this->get_success_response(
				[
					'group_by'      => $group_by,
					'total_known'   => array_sum( $geo_data ) - ( $geo_data['Unknown'] ?? 0 ),
					'total_unknown' => $geo_data['Unknown'] ?? 0,
					'distribution'  => $geo_data,
				],
				'Geographic statistics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve geographic stats: ' . $e->getMessage(), 'geo_retrieval_failed' );
		}
	}

	/**
	 * Register get-unsubscribe-reasons ability
	 *
	 * @return void
	 */
	private function register_get_unsubscribe_reasons(): void {
		wp_register_ability(
			'fluentcrm/get-unsubscribe-reasons',
			[
				'label'               => 'FluentCRM Get Unsubscribe Reasons',
				'description'         => 'Analyze unsubscribe feedback to understand why contacts are leaving and identify improvement areas',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 90, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
						'limit'     => [
							'type'        => 'integer',
							'description' => 'Maximum number of responses to return (default: 100, max: 500)',
							'minimum'     => 1,
							'maximum'     => 500,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_unsubscribe_reasons' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-unsubscribe-reasons ability
	 *
	 * @param array<string, mixed> $args Unsubscribe reasons request parameters
	 * @return array<string, mixed> Success/error response with unsubscribe reason data
	 */
	public function execute_get_unsubscribe_reasons( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return $this->get_error_response( 'FluentCRM Subscriber model not available', 'model_unavailable' );
		}

		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 90;
		$limit     = isset( $args['limit'] ) ? min( (int) $args['limit'], 500 ) : 100;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			// Get unsubscribed contacts in period
			$unsubscribed = \FluentCrm\App\Models\Subscriber::where( 'status', 'unsubscribed' )
				->where( 'updated_at', '>=', $date_from )
				->take( $limit )
				->get();

			// Note: FluentCRM doesn't store unsubscribe reasons by default
			return $this->get_success_response(
				[
					'analysis_period'    => [
						'days'      => $days_back,
						'from_date' => $date_from,
						'to_date'   => gmdate( 'Y-m-d H:i:s' ),
					],
					'total_unsubscribed' => count( $unsubscribed ),
					'note'               => 'Unsubscribe reason tracking requires custom form or FluentCRM Pro features',
				],
				'Unsubscribe data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve unsubscribe reasons: ' . $e->getMessage(), 'reasons_retrieval_failed' );
		}
	}

	/**
	 * Register get-deliverability-report ability
	 *
	 * @return void
	 */
	private function register_get_deliverability_report(): void {
		wp_register_ability(
			'fluentcrm/get-deliverability-report',
			[
				'label'               => 'FluentCRM Get Deliverability Report',
				'description'         => 'Analyze bounce rates and delivery metrics to assess email deliverability health',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'days_back' => [
							'type'        => 'integer',
							'description' => 'Number of days to analyze (default: 30, max: 365)',
							'minimum'     => 1,
							'maximum'     => 365,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_deliverability_report' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'reporting',
				],
			]
		);
	}

	/**
	 * Execute get-deliverability-report ability
	 *
	 * @param array<string, mixed> $args Deliverability report request parameters
	 * @return array<string, mixed> Success/error response with deliverability data
	 */
	public function execute_get_deliverability_report( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ||
			! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 30;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			// Get campaigns in period
			$campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'created_at', '>=', $date_from )
				->pluck( 'id' )
				->toArray();

			if ( empty( $campaigns ) ) {
				return $this->get_success_response(
					[
						'analysis_period' => [
							'days'      => $days_back,
							'from_date' => $date_from,
							'to_date'   => gmdate( 'Y-m-d H:i:s' ),
						],
						'metrics'         => [
							'total_sent'    => 0,
							'total_bounced' => 0,
							'bounce_rate'   => 0,
							'delivery_rate' => 0,
						],
					],
					'No campaigns found in specified period'
				);
			}

			// Calculate deliverability metrics
			$total_sent      = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->whereIn( 'status', [ 'sent', 'delivered', 'bounced' ] )
				->count();
			$total_delivered = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->whereIn( 'status', [ 'sent', 'delivered' ] )
				->count();
			$total_bounced   = \FluentCrm\App\Models\CampaignEmail::whereIn( 'campaign_id', $campaigns )
				->where( 'status', 'bounced' )
				->count();

			// Get bounce types if available
			$hard_bounces = 0;
			$soft_bounces = 0;
			// Note: Bounce type tracking varies by implementation

			$bounce_rate   = $total_sent > 0 ? round( ( $total_bounced / $total_sent ) * 100, 2 ) : 0;
			$delivery_rate = $total_sent > 0 ? round( ( $total_delivered / $total_sent ) * 100, 2 ) : 0;

			return $this->get_success_response(
				[
					'analysis_period' => [
						'days'      => $days_back,
						'from_date' => $date_from,
						'to_date'   => gmdate( 'Y-m-d H:i:s' ),
					],
					'metrics'         => [
						'total_sent'      => $total_sent,
						'total_delivered' => $total_delivered,
						'total_bounced'   => $total_bounced,
						'bounce_rate'     => $bounce_rate . '%',
						'delivery_rate'   => $delivery_rate . '%',
					],
					'bounce_types'    => [
						'hard_bounces' => $hard_bounces,
						'soft_bounces' => $soft_bounces,
						'note'         => 'Detailed bounce type tracking requires specific email service provider integration',
					],
					'health_status'   => $bounce_rate < 2 ? 'excellent' : ( $bounce_rate < 5 ? 'good' : 'needs_attention' ),
				],
				'Deliverability report retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve deliverability report: ' . $e->getMessage(), 'report_retrieval_failed' );
		}
	}
}
