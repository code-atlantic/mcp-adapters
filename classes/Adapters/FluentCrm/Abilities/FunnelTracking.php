<?php
/**
 * FluentCRM Funnel Tracking and Subscriber Management Abilities
 *
 * Provides comprehensive funnel subscriber tracking and management tools including:
 * - Subscriber funnel status across all active automations
 * - Manual funnel enrollment and removal operations
 * - Funnel progress reset and history tracking
 * - Complete subscriber automation journey visibility
 *
 * Complements Funnels.php which provides get-funnel-subscribers for listing
 * subscribers within a specific funnel.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FunnelTracking Ability Class
 *
 * Manages subscriber-centric funnel tracking operations including enrollment,
 * status monitoring, progress management, and complete automation history.
 */
class FunnelTracking extends BaseAbility {

	/**
	 * Check if FluentCRM funnel tracking is available
	 *
	 * @return bool True if funnel models are available
	 */
	private function is_funnel_tracking_available(): bool {
		return class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) &&
				class_exists( '\FluentCrm\App\Models\Funnel' ) &&
				class_exists( '\FluentCrm\App\Models\Subscriber' );
	}

	/**
	 * Register all funnel tracking abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if funnel tracking not available
		if ( ! $this->is_funnel_tracking_available() ) {
			return;
		}

		$this->register_get_subscriber_funnel_status();
		$this->register_enroll_subscriber_in_funnel();
		$this->register_remove_subscriber_from_funnel();
		$this->register_reset_subscriber_funnel_progress();
		$this->register_get_subscriber_funnel_history();
	}

	/**
	 * Register get-subscriber-funnel-status ability
	 *
	 * @return void
	 */
	private function register_get_subscriber_funnel_status(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber-funnel-status',
			[
				'label'               => 'FluentCRM Get Subscriber Funnel Status',
				'description'         => 'Retrieve a subscriber\'s current status across all active funnels, including next scheduled actions, completion status, and progress through each automation. Returns complete funnel subscriber records with loaded relationships for subscriber and funnel details. Example: Get all active automations for subscriber ID 123 to see their automation journey and upcoming scheduled sequences.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id' ],
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to retrieve funnel status for',
						],
						'status'        => [
							'type'        => 'string',
							'description' => 'Filter by funnel subscriber status',
							'enum'        => [ 'active', 'completed', 'cancelled' ],
						],
						'funnel_id'     => [
							'type'        => 'integer',
							'description' => 'Filter by specific funnel ID (optional)',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_subscriber_funnel_status' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register enroll-subscriber-in-funnel ability
	 *
	 * @return void
	 */
	private function register_enroll_subscriber_in_funnel(): void {
		wp_register_ability(
			'fluentcrm/enroll-subscriber-in-funnel',
			[
				'label'               => 'FluentCRM Enroll Subscriber in Funnel',
				'description'         => 'Manually enroll a subscriber into an automation funnel, bypassing normal trigger conditions. Sets initial status to active and schedules first sequence for immediate or delayed execution. Use this for manual campaign enrollment, testing automations, or recovering subscribers who should be in a funnel. Example: Enroll subscriber ID 123 into funnel ID 5 to manually add them to a welcome sequence.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id', 'funnel_id' ],
					'properties' => [
						'subscriber_id'        => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to enroll',
						],
						'funnel_id'            => [
							'type'        => 'integer',
							'description' => 'Funnel ID to enroll subscriber into',
						],
						'starting_sequence_id' => [
							'type'        => 'integer',
							'description' => 'Specific sequence to start at (optional - uses funnel\'s first sequence if not provided)',
						],
						'force_enroll'         => [
							'type'        => 'boolean',
							'description' => 'Force enrollment even if subscriber already exists in funnel (will create duplicate entry)',
							'default'     => false,
						],
						'notes'                => [
							'type'        => 'string',
							'description' => 'Optional notes about this enrollment (e.g., "Manual enrollment for testing")',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_enroll_subscriber_in_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register remove-subscriber-from-funnel ability
	 *
	 * @return void
	 */
	private function register_remove_subscriber_from_funnel(): void {
		wp_register_ability(
			'fluentcrm/remove-subscriber-from-funnel',
			[
				'label'               => 'FluentCRM Remove Subscriber from Funnel',
				'description'         => 'Remove a subscriber from an active funnel by setting their status to cancelled. This stops all future sequence execution for this subscriber in the specified funnel while preserving their history. The subscriber can be re-enrolled later if needed. Example: Remove subscriber ID 123 from funnel ID 5 to stop their automation journey without deleting history.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id', 'funnel_id' ],
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to remove from funnel',
						],
						'funnel_id'     => [
							'type'        => 'integer',
							'description' => 'Funnel ID to remove subscriber from',
						],
						'notes'         => [
							'type'        => 'string',
							'description' => 'Optional notes about removal reason (e.g., "Customer requested to stop automation")',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_remove_subscriber_from_funnel' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register reset-subscriber-funnel-progress ability
	 *
	 * @return void
	 */
	private function register_reset_subscriber_funnel_progress(): void {
		wp_register_ability(
			'fluentcrm/reset-subscriber-funnel-progress',
			[
				'label'               => 'FluentCRM Reset Subscriber Funnel Progress',
				'description'         => 'Reset a subscriber\'s progress in a funnel back to the beginning or a specific sequence. This reactivates their automation journey from the start (or specified point) while preserving original enrollment data. Useful for re-running automations, testing sequences, or giving subscribers a fresh start. Example: Reset subscriber ID 123 in funnel ID 5 to restart their welcome sequence from the beginning.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id', 'funnel_id' ],
					'properties' => [
						'subscriber_id'   => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to reset progress for',
						],
						'funnel_id'       => [
							'type'        => 'integer',
							'description' => 'Funnel ID to reset progress in',
						],
						'reset_to_start'  => [
							'type'        => 'boolean',
							'description' => 'Reset to funnel\'s first sequence (default: true)',
							'default'     => true,
						],
						'sequence_id'     => [
							'type'        => 'integer',
							'description' => 'Specific sequence ID to reset to (overrides reset_to_start if provided)',
						],
						'preserve_status' => [
							'type'        => 'boolean',
							'description' => 'Keep current status (completed/cancelled) instead of setting to active',
							'default'     => false,
						],
						'notes'           => [
							'type'        => 'string',
							'description' => 'Optional notes about reset reason (e.g., "Reset for retesting automation flow")',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_reset_subscriber_funnel_progress' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register get-subscriber-funnel-history ability
	 *
	 * @return void
	 */
	private function register_get_subscriber_funnel_history(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber-funnel-history',
			[
				'label'               => 'FluentCRM Get Subscriber Funnel History',
				'description'         => 'Retrieve complete automation history for a subscriber across all funnels, including active, completed, and cancelled automations. Shows enrollment dates, completion status, last executed sequences, and full timeline. Supports pagination for subscribers with extensive automation history. Returns array of funnel subscriber records with loaded relationships. Example: Get full automation history for subscriber ID 123 to audit their complete journey.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id' ],
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to retrieve history for',
						],
						'status'        => [
							'type'        => 'string',
							'description' => 'Filter by status (optional - returns all statuses if not provided)',
							'enum'        => [ 'active', 'completed', 'cancelled' ],
						],
						'page'          => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'      => [
							'type'        => 'integer',
							'description' => 'Records per page',
							'default'     => 50,
							'minimum'     => 1,
							'maximum'     => 200,
						],
						'order_by'      => [
							'type'        => 'string',
							'description' => 'Field to order results by',
							'enum'        => [ 'created_at', 'updated_at', 'last_executed_time' ],
							'default'     => 'created_at',
						],
						'order'         => [
							'type'        => 'string',
							'description' => 'Sort direction',
							'enum'        => [ 'asc', 'desc' ],
							'default'     => 'desc',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'execute_callback'    => [ $this, 'execute_get_subscriber_funnel_history' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'automation',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Execute get-subscriber-funnel-status ability
	 *
	 * @param array<string, mixed> $args Request parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_get_subscriber_funnel_status( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$status        = $args['status'] ?? null;
			$funnel_id     = $args['funnel_id'] ?? null;

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Build query with relationships
			$query = \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
				->with( [ 'subscriber', 'funnel' ] );

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			// Apply funnel filter
			if ( $funnel_id ) {
				$query->where( 'funnel_id', intval( $funnel_id ) );
			}

			// Get results ordered by most recent
			$funnel_subscribers = $query->orderBy( 'created_at', 'DESC' )->get();

			// Convert to array with full relationships
			$status_list = [];
			foreach ( $funnel_subscribers as $funnel_sub ) {
				$status_list[] = $funnel_sub->toArray();
			}

			return $this->get_success_response(
				[
					'subscriber_id'   => $subscriber_id,
					'funnel_count'    => count( $status_list ),
					'funnel_status'   => $status_list,
					'active_count'    => count( array_filter( $status_list, fn( $fs ) => 'active' === $fs['status'] ) ),
					'completed_count' => count( array_filter( $status_list, fn( $fs ) => 'completed' === $fs['status'] ) ),
					'cancelled_count' => count( array_filter( $status_list, fn( $fs ) => 'cancelled' === $fs['status'] ) ),
				],
				'Subscriber funnel status retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get subscriber funnel status: ' . $e->getMessage(), 'get_status_failed' );
		}
	}

	/**
	 * Execute enroll-subscriber-in-funnel ability
	 *
	 * @param array<string, mixed> $args Request parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_enroll_subscriber_in_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id        = intval( $args['subscriber_id'] );
			$funnel_id            = intval( $args['funnel_id'] );
			$starting_sequence_id = $args['starting_sequence_id'] ?? null;
			$force_enroll         = $args['force_enroll'] ?? false;
			$notes                = $args['notes'] ?? null;

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Verify funnel exists
			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );
			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Check for existing enrollment
			$existing = \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
				->where( 'funnel_id', $funnel_id )
				->first();

			if ( $existing && ! $force_enroll ) {
				return $this->get_error_response(
					'Subscriber already enrolled in this funnel. Use force_enroll=true to create duplicate enrollment.',
					'already_enrolled'
				);
			}

			// Get starting sequence
			$sequence_id = $starting_sequence_id;
			if ( ! $sequence_id && class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				$first_sequence = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
					->orderBy( 'sequence', 'ASC' )
					->first();

				if ( $first_sequence ) {
					$sequence_id = $first_sequence->id;
				}
			}

			// Create enrollment
			$enrollment_data = [
				'subscriber_id'        => $subscriber_id,
				'funnel_id'            => $funnel_id,
				'starting_sequence_id' => $sequence_id,
				'next_sequence_id'     => $sequence_id,
				'status'               => 'active',
				'source_trigger_name'  => 'manual_enrollment',
				'notes'                => $notes,
			];

			$funnel_subscriber = \FluentCrm\App\Models\FunnelSubscriber::create( $enrollment_data );

			// Load relationships
			$funnel_subscriber->load( [ 'subscriber', 'funnel' ] );

			return $this->get_success_response(
				[
					'funnel_subscriber' => $funnel_subscriber->toArray(),
					'action'            => 'enrolled',
					'enrolled_at'       => $funnel_subscriber->created_at,
				],
				'Subscriber enrolled in funnel successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to enroll subscriber in funnel: ' . $e->getMessage(), 'enrollment_failed' );
		}
	}

	/**
	 * Execute remove-subscriber-from-funnel ability
	 *
	 * @param array<string, mixed> $args Request parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_remove_subscriber_from_funnel( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$funnel_id     = intval( $args['funnel_id'] );
			$notes         = $args['notes'] ?? null;

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			// Find funnel subscriber record
			$funnel_subscriber = \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
				->where( 'funnel_id', $funnel_id )
				->where( 'status', 'active' ) // Only remove active enrollments
				->first();

			if ( ! $funnel_subscriber ) {
				return $this->get_error_response(
					'Active funnel enrollment not found for this subscriber',
					'enrollment_not_found'
				);
			}

			// Update status to cancelled
			$update_data = [ 'status' => 'cancelled' ];
			if ( $notes ) {
				// Append notes to existing notes
				$existing_notes       = $funnel_subscriber->notes ?? '';
				$update_data['notes'] = ! empty( $existing_notes )
					? $existing_notes . ' | Removed: ' . $notes
					: 'Removed: ' . $notes;
			}

			$funnel_subscriber->update( $update_data );

			// Reload with relationships
			$funnel_subscriber->load( [ 'subscriber', 'funnel' ] );

			return $this->get_success_response(
				[
					'funnel_subscriber' => $funnel_subscriber->toArray(),
					'action'            => 'removed',
					'previous_status'   => 'active',
					'new_status'        => 'cancelled',
					'removed_at'        => current_time( 'mysql' ),
				],
				'Subscriber removed from funnel successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove subscriber from funnel: ' . $e->getMessage(), 'removal_failed' );
		}
	}

	/**
	 * Execute reset-subscriber-funnel-progress ability
	 *
	 * @param array<string, mixed> $args Request parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_reset_subscriber_funnel_progress( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id   = intval( $args['subscriber_id'] );
			$funnel_id       = intval( $args['funnel_id'] );
			$reset_to_start  = $args['reset_to_start'] ?? true;
			$sequence_id     = $args['sequence_id'] ?? null;
			$preserve_status = $args['preserve_status'] ?? false;
			$notes           = $args['notes'] ?? null;

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			if ( $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			// Find funnel subscriber record
			$funnel_subscriber = \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
				->where( 'funnel_id', $funnel_id )
				->first();

			if ( ! $funnel_subscriber ) {
				return $this->get_error_response( 'Funnel enrollment not found for this subscriber', 'enrollment_not_found' );
			}

			// Determine target sequence
			$target_sequence_id = $sequence_id;

			if ( ! $target_sequence_id && $reset_to_start ) {
				// Get first sequence in funnel
				if ( class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
					$first_sequence = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
						->orderBy( 'sequence', 'ASC' )
						->first();

					if ( $first_sequence ) {
						$target_sequence_id = $first_sequence->id;
					}
				}

				// Use starting sequence as fallback
				if ( ! $target_sequence_id ) {
					$target_sequence_id = $funnel_subscriber->starting_sequence_id;
				}
			}

			// Build update data
			$update_data = [
				'next_sequence_id'     => $target_sequence_id,
				'last_sequence_id'     => null,
				'last_executed_time'   => null,
				'next_execution_time'  => null,
				'last_sequence_status' => 'pending',
			];

			// Update status unless preserving
			if ( ! $preserve_status ) {
				$update_data['status'] = 'active';
			}

			// Append reset notes
			if ( $notes ) {
				$existing_notes       = $funnel_subscriber->notes ?? '';
				$update_data['notes'] = ! empty( $existing_notes )
					? $existing_notes . ' | Reset: ' . $notes
					: 'Reset: ' . $notes;
			}

			// Store previous state for response
			$previous_state = [
				'status'               => $funnel_subscriber->status,
				'next_sequence_id'     => $funnel_subscriber->next_sequence_id,
				'last_sequence_id'     => $funnel_subscriber->last_sequence_id,
				'last_executed_time'   => $funnel_subscriber->last_executed_time,
				'last_sequence_status' => $funnel_subscriber->last_sequence_status,
			];

			// Update the record
			$funnel_subscriber->update( $update_data );

			// Reload with relationships
			$funnel_subscriber->load( [ 'subscriber', 'funnel' ] );

			return $this->get_success_response(
				[
					'funnel_subscriber' => $funnel_subscriber->toArray(),
					'action'            => 'reset',
					'reset_to'          => [
						'sequence_id' => $target_sequence_id,
						'status'      => $preserve_status ? $previous_state['status'] : 'active',
					],
					'previous_state'    => $previous_state,
					'reset_at'          => current_time( 'mysql' ),
				],
				'Subscriber funnel progress reset successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to reset subscriber funnel progress: ' . $e->getMessage(), 'reset_failed' );
		}
	}

	/**
	 * Execute get-subscriber-funnel-history ability
	 *
	 * @param array<string, mixed> $args Request parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_get_subscriber_funnel_history( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return $this->get_error_response( 'FunnelSubscriber model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$status        = $args['status'] ?? null;
			$page          = $args['page'] ?? 1;
			$per_page      = $args['per_page'] ?? 50;
			$order_by      = $args['order_by'] ?? 'created_at';
			$order         = $args['order'] ?? 'desc';

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Build query with relationships
			$query = \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
				->with( [ 'subscriber', 'funnel' ] );

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			// Get total count
			$total = $query->count();

			// Apply pagination and ordering
			$offset = ( $page - 1 ) * $per_page;
			$query->orderBy( $order_by, strtoupper( $order ) )
				->offset( $offset )
				->limit( $per_page );

			// Get results
			$history = $query->get();

			// Convert to array with full relationships
			$history_list = [];
			foreach ( $history as $funnel_sub ) {
				$history_list[] = $funnel_sub->toArray();
			}

			// Calculate summary statistics
			$summary = [
				'total_enrollments'     => $total,
				'active_enrollments'    => \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
					->where( 'status', 'active' )
					->count(),
				'completed_enrollments' => \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
					->where( 'status', 'completed' )
					->count(),
				'cancelled_enrollments' => \FluentCrm\App\Models\FunnelSubscriber::where( 'subscriber_id', $subscriber_id )
					->where( 'status', 'cancelled' )
					->count(),
			];

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'history'       => $history_list,
					'summary'       => $summary,
					'pagination'    => [
						'total'       => $total,
						'page'        => $page,
						'per_page'    => $per_page,
						'total_pages' => ceil( $total / $per_page ),
					],
				],
				'Subscriber funnel history retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get subscriber funnel history: ' . $e->getMessage(), 'get_history_failed' );
		}
	}
}
