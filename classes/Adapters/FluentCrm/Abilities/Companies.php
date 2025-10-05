<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentCrm\Abilities;

use MCP\Adapters\Adapters\FluentCrm\BaseAbility;

/**
 * FluentCRM Company Abilities
 *
 * Registers WordPress abilities for FluentCRM company management operations.
 * Companies feature is optional and must be enabled in FluentCRM settings.
 *
 * @package MCP\Adapters\Adapters\FluentCrm\Abilities
 * @since 1.0.0
 */
class Companies extends BaseAbility {

	/**
	 * Register all company-related abilities
	 *
	 * Only registers abilities if the companies feature is enabled in FluentCRM.
	 *
	 * @return void
	 */
	protected function register_abilities(): void {
		// Check if companies feature is enabled
		if ( ! $this->is_companies_enabled() ) {
			return;
		}

		$this->register_create_company();
		$this->register_list_companies();
		$this->register_get_company();
		$this->register_update_company();
		$this->register_delete_company();
		$this->register_add_company_contacts();
		$this->register_remove_company_contacts();
		$this->register_list_company_contacts();
		$this->register_set_primary_contact();
	}

	/**
	 * Check if companies feature is enabled in FluentCRM
	 *
	 * @return bool True if companies feature is enabled
	 */
	private function is_companies_enabled(): bool {
		if ( ! class_exists( '\FluentCrm\App\Services\Helper' ) ) {
			return false;
		}

		return \FluentCrm\App\Services\Helper::isCompanyEnabled();
	}

