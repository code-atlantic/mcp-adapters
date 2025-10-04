# FluentCRM Email Sequences - Validated Structure

**Validation Date**: 2025-10-04
**Model**: `\FluentCrm\App\Models\Sequence`
**Database Table**: `{prefix}_fc_email_sequences`
**Related Tables**: `{prefix}_fc_email_sequence_emails`, `{prefix}_fc_sequence_tracker`

---

## Summary

FluentCRM Email Sequences represent automated email series triggered by subscriber actions or conditions. The system uses three interconnected tables to manage sequence definitions, individual email steps, and subscriber enrollment tracking. Sequences support sophisticated trigger conditions, delayed execution, and comprehensive analytics.

## Database Schema

### Main Sequences Table: `wp_fc_email_sequences`

```sql
CREATE TABLE wp_fc_email_sequences (
    id             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id      BIGINT UNSIGNED  NULL DEFAULT NULL,
    title          VARCHAR(192)     NOT NULL,
    slug           VARCHAR(192)     NOT NULL,
    status         VARCHAR(50)      NOT NULL DEFAULT 'draft',
    type           VARCHAR(50)      NOT NULL DEFAULT 'sequence',
    settings       LONGTEXT         NULL,
    conditions     LONGTEXT         NULL,
    created_by     BIGINT UNSIGNED  NULL,
    created_at     TIMESTAMP        NULL,
    updated_at     TIMESTAMP        NULL,

    INDEX (type),
    INDEX (status),
    INDEX (parent_id)
);
```

### Sequence Emails Table: `wp_fc_email_sequence_emails`

```sql
CREATE TABLE wp_fc_email_sequence_emails (
    id                BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id         BIGINT UNSIGNED  NOT NULL,
    type              VARCHAR(50)      NOT NULL DEFAULT 'sequence_mail',
    status            VARCHAR(50)      NOT NULL DEFAULT 'draft',
    email_subject     VARCHAR(255)     NULL,
    email_pre_header  VARCHAR(255)     NULL,
    email_body        LONGTEXT         NULL,
    settings          LONGTEXT         NULL,
    utm_status        TINYINT(1)       NULL DEFAULT 0,
    utm_source        VARCHAR(192)     NULL,
    utm_medium        VARCHAR(192)     NULL,
    utm_campaign      VARCHAR(192)     NULL,
    utm_term          VARCHAR(192)     NULL,
    utm_content       VARCHAR(192)     NULL,
    delay             INT              NULL DEFAULT 0,
    sequence          INT              NULL DEFAULT 0,
    created_at        TIMESTAMP        NULL,
    updated_at        TIMESTAMP        NULL,

    INDEX (parent_id),
    INDEX (type),
    INDEX (status),
    INDEX (sequence)
);
```

### Subscriber Tracking Table: `wp_fc_sequence_tracker`

```sql
CREATE TABLE wp_fc_sequence_tracker (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    campaign_id          BIGINT UNSIGNED  NOT NULL,
    subscriber_id        BIGINT UNSIGNED  NOT NULL,
    last_sequence_id     BIGINT UNSIGNED  NULL,
    next_sequence_id     BIGINT UNSIGNED  NULL,
    status               VARCHAR(50)      NOT NULL DEFAULT 'active',
    type                 VARCHAR(50)      NOT NULL DEFAULT 'sequence_tracker',
    last_executed_time   TIMESTAMP        NULL,
    next_execution_time  TIMESTAMP        NULL,
    notes                TEXT             NULL,
    created_at           TIMESTAMP        NULL,
    updated_at           TIMESTAMP        NULL,

    INDEX (campaign_id),
    INDEX (subscriber_id),
    INDEX (status),
    INDEX (next_execution_time)
);
```

---

## Core Models

### Sequence Model

**Class**: `\FluentCrm\App\Models\Sequence`
**Table**: `wp_fc_email_sequences`

**Key Fields**:
- `id` - Auto-increment primary key
- `parent_id` - For sequence variants or AB tests (nullable)
- `title` - Sequence name/identifier
- `slug` - URL-friendly identifier
- `status` - Lifecycle status: 'draft', 'published', 'archived'
- `type` - Sequence type identifier (default: 'sequence')
- `settings` - JSON configuration (triggers, mailer settings, subscription status)
- `conditions` - JSON trigger conditions (tags, lists, custom rules)
- `created_by` - WordPress user ID who created sequence
- `created_at` - Creation timestamp
- `updated_at` - Last modification timestamp

