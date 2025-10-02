<?php
/**
 * FluentCRM Campaign Management Abilities
 *
 * Provides comprehensive campaign management tools including:
 * - Campaign CRUD operations (create, list, get, update, delete, duplicate)
 * - Campaign lifecycle management (schedule, send, pause, resume, cancel)
 * - Campaign testing and preview (test-send, preview)
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * Campaigns Ability Class
 *
 * Manages email campaigns in FluentCRM including creation, scheduling,
 * sending, analytics, and lifecycle operations.
 */
class Campaigns extends BaseAbility {

	/**
	 * Register all campaign-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Campaign CRUD Operations
		$this->register_create_campaign();
		$this->register_list_campaigns();
		$this->register_get_campaign();
		$this->register_update_campaign();
		$this->register_delete_campaign();
		$this->register_duplicate_campaign();

		// Campaign Lifecycle Operations
		$this->register_schedule_campaign();
		$this->register_send_campaign();
		$this->register_pause_campaign();
		$this->register_resume_campaign();
		$this->register_cancel_campaign();

		// Campaign Testing & Preview
		$this->register_test_send_campaign();
		$this->register_preview_campaign();
	}

	/**
	 * Register create-campaign ability
	 *
	 * @return void
	 */
	private function register_create_campaign(): void {
		wp_register_ability(
			'fluentcrm/create-campaign',
			[
				'label'               => 'Create FluentCRM Campaign',
				'description'         => 'Create a new email campaign in FluentCRM',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'title', 'subject', 'email_body' ],
					'properties' => [
						'title'          => [
							'type'        => 'string',
							'description' => 'Campaign title for internal use',
						],
						'subject'        => [
							'type'        => 'string',
							'description' => 'Email subject line',
						],
						'email_body'     => [
							'type'        => 'string',
							'description' => 'HTML email body content',
						],
						'template_id'    => [
							'type'        => 'integer',
							'description' => 'Email template ID to use (optional)',
						],
						'sender_name'    => [
							'type'        => 'string',
							'description' => 'Sender name (defaults to site settings)',
						],
						'sender_email'   => [
							'type'        => 'string',
							'description' => 'Sender email (defaults to site settings)',
						],
						'reply_to_name'  => [
							'type'        => 'string',
							'description' => 'Reply-to name',
						],
						'reply_to_email' => [
							'type'        => 'string',
							'description' => 'Reply-to email',
						],
						'list_ids'       => [
							'type'        => 'array',
							'description' => 'Array of list IDs to target',
							'items'       => [
								'type' => 'integer',
							],
						],
						'tag_ids'        => [
							'type'        => 'array',
							'description' => 'Array of tag IDs to target',
							'items'       => [
								'type' => 'integer',
							],
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_create_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute create-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign creation parameters
	 * @return array<string, mixed> Success/error response with campaign data
	 */
	public function execute_create_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		try {
			// Get default sender settings
			$settings = fluentcrm_get_option( 'email_settings', [] );

			// Build targeting subscribers array
			$subscribers = [];

			// Add lists to targeting
			if ( ! empty( $args['list_ids'] ) ) {
				foreach ( $args['list_ids'] as $list_id ) {
					if ( $this->list_exists( (int) $list_id ) ) {
						$subscribers[] = [ 'list' => (int) $list_id, 'tag' => null ];
					}
				}
			}

			// Add tags to targeting
			if ( ! empty( $args['tag_ids'] ) ) {
				foreach ( $args['tag_ids'] as $tag_id ) {
					if ( $this->tag_exists( (int) $tag_id ) ) {
						$subscribers[] = [ 'list' => null, 'tag' => (int) $tag_id ];
					}
				}
			}

			// If no targeting specified, use default 'all'
			if ( empty( $subscribers ) ) {
				$subscribers = [ [ 'list' => 'all', 'tag' => 'all' ] ];
			}

			// Prepare campaign data
			$campaign_data = [
				'title'           => sanitize_text_field( $args['title'] ),
				'email_subject'   => sanitize_text_field( $args['subject'] ),
				'email_body'      => wp_kses_post( $args['email_body'] ),
				'status'          => 'draft',
				'template_id'     => $args['template_id'] ?? null,
				'settings'        => [
					'mailer_settings' => [
						'from_name'      => $args['sender_name'] ?? $settings['from_name'] ?? get_bloginfo( 'name' ),
						'from_email'     => $args['sender_email'] ?? $settings['from_email'] ?? get_bloginfo( 'admin_email' ),
						'reply_to_name'  => $args['reply_to_name'] ?? $settings['from_name'] ?? get_bloginfo( 'name' ),
						'reply_to_email' => $args['reply_to_email'] ?? $settings['from_email'] ?? get_bloginfo( 'admin_email' ),
					],
					'subscribers'     => $subscribers,
				],
				'created_by'      => get_current_user_id(),
				'design_template' => 'simple',
			];

			// Create campaign
			$campaign = \FluentCrm\App\Models\Campaign::create( $campaign_data );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'subject'     => $campaign->subject,
					'status'      => $campaign->status,
				],
				'Campaign created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create campaign: ' . $e->getMessage(), 'creation_failed' );
		}
	}

	/**
	 * Register list-campaigns ability
	 *
	 * @return void
	 */
	private function register_list_campaigns(): void {
		wp_register_ability(
			'fluentcrm/list-campaigns',
			[
				'label'               => 'FluentCRM List Campaigns',
				'description'         => 'List email campaigns with filtering and pagination',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'status'   => [
							'type'        => 'string',
							'description' => 'Filter by status: draft, scheduled, working, paused, archived (also: pending-scheduled, processing)',
							'enum'        => [ 'draft', 'scheduled', 'pending-scheduled', 'working', 'processing', 'paused', 'archived' ],
						],
						'type'     => [
							'type'        => 'string',
							'description' => 'Filter by type: campaign, recurring',
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Number of campaigns per page (default: 10)',
							'default'     => 10,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number (default: 1)',
							'default'     => 1,
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_list_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute list-campaigns ability
	 *
	 * @param array<string, mixed> $args Filtering and pagination parameters
	 * @return array<string, mixed> Success/error response with campaigns list
	 */
	public function execute_list_campaigns( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		try {
			$query = \FluentCrm\App\Models\Campaign::query();

			// Apply status filter
			if ( ! empty( $args['status'] ) ) {
				$query->where( 'status', sanitize_text_field( $args['status'] ) );
			}

			// Apply type filter
			if ( ! empty( $args['type'] ) ) {
				$query->where( 'type', sanitize_text_field( $args['type'] ) );
			}

			// Pagination
			$per_page = absint( $args['per_page'] ?? 10 );
			$page     = absint( $args['page'] ?? 1 );
			$offset   = ( $page - 1 ) * $per_page;

			$total = $query->count();

			$campaigns = $query
				->orderBy( 'id', 'desc' )
				->limit( $per_page )
				->offset( $offset )
				->get();

			$formatted_campaigns = [];
			foreach ( $campaigns as $campaign ) {
				$formatted_campaigns[] = [
					'id'               => $campaign->id,
					'title'            => $campaign->title,
					'subject'          => $campaign->subject,
					'status'           => $campaign->status,
					'type'             => $campaign->type ?? 'campaign',
					'scheduled_at'     => $campaign->scheduled_at,
					'recipients_count' => $campaign->recipients_count ?? 0,
					'created_at'       => $campaign->created_at,
				];
			}

			return $this->get_success_response(
				[
					'campaigns'   => $formatted_campaigns,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Campaigns retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list campaigns: ' . $e->getMessage(), 'query_failed' );
		}
	}

	/**
	 * Register get-campaign ability
	 *
	 * @return void
	 */
	private function register_get_campaign(): void {
		wp_register_ability(
			'fluentcrm/get-campaign',
			[
				'label'               => 'FluentCRM Get Campaign',
				'description'         => 'Get detailed information about a specific campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id'   => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'include_stats' => [
							'type'        => 'boolean',
							'description' => 'Include campaign statistics (default: false)',
							'default'     => false,
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute get-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign retrieval parameters
	 * @return array<string, mixed> Success/error response with campaign data
	 */
	public function execute_get_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			$campaign_data = [
				'id'              => $campaign->id,
				'title'           => $campaign->title,
				'subject'         => $campaign->subject,
				'email_body'      => $campaign->email_body,
				'status'          => $campaign->status,
				'type'            => $campaign->type ?? 'campaign',
				'template_id'     => $campaign->template_id,
				'design_template' => $campaign->design_template,
				'settings'        => $campaign->settings,
				'scheduled_at'    => $campaign->scheduled_at,
				'created_at'      => $campaign->created_at,
				'updated_at'      => $campaign->updated_at,
			];

			// Include statistics if requested
			if ( ! empty( $args['include_stats'] ) ) {
				$campaign_data['stats'] = [
					'total_recipients' => $campaign->recipients_count ?? 0,
					'sent'             => $campaign->sent_count ?? 0,
					'opened'           => $campaign->open_count ?? 0,
					'clicked'          => $campaign->click_count ?? 0,
					'bounced'          => $campaign->bounce_count ?? 0,
					'unsubscribed'     => $campaign->unsubscribe_count ?? 0,
				];
			}

			return $this->get_success_response( $campaign_data, 'Campaign retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get campaign: ' . $e->getMessage(), 'retrieval_failed' );
		}
	}

	/**
	 * Register update-campaign ability
	 *
	 * @return void
	 */
	private function register_update_campaign(): void {
		wp_register_ability(
			'fluentcrm/update-campaign',
			[
				'label'               => 'FluentCRM Update Campaign',
				'description'         => 'Update campaign properties (draft campaigns only)',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id'  => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'title'        => [
							'type'        => 'string',
							'description' => 'Campaign title',
						],
						'subject'      => [
							'type'        => 'string',
							'description' => 'Email subject line',
						],
						'email_body'   => [
							'type'        => 'string',
							'description' => 'HTML email body content',
						],
						'sender_name'  => [
							'type'        => 'string',
							'description' => 'Sender name',
						],
						'sender_email' => [
							'type'        => 'string',
							'description' => 'Sender email',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_update_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute update-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only allow updating draft campaigns
			if ( 'draft' !== $campaign->status ) {
				return $this->get_error_response( 'Only draft campaigns can be updated', 'invalid_status' );
			}

			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['subject'] ) ) {
				$update_data['email_subject'] = sanitize_text_field( $args['subject'] );
			}

			if ( isset( $args['email_body'] ) ) {
				$update_data['email_body'] = wp_kses_post( $args['email_body'] );
			}

			// Update sender settings
			if ( isset( $args['sender_name'] ) || isset( $args['sender_email'] ) ) {
				$settings        = $campaign->settings ?? [];
				$mailer_settings = $settings['mailer_settings'] ?? [];

				if ( isset( $args['sender_name'] ) ) {
					$mailer_settings['from_name'] = sanitize_text_field( $args['sender_name'] );
				}

				if ( isset( $args['sender_email'] ) ) {
					$mailer_settings['from_email'] = sanitize_email( $args['sender_email'] );
				}

				$settings['mailer_settings'] = $mailer_settings;
				$update_data['settings']     = $settings;
			}

			if ( empty( $update_data ) ) {
				return $this->get_error_response( 'No update data provided', 'no_data' );
			}

			$campaign->update( $update_data );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'subject'     => $campaign->subject,
				],
				'Campaign updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update campaign: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Register delete-campaign ability
	 *
	 * @return void
	 */
	private function register_delete_campaign(): void {
		wp_register_ability(
			'fluentcrm/delete-campaign',
			[
				'label'               => 'FluentCRM Delete Campaign',
				'description'         => 'Delete a campaign permanently',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id', 'confirm' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'confirm'     => [
							'type'        => 'boolean',
							'description' => 'Must be true to confirm deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_delete_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute delete-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		if ( empty( $args['confirm'] ) ) {
			return $this->get_error_response( 'Deletion must be confirmed', 'confirmation_required' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );
			$title    = $campaign->title;

			// Delete associated campaign emails
			\FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign_id )->delete();

			// Delete campaign
			$campaign->delete();

			return $this->get_success_response(
				[
					'campaign_id' => $campaign_id,
					'title'       => $title,
				],
				'Campaign deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete campaign: ' . $e->getMessage(), 'deletion_failed' );
		}
	}

	/**
	 * Register duplicate-campaign ability
	 *
	 * @return void
	 */
	private function register_duplicate_campaign(): void {
		wp_register_ability(
			'fluentcrm/duplicate-campaign',
			[
				'label'               => 'FluentCRM Duplicate Campaign',
				'description'         => 'Duplicate an existing campaign as a draft',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID to duplicate',
						],
						'new_title'   => [
							'type'        => 'string',
							'description' => 'Title for duplicated campaign (defaults to "Copy of {original}")',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_duplicate_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute duplicate-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign duplication parameters
	 * @return array<string, mixed> Success/error response with new campaign data
	 */
	public function execute_duplicate_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$original = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Prepare duplicate data
			$duplicate_data = [
				'title'           => $args['new_title'] ?? 'Copy of ' . $original->title,
				'email_subject'   => $original->email_subject,
				'email_body'      => $original->email_body,
				'status'          => 'draft',
				'type'            => $original->type,
				'template_id'     => $original->template_id,
				'design_template' => $original->design_template,
				'settings'        => $original->settings,
				'created_by'      => get_current_user_id(),
			];

			// Create duplicate
			$duplicate = \FluentCrm\App\Models\Campaign::create( $duplicate_data );

			// Note: Lists and tags are copied via settings, not relationships
			// The settings field already contains subscribers array with list/tag targeting

			return $this->get_success_response(
				[
					'campaign_id' => $duplicate->id,
					'title'       => $duplicate->title,
					'subject'     => $duplicate->subject,
					'status'      => $duplicate->status,
				],
				'Campaign duplicated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to duplicate campaign: ' . $e->getMessage(), 'duplication_failed' );
		}
	}

	/**
	 * Register schedule-campaign ability
	 *
	 * @return void
	 */
	private function register_schedule_campaign(): void {
		wp_register_ability(
			'fluentcrm/schedule-campaign',
			[
				'label'               => 'FluentCRM Schedule Campaign',
				'description'         => 'Schedule a campaign for future sending',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id', 'scheduled_at' ],
					'properties' => [
						'campaign_id'  => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'scheduled_at' => [
							'type'        => 'string',
							'description' => 'Schedule datetime in Y-m-d H:i:s format',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_schedule_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute schedule-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign scheduling parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_schedule_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only draft campaigns can be scheduled
			if ( 'draft' !== $campaign->status ) {
				return $this->get_error_response( 'Only draft campaigns can be scheduled', 'invalid_status' );
			}

			$scheduled_at = sanitize_text_field( $args['scheduled_at'] );

			// Validate datetime format
			$datetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $scheduled_at );
			if ( ! $datetime ) {
				return $this->get_error_response( 'Invalid datetime format. Use Y-m-d H:i:s', 'invalid_datetime' );
			}

			// Update campaign status and scheduled time
			$campaign->update(
				[
					'status'       => 'scheduled',
					'scheduled_at' => $scheduled_at,
				]
			);

			return $this->get_success_response(
				[
					'campaign_id'  => $campaign->id,
					'title'        => $campaign->title,
					'status'       => $campaign->status,
					'scheduled_at' => $campaign->scheduled_at,
				],
				'Campaign scheduled successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to schedule campaign: ' . $e->getMessage(), 'scheduling_failed' );
		}
	}

	/**
	 * Register send-campaign ability
	 *
	 * @return void
	 */
	private function register_send_campaign(): void {
		wp_register_ability(
			'fluentcrm/send-campaign',
			[
				'label'               => 'FluentCRM Send Campaign',
				'description'         => 'Send a campaign immediately',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_send_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute send-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign sending parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_send_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only draft campaigns can be sent
			if ( 'draft' !== $campaign->status ) {
				return $this->get_error_response( 'Only draft campaigns can be sent immediately', 'invalid_status' );
			}

			// Update campaign status
			$campaign->update(
				[
					'status'       => 'scheduled',
					'scheduled_at' => current_time( 'mysql' ),
				]
			);

			// Trigger campaign processing
			do_action( 'fluentcrm_process_campaign_jobs', [ $campaign_id ] );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'status'      => 'sending',
				],
				'Campaign sending initiated'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to send campaign: ' . $e->getMessage(), 'sending_failed' );
		}
	}

	/**
	 * Register pause-campaign ability
	 *
	 * @return void
	 */
	private function register_pause_campaign(): void {
		wp_register_ability(
			'fluentcrm/pause-campaign',
			[
				'label'               => 'FluentCRM Pause Campaign',
				'description'         => 'Pause a currently sending campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_pause_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute pause-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign pause parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_pause_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only working campaigns can be paused
			if ( ! in_array( $campaign->status, [ 'working', 'scheduled' ], true ) ) {
				return $this->get_error_response( 'Only active campaigns can be paused', 'invalid_status' );
			}

			$campaign->update( [ 'status' => 'paused' ] );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'status'      => $campaign->status,
				],
				'Campaign paused successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to pause campaign: ' . $e->getMessage(), 'pause_failed' );
		}
	}

	/**
	 * Register resume-campaign ability
	 *
	 * @return void
	 */
	private function register_resume_campaign(): void {
		wp_register_ability(
			'fluentcrm/resume-campaign',
			[
				'label'               => 'FluentCRM Resume Campaign',
				'description'         => 'Resume a paused campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_resume_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute resume-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign resume parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_resume_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only paused campaigns can be resumed
			if ( 'paused' !== $campaign->status ) {
				return $this->get_error_response( 'Only paused campaigns can be resumed', 'invalid_status' );
			}

			$campaign->update( [ 'status' => 'working' ] );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'status'      => $campaign->status,
				],
				'Campaign resumed successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to resume campaign: ' . $e->getMessage(), 'resume_failed' );
		}
	}

	/**
	 * Register cancel-campaign ability
	 *
	 * @return void
	 */
	private function register_cancel_campaign(): void {
		wp_register_ability(
			'fluentcrm/cancel-campaign',
			[
				'label'               => 'FluentCRM Cancel Campaign',
				'description'         => 'Cancel a scheduled campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_cancel_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute cancel-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign cancellation parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_cancel_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Only scheduled campaigns can be cancelled
			if ( 'scheduled' !== $campaign->status ) {
				return $this->get_error_response( 'Only scheduled campaigns can be cancelled', 'invalid_status' );
			}

			$campaign->update(
				[
					'status'       => 'draft',
					'scheduled_at' => null,
				]
			);

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'title'       => $campaign->title,
					'status'      => $campaign->status,
				],
				'Campaign cancelled successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to cancel campaign: ' . $e->getMessage(), 'cancellation_failed' );
		}
	}

	/**
	 * Register test-send-campaign ability
	 *
	 * @return void
	 */
	private function register_test_send_campaign(): void {
		wp_register_ability(
			'fluentcrm/test-send-campaign',
			[
				'label'               => 'FluentCRM Test Send Campaign',
				'description'         => 'Send a test email for the campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id', 'test_email' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'test_email'  => [
							'type'        => 'string',
							'description' => 'Email address to send test to',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_test_send_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute test-send-campaign ability
	 *
	 * @param array<string, mixed> $args Test send parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_test_send_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		$test_email = sanitize_email( $args['test_email'] );
		if ( ! is_email( $test_email ) ) {
			return $this->get_error_response( 'Invalid email address', 'invalid_email' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Use FluentCRM's mailer to send test email
			$mailer_settings = $campaign->settings['mailer_settings'] ?? [];

			$email_data = [
				'to'      => [
					'email' => $test_email,
				],
				'subject' => '[TEST] ' . $campaign->subject,
				'body'    => $campaign->email_body,
				'headers' => [
					'From: ' . ( $mailer_settings['from_name'] ?? get_bloginfo( 'name' ) ) . ' <' . ( $mailer_settings['from_email'] ?? get_bloginfo( 'admin_email' ) ) . '>',
				],
			];

			// Send via WordPress mail function
			$sent = wp_mail(
				$test_email,
				$email_data['subject'],
				$email_data['body'],
				$email_data['headers']
			);

			if ( $sent ) {
				return $this->get_success_response(
					[
						'campaign_id' => $campaign->id,
						'test_email'  => $test_email,
					],
					'Test email sent successfully'
				);
			} else {
				return $this->get_error_response( 'Failed to send test email', 'mail_failed' );
			}
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to send test email: ' . $e->getMessage(), 'test_failed' );
		}
	}

	/**
	 * Register preview-campaign ability
	 *
	 * @return void
	 */
	private function register_preview_campaign(): void {
		wp_register_ability(
			'fluentcrm/preview-campaign',
			[
				'label'               => 'FluentCRM Preview Campaign',
				'description'         => 'Get rendered HTML preview of campaign',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'campaign_id' ],
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_preview_campaign' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'campaigns',
				],
			]
		);
	}

	/**
	 * Execute preview-campaign ability
	 *
	 * @param array<string, mixed> $args Campaign preview parameters
	 * @return array<string, mixed> Success/error response with rendered HTML
	 */
	public function execute_preview_campaign( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_unavailable' );
		}

		$campaign_id = absint( $args['campaign_id'] );

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return $this->get_error_response( 'Campaign not found', 'not_found' );
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			// Get rendered email body (with smartcodes processed)
			$email_body = apply_filters( 'fluentcrm_parse_campaign_email_text', $campaign->email_body, [ 'campaign_id' => $campaign_id ] );

			return $this->get_success_response(
				[
					'campaign_id' => $campaign->id,
					'subject'     => $campaign->subject,
					'html'        => $email_body,
					'plain_text'  => wp_strip_all_tags( $email_body ),
				],
				'Campaign preview generated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to generate preview: ' . $e->getMessage(), 'preview_failed' );
		}
	}
}
