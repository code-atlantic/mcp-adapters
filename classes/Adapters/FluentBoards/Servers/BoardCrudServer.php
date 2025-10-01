<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters\FluentBoards\Servers;

/**
 * FluentBoards Board CRUD MCP Server
 *
 * Focused server providing only board management operations (CRUD + structure).
 * Excludes task management, comments, attachments, and reporting features.
 */
class BoardCrudServer {

	/**
	 * Register board CRUD server with MCP adapter
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter MCP adapter instance
	 */
	public function register_with_adapter( $adapter ): void {
		$adapter->create_server(
			'fluentboards-board-crud', // $server_id
			'fluentboards-board-crud', // $server_route_namespace
			'mcp', // $server_route
			'FluentBoards Board CRUD', // $server_name
			'Board management operations only - create, read, update, delete, pin, archive, duplicate boards', // $server_description
			'0.1.0', // $server_version
			[
				\WP\MCP\Transport\Http\RestTransport::class, // $mcp_transports
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // $error_handler
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // $observability_handler
			[
				// Core CRUD operations
				'fluentboards/list-boards',
				'fluentboards/get-board',
				'fluentboards/create-board',
				'fluentboards/update-board',
				'fluentboards/delete-board',

				// Board structure operations
				'fluentboards/pin-board',
				'fluentboards/unpin-board',
				'fluentboards/archive-board',
				'fluentboards/restore-board',
				'fluentboards/duplicate-board',
			]
		);
	}
}
