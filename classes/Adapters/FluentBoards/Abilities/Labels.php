<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Label Abilities
 *
 * Registers WordPress abilities for FluentBoards label management operations
 * using the new WordPress Abilities API pattern.
 */
class Labels extends BaseAbility {

	/**
	 * Register all label-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_list_labels();
		$this->register_create_label();
		$this->register_update_label();
		$this->register_delete_label();
		$this->register_add_label_to_task();
		$this->register_remove_label_from_task();
		$this->register_get_task_labels();
	}

	/**
	 * Register list labels ability
	 */
	private function register_list_labels(): void {

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$result = wp_register_ability(
			'fluentboards/list-labels',
			[
				'label'               => 'List FluentBoards labels',
				'description'         => 'List all labels in a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id'  => [
							'type'        => 'integer',
							'description' => 'Board ID to list labels from',
						],
						'used_only' => [
							'type'        => 'boolean',
							'description' => 'Only return labels that are used in tasks',
							'default'     => false,
						],
					],
					'required'   => [ 'board_id' ],
				],
				'execute_callback'    => [ $this, 'execute_list_labels' ],
				'permission_callback' => [ $this, 'can_view_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register create label ability
	 */
	private function register_create_label(): void {
		wp_register_ability(
			'fluentboards/create-label',
			[
				'label'               => 'Create FluentBoards label',
				'description'         => 'Create a new label on a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'title'    => [
							'type'        => 'string',
							'description' => 'Label title/text',
						],
						'bg_color' => [
							'type'        => 'string',
							'description' => 'Background color (hex) - e.g., #4bce97',
							'pattern'     => '^#[0-9a-fA-F]{6}$',
						],
						'color'    => [
							'type'        => 'string',
							'description' => 'Text color (hex)',
							'pattern'     => '^#[0-9a-fA-F]{6}$',
						],
					],
					'required'   => [ 'board_id', 'title', 'bg_color', 'color' ],
				],
				'execute_callback'    => [ $this, 'execute_create_label' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register update label ability
	 */
	private function register_update_label(): void {
		wp_register_ability(
			'fluentboards/update-label',
			[
				'label'               => 'Update FluentBoards label',
				'description'         => 'Update an existing label',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'label_id' => [
							'type'        => 'integer',
							'description' => 'Label ID to update',
						],
						'title'    => [
							'type'        => 'string',
							'description' => 'Updated label title/text',
						],
						'bg_color' => [
							'type'        => 'string',
							'description' => 'Updated background color (hex)',
							'pattern'     => '^#[0-9a-fA-F]{6}$',
						],
						'color'    => [
							'type'        => 'string',
							'description' => 'Updated text color (hex)',
							'pattern'     => '^#[0-9a-fA-F]{6}$',
						],
					],
					'required'   => [ 'board_id', 'label_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_label' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register delete label ability
	 */
	private function register_delete_label(): void {
		wp_register_ability(
			'fluentboards/delete-label',
			[
				'label'               => 'Delete FluentBoards label',
				'description'         => 'Delete a label from a board',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'label_id' => [
							'type'        => 'integer',
							'description' => 'Label ID to delete',
						],
					],
					'required'   => [ 'board_id', 'label_id' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_label' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register add label to task ability
	 */
	private function register_add_label_to_task(): void {
		wp_register_ability(
			'fluentboards/add-label-to-task',
			[
				'label'               => 'Add FluentBoards label to task',
				'description'         => 'Add a label to a task',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'task_id'  => [
							'type'        => 'integer',
							'description' => 'Task ID',
						],
						'label_id' => [
							'type'        => 'integer',
							'description' => 'Label ID',
						],
					],
					'required'   => [ 'board_id', 'task_id', 'label_id' ],
				],
				'execute_callback'    => [ $this, 'execute_add_label_to_task' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register remove label from task ability
	 */
	private function register_remove_label_from_task(): void {
		wp_register_ability(
			'fluentboards/remove-label-from-task',
			[
				'label'               => 'Remove FluentBoards label from task',
				'description'         => 'Remove a label from a task',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'task_id'  => [
							'type'        => 'integer',
							'description' => 'Task ID',
						],
						'label_id' => [
							'type'        => 'integer',
							'description' => 'Label ID',
						],
					],
					'required'   => [ 'board_id', 'task_id', 'label_id' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_label_from_task' ],
				'permission_callback' => [ $this, 'can_manage_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Register get task labels ability
	 */
	private function register_get_task_labels(): void {
		wp_register_ability(
			'fluentboards/get-task-labels',
			[
				'label'               => 'Get FluentBoards task labels',
				'description'         => 'Get all labels assigned to a specific task',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'board_id' => [
							'type'        => 'integer',
							'description' => 'Board ID',
						],
						'task_id'  => [
							'type'        => 'integer',
							'description' => 'Task ID',
						],
					],
					'required'   => [ 'board_id', 'task_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_task_labels' ],
				'permission_callback' => [ $this, 'can_view_boards' ],
				'meta'                => [
					'category'    => 'fluentboards',
					'subcategory' => 'labels',
				],
			]
		);
	}

	/**
	 * Execute list labels ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_labels( array $args ): array {
		try {
			$board_id  = intval( $args['board_id'] );
			$used_only = $args['used_only'] ?? false;

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			// Check board access
			$board = $this->get_board_with_access_check( $board_id );
			if ( is_array( $board ) && isset( $board['error'] ) ) {
				return $board;
			}

			$label_model = new \FluentBoards\App\Models\Label();
			$query       = $label_model->where( 'board_id', $board_id );

			if ( $used_only ) {
				// Only get labels that are actually used in tasks
				$query = $query->whereHas( 'tasks' );
			}

			$labels = $query->orderBy( 'title', 'ASC' )->get();

			$result = [];
			foreach ( $labels as $label ) {
				$label_data = [
					'id'         => $label->id,
					'title'      => $label->title,
					'bg_color'   => $label->bg_color,
					'color'      => $label->color,
					'created_at' => $label->created_at,
				];

				// Include usage count if available
				if ( method_exists( $label, 'tasks' ) ) {
					$label_data['usage_count'] = $label->tasks()->count();
				}

				$result[] = $label_data;
			}

			return $this->get_success_response(
				[
					'board_id'         => $board_id,
					'labels'           => $result,
					'total_labels'     => count( $result ),
					'used_only_filter' => $used_only,
				],
				'Labels retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list labels: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute create label ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_label( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$title    = sanitize_text_field( $args['title'] );
			$bg_color = sanitize_hex_color( $args['bg_color'] );
			$color    = sanitize_hex_color( $args['color'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( empty( $title ) ) {
				return $this->get_error_response( 'Label title is required', 'title_required' );
			}

			if ( empty( $bg_color ) || empty( $color ) ) {
				return $this->get_error_response( 'Valid hex colors are required for background and text', 'invalid_colors' );
			}

			// Check board access
			$board = $this->get_board_with_access_check( $board_id );
			if ( is_array( $board ) && isset( $board['error'] ) ) {
				return $board;
			}

			// Check if label with same title already exists
			$label_model = new \FluentBoards\App\Models\Label();
			$existing    = $label_model->where( 'board_id', $board_id )
									->where( 'title', $title )
									->first();

			if ( $existing ) {
				return $this->get_error_response( 'Label with this title already exists', 'label_exists' );
			}

			$label = $label_model->create(
				[
					'board_id'   => $board_id,
					'title'      => $title,
					'bg_color'   => $bg_color,
					'color'      => $color,
					'created_by' => get_current_user_id(),
				]
			);

			return $this->get_success_response(
				[
					'label' => [
						'id'         => $label->id,
						'title'      => $label->title,
						'bg_color'   => $label->bg_color,
						'color'      => $label->color,
						'board_id'   => $label->board_id,
						'created_at' => $label->created_at,
					],
				],
				'Label created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create label: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute update label ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_label( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$label_id = intval( $args['label_id'] );

			if ( $board_id <= 0 || $label_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or label ID', 'invalid_ids' );
			}

			// Check board access
			$board = $this->get_board_with_access_check( $board_id );
			if ( is_array( $board ) && isset( $board['error'] ) ) {
				return $board;
			}

			// Get the label
			$label_model = new \FluentBoards\App\Models\Label();
			$label       = $label_model->where( 'id', $label_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $label ) {
				return $this->get_error_response( 'Label not found', 'label_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$title = sanitize_text_field( $args['title'] );
				if ( empty( $title ) ) {
					return $this->get_error_response( 'Label title cannot be empty', 'empty_title' );
				}

				// Check if another label with same title exists
				$existing = $label_model->where( 'board_id', $board_id )
										->where( 'title', $title )
										->where( 'id', '!=', $label_id )
										->first();

				if ( $existing ) {
					return $this->get_error_response( 'Another label with this title already exists', 'title_exists' );
				}

				$update_data['title'] = $title;
			}

			if ( isset( $args['bg_color'] ) ) {
				$bg_color = sanitize_hex_color( $args['bg_color'] );
				if ( empty( $bg_color ) ) {
					return $this->get_error_response( 'Invalid background color format', 'invalid_bg_color' );
				}
				$update_data['bg_color'] = $bg_color;
			}

			if ( isset( $args['color'] ) ) {
				$color = sanitize_hex_color( $args['color'] );
				if ( empty( $color ) ) {
					return $this->get_error_response( 'Invalid text color format', 'invalid_color' );
				}
				$update_data['color'] = $color;
			}

			if ( empty( $update_data ) ) {
				return $this->get_error_response( 'No fields to update', 'no_update_data' );
			}

			$label->update( $update_data );
			$label = $label_model->find( $label_id ); // Refresh

			return $this->get_success_response(
				[
					'label' => [
						'id'         => $label->id,
						'title'      => $label->title,
						'bg_color'   => $label->bg_color,
						'color'      => $label->color,
						'board_id'   => $label->board_id,
						'updated_at' => $label->updated_at,
					],
				],
				'Label updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update label: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete label ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_label( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$label_id = intval( $args['label_id'] );

			if ( $board_id <= 0 || $label_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or label ID', 'invalid_ids' );
			}

			// Check board access
			$board = $this->get_board_with_access_check( $board_id );
			if ( is_array( $board ) && isset( $board['error'] ) ) {
				return $board;
			}

			// Get the label
			$label_model = new \FluentBoards\App\Models\Label();
			$label       = $label_model->where( 'id', $label_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $label ) {
				return $this->get_error_response( 'Label not found', 'label_not_found' );
			}

			$label_title = $label->title;

			// Remove label from all tasks first
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relation_model->where( 'object_type', 'task' )
						->where( 'foreign_type', 'label' )
						->where( 'foreign_id', $label_id )
						->delete();

			// Delete the label
			$label->delete();

			return $this->get_success_response(
				[
					'label_id'    => $label_id,
					'label_title' => $label_title,
					'board_id'    => $board_id,
					'deleted_at'  => current_time( 'mysql' ),
				],
				'Label deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete label: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute add label to task ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_label_to_task( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$task_id  = intval( $args['task_id'] );
			$label_id = intval( $args['label_id'] );

			if ( $board_id <= 0 || $task_id <= 0 || $label_id <= 0 ) {
				return $this->get_error_response( 'Invalid board, task, or label ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Verify label belongs to board
			$label_model = new \FluentBoards\App\Models\Label();
			$label       = $label_model->where( 'id', $label_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $label ) {
				return $this->get_error_response( 'Label not found in this board', 'label_not_found' );
			}

			// Check if label is already assigned to task
			$relation_model = new \FluentBoards\App\Models\Relation();
			$existing       = $relation_model->where( 'object_type', 'task' )
									->where( 'object_id', $task_id )
									->where( 'foreign_type', 'label' )
									->where( 'foreign_id', $label_id )
									->first();

			if ( $existing ) {
				return $this->get_success_response(
					[
						'task_id'     => $task_id,
						'label_id'    => $label_id,
						'label_title' => $label->title,
						'action'      => 'already_assigned',
					],
					'Label is already assigned to this task'
				);
			}

			// Add label to task
			$relation = $relation_model->create(
				[
					'object_type'  => 'task',
					'object_id'    => $task_id,
					'foreign_type' => 'label',
					'foreign_id'   => $label_id,
					'created_by'   => get_current_user_id(),
				]
			);

			return $this->get_success_response(
				[
					'task_id'     => $task_id,
					'label'       => [
						'id'       => $label->id,
						'title'    => $label->title,
						'bg_color' => $label->bg_color,
						'color'    => $label->color,
					],
					'relation_id' => $relation->id,
					'action'      => 'assigned',
				],
				'Label added to task successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add label to task: ' . $e->getMessage(), 'add_failed' );
		}
	}

	/**
	 * Execute remove label from task ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_label_from_task( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$task_id  = intval( $args['task_id'] );
			$label_id = intval( $args['label_id'] );

			if ( $board_id <= 0 || $task_id <= 0 || $label_id <= 0 ) {
				return $this->get_error_response( 'Invalid board, task, or label ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Get the label for response
			$label_model = new \FluentBoards\App\Models\Label();
			$label       = $label_model->where( 'id', $label_id )
								->where( 'board_id', $board_id )
								->first();

			if ( ! $label ) {
				return $this->get_error_response( 'Label not found in this board', 'label_not_found' );
			}

			// Find and remove the relation
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relation       = $relation_model->where( 'object_type', 'task' )
									->where( 'object_id', $task_id )
									->where( 'foreign_type', 'label' )
									->where( 'foreign_id', $label_id )
									->first();

			if ( ! $relation ) {
				return $this->get_success_response(
					[
						'task_id'     => $task_id,
						'label_id'    => $label_id,
						'label_title' => $label->title,
						'action'      => 'not_assigned',
					],
					'Label is not assigned to this task'
				);
			}

			$relation->delete();

			return $this->get_success_response(
				[
					'task_id'    => $task_id,
					'label'      => [
						'id'       => $label->id,
						'title'    => $label->title,
						'bg_color' => $label->bg_color,
						'color'    => $label->color,
					],
					'action'     => 'removed',
					'removed_at' => current_time( 'mysql' ),
				],
				'Label removed from task successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove label from task: ' . $e->getMessage(), 'remove_failed' );
		}
	}

	/**
	 * Execute get task labels ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_task_labels( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$task_id  = intval( $args['task_id'] );

			if ( $board_id <= 0 || $task_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or task ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Get task labels through relations
			$relation_model  = new \FluentBoards\App\Models\Relation();
			$label_relations = $relation_model->where( 'object_type', 'task' )
											->where( 'object_id', $task_id )
											->where( 'foreign_type', 'label' )
											->get();

			$result      = [];
			$label_model = new \FluentBoards\App\Models\Label();

			foreach ( $label_relations as $relation ) {
				$label = $label_model->find( $relation->foreign_id );
				if ( $label && $label->board_id === $board_id ) {
					$result[] = [
						'id'          => $label->id,
						'title'       => $label->title,
						'bg_color'    => $label->bg_color,
						'color'       => $label->color,
						'assigned_at' => $relation->created_at,
						'assigned_by' => $relation->created_by,
					];
				}
			}

			return $this->get_success_response(
				[
					'task_id'      => $task_id,
					'board_id'     => $board_id,
					'labels'       => $result,
					'total_labels' => count( $result ),
				],
				'Task labels retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get task labels: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Get board with access check
	 *
	 * @param int $board_id Board ID
	 * @return object|array Board object or error array
	 */
	private function get_board_with_access_check( int $board_id ) {
		$board_model = new \FluentBoards\App\Models\Board();
		$board       = $board_model->find( $board_id );

		if ( ! $board ) {
			return $this->get_error_response( 'Board not found', 'board_not_found' );
		}

		$user_id = get_current_user_id();
		if ( ! $this->can_access_board( $board, $user_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		return $board;
	}

	/**
	 * Get task with access check
	 *
	 * @param int $board_id Board ID
	 * @param int $task_id Task ID
	 * @return object|array Task object or error array
	 */
	private function get_task_with_access_check( int $board_id, int $task_id ) {
		$task_model = new \FluentBoards\App\Models\Task();
		$task       = $task_model->where( 'id', $task_id )
						->where( 'board_id', $board_id )
						->first();

		if ( ! $task ) {
			return $this->get_error_response( 'Task not found', 'task_not_found' );
		}

		// Check board access
		$board = $this->get_board_with_access_check( $board_id );
		if ( is_array( $board ) && isset( $board['error'] ) ) {
			return $board;
		}

		return $task;
	}

	/**
	 * Check if user can access specific board
	 */
	private function can_access_board( $board, $user_id ): bool {
		if ( ! $board || ! $user_id ) {
			return false;
		}

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
	}
}
