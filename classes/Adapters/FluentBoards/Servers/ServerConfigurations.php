<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards MCP Server Configurations
 *
 * Centralized configuration for all FluentBoards MCP servers.
 * Each server is defined by ability groups and prompts rather than duplicate code.
 */
class ServerConfigurations {

	/**
	 * Get all server configurations
	 *
	 * @return array Array of server configurations
	 */
	public static function get_all(): array {
		return [
			'full'            => self::get_full_server_config(),
			'board-crud'      => self::get_board_crud_config(),
			'board-manager'   => self::get_board_manager_config(),
			'task-manager'    => self::get_task_manager_config(),
			'task-worker'     => self::get_task_worker_config(),
			'admin-reporting' => self::get_admin_reporting_config(),
		];
	}

	/**
	 * Full FluentBoards server - all abilities
	 *
	 * @return array
	 */
	private static function get_full_server_config(): array {
		return [
			'server_id'       => 'fluentboards-full',
			'route_namespace' => 'fluentboards',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Complete',
			'description'     => 'Complete FluentBoards project management with all features - boards, tasks, comments, attachments, reporting',
			'ability_groups'  => [
				'board',
				'board_member',
				'task',
				'stage',
				'comment',
				'label',
				'attachment',
				'user',
				'reporting',
				'test',
			],
			'prompts'         => [
				'fluentboards/project-overview',
				'fluentboards/analyze-workflow',
				'fluentboards/status-checkin',
				'fluentboards/team-productivity',
			],
		];
	}

	/**
	 * Board CRUD server - board management only
	 *
	 * @return array
	 */
	private static function get_board_crud_config(): array {
		return [
			'server_id'       => 'fluentboards-board-crud',
			'route_namespace' => 'fluentboards-board-crud',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Board CRUD',
			'description'     => 'Board management operations only - create, read, update, delete, pin, archive, duplicate boards',
			'ability_groups'  => [ 'board' ],
			'prompts'         => [],
		];
	}

	/**
	 * Board Manager server - for project managers
	 *
	 * @return array
	 */
	private static function get_board_manager_config(): array {
		return [
			'server_id'       => 'fluentboards-board-manager',
			'route_namespace' => 'fluentboards-manager',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Board Manager',
			'description'     => 'Complete board management for project managers - boards, members, stages, permissions, labels',
			'ability_groups'  => [
				'board',
				'board_member',
				'stage',
				'label',
			],
			'prompts'         => [
				'fluentboards/project-overview',
				'fluentboards/analyze-workflow',
			],
		];
	}

	/**
	 * Task Manager server - for coordinators
	 *
	 * @return array
	 */
	private static function get_task_manager_config(): array {
		return [
			'server_id'       => 'fluentboards-task-manager',
			'route_namespace' => 'fluentboards-tasks',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Task Manager',
			'description'     => 'Comprehensive task management for coordinators - create, assign, organize, comment, attach files',
			'ability_groups'  => [
				'task',
				'label',
				'comment',
				'attachment',
			],
			'prompts'         => [
				'fluentboards/status-checkin',
				'fluentboards/analyze-workflow',
			],
		];
	}

	/**
	 * Task Worker server - for individual contributors
	 *
	 * @return array
	 */
	private static function get_task_worker_config(): array {
		return [
			'server_id'       => 'fluentboards-task-worker',
			'route_namespace' => 'fluentboards-worker',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Task Worker',
			'description'     => 'Streamlined task operations for individual contributors - view, update, comment on assigned tasks',
			'ability_groups'  => [
				'task',
				'comment',
				'attachment',
				'activity',
			],
			'prompts'         => [
				'fluentboards/status-checkin',
			],
		];
	}

	/**
	 * Admin Reporting server - for administrators
	 *
	 * @return array
	 */
	private static function get_admin_reporting_config(): array {
		return [
			'server_id'       => 'fluentboards-admin-reporting',
			'route_namespace' => 'fluentboards-admin',
			'route'           => 'mcp',
			'name'            => 'FluentBoards Admin & Reporting',
			'description'     => 'Analytics, reporting, and admin operations - dashboards, metrics, user management, workload analysis',
			'ability_groups'  => [
				'reporting',
				'user',
				'activity',
			],
			'prompts'         => [
				'fluentboards/project-overview',
				'fluentboards/team-productivity',
				'fluentboards/analyze-workflow',
			],
		];
	}
}
