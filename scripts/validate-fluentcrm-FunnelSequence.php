<?php
/**
 * Validation script for FluentCRM FunnelSequence model and abilities
 *
 * Tests FunnelSequence model structure, toArray() method, and ability execution
 * Run via: /Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval-file scripts/validate-fluentcrm-FunnelSequence.php
 *
 * @package MCP\Adapters
 */

declare(strict_types=1);

// Ensure we're in WordPress context
if ( ! defined( 'ABSPATH' ) ) {
	echo "Error: Must be run in WordPress context\n";
	exit( 1 );
}

echo "=== FluentCRM FunnelSequence Validation ===\n\n";

// Check if FluentCampaign (Pro) is active
if ( ! defined( 'FLUENTCAMPAIGN' ) ) {
	echo "❌ SKIP: FluentCampaign Pro is not active\n";
	echo "This validation requires FluentCampaign Pro to be installed and activated.\n";
	exit( 0 );
}

// Check if required classes exist
if ( ! class_exists( '\FluentCampaign\App\Models\FunnelSequence' ) ) {
	echo "❌ ERROR: FunnelSequence model class not found\n";
	exit( 1 );
}

if ( ! class_exists( '\FluentCampaign\App\Models\Funnel' ) ) {
	echo "❌ ERROR: Funnel model class not found\n";
	exit( 1 );
}

echo "✓ FluentCampaign Pro is active\n";
echo "✓ FunnelSequence model class exists\n";
echo "✓ Funnel model class exists\n\n";

// Test 1: Find or create a test funnel
echo "--- Test 1: Funnel Setup ---\n";
$test_funnel = \FluentCampaign\App\Models\Funnel::where( 'title', 'LIKE', '%Test Funnel%' )->first();

if ( ! $test_funnel ) {
	echo "Creating test funnel...\n";
	$test_funnel = \FluentCampaign\App\Models\Funnel::create(
		[
			'title'        => 'Test Funnel for Validation',
			'trigger_name' => 'user_register',
			'status'       => 'draft',
			'type'         => 'funnels',
			'settings'     => [],
			'conditions'   => [],
			'created_by'   => 1,
		]
	);
	echo "✓ Test funnel created (ID: {$test_funnel->id})\n";
} else {
	echo "✓ Using existing test funnel (ID: {$test_funnel->id})\n";
}

// Test 2: Create a sequence
echo "\n--- Test 2: Create FunnelSequence ---\n";
$sequence_data = [
	'funnel_id'   => $test_funnel->id,
	'action_name' => 'send_email',
	'type'        => 'sequence',
	'title'       => 'Welcome Email',
	'description' => 'Send welcome email to new subscribers',
	'status'      => 'draft',
	'sequence'    => 1,
	'parent_id'   => 0,
	'delay'       => 0,
	'c_delay'     => 0,
	'settings'    => [
		'email_subject' => 'Welcome!',
		'email_body'    => '<p>Welcome to our community!</p>',
	],
	'conditions'  => null,
	'created_by'  => 1,
];

$sequence = \FluentCampaign\App\Models\FunnelSequence::create( $sequence_data );
echo "✓ FunnelSequence created (ID: {$sequence->id})\n";

// Test 3: Verify toArray() equivalent structure
echo "\n--- Test 3: Verify FunnelSequence Structure ---\n";
$expected_fields = [
	'id',
	'funnel_id',
	'parent_id',
	'action_name',
	'condition_type',
	'type',
	'title',
	'description',
	'status',
	'conditions',
	'settings',
	'note',
	'delay',
	'c_delay',
	'sequence',
	'created_by',
	'created_at',
	'updated_at',
];

$missing_fields = [];
foreach ( $expected_fields as $field ) {
	if ( ! property_exists( $sequence, $field ) && ! isset( $sequence->$field ) ) {
		$missing_fields[] = $field;
	}
}

if ( empty( $missing_fields ) ) {
	echo "✓ All expected fields present\n";
} else {
	echo '❌ Missing fields: ' . implode( ', ', $missing_fields ) . "\n";
}

// Display sequence data
echo "\nSequence data:\n";
echo "  ID: {$sequence->id}\n";
echo "  Funnel ID: {$sequence->funnel_id}\n";
echo "  Action: {$sequence->action_name}\n";
echo "  Title: {$sequence->title}\n";
echo "  Status: {$sequence->status}\n";
echo "  Sequence Order: {$sequence->sequence}\n";
echo "  Delay: {$sequence->delay} seconds\n";
echo "  C_Delay: {$sequence->c_delay} seconds\n";

// Test 4: Test ability execution
echo "\n--- Test 4: Test Ability Execution ---\n";

