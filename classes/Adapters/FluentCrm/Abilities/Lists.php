<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM List Abilities
 *
 * Registers WordPress abilities for FluentCRM list management operations
 * using the WordPress Abilities API pattern.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 */
class Lists extends BaseAbility {

	/**
	 * Register all list-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_create_list();
		$this->register_list_lists();
		$this->register_get_list();
		$this->register_update_list();
		$this->register_delete_list();
		$this->register_get_list_subscribers();
		$this->register_get_list_stats();
		$this->register_duplicate_list();
		$this->register_merge_lists();
	}

	/**
	 * Register create list ability
	 */
	private function register_create_list(): void {
		wp_register_ability(
			'fluentcrm/create-list',
			[
				'label'               => 'Create FluentCRM list',
				'description'         => 'Create a new contact list with title, description, and slug',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'title'       => [
							'type'        => 'string',
							'description' => 'List title (required)',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'List description',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'List slug (auto-generated from title if not provided)',
							'pattern'     => '^[a-z0-9-]+$',
						],
					],
					'required'   => [ 'title' ],
				],
				'execute_callback'    => [ $this, 'execute_create_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register list lists ability
	 */
	private function register_list_lists(): void {
		wp_register_ability(
			'fluentcrm/list-lists',
			[
				'label'               => 'List FluentCRM lists',
				'description'         => 'List all contact lists with pagination and search',
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
							'description' => 'Number of lists per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search lists by title',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_lists' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register get list ability
	 */
	private function register_get_list(): void {
		wp_register_ability(
			'fluentcrm/get-list',
			[
				'label'               => 'Get FluentCRM list',
				'description'         => 'Get detailed information about a specific list with subscriber count by status',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id' => [
							'type'        => 'integer',
							'description' => 'List ID to retrieve',
						],
					],
					'required'   => [ 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_list' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register update list ability
	 */
	private function register_update_list(): void {
		wp_register_ability(
			'fluentcrm/update-list',
			[
				'label'               => 'Update FluentCRM list',
				'description'         => 'Update list properties (title, description, slug)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id'     => [
							'type'        => 'integer',
							'description' => 'List ID to update (required)',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'New list title',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'New list description',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'New list slug',
							'pattern'     => '^[a-z0-9-]+$',
						],
					],
					'required'   => [ 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register delete list ability
	 */
	private function register_delete_list(): void {
		wp_register_ability(
			'fluentcrm/delete-list',
			[
				'label'               => 'Delete FluentCRM list',
				'description'         => 'Delete a list with option to keep or delete subscribers',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id'            => [
							'type'        => 'integer',
							'description' => 'List ID to delete',
						],
						'confirm_delete'     => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
						'delete_subscribers' => [
							'type'        => 'boolean',
							'description' => 'If true, delete all subscribers in this list; if false, keep subscribers',
							'default'     => false,
						],
					],
					'required'   => [ 'list_id', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register get list subscribers ability
	 */
	private function register_get_list_subscribers(): void {
		wp_register_ability(
			'fluentcrm/get-list-subscribers',
			[
				'label'               => 'Get FluentCRM list subscribers',
				'description'         => 'Get all subscribers in a list with pagination and status filtering',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id'  => [
							'type'        => 'integer',
							'description' => 'List ID to get subscribers from',
						],
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
						'status'   => [
							'type'        => 'string',
							'description' => 'Filter by subscriber status',
							'enum'        => [ 'subscribed', 'unsubscribed', 'pending', 'bounced', 'complained' ],
						],
					],
					'required'   => [ 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_list_subscribers' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register get list stats ability
	 */
	private function register_get_list_stats(): void {
		wp_register_ability(
			'fluentcrm/get-list-stats',
			[
				'label'               => 'Get FluentCRM list statistics',
				'description'         => 'Get list statistics including total, subscribed, unsubscribed, bounced counts',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id' => [
							'type'        => 'integer',
							'description' => 'List ID to get statistics for',
						],
					],
					'required'   => [ 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_list_stats' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register duplicate list ability
	 */
	private function register_duplicate_list(): void {
		wp_register_ability(
			'fluentcrm/duplicate-list',
			[
				'label'               => 'Duplicate FluentCRM list',
				'description'         => 'Clone a list with optional subscriber copy',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'list_id'          => [
							'type'        => 'integer',
							'description' => 'List ID to duplicate',
						],
						'new_title'        => [
							'type'        => 'string',
							'description' => 'Title for the duplicated list (optional - will use "Copy of {original title}" if not provided)',
						],
						'copy_subscribers' => [
							'type'        => 'boolean',
							'description' => 'If true, copy all subscribers to the new list',
							'default'     => false,
						],
					],
					'required'   => [ 'list_id' ],
				],
				'execute_callback'    => [ $this, 'execute_duplicate_list' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Register merge lists ability
	 */
	private function register_merge_lists(): void {
		wp_register_ability(
			'fluentcrm/merge-lists',
			[
				'label'               => 'Merge FluentCRM lists',
				'description'         => 'Merge multiple lists into one, combining all subscribers',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'source_list_ids'   => [
							'type'        => 'array',
							'description' => 'Array of list IDs to merge (at least 2 required)',
							'items'       => [
								'type' => 'integer',
							],
							'minItems'    => 2,
						],
						'target_list_title' => [
							'type'        => 'string',
							'description' => 'Title for the merged list',
						],
						'delete_source'     => [
							'type'        => 'boolean',
							'description' => 'If true, delete source lists after merging',
							'default'     => false,
						],
					],
					'required'   => [ 'source_list_ids', 'target_list_title' ],
				],
				'execute_callback'    => [ $this, 'execute_merge_lists' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'lists',
				],
			]
		);
	}

	/**
	 * Execute create list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_list( array $args ): array {
		try {
			$title       = sanitize_text_field( $args['title'] );
			$description = sanitize_textarea_field( $args['description'] ?? '' );
			$slug        = isset( $args['slug'] ) ? sanitize_title( $args['slug'] ) : sanitize_title( $title );

			if ( empty( $title ) ) {
				return $this->get_error_response( 'List title is required', 'title_required' );
			}

			// Check if slug already exists
			$existing = \FluentCrm\App\Models\Lists::where( 'slug', $slug )->first();
			if ( $existing ) {
				// Generate unique slug
				$slug = $slug . '-' . time();
			}

			$list = \FluentCrm\App\Models\Lists::create(
				[
					'title'       => $title,
					'slug'        => $slug,
					'description' => $description,
				]
			);

			return $this->get_success_response(
				[
					'list' => [
						'id'          => $list->id,
						'title'       => $list->title,
						'slug'        => $list->slug,
						'description' => $list->description,
						'created_at'  => $list->created_at,
					],
				],
				'List created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create list: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list lists ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_lists( array $args ): array {
		try {
			$search   = $args['search'] ?? '';
			$per_page = $args['per_page'] ?? 20;
			$page     = $args['page'] ?? 1;

			// Build query
			$query = \FluentCrm\App\Models\Lists::orderBy( 'created_at', 'DESC' );

			// Add search if provided
			if ( ! empty( $search ) ) {
				$query = $query->where( 'title', 'like', '%' . $search . '%' );
			}

			// Get total count
			$total = $query->count();

			// Get lists with pagination
			$offset = ( $page - 1 ) * $per_page;
			$lists  = $query->offset( $offset )
							->limit( $per_page )
							->get();

			$result = [];
			foreach ( $lists as $list ) {
				$result[] = [
					'id'          => $list->id,
					'title'       => $list->title,
					'slug'        => $list->slug,
					'description' => $list->description,
					'created_at'  => $list->created_at,
					'updated_at'  => $list->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'lists'        => $result,
					'total'        => $total,
					'page'         => $page,
					'per_page'     => $per_page,
					'total_pages'  => ceil( $total / $per_page ),
					'search_query' => $search,
				],
				'Lists retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list lists: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_list( array $args ): array {
		try {
			$list_id = intval( $args['list_id'] );

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			$list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $list ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			// Get subscriber counts by status
			$subscriber_counts = \FluentCrm\App\Models\Subscriber::whereHas(
				'lists',
				function ( $query ) use ( $list_id ) {
					$query->where( 'fc_lists.id', $list_id );
				}
			)->selectRaw( 'status, COUNT(*) as count' )
			->groupBy( 'status' )
			->get()
			->pluck( 'count', 'status' )
			->toArray();

			$total_subscribers = array_sum( $subscriber_counts );

			return $this->get_success_response(
				[
					'list'              => [
						'id'          => $list->id,
						'title'       => $list->title,
						'slug'        => $list->slug,
						'description' => $list->description,
						'created_at'  => $list->created_at,
						'updated_at'  => $list->updated_at,
					],
					'subscriber_counts' => [
						'total'        => $total_subscribers,
						'subscribed'   => $subscriber_counts['subscribed'] ?? 0,
						'unsubscribed' => $subscriber_counts['unsubscribed'] ?? 0,
						'pending'      => $subscriber_counts['pending'] ?? 0,
						'bounced'      => $subscriber_counts['bounced'] ?? 0,
						'complained'   => $subscriber_counts['complained'] ?? 0,
					],
				],
				'List retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get list: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_list( array $args ): array {
		try {
			$list_id = intval( $args['list_id'] );

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			$list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $list ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			if ( isset( $args['slug'] ) ) {
				$new_slug = sanitize_title( $args['slug'] );

				// Check if slug already exists (excluding current list)
				$existing = \FluentCrm\App\Models\Lists::where( 'slug', $new_slug )
														->where( 'id', '!=', $list_id )
														->first();
				if ( $existing ) {
					return $this->get_error_response( 'Slug already exists', 'slug_exists' );
				}

				$update_data['slug'] = $new_slug;
			}

			// Update the list
			if ( ! empty( $update_data ) ) {
				$list->update( $update_data );
				$list = \FluentCrm\App\Models\Lists::find( $list_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'list' => [
						'id'          => $list->id,
						'title'       => $list->title,
						'slug'        => $list->slug,
						'description' => $list->description,
						'updated_at'  => $list->updated_at,
					],
				],
				'List updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update list: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_list( array $args ): array {
		try {
			$list_id            = intval( $args['list_id'] );
			$confirm_delete     = $args['confirm_delete'] ?? false;
			$delete_subscribers = $args['delete_subscribers'] ?? false;

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $list ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			$list_title = $list->title;

			// Get subscriber count before deletion
			// Use the relationship query to get subscriber IDs
			$subscriber_ids   = $list->subscribers->pluck( 'id' )->toArray();
			$subscriber_count = count( $subscriber_ids );

			// If delete_subscribers is true, delete all subscribers in this list
			if ( $delete_subscribers && $subscriber_count > 0 ) {
				\FluentCrm\App\Models\Subscriber::whereIn( 'id', $subscriber_ids )->delete();
			}

			// Delete the list (this will also remove list-subscriber relationships)
			$list->delete();

			return $this->get_success_response(
				[
					'list_id'             => $list_id,
					'list_title'          => $list_title,
					'subscribers_deleted' => $delete_subscribers ? $subscriber_count : 0,
					'subscribers_kept'    => $delete_subscribers ? 0 : $subscriber_count,
					'deleted_at'          => current_time( 'mysql' ),
				],
				'List deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete list: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute get list subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_list_subscribers( array $args ): array {
		try {
			$list_id  = intval( $args['list_id'] );
			$per_page = $args['per_page'] ?? 20;
			$page     = $args['page'] ?? 1;
			$status   = $args['status'] ?? null;

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			$list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $list ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			// Build query
			$query = \FluentCrm\App\Models\Subscriber::whereHas(
				'lists',
				function ( $query ) use ( $list_id ) {
					$query->where( 'fc_lists.id', $list_id );
				}
			);

			// Filter by status if provided
			if ( $status ) {
				$query = $query->where( 'status', $status );
			}

			// Get total count
			$total = $query->count();

			// Get subscribers with pagination
			$offset      = ( $page - 1 ) * $per_page;
			$subscribers = $query->orderBy( 'created_at', 'DESC' )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$result = [];
			foreach ( $subscribers as $subscriber ) {
				$result[] = [
					'id'         => $subscriber->id,
					'email'      => $subscriber->email,
					'first_name' => $subscriber->first_name,
					'last_name'  => $subscriber->last_name,
					'status'     => $subscriber->status,
					'created_at' => $subscriber->created_at,
				];
			}

			return $this->get_success_response(
				[
					'list_id'       => $list_id,
					'list_title'    => $list->title,
					'subscribers'   => $result,
					'total'         => $total,
					'page'          => $page,
					'per_page'      => $per_page,
					'total_pages'   => ceil( $total / $per_page ),
					'status_filter' => $status,
				],
				'List subscribers retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get list subscribers: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute get list stats ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_list_stats( array $args ): array {
		try {
			$list_id = intval( $args['list_id'] );

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			$list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $list ) {
				return $this->get_error_response( 'List not found', 'list_not_found' );
			}

			// Get subscriber counts by status
			$subscriber_counts = \FluentCrm\App\Models\Subscriber::whereHas(
				'lists',
				function ( $query ) use ( $list_id ) {
					$query->where( 'fc_lists.id', $list_id );
				}
			)->selectRaw( 'status, COUNT(*) as count' )
			->groupBy( 'status' )
			->get()
			->pluck( 'count', 'status' )
			->toArray();

			$total_subscribers = array_sum( $subscriber_counts );

			return $this->get_success_response(
				[
					'list_id'      => $list_id,
					'list_title'   => $list->title,
					'statistics'   => [
						'total_subscribers' => $total_subscribers,
						'subscribed'        => $subscriber_counts['subscribed'] ?? 0,
						'unsubscribed'      => $subscriber_counts['unsubscribed'] ?? 0,
						'pending'           => $subscriber_counts['pending'] ?? 0,
						'bounced'           => $subscriber_counts['bounced'] ?? 0,
						'complained'        => $subscriber_counts['complained'] ?? 0,
						'subscription_rate' => $total_subscribers > 0
							? round( ( $subscriber_counts['subscribed'] ?? 0 ) / $total_subscribers * 100, 2 )
							: 0,
					],
					'generated_at' => current_time( 'mysql' ),
				],
				'List statistics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get list statistics: ' . $e->getMessage(), 'stats_failed' );
		}
	}

	/**
	 * Execute duplicate list ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_duplicate_list( array $args ): array {
		try {
			$list_id          = intval( $args['list_id'] );
			$copy_subscribers = $args['copy_subscribers'] ?? false;

			if ( $list_id <= 0 ) {
				return $this->get_error_response( 'Invalid list ID', 'invalid_list_id' );
			}

			$original_list = \FluentCrm\App\Models\Lists::find( $list_id );

			if ( ! $original_list ) {
				return $this->get_error_response( 'Original list not found', 'list_not_found' );
			}

			// Prepare new list data
			$new_title = $args['new_title'] ?? 'Copy of ' . $original_list->title;
			$new_slug  = sanitize_title( $new_title );

			// Ensure unique slug
			$existing = \FluentCrm\App\Models\Lists::where( 'slug', $new_slug )->first();
			if ( $existing ) {
				$new_slug = $new_slug . '-' . time();
			}

			// Create the new list
			$new_list = \FluentCrm\App\Models\Lists::create(
				[
					'title'       => $new_title,
					'slug'        => $new_slug,
					'description' => $original_list->description,
				]
			);

			$subscribers_copied = 0;

			// Copy subscribers if requested
			if ( $copy_subscribers ) {
				// Access relationship as property (loads it) instead of calling as method
				$subscriber_ids = $original_list->subscribers->pluck( 'id' )->toArray();
				if ( ! empty( $subscriber_ids ) ) {
					// Attach with required pivot data
					$pivot_data = array_fill_keys(
						$subscriber_ids,
						[ 'object_type' => 'FluentCrm\App\Models\Lists' ]
					);
					$new_list->subscribers()->attach( $pivot_data );
					$subscribers_copied = count( $subscriber_ids );
				}
			}

			return $this->get_success_response(
				[
					'original_list'      => [
						'id'    => $original_list->id,
						'title' => $original_list->title,
					],
					'new_list'           => [
						'id'          => $new_list->id,
						'title'       => $new_list->title,
						'slug'        => $new_list->slug,
						'description' => $new_list->description,
						'created_at'  => $new_list->created_at,
					],
					'subscribers_copied' => $subscribers_copied,
				],
				'List duplicated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to duplicate list: ' . $e->getMessage(), 'duplicate_failed' );
		}
	}

	/**
	 * Execute merge lists ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_merge_lists( array $args ): array {
		try {
			$source_list_ids   = $args['source_list_ids'] ?? [];
			$target_list_title = sanitize_text_field( $args['target_list_title'] );
			$delete_source     = $args['delete_source'] ?? false;

			if ( count( $source_list_ids ) < 2 ) {
				return $this->get_error_response( 'At least 2 source lists required for merging', 'insufficient_lists' );
			}

			if ( empty( $target_list_title ) ) {
				return $this->get_error_response( 'Target list title is required', 'title_required' );
			}

			// Validate all source lists exist
			$source_lists = \FluentCrm\App\Models\Lists::whereIn( 'id', $source_list_ids )->get();

			if ( $source_lists->count() !== count( $source_list_ids ) ) {
				return $this->get_error_response( 'One or more source lists not found', 'list_not_found' );
			}

			// Create target list
			$target_slug = sanitize_title( $target_list_title );

			// Ensure unique slug
			$existing = \FluentCrm\App\Models\Lists::where( 'slug', $target_slug )->first();
			if ( $existing ) {
				$target_slug = $target_slug . '-' . time();
			}

			$target_list = \FluentCrm\App\Models\Lists::create(
				[
					'title'       => $target_list_title,
					'slug'        => $target_slug,
					'description' => 'Merged list from: ' . implode( ', ', $source_lists->pluck( 'title' )->toArray() ),
				]
			);

			// Collect all unique subscriber IDs from source lists
			$all_subscriber_ids = [];
			foreach ( $source_lists as $source_list ) {
				// Access relationship as property (loads it) instead of calling as method
				$subscriber_ids     = $source_list->subscribers->pluck( 'id' )->toArray();
				$all_subscriber_ids = array_merge( $all_subscriber_ids, $subscriber_ids );
			}

			// Remove duplicates
			$all_subscriber_ids = array_unique( $all_subscriber_ids );

			// Attach all subscribers to target list with required pivot data
			if ( ! empty( $all_subscriber_ids ) ) {
				$pivot_data = array_fill_keys(
					$all_subscriber_ids,
					[ 'object_type' => 'FluentCrm\App\Models\Lists' ]
				);
				$target_list->subscribers()->attach( $pivot_data );
			}

			$merged_count = count( $all_subscriber_ids );

			// Delete source lists if requested
			$deleted_lists = [];
			if ( $delete_source ) {
				foreach ( $source_lists as $source_list ) {
					$deleted_lists[] = [
						'id'    => $source_list->id,
						'title' => $source_list->title,
					];
					$source_list->delete();
				}
			}

			return $this->get_success_response(
				[
					'target_list'          => [
						'id'          => $target_list->id,
						'title'       => $target_list->title,
						'slug'        => $target_list->slug,
						'description' => $target_list->description,
						'created_at'  => $target_list->created_at,
					],
					'source_lists'         => $source_lists->map(
						function ( $list_obj ) {
							return [
								'id'    => $list_obj->id,
								'title' => $list_obj->title,
							];
						}
					)->toArray(),
					'total_subscribers'    => $merged_count,
					'source_lists_deleted' => $delete_source,
					'deleted_lists'        => $deleted_lists,
				],
				'Lists merged successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to merge lists: ' . $e->getMessage(), 'merge_failed' );
		}
	}
}
