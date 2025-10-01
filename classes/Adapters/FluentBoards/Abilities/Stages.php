<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Stage Abilities
 *
 * Registers WordPress abilities for FluentBoards stage management operations
 * using the new WordPress Abilities API pattern.
 */
class Stages extends BaseAbility {

	/**
	 * Register all stage-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_list_stages();
		$this->register_create_stage();
		$this->register_update_stage();
		$this->register_delete_stage();
		$this->register_restore_stage();
		$this->register_reorder_stages();
		$this->register_move_all_tasks();
		$this->register_archive_all_tasks();
		$this->register_get_archived_stages();
		$this->register_sort_stage_tasks();
		$this->register_get_stage_positions();
	}

	/**
	 * Register list stages ability
	 */
	private function register_list_stages(): void {
		wp_register_ability(
			'fluentboards/list-stages',
			[
				'label'               => 'List FluentBoards stages',
				'description'         => 'List all stages in a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id'         => [
							'type'        => 'integer',
							'description' => 'Board ID to list stages from',
						],
						'include_archived' => [
							'type'        => 'boolean',
							'description' => 'Include archived stages',
							'default'     => false,
						],
					],
					'required'   => [ 'board_id' ],
				],
				'execute_callback'    => [ $this, 'execute_list_stages' ],
				'permission_callback' => [ $this, 'can_view_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register create stage ability
	 */
	private function register_create_stage(): void {
		wp_register_ability(
			'fluentboards/create-stage',
			[
				'label'               => 'Create FluentBoards stage',
				'description'         => 'Create a new stage in a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'title'    => [
							'type'        => 'string',
							'description' => 'Stage title',
						],
						'position' => [
							'type'        => 'integer',
							'description' => 'Stage position (optional - auto-assigned if not provided)',
						],
						'settings' => [
							'type'        => 'object',
							'description' => 'Stage settings (default_task_status, etc.)',
						],
					],
					'required'   => [ 'board_id', 'title' ],
				],
				'execute_callback'    => [ $this, 'execute_create_stage' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register update stage ability
	 */
	private function register_update_stage(): void {
		wp_register_ability(
			'fluentboards/update-stage',
			[
				'label'               => 'Update FluentBoards stage',
				'description'         => 'Update an existing stage',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID to update',
						],
						'title'    => [
							'type'        => 'string',
							'description' => 'Updated stage title',
						],
						'bg_color' => [
							'type'        => 'string',
							'description' => 'Updated stage background color (hex)',
							'pattern'     => '^#[0-9a-fA-F]{6}$',
						],
						'settings' => [
							'type'        => 'object',
							'description' => 'Updated stage settings',
						],
					],
					'required'   => [ 'board_id', 'stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_stage' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register delete stage ability (archive)
	 */
	private function register_delete_stage(): void {
		wp_register_ability(
			'fluentboards/delete-stage',
			[
				'label'               => 'Archive FluentBoards stage',
				'description'         => 'Archive a stage (soft delete)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID to archive',
						],
					],
					'required'   => [ 'board_id', 'stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_stage' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register restore stage ability
	 */
	private function register_restore_stage(): void {
		wp_register_ability(
			'fluentboards/restore-stage',
			[
				'label'               => 'Restore FluentBoards stage',
				'description'         => 'Restore an archived stage',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID to restore',
						],
					],
					'required'   => [ 'board_id', 'stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_restore_stage' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register reorder stages ability
	 */
	private function register_reorder_stages(): void {
		wp_register_ability(
			'fluentboards/reorder-stages',
			[
				'label'               => 'Reorder FluentBoards stages',
				'description'         => 'Reorder stages in a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id'  => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_ids' => [
							'type'        => 'array',
							'items'       => [
								'type' => 'integer',
							],
							'description' => 'Array of stage IDs in new order',
						],
					],
					'required'   => [ 'board_id', 'stage_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_reorder_stages' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register move all tasks ability
	 */
	private function register_move_all_tasks(): void {
		wp_register_ability(
			'fluentboards/move-all-tasks',
			[
				'label'               => 'Move all tasks between stages',
				'description'         => 'Move all tasks from one stage to another',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id'     => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'old_stage_id' => [
							'type'        => 'integer',
							'description' => 'Source stage ID',
						],
						'new_stage_id' => [
							'type'        => 'integer',
							'description' => 'Destination stage ID',
						],
					],
					'required'   => [ 'board_id', 'old_stage_id', 'new_stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_move_all_tasks' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register archive all tasks ability
	 */
	private function register_archive_all_tasks(): void {
		wp_register_ability(
			'fluentboards/archive-all-tasks',
			[
				'label'               => 'Archive all tasks in stage',
				'description'         => 'Archive all tasks in a stage',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID to archive all tasks from',
						],
					],
					'required'   => [ 'board_id', 'stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_archive_all_tasks' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register get archived stages ability
	 */
	private function register_get_archived_stages(): void {
		wp_register_ability(
			'fluentboards/get-archived-stages',
			[
				'label'               => 'Get archived FluentBoards stages',
				'description'         => 'Get all archived stages in a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id'     => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'page'         => [
							'type'        => 'integer',
							'description' => 'Page number (ignored if noPagination is true)',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'     => [
							'type'        => 'integer',
							'description' => 'Number of stages per page (ignored if noPagination is true)',
							'default'     => 30,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'noPagination' => [
							'type'        => 'boolean',
							'description' => 'Return all archived stages without pagination',
							'default'     => false,
						],
					],
					'required'   => [ 'board_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_archived_stages' ],
				'permission_callback' => [ $this, 'can_view_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register sort stage tasks ability
	 */
	private function register_sort_stage_tasks(): void {
		wp_register_ability(
			'fluentboards/sort-stage-tasks',
			[
				'label'               => 'Sort FluentBoards stage tasks',
				'description'         => 'Reorder tasks within a stage',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID',
						],
						'order'    => [
							'type'        => 'string',
							'description' => 'Sort field: priority, due_at, position, created_at, title',
							'enum'        => [ 'priority', 'due_at', 'position', 'created_at', 'title' ],
						],
						'orderBy'  => [
							'type'        => 'string',
							'description' => 'Sort direction: ASC (ascending) or DESC (descending)',
							'enum'        => [ 'ASC', 'DESC' ],
						],
					],
					'required'   => [ 'board_id', 'stage_id', 'order', 'orderBy' ],
				],
				'execute_callback'    => [ $this, 'execute_sort_stage_tasks' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Register get stage positions ability
	 */
	private function register_get_stage_positions(): void {
		wp_register_ability(
			'fluentboards/get-stage-positions',
			[
				'label'               => 'Get FluentBoards stage positions',
				'description'         => 'Get available positions for tasks in a stage',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'stage_id' => [
							'type'        => 'integer',
							'description' => 'Stage ID',
						],
					],
					'required'   => [ 'board_id', 'stage_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_stage_positions' ],
				'permission_callback' => [ $this, 'can_view_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'stages',
				],
			]
		);
	}

	/**
	 * Execute list stages ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_stages( array $args ): array {
		try {
			$board_id         = intval( $args['board_id'] );
			$include_archived = $args['include_archived'] ?? false;

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			$stage_model = new \FluentBoards\App\Models\Stage();
			$query       = $stage_model->where( 'board_id', $board_id );

			// Include archived stages if requested
			if ( ! $include_archived ) {
				$query = $query->whereNull( 'archived_at' );
			}

			$stages = $query->orderBy( 'position', 'ASC' )
							->with(
								[
									'tasks' => function ( $query ) {
										$query->whereNull( 'archived_at' );
									},
								]
							)
							->get();

			$stages_data = [];
			foreach ( $stages as $stage ) {
				$stages_data[] = [
					'id'          => $stage->id,
					'title'       => $stage->title,
					'description' => $stage->description,
					'position'    => $stage->position,
					'bg_color'    => $stage->bg_color,
					'board_id'    => $stage->board_id,
					'tasks_count' => $stage->tasks->count(),
					'settings'    => $stage->settings,
					'created_at'  => $stage->created_at,
					'updated_at'  => $stage->updated_at,
					'archived_at' => $stage->archived_at,
					'is_archived' => ! empty( $stage->archived_at ),
				];
			}

			return $this->get_success_response(
				[
					'stages'           => $stages_data,
					'total_stages'     => count( $stages_data ),
					'board_id'         => $board_id,
					'include_archived' => $include_archived,
				],
				'Stages retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list stages: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute create stage ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_stage( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$title    = sanitize_text_field( $args['title'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( empty( $title ) ) {
				return $this->get_error_response( 'Stage title is required', 'title_required' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Get the next position if not provided
			$position = $args['position'] ?? null;
			if ( null === $position ) {
				$stage_model = new \FluentBoards\App\Models\Stage();
				$last_stage  = $stage_model->where( 'board_id', $board_id )
										->whereNull( 'archived_at' )
										->orderBy( 'position', 'DESC' )
										->first();
				$position    = $last_stage ? $last_stage->position + 1 : 1;
			}

			$stage_data = [
				'title'      => $title,
				'board_id'   => $board_id,
				'position'   => intval( $position ),
				'settings'   => $args['settings'] ?? [],
				'created_by' => get_current_user_id(),
			];

			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->create( $stage_data );

			return $this->get_success_response(
				[
					'stage' => [
						'id'          => $stage->id,
						'title'       => $stage->title,
						'description' => $stage->description,
						'position'    => $stage->position,
						'bg_color'    => $stage->bg_color,
						'board_id'    => $stage->board_id,
						'settings'    => $stage->settings,
						'created_at'  => $stage->created_at,
						'updated_at'  => $stage->updated_at,
					],
				],
				'Stage created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create stage: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute update stage ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_stage( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['bg_color'] ) ) {
				$update_data['bg_color'] = sanitize_text_field( $args['bg_color'] );
			}

			if ( isset( $args['settings'] ) ) {
				$update_data['settings'] = $args['settings'];
			}

			// Update the stage
			if ( ! empty( $update_data ) ) {
				$stage->update( $update_data );
				$stage = $stage_model->find( $stage_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'stage' => [
						'id'          => $stage->id,
						'title'       => $stage->title,
						'description' => $stage->description,
						'position'    => $stage->position,
						'bg_color'    => $stage->bg_color,
						'board_id'    => $stage->board_id,
						'settings'    => $stage->settings,
						'updated_at'  => $stage->updated_at,
					],
				],
				'Stage updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update stage: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete stage ability (archive)
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_stage( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Check if already archived
			if ( $stage->archived_at ) {
				return $this->get_success_response(
					[
						'stage_id'    => $stage_id,
						'stage_title' => $stage->title,
						'is_archived' => true,
						'archived_at' => $stage->archived_at,
						'action'      => 'already_archived',
					],
					'Stage is already archived'
				);
			}

			// Archive the stage
			$stage->update(
				[
					'archived_at' => current_time( 'mysql' ),
				]
			);

			return $this->get_success_response(
				[
					'stage_id'    => $stage_id,
					'stage_title' => $stage->title,
					'is_archived' => true,
					'archived_at' => $stage->archived_at,
					'action'      => 'archived',
				],
				'Stage archived successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to archive stage: ' . $e->getMessage(), 'archive_failed' );
		}
	}

	/**
	 * Execute restore stage ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_restore_stage( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board (including archived)
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Check if stage is actually archived
			if ( ! $stage->archived_at ) {
				return $this->get_success_response(
					[
						'stage_id'    => $stage_id,
						'stage_title' => $stage->title,
						'is_archived' => false,
						'action'      => 'not_archived',
					],
					'Stage is not archived'
				);
			}

			// Restore the stage
			$stage->update(
				[
					'archived_at' => null,
				]
			);

			return $this->get_success_response(
				[
					'stage_id'    => $stage_id,
					'stage_title' => $stage->title,
					'is_archived' => false,
					'restored_at' => current_time( 'mysql' ),
					'action'      => 'restored',
				],
				'Stage restored successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to restore stage: ' . $e->getMessage(), 'restore_failed' );
		}
	}

	/**
	 * Execute reorder stages ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_reorder_stages( array $args ): array {
		try {
			$board_id  = intval( $args['board_id'] );
			$stage_ids = $args['stage_ids'] ?? [];

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( empty( $stage_ids ) || ! is_array( $stage_ids ) ) {
				return $this->get_error_response( 'Stage IDs array is required', 'stage_ids_required' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			$stage_model = new \FluentBoards\App\Models\Stage();

			// Verify all stage IDs belong to the board
			$existing_stages = $stage_model->where( 'board_id', $board_id )
										->whereIn( 'id', $stage_ids )
										->whereNull( 'archived_at' )
										->pluck( 'id' )
										->toArray();

			$missing_stages = array_diff( $stage_ids, $existing_stages );
			if ( ! empty( $missing_stages ) ) {
				return $this->get_error_response(
					'Invalid stage IDs: ' . implode( ', ', $missing_stages ),
					'invalid_stage_ids'
				);
			}

			// Update positions
			$updated_stages = [];
			foreach ( $stage_ids as $position => $stage_id ) {
				$stage = $stage_model->find( $stage_id );
				if ( $stage ) {
					$stage->update( [ 'position' => $position + 1 ] );
					$updated_stages[] = [
						'id'       => $stage->id,
						'title'    => $stage->title,
						'position' => $position + 1,
					];
				}
			}

			return $this->get_success_response(
				[
					'board_id'         => $board_id,
					'reordered_stages' => $updated_stages,
					'total_reordered'  => count( $updated_stages ),
				],
				'Stages reordered successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to reorder stages: ' . $e->getMessage(), 'reorder_failed' );
		}
	}

	/**
	 * Execute move all tasks ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_move_all_tasks( array $args ): array {
		try {
			$board_id     = intval( $args['board_id'] );
			$old_stage_id = intval( $args['old_stage_id'] );
			$new_stage_id = intval( $args['new_stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $old_stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid old stage ID', 'invalid_old_stage_id' );
			}

			if ( $new_stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid new stage ID', 'invalid_new_stage_id' );
			}

			if ( $old_stage_id === $new_stage_id ) {
				return $this->get_error_response( 'Source and destination stages cannot be the same', 'same_stage_error' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Verify both stages exist and belong to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stages      = $stage_model->where( 'board_id', $board_id )
								->whereIn( 'id', [ $old_stage_id, $new_stage_id ] )
								->whereNull( 'archived_at' )
								->get()
								->keyBy( 'id' );

			if ( ! isset( $stages[ $old_stage_id ] ) ) {
				return $this->get_error_response( 'Source stage not found', 'old_stage_not_found' );
			}

			if ( ! isset( $stages[ $new_stage_id ] ) ) {
				return $this->get_error_response( 'Destination stage not found', 'new_stage_not_found' );
			}

			// Move all tasks from old stage to new stage
			$task_model = new \FluentBoards\App\Models\Task();
			$tasks      = $task_model->where( 'board_id', $board_id )
							->where( 'stage_id', $old_stage_id )
							->whereNull( 'archived_at' )
							->get();

			$moved_tasks = [];
			foreach ( $tasks as $task ) {
				$task->update( [ 'stage_id' => $new_stage_id ] );
				$moved_tasks[] = [
					'id'           => $task->id,
					'title'        => $task->title,
					'old_stage_id' => $old_stage_id,
					'new_stage_id' => $new_stage_id,
				];
			}

			return $this->get_success_response(
				[
					'board_id'    => $board_id,
					'old_stage'   => [
						'id'    => $stages[ $old_stage_id ]->id,
						'title' => $stages[ $old_stage_id ]->title,
					],
					'new_stage'   => [
						'id'    => $stages[ $new_stage_id ]->id,
						'title' => $stages[ $new_stage_id ]->title,
					],
					'moved_tasks' => $moved_tasks,
					'total_moved' => count( $moved_tasks ),
				],
				'All tasks moved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to move all tasks: ' . $e->getMessage(), 'move_tasks_failed' );
		}
	}

	/**
	 * Execute archive all tasks ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_archive_all_tasks( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->whereNull( 'archived_at' )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Archive all tasks in the stage
			$task_model = new \FluentBoards\App\Models\Task();
			$tasks      = $task_model->where( 'board_id', $board_id )
							->where( 'stage_id', $stage_id )
							->whereNull( 'archived_at' )
							->get();

			$archived_tasks = [];
			$archived_at    = current_time( 'mysql' );

			foreach ( $tasks as $task ) {
				$task->update( [ 'archived_at' => $archived_at ] );
				$archived_tasks[] = [
					'id'          => $task->id,
					'title'       => $task->title,
					'archived_at' => $archived_at,
				];
			}

			return $this->get_success_response(
				[
					'board_id'       => $board_id,
					'stage'          => [
						'id'    => $stage->id,
						'title' => $stage->title,
					],
					'archived_tasks' => $archived_tasks,
					'total_archived' => count( $archived_tasks ),
					'archived_at'    => $archived_at,
				],
				'All tasks in stage archived successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to archive all tasks: ' . $e->getMessage(), 'archive_tasks_failed' );
		}
	}

	/**
	 * Execute get archived stages ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_archived_stages( array $args ): array {
		try {
			$board_id      = intval( $args['board_id'] );
			$page          = $args['page'] ?? 1;
			$per_page      = $args['per_page'] ?? 30;
			$no_pagination = $args['noPagination'] ?? false;

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			$stage_model = new \FluentBoards\App\Models\Stage();
			$query       = $stage_model->where( 'board_id', $board_id )
								->whereNotNull( 'archived_at' )
								->orderBy( 'archived_at', 'DESC' );

			if ( ! $no_pagination ) {
				$offset = ( $page - 1 ) * $per_page;
				$stages = $query->offset( $offset )->limit( $per_page )->get();
			} else {
				$stages = $query->get();
			}

			$stages_data = [];
			foreach ( $stages as $stage ) {
				$stages_data[] = [
					'id'          => $stage->id,
					'title'       => $stage->title,
					'description' => $stage->description,
					'position'    => $stage->position,
					'bg_color'    => $stage->bg_color,
					'board_id'    => $stage->board_id,
					'settings'    => $stage->settings,
					'created_at'  => $stage->created_at,
					'updated_at'  => $stage->updated_at,
					'archived_at' => $stage->archived_at,
				];
			}

			$response_data = [
				'archived_stages' => $stages_data,
				'total_archived'  => count( $stages_data ),
				'board_id'        => $board_id,
			];

			if ( ! $no_pagination ) {
				$response_data['page']     = $page;
				$response_data['per_page'] = $per_page;
			}

			return $this->get_success_response( $response_data, 'Archived stages retrieved successfully' );
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get archived stages: ' . $e->getMessage(), 'get_archived_failed' );
		}
	}

	/**
	 * Execute sort stage tasks ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_sort_stage_tasks( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );
			$order    = $args['order'];
			$order_by = $args['orderBy'];

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->whereNull( 'archived_at' )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Get and sort tasks
			$task_model = new \FluentBoards\App\Models\Task();
			$query      = $task_model->where( 'board_id', $board_id )
							->where( 'stage_id', $stage_id )
							->whereNull( 'archived_at' );

			// Apply sorting
			$query = $query->orderBy( $order, $order_by );

			$tasks = $query->get();

			// Update positions based on sorted order
			$updated_tasks = [];
			foreach ( $tasks as $index => $task ) {
				$task->update( [ 'position' => $index + 1 ] );
				$updated_tasks[] = [
					'id'         => $task->id,
					'title'      => $task->title,
					'position'   => $index + 1,
					'priority'   => $task->priority,
					'due_at'     => $task->due_at,
					'created_at' => $task->created_at,
				];
			}

			return $this->get_success_response(
				[
					'board_id'      => $board_id,
					'stage'         => [
						'id'    => $stage->id,
						'title' => $stage->title,
					],
					'sorted_tasks'  => $updated_tasks,
					'total_sorted'  => count( $updated_tasks ),
					'sort_criteria' => [
						'order'   => $order,
						'orderBy' => $order_by,
					],
				],
				'Stage tasks sorted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to sort stage tasks: ' . $e->getMessage(), 'sort_failed' );
		}
	}

	/**
	 * Execute get stage positions ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_stage_positions( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$stage_id = intval( $args['stage_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $stage_id <= 0 ) {
				return $this->get_error_response( 'Invalid stage ID', 'invalid_stage_id' );
			}

			// Check if board exists and user has access
			if ( ! $this->validate_board_access( $board_id ) ) {
				return $this->get_error_response( 'Board not found or access denied', 'board_access_denied' );
			}

			// Check if stage exists and belongs to the board
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->whereNull( 'archived_at' )
								->first();

			if ( ! $stage ) {
				return $this->get_error_response( 'Stage not found', 'stage_not_found' );
			}

			// Get current task positions in the stage
			$task_model = new \FluentBoards\App\Models\Task();
			$tasks      = $task_model->where( 'board_id', $board_id )
							->where( 'stage_id', $stage_id )
							->whereNull( 'archived_at' )
							->orderBy( 'position', 'ASC' )
							->get( [ 'id', 'title', 'position' ] );

			$positions      = [];
			$used_positions = [];

			foreach ( $tasks as $task ) {
				$positions[]      = [
					'task_id'    => $task->id,
					'task_title' => $task->title,
					'position'   => $task->position,
				];
				$used_positions[] = $task->position;
			}

			// Calculate available positions
			$max_position        = count( $tasks );
			$available_positions = [];
			for ( $i = 1; $i <= $max_position + 1; $i++ ) {
				$available_positions[] = $i;
			}

			return $this->get_success_response(
				[
					'board_id'            => $board_id,
					'stage'               => [
						'id'    => $stage->id,
						'title' => $stage->title,
					],
					'current_positions'   => $positions,
					'used_positions'      => $used_positions,
					'available_positions' => $available_positions,
					'max_position'        => $max_position,
					'next_position'       => $max_position + 1,
					'total_tasks'         => count( $tasks ),
				],
				'Stage positions retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get stage positions: ' . $e->getMessage(), 'get_positions_failed' );
		}
	}

	/**
	 * Validate board access for the current user
	 *
	 * @param int $board_id Board ID to validate
	 * @return bool True if user has access
	 */
	private function validate_board_access( int $board_id ): bool {
		if ( ! $this->board_exists( $board_id ) ) {
			return false;
		}

		// Get the board to check access
		try {
			$board_model = new \FluentBoards\App\Models\Board();
			$board       = $board_model->find( $board_id );

			if ( ! $board ) {
				return false;
			}

			$user_id = get_current_user_id();

			// Admin can access all boards
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}

			// Check if user is board owner
			if ( $board->created_by === $user_id ) {
				return true;
			}

			// Check if user is assigned to the board
			$user_board = $board->users()->where( 'fbs_relations.foreign_id', $user_id )->first();
			return ! empty( $user_board );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Validate stage exists and belongs to board
	 *
	 * @param int $stage_id Stage ID to validate
	 * @param int $board_id Board ID the stage should belong to
	 * @return bool True if stage exists and belongs to board
	 */
	private function validate_stage_exists( int $stage_id, int $board_id ): bool {
		if ( ! class_exists( '\FluentBoards\App\Models\Stage' ) ) {
			return false;
		}

		try {
			$stage_model = new \FluentBoards\App\Models\Stage();
			$stage       = $stage_model->where( 'id', $stage_id )
								->where( 'board_id', $board_id )
								->first();
			return ! empty( $stage );
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
