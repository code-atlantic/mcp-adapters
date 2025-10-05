<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm;

use MCP\Adapters\Adapters\FluentCrm\Abilities\CampaignAnalytics;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Campaigns;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Companies;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Funnels;
use MCP\Adapters\Adapters\FluentCrm\Abilities\FunnelSequences;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Lists;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Reporting;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Resources;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Sequences;
use MCP\Adapters\Adapters\FluentCrm\Abilities\SmartLinks;
use MCP\Adapters\Adapters\FluentCrm\Abilities\SubscriberNotes;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Subscribers;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Tags;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Templates;
use MCP\Adapters\Adapters\FluentCrm\Abilities\Webhooks;
use MCP\Adapters\Adapters\FluentCrm\Servers\Server;
use MCP\Adapters\Adapters\FluentCrm\Servers\ServerConfigurations;

// Load FluentCRM compatibility shims for missing helper functions
require_once __DIR__ . '/compatibility-shims.php';

/**
 * FluentCRM MCP Adapter
 *
 * Provides comprehensive MCP abilities for managing FluentCRM email marketing and CRM functionality.
 * This adapter enables AI models to interact with subscribers, lists, tags, campaigns, automations,
 * and analytics.
 *
 * Registers MCP servers for FluentCRM functionality including:
 * - Full: Complete FluentCRM functionality
 * - Tag Manager: Tag CRUD and subscriber tagging operations
 * - List Manager: List management and subscriber organization
 * - Campaign Manager: Email campaign creation and management
 * - Subscriber Manager: Contact management and segmentation
 */
class FluentCrmAdapter {

	/**
	 * Constructor - Initialize the FluentCRM adapter
	 */
	public function __construct() {

		// Only proceed if FluentCRM is active
		if ( ! $this->is_fluentcrm_active() ) {
			return;
		}

		// Hook into WordPress Abilities API initialization to register abilities
		// Priority 20 ensures did_action('abilities_api_init') returns > 0
		add_action( 'abilities_api_init', [ $this, 'register_abilities' ], 20 );

		// Register servers on the correct MCP adapter hook
		// Priority 10 ensures this runs after abilities_api_init has been triggered by the registry
		add_action( 'mcp_adapter_init', [ $this, 'ensure_abilities_registered' ], 5 );
		add_action( 'mcp_adapter_init', [ $this, 'register_all_servers' ], 10 );

		// Hook for initialization completion
		do_action( 'mcp_adapters_fluentcrm/loaded' );
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
	 * Register all FluentCRM abilities
	 */
	public function register_abilities(): void {

		// Core FluentCRM abilities
		new Subscribers();
		new SubscriberNotes();
		new Lists();
		new Tags();
		new Campaigns();
		new CampaignAnalytics();
		new Reporting();
		new Templates();
		new Companies();
		new Resources();
		new Webhooks();

		// Pro features (conditional registration handled within classes)
		new Sequences();
		new Funnels();
		new FunnelSequences();
		new SmartLinks();

		// Hook for additional abilities
		do_action( 'mcp_adapters_fluentcrm/abilities_registered' );
	}

	/**
	 * Track whether servers have been registered
	 */
	private static $servers_registered = false;

	/**
	 * Register all MCP servers for FluentCRM
	 *
	 * Called on mcp_adapter_init hook, which fires after abilities are registered
	 * and receives the MCP adapter instance as a parameter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
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

		// Register all configured servers
		$configurations = ServerConfigurations::get_all();
		foreach ( $configurations as $config ) {
			$server = new Server( $config );
			$server->register_with_adapter( $adapter );
		}

		// Mark servers as registered
		self::$servers_registered = true;

		// Hook for additional servers
		do_action( 'mcp_adapters_fluentcrm/servers_registered' );
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
							class_exists( '\WP\MCP\Core\McpAdapter' );

		return $abilities_api_active && $mcp_adapter_active;
	}

	/**
	 * Check if FluentCRM plugin is active and available
	 *
	 * @return bool True if FluentCRM is active and ready
	 */
	private function is_fluentcrm_active(): bool {
		return defined( 'FLUENTCRM' );
	}

	/**
	 * Check if FluentCRM Pro features are available
	 *
	 * @return bool True if FluentCampaign Pro is active
	 */
	public static function is_fluentcrm_pro_active(): bool {
		return defined( 'FLUENTCAMPAIGN' );
	}
}
