<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Prompts;

/**
 * FluentBoards Team Productivity Prompt
 *
 * Analyze team productivity metrics, individual performance, and collaboration patterns
 * across FluentBoards to optimize team effectiveness and resource allocation.
 */
class TeamProductivity {

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
		wp_register_ability(
			'fluentboards_team_productivity',
			[
				'label'               => 'FluentBoards Team Productivity Analysis',
				'description'         => 'Analyze team productivity metrics, individual performance, and collaboration patterns across FluentBoards to optimize team effectiveness and resource allocation.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'analysis_focus'  => [
							'type'        => 'string',
							'description' => 'Focus area: individual_performance, team_dynamics, collaboration_patterns, workload_balance, or comprehensive',
							'enum'        => [ 'individual_performance', 'team_dynamics', 'collaboration_patterns', 'workload_balance', 'comprehensive' ],
							'default'     => 'comprehensive',
						],
						'timeframe'       => [
							'type'        => 'string',
							'description' => 'Time period to analyze (e.g., "this week", "last month", "Q1 2024", "last 30 days")',
							'default'     => 'last 30 days',
						],
						'team_members'    => [
							'type'        => 'string',
							'description' => 'Specific team members to analyze (comma-separated names or IDs, or "all")',
							'default'     => 'all',
						],
						'board_types'     => [
							'type'        => 'string',
							'description' => 'Board types to include: kanban, to-do, roadmap, or all',
							'enum'        => [ 'kanban', 'to-do', 'roadmap', 'all' ],
							'default'     => 'all',
						],
						'compare_periods' => [
							'type'        => 'boolean',
							'description' => 'Compare with previous period for trend analysis',
							'default'     => true,
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_prompt' ],
				'permission_callback' => [ $this, 'can_access_reports' ],
				'meta'                => [
					'type'        => 'prompt',
					'category'    => 'fluentboards',
					'subcategory' => 'analytics',
				],
			]
		);
	}

	/**
	 * Execute the team productivity prompt
	 *
	 * @param array $args Prompt arguments
	 * @return array Response data
	 */
	public function execute_prompt( array $args ): array {
		$analysis_focus  = $args['analysis_focus'] ?? 'comprehensive';
		$timeframe       = $args['timeframe'] ?? 'last 30 days';
		$team_members    = $args['team_members'] ?? 'all';
		$board_types     = $args['board_types'] ?? 'all';
		$compare_periods = $args['compare_periods'] ?? true;

		$prompt_content = "Analyze team productivity and performance in FluentBoards with these parameters:
- Analysis Focus: {$analysis_focus}
- Timeframe: {$timeframe}
- Team Members: {$team_members}
- Board Types: {$board_types}
- Compare Periods: " . ( $compare_periods ? 'Yes' : 'No' ) . '

Please provide a comprehensive team productivity analysis covering:

## Individual Performance Metrics
- Task completion rates and velocity per team member
- Average time to complete tasks by complexity and type
- Quality indicators (rework rates, bug reports, feedback scores)
- Contribution patterns and consistency over time
- Skill development and capability growth

## Team Collaboration Analysis
- Cross-team communication and knowledge sharing
- Task handoff efficiency and collaboration patterns
- Comment activity and feedback quality
- Mentoring relationships and knowledge transfer
- Team cohesion and support patterns

## Workload Distribution
- Task assignment balance across team members
- Workload equity and capacity utilization
- Burnout risk indicators and stress patterns
- Skill-based task allocation effectiveness
- Overflow management and support patterns

## Productivity Trends
- Team velocity changes over time
- Seasonal patterns and productivity cycles
- Project type impact on team performance
- Tool adoption and workflow optimization
- Learning curve analysis for new team members

## Risk Assessment
- Burnout indicators and stress level monitoring
- Skill dependency risks and knowledge silos
- Team member flight risk and retention factors
- Project delivery risk assessment
- Resource constraint impact on team performance

## Optimization Recommendations
- Individual coaching and development opportunities
- Team structure and role optimization
- Workflow and process improvement suggestions
- Tool and technology recommendations
- Training and skill development priorities
- Resource allocation adjustments
- Communication and collaboration enhancements

Focus on providing actionable insights that help optimize team performance, improve individual satisfaction and growth, and enhance overall productivity while maintaining work-life balance and team cohesion.';

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
