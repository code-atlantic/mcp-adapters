<?php
/**
 * MCP Client Manager for registering and managing external MCP clients.
 *
 * @package MCP\Adapters
 */

declare(strict_types=1);

namespace MCP\Adapters\Core;

/**
 * McpClientManager class for managing MCP client instances.
 */
class McpClientManager {
	/**
	 * Registered MCP clients.
	 *
	 * @var McpClient[]
	 */
	private static array $clients = [];

	/**
	 * Initialize the client manager.
	 */
	public static function init(): void {
		add_action( 'abilities_api_init', [ __CLASS__, 'register_clients' ], 20 );
	}

	/**
	 * Register MCP clients.
	 *
	 * Fires the mcp_client_init action for plugins to register clients.
	 */
	public static function register_clients(): void {
		/**
		 * Fires when the MCP client system is initialized.
		 *
		 * @param McpClientManager $manager The client manager instance.
		 */
		do_action( 'mcp_client_init', new self() );
	}

	/**
	 * Create and register a new MCP client.
	 *
	 * @param string $client_id Client identifier.
	 * @param string $server_url Server URL.
	 * @param array  $config Client configuration.
	 * @return McpClient|null The created client or null on failure.
	 */
	public function create_client( string $client_id, string $server_url, array $config = [] ): ?McpClient {
		if ( isset( self::$clients[ $client_id ] ) ) {
			error_log( "McpClient with ID '{$client_id}' is already registered." );
			return null;
		}

		try {
			$client = new McpClient( $client_id, $server_url, $config );

			if ( $client->is_connected() ) {
				self::$clients[ $client_id ] = $client;
				return $client;
			}

			error_log( "McpClient '{$client_id}' failed to connect to '{$server_url}'." );
			return null;
		} catch ( \Exception $e ) {
			error_log( "McpClient '{$client_id}' creation failed: " . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Get a registered client by ID.
	 *
	 * @param string $client_id Client ID.
	 * @return McpClient|null The client or null if not found.
	 */
	public static function get_client( string $client_id ): ?McpClient {
		return self::$clients[ $client_id ] ?? null;
	}

	/**
	 * Get all registered clients.
	 *
	 * @return McpClient[] Array of registered clients.
	 */
	public static function get_clients(): array {
		return self::$clients;
	}

	/**
	 * Check if a client is registered.
	 *
	 * @param string $client_id Client ID.
	 * @return bool True if registered.
	 */
	public static function has_client( string $client_id ): bool {
		return isset( self::$clients[ $client_id ] );
	}

	/**
	 * Get client connection status.
	 *
	 * @return array Array of client statuses.
	 */
	public static function get_client_status(): array {
		$status = [];

		foreach ( self::$clients as $client_id => $client ) {
			$status[ $client_id ] = [
				'id'           => $client_id,
				'url'          => $client->get_server_url(),
				'connected'    => $client->is_connected(),
				'capabilities' => $client->get_capabilities(),
				'tools'        => count( $client->list_tools() ),
				'resources'    => count( $client->list_resources() ),
				'prompts'      => count( $client->list_prompts() ),
			];
		}

		return $status;
	}
}
