<?php
/**
 * FluentCRM Funnel Management Abilities
 *
 * Provides comprehensive funnel automation management tools including:
 * - Funnel CRUD operations (create, list, get, update, delete, duplicate)
 * - Funnel lifecycle management (activate, deactivate)
 * - Funnel analytics and subscriber tracking
 * - Funnel testing and condition validation
 *
 * Note: Funnels are part of FREE FluentCRM core
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * Funnels Ability Class
 *
 * Manages automation funnels in FluentCRM including creation, configuration,
 * trigger conditions, subscriber tracking, and performance analytics.
 *
 * Note: Funnels are part of FREE FluentCRM, not a Pro-only feature.
 */
class Funnels extends BaseAbility {

	/**
	 * Check if FluentCRM automation funnels are available
	 *
	 * Note: Funnels are part of FREE FluentCRM, not Pro
	 *
	 * @return bool True if funnel models are available
	 */
	private function are_funnels_available(): bool {
		return class_exists( '\FluentCrm\App\Models\Funnel' ) &&
				class_exists( '\FluentCrm\App\Models\FunnelSequence' ) &&
				class_exists( '\FluentCrm\App\Models\FunnelSubscriber' );
	}

	/**
	 * Register all funnel-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if funnel models not available
		if ( ! $this->are_funnels_available() ) {
			return;
		}

		// Funnel CRUD Operations
		$this->register_create_funnel();
		$this->register_list_funnels();
		$this->register_get_funnel();
		$this->register_update_funnel();
		$this->register_delete_funnel();
		$this->register_duplicate_funnel();

		// Funnel Lifecycle Operations
		$this->register_activate_funnel();
		$this->register_deactivate_funnel();

		// Funnel Analytics & Tracking
		$this->register_get_funnel_subscribers();
		$this->register_get_funnel_metrics();

		// Funnel Testing
		$this->register_test_funnel_conditions();
	}

	/**
	 * Register create-funnel ability
	 *
	 * @return void
	 */
	private function register_create_funnel(): void {
		wp_register_ability(
			'fluentcrm/create-funnel',
			[
				'label'               => 'FluentCRM Create Funnel',
				'description'         => 'Create a new automation funnel in FluentCRM ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'title', 'trigger_name' ],
					'properties' => [
						'title'        => [
							'type'        => 'string',
							'description' => 'Funnel name for internal use',
						],
						'trigger_name' => [
							'type'        => 'string',
							'description' => 'Funnel trigger type (e.g., user_register, tag_applied, list_applied)',
						],
						'status'       => [
							'type'        => 'string',
							'description' => 'Initial funnel status',
							'enum'        => [ 'draft', 'published' ],
							'default'     => 'draft',
						],
						'settings'     => [
							'type'        => 'object',
							'description' => 'Funnel configuration settings',
							'properties'  => [
								'subscription_status' => [
									'type'        => 'string',
									'description' => 'Required subscriber status (subscribed, pending, unsubscribed)',
									'enum'        => [ 'subscribed', 'pending', 'unsubscribed' ],
									'default'     => 'subscribed',
								],
							],
						],
						'conditions'   => [
							'type'        => 'object',
							'description' => 'Trigger conditions configuration',
							'properties'  => [
								'run_only_once' => [
									'type'        => 'boolean',
									'description' => 'Whether to run funnel only once per contact',
									'default'     => false,
								],
							],
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_create_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register list-funnels ability
	 *
	 * @return void
	 */
	private function register_list_funnels(): void {
		wp_register_ability(
			'fluentcrm/list-funnels',
			[
				'label'               => 'FluentCRM List Funnels',
				'description'         => 'List all automation funnels with optional status filter ',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'status'   => [
							'type'        => 'string',
							'description' => 'Filter by funnel status',
							'enum'        => [ 'published', 'draft', 'archived' ],
						],
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Funnels per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search funnels by title',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_list_funnels' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-funnel ability
	 *
	 * @return void
	 */
	private function register_get_funnel(): void {
		wp_register_ability(
			'fluentcrm/get-funnel',
			[
				'label'               => 'FluentCRM Get Funnel',
				'description'         => 'Get detailed funnel information with sequences ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel ID to retrieve',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register update-funnel ability
	 *
	 * @return void
	 */
	private function register_update_funnel(): void {
		wp_register_ability(
			'fluentcrm/update-funnel',
			[
				'label'               => 'FluentCRM Update Funnel',
				'description'         => 'Update funnel configuration and settings ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id'    => [
							'type'        => 'integer',
							'description' => 'Funnel ID to update',
						],
						'title'        => [
							'type'        => 'string',
							'description' => 'New funnel title',
						],
						'trigger_name' => [
							'type'        => 'string',
							'description' => 'Update trigger type',
						],
						'status'       => [
							'type'        => 'string',
							'description' => 'Update funnel status',
							'enum'        => [ 'draft', 'published', 'archived' ],
						],
						'settings'     => [
							'type'        => 'object',
							'description' => 'Update funnel settings',
						],
						'conditions'   => [
							'type'        => 'object',
							'description' => 'Update trigger conditions',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_update_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register delete-funnel ability
	 *
	 * @return void
	 */
	private function register_delete_funnel(): void {
		wp_register_ability(
			'fluentcrm/delete-funnel',
			[
				'label'               => 'FluentCRM Delete Funnel',
				'description'         => 'Delete a funnel permanently with confirmation ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id', 'confirm_delete' ],
					'properties' => [
						'funnel_id'      => [
							'type'        => 'integer',
							'description' => 'Funnel ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_delete_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register duplicate-funnel ability
	 *
	 * @return void
	 */
	private function register_duplicate_funnel(): void {
		wp_register_ability(
			'fluentcrm/duplicate-funnel',
			[
				'label'               => 'FluentCRM Duplicate Funnel',
				'description'         => 'Clone an existing funnel with all sequences ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel ID to duplicate',
						],
						'new_title' => [
							'type'        => 'string',
							'description' => 'Title for the duplicated funnel (optional - will use "Copy of {original title}" if not provided)',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_duplicate_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register activate-funnel ability
	 *
	 * @return void
	 */
	private function register_activate_funnel(): void {
		wp_register_ability(
			'fluentcrm/activate-funnel',
			[
				'label'               => 'FluentCRM Activate Funnel',
				'description'         => 'Enable a funnel to start processing triggers ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel ID to activate',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_activate_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register deactivate-funnel ability
	 *
	 * @return void
	 */
	private function register_deactivate_funnel(): void {
		wp_register_ability(
			'fluentcrm/deactivate-funnel',
			[
				'label'               => 'FluentCRM Deactivate Funnel',
				'description'         => 'Disable a funnel to stop processing triggers ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel ID to deactivate',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_deactivate_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-funnel-subscribers ability
	 *
	 * @return void
	 */
	private function register_get_funnel_subscribers(): void {
		wp_register_ability(
			'fluentcrm/get-funnel-subscribers',
			[
				'label'               => 'FluentCRM Get Funnel Subscribers',
				'description'         => 'List contacts currently in a funnel with their status ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id'   => [
							'type'        => 'integer',
							'description' => 'Funnel ID to get subscribers from',
						],
						'status'      => [
							'type'        => 'string',
							'description' => 'Filter by subscriber status in funnel',
							'enum'        => [ 'active', 'completed', 'cancelled' ],
						],
						'page'        => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'    => [
							'type'        => 'integer',
							'description' => 'Subscribers per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'sequence_id' => [
							'type'        => 'integer',
							'description' => 'Filter by specific sequence within funnel',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_funnel_subscribers' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get-funnel-metrics ability
	 *
	 * @return void
	 */
	private function register_get_funnel_metrics(): void {
		wp_register_ability(
			'fluentcrm/get-funnel-metrics',
			[
				'label'               => 'FluentCRM Get Funnel Metrics',
				'description'         => 'Get funnel performance analytics and statistics ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id' ],
					'properties' => [
						'funnel_id'  => [
							'type'        => 'integer',
							'description' => 'Funnel ID to get metrics for',
						],
						'start_date' => [
							'type'        => 'string',
							'description' => 'Start date for metrics (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'end_date'   => [
							'type'        => 'string',
							'description' => 'End date for metrics (Y-m-d format)',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_funnel_metrics' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register test-funnel-conditions ability
	 *
	 * @return void
	 */
	private function register_test_funnel_conditions(): void {
		wp_register_ability(
			'fluentcrm/test-funnel-conditions',
			[
				'label'               => 'FluentCRM Test Funnel Conditions',
				'description'         => 'Test funnel trigger conditions against a subscriber ',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'funnel_id', 'subscriber_id' ],
					'properties' => [
						'funnel_id'     => [
							'type'        => 'integer',
							'description' => 'Funnel ID to test',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to test conditions against',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_test_funnel_conditions' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute create-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel creation parameters
	 * @return array<string, mixed> Success/error response with funnel data
	 */
	public function execute_create_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			// Prepare funnel data
			$funnel_data = [
				'title'        => sanitize_text_field( $args['title'] ),
				'trigger_name' => sanitize_text_field( $args['trigger_name'] ),
				'status'       => $args['status'] ?? 'draft',
				'settings'     => $args['settings'] ?? [],
				'conditions'   => $args['conditions'] ?? [],
				'created_by'   => get_current_user_id(),
			];

			// Create the funnel
			$funnel = \FluentCrm\App\Models\Funnel::create( $funnel_data );

			return $this->get_success_response(
				[
					'funnel' => $funnel->toArray(),
				],
				'Funnel created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create funnel: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list-funnels ability
	 *
	 * @param array<string, mixed> $args List parameters
	 * @return array<string, mixed> Success/error response with funnels list
	 */
	public function execute_list_funnels( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';
			$status   = $args['status'] ?? null;

			// Build query
			$query = \FluentCrm\App\Models\Funnel::query();

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			// Apply search
			if ( ! empty( $search ) ) {
				$query->where( 'title', 'LIKE', '%' . $search . '%' );
			}

			// Get total count
			$total = $query->count();

			// Get paginated results
			$offset  = ( $page - 1 ) * $per_page;
			$funnels = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$funnel_list = [];
			foreach ( $funnels as $funnel ) {
				// Get funnel data with toArray() and add subscriber count
				$funnel_data = $funnel->toArray();

				// Add subscriber count if available
				if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
					$funnel_data['subscriber_count'] = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel->id )->count();
				}

				$funnel_list[] = $funnel_data;
			}

			return $this->get_success_response(
				[
					'funnels'    => $funnel_list,
					'total'      => $total,
					'page'       => $page,
					'per_page'   => $per_page,
					'total_page' => ceil( $total / $per_page ),
				],
				'Funnels retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list funnels: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel retrieval parameters
	 * @return array<string, mixed> Success/error response with funnel details
	 */
	public function execute_get_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id = intval( $args['funnel_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Get sequences if available
			$sequences = [];
			if ( class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				$sequences_data = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
														->orderBy( 'sequence', 'ASC' )
														->get();

				foreach ( $sequences_data as $sequence ) {
					$sequences[] = [
						'id'          => $sequence->id,
						'type'        => $sequence->type,
						'action_name' => $sequence->action_name,
						'title'       => $sequence->title,
						'status'      => $sequence->status,
						'sequence'    => $sequence->sequence,
						'delay'       => $sequence->delay,
						'c_delay'     => $sequence->c_delay,
						'settings'    => $sequence->settings,
						'conditions'  => $sequence->conditions,
						'parent_id'   => $sequence->parent_id,
						'created_at'  => $sequence->created_at,
					];
				}
			}

			// Get subscriber stats
			$subscriber_stats = [
				'total'     => 0,
				'active'    => 0,
				'completed' => 0,
				'cancelled' => 0,
			];

			if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
				$subscriber_stats['total']     = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )->count();
				$subscriber_stats['active']    = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )
																	->where( 'status', 'active' )
																	->count();
				$subscriber_stats['completed'] = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )
																		->where( 'status', 'completed' )
																		->count();
				$subscriber_stats['cancelled'] = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )
																		->where( 'status', 'cancelled' )
																		->count();
			}

			// Get funnel data with toArray() and add computed fields
			$funnel_data                     = $funnel->toArray();
			$funnel_data['sequences']        = $sequences;
			$funnel_data['sequences_count']  = count( $sequences );
			$funnel_data['subscriber_stats'] = $subscriber_stats;

			return $this->get_success_response(
				[
					'funnel' => $funnel_data,
				],
				'Funnel retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get funnel: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id = intval( $args['funnel_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['trigger_name'] ) ) {
				$update_data['trigger_name'] = sanitize_text_field( $args['trigger_name'] );
			}

			if ( isset( $args['status'] ) ) {
				$update_data['status'] = $args['status'];
			}

			if ( isset( $args['settings'] ) ) {
				$update_data['settings'] = $args['settings'];
			}

			if ( isset( $args['conditions'] ) ) {
				$update_data['conditions'] = $args['conditions'];
			}

			// Update the funnel
			if ( ! empty( $update_data ) ) {
				$funnel->update( $update_data );
				$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'funnel' => $funnel->toArray(),
				],
				'Funnel updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update funnel: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id      = intval( $args['funnel_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			$funnel_title = $funnel->title;

			// Delete related sequences
			if ( class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				\FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )->delete();
			}

			// Delete funnel subscribers
			if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
				\FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )->delete();
			}

			// Delete the funnel
			$funnel->delete();

			return $this->get_success_response(
				[
					'funnel_id'    => $funnel_id,
					'funnel_title' => $funnel_title,
					'deleted_at'   => current_time( 'mysql' ),
				],
				'Funnel deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete funnel: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute duplicate-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel duplication parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_duplicate_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id = intval( $args['funnel_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$original_funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $original_funnel ) {
				return $this->get_error_response( 'Original funnel not found', 'funnel_not_found' );
			}

			// Prepare new funnel data
			$new_title = $args['new_title'] ?? 'Copy of ' . $original_funnel->title;

			$new_funnel_data = [
				'title'        => $new_title,
				'trigger_name' => $original_funnel->trigger_name,
				'status'       => 'draft', // Always create duplicates as draft
				'settings'     => $original_funnel->settings,
				'conditions'   => $original_funnel->conditions,
				'created_by'   => get_current_user_id(),
			];

			// Create the new funnel
			$new_funnel = \FluentCrm\App\Models\Funnel::create( $new_funnel_data );

			// Duplicate sequences if available
			$sequences_duplicated = 0;
			if ( class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				$sequences = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
														->orderBy( 'sequence', 'ASC' )
														->get();

				foreach ( $sequences as $sequence ) {
					\FluentCrm\App\Models\FunnelSequence::create(
						[
							'funnel_id'      => $new_funnel->id,
							'type'           => $sequence->type,
							'action_name'    => $sequence->action_name,
							'title'          => $sequence->title,
							'status'         => $sequence->status,
							'sequence'       => $sequence->sequence,
							'delay'          => $sequence->delay,
							'c_delay'        => $sequence->c_delay,
							'settings'       => $sequence->settings,
							'conditions'     => $sequence->conditions,
							'parent_id'      => $sequence->parent_id,
							'condition_type' => $sequence->condition_type,
						]
					);
					++$sequences_duplicated;
				}
			}

			return $this->get_success_response(
				[
					'original_funnel' => [
						'id'    => $original_funnel->id,
						'title' => $original_funnel->title,
					],
					'new_funnel'      => [
						'id'                   => $new_funnel->id,
						'title'                => $new_funnel->title,
						'trigger_name'         => $new_funnel->trigger_name,
						'status'               => $new_funnel->status,
						'sequences_duplicated' => $sequences_duplicated,
						'created_at'           => $new_funnel->created_at,
					],
				],
				'Funnel duplicated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to duplicate funnel: ' . $e->getMessage(), 'duplicate_failed' );
		}
	}

	/**
	 * Execute activate-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel activation parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_activate_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id = intval( $args['funnel_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Check if already active
			if ( 'published' === $funnel->status ) {
				return $this->get_success_response(
					[
						'funnel_id'    => $funnel_id,
						'funnel_title' => $funnel->title,
						'status'       => 'published',
						'action'       => 'already_active',
					],
					'Funnel is already active'
				);
			}

			// Activate the funnel
			$funnel->update( [ 'status' => 'published' ] );

			return $this->get_success_response(
				[
					'funnel_id'    => $funnel_id,
					'funnel_title' => $funnel->title,
					'status'       => 'published',
					'action'       => 'activated',
					'activated_at' => current_time( 'mysql' ),
				],
				'Funnel activated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to activate funnel: ' . $e->getMessage(), 'activate_failed' );
		}
	}

	/**
	 * Execute deactivate-funnel ability
	 *
	 * @param array<string, mixed> $args Funnel deactivation parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_deactivate_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id = intval( $args['funnel_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Check if already inactive
			if ( 'draft' === $funnel->status ) {
				return $this->get_success_response(
					[
						'funnel_id'    => $funnel_id,
						'funnel_title' => $funnel->title,
						'status'       => 'draft',
						'action'       => 'already_inactive',
					],
					'Funnel is already inactive'
				);
			}

			// Deactivate the funnel
			$funnel->update( [ 'status' => 'draft' ] );

			return $this->get_success_response(
				[
					'funnel_id'      => $funnel_id,
					'funnel_title'   => $funnel->title,
					'status'         => 'draft',
					'action'         => 'deactivated',
					'deactivated_at' => current_time( 'mysql' ),
				],
				'Funnel deactivated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to deactivate funnel: ' . $e->getMessage(), 'deactivate_failed' );
		}
	}

	/**
	 * Execute get-funnel-subscribers ability
	 *
	 * @param array<string, mixed> $args Subscriber retrieval parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_get_funnel_subscribers( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id   = intval( $args['funnel_id'] );
			$status      = $args['status'] ?? null;
			$sequence_id = $args['sequence_id'] ?? null;
			$page        = $args['page'] ?? 1;
			$per_page    = $args['per_page'] ?? 20;

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			// Build query
			$query = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id );

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			// Apply sequence filter
			if ( $sequence_id ) {
				$query->where( 'next_sequence_id', intval( $sequence_id ) );
			}

			// Get total count
			$total = $query->count();

			// Get paginated results with subscriber details
			$offset      = ( $page - 1 ) * $per_page;
			$funnel_subs = $query->with( 'subscriber' )
								->orderBy( 'created_at', 'DESC' )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$subscribers = [];
			foreach ( $funnel_subs as $funnel_sub ) {
				$subscriber = $funnel_sub->subscriber;
				if ( $subscriber ) {
					$subscribers[] = [
						'funnel_subscriber_id' => $funnel_sub->id,
						'subscriber_id'        => $subscriber->id,
						'email'                => $subscriber->email,
						'first_name'           => $subscriber->first_name,
						'last_name'            => $subscriber->last_name,
						'status'               => $funnel_sub->status,
						'next_sequence_id'     => $funnel_sub->next_sequence_id,
						'last_executed_time'   => $funnel_sub->last_executed_time,
						'added_at'             => $funnel_sub->created_at,
					];
				}
			}

			return $this->get_success_response(
				[
					'subscribers' => $subscribers,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
					'funnel_id'   => $funnel_id,
				],
				'Funnel subscribers retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get funnel subscribers: ' . $e->getMessage(), 'get_subscribers_failed' );
		}
	}

	/**
	 * Execute get-funnel-metrics ability
	 *
	 * @param array<string, mixed> $args Metrics retrieval parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_get_funnel_metrics( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id  = intval( $args['funnel_id'] );
			$start_date = $args['start_date'] ?? null;
			$end_date   = $args['end_date'] ?? null;

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );

			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Get subscriber statistics
			$metrics = [
				'funnel_id'    => $funnel_id,
				'funnel_title' => $funnel->title,
				'status'       => $funnel->status,
				'subscribers'  => [
					'total'     => 0,
					'active'    => 0,
					'completed' => 0,
					'cancelled' => 0,
				],
				'sequences'    => [],
			];

			// Get subscriber counts
			if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
				$query = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id );

				// Apply date filters if provided
				if ( $start_date ) {
					$query->where( 'created_at', '>=', $start_date . ' 00:00:00' );
				}
				if ( $end_date ) {
					$query->where( 'created_at', '<=', $end_date . ' 23:59:59' );
				}

				$metrics['subscribers']['total']     = $query->count();
				$metrics['subscribers']['active']    = ( clone $query )->where( 'status', 'active' )->count();
				$metrics['subscribers']['completed'] = ( clone $query )->where( 'status', 'completed' )->count();
				$metrics['subscribers']['cancelled'] = ( clone $query )->where( 'status', 'cancelled' )->count();
			}

			// Get sequence statistics
			if ( class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				$sequences = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
														->orderBy( 'sequence', 'ASC' )
														->get();

				foreach ( $sequences as $sequence ) {
					$sequence_stats = [
						'id'                => $sequence->id,
						'title'             => $sequence->title,
						'type'              => $sequence->type,
						'subscribers_count' => 0,
					];

					// Count subscribers at this sequence
					if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
						$sequence_stats['subscribers_count'] = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )
																						->where( 'next_sequence_id', $sequence->id )
																						->count();
					}

					$metrics['sequences'][] = $sequence_stats;
				}
			}

			$metrics['date_range'] = [
				'start_date' => $start_date ?? 'all_time',
				'end_date'   => $end_date ?? 'all_time',
			];

			return $this->get_success_response(
				[
					'metrics' => $metrics,
				],
				'Funnel metrics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get funnel metrics: ' . $e->getMessage(), 'metrics_failed' );
		}
	}

	/**
	 * Execute test-funnel-conditions ability
	 *
	 * @param array<string, mixed> $args Test parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_test_funnel_conditions( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
			return $this->get_error_response( 'FluentCRM Funnel model not available - Feature requires FluentCRM', 'pro_required' );
		}

		try {
			$funnel_id     = intval( $args['funnel_id'] );
			$subscriber_id = intval( $args['subscriber_id'] );

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Validate funnel exists
			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );
			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Validate subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

			// Test basic conditions
			$test_results = [
				'funnel_id'     => $funnel_id,
				'funnel_title'  => $funnel->title,
				'subscriber_id' => $subscriber_id,
				'subscriber'    => [
					'email'      => $subscriber->email,
					'first_name' => $subscriber->first_name,
					'last_name'  => $subscriber->last_name,
					'status'     => $subscriber->status,
				],
				'conditions'    => [],
				'can_enter'     => true,
				'reasons'       => [],
			];

			// Check subscription status condition
			if ( isset( $funnel->settings['subscription_status'] ) ) {
				$required_status = $funnel->settings['subscription_status'];
				$status_match    = ( $subscriber->status === $required_status );

				$test_results['conditions']['subscription_status'] = [
					'required' => $required_status,
					'actual'   => $subscriber->status,
					'passed'   => $status_match,
				];

				if ( ! $status_match ) {
					$test_results['can_enter'] = false;
					$test_results['reasons'][] = "Subscriber status '{$subscriber->status}' does not match required '{$required_status}'";
				}
			}

			// Check run_only_once condition
			if ( isset( $funnel->conditions['run_only_once'] ) && $funnel->conditions['run_only_once'] ) {
				$already_in_funnel = false;
				if ( class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
					$existing          = \FluentCrm\App\Models\FunnelSubscriber::where( 'funnel_id', $funnel_id )
																	->where( 'subscriber_id', $subscriber_id )
																	->first();
					$already_in_funnel = ! empty( $existing );
				}

				$test_results['conditions']['run_only_once'] = [
					'enabled'           => true,
					'already_processed' => $already_in_funnel,
					'passed'            => ! $already_in_funnel,
				];

				if ( $already_in_funnel ) {
					$test_results['can_enter'] = false;
					$test_results['reasons'][] = 'Subscriber has already been processed by this funnel (run_only_once enabled)';
				}
			}

			// Check if subscriber has required lists/tags based on trigger
			$trigger_name = $funnel->trigger_name;
			if ( strpos( $trigger_name, 'list_' ) === 0 || strpos( $trigger_name, 'tag_' ) === 0 ) {
				$test_results['conditions']['trigger_match'] = [
					'trigger'      => $trigger_name,
					'can_validate' => false,
					'note'         => 'Trigger condition validation requires runtime context (e.g., which list/tag was applied)',
				];
			}

			return $this->get_success_response(
				[
					'test_results' => $test_results,
				],
				$test_results['can_enter'] ?
					'Subscriber passes all funnel conditions' :
					'Subscriber fails some funnel conditions'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to test funnel conditions: ' . $e->getMessage(), 'test_failed' );
		}
	}
}
