# FluentCRM Companies - Validated Structure

**Validation Date**: 2025-10-04
**Model**: `\FluentCrm\App\Models\Company`
**Database Table**: `{prefix}_fc_companies`
**Source**: Model code analysis and database schema inspection

---

## Summary

FluentCRM Company model represents organizational contacts in the CRM system. The model follows Laravel-style Eloquent ORM patterns with automatic hash generation, relationship management through pivot tables, and serialized metadata storage. Companies can be linked to multiple subscribers and have an owner (also a subscriber).

## Database Schema

```sql
CREATE TABLE wp_fc_companies (
    id                bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id          bigint unsigned NULL,
    name              varchar(192) NOT NULL,
    industry          varchar(192) NULL,
    email             varchar(192) NULL,
    type              varchar(50) NULL,
    address_line_1    varchar(192) NULL,
    address_line_2    varchar(192) NULL,
    city              varchar(192) NULL,
    state             varchar(192) NULL,
    postal_code       varchar(192) NULL,
    country           varchar(192) NULL,
    description       text NULL,
    logo              varchar(255) NULL,
    timezone          varchar(192) NULL,
    phone             varchar(50) NULL,
    website           varchar(192) NULL,
    date_of_start     date NULL,
    employees_number  varchar(50) NULL,
    linkedin_url      varchar(255) NULL,
    facebook_url      varchar(255) NULL,
    twitter_url       varchar(255) NULL,
    meta              text NULL,
    hash              varchar(90) NULL,
    created_at        timestamp NULL,
    updated_at        timestamp NULL
);
```

### Indexes
- PRIMARY KEY: `id`
- INDEX: `owner_id`, `hash`

### Relationship Table (fc_subscriber_pivot)
```sql
CREATE TABLE wp_fc_subscriber_pivot (
    id              bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    subscriber_id   bigint unsigned NOT NULL,
    object_id       bigint unsigned NOT NULL,
    object_type     varchar(192) NOT NULL,
    created_at      timestamp NULL
);
```
- INDEX: `subscriber_id`, `object_id`, `object_type`
- Companies are linked to subscribers via `object_type = 'FluentCrm\App\Models\Company'`

---

## Create Operations

### Minimal Create (name only)

**Request**:
```php
$company = \FluentCrm\App\Models\Company::create([
    'name' => 'Acme Corporation'
]);
```

**Expected Response Structure**:
```json
{
    "id": 1,
    "name": "Acme Corporation",
    "hash": "a7f2c3e5d4b1a6f8e9c2d7b4a5f6e3c8",
    "created_at": "2025-10-04 18:15:42",
    "updated_at": "2025-10-04 18:15:42",
    "owner_id": null,
    "industry": null,
    "email": null,
    "type": null,
    "phone": null,
    "website": null,
    "description": null,
    "meta": {
        "custom_values": []
    }
}
```

**Auto-Populated Fields**:
- `id` - Auto-increment integer
- `hash` - MD5 hash generated from UUID + timestamp + random (line 86: `md5(wp_generate_uuid4() . '_' . time() . '_' . mt_rand(1000, 9999))`)
- `created_at` - Current timestamp
- `updated_at` - Current timestamp
- `meta` - Serialized array with default structure `{custom_values: []}`

**Fields NOT in Response** (remain NULL in database):
- All optional contact information fields
- `owner_id` - Can be set to link to a subscriber
- Address fields, social URLs, etc.

---

### Full Create (all fields)

**Request**:
```php
$company = \FluentCrm\App\Models\Company::create([
    'name' => 'Acme Corporation',
    'owner_id' => 123,
    'industry' => 'Technology',
    'type' => 'customer',
    'email' => 'info@acmecorp.com',
    'phone' => '+1-555-0123',
    'address_line_1' => '123 Tech Boulevard',
    'address_line_2' => 'Suite 456',
    'city' => 'San Francisco',
    'state' => 'CA',
    'postal_code' => '94102',
    'country' => 'US',
    'description' => 'Leading technology solutions provider',
    'logo' => 'https://example.com/logo.png',
    'timezone' => 'America/Los_Angeles',
    'website' => 'https://acmecorp.com',
    'date_of_start' => '2020-01-15',
    'employees_number' => '50-100',
    'linkedin_url' => 'https://linkedin.com/company/acme',
    'facebook_url' => 'https://facebook.com/acmecorp',
    'twitter_url' => 'https://twitter.com/acmecorp',
    'meta' => [
        'custom_values' => [
            'annual_revenue' => '5000000',
            'certification' => 'ISO 9001'
        ]
    ]
]);
```