	/**
	 * Register create company ability
	 *
	 * @return void
	 */
	private function register_create_company(): void {
		wp_register_ability(
			'fluentcrm/create-company',
			[
				'label'               => 'Create FluentCRM company',
				'description'         => 'Create a new company entity in FluentCRM',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'name'           => [
							'type'        => 'string',
							'description' => 'Company name (required)',
						],
						'email'          => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Company email address',
						],
						'phone'          => [
							'type'        => 'string',
							'description' => 'Company phone number',
						],
						'address_line_1' => [
							'type'        => 'string',
							'description' => 'Company address line 1',
						],
						'address_line_2' => [
							'type'        => 'string',
							'description' => 'Company address line 2',
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
							'description' => 'Postal or ZIP code',
						],
						'country'        => [
							'type'        => 'string',
							'description' => 'Country code (e.g., US, GB, CA)',
						],
						'website'        => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => 'Company website URL',
						],
						'industry'       => [
							'type'        => 'string',
							'description' => 'Company industry or sector',
						],
						'description'    => [
							'type'        => 'string',
							'description' => 'Company description or notes',
						],
					],
					'required'   => [ 'name' ],
				],
				'execute_callback'    => [ $this, 'execute_create_company' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register list companies ability
	 *
	 * @return void
	 */
	private function register_list_companies(): void {
		wp_register_ability(
			'fluentcrm/list-companies',
			[
				'label'               => 'List FluentCRM companies',
				'description'         => 'List all companies with search and pagination support',
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
							'description' => 'Number of companies per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Search companies by name or email',
						],
					],
				],
				'execute_callback'    => [ $this, 'execute_list_companies' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register get company ability
	 *
	 * @return void
	 */
	private function register_get_company(): void {
		wp_register_ability(
			'fluentcrm/get-company',
			[
				'label'               => 'Get FluentCRM company',
				'description'         => 'Get detailed information about a specific company including associated contacts',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id' => [
							'type'        => 'integer',
							'description' => 'Company ID to retrieve',
						],
					],
					'required'   => [ 'company_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_company' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register update company ability
	 *
	 * @return void
	 */
	private function register_update_company(): void {
		wp_register_ability(
			'fluentcrm/update-company',
			[
				'label'               => 'Update FluentCRM company',
				'description'         => 'Update company properties and information',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'     => [
							'type'        => 'integer',
							'description' => 'Company ID to update (required)',
						],
						'name'           => [
							'type'        => 'string',
							'description' => 'Company name',
						],
						'email'          => [
							'type'        => 'string',
							'format'      => 'email',
							'description' => 'Company email address',
						],
						'phone'          => [
							'type'        => 'string',
							'description' => 'Company phone number',
						],
						'address_line_1' => [
							'type'        => 'string',
							'description' => 'Company address line 1',
						],
						'address_line_2' => [
							'type'        => 'string',
							'description' => 'Company address line 2',
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
							'description' => 'Postal or ZIP code',
						],
						'country'        => [
							'type'        => 'string',
							'description' => 'Country code (e.g., US, GB, CA)',
						],
						'website'        => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => 'Company website URL',
						],
						'industry'       => [
							'type'        => 'string',
							'description' => 'Company industry or sector',
						],
						'description'    => [
							'type'        => 'string',
							'description' => 'Company description or notes',
						],
					],
					'required'   => [ 'company_id' ],
				],
				'execute_callback'    => [ $this, 'execute_update_company' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register delete company ability
	 *
	 * @return void
	 */
	private function register_delete_company(): void {
		wp_register_ability(
			'fluentcrm/delete-company',
			[
				'label'               => 'Delete FluentCRM company',
				'description'         => 'Delete a company entity from FluentCRM',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id' => [
							'type'        => 'integer',
							'description' => 'Company ID to delete',
						],
					],
					'required'   => [ 'company_id' ],
				],
				'execute_callback'    => [ $this, 'execute_delete_company' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register add company contacts ability
	 *
	 * Attach one or more contacts to a company using many-to-many relationship.
	 * The first contact attached will be set as the primary contact if none exists.
	 *
	 * @return void
	 */
	private function register_add_company_contacts(): void {
		wp_register_ability(
			'fluentcrm/add-company-contacts',
			[
				'label'               => 'Add contacts to FluentCRM company',
				'description'         => 'Attach one or more contacts to a company using many-to-many relationship. The first contact will be set as primary if none exists.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'     => [
							'type'        => 'integer',
							'description' => 'Company ID to attach contacts to',
						],
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs to attach',
							'items'       => [
								'type' => 'integer',
							],
							'minItems'    => 1,
						],
					],
					'required'   => [ 'company_id', 'subscriber_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_add_company_contacts' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register remove company contacts ability
	 *
	 * Detach contacts from a company. If removing the primary contact, the next
	 * available contact will be automatically promoted to primary.
	 *
	 * @return void
	 */
	private function register_remove_company_contacts(): void {
		wp_register_ability(
			'fluentcrm/remove-company-contacts',
			[
				'label'               => 'Remove contacts from FluentCRM company',
				'description'         => 'Detach contacts from a company. If the primary contact is removed, another contact will be promoted to primary automatically.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'     => [
							'type'        => 'integer',
							'description' => 'Company ID to remove contacts from',
						],
						'subscriber_ids' => [
							'type'        => 'array',
							'description' => 'Array of subscriber IDs to detach',
							'items'       => [
								'type' => 'integer',
							],
							'minItems'    => 1,
						],
					],
					'required'   => [ 'company_id', 'subscriber_ids' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_company_contacts' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register list company contacts ability
	 *
	 * @return void
	 */
	private function register_list_company_contacts(): void {
		wp_register_ability(
			'fluentcrm/list-company-contacts',
			[
				'label'               => 'List FluentCRM company contacts',
				'description'         => 'Retrieve all contacts associated with a company, with pagination support. Returns contacts from the many-to-many relationship.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id' => [
							'type'        => 'integer',
							'description' => 'Company ID',
						],
						'page'       => [
							'type'        => 'integer',
							'description' => 'Page number for pagination',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'   => [
							'type'        => 'integer',
							'description' => 'Number of contacts per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
					'required'   => [ 'company_id' ],
				],
				'execute_callback'    => [ $this, 'execute_list_company_contacts' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register set primary contact ability
	 *
	 * @return void
	 */
	private function register_set_primary_contact(): void {
		wp_register_ability(
			'fluentcrm/set-primary-contact',
			[
				'label'               => 'Set primary contact for FluentCRM company',
				'description'         => 'Designate a specific contact as the primary contact for a company. The contact must already be associated with the company.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'    => [
							'type'        => 'integer',
							'description' => 'Company ID',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to set as primary contact',
						],
					],
					'required'   => [ 'company_id', 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_set_primary_contact' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Execute create company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_create_company( array $args ): array {
		try {
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$name = sanitize_text_field( $args['name'] );

			if ( empty( $name ) ) {
				return $this->get_error_response( 'Company name is required', 'name_required' );
			}

			$company_data = [
				'name' => $name,
			];

			// Add optional fields
			if ( ! empty( $args['email'] ) ) {
				$company_data['email'] = sanitize_email( $args['email'] );
			}

			if ( ! empty( $args['phone'] ) ) {
				$company_data['phone'] = sanitize_text_field( $args['phone'] );
			}

			if ( ! empty( $args['address_line_1'] ) ) {
				$company_data['address_line_1'] = sanitize_text_field( $args['address_line_1'] );
			}

			if ( ! empty( $args['address_line_2'] ) ) {
				$company_data['address_line_2'] = sanitize_text_field( $args['address_line_2'] );
			}

			if ( ! empty( $args['city'] ) ) {
				$company_data['city'] = sanitize_text_field( $args['city'] );
			}

			if ( ! empty( $args['state'] ) ) {
				$company_data['state'] = sanitize_text_field( $args['state'] );
			}

			if ( ! empty( $args['postal_code'] ) ) {
				$company_data['postal_code'] = sanitize_text_field( $args['postal_code'] );
			}

			if ( ! empty( $args['country'] ) ) {
				$company_data['country'] = sanitize_text_field( $args['country'] );
			}

			if ( ! empty( $args['website'] ) ) {
				// Validate URL format before sanitizing
				if ( ! filter_var( $args['website'], FILTER_VALIDATE_URL ) ) {
					return $this->get_error_response( 'Invalid website URL format', 'invalid_website' );
				}
				$company_data['website'] = esc_url_raw( $args['website'] );
			}

			if ( ! empty( $args['industry'] ) ) {
				$company_data['industry'] = sanitize_text_field( $args['industry'] );
			}

			if ( ! empty( $args['description'] ) ) {
				$company_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			$company = FluentCrmApi( 'companies' )->createOrUpdate( $company_data );

			return $this->get_success_response(
				[
					'company' => [
						'id'             => $company->id,
						'name'           => $company->name,
						'email'          => $company->email,
						'phone'          => $company->phone,
						'address_line_1' => $company->address_line_1,
						'address_line_2' => $company->address_line_2,
						'city'           => $company->city,
						'state'          => $company->state,
						'postal_code'    => $company->postal_code,
						'country'        => $company->country,
						'website'        => $company->website,
						'industry'       => $company->industry,
						'description'    => $company->description,
						'created_at'     => $company->created_at,
					],
				],
				'Company created successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to create company: ' . $e->getMessage(), 'create_failed' );
		}
	}

	/**
	 * Execute list companies ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_companies( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';

			$query = \FluentCrm\App\Models\Company::query();

			// Add search if provided
			if ( ! empty( $search ) ) {
				$query->where(
					function ( $q ) use ( $search ) {
						$q->where( 'name', 'like', '%' . $search . '%' )
							->orWhere( 'email', 'like', '%' . $search . '%' );
					}
				);
			}

			// Get total count before pagination
			$total = $query->count();

			// Get companies with pagination
			$offset    = ( $page - 1 ) * $per_page;
			$companies = $query->orderBy( 'name', 'ASC' )
								->offset( $offset )
								->limit( $per_page )
								->get();

			$result = [];
			foreach ( $companies as $company ) {
				$result[] = [
					'id'         => $company->id,
					'name'       => $company->name,
					'email'      => $company->email,
					'phone'      => $company->phone,
					'city'       => $company->city,
					'state'      => $company->state,
					'country'    => $company->country,
					'website'    => $company->website,
					'industry'   => $company->industry,
					'created_at' => $company->created_at,
					'updated_at' => $company->updated_at,
				];
			}

			return $this->get_success_response(
				[
					'companies'    => $result,
					'total'        => $total,
					'page'         => $page,
					'per_page'     => $per_page,
					'total_pages'  => ceil( $total / $per_page ),
					'search_query' => $search,
				],
				'Companies retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list companies: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute get company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_company( array $args ): array {
		try {
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$company_id = intval( $args['company_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			$company = FluentCrmApi( 'companies' )->getCompany( $company_id, [ 'subscribers' ] );

			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			$subscribers = [];
			if ( $company->subscribers ) {
				foreach ( $company->subscribers as $subscriber ) {
					$subscribers[] = [
						'id'         => $subscriber->id,
						'email'      => $subscriber->email,
						'first_name' => $subscriber->first_name,
						'last_name'  => $subscriber->last_name,
						'status'     => $subscriber->status,
					];
				}
			}

			return $this->get_success_response(
				[
					'company' => [
						'id'                => $company->id,
						'name'              => $company->name,
						'email'             => $company->email,
						'phone'             => $company->phone,
						'address_line_1'    => $company->address_line_1,
						'address_line_2'    => $company->address_line_2,
						'city'              => $company->city,
						'state'             => $company->state,
						'postal_code'       => $company->postal_code,
						'country'           => $company->country,
						'website'           => $company->website,
						'industry'          => $company->industry,
						'description'       => $company->description,
						'created_at'        => $company->created_at,
						'updated_at'        => $company->updated_at,
						'subscribers'       => $subscribers,
						'subscribers_count' => count( $subscribers ),
					],
				],
				'Company retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get company: ' . $e->getMessage(), 'get_failed' );
		}
	}

	/**
	 * Execute update company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_company( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$company_id = intval( $args['company_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			// Verify company exists first
			$company = \FluentCrm\App\Models\Company::find( $company_id );

			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			$update_data = [];

			if ( isset( $args['name'] ) ) {
				$update_data['name'] = sanitize_text_field( $args['name'] );
			}

			if ( isset( $args['email'] ) ) {
				$update_data['email'] = sanitize_email( $args['email'] );
			}

			if ( isset( $args['phone'] ) ) {
				$update_data['phone'] = sanitize_text_field( $args['phone'] );
			}

			if ( isset( $args['address_line_1'] ) ) {
				$update_data['address_line_1'] = sanitize_text_field( $args['address_line_1'] );
			}

			if ( isset( $args['address_line_2'] ) ) {
				$update_data['address_line_2'] = sanitize_text_field( $args['address_line_2'] );
			}

			if ( isset( $args['city'] ) ) {
				$update_data['city'] = sanitize_text_field( $args['city'] );
			}

			if ( isset( $args['state'] ) ) {
				$update_data['state'] = sanitize_text_field( $args['state'] );
			}

			if ( isset( $args['postal_code'] ) ) {
				$update_data['postal_code'] = sanitize_text_field( $args['postal_code'] );
			}

			if ( isset( $args['country'] ) ) {
				$update_data['country'] = sanitize_text_field( $args['country'] );
			}

			if ( isset( $args['website'] ) ) {
				// Validate URL format before sanitizing (allow empty to clear field)
				if ( ! empty( $args['website'] ) && ! filter_var( $args['website'], FILTER_VALIDATE_URL ) ) {
					return $this->get_error_response( 'Invalid website URL format', 'invalid_website' );
				}
				$update_data['website'] = esc_url_raw( $args['website'] );
			}

			if ( isset( $args['industry'] ) ) {
				$update_data['industry'] = sanitize_text_field( $args['industry'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			// Update the company
			if ( ! empty( $update_data ) ) {
				$company->fill( $update_data );
				$company->save();
			}

			return $this->get_success_response(
				[
					'company' => [
						'id'             => $company->id,
						'name'           => $company->name,
						'email'          => $company->email,
						'phone'          => $company->phone,
						'address_line_1' => $company->address_line_1,
						'address_line_2' => $company->address_line_2,
						'city'           => $company->city,
						'state'          => $company->state,
						'postal_code'    => $company->postal_code,
						'country'        => $company->country,
						'website'        => $company->website,
						'industry'       => $company->industry,
						'description'    => $company->description,
						'updated_at'     => $company->updated_at,
					],
				],
				'Company updated successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update company: ' . $e->getMessage(), 'update_failed' );
		}
	}

	/**
	 * Execute delete company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_delete_company( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$company_id = intval( $args['company_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			$company = \FluentCrm\App\Models\Company::find( $company_id );

			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			$company_name = $company->name;
			$company->delete();

			return $this->get_success_response(
				[
					'company_id'   => $company_id,
					'company_name' => $company_name,
					'deleted_at'   => current_time( 'mysql' ),
				],
				'Company deleted successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to delete company: ' . $e->getMessage(), 'delete_failed' );
		}
	}

	/**
	 * Execute add company contacts ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_company_contacts( array $args ): array {
		try {
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$company_id     = intval( $args['company_id'] );
			$subscriber_ids = array_map( 'intval', $args['subscriber_ids'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			if ( empty( $subscriber_ids ) ) {
				return $this->get_error_response( 'At least one subscriber ID is required', 'empty_subscriber_ids' );
			}

			// Verify company exists
			$company = \FluentCrm\App\Models\Company::find( $company_id );
			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			// Use FluentCRM API to attach contacts
			$result = FluentCrmApi( 'companies' )->attachContactsByIds( $subscriber_ids, [ $company_id ] );

			if ( ! $result ) {
				return $this->get_error_response( 'Failed to attach contacts - invalid data or contacts not found', 'attach_failed' );
			}

			// Reload company with subscribers
			$company = \FluentCrm\App\Models\Company::with( 'subscribers' )->find( $company_id );

			return $this->get_success_response(
				[
					'company' => $company->toArray(),
				],
				'Contacts attached successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add contacts: ' . $e->getMessage(), 'attach_failed' );
		}
	}

	/**
	 * Execute remove company contacts ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_company_contacts( array $args ): array {
		try {
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$company_id     = intval( $args['company_id'] );
			$subscriber_ids = array_map( 'intval', $args['subscriber_ids'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			if ( empty( $subscriber_ids ) ) {
				return $this->get_error_response( 'At least one subscriber ID is required', 'empty_subscriber_ids' );
			}

			// Verify company exists
			$company = \FluentCrm\App\Models\Company::find( $company_id );
			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			// Use FluentCRM API to detach contacts
			$result = FluentCrmApi( 'companies' )->detachContactsByIds( $subscriber_ids, [ $company_id ] );

			if ( ! $result ) {
				return $this->get_error_response( 'Failed to detach contacts - invalid data or contacts not found', 'detach_failed' );
			}

			// Reload company with subscribers
			$company = \FluentCrm\App\Models\Company::with( 'subscribers' )->find( $company_id );

			return $this->get_success_response(
				[
					'company' => $company->toArray(),
				],
				'Contacts detached successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove contacts: ' . $e->getMessage(), 'detach_failed' );
		}
	}

	/**
	 * Execute list company contacts ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_list_company_contacts( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$company_id = intval( $args['company_id'] );
			$page       = $args['page'] ?? 1;
			$per_page   = $args['per_page'] ?? 20;

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			$company = \FluentCrm\App\Models\Company::find( $company_id );
			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			// Get contacts via many-to-many relationship
			$query = $company->subscribers();

			// Get total count
			$total = $query->count();

			// Get contacts with pagination
			$offset   = ( $page - 1 ) * $per_page;
			$contacts = $query->orderBy( 'fc_subscribers.created_at', 'DESC' )
							->offset( $offset )
							->limit( $per_page )
							->get();

			$result = [];
			foreach ( $contacts as $contact ) {
				$result[] = [
					'id'           => $contact->id,
					'email'        => $contact->email,
					'first_name'   => $contact->first_name,
					'last_name'    => $contact->last_name,
					'full_name'    => $contact->full_name,
					'status'       => $contact->status,
					'is_primary'   => ( (int) $company->owner_id === (int) $contact->id ),
					'created_at'   => $contact->created_at,
					'pivot_status' => $contact->pivot->status ?? null,
				];
			}

			return $this->get_success_response(
				[
					'company_id'      => $company_id,
					'company_name'    => $company->name,
					'primary_contact' => $company->owner_id,
					'contacts'        => $result,
					'total'           => $total,
					'page'            => $page,
					'per_page'        => $per_page,
					'total_pages'     => ceil( $total / $per_page ),
				],
				'Company contacts retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to list contacts: ' . $e->getMessage(), 'list_failed' );
		}
	}

	/**
	 * Execute set primary contact ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_set_primary_contact( array $args ): array {
		try {
			if ( ! class_exists( '\FluentCrm\App\Models\Company' ) ) {
				return $this->get_error_response( 'Company model not available', 'model_unavailable' );
			}

			$company_id    = intval( $args['company_id'] );
			$subscriber_id = intval( $args['subscriber_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			if ( $subscriber_id <= 0 ) {
				return $this->get_error_response( 'Invalid subscriber ID', 'invalid_subscriber_id' );
			}

			// Verify company exists
			$company = \FluentCrm\App\Models\Company::find( $company_id );
			if ( ! $company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			// Verify subscriber exists
			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( ! $subscriber ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Verify subscriber is associated with the company
			$is_associated = $company->subscribers()->where( 'fc_subscribers.id', $subscriber_id )->exists();
			if ( ! $is_associated ) {
				return $this->get_error_response( 'Subscriber is not associated with this company', 'not_associated' );
			}

			// Set as primary contact (owner_id)
			$company->owner_id = $subscriber_id;
			$company->save();

			// Reload company with relationships
			$company = \FluentCrm\App\Models\Company::with( [ 'owner', 'subscribers' ] )->find( $company_id );

			return $this->get_success_response(
				[
					'company' => $company->toArray(),
				],
				'Primary contact set successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to set primary contact: ' . $e->getMessage(), 'set_primary_failed' );
		}
	}
}
