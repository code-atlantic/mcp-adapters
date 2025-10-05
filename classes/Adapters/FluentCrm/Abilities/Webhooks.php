<?php
/**
 * FluentCRM Incoming Webhook Management Abilities
 *
 * Provides comprehensive incoming webhook management tools including:
 * - Webhook CRUD operations (create, list, get, update, delete)
 * - Webhook URL generation and management
 * - Webhook field mapping configuration
 * - Webhook testing and validation
 *
 * Note: These are INCOMING webhooks for receiving data and creating contacts.
 * Outgoing webhooks are handled as funnel actions, not standalone features.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * Webhooks Ability Class
 *
 * Manages incoming webhooks in FluentCRM including URL generation, field mapping,
 * list/tag assignment, and contact creation from external data sources.
 *
 * Architecture Note: FluentCRM's Webhook model extends Meta model and uses 'webhook'
 * as object_type. Each webhook has a unique hash for URL generation.
 */
class Webhooks extends BaseAbility {

	/**
	 * Check if FluentCRM incoming webhooks are available
	 *
	 * @return bool True if webhook models are available
	 */
	private function are_webhooks_available(): bool {
		return class_exists( '\FluentCrm\App\Models\Webhook' );
	}

	/**
	 * Register all webhook-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if webhook models not available
		if ( ! $this->are_webhooks_available() ) {
			return;
		}

		// Webhook CRUD Operations
		$this->register_create_webhook();
		$this->register_list_webhooks();
		$this->register_get_webhook();
		$this->register_update_webhook();
		$this->register_delete_webhook();

		// Webhook Utility Operations
		$this->register_get_webhook_fields();
	}

	/**
	 * Register create-webhook ability
	 *
	 * @return void
	 */
	private function register_create_webhook(): void {
		wp_register_ability(
			'fluentcrm/create-webhook',
			[
				'label'               => 'FluentCRM Create Webhook',
				'description'         => 'Create a new incoming webhook to receive contact data from external sources. Returns webhook object with id, name, url (auto-generated with unique hash), status, assigned lists, tags, and companies (if enabled). The URL can be used to POST JSON data to create/update contacts.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'name' ],
					'properties' => [
						'name'      => [
							'type'        => 'string',
							'description' => 'Webhook name for identification',
						],
						'lists'     => [
							'type'        => 'array',
							'description' => 'List IDs to assign contacts to',
							'items'       => [
								'type' => 'integer',
							],
							'default'     => [],
						],
						'tags'      => [
							'type'        => 'array',
							'description' => 'Tag IDs to assign contacts',
							'items'       => [
								'type' => 'integer',
							],
							'default'     => [],
						],
						'companies' => [
							'type'        => 'array',
							'description' => 'Company IDs to assign contacts (requires Companies feature enabled)',
							'items'       => [
								'type' => 'integer',
							],
							'default'     => [],
						],
						'status'    => [
							'type'        => 'string',
							'description' => 'Default contact status for incoming data',
							'enum'        => [ 'subscribed', 'pending', 'unsubscribed' ],
							'default'     => 'subscribed',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_create_webhook' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register list-webhooks ability
	 *
	 * @return void
	 */
	private function register_list_webhooks(): void {
		wp_register_ability(
			'fluentcrm/list-webhooks',
			[
				'label'               => 'FluentCRM List Webhooks',
				'description'         => 'List all incoming webhooks with pagination support. Returns array of webhook objects containing id, name, url, status, lists, tags, and companies. Useful for webhook inventory and management.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Webhooks per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search webhooks by name',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_list_webhooks' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register get-webhook ability
	 *
	 * @return void
	 */
	private function register_get_webhook(): void {
		wp_register_ability(
			'fluentcrm/get-webhook',
			[
				'label'               => 'FluentCRM Get Webhook',
				'description'         => 'Get detailed webhook information including configuration and usage instructions. Returns complete webhook object with id, key (hash), name, url, status, lists, tags, companies, and available field mappings for integration.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'webhook_id' ],
					'properties' => [
						'webhook_id' => [
							'type'        => 'integer',
							'description' => 'Webhook ID to retrieve',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_get_webhook' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register update-webhook ability
	 *
	 * @return void
	 */
	private function register_update_webhook(): void {
		wp_register_ability(
			'fluentcrm/update-webhook',
			[
				'label'               => 'FluentCRM Update Webhook',
				'description'         => 'Update webhook configuration including name, lists, tags, companies, and status. Returns updated webhook object. Note: Webhook URL and hash cannot be changed after creation.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'webhook_id' ],
					'properties' => [
						'webhook_id' => [
							'type'        => 'integer',
							'description' => 'Webhook ID to update',
						],
						'name'       => [
							'type'        => 'string',
							'description' => 'New webhook name',
						],
						'lists'      => [
							'type'        => 'array',
							'description' => 'Update assigned list IDs',
							'items'       => [
								'type' => 'integer',
							],
						],
						'tags'       => [
							'type'        => 'array',
							'description' => 'Update assigned tag IDs',
							'items'       => [
								'type' => 'integer',
							],
						],
						'companies'  => [
							'type'        => 'array',
							'description' => 'Update assigned company IDs',
							'items'       => [
								'type' => 'integer',
							],
						],
						'status'     => [
							'type'        => 'string',
							'description' => 'Update default contact status',
							'enum'        => [ 'subscribed', 'pending', 'unsubscribed' ],
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_update_webhook' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register delete-webhook ability
	 *
	 * @return void
	 */
	private function register_delete_webhook(): void {
		wp_register_ability(
			'fluentcrm/delete-webhook',
			[
				'label'               => 'FluentCRM Delete Webhook',
				'description'         => 'Delete an incoming webhook permanently. Returns deletion confirmation with webhook_id, name, and deleted_at timestamp. Requires explicit confirmation to prevent accidental deletion of active webhooks.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'webhook_id', 'confirm_delete' ],
					'properties' => [
						'webhook_id'     => [
							'type'        => 'integer',
							'description' => 'Webhook ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_delete_webhook' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register get-webhook-fields ability
	 *
	 * @return void
	 */
	private function register_get_webhook_fields(): void {
		wp_register_ability(
			'fluentcrm/get-webhook-fields',
			[
				'label'               => 'FluentCRM Get Webhook Fields',
				'description'         => 'Get available field mappings for webhook integration. Returns standard contact fields (email, first_name, last_name, etc.) and custom fields with their keys and labels. Use these fields to construct proper webhook POST payloads.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_webhook_fields' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'webhooks',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Execute create-webhook ability
	 *
	 * @param array<string, mixed> $args Webhook creation parameters
	 * @return array<string, mixed> Success/error response with webhook data
	 */
	public function execute_create_webhook( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			// Prepare webhook data
			$webhook_data = [
				'name'   => sanitize_text_field( $args['name'] ),
				'lists'  => $args['lists'] ?? [],
				'tags'   => $args['tags'] ?? [],
				'status' => $args['status'] ?? 'subscribed',
			];

			// Add companies if feature is enabled
			if ( method_exists( '\FluentCrm\App\Services\Helper', 'isCompanyEnabled' ) &&
				\FluentCrm\App\Services\Helper::isCompanyEnabled() ) {
				$webhook_data['companies'] = $args['companies'] ?? [];
			}

			// Create the webhook using the model's store method
			$webhook = ( new \FluentCrm\App\Models\Webhook() )->store( $webhook_data );

			if ( ! $webhook ) {
				return $this->get_error_response( 'Failed to create webhook', 'create_failed' );
			}

			return $this->get_success_response(
				[
					'webhook' => $webhook->toArray(),
				],
				'Webhook created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create webhook: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list-webhooks ability
	 *
	 * @param array<string, mixed> $args List parameters
	 * @return array<string, mixed> Success/error response with webhooks list
	 */
	public function execute_list_webhooks( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';

			// Build query
			$query = \FluentCrm\App\Models\Webhook::query();

			// Apply search if provided
			if ( ! empty( $search ) ) {
				$query->where( 'value', 'LIKE', '%"name":"' . $search . '%"' );
			}

			// Get total count
			$total = $query->count();

			// Get paginated results
			$offset   = ( $page - 1 ) * $per_page;
			$webhooks = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$webhook_list = [];
			foreach ( $webhooks as $webhook ) {
				$webhook_list[] = $webhook->toArray();
			}

			return $this->get_success_response(
				[
					'webhooks'    => $webhook_list,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Webhooks retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list webhooks: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get-webhook ability
	 *
	 * @param array<string, mixed> $args Webhook retrieval parameters
	 * @return array<string, mixed> Success/error response with webhook details
	 */
	public function execute_get_webhook( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			$webhook_id = intval( $args['webhook_id'] );

			if ( $webhook_id <= 0 ) {
				return $this->get_error_response( 'Invalid webhook ID', 'invalid_webhook_id' );
			}

			$webhook = \FluentCrm\App\Models\Webhook::find( $webhook_id );

			if ( ! $webhook ) {
				return $this->get_error_response( 'Webhook not found', 'webhook_not_found' );
			}

			// Get available fields for this webhook
			$available_fields = $webhook->getFields();

			return $this->get_success_response(
				[
					'webhook'          => $webhook->toArray(),
					'available_fields' => $available_fields,
				],
				'Webhook retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get webhook: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update-webhook ability
	 *
	 * @param array<string, mixed> $args Webhook update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_webhook( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			$webhook_id = intval( $args['webhook_id'] );

			if ( $webhook_id <= 0 ) {
				return $this->get_error_response( 'Invalid webhook ID', 'invalid_webhook_id' );
			}

			$webhook = \FluentCrm\App\Models\Webhook::find( $webhook_id );

			if ( ! $webhook ) {
				return $this->get_error_response( 'Webhook not found', 'webhook_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['name'] ) ) {
				$update_data['name'] = sanitize_text_field( $args['name'] );
			}

			if ( isset( $args['lists'] ) ) {
				$update_data['lists'] = $args['lists'];
			}

			if ( isset( $args['tags'] ) ) {
				$update_data['tags'] = $args['tags'];
			}

			if ( isset( $args['companies'] ) ) {
				$update_data['companies'] = $args['companies'];
			}

			if ( isset( $args['status'] ) ) {
				$update_data['status'] = $args['status'];
			}

			// Update the webhook using saveChanges method
			if ( ! empty( $update_data ) ) {
				$webhook->saveChanges( $update_data );
				$webhook = \FluentCrm\App\Models\Webhook::find( $webhook_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'webhook' => $webhook->toArray(),
				],
				'Webhook updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update webhook: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete-webhook ability
	 *
	 * @param array<string, mixed> $args Webhook deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_webhook( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			$webhook_id     = intval( $args['webhook_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $webhook_id <= 0 ) {
				return $this->get_error_response( 'Invalid webhook ID', 'invalid_webhook_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$webhook = \FluentCrm\App\Models\Webhook::find( $webhook_id );

			if ( ! $webhook ) {
				return $this->get_error_response( 'Webhook not found', 'webhook_not_found' );
			}

			$webhook_data = $webhook->toArray();
			$webhook_name = $webhook_data['value']['name'] ?? 'Unnamed Webhook';

			// Delete the webhook
			$webhook->delete();

			return $this->get_success_response(
				[
					'webhook_id'   => $webhook_id,
					'webhook_name' => $webhook_name,
					'deleted_at'   => current_time( 'mysql' ),
				],
				'Webhook deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete webhook: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute get-webhook-fields ability
	 *
	 * @param array<string, mixed> $args Parameters (none required)
	 * @return array<string, mixed> Success/error response with available fields
	 */
	public function execute_get_webhook_fields( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\Webhook' ) ) {
			return $this->get_error_response( 'FluentCRM Webhook model not available', 'model_not_available' );
		}

		try {
			$webhook = new \FluentCrm\App\Models\Webhook();
			$fields  = $webhook->getFields();

			return $this->get_success_response(
				[
					'fields'        => $fields['fields'] ?? [],
					'custom_fields' => $fields['custom_fields'] ?? [],
					'usage_note'    => 'POST JSON data to webhook URL with these field keys to create/update contacts',
				],
				'Webhook fields retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get webhook fields: ' . $e->getMessage(), 'get_fields_failed' );
		}
	}
}