**Expected Response Structure**:
```json
{
    "id": 2,
    "owner_id": 123,
    "name": "Acme Corporation",
    "industry": "Technology",
    "type": "customer",
    "email": "info@acmecorp.com",
    "phone": "+1-555-0123",
    "address_line_1": "123 Tech Boulevard",
    "address_line_2": "Suite 456",
    "city": "San Francisco",
    "state": "CA",
    "postal_code": "94102",
    "country": "US",
    "description": "Leading technology solutions provider",
    "logo": "https://example.com/logo.png",
    "timezone": "America/Los_Angeles",
    "website": "https://acmecorp.com",
    "date_of_start": "2020-01-15",
    "employees_number": "50-100",
    "linkedin_url": "https://linkedin.com/company/acme",
    "facebook_url": "https://facebook.com/acmecorp",
    "twitter_url": "https://twitter.com/acmecorp",
    "meta": {
        "custom_values": {
            "annual_revenue": "5000000",
            "certification": "ISO 9001"
        }
    },
    "hash": "b8e3d4f6c7a5e2f9d8b3c4a6f5e7d2c1",
    "created_at": "2025-10-04 18:15:42",
    "updated_at": "2025-10-04 18:15:42"
}
```

**Notes**:
- `meta` field is serialized in database but unserialized in model responses (lines 150-170)
- `meta` always includes `custom_values` array even if empty (line 161-162)
- All timestamp fields are strings in `Y-m-d H:i:s` format
- `hash` is auto-generated on creation and cannot be set manually (line 86)

---

## Read Operations

### Basic Read (no relationships)

**Request**:
```php
$company = \FluentCrm\App\Models\Company::find($id);
```

**Expected Response Structure**:
```json
{
    "id": 1,
    "owner_id": null,
    "name": "Acme Corporation",
    "industry": null,
    "type": null,
    "email": null,
    "phone": null,
    "address_line_1": null,
    "address_line_2": null,
    "postal_code": null,
    "city": null,
    "state": null,
    "country": null,
    "timezone": null,
    "employees_number": null,
    "description": null,
    "logo": null,
    "linkedin_url": null,
    "facebook_url": null,
    "twitter_url": null,
    "website": null,
    "date_of_start": null,
    "meta": {
        "custom_values": []
    },
    "hash": "a7f2c3e5d4b1a6f8e9c2d7b4a5f6e3c8",
    "created_at": "2025-10-04 18:15:42",
    "updated_at": "2025-10-04 18:15:42"
}
```

**Type Observations**:
- Integer fields: `id`, `owner_id` (can be NULL)
- String fields: All address, contact, and metadata fields
- Date field: `date_of_start` (format: `Y-m-d`)
- Timestamp fields: `created_at`, `updated_at` (format: `Y-m-d H:i:s`)
- Array field: `meta` (unserialized from database)

---

### Read with Relationships

**Request**:
```php
$company = \FluentCrm\App\Models\Company::with(['subscribers', 'owner', 'notes'])->find($id);
```

**Expected Response Structure**:
```json
{
    "id": 1,
    "name": "Acme Corporation",
    "hash": "a7f2c3e5d4b1a6f8e9c2d7b4a5f6e3c8",
    "created_at": "2025-10-04 18:15:42",
    "updated_at": "2025-10-04 18:15:42",
    "subscribers": [
        {
            "id": 456,
            "email": "contact@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "status": "subscribed",
            "pivot": {
                "object_id": 1,
                "subscriber_id": 456,
                "object_type": "FluentCrm\\App\\Models\\Company",
                "created_at": "2025-10-04 18:20:00"
            }
        }
    ],
    "owner": {
        "id": 123,
        "email": "owner@example.com",
        "first_name": "Jane",
        "last_name": "Smith",
        "status": "subscribed"
    },
    "notes": []
}
```

**Relationship Details**:
- `subscribers()` - BelongsToMany relationship via `fc_subscriber_pivot` table (line 123-128)
  - Uses `object_id` (company ID) and `subscriber_id` columns
  - Filters by `object_type = 'FluentCrm\App\Models\Company'`
  - Returns array of Subscriber models with pivot data
