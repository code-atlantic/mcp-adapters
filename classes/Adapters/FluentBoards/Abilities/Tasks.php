<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Task Abilities
 *
 * Registers WordPress abilities for FluentBoards task management operations
 * using the new WordPress Abilities API pattern.
 */
class Tasks extends BaseAbility {

	/**
	 * Register all task-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_list_tasks();
		$this->register_get_task();
		$this->register_create_task();
		$this->register_update_task();
		$this->register_delete_task();
		$this->register_move_task();
		$this->register_assign_yourself_to_task();
		$this->register_detach_yourself_from_task();
		$this->register_clone_task();
		$this->register_archive_task();
		$this->register_restore_task();
		$this->register_change_task_status();
		$this->register_update_task_dates();
	}

	/**
	 * Register list tasks ability
	 */
	private function register_list_tasks(): void {
		wp_register_ability('fluentboards_list_tasks', [
			'label'               => 'List FluentBoards tasks',
			'description'         => 'List tasks in a board',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'stage_id' => [
						'type'        => 'integer',
						'description' => 'Filter tasks by stage ID',
					],
					'search'   => [
						'type'        => 'string',
						'description' => 'Search tasks by title or description',
					],
				],
				'required'   => [ 'board_id' ],
			],
			'execute_callback'    => [ $this, 'execute_list_tasks' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register get task ability
	 */
	private function register_get_task(): void {
		wp_register_ability('fluentboards_get_task', [
			'label'               => 'Get FluentBoards task',
			'description'         => 'Get details of a specific task including comments and attachments',
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
			'execute_callback'    => [ $this, 'execute_get_task' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register create task ability
	 */
	private function register_create_task(): void {
		wp_register_ability('fluentboards_create_task', [
			'label'               => 'Create FluentBoards task',
			'description'         => 'Create a new task with comprehensive parameters',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'       => [
						'type'        => 'integer',
						'description' => 'Board ID where the task will be created',
					],
					'title'          => [
						'type'        => 'string',
						'description' => 'Task title (required)',
					],
					'stage_id'       => [
						'type'        => 'integer',
						'description' => 'Stage ID where the task should be placed',
					],
					'description'    => [
						'type'        => 'string',
						'description' => 'Task description (supports HTML and markdown)',
					],
					'priority'       => [
						'type'        => 'string',
						'enum'        => [ 'low', 'normal', 'medium', 'high', 'urgent' ],
						'description' => 'Task priority level',
					],
					'due_at'         => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Due date and time (Y-m-d H:i:s format)',
					],
					'started_at'     => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Start date and time (Y-m-d H:i:s format)',
					],
					'remind_at'      => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Reminder date and time (Y-m-d H:i:s format)',
					],
					'reminder_type'  => [
						'type'        => 'string',
						'enum'        => [ 'email', 'dashboard', 'both' ],
						'description' => 'Type of reminder notification',
					],
					'lead_value'     => [
						'type'        => 'number',
						'minimum'     => 0,
						'maximum'     => 9999999.99,
						'description' => 'Lead/budget value for the task',
					],
					'crm_contact_id' => [
						'type'        => 'integer',
						'description' => 'FluentCRM contact ID to associate with task',
					],
					'type'           => [
						'type'        => 'string',
						'enum'        => [ 'task', 'milestone', 'idea' ],
						'description' => 'Type of task item',
					],
					'status'         => [
						'type'        => 'string',
						'enum'        => [ 'open', 'in_progress', 'completed', 'closed' ],
						'description' => 'Task status',
					],
					'scope'          => [
						'type'        => 'string',
						'description' => 'Task scope or category',
					],
					'source'         => [
						'type'        => 'string',
						'description' => 'Source where task originated from',
					],
					'is_template'    => [
						'type'        => 'string',
						'enum'        => [ 'yes', 'no' ],
						'default'     => 'no',
						'description' => 'Whether this task is a template',
					],
					'assignees'      => [
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
						'description' => 'Array of user IDs to assign to the task',
					],
					'labels'         => [
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
						'description' => 'Array of label IDs to apply to the task',
					],
					'settings'       => [
						'type'        => 'object',
						'description' => 'Task-specific settings and configuration',
						'properties'  => [
							'cover' => [
								'type'        => 'object',
								'description' => 'Task cover image settings',
								'properties'  => [
									'imageId'         => [ 'type' => 'integer' ],
									'backgroundImage' => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
				'required'   => [ 'board_id', 'title', 'stage_id' ],
			],
			'execute_callback'    => [ $this, 'execute_create_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register update task ability
	 */
	private function register_update_task(): void {
		wp_register_ability('fluentboards_update_task', [
			'label'               => 'Update FluentBoards task',
			'description'         => 'Update an existing task with comprehensive properties',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'       => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'        => [
						'type'        => 'integer',
						'description' => 'Task ID to update',
					],
					'title'          => [
						'type'        => 'string',
						'description' => 'Updated task title',
					],
					'description'    => [
						'type'        => 'string',
						'description' => 'Updated task description (supports HTML and markdown)',
					],
					'stage_id'       => [
						'type'        => 'integer',
						'description' => 'New stage ID to move task to',
					],
					'priority'       => [
						'type'        => 'string',
						'enum'        => [ 'low', 'normal', 'medium', 'high', 'urgent' ],
						'description' => 'Updated task priority level',
					],
					'due_at'         => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Updated due date and time (Y-m-d H:i:s format)',
					],
					'started_at'     => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Updated start date and time (Y-m-d H:i:s format)',
					],
					'remind_at'      => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Updated reminder date and time (Y-m-d H:i:s format)',
					],
					'reminder_type'  => [
						'type'        => 'string',
						'enum'        => [ 'email', 'dashboard', 'both' ],
						'description' => 'Updated reminder notification type',
					],
					'lead_value'     => [
						'type'        => 'number',
						'minimum'     => 0,
						'maximum'     => 9999999.99,
						'description' => 'Updated lead/budget value for the task',
					],
					'crm_contact_id' => [
						'type'        => 'integer',
						'description' => 'Updated FluentCRM contact ID',
					],
					'status'         => [
						'type'        => 'string',
						'enum'        => [ 'open', 'in_progress', 'completed', 'closed' ],
						'description' => 'Updated task status',
					],
					'scope'          => [
						'type'        => 'string',
						'description' => 'Updated task scope or category',
					],
					'source'         => [
						'type'        => 'string',
						'description' => 'Updated source where task originated from',
					],
					'log_minutes'    => [
						'type'        => 'integer',
						'minimum'     => 0,
						'description' => 'Minutes to log for time tracking',
					],
					'assignees'      => [
						'type'        => 'array',
						'items'       => [
							'type' => 'integer',
						],
						'description' => 'Updated array of user IDs assigned to the task',
					],
					'settings'       => [
						'type'        => 'object',
						'description' => 'Updated task-specific settings and configuration',
						'properties'  => [
							'cover' => [
								'type'        => 'object',
								'description' => 'Task cover image settings',
								'properties'  => [
									'imageId'         => [ 'type' => 'integer' ],
									'backgroundImage' => [ 'type' => 'string' ],
								],
							],
						],
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_update_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register delete task ability
	 */
	private function register_delete_task(): void {
		wp_register_ability('fluentboards_delete_task', [
			'label'               => 'Delete FluentBoards task',
			'description'         => 'Delete a task permanently (requires confirmation)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'       => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'        => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'confirm_delete' => [
						'type'        => 'boolean',
						'description' => 'Confirmation required: set to true to proceed with deletion',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'confirm_delete' ],
			],
			'execute_callback'    => [ $this, 'execute_delete_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register move task ability
	 */
	private function register_move_task(): void {
		wp_register_ability('fluentboards_move_task', [
			'label'               => 'Move FluentBoards task',
			'description'         => 'Move task to different stage or board',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'   => [
						'type'        => 'integer',
						'description' => 'Current board ID',
					],
					'task_id'    => [
						'type'        => 'integer',
						'description' => 'Task ID to move',
					],
					'newStageId' => [
						'type'        => 'integer',
						'description' => 'New stage ID to move task to',
					],
					'newIndex'   => [
						'type'        => 'integer',
						'description' => 'New position index in stage (0-based)',
						'minimum'     => 0,
					],
					'newBoardId' => [
						'type'        => 'integer',
						'description' => 'New board ID (optional, for moving between boards)',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'newStageId', 'newIndex' ],
			],
			'execute_callback'    => [ $this, 'execute_move_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register assign yourself to task ability
	 */
	private function register_assign_yourself_to_task(): void {
		wp_register_ability('fluentboards_assign_yourself_to_task', [
			'label'               => 'Assign yourself to FluentBoards task',
			'description'         => 'Assign yourself to a task',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID to assign yourself to',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_assign_yourself_to_task' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register detach yourself from task ability
	 */
	private function register_detach_yourself_from_task(): void {
		wp_register_ability('fluentboards_detach_yourself_from_task', [
			'label'               => 'Detach yourself from FluentBoards task',
			'description'         => 'Remove yourself from a task',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID to remove yourself from',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_detach_yourself_from_task' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register clone task ability
	 */
	private function register_clone_task(): void {
		wp_register_ability('fluentboards_clone_task', [
			'label'               => 'Clone FluentBoards task',
			'description'         => 'Clone/duplicate a task with options',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'        => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'         => [
						'type'        => 'integer',
						'description' => 'Task ID to clone',
					],
					'title'           => [
						'type'        => 'string',
						'description' => 'Title for the cloned task',
					],
					'target_board_id' => [
						'type'        => 'integer',
						'description' => 'Target board ID for cloned task',
					],
					'stage_id'        => [
						'type'        => 'integer',
						'description' => 'Target stage ID for cloned task',
					],
					'assignee'        => [
						'type'        => 'boolean',
						'description' => 'Clone assignees',
						'default'     => true,
					],
					'subtask'         => [
						'type'        => 'boolean',
						'description' => 'Clone subtasks',
						'default'     => true,
					],
					'label'           => [
						'type'        => 'boolean',
						'description' => 'Clone labels',
						'default'     => true,
					],
					'attachment'      => [
						'type'        => 'boolean',
						'description' => 'Clone attachments',
						'default'     => false,
					],
					'comment'         => [
						'type'        => 'boolean',
						'description' => 'Clone comments',
						'default'     => false,
					],
				],
				'required'   => [ 'board_id', 'task_id', 'title', 'stage_id' ],
			],
			'execute_callback'    => [ $this, 'execute_clone_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register archive task ability
	 */
	private function register_archive_task(): void {
		wp_register_ability('fluentboards_archive_task', [
			'label'               => 'Archive FluentBoards task',
			'description'         => 'Archive a task (soft delete)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID to archive',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_archive_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register restore task ability
	 */
	private function register_restore_task(): void {
		wp_register_ability('fluentboards_restore_task', [
			'label'               => 'Restore FluentBoards task',
			'description'         => 'Restore an archived task',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID to restore',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_restore_task' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register change task status ability
	 */
	private function register_change_task_status(): void {
		wp_register_ability('fluentboards_change_task_status', [
			'label'               => 'Change FluentBoards task status',
			'description'         => 'Change the status/stage of a task',
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
					'stage_id' => [
						'type'        => 'integer',
						'description' => 'New stage ID',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'stage_id' ],
			],
			'execute_callback'    => [ $this, 'execute_change_task_status' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	/**
	 * Register update task dates ability
	 */
	private function register_update_task_dates(): void {
		wp_register_ability('fluentboards_update_task_dates', [
			'label'               => 'Update FluentBoards task dates',
			'description'         => 'Update task start and due dates',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'   => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'    => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'started_at' => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Task start date and time (Y-m-d H:i:s format)',
					],
					'due_at'     => [
						'type'        => 'string',
						'format'      => 'date-time',
						'description' => 'Task due date and time (Y-m-d H:i:s format)',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_update_task_dates' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'tasks',
			],
		]);
	}

	// ========================================
	// Execute Methods
	// ========================================

	/**
	 * Execute list tasks
	 */
	public function execute_list_tasks( array $args ): array {
		$board_id = (int) $args['board_id'];
		$stage_id = ! empty( $args['stage_id'] ) ? (int) $args['stage_id'] : null;
		$search   = ! empty( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		try {
			$query = \FluentBoards\App\Models\Task::where( 'board_id', $board_id );

			if ( $stage_id ) {
				$query->where( 'stage_id', $stage_id );
			}

			if ( $search ) {
				$query->where(function ( $q ) use ( $search ) {
					$q->where( 'title', 'like', '%' . $search . '%' )
						->orWhere( 'description', 'like', '%' . $search . '%' );
				});
			}

			$tasks = $query->with( [ 'assignees', 'stage', 'labels', 'comments', 'attachments' ] )
							->orderBy( 'position' )
							->get();

			return $this->get_success_response([
				'tasks' => $tasks->toArray(),
				'total' => $tasks->count(),
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve tasks: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute get task
	 */
	public function execute_get_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::with([
				'assignees',
				'stage',
				'board',
				'labels',
				'comments.user',
				'comments.replies.user',
				'attachments',
				'subtasks',
				'customFieldValues',
				'watchers',
			])->find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			return $this->get_success_response( [ 'task' => $task->toArray() ] );
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to retrieve task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute create task
	 */
	public function execute_create_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$stage_id = (int) $args['stage_id'];
		$title    = sanitize_text_field( $args['title'] );

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->board_exists( $board_id ) ) {
			return $this->get_error_response( 'Board not found', 'not_found' );
		}

		// Verify stage belongs to board
		$stage = \FluentBoards\App\Models\Stage::where( 'id', $stage_id )
												->where( 'board_id', $board_id )
												->first();
		if ( ! $stage ) {
			return $this->get_error_response( 'Invalid stage for this board', 'invalid_stage' );
		}

		try {
			$task_data = [
				'board_id'   => $board_id,
				'stage_id'   => $stage_id,
				'title'      => $title,
				'position'   => $this->get_next_task_position( $stage_id ),
				'created_by' => get_current_user_id(),
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			];

			// Add optional fields
			if ( ! empty( $args['description'] ) ) {
				$task_data['description'] = wp_kses_post( $args['description'] );
			}
			if ( ! empty( $args['priority'] ) ) {
				$task_data['priority'] = sanitize_text_field( $args['priority'] );
			}
			if ( ! empty( $args['due_at'] ) ) {
				$task_data['due_at'] = sanitize_text_field( $args['due_at'] );
			}
			if ( ! empty( $args['started_at'] ) ) {
				$task_data['started_at'] = sanitize_text_field( $args['started_at'] );
			}
			if ( ! empty( $args['remind_at'] ) ) {
				$task_data['remind_at'] = sanitize_text_field( $args['remind_at'] );
			}
			if ( ! empty( $args['reminder_type'] ) ) {
				$task_data['reminder_type'] = sanitize_text_field( $args['reminder_type'] );
			}
			if ( isset( $args['lead_value'] ) ) {
				$task_data['lead_value'] = (float) $args['lead_value'];
			}
			if ( ! empty( $args['crm_contact_id'] ) ) {
				$task_data['crm_contact_id'] = (int) $args['crm_contact_id'];
			}
			if ( ! empty( $args['type'] ) ) {
				$task_data['type'] = sanitize_text_field( $args['type'] );
			}
			if ( ! empty( $args['status'] ) ) {
				$task_data['status'] = sanitize_text_field( $args['status'] );
			}
			if ( ! empty( $args['scope'] ) ) {
				$task_data['scope'] = sanitize_text_field( $args['scope'] );
			}
			if ( ! empty( $args['source'] ) ) {
				$task_data['source'] = sanitize_text_field( $args['source'] );
			}
			if ( ! empty( $args['is_template'] ) ) {
				$task_data['is_template'] = 'yes' === $args['is_template'] ? 1 : 0;
			}
			if ( ! empty( $args['settings'] ) ) {
				$task_data['settings'] = wp_json_encode( $args['settings'] );
			}

			$task = \FluentBoards\App\Models\Task::create( $task_data );

			// Handle assignees
			if ( ! empty( $args['assignees'] ) && is_array( $args['assignees'] ) ) {
				$this->assign_users_to_task( $task->id, $args['assignees'] );
			}

			// Handle labels
			if ( ! empty( $args['labels'] ) && is_array( $args['labels'] ) ) {
				$this->attach_labels_to_task( $task->id, $args['labels'] );
			}

			// Load task with relationships
			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task created successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute update task
	 */
	public function execute_update_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			$update_data = [ 'updated_at' => current_time( 'mysql' ) ];

			// Update allowed fields
			if ( ! empty( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}
			if ( ! empty( $args['description'] ) ) {
				$update_data['description'] = wp_kses_post( $args['description'] );
			}
			if ( ! empty( $args['stage_id'] ) ) {
				// Verify stage belongs to board
				$stage = \FluentBoards\App\Models\Stage::where( 'id', (int) $args['stage_id'] )
														->where( 'board_id', $board_id )
														->first();
				if ( ! $stage ) {
					return $this->get_error_response( 'Invalid stage for this board', 'invalid_stage' );
				}
				$update_data['stage_id'] = (int) $args['stage_id'];
			}
			if ( ! empty( $args['priority'] ) ) {
				$update_data['priority'] = sanitize_text_field( $args['priority'] );
			}
			if ( isset( $args['due_at'] ) ) {
				$update_data['due_at'] = $args['due_at'] ? sanitize_text_field( $args['due_at'] ) : null;
			}
			if ( isset( $args['started_at'] ) ) {
				$update_data['started_at'] = $args['started_at'] ? sanitize_text_field( $args['started_at'] ) : null;
			}
			if ( isset( $args['remind_at'] ) ) {
				$update_data['remind_at'] = $args['remind_at'] ? sanitize_text_field( $args['remind_at'] ) : null;
			}
			if ( ! empty( $args['reminder_type'] ) ) {
				$update_data['reminder_type'] = sanitize_text_field( $args['reminder_type'] );
			}
			if ( isset( $args['lead_value'] ) ) {
				$update_data['lead_value'] = (float) $args['lead_value'];
			}
			if ( ! empty( $args['crm_contact_id'] ) ) {
				$update_data['crm_contact_id'] = (int) $args['crm_contact_id'];
			}
			if ( ! empty( $args['status'] ) ) {
				$update_data['status'] = sanitize_text_field( $args['status'] );
			}
			if ( ! empty( $args['scope'] ) ) {
				$update_data['scope'] = sanitize_text_field( $args['scope'] );
			}
			if ( ! empty( $args['source'] ) ) {
				$update_data['source'] = sanitize_text_field( $args['source'] );
			}
			if ( ! empty( $args['settings'] ) ) {
				$update_data['settings'] = wp_json_encode( $args['settings'] );
			}

			$task->update( $update_data );

			// Handle assignees update
			if ( isset( $args['assignees'] ) && is_array( $args['assignees'] ) ) {
				$this->update_task_assignees( $task_id, $args['assignees'] );
			}

			// Handle time logging
			if ( ! empty( $args['log_minutes'] ) && (int) $args['log_minutes'] > 0 ) {
				$this->log_task_time( $task_id, (int) $args['log_minutes'] );
			}

			// Load task with relationships
			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task updated successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute delete task
	 */
	public function execute_delete_task( array $args ): array {
		$board_id       = (int) $args['board_id'];
		$task_id        = (int) $args['task_id'];
		$confirm_delete = (bool) $args['confirm_delete'];

		if ( ! $confirm_delete ) {
			return $this->get_error_response( 'Deletion not confirmed', 'confirmation_required' );
		}

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Delete related data
			\FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
											->where( 'object_type', 'task_assignee' )
											->delete();

			\FluentBoards\App\Models\Comment::where( 'object_id', $task_id )
											->where( 'object_type', 'task' )
											->delete();

			\FluentBoards\App\Models\Attachment::where( 'parent_id', $task_id )
												->where( 'type', 'task_attachment' )
												->delete();

			// Delete the task
			$task->delete();

			return $this->get_success_response([
				'message' => 'Task deleted successfully',
				'task_id' => $task_id,
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute move task
	 */
	public function execute_move_task( array $args ): array {
		$board_id     = (int) $args['board_id'];
		$task_id      = (int) $args['task_id'];
		$new_stage_id = (int) $args['newStageId'];
		$new_index    = (int) $args['newIndex'];
		$new_board_id = ! empty( $args['newBoardId'] ) ? (int) $args['newBoardId'] : $board_id;

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to source board', 'access_denied' );
		}

		if ( $new_board_id !== $board_id && ! $this->can_access_board( $new_board_id ) ) {
			return $this->get_error_response( 'Access denied to target board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Verify stage belongs to target board
			$stage = \FluentBoards\App\Models\Stage::where( 'id', $new_stage_id )
													->where( 'board_id', $new_board_id )
													->first();
			if ( ! $stage ) {
				return $this->get_error_response( 'Invalid stage for target board', 'invalid_stage' );
			}

			// Update task position in current stage if moving within same stage
			if ( $task->stage_id === $new_stage_id ) {
				$this->reorder_tasks_in_stage( $new_stage_id, $task_id, $new_index );
			} else {
				// Moving to different stage - update stage and position
				$task->update([
					'stage_id'   => $new_stage_id,
					'board_id'   => $new_board_id,
					'position'   => $new_index,
					'updated_at' => current_time( 'mysql' ),
				]);

				// Reorder other tasks in both stages
				$this->reorder_tasks_in_stage( $task->stage_id ); // Old stage
				$this->reorder_tasks_in_stage( $new_stage_id );   // New stage
			}

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task moved successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to move task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute assign yourself to task
	 */
	public function execute_assign_yourself_to_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];
		$user_id  = get_current_user_id();

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Check if already assigned
			$existing_assignment = \FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
																	->where( 'object_type', 'task_assignee' )
																	->where( 'foreign_id', $user_id )
																	->first();

			if ( $existing_assignment ) {
				return $this->get_error_response( 'You are already assigned to this task', 'already_assigned' );
			}

			// Create assignment
			\FluentBoards\App\Models\Relation::create([
				'object_id'   => $task_id,
				'object_type' => 'task_assignee',
				'foreign_id'  => $user_id,
				'settings'    => wp_json_encode( [] ),
				'created_at'  => current_time( 'mysql' ),
			]);

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Successfully assigned yourself to task',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to assign yourself to task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute detach yourself from task
	 */
	public function execute_detach_yourself_from_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];
		$user_id  = get_current_user_id();

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Remove assignment
			$deleted = \FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
														->where( 'object_type', 'task_assignee' )
														->where( 'foreign_id', $user_id )
														->delete();

			if ( ! $deleted ) {
				return $this->get_error_response( 'You are not assigned to this task', 'not_assigned' );
			}

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Successfully removed yourself from task',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove yourself from task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute clone task
	 */
	public function execute_clone_task( array $args ): array {
		$board_id        = (int) $args['board_id'];
		$task_id         = (int) $args['task_id'];
		$title           = sanitize_text_field( $args['title'] );
		$stage_id        = (int) $args['stage_id'];
		$target_board_id = ! empty( $args['target_board_id'] ) ? (int) $args['target_board_id'] : $board_id;

		$clone_assignee   = $args['assignee'] ?? true;
		$clone_subtask    = $args['subtask'] ?? true;
		$clone_label      = $args['label'] ?? true;
		$clone_attachment = $args['attachment'] ?? false;
		$clone_comment    = $args['comment'] ?? false;

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to source board', 'access_denied' );
		}

		if ( $target_board_id !== $board_id && ! $this->can_access_board( $target_board_id ) ) {
			return $this->get_error_response( 'Access denied to target board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$original_task = \FluentBoards\App\Models\Task::with([
				'assignees',
				'labels',
				'attachments',
				'comments',
				'subtasks',
			])->find( $task_id );

			if ( ! $original_task || $original_task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Verify stage belongs to target board
			$stage = \FluentBoards\App\Models\Stage::where( 'id', $stage_id )
													->where( 'board_id', $target_board_id )
													->first();
			if ( ! $stage ) {
				return $this->get_error_response( 'Invalid stage for target board', 'invalid_stage' );
			}

			// Clone the task
			$clone_data = $original_task->toArray();
			unset( $clone_data['id'], $clone_data['created_at'], $clone_data['updated_at'] );

			$clone_data['title']      = $title;
			$clone_data['board_id']   = $target_board_id;
			$clone_data['stage_id']   = $stage_id;
			$clone_data['position']   = $this->get_next_task_position( $stage_id );
			$clone_data['created_by'] = get_current_user_id();
			$clone_data['created_at'] = current_time( 'mysql' );
			$clone_data['updated_at'] = current_time( 'mysql' );

			$cloned_task = \FluentBoards\App\Models\Task::create( $clone_data );

			// Clone related data based on options
			if ( $clone_assignee && $original_task->assignees ) {
				foreach ( $original_task->assignees as $assignee ) {
					\FluentBoards\App\Models\Relation::create([
						'object_id'   => $cloned_task->id,
						'object_type' => 'task_assignee',
						'foreign_id'  => $assignee->id,
						'settings'    => wp_json_encode( [] ),
						'created_at'  => current_time( 'mysql' ),
					]);
				}
			}

			if ( $clone_label && $original_task->labels ) {
				foreach ( $original_task->labels as $label ) {
					\FluentBoards\App\Models\Relation::create([
						'object_id'   => $cloned_task->id,
						'object_type' => 'task_label',
						'foreign_id'  => $label->id,
						'settings'    => wp_json_encode( [] ),
						'created_at'  => current_time( 'mysql' ),
					]);
				}
			}

			// Note: Attachment and comment cloning would require more complex logic
			// depending on FluentBoards implementation

			$cloned_task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'             => $cloned_task->toArray(),
				'original_task_id' => $task_id,
				'message'          => 'Task cloned successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to clone task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute archive task
	 */
	public function execute_archive_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			$task->update([
				'archived_at' => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			]);

			return $this->get_success_response([
				'message' => 'Task archived successfully',
				'task_id' => $task_id,
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to archive task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute restore task
	 */
	public function execute_restore_task( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		try {
			// Find archived task
			$task = \FluentBoards\App\Models\Task::where( 'id', $task_id )
												->where( 'board_id', $board_id )
												->whereNotNull( 'archived_at' )
												->first();

			if ( ! $task ) {
				return $this->get_error_response( 'Archived task not found', 'not_found' );
			}

			$task->update([
				'archived_at' => null,
				'updated_at'  => current_time( 'mysql' ),
			]);

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task restored successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to restore task: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute change task status
	 */
	public function execute_change_task_status( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];
		$stage_id = (int) $args['stage_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			// Verify stage belongs to board
			$stage = \FluentBoards\App\Models\Stage::where( 'id', $stage_id )
													->where( 'board_id', $board_id )
													->first();
			if ( ! $stage ) {
				return $this->get_error_response( 'Invalid stage for this board', 'invalid_stage' );
			}

			$old_stage_id = $task->stage_id;

			$task->update([
				'stage_id'   => $stage_id,
				'updated_at' => current_time( 'mysql' ),
			]);

			// Reorder tasks in both stages if stage changed
			if ( $old_stage_id !== $stage_id ) {
				$this->reorder_tasks_in_stage( $old_stage_id );
				$this->reorder_tasks_in_stage( $stage_id );
			}

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task status changed successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to change task status: ' . $e->getMessage(), 'database_error' );
		}
	}

	/**
	 * Execute update task dates
	 */
	public function execute_update_task_dates( array $args ): array {
		$board_id = (int) $args['board_id'];
		$task_id  = (int) $args['task_id'];

		if ( ! $this->can_access_board( $board_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		if ( ! $this->task_exists( $task_id ) ) {
			return $this->get_error_response( 'Task not found', 'not_found' );
		}

		try {
			$task = \FluentBoards\App\Models\Task::find( $task_id );

			if ( ! $task || $task->board_id !== $board_id ) {
				return $this->get_error_response( 'Task not found in specified board', 'not_found' );
			}

			$update_data = [ 'updated_at' => current_time( 'mysql' ) ];

			if ( isset( $args['started_at'] ) ) {
				$update_data['started_at'] = $args['started_at'] ? sanitize_text_field( $args['started_at'] ) : null;
			}

			if ( isset( $args['due_at'] ) ) {
				$update_data['due_at'] = $args['due_at'] ? sanitize_text_field( $args['due_at'] ) : null;
			}

			$task->update( $update_data );

			$task->load( [ 'assignees', 'stage', 'labels' ] );

			return $this->get_success_response([
				'task'    => $task->toArray(),
				'message' => 'Task dates updated successfully',
			]);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update task dates: ' . $e->getMessage(), 'database_error' );
		}
	}

	// ========================================
	// Helper Methods
	// ========================================

	/**
	 * Check if user can access a specific board
	 */
	private function can_access_board( int $board_id ): bool {
		// Use parent method for basic capability check
		if ( ! $this->can_view_boards( $board_id ) ) {
			return false;
		}

		// Additional board-specific checks can be added here
		return $this->board_exists( $board_id );
	}

	/**
	 * Get the next position for a task in a stage
	 */
	private function get_next_task_position( int $stage_id ): int {
		$max_position = \FluentBoards\App\Models\Task::where( 'stage_id', $stage_id )
													->whereNull( 'archived_at' )
													->max( 'position' );
		return ( $max_position ?? 0 ) + 1;
	}

	/**
	 * Assign users to a task
	 */
	private function assign_users_to_task( int $task_id, array $user_ids ): void {
		foreach ( $user_ids as $user_id ) {
			$user_id = (int) $user_id;
			if ( ! get_user_by( 'id', $user_id ) ) {
				continue; // Skip invalid user IDs
			}

			// Check if already assigned
			$existing = \FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
														->where( 'object_type', 'task_assignee' )
														->where( 'foreign_id', $user_id )
														->first();

			if ( ! $existing ) {
				\FluentBoards\App\Models\Relation::create([
					'object_id'   => $task_id,
					'object_type' => 'task_assignee',
					'foreign_id'  => $user_id,
					'settings'    => wp_json_encode( [] ),
					'created_at'  => current_time( 'mysql' ),
				]);
			}
		}
	}

	/**
	 * Update task assignees (replace existing)
	 */
	private function update_task_assignees( int $task_id, array $user_ids ): void {
		// Remove existing assignments
		\FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
										->where( 'object_type', 'task_assignee' )
										->delete();

		// Add new assignments
		$this->assign_users_to_task( $task_id, $user_ids );
	}

	/**
	 * Attach labels to a task
	 */
	private function attach_labels_to_task( int $task_id, array $label_ids ): void {
		foreach ( $label_ids as $label_id ) {
			$label_id = (int) $label_id;

			// Verify label exists
			$label = \FluentBoards\App\Models\Label::find( $label_id );
			if ( ! $label ) {
				continue; // Skip invalid label IDs
			}

			// Check if already attached
			$existing = \FluentBoards\App\Models\Relation::where( 'object_id', $task_id )
														->where( 'object_type', 'task_label' )
														->where( 'foreign_id', $label_id )
														->first();

			if ( ! $existing ) {
				\FluentBoards\App\Models\Relation::create([
					'object_id'   => $task_id,
					'object_type' => 'task_label',
					'foreign_id'  => $label_id,
					'settings'    => wp_json_encode( [] ),
					'created_at'  => current_time( 'mysql' ),
				]);
			}
		}
	}

	/**
	 * Log time for a task
	 */
	private function log_task_time( int $task_id, int $minutes ): void {
		// This would integrate with FluentBoards time tracking if available
		// Implementation depends on FluentBoards time tracking structure

		// For now, we'll create a comment with time information
		\FluentBoards\App\Models\Comment::create([
			'object_id'    => $task_id,
			'object_type'  => 'task',
			'comment'      => sprintf( 'Time logged: %d minutes', $minutes ),
			'comment_type' => 'time_log',
			'created_by'   => get_current_user_id(),
			'created_at'   => current_time( 'mysql' ),
		]);
	}

	/**
	 * Reorder tasks in a stage
	 */
	private function reorder_tasks_in_stage( int $stage_id, ?int $moved_task_id = null, ?int $new_position = null ): void {
		$tasks = \FluentBoards\App\Models\Task::where( 'stage_id', $stage_id )
												->whereNull( 'archived_at' )
												->orderBy( 'position' )
												->get();

		$position = 1;
		foreach ( $tasks as $task ) {
			if ( $moved_task_id && $moved_task_id === $task->id && null !== $new_position ) {
				$task->update( [ 'position' => $new_position ] );
			} else {
				$task->update( [ 'position' => $position ] );
				++$position;
			}
		}
	}
}