### SequenceEmail Model

**Class**: `\FluentCrm\App\Models\SequenceEmail`
**Table**: `wp_fc_email_sequence_emails`

**Key Fields**:
- `id` - Auto-increment primary key
- `parent_id` - Foreign key to `fc_email_sequences.id`
- `type` - Email type (default: 'sequence_mail')
- `status` - Email status: 'draft', 'published'
- `email_subject` - Email subject line
- `email_pre_header` - Email preview text
- `email_body` - HTML email content
- `settings` - JSON configuration (timings, conditions)
- `utm_status` - UTM tracking enabled (0/1)
- `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` - UTM parameters
- `delay` - Step-specific delay in seconds (deprecated, use settings.timings)
- `sequence` - Execution order within parent sequence
- `created_at` - Creation timestamp
- `updated_at` - Last modification timestamp

### SequenceTracker Model

**Class**: `\FluentCrm\App\Models\SequenceTracker`
**Table**: `wp_fc_sequence_tracker`

**Key Fields**:
- `id` - Auto-increment primary key
- `campaign_id` - Foreign key to `fc_email_sequences.id`
- `subscriber_id` - Foreign key to `fc_subscribers.id`
- `last_sequence_id` - Last executed email ID
- `next_sequence_id` - Next email to execute
- `status` - Enrollment status: 'active', 'completed', 'cancelled'
- `type` - Tracker type (default: 'sequence_tracker')
- `last_executed_time` - When last email was sent
- `next_execution_time` - When next email should be sent
- `notes` - Execution notes or error messages
- `created_at` - Enrollment timestamp
- `updated_at` - Last update timestamp

---

## Settings Object Structure

### Sequence Settings

The `settings` field in `fc_email_sequences` contains a comprehensive JSON object:

```json
{
  "mailer_settings": {
    "from_name": "",           // Sender name (empty = use global)
    "from_email": "",          // Sender email (empty = use global)
    "reply_to_name": "",       // Reply-to name
    "reply_to_email": "",      // Reply-to email
    "is_custom": "yes|no"      // Use custom vs global settings
  },
  "subscription_status": "subscribed|pending|unsubscribed",
  "subscription_status_lists": [],  // List IDs for status filtering
  "subscription_status_tags": []    // Tag IDs for status filtering
}
```

### SequenceEmail Settings

The `settings` field in `fc_email_sequence_emails` contains timing and execution rules:

```json
{
  "timings": {
    "delay": 1,              // Delay amount (integer)
    "delay_unit": "days"     // Unit: 'minutes', 'hours', 'days', 'weeks'
  },
  "conditions": {
    // Optional conditional logic for this email
  },
  "template_id": null,       // Email template reference
  "design_template": "simple" // Design template identifier
}
```

**Delay Units**:
- `minutes` - Delay in minutes
- `hours` - Delay in hours
- `days` - Delay in days (most common)
- `weeks` - Delay in weeks

**Example Delays**:
```json
// Send immediately
{"timings": {"delay": 0, "delay_unit": "minutes"}}

// Send after 1 day
{"timings": {"delay": 1, "delay_unit": "days"}}

// Send after 3 hours
{"timings": {"delay": 3, "delay_unit": "hours"}}

// Send after 2 weeks
{"timings": {"delay": 2, "delay_unit": "weeks"}}
```

---

## Conditions Object Structure

The `conditions` field defines trigger rules for sequence enrollment:

```json
{
  "tags": [1, 5, 10],           // Required tag IDs
  "lists": [2, 8],              // Required list IDs
  "run_only_one": "yes|no",     // Enroll subscriber only once
  "can_enter_existing_contacts": "yes|no"  // Allow existing contacts
}
```

**Condition Evaluation**:
- **tags**: Subscriber must have ALL specified tags
- **lists**: Subscriber must be in ALL specified lists
- **run_only_one**: If 'yes', prevent re-enrollment after completion
- **can_enter_existing_contacts**: If 'yes', apply to existing subscribers on publish

---

## Status Values

### Sequence Status

| Status | Description | Use Case |
|--------|-------------|----------|
| `draft` | Editing state | Sequence being composed |
| `published` | Active/running | Sequence actively enrolling subscribers |
| `archived` | Inactive/historical | Sequence stopped or completed |