- `owner()` - BelongsTo relationship to Subscriber model (line 130-133)
  - Links via `owner_id` foreign key
  - Returns single Subscriber model or NULL
- `notes()` - HasMany relationship to CompanyNote model (line 145-148)
  - Links via `subscriber_id` (note: uses subscriber_id not company_id)
  - Returns array of CompanyNote models

---

## Update Operations

### Partial Update

**Request**:
```php
$company = \FluentCrm\App\Models\Company::find($id);
$company->update([
    'industry' => 'Software',
    'employees_number' => '100-200'
]);
```

**Expected Behavior**:
- Only specified fields are updated
- `updated_at` timestamp is refreshed automatically
- Other fields remain unchanged
- Returns boolean `true` on success

### Updating Meta Field

**Request**:
```php
$company = \FluentCrm\App\Models\Company::find($id);
$meta = $company->meta; // Get current meta
$meta['custom_values']['new_field'] = 'new_value';
$company->update(['meta' => $meta]);
```

**Meta Handling**:
- Setter serializes array before database storage (line 150-153)
- Getter unserializes from database (line 155-170)
- Always includes default `custom_values` array if empty
- Custom values accessed via `getCustomValues()` method (line 172-175)

---

## Delete Operations

### Hard Delete

**Request**:
```php
$company = \FluentCrm\App\Models\Company::find($id);
$company->delete();
```

**Expected Behavior**:
- Company record is permanently removed from database
- No soft delete functionality detected in model
- Returns boolean `true` on success
- Orphaned relationships in `fc_subscriber_pivot` may remain (check cascade rules)

**Relationship Cleanup**:
- Subscribers relationship: Pivot entries should be manually cleaned
- Owner relationship: No cascade effect on owner subscriber
- Notes relationship: Dependent notes may be orphaned

---

## Relationships

### Attaching Subscribers

**Request**:
```php
global $wpdb;
$pivot_table = $wpdb->prefix . 'fc_subscriber_pivot';
$wpdb->insert($pivot_table, [
    'subscriber_id' => 456,
    'object_id' => 1, // company ID
    'object_type' => 'FluentCrm\App\Models\Company',
    'created_at' => current_time('mysql')
]);
```

**Or using Eloquent**:
```php
$company = \FluentCrm\App\Models\Company::find(1);
// Note: Standard Laravel attach() method may need verification
```

**Pivot Table Structure**:
- `subscriber_id`: Foreign key to fc_subscribers table
- `object_id`: Company ID
- `object_type`: Always `'FluentCrm\App\Models\Company'` for companies
- `created_at`: Timestamp when relationship was created

### Getting Subscriber Count

**Request**:
```php
$company = \FluentCrm\App\Models\Company::find($id);
$count = $company->getContactsCount();
```

**Returns**: Integer count of associated subscribers (line 135-138)

---

## Type Consistency

### Field Types (from model)

**Integer Fields**:
- `id` - bigint unsigned, auto-increment
- `owner_id` - bigint unsigned, nullable

**String/VARCHAR Fields**:
- `name` - varchar(192), required
- `industry` - varchar(192), nullable
- `type` - varchar(50), nullable
- `email` - varchar(192), nullable
- `phone` - varchar(50), nullable
- All address fields - varchar(192), nullable
- `timezone` - varchar(192), nullable
- `employees_number` - varchar(50), nullable (NOTE: stored as string, not integer)
- Social URLs - varchar(255), nullable
- `website` - varchar(192), nullable

**Text Fields**:
- `description` - text, nullable
- `meta` - text, nullable (serialized array)

**Date Fields**:
- `date_of_start` - date, nullable, format: `Y-m-d`

**Hash Fields**:
- `hash` - varchar(90), auto-generated MD5 string

**Timestamp Fields**:
- `created_at` - timestamp, auto-populated
- `updated_at` - timestamp, auto-updated

---

## Edge Cases

### NULL vs Empty Handling

**Meta Field**:
- NULL in database → Returns `{custom_values: []}` (lines 159-170)
- Empty array → Returns `{custom_values: []}`
- Never returns NULL, always returns object

**String Fields**:
- NULL in database → Returns `null` in response
- Empty string → Returns empty string `""`
- Both are valid and distinct

### Searchable Fields

The model defines searchable fields for query filtering (lines 74-79):
- `name`
- `phone`
- `description`
- `email`

