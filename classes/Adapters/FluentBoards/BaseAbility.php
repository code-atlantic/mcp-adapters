<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards;

/**
 * Base class for FluentBoards Abilities
 *
 * Provides common functionality for all FluentBoards abilities including
 * permission checks and utility methods
 */
abstract class BaseAbility {

    /**
     * Constructor - Initialize the ability class
     */
    public function __construct() {
        // Check if FluentBoards is active
        if (!$this->is_fluent_boards_active()) {
            return;
        }

        $this->register_abilities();
    }

    /**
     * Register all abilities for this ability class
     *
     * @return void
     */
    abstract protected function register_abilities(): void;

    /**
     * Check if FluentBoards plugin is active and available
     *
     * @return bool True if FluentBoards is active and ready
     */
    protected function is_fluent_boards_active(): bool {
        return defined('FLUENT_BOARDS') &&
               class_exists('\FluentBoards\App\Models\Board') &&
               (is_plugin_active('fluent-boards/fluent-boards.php') ||
                is_plugin_active('fluent-boards-pro/fluent-boards-pro.php') ||
                function_exists('fluent_boards'));
    }

    /**
     * Check if user has permission for FluentBoards operations
     *
     * @param int|null $board_id Optional board ID for board-specific permissions
     * @return bool True if user has permission
     */
    protected function can_manage_boards(?int $board_id = null): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        // Check if user can manage FluentBoards
        if (current_user_can('manage_options') || current_user_can('fluent_boards_admin')) {
            return true;
        }

        // If specific board ID provided, check board-specific permissions
        if ($board_id && function_exists('fluent_boards_user_can')) {
            return fluent_boards_user_can('manage_board', $board_id);
        }

        return false;
    }

    /**
     * Check if current user can view boards
     *
     * @param int|null $board_id Optional board ID for board-specific permissions
     * @return bool True if user can view
     */
    protected function can_view_boards(?int $board_id = null): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        // Anyone who can manage can also view
        if ($this->can_manage_boards($board_id)) {
            return true;
        }

        // Check FluentBoards view permissions
        if (current_user_can('fluent_boards_view')) {
            return true;
        }

        // If specific board ID provided, check board-specific permissions
        if ($board_id && function_exists('fluent_boards_user_can')) {
            return fluent_boards_user_can('view_board', $board_id);
        }

        return false;
    }

    /**
     * Validate board ID exists
     *
     * @param int $board_id Board ID to validate
     * @return bool True if board exists
     */
    protected function board_exists(int $board_id): bool {
        if (!class_exists('\FluentBoards\App\Models\Board')) {
            return false;
        }

        try {
            $board = \FluentBoards\App\Models\Board::find($board_id);
            return !empty($board);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate task ID exists
     *
     * @param int $task_id Task ID to validate
     * @return bool True if task exists
     */
    protected function task_exists(int $task_id): bool {
        if (!class_exists('\FluentBoards\App\Models\Task')) {
            return false;
        }

        try {
            $task = \FluentBoards\App\Models\Task::find($task_id);
            return !empty($task);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get standardized error response
     *
     * @param string $message Error message
     * @param string $code Error code
     * @return array Error response
     */
    protected function get_error_response(string $message, string $code = 'error'): array {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * Get standardized success response
     *
     * @param array $data Response data
     * @param string $message Success message
     * @return array Success response
     */
    protected function get_success_response(array $data = [], string $message = 'Success'): array {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }
}