**Transitions**:
- `draft` → `published` - Activate sequence
- `published` → `archived` - Deactivate sequence
- `archived` → `draft` - Reactivate for editing
- Any status can transition to any other status

### SequenceEmail Status

| Status | Description | Use Case |
|--------|-------------|----------|
| `draft` | Editing state | Email being composed |
| `published` | Active | Email ready to send |

### SequenceTracker Status

| Status | Description | Use Case |
|--------|-------------|----------|
| `active` | Enrollment in progress | Subscriber progressing through sequence |
| `completed` | All emails sent | Subscriber finished sequence |
| `cancelled` | Enrollment stopped | Subscriber unsubscribed or manually removed |

---

## Create Operations

### Create Sequence

**Request**:
```php
$sequence = \FluentCrm\App\Models\Sequence::create([
    'title' => 'Welcome Series',
    'slug' => 'welcome-series',
    'status' => 'draft',
    'type' => 'sequence',
    'settings' => [
        'mailer_settings' => [
            'from_name' => 'Sarah from Acme',
            'from_email' => 'sarah@acme.com',
            'reply_to_name' => 'Support',
            'reply_to_email' => 'support@acme.com',
            'is_custom' => 'yes'
        ],
        'subscription_status' => 'subscribed'
    ],
    'conditions' => [
        'tags' => [1, 5],
        'lists' => [],
        'run_only_one' => 'yes',
        'can_enter_existing_contacts' => 'no'
    ],
    'created_by' => get_current_user_id()
]);
```

**Response Structure**:
```json
{
  "id": 123,
  "parent_id": null,
  "title": "Welcome Series",
  "slug": "welcome-series",
  "status": "draft",
  "type": "sequence",
  "settings": {
    "mailer_settings": {
      "from_name": "Sarah from Acme",
      "from_email": "sarah@acme.com",
      "reply_to_name": "Support",
      "reply_to_email": "support@acme.com",
      "is_custom": "yes"
    },
    "subscription_status": "subscribed"
  },
  "conditions": {
    "tags": [1, 5],
    "lists": [],
    "run_only_one": "yes",
    "can_enter_existing_contacts": "no"
  },
  "created_by": 1,
  "created_at": "2025-10-04 18:00:00",
  "updated_at": "2025-10-04 18:00:00"
}
```

### Create Sequence Email

**Request**:
```php
$email = \FluentCrm\App\Models\SequenceEmail::create([
    'parent_id' => 123,
    'type' => 'sequence_mail',
    'status' => 'published',
    'email_subject' => 'Welcome to Our Community!',
    'email_pre_header' => 'Thanks for joining us',
    'email_body' => '<p>Hi {{contact.first_name}},</p><p>Welcome!</p>',
    'settings' => [
        'timings' => [
            'delay' => 1,
            'delay_unit' => 'days'
        ]
    ],
    'sequence' => 1,
    'utm_status' => 1,
    'utm_source' => 'sequence',
    'utm_medium' => 'email',
    'utm_campaign' => 'welcome-series'
]);
```

**Response Structure**:
```json
{
  "id": 456,
  "parent_id": 123,
  "type": "sequence_mail",
  "status": "published",
  "email_subject": "Welcome to Our Community!",
  "email_pre_header": "Thanks for joining us",
  "email_body": "<p>Hi {{contact.first_name}},</p><p>Welcome!</p>",
  "settings": {
    "timings": {
      "delay": 1,
      "delay_unit": "days"
    }
  },
  "sequence": 1,
  "utm_status": 1,
  "utm_source": "sequence",
  "utm_medium": "email",
  "utm_campaign": "welcome-series",
  "delay": 0,
  "created_at": "2025-10-04 18:00:00",
  "updated_at": "2025-10-04 18:00:00"
}
```

---

## Read Operations

### Read Sequence (without relationships)

**Request**:
```php
$sequence = \FluentCrm\App\Models\Sequence::find($id);
```

**Response**: Basic sequence object with parsed JSON settings and conditions as arrays

### Read Sequence with Emails

**Request**:
```php
$sequence = \FluentCrm\App\Models\Sequence::with('emails')->find($id);
```

