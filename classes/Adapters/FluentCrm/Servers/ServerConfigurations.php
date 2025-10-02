<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Servers;

/**
 * FluentCRM MCP Server Configurations
 *
 * Centralized configuration for all FluentCRM MCP servers.
 * Each server is defined by ability groups and prompts rather than duplicate code.
 */
class ServerConfigurations {

	/**
	 * Get all server configurations
	 *
	 * @return array Array of server configurations
	 */
	public static function get_all(): array {
		return [
			'full'             => self::get_full_server_config(),
			'marketer'         => self::get_marketer_config(),
			'automation'       => self::get_automation_manager_config(),
			'list-manager'     => self::get_list_manager_config(),
			'analyst'          => self::get_analyst_config(),
			'campaign-manager' => self::get_campaign_manager_config(),
		];
	}

	/**
	 * Full FluentCRM server - all abilities
	 *
	 * @return array
	 */
	private static function get_full_server_config(): array {
		return [
			'server_id'       => 'fluentcrm-full',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm',
			'name'            => 'FluentCRM Full Access',
			'description'     => 'Complete FluentCRM marketing automation platform with all features - subscribers, campaigns, automations, analytics',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'subscriber',
				'campaign',
				'campaign_analytics',
				'list',
				'tag',
				'funnel',
				'sequence',
				'segment',
				'template',
				'smart_link',
				'reporting',
				'webhook',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}

	/**
	 * Marketer server - campaign and list management
	 *
	 * @return array
	 */
	private static function get_marketer_config(): array {
		return [
			'server_id'       => 'fluentcrm-marketer',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm/marketer',
			'name'            => 'FluentCRM Marketer',
			'description'     => 'Marketing campaign operations - create campaigns, manage lists/tags, send sequences, analyze performance',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'campaign',
				'campaign_analytics',
				'list',
				'tag',
				'sequence',
				'reporting',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}

	/**
	 * Automation Manager server - funnels and sequences
	 *
	 * @return array
	 */
	private static function get_automation_manager_config(): array {
		return [
			'server_id'       => 'fluentcrm-automation',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm/automation',
			'name'            => 'FluentCRM Automation Manager',
			'description'     => 'Marketing automation workflows - funnels, sequences, segments, smart links, trigger-based automations',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'funnel',
				'sequence',
				'segment',
				'smart_link',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}

	/**
	 * List Manager server - subscriber and list operations
	 *
	 * @return array
	 */
	private static function get_list_manager_config(): array {
		return [
			'server_id'       => 'fluentcrm-lists',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm/lists',
			'name'            => 'FluentCRM List Manager',
			'description'     => 'Subscriber and list management - create/update subscribers, organize lists, apply tags, segment audiences',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'subscriber',
				'list',
				'tag',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}

	/**
	 * Analyst server - reporting and analytics
	 *
	 * @return array
	 */
	private static function get_analyst_config(): array {
		return [
			'server_id'       => 'fluentcrm-analyst',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm/analyst',
			'name'            => 'FluentCRM Analyst',
			'description'     => 'Marketing analytics and reporting - campaign performance, subscriber metrics, engagement analysis',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'reporting',
				'campaign_analytics',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}

	/**
	 * Campaign Manager server - campaign operations
	 *
	 * @return array
	 */
	private static function get_campaign_manager_config(): array {
		return [
			'server_id'       => 'fluentcrm-campaigns',
			'route_namespace' => 'mcp-adapters/v1',
			'route'           => 'fluentcrm/campaigns',
			'name'            => 'FluentCRM Campaign Manager',
			'description'     => 'Campaign management and execution - create campaigns, manage templates, analyze performance metrics',
			'version'         => '0.1.0',
			'ability_groups'  => [
				'campaign',
				'template',
				'campaign_analytics',
			],
			'prompts'         => [],
			'resources'       => [],
		];
	}
}
