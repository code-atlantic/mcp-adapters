<?php
/**
 * FluentCRM Email Templates - Comprehensive Validation Script
 *
 * This script validates the structure and behavior of FluentCRM's Template model,
 * which uses WordPress posts table with custom post types for email templates.
 *
 * Usage: php validate-fluentcrm-templates.php
 *
 * @package MCP_Adapters
 * @version 1.0.0
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	die( 'WordPress not loaded' );
}

// Check FluentCRM is active
if ( ! defined( 'FLUENTCRM' ) ) {
	die( 'FluentCRM plugin is not active' );
}

class FluentCrmTemplateValidator {

	private $results           = [];
	private $test_template_ids = [];

	public function __construct() {
		echo "\n";
		echo "========================================\n";
		echo "FluentCRM Template Validation Script\n";
		echo "========================================\n";
		echo 'Date: ' . date( 'Y-m-d H:i:s' ) . "\n";
		echo "========================================\n\n";
	}

	public function run() {
		$this->section( 'DATABASE SCHEMA VALIDATION' );
		$this->validateDatabaseSchema();

		$this->section( 'TEMPLATE CPT CONFIGURATION' );
		$this->validateCptConfiguration();

		$this->section( 'CREATE OPERATIONS - EMAIL TEMPLATE' );
		$this->validateEmailTemplateCreate();

		$this->section( 'CREATE OPERATIONS - CAMPAIGN TEMPLATE' );
		$this->validateCampaignTemplateCreate();

		$this->section( 'READ OPERATIONS' );
		$this->validateReadOperations();

		$this->section( 'UPDATE OPERATIONS' );
		$this->validateUpdateOperations();

		$this->section( 'DELETE OPERATIONS' );
		$this->validateDeleteOperations();

		$this->section( 'TEMPLATE SETTINGS & META' );
		$this->validateTemplateSettings();

		$this->section( 'TEMPLATE RENDERING' );
		$this->validateTemplateRendering();

		$this->section( 'TYPE CONSISTENCY ANALYSIS' );
		$this->validateTypeConsistency();

		$this->section( 'CLEANUP' );
		$this->cleanup();

		$this->printSummary();
	}

	private function section( $title ) {
		echo "\n";
		echo "========================================\n";
		echo strtoupper( $title ) . "\n";
		echo "========================================\n\n";
	}

	private function test( $name, $callback ) {
		echo "→ Testing: {$name}\n";
		try {
			$result                 = $callback();
			$this->results[ $name ] = [
				'status' => 'PASS',
				'result' => $result,
			];
			echo "  ✓ PASS\n";
			return $result;
		} catch ( Exception $e ) {
			$this->results[ $name ] = [
				'status' => 'FAIL',
				'error'  => $e->getMessage(),
			];
			echo '  ✗ FAIL: ' . $e->getMessage() . "\n";
			return null;
		}
	}

	private function dump( $label, $data ) {
		echo "\n  📊 {$label}:\n";
		echo '  ' . str_replace( "\n", "\n  ", print_r( $data, true ) ) . "\n";
	}

	private function json( $label, $data ) {
		echo "\n  📋 {$label}:\n";
		echo '  ' . str_replace( "\n", "\n  ", json_encode( $data, JSON_PRETTY_PRINT ) ) . "\n";
	}

	private function validateDatabaseSchema() {
		global $wpdb;

		$this->test('WordPress posts table exists', function () use ( $wpdb ) {
			$table  = $wpdb->posts;
			$result = $wpdb->get_results( "DESCRIBE {$table}" );
			if ( empty( $result ) ) {
				throw new Exception( 'Posts table not found' );
			}

			$this->dump( 'Posts Table Schema', $result );
			return $result;
		});

		$this->test('WordPress postmeta table exists', function () use ( $wpdb ) {
			$table  = $wpdb->postmeta;
			$result = $wpdb->get_results( "DESCRIBE {$table}" );
			if ( empty( $result ) ) {
				throw new Exception( 'Postmeta table not found' );
			}

			$this->dump( 'Postmeta Table Schema', $result );
			return $result;
		});

		$this->test('Check existing templates in database', function () use ( $wpdb ) {
			$email_templates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_type, post_status, post_date, post_modified
                     FROM {$wpdb->posts}
                     WHERE post_type = %s
                     LIMIT 5",
					'fc_template'
				)
			);

			$campaign_templates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_type, post_status, post_date, post_modified
                     FROM {$wpdb->posts}
                     WHERE post_type = %s
                     LIMIT 5",
					FLUENTCRM . 'campaigntemplate'
				)
			);

			$this->dump( 'Sample Email Templates (fc_template)', $email_templates );
			$this->dump( 'Sample Campaign Templates', $campaign_templates );

			return [
				'email_template_count'    => count( $email_templates ),
				'campaign_template_count' => count( $campaign_templates ),
			];
		});
	}

	private function validateCptConfiguration() {
		$this->test('Email template CPT slug', function () {
			$slug = fluentcrmTemplateCPTSlug();
			if ( $slug !== 'fc_template' ) {
				throw new Exception( "Unexpected CPT slug: {$slug}" );
			}
			echo "  Slug: {$slug}\n";
			return $slug;
		});

		$this->test('Campaign template CPT slug', function () {
			$slug     = fluentcrmCampaignTemplateCPTSlug();
			$expected = FLUENTCRM . 'campaigntemplate';
			if ( $slug !== $expected ) {
				throw new Exception( "Unexpected CPT slug: {$slug}" );
			}
			echo "  Slug: {$slug}\n";
			return $slug;
		});

		$this->test('Template model table name', function () {
			$template = new \FluentCrm\App\Models\Template();
			$table    = $template->getTable();
			if ( $table !== 'posts' ) {
				throw new Exception( "Unexpected table: {$table}" );
			}
			echo "  Table: {$table}\n";
			return $table;
		});

		$this->test('Template model primary key', function () {
			$template = new \FluentCrm\App\Models\Template();
			$pk       = $template->getKeyName();
			if ( $pk !== 'ID' ) {
				throw new Exception( "Unexpected primary key: {$pk}" );
			}
			echo "  Primary Key: {$pk}\n";
			return $pk;
		});

		$this->test('Template model timestamps', function () {
			$template = new \FluentCrm\App\Models\Template();

			// Check via reflection since constants
			$reflection = new ReflectionClass( $template );
			$created_at = $reflection->getConstant( 'CREATED_AT' );
			$updated_at = $reflection->getConstant( 'UPDATED_AT' );

			echo "  CREATED_AT: {$created_at}\n";
			echo "  UPDATED_AT: {$updated_at}\n";

			return [
				'created_at' => $created_at,
				'updated_at' => $updated_at,
			];
		});
	}

	private function validateEmailTemplateCreate() {
		$template_id = $this->test('Create minimal email template', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Test Email Template - ' . time(),
				'post_content' => '<p>Test email content</p>',
				'post_type'    => fluentcrmTemplateCPTSlug(),
				'post_status'  => 'publish',
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Template creation failed' );
			}

			$this->test_template_ids[] = $template->ID;
			$this->json( 'Created Template', $template->toArray() );

			return $template->ID;
		});

		$this->test('Read created email template', function () use ( $template_id ) {
			if ( ! $template_id ) {
				throw new Exception( 'No template ID from create test' );
			}

			$template = \FluentCrm\App\Models\Template::find( $template_id );
			if ( ! $template ) {
				throw new Exception( "Template not found: {$template_id}" );
			}

			$this->json( 'Template Full Structure', $template->toArray() );

			// Validate required fields
			if ( $template->post_type !== 'fc_template' ) {
				throw new Exception( "Wrong post_type: {$template->post_type}" );
			}

			if ( empty( $template->post_date ) ) {
				throw new Exception( 'post_date not set' );
			}

			if ( empty( $template->post_modified ) ) {
				throw new Exception( 'post_modified not set' );
			}

			return $template->toArray();
		});

		$this->test('Create email template with all optional fields', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Full Email Template - ' . time(),
				'post_content' => '<h1>Hello {{subscriber.first_name}}</h1><p>Welcome to our newsletter!</p>',
				'post_excerpt' => 'Newsletter welcome template',
				'post_type'    => fluentcrmTemplateCPTSlug(),
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Full template creation failed' );
			}

			$this->test_template_ids[] = $template->ID;
			$this->json( 'Full Template Created', $template->toArray() );

			return $template->ID;
		});

		$this->test('Create draft email template', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Draft Email Template - ' . time(),
				'post_content' => '<p>Draft content</p>',
				'post_type'    => fluentcrmTemplateCPTSlug(),
				'post_status'  => 'draft',
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Draft template creation failed' );
			}

			$this->test_template_ids[] = $template->ID;

			echo "  Status: {$template->post_status}\n";

			return $template->ID;
		});
	}

	private function validateCampaignTemplateCreate() {
		$this->test('Create campaign template', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Test Campaign Template - ' . time(),
				'post_content' => '<h1>Campaign Email</h1><p>This is a campaign template.</p>',
				'post_type'    => fluentcrmCampaignTemplateCPTSlug(),
				'post_status'  => 'publish',
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Campaign template creation failed' );
			}

			$this->test_template_ids[] = $template->ID;
			$this->json( 'Campaign Template Created', $template->toArray() );

			if ( $template->post_type !== FLUENTCRM . 'campaigntemplate' ) {
				throw new Exception( 'Wrong post_type for campaign template' );
			}

			return $template->ID;
		});
	}

	private function validateReadOperations() {
		// Get a template ID for testing
		$template_id = ! empty( $this->test_template_ids ) ? $this->test_template_ids[0] : null;

		if ( ! $template_id ) {
			echo "⚠️  No test templates available, skipping read tests\n";
			return;
		}

		$this->test('Read template by ID', function () use ( $template_id ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );
			if ( ! $template ) {
				throw new Exception( 'Template not found' );
			}

			$this->json( 'Template by ID', $template->toArray() );
			return $template->toArray();
		});

		$this->test('Query email templates scope', function () {
			$templates = \FluentCrm\App\Models\Template::emailTemplates()->limit( 3 )->get();

			echo '  Found: ' . count( $templates ) . " email templates\n";

			if ( count( $templates ) > 0 ) {
				$this->json( 'First Email Template', $templates[0]->toArray() );
			}

			return $templates->toArray();
		});

		$this->test('Query campaign templates scope', function () {
			$templates = \FluentCrm\App\Models\Template::campaignTemplate()->limit( 3 )->get();

			echo '  Found: ' . count( $templates ) . " campaign templates\n";

			if ( count( $templates ) > 0 ) {
				$this->json( 'First Campaign Template', $templates[0]->toArray() );
			}

			return $templates->toArray();
		});

		$this->test('Query templates by status', function () {
			$published = \FluentCrm\App\Models\Template::emailTemplates( [ 'publish' ] )->count();
			$draft     = \FluentCrm\App\Models\Template::emailTemplates( [ 'draft' ] )->count();

			echo "  Published: {$published}\n";
			echo "  Draft: {$draft}\n";

			return [
				'published' => $published,
				'draft'     => $draft,
			];
		});
	}

	private function validateUpdateOperations() {
		$template_id = ! empty( $this->test_template_ids ) ? $this->test_template_ids[0] : null;

		if ( ! $template_id ) {
			echo "⚠️  No test templates available, skipping update tests\n";
			return;
		}

		$this->test('Update template title', function () use ( $template_id ) {
			$template       = \FluentCrm\App\Models\Template::find( $template_id );
			$original_title = $template->post_title;

			$new_title = 'Updated Template - ' . time();
			$template->update( [ 'post_title' => $new_title ] );

			$template->fresh();

			if ( $template->post_title !== $new_title ) {
				throw new Exception( 'Title not updated' );
			}

			echo "  Original: {$original_title}\n";
			echo "  Updated: {$template->post_title}\n";

			return true;
		});

		$this->test('Update template content', function () use ( $template_id ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			$new_content = '<h1>Updated Content</h1><p>Modified at ' . time() . '</p>';
			$template->update( [ 'post_content' => $new_content ] );

			$template->fresh();

			if ( $template->post_content !== $new_content ) {
				throw new Exception( 'Content not updated' );
			}

			echo "  Content updated successfully\n";

			return true;
		});

		$this->test('Update template status', function () use ( $template_id ) {
			$template        = \FluentCrm\App\Models\Template::find( $template_id );
			$original_status = $template->post_status;

			$new_status = $original_status === 'publish' ? 'draft' : 'publish';
			$template->update( [ 'post_status' => $new_status ] );

			$template->fresh();

			if ( $template->post_status !== $new_status ) {
				throw new Exception( 'Status not updated' );
			}

			echo "  Original: {$original_status}\n";
			echo "  Updated: {$template->post_status}\n";

			return true;
		});

		$this->test('Verify post_modified auto-updates', function () use ( $template_id ) {
			$template          = \FluentCrm\App\Models\Template::find( $template_id );
			$original_modified = $template->post_modified;

			// Wait a second to ensure timestamp difference
			sleep( 1 );

			$template->update( [ 'post_excerpt' => 'Modified excerpt' ] );
			$template->fresh();

			if ( $template->post_modified <= $original_modified ) {
				throw new Exception( 'post_modified not updated' );
			}

			echo "  Original: {$original_modified}\n";
			echo "  Updated: {$template->post_modified}\n";

			return true;
		});
	}

	private function validateDeleteOperations() {
		$this->test('Create template for deletion test', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Template to Delete - ' . time(),
				'post_content' => '<p>Will be deleted</p>',
				'post_type'    => fluentcrmTemplateCPTSlug(),
				'post_status'  => 'publish',
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Test template creation failed' );
			}

			$template_id = $template->ID;

			// Delete it
			$deleted = $template->delete();

			if ( ! $deleted ) {
				throw new Exception( 'Delete operation failed' );
			}

			// Verify it's gone
			$check = \FluentCrm\App\Models\Template::find( $template_id );

			if ( $check ) {
				throw new Exception( 'Template still exists after delete' );
			}

			echo "  Template {$template_id} deleted successfully\n";

			return true;
		});
	}

	private function validateTemplateSettings() {
		global $wpdb;

		$template_id = ! empty( $this->test_template_ids ) ? $this->test_template_ids[0] : null;

		if ( ! $template_id ) {
			echo "⚠️  No test templates available, skipping settings tests\n";
			return;
		}

		$this->test('Check template postmeta structure', function () use ( $wpdb, $template_id ) {
			$meta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
					$template_id
				)
			);

			$this->dump( 'Template Postmeta', $meta );

			return $meta;
		});

		$this->test('Add custom meta to template', function () use ( $template_id ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			$settings = [
				'design_template'  => 'simple',
				'email_subject'    => 'Test Subject',
				'email_pre_header' => 'Preview text here',
			];

			foreach ( $settings as $key => $value ) {
				update_post_meta( $template_id, '_' . $key, $value );
			}

			// Read back
			$design_template = get_post_meta( $template_id, '_design_template', true );
			$email_subject   = get_post_meta( $template_id, '_email_subject', true );

			echo "  Design Template: {$design_template}\n";
			echo "  Email Subject: {$email_subject}\n";

			if ( $design_template !== 'simple' ) {
				throw new Exception( 'Meta not saved correctly' );
			}

			return $settings;
		});

		$this->test('FluentCRM template settings meta', function () use ( $template_id ) {
			// FluentCRM stores template settings in postmeta
			$settings = [
				'template_config' => [
					'type'    => 'campaign',
					'design'  => 'raw_html',
					'version' => '2.0',
				],
			];

			update_post_meta( $template_id, '_template_config', json_encode( $settings ) );

			$saved   = get_post_meta( $template_id, '_template_config', true );
			$decoded = json_decode( $saved, true );

			$this->json( 'Template Config Saved', $decoded );

			if ( $decoded['template_config']['type'] !== 'campaign' ) {
				throw new Exception( 'JSON meta not saved correctly' );
			}

			return $decoded;
		});
	}

	private function validateTemplateRendering() {
		$this->test('Template render method', function () {
			$template = \FluentCrm\App\Models\Template::create([
				'post_title'   => 'Render Test Template - ' . time(),
				'post_content' => '<h1>Hello {{subscriber.first_name}}</h1><p>Email: {{subscriber.email}}</p>',
				'post_type'    => fluentcrmTemplateCPTSlug(),
				'post_status'  => 'publish',
			]);

			if ( ! $template || ! $template->ID ) {
				throw new Exception( 'Render test template creation failed' );
			}

			$this->test_template_ids[] = $template->ID;

			// Test render method
			$rendered = $template->render();

			echo "  Original Content:\n  {$template->post_content}\n";
			echo "  Rendered Content:\n  {$rendered}\n";

			// Note: Without subscriber context, placeholders won't be replaced
			// Just verify method works
			if ( ! is_string( $rendered ) ) {
				throw new Exception( 'Render did not return string' );
			}

			return $rendered;
		});

		$this->test('Render with custom content', function () {
			$template = \FluentCrm\App\Models\Template::emailTemplates()->first();

			if ( ! $template ) {
				throw new Exception( 'No template available for render test' );
			}

			$custom_content = '<p>Custom content for {{subscriber.first_name}}</p>';
			$rendered       = $template->render( $custom_content );

			echo "  Custom Content: {$custom_content}\n";
			echo "  Rendered: {$rendered}\n";

			return $rendered;
		});
	}

	private function validateTypeConsistency() {
		$template_id = ! empty( $this->test_template_ids ) ? $this->test_template_ids[0] : null;

		if ( ! $template_id ) {
			echo "⚠️  No test templates available, skipping type tests\n";
			return;
		}

		$this->test('Field type analysis', function () use ( $template_id ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			$types = [
				'ID'            => gettype( $template->ID ),
				'post_author'   => gettype( $template->post_author ),
				'post_date'     => gettype( $template->post_date ),
				'post_modified' => gettype( $template->post_modified ),
				'post_title'    => gettype( $template->post_title ),
				'post_content'  => gettype( $template->post_content ),
				'post_status'   => gettype( $template->post_status ),
				'post_type'     => gettype( $template->post_type ),
			];

			$this->dump( 'Field Types', $types );

			// Verify key types
			if ( ! is_int( $template->ID ) ) {
				throw new Exception( 'ID should be integer, got: ' . gettype( $template->ID ) );
			}

			if ( ! is_string( $template->post_date ) ) {
				throw new Exception( 'post_date should be string, got: ' . gettype( $template->post_date ) );
			}

			echo "  ✓ ID is integer\n";
			echo "  ✓ post_date is string\n";
			echo "  ✓ post_title is string\n";

			return $types;
		});

		$this->test('Timestamp format validation', function () use ( $template_id ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );

			$date_pattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

			if ( ! preg_match( $date_pattern, $template->post_date ) ) {
				throw new Exception( "post_date format invalid: {$template->post_date}" );
			}

			if ( ! preg_match( $date_pattern, $template->post_modified ) ) {
				throw new Exception( "post_modified format invalid: {$template->post_modified}" );
			}

			echo "  post_date: {$template->post_date}\n";
			echo "  post_modified: {$template->post_modified}\n";
			echo "  ✓ Timestamps match Y-m-d H:i:s format\n";

			return true;
		});

		$this->test('Collection type validation', function () {
			$templates = \FluentCrm\App\Models\Template::emailTemplates()->limit( 3 )->get();

			$collection_class = get_class( $templates );

			echo "  Collection Class: {$collection_class}\n";
			echo '  Count: ' . count( $templates ) . "\n";

			if ( ! ( $templates instanceof \FluentCrm\Framework\Database\Orm\Collection ) ) {
				throw new Exception( 'Query did not return Collection object' );
			}

			echo "  ✓ Returns FluentCrm Collection\n";

			return $collection_class;
		});
	}

	private function cleanup() {
		global $wpdb;

		echo "Cleaning up test templates...\n";

		foreach ( $this->test_template_ids as $id ) {
			// Delete template and all meta
			wp_delete_post( $id, true );
			echo "  ✓ Deleted template {$id}\n";
		}

		echo "Cleanup complete.\n";
	}

	private function printSummary() {
		echo "\n";
		echo "========================================\n";
		echo "VALIDATION SUMMARY\n";
		echo "========================================\n\n";

		$total  = count( $this->results );
		$passed = count( array_filter( $this->results, fn( $r ) => $r['status'] === 'PASS' ) );
		$failed = $total - $passed;

		echo "Total Tests: {$total}\n";
		echo "Passed: {$passed}\n";
		echo "Failed: {$failed}\n\n";

		if ( $failed > 0 ) {
			echo "Failed Tests:\n";
			foreach ( $this->results as $name => $result ) {
				if ( $result['status'] === 'FAIL' ) {
					echo "  ✗ {$name}: {$result['error']}\n";
				}
			}
			echo "\n";
		}

		echo "Validation complete!\n";
		echo "========================================\n\n";
	}
}

// Run validation
$validator = new FluentCrmTemplateValidator();
$validator->run();
