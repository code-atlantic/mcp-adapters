<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Templates Abilities
 *
 * Registers WordPress abilities for FluentCRM email template management operations
 * using the WordPress Abilities API pattern.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 */
class Templates extends BaseAbility {

	/**
	 * Register all template-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_create_template();
		$this->register_list_templates();
		$this->register_get_template();
		$this->register_update_template();
		$this->register_delete_template();
		$this->register_duplicate_template();
		$this->register_apply_template_to_campaign();
	}

	/**
	 * Register create template ability
	 */
	private function register_create_template(): void {
		wp_register_ability(
			'fluentcrm/create-template',
			[
				'label'               => 'Create FluentCRM email template',
				'description'         => 'Create a new email template in FluentCRM',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'post_title'       => [
							'type'        => 'string',
							'description' => 'Template name',
						],
						'post_content'     => [
							'type'        => 'string',
							'description' => 'Email template HTML content',
						],
						'email_subject'    => [
							'type'        => 'string',
							'description' => 'Default email subject line',
						],
						'email_pre_header' => [
							'type'        => 'string',
							'description' => 'Email pre-header text (preview text)',
						],
						'template_config'  => [
							'type'        => 'object',
							'description' => 'Template configuration settings (JSON object)',
						],
					],
					'required'   => [ 'post_title', 'post_content' ],
				],
				'execute_callback'    => [ $this, 'execute_create_template' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register list templates ability
	 */
	private function register_list_templates(): void {
		wp_register_ability(
			'fluentcrm/list-templates',
			[
				'label'               => 'List FluentCRM email templates',
				'description'         => 'List all email templates with optional category filtering',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'search'   => [
							'type'        => 'string',
							'description' => 'Search term to filter templates',
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => 'Number of templates per page',
							'default'     => 15,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'page'     => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_templates' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register get template ability
	 */
	private function register_get_template(): void {
		wp_register_ability(
			'fluentcrm/get-template',
			[
				'label'               => 'Get FluentCRM email template details',
				'description'         => 'Get detailed information about a specific email template including HTML content',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Template ID',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_template' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register update template ability
	 */
	private function register_update_template(): void {
		wp_register_ability(
			'fluentcrm/update-template',
			[
				'label'               => 'Update FluentCRM email template',
				'description'         => 'Update an existing email template content and settings',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'template_id'      => [
							'type'        => 'integer',
							'description' => 'Template ID to update',
						],
						'post_title'       => [
							'type'        => 'string',
							'description' => 'Template name',
						],
						'post_content'     => [
							'type'        => 'string',
							'description' => 'Email template HTML content',
						],
						'email_subject'    => [
							'type'        => 'string',
							'description' => 'Default email subject line',
						],
						'email_pre_header' => [
							'type'        => 'string',
							'description' => 'Email pre-header text (preview text)',
						],
						'template_config'  => [
							'type'        => 'object',
							'description' => 'Template configuration settings (JSON object)',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_template' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register delete template ability
	 */
	private function register_delete_template(): void {
		wp_register_ability(
			'fluentcrm/delete-template',
			[
				'label'               => 'Delete FluentCRM email template',
				'description'         => 'Delete an email template from FluentCRM',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Template ID to delete',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_template' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register duplicate template ability
	 */
	private function register_duplicate_template(): void {
		wp_register_ability(
			'fluentcrm/duplicate-template',
			[
				'label'               => 'Duplicate FluentCRM email template',
				'description'         => 'Create a copy of an existing email template',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Template ID to duplicate',
						],
						'new_title'   => [
							'type'        => 'string',
							'description' => 'Title for the duplicated template (optional, defaults to "Copy of [original]")',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'execute_callback'    => [ $this, 'execute_duplicate_template' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Register apply template to campaign ability
	 */
	private function register_apply_template_to_campaign(): void {
		wp_register_ability(
			'fluentcrm/apply-template-to-campaign',
			[
				'label'               => 'Apply template to FluentCRM campaign',
				'description'         => 'Set a campaign to use a specific email template',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'campaign_id' => [
							'type'        => 'integer',
							'description' => 'Campaign ID',
						],
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Template ID to apply',
						],
					],
					'required'   => [ 'campaign_id', 'template_id' ],
				],
				'execute_callback'    => [ $this, 'execute_apply_template_to_campaign' ],
				'permission_callback' => [ $this, 'can_manage_campaigns' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'templates',
				],
			]
		);
	}

	/**
	 * Execute create template ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_template( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$post_title       = sanitize_text_field( $args['post_title'] );
			$post_content     = wp_kses_post( $args['post_content'] );
			$email_subject    = isset( $args['email_subject'] ) ? sanitize_text_field( $args['email_subject'] ) : '';
			$email_pre_header = isset( $args['email_pre_header'] ) ? sanitize_text_field( $args['email_pre_header'] ) : '';
			$template_config  = isset( $args['template_config'] ) ? $args['template_config'] : [];

			if ( empty( $post_title ) || empty( $post_content ) ) {
				return $this->get_error_response( 'Template title and content are required', 'missing_required_fields' );
			}

			// Create template
			$template = \FluentCrm\App\Models\Template::create(
				[
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_type'    => 'fc_template',
					'post_status'  => 'publish',
				]
			);

			if ( ! $template ) {
				return $this->get_error_response( 'Failed to create template', 'creation_failed' );
			}

			// Save email subject as post meta
			if ( ! empty( $email_subject ) ) {
				update_post_meta( $template->ID, '_email_subject', $email_subject );
			}

			// Save email pre-header as post meta (custom extension, not standard FluentCRM field)
			if ( ! empty( $email_pre_header ) ) {
				update_post_meta( $template->ID, '_email_pre_header', $email_pre_header );
			}

			// Save template config
			if ( ! empty( $template_config ) ) {
				update_post_meta( $template->ID, '_template_config', $template_config );
			}

			return $this->get_success_response(
				[
					'template_id' => $template->ID,
					'template'    => $this->format_template_response( $template ),
				],
				'Template created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create template: ' . $e->getMessage(), 'template_creation_failed' );
		}
	}

	/**
	 * Execute list templates ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_templates( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$search   = $args['search'] ?? '';
			$per_page = $args['per_page'] ?? 15;
			$page     = $args['page'] ?? 1;

			$query = \FluentCrm\App\Models\Template::where( 'post_type', 'fc_template' )
													->where( 'post_status', 'publish' );

			// Apply search filter
			if ( ! empty( $search ) ) {
				$query->where(
					function ( $q ) use ( $search ) {
						$q->where( 'post_title', 'LIKE', '%' . $search . '%' )
							->orWhere( 'post_content', 'LIKE', '%' . $search . '%' );
					}
				);
			}

			// Get total count for pagination
			$total = $query->count();

			// Apply pagination
			$offset    = ( $page - 1 ) * $per_page;
			$templates = $query->orderBy( 'created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$formatted_templates = [];
			foreach ( $templates as $template ) {
				$formatted_templates[] = $this->format_template_response( $template );
			}

			return $this->get_success_response(
				[
					'templates'  => $formatted_templates,
					'pagination' => [
						'total'        => $total,
						'per_page'     => $per_page,
						'current_page' => $page,
						'total_pages'  => ceil( $total / $per_page ),
					],
				],
				'Templates retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list templates: ' . $e->getMessage(), 'list_templates_failed' );
		}
	}

	/**
	 * Execute get template ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_template( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$template_id = intval( $args['template_id'] );

			if ( $template_id <= 0 ) {
				return $this->get_error_response( 'Invalid template ID', 'invalid_template_id' );
			}

			$template = \FluentCrm\App\Models\Template::find( $template_id );

			if ( ! $template || 'fc_template' !== $template->post_type ) {
				return $this->get_error_response( 'Template not found', 'template_not_found' );
			}

			return $this->get_success_response(
				[
					'template' => $this->format_template_response( $template, true ),
				],
				'Template retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get template: ' . $e->getMessage(), 'get_template_failed' );
		}
	}

	/**
	 * Execute update template ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_template( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$template_id = intval( $args['template_id'] );

			if ( $template_id <= 0 ) {
				return $this->get_error_response( 'Invalid template ID', 'invalid_template_id' );
			}

			$template = \FluentCrm\App\Models\Template::find( $template_id );

			if ( ! $template || 'fc_template' !== $template->post_type ) {
				return $this->get_error_response( 'Template not found', 'template_not_found' );
			}

			// Update template fields if provided
			$update_data = [];

			if ( isset( $args['post_title'] ) ) {
				$update_data['post_title'] = sanitize_text_field( $args['post_title'] );
			}

			if ( isset( $args['post_content'] ) ) {
				$update_data['post_content'] = wp_kses_post( $args['post_content'] );
			}

			if ( ! empty( $update_data ) ) {
				$template->update( $update_data );
			}

			// Update meta fields
			if ( isset( $args['email_subject'] ) ) {
				update_post_meta( $template_id, '_email_subject', sanitize_text_field( $args['email_subject'] ) );
			}

			if ( isset( $args['email_pre_header'] ) ) {
				update_post_meta( $template_id, '_email_pre_header', sanitize_text_field( $args['email_pre_header'] ) );
			}

			if ( isset( $args['template_config'] ) ) {
				update_post_meta( $template_id, '_template_config', $args['template_config'] );
			}

			// Refresh template data
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			return $this->get_success_response(
				[
					'template' => $this->format_template_response( $template, true ),
				],
				'Template updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update template: ' . $e->getMessage(), 'update_template_failed' );
		}
	}

	/**
	 * Execute delete template ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_template( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$template_id = intval( $args['template_id'] );

			if ( $template_id <= 0 ) {
				return $this->get_error_response( 'Invalid template ID', 'invalid_template_id' );
			}

			$template = \FluentCrm\App\Models\Template::find( $template_id );

			if ( ! $template || 'fc_template' !== $template->post_type ) {
				return $this->get_error_response( 'Template not found', 'template_not_found' );
			}

			// Delete template
			$deleted = $template->delete();

			if ( ! $deleted ) {
				return $this->get_error_response( 'Failed to delete template', 'deletion_failed' );
			}

			return $this->get_success_response(
				[
					'template_id' => $template_id,
					'deleted'     => true,
				],
				'Template deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete template: ' . $e->getMessage(), 'delete_template_failed' );
		}
	}

	/**
	 * Execute duplicate template ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_duplicate_template( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			$template_id = intval( $args['template_id'] );

			if ( $template_id <= 0 ) {
				return $this->get_error_response( 'Invalid template ID', 'invalid_template_id' );
			}

			$original_template = \FluentCrm\App\Models\Template::find( $template_id );

			if ( ! $original_template || 'fc_template' !== $original_template->post_type ) {
				return $this->get_error_response( 'Template not found', 'template_not_found' );
			}

			// Generate new title
			$new_title = isset( $args['new_title'] ) && ! empty( $args['new_title'] )
				? sanitize_text_field( $args['new_title'] )
				: 'Copy of ' . $original_template->post_title;

			// Create duplicate
			$duplicate = \FluentCrm\App\Models\Template::create(
				[
					'post_title'   => $new_title,
					'post_content' => $original_template->post_content,
					'post_type'    => 'fc_template',
					'post_status'  => 'publish',
				]
			);

			if ( ! $duplicate ) {
				return $this->get_error_response( 'Failed to duplicate template', 'duplication_failed' );
			}

			// Copy meta fields
			$email_subject    = get_post_meta( $template_id, '_email_subject', true );
			$email_pre_header = get_post_meta( $template_id, '_email_pre_header', true );
			$template_config  = get_post_meta( $template_id, '_template_config', true );

			if ( ! empty( $email_subject ) ) {
				update_post_meta( $duplicate->ID, '_email_subject', $email_subject );
			}

			if ( ! empty( $email_pre_header ) ) {
				update_post_meta( $duplicate->ID, '_email_pre_header', $email_pre_header );
			}

			if ( ! empty( $template_config ) ) {
				update_post_meta( $duplicate->ID, '_template_config', $template_config );
			}

			return $this->get_success_response(
				[
					'original_template_id' => $template_id,
					'new_template_id'      => $duplicate->ID,
					'template'             => $this->format_template_response( $duplicate ),
				],
				'Template duplicated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to duplicate template: ' . $e->getMessage(), 'duplicate_template_failed' );
		}
	}

	/**
	 * Execute apply template to campaign ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_apply_template_to_campaign( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
				return $this->get_error_response( 'FluentCRM Template model not available', 'model_not_found' );
			}

			if ( ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
				return $this->get_error_response( 'FluentCRM Campaign model not available', 'model_not_found' );
			}

			$campaign_id = intval( $args['campaign_id'] );
			$template_id = intval( $args['template_id'] );

			if ( $campaign_id <= 0 || $template_id <= 0 ) {
				return $this->get_error_response( 'Invalid campaign ID or template ID', 'invalid_ids' );
			}

			// Verify campaign exists
			$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_id );

			if ( ! $campaign ) {
				return $this->get_error_response( 'Campaign not found', 'campaign_not_found' );
			}

			// Verify template exists
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			if ( ! $template || 'fc_template' !== $template->post_type ) {
				return $this->get_error_response( 'Template not found', 'template_not_found' );
			}

			// Apply template to campaign
			$campaign->update(
				[
					'email_body' => $template->post_content,
				]
			);

			// Update campaign subject if template has one
			$email_subject = get_post_meta( $template_id, '_email_subject', true );
			if ( ! empty( $email_subject ) ) {
				$campaign->update(
					[
						'email_subject' => $email_subject,
					]
				);
			}

			// Update campaign pre-header if template has one (custom extension field)
			$email_pre_header = get_post_meta( $template_id, '_email_pre_header', true );
			if ( ! empty( $email_pre_header ) ) {
				$campaign->update(
					[
						'email_pre_header' => $email_pre_header,
					]
				);
			}

			return $this->get_success_response(
				[
					'campaign_id' => $campaign_id,
					'template_id' => $template_id,
					'applied'     => true,
				],
				'Template applied to campaign successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to apply template to campaign: ' . $e->getMessage(), 'apply_template_failed' );
		}
	}

	/**
	 * Format template response
	 *
	 * @param object $template Template object
	 * @param bool   $include_content Whether to include full HTML content
	 * @return array Formatted template data
	 */
	private function format_template_response( $template, bool $include_content = false ): array {
		$formatted = [
			'id'         => $template->ID,
			'title'      => $template->post_title,
			'created_at' => $template->created_at,
			'updated_at' => $template->updated_at,
		];

		// Include meta fields
		$email_subject    = get_post_meta( $template->ID, '_email_subject', true );
		$email_pre_header = get_post_meta( $template->ID, '_email_pre_header', true );
		$template_config  = get_post_meta( $template->ID, '_template_config', true );

		if ( ! empty( $email_subject ) ) {
			$formatted['email_subject'] = $email_subject;
		}

		if ( ! empty( $email_pre_header ) ) {
			$formatted['email_pre_header'] = $email_pre_header;
		}

		if ( ! empty( $template_config ) ) {
			$formatted['template_config'] = $template_config;
		}

		// Include full content if requested (for get-template, but not for list-templates)
		if ( $include_content ) {
			$formatted['content'] = $template->post_content;
		} else {
			// Include excerpt for list view
			$formatted['excerpt'] = wp_trim_words( wp_strip_all_tags( $template->post_content ), 30 );
		}

		return $formatted;
	}
}
