<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Abilities;

use MCP\Adapters\Adapters\FluentBoards\BaseAbility;

/**
 * FluentBoards User Abilities
 *
 * Registers WordPress abilities for FluentBoards user management operations
 * using the new WordPress Abilities API pattern.
 */
class Users extends BaseAbility {

	/**
	 * Register all user-related abilities
	 */
	protected function register_abilities(): void {
		$this->register_get_board_users();
		$this->register_add_board_member();
		$this->register_remove_board_member();
		$this->register_update_member_role();
		$this->register_search_users();
		$this->register_get_user_info();
		$this->register_get_all_users();
		$this->register_set_super_admin();
		$this->register_remove_super_admin();
		$this->register_update_board_permissions();
		$this->register_bulk_add_members();
		$this->register_bulk_set_super_admins();
		$this->register_get_user_tasks();
		$this->register_get_user_activities();
		$this->register_get_user_boards();
	}

	/**
	 * Register get board users ability
	 */
	private function register_get_board_users(): void {
		wp_register_ability('fluentboards_get_board_users', [
			'label'               => 'Get FluentBoards board users',
			'description'         => 'Get all users assigned to a board with their roles and permissions',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID to retrieve users from',
					],
				],
				'required'   => [ 'board_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_board_users' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register add board member ability
	 */
	private function register_add_board_member(): void {
		wp_register_ability('fluentboards_add_board_member', [
			'label'               => 'Add FluentBoards board member',
			'description'         => 'Add a user to a board with specified role',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID to add member to',
					],
					'user_id'  => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to add as member',
					],
					'role'     => [
						'type'        => 'string',
						'description' => 'Member role: "member" (default), "manager" (board admin), "viewer" (read-only, Pro required)',
						'enum'        => [ 'member', 'manager', 'viewer' ],
						'default'     => 'member',
					],
				],
				'required'   => [ 'board_id', 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_add_board_member' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register remove board member ability
	 */
	private function register_remove_board_member(): void {
		wp_register_ability('fluentboards_remove_board_member', [
			'label'               => 'Remove FluentBoards board member',
			'description'         => 'Remove a user from a board',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID to remove member from',
					],
					'user_id'  => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to remove from board',
					],
				],
				'required'   => [ 'board_id', 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_remove_board_member' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register update member role ability
	 */
	private function register_update_member_role(): void {
		wp_register_ability('fluentboards_update_member_role', [
			'label'               => 'Update FluentBoards member role',
			'description'         => 'Update a board member\'s role and permissions',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id' => [
						'type'        => 'integer',
						'description' => 'Board ID where member belongs',
					],
					'user_id'  => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to update',
					],
					'role'     => [
						'type'        => 'string',
						'description' => 'New role: "member", "manager" (board admin), "viewer" (read-only, Pro required)',
						'enum'        => [ 'member', 'manager', 'viewer' ],
					],
				],
				'required'   => [ 'board_id', 'user_id', 'role' ],
			],
			'execute_callback'    => [ $this, 'execute_update_member_role' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register search users ability
	 */
	private function register_search_users(): void {
		wp_register_ability('fluentboards_search_users', [
			'label'               => 'Search FluentBoards users',
			'description'         => 'Search for WordPress users by name, email, or login',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'search_term' => [
						'type'        => 'string',
						'description' => 'Search term to find users by login, email, nicename, first_name, or last_name',
					],
					'board_id'    => [
						'type'        => 'integer',
						'description' => 'Optional board ID to filter users with board access',
					],
				],
				'required'   => [ 'search_term' ],
			],
			'execute_callback'    => [ $this, 'execute_search_users' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register get user info ability
	 */
	private function register_get_user_info(): void {
		wp_register_ability('fluentboards_get_user_info', [
			'label'               => 'Get FluentBoards user info',
			'description'         => 'Get detailed information about a specific user including FluentBoards and FluentCRM data',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to retrieve information for',
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_user_info' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register get all users ability
	 */
	private function register_get_all_users(): void {
		wp_register_ability('fluentboards_get_all_users', [
			'label'               => 'Get all FluentBoards users',
			'description'         => 'Get all FluentBoards users with pagination',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'page'     => [
						'type'        => 'integer',
						'description' => 'Page number for pagination',
						'default'     => 1,
						'minimum'     => 1,
					],
					'per_page' => [
						'type'        => 'integer',
						'description' => 'Number of users per page',
						'default'     => 20,
						'minimum'     => 1,
						'maximum'     => 100,
					],
				],
			],
			'execute_callback'    => [ $this, 'execute_get_all_users' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register set super admin ability
	 */
	private function register_set_super_admin(): void {
		wp_register_ability('fluentboards_set_super_admin', [
			'label'               => 'Set FluentBoards super admin',
			'description'         => 'Grant FluentBoards super admin privileges to a user',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to make super admin',
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_set_super_admin' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register remove super admin ability
	 */
	private function register_remove_super_admin(): void {
		wp_register_ability('fluentboards_remove_super_admin', [
			'label'               => 'Remove FluentBoards super admin',
			'description'         => 'Remove FluentBoards super admin privileges from a user',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to remove super admin privileges from',
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_remove_super_admin' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register update board permissions ability
	 */
	private function register_update_board_permissions(): void {
		wp_register_ability('fluentboards_update_board_permissions', [
			'label'               => 'Update FluentBoards board permissions',
			'description'         => 'Update specific permissions for a user on a board',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'board_id'    => [
						'type'        => 'integer',
						'description' => 'Board ID to update permissions for',
					],
					'user_id'     => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to update permissions for',
					],
					'permissions' => [
						'type'        => 'array',
						'description' => 'Array of permission strings: ["create_tasks", "edit_tasks", "delete_tasks"]',
						'items'       => [
							'type' => 'string',
						],
					],
				],
				'required'   => [ 'board_id', 'user_id', 'permissions' ],
			],
			'execute_callback'    => [ $this, 'execute_update_board_permissions' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register bulk add members ability
	 */
	private function register_bulk_add_members(): void {
		wp_register_ability('fluentboards_bulk_add_members', [
			'label'               => 'Bulk add FluentBoards members',
			'description'         => 'Add multiple users to multiple boards with specified roles',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'operations' => [
						'type'        => 'array',
						'description' => 'Array of operations: [{"board_id": 1, "user_id": 2, "role": "member"}, ...]',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'board_id' => [
									'type' => 'integer',
								],
								'user_id'  => [
									'type' => 'integer',
								],
								'role'     => [
									'type' => 'string',
									'enum' => [ 'member', 'manager', 'viewer' ],
								],
							],
							'required'   => [ 'board_id', 'user_id' ],
						],
					],
				],
				'required'   => [ 'operations' ],
			],
			'execute_callback'    => [ $this, 'execute_bulk_add_members' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register bulk set super admins ability
	 */
	private function register_bulk_set_super_admins(): void {
		wp_register_ability('fluentboards_bulk_set_super_admins', [
			'label'               => 'Bulk set FluentBoards super admins',
			'description'         => 'Grant or revoke FluentBoards super admin privileges for multiple users',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_ids'     => [
						'type'        => 'array',
						'description' => 'Array of WordPress user IDs to update',
						'items'       => [
							'type' => 'integer',
						],
					],
					'grant_access' => [
						'type'        => 'boolean',
						'description' => 'True to grant super admin access, false to revoke',
					],
				],
				'required'   => [ 'user_ids', 'grant_access' ],
			],
			'execute_callback'    => [ $this, 'execute_bulk_set_super_admins' ],
			'permission_callback' => [ $this, 'can_manage_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register get user tasks ability
	 */
	private function register_get_user_tasks(): void {
		wp_register_ability('fluentboards_get_user_tasks', [
			'label'               => 'Get FluentBoards user tasks',
			'description'         => 'Get all tasks assigned to a user across all boards',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to get tasks for',
					],
					'status'  => [
						'type'        => 'string',
						'description' => 'Filter by task status: "open", "in_progress", "completed", "closed"',
						'enum'        => [ 'open', 'in_progress', 'completed', 'closed' ],
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_user_tasks' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register get user activities ability
	 */
	private function register_get_user_activities(): void {
		wp_register_ability('fluentboards_get_user_activities', [
			'label'               => 'Get FluentBoards user activities',
			'description'         => 'Get recent activities for a user across all boards',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to get activities for',
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_user_activities' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Register get user boards ability
	 */
	private function register_get_user_boards(): void {
		wp_register_ability('fluentboards_get_user_boards', [
			'label'               => 'Get FluentBoards user boards',
			'description'         => 'Get all boards accessible to a user',
			'input_schema'        => [
				'type'       => 'object',
				'properties' => [
					'user_id' => [
						'type'        => 'integer',
						'description' => 'WordPress user ID to get boards for',
					],
				],
				'required'   => [ 'user_id' ],
			],
			'execute_callback'    => [ $this, 'execute_get_user_boards' ],
			'permission_callback' => [ $this, 'can_view_boards' ],
			'meta'                => [
				'category'    => 'fluentboards',
				'subcategory' => 'users',
			],
		]);
	}

	/**
	 * Execute get board users ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_board_users( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			// Verify board exists
			if ( ! $this->board_exists( $board_id ) ) {
				return $this->get_error_response( 'Board not found', 'board_not_found' );
			}

			// Get board users via relations
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relations      = $relation_model->where( 'object_id', $board_id )
									->where( 'object_type', 'board' )
									->where( 'foreign_type', 'user' )
									->get();

			$users = [];
			foreach ( $relations as $relation ) {
				$user = get_user_by( 'id', $relation->foreign_id );
				if ( $user ) {
					$settings = $relation->settings ?? [];
					$users[]  = [
						'id'           => $user->ID,
						'login'        => $user->user_login,
						'email'        => $user->user_email,
						'display_name' => $user->display_name,
						'first_name'   => get_user_meta( $user->ID, 'first_name', true ),
						'last_name'    => get_user_meta( $user->ID, 'last_name', true ),
						'role'         => $settings['role'] ?? 'member',
						'permissions'  => $settings['permissions'] ?? [],
						'avatar_url'   => get_avatar_url( $user->ID ),
						'joined_at'    => $relation->created_at,
					];
				}
			}

			return $this->get_success_response([
				'users'       => $users,
				'total_users' => count( $users ),
				'board_id'    => $board_id,
			], 'Board users retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get board users: ' . $e->getMessage(), 'get_board_users_failed' );
		}
	}

	/**
	 * Execute add board member ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_add_board_member( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$user_id  = intval( $args['user_id'] );
			$role     = $args['role'] ?? 'member';

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Verify board exists
			if ( ! $this->board_exists( $board_id ) ) {
				return $this->get_error_response( 'Board not found', 'board_not_found' );
			}

			// Verify user exists
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Check if user is already a member
			$relation_model    = new \FluentBoards\App\Models\Relation();
			$existing_relation = $relation_model->where( 'object_id', $board_id )
												->where( 'object_type', 'board' )
												->where( 'foreign_id', $user_id )
												->where( 'foreign_type', 'user' )
												->first();

			if ( $existing_relation ) {
				return $this->get_error_response( 'User is already a member of this board', 'user_already_member' );
			}

			// Add user to board
			$relation = $relation_model->create([
				'object_id'    => $board_id,
				'object_type'  => 'board',
				'foreign_id'   => $user_id,
				'foreign_type' => 'user',
				'settings'     => [
					'role'        => $role,
					'permissions' => $this->get_default_permissions_for_role( $role ),
				],
			]);

			return $this->get_success_response([
				'board_id'    => $board_id,
				'user'        => [
					'id'           => $user->ID,
					'login'        => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'role'         => $role,
				],
				'relation_id' => $relation->id,
				'joined_at'   => $relation->created_at,
			], 'User added to board successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to add board member: ' . $e->getMessage(), 'add_board_member_failed' );
		}
	}

	/**
	 * Execute remove board member ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_board_member( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$user_id  = intval( $args['user_id'] );

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Verify board exists
			if ( ! $this->board_exists( $board_id ) ) {
				return $this->get_error_response( 'Board not found', 'board_not_found' );
			}

			// Find the relation
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relation       = $relation_model->where( 'object_id', $board_id )
									->where( 'object_type', 'board' )
									->where( 'foreign_id', $user_id )
									->where( 'foreign_type', 'user' )
									->first();

			if ( ! $relation ) {
				return $this->get_error_response( 'User is not a member of this board', 'user_not_member' );
			}

			// Get user info before deletion
			$user      = get_user_by( 'id', $user_id );
			$user_name = $user ? $user->display_name : 'Unknown User';

			// Remove the relation
			$relation->delete();

			return $this->get_success_response([
				'board_id'   => $board_id,
				'user_id'    => $user_id,
				'user_name'  => $user_name,
				'removed_at' => current_time( 'mysql' ),
			], 'User removed from board successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove board member: ' . $e->getMessage(), 'remove_board_member_failed' );
		}
	}

	/**
	 * Execute update member role ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_member_role( array $args ): array {
		try {
			$board_id = intval( $args['board_id'] );
			$user_id  = intval( $args['user_id'] );
			$role     = $args['role'];

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Verify board exists
			if ( ! $this->board_exists( $board_id ) ) {
				return $this->get_error_response( 'Board not found', 'board_not_found' );
			}

			// Find the relation
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relation       = $relation_model->where( 'object_id', $board_id )
									->where( 'object_type', 'board' )
									->where( 'foreign_id', $user_id )
									->where( 'foreign_type', 'user' )
									->first();

			if ( ! $relation ) {
				return $this->get_error_response( 'User is not a member of this board', 'user_not_member' );
			}

			// Update role and permissions
			$settings                = $relation->settings ?? [];
			$settings['role']        = $role;
			$settings['permissions'] = $this->get_default_permissions_for_role( $role );

			$relation->update( [ 'settings' => $settings ] );

			$user = get_user_by( 'id', $user_id );

			return $this->get_success_response([
				'board_id'   => $board_id,
				'user'       => [
					'id'           => $user->ID,
					'display_name' => $user->display_name,
					'role'         => $role,
					'permissions'  => $settings['permissions'],
				],
				'updated_at' => current_time( 'mysql' ),
			], 'Member role updated successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update member role: ' . $e->getMessage(), 'update_member_role_failed' );
		}
	}

	/**
	 * Execute search users ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_search_users( array $args ): array {
		try {
			$search_term = sanitize_text_field( $args['search_term'] );
			$board_id    = isset( $args['board_id'] ) ? intval( $args['board_id'] ) : null;

			if ( empty( $search_term ) ) {
				return $this->get_error_response( 'Search term is required', 'search_term_required' );
			}

			// Search WordPress users
			$user_query = new \WP_User_Query([
				'search'         => '*' . $search_term . '*',
				'search_columns' => [ 'user_login', 'user_email', 'user_nicename', 'display_name' ],
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'     => 'first_name',
						'value'   => $search_term,
						'compare' => 'LIKE',
					],
					[
						'key'     => 'last_name',
						'value'   => $search_term,
						'compare' => 'LIKE',
					],
				],
				'number'         => 50, // Limit results
			]);

			$users = [];
			foreach ( $user_query->results as $user ) {
				$user_data = [
					'id'           => $user->ID,
					'login'        => $user->user_login,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'first_name'   => get_user_meta( $user->ID, 'first_name', true ),
					'last_name'    => get_user_meta( $user->ID, 'last_name', true ),
					'avatar_url'   => get_avatar_url( $user->ID ),
				];

				// If board_id is provided, check if user has access
				if ( $board_id ) {
					$relation_model = new \FluentBoards\App\Models\Relation();
					$relation       = $relation_model->where( 'object_id', $board_id )
											->where( 'object_type', 'board' )
											->where( 'foreign_id', $user->ID )
											->where( 'foreign_type', 'user' )
											->first();

					$user_data['has_board_access'] = ! empty( $relation );
					$user_data['board_role']       = $relation ? ( $relation->settings['role'] ?? 'member' ) : null;
				}

				$users[] = $user_data;
			}

			return $this->get_success_response([
				'users'       => $users,
				'total_found' => count( $users ),
				'search_term' => $search_term,
				'board_id'    => $board_id,
			], 'Users found successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to search users: ' . $e->getMessage(), 'search_users_failed' );
		}
	}

	/**
	 * Execute get user info ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_user_info( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Get WordPress user
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Get FluentBoards data
			$relation_model  = new \FluentBoards\App\Models\Relation();
			$board_relations = $relation_model->where( 'foreign_id', $user_id )
											->where( 'foreign_type', 'user' )
											->where( 'object_type', 'board' )
											->get();

			$boards = [];
			foreach ( $board_relations as $relation ) {
				$board = \FluentBoards\App\Models\Board::find( $relation->object_id );
				if ( $board ) {
					$settings = $relation->settings ?? [];
					$boards[] = [
						'id'          => $board->id,
						'title'       => $board->title,
						'role'        => $settings['role'] ?? 'member',
						'permissions' => $settings['permissions'] ?? [],
						'joined_at'   => $relation->created_at,
					];
				}
			}

			// Get FluentCRM data if available
			$crm_data = null;
			if ( class_exists( '\FluentCrm\App\Models\Subscriber' ) ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::where( 'user_id', $user_id )->first();
				if ( $subscriber ) {
					$crm_data = [
						'id'     => $subscriber->id,
						'email'  => $subscriber->email,
						'status' => $subscriber->status,
						'tags'   => $subscriber->tags ? $subscriber->tags->pluck( 'title' ) : [],
						'lists'  => $subscriber->lists ? $subscriber->lists->pluck( 'title' ) : [],
					];
				}
			}

			$user_info = [
				'id'           => $user->ID,
				'login'        => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name,
				'first_name'   => get_user_meta( $user->ID, 'first_name', true ),
				'last_name'    => get_user_meta( $user->ID, 'last_name', true ),
				'description'  => get_user_meta( $user->ID, 'description', true ),
				'roles'        => $user->roles,
				'avatar_url'   => get_avatar_url( $user->ID ),
				'registered'   => $user->user_registered,
				'fluentboards' => [
					'boards'       => $boards,
					'total_boards' => count( $boards ),
				],
				'fluentcrm'    => $crm_data,
			];

			return $this->get_success_response([
				'user' => $user_info,
			], 'User information retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get user info: ' . $e->getMessage(), 'get_user_info_failed' );
		}
	}

	/**
	 * Execute get all users ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_all_users( array $args ): array {
		try {
			$page     = $args['page'] ?? 1;
			$per_page = $args['per_page'] ?? 20;

			$offset = ( $page - 1 ) * $per_page;

			// Get all WordPress users
			$user_query = new \WP_User_Query([
				'number'  => $per_page,
				'offset'  => $offset,
				'orderby' => 'display_name',
				'order'   => 'ASC',
			]);

			$users = [];
			foreach ( $user_query->results as $user ) {
				// Check if user has any FluentBoards access
				$relation_model = new \FluentBoards\App\Models\Relation();
				$board_count    = $relation_model->where( 'foreign_id', $user->ID )
											->where( 'foreign_type', 'user' )
											->where( 'object_type', 'board' )
											->count();

				$users[] = [
					'id'                  => $user->ID,
					'login'               => $user->user_login,
					'email'               => $user->user_email,
					'display_name'        => $user->display_name,
					'first_name'          => get_user_meta( $user->ID, 'first_name', true ),
					'last_name'           => get_user_meta( $user->ID, 'last_name', true ),
					'roles'               => $user->roles,
					'registered'          => $user->user_registered,
					'avatar_url'          => get_avatar_url( $user->ID ),
					'fluentboards_access' => $board_count > 0,
					'board_count'         => $board_count,
				];
			}

			// Get total count
			$total_query = new \WP_User_Query( [ 'fields' => 'ID' ] );
			$total_users = $total_query->get_total();

			return $this->get_success_response([
				'users'      => $users,
				'pagination' => [
					'page'        => $page,
					'per_page'    => $per_page,
					'total_users' => $total_users,
					'total_pages' => ceil( $total_users / $per_page ),
				],
			], 'All users retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get all users: ' . $e->getMessage(), 'get_all_users_failed' );
		}
	}

	/**
	 * Execute set super admin ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_set_super_admin( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Add FluentBoards super admin capability
			$user->add_cap( 'fluent_boards_admin' );

			return $this->get_success_response([
				'user_id'        => $user_id,
				'user_name'      => $user->display_name,
				'is_super_admin' => true,
				'granted_at'     => current_time( 'mysql' ),
			], 'Super admin privileges granted successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to set super admin: ' . $e->getMessage(), 'set_super_admin_failed' );
		}
	}

	/**
	 * Execute remove super admin ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_remove_super_admin( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Remove FluentBoards super admin capability
			$user->remove_cap( 'fluent_boards_admin' );

			return $this->get_success_response([
				'user_id'        => $user_id,
				'user_name'      => $user->display_name,
				'is_super_admin' => false,
				'revoked_at'     => current_time( 'mysql' ),
			], 'Super admin privileges removed successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to remove super admin: ' . $e->getMessage(), 'remove_super_admin_failed' );
		}
	}

	/**
	 * Execute update board permissions ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_update_board_permissions( array $args ): array {
		try {
			$board_id    = intval( $args['board_id'] );
			$user_id     = intval( $args['user_id'] );
			$permissions = $args['permissions'] ?? [];

			if ( $board_id <= 0 ) {
				return $this->get_error_response( 'Invalid board ID', 'invalid_board_id' );
			}

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Verify board exists
			if ( ! $this->board_exists( $board_id ) ) {
				return $this->get_error_response( 'Board not found', 'board_not_found' );
			}

			// Find the relation
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relation       = $relation_model->where( 'object_id', $board_id )
									->where( 'object_type', 'board' )
									->where( 'foreign_id', $user_id )
									->where( 'foreign_type', 'user' )
									->first();

			if ( ! $relation ) {
				return $this->get_error_response( 'User is not a member of this board', 'user_not_member' );
			}

			// Update permissions
			$settings                = $relation->settings ?? [];
			$settings['permissions'] = array_values( $permissions ); // Ensure array values

			$relation->update( [ 'settings' => $settings ] );

			$user = get_user_by( 'id', $user_id );

			return $this->get_success_response([
				'board_id'   => $board_id,
				'user'       => [
					'id'           => $user->ID,
					'display_name' => $user->display_name,
					'permissions'  => $settings['permissions'],
				],
				'updated_at' => current_time( 'mysql' ),
			], 'Board permissions updated successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to update board permissions: ' . $e->getMessage(), 'update_permissions_failed' );
		}
	}

	/**
	 * Execute bulk add members ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_add_members( array $args ): array {
		try {
			$operations = $args['operations'] ?? [];

			if ( empty( $operations ) ) {
				return $this->get_error_response( 'No operations provided', 'no_operations' );
			}

			$results    = [];
			$successful = 0;
			$failed     = 0;

			foreach ( $operations as $operation ) {
				$board_id = intval( $operation['board_id'] ?? 0 );
				$user_id  = intval( $operation['user_id'] ?? 0 );
				$role     = $operation['role'] ?? 'member';

				try {
					// Validate inputs
					if ( $board_id <= 0 || $user_id <= 0 ) {
						$results[] = [
							'board_id' => $board_id,
							'user_id'  => $user_id,
							'success'  => false,
							'error'    => 'Invalid board or user ID',
						];
						++$failed;
						continue;
					}

					// Check if board exists
					if ( ! $this->board_exists( $board_id ) ) {
						$results[] = [
							'board_id' => $board_id,
							'user_id'  => $user_id,
							'success'  => false,
							'error'    => 'Board not found',
						];
						++$failed;
						continue;
					}

					// Check if user exists
					$user = get_user_by( 'id', $user_id );
					if ( ! $user ) {
						$results[] = [
							'board_id' => $board_id,
							'user_id'  => $user_id,
							'success'  => false,
							'error'    => 'User not found',
						];
						++$failed;
						continue;
					}

					// Check if already a member
					$relation_model = new \FluentBoards\App\Models\Relation();
					$existing       = $relation_model->where( 'object_id', $board_id )
											->where( 'object_type', 'board' )
											->where( 'foreign_id', $user_id )
											->where( 'foreign_type', 'user' )
											->first();

					if ( $existing ) {
						$results[] = [
							'board_id' => $board_id,
							'user_id'  => $user_id,
							'success'  => false,
							'error'    => 'User already a member',
						];
						++$failed;
						continue;
					}

					// Add member
					$relation = $relation_model->create([
						'object_id'    => $board_id,
						'object_type'  => 'board',
						'foreign_id'   => $user_id,
						'foreign_type' => 'user',
						'settings'     => [
							'role'        => $role,
							'permissions' => $this->get_default_permissions_for_role( $role ),
						],
					]);

					$results[] = [
						'board_id'    => $board_id,
						'user_id'     => $user_id,
						'user_name'   => $user->display_name,
						'role'        => $role,
						'success'     => true,
						'relation_id' => $relation->id,
					];
					++$successful;
				} catch ( \Exception $e ) {
					$results[] = [
						'board_id' => $board_id,
						'user_id'  => $user_id,
						'success'  => false,
						'error'    => $e->getMessage(),
					];
					++$failed;
				}
			}

			return $this->get_success_response([
				'operations' => $results,
				'summary'    => [
					'total'      => count( $operations ),
					'successful' => $successful,
					'failed'     => $failed,
				],
			], 'Bulk member operations completed');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to bulk add members: ' . $e->getMessage(), 'bulk_add_members_failed' );
		}
	}

	/**
	 * Execute bulk set super admins ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_bulk_set_super_admins( array $args ): array {
		try {
			$user_ids     = $args['user_ids'] ?? [];
			$grant_access = (bool) $args['grant_access'];

			if ( empty( $user_ids ) ) {
				return $this->get_error_response( 'No user IDs provided', 'no_user_ids' );
			}

			$results    = [];
			$successful = 0;
			$failed     = 0;

			foreach ( $user_ids as $user_id ) {
				$user_id = intval( $user_id );

				try {
					if ( $user_id <= 0 ) {
						$results[] = [
							'user_id' => $user_id,
							'success' => false,
							'error'   => 'Invalid user ID',
						];
						++$failed;
						continue;
					}

					$user = get_user_by( 'id', $user_id );
					if ( ! $user ) {
						$results[] = [
							'user_id' => $user_id,
							'success' => false,
							'error'   => 'User not found',
						];
						++$failed;
						continue;
					}

					// Grant or revoke capability
					if ( $grant_access ) {
						$user->add_cap( 'fluent_boards_admin' );
					} else {
						$user->remove_cap( 'fluent_boards_admin' );
					}

					$results[] = [
						'user_id'        => $user_id,
						'user_name'      => $user->display_name,
						'is_super_admin' => $grant_access,
						'success'        => true,
					];
					++$successful;
				} catch ( \Exception $e ) {
					$results[] = [
						'user_id' => $user_id,
						'success' => false,
						'error'   => $e->getMessage(),
					];
					++$failed;
				}
			}

			$action = $grant_access ? 'granted' : 'revoked';

			return $this->get_success_response([
				'operations' => $results,
				'summary'    => [
					'total'      => count( $user_ids ),
					'successful' => $successful,
					'failed'     => $failed,
					'action'     => $action,
				],
			], "Bulk super admin privileges {$action}");
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to bulk set super admins: ' . $e->getMessage(), 'bulk_set_super_admins_failed' );
		}
	}

	/**
	 * Execute get user tasks ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_user_tasks( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );
			$status  = $args['status'] ?? null;

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Get user
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Get tasks assigned to user
			$task_model = new \FluentBoards\App\Models\Task();
			$query      = $task_model->whereHas('assignees', function ( $q ) use ( $user_id ) {
				$q->where( 'foreign_id', $user_id );
			})->with( [ 'board', 'stage' ] );

			// Apply status filter
			if ( $status ) {
				$query->where( 'status', $status );
			}

			$tasks = $query->orderBy( 'created_at', 'DESC' )->get();

			$formatted_tasks = [];
			foreach ( $tasks as $task ) {
				$formatted_tasks[] = [
					'id'          => $task->id,
					'title'       => $task->title,
					'description' => $task->description,
					'status'      => $task->status,
					'priority'    => $task->priority,
					'due_at'      => $task->due_at,
					'created_at'  => $task->created_at,
					'board'       => [
						'id'    => $task->board->id,
						'title' => $task->board->title,
					],
					'stage'       => [
						'id'    => $task->stage->id,
						'title' => $task->stage->title,
					],
				];
			}

			return $this->get_success_response([
				'tasks'         => $formatted_tasks,
				'total_tasks'   => count( $formatted_tasks ),
				'user_id'       => $user_id,
				'user_name'     => $user->display_name,
				'status_filter' => $status,
			], 'User tasks retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get user tasks: ' . $e->getMessage(), 'get_user_tasks_failed' );
		}
	}

	/**
	 * Execute get user activities ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_user_activities( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Get user
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Get activities (this would depend on FluentBoards activity tracking)
			// For now, we'll get basic activity from comments and task assignments
			$activities = [];

			// Get recent comments
			if ( class_exists( '\FluentBoards\App\Models\Comment' ) ) {
				$comment_model = new \FluentBoards\App\Models\Comment();
				$comments      = $comment_model->where( 'created_by', $user_id )
										->with( 'task' )
										->orderBy( 'created_at', 'DESC' )
										->limit( 10 )
										->get();

				foreach ( $comments as $comment ) {
					$activities[] = [
						'type'        => 'comment',
						'action'      => 'commented on task',
						'object_id'   => $comment->object_id,
						'object_type' => $comment->object_type,
						'content'     => substr( $comment->content, 0, 100 ) . '...',
						'created_at'  => $comment->created_at,
					];
				}
			}

			// Get recent task creations
			$task_model = new \FluentBoards\App\Models\Task();
			$tasks      = $task_model->where( 'created_by', $user_id )
								->orderBy( 'created_at', 'DESC' )
								->limit( 10 )
								->get();

			foreach ( $tasks as $task ) {
				$activities[] = [
					'type'        => 'task_creation',
					'action'      => 'created task',
					'object_id'   => $task->id,
					'object_type' => 'task',
					'content'     => $task->title,
					'created_at'  => $task->created_at,
				];
			}

			// Sort activities by date
			usort($activities, function ( $a, $b ) {
				return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
			});

			// Limit to 20 most recent
			$activities = array_slice( $activities, 0, 20 );

			return $this->get_success_response([
				'activities'       => $activities,
				'total_activities' => count( $activities ),
				'user_id'          => $user_id,
				'user_name'        => $user->display_name,
			], 'User activities retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get user activities: ' . $e->getMessage(), 'get_user_activities_failed' );
		}
	}

	/**
	 * Execute get user boards ability
	 *
	 * @param array $args Ability arguments
	 * @return array Response data
	 */
	public function execute_get_user_boards( array $args ): array {
		try {
			$user_id = intval( $args['user_id'] );

			if ( $user_id <= 0 ) {
				return $this->get_error_response( 'Invalid user ID', 'invalid_user_id' );
			}

			// Get user
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return $this->get_error_response( 'User not found', 'user_not_found' );
			}

			// Get boards user has access to
			$relation_model = new \FluentBoards\App\Models\Relation();
			$relations      = $relation_model->where( 'foreign_id', $user_id )
										->where( 'foreign_type', 'user' )
										->where( 'object_type', 'board' )
										->get();

			$boards = [];
			foreach ( $relations as $relation ) {
				$board = \FluentBoards\App\Models\Board::withCount( 'tasks' )
														->find( $relation->object_id );
				if ( $board && ! $board->archived_at ) {
					$settings = $relation->settings ?? [];
					$boards[] = [
						'id'               => $board->id,
						'title'            => $board->title,
						'description'      => $board->description,
						'type'             => $board->type,
						'created_at'       => $board->created_at,
						'tasks_count'      => $board->tasks_count,
						'user_role'        => $settings['role'] ?? 'member',
						'user_permissions' => $settings['permissions'] ?? [],
						'joined_at'        => $relation->created_at,
					];
				}
			}

			return $this->get_success_response([
				'boards'       => $boards,
				'total_boards' => count( $boards ),
				'user_id'      => $user_id,
				'user_name'    => $user->display_name,
			], 'User boards retrieved successfully');
		} catch ( \Exception $e ) {
			return $this->get_error_response( 'Failed to get user boards: ' . $e->getMessage(), 'get_user_boards_failed' );
		}
	}

	/**
	 * Get default permissions for a role
	 *
	 * @param string $role
	 * @return array
	 */
	private function get_default_permissions_for_role( string $role ): array {
		switch ( $role ) {
			case 'manager':
				return [ 'create_tasks', 'edit_tasks', 'delete_tasks', 'manage_members', 'edit_board' ];
			case 'member':
				return [ 'create_tasks', 'edit_tasks' ];
			case 'viewer':
				return [ 'view_tasks' ];
			default:
				return [ 'create_tasks', 'edit_tasks' ];
		}
	}
}
