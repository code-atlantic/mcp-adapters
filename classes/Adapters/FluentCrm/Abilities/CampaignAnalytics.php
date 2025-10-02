<?php
/**
 * FluentCRM Campaign Analytics Abilities
 *
 * Provides comprehensive campaign analytics and performance tracking tools including:
 * - Campaign metrics (sent, opened, clicked, bounced, unsubscribed, rates)
 * - Contact engagement tracking (campaign recipients with status)
 * - Click tracking (detailed URL click data)
 * - Open tracking (timestamps and engagement patterns)
 * - Subject line performance comparison
 * - Send time optimization analysis
 * - Campaign performance comparison
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * CampaignAnalytics Ability Class
 *
 * Provides analytics and performance tracking for email campaigns in FluentCRM.
 * All analytics operations require view permissions and use FluentCRM's native
 * CampaignEmail and CampaignUrlMetric models for accurate tracking data.
 */
class CampaignAnalytics extends BaseAbility {

	/**
	 * Register all campaign analytics abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Core Analytics
		$this->register_get_campaign_analytics();
		$this->register_get_campaign_contacts();
		$this->register_get_campaign_clicks();
		$this->register_get_campaign_opens();

		// Advanced Analytics
		$this->register_get_email_performance_by_subject();
		$this->register_get_send_time_optimization();
		$this->register_compare_campaigns();
	}

	/**
	 * Register get-campaign-analytics ability
	 *
	 * @return void
	 */
	private function register_get_campaign_analytics(): void {
		wp_register_ability(
			'fluentcrm/get-campaign-analytics',
			[
				'label'               => 'FluentCRM Get Campaign Analytics',
				'description'         => 'Get complete campaign metrics including sent, opened, clicked, bounced, unsubscribed counts and rates',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to get analytics for',
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_campaign_analytics' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-campaign-analytics ability
	 *
	 * @param array<string, mixed> $args Analytics request parameters
	 * @return array<string, mixed> Success/error response with analytics data
	 */
	public function execute_get_campaign_analytics( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) || ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$campaign_id = (int) $args['campaign_id'];

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'campaign_not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Get email stats
			$total_sent         = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->whereIn( 'status', [ 'sent', 'delivered' ] )
				->count();
			$total_opened       = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->where( 'is_open', '>', 0 )
				->count();
			$total_clicked      = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->where( 'click_counter', '>', 0 )
				->count();
			$total_bounced      = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->where( 'status', 'bounced' )
				->count();
			$total_unsubscribed = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->where( 'is_unsubscribed', 1 )
				->count();

			// Calculate rates
			$open_rate          = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 2 ) : 0;
			$click_rate         = $total_sent > 0 ? round( ( $total_clicked / $total_sent ) * 100, 2 ) : 0;
			$click_to_open_rate = $total_opened > 0 ? round( ( $total_clicked / $total_opened ) * 100, 2 ) : 0;
			$bounce_rate        = $total_sent > 0 ? round( ( $total_bounced / $total_sent ) * 100, 2 ) : 0;
			$unsubscribe_rate   = $total_sent > 0 ? round( ( $total_unsubscribed / $total_sent ) * 100, 2 ) : 0;

			return $this->get_success_response(
				[
					'campaign_id'      => $campaign->id,
					'campaign_title'   => $campaign->title,
					'campaign_subject' => $campaign->subject,
					'campaign_status'  => $campaign->status,
					'metrics'          => [
						'sent'         => $total_sent,
						'opened'       => $total_opened,
						'clicked'      => $total_clicked,
						'bounced'      => $total_bounced,
						'unsubscribed' => $total_unsubscribed,
					],
					'rates'            => [
						'open_rate'          => $open_rate . '%',
						'click_rate'         => $click_rate . '%',
						'click_to_open_rate' => $click_to_open_rate . '%',
						'bounce_rate'        => $bounce_rate . '%',
						'unsubscribe_rate'   => $unsubscribe_rate . '%',
					],
					'scheduled_at'     => $campaign->scheduled_at,
					'created_at'       => $campaign->created_at,
				],
				'Campaign analytics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve campaign analytics: ' . $e->getMessage(), 'analytics_retrieval_failed' );
		}
	}

