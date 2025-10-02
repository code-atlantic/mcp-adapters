<?php
declare(strict_types=1);

namespace MCP\Adapters\Adapters;

use MCP\Adapters\Adapters\FluentBoards\Servers\AbilityRegistry;

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
			$all_abilities = array_merge(
				$all_abilities,
				AbilityRegistry::get_board_abilities(),
				AbilityRegistry::get_board_member_abilities(),
				AbilityRegistry::get_task_abilities(),
				AbilityRegistry::get_stage_abilities(),
				AbilityRegistry::get_comment_abilities(),
				AbilityRegistry::get_label_abilities(),
				AbilityRegistry::get_attachment_abilities(),
				AbilityRegistry::get_user_abilities(),
				AbilityRegistry::get_reporting_abilities(),
				AbilityRegistry::get_test_abilities()
			);
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
			$all_prompts = array_merge(
				$all_prompts,
				[
					'fluentboards/project-overview',
					'fluentboards/analyze-workflow',
					'fluentboards/status-checkin',
					'fluentboards/team-productivity',
				]
			);
		}

		// Hook for other adapters to add their prompts
		$all_prompts = apply_filters( 'mcp_adapters_all_prompts', $all_prompts );

		return $all_prompts;
	}
}
