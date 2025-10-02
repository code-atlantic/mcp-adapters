<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

use MCP\Adapters\Adapters\FluentBoards\Servers\AbilityRegistry;

/**
 * Configurable MCP Server
 *
 * Single server class that can be configured for different roles and scopes.
 * Replaces multiple nearly-identical server classes with configuration-driven approach.
 */
class Server {

	/**
	 * Server configuration
	 *
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @param array $config Server configuration
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Register server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			$this->config['server_id'],
			$this->config['route_namespace'],
			$this->config['route'],
			$this->config['name'],
			$this->config['description'],
			$this->config['version'] ?? '0.1.0',
			$this->config['transports'] ?? [
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			$this->config['error_handler'] ?? \WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			$this->config['observability_handler'] ?? \WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_abilities(),
			$this->config['resources'] ?? [],
			$this->get_prompts()
		);
	}

	/**
	 * Get abilities for this server based on configuration
	 *
	 * @return array
	 */
	private function get_abilities(): array {
		$ability_groups = $this->config['ability_groups'] ?? [];

		if ( empty( $ability_groups ) ) {
			return [];
		}

		$abilities = [];
		foreach ( $ability_groups as $group ) {
			$method = "get_{$group}_abilities";
			if ( method_exists( AbilityRegistry::class, $method ) ) {
				$abilities = array_merge( $abilities, AbilityRegistry::$method() );
			}
		}

		return $abilities;
	}

	/**
	 * Get prompts for this server based on configuration
	 *
	 * @return array
	 */
	private function get_prompts(): array {
		return $this->config['prompts'] ?? [];
	}
}
