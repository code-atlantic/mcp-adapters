<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards;

use MCP\Adapters\Plugin;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Boards;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Tasks;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Stages;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Comments;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Users;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Attachments;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Reporting;
use MCP\Adapters\Adapters\FluentBoards\Abilities\Labels;
use MCP\Adapters\Adapters\FluentBoards\Abilities\EnumTest;
use MCP\Adapters\Adapters\FluentBoards\Prompts\ProjectOverview;
use MCP\Adapters\Adapters\FluentBoards\Prompts\AnalyzeWorkflow;
use MCP\Adapters\Adapters\FluentBoards\Prompts\StatusCheckin;
use MCP\Adapters\Adapters\FluentBoards\Prompts\TeamProductivity;
use MCP\Adapters\Adapters\FluentBoards\Servers\BoardCrudServer;
use MCP\Adapters\Adapters\FluentBoards\Servers\FullFluentBoardsServer;

/**
 * FluentBoards MCP Adapter
 *
 * Provides comprehensive MCP abilities for managing FluentBoards project management functionality.
 * This adapter enables AI models to interact with boards, tasks, stages, comments, labels,
 * attachments, reporting, and Pro features like subtasks, time tracking, custom fields, and folders.
 *
 * Registers multiple concurrent MCP servers:
 * - Full FluentBoards Server: Complete functionality (81 abilities)
 * - Board CRUD Server: Board management only (10 abilities)
 *
 * Pro features are automatically detected and registered only when FluentBoards Pro is active.
 */
class FluentBoardsAdapter {

	/**
	 * Constructor - Initialize the FluentBoards adapter
	 */
	public function __construct() {
		// Only proceed if FluentBoards is active
		if ( ! $this->is_fluent_boards_active() ) {
			return;
		}

		// Hook into WordPress Abilities API initialization and register all servers
		add_action( 'abilities_api_init', [ $this, 'register_abilities' ], 10 );
		add_action( 'abilities_api_init', [ $this, 'register_prompts' ], 10 );
		add_action( 'wp_loaded', [ $this, 'register_all_servers' ], 25 );

		// Hook for initialization completion
		do_action( 'mcp_adapters_fluentboards_loaded' );
	}

	/**
	 * Register all FluentBoards abilities
	 */
	public function register_abilities(): void {
		// Core FluentBoards abilities (available in both Free and Pro)
		new Boards();
		new Tasks();
		new Stages();
		new Comments();
		new Users();
		new Attachments();
		new Reporting();
		new Labels();
		new EnumTest();

		// Pro-only abilities (automatically detected)
		if ( Plugin::is_fluent_boards_pro_active() ) {
			// Pro abilities will be implemented when required
		}

		// Hook for additional abilities
		do_action( 'mcp_adapters_fluentboards_abilities_registered' );
	}

	/**
	 * Register FluentBoards prompts
	 */
	public function register_prompts(): void {
		new ProjectOverview();
		new AnalyzeWorkflow();
		new StatusCheckin();
		new TeamProductivity();

		// Hook for additional prompts
		do_action( 'mcp_adapters_fluentboards_prompts_registered' );
	}

	/**
	 * Register all MCP servers concurrently
	 */
	public function register_all_servers(): void {
		// Check if MCP adapter is available
		if ( ! $this->is_mcp_adapter_available() ) {
			return;
		}

		// Register both servers - they run concurrently with different endpoints
		$this->register_board_crud_server();
		$this->register_full_server();

		// Hook for additional servers
		do_action( 'mcp_adapters_fluentboards_servers_registered' );
	}

	/**
	 * Register board CRUD only server
	 */
	private function register_board_crud_server(): void {
		add_action(
			'mcp_adapter_init',
			function ( $adapter ) {
				$server = new BoardCrudServer();
				$server->register_with_adapter( $adapter );
			}
		);
	}

	/**
	 * Register full FluentBoards server
	 */
	private function register_full_server(): void {
		add_action(
			'mcp_adapter_init',
			function ( $adapter ) {
				$server = new FullFluentBoardsServer();
				$server->register_with_adapter( $adapter );
			}
		);
	}

	/**
	 * Check if MCP Adapter plugin is available
	 *
	 * @return bool True if MCP Adapter is active
	 */
	private function is_mcp_adapter_available(): bool {
		// Check for abilities-api plugin
		$abilities_api_active = is_plugin_active( 'abilities-api/abilities-api.php' ) &&
								function_exists( 'wp_register_ability' );

		// Check for mcp-adapter plugin
		$mcp_adapter_active = is_plugin_active( 'mcp-adapter/mcp-adapter.php' ) &&
							class_exists( '\WP\MCP\Plugin' );

		return $abilities_api_active && $mcp_adapter_active;
	}

	/**
	 * Check if FluentBoards plugin is active and available
	 *
	 * @return bool True if FluentBoards is active and ready
	 */
	private function is_fluent_boards_active(): bool {
		return defined( 'FLUENT_BOARDS' ) &&
				class_exists( '\FluentBoards\App\Models\Board' ) &&
				( is_plugin_active( 'fluent-boards/fluent-boards.php' ) ||
				is_plugin_active( 'fluent-boards-pro/fluent-boards-pro.php' ) ||
				function_exists( 'fluent_boards' ) );
	}

	/**
	 * Get FluentBoards version
	 *
	 * @return string FluentBoards version or 'unknown'
	 */
	public static function get_fluent_boards_version(): string {
		if ( defined( 'FLUENT_BOARDS_VERSION' ) ) {
			return FLUENT_BOARDS_VERSION;
		}

		if ( function_exists( 'get_plugin_data' ) ) {
			$plugin_file = WP_PLUGIN_DIR . '/fluent-boards/fluent-boards.php';
			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file );
				return $plugin_data['Version'] ?? 'unknown';
			}
		}

		return 'unknown';
	}

	/**
	 * Check if specific FluentBoards feature is available
	 *
	 * @param string $feature Feature name to check
	 * @return bool True if feature is available
	 */
	public static function has_feature( string $feature ): bool {
		switch ( $feature ) {
			case 'pro':
				return Plugin::is_fluent_boards_pro_active();

			case 'subtasks':
				return Plugin::is_fluent_boards_pro_active() &&
						class_exists( '\FluentBoards\App\Models\SubTask' );

			case 'time_tracking':
				return Plugin::is_fluent_boards_pro_active() &&
						class_exists( '\FluentBoards\App\Models\TaskTimeTrack' );

			case 'custom_fields':
				return Plugin::is_fluent_boards_pro_active() &&
						class_exists( '\FluentBoards\App\Models\CustomField' );

			case 'folders':
				return Plugin::is_fluent_boards_pro_active() &&
						class_exists( '\FluentBoards\App\Models\Folder' );

			default:
				return false;
		}
	}
}
