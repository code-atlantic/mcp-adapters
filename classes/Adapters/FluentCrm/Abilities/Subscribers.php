<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Subscriber Abilities
 *
 * Registers WordPress abilities for FluentCRM subscriber (contact) management operations
 * using the WordPress Abilities API pattern.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 */
class Subscribers extends BaseAbility {

	/**
	 * Register all subscriber-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_create_subscriber();
		$this->register_list_subscribers();
		$this->register_get_subscriber();
		$this->register_update_subscriber();
		$this->register_delete_subscriber();
		$this->register_bulk_import_subscribers();
		$this->register_bulk_update_subscribers();
		$this->register_bulk_delete_subscribers();
		$this->register_add_subscriber_to_list();
		$this->register_remove_subscriber_from_list();
		$this->register_add_subscriber_tag();
		$this->register_remove_subscriber_tag();
		$this->register_update_subscriber_status();
		$this->register_merge_subscribers();
		$this->register_search_subscribers();
	}

	/**
	 * Register create subscriber ability
	 */
	private function register_create_subscriber(): void {
		wp_register_ability(
			'fluentcrm/create-subscriber',
			[
				'label'               => 'Create subscriber',
				'description'         => 'Create a new contact with email, name, status, address, and custom fields. Optionally assign to lists and tags. Returns created subscriber with all fields. Relations (tags, lists) NOT included by default - use get-subscriber with "with" parameter to load relationships.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'email'          => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Contact email address (required)',
						],
						'first_name'     => [
							'type'        => 'string',
							'description' => 'Contact first name',
						],
						'last_name'      => [
							'type'        => 'string',
							'description' => 'Contact last name',
						],
						'status'         => [
							'type'        => 'string',
							'description' => 'Subscription status',
							'enum'        => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
							'default'     => 'subscribed',
						],
						'phone'          => [
							'type'        => 'string',
							'description' => 'Contact phone number',
						],
						'address_line_1' => [
							'type'        => 'string',
							'description' => 'Address line 1',
						],
						'address_line_2' => [
							'type'        => 'string',
							'description' => 'Address line 2',
						],
						'city'           => [
							'type'        => 'string',
							'description' => 'City',
						],
						'state'          => [
							'type'        => 'string',
							'description' => 'State or province',
						],
						'postal_code'    => [
							'type'        => 'string',
							'description' => 'Postal/ZIP code',
						],
						'country'        => [
							'type'        => 'string',
							'description' => 'Country code - e.g., US, UK, CA, DE, FR (ISO 3166-1 alpha-2)',
							'pattern'     => '^[A-Z]{2}$',
						],
						'timezone'       => [
							'type'        => 'string',
							'description' => 'Timezone identifier - e.g., America/New_York, Europe/London, Asia/Tokyo (IANA timezone database)',
						],
						'date_of_birth'  => [
							'type'        => 'string',
							'format'      => 'date',
							'description' => 'Date of birth in YYYY-MM-DD format - e.g., 1990-05-15',
							'pattern'     => '^\d{4}-\d{2}-\d{2}$',
						],
						'custom_values'  => [
							'type'        => 'object',
							'description' => 'Custom field values as key-value pairs',
						],
						'tags'           => [
							'type'        => 'array',
							'description' => 'Array of tag IDs to assign',
							'items'       => [
								'type' => 'integer',
							],
						],
						'lists'          => [
							'type'        => 'array',
							'description' => 'Array of list IDs to assign',
							'items'       => [
								'type' => 'integer',
							],
						],
					],
					'required'   => [ 'email' ],
				],
				'execute_callback'    => [ $this, 'execute_create_subscriber' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register list subscribers ability
	 */
	private function register_list_subscribers(): void {
		wp_register_ability(
			'fluentcrm/list-subscribers',
			[
				'label'               => 'List subscribers',
				'description'         => 'List and search contacts with pagination (page, per_page), filtering (status, tags, lists), search (email, name, phone), and sorting (orderby, order). Returns subscribers array with pagination metadata (total, per_page, total_pages).',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Number of subscribers per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search subscribers by email, name, or phone',
						],
						'status'   => [
							'type'        => 'string',
							'description' => 'Filter by subscription status',
							'enum'        => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
						],
						'tags'     => [
							'type'        => 'array',
							'description' => 'Filter by tag IDs (subscribers must have ALL tags)',
							'items'       => [
								'type' => 'integer',
							],
						],
						'lists'    => [
							'type'        => 'array',
							'description' => 'Filter by list IDs (subscribers must be in ALL lists)',
							'items'       => [
								'type' => 'integer',
							],
						],
						'orderby'  => [
							'type'        => 'string',
							'description' => 'Order results by field',
							'enum'        => [ 'id', 'email', 'first_name', 'last_name', 'created_at', 'updated_at' ],
							'default'     => 'created_at',
						],
						'order'    => [
							'type'        => 'string',
							'description' => 'Sort direction',
							'enum'        => [ 'ASC', 'DESC' ],
							'default'     => 'DESC',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_subscribers' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register get subscriber ability
	 */
	private function register_get_subscriber(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber',
			[
				'label'               => 'Get subscriber details',
				'description'         => 'Get detailed contact information by ID or email. Use "with" parameter to load relationships (tags, lists, stats, custom_fields). Returns complete subscriber data with optional relationship arrays based on "with" selection.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to retrieve',
						],
						'email'         => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Subscriber email to retrieve (alternative to ID)',
						],
						'with'          => [
							'type'        => 'array',
							'description' => 'Include related data (tags, lists, stats)',
							'items'       => [
								'type' => 'string',
								'enum' => [ 'tags', 'lists', 'stats', 'custom_fields' ],
							],
							'default'     => [ 'tags', 'lists' ],
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_get_subscriber' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register update subscriber ability
	 */
	private function register_update_subscriber(): void {
		wp_register_ability(
			'fluentcrm/update-subscriber',
			[
				'label'               => 'Update subscriber',
				'description'         => 'Update contact email, name, address, phone, timezone, or custom fields. Supports partial updates (any combination of fields). Returns updated subscriber with all fields.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id'  => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to update (required)',
						],
						'email'          => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'New email address',
						],
						'first_name'     => [
							'type'        => 'string',
							'description' => 'New first name',
						],
						'last_name'      => [
							'type'        => 'string',
							'description' => 'New last name',
						],
						'phone'          => [
							'type'        => 'string',
							'description' => 'New phone number',
						],
						'address_line_1' => [
							'type'        => 'string',
							'description' => 'New address line 1',
						],
						'address_line_2' => [
							'type'        => 'string',
							'description' => 'New address line 2',
						],
						'city'           => [
							'type'        => 'string',
							'description' => 'New city',
						],
						'state'          => [
							'type'        => 'string',
							'description' => 'New state or province',
						],
						'postal_code'    => [
							'type'        => 'string',
							'description' => 'New postal/ZIP code',
						],
						'country'        => [
							'type'        => 'string',
							'description' => 'New country code',
						],
						'timezone'       => [
							'type'        => 'string',
							'description' => 'New timezone identifier',
						],
						'date_of_birth'  => [
							'type'        => 'string',
							'format'      => 'date',
							'description' => 'New date of birth (YYYY-MM-DD format)',
						],
						'custom_values'  => [
							'type'        => 'object',
							'description' => 'Custom field values to update',
						],
					],
					'required'   => [ 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_subscriber' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register delete subscriber ability
	 */
	private function register_delete_subscriber(): void {
		wp_register_ability(
			'fluentcrm/delete-subscriber',
			[
				'label'               => 'Delete subscriber',
				'description'         => 'Permanently delete a contact and all related data (tags, lists, activity history). Requires confirmation. Cannot be undone. Returns deleted subscriber ID, email, and deletion timestamp.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id'  => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
					'required'   => [ 'subscriber_id', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_subscriber' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register bulk import subscribers ability
	 */
	private function register_bulk_import_subscribers(): void {
		wp_register_ability(
			'fluentcrm/bulk-import-subscribers',
			[
				'label'               => 'Bulk import subscribers',
				'description'         => 'Import multiple contacts from array with optional list/tag assignment to all. Supports update_existing flag to modify existing contacts. Returns import summary with counts (total, imported, updated, failed) and error details.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscribers'     => [
							'type'        => 'array',
							'description' => 'Array of subscriber objects to import',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'email'      => [
										'type' => 'string',
									],
									'first_name' => [
										'type' => 'string',
									],
									'last_name'  => [
										'type' => 'string',
									],
									'status'     => [
										'type' => 'string',
										'enum' => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
									],
								],
							],
						],
						'tags'            => [
							'type'        => 'array',
							'description' => 'Tag IDs to assign to all imported subscribers',
							'items'       => [
								'type' => 'integer',
							],
						],
						'lists'           => [
							'type'        => 'array',
							'description' => 'List IDs to assign to all imported subscribers',
							'items'       => [
								'type' => 'integer',
							],
						],
						'update_existing' => [
							'type'        => 'boolean',
							'description' => 'Update existing subscribers if email matches',
							'default'     => false,
						],
					],
					'required'   => [ 'subscribers' ],
				],
				'execute_callback'    => [ $this, 'execute_bulk_import_subscribers' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register bulk update subscribers ability
	 */
	private function register_bulk_update_subscribers(): void {
		wp_register_ability(
			'fluentcrm/bulk-update-subscribers',
			[
				'label'               => 'Bulk update subscribers',
				'description'         => 'Batch update status, timezone, country, or custom fields for multiple subscribers. All specified subscribers receive same updates. Returns update summary with counts (total, updated, failed).',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs to update',
							'items'       => [
								'type' => 'integer',
							],
						],
						'update_data'    => [
							'type'        => 'object',
							'description' => 'Fields to update on all selected subscribers',
							'properties'  => [
								'status'        => [
									'type' => 'string',
									'enum' => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
								],
								'timezone'      => [
									'type' => 'string',
								],
								'country'       => [
									'type' => 'string',
								],
								'custom_values' => [
									'type' => 'object',
								],
							],
						],
					],
					'required'   => [ 'subscriber_ids', 'update_data' ],
				],
				'execute_callback'    => [ $this, 'execute_bulk_update_subscribers' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register bulk delete subscribers ability
	 */
	private function register_bulk_delete_subscribers(): void {
		wp_register_ability(
			'fluentcrm/bulk-delete-subscribers',
			[
				'label'               => 'Bulk delete subscribers',
				'description'         => 'Permanently delete multiple contacts. Requires confirmation. Cannot be undone. Returns deletion summary with counts (total, deleted, failed).',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs to delete',
							'items'       => [
								'type' => 'integer',
							],
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
					'required'   => [ 'subscriber_ids', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_bulk_delete_subscribers' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register add subscriber to list ability
	 */
	private function register_add_subscriber_to_list(): void {
		wp_register_ability(
			'fluentcrm/add-subscriber-to-list',
			[
				'label'               => 'Add subscriber to list',
				'description'         => 'Assign a contact to a FluentCRM list',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID',
						],
						'list_id'       => [
							'type'        => 'integer',
							'description' => 'List ID to assign subscriber to',
						],
					],
					'required'   => [ 'subscriber_id', 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_add_subscriber_to_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register remove subscriber from list ability
	 */
	private function register_remove_subscriber_from_list(): void {
		wp_register_ability(
			'fluentcrm/remove-subscriber-from-list',
			[
				'label'               => 'Remove subscriber from list',
				'description'         => 'Remove a contact from a FluentCRM list',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID',
						],
						'list_id'       => [
							'type'        => 'integer',
							'description' => 'List ID to remove subscriber from',
						],
					],
					'required'   => [ 'subscriber_id', 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_subscriber_from_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register add subscriber tag ability
	 */
	private function register_add_subscriber_tag(): void {
		wp_register_ability(
			'fluentcrm/add-subscriber-tag',
			[
				'label'               => 'Add tag to subscriber',
				'description'         => 'Add a tag to a FluentCRM contact',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID',
						],
						'tag_id'        => [
							'type'        => 'integer',
							'description' => 'Tag ID to add to subscriber',
						],
					],
					'required'   => [ 'subscriber_id', 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_add_subscriber_tag' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register remove subscriber tag ability
	 */
	private function register_remove_subscriber_tag(): void {
		wp_register_ability(
			'fluentcrm/remove-subscriber-tag',
			[
				'label'               => 'Remove tag from subscriber',
				'description'         => 'Remove a tag from a FluentCRM contact',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID',
						],
						'tag_id'        => [
							'type'        => 'integer',
							'description' => 'Tag ID to remove from subscriber',
						],
					],
					'required'   => [ 'subscriber_id', 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_subscriber_tag' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register update subscriber status ability
	 */
	private function register_update_subscriber_status(): void {
		wp_register_ability(
			'fluentcrm/update-subscriber-status',
			[
				'label'               => 'Update subscriber status',
				'description'         => 'Change contact subscription status to subscribed, unsubscribed, pending, bounced, or complained. Returns subscriber ID and new status.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID',
						],
						'status'        => [
							'type'        => 'string',
							'description' => 'New subscription status',
							'enum'        => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
						],
					],
					'required'   => [ 'subscriber_id', 'status' ],
				],
				'execute_callback'    => [ $this, 'execute_update_subscriber_status' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register merge subscribers ability
	 */
	private function register_merge_subscribers(): void {
		wp_register_ability(
			'fluentcrm/merge-subscribers',
			[
				'label'               => 'Merge duplicate subscribers',
				'description'         => 'Merge duplicate contacts into primary subscriber, combining all tags, lists, and activity history. Merged contacts are permanently deleted. Returns primary subscriber ID and merge count.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'primary_subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Primary subscriber ID to keep',
						],
						'merge_subscriber_ids'  => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs to merge into primary (will be deleted)',
							'items'       => [
								'type' => 'integer',
							],
						],
					],
					'required'   => [ 'primary_subscriber_id', 'merge_subscriber_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_merge_subscribers' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Register search subscribers ability
	 */
	private function register_search_subscribers(): void {
		wp_register_ability(
			'fluentcrm/search-subscribers',
			[
				'label'               => 'Advanced search subscribers',
				'description'         => 'Advanced contact search with complex filters (email_contains, name_contains, has_tags, has_lists, status_in, country, created_after/before, last_activity_after) and pagination. Returns filtered subscribers array with pagination metadata.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'filters'  => [
							'type'        => 'object',
							'description' => 'Advanced search filters',
							'properties'  => [
								'email_contains'      => [
									'type' => 'string',
								],
								'name_contains'       => [
									'type' => 'string',
								],
								'has_tags'            => [
									'type'  => 'array',
									'items' => [ 'type' => 'integer' ],
								],
								'has_lists'           => [
									'type'  => 'array',
									'items' => [ 'type' => 'integer' ],
								],
								'status_in'           => [
									'type'  => 'array',
									'items' => [
										'type' => 'string',
										'enum' => [ 'subscribed', 'pending', 'unsubscribed', 'bounced', 'complained' ],
									],
								],
								'country'             => [
									'type' => 'string',
								],
								'created_after'       => [
									'type'   => 'string',
									'format' => 'date',
								],
								'created_before'      => [
									'type'   => 'string',
									'format' => 'date',
								],
								'last_activity_after' => [
									'type'   => 'string',
									'format' => 'date',
								],
							],
						],
						'page'     => [
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						],
						'per_page' => [
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						],
					],
					'required'   => [ 'filters' ],
				],
				'execute_callback'    => [ $this, 'execute_search_subscribers' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'subscribers',
				],
			]
		);
	}

	/**
	 * Execute create subscriber ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_subscriber( array $args ): array {
		try {
			$email = sanitize_email( $args['email'] );

			if ( ! is_email( $email ) ) {
				return $this->get_error_response( 'Invalid email address', 'invalid_email' );
			}

			// Check if subscriber already exists
			$existing = \FluentCrm\App\Models\Subscriber::where( 'email', $email )->first();
			if ( $existing ) {
				return $this->get_error_response( 'Subscriber with this email already exists', 'subscriber_exists' );
			}

			// Prepare subscriber data
			$subscriber_data = [
				'email'      => $email,
				'status'     => $args['status'] ?? 'subscribed',
				'first_name' => isset( $args['first_name'] ) ? sanitize_text_field( $args['first_name'] ) : '',
				'last_name'  => isset( $args['last_name'] ) ? sanitize_text_field( $args['last_name'] ) : '',
			];

			// Add optional fields
			$optional_fields = [
				'phone',
				'address_line_1',
				'address_line_2',
				'city',
				'state',
				'postal_code',
				'country',
				'timezone',
				'date_of_birth',
			];

			foreach ( $optional_fields as $field ) {
				if ( isset( $args[ $field ] ) ) {
					$subscriber_data[ $field ] = sanitize_text_field( $args[ $field ] );
				}
			}

			// Create subscriber using FluentCRM API
			$subscriber = \FluentCrmApi( 'contacts' )->createOrUpdate( $subscriber_data );

			if ( ! $subscriber ) {
				return $this->get_error_response( 'Failed to create subscriber', 'create_failed' );
			}

			// Add custom field values if provided
			if ( ! empty( $args['custom_values'] ) && is_array( $args['custom_values'] ) ) {
				foreach ( $args['custom_values'] as $key => $value ) {
					$subscriber->updateMeta( $key, $value, 'custom_field' );
				}
			}

			// Attach tags if provided
			if ( ! empty( $args['tags'] ) && is_array( $args['tags'] ) ) {
				$subscriber->attachTags( $args['tags'] );
			}

			// Attach lists if provided
			if ( ! empty( $args['lists'] ) && is_array( $args['lists'] ) ) {
				$subscriber->attachLists( $args['lists'] );
			}

			// Refresh to get complete data with computed fields
			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber->id );

			return $this->get_success_response(
				[
					'subscriber' => $subscriber->toArray(),
				],
				'Subscriber created successfully. Note: Relations (tags, lists) not included by default - use get-subscriber with "with" parameter to load relationships.'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute list subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_subscribers( array $args ): array {
		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';
			$status   = $args['status'] ?? null;
			$orderby  = $args['orderby'] ?? 'created_at';
			$order    = $args['order'] ?? 'DESC';

			$query = \FluentCrm\App\Models\Subscriber::query();

			// Apply search if provided
			if ( ! empty( $search ) ) {
				$query->where(
					function ( $q ) use ( $search ) {
						$q->where( 'email', 'like', '%' . $search . '%' )
						->orWhere( 'first_name', 'like', '%' . $search . '%' )
						->orWhere( 'last_name', 'like', '%' . $search . '%' )
						->orWhere( 'phone', 'like', '%' . $search . '%' );
					}
				);
			}

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			// Apply tag filter
			if ( ! empty( $args['tags'] ) && is_array( $args['tags'] ) ) {
				foreach ( $args['tags'] as $tag_id ) {
					$query->whereHas(
						'tags',
						function ( $q ) use ( $tag_id ) {
							$q->where( 'fc_tags.id', $tag_id );
						}
					);
				}
			}

			// Apply list filter
			if ( ! empty( $args['lists'] ) && is_array( $args['lists'] ) ) {
				foreach ( $args['lists'] as $list_id ) {
					$query->whereHas(
						'lists',
						function ( $q ) use ( $list_id ) {
							$q->where( 'fc_lists.id', $list_id );
						}
					);
				}
			}

			// Get total count before pagination
			$total = $query->count();

			// Apply ordering and pagination
			$offset      = ( $page - 1 ) * $per_page;
			$subscribers = $query->orderBy( $orderby, $order )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$result = [];
			foreach ( $subscribers as $subscriber ) {
				$result[] = $subscriber->toArray();
			}

			return $this->get_success_response(
				[
					'subscribers' => $result,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Subscribers retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list subscribers: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute get subscriber ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_subscriber( array $args ): array {
		try {
			$subscriber = null;

			// Find by ID or email
			if ( ! empty( $args['subscriber_id'] ) ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::find( intval( $args['subscriber_id'] ) );
			} elseif ( ! empty( $args['email'] ) ) {
				$email      = sanitize_email( $args['email'] );
				$subscriber = \FluentCrm\App\Models\Subscriber::where( 'email', $email )->first();
			}

			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$with = $args['with'] ?? [];

			// Reload subscriber with requested relationships
			$query = \FluentCrm\App\Models\Subscriber::query();

			// Filter valid relationships
			$valid_relationships = array_intersect( $with, [ 'tags', 'lists' ] );
			if ( ! empty( $valid_relationships ) ) {
				$query->with( $valid_relationships );
			}

			if ( ! empty( $args['subscriber_id'] ) ) {
				$subscriber = $query->find( intval( $args['subscriber_id'] ) );
			} else {
				$email      = sanitize_email( $args['email'] );
				$subscriber = $query->where( 'email', $email )->first();
			}

			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$data = $subscriber->toArray();

			// Include stats if requested (not a model relationship)
			if ( in_array( 'stats', $with, true ) ) {
				$data['stats'] = [
					'total_emails_sent'    => $subscriber->total_emails_sent ?? 0,
					'total_emails_opened'  => $subscriber->total_emails_opened ?? 0,
					'total_emails_clicked' => $subscriber->total_emails_clicked ?? 0,
				];
			}

			// Include custom fields if requested (not a model relationship)
			if ( in_array( 'custom_fields', $with, true ) ) {
				$data['custom_fields'] = $subscriber->custom_fields();
			}

			return $this->get_success_response(
				[ 'subscriber' => $data ],
				'Subscriber retrieved successfully. Relations included based on "with" parameter: ' . implode( ', ', $valid_relationships )
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute update subscriber ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_subscriber( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$subscriber    = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Prepare update data
			$update_data = [];

			$updateable_fields = [
				'email',
				'first_name',
				'last_name',
				'phone',
				'address_line_1',
				'address_line_2',
				'city',
				'state',
				'postal_code',
				'country',
				'timezone',
				'date_of_birth',
			];

			foreach ( $updateable_fields as $field ) {
				if ( isset( $args[ $field ] ) ) {
					if ( 'email' === $field ) {
						$email = sanitize_email( $args[ $field ] );
						if ( ! is_email( $email ) ) {
							return $this->get_error_response( 'Invalid email address', 'invalid_email' );
						}
						$update_data[ $field ] = $email;
					} else {
						$update_data[ $field ] = sanitize_text_field( $args[ $field ] );
					}
				}
			}

			// Update subscriber
			if ( ! empty( $update_data ) ) {
				$subscriber->fill( $update_data );
				$subscriber->save();
			}

			// Update custom field values if provided
			if ( ! empty( $args['custom_values'] ) && is_array( $args['custom_values'] ) ) {
				foreach ( $args['custom_values'] as $key => $value ) {
					$subscriber->updateMeta( $key, $value, 'custom_field' );
				}
			}

			// Refresh to get complete updated data
			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber->id );

			return $this->get_success_response(
				[
					'subscriber' => $subscriber->toArray(),
				],
				'Subscriber updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute delete subscriber ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_subscriber( array $args ): array {
		try {
			$subscriber_id  = intval( $args['subscriber_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$email = $subscriber->email;

			// Delete subscriber (FluentCRM handles cleanup of related data)
			$subscriber->delete();

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'email'         => $email,
					'deleted_at'    => current_time( 'mysql' ),
				],
				'Subscriber deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute bulk import subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_import_subscribers( array $args ): array {
		$subscribers     = $args['subscribers'] ?? [];
		$tags            = $args['tags'] ?? [];
		$lists           = $args['lists'] ?? [];
		$update_existing = $args['update_existing'] ?? false;

		if ( empty( $subscribers ) || ! is_array( $subscribers ) ) {
			return $this->get_error_response( 'Subscribers array is required', 'invalid_data' );
		}

		$imported = 0;
		$updated  = 0;
		$failed   = 0;
		$errors   = [];

		foreach ( $subscribers as $index => $subscriber_data ) {
			// Validate email presence
			if ( empty( $subscriber_data['email'] ) ) {
				$errors[] = "Row {$index}: Email is required";
				++$failed;
				continue;
			}

			$email = sanitize_email( $subscriber_data['email'] );

			// Validate email format
			if ( ! is_email( $email ) ) {
				$errors[] = "Row {$index}: Invalid email: {$email}";
				++$failed;
				continue;
			}

			// Prepare subscriber data
			$data = [
				'email'      => $email,
				'status'     => $subscriber_data['status'] ?? 'subscribed',
				'first_name' => isset( $subscriber_data['first_name'] ) ? sanitize_text_field( $subscriber_data['first_name'] ) : '',
				'last_name'  => isset( $subscriber_data['last_name'] ) ? sanitize_text_field( $subscriber_data['last_name'] ) : '',
			];

			// Check for existing subscriber
			$existing = \FluentCrm\App\Models\Subscriber::where( 'email', $email )->first();

			// If update_existing is false and subscriber exists, skip
			if ( ! $update_existing && $existing ) {
				$errors[] = "Row {$index}: Email already exists: {$email}";
				++$failed;
				continue;
			}

			// Try to create or update subscriber
			try {
				$subscriber = \FluentCrmApi( 'contacts' )->createOrUpdate( $data );
				if ( ! $subscriber ) {
					$errors[] = "Row {$index}: Failed to create subscriber";
					++$failed;
					continue;
				}

				// Attach tags and lists if provided
				if ( ! empty( $tags ) && is_array( $tags ) ) {
					$subscriber->attachTags( $tags );
				}
				if ( ! empty( $lists ) && is_array( $lists ) ) {
					$subscriber->attachLists( $lists );
				}

				// Increment appropriate counter based on whether subscriber existed
				if ( $existing ) {
					++$updated;
				} else {
					++$imported;
				}
			} catch ( \Exception $e ) {
				$errors[] = "Row {$index}: {$e->getMessage()}";
				++$failed;
			}
		}

		return $this->get_success_response(
			[
				'total'    => count( $subscribers ),
				'imported' => $imported,
				'updated'  => $updated,
				'failed'   => $failed,
				'errors'   => $errors,
			],
			"Bulk import completed. Imported: {$imported}, Updated: {$updated}, Failed: {$failed}"
		);
	}

	/**
	 * Execute bulk update subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_update_subscribers( array $args ): array {
		try {
			$subscriber_ids = $args['subscriber_ids'] ?? [];
			$update_data    = $args['update_data'] ?? [];

			if ( empty( $subscriber_ids ) || ! is_array( $subscriber_ids ) ) {
				return $this->get_error_response( 'Subscriber IDs array is required', 'invalid_data' );
			}

			if ( empty( $update_data ) || ! is_array( $update_data ) ) {
				return $this->get_error_response( 'Update data is required', 'invalid_data' );
			}

			$updated = 0;
			$failed  = 0;

			foreach ( $subscriber_ids as $subscriber_id ) {
				try {
					$subscriber = \FluentCrm\App\Models\Subscriber::find( intval( $subscriber_id ) );
					if ( ! $subscriber ) {
						++$failed;
						continue;
					}

					// Update basic fields
					$basic_fields = [ 'status', 'timezone', 'country' ];
					foreach ( $basic_fields as $field ) {
						if ( isset( $update_data[ $field ] ) ) {
							$subscriber->$field = sanitize_text_field( $update_data[ $field ] );
						}
					}
					$subscriber->save();

					// Update custom fields
					if ( ! empty( $update_data['custom_values'] ) && is_array( $update_data['custom_values'] ) ) {
						foreach ( $update_data['custom_values'] as $key => $value ) {
							$subscriber->updateMeta( $key, $value, 'custom_field' );
						}
					}

					++$updated;
				} catch ( \Exception $e ) {
					++$failed;
				}
			}

			return $this->get_success_response(
				[
					'total'   => count( $subscriber_ids ),
					'updated' => $updated,
					'failed'  => $failed,
				],
				"Bulk update completed. Updated: {$updated}, Failed: {$failed}"
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to bulk update subscribers: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute bulk delete subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_delete_subscribers( array $args ): array {
		try {
			$subscriber_ids = $args['subscriber_ids'] ?? [];
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			if ( empty( $subscriber_ids ) || ! is_array( $subscriber_ids ) ) {
				return $this->get_error_response( 'Subscriber IDs array is required', 'invalid_data' );
			}

			$deleted = 0;
			$failed  = 0;

			foreach ( $subscriber_ids as $subscriber_id ) {
				try {
					$subscriber = \FluentCrm\App\Models\Subscriber::find( intval( $subscriber_id ) );
					if ( $subscriber ) {
						$subscriber->delete();
						++$deleted;
					} else {
						++$failed;
					}
				} catch ( \Exception $e ) {
					++$failed;
				}
			}

			return $this->get_success_response(
				[
					'total'   => count( $subscriber_ids ),
					'deleted' => $deleted,
					'failed'  => $failed,
				],
				"Bulk delete completed. Deleted: {$deleted}, Failed: {$failed}"
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to bulk delete subscribers: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute add subscriber to list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_subscriber_to_list( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$list_id       = intval( $args['list_id'] );

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			if ( ! $this->list_exists( $list_id ) ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			$subscriber->attachLists( [ $list_id ] );

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id,
				],
				'Subscriber added to list successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add subscriber to list: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute remove subscriber from list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_subscriber_from_list( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$list_id       = intval( $args['list_id'] );

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$subscriber->detachLists( [ $list_id ] );

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id,
				],
				'Subscriber removed from list successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove subscriber from list: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute add subscriber tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_subscriber_tag( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$tag_id        = intval( $args['tag_id'] );

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			$subscriber->attachTags( [ $tag_id ] );

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'tag_id'        => $tag_id,
				],
				'Tag added to subscriber successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add tag to subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute remove subscriber tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_subscriber_tag( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$tag_id        = intval( $args['tag_id'] );

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$subscriber->detachTags( [ $tag_id ] );

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'tag_id'        => $tag_id,
				],
				'Tag removed from subscriber successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove tag from subscriber: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute update subscriber status ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_subscriber_status( array $args ): array {
		try {
			$subscriber_id = intval( $args['subscriber_id'] );
			$status        = sanitize_text_field( $args['status'] );

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$subscriber->status = $status;
			$subscriber->save();

			return $this->get_success_response(
				[
					'subscriber_id' => $subscriber_id,
					'status'        => $status,
				],
				'Subscriber status updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update subscriber status: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute merge subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_merge_subscribers( array $args ): array {
		try {
			$primary_id = intval( $args['primary_subscriber_id'] );
			$merge_ids  = $args['merge_subscriber_ids'] ?? [];

			if ( empty( $merge_ids ) || ! is_array( $merge_ids ) ) {
				return $this->get_error_response( 'Merge subscriber IDs array is required', 'invalid_data' );
			}

			$primary = \FluentCrm\App\Models\Subscriber::find( $primary_id );
			if ( ! $primary ) {
				return $this->get_error_response( 'Primary subscriber not found', 'subscriber_not_found' );
			}

			$merged_count = 0;

			foreach ( $merge_ids as $merge_id ) {
				$merge_id = intval( $merge_id );
				if ( $merge_id === $primary_id ) {
					continue;
				}

				$merge_subscriber = \FluentCrm\App\Models\Subscriber::find( $merge_id );
				if ( ! $merge_subscriber ) {
					continue;
				}

				// Merge tags
				$merge_tags = $merge_subscriber->tags()->get()->pluck( 'id' )->toArray();
				if ( ! empty( $merge_tags ) ) {
					$primary->attachTags( $merge_tags );
				}

				// Merge lists
				$merge_lists = $merge_subscriber->lists()->get()->pluck( 'id' )->toArray();
				if ( ! empty( $merge_lists ) ) {
					$primary->attachLists( $merge_lists );
				}

				// Delete merged subscriber
				$merge_subscriber->delete();
				++$merged_count;
			}

			return $this->get_success_response(
				[
					'primary_subscriber_id' => $primary_id,
					'merged_count'          => $merged_count,
				],
				"Successfully merged {$merged_count} subscribers into primary contact"
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to merge subscribers: ' . $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Execute search subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_search_subscribers( array $args ): array {
		try {
			$filters  = $args['filters'] ?? [];
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;

			$query = \FluentCrm\App\Models\Subscriber::query();

			// Apply filters
			if ( ! empty( $filters['email_contains'] ) ) {
				$query->where( 'email', 'like', '%' . $filters['email_contains'] . '%' );
			}

			if ( ! empty( $filters['name_contains'] ) ) {
				$query->where(
					function ( $q ) use ( $filters ) {
						$q->where( 'first_name', 'like', '%' . $filters['name_contains'] . '%' )
						->orWhere( 'last_name', 'like', '%' . $filters['name_contains'] . '%' );
					}
				);
			}

			if ( ! empty( $filters['has_tags'] ) && is_array( $filters['has_tags'] ) ) {
				foreach ( $filters['has_tags'] as $tag_id ) {
					$query->whereHas(
						'tags',
						function ( $q ) use ( $tag_id ) {
							$q->where( 'fc_tags.id', $tag_id );
						}
					);
				}
			}

			if ( ! empty( $filters['has_lists'] ) && is_array( $filters['has_lists'] ) ) {
				foreach ( $filters['has_lists'] as $list_id ) {
					$query->whereHas(
						'lists',
						function ( $q ) use ( $list_id ) {
							$q->where( 'fc_lists.id', $list_id );
						}
					);
				}
			}

			if ( ! empty( $filters['status_in'] ) && is_array( $filters['status_in'] ) ) {
				$query->whereIn( 'status', $filters['status_in'] );
			}

			if ( ! empty( $filters['country'] ) ) {
				$query->where( 'country', $filters['country'] );
			}

			if ( ! empty( $filters['created_after'] ) ) {
				$query->where( 'created_at', '>=', $filters['created_after'] );
			}

			if ( ! empty( $filters['created_before'] ) ) {
				$query->where( 'created_at', '<=', $filters['created_before'] );
			}

			if ( ! empty( $filters['last_activity_after'] ) ) {
				$query->where( 'last_activity', '>=', $filters['last_activity_after'] );
			}

			// Get total count
			$total = $query->count();

			// Apply pagination
			$offset      = ( $page - 1 ) * $per_page;
			$subscribers = $query->orderBy( 'created_at', 'DESC' )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$result = [];
			foreach ( $subscribers as $subscriber ) {
				$result[] = $subscriber->toArray();
			}

			return $this->get_success_response(
				[
					'subscribers' => $result,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Search completed successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to search subscribers: ' . $e->getMessage(), 'exception' );
		}
	}
}
