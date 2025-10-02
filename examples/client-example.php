<?php
/**
 * MCP Client Usage Example
 *
 * This file demonstrates how to register external MCP servers as WordPress abilities
 * using the MCP Adapters plugin.
 *
 * @package MCP\Adapters
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Example 1: Basic client connection
 *
 * Connects to an external MCP server and auto-registers all its tools, resources,
 * and prompts as WordPress abilities with the namespace: mcp_{client_id}/tool-name
 */
add_action( 'mcp_client_init', function ( $manager ) {
	// Example: WordPress.com Domains MCP Server
	$client = $manager->create_client(
		'wpcom-domains',
		'https://wpcom-domains-mcp.a8cai.workers.dev/mcp',
		[
			'timeout' => 30,
		]
	);

	if ( $client ) {
		// Client connected successfully
		// Remote tools are now available as WordPress abilities
		// Example: mcp_wpcom-domains/check-domain
	}
});

/**
 * Example 2: Authenticated client connection
 *
 * Demonstrates various authentication methods
 */
add_action( 'mcp_client_init', function ( $manager ) {
	// Bearer token authentication
	$client = $manager->create_client(
		'my-service',
		'https://api.example.com/mcp',
		[
			'auth'    => [
				'type'  => 'bearer',
				'token' => 'your-api-token-here',
			],
			'timeout' => 30,
		]
	);

	// Or API key authentication
	// $client = $manager->create_client(
	// 'my-service',
	// 'https://api.example.com/mcp',
	// [
	// 'auth' => [
	// 'type' => 'api_key',
	// 'key'  => 'your-api-key-here',
	// ],
	// ]
	// );

	// Or Basic authentication
	// $client = $manager->create_client(
	// 'my-service',
	// 'https://api.example.com/mcp',
	// [
	// 'auth' => [
	// 'type'     => 'basic',
	// 'username' => 'your-username',
	// 'password' => 'your-password',
	// ],
	// ]
	// );
});

/**
 * Example 3: Custom permission control
 *
 * Restrict access to MCP client abilities based on user capabilities
 */
add_filter( 'mcp_client_permission', function ( $allowed, $client_id ) {
	// Only allow editors and above to use external MCP tools
	return current_user_can( 'edit_others_posts' );
}, 10, 2 );

/**
 * Example 4: Using remote tools programmatically
 *
 * Once a client is registered, its tools are available as WordPress abilities
 */
add_action( 'init', function () {
	// Check if the ability exists
	$ability = wp_get_ability( 'mcp_wpcom-domains/check-domain' );

	if ( $ability ) {
		// Execute the remote tool
		$result = $ability->execute( [
			'domain' => 'example.com',
		] );

		if ( ! is_wp_error( $result ) ) {
			// Handle successful result
			// $result contains the response from the remote MCP server
		}
	}
}, 20 );

/**
 * Example 5: Multiple clients
 *
 * You can register multiple external MCP servers
 */
add_action( 'mcp_client_init', function ( $manager ) {
	// Client 1: AI Service
	$manager->create_client(
		'ai-service',
		'https://ai.example.com/mcp',
		[
			'auth' => [
				'type'  => 'bearer',
				'token' => getenv( 'AI_SERVICE_TOKEN' ),
			],
		]
	);

	// Client 2: Data Service
	$manager->create_client(
		'data-service',
		'https://data.example.com/mcp',
		[
			'auth' => [
				'type' => 'api_key',
				'key'  => getenv( 'DATA_SERVICE_KEY' ),
			],
		]
	);

	// Now you have abilities like:
	// - mcp_ai-service/generate-text
	// - mcp_data-service/fetch-analytics
	// etc.
});

/**
 * Example 6: Checking client status
 *
 * Get information about registered clients
 */
add_action( 'admin_init', function () {
	if ( ! class_exists( '\MCP\Adapters\Core\McpClientManager' ) ) {
		return;
	}

	// Get all client statuses
	$statuses = \MCP\Adapters\Core\McpClientManager::get_client_status();

	foreach ( $statuses as $client_id => $status ) {
		error_log( sprintf(
			'MCP Client: %s | Connected: %s | Tools: %d',
			$client_id,
			$status['connected'] ? 'Yes' : 'No',
			$status['tools']
		) );
	}
});