**Response Structure**:
```json
{
  "id": 123,
  "title": "Welcome Series",
  "status": "published",
  "...": "...",
  "emails": [
    {
      "id": 456,
      "parent_id": 123,
      "email_subject": "Welcome Email 1",
      "sequence": 1,
      "settings": {
        "timings": {
          "delay": 0,
          "delay_unit": "minutes"
        }
      }
    },
    {
      "id": 457,
      "parent_id": 123,
      "email_subject": "Welcome Email 2",
      "sequence": 2,
      "settings": {
        "timings": {
          "delay": 3,
          "delay_unit": "days"
        }
      }
    }
  ]
}
```

**Notes**:
- Emails are ordered by `sequence` field (execution order)
- Settings JSON is automatically parsed to arrays
- UTM parameters are included if `utm_status = 1`

---

## Update Operations

### Update Sequence

**Request**:
```php
$sequence = \FluentCrm\App\Models\Sequence::find($id);
$sequence->update([
    'title' => 'Updated Welcome Series',
    'status' => 'published',
    'settings' => array_merge($sequence->settings, [
        'subscription_status' => 'pending'
    ])
]);
```

**Notes**:
- Partial updates supported (only changed fields)
- Settings merge preserves existing keys
- Status can be changed freely between draft/published/archived
- `updated_at` timestamp automatically updated

### Update Sequence Email

**Request**:
```php
$email = \FluentCrm\App\Models\SequenceEmail::find($id);
$email->update([
    'email_subject' => 'Updated Subject',
    'settings' => [
        'timings' => [
            'delay' => 7,
            'delay_unit' => 'days'
        ]
    ]
]);
```

**Important**: When updating email settings, provide complete `settings` object to avoid data loss

---

## Execution Flow

### Sequence Enrollment Process

```
1. Trigger Event Occurs
   ↓
2. Evaluate Sequence Conditions
   - Check tags/lists requirements
   - Verify subscription status
   - Check run_only_one setting
   ↓ (if match)
3. Create SequenceTracker
   - status: 'active'
   - next_sequence_id: first email ID
   - next_execution_time: calculated from first email delay
   ↓
4. Process Email Sequence
   - Execute emails in `sequence` order
   - Apply delay from settings.timings
   - Update tracker after each email
   - Record in campaign_emails table
   ↓
5. Complete Sequence
   - Update tracker status: 'completed'
   - Record completion timestamp
```

### Delay Calculation

```php
// Email with delay: 1 day
$settings = ['timings' => ['delay' => 1, 'delay_unit' => 'days']];

// Calculate next execution time
$delay_seconds = 1 * 24 * 60 * 60; // 86400 seconds
$next_execution = current_time('timestamp') + $delay_seconds;
```

### Execution Order

Emails execute in order of their `sequence` field:
- `sequence = 1` - First email
- `sequence = 2` - Second email
- `sequence = 3` - Third email, etc.

Within each sequence position, delays are cumulative from previous emails.

---

## Relationships

### Sequence → Emails

**Type**: HasMany
**Method**: `$sequence->emails()`
**Foreign Key**: `parent_id` in `fc_email_sequence_emails`
**Description**: All email steps in the sequence

**Usage**:
```php
$sequence = Sequence::with('emails')->find($id);
foreach ($sequence->emails as $email) {
    echo "Step {$email->sequence}: {$email->email_subject}\n";
}
```

### Sequence → Subscribers (via Tracker)

**Type**: HasManyThrough
**Method**: `$sequence->subscribers()`
**Through**: `fc_sequence_tracker`
**Description**: All enrolled subscribers

**Usage**:
```php
$sequence = Sequence::find($id);
$active_count = $sequence->subscribers()->where('status', 'active')->count();
$completed_count = $sequence->subscribers()->where('status', 'completed')->count();
```

---

## Smart Tags / Merge Fields

Sequence emails support dynamic content via smart tags:

**Contact Fields**:
- `{{contact.first_name}}` - Contact first name
- `{{contact.last_name}}` - Contact last name
- `{{contact.email}}` - Contact email address
- `{{contact.full_name}}` - Contact full name

**Custom Fields**:
- `{{contact.custom.field_name}}` - Custom field value

**Sequence Fields**:
- `{{sequence.title}}` - Sequence name
- `{{unsubscribe_url}}` - Unsubscribe link
- `{{email.view_in_browser}}` - Web version link

---

## Analytics and Tracking

### Email Performance

Track email opens, clicks, and conversions:

