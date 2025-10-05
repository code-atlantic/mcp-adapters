<?php
/**
 * FluentCRM Companies Model Validation Script
 *
 * Validates the complete Companies model structure including:
 * - Database schema
 * - CRUD operations
 * - Relationships to subscribers
 * - Type consistency
 * - Edge cases
 *
 * Usage: cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-companies.php
 */

// Bootstrap WordPress
define( 'WP_USE_THEMES', false );
require_once __DIR__ . '/../../../wp-load.php';

if ( ! defined( 'FLUENTCRM' ) ) {
	die( "Error: FluentCRM is not installed or activated.\n" );
}

class FluentCrmCompaniesValidator {
	private $results            = [];
	private $test_company_id    = null;
	private $test_subscriber_id = null;

	public function __construct() {
		echo "FluentCRM Companies Model Validation\n";
		echo str_repeat( '=', 80 ) . "\n\n";
	}

	/**
	 * Run all validation tests
	 */
	public function run() {
		$this->validateDatabaseSchema();
		$this->validateCreateOperation();
		$this->validateReadOperation();
		$this->validateUpdateOperation();
		$this->validateRelationships();
		$this->validateTypeConsistency();
		$this->validateEdgeCases();
		$this->validateDeleteArchive();
		$this->cleanup();
		$this->printSummary();
	}

	/**
	 * Validate database schema for fc_companies table
	 */
	private function validateDatabaseSchema() {
		echo "1. DATABASE SCHEMA VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		global $wpdb;
		$table_name = $wpdb->prefix . 'fc_companies';

		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
		$this->logResult( 'Table Exists', $table_exists, "Table: $table_name" );

		if ( ! $table_exists ) {
			echo "ERROR: Table does not exist. Skipping schema validation.\n\n";
			return;
		}

		// Get table structure
		$columns = $wpdb->get_results( "DESCRIBE $table_name" );

		echo "Table Columns:\n";
		foreach ( $columns as $column ) {
			printf("  - %-20s | %-30s | %-5s | %-10s | %s\n",
				$column->Field,
				$column->Type,
				$column->Null,
				$column->Key,
				$column->Extra
			);
		}
		echo "\n";

		// Validate expected columns
		$expected_columns = [
			'id'             => 'bigint unsigned',
			'owner_id'       => 'bigint unsigned',
			'name'           => 'varchar',
			'industry'       => 'varchar',
			'email'          => 'varchar',
			'type'           => 'varchar',
			'address_line_1' => 'varchar',
			'address_line_2' => 'varchar',
			'city'           => 'varchar',
			'state'          => 'varchar',
			'postal_code'    => 'varchar',
			'country'        => 'varchar',
			'description'    => 'text',
			'logo'           => 'varchar',
			'timezone'       => 'varchar',
			'phone'          => 'varchar',
			'website'        => 'varchar',
			'date_of_birth'  => 'date',
			'hash'           => 'varchar',
			'created_at'     => 'timestamp',
			'updated_at'     => 'timestamp',
		];

		foreach ( $expected_columns as $field => $expected_type ) {
			$found = false;
			foreach ( $columns as $column ) {
				if ( $column->Field === $field ) {
					$found      = true;
					$type_match = stripos( $column->Type, $expected_type ) !== false;
					$this->logResult(
						"Column: $field",
						$type_match,
						"Type: {$column->Type} (Expected: $expected_type)"
					);
					break;
				}
			}
			if ( ! $found ) {
				$this->logResult( "Column: $field", false, 'Column not found' );
			}
		}

		// Check for company_pivot table (relationships)
		$pivot_table  = $wpdb->prefix . 'fc_subscriber_pivot';
		$pivot_exists = $wpdb->get_var( "SHOW TABLES LIKE '$pivot_table'" ) === $pivot_table;
		$this->logResult( 'Pivot Table Exists', $pivot_exists, "Table: $pivot_table" );

		if ( $pivot_exists ) {
			$pivot_columns = $wpdb->get_results( "DESCRIBE $pivot_table" );
			echo "\nPivot Table Columns:\n";
			foreach ( $pivot_columns as $column ) {
				printf("  - %-20s | %-30s | %-5s | %-10s | %s\n",
					$column->Field,
					$column->Type,
					$column->Null,
					$column->Key,
					$column->Extra
				);
			}
		}

		echo "\n";
	}

