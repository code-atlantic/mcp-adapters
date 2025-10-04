# FluentCRM Email Template - Validated Structure

**Validation Date**: 2025-10-04
**Model**: `\FluentCrm\App\Models\Template`
**Database Table**: `{prefix}_posts` (Custom Post Type)
**Controller**: `\FluentCrm\App\Http\Controllers\TemplateController`

---

## Summary

FluentCRM Email Templates use WordPress's `posts` table with custom post types (`fc_template` and `fluentcrm_campaigntemplate`). Template metadata (email subject, design settings, footer configuration) is stored in `postmeta`. The model uses Laravel-style Eloquent ORM with custom timestamp fields (`post_date`, `post_modified`).

## Database Schema

### Posts Table (Template Storage)

```sql
-- Templates use standard WordPress posts table
CREATE TABLE wp_posts (
    ID                  bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_author         bigint unsigned NOT NULL DEFAULT 0,
    post_date           datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_date_gmt       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_content        longtext NOT NULL,
    post_title          text NOT NULL,
    post_excerpt        text NOT NULL,
    post_status         varchar(20) NOT NULL DEFAULT 'publish',
    comment_status      varchar(20) NOT NULL DEFAULT 'open',
    ping_status         varchar(20) NOT NULL DEFAULT 'open',
    post_password       varchar(255) NOT NULL DEFAULT '',
    post_name           varchar(200) NOT NULL DEFAULT '',
    to_ping             text NOT NULL,
    pinged              text NOT NULL,
    post_modified       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_modified_gmt   datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    post_content_filtered longtext NOT NULL,
    post_parent         bigint unsigned NOT NULL DEFAULT 0,
    guid                varchar(255) NOT NULL DEFAULT '',
    menu_order          int NOT NULL DEFAULT 0,
    post_type           varchar(20) NOT NULL DEFAULT 'post',
    post_mime_type      varchar(100) NOT NULL DEFAULT '',
    comment_count       bigint NOT NULL DEFAULT 0,
    -- Indexes
    KEY type_status_date (post_type, post_status, post_date, ID),
    KEY post_author (post_author)
);
```

### Post Meta Table (Template Metadata)

```sql
CREATE TABLE wp_postmeta (
    meta_id     bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id     bigint unsigned NOT NULL DEFAULT 0,
    meta_key    varchar(255) DEFAULT NULL,
    meta_value  longtext,
    KEY post_id (post_id),
    KEY meta_key (meta_key(191))
);
```

### Template-Specific Meta Keys

| Meta Key | Type | Description |
|----------|------|-------------|
| `_email_subject` | string | Email subject line |
| `_edit_type` | string | Editor type: 'html', 'visual', 'raw_html' |
| `_design_template` | string | Design template identifier |
| `_template_config` | array (serialized) | Template configuration settings |
| `_footer_settings` | array (serialized) | Footer configuration |

---

## Custom Post Type Configuration

### Email Template CPT

**Slug**: `fc_template`
**Function**: `fluentcrmTemplateCPTSlug()` returns `'fc_template'`

**Valid Statuses**:
- `publish` - Published, available for use
- `draft` - Draft, not yet published
- `trash` - Trashed (soft delete)

### Campaign Template CPT

**Slug**: `fluentcrm_campaigntemplate` (FLUENTCRM constant + 'campaigntemplate')
**Function**: `fluentcrmCampaignTemplateCPTSlug()` returns `FLUENTCRM . 'campaigntemplate'`

**Purpose**: Specialized templates for campaign emails

---

## Model Configuration

### Template Model Properties

```php
class Template extends Model {
    // Custom timestamp field names
    const CREATED_AT = 'post_date';
    const UPDATED_AT = 'post_modified';

    // Table and key configuration
    protected $table = 'posts';
    protected $primaryKey = 'ID';

    // Timestamps use WordPress post date fields
    public $timestamps = true;
}
```

