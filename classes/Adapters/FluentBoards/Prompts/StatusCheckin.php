<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Prompts;

/**
 * FluentBoards Status Check-in Prompt
 *
 * Generate status check-in reports for daily standups, team meetings, and progress reviews
 * based on FluentBoards activity and task updates.
 */
class StatusCheckin {

	/**
	 * Constructor - Register the prompt as an ability
	 */
	public function __construct() {
		// Only register if FluentBoards is active
		if ( ! $this->is_fluent_boards_active() ) {
			return;
		}

		$this->register_prompt();
	}

	/**
	 * Register the prompt as a WordPress ability
	 */
	private function register_prompt(): void {
		wp_register_ability('fluentboards_status_checkin', [
			'label'               => 'FluentBoards Status Check-in',
			'description'         => 'Generate status check-in reports for daily standups, team meetings, and progress reviews based on FluentBoards activity and task updates.',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'meeting_type'     => [
						'type'        => 'string',
						'description' => 'Type of check-in: daily_standup, weekly_review, sprint_review, retrospective, or custom',
						'enum'        => [ 'daily_standup', 'weekly_review', 'sprint_review', 'retrospective', 'custom' ],
						'default'     => 'daily_standup',
					],
					'period'           => [
						'type'        => 'string',
						'description' => 'Time period to report on (e.g., "since yesterday", "last week", "sprint 5", "2024-01-15 to 2024-01-22")',
						'default'     => 'since yesterday',
					],
					'team_members'     => [
						'type'        => 'string',
						'description' => 'Specific team members to include in report (comma-separated names or IDs)',
						'default'     => 'all active members',
					],
					'board_ids'        => [
						'type'        => 'string',
						'description' => 'Specific board IDs to report on (comma-separated)',
						'default'     => 'all active boards',
					],
					'include_blockers' => [
						'type'        => 'boolean',
						'description' => 'Include detailed blocker analysis',
						'default'     => true,
					],
				],
			],
			'execute_callback'    => [ $this, 'execute_prompt' ],
			'permission_callback' => [ $this, 'can_access_reports' ],
			'meta'                => [
				'type'        => 'prompt',
				'category'    => 'fluentboards',
				'subcategory' => 'status',
			],
		]);
	}

	/**
	 * Execute the status check-in prompt
	 *
	 * @param array $args Prompt arguments
	 * @return array Response data
	 */
	public function execute_prompt( array $args ): array {
		$meeting_type     = $args['meeting_type'] ?? 'daily_standup';
		$period           = $args['period'] ?? 'since yesterday';
		$team_members     = $args['team_members'] ?? 'all active members';
		$board_ids        = $args['board_ids'] ?? 'all active boards';
		$include_blockers = $args['include_blockers'] ?? true;

		$prompt_content = "Generate a FluentBoards status check-in report with these parameters:
- Meeting Type: {$meeting_type}
- Period: {$period}
- Team Members: {$team_members}
- Boards: {$board_ids}
- Include Blockers: " . ( $include_blockers ? 'Yes' : 'No' ) . '

Please provide a concise status update covering:

## What Was Completed
- Tasks moved to "Done" or completed status
- Deliverables finished and milestones achieved
- Key accomplishments and progress highlights
- Issues resolved and problems solved

## Current Work in Progress
- Active tasks currently being worked on
- Tasks in "In Progress" or active stages
- Current priorities and focus areas
- Expected completion timelines

## Coming Up Next
- Tasks ready to start or planned for immediate future
- Upcoming deadlines and key milestones
- Scheduled meetings and important events
- Dependencies waiting to be unblocked

## Blockers and Issues
- Tasks stuck or unable to progress
- Dependencies blocking forward movement
- Resource constraints or availability issues
- Technical problems requiring resolution
- Decisions needed from stakeholders

## Action Items and Follow-ups
- Specific tasks requiring immediate attention
- Follow-up items from previous meetings
- Decisions pending and who needs to make them
- Support needed from other teams or stakeholders

Focus on providing clear, actionable information that facilitates productive team communication and keeps everyone aligned on priorities and progress. Keep individual updates concise but comprehensive enough for team coordination.';

		return [
			'success'    => true,
			'prompt'     => $prompt_content,
			'parameters' => $args,
		];
	}

	/**
	 * Check if user can access FluentBoards reports
	 *
	 * @return bool True if user has permission
	 */
	public function can_access_reports(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if user can manage FluentBoards or view reports
		return current_user_can( 'manage_options' ) ||
				current_user_can( 'fluent_boards_admin' ) || // phpcs:ignore WordPress.WP.Capabilities.Unknown
				current_user_can( 'fluent_boards_view' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Check if FluentBoards plugin is active
	 *
	 * @return bool True if FluentBoards is active
	 */
	private function is_fluent_boards_active(): bool {
		return defined( 'FLUENT_BOARDS' ) &&
				class_exists( '\FluentBoards\App\Models\Board' ) &&
				( is_plugin_active( 'fluent-boards/fluent-boards.php' ) ||
				is_plugin_active( 'fluent-boards-pro/fluent-boards-pro.php' ) ||
				function_exists( 'fluent_boards' ) );
	}
}
