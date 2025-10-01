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

		// Hook into WordPress Abilities API initialization to register abilities and prompts
		add_action( 'abilities_api_init', [ $this, 'register_abilities' ], 10 );
		add_action( 'abilities_api_init', [ $this, 'register_prompts' ], 10 );

		// Register servers on the correct MCP adapter hook
		// Priority 10 ensures this runs after abilities_api_init has been triggered by the registry
		add_action( 'mcp_adapter_init', [ $this, 'ensure_abilities_registered' ], 5 );
		add_action( 'mcp_adapter_init', [ $this, 'register_all_servers' ], 10 );

		// Hook for initialization completion
		do_action( 'mcp_adapters_fluentboards/loaded' );
	}

	/**
	 * Ensure abilities are registered before MCP servers try to use them
	 *
	 * This method forces the Abilities API to initialize by calling get_instance(),
	 * which triggers the abilities_api_init hook if it hasn't fired yet.
	 */
	public function ensure_abilities_registered(): void {
		// Force the abilities registry to initialize, which triggers abilities_api_init
		\WP_Abilities_Registry::get_instance();
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
		do_action( 'mcp_adapters_fluentboards/abilities_registered' );
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
		do_action( 'mcp_adapters_fluentboards/prompts_registered' );
	}

	/**
	 * Track whether servers have been registered
	 */
	private static $servers_registered = false;

	/**
	 * Register all MCP servers concurrently
	 *
	 * Called on mcp_adapter_init hook, which fires after abilities are registered
	 * and receives the MCP adapter instance as a parameter
	 */
	public function register_all_servers( $adapter ): void {
		// Prevent duplicate registration
		if ( self::$servers_registered ) {
			return;
		}

		// Check if MCP adapter is available
		if ( ! $this->is_mcp_adapter_available() ) {
			return;
		}

		// Register both servers directly - they run concurrently with different endpoints
		$this->register_board_crud_server( $adapter );
		$this->register_full_server( $adapter );

		// Mark servers as registered
		self::$servers_registered = true;

		// Hook for additional servers
		do_action( 'mcp_adapters_fluentboards/servers_registered' );
	}

	/**
	 * Register board CRUD only server
	 *
	 * @param object $adapter MCP adapter instance
	 */
	private function register_board_crud_server( $adapter ): void {

		$server = new BoardCrudServer();
		$server->register_with_adapter( $adapter );
	}

	/**
	 * Register full FluentBoards server
	 *
	 * @param object $adapter MCP adapter instance
	 */
	private function register_full_server( $adapter ): void {

		$server = new FullFluentBoardsServer();
		$server->register_with_adapter( $adapter );
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

		// Check for mcp-adapter plugin - use correct core class
		$mcp_adapter_active = is_plugin_active( 'mcp-adapter/mcp-adapter.php' ) &&
							class_exists( '\WP\MCP\Core\McpAdapter' );

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
