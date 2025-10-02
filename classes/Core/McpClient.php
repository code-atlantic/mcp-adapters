<?php
/**
 * MCP Client for connecting to external MCP servers.
 *
 * @package MCP\Adapters
 */

declare(strict_types=1);

namespace MCP\Adapters\Core;

/**
 * McpClient class for bidirectional MCP integration.
 *
 * Connects to external MCP servers and auto-registers remote capabilities
 * as WordPress abilities with namespace: mcp_{client_id}/tool-name
 */
class McpClient {
	/**
	 * Client identifier.
	 *
	 * @var string
	 */
	private string $client_id;

	/**
	 * Server URL.
	 *
	 * @var string
	 */
	private string $server_url;

	/**
	 * Client configuration.
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Connection status.
	 *
	 * @var bool
	 */
	private bool $connected = false;

	/**
	 * Server capabilities (tools, resources, prompts).
	 *
	 * @var array
	 */
	private array $capabilities = [];

	/**
	 * Session ID from server.
	 *
	 * @var string|null
	 */
	private ?string $session_id = null;

	/**
	 * Constructor.
	 *
	 * @param string $client_id Client identifier.
	 * @param string $server_url Server URL.
	 * @param array  $config Client configuration.
	 */
	public function __construct( string $client_id, string $server_url, array $config = [] ) {
		$this->client_id  = $client_id;
		$this->server_url = $server_url;
		$this->config     = wp_parse_args(
			$config,
			[
				'timeout' => 30,
				'auth'    => [],
			]
		);

		// Auto-connect on instantiation.
		$this->connect();
	}