**Key Differences from Standard Eloquent**:
- Uses `post_date` instead of `created_at`
- Uses `post_modified` instead of `updated_at`
- Primary key is `ID` (uppercase) not `id`
- Table is `posts` not dedicated template table

---

## Create Operations

### Minimal Email Template Create

**Request**:
```php
$template = \FluentCrm\App\Models\Template::create([
    'post_title' => 'My Email Template',
    'post_content' => '<p>Email content here</p>',
    'post_type' => fluentcrmTemplateCPTSlug(), // 'fc_template'
    'post_status' => 'publish'
]);
```

**Response Structure**:
```json
{
    "ID": 5678,
    "post_title": "My Email Template",
    "post_content": "<p>Email content here</p>",
    "post_excerpt": "",
    "post_type": "fc_template",
    "post_status": "publish",
    "post_author": "0",
    "post_date": "2025-10-04 18:30:00",
    "post_date_gmt": "2025-10-04 18:30:00",
    "post_modified": "2025-10-04 18:30:00",
    "post_modified_gmt": "2025-10-04 18:30:00"
}
```

**Auto-Populated Fields**:
- `ID` - Auto-increment primary key
- `post_date` - Current timestamp (WordPress format)
- `post_date_gmt` - Current GMT timestamp
- `post_modified` - Current timestamp
- `post_modified_gmt` - Current GMT timestamp
- `post_excerpt` - Empty string if not provided
- `post_author` - Current user ID or 0

### Full Template Create with Metadata

**Request** (via TemplateController):
```php
// POST to /wp-json/fluent-crm/v2/templates
{
    "template": {
        "post_title": "Welcome Email",
        "post_content": "<h1>Welcome {{subscriber.first_name}}!</h1>",
        "post_excerpt": "New subscriber welcome template",
        "email_subject": "Welcome to Our Newsletter",
        "edit_type": "html",
        "design_template": "simple",
        "settings": {
            "template_config": {
                "content_padding": 20,
                "primary_color": "#4A90E2"
            },
            "footer_settings": {
                "custom_footer": "yes",
                "footer_content": "<p>Unsubscribe | Manage Preferences</p>"
            }
        }
    }
}
```

**Database Changes**:
1. **Posts Table** - New record with `post_type='fc_template'`
2. **Postmeta Table** - Multiple meta records:
   - `_email_subject` → "Welcome to Our Newsletter"
   - `_edit_type` → "html"
   - `_design_template` → "simple"
   - `_template_config` → serialized array
   - `_footer_settings` → serialized array

**Response**:
```json
{
    "message": "Template successfully created",
    "template_id": 5678
}
```

### Create Campaign Template

**Request**:
```php
$template = \FluentCrm\App\Models\Template::create([
    'post_title' => 'Campaign Email Template',
    'post_content' => '<h1>Special Campaign</h1>',
    'post_type' => fluentcrmCampaignTemplateCPTSlug(),
    'post_status' => 'publish'
]);
```

**Notes**:
- Uses different `post_type` value
- Same structure as email templates
- Typically used for campaign-specific designs

### Create Draft Template

**Request**:
```php
$template = \FluentCrm\App\Models\Template::create([
    'post_title' => 'Draft Template',
    'post_content' => '<p>Work in progress</p>',
    'post_type' => 'fc_template',
    'post_status' => 'draft'
]);
```

**Notes**:
- `post_status='draft'` prevents template from appearing in published lists
- Can be updated to `'publish'` later
- Not available for selection in campaigns until published

---

## Read Operations

### Read Single Template by ID

**Request**:
```php
$template = \FluentCrm\App\Models\Template::find($id);
```

