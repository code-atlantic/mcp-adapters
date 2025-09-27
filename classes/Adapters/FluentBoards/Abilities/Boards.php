<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Board Abilities
 *
 * Registers WordPress abilities for FluentBoards board management operations
 * using the new WordPress Abilities API pattern.
 */
class Boards extends BaseAbility {

    /**
     * Register all board-related abilities
     */
    protected function register_abilities(): void {
        $this->register_list_boards();
        $this->register_get_board();
        $this->register_create_board();
        $this->register_update_board();
        $this->register_delete_board();
        $this->register_archive_board();
        $this->register_restore_board();
        $this->register_duplicate_board();
        $this->register_pin_board();
        $this->register_unpin_board();
    }

    /**
     * Register list boards ability
     */
    private function register_list_boards(): void {
        wp_register_ability('fluentboards_list_boards', [
            'label' => 'List FluentBoards boards',
            'description' => 'List all boards accessible to the current user',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page number',
                        'default' => 1,
                        'minimum' => 1,
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of boards per page',
                        'default' => 20,
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search boards by title',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Filter boards by type',
                        'enum' => ['to-do', 'kanban', 'roadmap'],
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_list_boards'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Register get board ability
     */
    private function register_get_board(): void {
        wp_register_ability('fluentboards_get_board', [
            'label' => 'Get FluentBoards board',
            'description' => 'Get detailed information about a specific board',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to retrieve',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_get_board'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Register create board ability
     */
    private function register_create_board(): void {
        wp_register_ability('fluentboards_create_board', [
            'label' => 'Create FluentBoards board',
            'description' => 'Create a new board with comprehensive options',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => [
                        'type' => 'string',
                        'description' => 'Board title (required)',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Board description',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Board type',
                        'enum' => ['to-do', 'roadmap'],
                        'default' => 'to-do',
                    ],
                    'background' => [
                        'type' => 'object',
                        'description' => 'Board background configuration',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                                'description' => 'Background preset ID',
                                'enum' => [
                                    'solid_1', 'solid_2', 'solid_3', 'solid_4', 'solid_5',
                                    'gradient_1', 'gradient_2', 'gradient_3', 'gradient_4',
                                ],
                            ],
                            'color' => [
                                'type' => 'string',
                                'description' => 'Background color as hex code (e.g., #4A9B7F)',
                                'pattern' => '^#[0-9a-fA-F]{6}$',
                            ],
                        ],
                    ],
                ],
                'required' => ['title'],
            ],
            'execute_callback' => [$this, 'execute_create_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Execute list boards ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_list_boards(array $args): array {
        try {
            $search = $args['search'] ?? '';
            $per_page = $args['per_page'] ?? 20;
            $type = $args['type'] ?? 'to-do';
            $page = $args['page'] ?? 1;

            $userId = get_current_user_id();
            $boardModel = new \FluentBoards\App\Models\Board();
            $boardService = new \FluentBoards\App\Services\BoardService();

            // Build query
            $query = $boardModel->whereNull('archived_at')
                               ->where('type', $type)
                               ->byAccessUser($userId);

            // Add search if provided
            if (!empty($search)) {
                $query = $query->where('title', 'like', '%' . $search . '%');
            }

            // Get boards with pagination
            $offset = ($page - 1) * $per_page;
            $boards = $query->orderBy('created_at', 'DESC')
                           ->withCount('completedTasks')
                           ->with(['stages', 'users'])
                           ->offset($offset)
                           ->limit($per_page)
                           ->get();

            $result = [];
            $pinned_boards = [];
            $regular_boards = [];

            foreach ($boards as $board) {
                $board_data = [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'type' => $board->type,
                    'created_at' => $board->created_at,
                    'updated_at' => $board->updated_at,
                    'completed_tasks_count' => $board->completed_tasks_count,
                    'stages_count' => $board->stages->count(),
                    'users_count' => $board->users->count(),
                    'is_pinned' => $boardService->isPinned($board->id),
                    'settings' => $board->settings,
                    'meta' => $board->meta
                ];

                if ($board_data['is_pinned']) {
                    $pinned_boards[] = $board_data;
                } else {
                    $regular_boards[] = $board_data;
                }
            }

            // Pinned boards appear first
            $result = array_merge($pinned_boards, $regular_boards);

            return $this->get_success_response([
                'boards' => $result,
                'total_boards' => count($result),
                'pinned_count' => count($pinned_boards),
                'regular_count' => count($regular_boards),
                'search_query' => $search,
                'type_filter' => $type,
                'page' => $page,
                'per_page' => $per_page
            ], 'Boards retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to list boards: ' . $e->getMessage(), 'list_failed');
        }
    }

    /**
     * Execute get board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            $boardModel = new \FluentBoards\App\Models\Board();
            $boardService = new \FluentBoards\App\Services\BoardService();

            $board = $boardModel->with(['stages', 'users', 'tasks'])
                               ->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'type' => $board->type,
                    'created_at' => $board->created_at,
                    'updated_at' => $board->updated_at,
                    'stages_count' => $board->stages->count(),
                    'users_count' => $board->users->count(),
                    'tasks_count' => $board->tasks->count(),
                    'is_pinned' => $boardService->isPinned($board->id),
                    'settings' => $board->settings,
                    'meta' => $board->meta
                ]
            ], 'Board retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get board: ' . $e->getMessage(), 'get_failed');
        }
    }

    /**
     * Execute create board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_create_board(array $args): array {
        try {
            $title = sanitize_text_field($args['title']);
            $description = sanitize_textarea_field($args['description'] ?? '');
            $type = $args['type'] ?? 'to-do';

            if (empty($title)) {
                return $this->get_error_response('Board title is required', 'title_required');
            }

            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->create([
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'created_by' => get_current_user_id()
            ]);

            $boardService = new \FluentBoards\App\Services\BoardService();

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'type' => $board->type,
                    'created_at' => $board->created_at,
                    'is_pinned' => $boardService->isPinned($board->id)
                ]
            ], 'Board created successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to create board: ' . $e->getMessage(), 'create_failed');
        }
    }

    /**
     * Register pin board ability
     */
    private function register_pin_board(): void {
        wp_register_ability('fluentboards_pin_board', [
            'label' => 'Pin FluentBoards board',
            'description' => 'Pin a board to the top of the user\'s board list',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to pin',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_pin_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Register unpin board ability
     */
    private function register_unpin_board(): void {
        wp_register_ability('fluentboards_unpin_board', [
            'label' => 'Unpin FluentBoards board',
            'description' => 'Unpin a board from the user\'s pinned list',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to unpin',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_unpin_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Execute pin board ability
     */
    public function execute_pin_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if board exists and user has access
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $boardService = new \FluentBoards\App\Services\BoardService();

            // Check if already pinned
            if ($boardService->isPinned($board_id)) {
                return $this->get_success_response([
                    'board_id' => $board_id,
                    'is_pinned' => true,
                    'action' => 'already_pinned'
                ], 'Board is already pinned');
            }

            // Pin the board
            $boardService->pinBoard($board_id);

            return $this->get_success_response([
                'board_id' => $board_id,
                'board_title' => $board->title,
                'is_pinned' => true,
                'action' => 'pinned'
            ], 'Board has been pinned successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to pin board: ' . $e->getMessage(), 'pin_failed');
        }
    }

    /**
     * Execute unpin board ability
     */
    public function execute_unpin_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if board exists and user has access
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $boardService = new \FluentBoards\App\Services\BoardService();

            // Check if board is actually pinned
            if (!$boardService->isPinned($board_id)) {
                return $this->get_success_response([
                    'board_id' => $board_id,
                    'is_pinned' => false,
                    'action' => 'not_pinned'
                ], 'Board is not pinned');
            }

            // Unpin the board
            $result = $boardService->unpinBoard($board_id);

            if ($result) {
                return $this->get_success_response([
                    'board_id' => $board_id,
                    'board_title' => $board->title,
                    'is_pinned' => false,
                    'action' => 'unpinned'
                ], 'Board has been unpinned successfully');
            } else {
                return $this->get_error_response('Failed to unpin board', 'unpin_failed');
            }

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to unpin board: ' . $e->getMessage(), 'unpin_failed');
        }
    }

    /**
     * Check if user can access specific board
     */
    private function can_access_board($board, $userId): bool {
        if (!$board || !$userId) {
            return false;
        }

        // Admin can access all boards
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check if user is board owner
        if ($board->created_by == $userId) {
            return true;
        }

        // Check if user is assigned to the board
        $userBoard = $board->users()->where('fbs_relations.foreign_id', $userId)->first();
        return !empty($userBoard);
    }

    /**
     * Register update board ability
     */
    private function register_update_board(): void {
        wp_register_ability('fluentboards_update_board', [
            'label' => 'Update FluentBoards board',
            'description' => 'Update an existing board with comprehensive options including background, currency, and settings',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to update (required)',
                    ],
                    'title' => [
                        'type' => 'string',
                        'description' => 'New board title',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'New board description',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'New board type',
                        'enum' => ['to-do', 'kanban', 'roadmap'],
                    ],
                    'currency' => [
                        'type' => 'string',
                        'description' => 'Currency code for budget tracking (e.g., USD, EUR, GBP)',
                    ],
                    'background' => [
                        'type' => 'object',
                        'description' => 'Board background configuration',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                                'description' => 'Background preset ID from available solid colors or gradients',
                                'enum' => [
                                    'solid_1', 'solid_2', 'solid_3', 'solid_4', 'solid_5',
                                    'solid_6', 'solid_7', 'solid_8', 'solid_9', 'solid_10',
                                    'solid_11', 'solid_12', 'solid_13', 'solid_14', 'solid_15',
                                    'solid_16', 'solid_17', 'solid_18', 'solid_19', 'solid_20',
                                    'solid_21', 'solid_22', 'solid_23', 'gradient_1', 'gradient_2',
                                    'gradient_3', 'gradient_4', 'gradient_5', 'gradient_6',
                                ],
                            ],
                            'color' => [
                                'type' => 'string',
                                'description' => 'Background color as hex code (e.g., #4A9B7F) or CSS gradient',
                                'pattern' => '^(#[0-9a-fA-F]{6}|linear-gradient\\(.+\\)|hsla\\(.+\\))$',
                            ],
                            'is_image' => [
                                'type' => 'boolean',
                                'description' => 'Whether background is an image or solid color',
                            ],
                            'image_url' => [
                                'type' => 'string',
                                'format' => 'uri',
                                'description' => 'Background image URL (if is_image is true)',
                            ],
                        ],
                    ],
                    'settings' => [
                        'type' => 'object',
                        'description' => 'Board configuration settings',
                        'properties' => [
                            'enable_stage_change_email' => [
                                'type' => 'boolean',
                                'description' => 'Send email notifications when tasks change stages',
                            ],
                        ],
                    ],
                    'page_id' => [
                        'type' => 'integer',
                        'description' => 'Associated WordPress page ID for roadmap display',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_update_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    private function register_delete_board(): void {
        wp_register_ability('fluentboards_delete_board', [
            'label' => 'Delete FluentBoards board',
            'description' => 'Delete a board permanently (requires confirmation)',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to delete',
                    ],
                    'confirm_delete' => [
                        'type' => 'boolean',
                        'description' => 'Confirmation required: set to true to proceed with deletion',
                    ],
                ],
                'required' => ['board_id', 'confirm_delete'],
            ],
            'execute_callback' => [$this, 'execute_delete_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    private function register_archive_board(): void {
        wp_register_ability('fluentboards_archive_board', [
            'label' => 'Archive FluentBoards board',
            'description' => 'Archive a board (soft delete - can be restored)',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to archive',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_archive_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    private function register_restore_board(): void {
        wp_register_ability('fluentboards_restore_board', [
            'label' => 'Restore FluentBoards board',
            'description' => 'Restore an archived board',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to restore',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_restore_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    private function register_duplicate_board(): void {
        wp_register_ability('fluentboards_duplicate_board', [
            'label' => 'Duplicate FluentBoards board',
            'description' => 'Create a duplicate copy of an existing board',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID to duplicate',
                    ],
                    'new_title' => [
                        'type' => 'string',
                        'description' => 'Title for the duplicated board (optional - will use "Copy of {original title}" if not provided)',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_duplicate_board'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'boards',
            ],
        ]);
    }

    /**
     * Execute update board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_update_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if board exists
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            // Prepare update data
            $update_data = [];

            if (isset($args['title'])) {
                $update_data['title'] = sanitize_text_field($args['title']);
            }

            if (isset($args['description'])) {
                $update_data['description'] = sanitize_textarea_field($args['description']);
            }

            if (isset($args['type'])) {
                $update_data['type'] = $args['type'];
            }

            if (isset($args['currency'])) {
                $update_data['currency'] = $args['currency'];
            }

            if (isset($args['background'])) {
                $update_data['background'] = $args['background'];
            }

            if (isset($args['settings'])) {
                $update_data['settings'] = $args['settings'];
            }

            // Update the board
            if (!empty($update_data)) {
                $board->update($update_data);
                $board = $boardModel->find($board_id); // Refresh
            }

            // Handle page_id separately if provided
            if (isset($args['page_id'])) {
                // This would need FluentBoards Pro API integration
                $meta = $board->meta ?? [];
                $meta['page_id'] = intval($args['page_id']);
                $board->update(['meta' => $meta]);
            }

            $boardService = new \FluentBoards\App\Services\BoardService();

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'description' => $board->description,
                    'type' => $board->type,
                    'updated_at' => $board->updated_at,
                    'is_pinned' => $boardService->isPinned($board->id),
                    'settings' => $board->settings,
                    'meta' => $board->meta
                ]
            ], 'Board updated successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to update board: ' . $e->getMessage(), 'update_failed');
        }
    }

    /**
     * Execute delete board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_delete_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $confirm_delete = $args['confirm_delete'] ?? false;

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if (!$confirm_delete) {
                return $this->get_error_response('Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required');
            }

            // Check if board exists
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $board_title = $board->title;

            // Delete all related data first
            // Delete tasks
            $taskModel = new \FluentBoards\App\Models\Task();
            $taskModel->where('board_id', $board_id)->delete();

            // Delete stages
            $stageModel = new \FluentBoards\App\Models\Stage();
            $stageModel->where('board_id', $board_id)->delete();

            // Delete board relations (users, etc.)
            $relationModel = new \FluentBoards\App\Models\Relation();
            $relationModel->where('object_id', $board_id)
                         ->where('object_type', 'board')
                         ->delete();

            // Finally delete the board
            $board->delete();

            return $this->get_success_response([
                'board_id' => $board_id,
                'board_title' => $board_title,
                'deleted_at' => current_time('mysql')
            ], 'Board deleted successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to delete board: ' . $e->getMessage(), 'delete_failed');
        }
    }

    /**
     * Execute archive board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_archive_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if board exists
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            // Check if already archived
            if ($board->archived_at) {
                return $this->get_success_response([
                    'board_id' => $board_id,
                    'board_title' => $board->title,
                    'is_archived' => true,
                    'archived_at' => $board->archived_at,
                    'action' => 'already_archived'
                ], 'Board is already archived');
            }

            // Archive the board
            $board->update([
                'archived_at' => current_time('mysql')
            ]);

            return $this->get_success_response([
                'board_id' => $board_id,
                'board_title' => $board->title,
                'is_archived' => true,
                'archived_at' => $board->archived_at,
                'action' => 'archived'
            ], 'Board archived successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to archive board: ' . $e->getMessage(), 'archive_failed');
        }
    }

    /**
     * Execute restore board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_restore_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if board exists (including archived)
            $boardModel = new \FluentBoards\App\Models\Board();
            $board = $boardModel->withTrashed()->find($board_id);

            if (!$board) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($board, $userId)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            // Check if board is actually archived
            if (!$board->archived_at) {
                return $this->get_success_response([
                    'board_id' => $board_id,
                    'board_title' => $board->title,
                    'is_archived' => false,
                    'action' => 'not_archived'
                ], 'Board is not archived');
            }

            // Restore the board
            $board->update([
                'archived_at' => null
            ]);

            return $this->get_success_response([
                'board_id' => $board_id,
                'board_title' => $board->title,
                'is_archived' => false,
                'restored_at' => current_time('mysql'),
                'action' => 'restored'
            ], 'Board restored successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to restore board: ' . $e->getMessage(), 'restore_failed');
        }
    }

    /**
     * Execute duplicate board ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_duplicate_board(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check if original board exists
            $boardModel = new \FluentBoards\App\Models\Board();
            $original_board = $boardModel->with(['stages', 'users'])->find($board_id);

            if (!$original_board) {
                return $this->get_error_response('Original board not found', 'board_not_found');
            }

            // Check user access
            $userId = get_current_user_id();
            if (!$this->can_access_board($original_board, $userId)) {
                return $this->get_error_response('Access denied to original board', 'access_denied');
            }

            // Prepare new board data
            $new_title = $args['new_title'] ?? 'Copy of ' . $original_board->title;

            $new_board_data = [
                'title' => $new_title,
                'description' => $original_board->description,
                'type' => $original_board->type,
                'currency' => $original_board->currency,
                'background' => $original_board->background,
                'settings' => $original_board->settings,
                'meta' => $original_board->meta,
                'created_by' => $userId
            ];

            // Create the new board
            $new_board = $boardModel->create($new_board_data);

            // Duplicate stages
            foreach ($original_board->stages as $stage) {
                $stageModel = new \FluentBoards\App\Models\Stage();
                $stageModel->create([
                    'title' => $stage->title,
                    'description' => $stage->description,
                    'board_id' => $new_board->id,
                    'position' => $stage->position,
                    'bg_color' => $stage->bg_color,
                    'settings' => $stage->settings
                ]);
            }

            // Add current user to the new board
            $relationModel = new \FluentBoards\App\Models\Relation();
            $relationModel->create([
                'object_id' => $new_board->id,
                'object_type' => 'board',
                'foreign_id' => $userId,
                'foreign_type' => 'user',
                'settings' => ['role' => 'manager']
            ]);

            $boardService = new \FluentBoards\App\Services\BoardService();

            return $this->get_success_response([
                'original_board' => [
                    'id' => $original_board->id,
                    'title' => $original_board->title
                ],
                'new_board' => [
                    'id' => $new_board->id,
                    'title' => $new_board->title,
                    'description' => $new_board->description,
                    'type' => $new_board->type,
                    'created_at' => $new_board->created_at,
                    'is_pinned' => $boardService->isPinned($new_board->id)
                ]
            ], 'Board duplicated successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to duplicate board: ' . $e->getMessage(), 'duplicate_failed');
        }
    }
}