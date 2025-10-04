<?php
/**
 * FluentCRM Email Sequences Model Validation Script
 *
 * Validates the complete Email Sequences model structure including:
 * - Database schema for fc_email_sequences table
 * - Create operation (all sequence types and settings)
 * - Read operation (with sequence emails/steps)
 * - Update operation (status changes, settings)
 * - Delete/archive behavior
 * - Sequence emails relationship (fc_email_sequence_emails)
 * - Settings structure (JSON field - triggers, conditions, execution)
 * - Subscriber tracking (fc_sequence_tracker table)
 * - Sequence flow and trigger system
 *
 * Usage: cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-sequences.php
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

if (!defined('FLUENTCRM')) {
    die("Error: FluentCRM is not installed or activated.\n");
}

class FluentCrmSequencesValidator {
    private $results = [];
    private $test_sequence_id = null;
    private $test_email_id = null;
    private $test_subscriber_id = null;

    public function __construct() {
        echo "FluentCRM Email Sequences Model Validation\n";
        echo str_repeat("=", 80) . "\n\n";
    }

    /**
     * Run all validation tests
     */
    public function run() {
        $this->validateDatabaseSchema();
        $this->validateSequenceTypes();
        $this->validateCreateOperation();
        $this->validateReadOperation();
        $this->validateUpdateOperation();
        $this->validateSequenceEmails();
        $this->validateSettingsStructure();
        $this->validateSubscriberTracking();
        $this->validateTriggerSystem();
        $this->validateExecutionFlow();
        $this->validateStatusTransitions();
        $this->validateDeleteArchive();
        $this->cleanup();
        $this->printSummary();
    }

    /**
     * Validate database schema for all sequence-related tables
     */
    private function validateDatabaseSchema() {
        echo "1. DATABASE SCHEMA VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        global $wpdb;

        // Validate fc_email_sequences table
        $sequences_table = $wpdb->prefix . 'fc_email_sequences';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sequences_table'") === $sequences_table;
        $this->logResult('Sequences Table Exists', $table_exists, "Table: $sequences_table");

        if ($table_exists) {
            $columns = $wpdb->get_results("DESCRIBE $sequences_table");
            echo "\nSequences Table Columns:\n";
            foreach ($columns as $column) {
                echo sprintf("  - %-25s | %-35s | %-5s | %-10s | %s\n",
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
                'id' => 'bigint',
                'parent_id' => 'bigint',
                'title' => 'varchar',
                'slug' => 'varchar',
                'status' => 'varchar',
                'type' => 'varchar',
                'settings' => 'longtext',
                'conditions' => 'longtext',
                'created_by' => 'bigint',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ];

            foreach ($expected_columns as $field => $expected_type) {
                $found = false;
                foreach ($columns as $column) {
                    if ($column->Field === $field) {
                        $found = true;
                        $type_match = stripos($column->Type, $expected_type) !== false;
                        $this->logResult(
                            "Column: $field",
                            $type_match,
                            "Type: {$column->Type} (Expected: $expected_type)"
                        );
                        break;
                    }
                }
                if (!$found) {
                    $this->logResult("Column: $field", false, "Column not found");
                }
            }
        }

        // Validate fc_email_sequence_emails table
        $emails_table = $wpdb->prefix . 'fc_email_sequence_emails';
        $emails_exists = $wpdb->get_var("SHOW TABLES LIKE '$emails_table'") === $emails_table;
        $this->logResult('Sequence Emails Table Exists', $emails_exists, "Table: $emails_table");

        if ($emails_exists) {
            $email_columns = $wpdb->get_results("DESCRIBE $emails_table");
            echo "\nSequence Emails Table Columns:\n";
            foreach ($email_columns as $column) {
                echo sprintf("  - %-25s | %-35s | %-5s | %-10s | %s\n",
                    $column->Field,
                    $column->Type,
                    $column->Null,
                    $column->Key,
                    $column->Extra
                );
            }
        }

        // Validate fc_sequence_tracker table
        $tracker_table = $wpdb->prefix . 'fc_sequence_tracker';
        $tracker_exists = $wpdb->get_var("SHOW TABLES LIKE '$tracker_table'") === $tracker_table;
        $this->logResult('Sequence Tracker Table Exists', $tracker_exists, "Table: $tracker_table");

        if ($tracker_exists) {
            $tracker_columns = $wpdb->get_results("DESCRIBE $tracker_table");
            echo "\nSequence Tracker Table Columns:\n";
            foreach ($tracker_columns as $column) {
                echo sprintf("  - %-25s | %-35s | %-5s | %-10s | %s\n",
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
     * Validate different sequence types
     */
    private function validateSequenceTypes() {
        echo "2. SEQUENCE TYPES VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        // Check existing sequences for type variations
        global $wpdb;
        $sequences_table = $wpdb->prefix . 'fc_email_sequences';

        $types = $wpdb->get_results("
            SELECT DISTINCT type, COUNT(*) as count
            FROM $sequences_table
            GROUP BY type
        ");

        echo "Existing Sequence Types:\n";
        foreach ($types as $type) {
            echo "  - {$type->type}: {$type->count} sequences\n";
            $this->logResult("Type: {$type->type}", true, "Count: {$type->count}");
        }

        // Validate status values
        $statuses = $wpdb->get_results("
            SELECT DISTINCT status, COUNT(*) as count
            FROM $sequences_table
            GROUP BY status
        ");

        echo "\nExisting Status Values:\n";
        foreach ($statuses as $status) {
            echo "  - {$status->status}: {$status->count} sequences\n";
            $this->logResult("Status: {$status->status}", true, "Count: {$status->count}");
        }

        echo "\n";
    }

    /**
     * Validate create operation with comprehensive settings
     */
    private function validateCreateOperation() {
        echo "3. CREATE OPERATION VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        try {
            // Prepare comprehensive test data
            $test_data = [
                'title' => 'Test Sequence ' . time(),
                'slug' => 'test-sequence-' . time(),
                'status' => 'draft',
                'type' => 'sequence',
                'settings' => json_encode([
                    'mailer_settings' => [
                        'from_name' => 'Test Sender',
                        'from_email' => 'test@example.com',
                        'reply_to_name' => 'Reply To',
                        'reply_to_email' => 'reply@example.com',
                        'is_custom' => 'yes',
                    ],
                    'subscription_status' => 'subscribed',
                ]),
                'conditions' => json_encode([
                    'tags' => [],
                    'lists' => [],
                ]),
                'created_by' => get_current_user_id(),
            ];

            echo "Test Data:\n";
            echo json_encode(json_decode($test_data['settings']), JSON_PRETTY_PRINT) . "\n\n";

            // Create sequence
            if (class_exists('\FluentCrm\App\Models\Sequence')) {
                $sequence = \FluentCrm\App\Models\Sequence::create($test_data);
                $this->test_sequence_id = $sequence->id;

                $this->logResult('Sequence Created', true, "ID: {$sequence->id}");

                echo "\nCreated Sequence Object:\n";
                $seq_array = $sequence->toArray();
                echo json_encode($seq_array, JSON_PRETTY_PRINT) . "\n\n";

                // Verify fields
                $this->logResult('Auto ID', !empty($sequence->id), "ID: {$sequence->id}");
                $this->logResult('Title Saved', $sequence->title === $test_data['title'], "Value: {$sequence->title}");
                $this->logResult('Slug Saved', $sequence->slug === $test_data['slug'], "Value: {$sequence->slug}");
                $this->logResult('Status Saved', $sequence->status === $test_data['status'], "Value: {$sequence->status}");
                $this->logResult('Settings JSON', !empty($sequence->settings), "Settings stored");
                $this->logResult('Created At', !empty($sequence->created_at), "Value: {$sequence->created_at}");
                $this->logResult('Updated At', !empty($sequence->updated_at), "Value: {$sequence->updated_at}");

            } else {
                $this->logResult('Sequence Model', false, 'FluentCrm\App\Models\Sequence class not found');
            }

        } catch (\Exception $e) {
            $this->logResult('Create Operation', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate read operation with relationships
     */
    private function validateReadOperation() {
        echo "4. READ OPERATION VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id) {
            echo "Skipping: No test sequence created\n\n";
            return;
        }

        try {
            // Read without relationships
            $sequence = \FluentCrm\App\Models\Sequence::find($this->test_sequence_id);
            $this->logResult('Read by ID', !is_null($sequence), "ID: {$this->test_sequence_id}");

            if ($sequence) {
                echo "\nSequence Data (without relationships):\n";
                echo json_encode($sequence->toArray(), JSON_PRETTY_PRINT) . "\n\n";

                // Check settings parsing
                $settings = $sequence->settings;
                $is_array = is_array($settings);
                $this->logResult('Settings Parsed', $is_array, "Type: " . gettype($settings));

                if ($is_array && isset($settings['mailer_settings'])) {
                    $this->logResult('Mailer Settings', true, "Found mailer_settings key");
                }
            }

            // Read with emails relationship
            if (method_exists(\FluentCrm\App\Models\Sequence::class, 'emails')) {
                $with_emails = \FluentCrm\App\Models\Sequence::with('emails')->find($this->test_sequence_id);
                $this->logResult('Read with Emails', !is_null($with_emails), "ID: {$this->test_sequence_id}");

                if ($with_emails) {
                    $emails = $with_emails->emails ?? null;
                    $this->logResult('Emails Relation', !is_null($emails), "Type: " . gettype($emails));

                    if ($emails) {
                        echo "\nSequence Emails Count: " . count($emails) . "\n";
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logResult('Read Operation', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate update operation
     */
    private function validateUpdateOperation() {
        echo "5. UPDATE OPERATION VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id) {
            echo "Skipping: No test sequence created\n\n";
            return;
        }

        try {
            $sequence = \FluentCrm\App\Models\Sequence::find($this->test_sequence_id);

            if (!$sequence) {
                $this->logResult('Update Operation', false, 'Sequence not found');
                return;
            }

            $original_title = $sequence->title;
            $original_status = $sequence->status;

            // Update fields
            $updates = [
                'title' => 'Updated Sequence Title',
                'status' => 'published',
                'settings' => array_merge($sequence->settings ?? [], [
                    'updated_field' => 'test_value',
                ]),
            ];

            $sequence->update($updates);
            $sequence->refresh();

            $this->logResult(
                'Update: title',
                $sequence->title === $updates['title'],
                "Original: {$original_title} → New: {$sequence->title}"
            );

            $this->logResult(
                'Update: status',
                $sequence->status === $updates['status'],
                "Original: {$original_status} → New: {$sequence->status}"
            );

            $this->logResult(
                'Settings Updated',
                isset($sequence->settings['updated_field']),
                "Found updated_field in settings"
            );

        } catch (\Exception $e) {
            $this->logResult('Update Operation', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate sequence emails relationship
     */
    private function validateSequenceEmails() {
        echo "6. SEQUENCE EMAILS VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id) {
            echo "Skipping: No test sequence created\n\n";
            return;
        }

        try {
            // Check if SequenceEmail model exists
            if (!class_exists('\FluentCrm\App\Models\SequenceEmail')) {
                $this->logResult('SequenceEmail Model', false, 'Class not found');
                echo "\n";
                return;
            }

            // Create a test sequence email
            $email_data = [
                'parent_id' => $this->test_sequence_id,
                'type' => 'sequence_mail',
                'status' => 'published',
                'email_subject' => 'Test Email Subject',
                'email_pre_header' => 'Test pre-header',
                'email_body' => '<p>Test email body</p>',
                'settings' => json_encode([
                    'timings' => [
                        'delay' => 1,
                        'delay_unit' => 'days',
                    ],
                ]),
            ];

            $email = \FluentCrm\App\Models\SequenceEmail::create($email_data);
            $this->test_email_id = $email->id;

            $this->logResult('Email Created', !is_null($email), "ID: {$email->id}");

            echo "\nCreated Sequence Email:\n";
            echo json_encode($email->toArray(), JSON_PRETTY_PRINT) . "\n\n";

            // Load sequence with emails
            $sequence = \FluentCrm\App\Models\Sequence::with('emails')->find($this->test_sequence_id);
            $emails = $sequence->emails ?? null;

            if ($emails && count($emails) > 0) {
                $this->logResult('Emails Loaded', true, "Count: " . count($emails));

                foreach ($emails as $seq_email) {
                    echo "  - Email ID: {$seq_email->id}, Subject: {$seq_email->email_subject}\n";
                }
                echo "\n";
            } else {
                $this->logResult('Emails Loaded', false, "No emails found");
            }

            // Verify settings structure
            $email_settings = $email->settings;
            if (is_array($email_settings) && isset($email_settings['timings'])) {
                $this->logResult('Email Settings', true, "Timings found: " . json_encode($email_settings['timings']));
            }

        } catch (\Exception $e) {
            $this->logResult('Sequence Emails', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate settings structure
     */
    private function validateSettingsStructure() {
        echo "7. SETTINGS STRUCTURE VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id) {
            echo "Skipping: No test sequence created\n\n";
            return;
        }

        try {
            $sequence = \FluentCrm\App\Models\Sequence::find($this->test_sequence_id);
            $settings = $sequence->settings;

            if (!is_array($settings)) {
                $this->logResult('Settings Type', false, "Not an array: " . gettype($settings));
                echo "\n";
                return;
            }

            echo "Settings Structure:\n";
            echo json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";

            // Check expected keys
            $expected_keys = [
                'mailer_settings',
                'subscription_status',
            ];

            foreach ($expected_keys as $key) {
                $has_key = isset($settings[$key]);
                $this->logResult(
                    "Settings: $key",
                    $has_key,
                    $has_key ? "Present" : "Missing"
                );
            }

            // Validate mailer_settings structure
            if (isset($settings['mailer_settings'])) {
                $mailer = $settings['mailer_settings'];
                $mailer_keys = ['from_name', 'from_email', 'reply_to_name', 'reply_to_email', 'is_custom'];

                foreach ($mailer_keys as $key) {
                    $this->logResult(
                        "Mailer: $key",
                        isset($mailer[$key]),
                        isset($mailer[$key]) ? "Value: {$mailer[$key]}" : "Missing"
                    );
                }
            }

        } catch (\Exception $e) {
            $this->logResult('Settings Structure', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate subscriber tracking
     */
    private function validateSubscriberTracking() {
        echo "8. SUBSCRIBER TRACKING VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        try {
            // Create test subscriber
            $subscriber_data = [
                'email' => 'sequence-test-' . time() . '@example.com',
                'first_name' => 'Sequence',
                'last_name' => 'Test',
                'status' => 'subscribed',
            ];

            $subscriber = \FluentCrm\App\Models\Subscriber::create($subscriber_data);
            $this->test_subscriber_id = $subscriber->id;
            $this->logResult('Test Subscriber Created', true, "ID: {$subscriber->id}");

            // Check if SequenceTracker model exists
            if (!class_exists('\FluentCrm\App\Models\SequenceTracker')) {
                $this->logResult('SequenceTracker Model', false, 'Class not found');
                echo "\n";
                return;
            }

            // Check tracker table structure
            global $wpdb;
            $tracker_table = $wpdb->prefix . 'fc_sequence_tracker';

            // Get sample tracking records
            $trackers = $wpdb->get_results("
                SELECT * FROM $tracker_table
                LIMIT 3
            ");

            if ($trackers) {
                echo "\nSample Tracker Records:\n";
                foreach ($trackers as $tracker) {
                    echo json_encode($tracker, JSON_PRETTY_PRINT) . "\n";
                }
                echo "\n";
                $this->logResult('Tracker Records', true, "Found " . count($trackers) . " sample records");
            } else {
                $this->logResult('Tracker Records', true, "No existing records (expected for new installation)");
            }

        } catch (\Exception $e) {
            $this->logResult('Subscriber Tracking', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate trigger system
     */
    private function validateTriggerSystem() {
        echo "9. TRIGGER SYSTEM VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        try {
            // Get sequences with different condition types
            global $wpdb;
            $sequences_table = $wpdb->prefix . 'fc_email_sequences';

            $sequences = $wpdb->get_results("
                SELECT id, title, type, conditions
                FROM $sequences_table
                WHERE conditions IS NOT NULL
                AND conditions != ''
                LIMIT 5
            ");

            if ($sequences) {
                echo "Sequences with Conditions:\n";
                foreach ($sequences as $seq) {
                    echo "\nSequence: {$seq->title} (ID: {$seq->id})\n";
                    $conditions = json_decode($seq->conditions, true);
                    if ($conditions) {
                        echo "  Conditions:\n";
                        echo json_encode($conditions, JSON_PRETTY_PRINT) . "\n";
                    }
                }
                $this->logResult('Trigger Conditions', true, "Found " . count($sequences) . " sequences with conditions");
            } else {
                $this->logResult('Trigger Conditions', true, "No sequences with conditions (expected for clean install)");
            }

        } catch (\Exception $e) {
            $this->logResult('Trigger System', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate execution flow
     */
    private function validateExecutionFlow() {
        echo "10. EXECUTION FLOW VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id || !$this->test_email_id) {
            echo "Skipping: Missing test sequence or email\n\n";
            return;
        }

        try {
            // Load sequence with all emails
            $sequence = \FluentCrm\App\Models\Sequence::with('emails')->find($this->test_sequence_id);
            $emails = $sequence->emails ?? [];

            echo "Sequence Execution Flow:\n";
            echo "  Sequence: {$sequence->title} (ID: {$sequence->id})\n";
            echo "  Status: {$sequence->status}\n";
            echo "  Total Emails: " . count($emails) . "\n\n";

            if (count($emails) > 0) {
                echo "Email Steps:\n";
                foreach ($emails as $index => $email) {
                    $step = $index + 1;
                    $settings = $email->settings ?? [];
                    $timings = $settings['timings'] ?? [];
                    $delay = $timings['delay'] ?? 0;
                    $delay_unit = $timings['delay_unit'] ?? 'days';

                    echo "  Step $step:\n";
                    echo "    Email ID: {$email->id}\n";
                    echo "    Subject: {$email->email_subject}\n";
                    echo "    Delay: $delay $delay_unit\n";
                    echo "    Status: {$email->status}\n\n";
                }

                $this->logResult('Execution Flow', true, count($emails) . " email steps configured");
            }

        } catch (\Exception $e) {
            $this->logResult('Execution Flow', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate status transitions
     */
    private function validateStatusTransitions() {
        echo "11. STATUS TRANSITIONS VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        if (!$this->test_sequence_id) {
            echo "Skipping: No test sequence created\n\n";
            return;
        }

        try {
            $sequence = \FluentCrm\App\Models\Sequence::find($this->test_sequence_id);

            $status_transitions = [
                'draft' => 'published',
                'published' => 'archived',
                'archived' => 'draft',
            ];

            foreach ($status_transitions as $from => $to) {
                $sequence->status = $from;
                $sequence->save();
                $sequence->refresh();

                $transition_success = $sequence->status === $from;
                $this->logResult("Status: $from", $transition_success, "Value: {$sequence->status}");

                $sequence->status = $to;
                $sequence->save();
                $sequence->refresh();

                $transition_success = $sequence->status === $to;
                $this->logResult("Transition: $from → $to", $transition_success, "Value: {$sequence->status}");
            }

        } catch (\Exception $e) {
            $this->logResult('Status Transitions', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Validate delete and archive behavior
     */
    private function validateDeleteArchive() {
        echo "12. DELETE/ARCHIVE VALIDATION\n";
        echo str_repeat("-", 80) . "\n";

        try {
            // Create a sequence to delete
            $delete_test = \FluentCrm\App\Models\Sequence::create([
                'title' => 'Delete Test ' . time(),
                'slug' => 'delete-test-' . time(),
                'status' => 'draft',
                'type' => 'sequence',
            ]);
            $delete_id = $delete_test->id;

            // Test delete
            $deleted = $delete_test->delete();
            $this->logResult('Delete Operation', $deleted, "ID: $delete_id");

            // Try to find deleted sequence
            $found_after_delete = \FluentCrm\App\Models\Sequence::find($delete_id);
            $this->logResult('Hard Delete', is_null($found_after_delete), "Sequence removed from database");

            // Check soft delete support
            if (method_exists(\FluentCrm\App\Models\Sequence::class, 'withTrashed')) {
                $with_trashed = \FluentCrm\App\Models\Sequence::withTrashed()->find($delete_id);
                $this->logResult('Soft Delete Support', !is_null($with_trashed), "Sequence found in trash");
            } else {
                $this->logResult('Soft Delete Support', false, "No soft delete trait detected");
            }

        } catch (\Exception $e) {
            $this->logResult('Delete/Archive', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Cleanup test data
     */
    private function cleanup() {
        echo "13. CLEANUP\n";
        echo str_repeat("-", 80) . "\n";

        try {
            // Delete test email
            if ($this->test_email_id) {
                $email = \FluentCrm\App\Models\SequenceEmail::find($this->test_email_id);
                if ($email) {
                    $email->delete();
                    $this->logResult('Email Cleanup', true, "ID: {$this->test_email_id}");
                }
            }

            // Delete test subscriber
            if ($this->test_subscriber_id) {
                $subscriber = \FluentCrm\App\Models\Subscriber::find($this->test_subscriber_id);
                if ($subscriber) {
                    $subscriber->delete();
                    $this->logResult('Subscriber Cleanup', true, "ID: {$this->test_subscriber_id}");
                }
            }

            // Delete test sequence
            if ($this->test_sequence_id) {
                $sequence = \FluentCrm\App\Models\Sequence::find($this->test_sequence_id);
                if ($sequence) {
                    $sequence->delete();
                    $this->logResult('Sequence Cleanup', true, "ID: {$this->test_sequence_id}");
                }
            }

        } catch (\Exception $e) {
            $this->logResult('Cleanup', false, 'Exception: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Log test result
     */
    private function logResult($test, $passed, $details = '') {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        $this->results[] = ['test' => $test, 'passed' => $passed, 'details' => $details];
        echo sprintf("  %-35s %s  %s\n", $test, $status, $details);
    }

    /**
     * Print summary of all tests
     */
    private function printSummary() {
        echo "\nVALIDATION SUMMARY\n";
        echo str_repeat("=", 80) . "\n";

        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r['passed']));
        $failed = $total - $passed;

        echo "Total Tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Success Rate: " . ($total > 0 ? round(($passed / $total) * 100, 2) : 0) . "%\n\n";

        if ($failed > 0) {
            echo "FAILED TESTS:\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "  ❌ {$result['test']}: {$result['details']}\n";
                }
            }
        }

        echo "\n";
    }
}

// Run validation
$validator = new FluentCrmSequencesValidator();
$validator->run();
