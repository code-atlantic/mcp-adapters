<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Funnel Sequence Abilities
 *
 * Registers WordPress abilities for FluentCRM automation funnel sequence management.
 * Sequences are individual automation steps/actions within a funnel workflow.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 */
class FunnelSequences extends BaseAbility {

	/**
	 * Register all funnel sequence-related abilities
	 */
	protected function register_abilities(): void {
		// Check if FluentCampaign (Pro) is active
		if ( ! $this->is_fluentcampaign_active() ) {
			return;
		}

		$this->register_add_funnel_sequence();
		$this->register_list_funnel_sequences();
		$this->register_update_funnel_sequence();
		$this->register_remove_funnel_sequence();
		$this->register_reorder_funnel_sequences();
	}

	/**
	 * Check if FluentCampaign (Pro) plugin is active
	 *
	 * @return bool True if FluentCampaign is active
	 */
	private function is_fluentcampaign_active(): bool {
		return defined( 'FLUENTCAMPAIGN' ) &&
			class_exists( '\FluentCrm\App\Models\Funnel', false ) &&
			class_exists( '\FluentCrm\App\Models\FunnelSequence', false );
	}

	/**
	 * Register add funnel sequence ability
	 */
	private function register_add_funnel_sequence(): void {
		wp_register_ability(
			'fluentcrm/add-funnel-sequence',
			[
				'label'               => 'Add Funnel Sequence to FluentCRM Funnel',
				'description'         => 'Add an automation step/action to a funnel workflow (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id'      => [
							'type'        => 'integer',
							'description' => 'Funnel ID to add sequence to (required)',
						],
						'action_name'    => [
							'type'        => 'string',
							'description' => 'Action identifier (e.g., send_email, add_tag, wait)',
						],
						'title'          => [
							'type'        => 'string',
							'description' => 'Sequence step name',
						],
						'description'    => [
							'type'        => 'string',
							'description' => 'Step description',
						],
						'conditions'     => [
							'type'        => 'object',
							'description' => 'JSON step conditions for conditional logic',
							'properties'  => [
								'type'         => [
									'type'        => 'string',
									'description' => 'Condition type (e.g., conditional_split)',
								],
								'conditions'   => [
									'type'        => 'array',
									'description' => 'Array of condition objects',
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'field'    => [ 'type' => 'string' ],
											'operator' => [ 'type' => 'string' ],
											'value'    => [ 'type' => [ 'string', 'number', 'array' ] ],
										],
									],
								],
								'yes_sequence' => [
									'type'        => 'integer',
									'description' => 'Sequence ID if condition is true',
								],
								'no_sequence'  => [
									'type'        => 'integer',
									'description' => 'Sequence ID if condition is false',
								],
							],
						],
						'settings'       => [
							'type'        => 'object',
							'description' => 'JSON step settings (action-specific configuration)',
							'properties'  => [
								'email_subject'      => [
									'type'        => 'string',
									'description' => 'Email subject for send_email action',
								],
								'email_body'         => [
									'type'        => 'string',
									'description' => 'Email body HTML for send_email action',
								],
								'tag_ids'            => [
									'type'        => 'array',
									'description' => 'Tag IDs for tag actions',
									'items'       => [ 'type' => 'integer' ],
								],
								'list_ids'           => [
									'type'        => 'array',
									'description' => 'List IDs for list actions',
									'items'       => [ 'type' => 'integer' ],
								],
								'benchmark_value'    => [
									'type'        => 'number',
									'description' => 'Revenue/conversion value for tracking',
								],
								'benchmark_currency' => [
									'type'        => 'string',
									'description' => 'Currency code (default: USD)',
								],
							],
						],
						'delay'          => [
							'type'        => 'integer',
							'description' => 'Delay in seconds before execution (0 = immediate)',
							'default'     => 0,
							'minimum'     => 0,
						],
						'sequence'       => [
							'type'        => 'integer',
							'description' => 'Execution order within funnel (auto-calculated if not provided)',
							'minimum'     => 1,
						],
						'parent_id'      => [
							'type'        => 'integer',
							'description' => 'Parent sequence ID for branching (0 = root level)',
							'default'     => 0,
							'minimum'     => 0,
						],
						'condition_type' => [
							'type'        => 'string',
							'description' => 'Conditional branching type',
						],
						'status'         => [
							'type'        => 'string',
							'description' => 'Sequence status',
							'enum'        => [ 'draft', 'published' ],
							'default'     => 'draft',
						],
					],
					'required'   => [ 'funnel_id', 'action_name' ],
				],
				'execute_callback'    => [ $this, 'execute_add_funnel_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'funnels',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register list funnel sequences ability
	 */
	private function register_list_funnel_sequences(): void {
		wp_register_ability(
			'fluentcrm/list-funnel-sequences',
			[
				'label'               => 'List FluentCRM Funnel Sequences',
				'description'         => 'List all automation steps in a funnel workflow (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id' => [
							'type'        => 'integer',
							'description' => 'Funnel ID to list sequences from (required)',
						],
						'status'    => [
							'type'        => 'string',
							'description' => 'Filter by status',
							'enum'        => [ 'draft', 'published' ],
						],
					],
					'required'   => [ 'funnel_id' ],
				],
				'execute_callback'    => [ $this, 'execute_list_funnel_sequences' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'funnels',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register update funnel sequence ability
	 */
	private function register_update_funnel_sequence(): void {
		wp_register_ability(
			'fluentcrm/update-funnel-sequence',
			[
				'label'               => 'Update FluentCRM Funnel Sequence',
				'description'         => 'Update an automation step in a funnel workflow (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id'    => [
							'type'        => 'integer',
							'description' => 'Sequence ID to update (required)',
						],
						'action_name'    => [
							'type'        => 'string',
							'description' => 'Updated action identifier',
						],
						'title'          => [
							'type'        => 'string',
							'description' => 'Updated sequence step name',
						],
						'description'    => [
							'type'        => 'string',
							'description' => 'Updated step description',
						],
						'conditions'     => [
							'type'        => 'object',
							'description' => 'Updated step conditions',
						],
						'settings'       => [
							'type'        => 'object',
							'description' => 'Updated step settings',
						],
						'delay'          => [
							'type'        => 'integer',
							'description' => 'Updated delay in seconds',
							'minimum'     => 0,
						],
						'sequence'       => [
							'type'        => 'integer',
							'description' => 'Updated execution order',
							'minimum'     => 1,
						],
						'parent_id'      => [
							'type'        => 'integer',
							'description' => 'Updated parent sequence ID',
							'minimum'     => 0,
						],
						'condition_type' => [
							'type'        => 'string',
							'description' => 'Updated conditional branching type',
						],
						'status'         => [
							'type'        => 'string',
							'description' => 'Updated status',
							'enum'        => [ 'draft', 'published' ],
						],
					],
					'required'   => [ 'sequence_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_funnel_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'funnels',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register remove funnel sequence ability
	 */
	private function register_remove_funnel_sequence(): void {
		wp_register_ability(
			'fluentcrm/remove-funnel-sequence',
			[
				'label'               => 'Remove FluentCRM Funnel Sequence',
				'description'         => 'Remove an automation step from a funnel workflow (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id'    => [
							'type'        => 'integer',
							'description' => 'Sequence ID to remove (required)',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
					'required'   => [ 'sequence_id', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_funnel_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'funnels',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register reorder funnel sequences ability
	 */
	private function register_reorder_funnel_sequences(): void {
		wp_register_ability(
			'fluentcrm/reorder-funnel-sequences',
			[
				'label'               => 'Reorder FluentCRM Funnel Sequences',
				'description'         => 'Reorder automation steps in a funnel workflow (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_id'    => [
							'type'        => 'integer',
							'description' => 'Funnel ID containing sequences (required)',
						],
						'sequence_ids' => [
							'type'        => 'array',
							'description' => 'Array of sequence IDs in desired order (required)',
							'items'       => [
								'type' => 'integer',
							],
							'minItems'    => 1,
						],
					],
					'required'   => [ 'funnel_id', 'sequence_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_reorder_funnel_sequences' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'funnels',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute add funnel sequence
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_funnel_sequence( array $args ): array {
		try {
			$funnel_id   = absint( $args['funnel_id'] ?? 0 );
			$action_name = sanitize_text_field( $args['action_name'] ?? '' );

			if ( empty( $funnel_id ) || $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			if ( empty( $action_name ) ) {
				return $this->get_error_response( 'Action name is required', 'action_name_required' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for funnels', 'pro_required' );
			}

			// Verify funnel exists
			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );
			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Auto-calculate sequence order if not provided
			$sequence_order = $args['sequence'] ?? null;
			if ( null === $sequence_order ) {
				$max_sequence   = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
					->max( 'sequence' );
				$sequence_order = ( $max_sequence ?? 0 ) + 1;
			}

			// Prepare sequence data
			$sequence_data = [
				'funnel_id'   => $funnel_id,
				'action_name' => $action_name,
				'type'        => 'sequence',
				'status'      => $args['status'] ?? 'draft',
				'sequence'    => $sequence_order,
				'parent_id'   => absint( $args['parent_id'] ?? 0 ),
				'delay'       => absint( $args['delay'] ?? 0 ),
				'created_by'  => get_current_user_id(),
			];

			// Optional fields
			if ( isset( $args['title'] ) ) {
				$sequence_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				$sequence_data['description'] = sanitize_text_field( $args['description'] );
			}

			if ( isset( $args['condition_type'] ) ) {
				$sequence_data['condition_type'] = sanitize_text_field( $args['condition_type'] );
			}

			if ( isset( $args['conditions'] ) ) {
				$sequence_data['conditions'] = $args['conditions'];
			}

			if ( isset( $args['settings'] ) ) {
				$sequence_data['settings'] = $args['settings'];
			}

			// Calculate cumulative delay
			$parent_id = $sequence_data['parent_id'];
			$c_delay   = $sequence_data['delay'];

			if ( $parent_id > 0 ) {
				$parent = \FluentCrm\App\Models\FunnelSequence::find( $parent_id );
				if ( $parent ) {
					$c_delay += ( $parent->c_delay ?? 0 );
				}
			}

			$sequence_data['c_delay'] = $c_delay;

			// Create sequence
			$sequence = \FluentCrm\App\Models\FunnelSequence::create( $sequence_data );

			return $this->get_success_response(
				[
					'sequence' => $this->format_sequence_response( $sequence ),
				],
				'Funnel sequence added successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add funnel sequence: ' . $e->getMessage(), 'add_failed' );
		}
	}

	/**
	 * Execute list funnel sequences
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_funnel_sequences( array $args ): array {
		try {
			$funnel_id = absint( $args['funnel_id'] ?? 0 );
			$status    = $args['status'] ?? null;

			if ( empty( $funnel_id ) || $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			// Verify funnel exists
			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );
			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			$query = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id );

			// Add status filter if provided
			if ( ! empty( $status ) ) {
				$query = $query->where( 'status', $status );
			}

			// Get sequences ordered by execution order
			$sequences = $query->orderBy( 'sequence', 'ASC' )->get();

			$formatted_sequences = [];
			foreach ( $sequences as $sequence ) {
				$formatted_sequences[] = $this->format_sequence_response( $sequence );
			}

			return $this->get_success_response(
				[
					'funnel_id' => $funnel_id,
					'sequences' => $formatted_sequences,
					'total'     => count( $formatted_sequences ),
				],
				'Funnel sequences retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list funnel sequences: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute update funnel sequence
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_funnel_sequence( array $args ): array {
		try {
			$sequence_id = absint( $args['sequence_id'] ?? 0 );

			if ( empty( $sequence_id ) || $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			$sequence = \FluentCrm\App\Models\FunnelSequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			$update_data = [];

			// Update fields if provided
			if ( isset( $args['action_name'] ) ) {
				$update_data['action_name'] = sanitize_text_field( $args['action_name'] );
			}

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_text_field( $args['description'] );
			}

			if ( isset( $args['status'] ) ) {
				$update_data['status'] = $args['status'];
			}

			if ( isset( $args['condition_type'] ) ) {
				$update_data['condition_type'] = sanitize_text_field( $args['condition_type'] );
			}

			if ( isset( $args['conditions'] ) ) {
				$update_data['conditions'] = $args['conditions'];
			}

			if ( isset( $args['settings'] ) ) {
				$update_data['settings'] = $args['settings'];
			}

			if ( isset( $args['sequence'] ) ) {
				$update_data['sequence'] = absint( $args['sequence'] );
			}

			if ( isset( $args['parent_id'] ) ) {
				$update_data['parent_id'] = absint( $args['parent_id'] );
			}

			if ( isset( $args['delay'] ) ) {
				$update_data['delay'] = absint( $args['delay'] );

				// Recalculate cumulative delay
				$parent_id = $update_data['parent_id'] ?? $sequence->parent_id;
				$c_delay   = $update_data['delay'];

				if ( $parent_id > 0 ) {
					$parent = \FluentCrm\App\Models\FunnelSequence::find( $parent_id );
					if ( $parent ) {
						$c_delay += ( $parent->c_delay ?? 0 );
					}
				}

				$update_data['c_delay'] = $c_delay;
			}

			// Allow updates with no changes - just return current sequence
			if ( empty( $update_data ) ) {
				return $this->get_success_response(
					[
						'sequence' => $this->format_sequence_response( $sequence ),
					],
					'No changes made'
				);
			}

			$sequence->update( $update_data );

			// Refresh to get updated values
			$sequence = \FluentCrm\App\Models\FunnelSequence::find( $sequence_id );

			return $this->get_success_response(
				[
					'sequence' => $this->format_sequence_response( $sequence ),
				],
				'Funnel sequence updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update funnel sequence: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute remove funnel sequence
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_funnel_sequence( array $args ): array {
		try {
			$sequence_id    = absint( $args['sequence_id'] ?? 0 );
			$confirm_delete = (bool) ( $args['confirm_delete'] ?? false );

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			if ( empty( $sequence_id ) || $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\FunnelSequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			$sequence = \FluentCrm\App\Models\FunnelSequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			$funnel_id     = $sequence->funnel_id;
			$sequence_name = $sequence->title ?? $sequence->action_name;

			$sequence->delete();

			return $this->get_success_response(
				[
					'sequence_id'   => $sequence_id,
					'funnel_id'     => $funnel_id,
					'sequence_name' => $sequence_name,
					'deleted_at'    => current_time( 'mysql' ),
				],
				'Funnel sequence removed successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove funnel sequence: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute reorder funnel sequences
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_reorder_funnel_sequences( array $args ): array {
		try {
			$funnel_id    = absint( $args['funnel_id'] ?? 0 );
			$sequence_ids = $args['sequence_ids'] ?? [];

			if ( empty( $funnel_id ) || $funnel_id <= 0 ) {
				return $this->get_error_response( 'Invalid funnel ID', 'invalid_funnel_id' );
			}

			if ( empty( $sequence_ids ) || ! is_array( $sequence_ids ) ) {
				return $this->get_error_response( 'Sequence IDs array is required', 'sequence_ids_required' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\Funnel' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			// Verify funnel exists
			$funnel = \FluentCrm\App\Models\Funnel::find( $funnel_id );
			if ( ! $funnel ) {
				return $this->get_error_response( 'Funnel not found', 'funnel_not_found' );
			}

			// Update sequence order for each sequence ID
			$updated_count = 0;
			foreach ( $sequence_ids as $index => $sequence_id ) {
				$sequence_id = absint( $sequence_id );
				if ( $sequence_id <= 0 ) {
					continue;
				}

				$sequence = \FluentCrm\App\Models\FunnelSequence::where( 'id', $sequence_id )
					->where( 'funnel_id', $funnel_id )
					->first();

				if ( $sequence ) {
					$sequence->update( [ 'sequence' => $index + 1 ] );
					++$updated_count;
				}
			}

			// Get updated sequences
			$sequences = \FluentCrm\App\Models\FunnelSequence::where( 'funnel_id', $funnel_id )
				->orderBy( 'sequence', 'ASC' )
				->get();

			$formatted_sequences = [];
			foreach ( $sequences as $sequence ) {
				$formatted_sequences[] = $this->format_sequence_response( $sequence );
			}

			return $this->get_success_response(
				[
					'funnel_id'     => $funnel_id,
					'updated_count' => $updated_count,
					'sequences'     => $formatted_sequences,
				],
				'Funnel sequences reordered successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to reorder funnel sequences: ' . $e->getMessage(), 'reorder_failed' );
		}
	}

	/**
	 * Format sequence response using toArray() pattern
	 *
	 * @param object $sequence FluentCampaign FunnelSequence model instance
	 * @return array Formatted sequence data
	 */
	private function format_sequence_response( $sequence ): array {
		return [
			'id'             => $sequence->id,
			'funnel_id'      => $sequence->funnel_id,
			'parent_id'      => $sequence->parent_id ?? 0,
			'action_name'    => $sequence->action_name,
			'condition_type' => $sequence->condition_type ?? null,
			'type'           => $sequence->type ?? 'sequence',
			'title'          => $sequence->title ?? null,
			'description'    => $sequence->description ?? null,
			'status'         => $sequence->status,
			'conditions'     => $sequence->conditions ?? null,
			'settings'       => $sequence->settings ?? null,
			'note'           => $sequence->note ?? null,
			'delay'          => $sequence->delay ?? 0,
			'c_delay'        => $sequence->c_delay ?? 0,
			'sequence'       => $sequence->sequence ?? 1,
			'created_by'     => $sequence->created_by ?? null,
			'created_at'     => $sequence->created_at,
			'updated_at'     => $sequence->updated_at ?? $sequence->created_at,
		];
	}
}