**Response Structure**:
```json
{
    "ID": 5678,
    "post_author": "1",
    "post_date": "2025-10-04 18:30:00",
    "post_date_gmt": "2025-10-04 18:30:00",
    "post_content": "<h1>Welcome {{subscriber.first_name}}!</h1>",
    "post_title": "Welcome Email",
    "post_excerpt": "New subscriber welcome template",
    "post_status": "publish",
    "post_type": "fc_template",
    "post_modified": "2025-10-04 18:30:00",
    "post_modified_gmt": "2025-10-04 18:30:00",
    "comment_status": "open",
    "ping_status": "open",
    "post_password": "",
    "post_name": "welcome-email",
    "guid": "http://mcp.local/?post_type=fc_template&#038;p=5678",
    "post_parent": "0",
    "menu_order": "0",
    "post_mime_type": "",
    "comment_count": "0"
}
```

**Field Types**:
- `ID`: integer
- `post_author`: string (numeric string like "1")
- Timestamps: strings in `Y-m-d H:i:s` format
- Numeric strings: `post_parent`, `menu_order`, `comment_count`

### Read Template with Metadata

**Request** (via TemplateController):
```php
// GET /wp-json/fluent-crm/v2/templates/{id}
$response = TemplateController::template($request, $id);
```

**Response Structure**:
```json
{
    "template": {
        "post_title": "Welcome Email",
        "post_content": "<h1>Welcome {{subscriber.first_name}}!</h1>",
        "post_excerpt": "New subscriber welcome template",
        "email_subject": "Welcome to Our Newsletter",
        "edit_type": "html",
        "design_template": "simple",
        "settings": {
            "template_config": {
                "content_padding": 20,
                "primary_color": "#4A90E2"
            },
            "footer_settings": {
                "custom_footer": "yes",
                "footer_content": "<p>Unsubscribe | Manage Preferences</p>"
            }
        }
    }
}
```

**Meta Field Resolution**:
- `email_subject` ← `get_post_meta($id, '_email_subject', true)`
- `edit_type` ← `get_post_meta($id, '_edit_type', true)` (default: 'html')
- `design_template` ← `get_post_meta($id, '_design_template', true)`
- `settings.template_config` ← `get_post_meta($id, '_template_config', true)`
- `settings.footer_settings` ← `get_post_meta($id, '_footer_settings', true)`

### Query Email Templates (Scope)

**Request**:
```php
$templates = \FluentCrm\App\Models\Template::emailTemplates()->get();
```

**SQL Generated**:
```sql
SELECT * FROM wp_posts
WHERE post_type = 'fc_template'
AND post_status IN ('publish')
```

**With Multiple Statuses**:
```php
$templates = \FluentCrm\App\Models\Template::emailTemplates(['publish', 'draft'])->get();
```

**Result**: Collection of Template objects

### Query Campaign Templates (Scope)

**Request**:
```php
$templates = \FluentCrm\App\Models\Template::campaignTemplate()->get();
```

**SQL Generated**:
```sql
SELECT * FROM wp_posts
WHERE post_type = 'fluentcrm_campaigntemplate'
AND post_status = 'publish'
```

**Note**: Campaign template scope only returns published templates

### List Templates with Pagination

**Request** (via TemplateController):
```php
// GET /wp-json/fluent-crm/v2/templates?page=1&per_page=20&order=desc&orderBy=ID
$response = TemplateController::templates($request);
```

**Query Parameters**:
- `types`: Array of statuses (default: `['publish', 'draft']`)
- `search`: Search by `post_title` (LIKE query)
- `order`: 'asc' or 'desc' (default: 'desc')
- `orderBy`: Column name (default: 'ID')
- `page`: Page number (default: 1)
- `per_page`: Results per page (default: 20)

**Response Structure**:
```json
{
    "templates": {
        "data": [
            {
                "ID": 5678,
                "post_title": "Welcome Email",
                "post_status": "publish",
                "post_date": "2025-10-04 18:30:00",
                "design_template": "simple"
            }
        ],
        "current_page": 1,
        "per_page": 20,
        "total": 45,
        "last_page": 3
    }
}
```

**Notes**:
- Each template includes `design_template` from postmeta
- Paginated using Eloquent's `paginate()` method
- Search is case-insensitive LIKE query

---