	/**
	 * Register get-campaign-contacts ability
	 *
	 * @return void
	 */
	private function register_get_campaign_contacts(): void {
		wp_register_ability(
			'fluentcrm/get-campaign-contacts',
			[
				'label'               => 'FluentCRM Get Campaign Contacts',
				'description'         => 'List contacts who received campaign with optional status filter (sent, opened, clicked, bounced, unsubscribed)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to get contacts for',
						],
						'status'      => [
							'type'        => 'string',
							'description' => 'Filter by status: sent, opened, clicked, bounced, unsubscribed',
							'enum'        => [ 'sent', 'opened', 'clicked', 'bounced', 'unsubscribed' ],
						],
						'limit'       => [
							'type'        => 'integer',
							'description' => 'Maximum number of contacts to return (default: 50, max: 500)',
							'minimum'     => 1,
							'maximum'     => 500,
						],
						'offset'      => [
							'type'        => 'integer',
							'description' => 'Number of contacts to skip for pagination (default: 0)',
							'minimum'     => 0,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_campaign_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-campaign-contacts ability
	 *
	 * @param array<string, mixed> $args Contact request parameters
	 * @return array<string, mixed> Success/error response with contact data
	 */
	public function execute_get_campaign_contacts( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM CampaignEmail model not available', 'model_unavailable' );
		}

		$campaign_id = (int) $args['campaign_id'];
		$status      = $args['status'] ?? null;
		$limit       = isset( $args['limit'] ) ? min( (int) $args['limit'], 500 ) : 50;
		$offset      = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'campaign_not_found' );
		}

		try {
			$query = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->with( 'subscriber' );

			// Apply status filter
			if ( $status ) {
				switch ( $status ) {
					case 'sent':
						$query->whereIn( 'status', [ 'sent', 'delivered' ] );
						break;
					case 'opened':
						$query->where( 'is_open', '>', 0 );
						break;
					case 'clicked':
						$query->where( 'click_counter', '>', 0 );
						break;
					case 'bounced':
						$query->where( 'status', 'bounced' );
						break;
					case 'unsubscribed':
						$query->where( 'is_unsubscribed', 1 );
						break;
				}
			}

			// Get total count before pagination
			$total = $query->count();

			// Apply pagination
			$campaign_emails = $query->skip( $offset )->take( $limit )->get();

			$contacts = [];
			foreach ( $campaign_emails as $email ) {
				if ( ! $email->subscriber ) {
					continue;
				}

				$contacts[] = [
					'subscriber_id' => $email->subscriber->id,
					'email'         => $email->subscriber->email,
					'name'          => $email->subscriber->full_name,
					'status'        => $email->status,
					'sent_at'       => $email->created_at,
					'opened'        => $email->is_open > 0,
					'open_count'    => $email->is_open,
					'last_open_at'  => $email->is_open > 0 ? $email->updated_at : null,
					'clicked'       => $email->click_counter > 0,
					'click_count'   => $email->click_counter,
					'bounced'       => 'bounced' === $email->status,
					'unsubscribed'  => 1 === $email->is_unsubscribed,
				];
			}

			return $this->get_success_response(
				[
					'campaign_id' => $campaign_id,
					'total'       => $total,
					'limit'       => $limit,
					'offset'      => $offset,
					'contacts'    => $contacts,
				],
				'Campaign contacts retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve campaign contacts: ' . $e->getMessage(), 'contacts_retrieval_failed' );
		}
	}

	/**
	 * Register get-campaign-clicks ability
	 *
	 * @return void
	 */
	private function register_get_campaign_clicks(): void {
		wp_register_ability(
			'fluentcrm/get-campaign-clicks',
			[
				'label'               => 'FluentCRM Get Campaign Clicks',
				'description'         => 'Get detailed click tracking data for campaign including URLs and click counts',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to get click tracking for',
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_campaign_clicks' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-campaign-clicks ability
	 *
	 * @param array<string, mixed> $args Click tracking request parameters
	 * @return array<string, mixed> Success/error response with click data
	 */
	public function execute_get_campaign_clicks( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignUrlMetric' ) ) {
			return $this->get_error_response( 'FluentCRM CampaignUrlMetric model not available', 'model_unavailable' );
		}

		$campaign_id = (int) $args['campaign_id'];

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'campaign_not_found' );
		}

		try {
			$url_metrics = \FluentCrm\App\Models\CampaignUrlMetric::where( 'campaign_id', $campaign_id )
				->orderBy( 'counter', 'desc' )
				->get();

			$total_clicks = 0;
			$urls         = [];

			foreach ( $url_metrics as $metric ) {
				$total_clicks += $metric->counter;
				$urls[]        = [
					'url'           => $metric->url,
					'click_count'   => $metric->counter,
					'unique_clicks' => $metric->unique_clicks ?? 0,
					'short_url'     => $metric->short_url ?? null,
				];
			}

			return $this->get_success_response(
				[
					'campaign_id'  => $campaign_id,
					'total_clicks' => $total_clicks,
					'unique_urls'  => count( $urls ),
					'url_data'     => $urls,
				],
				'Campaign click data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve campaign clicks: ' . $e->getMessage(), 'clicks_retrieval_failed' );
		}
	}

