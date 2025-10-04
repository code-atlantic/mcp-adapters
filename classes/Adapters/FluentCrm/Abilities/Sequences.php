<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Sequence Abilities
 *
 * Registers WordPress abilities for FluentCRM email sequence management operations.
 * Sequences are a FluentCRM Pro feature that allows automated email delivery.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 */
class Sequences extends BaseAbility {

	/**
	 * Register all sequence-related abilities
	 */
	protected function register_abilities(): void {
		// Check if FluentCampaign (Pro) is active
		if ( ! $this->is_fluentcampaign_active() ) {
			return;
		}

		$this->register_create_sequence();
		$this->register_list_sequences();
		$this->register_get_sequence();
		$this->register_update_sequence();
		$this->register_delete_sequence();
		$this->register_add_subscriber_to_sequence();
		$this->register_remove_subscriber_from_sequence();
		$this->register_get_sequence_performance();

		// Sequence email management
		$this->register_add_sequence_email();
		$this->register_list_sequence_emails();
		$this->register_update_sequence_email();
		$this->register_delete_sequence_email();
	}

	/**
	 * Check if FluentCampaign (Pro) plugin is active
	 *
	 * @return bool True if FluentCampaign is active
	 */
	private function is_fluentcampaign_active(): bool {
		return defined( 'FLUENTCAMPAIGN' ) && class_exists( '\FluentCampaign\App\Models\Sequence' );
	}

