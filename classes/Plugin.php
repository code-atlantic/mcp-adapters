<?php
declare(strict_types=1);

namespace MCP\Adapters;

use MCP\Adapters\Adapters\FluentBoards\FluentBoardsAdapter;
use MCP\Adapters\Adapters\AllAbilitiesServer;
use MCP\Adapters\Admin\DashboardWidget;
use MCP\Adapters\Core\McpClientManager;

/**
 * Main plugin class for MCP Adapters
 *
 * Coordinates the loading and initialization of all MCP adapters
 * for various WordPress plugins using the new Abilities API.
 */
class Plugin {

	/**
	 * Plugin constructor - Set up hooks
	 */
	public function __construct() {

		// Initialize adapters early so they can register for abilities_api_init
		add_action( 'plugins_loaded', [ $this, 'init' ], 25 );
		add_action( 'wp_loaded', [ $this, 'late_init' ], 20 );
	}

	/**
	 * Initialize adapters early - after WordPress and plugins are loaded
	 */
	public function init(): void {
		// Load text domain for translations
		load_plugin_textdomain(
			'mcp-adapters',
			false,
			dirname( plugin_basename( MCP_ADAPTERS_PLUGIN_FILE ) ) . '/languages'
		);

		// Initialize admin features
		if ( is_admin() ) {
			new DashboardWidget();
			add_action( 'wp_ajax_mcp_test_ability', [ $this, 'ajax_test_ability' ] );
		}

		// Initialize MCP client manager
		McpClientManager::init();

		// Initialize adapters based on active plugins
		$this->initialize_adapters();
	}

	/**
	 * Late initialization for adapters that need all plugins fully loaded
	 */
	public function late_init(): void {
		// Register the all-abilities server after all adapters have registered their abilities
		add_action( 'mcp_adapter_init', [ $this, 'register_all_abilities_server' ], 999 );

		// Any late initialization can happen here
		do_action( 'mcp_adapters_loaded' );
	}

	/**
	 * Register the universal all-abilities MCP server
	 *
	 * @param object $adapter MCP adapter instance
	 */
	public function register_all_abilities_server( $adapter ): void {
		if ( ! $this->is_mcp_adapter_available() ) {
			return;
		}

		// Force abilities registry to initialize and trigger abilities_api_init
		// This ensures all abilities are registered before we try to read them
		if ( class_exists( '\WP_Abilities_Registry' ) ) {
			\WP_Abilities_Registry::get_instance();
		}

		$server = new AllAbilitiesServer();
		$server->register_with_adapter( $adapter );
	}

	/**
	 * Initialize available adapters based on active plugins
	 */
	private function initialize_adapters(): void {

		// FluentBoards Adapter
		if ( $this->is_fluent_boards_active() ) {
			new FluentBoardsAdapter();
		}

		// Future adapters can be added here:
		// if ($this->is_some_plugin_active()) {
		// new SomePluginAdapter();
		// }

		// Hook for third-party adapters
		do_action( 'mcp_adapters_initialize', $this );
	}

	/**
	 * Check if MCP Adapter plugin is available
	 *
	 * @return bool True if MCP Adapter is active and ready
	 */
	private function is_mcp_adapter_available(): bool {
		return class_exists( '\WP\MCP\Core\McpAdapter' ) &&
				function_exists( 'wp_register_ability' );
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
	 * Check if FluentBoards Pro features are available
	 *
	 * @return bool True if FluentBoards Pro is active
	 */
	public static function is_fluent_boards_pro_active(): bool {
		return defined( 'FLUENT_BOARDS_PRO' ) ||
				is_plugin_active( 'fluent-boards-pro/fluent-boards-pro.php' );
	}

	/**
	 * Get plugin version
	 *
	 * @return string Plugin version
	 */
	public static function get_version(): string {
		return MCP_ADAPTERS_VERSION;
	}

	/**
	 * Get plugin directory path
	 *
	 * @return string Plugin directory path
	 */
	public static function get_plugin_dir(): string {
		return MCP_ADAPTERS_PLUGIN_DIR;
	}

	/**
	 * Get plugin directory URL
	 *
	 * @return string Plugin directory URL
	 */
	public static function get_plugin_url(): string {
		return MCP_ADAPTERS_PLUGIN_URL;
	}

	/**
	 * AJAX handler for testing abilities from dashboard widget
	 */
	public function ajax_test_ability(): void {
		check_ajax_referer( 'mcp_test_ability', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$ability = sanitize_text_field( wp_unslash( $_POST['ability'] ?? '' ) );

		if ( empty( $ability ) ) {
			wp_send_json_error( 'No ability specified' );
		}

		// Test data for each ability
		$test_data = [
			'fluentboards/test-verbose-enum'       => [ 'preset' => 'solid_5' ],
			'fluentboards/test-pattern-enum'       => [ 'preset' => 'gradient_3' ],
			'fluentboards/generate-planet-verbose' => [
				'planet_name' => 'Mars',
				'texture'     => 'rock_5',
			],
			'fluentboards/generate-planet-pattern' => [
				'planet_name' => 'Jupiter',
				'texture'     => 'gas_3',
			],
		];

		$args = $test_data[ $ability ] ?? [];

		if ( empty( $args ) ) {
			wp_send_json_error( 'No test data available for this ability' );
		}

		// Get the ability object
		$ability_obj = \wp_get_ability( $ability );

		if ( ! $ability_obj ) {
			wp_send_json_error( "Ability '{$ability}' not found" );
		}

		// Execute the ability
		$result = $ability_obj->execute( $args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			[
				'message' => wp_json_encode( $result, JSON_PRETTY_PRINT ),
				'ability' => $ability,
				'args'    => $args,
			]
		);
	}
}