	/**
	 * Connect to external MCP server.
	 *
	 * @return bool True if connection successful.
	 */
	public function connect(): bool {
		try {
			// Send initialization request.
			$response = $this->send_request( 'initialize', [] );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			// Extract server capabilities.
			$this->capabilities = $response['capabilities'] ?? [];
			$this->session_id   = $response['sessionId'] ?? null;
			$this->connected    = true;

			// Auto-register remote capabilities as WordPress abilities.
			$this->register_remote_capabilities();

			return true;
		} catch ( \Exception $e ) {
			error_log( "McpClient ({$this->client_id}) connection failed: " . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Send JSON-RPC request to server.
	 *
	 * @param string $method Request method.
	 * @param array  $params Request parameters.
	 * @return array|\WP_Error Response or error.
	 */
	private function send_request( string $method, array $params ) {
		$request_body = [
			'jsonrpc' => '2.0',
			'id'      => wp_rand( 1, 999999 ),
			'method'  => $method,
			'params'  => $params,
		];

		$args = [
			'timeout' => $this->config['timeout'],
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $request_body ),
		];

		// Add authentication if configured.
		if ( ! empty( $this->config['auth'] ) ) {
			$args['headers'] = array_merge( $args['headers'], $this->get_auth_headers() );
		}

		$response = wp_remote_post( $this->server_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new \WP_Error(
				'mcp_client_error',
				$data['error']['message'] ?? 'Unknown error',
				$data['error']
			);
		}

		return $data['result'] ?? [];
	}

	/**
	 * Get authentication headers.
	 *
	 * @return array Authentication headers.
	 */
	private function get_auth_headers(): array {
		$auth = $this->config['auth'];
		$type = $auth['type'] ?? 'bearer';

		switch ( $type ) {
			case 'bearer':
				return [ 'Authorization' => 'Bearer ' . $auth['token'] ];

			case 'api_key':
				return [ 'X-API-Key' => $auth['key'] ];

			case 'basic':
				$credentials = base64_encode( $auth['username'] . ':' . $auth['password'] );
				return [ 'Authorization' => 'Basic ' . $credentials ];

			default:
				return [];
		}
	}

	/**
	 * Register remote capabilities as WordPress abilities.
	 */
	private function register_remote_capabilities(): void {
		// Register tools.
		$tools = $this->list_tools();
		foreach ( $tools as $tool ) {
			$this->register_remote_tool( $tool );
		}

		// Register resources.
		$resources = $this->list_resources();
		foreach ( $resources as $resource ) {
			$this->register_remote_resource( $resource );
		}

		// Register prompts.
		$prompts = $this->list_prompts();
		foreach ( $prompts as $prompt ) {
			$this->register_remote_prompt( $prompt );
		}
	}

	/**
	 * Register a remote tool as WordPress ability.
	 *
	 * @param array $tool Tool data from remote server.
	 */
	private function register_remote_tool( array $tool ): void {
		$ability_name = "mcp_{$this->client_id}/" . $tool['name'];

		\wp_register_ability(
			$ability_name,
			[
				'description'         => $tool['description'] ?? '',
				'input_schema'        => $tool['inputSchema'] ?? [],
				'permission_callback' => function () {
					return apply_filters( 'mcp_client_permission', true, $this->client_id );
				},
				'execute_callback'    => function ( $args ) use ( $tool ) {
					return $this->call_tool( $tool['name'], $args );
				},
			]
		);
	}

	/**
	 * Register a remote resource as WordPress ability.
	 *
	 * @param array $resource Resource data from remote server.
	 */
	private function register_remote_resource( array $resource ): void {
		$ability_name = "mcp_{$this->client_id}/resource/" . $resource['uri'];

		\wp_register_ability(
			$ability_name,
			[
				'description'         => $resource['description'] ?? '',
				'input_schema'        => [],
				'permission_callback' => function () {
					return apply_filters( 'mcp_client_permission', true, $this->client_id );
				},
				'execute_callback'    => function ( $args ) use ( $resource ) {
					return $this->read_resource( $resource['uri'] );
				},
			]
		);
	}

	/**
	 * Register a remote prompt as WordPress ability.
	 *
	 * @param array $prompt Prompt data from remote server.
	 */
	private function register_remote_prompt( array $prompt ): void {
		$ability_name = "mcp_{$this->client_id}/prompt/" . $prompt['name'];

		\wp_register_ability(
			$ability_name,
			[
				'description'         => $prompt['description'] ?? '',
				'input_schema'        => $prompt['arguments'] ?? [],
				'permission_callback' => function () {
					return apply_filters( 'mcp_client_permission', true, $this->client_id );
				},
				'execute_callback'    => function ( $args ) use ( $prompt ) {
					return $this->get_prompt( $prompt['name'], $args );
				},
			]
		);
	}

	/**
	 * Call a remote tool.
	 *
	 * @param string $tool_name Tool name.
	 * @param array  $args Tool arguments.
	 * @return array|\WP_Error Tool result or error.
	 */
	public function call_tool( string $tool_name, array $args = [] ) {
		return $this->send_request(
			'tools/call',
			[
				'name'      => $tool_name,
				'arguments' => $args,
			]
		);
	}

	/**
	 * Read a remote resource.
	 *
	 * @param string $uri Resource URI.
	 * @return array|\WP_Error Resource data or error.
	 */
	public function read_resource( string $uri ) {
		return $this->send_request(
			'resources/read',
			[
				'uri' => $uri,
			]
		);
	}

	/**
	 * Get a remote prompt.
	 *
	 * @param string $name Prompt name.
	 * @param array  $args Prompt arguments.
	 * @return array|\WP_Error Prompt result or error.
	 */
	public function get_prompt( string $name, array $args = [] ) {
		return $this->send_request(
			'prompts/get',
			[
				'name'      => $name,
				'arguments' => $args,
			]
		);
	}

	/**
	 * List available tools from remote server.
	 *
	 * @return array List of tools.
	 */
	public function list_tools(): array {
		$response = $this->send_request( 'tools/list', [] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		return $response['tools'] ?? [];
	}

	/**
	 * List available resources from remote server.
	 *
	 * @return array List of resources.
	 */
	public function list_resources(): array {
		$response = $this->send_request( 'resources/list', [] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		return $response['resources'] ?? [];
	}

	/**
	 * List available prompts from remote server.
	 *
	 * @return array List of prompts.
	 */
	public function list_prompts(): array {
		$response = $this->send_request( 'prompts/list', [] );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		return $response['prompts'] ?? [];
	}

	/**
	 * Check if client is connected.
	 *
	 * @return bool True if connected.
	 */
	public function is_connected(): bool {
		return $this->connected;
	}

	/**
	 * Get client ID.
	 *
	 * @return string Client ID.
	 */
	public function get_id(): string {
		return $this->client_id;
	}

	/**
	 * Get server URL.
	 *
	 * @return string Server URL.
	 */
	public function get_server_url(): string {
		return $this->server_url;
	}

	/**
	 * Get server capabilities.
	 *
	 * @return array Server capabilities.
	 */
	public function get_capabilities(): array {
		return $this->capabilities;
	}
}
