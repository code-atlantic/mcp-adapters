<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Prompts;

/**
 * FluentBoards Project Overview Prompt
 *
 * Provides comprehensive project overview reports across all FluentBoards,
 * delivering executive-level insights into project portfolio health.
 */
class ProjectOverview {

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
			'fluentboards/project-overview',
			[
				'label'               => 'FluentBoards Project Overview',
				'description'         => 'Generate comprehensive project overview reports across all FluentBoards, providing executive-level insights into project portfolio health, resource allocation, and strategic priorities.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'report_type'       => [
							'type'        => 'string',
							'description' => 'Type of overview: executive_summary, detailed_status, roadmap_review, resource_analysis, or comprehensive',
							'enum'        => [ 'executive_summary', 'detailed_status', 'roadmap_review', 'resource_analysis', 'comprehensive' ],
							'default'     => 'comprehensive',
						],
						'timeframe'         => [
							'type'        => 'string',
							'description' => 'Period to analyze (e.g., "this quarter", "last month", "2024 YTD", "next 90 days")',
							'default'     => 'this quarter',
						],
						'board_types'       => [
							'type'        => 'string',
							'description' => 'Board types to include: kanban, to-do, roadmap, or all (comma-separated)',
							'default'     => 'all',
						],
						'priority_filter'   => [
							'type'        => 'string',
							'description' => 'Priority levels to focus on: urgent, high, medium, low, or all',
							'enum'        => [ 'urgent', 'high', 'medium', 'low', 'all' ],
							'default'     => 'all',
						],
						'include_forecasts' => [
							'type'        => 'boolean',
							'description' => 'Include predictive analytics and forecasts',
							'default'     => true,
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_prompt' ],
				'permission_callback' => [ $this, 'can_access_reports' ],
				'meta'                => [
					'type'        => 'prompt',
					'category'    => 'fluentboards',
					'subcategory' => 'reports',
				],
			]
		);
	}

	/**
	 * Execute the project overview prompt
	 *
	 * @param array $args Prompt arguments
	 * @return array Response data
	 */
	public function execute_prompt( array $args ): array {
		$report_type       = $args['report_type'] ?? 'comprehensive';
		$timeframe         = $args['timeframe'] ?? 'this quarter';
		$board_types       = $args['board_types'] ?? 'all';
		$priority_filter   = $args['priority_filter'] ?? 'all';
		$include_forecasts = $args['include_forecasts'] ?? true;

		$prompt_content = "Generate a comprehensive FluentBoards project overview with these parameters:
- Report Type: {$report_type}
- Timeframe: {$timeframe}
- Board Types: {$board_types}
- Priority Filter: {$priority_filter}
- Include Forecasts: " . ( $include_forecasts ? 'Yes' : 'No' ) . '

Please provide a strategic project portfolio analysis covering:

## Portfolio Summary
- Total active projects and their current phases
- Overall portfolio health score and key indicators
- Resource allocation across projects and teams
- Project priority matrix and strategic alignment

## Project Status Dashboard
- Projects by status (Planning, Active, On Hold, Completed)
- Critical path projects and dependencies
- Projects at risk or behind schedule
- Recent completions and upcoming milestones

## Resource Allocation Analysis
- Team capacity utilization across projects
- Workload distribution and potential bottlenecks
- Skill allocation and expertise deployment
- Resource conflicts and optimization opportunities

## Performance Metrics
- Portfolio velocity and throughput trends
- Project completion rates and success metrics
- Budget/timeline adherence across projects
- Quality indicators and deliverable outcomes

## Team Performance Overview
- Cross-project collaboration patterns
- Individual contributor impact and growth
- Communication effectiveness and knowledge sharing
- Training needs and skill gap identification

## Forecasts and Predictions
- Project completion timeline predictions
- Resource requirement forecasting
- Risk probability assessments
- Capacity planning recommendations for next quarter

## Strategic Recommendations
- Portfolio optimization opportunities
- Resource reallocation suggestions
- Process improvement initiatives
- Technology and tooling enhancement recommendations

Focus on providing actionable insights for strategic decision-making, resource planning, and portfolio optimization that align with business objectives and maximize team effectiveness.';

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