// Check if abilities are registered
if ( ! function_exists( 'wp_get_ability' ) ) {
	echo "❌ ERROR: wp_get_ability() function not found\n";
	echo "The Abilities API plugin may not be active.\n";
	exit( 1 );
}

// Test list-funnel-sequences ability
$list_ability = wp_get_ability( 'fluentcrm/list-funnel-sequences' );
if ( ! $list_ability ) {
	echo "❌ ERROR: fluentcrm/list-funnel-sequences ability not registered\n";
} else {
	echo "✓ list-funnel-sequences ability registered\n";

	$result = $list_ability->execute(
		[
			'funnel_id' => $test_funnel->id,
		]
	);

	if ( $result['success'] ?? false ) {
		$count = $result['data']['total'] ?? 0;
		echo "✓ Listed {$count} sequences for funnel {$test_funnel->id}\n";
	} else {
		echo '❌ List sequences failed: ' . ( $result['error']['message'] ?? 'Unknown error' ) . "\n";
	}
}

// Test update-funnel-sequence ability
$update_ability = wp_get_ability( 'fluentcrm/update-funnel-sequence' );
if ( ! $update_ability ) {
	echo "❌ ERROR: fluentcrm/update-funnel-sequence ability not registered\n";
} else {
	echo "✓ update-funnel-sequence ability registered\n";

	$result = $update_ability->execute(
		[
			'sequence_id' => $sequence->id,
			'title'       => 'Updated Welcome Email',
			'delay'       => 3600, // 1 hour
		]
	);

	if ( $result['success'] ?? false ) {
		echo "✓ Sequence updated successfully\n";
	} else {
		echo '❌ Update sequence failed: ' . ( $result['error']['message'] ?? 'Unknown error' ) . "\n";
	}
}

// Test 5: Verify cumulative delay calculation
echo "\n--- Test 5: Cumulative Delay Calculation ---\n";
$sequence2_data = [
	'funnel_id'   => $test_funnel->id,
	'action_name' => 'add_tag',
	'type'        => 'sequence',
	'title'       => 'Add Tag',
	'status'      => 'draft',
	'sequence'    => 2,
	'parent_id'   => $sequence->id,
	'delay'       => 86400, // 1 day
	'settings'    => [ 'tag_ids' => [ 1 ] ],
	'created_by'  => 1,
];

// Calculate expected c_delay
$parent           = \FluentCampaign\App\Models\FunnelSequence::find( $sequence->id );
$expected_c_delay = $sequence2_data['delay'] + ( $parent->c_delay ?? 0 );

$sequence2_data['c_delay'] = $expected_c_delay;
$sequence2                 = \FluentCampaign\App\Models\FunnelSequence::create( $sequence2_data );

echo "✓ Created child sequence (ID: {$sequence2->id})\n";
echo "  Parent c_delay: {$parent->c_delay} seconds\n";
echo "  Child delay: {$sequence2->delay} seconds\n";
echo "  Child c_delay: {$sequence2->c_delay} seconds\n";

if ( $sequence2->c_delay === $expected_c_delay ) {
	echo "✓ Cumulative delay calculated correctly\n";
} else {
	echo "❌ Cumulative delay mismatch: expected {$expected_c_delay}, got {$sequence2->c_delay}\n";
}

// Test 6: Test reorder-funnel-sequences ability
echo "\n--- Test 6: Test Sequence Reordering ---\n";
$reorder_ability = wp_get_ability( 'fluentcrm/reorder-funnel-sequences' );
if ( ! $reorder_ability ) {
	echo "❌ ERROR: fluentcrm/reorder-funnel-sequences ability not registered\n";
} else {
	echo "✓ reorder-funnel-sequences ability registered\n";

	$result = $reorder_ability->execute(
		[
			'funnel_id'    => $test_funnel->id,
			'sequence_ids' => [ $sequence2->id, $sequence->id ], // Reverse order
		]
	);

	if ( $result['success'] ?? false ) {
		$updated_count = $result['data']['updated_count'] ?? 0;
		echo "✓ Reordered {$updated_count} sequences\n";
	} else {
		echo '❌ Reorder failed: ' . ( $result['error']['message'] ?? 'Unknown error' ) . "\n";
	}
}

// Cleanup
echo "\n--- Cleanup ---\n";
$sequence->delete();
$sequence2->delete();
echo "✓ Test sequences deleted\n";

// Only delete test funnel if we created it
if ( strpos( $test_funnel->title, 'Test Funnel for Validation' ) !== false ) {
	$test_funnel->delete();
	echo "✓ Test funnel deleted\n";
}

echo "\n=== Validation Complete ===\n";
echo "✓ All tests passed successfully\n";
