<?php
/**
 * FluentCRM Company Note Management Abilities
 *
 * Provides comprehensive company note management tools including:
 * - Note CRUD operations (create, list, get, update, delete)
 * - Company note association and retrieval
 * - Note type categorization (general, activity, call, meeting, etc.)
 * - Note history and timeline tracking
 *
 * Technical Details:
 * - Uses fc_subscriber_notes table with special status '_company_note_'
 * - Links to companies via subscriber_id (naming is historical)
 * - Auto-populates created_by with current user ID
 * - Global scope filters notes by status '_company_note_'
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */

declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * CompanyNotes Ability Class
 *
 * Manages notes attached to companies in FluentCRM including creation,
 * retrieval, updates, categorization, and deletion operations.
 */
class CompanyNotes extends BaseAbility {

	/**
	 * Check if FluentCRM company notes are available
	 *
	 * @return bool True if CompanyNote model is available
	 */
	private function are_company_notes_available(): bool {
		return class_exists( '\FluentCrm\App\Models\CompanyNote' ) &&
				class_exists( '\FluentCrm\App\Models\Company' );
	}

	/**
	 * Register all company note-related abilities
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Skip registration if company note models not available
		if ( ! $this->are_company_notes_available() ) {
			return;
		}

		// Company Note CRUD Operations
		$this->register_create_company_note();
		$this->register_list_company_notes();
		$this->register_get_company_note();
		$this->register_update_company_note();
		$this->register_delete_company_note();
	}

	/**
	 * Register create-company-note ability
	 *
	 * @return void
	 */
	private function register_create_company_note(): void {
		wp_register_ability(
			'fluentcrm/create-company-note',
			[
				'label'               => 'FluentCRM Create Company Note',
				'description'         => 'Create a note on a company with optional type categorization. Returns complete note object including auto-generated fields (id, created_by, timestamps, status). Example: Create meeting note with description "Discussed Q4 strategy" for company #123.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'company_id', 'description' ],
					'properties' => [
						'company_id'  => [
							'type'        => 'integer',
							'description' => 'Company ID to attach note to',
						],
						'type'        => [
							'type'        => 'string',
							'description' => 'Note type category for organization (e.g., general, activity, call, meeting, email)',
							'default'     => 'general',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'Optional note title or subject line',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Note content/body (supports HTML formatting)',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_create_company_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register list-company-notes ability
	 *
	 * @return void
	 */
	private function register_list_company_notes(): void {
		wp_register_ability(
			'fluentcrm/list-company-notes',
			[
				'label'               => 'FluentCRM List Company Notes',
				'description'         => 'Retrieve all notes for a specific company with pagination and optional type filtering. Returns array of note objects with complete details including creator information. Supports filtering by note type (general, activity, call, etc.).',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'company_id' ],
					'properties' => [
						'company_id' => [
							'type'        => 'integer',
							'description' => 'Company ID to get notes from',
						],
						'type'       => [
							'type'        => 'string',
							'description' => 'Filter by note type category',
						],
						'page'       => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'   => [
							'type'        => 'integer',
							'description' => 'Notes per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_list_company_notes' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register get-company-note ability
	 *
	 * @return void
	 */
	private function register_get_company_note(): void {
		wp_register_ability(
			'fluentcrm/get-company-note',
			[
				'label'               => 'FluentCRM Get Company Note',
				'description'         => 'Retrieve a specific company note by ID with complete details. Returns full note object including company relationship, creator information, and all metadata fields via toArray() method.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id' ],
					'properties' => [
						'note_id' => [
							'type'        => 'integer',
							'description' => 'Note ID to retrieve',
						],
					],
				],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'execute_callback'    => [ $this, 'execute_get_company_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register update-company-note ability
	 *
	 * @return void
	 */
	private function register_update_company_note(): void {
		wp_register_ability(
			'fluentcrm/update-company-note',
			[
				'label'               => 'FluentCRM Update Company Note',
				'description'         => 'Update an existing company note\'s content, type, or title. Supports partial updates - only provide fields to change. Returns updated note object with refreshed timestamps. All fields are optional except note_id.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id' ],
					'properties' => [
						'note_id'     => [
							'type'        => 'integer',
							'description' => 'Note ID to update',
						],
						'type'        => [
							'type'        => 'string',
							'description' => 'Update note type category',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'Update note title (empty string allowed)',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Update note content (empty string allowed)',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_update_company_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register delete-company-note ability
	 *
	 * @return void
	 */
	private function register_delete_company_note(): void {
		wp_register_ability(
			'fluentcrm/delete-company-note',
			[
				'label'               => 'FluentCRM Delete Company Note',
				'description'         => 'Permanently delete a company note with confirmation requirement. Returns deleted note ID and title for verification. Requires confirm_delete parameter set to true to prevent accidental deletion.',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'note_id', 'confirm_delete' ],
					'properties' => [
						'note_id'        => [
							'type'        => 'integer',
							'description' => 'Note ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
				],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'execute_callback'    => [ $this, 'execute_delete_company_note' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Execute create-company-note ability
	 *
	 * @param array<string, mixed> $args Note creation parameters
	 * @return array<string, mixed> Success/error response with note data
	 */
	public function execute_create_company_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CompanyNote' ) ) {
			return $this->get_error_response( 'FluentCRM CompanyNote model not available', 'model_unavailable' );
		}

		try {
			$company_id = intval( $args['company_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			// Validate company exists
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$company = \FluentCrm\App\Models\Company::find( $company_id );
			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			// Prepare note data - uses subscriber_id for company association (historical naming)
			$note_data = [
				'subscriber_id' => $company_id,
				'type'          => sanitize_text_field( $args['type'] ?? 'general' ),
				'description'   => wp_kses_post( $args['description'] ),
			];

			// Add optional title if provided
			if ( ! empty( $args['title'] ) ) {
				$note_data['title'] = sanitize_text_field( $args['title'] );
			}

			// Create the note (created_by and status are auto-populated by model boot)
			$note = \FluentCrm\App\Models\CompanyNote::create( $note_data );

			return $this->get_success_response(
				[
					'note' => $note->toArray(),
				],
				'Company note created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create company note: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list-company-notes ability
	 *
	 * @param array<string, mixed> $args List parameters
	 * @return array<string, mixed> Success/error response with notes list
	 */
	public function execute_list_company_notes( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CompanyNote' ) ) {
			return $this->get_error_response( 'FluentCRM CompanyNote model not available', 'model_unavailable' );
		}

		try {
			$company_id = intval( $args['company_id'] );
			$type       = $args['type'] ?? null;
			$page       = $args['page'] ?? 1;
			$per_page   = $args['per_page'] ?? 20;

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			// Build query - uses subscriber_id for company association
			$query = \FluentCrm\App\Models\CompanyNote::where( 'subscriber_id', $company_id );

			// Apply type filter if provided
			if ( ! empty( $type ) ) {
				$query->where( 'type', $type );
			}

			// Get total count
			$total = $query->count();

			// Get paginated results
			$offset = ( $page - 1 ) * $per_page;
			$notes  = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$note_list = [];
			foreach ( $notes as $note ) {
				$note_list[] = $note->toArray();
			}

			return $this->get_success_response(
				[
					'notes'       => $note_list,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
					'company_id'  => $company_id,
				],
				'Company notes retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list company notes: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get-company-note ability
	 *
	 * @param array<string, mixed> $args Note retrieval parameters
	 * @return array<string, mixed> Success/error response with note details
	 */
	public function execute_get_company_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CompanyNote' ) ) {
			return $this->get_error_response( 'FluentCRM CompanyNote model not available', 'model_unavailable' );
		}

		try {
			$note_id = intval( $args['note_id'] );

			if ( $note_id <= 0 ) {
				return $this->get_error_response( 'Invalid note ID', 'invalid_note_id' );
			}

			$note = \FluentCrm\App\Models\CompanyNote::find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			return $this->get_success_response(
				[
					'note' => $note->toArray(),
				],
				'Company note retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get company note: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update-company-note ability
	 *
	 * @param array<string, mixed> $args Note update parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_update_company_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CompanyNote' ) ) {
			return $this->get_error_response( 'FluentCRM CompanyNote model not available', 'model_unavailable' );
		}

		try {
			$note_id = intval( $args['note_id'] );

			if ( $note_id <= 0 ) {
				return $this->get_error_response( 'Invalid note ID', 'invalid_note_id' );
			}

			$note = \FluentCrm\App\Models\CompanyNote::find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['type'] ) ) {
				$update_data['type'] = sanitize_text_field( $args['type'] );
			}

			if ( isset( $args['title'] ) ) {
				// Allow empty strings for title
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['description'] ) ) {
				// Allow empty strings for description
				$update_data['description'] = empty( $args['description'] ) ? '' : wp_kses_post( $args['description'] );
			}

			// Update the note if there's data to update
			if ( ! empty( $update_data ) ) {
				$note->update( $update_data );
				$note = \FluentCrm\App\Models\CompanyNote::find( $note_id ); // Refresh
			}

			return $this->get_success_response(
				[
					'note' => $note->toArray(),
				],
				'Company note updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update company note: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete-company-note ability
	 *
	 * @param array<string, mixed> $args Note deletion parameters
	 * @return array<string, mixed> Success/error response
	 */
	public function execute_delete_company_note( array $args ): array {
		if ( ! class_exists( '\FluentCrm\App\Models\CompanyNote' ) ) {
			return $this->get_error_response( 'FluentCRM CompanyNote model not available', 'model_unavailable' );
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

			$note = \FluentCrm\App\Models\CompanyNote::find( $note_id );

			if ( ! $note ) {
				return $this->get_error_response( 'Note not found', 'note_not_found' );
			}

			$note_title = $note->title ?? 'Untitled Note';
			$company_id = $note->subscriber_id;

			// Delete the note
			$note->delete();

			return $this->get_success_response(
				[
					'note_id'    => $note_id,
					'note_title' => $note_title,
					'company_id' => $company_id,
					'deleted_at' => current_time( 'mysql' ),
				],
				'Company note deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete company note: ' . $e->getMessage(), 'delete_failed' );
		}
	}
}