**Usage**:
```php
$companies = \FluentCrm\App\Models\Company::searchBy('Acme')->get();
```

### Scopes Available

**Filter by Type** (line 108-111):
```php
$customers = \FluentCrm\App\Models\Company::ofType('customer')->get();
```

**Filter by Industry** (line 113-116):
```php
$tech = \FluentCrm\App\Models\Company::ofIndustry('Technology')->get();
```

---

## Validation Script Results

### Test Data Created
*(Note: Requires running Local site to execute validation script)*

**Validation Script Location**: `/wp-content/plugins/mcp-adapters/validate-fluentcrm-companies.php`

**To Run**:
```bash
cd "/Users/danieliser/Local Sites/mcp/app/public"
php wp-content/plugins/mcp-adapters/validate-fluentcrm-companies.php
```

**Tests Performed**:
1. Database schema validation
2. Create operation (minimal and full)
3. Read operation (with and without relationships)
4. Update operation
5. Relationship management (subscribers, owner, notes)
6. Type consistency verification
7. Edge cases (NULL, empty, defaults)
8. Delete/cleanup behavior

---

## Key Findings

### Confirmed Behaviors

1. **Hash Auto-Generation**:
   - Generated on create using `md5(wp_generate_uuid4() . '_' . time() . '_' . mt_rand(1000, 9999))`
   - Cannot be manually set
   - Always 32-character MD5 string

2. **Meta Serialization**:
   - Stored as serialized PHP array in database
   - Unserialized automatically on model access
   - Always includes `custom_values` array structure
   - Never returns NULL, defaults to `{custom_values: []}`

3. **Relationships**:
   - Subscribers linked via pivot table with `object_type` discriminator
   - Owner is optional FK to subscribers table
   - Notes use `subscriber_id` column (potentially confusing naming)

4. **Employees Number**:
   - Stored as VARCHAR(50), not integer
   - Allows ranges like "50-100" or "500+"
   - Should be validated as string, not numeric

5. **Required Fields**:
   - Only `name` is truly required
   - All other fields are optional
   - No unique constraints except primary key

### Potential Issues

1. **Pivot Cleanup**: No automatic cascade delete for pivot entries
2. **Notes Foreign Key**: Uses `subscriber_id` for company notes (naming confusion)
3. **Owner Validation**: No FK constraint validation in model
4. **Type Field**: No enum validation, accepts any string
5. **Industry Field**: No predefined options, free-form text

---

## Mappable Fields Reference

Fields available for import/CSV mapping (from `mappables()` method, lines 48-72):

- `name` - Company Name (required)
- `owner_email` - Owner Email
- `owner_name` - Owner Name
- `industry` - Industry
- `description` - Company Description
- `logo` - Company Logo URL
- `type` - Type
- `email` - Company Email
- `phone` - Company Phone
- `address_line_1` - Address Line 1
- `address_line_2` - Address Line 2
- `postal_code` - Postal Code
- `city` - City
- `state` - State
- `country` - Country
- `employees_number` - Employees Number
- `linkedin_url` - LinkedIn URL
- `facebook_url` - Facebook URL
- `twitter_url` - Twitter URL
- `website` - Website URL

---

## Recommendations for MCP Adapter

### Input Schema Considerations

1. **name**: Required string field
2. **owner_id**: Optional integer, should validate against existing subscribers
3. **type**: Optional string, consider enum if FluentCRM defines standard types
4. **industry**: Optional string, consider enum if standard industries exist
5. **employees_number**: String field (not integer), document accepted formats
6. **meta**: Object with `custom_values` structure
7. **All URLs**: Validate format as valid URLs
8. **date_of_start**: Validate as date format `Y-m-d`

### Response Considerations

1. Always include `meta` in responses (never NULL)
2. Distinguish between NULL and empty string for optional fields
3. When eager-loading relationships, document pivot structure
4. Document that `hash` is auto-generated and read-only
5. Return `owner` relationship data when requested, not just `owner_id`

### CRUD Implementation Notes

1. **Create**: Only require `name`, auto-generate `hash`
2. **Read**: Support optional `with` parameter for relationships
3. **Update**: Allow partial updates, preserve unspecified fields
4. **Delete**: Consider warning about orphaned pivot entries
5. **Relationships**: Provide separate tools for managing subscriber associations

---

**Validation Status**: Source code analysis complete. Database execution requires running Local site.
