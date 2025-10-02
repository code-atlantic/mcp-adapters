<?php
/**
 * FluentCRM Ability Registry
 *
 * Central registry for all FluentCRM tool abilities organized by domain.
 * Each method returns an array of ability names that will be populated as tools are implemented.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Servers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Servers;

/**
 * FluentCRM Ability Registry
 *
 * Static registry class that defines all available FluentCRM tool abilities
 * organized by functional domain (subscribers, lists, campaigns, etc.).
 */
class AbilityRegistry {
	/**
	 * Get subscriber management abilities
	 *
	 * Includes subscriber CRUD operations, bulk operations, and list/tag management.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_subscriber_abilities(): array {
		return [
			'fluentcrm/create-subscriber',
			'fluentcrm/list-subscribers',
			'fluentcrm/get-subscriber',
			'fluentcrm/update-subscriber',
			'fluentcrm/delete-subscriber',
			'fluentcrm/bulk-import-subscribers',
			'fluentcrm/bulk-update-subscribers',
			'fluentcrm/bulk-delete-subscribers',
			'fluentcrm/add-subscriber-to-list',
			'fluentcrm/remove-subscriber-from-list',
			'fluentcrm/add-subscriber-tag',
			'fluentcrm/remove-subscriber-tag',
			'fluentcrm/update-subscriber-status',
			'fluentcrm/merge-subscribers',
			'fluentcrm/search-subscribers',
		];
	}

	/**
	 * Get list management abilities
	 *
	 * Includes list CRUD operations, statistics, and list management.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_list_abilities(): array {
		return [
			'fluentcrm/create-list',
			'fluentcrm/list-lists',
			'fluentcrm/get-list',
			'fluentcrm/update-list',
			'fluentcrm/delete-list',
			'fluentcrm/get-list-subscribers',
			'fluentcrm/get-list-stats',
			'fluentcrm/duplicate-list',
			'fluentcrm/merge-lists',
		];
	}

	/**
	 * Get tag management abilities
	 *
	 * Includes tag CRUD operations, statistics, and tag management.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_tag_abilities(): array {
		return [
			'fluentcrm/create-tag',
			'fluentcrm/list-tags',
			'fluentcrm/get-tag',
			'fluentcrm/update-tag',
			'fluentcrm/delete-tag',
			'fluentcrm/get-tag-subscribers',
			'fluentcrm/get-tag-stats',
			'fluentcrm/bulk-apply-tags',
			'fluentcrm/bulk-remove-tags',
		];
	}

	/**
	 * Get campaign management abilities
	 *
	 * Includes campaign CRUD operations, scheduling, and sending.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_campaign_abilities(): array {
		return [
			'fluentcrm/create-campaign',
			'fluentcrm/list-campaigns',
			'fluentcrm/get-campaign',
			'fluentcrm/update-campaign',
			'fluentcrm/delete-campaign',
			'fluentcrm/duplicate-campaign',
			'fluentcrm/schedule-campaign',
			'fluentcrm/send-campaign',
			'fluentcrm/pause-campaign',
			'fluentcrm/resume-campaign',
			'fluentcrm/cancel-campaign',
			'fluentcrm/test-send-campaign',
			'fluentcrm/preview-campaign',
		];
	}

	/**
	 * Get campaign analytics abilities
	 *
	 * Includes analytics, tracking, and metrics for campaigns.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_campaign_analytics_abilities(): array {
		return [
			'fluentcrm/get-campaign-analytics',
			'fluentcrm/get-campaign-contacts',
			'fluentcrm/get-campaign-clicks',
			'fluentcrm/get-campaign-opens',
			'fluentcrm/get-email-performance-by-subject',
			'fluentcrm/get-send-time-optimization',
			'fluentcrm/compare-campaigns',
		];
	}

	/**
	 * Get funnel (automation) abilities
	 *
	 * Includes automation workflow management (FluentCRM Pro feature).
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_funnel_abilities(): array {
		return [];
	}

	/**
	 * Get sequence abilities
	 *
	 * Includes email sequence management (FluentCRM Pro feature).
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_sequence_abilities(): array {
		return [
			'fluentcrm/create-sequence',
			'fluentcrm/list-sequences',
			'fluentcrm/get-sequence',
			'fluentcrm/update-sequence',
			'fluentcrm/delete-sequence',
			'fluentcrm/add-subscriber-to-sequence',
			'fluentcrm/remove-subscriber-from-sequence',
			'fluentcrm/get-sequence-performance',
		];
	}

	/**
	 * Get segment abilities
	 *
	 * DISABLED: Segment model doesn't exist in FluentCampaign Pro.
	 * Segments use Meta model + filter hooks, not a dedicated model.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_segment_abilities(): array {
		return [];
	}

	/**
	 * Get company management abilities
	 *
	 * Includes company CRUD operations and management.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_company_abilities(): array {
		return [
			'fluentcrm/create-company',
			'fluentcrm/list-companies',
			'fluentcrm/get-company',
			'fluentcrm/update-company',
			'fluentcrm/delete-company',
			'fluentcrm/add-subscriber-to-company',
			'fluentcrm/remove-subscriber-from-company',
			'fluentcrm/get-company-subscribers',
		];
	}

	/**
	 * Get template management abilities
	 *
	 * Includes email template CRUD operations and management.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_template_abilities(): array {
		return [
			'fluentcrm/create-template',
			'fluentcrm/list-templates',
			'fluentcrm/get-template',
			'fluentcrm/update-template',
			'fluentcrm/delete-template',
			'fluentcrm/duplicate-template',
			'fluentcrm/apply-template-to-campaign',
		];
	}

	/**
	 * Get smart link abilities
	 *
	 * Includes smart link management (FluentCRM Pro feature).
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_smart_link_abilities(): array {
		return [];
	}

	/**
	 * Get reporting abilities
	 *
	 * Includes dashboard analytics, reporting, and data exports.
	 *
	 * @return array<string> Array of ability names in format 'fluentcrm/action-name'
	 */
	public static function get_reporting_abilities(): array {
		return [];
	}
}
