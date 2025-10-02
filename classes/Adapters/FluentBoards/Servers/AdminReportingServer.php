<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards Admin Reporting MCP Server
 *
 * Analytics and reporting server for administrators and stakeholders.
 * Provides comprehensive insights, metrics, and admin operations.
 */
class AdminReportingServer {

	/**
	 * Register admin reporting server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-admin-reporting',
			'fluentboards-admin',
			'mcp',
			'FluentBoards Admin & Reporting',
			'Analytics, reporting, and admin operations - dashboards, metrics, user management, workload analysis',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_admin_reporting_abilities(),
			[],
			$this->get_admin_reporting_prompts()
		);
	}

	/**
	 * Get admin reporting abilities
	 *
	 * @return array Admin reporting ability names
	 */
	private function get_admin_reporting_abilities(): array {
		return array_merge(
			$this->get_dashboard_abilities(),
			$this->get_reporting_abilities(),
			$this->get_user_management_abilities(),
			$this->get_activity_monitoring_abilities()
		);
	}

	/**
	 * Get dashboard abilities
	 *
	 * @return array
	 */
	private function get_dashboard_abilities(): array {
		return [
			'fluentboards/get-dashboard-stats',
			'fluentboards/get-board-report',
			'fluentboards/get-all-board-reports',
		];
	}

	/**
	 * Get reporting abilities
	 *
	 * @return array
	 */
	private function get_reporting_abilities(): array {
		return [
			// Project reporting
			'fluentboards/get-project-reports',
			'fluentboards/get-member-reports',
			'fluentboards/get-stage-wise-reports',

			// Workload analysis
			'fluentboards/get-team-workload',
			'fluentboards/get-timesheet-reports',
			'fluentboards/get-timesheet-by-users',
			'fluentboards/get-timesheet-by-tasks',
		];
	}

	/**
	 * Get user management abilities
	 *
	 * @return array
	 */
	private function get_user_management_abilities(): array {
		return [
			// User operations
			'fluentboards/get-all-users',
			'fluentboards/search-users',
			'fluentboards/get-user-info',
			'fluentboards/get-user-boards',
			'fluentboards/get-user-tasks',

			// Admin permissions
			'fluentboards/set-super-admin',
			'fluentboards/remove-super-admin',
			'fluentboards/bulk-set-super-admins',
		];
	}

	/**
	 * Get activity monitoring abilities
	 *
	 * @return array
	 */
	private function get_activity_monitoring_abilities(): array {
		return [
			'fluentboards/get-user-activities',
			'fluentboards/get-board-activities',
			'fluentboards/get-activity-timeline',
		];
	}

	/**
	 * Get admin reporting prompts
	 *
	 * @return array
	 */
	private function get_admin_reporting_prompts(): array {
		return [
			'fluentboards/project-overview',
			'fluentboards/team-productivity',
			'fluentboards/analyze-workflow',
		];
	}
}
