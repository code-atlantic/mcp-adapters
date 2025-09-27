<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Comment Abilities
 *
 * Registers WordPress abilities for FluentBoards comment management operations
 * using the new WordPress Abilities API pattern.
 */
class Comments extends BaseAbility {

    /**
     * Register all comment-related abilities
     */
    protected function register_abilities(): void {
        $this->register_get_comments();
        $this->register_add_comment();
        $this->register_update_comment();
        $this->register_update_reply();
        $this->register_delete_comment();
        $this->register_delete_reply();
        $this->register_update_comment_privacy();
    }

    /**
     * Register get comments ability
     */
    private function register_get_comments(): void {
        wp_register_ability('fluentboards_get_comments', [
            'label' => 'Get FluentBoards task comments',
            'description' => 'Get all comments for a task',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'task_id' => [
                        'type' => 'integer',
                        'description' => 'Task ID',
                    ],
                ],
                'required' => ['board_id', 'task_id'],
            ],
            'execute_callback' => [$this, 'execute_get_comments'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register add comment ability
     */
    private function register_add_comment(): void {
        wp_register_ability('fluentboards_add_comment', [
            'label' => 'Add FluentBoards comment',
            'description' => 'Add a comment to a task',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'task_id' => [
                        'type' => 'integer',
                        'description' => 'Task ID',
                    ],
                    'comment' => [
                        'type' => 'string',
                        'description' => 'Comment text',
                    ],
                    'comment_type' => [
                        'type' => 'string',
                        'description' => 'Comment type - comment for new comments, reply for replies',
                        'enum' => ['comment', 'reply'],
                        'default' => 'comment',
                    ],
                    'parent_id' => [
                        'type' => 'integer',
                        'description' => 'Parent comment ID (required for replies)',
                    ],
                    'notify_users' => [
                        'type' => 'array',
                        'description' => 'Array of user IDs to notify about this comment',
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
                'required' => ['board_id', 'task_id', 'comment'],
            ],
            'execute_callback' => [$this, 'execute_add_comment'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register update comment ability
     */
    private function register_update_comment(): void {
        wp_register_ability('fluentboards_update_comment', [
            'label' => 'Update FluentBoards comment',
            'description' => 'Update an existing comment',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'comment_id' => [
                        'type' => 'integer',
                        'description' => 'Comment ID to update',
                    ],
                    'comment' => [
                        'type' => 'string',
                        'description' => 'Updated comment text',
                    ],
                ],
                'required' => ['board_id', 'comment_id', 'comment'],
            ],
            'execute_callback' => [$this, 'execute_update_comment'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register update reply ability
     */
    private function register_update_reply(): void {
        wp_register_ability('fluentboards_update_reply', [
            'label' => 'Update FluentBoards reply',
            'description' => 'Update an existing reply',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'reply_id' => [
                        'type' => 'integer',
                        'description' => 'Reply ID to update',
                    ],
                    'comment' => [
                        'type' => 'string',
                        'description' => 'Updated reply text',
                    ],
                ],
                'required' => ['board_id', 'reply_id', 'comment'],
            ],
            'execute_callback' => [$this, 'execute_update_reply'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register delete comment ability
     */
    private function register_delete_comment(): void {
        wp_register_ability('fluentboards_delete_comment', [
            'label' => 'Delete FluentBoards comment',
            'description' => 'Delete a comment permanently',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'comment_id' => [
                        'type' => 'integer',
                        'description' => 'Comment ID to delete',
                    ],
                    'confirm_delete' => [
                        'type' => 'boolean',
                        'description' => 'Confirmation required: set to true to proceed with deletion',
                    ],
                ],
                'required' => ['board_id', 'comment_id', 'confirm_delete'],
            ],
            'execute_callback' => [$this, 'execute_delete_comment'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register delete reply ability
     */
    private function register_delete_reply(): void {
        wp_register_ability('fluentboards_delete_reply', [
            'label' => 'Delete FluentBoards reply',
            'description' => 'Delete a reply permanently',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'reply_id' => [
                        'type' => 'integer',
                        'description' => 'Reply ID to delete',
                    ],
                    'confirm_delete' => [
                        'type' => 'boolean',
                        'description' => 'Confirmation required: set to true to proceed with deletion',
                    ],
                ],
                'required' => ['board_id', 'reply_id', 'confirm_delete'],
            ],
            'execute_callback' => [$this, 'execute_delete_reply'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Register update comment privacy ability
     */
    private function register_update_comment_privacy(): void {
        wp_register_ability('fluentboards_update_comment_privacy', [
            'label' => 'Update FluentBoards comment privacy',
            'description' => 'Update comment privacy settings',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID',
                    ],
                    'comment_id' => [
                        'type' => 'integer',
                        'description' => 'Comment ID',
                    ],
                    'is_private' => [
                        'type' => 'boolean',
                        'description' => 'Whether comment should be private',
                    ],
                ],
                'required' => ['board_id', 'comment_id', 'is_private'],
            ],
            'execute_callback' => [$this, 'execute_update_comment_privacy'],
            'permission_callback' => [$this, 'can_manage_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'comments',
            ],
        ]);
    }

    /**
     * Execute get comments ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_comments(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $task_id = intval($args['task_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($task_id <= 0) {
                return $this->get_error_response('Invalid task ID', 'invalid_task_id');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Verify task exists
            if (!$this->task_exists($task_id)) {
                return $this->get_error_response('Task not found', 'task_not_found');
            }

            // Get comments
            $commentModel = new \FluentBoards\App\Models\Comment();
            $comments = $commentModel->where('object_id', $task_id)
                                   ->where('object_type', 'task')
                                   ->with(['user', 'replies.user'])
                                   ->orderBy('created_at', 'ASC')
                                   ->get();

            $formatted_comments = [];
            foreach ($comments as $comment) {
                $comment_data = [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'object_id' => $comment->object_id,
                    'object_type' => $comment->object_type,
                    'created_by' => $comment->created_by,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'is_private' => $comment->is_private ?? false,
                    'user' => $comment->user ? [
                        'id' => $comment->user->ID,
                        'name' => $comment->user->display_name,
                        'email' => $comment->user->user_email,
                    ] : null,
                    'replies' => []
                ];

                // Add replies if any
                if ($comment->replies) {
                    foreach ($comment->replies as $reply) {
                        $comment_data['replies'][] = [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'created_by' => $reply->created_by,
                            'created_at' => $reply->created_at,
                            'updated_at' => $reply->updated_at,
                            'user' => $reply->user ? [
                                'id' => $reply->user->ID,
                                'name' => $reply->user->display_name,
                                'email' => $reply->user->user_email,
                            ] : null,
                        ];
                    }
                }

                $formatted_comments[] = $comment_data;
            }

            return $this->get_success_response([
                'comments' => $formatted_comments,
                'total_comments' => count($formatted_comments),
                'board_id' => $board_id,
                'task_id' => $task_id
            ], 'Comments retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get comments: ' . $e->getMessage(), 'get_comments_failed');
        }
    }

    /**
     * Execute add comment ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_add_comment(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $task_id = intval($args['task_id']);
            $comment_text = sanitize_textarea_field($args['comment']);
            $comment_type = $args['comment_type'] ?? 'comment';
            $parent_id = isset($args['parent_id']) ? intval($args['parent_id']) : null;
            $notify_users = $args['notify_users'] ?? [];

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($task_id <= 0) {
                return $this->get_error_response('Invalid task ID', 'invalid_task_id');
            }

            if (empty($comment_text)) {
                return $this->get_error_response('Comment text is required', 'comment_required');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Verify task exists
            if (!$this->task_exists($task_id)) {
                return $this->get_error_response('Task not found', 'task_not_found');
            }

            // If reply, verify parent comment exists
            if ($comment_type === 'reply' && $parent_id) {
                $commentModel = new \FluentBoards\App\Models\Comment();
                $parent_comment = $commentModel->find($parent_id);
                if (!$parent_comment) {
                    return $this->get_error_response('Parent comment not found', 'parent_comment_not_found');
                }
            }

            $userId = get_current_user_id();

            // Create comment
            $commentModel = new \FluentBoards\App\Models\Comment();
            $comment_data = [
                'content' => $comment_text,
                'object_id' => $task_id,
                'object_type' => 'task',
                'created_by' => $userId,
                'type' => $comment_type
            ];

            if ($parent_id) {
                $comment_data['parent_id'] = $parent_id;
            }

            $comment = $commentModel->create($comment_data);

            // Handle notifications if provided
            if (!empty($notify_users) && is_array($notify_users)) {
                // This would integrate with FluentBoards notification system
                foreach ($notify_users as $user_id) {
                    if (get_user_by('id', $user_id)) {
                        // Add notification logic here
                        do_action('fluent_boards_comment_notification', $comment->id, $user_id);
                    }
                }
            }

            // Get user data for response
            $user = get_user_by('id', $userId);

            return $this->get_success_response([
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'object_id' => $comment->object_id,
                    'object_type' => $comment->object_type,
                    'created_by' => $comment->created_by,
                    'created_at' => $comment->created_at,
                    'type' => $comment->type,
                    'parent_id' => $comment->parent_id ?? null,
                    'user' => [
                        'id' => $user->ID,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                    ]
                ],
                'board_id' => $board_id,
                'task_id' => $task_id,
                'notifications_sent' => count($notify_users)
            ], 'Comment added successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to add comment: ' . $e->getMessage(), 'add_comment_failed');
        }
    }

    /**
     * Execute update comment ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_update_comment(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $comment_id = intval($args['comment_id']);
            $comment_text = sanitize_textarea_field($args['comment']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($comment_id <= 0) {
                return $this->get_error_response('Invalid comment ID', 'invalid_comment_id');
            }

            if (empty($comment_text)) {
                return $this->get_error_response('Comment text is required', 'comment_required');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Get comment
            $commentModel = new \FluentBoards\App\Models\Comment();
            $comment = $commentModel->find($comment_id);

            if (!$comment) {
                return $this->get_error_response('Comment not found', 'comment_not_found');
            }

            $userId = get_current_user_id();

            // Check if user can edit this comment (owner or admin)
            if ($comment->created_by !== $userId && !current_user_can('manage_options')) {
                return $this->get_error_response('Permission denied to edit this comment', 'permission_denied');
            }

            // Update comment
            $comment->update([
                'content' => $comment_text,
                'updated_at' => current_time('mysql')
            ]);

            return $this->get_success_response([
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'object_id' => $comment->object_id,
                    'object_type' => $comment->object_type,
                    'created_by' => $comment->created_by,
                    'updated_at' => $comment->updated_at,
                ],
                'board_id' => $board_id
            ], 'Comment updated successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to update comment: ' . $e->getMessage(), 'update_comment_failed');
        }
    }

    /**
     * Execute update reply ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_update_reply(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $reply_id = intval($args['reply_id']);
            $comment_text = sanitize_textarea_field($args['comment']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($reply_id <= 0) {
                return $this->get_error_response('Invalid reply ID', 'invalid_reply_id');
            }

            if (empty($comment_text)) {
                return $this->get_error_response('Reply text is required', 'reply_required');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Get reply (which is also a comment with parent_id)
            $commentModel = new \FluentBoards\App\Models\Comment();
            $reply = $commentModel->find($reply_id);

            if (!$reply) {
                return $this->get_error_response('Reply not found', 'reply_not_found');
            }

            $userId = get_current_user_id();

            // Check if user can edit this reply (owner or admin)
            if ($reply->created_by !== $userId && !current_user_can('manage_options')) {
                return $this->get_error_response('Permission denied to edit this reply', 'permission_denied');
            }

            // Update reply
            $reply->update([
                'content' => $comment_text,
                'updated_at' => current_time('mysql')
            ]);

            return $this->get_success_response([
                'reply' => [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'parent_id' => $reply->parent_id,
                    'created_by' => $reply->created_by,
                    'updated_at' => $reply->updated_at,
                ],
                'board_id' => $board_id
            ], 'Reply updated successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to update reply: ' . $e->getMessage(), 'update_reply_failed');
        }
    }

    /**
     * Execute delete comment ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_delete_comment(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $comment_id = intval($args['comment_id']);
            $confirm_delete = $args['confirm_delete'] ?? false;

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($comment_id <= 0) {
                return $this->get_error_response('Invalid comment ID', 'invalid_comment_id');
            }

            if (!$confirm_delete) {
                return $this->get_error_response('Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Get comment
            $commentModel = new \FluentBoards\App\Models\Comment();
            $comment = $commentModel->with('replies')->find($comment_id);

            if (!$comment) {
                return $this->get_error_response('Comment not found', 'comment_not_found');
            }

            $userId = get_current_user_id();

            // Check if user can delete this comment (owner or admin)
            if ($comment->created_by !== $userId && !current_user_can('manage_options')) {
                return $this->get_error_response('Permission denied to delete this comment', 'permission_denied');
            }

            // Count replies before deletion
            $replies_count = $comment->replies ? $comment->replies->count() : 0;

            // Delete all replies first
            if ($comment->replies) {
                foreach ($comment->replies as $reply) {
                    $reply->delete();
                }
            }

            // Delete the comment
            $comment->delete();

            return $this->get_success_response([
                'comment_id' => $comment_id,
                'board_id' => $board_id,
                'replies_deleted' => $replies_count,
                'deleted_at' => current_time('mysql')
            ], 'Comment and replies deleted successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to delete comment: ' . $e->getMessage(), 'delete_comment_failed');
        }
    }

    /**
     * Execute delete reply ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_delete_reply(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $reply_id = intval($args['reply_id']);
            $confirm_delete = $args['confirm_delete'] ?? false;

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($reply_id <= 0) {
                return $this->get_error_response('Invalid reply ID', 'invalid_reply_id');
            }

            if (!$confirm_delete) {
                return $this->get_error_response('Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Get reply
            $commentModel = new \FluentBoards\App\Models\Comment();
            $reply = $commentModel->find($reply_id);

            if (!$reply) {
                return $this->get_error_response('Reply not found', 'reply_not_found');
            }

            $userId = get_current_user_id();

            // Check if user can delete this reply (owner or admin)
            if ($reply->created_by !== $userId && !current_user_can('manage_options')) {
                return $this->get_error_response('Permission denied to delete this reply', 'permission_denied');
            }

            // Delete the reply
            $reply->delete();

            return $this->get_success_response([
                'reply_id' => $reply_id,
                'board_id' => $board_id,
                'deleted_at' => current_time('mysql')
            ], 'Reply deleted successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to delete reply: ' . $e->getMessage(), 'delete_reply_failed');
        }
    }

    /**
     * Execute update comment privacy ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_update_comment_privacy(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $comment_id = intval($args['comment_id']);
            $is_private = (bool) $args['is_private'];

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            if ($comment_id <= 0) {
                return $this->get_error_response('Invalid comment ID', 'invalid_comment_id');
            }

            // Verify board exists and user has access
            if (!$this->board_exists($board_id)) {
                return $this->get_error_response('Board not found', 'board_not_found');
            }

            // Get comment
            $commentModel = new \FluentBoards\App\Models\Comment();
            $comment = $commentModel->find($comment_id);

            if (!$comment) {
                return $this->get_error_response('Comment not found', 'comment_not_found');
            }

            $userId = get_current_user_id();

            // Check if user can modify this comment privacy (owner or admin)
            if ($comment->created_by !== $userId && !current_user_can('manage_options')) {
                return $this->get_error_response('Permission denied to modify this comment privacy', 'permission_denied');
            }

            // Update privacy setting
            $comment->update([
                'is_private' => $is_private,
                'updated_at' => current_time('mysql')
            ]);

            return $this->get_success_response([
                'comment' => [
                    'id' => $comment->id,
                    'is_private' => $comment->is_private,
                    'updated_at' => $comment->updated_at,
                ],
                'board_id' => $board_id
            ], 'Comment privacy updated successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to update comment privacy: ' . $e->getMessage(), 'update_privacy_failed');
        }
    }
}