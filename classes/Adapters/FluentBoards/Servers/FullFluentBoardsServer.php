<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * Full FluentBoards MCP Server
 *
 * Complete server exposing all FluentBoards capabilities including boards,
 * tasks, comments, attachments, users, labels, and reporting.
 */
class FullFluentBoardsServer {

	/**
	 * Register full FluentBoards server with MCP adapter
	 *
	 * @param object $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-full',
			'fluentboards',
			'mcp',
			'FluentBoards Complete',
			'Complete FluentBoards project management with all features - boards, tasks, comments, attachments, reporting',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_all_abilities()
		);
	}

	/**
	 * Get all available FluentBoards abilities
	 *
	 * @return array Complete list of ability names
	 */
	private function get_all_abilities(): array {
		return [
			// Board Management
			'fluentboards/create-board',
			'fluentboards/list-boards',
			'fluentboards/get-board',
			'fluentboards/update-board',
			'fluentboards/delete-board',
			'fluentboards/duplicate-board',
			'fluentboards/archive-board',
			'fluentboards/restore-board',
			'fluentboards/pin-board',
			'fluentboards/unpin-board',
			'fluentboards/update-board-permissions',

			// Board Members
			'fluentboards/get-board-users',
			'fluentboards/add-board-member',
			'fluentboards/remove-board-member',
			'fluentboards/update-member-role',
			'fluentboards/bulk-add-members',

			// Task Management
			'fluentboards/create-task',
			'fluentboards/list-tasks',
			'fluentboards/get-task',
			'fluentboards/update-task',
			'fluentboards/delete-task',
			'fluentboards/clone-task',
			'fluentboards/archive-task',
			'fluentboards/restore-task',
			'fluentboards/move-task',
			'fluentboards/change-task-status',
			'fluentboards/assign-yourself-to-task',
			'fluentboards/detach-yourself-from-task',

			// Stage Management
			'fluentboards/create-stage',
			'fluentboards/list-stages',
			'fluentboards/update-stage',
			'fluentboards/delete-stage',
			'fluentboards/restore-stage',
			'fluentboards/get-archived-stages',
			'fluentboards/get-stage-positions',
			'fluentboards/reorder-stages',
			'fluentboards/move-all-tasks',
			'fluentboards/archive-all-tasks',
			'fluentboards/sort-stage-tasks',

			// Comments
			'fluentboards/add-comment',
			'fluentboards/get-comments',
			'fluentboards/update-comment',
			'fluentboards/delete-comment',
			'fluentboards/update-comment-privacy',
			'fluentboards/add-reply',
			'fluentboards/update-reply',
			'fluentboards/delete-reply',

			// Labels
			'fluentboards/create-label',
			'fluentboards/list-labels',
			'fluentboards/update-label',
			'fluentboards/delete-label',
			'fluentboards/add-label-to-task',
			'fluentboards/remove-label-from-task',
			'fluentboards/get-task-labels',

			// Attachments
			'fluentboards/add-task-attachment',
			'fluentboards/get-task-attachments',
			'fluentboards/get-attachment-files',
			'fluentboards/update-attachment',
			'fluentboards/delete-attachment',

			// Users & Activities
			'fluentboards/get-all-users',
			'fluentboards/search-users',
			'fluentboards/get-user-info',
			'fluentboards/get-user-boards',
			'fluentboards/get-user-tasks',
			'fluentboards/get-user-activities',
			'fluentboards/get-board-activities',
			'fluentboards/get-activity-timeline',
			'fluentboards/set-super-admin',
			'fluentboards/remove-super-admin',
			'fluentboards/bulk-set-super-admins',

			// Reporting
			'fluentboards/get-dashboard-stats',
			'fluentboards/get-board-report',
			'fluentboards/get-all-board-reports',
			'fluentboards/get-project-reports',
			'fluentboards/get-member-reports',
			'fluentboards/get-stage-wise-reports',
			'fluentboards/get-team-workload',
			'fluentboards/get-timesheet-reports',
			'fluentboards/get-timesheet-by-users',
			'fluentboards/get-timesheet-by-tasks',

			// Test Abilities (for development)
			'fluentboards/test-verbose-enum',
			'fluentboards/test-pattern-enum',
			'fluentboards/generate-planet-verbose',
			'fluentboards/generate-planet-pattern',
		];
	}
}