	/**
	 * Register get-campaign-opens ability
	 *
	 * @return void
	 */
	private function register_get_campaign_opens(): void {
		wp_register_ability(
			'fluentcrm/get-campaign-opens',
			[
				'label'               => 'FluentCRM Get Campaign Opens',
				'description'         => 'Get open tracking data for campaign with timestamps and engagement patterns',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to get open tracking for',
						],
						'limit'       => [
							'type'        => 'integer',
							'description' => 'Maximum number of opens to return (default: 100, max: 500)',
							'minimum'     => 1,
							'maximum'     => 500,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_campaign_opens' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-campaign-opens ability
	 *
	 * @param array<string, mixed> $args Open tracking request parameters
	 * @return array<string, mixed> Success/error response with open data
	 */
	public function execute_get_campaign_opens( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM CampaignEmail model not available', 'model_unavailable' );
		}

		$campaign_id = (int) $args['campaign_id'];
		$limit       = isset( $args['limit'] ) ? min( (int) $args['limit'], 500 ) : 100;

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'campaign_not_found' );
		}

		try {
			$opened_emails = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
				->where( 'is_open', '>', 0 )
				->with( 'subscriber' )
				->orderBy( 'updated_at', 'desc' )
				->take( $limit )
				->get();

			$total_opens  = 0;
			$unique_opens = 0;
			$opens_data   = [];

			foreach ( $opened_emails as $email ) {
				if ( ! $email->subscriber ) {
					continue;
				}

				$total_opens += $email->is_open;
				++$unique_opens;

				$opens_data[] = [
					'subscriber_id' => $email->subscriber->id,
					'email'         => $email->subscriber->email,
					'name'          => $email->subscriber->full_name,
					'open_count'    => $email->is_open,
					'first_open_at' => $email->created_at,
					'last_open_at'  => $email->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'campaign_id'  => $campaign_id,
					'total_opens'  => $total_opens,
					'unique_opens' => $unique_opens,
					'opens_data'   => $opens_data,
				],
				'Campaign open data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve campaign opens: ' . $e->getMessage(), 'opens_retrieval_failed' );
		}
	}

	/**
	 * Register get-email-performance-by-subject ability
	 *
	 * @return void
	 */
	private function register_get_email_performance_by_subject(): void {
		wp_register_ability(
			'fluentcrm/get-email-performance-by-subject',
			[
				'label'               => 'FluentCRM Get Email Performance By Subject',
				'description'         => 'Compare subject line performance across multiple campaigns to identify effective patterns',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'campaign_ids' => [
							'type'        => 'array',
							'description' => 'Array of campaign IDs to compare (if empty, analyzes all campaigns)',
							'items'       => [
								'type' => 'integer',
							],
						],
						'limit'        => [
							'type'        => 'integer',
							'description' => 'Maximum number of campaigns to analyze (default: 20, max: 100)',
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_email_performance_by_subject' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-email-performance-by-subject ability
	 *
	 * @param array<string, mixed> $args Subject performance request parameters
	 * @return array<string, mixed> Success/error response with subject performance data
	 */
	public function execute_get_email_performance_by_subject( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) || ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$campaign_ids = $args['campaign_ids'] ?? [];
		$limit        = isset( $args['limit'] ) ? min( (int) $args['limit'], 100 ) : 20;

		try {
			$query = \FluentCrm\App\Models\Campaign::whereIn( 'status', [ 'sent', 'processing', 'archived' ] );

			if ( ! empty( $campaign_ids ) ) {
				$query->whereIn( 'id', $campaign_ids );
			}

			$campaigns = $query->orderBy( 'created_at', 'desc' )
				->take( $limit )
				->get();

			$performance_data = [];

			foreach ( $campaigns as $campaign ) {
				$total_sent    = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->whereIn( 'status', [ 'sent', 'delivered' ] )
					->count();
				$total_opened  = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'is_open', '>', 0 )
					->count();
				$total_clicked = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'click_counter', '>', 0 )
					->count();

				$open_rate  = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 2 ) : 0;
				$click_rate = $total_sent > 0 ? round( ( $total_clicked / $total_sent ) * 100, 2 ) : 0;

				$performance_data[] = [
					'campaign_id' => $campaign->id,
					'subject'     => $campaign->subject,
					'sent'        => $total_sent,
					'opened'      => $total_opened,
					'clicked'     => $total_clicked,
					'open_rate'   => $open_rate . '%',
					'click_rate'  => $click_rate . '%',
					'sent_at'     => $campaign->scheduled_at ?? $campaign->created_at,
				];
			}

			// Sort by open rate descending
			usort(
				$performance_data,
				function ( $a, $b ) {
					return (float) $b['open_rate'] <=> (float) $a['open_rate'];
				}
			);

			return $this->get_success_response(
				[
					'total_campaigns' => count( $performance_data ),
					'performance'     => $performance_data,
				],
				'Subject line performance data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve subject performance: ' . $e->getMessage(), 'performance_retrieval_failed' );
		}
	}

