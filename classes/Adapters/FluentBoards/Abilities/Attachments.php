<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards Attachment Abilities
 *
 * Registers WordPress abilities for FluentBoards attachment management operations
 * using the new WordPress Abilities API pattern.
 */
class Attachments extends BaseAbility {

	/**
	 * Register all attachment-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_get_task_attachments();
		$this->register_add_task_attachment();
		$this->register_update_attachment();
		$this->register_delete_attachment();
		$this->register_upload_attachment_file();
		$this->register_get_attachment_files();
	}

	/**
	 * Register get task attachments ability
	 */
	private function register_get_task_attachments(): void {
		wp_register_ability('fluentboards_get_task_attachments', [
			'label'               => 'Get FluentBoards task attachments',
			'description'         => 'Get all attachments for a task',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_task_attachments' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'attachments',
			],
		]);
	}

	/**
	 * Register add task attachment ability
	 */
	private function register_add_task_attachment(): void {
		wp_register_ability('fluentboards_add_task_attachment', [
			'label'               => 'Add FluentBoards task attachment',
			'description'         => 'Add an attachment to a task',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'    => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'     => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'title'       => [
						'type'        => 'string',
						'description' => 'Attachment title',
					],
					'url'         => [
						'type'        => 'string',
						'description' => 'Attachment URL',
					],
					'type'        => [
						'type'        => 'string',
						'description' => 'Attachment type',
						'enum'        => [ 'file', 'link' ],
						'default'     => 'link',
					],
					'description' => [
						'type'        => 'string',
						'description' => 'Attachment description',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'title', 'url' ],
			],
			'execute_callback'    => [ $this, 'execute_add_task_attachment' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'attachments',
			],
		]);
	}

	/**
	 * Register update attachment ability
	 */
	private function register_update_attachment(): void {
		wp_register_ability('fluentboards_update_attachment', [
			'label'               => 'Update FluentBoards attachment',
			'description'         => 'Update an existing attachment',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'      => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'       => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'attachment_id' => [
						'type'        => 'integer',
						'description' => 'Attachment ID to update',
					],
					'title'         => [
						'type'        => 'string',
						'description' => 'Updated attachment title',
					],
					'url'           => [
						'type'        => 'string',
						'description' => 'Updated attachment URL',
					],
					'description'   => [
						'type'        => 'string',
						'description' => 'Updated attachment description',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'attachment_id' ],
			],
			'execute_callback'    => [ $this, 'execute_update_attachment' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'attachments',
			],
		]);
	}

	/**
	 * Register delete attachment ability
	 */
	private function register_delete_attachment(): void {
		wp_register_ability('fluentboards_delete_attachment', [
			'label'               => 'Delete FluentBoards attachment',
			'description'         => 'Delete an attachment permanently',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'       => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'        => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'attachment_id'  => [
						'type'        => 'integer',
						'description' => 'Attachment ID to delete',
					],
					'confirm_delete' => [
						'type'        => 'boolean',
						'description' => 'Confirmation required: set to true to proceed with deletion',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'attachment_id', 'confirm_delete' ],
			],
			'execute_callback'    => [ $this, 'execute_delete_attachment' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'attachments',
			],
		]);
	}

	/**
	 * Register upload attachment file ability (Pro required)
	 */
	private function register_upload_attachment_file(): void {
		wp_register_ability('fluentboards_upload_attachment_file', [
			'label'               => 'Upload FluentBoards attachment file',
			'description'         => 'Upload a file attachment to a task (requires FluentBoards Pro)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'    => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'     => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
					'file_data'   => [
						'type'        => 'string',
						'description' => 'Base64 encoded file data',
					],
					'file_name'   => [
						'type'        => 'string',
						'description' => 'File name with extension',
					],
					'file_type'   => [
						'type'        => 'string',
						'description' => 'MIME type of the file',
					],
					'title'       => [
						'type'        => 'string',
						'description' => 'Attachment title (optional, defaults to filename)',
					],
					'description' => [
						'type'        => 'string',
						'description' => 'Attachment description',
					],
				],
				'required'   => [ 'board_id', 'task_id', 'file_data', 'file_name', 'file_type' ],
			],
			'execute_callback'    => [ $this, 'execute_upload_attachment_file' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'     => 'fluentboards',
				'subcategory'  => 'attachments',
				'requires_pro' => true,
			],
		]);
	}

	/**
	 * Register get attachment files ability (Pro required)
	 */
	private function register_get_attachment_files(): void {
		wp_register_ability('fluentboards_get_attachment_files', [
			'label'               => 'Get FluentBoards attachment files',
			'description'         => 'Get all files attached to a task (requires FluentBoards Pro)',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID',
					],
					'task_id'  => [
						'type'        => 'integer',
						'description' => 'Task ID',
					],
				],
				'required'   => [ 'board_id', 'task_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_attachment_files' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'     => 'fluentboards',
				'subcategory'  => 'attachments',
				'requires_pro' => true,
			],
		]);
	}

	/**
	 * Execute get task attachments ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_task_attachments( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$task_id  = intval( $args['task_id'] );

			if ( $board_id <= 0 || $task_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or task ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$attachments      = $attachment_model->where( 'task_id', $task_id )
										->orderBy( 'created_at', 'DESC' )
										->get();

			$result = [];
			foreach ( $attachments as $attachment ) {
				$result[] = [
					'id'          => $attachment->id,
					'title'       => $attachment->title,
					'url'         => $attachment->full_url,
					'type'        => $attachment->type,
					'description' => $attachment->description,
					'file_size'   => $attachment->file_size,
					'mime_type'   => $attachment->mime_type,
					'created_at'  => $attachment->created_at,
					'created_by'  => $attachment->created_by,
				];
			}

			return $this->get_success_response([
				'task_id'           => $task_id,
				'board_id'          => $board_id,
				'attachments'       => $result,
				'total_attachments' => count( $result ),
			], 'Task attachments retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get task attachments: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute add task attachment ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_task_attachment( array $args ): array {
		try {
			$board_id    = intval( $args['board_id'] );
			$task_id     = intval( $args['task_id'] );
			$title       = sanitize_text_field( $args['title'] );
			$url         = esc_url_raw( $args['url'] );
			$type        = $args['type'] ?? 'link';
			$description = sanitize_textarea_field( $args['description'] ?? '' );

			if ( $board_id <= 0 || $task_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or task ID', 'invalid_ids' );
			}

			if ( empty( $title ) || empty( $url ) ) {
				return $this->get_error_response( 'Title and URL are required', 'missing_required_fields' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$attachment       = $attachment_model->create([
				'task_id'     => $task_id,
				'title'       => $title,
				'full_url'    => $url,
				'type'        => $type,
				'description' => $description,
				'created_by'  => get_current_user_id(),
			]);

			return $this->get_success_response([
				'attachment' => [
					'id'          => $attachment->id,
					'title'       => $attachment->title,
					'url'         => $attachment->full_url,
					'type'        => $attachment->type,
					'description' => $attachment->description,
					'created_at'  => $attachment->created_at,
					'created_by'  => $attachment->created_by,
				],
				'task_id'    => $task_id,
				'board_id'   => $board_id,
			], 'Attachment added successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add attachment: ' . $e->getMessage(), 'add_failed' );
		}
	}

	/**
	 * Execute update attachment ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_attachment( array $args ): array {
		try {
			$board_id      = intval( $args['board_id'] );
			$task_id       = intval( $args['task_id'] );
			$attachment_id = intval( $args['attachment_id'] );

			if ( $board_id <= 0 || $task_id <= 0 || $attachment_id <= 0 ) {
				return $this->get_error_response( 'Invalid board, task, or attachment ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Get the attachment
			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$attachment       = $attachment_model->where( 'id', $attachment_id )
										->where( 'task_id', $task_id )
										->first();

			if ( ! $attachment ) {
				return $this->get_error_response( 'Attachment not found', 'attachment_not_found' );
			}

			// Prepare update data
			$update_data = [];

			if ( isset( $args['title'] ) ) {
				$update_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['url'] ) ) {
				$update_data['full_url'] = esc_url_raw( $args['url'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			if ( empty( $update_data ) ) {
				return $this->get_error_response( 'No fields to update', 'no_update_data' );
			}

			$attachment->update( $update_data );
			$attachment = $attachment_model->find( $attachment_id ); // Refresh

			return $this->get_success_response([
				'attachment' => [
					'id'          => $attachment->id,
					'title'       => $attachment->title,
					'url'         => $attachment->full_url,
					'type'        => $attachment->type,
					'description' => $attachment->description,
					'updated_at'  => $attachment->updated_at,
				],
				'task_id'    => $task_id,
				'board_id'   => $board_id,
			], 'Attachment updated successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update attachment: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete attachment ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_attachment( array $args ): array {
		try {
			$board_id       = intval( $args['board_id'] );
			$task_id        = intval( $args['task_id'] );
			$attachment_id  = intval( $args['attachment_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $board_id <= 0 || $task_id <= 0 || $attachment_id <= 0 ) {
				return $this->get_error_response( 'Invalid board, task, or attachment ID', 'invalid_ids' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Get the attachment
			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$attachment       = $attachment_model->where( 'id', $attachment_id )
										->where( 'task_id', $task_id )
										->first();

			if ( ! $attachment ) {
				return $this->get_error_response( 'Attachment not found', 'attachment_not_found' );
			}

			$attachment_title = $attachment->title;

			// Delete the attachment
			$attachment->delete();

			return $this->get_success_response([
				'attachment_id'    => $attachment_id,
				'attachment_title' => $attachment_title,
				'task_id'          => $task_id,
				'board_id'         => $board_id,
				'deleted_at'       => current_time( 'mysql' ),
			], 'Attachment deleted successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete attachment: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute upload attachment file ability (Pro required)
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_upload_attachment_file( array $args ): array {
		try {
			// Check if Pro features are available
			if ( ! $this->is_fluent_boards_pro_active() ) {
				return $this->get_error_response( 'This feature requires FluentBoards Pro', 'pro_required' );
			}

			$board_id    = intval( $args['board_id'] );
			$task_id     = intval( $args['task_id'] );
			$file_data   = $args['file_data'];
			$file_name   = sanitize_file_name( $args['file_name'] );
			$file_type   = $args['file_type'];
			$title       = sanitize_text_field( $args['title'] ?? $file_name );
			$description = sanitize_textarea_field( $args['description'] ?? '' );

			if ( $board_id <= 0 || $task_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or task ID', 'invalid_ids' );
			}

			if ( empty( $file_data ) || empty( $file_name ) || empty( $file_type ) ) {
				return $this->get_error_response( 'File data, name, and type are required', 'missing_file_data' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			// Decode base64 file data
			$decoded_data = base64_decode( $file_data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false === $decoded_data ) {
				return $this->get_error_response( 'Invalid base64 file data', 'invalid_file_data' );
			}

			// Validate file type and size
			$allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword' ];
			if ( ! in_array( $file_type, $allowed_types, true ) ) {
				return $this->get_error_response( 'File type not allowed', 'invalid_file_type' );
			}

			$max_size = 10 * 1024 * 1024; // 10MB
			if ( strlen( $decoded_data ) > $max_size ) {
				return $this->get_error_response( 'File size too large (max 10MB)', 'file_too_large' );
			}

			// Use WordPress media handling
			$upload_dir  = wp_upload_dir();
			$upload_path = $upload_dir['path'] . '/' . $file_name;
			$upload_url  = $upload_dir['url'] . '/' . $file_name;

			// Write file to uploads directory
			$result = file_put_contents( $upload_path, $decoded_data ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === $result ) {
				return $this->get_error_response( 'Failed to save file', 'file_save_failed' );
			}

			// Create WordPress attachment
			$attachment_data = [
				'post_mime_type' => $file_type,
				'post_title'     => $title,
				'post_content'   => $description,
				'post_status'    => 'inherit',
			];

			$wp_attachment_id = wp_insert_attachment( $attachment_data, $upload_path );
			if ( is_wp_error( $wp_attachment_id ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $upload_path ); // Clean up file on error
				return $this->get_error_response( 'Failed to create WordPress attachment', 'wp_attachment_failed' );
			}

			// Generate metadata
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attachment_metadata = wp_generate_attachment_metadata( $wp_attachment_id, $upload_path );
			wp_update_attachment_metadata( $wp_attachment_id, $attachment_metadata );

			// Create FluentBoards attachment record
			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$attachment       = $attachment_model->create([
				'task_id'          => $task_id,
				'title'            => $title,
				'full_url'         => $upload_url,
				'type'             => 'file',
				'description'      => $description,
				'file_size'        => strlen( $decoded_data ),
				'mime_type'        => $file_type,
				'wp_attachment_id' => $wp_attachment_id,
				'created_by'       => get_current_user_id(),
			]);

			return $this->get_success_response([
				'attachment' => [
					'id'               => $attachment->id,
					'title'            => $attachment->title,
					'url'              => $attachment->full_url,
					'type'             => $attachment->type,
					'description'      => $attachment->description,
					'file_size'        => $attachment->file_size,
					'mime_type'        => $attachment->mime_type,
					'wp_attachment_id' => $wp_attachment_id,
					'created_at'       => $attachment->created_at,
				],
				'task_id'    => $task_id,
				'board_id'   => $board_id,
			], 'File uploaded successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to upload file: ' . $e->getMessage(), 'upload_failed' );
		}
	}

	/**
	 * Execute get attachment files ability (Pro required)
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_attachment_files( array $args ): array {
		try {
			// Check if Pro features are available
			if ( ! $this->is_fluent_boards_pro_active() ) {
				return $this->get_error_response( 'This feature requires FluentBoards Pro', 'pro_required' );
			}

			$board_id = intval( $args['board_id'] );
			$task_id  = intval( $args['task_id'] );

			if ( $board_id <= 0 || $task_id <= 0 ) {
				return $this->get_error_response( 'Invalid board or task ID', 'invalid_ids' );
			}

			// Verify task belongs to board and user has access
			$task = $this->get_task_with_access_check( $board_id, $task_id );
			if ( is_array( $task ) && isset( $task['error'] ) ) {
				return $task;
			}

			$attachment_model = new \FluentBoards\App\Models\Attachment();
			$files            = $attachment_model->where( 'task_id', $task_id )
									->where( 'type', 'file' )
									->whereNotNull( 'wp_attachment_id' )
									->orderBy( 'created_at', 'DESC' )
									->get();

			$result = [];
			foreach ( $files as $file ) {
				$wp_attachment = get_post( $file->wp_attachment_id );
				$result[]      = [
					'id'                  => $file->id,
					'title'               => $file->title,
					'url'                 => $file->full_url,
					'description'         => $file->description,
					'file_size'           => $file->file_size,
					'mime_type'           => $file->mime_type,
					'wp_attachment_id'    => $file->wp_attachment_id,
					'wp_attachment_title' => $wp_attachment ? $wp_attachment->post_title : null,
					'created_at'          => $file->created_at,
					'created_by'          => $file->created_by,
				];
			}

			return $this->get_success_response([
				'task_id'     => $task_id,
				'board_id'    => $board_id,
				'files'       => $result,
				'total_files' => count( $result ),
			], 'Attachment files retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get attachment files: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Get task with access check
	 *
	 * @param int $board_id Board ID
	 * @param int $task_id Task ID
	 * @return object|array Task object or error array
	 */
	private function get_task_with_access_check( int $board_id, int $task_id ) {
		$task_model = new \FluentBoards\App\Models\Task();
		$task       = $task_model->where( 'id', $task_id )
						->where( 'board_id', $board_id )
						->first();

		if ( ! $task ) {
			return $this->get_error_response( 'Task not found', 'task_not_found' );
		}

		// Check board access
		$board_model = new \FluentBoards\App\Models\Board();
		$board       = $board_model->find( $board_id );

		if ( ! $board ) {
			return $this->get_error_response( 'Board not found', 'board_not_found' );
		}

		$user_id = get_current_user_id();
		if ( ! $this->can_access_board( $board, $user_id ) ) {
			return $this->get_error_response( 'Access denied to board', 'access_denied' );
		}

		return $task;
	}

	/**
	 * Check if user can access specific board
	 */
	private function can_access_board( $board, $user_id ): bool {
		if ( ! $board || ! $user_id ) {
			return false;
		}

		// Admin can access all boards
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if user is board owner
		if ( $board->created_by === $user_id ) {
			return true;
		}

		// Check if user is assigned to the board
		$user_board = $board->users()->where( 'fbs_relations.foreign_id', $user_id )->first();
		return ! empty( $user_board );
	}

	/**
	 * Check if FluentBoards Pro is active
	 */
	private function is_fluent_boards_pro_active(): bool {
		return defined( 'FLUENT_BOARDS_PRO' ) ||
				is_plugin_active( 'fluent-boards-pro/fluent-boards-pro.php' ) ||
				function_exists( 'fluent_boards_pro' );
	}
}
