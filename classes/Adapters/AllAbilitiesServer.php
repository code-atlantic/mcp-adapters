<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters;

use MCP\Adapters\Adapters\FluentBoards\Servers\FullFluentBoardsServer;

/**
 * All Abilities MCP Server
 *
 * Universal server that exposes ALL registered WordPress abilities from all adapters.
 * Useful for testing and development - provides complete access to every ability
 * registered through the WordPress Abilities API.
 */
class AllAbilitiesServer {

	/**
	 * Register all-abilities server with MCP adapter
	 *
	 * @param object $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'all-abilities',
			'mcp-all',
			'abilities',
			'All WordPress Abilities',
			'Universal server exposing all registered WordPress abilities from all active adapters',
			'0.1.0',
			[
				\WP\MCP\Transport\Http\RestTransport::class,
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$this->get_all_abilities(),
			[], // Resources - none currently
			$this->get_all_prompts()
		);
	}

	/**
	 * Get all abilities from all active adapters
	 *
	 * @return array List of all ability names
	 */
	private function get_all_abilities(): array {
		$all_abilities = [];

		// FluentBoards abilities (if active)
		if ( defined( 'FLUENT_BOARDS' ) && class_exists( '\FluentBoards\App\Models\Board' ) ) {
			$server        = new FullFluentBoardsServer();
			$all_abilities = array_merge( $all_abilities, $this->call_private_method( $server, 'get_all_abilities' ) );
		}

		// Hook for other adapters to add their abilities
		$all_abilities = apply_filters( 'mcp_adapters_all_abilities', $all_abilities );

		return $all_abilities;
	}

	/**
	 * Get all prompts from all active adapters
	 *
	 * @return array List of all prompt ability names
	 */
	private function get_all_prompts(): array {
		$all_prompts = [];

		// FluentBoards prompts (if active)
		if ( defined( 'FLUENT_BOARDS' ) && class_exists( '\FluentBoards\App\Models\Board' ) ) {
			$server      = new FullFluentBoardsServer();
			$all_prompts = array_merge( $all_prompts, $this->call_private_method( $server, 'get_all_prompts' ) );
		}

		// Hook for other adapters to add their prompts
		$all_prompts = apply_filters( 'mcp_adapters_all_prompts', $all_prompts );

		return $all_prompts;
	}

	/**
	 * Call private method using reflection
	 *
	 * @param object $object The object instance.
	 * @param string $method The method name.
	 * @return mixed Method return value.
	 */
	private function call_private_method( $object, string $method ) {
		$reflection = new \ReflectionClass( $object );
		$method_obj = $reflection->getMethod( $method );
		$method_obj->setAccessible( true );
		return $method_obj->invoke( $object );
	}
}
