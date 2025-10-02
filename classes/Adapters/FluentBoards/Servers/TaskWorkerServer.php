<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards Task Worker MCP Server
 *
 * Streamlined server for individual contributors and team members.
 * Focuses on personal task operations without management capabilities.
 */
class TaskWorkerServer {

	/**
	 * Register task worker server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-task-worker',
			'fluentboards-worker',
			'mcp',
			'FluentBoards Task Worker',
			'Streamlined task operations for individual contributors - view, update, comment on assigned tasks',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_task_worker_abilities(),
			[],
			$this->get_task_worker_prompts()
		);
	}

	/**
	 * Get task worker abilities
	 *
	 * @return array Task worker ability names
	 */
	private function get_task_worker_abilities(): array {
		return array_merge(
			$this->get_personal_task_abilities(),
			$this->get_collaboration_abilities(),
			$this->get_information_abilities()
		);
	}

	/**
	 * Get personal task abilities
	 *
	 * @return array
	 */
	private function get_personal_task_abilities(): array {
		return [
			// View tasks
			'fluentboards/list-tasks',
			'fluentboards/get-task',
			'fluentboards/get-user-tasks',

			// Update own tasks
			'fluentboards/update-task',
			'fluentboards/change-task-status',
			'fluentboards/assign-yourself-to-task',
			'fluentboards/detach-yourself-from-task',
		];
	}

	/**
	 * Get collaboration abilities
	 *
	 * @return array
	 */
	private function get_collaboration_abilities(): array {
		return [
			// Comments
			'fluentboards/add-comment',
			'fluentboards/get-comments',
			'fluentboards/update-comment',
			'fluentboards/delete-comment',
			'fluentboards/update-reply',
			'fluentboards/delete-reply',

			// Attachments
			'fluentboards/add-task-attachment',
			'fluentboards/get-task-attachments',
			'fluentboards/get-attachment-files',
		];
	}

	/**
	 * Get information abilities
	 *
	 * @return array
	 */
	private function get_information_abilities(): array {
		return [
			// Context
			'fluentboards/list-boards',
			'fluentboards/get-board',
			'fluentboards/list-stages',
			'fluentboards/get-task-labels',
			'fluentboards/list-labels',

			// Activity
			'fluentboards/get-user-activities',
			'fluentboards/get-activity-timeline',
		];
	}

	/**
	 * Get task worker prompts
	 *
	 * @return array
	 */
	private function get_task_worker_prompts(): array {
		return [
			'fluentboards/status-checkin',
		];
	}
}