	/**
	 * Register get-send-time-optimization ability
	 *
	 * @return void
	 */
	private function register_get_send_time_optimization(): void {
		wp_register_ability(
			'fluentcrm/get-send-time-optimization',
			[
				'label'               => 'FluentCRM Get Send Time Optimization',
				'description'         => 'Analyze best send times based on historical campaign performance data (day of week and hour)',
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
				'execute_callback'    => [ $this, 'execute_get_send_time_optimization' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute get-send-time-optimization ability
	 *
	 * @param array<string, mixed> $args Send time optimization request parameters
	 * @return array<string, mixed> Success/error response with send time optimization data
	 */
	public function execute_get_send_time_optimization( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) || ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$days_back = isset( $args['days_back'] ) ? min( (int) $args['days_back'], 365 ) : 90;

		try {
			$date_from = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_back} days" ) );

			$campaigns = \FluentCrm\App\Models\Campaign::where( 'status', 'sent' )
				->where( 'scheduled_at', '>=', $date_from )
				->get();

			$day_performance  = [];
			$hour_performance = [];

			foreach ( $campaigns as $campaign ) {
				$send_date   = $campaign->scheduled_at ?? $campaign->created_at;
				$day_of_week = gmdate( 'l', strtotime( $send_date ) );
				$hour        = (int) gmdate( 'H', strtotime( $send_date ) );

				$total_sent   = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->whereIn( 'status', [ 'sent', 'delivered' ] )
					->count();
				$total_opened = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'is_open', '>', 0 )
					->count();

				$open_rate = $total_sent > 0 ? ( $total_opened / $total_sent ) * 100 : 0;

				// Aggregate by day
				if ( ! isset( $day_performance[ $day_of_week ] ) ) {
					$day_performance[ $day_of_week ] = [
						'total_campaigns' => 0,
						'total_sent'      => 0,
						'total_opened'    => 0,
						'avg_open_rate'   => 0,
					];
				}
				++$day_performance[ $day_of_week ]['total_campaigns'];
				$day_performance[ $day_of_week ]['total_sent']   += $total_sent;
				$day_performance[ $day_of_week ]['total_opened'] += $total_opened;

				// Aggregate by hour
				if ( ! isset( $hour_performance[ $hour ] ) ) {
					$hour_performance[ $hour ] = [
						'total_campaigns' => 0,
						'total_sent'      => 0,
						'total_opened'    => 0,
						'avg_open_rate'   => 0,
					];
				}
				++$hour_performance[ $hour ]['total_campaigns'];
				$hour_performance[ $hour ]['total_sent']   += $total_sent;
				$hour_performance[ $hour ]['total_opened'] += $total_opened;
			}

			// Calculate average open rates
			foreach ( $day_performance as $day => &$data ) {
				$data['avg_open_rate'] = $data['total_sent'] > 0 ?
					round( ( $data['total_opened'] / $data['total_sent'] ) * 100, 2 ) : 0;
			}

			foreach ( $hour_performance as $hour => &$data ) {
				$data['avg_open_rate'] = $data['total_sent'] > 0 ?
					round( ( $data['total_opened'] / $data['total_sent'] ) * 100, 2 ) : 0;
			}

			// Sort by open rate
			uasort(
				$day_performance,
				function ( $a, $b ) {
					return $b['avg_open_rate'] <=> $a['avg_open_rate'];
				}
			);
			uasort(
				$hour_performance,
				function ( $a, $b ) {
					return $b['avg_open_rate'] <=> $a['avg_open_rate'];
				}
			);

			// Get best day and hour
			$best_day  = array_key_first( $day_performance );
			$best_hour = array_key_first( $hour_performance );

			return $this->get_success_response(
				[
					'analysis_period' => [
						'days_analyzed' => $days_back,
						'from_date'     => $date_from,
						'to_date'       => gmdate( 'Y-m-d H:i:s' ),
					],
					'best_send_time'  => [
						'day'  => $best_day,
						'hour' => $best_hour . ':00',
					],
					'by_day_of_week'  => $day_performance,
					'by_hour'         => $hour_performance,
				],
				'Send time optimization data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve send time optimization: ' . $e->getMessage(), 'optimization_retrieval_failed' );
		}
	}

	/**
	 * Register compare-campaigns ability
	 *
	 * @return void
	 */
	private function register_compare_campaigns(): void {
		wp_register_ability(
			'fluentcrm/compare-campaigns',
			[
				'label'               => 'FluentCRM Compare Campaigns',
				'description'         => 'Compare performance metrics across multiple campaigns side-by-side',
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
				'execute_callback'    => [ $this, 'execute_compare_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute compare-campaigns ability
	 *
	 * @param array<string, mixed> $args Campaign comparison request parameters
	 * @return array<string, mixed> Success/error response with comparison data
	 */
	public function execute_compare_campaigns( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) || ! class_exists( '\FluentCrm\App\Models\CampaignEmail' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign models not available', 'models_unavailable' );
		}

		$campaign_ids = $args['campaign_ids'] ?? [];

		if ( count( $campaign_ids ) < 2 || count( $campaign_ids ) > 10 ) {
			return $this->get_error_response( 'Please provide between 2 and 10 campaign IDs to compare', 'invalid_campaign_count' );
		}

		try {
			$comparison_data = [];

			foreach ( $campaign_ids as $campaign_id ) {
				$campaign_id = (int) $campaign_id;

				if ( ! $this->campaign_exists( $campaign_id ) ) {
					$comparison_data[] = [
						'campaign_id' => $campaign_id,
						'error'       => 'Campaign not found',
					];
					continue;
				}

				$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

				$total_sent         = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
					->whereIn( 'status', [ 'sent', 'delivered' ] )
					->count();
				$total_opened       = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
					->where( 'is_open', '>', 0 )
					->count();
				$total_clicked      = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
					->where( 'click_counter', '>', 0 )
					->count();
				$total_bounced      = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
					->where( 'status', 'bounced' )
					->count();
				$total_unsubscribed = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )
					->where( 'is_unsubscribed', 1 )
					->count();

				$open_rate          = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 2 ) : 0;
				$click_rate         = $total_sent > 0 ? round( ( $total_clicked / $total_sent ) * 100, 2 ) : 0;
				$click_to_open_rate = $total_opened > 0 ? round( ( $total_clicked / $total_opened ) * 100, 2 ) : 0;
				$bounce_rate        = $total_sent > 0 ? round( ( $total_bounced / $total_sent ) * 100, 2 ) : 0;
				$unsubscribe_rate   = $total_sent > 0 ? round( ( $total_unsubscribed / $total_sent ) * 100, 2 ) : 0;

				$comparison_data[] = [
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'subject'     => $campaign->subject,
					'status'      => $campaign->status,
					'sent_at'     => $campaign->scheduled_at,
					'metrics'     => [
						'sent'         => $total_sent,
						'opened'       => $total_opened,
						'clicked'      => $total_clicked,
						'bounced'      => $total_bounced,
						'unsubscribed' => $total_unsubscribed,
					],
					'rates'       => [
						'open_rate'          => $open_rate,
						'click_rate'         => $click_rate,
						'click_to_open_rate' => $click_to_open_rate,
						'bounce_rate'        => $bounce_rate,
						'unsubscribe_rate'   => $unsubscribe_rate,
					],
				];
			}

			return $this->get_success_response(
				[
					'total_campaigns' => count( $comparison_data ),
					'campaigns'       => $comparison_data,
				],
				'Campaign comparison data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to compare campaigns: ' . $e->getMessage(), 'comparison_failed' );
		}
	}
}