	/**
	 * Register create sequence ability
	 */
	private function register_create_sequence(): void {
		wp_register_ability(
			'fluentcrm/create-sequence',
			[
				'label'               => 'Create FluentCRM Sequence',
				'description'         => 'Create a new email sequence (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'title'       => [
							'type'        => 'string',
							'description' => 'Sequence title (required)',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'Sequence description',
						],
						'status'      => [
							'type'        => 'string',
							'description' => 'Sequence status',
							'enum'        => [ 'draft', 'published', 'archived' ],
							'default'     => 'draft',
						],
						'settings'    => [
							'type'        => 'object',
							'description' => 'Sequence configuration settings',
							'properties'  => [
								'email_subject_prefix'    => [
									'type'        => 'string',
									'description' => 'Prefix for all email subjects in this sequence',
								],
								'unsubscribe_on_complete' => [
									'type'        => 'boolean',
									'description' => 'Automatically unsubscribe contacts after sequence completes',
									'default'     => false,
								],
								'allow_re_enrollment'     => [
									'type'        => 'boolean',
									'description' => 'Allow contacts to re-enter the sequence',
									'default'     => false,
								],
							],
						],
					],
					'required'   => [ 'title' ],
				],
				'execute_callback'    => [ $this, 'execute_create_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register list sequences ability
	 */
	private function register_list_sequences(): void {
		wp_register_ability(
			'fluentcrm/list-sequences',
			[
				'label'               => 'List FluentCRM Sequences',
				'description'         => 'List all email sequences with filters (FluentCRM Pro feature)',
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
							'description' => 'Number of sequences per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'status'   => [
							'type'        => 'string',
							'description' => 'Filter by status',
							'enum'        => [ 'draft', 'published', 'archived' ],
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search sequences by title',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_sequences' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get sequence ability
	 */
	private function register_get_sequence(): void {
		wp_register_ability(
			'fluentcrm/get-sequence',
			[
				'label'               => 'Get FluentCRM Sequence',
				'description'         => 'Get detailed information about a specific sequence including emails (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id' => [
							'type'        => 'integer',
							'description' => 'Sequence ID to retrieve',
						],
					],
					'required'   => [ 'sequence_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register update sequence ability
	 */
	private function register_update_sequence(): void {
		wp_register_ability(
			'fluentcrm/update-sequence',
			[
				'label'               => 'Update FluentCRM Sequence',
				'description'         => 'Update an existing email sequence configuration (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id' => [
							'type'        => 'integer',
							'description' => 'Sequence ID to update (required)',
						],
						'title'       => [
							'type'        => 'string',
							'description' => 'New sequence title',
						],
						'description' => [
							'type'        => 'string',
							'description' => 'New sequence description',
						],
						'status'      => [
							'type'        => 'string',
							'description' => 'New sequence status',
							'enum'        => [ 'draft', 'published', 'archived' ],
						],
						'settings'    => [
							'type'        => 'object',
							'description' => 'Sequence configuration settings',
							'properties'  => [
								'email_subject_prefix'    => [
									'type'        => 'string',
									'description' => 'Prefix for all email subjects in this sequence',
								],
								'unsubscribe_on_complete' => [
									'type'        => 'boolean',
									'description' => 'Automatically unsubscribe contacts after sequence completes',
								],
								'allow_re_enrollment'     => [
									'type'        => 'boolean',
									'description' => 'Allow contacts to re-enter the sequence',
								],
							],
						],
					],
					'required'   => [ 'sequence_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register delete sequence ability
	 */
	private function register_delete_sequence(): void {
		wp_register_ability(
			'fluentcrm/delete-sequence',
			[
				'label'               => 'Delete FluentCRM Sequence',
				'description'         => 'Delete a sequence permanently (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id'    => [
							'type'        => 'integer',
							'description' => 'Sequence ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required: set to true to proceed with deletion',
						],
					],
					'required'   => [ 'sequence_id', 'confirm_delete' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register add subscriber to sequence ability
	 */
	private function register_add_subscriber_to_sequence(): void {
		wp_register_ability(
			'fluentcrm/add-subscriber-to-sequence',
			[
				'label'               => 'Add Subscriber to FluentCRM Sequence',
				'description'         => 'Enroll a contact in an email sequence (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id'   => [
							'type'        => 'integer',
							'description' => 'Sequence ID',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber/contact ID to enroll',
						],
						'restart'       => [
							'type'        => 'boolean',
							'description' => 'Restart sequence from beginning if already enrolled',
							'default'     => false,
						],
					],
					'required'   => [ 'sequence_id', 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_add_subscriber_to_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register remove subscriber from sequence ability
	 */
	private function register_remove_subscriber_from_sequence(): void {
		wp_register_ability(
			'fluentcrm/remove-subscriber-from-sequence',
			[
				'label'               => 'Remove Subscriber from FluentCRM Sequence',
				'description'         => 'Unenroll a contact from an email sequence (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id'   => [
							'type'        => 'integer',
							'description' => 'Sequence ID',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber/contact ID to unenroll',
						],
					],
					'required'   => [ 'sequence_id', 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_subscriber_from_sequence' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Register get sequence performance ability
	 */
	private function register_get_sequence_performance(): void {
		wp_register_ability(
			'fluentcrm/get-sequence-performance',
			[
				'label'               => 'Get FluentCRM Sequence Performance',
				'description'         => 'Get analytics and performance metrics for a sequence (FluentCRM Pro feature)',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'sequence_id' => [
							'type'        => 'integer',
							'description' => 'Sequence ID',
						],
					],
					'required'   => [ 'sequence_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_sequence_performance' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute create sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_sequence( array $args ): array {
		try {
			$title       = sanitize_text_field( $args['title'] );
			$description = sanitize_textarea_field( $args['description'] ?? '' );
			$status      = $args['status'] ?? 'draft';
			$settings    = $args['settings'] ?? [];

			if ( empty( $title ) ) {
				return $this->get_error_response( 'Sequence title is required', 'title_required' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			// Create sequence with only fillable fields (title, settings)
			$sequence = \FluentCampaign\App\Models\Sequence::create(
				[
					'title'    => $title,
					'settings' => $settings,
				]
			);

			// Update non-fillable fields (description, status) after creation using raw DB query
			// FluentCRM's Sequence model doesn't allow these in $fillable array
			$update_data = [];
			$formats     = [];

			// Always set status if it's not draft
			if ( $status !== 'draft' ) {
				$update_data['status'] = $status;
				$formats[]             = '%s';
			}

			// Set description if provided (even if empty string to clear it)
			if ( isset( $args['description'] ) ) {
				$update_data['description'] = $description;
				$formats[]                  = '%s';
			}

			// Apply updates if we have any
			if ( ! empty( $update_data ) ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'fc_campaigns';
				$wpdb->update(
					$table_name,
					$update_data,
					[ 'id' => $sequence->id ],
					$formats,
					[ '%d' ]
				);

				// Directly update the sequence object with the new values to avoid cache issues
				foreach ( $update_data as $key => $value ) {
					$sequence->$key = $value;
				}
			}

			return $this->get_success_response(
				[
					'sequence' => $this->format_sequence_response( $sequence ),
				],
				'Sequence created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create sequence: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list sequences ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_sequences( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$status   = $args['status'] ?? null;
			$search   = $args['search'] ?? '';

			$query = \FluentCampaign\App\Models\Sequence::query();

			// Add status filter if provided
			if ( ! empty( $status ) ) {
				$query = $query->where( 'status', $status );
			}

			// Add search if provided
			if ( ! empty( $search ) ) {
				$query = $query->where( 'title', 'like', '%' . $search . '%' );
			}

			// Get total count before pagination
			$total = $query->count();

			// Get sequences with pagination
			$offset    = ( $page - 1 ) * $per_page;
			$sequences = $query->orderBy( 'created_at', 'DESC' )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$result = [];
			foreach ( $sequences as $sequence ) {
				$result[] = [
					'id'          => $sequence->id,
					'title'       => $sequence->title,
					'description' => $sequence->description ?? '',
					'status'      => $sequence->status,
					'created_at'  => $sequence->created_at,
					'updated_at'  => $sequence->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'sequences'   => $result,
					'total'       => $total,
					'page'        => $page,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total / $per_page ),
				],
				'Sequences retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list sequences: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_sequence( array $args ): array {
		try {
			$sequence_id = intval( $args['sequence_id'] );

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );

			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Get sequence emails using SequenceMail model
			$emails = [];
			if ( class_exists( '\FluentCampaign\App\Models\SequenceMail' ) ) {
				$sequence_emails = \FluentCampaign\App\Models\SequenceMail::where( 'parent_id', $sequence_id )
					->orderBy( 'delay', 'ASC' )
					->get();

				foreach ( $sequence_emails as $email ) {
					$delay_unit = 'days';
					if ( isset( $email->settings['timings']['delay_unit'] ) ) {
						$delay_unit = $email->settings['timings']['delay_unit'];
					}

					$emails[] = [
						'id'            => $email->id,
						'title'         => $email->title,
						'email_subject' => $email->email_subject,
						'delay'         => $email->delay,
						'delay_unit'    => $delay_unit,
						'status'        => $email->status,
					];
				}
			}

			$response                 = $this->format_sequence_response( $sequence );
			$response['emails_count'] = count( $emails );
			$response['emails']       = $emails;

			return $this->get_success_response(
				[
					'sequence' => $response,
				],
				'Sequence retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get sequence: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_sequence( array $args ): array {
		try {
			$sequence_id = intval( $args['sequence_id'] );

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );

			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Separate fillable and non-fillable fields
			$fillable_data     = [];
			$non_fillable_data = [];

			if ( isset( $args['title'] ) ) {
				$fillable_data['title'] = sanitize_text_field( $args['title'] );
			}

			if ( isset( $args['settings'] ) ) {
				$fillable_data['settings'] = array_merge( $sequence->settings ?? [], $args['settings'] );
			}

			// Non-fillable fields (must use direct DB update)
			if ( isset( $args['description'] ) ) {
				$non_fillable_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			if ( isset( $args['status'] ) ) {
				$non_fillable_data['status'] = $args['status'];
			}

			// Update fillable fields through model
			if ( ! empty( $fillable_data ) ) {
				$sequence->update( $fillable_data );
			}

			// Update non-fillable fields through direct DB query
			if ( ! empty( $non_fillable_data ) ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'fc_campaigns';
				$formats    = array_fill( 0, count( $non_fillable_data ), '%s' );
				$wpdb->update(
					$table_name,
					$non_fillable_data,
					[ 'id' => $sequence_id ],
					$formats,
					[ '%d' ]
				);

				// Directly update the sequence object with the new values to avoid cache issues
				foreach ( $non_fillable_data as $key => $value ) {
					$sequence->$key = $value;
				}
			}

			return $this->get_success_response(
				[
					'sequence' => $this->format_sequence_response( $sequence ),
				],
				'Sequence updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update sequence: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_sequence( array $args ): array {
		try {
			$sequence_id    = intval( $args['sequence_id'] );
			$confirm_delete = $args['confirm_delete'] ?? false;

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );

			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			$sequence_title = $sequence->title;

			// Delete the sequence (cascade will handle related emails and subscribers)
			$sequence->delete();

			return $this->get_success_response(
				[
					'sequence_id'    => $sequence_id,
					'sequence_title' => $sequence_title,
					'deleted_at'     => current_time( 'mysql' ),
				],
				'Sequence deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete sequence: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute add subscriber to sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_subscriber_to_sequence( array $args ): array {
		try {
			$sequence_id   = intval( $args['sequence_id'] );
			$subscriber_id = intval( $args['subscriber_id'] );
			$restart       = $args['restart'] ?? false;

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			// Verify sequence exists
			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Check if sequence is published
			if ( 'published' !== $sequence->status ) {
				return $this->get_error_response( 'Sequence must be published to enroll subscribers', 'sequence_not_published' );
			}

			// Enroll subscriber in sequence
			// Note: FluentCRM's subscribe() may return null/false even on success if sequence has no emails yet
			$sequence->subscribe( [ $subscriber_id ], $restart );

			return $this->get_success_response(
				[
					'sequence_id'   => $sequence_id,
					'subscriber_id' => $subscriber_id,
					'enrolled_at'   => current_time( 'mysql' ),
					'restarted'     => $restart,
				],
				'Subscriber enrolled in sequence successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add subscriber to sequence: ' . $e->getMessage(), 'enrollment_failed' );
		}
	}

	/**
	 * Execute remove subscriber from sequence ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_subscriber_from_sequence( array $args ): array {
		try {
			$sequence_id   = intval( $args['sequence_id'] );
			$subscriber_id = intval( $args['subscriber_id'] );

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			// Verify sequence exists
			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Unenroll subscriber from sequence
			// Note: FluentCRM's unsubscribe() may return null/false even on success
			$sequence->unsubscribe( [ $subscriber_id ] );

			return $this->get_success_response(
				[
					'sequence_id'   => $sequence_id,
					'subscriber_id' => $subscriber_id,
					'unenrolled_at' => current_time( 'mysql' ),
				],
				'Subscriber removed from sequence successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove subscriber from sequence: ' . $e->getMessage(), 'unenrollment_failed' );
		}
	}

	/**
	 * Execute get sequence performance ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_sequence_performance( array $args ): array {
		try {
			$sequence_id = intval( $args['sequence_id'] );

			if ( $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );

			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Get sequence subscribers if SequenceTracker model exists
			$enrolled_count  = 0;
			$completed_count = 0;
			$active_count    = 0;

			if ( class_exists( '\FluentCampaign\App\Models\SequenceTracker' ) ) {
				$enrolled_count  = \FluentCampaign\App\Models\SequenceTracker::where( 'campaign_id', $sequence_id )->count();
				$completed_count = \FluentCampaign\App\Models\SequenceTracker::where( 'campaign_id', $sequence_id )
																				->where( 'status', 'completed' )
																				->count();
				$active_count    = \FluentCampaign\App\Models\SequenceTracker::where( 'campaign_id', $sequence_id )
																			->where( 'status', 'active' )
																			->count();
			}

			// Get email performance stats
			$email_stats = [];
			if ( $sequence->emails ) {
				foreach ( $sequence->emails as $email ) {
					$email_stats[] = [
						'email_id'      => $email->id,
						'email_subject' => $email->email_subject,
						'sent'          => $email->total_sent ?? 0,
						'opens'         => $email->total_opened ?? 0,
						'clicks'        => $email->total_clicked ?? 0,
					];
				}
			}

			return $this->get_success_response(
				[
					'sequence_id'     => $sequence_id,
					'sequence_title'  => $sequence->title,
					'enrolled_count'  => $enrolled_count,
					'active_count'    => $active_count,
					'completed_count' => $completed_count,
					'email_count'     => count( $email_stats ),
					'email_stats'     => $email_stats,
				],
				'Sequence performance retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get sequence performance: ' . $e->getMessage(), 'performance_failed' );
		}
	}

	/**
	 * Format sequence response with normalized description field
	 *
	 * FluentCRM stores empty descriptions as NULL in the database.
	 * This method normalizes NULL to empty string for consistent API responses.
	 *
	 * @param object $sequence FluentCRM Sequence model instance
	 * @return array Formatted sequence data
	 */
	private function format_sequence_response( $sequence ): array {
		return [
			'id'          => $sequence->id,
			'title'       => $sequence->title,
			'description' => $sequence->description ?? '',
			'status'      => $sequence->status,
			'settings'    => $sequence->settings,
			'created_at'  => $sequence->created_at,
			'updated_at'  => $sequence->updated_at ?? $sequence->created_at,
		];
	}

	/**
	 * Register add sequence email ability
	 */
	private function register_add_sequence_email(): void {
		wp_register_ability(
			'fluentcrm/add-sequence-email',
			[
				'label'               => 'Add Email to FluentCRM Sequence',
				'description'         => 'Add an email to a FluentCRM sequence',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'sequence_id', 'email_subject', 'email_body' ],
					'properties' => [
						'sequence_id'   => [
							'type'        => 'integer',
							'description' => 'Sequence ID to add email to',
						],
						'email_subject' => [
							'type'        => 'string',
							'description' => 'Email subject line',
						],
						'email_body'    => [
							'type'        => 'string',
							'description' => 'Email body HTML content',
						],
						'delay'         => [
							'type'        => 'integer',
							'description' => 'Delay in days before sending (default: 0)',
							'default'     => 0,
						],
						'delay_unit'    => [
							'type'        => 'string',
							'description' => 'Delay unit: days, hours (default: days)',
							'enum'        => [ 'days', 'hours' ],
							'default'     => 'days',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_add_sequence_email' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute add sequence email
	 */
	public function execute_add_sequence_email( array $args ): array {
		try {
			$sequence_id = absint( $args['sequence_id'] ?? 0 );
			$subject     = sanitize_text_field( $args['email_subject'] ?? '' );
			$email_body  = $args['email_body'] ?? '';
			$delay       = isset( $args['delay'] ) ? intval( $args['delay'] ) : 0;
			$delay_unit  = sanitize_text_field( $args['delay_unit'] ?? 'days' );

			if ( empty( $sequence_id ) || $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( empty( $subject ) ) {
				return $this->get_error_response( 'Email subject is required', 'subject_required' );
			}

			if ( ! isset( $args['email_body'] ) ) {
				return $this->get_error_response( 'Email body is required', 'email_body_required' );
			}

			if ( $delay < 0 ) {
				return $this->get_error_response( 'Delay cannot be negative', 'invalid_delay' );
			}

			if ( ! in_array( $delay_unit, [ 'days', 'hours' ], true ) ) {
				return $this->get_error_response( 'Invalid delay unit. Must be "days" or "hours"', 'invalid_delay_unit' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required for sequences', 'pro_required' );
			}

			// Verify sequence exists
			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			// Create sequence email with settings structure matching FluentCRM
			// Note: FluentCRM auto-calculates 'delay' field from settings['timings']
			// We must include default settings structure for FluentCRM compatibility
			$email_settings = [
				'action_triggers'  => [],
				'timings'          => [
					'delay'        => $delay,
					'delay_unit'   => $delay_unit,
					'is_anytime'   => 'yes',
					'sending_time' => [ '', '' ],
				],
				'template_config'  => [],
				'mailer_settings'  => [
					'from_name'      => '',
					'from_email'     => '',
					'reply_to_name'  => '',
					'reply_to_email' => '',
					'is_custom'      => 'no',
				],
			];

			$email = \FluentCampaign\App\Models\SequenceMail::create(
				[
					'parent_id'       => $sequence_id,
					'title'           => $subject,
					'email_subject'   => $subject,
					'email_body'      => $email_body,
					'design_template' => 'simple',
					'status'          => 'published',
					'settings'        => $email_settings,
				]
			);

			// Refresh to get auto-calculated delay value and ensure settings are loaded
			$email = \FluentCampaign\App\Models\SequenceMail::find( $email->id );

			return $this->get_success_response(
				[
					'email' => [
						'id'            => $email->id,
						'sequence_id'   => $sequence_id,
						'email_subject' => $email->email_subject,
						'email_body'    => $email->email_body,
						'delay'         => isset( $email->settings['timings']['delay'] ) ? (int) $email->settings['timings']['delay'] : $delay,
						'delay_unit'    => $email->settings['timings']['delay_unit'] ?? $delay_unit,
						'created_at'    => $email->created_at,
					],
				],
				'Email added to sequence successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add email to sequence: ' . $e->getMessage(), 'email_add_failed' );
		}
	}

	/**
	 * Register list sequence emails ability
	 */
	private function register_list_sequence_emails(): void {
		wp_register_ability(
			'fluentcrm/list-sequence-emails',
			[
				'label'               => 'List FluentCRM Sequence Emails',
				'description'         => 'List all emails in a FluentCRM sequence',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'sequence_id' ],
					'properties' => [
						'sequence_id' => [
							'type'        => 'integer',
							'description' => 'Sequence ID to list emails from',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_sequence_emails' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute list sequence emails
	 */
	public function execute_list_sequence_emails( array $args ): array {
		try {
			$sequence_id = absint( $args['sequence_id'] ?? 0 );

			if ( empty( $sequence_id ) || $sequence_id <= 0 ) {
				return $this->get_error_response( 'Invalid sequence ID', 'invalid_sequence_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			// Verify sequence exists
			$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
			if ( ! $sequence ) {
				return $this->get_error_response( 'Sequence not found', 'sequence_not_found' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\SequenceMail' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			$emails = \FluentCampaign\App\Models\SequenceMail::where( 'parent_id', $sequence_id )
				->orderBy( 'delay', 'ASC' )
				->get();

			$formatted_emails = [];
			foreach ( $emails as $email ) {
				$formatted_emails[] = [
					'id'            => $email->id,
					'sequence_id'   => $sequence_id,
					'title'         => $email->title,
					'email_subject' => $email->email_subject,
					'email_body'    => $email->email_body,
					'delay'         => $email->delay,
					'delay_unit'    => isset( $email->settings['timings']['delay_unit'] ) ? $email->settings['timings']['delay_unit'] : 'days',
					'status'        => $email->status,
					'created_at'    => $email->created_at,
				];
			}

			return $this->get_success_response(
				[
					'sequence_id' => $sequence_id,
					'emails'      => $formatted_emails,
					'total'       => count( $formatted_emails ),
				],
				'Sequence emails retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list sequence emails: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Register update sequence email ability
	 */
	private function register_update_sequence_email(): void {
		wp_register_ability(
			'fluentcrm/update-sequence-email',
			[
				'label'               => 'Update FluentCRM Sequence Email',
				'description'         => 'Update an email in a FluentCRM sequence',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'email_id' ],
					'properties' => [
						'email_id'      => [
							'type'        => 'integer',
							'description' => 'Email ID to update',
						],
						'email_subject' => [
							'type'        => 'string',
							'description' => 'Updated email subject',
						],
						'email_body'    => [
							'type'        => 'string',
							'description' => 'Updated email body HTML',
						],
						'delay'         => [
							'type'        => 'integer',
							'description' => 'Updated delay in days or hours',
						],
						'delay_unit'    => [
							'type'        => 'string',
							'description' => 'Delay unit: days, hours (default: days)',
							'enum'        => [ 'days', 'hours' ],
							'default'     => 'days',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_update_sequence_email' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute update sequence email
	 */
	public function execute_update_sequence_email( array $args ): array {
		try {
			$email_id = absint( $args['email_id'] ?? 0 );

			if ( empty( $email_id ) || $email_id <= 0 ) {
				return $this->get_error_response( 'Invalid email ID', 'invalid_email_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\SequenceMail' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			$email = \FluentCampaign\App\Models\SequenceMail::find( $email_id );
			if ( ! $email ) {
				return $this->get_error_response( 'Email not found', 'email_not_found' );
			}

			$update_data = [];
			if ( isset( $args['email_subject'] ) ) {
				$update_data['email_subject'] = sanitize_text_field( $args['email_subject'] );
			}
			if ( isset( $args['email_body'] ) ) {
				// Allow empty email body - use wp_kses_post only if not empty
				$update_data['email_body'] = empty( $args['email_body'] ) ? '' : wp_kses_post( $args['email_body'] );
			}
			if ( isset( $args['delay'] ) ) {
				$delay = intval( $args['delay'] );
				if ( $delay < 0 ) {
					return $this->get_error_response( 'Delay cannot be negative', 'invalid_delay' );
				}

				// FluentCRM stores delay in seconds
				$delay_unit = $args['delay_unit'] ?? 'days';
				if ( ! in_array( $delay_unit, [ 'days', 'hours' ], true ) ) {
					return $this->get_error_response( 'Invalid delay_unit. Must be "days" or "hours"', 'invalid_delay_unit' );
				}

				// Convert to seconds
				$delay_in_seconds     = ( $delay_unit === 'hours' ) ? ( $delay * HOUR_IN_SECONDS ) : ( $delay * DAY_IN_SECONDS );
				$update_data['delay'] = $delay_in_seconds;

				// Update settings with delay info
				$settings                = $email->settings ?? [];
				$settings['timings']     = [
					'delay'      => $delay,
					'delay_unit' => $delay_unit,
				];
				$update_data['settings'] = $settings;
			}

			// Allow updates with no changes - just return current email
			if ( empty( $update_data ) ) {
				$delay_unit = isset( $email->settings['timings']['delay_unit'] ) ? $email->settings['timings']['delay_unit'] : 'days';
				$delay      = isset( $email->settings['timings']['delay'] ) ? intval( $email->settings['timings']['delay'] ) : 0;

				return $this->get_success_response(
					[
						'email' => [
							'id'            => $email->id,
							'email_subject' => $email->email_subject,
							'email_body'    => $email->email_body,
							'delay'         => $delay,
							'delay_unit'    => $delay_unit,
						],
					],
					'No changes made'
				);
			}

			$email->update( $update_data );

			// Refresh to get updated values
			$email = \FluentCampaign\App\Models\SequenceMail::find( $email_id );

			// Convert delay back from seconds to user-specified unit
			$delay_unit = isset( $email->settings['timings']['delay_unit'] ) ? $email->settings['timings']['delay_unit'] : 'days';
			$delay      = isset( $email->settings['timings']['delay'] ) ? intval( $email->settings['timings']['delay'] ) : 0;

			return $this->get_success_response(
				[
					'email' => [
						'id'            => $email->id,
						'email_subject' => $email->email_subject,
						'email_body'    => $email->email_body,
						'delay'         => $delay,
						'delay_unit'    => $delay_unit,
					],
				],
				'Sequence email updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update sequence email: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Register delete sequence email ability
	 */
	private function register_delete_sequence_email(): void {
		wp_register_ability(
			'fluentcrm/delete-sequence-email',
			[
				'label'               => 'Delete FluentCRM Sequence Email',
				'description'         => 'Delete an email from a FluentCRM sequence',
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'email_id', 'confirm_delete' ],
					'properties' => [
						'email_id'       => [
							'type'        => 'integer',
							'description' => 'Email ID to delete',
						],
						'confirm_delete' => [
							'type'        => 'boolean',
							'description' => 'Confirmation required (must be true)',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_delete_sequence_email' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'sequences',
					'pro_feature' => true,
				],
			]
		);
	}

	/**
	 * Execute delete sequence email
	 */
	public function execute_delete_sequence_email( array $args ): array {
		try {
			$email_id       = absint( $args['email_id'] ?? 0 );
			$confirm_delete = (bool) ( $args['confirm_delete'] ?? false );

			if ( ! $confirm_delete ) {
				return $this->get_error_response( 'Confirmation required for deletion. Set confirm_delete to true.', 'confirmation_required' );
			}

			if ( empty( $email_id ) || $email_id <= 0 ) {
				return $this->get_error_response( 'Invalid email ID', 'invalid_email_id' );
			}

			if ( ! class_exists( '\FluentCampaign\App\Models\SequenceMail' ) ) {
				return $this->get_error_response( 'FluentCampaign Pro is required', 'pro_required' );
			}

			$email = \FluentCampaign\App\Models\SequenceMail::find( $email_id );
			if ( ! $email ) {
				return $this->get_error_response( 'Email not found', 'email_not_found' );
			}

			$sequence_id   = $email->parent_id;
			$email_subject = $email->email_subject;

			$email->delete();

			return $this->get_success_response(
				[
					'email_id'      => $email_id,
					'sequence_id'   => $sequence_id,
					'email_subject' => $email_subject,
					'deleted_at'    => current_time( 'mysql' ),
				],
				'Sequence email deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete sequence email: ' . $e->getMessage(), 'delete_failed' );
		}
	}
}
