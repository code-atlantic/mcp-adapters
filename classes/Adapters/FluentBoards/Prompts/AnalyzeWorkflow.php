<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Prompts;

/**
 * FluentBoards Workflow Analysis Prompt
 *
 * Analyzes FluentBoards project workflow, team productivity metrics,
 * and provides optimization recommendations for project management.
 */
class AnalyzeWorkflow {

    /**
     * Constructor - Register the prompt as an ability
     */
    public function __construct() {
        // Only register if FluentBoards is active
        if (!$this->is_fluent_boards_active()) {
            return;
        }

        $this->register_prompt();
    }

    /**
     * Register the prompt as a WordPress ability
     */
    private function register_prompt(): void {
        wp_register_ability('fluentboards_analyze_workflow', [
            'label' => 'FluentBoards Workflow Analysis',
            'description' => 'Analyze FluentBoards project workflow, team productivity metrics, and provide optimization recommendations for project management.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_ids' => [
                        'type' => 'string',
                        'description' => 'Specific board IDs to analyze (comma-separated). If empty, analyzes all accessible boards.',
                    ],
                    'timeframe' => [
                        'type' => 'string',
                        'description' => 'Time period for analysis (e.g., "last 7 days", "this month", "last quarter", "2024-01-01 to 2024-01-31")',
                        'default' => 'last 30 days',
                    ],
                    'focus_area' => [
                        'type' => 'string',
                        'description' => 'Specific area to focus on: bottlenecks, velocity, team_performance, stage_efficiency, or general',
                        'enum' => ['bottlenecks', 'velocity', 'team_performance', 'stage_efficiency', 'general'],
                        'default' => 'general',
                    ],
                    'team_members' => [
                        'type' => 'string',
                        'description' => 'Specific team member IDs or usernames to analyze (comma-separated)',
                    ],
                    'include_recommendations' => [
                        'type' => 'boolean',
                        'description' => 'Whether to include optimization recommendations',
                        'default' => true,
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_prompt'],
            'permission_callback' => [$this, 'can_access_reports'],
            'meta' => [
                'type' => 'prompt',
                'category' => 'fluentboards',
                'subcategory' => 'analysis',
            ],
        ]);
    }

    /**
     * Execute the workflow analysis prompt
     *
     * @param array $args Prompt arguments
     * @return array Response data
     */
    public function execute_prompt(array $args): array {
        $board_ids = $args['board_ids'] ?? '';
        $timeframe = $args['timeframe'] ?? 'last 30 days';
        $focus_area = $args['focus_area'] ?? 'general';
        $team_members = $args['team_members'] ?? '';
        $include_recommendations = $args['include_recommendations'] ?? true;

        $prompt_content = "Analyze FluentBoards project workflow and team productivity with these parameters:
- Boards: " . ($board_ids ? $board_ids : 'All accessible boards') . "
- Timeframe: {$timeframe}
- Focus Area: {$focus_area}
- Team Members: " . ($team_members ? $team_members : 'All team members') . "
- Include Recommendations: " . ($include_recommendations ? 'Yes' : 'No') . "

Please provide a comprehensive analysis covering:

## Project Overview
- Board structure and organization
- Active projects and their current status
- Team member involvement and responsibilities
- Overall project health indicators

## Workflow Analysis
- Task progression through stages (To Do → In Progress → Done)
- Stage bottlenecks and flow efficiency
- Task completion rates and velocity
- Average time spent in each stage

## Productivity Metrics
- Tasks created vs completed over time
- Team member contribution patterns
- Work distribution across board types (Kanban, To-Do, Roadmap)
- Peak productivity periods and patterns

## Collaboration Insights
- Comment activity and team communication
- Task handoffs between team members
- Label usage for categorization and priority
- Cross-board collaboration patterns

## Optimization Recommendations
- Workflow improvements for better efficiency
- Stage configuration optimizations
- Team allocation and workload balancing
- Process automation opportunities
- Best practices for project management

Focus on actionable insights that can improve team productivity, project delivery times, and overall workflow efficiency while maintaining quality and team satisfaction.";

        return [
            'success' => true,
            'prompt' => $prompt_content,
            'parameters' => $args,
        ];
    }

    /**
     * Check if user can access FluentBoards reports
     *
     * @return bool True if user has permission
     */
    public function can_access_reports(): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        // Check if user can manage FluentBoards or view reports
        return current_user_can('manage_options') ||
               current_user_can('fluent_boards_admin') ||
               current_user_can('fluent_boards_view');
    }

    /**
     * Check if FluentBoards plugin is active
     *
     * @return bool True if FluentBoards is active
     */
    private function is_fluent_boards_active(): bool {
        return defined('FLUENT_BOARDS') &&
               class_exists('\FluentBoards\App\Models\Board') &&
               (is_plugin_active('fluent-boards/fluent-boards.php') ||
                is_plugin_active('fluent-boards-pro/fluent-boards-pro.php') ||
                function_exists('fluent_boards'));
    }
}