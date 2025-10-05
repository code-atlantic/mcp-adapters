<?php
/**
 * FluentCRM Subscriber Notes Management Abilities
 *
 * Provides comprehensive contact note management tools including:
 * - Note CRUD operations (create, list, get, update, delete)
 * - Note type management (note, activity log, custom types)
 * - Subscriber-specific note retrieval
 * - Note history and activity tracking
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * SubscriberNotes Ability Class
 *
 * Manages subscriber notes in FluentCRM including creation, retrieval,
 * updates, and deletion of notes associated with contacts. Notes support
 * various types (note, activity, system logs) and parent-child relationships.
 */
class SubscriberNotes extends BaseAbility {

	/**
	 * Check if FluentCRM subscriber notes are available
	 *
	 * @return bool True if SubscriberNote model is available
	 */
	private function are_subscriber_notes_available(): bool {
		return class_exists( '\FluentCrm\App\Models\SubscriberNote' );
	}

	/**
	 * Register all subscriber note-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if subscriber notes not available
		if ( ! $this->are_subscriber_notes_available() ) {
			return;
		}

		// Note CRUD Operations
		$this->register_create_subscriber_note();
		$this->register_list_subscriber_notes();
		$this->register_get_subscriber_note();
		$this->register_update_subscriber_note();
		$this->register_delete_subscriber_note();
	}

	/**
	 * Register create-subscriber-note ability
	 *
	 * @return void
	 */
	private function register_create_subscriber_note(): void {
		wp_register_ability(
			'fluentcrm/create-subscriber-note',
			[
				'label'               => 'FluentCRM Create Subscriber Note',
				'description'         => 'Create a note on a contact record. Returns complete note data including ID, timestamps, and creator information. Relations NOT included by default - use \'with\' parameter to load relationships.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id' ],
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Contact ID to add note to',
							'minimum'     => 1,
						],
						'title'         => [
							'type'        => 'string',
							'description' => 'Note title or subject (optional for simple notes)',
						],
						'description'   => [
							'type'        => 'string',
							'description' => 'Note content or body text',
						],
						'type'          => [
							'type'        => 'string',
							'description' => 'Note type for categorization',
							'enum'        => [ 'note', 'activity', 'call', 'email', 'meeting', 'task' ],
							'default'     => 'note',
						],
						'parent_id'     => [
							'type'        => 'integer',
							'description' => 'Parent note ID for threaded/nested notes',
							'minimum'     => 1,
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_create_subscriber_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'contacts',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register list-subscriber-notes ability
	 *
	 * @return void
	 */
	private function register_list_subscriber_notes(): void {
		wp_register_ability(
			'fluentcrm/list-subscriber-notes',
			[
				'label'               => 'FluentCRM List Subscriber Notes',
				'description'         => 'List all notes for a contact with optional type filtering and pagination. Returns array of notes with complete data. Relations NOT included by default - use \'with\' parameter to load relationships.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'subscriber_id' ],
					'properties' => [
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Contact ID to get notes from',
							'minimum'     => 1,
						],
						'type'          => [
							'type'        => 'string',
							'description' => 'Filter by note type',
							'enum'        => [ 'note', 'activity', 'call', 'email', 'meeting', 'task' ],
						],
						'page'          => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'      => [
							'type'        => 'integer',
							'description' => 'Notes per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'with'          => [
							'type'        => 'array',
							'description' => 'Relations to load (e.g., ["subscriber"])',
							'items'       => [
								'type' => 'string',
								'enum' => [ 'subscriber' ],
							],
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_list_subscriber_notes' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'contacts',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register get-subscriber-note ability
	 *
	 * @return void
	 */
	private function register_get_subscriber_note(): void {
		wp_register_ability(
			'fluentcrm/get-subscriber-note',
			[
				'label'               => 'FluentCRM Get Subscriber Note',
				'description'         => 'Get a specific note by ID with complete details including creator information. Returns full note data with all fields. Relations NOT included by default - use \'with\' parameter to load relationships.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id' ],
					'properties' => [
						'note_id' => [
							'type'        => 'integer',
							'description' => 'Note ID to retrieve',
							'minimum'     => 1,
						],
						'with'    => [
							'type'        => 'array',
							'description' => 'Relations to load (e.g., ["subscriber"])',
							'items'       => [
								'type' => 'string',
								'enum' => [ 'subscriber' ],
							],
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_subscriber_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'contacts',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register update-subscriber-note ability
	 *
	 * @return void
	 */
	private function register_update_subscriber_note(): void {
		wp_register_ability(
			'fluentcrm/update-subscriber-note',
			[
				'label'               => 'FluentCRM Update Subscriber Note',
				'description'         => 'Update an existing note\'s content or type. All fields are optional - only provided fields will be updated. Returns updated note with all fields.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id' ],
					'properties' => [
						'note_id'     => [
							'type'        => 'integer',
							'description' => 'Note ID to update',
							'minimum'     => 1,
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'Updated note title',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Updated note content',
						],
						'type'        => [
							'type'        => 'string',
							'description' => 'Updated note type',
							'enum'        => [ 'note', 'activity', 'call', 'email', 'meeting', 'task' ],
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_update_subscriber_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'contacts',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Register delete-subscriber-note ability
	 *
	 * @return void
	 */
	private function register_delete_subscriber_note(): void {
		wp_register_ability(
			'fluentcrm/delete-subscriber-note',
			[
				'label'               => 'FluentCRM Delete Subscriber Note',
				'description'         => 'Delete a note permanently. Requires explicit confirmation to prevent accidental deletion. Returns deleted note ID and timestamp.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id', 'confirm_delete' ],
					'properties' => [
						'note_id'        => [
							'type'        => 'integer',
							'description' => 'Note ID to delete',
							'minimum'     => 1,
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_delete_subscriber_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'contacts',
					'pro_feature' => false,
				],
			]
		);
	}

	/**
	 * Execute create-subscriber-note ability
	 *
	 * @param array<string, mixed> $args Note creation parameters
	 * @return array<string, mixed> Success/error response with note data
	 */
	public function execute_create_subscriber_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
			return $this->get_error_response( 'FluentCRM SubscriberNote model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id = intval( $args['subscriber_id'] );

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Prepare note data
			$note_data = [
				'subscriber_id' => $subscriber_id,
				'type'          => $args['type'] ?? 'note',
			];

			// Add optional fields
			if ( isset( $args['title'] ) && ! empty( $args['title'] ) ) {
				$note_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				// Allow empty strings for description
				$note_data['description'] = empty( $args['description'] ) ? '' : wp_kses_post( $args['description'] );
			}

			if ( isset( $args['parent_id'] ) ) {
				$note_data['parent_id'] = intval( $args['parent_id'] );
			}

			// Create the note
			$note = \FluentCrm\App\Models\SubscriberNote::create( $note_data );

			// Load relations if requested
			if ( ! empty( $args['with'] ) && is_array( $args['with'] ) ) {
				$note->load( $args['with'] );
			}

			return $this->get_success_response(
				[
					'note' => $note->toArray(),
				],
				'Note created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create note: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list-subscriber-notes ability
	 *
	 * @param array<string, mixed> $args List parameters
	 * @return array<string, mixed> Success/error response with notes list
	 */
	public function execute_list_subscriber_notes( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
			return $this->get_error_response( 'FluentCRM SubscriberNote model not available', 'model_unavailable' );
		}

		try {
			$subscriber_id = intval( $args['subscriber_id'] );

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$type     = $args['type'] ?? null;

			// Build query
			$query = \FluentCrm\App\Models\SubscriberNote::where( 'subscriber_id', $subscriber_id );

			// Apply type filter
			if ( $type ) {
				$query->where( 'type', $type );
			}

			// Get total count
			$total = $query->count();

			// Get paginated results
			$offset = ( $page - 1 ) * $per_page;
			$notes  = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page );

			// Load relations if requested
			if ( ! empty( $args['with'] ) && is_array( $args['with'] ) ) {
				$notes->with( $args['with'] );
			}

			$notes = $notes->get();

			// Convert to array with toArray()
			$notes_array = [];
			foreach ( $notes as $note ) {
				$notes_array[] = $note->toArray();
			}

			return $this->get_success_response(
				[
					'notes'       => $notes_array,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Notes retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list notes: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get-subscriber-note ability
	 *
	 * @param array<string, mixed> $args Note retrieval parameters
	 * @return array<string, mixed> Success/error response with note details
	 */
	public function execute_get_subscriber_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
			return $this->get_error_response( 'FluentCRM SubscriberNote model not available', 'model_unavailable' );
		}

		try {
			$note_id = intval( $args['note_id'] );

			if ( $note_id <= 0 ) {
				return $this->get_error_response( 'Invalid note ID', 'invalid_note_id' );
			}

			// Build query
			$query = \FluentCrm\App\Models\SubscriberNote::query();

			// Load relations if requested
			if ( ! empty( $args['with'] ) && is_array( $args['with'] ) ) {
				$query->with( $args['with'] );
			}

			$note = $query->find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			// Get creator information if available
			$note_data           = $note->toArray();
			$note_data['author'] = $note->createdBy();

			return $this->get_success_response(
				[
					'note' => $note_data,
				],
				'Note retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get note: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update-subscriber-note ability
	 *
	 * @param array<string, mixed> $args Note update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_subscriber_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
			return $this->get_error_response( 'FluentCRM SubscriberNote model not available', 'model_unavailable' );
		}

		try {
			$note_id = intval( $args['note_id'] );

			if ( $note_id <= 0 ) {
				return $this->get_error_response( 'Invalid note ID', 'invalid_note_id' );
			}

			$note = \FluentCrm\App\Models\SubscriberNote::find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				// Allow empty strings for description
				$update_data['description'] = empty( $args['description'] ) ? '' : wp_kses_post( $args['description'] );
			}

			if ( isset( $args['type'] ) ) {
				$update_data['type'] = $args['type'];
			}

			// Update the note if there are changes
			if ( ! empty( $update_data ) ) {
				$note->update( $update_data );
				$note = \FluentCrm\App\Models\SubscriberNote::find( $note_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'note' => $note->toArray(),
				],
				'Note updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update note: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete-subscriber-note ability
	 *
	 * @param array<string, mixed> $args Note deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_subscriber_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
			return $this->get_error_response( 'FluentCRM SubscriberNote model not available', 'model_unavailable' );
		}

		try {
			$note_id        = intval( $args['note_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $note_id <= 0 ) {
				return $this->get_error_response( 'Invalid note ID', 'invalid_note_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			$note = \FluentCrm\App\Models\SubscriberNote::find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			$note_title = $note->title ?? 'Untitled Note';

			// Delete the note
			$note->delete();

			return $this->get_success_response(
				[
					'note_id'    => $note_id,
					'note_title' => $note_title,
					'deleted_at' => current_time( 'mysql' ),
				],
				'Note deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete note: ' . $e->getMessage(), 'delete_failed' );
		}
	}
}
