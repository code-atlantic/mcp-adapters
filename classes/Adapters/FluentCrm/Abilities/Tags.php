<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Tag Abilities
 *
 * Registers WordPress abilities for FluentCRM tag management operations
 * using the WordPress Abilities API pattern.
 */
class Tags extends BaseAbility {

	/**
	 * Register all tag-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_create_tag();
		$this->register_list_tags();
		$this->register_get_tag();
		$this->register_update_tag();
		$this->register_delete_tag();
		$this->register_get_tag_subscribers();
		$this->register_get_tag_stats();
		$this->register_bulk_apply_tags();
		$this->register_bulk_remove_tags();
	}

	/**
	 * Register create tag ability
	 */
	private function register_create_tag(): void {
		wp_register_ability(
			'fluentcrm/create-tag',
			[
				'label'               => 'Create FluentCRM tag',
				'description'         => 'Create a new tag for subscriber organization',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'title'       => [
							'type'        => 'string',
							'description' => 'Tag title (required)',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Tag description',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'Tag slug (optional - auto-generated from title if not provided)',
						],
					],
					'required'   => [ 'title' ],
				],
				'execute_callback'    => [ $this, 'execute_create_tag' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register list tags ability
	 */
	private function register_list_tags(): void {
		wp_register_ability(
			'fluentcrm/list-tags',
			[
				'label'               => 'List FluentCRM tags',
				'description'         => 'List all tags with pagination and search',
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
							'description' => 'Number of tags per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search tags by title or slug',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_tags' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register get tag ability
	 */
	private function register_get_tag(): void {
		wp_register_ability(
			'fluentcrm/get-tag',
			[
				'label'               => 'Get FluentCRM tag',
				'description'         => 'Get detailed information about a specific tag including subscriber count',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id' => [
							'type'        => 'integer',
							'description' => 'Tag ID to retrieve',
						],
					],
					'required'   => [ 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_tag' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register update tag ability
	 */
	private function register_update_tag(): void {
		wp_register_ability(
			'fluentcrm/update-tag',
			[
				'label'               => 'Update FluentCRM tag',
				'description'         => 'Update tag properties',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id'      => [
							'type'        => 'integer',
							'description' => 'Tag ID to update (required)',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'New tag title',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'New tag description',
						],
						'slug'        => [
							'type'        => 'string',
							'description' => 'New tag slug',
						],
					],
					'required'   => [ 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_tag' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register delete tag ability
	 */
	private function register_delete_tag(): void {
		wp_register_ability(
			'fluentcrm/delete-tag',
			[
				'label'               => 'Delete FluentCRM tag',
				'description'         => 'Delete a tag permanently (requires confirmation)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id'         => [
							'type'        => 'integer',
							'description' => 'Tag ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
					'required'   => [ 'tag_id', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_tag' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register get tag subscribers ability
	 */
	private function register_get_tag_subscribers(): void {
		wp_register_ability(
			'fluentcrm/get-tag-subscribers',
			[
				'label'               => 'Get subscribers with tag',
				'description'         => 'Get all subscribers associated with a specific tag',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id'   => [
							'type'        => 'integer',
							'description' => 'Tag ID',
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
					'required'   => [ 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_tag_subscribers' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register get tag stats ability
	 */
	private function register_get_tag_stats(): void {
		wp_register_ability(
			'fluentcrm/get-tag-stats',
			[
				'label'               => 'Get tag statistics',
				'description'         => 'Get statistics for a tag including total subscribers and breakdown by status',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'tag_id' => [
							'type'        => 'integer',
							'description' => 'Tag ID',
						],
					],
					'required'   => [ 'tag_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_tag_stats' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register bulk apply tags ability
	 */
	private function register_bulk_apply_tags(): void {
		wp_register_ability(
			'fluentcrm/bulk-apply-tags',
			[
				'label'               => 'Bulk apply tags to subscribers',
				'description'         => 'Apply one or more tags to multiple subscribers',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs',
							'items'       => [
								'type' => 'integer',
							],
						],
						'tag_ids'        => [
							'type'        => 'array',
							'description' => 'Array of tag IDs to apply',
							'items'       => [
								'type' => 'integer',
							],
						],
					],
					'required'   => [ 'subscriber_ids', 'tag_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_bulk_apply_tags' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Register bulk remove tags ability
	 */
	private function register_bulk_remove_tags(): void {
		wp_register_ability(
			'fluentcrm/bulk-remove-tags',
			[
				'label'               => 'Bulk remove tags from subscribers',
				'description'         => 'Remove one or more tags from multiple subscribers',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs',
							'items'       => [
								'type' => 'integer',
							],
						],
						'tag_ids'        => [
							'type'        => 'array',
							'description' => 'Array of tag IDs to remove',
							'items'       => [
								'type' => 'integer',
							],
						],
					],
					'required'   => [ 'subscriber_ids', 'tag_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_bulk_remove_tags' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'tags',
				],
			]
		);
	}

	/**
	 * Execute create tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_tag( array $args ): array {
		try {
			$title       = sanitize_text_field( $args['title'] );
			$description = isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '';
			$slug        = isset( $args['slug'] ) ? sanitize_title( $args['slug'] ) : sanitize_title( $title );

			if ( empty( $title ) ) {
				return $this->get_error_response( 'Tag title is required', 'title_required' );
			}

			// Check if tag with same slug exists
			$existing_tag = \FluentCrm\App\Models\Tag::where( 'slug', $slug )->first();
			if ( $existing_tag ) {
				return $this->get_error_response( 'Tag with this slug already exists', 'duplicate_slug' );
			}

			$tag = \FluentCrm\App\Models\Tag::create(
				[
					'title'       => $title,
					'slug'        => $slug,
					'description' => $description,
				]
			);

			return $this->get_success_response(
				[
					'tag' => [
						'id'          => $tag->id,
						'title'       => $tag->title,
						'slug'        => $tag->slug,
						'description' => $tag->description,
						'created_at'  => $tag->created_at,
					],
				],
				'Tag created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create tag: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list tags ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_tags( array $args ): array {
		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';

			$query = \FluentCrm\App\Models\Tag::query();

			// Add search if provided
			if ( ! empty( $search ) ) {
				$query->where(
					function ( $q ) use ( $search ) {
						$q->where( 'title', 'like', '%' . $search . '%' )
						->orWhere( 'slug', 'like', '%' . $search . '%' );
					}
				);
			}

			// Get total count before pagination
			$total = $query->count();

			// Get tags with pagination
			$offset = ( $page - 1 ) * $per_page;
			$tags   = $query->orderBy( 'title', 'ASC' )
							->offset( $offset )
							->limit( $per_page )
							->withCount( 'subscribers' )
							->get();

			$result = [];
			foreach ( $tags as $tag ) {
				$result[] = [
					'id'                => $tag->id,
					'title'             => $tag->title,
					'slug'              => $tag->slug,
					'description'       => $tag->description,
					'created_at'        => $tag->created_at,
					'subscribers_count' => $tag->subscribers_count ?? 0,
				];
			}

			return $this->get_success_response(
				[
					'tags'        => $result,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Tags retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list tags: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_tag( array $args ): array {
		try {
			$tag_id = intval( $args['tag_id'] );

			if ( $tag_id <= 0 ) {
				return $this->get_error_response( 'Invalid tag ID', 'invalid_tag_id' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			$tag = \FluentCrm\App\Models\Tag::withCount( 'subscribers' )->find( $tag_id );

			return $this->get_success_response(
				[
					'tag' => [
						'id'                => $tag->id,
						'title'             => $tag->title,
						'slug'              => $tag->slug,
						'description'       => $tag->description,
						'created_at'        => $tag->created_at,
						'updated_at'        => $tag->updated_at,
						'subscribers_count' => $tag->subscribers_count ?? 0,
					],
				],
				'Tag retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get tag: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_tag( array $args ): array {
		try {
			$tag_id = intval( $args['tag_id'] );

			if ( $tag_id <= 0 ) {
				return $this->get_error_response( 'Invalid tag ID', 'invalid_tag_id' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			$tag = \FluentCrm\App\Models\Tag::find( $tag_id );

			// Check if any update fields were provided at all
			$has_update_fields = isset( $args['title'] ) || isset( $args['description'] ) || isset( $args['slug'] );
			if ( ! $has_update_fields ) {
				return $this->get_error_response( 'No update data provided. At least one field (title, description, or slug) must be specified.', 'no_update_data' );
			}

			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			if ( isset( $args['slug'] ) ) {
				$new_slug = sanitize_title( $args['slug'] );
				// Check if new slug is not already taken by another tag
				if ( $new_slug !== $tag->slug ) {
					$existing = \FluentCrm\App\Models\Tag::where( 'slug', $new_slug )->where( 'id', '!=', $tag_id )->first();
					if ( $existing ) {
						return $this->get_error_response( 'Tag with this slug already exists', 'duplicate_slug' );
					}
				}
				// Always include slug in update_data, even if it's the same (FluentCRM allows this)
				$update_data['slug'] = $new_slug;
			}

			// Note: $update_data might be empty if fields were provided but resulted in no changes
			// This is OK - we still want to return success with current data
			if ( empty( $update_data ) ) {
				$update_data = [];
			}

			$tag->update( $update_data );
			$tag = \FluentCrm\App\Models\Tag::withCount( 'subscribers' )->find( $tag_id );

			return $this->get_success_response(
				[
					'tag' => [
						'id'                => $tag->id,
						'title'             => $tag->title,
						'slug'              => $tag->slug,
						'description'       => $tag->description,
						'updated_at'        => $tag->updated_at,
						'subscribers_count' => $tag->subscribers_count ?? 0,
					],
				],
				'Tag updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update tag: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete tag ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_tag( array $args ): array {
		try {
			$tag_id         = intval( $args['tag_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $tag_id <= 0 ) {
				return $this->get_error_response( 'Invalid tag ID', 'invalid_tag_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			$tag       = \FluentCrm\App\Models\Tag::withCount( 'subscribers' )->find( $tag_id );
			$tag_title = $tag->title;
			$sub_count = $tag->subscribers_count ?? 0;

			// Delete the tag (this will also remove tag-subscriber associations via FluentCRM's model events)
			$tag->delete();

			return $this->get_success_response(
				[
					'tag_id'                     => $tag_id,
					'tag_title'                  => $tag_title,
					'affected_subscribers_count' => $sub_count,
					'deleted_at'                 => current_time( 'mysql' ),
				],
				'Tag deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete tag: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute get tag subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_tag_subscribers( array $args ): array {
		try {
			$tag_id   = intval( $args['tag_id'] );
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$status   = $args['status'] ?? null;

			if ( $tag_id <= 0 ) {
				return $this->get_error_response( 'Invalid tag ID', 'invalid_tag_id' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			// Validate status if provided
			if ( $status !== null ) {
				$valid_statuses = [ 'subscribed', 'unsubscribed', 'pending', 'bounced', 'complained' ];
				if ( ! in_array( $status, $valid_statuses, true ) ) {
					return $this->get_error_response( 'Invalid status value. Must be one of: subscribed, unsubscribed, pending, bounced, complained', 'invalid_status' );
				}
			}

			$tag = \FluentCrm\App\Models\Tag::find( $tag_id );

			// Get subscribers with this tag
			$query = $tag->subscribers();

			if ( $status ) {
				$query->where( 'fc_subscribers.status', $status );
			}

			$total = $query->count();

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
					'tag'         => [
						'id'    => $tag->id,
						'title' => $tag->title,
					],
					'subscribers' => $result,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Tag subscribers retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get tag subscribers: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute get tag stats ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_tag_stats( array $args ): array {
		try {
			$tag_id = intval( $args['tag_id'] );

			if ( $tag_id <= 0 ) {
				return $this->get_error_response( 'Invalid tag ID', 'invalid_tag_id' );
			}

			if ( ! $this->tag_exists( $tag_id ) ) {
				return $this->get_error_response( 'Tag not found', 'tag_not_found' );
			}

			$tag = \FluentCrm\App\Models\Tag::find( $tag_id );

			// Get total subscribers
			$total = $tag->subscribers()->count();

			// Get breakdown by status (need to qualify table name for pivot queries)
			$stats = [
				'subscribed'   => $tag->subscribers()->where( 'fc_subscribers.status', 'subscribed' )->count(),
				'unsubscribed' => $tag->subscribers()->where( 'fc_subscribers.status', 'unsubscribed' )->count(),
				'pending'      => $tag->subscribers()->where( 'fc_subscribers.status', 'pending' )->count(),
				'bounced'      => $tag->subscribers()->where( 'fc_subscribers.status', 'bounced' )->count(),
				'complained'   => $tag->subscribers()->where( 'fc_subscribers.status', 'complained' )->count(),
			];

			return $this->get_success_response(
				[
					'tag'               => [
						'id'    => $tag->id,
						'title' => $tag->title,
						'slug'  => $tag->slug,
					],
					'total_subscribers' => $total,
					'by_status'         => $stats,
				],
				'Tag statistics retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get tag stats: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute bulk apply tags ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_apply_tags( array $args ): array {
		try {
			$subscriber_ids = $args['subscriber_ids'] ?? [];
			$tag_ids        = $args['tag_ids'] ?? [];

			if ( empty( $subscriber_ids ) ) {
				return $this->get_error_response( 'Subscriber IDs are required', 'subscriber_ids_required' );
			}

			if ( empty( $tag_ids ) ) {
				return $this->get_error_response( 'Tag IDs are required', 'tag_ids_required' );
			}

			// Validate all tag IDs exist
			foreach ( $tag_ids as $tag_id ) {
				if ( ! $this->tag_exists( $tag_id ) ) {
					return $this->get_error_response( "Tag ID {$tag_id} not found", 'tag_not_found' );
				}
			}

			// Validate all subscriber IDs exist
			foreach ( $subscriber_ids as $subscriber_id ) {
				if ( ! $this->subscriber_exists( $subscriber_id ) ) {
					return $this->get_error_response( "Subscriber ID {$subscriber_id} not found", 'subscriber_not_found' );
				}
			}

			$applied_count = 0;
			$errors        = [];

			foreach ( $subscriber_ids as $subscriber_id ) {
				try {
					$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
					$subscriber->attachTags( $tag_ids );
					++$applied_count;
				} catch ( \Exception $e ) {
					$errors[] = "Subscriber {$subscriber_id}: " . $e->getMessage();
				}
			}

			$response_data = [
				'applied_to_subscribers' => $applied_count,
				'total_subscribers'      => count( $subscriber_ids ),
				'tag_ids'                => $tag_ids,
				'subscriber_ids'         => $subscriber_ids,
			];

			if ( ! empty( $errors ) ) {
				$response_data['errors'] = $errors;
			}

			return $this->get_success_response(
				$response_data,
				"Tags applied to {$applied_count} subscribers successfully"
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to apply tags: ' . $e->getMessage(), 'apply_failed' );
		}
	}

	/**
	 * Execute bulk remove tags ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_remove_tags( array $args ): array {
		try {
			$subscriber_ids = $args['subscriber_ids'] ?? [];
			$tag_ids        = $args['tag_ids'] ?? [];

			if ( empty( $subscriber_ids ) ) {
				return $this->get_error_response( 'Subscriber IDs are required', 'subscriber_ids_required' );
			}

			if ( empty( $tag_ids ) ) {
				return $this->get_error_response( 'Tag IDs are required', 'tag_ids_required' );
			}

			// Validate all tag IDs exist
			foreach ( $tag_ids as $tag_id ) {
				if ( ! $this->tag_exists( $tag_id ) ) {
					return $this->get_error_response( "Tag ID {$tag_id} not found", 'tag_not_found' );
				}
			}

			// Validate all subscriber IDs exist
			foreach ( $subscriber_ids as $subscriber_id ) {
				if ( ! $this->subscriber_exists( $subscriber_id ) ) {
					return $this->get_error_response( "Subscriber ID {$subscriber_id} not found", 'subscriber_not_found' );
				}
			}

			$removed_count = 0;
			$errors        = [];

			foreach ( $subscriber_ids as $subscriber_id ) {
				try {
					$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
					$subscriber->detachTags( $tag_ids );
					++$removed_count;
				} catch ( \Exception $e ) {
					$errors[] = "Subscriber {$subscriber_id}: " . $e->getMessage();
				}
			}

			$response_data = [
				'removed_from_subscribers' => $removed_count,
				'total_subscribers'        => count( $subscriber_ids ),
				'tag_ids'                  => $tag_ids,
				'subscriber_ids'           => $subscriber_ids,
			];

			if ( ! empty( $errors ) ) {
				$response_data['errors'] = $errors;
			}

			return $this->get_success_response(
				$response_data,
				"Tags removed from {$removed_count} subscribers successfully"
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove tags: ' . $e->getMessage(), 'remove_failed' );
		}
	}
}
