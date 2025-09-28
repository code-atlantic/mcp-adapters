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
			'1.0.0',
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
			'fluentboards_add_board_member',
			'fluentboards_add_comment',
			'fluentboards_add_label_to_task',
			'fluentboards_add_task_attachment',
			'fluentboards_archive_all_tasks',
			'fluentboards_archive_board',
			'fluentboards_archive_task',
			'fluentboards_assign_yourself_to_task',
			'fluentboards_bulk_add_members',
			'fluentboards_bulk_set_super_admins',
			'fluentboards_change_task_status',
			'fluentboards_clone_task',
			'fluentboards_create_board',
			'fluentboards_create_label',
			'fluentboards_create_stage',
			'fluentboards_create_task',
			'fluentboards_delete_attachment',
			'fluentboards_delete_board',
			'fluentboards_delete_comment',
			'fluentboards_delete_label',
			'fluentboards_delete_reply',
			'fluentboards_delete_stage',
			'fluentboards_delete_task',
			'fluentboards_detach_yourself_from_task',
			'fluentboards_duplicate_board',
			'fluentboards_get_activity_timeline',
			'fluentboards_get_all_board_reports',
			'fluentboards_get_all_users',
			'fluentboards_get_archived_stages',
			'fluentboards_get_attachment_files',
			'fluentboards_get_board_activities',
			'fluentboards_get_board_report',
			'fluentboards_get_board_users',
			'fluentboards_get_board',
			'fluentboards_get_comments',
			'fluentboards_get_dashboard_stats',
			'fluentboards_get_member_reports',
			'fluentboards_get_project_reports',
			'fluentboards_get_stage_positions',
			'fluentboards_get_stage_wise_reports',
			'fluentboards_get_task_attachments',
			'fluentboards_get_task_labels',
			'fluentboards_get_task',
			'fluentboards_get_team_workload',
			'fluentboards_get_timesheet_by_tasks',
			'fluentboards_get_timesheet_by_users',
			'fluentboards_get_timesheet_reports',
			'fluentboards_get_user_activities',
			'fluentboards_get_user_boards',
			'fluentboards_get_user_info',
			'fluentboards_get_user_tasks',
			'fluentboards_list_boards',
			'fluentboards_list_labels',
			'fluentboards_list_stages',
			'fluentboards_list_tasks',
			'fluentboards_move_all_tasks',
			'fluentboards_move_task',
			'fluentboards_pin_board',
			'fluentboards_remove_board_member',
			'fluentboards_remove_label_from_task',
			'fluentboards_remove_super_admin',
			'fluentboards_reorder_stages',
			'fluentboards_restore_board',
			'fluentboards_restore_stage',
			'fluentboards_restore_task',
			'fluentboards_search_users',
			'fluentboards_set_super_admin',
			'fluentboards_sort_stage_tasks',
			'fluentboards_unpin_board',
			'fluentboards_update_attachment',
			'fluentboards_update_board_permissions',
			'fluentboards_update_board',
			'fluentboards_update_comment_privacy',
			'fluentboards_update_comment',
			'fluentboards_update_label',
			'fluentboards_update_member_role',
			'fluentboards_update_reply',
			'fluentboards_update_stage',
			'fluentboards_update_task_dates',
			'fluentboards_update_task',
			'fluentboards_upload_attachment_file',
		];
	}

}