```php
// Get email statistics
$email = SequenceEmail::find($id);
$stats = $email->stats(); // Assuming stats() method exists

// Expected structure
[
    'sent' => 1000,
    'opens' => 450,
    'clicks' => 120,
    'unsubscribes' => 5
]
```

### Sequence Performance

Aggregate metrics across all emails in a sequence:

```php
$sequence = Sequence::with('emails')->find($id);
$total_sent = 0;
$total_opens = 0;

foreach ($sequence->emails as $email) {
    $stats = $email->stats();
    $total_sent += $stats['sent'] ?? 0;
    $total_opens += $stats['opens'] ?? 0;
}

$open_rate = $total_sent > 0 ? ($total_opens / $total_sent) * 100 : 0;
```

---

## Common Query Patterns

### Get Active Sequences

```php
$active = Sequence::where('status', 'published')->get();
```

### Get Sequences by Type

```php
$sequences = Sequence::where('type', 'sequence')->get();
```

### Get Sequence with All Data

```php
$sequence = Sequence::with(['emails' => function($query) {
    $query->orderBy('sequence', 'asc');
}])->find($id);
```

### Get Subscribers in Sequence

```php
$trackers = SequenceTracker::where('campaign_id', $sequence_id)
    ->where('status', 'active')
    ->with('subscriber')
    ->get();
```

### Get Next Scheduled Emails

```php
$due_trackers = SequenceTracker::where('next_execution_time', '<=', current_time('mysql'))
    ->where('status', 'active')
    ->with(['sequence', 'subscriber', 'next_email'])
    ->limit(100)
    ->get();
```

---

## Important Characteristics

### 1. Settings JSON Storage

Both `fc_email_sequences.settings` and `fc_email_sequence_emails.settings` are stored as JSON TEXT fields and automatically parsed to arrays by Eloquent accessors.

### 2. Delay Field Deprecation

The `delay` field in `fc_email_sequence_emails` is deprecated. Use `settings.timings.delay` and `settings.timings.delay_unit` instead for better flexibility.

### 3. Sequence Execution Order

The `sequence` field determines execution order. Gaps in numbering are allowed (e.g., 1, 3, 5, 10).

### 4. Subscriber Tracking

`fc_sequence_tracker` maintains enrollment state. One record per subscriber per sequence.

### 5. UTM Tracking

UTM parameters are optional. When `utm_status = 1`, all link in email will have UTM tags appended.

### 6. Status Independence

Sequence status ('draft', 'published', 'archived') is independent of email status. Published sequence can have draft emails (won't send).

### 7. Mailer Settings Inheritance

If `settings.mailer_settings.is_custom = 'no'`, emails use global FluentCRM sender settings.

---

## Edge Cases and Gotchas

### 1. Empty Email Body

Unlike campaigns, sequence emails can have empty `email_body` (useful for condition/action-only steps):

```php
$email = SequenceEmail::create([
    'parent_id' => $sequence_id,
    'email_subject' => 'Trigger Action',
    'email_body' => '', // Allowed
    'settings' => [...]
]);
```

### 2. Conditions in Settings vs Root

**Root `conditions`**: Sequence-level enrollment triggers
**Email `settings.conditions`**: Per-email conditional logic

Don't confuse these - they serve different purposes.

### 3. Delay Unit Conversion

Always convert delay to seconds for calculations:

```php
$delay_map = [
    'minutes' => 60,
    'hours' => 3600,
    'days' => 86400,
    'weeks' => 604800
];

$delay_seconds = $delay * $delay_map[$delay_unit];
```

### 4. Tracker Next Email ID

`next_sequence_id` in tracker is the **email ID**, not the `sequence` order number.

### 5. Sequence Deletion

Deleting a sequence does NOT cascade to emails or trackers by default. Handle cleanup manually:

```php
// Proper sequence deletion
$sequence->emails()->delete();
$sequence->trackers()->delete();
$sequence->delete();
```

---

## Validation Script

The complete validation script used to generate this documentation is available at:
`/Users/danieliser/Local Sites/mcp/app/public/wp-content/plugins/mcp-adapters/validate-fluentcrm-sequences.php`

Run it to verify current behavior:
```bash
cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-sequences.php
```

---

## Schema Version

This validation was performed against:
- **FluentCRM Version**: Active installation as of 2025-10-04
- **WordPress Version**: Compatible with WordPress 6.4+
- **PHP Version**: 8.0+

Schema may change in future FluentCRM versions. Re-run validation script after major updates.
