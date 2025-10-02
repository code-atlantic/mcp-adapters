<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards Task Manager MCP Server
 *
 * Task management server for project coordinators and team leads.
 * Includes comprehensive task operations, assignments, labels, and comments.
 */
class TaskManagerServer {

	/**
	 * Register task manager server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-task-manager',
			'fluentboards-tasks',
			'mcp',
			'FluentBoards Task Manager',
			'Comprehensive task management for coordinators - create, assign, organize, comment, attach files',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_task_manager_abilities(),
			[],
			$this->get_task_manager_prompts()
		);
	}

	/**
	 * Get task manager abilities
	 *
	 * @return array Task manager ability names
	 */
	private function get_task_manager_abilities(): array {
		return array_merge(
			$this->get_task_management_abilities(),
			$this->get_task_organization_abilities(),
			$this->get_comment_abilities(),
			$this->get_attachment_abilities()
		);
	}

	/**
	 * Get task management abilities
	 *
	 * @return array
	 */
	private function get_task_management_abilities(): array {
		return [
			// Core CRUD
			'fluentboards/create-task',
			'fluentboards/list-tasks',
			'fluentboards/get-task',
			'fluentboards/update-task',
			'fluentboards/delete-task',

			// Task operations
			'fluentboards/clone-task',
			'fluentboards/archive-task',
			'fluentboards/restore-task',
			'fluentboards/move-task',
			'fluentboards/change-task-status',
		];
	}

	/**
	 * Get task organization abilities
	 *
	 * @return array
	 */
	private function get_task_organization_abilities(): array {
		return [
			// Labels
			'fluentboards/add-label-to-task',
			'fluentboards/remove-label-from-task',
			'fluentboards/get-task-labels',
			'fluentboards/list-labels',
		];
	}

	/**
	 * Get comment abilities
	 *
	 * @return array
	 */
	private function get_comment_abilities(): array {
		return [
			'fluentboards/add-comment',
			'fluentboards/get-comments',
			'fluentboards/update-comment',
			'fluentboards/delete-comment',
			'fluentboards/update-comment-privacy',
			'fluentboards/update-reply',
			'fluentboards/delete-reply',
		];
	}

	/**
	 * Get attachment abilities
	 *
	 * @return array
	 */
	private function get_attachment_abilities(): array {
		return [
			'fluentboards/add-task-attachment',
			'fluentboards/get-task-attachments',
			'fluentboards/get-attachment-files',
			'fluentboards/update-attachment',
			'fluentboards/delete-attachment',
		];
	}

	/**
	 * Get task manager prompts
	 *
	 * @return array
	 */
	private function get_task_manager_prompts(): array {
		return [
			'fluentboards/status-checkin',
			'fluentboards/analyze-workflow',
		];
	}
}
