<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * Centralized registry of FluentBoards abilities organized by category
 *
 * Single source of truth for all ability definitions, eliminating duplication
 * across multiple server classes.
 */
class AbilityRegistry {

	/**
	 * Get board management abilities
	 *
	 * @return array
	 */
	public static function get_board_abilities(): array {
		return [
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
		];
	}

	/**
	 * Get board member management abilities
	 *
	 * @return array
	 */
	public static function get_board_member_abilities(): array {
		return [
			'fluentboards/get-board-users',
			'fluentboards/add-board-member',
			'fluentboards/remove-board-member',
			'fluentboards/update-member-role',
			'fluentboards/bulk-add-members',
		];
	}

	/**
	 * Get task management abilities
	 *
	 * @return array
	 */
	public static function get_task_abilities(): array {
		return [
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
		];
	}

	/**
	 * Get stage management abilities
	 *
	 * @return array
	 */
	public static function get_stage_abilities(): array {
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
	 * Get comment management abilities
	 *
	 * @return array
	 */
	public static function get_comment_abilities(): array {
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
	 * Get label management abilities
	 *
	 * @return array
	 */
	public static function get_label_abilities(): array {
		return [
			'fluentboards/create-label',
			'fluentboards/list-labels',
			'fluentboards/update-label',
			'fluentboards/delete-label',
			'fluentboards/add-label-to-task',
			'fluentboards/remove-label-from-task',
			'fluentboards/get-task-labels',
		];
	}

	/**
	 * Get attachment management abilities
	 *
	 * @return array
	 */
	public static function get_attachment_abilities(): array {
		return [
			'fluentboards/add-task-attachment',
			'fluentboards/get-task-attachments',
			'fluentboards/get-attachment-files',
			'fluentboards/update-attachment',
			'fluentboards/delete-attachment',
		];
	}

	/**
	 * Get user and activity abilities
	 *
	 * @return array
	 */
	public static function get_user_abilities(): array {
		return [
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
		];
	}

	/**
	 * Get reporting and analytics abilities
	 *
	 * @return array
	 */
	public static function get_reporting_abilities(): array {
		return [
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
		];
	}

	/**
	 * Get test abilities for development
	 *
	 * @return array
	 */
	public static function get_test_abilities(): array {
		return [
			'fluentboards/test-verbose-enum',
			'fluentboards/test-pattern-enum',
			'fluentboards/generate-planet-verbose',
			'fluentboards/generate-planet-pattern',
		];
	}

	/**
	 * Get activity monitoring abilities
	 *
	 * @return array
	 */
	public static function get_activity_abilities(): array {
		return [
			'fluentboards/get-user-activities',
			'fluentboards/get-board-activities',
			'fluentboards/get-activity-timeline',
		];
	}
}