## Update Operations

### Update Template Title

**Request**:
```php
$template = \FluentCrm\App\Models\Template::find($id);
$template->update([
    'post_title' => 'Updated Template Title'
]);
```

**Database Changes**:
- `post_title` updated
- `post_modified` auto-updated to current timestamp
- `post_modified_gmt` auto-updated to current GMT timestamp

**Response** (after `fresh()`):
```json
{
    "ID": 5678,
    "post_title": "Updated Template Title",
    "post_modified": "2025-10-04 19:15:00",
    "post_modified_gmt": "2025-10-04 19:15:00"
}
```

### Update Template Content

**Request**:
```php
$template->update([
    'post_content' => '<h1>New Email Content</h1><p>Updated body</p>'
]);
```

**Notes**:
- Content can include HTML and FluentCRM smartcodes (e.g., `{{subscriber.first_name}}`)
- `post_modified` timestamps auto-update
- WordPress `wp_kses_post()` sanitization may be applied

### Update Template Status

**Request**:
```php
$template->update([
    'post_status' => 'draft' // or 'publish', 'trash'
]);
```

**Valid Status Values**:
- `publish` - Active and available
- `draft` - Hidden from published lists
- `trash` - Soft deleted (can be restored)

### Update Template with Metadata

**Request** (via TemplateController):
```php
// PUT /wp-json/fluent-crm/v2/templates/{id}
{
    "template": {
        "post_title": "Updated Welcome Email",
        "post_content": "<h1>Hello {{subscriber.first_name}}!</h1>",
        "email_subject": "New Subject Line",
        "settings": {
            "template_config": {
                "content_padding": 30
            }
        }
    }
}
```

**Database Changes**:
1. **Posts Table** - Updates `post_title`, `post_content`, `post_modified`
2. **Postmeta Table** - Updates:
   - `_email_subject`
   - `_template_config`
   - Other meta fields as provided

**Response**:
```json
{
    "message": "Template successfully updated",
    "template_id": 5678
}
```

### Partial Updates

**Request**:
```php
// Only update specific fields
$template->update([
    'post_excerpt' => 'New description'
]);
```

**Notes**:
- Only specified fields change
- `post_modified` always updates
- Other fields remain unchanged
- No required fields for updates

---

## Delete Operations

### Soft Delete (Trash)

**Request**:
```php
$template = \FluentCrm\App\Models\Template::find($id);
$template->update(['post_status' => 'trash']);
```

**Database Changes**:
- `post_status` → 'trash'
- Template hidden from normal queries
- Can be restored later
- Postmeta retained

### Permanent Delete

**Request**:
```php
$template = \FluentCrm\App\Models\Template::find($id);
$template->delete();
```

**Database Changes**:
- Post record deleted from `wp_posts`
- Associated postmeta deleted from `wp_postmeta` (via WordPress `wp_delete_post()`)
- Cannot be undone

**Verification**:
```php
$check = \FluentCrm\App\Models\Template::find($id);
// Returns null if deleted
```

**Notes**:
- Eloquent `delete()` method triggers WordPress post deletion hooks
- All relationships and meta cleaned up automatically

---

## Template Settings Structure

### Template Config (_template_config)

**Storage**: Serialized array in postmeta
**Meta Key**: `_template_config`

**Default Structure**:
```php
[
    'content_padding' => 20,        // Integer: padding in pixels
    'primary_color' => '#4A90E2',   // String: hex color code
    'background_color' => '#FFFFFF', // String: hex color code
    'text_color' => '#333333',      // String: hex color code
    'link_color' => '#0073AA',      // String: hex color code
    'font_family' => 'Arial',       // String: font name
    'container_width' => 600        // Integer: width in pixels
]
```

**Access**:
```php
$config = get_post_meta($template_id, '_template_config', true);
if (!is_array($config)) {
    $config = [];
}

// Set default if missing
if (!isset($config['content_padding'])) {
    $config['content_padding'] = 20;
}
```

