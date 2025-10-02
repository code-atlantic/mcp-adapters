<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm;

/**
 * Base class for FluentCRM Abilities
 *
 * Provides common functionality for all FluentCRM abilities including
 * permission checks, validation methods, and utility functions.
 *
 * @package MCP\Adapters\Adapters\FluentCrm
 */
abstract class BaseAbility {

	/**
	 * Constructor - Initialize the ability class
	 */
	public function __construct() {

		// Check if FluentCRM is active
		if ( ! $this->is_fluentcrm_active() ) {
			return;
		}

		$this->register_abilities();
	}

	/**
	 * Register all abilities for this ability class
	 *
	 * Each concrete ability class must implement this method to register
	 * its specific abilities using wp_register_ability().
	 *
	 * @return void
	 */
	abstract protected function register_abilities(): void;

	/**
	 * Check if FluentCRM plugin is active and available
	 *
	 * Verifies that FluentCRM is properly loaded by checking for:
	 * - FLUENTCRM constant definition
	 * - Core Subscriber model class availability
	 *
	 * @return bool True if FluentCRM is active and ready
	 */
	protected function is_fluentcrm_active(): bool {
		// Primary check: FluentCRM constant and core class availability
		return defined( 'FLUENTCRM' ) && class_exists( '\FluentCrm\App\Models\Subscriber' );
	}

	/**
	 * Check if user has permission to manage FluentCRM
	 *
	 * Checks for administrative capabilities including:
	 * - manage_options (WordPress admin)
	 * - fluentcrm_manage_contacts (FluentCRM admin capability)
	 *
	 * @return bool True if user has management permission
	 */
	public function can_manage_fluentcrm(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if user can manage FluentCRM
		if ( current_user_can( 'manage_options' ) || current_user_can( 'fluentcrm_manage_contacts' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		return false;
	}

	/**
	 * Check if current user can view contacts
	 *
	 * More permissive than management - allows viewing without modification.
	 * Users with management permissions automatically have view permissions.
	 *
	 * @return bool True if user can view contacts
	 */
	public function can_view_contacts(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Anyone who can manage can also view
		if ( $this->can_manage_fluentcrm() ) {
			return true;
		}

		// Check FluentCRM view permissions
		if ( current_user_can( 'fluentcrm_view_contacts' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		return false;
	}

	/**
	 * Check if current user can manage campaigns
	 *
	 * Specific permission for campaign operations including:
	 * - Creating and editing campaigns
	 * - Managing email sequences
	 * - Campaign scheduling and analytics
	 *
	 * @return bool True if user can manage campaigns
	 */
	public function can_manage_campaigns(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Check if user has campaign management capability
		if ( current_user_can( 'manage_options' ) || current_user_can( 'fluentcrm_manage_campaigns' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return true;
		}

		return false;
	}

	/**
	 * Validate subscriber ID exists in FluentCRM
	 *
	 * @param int $subscriber_id Subscriber ID to validate
	 * @return bool True if subscriber exists
	 */
	protected function subscriber_exists( int $subscriber_id ): bool {
		if ( ! class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
			return false;
		}

		try {
			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			return ! empty( $subscriber );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Validate campaign ID exists in FluentCRM
	 *
	 * @param int $campaign_id Campaign ID to validate
	 * @return bool True if campaign exists
	 */
	protected function campaign_exists( int $campaign_id ): bool {
		if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return false;
		}

		try {
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );
			return ! empty( $campaign );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Validate list ID exists in FluentCRM
	 *
	 * @param int $list_id List ID to validate
	 * @return bool True if list exists
	 */
	protected function list_exists( int $list_id ): bool {
		if ( ! class_exists( '\FluentCrm\App\Models\Lists' ) ) {
			return false;
		}

		try {
			$list = \FluentCrm\App\Models\Lists::find( $list_id );
			return ! empty( $list );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Validate tag ID exists in FluentCRM
	 *
	 * @param int $tag_id Tag ID to validate
	 * @return bool True if tag exists
	 */
	protected function tag_exists( int $tag_id ): bool {
		if ( ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
			return false;
		}

		try {
			$tag = \FluentCrm\App\Models\Tag::find( $tag_id );
			return ! empty( $tag );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get standardized error response
	 *
	 * Provides consistent error formatting across all FluentCRM abilities.
	 *
	 * @param string $message Error message describing what went wrong
	 * @param string $code    Error code for categorization (default: 'error')
	 * @return array{success: false, error: array{code: string, message: string}} Error response array
	 */
	protected function get_error_response( string $message, string $code = 'error' ): array {
		return [
			'success' => false,
			'error'   => [
				'code'    => $code,
				'message' => $message,
			],
		];
	}

	/**
	 * Get standardized success response
	 *
	 * Provides consistent success formatting across all FluentCRM abilities.
	 *
	 * @param array<string, mixed> $data    Response data payload (default: empty array)
	 * @param string               $message Success message (default: 'Success')
	 * @return array{success: true, message: string, data: array<string, mixed>} Success response array
	 */
	protected function get_success_response( array $data = [], string $message = 'Success' ): array {
		return [
			'success' => true,
			'message' => $message,
			'data'    => $data,
		];
	}
}
