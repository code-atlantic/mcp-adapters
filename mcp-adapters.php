<?php
/**
 * Plugin Name: MCP Adapters
 * Plugin URI: https://github.com/wpmcp/mcp-adapters
 * Description: MCP adapters for WordPress plugins using the new WordPress Abilities API and MCP Adapter system.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: WP MCP Team
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mcp-adapters
 * Domain Path: /languages
 * Requires Plugins: wordpress/abilities-api, wordpress/mcp-adapter
 *
 * @package MCP\Adapters
 */

declare(strict_types=1);

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'MCP_ADAPTERS_VERSION', '0.1.0' );
define( 'MCP_ADAPTERS_PLUGIN_FILE', __FILE__ );
define( 'MCP_ADAPTERS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCP_ADAPTERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once __DIR__ . '/vendor/autoload.php';
} else {
	// Show admin notice if autoloader is missing
	add_action(
		'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>MCP Adapters:</strong> Composer dependencies not installed. ';
			echo 'Please run <code>composer install</code> in the plugin directory.';
			echo '</p></div>';
		}
	);
	return;
}

// Check for required dependencies
add_action(
	'plugins_loaded', function () {
		// Check if WordPress Abilities API is available
		if ( ! function_exists( 'wp_register_ability' ) ) {
			add_action(
				'admin_notices', function () {
					echo '<div class="notice notice-error"><p>';
					echo '<strong>MCP Adapters:</strong> WordPress Abilities API is required but not available. ';
					echo 'Please ensure the wordpress/abilities-api package is properly installed.';
					echo '</p></div>';
				}
			);
			return;
		}

		// Check if MCP Adapter is available
		if ( ! class_exists( 'WP\\MCP\\Plugin' ) ) {
			add_action(
				'admin_notices', function () {
					echo '<div class="notice notice-error"><p>';
					echo '<strong>MCP Adapters:</strong> MCP Adapter plugin is required but not available. ';
					echo 'Please install and activate the mcp-adapter plugin.';
					echo '</p></div>';
				}
			);
			return;
		}

		// Initialize the plugin
				new MCP\Adapters\Plugin();
	}, 20
); // Load after other plugins