### Footer Settings (_footer_settings)

**Storage**: Serialized array in postmeta
**Meta Key**: `_footer_settings`

**Structure**:
```php
[
    'custom_footer' => 'yes',  // String: 'yes' or 'no'
    'footer_content' => '<p>Unsubscribe | Manage Preferences</p>' // String: HTML content
]
```

**Default Values**:
```php
[
    'custom_footer' => 'no',
    'footer_content' => ''
]
```

**Usage**:
```php
$footer = get_post_meta($template_id, '_footer_settings', true);
if (!$footer || !is_array($footer)) {
    $footer = [
        'custom_footer' => 'no',
        'footer_content' => ''
    ];
}
```

### Edit Type (_edit_type)

**Storage**: String in postmeta
**Meta Key**: `_edit_type`

**Valid Values**:
- `'html'` - Visual HTML editor (default)
- `'visual'` - Visual block editor
- `'raw_html'` - Raw HTML code editor

**Default**: `'html'`

**Access**:
```php
$edit_type = get_post_meta($template_id, '_edit_type', true);
if (!$edit_type) {
    $edit_type = 'html';
}
```

### Email Subject (_email_subject)

**Storage**: String in postmeta
**Meta Key**: `_email_subject`

**Purpose**: Default subject line for emails using this template

**Notes**:
- Can include FluentCRM smartcodes: `{{subscriber.first_name}}`
- Falls back to `post_title` if not set
- Actual campaign emails can override this

**Access**:
```php
$subject = get_post_meta($template_id, '_email_subject', true);
if (!$subject) {
    $subject = get_the_title($template_id);
}
```

### Design Template (_design_template)

**Storage**: String in postmeta
**Meta Key**: `_design_template`

**Purpose**: Identifier for template design/layout system

**Common Values**:
- `'simple'` - Basic single-column layout
- `'classic'` - Traditional email layout
- `'modern'` - Contemporary design
- `'raw_html'` - No predefined layout

**Default**: Retrieved from `Helper::getDefaultEmailTemplate()`

**Access**:
```php
$design = get_post_meta($template_id, '_design_template', true);
if (!$design) {
    $design = \FluentCrm\App\Services\Helper::getDefaultEmailTemplate();
}
```

---

## Template Rendering

### Render Method

**Purpose**: Parse template content and replace smartcodes

**Request**:
```php
$template = \FluentCrm\App\Models\Template::find($id);
$rendered = $template->render();
```

**Process**:
1. Get `post_content`
2. Pass through `Parser::parse($content, [])`
3. Replace FluentCRM smartcodes (e.g., `{{subscriber.first_name}}`)
4. Return rendered HTML string

**Render Custom Content**:
```php
$custom_content = '<p>Hello {{subscriber.first_name}}</p>';
$rendered = $template->render($custom_content);
```

**Notes**:
- Without subscriber context, placeholders remain unreplaced
- Parser handles smartcode syntax: `{{subscriber.field}}`, `{{contact.field}}`
- Can include conditional logic with FluentCRM syntax

### Smartcode Examples

**Subscriber Fields**:
- `{{subscriber.first_name}}`
- `{{subscriber.last_name}}`
- `{{subscriber.email}}`
- `{{subscriber.full_name}}`

**Contact Fields**:
- `{{contact.address_line_1}}`
- `{{contact.city}}`
- `{{contact.country}}`

**System Fields**:
- `{{crm.business_name}}`
- `{{crm.business_address}}`
- `{{crm.unsubscribe_url}}`
- `{{crm.manage_subscription_url}}`

---

## Duplicate Template

### Duplicate Operation

**Request** (via TemplateController):
```php
// POST /wp-json/fluent-crm/v2/templates/{id}/duplicate
$response = TemplateController::duplicate($template_id);
```

**Process**:
1. Load original template
2. Create new post with:
   - `post_title` → `'[Duplicate] ' . original_title`
   - `post_content` → Copy of original
   - `post_excerpt` → Copy of original
   - `post_type` → Same as original
   - `post_status` → 'publish'
