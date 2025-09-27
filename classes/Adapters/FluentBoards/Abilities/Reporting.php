<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Reporting Abilities
 *
 * Registers WordPress abilities for FluentBoards reporting and analytics operations
 * using the new WordPress Abilities API pattern.
 */
class Reporting extends BaseAbility {

    /**
     * Register all reporting-related abilities
     */
    protected function register_abilities(): void {
        $this->register_get_project_reports();
        $this->register_get_timesheet_reports();
        $this->register_get_stage_wise_reports();
        $this->register_get_dashboard_stats();
        $this->register_get_board_activities();
        $this->register_get_member_reports();
        $this->register_get_all_board_reports();
        $this->register_get_board_report();
        $this->register_get_timesheet_by_tasks();
        $this->register_get_timesheet_by_users();
        $this->register_get_team_workload();
        $this->register_get_activity_timeline();
    }

    /**
     * Register get project reports ability
     */
    private function register_get_project_reports(): void {
        wp_register_ability('fluentboards_get_project_reports', [
            'label' => 'Get FluentBoards project reports',
            'description' => 'Get comprehensive project reports and analytics',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_ids' => [
                        'type' => 'array',
                        'description' => 'Array of board IDs to include in report',
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'Start date for report (YYYY-MM-DD format)',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'End date for report (YYYY-MM-DD format)',
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_project_reports'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get timesheet reports ability
     */
    private function register_get_timesheet_reports(): void {
        wp_register_ability('fluentboards_get_timesheet_reports', [
            'label' => 'Get FluentBoards timesheet reports',
            'description' => 'Get time tracking and timesheet reports',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Specific board ID for timesheet',
                    ],
                    'user_id' => [
                        'type' => 'integer',
                        'description' => 'Specific user ID for timesheet',
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'Start date for timesheet (YYYY-MM-DD format)',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'End date for timesheet (YYYY-MM-DD format)',
                    ],
                    'report_type' => [
                        'type' => 'string',
                        'description' => 'Type of timesheet report',
                        'enum' => ['by-tasks', 'by-users', 'summary'],
                        'default' => 'summary',
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_timesheet_reports'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get stage wise reports ability
     */
    private function register_get_stage_wise_reports(): void {
        wp_register_ability('fluentboards_get_stage_wise_reports', [
            'label' => 'Get FluentBoards stage-wise reports',
            'description' => 'Get stage-wise task distribution and progress reports',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for stage-wise reports',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_get_stage_wise_reports'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get dashboard stats ability
     */
    private function register_get_dashboard_stats(): void {
        wp_register_ability('fluentboards_get_dashboard_stats', [
            'label' => 'Get FluentBoards dashboard stats',
            'description' => 'Get dashboard statistics and key metrics',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'period' => [
                        'type' => 'string',
                        'description' => 'Time period for dashboard stats',
                        'enum' => ['today', 'week', 'month', 'quarter', 'year'],
                        'default' => 'month',
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_dashboard_stats'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get board activities ability
     */
    private function register_get_board_activities(): void {
        wp_register_ability('fluentboards_get_board_activities', [
            'label' => 'Get FluentBoards board activities',
            'description' => 'Get recent activities and changes for a board',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for activities',
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page number for pagination',
                        'default' => 1,
                        'minimum' => 1,
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of activities per page',
                        'default' => 20,
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_get_board_activities'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get member reports ability
     */
    private function register_get_member_reports(): void {
        wp_register_ability('fluentboards_get_member_reports', [
            'label' => 'Get FluentBoards member reports',
            'description' => 'Get comprehensive member performance and activity reports',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'user_id' => [
                        'type' => 'integer',
                        'description' => 'User ID for detailed member report',
                    ],
                    'board_ids' => [
                        'type' => 'array',
                        'description' => 'Array of board IDs to include in report',
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'Start date for report period',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'End date for report period',
                    ],
                    'report_type' => [
                        'type' => 'string',
                        'description' => 'Type of member report',
                        'enum' => ['tasks', 'activities', 'projects', 'comprehensive'],
                        'default' => 'comprehensive',
                    ],
                ],
                'required' => ['user_id'],
            ],
            'execute_callback' => [$this, 'execute_get_member_reports'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get all board reports ability
     */
    private function register_get_all_board_reports(): void {
        wp_register_ability('fluentboards_get_all_board_reports', [
            'label' => 'Get all FluentBoards board reports',
            'description' => 'Get reports for all accessible boards',
            'input_schema' => [
                'type' => 'object',
            ],
            'execute_callback' => [$this, 'execute_get_all_board_reports'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get board report ability
     */
    private function register_get_board_report(): void {
        wp_register_ability('fluentboards_get_board_report', [
            'label' => 'Get FluentBoards board report',
            'description' => 'Get detailed report for a specific board',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for detailed report',
                    ],
                ],
                'required' => ['board_id'],
            ],
            'execute_callback' => [$this, 'execute_get_board_report'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get timesheet by tasks ability (Pro required)
     */
    private function register_get_timesheet_by_tasks(): void {
        wp_register_ability('fluentboards_get_timesheet_by_tasks', [
            'label' => 'Get FluentBoards timesheet by tasks',
            'description' => 'Get timesheet report grouped by tasks (Pro required)',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for timesheet filter',
                    ],
                    'date_range' => [
                        'type' => 'array',
                        'description' => 'Date range [start_date, end_date] in YYYY-MM-DD format',
                        'items' => [
                            'type' => 'string',
                            'format' => 'date',
                        ],
                        'minItems' => 2,
                        'maxItems' => 2,
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_timesheet_by_tasks'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
                'pro_required' => true,
            ],
        ]);
    }

    /**
     * Register get timesheet by users ability (Pro required)
     */
    private function register_get_timesheet_by_users(): void {
        wp_register_ability('fluentboards_get_timesheet_by_users', [
            'label' => 'Get FluentBoards timesheet by users',
            'description' => 'Get timesheet report grouped by users (Pro required)',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for timesheet filter',
                    ],
                    'date_range' => [
                        'type' => 'array',
                        'description' => 'Date range [start_date, end_date] in YYYY-MM-DD format',
                        'items' => [
                            'type' => 'string',
                            'format' => 'date',
                        ],
                        'minItems' => 2,
                        'maxItems' => 2,
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_timesheet_by_users'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
                'pro_required' => true,
            ],
        ]);
    }

    /**
     * Register get team workload ability
     */
    private function register_get_team_workload(): void {
        wp_register_ability('fluentboards_get_team_workload', [
            'label' => 'Get FluentBoards team workload',
            'description' => 'Get team workload and capacity analysis',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for team workload analysis',
                    ],
                    'period' => [
                        'type' => 'string',
                        'description' => 'Time period for workload analysis',
                        'enum' => ['current', 'upcoming', 'overdue'],
                        'default' => 'current',
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_team_workload'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Register get activity timeline ability
     */
    private function register_get_activity_timeline(): void {
        wp_register_ability('fluentboards_get_activity_timeline', [
            'label' => 'Get FluentBoards activity timeline',
            'description' => 'Get activity timeline and project history',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'board_id' => [
                        'type' => 'integer',
                        'description' => 'Board ID for activity timeline',
                    ],
                    'user_id' => [
                        'type' => 'integer',
                        'description' => 'User ID to filter activities',
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'Start date for activity timeline',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'End date for activity timeline',
                    ],
                    'page' => [
                        'type' => 'integer',
                        'description' => 'Page number for pagination',
                        'default' => 1,
                        'minimum' => 1,
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'description' => 'Number of activities per page',
                        'default' => 50,
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                ],
            ],
            'execute_callback' => [$this, 'execute_get_activity_timeline'],
            'permission_callback' => [$this, 'can_view_boards'],
            'meta' => [
                'category' => 'fluentboards',
                'subcategory' => 'reporting',
            ],
        ]);
    }

    /**
     * Execute get project reports ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_project_reports(array $args): array {
        try {
            $board_ids = $args['board_ids'] ?? [];
            $date_from = $args['date_from'] ?? null;
            $date_to = $args['date_to'] ?? null;

            // Validate date formats
            if ($date_from && !$this->validate_date_format($date_from)) {
                return $this->get_error_response('Invalid date_from format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            if ($date_to && !$this->validate_date_format($date_to)) {
                return $this->get_error_response('Invalid date_to format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            $boardService = new \FluentBoards\App\Services\BoardService();
            $userId = get_current_user_id();

            // Get accessible board IDs
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            // Filter requested board IDs by accessible ones
            if (!empty($board_ids)) {
                $board_ids = array_intersect($board_ids, $accessibleBoardIds);
            } else {
                $board_ids = $accessibleBoardIds;
            }

            if (empty($board_ids)) {
                return $this->get_error_response('No accessible boards found', 'no_boards_found');
            }

            $reports = [];
            $totalStats = [
                'total_boards' => count($board_ids),
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'overdue_tasks' => 0,
                'high_priority_tasks' => 0,
            ];

            foreach ($board_ids as $board_id) {
                try {
                    $boardReport = $boardService->getBoardReports($board_id);
                    $board = \FluentBoards\App\Models\Board::find($board_id);

                    if ($board) {
                        $reports[] = [
                            'board_id' => $board_id,
                            'board_title' => $board->title,
                            'board_type' => $board->type,
                            'report' => $boardReport,
                        ];

                        // Aggregate stats
                        if (isset($boardReport['completion'])) {
                            $totalStats['total_tasks'] += $boardReport['completion']['total'] ?? 0;
                            $totalStats['completed_tasks'] += $boardReport['completion']['completed'] ?? 0;
                            $totalStats['overdue_tasks'] += $boardReport['completion']['overdue'] ?? 0;
                        }
                        if (isset($boardReport['priority']['high'])) {
                            $totalStats['high_priority_tasks'] += $boardReport['priority']['high'];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip boards that can't be processed
                    continue;
                }
            }

            // Calculate completion percentage
            $totalStats['completion_percentage'] = $totalStats['total_tasks'] > 0
                ? round(($totalStats['completed_tasks'] / $totalStats['total_tasks']) * 100, 2)
                : 0;

            return $this->get_success_response([
                'reports' => $reports,
                'total_stats' => $totalStats,
                'date_range' => [
                    'from' => $date_from,
                    'to' => $date_to,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Project reports retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get project reports: ' . $e->getMessage(), 'reports_failed');
        }
    }

    /**
     * Execute get timesheet reports ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_timesheet_reports(array $args): array {
        try {
            // Check if Pro features are available for time tracking
            if (!class_exists('\FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack')) {
                return $this->get_error_response('Time tracking requires FluentBoards Pro', 'pro_required');
            }

            $board_id = $args['board_id'] ?? null;
            $user_id = $args['user_id'] ?? null;
            $date_from = $args['date_from'] ?? null;
            $date_to = $args['date_to'] ?? null;
            $report_type = $args['report_type'] ?? 'summary';

            // Validate date formats
            if ($date_from && !$this->validate_date_format($date_from)) {
                return $this->get_error_response('Invalid date_from format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            if ($date_to && !$this->validate_date_format($date_to)) {
                return $this->get_error_response('Invalid date_to format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            $timeTrackModel = new \FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack();
            $userId = get_current_user_id();

            // Get accessible board IDs
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            // Build query
            $query = $timeTrackModel->where('status', 'commited');

            // Filter by board access
            if ($board_id) {
                if (!in_array($board_id, $accessibleBoardIds)) {
                    return $this->get_error_response('Access denied to board', 'access_denied');
                }
                $query->where('board_id', $board_id);
            } else {
                $query->whereIn('board_id', $accessibleBoardIds);
            }

            // Filter by user
            if ($user_id) {
                $query->where('created_by', $user_id);
            }

            // Filter by date range
            if ($date_from && $date_to) {
                $query->whereBetween('completed_at', [$date_from, $date_to]);
            }

            $timeTracks = $query->with(['task', 'user', 'board'])->get();

            $report = $this->format_timesheet_report($timeTracks, $report_type);

            return $this->get_success_response([
                'report' => $report,
                'report_type' => $report_type,
                'filters' => [
                    'board_id' => $board_id,
                    'user_id' => $user_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Timesheet report retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get timesheet reports: ' . $e->getMessage(), 'timesheet_failed');
        }
    }

    /**
     * Execute get stage wise reports ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_stage_wise_reports(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check board access
            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if (!in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $boardService = new \FluentBoards\App\Services\BoardService();
            $stages = $boardService->getStageWiseBoardReports($board_id);

            $board = \FluentBoards\App\Models\Board::find($board_id);

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'type' => $board->type,
                ],
                'stages' => $stages,
                'generated_at' => current_time('mysql'),
            ], 'Stage-wise reports retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get stage-wise reports: ' . $e->getMessage(), 'stage_reports_failed');
        }
    }

    /**
     * Execute get dashboard stats ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_dashboard_stats(array $args): array {
        try {
            $period = $args['period'] ?? 'month';
            $userId = get_current_user_id();

            // Calculate date range based on period
            $dateRange = $this->calculate_period_date_range($period);

            // Get accessible board IDs
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if (empty($accessibleBoardIds)) {
                return $this->get_error_response('No accessible boards found', 'no_boards_found');
            }

            // Get basic stats
            $taskModel = new \FluentBoards\App\Models\Task();
            $boardModel = new \FluentBoards\App\Models\Board();

            $totalBoards = $boardModel->whereIn('id', $accessibleBoardIds)
                                   ->whereNull('archived_at')
                                   ->count();

            $totalTasks = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                  ->whereNull('archived_at')
                                  ->count();

            $completedTasks = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                      ->whereNull('archived_at')
                                      ->where('status', 'closed')
                                      ->count();

            $overdueTasks = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                    ->whereNull('archived_at')
                                    ->where('status', 'open')
                                    ->where('due_at', '<', current_time('mysql'))
                                    ->whereNotNull('due_at')
                                    ->count();

            // Get period-specific stats
            $periodTasks = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                   ->whereNull('archived_at')
                                   ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                   ->count();

            $periodCompletedTasks = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                            ->whereNull('archived_at')
                                            ->where('status', 'closed')
                                            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
                                            ->count();

            // Get priority distribution
            $priorityStats = $taskModel->whereIn('board_id', $accessibleBoardIds)
                                     ->whereNull('archived_at')
                                     ->where('status', 'open')
                                     ->selectRaw('priority, COUNT(*) as count')
                                     ->groupBy('priority')
                                     ->pluck('count', 'priority')
                                     ->toArray();

            // Get recent activities count
            $recentActivities = 0;
            if (class_exists('\FluentBoards\App\Models\Activity')) {
                $recentActivities = \FluentBoards\App\Models\Activity::whereIn('object_id', $accessibleBoardIds)
                                                                   ->where('object_type', 'board')
                                                                   ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                                                                   ->count();
            }

            $stats = [
                'overview' => [
                    'total_boards' => $totalBoards,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'overdue_tasks' => $overdueTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                ],
                'period_stats' => [
                    'period' => $period,
                    'date_range' => $dateRange,
                    'new_tasks' => $periodTasks,
                    'completed_tasks' => $periodCompletedTasks,
                    'recent_activities' => $recentActivities,
                ],
                'priority_distribution' => [
                    'high' => $priorityStats['high'] ?? 0,
                    'medium' => $priorityStats['medium'] ?? 0,
                    'low' => $priorityStats['low'] ?? 0,
                    'normal' => $priorityStats['normal'] ?? 0,
                    'urgent' => $priorityStats['urgent'] ?? 0,
                ],
            ];

            return $this->get_success_response([
                'stats' => $stats,
                'generated_at' => current_time('mysql'),
            ], 'Dashboard stats retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get dashboard stats: ' . $e->getMessage(), 'dashboard_failed');
        }
    }

    /**
     * Execute get board activities ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_board_activities(array $args): array {
        try {
            $board_id = intval($args['board_id']);
            $page = $args['page'] ?? 1;
            $per_page = $args['per_page'] ?? 20;

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check board access
            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if (!in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            if (!class_exists('\FluentBoards\App\Models\Activity')) {
                return $this->get_error_response('Activity tracking not available', 'activities_not_available');
            }

            $activityModel = new \FluentBoards\App\Models\Activity();

            $offset = ($page - 1) * $per_page;

            $activities = $activityModel->where('object_id', $board_id)
                                      ->where('object_type', 'board')
                                      ->orWhere(function ($query) use ($board_id) {
                                          $query->where('object_type', 'task')
                                                ->whereHas('task', function ($taskQuery) use ($board_id) {
                                                    $taskQuery->where('board_id', $board_id);
                                                });
                                      })
                                      ->with(['user'])
                                      ->orderBy('created_at', 'DESC')
                                      ->offset($offset)
                                      ->limit($per_page)
                                      ->get();

            $totalActivities = $activityModel->where('object_id', $board_id)
                                           ->where('object_type', 'board')
                                           ->orWhere(function ($query) use ($board_id) {
                                               $query->where('object_type', 'task')
                                                     ->whereHas('task', function ($taskQuery) use ($board_id) {
                                                         $taskQuery->where('board_id', $board_id);
                                                     });
                                           })
                                           ->count();

            $formattedActivities = [];
            foreach ($activities as $activity) {
                $user = $activity->user;
                $formattedActivities[] = [
                    'id' => $activity->id,
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'object_type' => $activity->object_type,
                    'object_id' => $activity->object_id,
                    'old_value' => $activity->old_value,
                    'new_value' => $activity->new_value,
                    'created_at' => $activity->created_at,
                    'user' => $user ? [
                        'id' => $user->ID,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                        'avatar' => function_exists('fluent_boards_user_avatar')
                            ? fluent_boards_user_avatar($user->user_email)
                            : get_avatar_url($user->user_email),
                    ] : null,
                ];
            }

            $board = \FluentBoards\App\Models\Board::find($board_id);

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                ],
                'activities' => $formattedActivities,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $totalActivities,
                    'total_pages' => ceil($totalActivities / $per_page),
                ],
                'generated_at' => current_time('mysql'),
            ], 'Board activities retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get board activities: ' . $e->getMessage(), 'activities_failed');
        }
    }

    /**
     * Execute get member reports ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_member_reports(array $args): array {
        try {
            $user_id = intval($args['user_id']);
            $board_ids = $args['board_ids'] ?? [];
            $date_from = $args['date_from'] ?? null;
            $date_to = $args['date_to'] ?? null;
            $report_type = $args['report_type'] ?? 'comprehensive';

            if ($user_id <= 0) {
                return $this->get_error_response('Invalid user ID', 'invalid_user_id');
            }

            // Validate date formats
            if ($date_from && !$this->validate_date_format($date_from)) {
                return $this->get_error_response('Invalid date_from format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            if ($date_to && !$this->validate_date_format($date_to)) {
                return $this->get_error_response('Invalid date_to format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            $currentUserId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($currentUserId);

            // Filter board IDs by accessible ones
            if (!empty($board_ids)) {
                $board_ids = array_intersect($board_ids, $accessibleBoardIds);
            } else {
                $board_ids = $accessibleBoardIds;
            }

            if (empty($board_ids)) {
                return $this->get_error_response('No accessible boards found', 'no_boards_found');
            }

            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return $this->get_error_response('User not found', 'user_not_found');
            }

            $report = $this->generate_member_report($user_id, $board_ids, $date_from, $date_to, $report_type);

            return $this->get_success_response([
                'user' => [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                ],
                'report' => $report,
                'report_type' => $report_type,
                'filters' => [
                    'board_ids' => $board_ids,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Member report retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get member reports: ' . $e->getMessage(), 'member_reports_failed');
        }
    }

    /**
     * Execute get all board reports ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_all_board_reports(array $args): array {
        try {
            $boardService = new \FluentBoards\App\Services\BoardService();
            $report = $boardService->getAllBoardReports();

            return $this->get_success_response([
                'report' => $report,
                'generated_at' => current_time('mysql'),
            ], 'All board reports retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get all board reports: ' . $e->getMessage(), 'all_reports_failed');
        }
    }

    /**
     * Execute get board report ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_board_report(array $args): array {
        try {
            $board_id = intval($args['board_id']);

            if ($board_id <= 0) {
                return $this->get_error_response('Invalid board ID', 'invalid_board_id');
            }

            // Check board access
            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if (!in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $boardService = new \FluentBoards\App\Services\BoardService();
            $report = $boardService->getBoardReports($board_id);

            $board = \FluentBoards\App\Models\Board::find($board_id);

            return $this->get_success_response([
                'board' => [
                    'id' => $board->id,
                    'title' => $board->title,
                    'type' => $board->type,
                ],
                'report' => $report,
                'generated_at' => current_time('mysql'),
            ], 'Board report retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get board report: ' . $e->getMessage(), 'board_report_failed');
        }
    }

    /**
     * Execute get timesheet by tasks ability (Pro required)
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_timesheet_by_tasks(array $args): array {
        try {
            if (!$this->is_pro_available()) {
                return $this->get_error_response('This feature requires FluentBoards Pro', 'pro_required');
            }

            $board_id = $args['board_id'] ?? null;
            $date_range = $args['date_range'] ?? null;

            // Validate date range
            if ($date_range && (count($date_range) !== 2 || !$this->validate_date_format($date_range[0]) || !$this->validate_date_format($date_range[1]))) {
                return $this->get_error_response('Invalid date_range format. Use [start_date, end_date] in YYYY-MM-DD format.', 'invalid_date_range');
            }

            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if ($board_id && !in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $timeTrackModel = new \FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack();
            $query = $timeTrackModel->where('status', 'commited');

            if ($board_id) {
                $query->where('board_id', $board_id);
            } else {
                $query->whereIn('board_id', $accessibleBoardIds);
            }

            if ($date_range) {
                $query->whereBetween('completed_at', $date_range);
            }

            $timeTracks = $query->with(['task', 'user', 'board'])->get();

            $reportByTasks = $this->group_timesheet_by_tasks($timeTracks);

            return $this->get_success_response([
                'report' => $reportByTasks,
                'filters' => [
                    'board_id' => $board_id,
                    'date_range' => $date_range,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Timesheet by tasks retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get timesheet by tasks: ' . $e->getMessage(), 'timesheet_tasks_failed');
        }
    }

    /**
     * Execute get timesheet by users ability (Pro required)
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_timesheet_by_users(array $args): array {
        try {
            if (!$this->is_pro_available()) {
                return $this->get_error_response('This feature requires FluentBoards Pro', 'pro_required');
            }

            $board_id = $args['board_id'] ?? null;
            $date_range = $args['date_range'] ?? null;

            // Validate date range
            if ($date_range && (count($date_range) !== 2 || !$this->validate_date_format($date_range[0]) || !$this->validate_date_format($date_range[1]))) {
                return $this->get_error_response('Invalid date_range format. Use [start_date, end_date] in YYYY-MM-DD format.', 'invalid_date_range');
            }

            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if ($board_id && !in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $timeTrackModel = new \FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack();
            $query = $timeTrackModel->where('status', 'commited');

            if ($board_id) {
                $query->where('board_id', $board_id);
            } else {
                $query->whereIn('board_id', $accessibleBoardIds);
            }

            if ($date_range) {
                $query->whereBetween('completed_at', $date_range);
            }

            $timeTracks = $query->with(['task', 'user', 'board'])->get();

            $reportByUsers = $this->group_timesheet_by_users($timeTracks);

            return $this->get_success_response([
                'report' => $reportByUsers,
                'filters' => [
                    'board_id' => $board_id,
                    'date_range' => $date_range,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Timesheet by users retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get timesheet by users: ' . $e->getMessage(), 'timesheet_users_failed');
        }
    }

    /**
     * Execute get team workload ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_team_workload(array $args): array {
        try {
            $board_id = $args['board_id'] ?? null;
            $period = $args['period'] ?? 'current';

            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if ($board_id && !in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $boardIds = $board_id ? [$board_id] : $accessibleBoardIds;

            if (empty($boardIds)) {
                return $this->get_error_response('No accessible boards found', 'no_boards_found');
            }

            $workload = $this->calculate_team_workload($boardIds, $period);

            return $this->get_success_response([
                'workload' => $workload,
                'period' => $period,
                'board_id' => $board_id,
                'generated_at' => current_time('mysql'),
            ], 'Team workload retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get team workload: ' . $e->getMessage(), 'workload_failed');
        }
    }

    /**
     * Execute get activity timeline ability
     *
     * @param array $args Ability arguments
     * @return array Response data
     */
    public function execute_get_activity_timeline(array $args): array {
        try {
            $board_id = $args['board_id'] ?? null;
            $user_id = $args['user_id'] ?? null;
            $date_from = $args['date_from'] ?? null;
            $date_to = $args['date_to'] ?? null;
            $page = $args['page'] ?? 1;
            $per_page = $args['per_page'] ?? 50;

            // Validate date formats
            if ($date_from && !$this->validate_date_format($date_from)) {
                return $this->get_error_response('Invalid date_from format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            if ($date_to && !$this->validate_date_format($date_to)) {
                return $this->get_error_response('Invalid date_to format. Use YYYY-MM-DD.', 'invalid_date_format');
            }

            if (!class_exists('\FluentBoards\App\Models\Activity')) {
                return $this->get_error_response('Activity tracking not available', 'activities_not_available');
            }

            $userId = get_current_user_id();
            $accessibleBoardIds = $this->get_accessible_board_ids($userId);

            if ($board_id && !in_array($board_id, $accessibleBoardIds)) {
                return $this->get_error_response('Access denied to board', 'access_denied');
            }

            $activityModel = new \FluentBoards\App\Models\Activity();
            $query = $activityModel->newQuery();

            // Filter by board
            if ($board_id) {
                $query->where(function ($q) use ($board_id) {
                    $q->where('object_id', $board_id)->where('object_type', 'board')
                      ->orWhereHas('task', function ($taskQuery) use ($board_id) {
                          $taskQuery->where('board_id', $board_id);
                      });
                });
            } else {
                // Filter by accessible boards
                $query->where(function ($q) use ($accessibleBoardIds) {
                    $q->whereIn('object_id', $accessibleBoardIds)->where('object_type', 'board')
                      ->orWhereHas('task', function ($taskQuery) use ($accessibleBoardIds) {
                          $taskQuery->whereIn('board_id', $accessibleBoardIds);
                      });
                });
            }

            // Filter by user
            if ($user_id) {
                $query->where('created_by', $user_id);
            }

            // Filter by date range
            if ($date_from && $date_to) {
                $query->whereBetween('created_at', [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
            }

            $offset = ($page - 1) * $per_page;
            $activities = $query->with(['user', 'task', 'board'])
                               ->orderBy('created_at', 'DESC')
                               ->offset($offset)
                               ->limit($per_page)
                               ->get();

            $totalActivities = $query->count();

            $timeline = $this->format_activity_timeline($activities);

            return $this->get_success_response([
                'timeline' => $timeline,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $totalActivities,
                    'total_pages' => ceil($totalActivities / $per_page),
                ],
                'filters' => [
                    'board_id' => $board_id,
                    'user_id' => $user_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                ],
                'generated_at' => current_time('mysql'),
            ], 'Activity timeline retrieved successfully');

        } catch (\Exception $e) {
            return $this->get_error_response('Failed to get activity timeline: ' . $e->getMessage(), 'timeline_failed');
        }
    }

    /**
     * Get accessible board IDs for a user
     *
     * @param int $userId User ID
     * @return array Board IDs the user can access
     */
    private function get_accessible_board_ids(int $userId): array {
        if (!class_exists('\FluentBoards\App\Services\PermissionManager')) {
            return [];
        }

        try {
            return \FluentBoards\App\Services\PermissionManager::getBoardIdsForUser($userId);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date Date string to validate
     * @return bool True if valid format
     */
    private function validate_date_format(string $date): bool {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Calculate period date range
     *
     * @param string $period Period type
     * @return array Start and end dates
     */
    private function calculate_period_date_range(string $period): array {
        $now = current_time('mysql');
        $currentDate = date('Y-m-d', strtotime($now));

        switch ($period) {
            case 'today':
                return [
                    'start' => $currentDate . ' 00:00:00',
                    'end' => $currentDate . ' 23:59:59',
                ];

            case 'week':
                $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($now)));
                $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($now)));
                return [
                    'start' => $weekStart . ' 00:00:00',
                    'end' => $weekEnd . ' 23:59:59',
                ];

            case 'month':
                $monthStart = date('Y-m-01', strtotime($now));
                $monthEnd = date('Y-m-t', strtotime($now));
                return [
                    'start' => $monthStart . ' 00:00:00',
                    'end' => $monthEnd . ' 23:59:59',
                ];

            case 'quarter':
                $quarter = ceil(date('n', strtotime($now)) / 3);
                $quarterStart = date('Y-m-d', mktime(0, 0, 0, ($quarter - 1) * 3 + 1, 1, date('Y', strtotime($now))));
                $quarterEnd = date('Y-m-d', mktime(0, 0, 0, $quarter * 3 + 1, 0, date('Y', strtotime($now))));
                return [
                    'start' => $quarterStart . ' 00:00:00',
                    'end' => $quarterEnd . ' 23:59:59',
                ];

            case 'year':
                $yearStart = date('Y-01-01', strtotime($now));
                $yearEnd = date('Y-12-31', strtotime($now));
                return [
                    'start' => $yearStart . ' 00:00:00',
                    'end' => $yearEnd . ' 23:59:59',
                ];

            default:
                return [
                    'start' => date('Y-m-01 00:00:00', strtotime($now)),
                    'end' => date('Y-m-t 23:59:59', strtotime($now)),
                ];
        }
    }

    /**
     * Format timesheet report
     *
     * @param object $timeTracks Time tracking records
     * @param string $reportType Report type
     * @return array Formatted report
     */
    private function format_timesheet_report($timeTracks, string $reportType): array {
        switch ($reportType) {
            case 'by-tasks':
                return $this->group_timesheet_by_tasks($timeTracks);

            case 'by-users':
                return $this->group_timesheet_by_users($timeTracks);

            case 'summary':
            default:
                return $this->create_timesheet_summary($timeTracks);
        }
    }

    /**
     * Group timesheet data by tasks
     *
     * @param object $timeTracks Time tracking records
     * @return array Grouped data
     */
    private function group_timesheet_by_tasks($timeTracks): array {
        $grouped = [];

        foreach ($timeTracks as $track) {
            $taskId = $track->task_id;

            if (!isset($grouped[$taskId])) {
                $grouped[$taskId] = [
                    'task_id' => $taskId,
                    'task_title' => $track->task ? $track->task->title : 'Unknown Task',
                    'board' => $track->board ? [
                        'id' => $track->board->id,
                        'title' => $track->board->title,
                    ] : null,
                    'total_billable_minutes' => 0,
                    'total_working_minutes' => 0,
                    'entries' => [],
                ];
            }

            $grouped[$taskId]['total_billable_minutes'] += $track->billable_minutes;
            $grouped[$taskId]['total_working_minutes'] += $track->working_minutes;
            $grouped[$taskId]['entries'][] = [
                'id' => $track->id,
                'billable_minutes' => $track->billable_minutes,
                'working_minutes' => $track->working_minutes,
                'completed_at' => $track->completed_at,
                'message' => $track->message,
                'user' => $track->user ? [
                    'id' => $track->user->ID,
                    'name' => $track->user->display_name,
                    'email' => $track->user->user_email,
                ] : null,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Group timesheet data by users
     *
     * @param object $timeTracks Time tracking records
     * @return array Grouped data
     */
    private function group_timesheet_by_users($timeTracks): array {
        $grouped = [];

        foreach ($timeTracks as $track) {
            $userId = $track->created_by;

            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'user_id' => $userId,
                    'user' => $track->user ? [
                        'id' => $track->user->ID,
                        'name' => $track->user->display_name,
                        'email' => $track->user->user_email,
                    ] : null,
                    'total_billable_minutes' => 0,
                    'total_working_minutes' => 0,
                    'tasks' => [],
                ];
            }

            $grouped[$userId]['total_billable_minutes'] += $track->billable_minutes;
            $grouped[$userId]['total_working_minutes'] += $track->working_minutes;

            $taskId = $track->task_id;
            if (!isset($grouped[$userId]['tasks'][$taskId])) {
                $grouped[$userId]['tasks'][$taskId] = [
                    'task_id' => $taskId,
                    'task_title' => $track->task ? $track->task->title : 'Unknown Task',
                    'board' => $track->board ? [
                        'id' => $track->board->id,
                        'title' => $track->board->title,
                    ] : null,
                    'billable_minutes' => 0,
                    'working_minutes' => 0,
                    'entries_count' => 0,
                ];
            }

            $grouped[$userId]['tasks'][$taskId]['billable_minutes'] += $track->billable_minutes;
            $grouped[$userId]['tasks'][$taskId]['working_minutes'] += $track->working_minutes;
            $grouped[$userId]['tasks'][$taskId]['entries_count']++;
        }

        // Convert tasks array to indexed array
        foreach ($grouped as &$userGroup) {
            $userGroup['tasks'] = array_values($userGroup['tasks']);
        }

        return array_values($grouped);
    }

    /**
     * Create timesheet summary
     *
     * @param object $timeTracks Time tracking records
     * @return array Summary data
     */
    private function create_timesheet_summary($timeTracks): array {
        $totalBillableMinutes = 0;
        $totalWorkingMinutes = 0;
        $uniqueTasks = [];
        $uniqueUsers = [];
        $uniqueBoards = [];

        foreach ($timeTracks as $track) {
            $totalBillableMinutes += $track->billable_minutes;
            $totalWorkingMinutes += $track->working_minutes;
            $uniqueTasks[$track->task_id] = true;
            $uniqueUsers[$track->created_by] = true;
            if ($track->board) {
                $uniqueBoards[$track->board->id] = $track->board->title;
            }
        }

        return [
            'summary' => [
                'total_entries' => $timeTracks->count(),
                'total_billable_minutes' => $totalBillableMinutes,
                'total_working_minutes' => $totalWorkingMinutes,
                'total_billable_hours' => round($totalBillableMinutes / 60, 2),
                'total_working_hours' => round($totalWorkingMinutes / 60, 2),
                'unique_tasks' => count($uniqueTasks),
                'unique_users' => count($uniqueUsers),
                'unique_boards' => count($uniqueBoards),
            ],
            'boards_involved' => $uniqueBoards,
        ];
    }

    /**
     * Generate member report
     *
     * @param int $userId User ID
     * @param array $boardIds Board IDs
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @param string $reportType Report type
     * @return array Member report
     */
    private function generate_member_report(int $userId, array $boardIds, ?string $dateFrom, ?string $dateTo, string $reportType): array {
        $taskModel = new \FluentBoards\App\Models\Task();
        $report = [];

        // Get assigned tasks
        $taskQuery = $taskModel->whereIn('board_id', $boardIds)
                             ->whereHas('assignees', function ($query) use ($userId) {
                                 $query->where('foreign_id', $userId);
                             });

        if ($dateFrom && $dateTo) {
            $taskQuery->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        }

        $assignedTasks = $taskQuery->with(['board', 'stage'])->get();

        $report['tasks'] = [
            'total_assigned' => $assignedTasks->count(),
            'completed' => $assignedTasks->where('status', 'closed')->count(),
            'in_progress' => $assignedTasks->where('status', 'in_progress')->count(),
            'open' => $assignedTasks->where('status', 'open')->count(),
            'overdue' => $assignedTasks->where('due_at', '<', current_time('mysql'))
                                    ->where('status', '!=', 'closed')
                                    ->count(),
        ];

        // Add activities if requested
        if (in_array($reportType, ['activities', 'comprehensive']) && class_exists('\FluentBoards\App\Models\Activity')) {
            $activityQuery = \FluentBoards\App\Models\Activity::where('created_by', $userId);

            if ($dateFrom && $dateTo) {
                $activityQuery->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            $activities = $activityQuery->count();
            $report['activities'] = [
                'total_activities' => $activities,
            ];
        }

        // Add time tracking if Pro is available and requested
        if (in_array($reportType, ['comprehensive']) && $this->is_pro_available()) {
            $timeQuery = \FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack::where('created_by', $userId)
                                                                                  ->where('status', 'commited')
                                                                                  ->whereIn('board_id', $boardIds);

            if ($dateFrom && $dateTo) {
                $timeQuery->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            $timeTracks = $timeQuery->get();
            $report['time_tracking'] = [
                'total_entries' => $timeTracks->count(),
                'total_billable_minutes' => $timeTracks->sum('billable_minutes'),
                'total_working_minutes' => $timeTracks->sum('working_minutes'),
            ];
        }

        return $report;
    }

    /**
     * Calculate team workload
     *
     * @param array $boardIds Board IDs
     * @param string $period Period type
     * @return array Workload data
     */
    private function calculate_team_workload(array $boardIds, string $period): array {
        $taskModel = new \FluentBoards\App\Models\Task();
        $relationModel = new \FluentBoards\App\Models\Relation();

        // Get all users assigned to these boards
        $boardUsers = $relationModel->whereIn('object_id', $boardIds)
                                   ->where('object_type', 'board_user')
                                   ->with('user')
                                   ->get();

        $workload = [];

        foreach ($boardUsers as $boardUser) {
            if (!$boardUser->user) continue;

            $user = $boardUser->user;
            $userId = $user->ID;

            // Calculate task counts based on period
            $taskQuery = $taskModel->whereIn('board_id', $boardIds)
                                 ->whereHas('assignees', function ($query) use ($userId) {
                                     $query->where('foreign_id', $userId);
                                 });

            switch ($period) {
                case 'current':
                    $taskQuery->where('status', '!=', 'closed');
                    break;

                case 'upcoming':
                    $taskQuery->where('status', 'open')
                             ->whereNotNull('due_at')
                             ->where('due_at', '>', current_time('mysql'));
                    break;

                case 'overdue':
                    $taskQuery->where('status', '!=', 'closed')
                             ->whereNotNull('due_at')
                             ->where('due_at', '<', current_time('mysql'));
                    break;
            }

            $tasks = $taskQuery->get();

            $workload[] = [
                'user' => [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                ],
                'task_counts' => [
                    'total' => $tasks->count(),
                    'high_priority' => $tasks->where('priority', 'high')->count(),
                    'medium_priority' => $tasks->where('priority', 'medium')->count(),
                    'low_priority' => $tasks->where('priority', 'low')->count(),
                ],
                'capacity_indicator' => $this->calculate_capacity_indicator($tasks->count()),
            ];
        }

        return $workload;
    }

    /**
     * Calculate capacity indicator
     *
     * @param int $taskCount Task count
     * @return string Capacity indicator
     */
    private function calculate_capacity_indicator(int $taskCount): string {
        if ($taskCount <= 5) return 'low';
        if ($taskCount <= 15) return 'medium';
        if ($taskCount <= 25) return 'high';
        return 'overloaded';
    }

    /**
     * Format activity timeline
     *
     * @param object $activities Activity records
     * @return array Formatted timeline
     */
    private function format_activity_timeline($activities): array {
        $timeline = [];

        foreach ($activities as $activity) {
            $timeline[] = [
                'id' => $activity->id,
                'action' => $activity->action,
                'description' => $activity->description,
                'object_type' => $activity->object_type,
                'object_id' => $activity->object_id,
                'old_value' => $activity->old_value,
                'new_value' => $activity->new_value,
                'created_at' => $activity->created_at,
                'user' => $activity->user ? [
                    'id' => $activity->user->ID,
                    'name' => $activity->user->display_name,
                    'email' => $activity->user->user_email,
                ] : null,
                'task' => $activity->task ? [
                    'id' => $activity->task->id,
                    'title' => $activity->task->title,
                ] : null,
                'board' => $activity->board ? [
                    'id' => $activity->board->id,
                    'title' => $activity->board->title,
                ] : null,
            ];
        }

        return $timeline;
    }

    /**
     * Check if FluentBoards Pro is available
     *
     * @return bool True if Pro is available
     */
    private function is_pro_available(): bool {
        return class_exists('\FluentBoardsPro\App\Modules\TimeTracking\Model\TimeTrack') &&
               (is_plugin_active('fluent-boards-pro/fluent-boards-pro.php') ||
                defined('FLUENT_BOARDS_PRO'));
    }
}