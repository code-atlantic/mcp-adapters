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
		$this->register_add_subscriber_to_company();
		$this->register_remove_subscriber_from_company();
		$this->register_get_company_subscribers();
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
	 * Register add subscriber to company ability
	 *
	 * @return void
	 */
	private function register_add_subscriber_to_company(): void {
		wp_register_ability(
			'fluentcrm/add-subscriber-to-company',
			[
				'label'               => 'Add subscriber to FluentCRM company',
				'description'         => 'Link a contact to a company',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'    => [
							'type'        => 'integer',
							'description' => 'Company ID',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to link',
						],
					],
					'required'   => [ 'company_id', 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_add_subscriber_to_company' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register remove subscriber from company ability
	 *
	 * @return void
	 */
	private function register_remove_subscriber_from_company(): void {
		wp_register_ability(
			'fluentcrm/remove-subscriber-from-company',
			[
				'label'               => 'Remove subscriber from FluentCRM company',
				'description'         => 'Unlink a contact from a company',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id'    => [
							'type'        => 'integer',
							'description' => 'Company ID',
						],
						'subscriber_id' => [
							'type'        => 'integer',
							'description' => 'Subscriber ID to unlink',
						],
					],
					'required'   => [ 'company_id', 'subscriber_id' ],
				],
				'execute_callback'    => [ $this, 'execute_remove_subscriber_from_company' ],
				'permission_callback' => [ $this, 'can_manage_fluentcrm' ],
				'meta'                => [
					'category'    => 'fluentcrm',
					'subcategory' => 'companies',
				],
			]
		);
	}

	/**
	 * Register get company subscribers ability
	 *
	 * @return void
	 */
	private function register_get_company_subscribers(): void {
		wp_register_ability(
			'fluentcrm/get-company-subscribers',
			[
				'label'               => 'Get FluentCRM company subscribers',
				'description'         => 'List all contacts associated with a company',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'company_id' => [
							'type'        => 'integer',
							'description' => 'Company ID',
						],
						'page'       => [
							'type'        => 'integer',
							'description' => 'Page number',
							'default'     => 1,
							'minimum'     => 1,
						],
						'per_page'   => [
							'type'        => 'integer',
							'description' => 'Number of subscribers per page',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
					],
					'required'   => [ 'company_id' ],
				],
				'execute_callback'    => [ $this, 'execute_get_company_subscribers' ],
				'permission_callback' => [ $this, 'can_view_contacts' ],
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
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;
			$search   = $args['search'] ?? '';

			$query = FluentCrmApi( 'companies' )->getInstance();

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
			if ( ! function_exists( 'FluentCrmApi' ) ) {
				return $this->get_error_response( 'FluentCRM API not available', 'api_unavailable' );
			}

			$company_id = intval( $args['company_id'] );

			if ( $company_id <= 0 ) {
				return $this->get_error_response( 'Invalid company ID', 'invalid_company_id' );
			}

			// Verify company exists first
			$existing_company = FluentCrmApi( 'companies' )->find( $company_id );

			if ( ! $existing_company ) {
				return $this->get_error_response( 'Company not found', 'company_not_found' );
			}

			$update_data = [ 'id' => $company_id ];

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
				$update_data['website'] = esc_url_raw( $args['website'] );
			}

			if ( isset( $args['industry'] ) ) {
				$update_data['industry'] = sanitize_text_field( $args['industry'] );
			}

			if ( isset( $args['description'] ) ) {
				$update_data['description'] = sanitize_textarea_field( $args['description'] );
			}

			$company = FluentCrmApi( 'companies' )->createOrUpdate( $update_data );

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
	 * Execute add subscriber to company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_subscriber_to_company( array $args ): array {
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
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			// Check if already linked
			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );
			if ( $subscriber && $subscriber->company_id === $company_id ) {
				return $this->get_success_response(
					[
						'company_id'    => $company_id,
						'subscriber_id' => $subscriber_id,
						'action'        => 'already_linked',
					],
					'Subscriber is already linked to this company'
				);
			}

			// Link subscriber to company
			$subscriber->company_id = $company_id;
			$subscriber->save();

			return $this->get_success_response(
				[
					'company_id'       => $company_id,
					'company_name'     => $company->name,
					'subscriber_id'    => $subscriber_id,
					'subscriber_email' => $subscriber->email,
					'action'           => 'linked',
				],
				'Subscriber linked to company successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add subscriber to company: ' . $e->getMessage(), 'link_failed' );
		}
	}

	/**
	 * Execute remove subscriber from company ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_subscriber_from_company( array $args ): array {
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

			// Verify subscriber exists
			if ( ! $this->subscriber_exists( $subscriber_id ) ) {
				return $this->get_error_response( 'Subscriber not found', 'subscriber_not_found' );
			}

			$subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

			// Check if subscriber is linked to this company
			if ( ! $subscriber || $subscriber->company_id !== $company_id ) {
				return $this->get_success_response(
					[
						'company_id'    => $company_id,
						'subscriber_id' => $subscriber_id,
						'action'        => 'not_linked',
					],
					'Subscriber is not linked to this company'
				);
			}

			// Unlink subscriber from company
			$subscriber->company_id = null;
			$subscriber->save();

			return $this->get_success_response(
				[
					'company_id'       => $company_id,
					'subscriber_id'    => $subscriber_id,
					'subscriber_email' => $subscriber->email,
					'action'           => 'unlinked',
				],
				'Subscriber unlinked from company successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove subscriber from company: ' . $e->getMessage(), 'unlink_failed' );
		}
	}

	/**
	 * Execute get company subscribers ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_company_subscribers( array $args ): array {
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

			// Get subscribers for this company
			$query = \FluentCrm\App\Models\Subscriber::where( 'company_id', $company_id );

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
					'full_name'  => $subscriber->full_name,
					'status'     => $subscriber->status,
					'created_at' => $subscriber->created_at,
				];
			}

			return $this->get_success_response(
				[
					'company_id'   => $company_id,
					'company_name' => $company->name,
					'subscribers'  => $result,
					'total'        => $total,
					'page'         => $page,
					'per_page'     => $per_page,
					'total_pages'  => ceil( $total / $per_page ),
				],
				'Company subscribers retrieved successfully'
			);
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get company subscribers: ' . $e->getMessage(), 'get_subscribers_failed' );
		}
	}
}