3. Copy all meta fields:
   - `_email_subject`
   - `_edit_type`
   - `_template_config`
   - `_footer_settings`
   - `_design_template`

**Response**:
```json
{
    "message": "Template duplicated successfully",
    "template_id": 5679
}
```

**Notes**:
- New template gets fresh timestamps
- Original template unchanged
- All metadata copied exactly

---

## Type Consistency Findings

### ID Fields

- ✅ `ID`: integer (primary key)
- ⚠️ `post_author`: string (numeric string like "1")
- ⚠️ `post_parent`: string (numeric string like "0")

### Numeric Fields

- ⚠️ `menu_order`: string (numeric string like "0")
- ⚠️ `comment_count`: string (numeric string like "0")

### Timestamp Fields

- ✅ `post_date`: string in `Y-m-d H:i:s` format
- ✅ `post_date_gmt`: string in `Y-m-d H:i:s` format
- ✅ `post_modified`: string in `Y-m-d H:i:s` format
- ✅ `post_modified_gmt`: string in `Y-m-d H:i:s` format

**Format**: All timestamps are strings, not DateTime objects
**Pattern**: `/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/`

### String Fields

- ✅ `post_title`: text (can be empty string)
- ✅ `post_content`: longtext (HTML content)
- ✅ `post_excerpt`: text (can be empty string)
- ✅ `post_status`: varchar(20)
- ✅ `post_type`: varchar(20)
- ✅ `post_name`: varchar(200) (slug)
- ✅ `guid`: varchar(255) (permalink)

### Meta Value Types

- ⚠️ All postmeta values stored as `longtext` (strings)
- Arrays serialized with PHP `serialize()`
- Retrieved with `get_post_meta()` auto-unserializes
- Type casting required for numeric meta values

### Collections

- ✅ Query results: `FluentCrm\Framework\Database\Orm\Collection` objects
- ✅ Serialize to arrays correctly for JSON responses

---

## Edge Cases & Gotchas

### 1. Post Type String Matching

**Issue**: Template queries depend on exact `post_type` string matching

**Impact**:
```php
// Wrong - won't find templates
$templates = Template::where('post_type', 'template')->get();

// Correct - use helper function
$templates = Template::emailTemplates()->get();
```

**Solution**: Always use scope methods `emailTemplates()` or `campaignTemplate()` or helper functions

### 2. Timestamp Field Names

**Issue**: Model uses non-standard timestamp field names

**Impact**:
```php
// Wrong - looking for 'created_at'
$created = $template->created_at; // Returns null

// Correct - use WordPress field names
$created = $template->post_date;
```

**Solution**: Remember `CREATED_AT = 'post_date'`, `UPDATED_AT = 'post_modified'`

### 3. Meta Array Serialization

**Issue**: Meta arrays may not be arrays after retrieval

**Impact**:
```php
$config = get_post_meta($id, '_template_config', true);
// May return empty string '' instead of array []
```

**Solution**: Always validate meta is array:
```php
if (!$config || !is_array($config)) {
    $config = [];
}
```

### 4. Post Status Filtering

**Issue**: Trashed posts included in queries without status filter

**Impact**:
```php
// May include trashed templates
$templates = Template::where('post_type', 'fc_template')->get();
```

**Solution**: Use scope methods or add status filter:
```php
$templates = Template::emailTemplates(['publish', 'draft'])->get();
```

### 5. Primary Key is Uppercase 'ID'

**Issue**: WordPress uses `ID` (uppercase) not `id`

**Impact**:
```php
// Wrong - looking for lowercase 'id'
$template->id; // May not work

// Correct - uppercase 'ID'
$template->ID;
```

**Solution**: Use `ID` for WordPress posts table

### 6. Email Subject Default

**Issue**: Email subject meta may be empty

**Impact**: Templates without `_email_subject` meta return empty string