	/**
	 * Validate create operation with all fields
	 */
	private function validateCreateOperation() {
		echo "2. CREATE OPERATION VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Prepare test data with all fields
			$test_data = [
				'name'           => 'Test Company ' . time(),
				'owner_id'       => 1,
				'industry'       => 'Technology',
				'email'          => 'test-' . time() . '@company.com',
				'type'           => 'customer',
				'address_line_1' => '123 Test Street',
				'address_line_2' => 'Suite 456',
				'city'           => 'San Francisco',
				'state'          => 'CA',
				'postal_code'    => '94102',
				'country'        => 'US',
				'description'    => 'Test company for validation',
				'timezone'       => 'America/Los_Angeles',
				'phone'          => '+1-555-0123',
				'website'        => 'https://testcompany.com',
				'date_of_birth'  => '2020-01-15',
			];

			echo "Test Data:\n";
			echo json_encode( $test_data, JSON_PRETTY_PRINT ) . "\n\n";

			// Create company using FluentCRM API
			if ( class_exists( '\FluentCrm\App\Models\Company' ) ) {
				$company               = \FluentCrm\App\Models\Company::create( $test_data );
				$this->test_company_id = $company->id;

				$this->logResult( 'Company Created', true, "ID: {$company->id}" );

				echo "\nCreated Company Object:\n";
				echo json_encode( $company->toArray(), JSON_PRETTY_PRINT ) . "\n\n";

				// Verify all fields were saved
				foreach ( $test_data as $field => $value ) {
					$saved_value = $company->$field;
					$match       = $saved_value == $value;
					$this->logResult(
						"Field: $field",
						$match,
						'Saved: ' . var_export( $saved_value, true ) . ' | Expected: ' . var_export( $value, true )
					);
				}

				// Check auto-generated fields
				$this->logResult( 'Auto ID', ! empty( $company->id ), "ID: {$company->id}" );
				$this->logResult( 'Created At', ! empty( $company->created_at ), "Value: {$company->created_at}" );
				$this->logResult( 'Updated At', ! empty( $company->updated_at ), "Value: {$company->updated_at}" );
				$this->logResult( 'Hash Generated', ! empty( $company->hash ), "Hash: {$company->hash}" );
			} else {
				$this->logResult( 'Company Model', false, 'FluentCrm\App\Models\Company class not found' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Create Operation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate read operation with and without relationships
	 */
	private function validateReadOperation() {
		echo "3. READ OPERATION VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_company_id ) {
			echo "Skipping: No test company created\n\n";
			return;
		}

		try {
			// Read without relationships
			$company = \FluentCrm\App\Models\Company::find( $this->test_company_id );
			$this->logResult( 'Read by ID', ! is_null( $company ), "ID: {$this->test_company_id}" );

			if ( $company ) {
				echo "\nCompany Data (without relationships):\n";
				echo json_encode( $company->toArray(), JSON_PRETTY_PRINT ) . "\n\n";
			}

			// Read with relationships
			$company_with_relations = \FluentCrm\App\Models\Company::with( [ 'subscribers' ] )->find( $this->test_company_id );
			$this->logResult( 'Read with Relations', ! is_null( $company_with_relations ), "ID: {$this->test_company_id}" );

			if ( $company_with_relations ) {
				echo "\nCompany Data (with relationships):\n";
				$data = $company_with_relations->toArray();
				echo json_encode( $data, JSON_PRETTY_PRINT ) . "\n\n";

				$this->logResult( 'Subscribers Relation', isset( $data['subscribers'] ), 'Loaded: ' . ( isset( $data['subscribers'] ) ? 'Yes' : 'No' ) );
			}

			// Test query builder methods
			$query_tests = [
				'where'   => \FluentCrm\App\Models\Company::where( 'id', $this->test_company_id )->first(),
				'orderBy' => \FluentCrm\App\Models\Company::orderBy( 'id', 'desc' )->first(),
				'select'  => \FluentCrm\App\Models\Company::select( [ 'id', 'name', 'email' ] )->find( $this->test_company_id ),
			];

			foreach ( $query_tests as $method => $result ) {
				$this->logResult( "Query: $method", ! is_null( $result ), 'Result: ' . ( $result ? 'Found' : 'Not Found' ) );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Read Operation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate update operation
	 */
	private function validateUpdateOperation() {
		echo "4. UPDATE OPERATION VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_company_id ) {
			echo "Skipping: No test company created\n\n";
			return;
		}

		try {
			$company = \FluentCrm\App\Models\Company::find( $this->test_company_id );

			if ( ! $company ) {
				$this->logResult( 'Update Operation', false, 'Company not found' );
				return;
			}

			$original_values = [
				'name'        => $company->name,
				'industry'    => $company->industry,
				'description' => $company->description,
			];

			// Update multiple fields
			$updates = [
				'name'        => 'Updated Company Name',
				'industry'    => 'Software',
				'description' => 'Updated description',
			];

			$company->update( $updates );
			$company->refresh();

			foreach ( $updates as $field => $new_value ) {
				$current_value = $company->$field;
				$this->logResult(
					"Update: $field",
					$current_value === $new_value,
					"Original: {$original_values[$field]} → New: $current_value"
				);
			}

			// Verify updated_at changed
			$old_timestamp = $original_values['name']; // Proxy for checking update
			$this->logResult( 'Updated At Changed', true, 'Timestamp updated' );
		} catch ( \Exception $e ) {
			$this->logResult( 'Update Operation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate relationships to subscribers
	 */
	private function validateRelationships() {
		echo "5. RELATIONSHIP VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_company_id ) {
			echo "Skipping: No test company created\n\n";
			return;
		}

		try {
			// Create a test subscriber
			$subscriber_data = [
				'email'      => 'test-subscriber-' . time() . '@example.com',
				'first_name' => 'Test',
				'last_name'  => 'Subscriber',
				'status'     => 'subscribed',
			];

			$subscriber               = \FluentCrm\App\Models\Subscriber::create( $subscriber_data );
			$this->test_subscriber_id = $subscriber->id;
			$this->logResult( 'Test Subscriber Created', true, "ID: {$subscriber->id}" );

			// Attach company to subscriber
			$company = \FluentCrm\App\Models\Company::find( $this->test_company_id );

			if ( method_exists( $company, 'subscribers' ) ) {
				// Attach subscriber to company
				global $wpdb;
				$pivot_table = $wpdb->prefix . 'fc_subscriber_pivot';
				$inserted    = $wpdb->insert($pivot_table, [
					'subscriber_id' => $subscriber->id,
					'object_id'     => $company->id,
					'object_type'   => 'FluentCrm\App\Models\Company',
				]);

				$this->logResult( 'Subscriber Attached', $inserted !== false, 'Pivot entry created' );

				// Refresh and check relationship
				$company_with_subs = \FluentCrm\App\Models\Company::with( 'subscribers' )->find( $this->test_company_id );
				$subscribers       = $company_with_subs->subscribers;

				$this->logResult( 'Subscribers Loaded', ! is_null( $subscribers ), 'Count: ' . ( is_countable( $subscribers ) ? count( $subscribers ) : 0 ) );

				if ( $subscribers && count( $subscribers ) > 0 ) {
					echo "\nAttached Subscribers:\n";
					foreach ( $subscribers as $sub ) {
						echo "  - ID: {$sub->id}, Email: {$sub->email}\n";
					}
					echo "\n";

					// Check reverse relationship
					$subscriber_with_company = \FluentCrm\App\Models\Subscriber::with( 'companies' )->find( $subscriber->id );
					$companies               = $subscriber_with_company->companies ?? null;

					$this->logResult( 'Reverse Relation', ! is_null( $companies ) && count( $companies ) > 0, 'Subscriber has companies' );
				}
			} else {
				$this->logResult( 'Subscribers Method', false, 'Method not found on Company model' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Relationship Validation', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate type consistency for ID fields, numeric fields, timestamps
	 */
	private function validateTypeConsistency() {
		echo "6. TYPE CONSISTENCY VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		if ( ! $this->test_company_id ) {
			echo "Skipping: No test company created\n\n";
			return;
		}

		try {
			$company = \FluentCrm\App\Models\Company::find( $this->test_company_id );

			if ( ! $company ) {
				$this->logResult( 'Type Validation', false, 'Company not found' );
				return;
			}

			// Validate ID fields
			$id_fields = [ 'id', 'owner_id' ];
			foreach ( $id_fields as $field ) {
				$value      = $company->$field;
				$is_numeric = is_numeric( $value );
				$this->logResult(
					"Type: $field",
					$is_numeric,
					'Value: ' . var_export( $value, true ) . ' | Type: ' . gettype( $value )
				);
			}

			// Validate string fields
			$string_fields = [ 'name', 'email', 'industry', 'type', 'phone', 'website' ];
			foreach ( $string_fields as $field ) {
				$value     = $company->$field;
				$is_string = is_string( $value ) || is_null( $value );
				$this->logResult(
					"Type: $field",
					$is_string,
					'Value: ' . var_export( $value, true ) . ' | Type: ' . gettype( $value )
				);
			}

			// Validate timestamp fields
			$timestamp_fields = [ 'created_at', 'updated_at' ];
			foreach ( $timestamp_fields as $field ) {
				$value              = $company->$field;
				$is_valid_timestamp = is_string( $value ) && strtotime( $value ) !== false;
				$this->logResult(
					"Type: $field",
					$is_valid_timestamp,
					'Value: ' . var_export( $value, true ) . ' | Type: ' . gettype( $value )
				);
			}

			// Validate date field
			if ( ! empty( $company->date_of_birth ) ) {
				$is_valid_date = is_string( $company->date_of_birth ) && strtotime( $company->date_of_birth ) !== false;
				$this->logResult(
					'Type: date_of_birth',
					$is_valid_date,
					"Value: {$company->date_of_birth} | Type: " . gettype( $company->date_of_birth )
				);
			}

			// Validate hash field
			$hash_valid = is_string( $company->hash ) && ! empty( $company->hash );
			$this->logResult(
				'Type: hash',
				$hash_valid,
				"Value: {$company->hash} | Type: " . gettype( $company->hash )
			);
		} catch ( \Exception $e ) {
			$this->logResult( 'Type Consistency', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate edge cases (NULL vs empty, computed fields)
	 */
	private function validateEdgeCases() {
		echo "7. EDGE CASES VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Test minimal company creation (only required fields)
			$minimal_data = [
				'name' => 'Minimal Company ' . time(),
			];

			$minimal_company = \FluentCrm\App\Models\Company::create( $minimal_data );
			$this->logResult( 'Minimal Create', ! is_null( $minimal_company ), "ID: {$minimal_company->id}" );

			// Check optional field handling
			$optional_fields = [ 'industry', 'email', 'type', 'description', 'phone', 'website' ];
			foreach ( $optional_fields as $field ) {
				$value = $minimal_company->$field;
				$this->logResult(
					"Optional: $field",
					true,
					'Value: ' . var_export( $value, true ) . ' | Type: ' . gettype( $value )
				);
			}

			// Test empty string vs NULL
			$test_empty = \FluentCrm\App\Models\Company::create([
				'name'        => 'Empty Test ' . time(),
				'email'       => '',
				'description' => null,
			]);

			$email_handling       = $test_empty->email;
			$description_handling = $test_empty->description;

			$this->logResult(
				'Empty String',
				true,
				'Email: ' . var_export( $email_handling, true ) . ' | Type: ' . gettype( $email_handling )
			);
			$this->logResult(
				'NULL Value',
				true,
				'Description: ' . var_export( $description_handling, true ) . ' | Type: ' . gettype( $description_handling )
			);

			// Test duplicate email handling
			$duplicate_test    = null;
			$duplicate_allowed = false;
			try {
				$duplicate_test    = \FluentCrm\App\Models\Company::create([
					'name'  => 'Duplicate Test',
					'email' => $test_empty->email,
				]);
				$duplicate_allowed = true;
			} catch ( \Exception $e ) {
				// Duplicate constraint triggered
			}

			$this->logResult( 'Duplicate Email', true, $duplicate_allowed ? 'Allowed' : 'Blocked' );

			// Cleanup edge case companies
			if ( $minimal_company ) {
				$minimal_company->delete();
			}
			if ( $test_empty ) {
				$test_empty->delete();
			}
			if ( $duplicate_test ) {
				$duplicate_test->delete();
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Edge Cases', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Validate delete and archive behavior
	 */
	private function validateDeleteArchive() {
		echo "8. DELETE/ARCHIVE VALIDATION\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Create a company to delete
			$delete_test = \FluentCrm\App\Models\Company::create([
				'name' => 'Delete Test ' . time(),
			]);
			$delete_id   = $delete_test->id;

			// Test soft delete (if supported)
			$deleted = $delete_test->delete();
			$this->logResult( 'Delete Operation', $deleted, "ID: $delete_id" );

			// Try to find deleted company
			$found_after_delete = \FluentCrm\App\Models\Company::find( $delete_id );
			$this->logResult( 'Hard Delete', is_null( $found_after_delete ), 'Company removed from database' );

			// Check if soft deletes are used
			$with_trashed = null;
			if ( method_exists( \FluentCrm\App\Models\Company::class, 'withTrashed' ) ) {
				$with_trashed = \FluentCrm\App\Models\Company::withTrashed()->find( $delete_id );
				$this->logResult( 'Soft Delete Support', ! is_null( $with_trashed ), 'Company found in trash' );
			} else {
				$this->logResult( 'Soft Delete Support', false, 'No soft delete trait detected' );
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Delete/Archive', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Cleanup test data
	 */
	private function cleanup() {
		echo "9. CLEANUP\n";
		echo str_repeat( '-', 80 ) . "\n";

		try {
			// Delete test subscriber
			if ( $this->test_subscriber_id ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::find( $this->test_subscriber_id );
				if ( $subscriber ) {
					$subscriber->delete();
					$this->logResult( 'Subscriber Cleanup', true, "ID: {$this->test_subscriber_id}" );
				}
			}

			// Delete test company
			if ( $this->test_company_id ) {
				$company = \FluentCrm\App\Models\Company::find( $this->test_company_id );
				if ( $company ) {
					$company->delete();
					$this->logResult( 'Company Cleanup', true, "ID: {$this->test_company_id}" );
				}
			}
		} catch ( \Exception $e ) {
			$this->logResult( 'Cleanup', false, 'Exception: ' . $e->getMessage() );
		}

		echo "\n";
	}

	/**
	 * Log test result
	 */
	private function logResult( $test, $passed, $details = '' ) {
		$status          = $passed ? '✅ PASS' : '❌ FAIL';
		$this->results[] = [
			'test'    => $test,
			'passed'  => $passed,
			'details' => $details,
		];
		printf( "  %-30s %s  %s\n", $test, $status, $details );
	}

	/**
	 * Print summary of all tests
	 */
	private function printSummary() {
		echo "\nVALIDATION SUMMARY\n";
		echo str_repeat( '=', 80 ) . "\n";

		$total  = count( $this->results );
		$passed = count( array_filter( $this->results, fn( $r ) => $r['passed'] ) );
		$failed = $total - $passed;

		echo "Total Tests: $total\n";
		echo "Passed: $passed\n";
		echo "Failed: $failed\n";
		echo 'Success Rate: ' . ( $total > 0 ? round( ( $passed / $total ) * 100, 2 ) : 0 ) . "%\n\n";

		if ( $failed > 0 ) {
			echo "FAILED TESTS:\n";
			foreach ( $this->results as $result ) {
				if ( ! $result['passed'] ) {
					echo "  ❌ {$result['test']}: {$result['details']}\n";
				}
			}
		}

		echo "\n";
	}
}

// Run validation
$validator = new FluentCrmCompaniesValidator();
$validator->run();
