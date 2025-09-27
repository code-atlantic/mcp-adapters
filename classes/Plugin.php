<?php
declare(strict_types=1);

namespace MCP\Adapters;

use MCP\Adapters\Adapters\FluentBoards\FluentBoardsAdapter;

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
        add_action('init', [$this, 'init'], 20);
        add_action('wp_loaded', [$this, 'late_init'], 20);
    }

    /**
     * Initialize adapters early - after WordPress and plugins are loaded
     */
    public function init(): void {
        // Load text domain for translations
        load_plugin_textdomain(
            'mcp-adapters',
            false,
            dirname(plugin_basename(MCP_ADAPTERS_PLUGIN_FILE)) . '/languages'
        );

        // Initialize adapters based on active plugins
        $this->initialize_adapters();
    }

    /**
     * Late initialization for adapters that need all plugins fully loaded
     */
    public function late_init(): void {
        // Any late initialization can happen here
        do_action('mcp_adapters_loaded');
    }

    /**
     * Initialize available adapters based on active plugins
     */
    private function initialize_adapters(): void {
        // FluentBoards Adapter
        if ($this->is_fluent_boards_active()) {
            new FluentBoardsAdapter();
        }

        // Future adapters can be added here:
        // if ($this->is_some_plugin_active()) {
        //     new SomePluginAdapter();
        // }

        // Hook for third-party adapters
        do_action('mcp_adapters_initialize', $this);
    }

    /**
     * Check if FluentBoards plugin is active and available
     *
     * @return bool True if FluentBoards is active and ready
     */
    private function is_fluent_boards_active(): bool {
        return defined('FLUENT_BOARDS') &&
               class_exists('\FluentBoards\App\Models\Board') &&
               (is_plugin_active('fluent-boards/fluent-boards.php') ||
                is_plugin_active('fluent-boards-pro/fluent-boards-pro.php') ||
                function_exists('fluent_boards'));
    }

    /**
     * Check if FluentBoards Pro features are available
     *
     * @return bool True if FluentBoards Pro is active
     */
    public static function is_fluent_boards_pro_active(): bool {
        return defined('FLUENT_BOARDS_PRO') ||
               is_plugin_active('fluent-boards-pro/fluent-boards-pro.php');
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
}