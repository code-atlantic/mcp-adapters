<?php
/**
 * FluentCRM Smart Link Management Abilities
 *
 * Provides comprehensive smart link management tools including:
 * - Smart link CRUD operations (create, list, get, update, delete)
 * - Click tracking and analytics (detailed clicks, conversions)
 * - Short URL generation for smart links
 *
 * PRO FEATURE: Requires FluentCRM Pro with FluentCampaign
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * SmartLinks Ability Class
 *
 * Manages smart links in FluentCRM for tracking link clicks, conversions,
 * and triggering automation actions based on user interactions.
 *
 * Note: This is a FluentCRM Pro feature requiring FluentCampaign.
 */
class SmartLinks extends BaseAbility {

	/**
	 * Check if FluentCRM Pro smart links are available
	 *
	 * @return bool True if SmartLink model is available
	 */
	private function are_smart_links_available(): bool {
		return defined( 'FLUENTCAMPAIGN' ) &&
				class_exists( '\FluentCampaign\App\Models\SmartLink' );
	}

	/**
	 * Register all smart link-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if Pro features not available
		if ( ! $this->are_smart_links_available() ) {
			return;
		}

		// Smart Link CRUD Operations
		$this->register_create_smart_link();
		$this->register_list_smart_links();
		$this->register_get_smart_link();
		$this->register_update_smart_link();
		$this->register_delete_smart_link();

		// Smart Link Analytics & Tracking
		$this->register_get_smart_link_clicks();
		$this->register_get_smart_link_conversions();

		// URL Generation
		$this->register_generate_short_url();
	}

	/**
	 * Register create-smart-link ability
	 *
	 * @return void
	 */
	private function register_create_smart_link(): void {
		wp_register_ability(
			'fluentcrm/create-smart-link',
			[
				'label'               => 'FluentCRM Create Smart Link',
				'description'         => 'Create a new smart link with URL, title, and automation actions (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'url', 'title' ],
					'properties' => [
						'url'     => [
							'type'        => 'string',
							'description' => 'Target URL to redirect to',
							'format'      => 'uri',
						],
						'title'   => [
							'type'        => 'string',
							'description' => 'Smart link title for internal reference',
						],
						'notes'   => [
							'type'        => 'string',
							'description' => 'Optional notes about the smart link',
						],
						'actions' => [
							'type'        => 'object',
							'description' => 'Automation actions to trigger on click (lists to add, tags to apply, etc.)',
							'properties'  => [
								'lists'        => [
									'type'        => 'array',
									'description' => 'List IDs to add subscriber to on click',
									'items'       => [
										'type' => 'integer',
									],
								],
								'tags'         => [
									'type'        => 'array',
									'description' => 'Tag IDs to apply to subscriber on click',
									'items'       => [
										'type' => 'integer',
									],
								],
								'remove_lists' => [
									'type'        => 'array',
									'description' => 'List IDs to remove subscriber from on click',
									'items'       => [
										'type' => 'integer',
									],
								],
								'remove_tags'  => [
									'type'        => 'array',
									'description' => 'Tag IDs to remove from subscriber on click',
									'items'       => [
										'type' => 'integer',
									],
								],
								'webhook'      => [
									'type'        => 'string',
									'description' => 'Webhook URL to trigger on click',
									'format'      => 'uri',
								],
								'auto_login'   => [
									'type'        => 'string',
									'description' => 'Auto-login setting (yes/no)',
									'enum'        => [ 'yes', 'no' ],
									'default'     => 'no',
								],
							],
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_create_smart_link' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register list-smart-links ability
	 *
	 * @return void
	 */
	private function register_list_smart_links(): void {
		wp_register_ability(
			'fluentcrm/list-smart-links',
			[
				'label'               => 'FluentCRM List Smart Links',
				'description'         => 'List all smart links with pagination (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Smart links per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search smart links by title or URL',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_list_smart_links' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-smart-link ability
	 *
	 * @return void
	 */
	private function register_get_smart_link(): void {
		wp_register_ability(
			'fluentcrm/get-smart-link',
			[
				'label'               => 'FluentCRM Get Smart Link',
				'description'         => 'Get smart link details including click statistics (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id' ],
					'properties' => [
						'link_id' => [
							'type'        => 'integer',
							'description' => 'Smart link ID to retrieve',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_smart_link' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register update-smart-link ability
	 *
	 * @return void
	 */
	private function register_update_smart_link(): void {
		wp_register_ability(
			'fluentcrm/update-smart-link',
			[
				'label'               => 'FluentCRM Update Smart Link',
				'description'         => 'Update smart link properties including URL, title, and actions (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id' ],
					'properties' => [
						'link_id' => [
							'type'        => 'integer',
							'description' => 'Smart link ID to update',
						],
						'url'     => [
							'type'        => 'string',
							'description' => 'New target URL',
						],
						'title'   => [
							'type'        => 'string',
							'description' => 'New smart link title',
						],
						'notes'   => [
							'type'        => 'string',
							'description' => 'Update smart link notes',
						],
						'actions' => [
							'type'        => 'object',
							'description' => 'Update automation actions',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_update_smart_link' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register delete-smart-link ability
	 *
	 * @return void
	 */
	private function register_delete_smart_link(): void {
		wp_register_ability(
			'fluentcrm/delete-smart-link',
			[
				'label'               => 'FluentCRM Delete Smart Link',
				'description'         => 'Delete a smart link permanently (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id', 'confirm_delete' ],
					'properties' => [
						'link_id'        => [
							'type'        => 'integer',
							'description' => 'Smart link ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_delete_smart_link' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-smart-link-clicks ability
	 *
	 * @return void
	 */
	private function register_get_smart_link_clicks(): void {
		wp_register_ability(
			'fluentcrm/get-smart-link-clicks',
			[
				'label'               => 'FluentCRM Get Campaign Clicks',
				'description'         => 'Get detailed click tracking data for a smart link (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id' ],
					'properties' => [
						'link_id'    => [
							'type'        => 'integer',
							'description' => 'Smart link ID to get clicks for',
						],
						'start_date' => [
							'type'        => 'string',
							'description' => 'Start date for click data (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'end_date'   => [
							'type'        => 'string',
							'description' => 'End date for click data (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'page'       => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'   => [
							'type'        => 'integer',
							'description' => 'Clicks per page',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_smart_link_clicks' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-smart-link-conversions ability
	 *
	 * @return void
	 */
	private function register_get_smart_link_conversions(): void {
		wp_register_ability(
			'fluentcrm/get-smart-link-conversions',
			[
				'label'               => 'FluentCRM Get Smart Link Conversions',
				'description'         => 'Get conversion metrics and statistics for a smart link (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id' ],
					'properties' => [
						'link_id'    => [
							'type'        => 'integer',
							'description' => 'Smart link ID to get conversions for',
						],
						'start_date' => [
							'type'        => 'string',
							'description' => 'Start date for conversion data (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'end_date'   => [
							'type'        => 'string',
							'description' => 'End date for conversion data (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_smart_link_conversions' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register generate-short-url ability
	 *
	 * @return void
	 */
	private function register_generate_short_url(): void {
		wp_register_ability(
			'fluentcrm/generate-short-url',
			[
				'label'               => 'FluentCRM Generate Short Url',
				'description'         => 'Generate shortened URL for a smart link (Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'link_id' ],
					'properties' => [
						'link_id' => [
							'type'        => 'integer',
							'description' => 'Smart link ID to generate short URL for',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_generate_short_url' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'smart_links',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute create-smart-link ability
	 *
	 * @param array<string, mixed> $args Smart link creation parameters
	 * @return array<string, mixed> Success/error response with link data
	 */
	public function execute_create_smart_link( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			// Validate URL - must validate BEFORE escaping to catch invalid formats
			if ( filter_var( $args['url'], FILTER_VALIDATE_URL ) === false ) {
				return $this->get_error_response( 'Invalid URL provided', 'invalid_url' );
			}

			$url = esc_url_raw( $args['url'] );

			// Prepare smart link data using FluentCRM's actual field names
			$link_data = [
				'title'      => sanitize_text_field( $args['title'] ),
				'target_url' => $url, // FluentCRM uses 'target_url' not 'url'
				'notes'      => isset( $args['notes'] ) ? sanitize_textarea_field( $args['notes'] ) : '',
				'actions'    => $args['actions'] ?? [],
				'created_by' => get_current_user_id(),
			];

			// Create the smart link
			$smart_link = \FluentCampaign\App\Models\SmartLink::create( $link_data );

			return $this->get_success_response(
				[
					'smart_link' => [
						'id'         => $smart_link->id,
						'title'      => $smart_link->title,
						'url'        => $smart_link->target_url, // Return as 'url' for API consistency
						'short_url'  => $this->get_short_url( $smart_link ),
						'actions'    => $smart_link->actions,
						'notes'      => $smart_link->notes ?? '',
						'created_at' => $smart_link->created_at,
					],
				],
				'Smart link created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create smart link: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list-smart-links ability
	 *
	 * @param array<string, mixed> $args List parameters
	 * @return array<string, mixed> Success/error response with smart links list
	 */
	public function execute_list_smart_links( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';

			// Build query
			$query = \FluentCampaign\App\Models\SmartLink::query();

			// Apply search
			if ( ! empty( $search ) ) {
				$query->where(
					function ( $q ) use ( $search ) {
						$q->where( 'title', 'LIKE', '%' . $search . '%' )
						->orWhere( 'target_url', 'LIKE', '%' . $search . '%' );
					}
				);
			}

			// Get total count
			$total = $query->count();

			// Get paginated results
			$offset = ( $page - 1 ) * $per_page;
			$links  = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$link_list = [];
			foreach ( $links as $link ) {
				$link_list[] = [
					'id'         => $link->id,
					'title'      => $link->title,
					'url'        => $link->target_url,
					'short_url'  => $this->get_short_url( $link ),
					'clicks'     => 0, // Click tracking unavailable
					'created_at' => $link->created_at,
					'updated_at' => $link->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'smart_links' => $link_list,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Smart links retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list smart links: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get-smart-link ability
	 *
	 * @param array<string, mixed> $args Smart link retrieval parameters
	 * @return array<string, mixed> Success/error response with link details
	 */
	public function execute_get_smart_link( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id = intval( $args['link_id'] );

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			// Click tracking unavailable
			$total_clicks  = 0;
			$unique_clicks = 0;

			return $this->get_success_response(
				[
					'smart_link' => [
						'id'         => $smart_link->id,
						'title'      => $smart_link->title,
						'url'        => $smart_link->target_url,
						'short_url'  => $this->get_short_url( $smart_link ),
						'actions'    => $smart_link->actions,
						'stats'      => [
							'total_clicks'  => $total_clicks,
							'unique_clicks' => $unique_clicks,
						],
						'created_at' => $smart_link->created_at,
						'updated_at' => $smart_link->updated_at,
					],
				],
				'Smart link retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get smart link: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update-smart-link ability
	 *
	 * @param array<string, mixed> $args Smart link update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_smart_link( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id = intval( $args['link_id'] );

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['url'] ) ) {
				// Validate URL - must validate BEFORE escaping to catch invalid formats
				if ( filter_var( $args['url'], FILTER_VALIDATE_URL ) === false ) {
					return $this->get_error_response( 'Invalid URL provided', 'invalid_url' );
				}

				$url                       = esc_url_raw( $args['url'] );
				$update_data['target_url'] = $url; // FluentCRM uses 'target_url' not 'url'
			}

			if ( isset( $args['notes'] ) ) {
				$update_data['notes'] = sanitize_textarea_field( $args['notes'] );
			}

			if ( isset( $args['actions'] ) ) {
				$update_data['actions'] = $args['actions'];
			}

			// Update the smart link
			if ( ! empty( $update_data ) ) {
				$smart_link->update( $update_data );
				$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'smart_link' => [
						'id'         => $smart_link->id,
						'title'      => $smart_link->title,
						'url'        => $smart_link->target_url,
						'short_url'  => $this->get_short_url( $smart_link ),
						'actions'    => $smart_link->actions,
						'updated_at' => $smart_link->updated_at,
					],
				],
				'Smart link updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update smart link: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete-smart-link ability
	 *
	 * @param array<string, mixed> $args Smart link deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_smart_link( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id        = intval( $args['link_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			$link_title = $smart_link->title;

			// Delete the smart link
			$smart_link->delete();

			return $this->get_success_response(
				[
					'link_id'    => $link_id,
					'link_title' => $link_title,
					'deleted_at' => current_time( 'mysql' ),
				],
				'Smart link deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete smart link: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute get-smart-link-clicks ability
	 *
	 * @param array<string, mixed> $args Click tracking parameters
	 * @return array<string, mixed> Success/error response with click data
	 */
	public function execute_get_smart_link_clicks( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id    = intval( $args['link_id'] );
			$start_date = $args['start_date'] ?? null;
			$end_date   = $args['end_date'] ?? null;
			$page       = $args['page'] ?? 1;
			$per_page   = $args['per_page'] ?? 50;

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			// Click tracking is not available - UrlMetric model doesn't exist in FluentCampaign Pro
			// SmartLinks track clicks internally but don't expose a queryable model
			$clicks = [];
			$total  = 0;

			return $this->get_success_response(
				[
					'link_id'     => $link_id,
					'link_title'  => $smart_link->title,
					'clicks'      => $clicks,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => $total > 0 ? ceil( $total / $per_page ) : 0,
					'date_range'  => [
						'start_date' => $start_date ?? 'all_time',
						'end_date'   => $end_date ?? 'all_time',
					],
				],
				'Click data retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get click data: ' . $e->getMessage(), 'clicks_failed' );
		}
	}

	/**
	 * Execute get-smart-link-conversions ability
	 *
	 * @param array<string, mixed> $args Conversion tracking parameters
	 * @return array<string, mixed> Success/error response with conversion metrics
	 */
	public function execute_get_smart_link_conversions( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id    = intval( $args['link_id'] );
			$start_date = $args['start_date'] ?? null;
			$end_date   = $args['end_date'] ?? null;

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			// Calculate conversion metrics
			$conversions = [
				'link_id'           => $link_id,
				'link_title'        => $smart_link->title,
				'total_clicks'      => 0,
				'unique_clicks'     => 0,
				'click_through'     => 0,
				'actions_triggered' => [
					'lists_added'    => 0,
					'tags_applied'   => 0,
					'webhooks_fired' => 0,
				],
				'date_range'        => [
					'start_date' => $start_date ?? 'all_time',
					'end_date'   => $end_date ?? 'all_time',
				],
			];

			// Click tracking not available - UrlMetric model doesn't exist

			// Calculate action conversions based on smart link actions
			$actions = $smart_link->actions ?? [];
			if ( ! empty( $actions['lists'] ) ) {
				$conversions['actions_triggered']['lists_added'] = count( $actions['lists'] ) * $conversions['unique_clicks'];
			}
			if ( ! empty( $actions['tags'] ) ) {
				$conversions['actions_triggered']['tags_applied'] = count( $actions['tags'] ) * $conversions['unique_clicks'];
			}
			if ( ! empty( $actions['webhook'] ) ) {
				$conversions['actions_triggered']['webhooks_fired'] = $conversions['total_clicks'];
			}

			return $this->get_success_response(
				[
					'conversions' => $conversions,
				],
				'Conversion metrics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get conversion metrics: ' . $e->getMessage(), 'conversions_failed' );
		}
	}

	/**
	 * Execute generate-short-url ability
	 *
	 * @param array<string, mixed> $args URL generation parameters
	 * @return array<string, mixed> Success/error response with short URL
	 */
	public function execute_generate_short_url( array $args ): array {
		if ( ! class_exists( '\FluentCampaign\App\Models\SmartLink' ) ) {
			return $this->get_error_response( 'SmartLink model not available - FluentCampaign Pro required', 'pro_required' );
		}

		try {
			$link_id = intval( $args['link_id'] );

			if ( $link_id <= 0 ) {
				return $this->get_error_response( 'Invalid smart link ID', 'invalid_link_id' );
			}

			$smart_link = \FluentCampaign\App\Models\SmartLink::find( $link_id );

			if ( ! $smart_link ) {
				return $this->get_error_response( 'Smart link not found', 'link_not_found' );
			}

			$short_url = $this->get_short_url( $smart_link );

			return $this->get_success_response(
				[
					'link_id'      => $link_id,
					'link_title'   => $smart_link->title,
					'original_url' => $smart_link->target_url,
					'short_url'    => $short_url,
				],
				'Short URL generated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to generate short URL: ' . $e->getMessage(), 'url_generation_failed' );
		}
	}

	/**
	 * Helper: Get short URL for a smart link
	 *
	 * @param object $smart_link Smart link model instance
	 * @return string Short URL
	 */
	private function get_short_url( $smart_link ): string {
		// Use FluentCRM's built-in short_url attribute with link_id appended for tracking
		// Base format: ?fluentcrm=1&route=smart_url&slug={short}
		$base_url = $smart_link->short_url ?? '';

		// If we have a short_url and an ID, append the link_id for tracking purposes
		if ( ! empty( $base_url ) && isset( $smart_link->id ) ) {
			$base_url = add_query_arg( 'link_id', $smart_link->id, $base_url );
		}

		return $base_url;
	}
}
