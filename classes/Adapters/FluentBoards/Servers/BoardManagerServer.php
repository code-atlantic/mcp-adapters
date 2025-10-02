<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards Board Manager MCP Server
 *
 * Comprehensive board management server for project managers and administrators.
 * Includes board CRUD, structure, members, permissions, and stages.
 */
class BoardManagerServer {

	/**
	 * Register board manager server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-board-manager',
			'fluentboards-manager',
			'mcp',
			'FluentBoards Board Manager',
			'Complete board management for project managers - boards, members, stages, permissions, labels',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_board_manager_abilities(),
			[],
			$this->get_board_manager_prompts()
		);
	}

	/**
	 * Get board manager abilities
	 *
	 * @return array Board manager ability names
	 */
	private function get_board_manager_abilities(): array {
		return array_merge(
			$this->get_board_management_abilities(),
			$this->get_member_management_abilities(),
			$this->get_stage_management_abilities(),
			$this->get_label_management_abilities()
		);
	}

	/**
	 * Get board management abilities
	 *
	 * @return array
	 */
	private function get_board_management_abilities(): array {
		return [
			// Core CRUD
			'fluentboards/create-board',
			'fluentboards/list-boards',
			'fluentboards/get-board',
			'fluentboards/update-board',
			'fluentboards/delete-board',

			// Board operations
			'fluentboards/duplicate-board',
			'fluentboards/archive-board',
			'fluentboards/restore-board',
			'fluentboards/pin-board',
			'fluentboards/unpin-board',
			'fluentboards/update-board-permissions',
		];
	}

	/**
	 * Get member management abilities
	 *
	 * @return array
	 */
	private function get_member_management_abilities(): array {
		return [
			'fluentboards/get-board-users',
			'fluentboards/add-board-member',
			'fluentboards/remove-board-member',
			'fluentboards/update-member-role',
			'fluentboards/bulk-add-members',
		];
	}

	/**
	 * Get stage management abilities
	 *
	 * @return array
	 */
	private function get_stage_management_abilities(): array {
		return [
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
		];
	}

	/**
	 * Get label management abilities
	 *
	 * @return array
	 */
	private function get_label_management_abilities(): array {
		return [
			'fluentboards/create-label',
			'fluentboards/list-labels',
			'fluentboards/update-label',
			'fluentboards/delete-label',
		];
	}

	/**
	 * Get board manager prompts
	 *
	 * @return array
	 */
	private function get_board_manager_prompts(): array {
		return [
			'fluentboards/project-overview',
			'fluentboards/analyze-workflow',
		];
	}
}
