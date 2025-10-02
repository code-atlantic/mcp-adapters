<?php
declare(strict_types=1);

namespace MCP\Adapters\Admin;

/**
 * MCP Adapters Dashboard Widget
 *
 * Provides a WordPress dashboard widget showing:
 * - MCP server status and available tools
 * - MCP client connections (when implemented)
 * - Quick testing interface for abilities
 */
class DashboardWidget {

	/**
	 * Register dashboard widget hooks
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
	}

	/**
	 * Register the MCP dashboard widget
	 */
	public function register_dashboard_widget(): void {
		wp_add_dashboard_widget(
			'mcp_adapters_status',
			'MCP Adapters Status',
			[ $this, 'render_dashboard_widget' ],
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render the dashboard widget content
	 */
	public function render_dashboard_widget(): void {
		?>
		<div class="mcp-dashboard-widget">
			<style>
				.mcp-dashboard-widget { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
				.mcp-status-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #dcdcde; }
				.mcp-status-section:last-child { border-bottom: none; margin-bottom: 0; }
				.mcp-status-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
				.mcp-status-header h4 { margin: 0; font-size: 14px; font-weight: 600; }
				.mcp-status-badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 500; }
				.mcp-status-badge.active { background: #d5e8d4; color: #1d4620; }
				.mcp-status-badge.inactive { background: #f4f5f7; color: #50575e; }
				.mcp-server-list { margin: 0; padding: 0; list-style: none; }
				.mcp-server-item { padding: 8px 0; display: flex; justify-content: space-between; align-items: center; }
				.mcp-server-item:not(:last-child) { border-bottom: 1px solid #f0f0f1; }
				.mcp-server-name { font-weight: 500; color: #1d2327; }
				.mcp-server-meta { font-size: 12px; color: #646970; }
				.mcp-tool-count { background: #f0f6fc; color: #0969da; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; }
				.mcp-quick-test { margin-top: 15px; padding-top: 15px; border-top: 1px solid #dcdcde; }
				.mcp-test-form { display: flex; gap: 8px; align-items: center; }
				.mcp-test-select { flex: 1; }
				.mcp-test-result { margin-top: 10px; padding: 10px; border-radius: 4px; font-size: 13px; display: none; }
				.mcp-test-result.success { background: #d5e8d4; color: #1d4620; }
				.mcp-test-result.error { background: #ffe5e5; color: #8a0000; }
			</style>

			<?php
			$servers = $this->get_registered_servers();
			$clients = $this->get_registered_clients();
			?>

			<!-- MCP Servers Section -->
			<div class="mcp-status-section">
				<div class="mcp-status-header">
					<h4>🔌 MCP Servers</h4>
					<span class="mcp-status-badge <?php echo ! empty( $servers ) ? 'active' : 'inactive'; ?>">
						<?php echo count( $servers ); ?> Active
					</span>
				</div>
				<?php if ( ! empty( $servers ) ) : ?>
					<ul class="mcp-server-list">
						<?php foreach ( $servers as $server ) : ?>
							<li class="mcp-server-item">
								<div>
									<div class="mcp-server-name"><?php echo esc_html( $server['name'] ); ?></div>
									<div class="mcp-server-meta">
										<?php echo esc_html( $server['endpoint'] ); ?>
									</div>
								</div>
								<span class="mcp-tool-count"><?php echo esc_html( $server['tool_count'] ); ?> tools</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p style="color: #646970; margin: 0;">No MCP servers registered</p>
				<?php endif; ?>
			</div>

			<!-- MCP Clients Section -->
			<div class="mcp-status-section">
				<div class="mcp-status-header">
					<h4>🌐 MCP Clients</h4>
					<span class="mcp-status-badge <?php echo ! empty( $clients ) ? 'active' : 'inactive'; ?>">
						<?php echo count( $clients ); ?> Connected
					</span>
				</div>
				<?php if ( ! empty( $clients ) ) : ?>
					<ul class="mcp-server-list">
						<?php foreach ( $clients as $client ) : ?>
							<li class="mcp-server-item">
								<div>
									<div class="mcp-server-name"><?php echo esc_html( $client['name'] ); ?></div>
									<div class="mcp-server-meta"><?php echo esc_html( $client['url'] ); ?></div>
								</div>
								<span class="mcp-tool-count"><?php echo esc_html( $client['tool_count'] ); ?> tools</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p style="color: #646970; margin: 0;">No MCP clients connected</p>
				<?php endif; ?>
			</div>

			<!-- Quick Test Section -->
			<div class="mcp-quick-test">
				<h4 style="margin: 0 0 10px 0; font-size: 14px;">🧪 Quick Test</h4>
				<div class="mcp-test-form">
					<select id="mcp-test-ability" class="mcp-test-select">
						<option value="">Select an ability to test...</option>
						<?php foreach ( $this->get_test_abilities() as $ability ) : ?>
							<option value="<?php echo esc_attr( $ability['name'] ); ?>">
								<?php echo esc_html( $ability['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-primary" id="mcp-test-run">Test</button>
				</div>
				<div id="mcp-test-result" class="mcp-test-result"></div>
			</div>

			<script>
			(function($) {
				$('#mcp-test-run').on('click', function() {
					var ability = $('#mcp-test-ability').val();
					var $result = $('#mcp-test-result');

					if (!ability) {
						$result.removeClass('success').addClass('error')
							.text('Please select an ability to test').fadeIn();
						return;
					}

					$result.hide();
					$(this).prop('disabled', true).text('Testing...');

					$.ajax({
						url: ajaxurl,
						method: 'POST',
						data: {
							action: 'mcp_test_ability',
							ability: ability,
							nonce: '<?php echo esc_js( wp_create_nonce( 'mcp_test_ability' ) ); ?>'
						},
						success: function(response) {
							if (response.success) {
								$result.removeClass('error').addClass('success')
									.html('<strong>✓ Success:</strong> ' + response.data.message).fadeIn();
							} else {
								$result.removeClass('success').addClass('error')
									.html('<strong>✗ Error:</strong> ' + response.data).fadeIn();
							}
						},
						error: function() {
							$result.removeClass('success').addClass('error')
								.text('Request failed. Please try again.').fadeIn();
						},
						complete: function() {
							$('#mcp-test-run').prop('disabled', false).text('Test');
						}
					});
				});
			})(jQuery);
			</script>
		</div>
		<?php
	}

	/**
	 * Get registered MCP servers with their tool counts
	 *
	 * @return array Server information
	 */
	private function get_registered_servers(): array {
		$servers = [];

		// Check for FluentBoards servers
		if ( defined( 'FLUENT_BOARDS' ) ) {
			$servers[] = [
				'name'       => 'FluentBoards Complete',
				'endpoint'   => '/fluentboards/mcp',
				'tool_count' => 83,
			];

			$servers[] = [
				'name'       => 'FluentBoards Board CRUD',
				'endpoint'   => '/fluentboards-board-crud/mcp',
				'tool_count' => 10,
			];
		}

		// All abilities server
		$servers[] = [
			'name'       => 'All WordPress Abilities',
			'endpoint'   => '/mcp-all/abilities',
			'tool_count' => 83,
		];

		return apply_filters( 'mcp_adapters_dashboard_servers', $servers );
	}

	/**
	 * Get registered MCP clients
	 *
	 * @return array Client information
	 */
	private function get_registered_clients(): array {
		$clients = [];

		if ( class_exists( '\MCP\Adapters\Core\McpClientManager' ) ) {
			$client_statuses = \MCP\Adapters\Core\McpClientManager::get_client_status();

			foreach ( $client_statuses as $client_id => $status ) {
				$clients[] = [
					'name'       => ucwords( str_replace( '-', ' ', $client_id ) ),
					'url'        => $status['url'],
					'tool_count' => $status['tools'] + $status['resources'] + $status['prompts'],
				];
			}
		}

		return apply_filters( 'mcp_adapters_dashboard_clients', $clients );
	}

	/**
	 * Get test abilities for quick testing
	 *
	 * @return array Test abilities
	 */
	private function get_test_abilities(): array {
		$abilities = [];

		if ( defined( 'FLUENT_BOARDS' ) ) {
			$abilities[] = [
				'name'  => 'fluentboards/test-verbose-enum',
				'label' => 'Test Verbose Enum (solid_5)',
			];

			$abilities[] = [
				'name'  => 'fluentboards/test-pattern-enum',
				'label' => 'Test Pattern Enum (gradient_3)',
			];

			$abilities[] = [
				'name'  => 'fluentboards/generate-planet-verbose',
				'label' => 'Generate Planet - Verbose (Mars/rock_5)',
			];

			$abilities[] = [
				'name'  => 'fluentboards/generate-planet-pattern',
				'label' => 'Generate Planet - Pattern (Jupiter/gas_3)',
			];
		}

		return $abilities;
	}
}