**Solution**: Fall back to `post_title`:
```php
$subject = get_post_meta($id, '_email_subject', true);
if (empty($subject)) {
    $subject = $template->post_title;
}
```

### 7. Design Template Default

**Issue**: New templates need design template

**Impact**: Missing `_design_template` causes rendering issues

**Solution**: Use helper to get default:
```php
$design = get_post_meta($id, '_design_template', true);
if (!$design) {
    $design = Helper::getDefaultEmailTemplate();
}
```

### 8. Delete Cascading

**Issue**: Deleting template should remove all meta

**Impact**: Using Eloquent `delete()` may not trigger WordPress hooks

**Solution**: Use WordPress function for complete cleanup:
```php
// Not this
$template->delete();

// Do this for complete cleanup
wp_delete_post($template->ID, true); // true = bypass trash
```

---

## Validation Script

The comprehensive validation script is available at:
`/Users/danieliser/Local Sites/mcp/app/public/wp-content/plugins/mcp-adapters/validate-fluentcrm-templates.php`

Run it to verify current behavior:
```bash
cd "/Users/danieliser/Local Sites/mcp/app/public" && php wp-content/plugins/mcp-adapters/validate-fluentcrm-templates.php
```

**Note**: Requires active WordPress installation with FluentCRM plugin enabled and database access.

---

## Schema Version

This validation was performed against:
- **FluentCRM Version**: Active installation as of 2025-10-04
- **WordPress Version**: 6.4+
- **PHP Version**: 8.0+
- **Database**: WordPress posts/postmeta tables

Schema may change in future FluentCRM versions. Re-run validation script after major updates.

---

## Testing Implications

### Type Assertions

```php
// Primary key
$this->assertIsInt($template->ID);

// Author ID is string
$this->assertIsString($template->post_author);
$this->assertEquals("1", $template->post_author);

// Timestamps are strings
$this->assertIsString($template->post_date);
$this->assertMatchesRegularExpression(
    '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
    $template->post_date
);

// Collection types
$templates = Template::emailTemplates()->get();
$this->assertInstanceOf(
    'FluentCrm\Framework\Database\Orm\Collection',
    $templates
);
```

### Meta Field Validation

```php
// Verify meta is array
$config = get_post_meta($id, '_template_config', true);
$this->assertIsArray($config);

// Check specific config values
$this->assertArrayHasKey('content_padding', $config);
$this->assertIsInt($config['content_padding']);

// Verify footer settings structure
$footer = get_post_meta($id, '_footer_settings', true);
$this->assertArrayHasKey('custom_footer', $footer);
$this->assertContains($footer['custom_footer'], ['yes', 'no']);
```

### Status Testing

```php
// Valid status values
$valid_statuses = ['publish', 'draft', 'trash'];
$this->assertContains($template->post_status, $valid_statuses);

// Draft templates not in published query
$published = Template::emailTemplates(['publish'])->get();
$draft = Template::emailTemplates(['draft'])->get();
$this->assertNotContains($draft[0]->ID, $published->pluck('ID'));
```

### Scope Query Testing

```php
// Email templates query
$email_templates = Template::emailTemplates()->get();
foreach ($email_templates as $tpl) {
    $this->assertEquals('fc_template', $tpl->post_type);
}

// Campaign templates query
$campaign_templates = Template::campaignTemplate()->get();
foreach ($campaign_templates as $tpl) {
    $this->assertEquals(FLUENTCRM . 'campaigntemplate', $tpl->post_type);
}
```

---

## Related Documentation

- WordPress Posts Table: https://codex.wordpress.org/Database_Description#Posts_Table
- WordPress Postmeta Table: https://codex.wordpress.org/Database_Description#Postmeta_Table
- FluentCRM Template Controller: `/wp-content/plugins/fluent-crm/app/Http/Controllers/TemplateController.php`
- FluentCRM Template Model: `/wp-content/plugins/fluent-crm/app/Models/Template.php`
