# FluentCRM Field Coverage Analysis Report

**Analysis Date**: 2025-10-04
**Validated Against**: tests/e2e/fluentcrm/VALIDATED_SHAPES.md
**Ability Files**: classes/Adapters/FluentCRM/Abilities/*.php

---

## Executive Summary

**Average Coverage**: 58.7%
**Abilities Using toArray()**: 0/5 (0%)
**Abilities Needing toArray()**: 5/5 (100%)
**Top 3 Models with Worst Coverage**: Subscriber (54.2%), Campaign (42.9%), Sequence (50.0%)

**Critical Findings**:
1. ALL abilities use manual field selection instead of toArray()
2. Missing critical workflow fields across all models
3. Subscriber model missing 22/48 fields including user_id, company_id, custom meta
4. Campaign model missing 12/21 fields including analytics, template_id, settings details
5. Lists missing is_public field (public visibility control)

---

## Coverage Matrix

| Model | Total Fields | Ability Fields | Coverage % | Pattern | Priority |
|-------|-------------|----------------|------------|---------|----------|
| Subscriber | 48 | 26 | 54.2% | Manual | CRITICAL |
| Tag | 6 | 5 | 83.3% | Manual | MEDIUM |
| List | 7 | 6 | 85.7% | Manual | MEDIUM |
| Campaign | 21 | 9 | 42.9% | Manual | CRITICAL |
| Sequence | 10 | 5 | 50.0% | Manual | HIGH |

---

## Model: Subscriber

**Total Fields (from validation)**: 48
**Fields in Ability Schema**: 26
**Coverage**: 54.2%

### Database Schema Fields (from VALIDATED_SHAPES.md)
```
Core Identity: id, user_id, hash, contact_owner, company_id
Name Fields: prefix, first_name, last_name, full_name (computed)
Contact: email, phone, timezone
Address: address_line_1, address_line_2, postal_code, city, state, country
Location: ip, latitude, longitude
Metrics: total_points, life_time_value
Profile: status, contact_type, source, avatar, date_of_birth
Timestamps: created_at, last_activity, updated_at
Computed: photo (from avatar)
Relationships: tags, lists, notes, meta
```

### Missing Fields

**High Priority** (Critical for CRM functionality):
- `user_id` - WordPress user linkage (integration with WP users)
- `company_id` - B2B company association
- `contact_owner` - Sales/support assignment
- `contact_type` - lead/customer/partner classification
- `source` - Lead source tracking
- `total_points` - Gamification/scoring system
- `life_time_value` - Revenue tracking
- `last_activity` - Engagement tracking

**Medium Priority** (Useful workflow fields):
- `prefix` - Title (Mr/Ms/Dr)
- `latitude` / `longitude` - Geo-location data
- `ip` - Tracking/security
- `avatar` - Custom profile image URL
- `date_of_birth` - Demographics/marketing

**Low Priority** (Nice-to-have):
- Computed field `photo` is derived from avatar, not critical

### Implementation Pattern
- **Current**: Manual field selection in execute methods
- **Issue**: Hardcoded field list in responses, misses model updates

### Impact
**What functionality is missing due to field gaps**:
- No user integration (can't link CRM contacts to WordPress users)
- No company/B2B features
- No assignment/ownership features
- No lead scoring/gamification
- No revenue tracking
- No engagement metrics
- Limited demographic targeting

### Fix
```php
// In Subscribers.php execute methods, REPLACE manual arrays like:
[
    'id'         => $subscriber->id,
    'email'      => $subscriber->email,
    'first_name' => $subscriber->first_name,
    // ... hardcoded 20 more fields
]

// WITH toArray() call:
$subscriber->toArray()

// Or for filtered response:
$subscriber->makeVisible([
    'user_id', 'company_id', 'contact_owner', 'source',
    'total_points', 'life_time_value', 'last_activity'
])->toArray()
```

---

## Model: Tag

**Total Fields (from validation)**: 6
**Fields in Ability Schema**: 5
**Coverage**: 83.3%

### Database Schema Fields
```
id, title, slug, description, created_at, updated_at
```

### Missing Fields
**Low Priority**:
- None critically missing

### Implementation Pattern
- **Current**: Manual field selection
- **Should Use**: toArray() for consistency

### Impact
Coverage is good (83.3%), but manual selection creates maintenance burden.

### Fix
```php
// In Tags.php execute methods:
$tag->toArray()  // Already covers all fields
```

---

## Model: List

**Total Fields (from validation)**: 7
**Fields in Ability Schema**: 6
**Coverage**: 85.7%

### Database Schema Fields
```
id, title, slug, description, is_public, created_at, updated_at
```

### Missing Fields
**High Priority**:
- `is_public` - Public visibility control (CRITICAL for privacy/GDPR)

### Implementation Pattern
- **Current**: Manual field selection
- **Issue**: Missing is_public flag breaks privacy features

### Impact
**What functionality is missing**:
- Cannot control list visibility (public vs private)
- Privacy/GDPR compliance issues
- Cannot distinguish between public signup lists and internal segmentation lists

### Fix
```php
// In Lists.php execute methods, ADD is_public:
[
    'id'          => $list->id,
    'title'       => $list->title,
    'slug'        => $list->slug,
    'description' => $list->description,
    'is_public'   => $list->is_public,  // ADD THIS
    'created_at'  => $list->created_at,
    'updated_at'  => $list->updated_at,
]

// OR use toArray():
$list->toArray()
```

---

## Model: Campaign

**Total Fields (from validation)**: 21
**Fields in Ability Schema**: 9
**Coverage**: 42.9%

### Database Schema Fields (from Campaign table structure)
```
Core: id, type, title, email_subject, email_body, status
Design: design_template, template_id, utm_status
Settings: settings (JSON - mailer_settings, subscribers, tracking)
Scheduling: scheduled_at, send_at
Analytics: recipients_count, sent_count, open_count, click_count, bounce_count, unsubscribe_count
Ownership: created_by
Timestamps: created_at, updated_at
```

### Missing Fields

**High Priority** (Critical campaign features):
- `recipients_count` - Total recipients for campaign
- `sent_count` - Delivery tracking
- `open_count` - Engagement tracking
- `click_count` - Conversion tracking
- `bounce_count` - Deliverability tracking
- `unsubscribe_count` - List health tracking
- `template_id` - Template usage tracking
- `utm_status` - UTM parameter tracking
- `type` - campaign/recurring classification

**Medium Priority**:
- `created_by` - User attribution
- `send_at` - Actual send time vs scheduled_at

**Low Priority**:
- Detailed settings breakdown (mostly internal)

### Implementation Pattern
- **Current**: Manual field selection
- **Issue**: Missing ALL analytics fields

### Impact
**What functionality is missing**:
- No campaign analytics (opens, clicks, bounces)
- No delivery tracking
- No template reuse tracking
- No UTM parameter management
- Cannot distinguish campaign types (one-off vs recurring)
- No user attribution for created campaigns

### Fix
```php
// In Campaigns.php execute_get_campaign(), REPLACE:
$campaign_data = [
    'id'              => $campaign->id,
    'title'           => $campaign->title,
    // ... 9 manual fields
];

// WITH:
$campaign_data = $campaign->toArray();

// If stats are optional, keep the include_stats pattern but use toArray():
if ( ! empty( $args['include_stats'] ) ) {
    $campaign_data['stats'] = [
        'total_recipients' => $campaign->recipients_count ?? 0,
        'sent'             => $campaign->sent_count ?? 0,
        'opened'           => $campaign->open_count ?? 0,
        'clicked'          => $campaign->click_count ?? 0,
        'bounced'          => $campaign->bounce_count ?? 0,
        'unsubscribed'     => $campaign->unsubscribe_count ?? 0,
    ];
}
```

---

## Model: Sequence

**Total Fields (from validation)**: 10
**Fields in Ability Schema**: 5
**Coverage**: 50.0%

### Database Schema Fields
```
Core: id, title, description, status
Settings: settings (JSON)
Type: type (sequence type classification)
Analytics: total_sent, total_opened, total_clicked
Timestamps: created_at, updated_at
```

### Missing Fields

**High Priority** (Analytics and classification):
- `type` - Sequence type classification
- `total_sent` - Delivery tracking
- `total_opened` - Engagement tracking
- `total_clicked` - Conversion tracking

**Medium Priority**:
- None beyond the above

### Implementation Pattern
- **Current**: Manual field selection
- **Issue**: Missing all analytics fields

### Impact
**What functionality is missing**:
- No sequence performance tracking
- Cannot distinguish sequence types
- No engagement analytics
- Limited optimization insights

### Fix
```php
// In Sequences.php format_sequence_response(), REPLACE:
private function format_sequence_response( $sequence ): array {
    return [
        'id'          => $sequence->id,
        'title'       => $sequence->title,
        'description' => $sequence->description ?? '',
        'status'      => $sequence->status,
        'settings'    => $sequence->settings,
        'created_at'  => $sequence->created_at,
        'updated_at'  => $sequence->updated_at ?? $sequence->created_at,
    ];
}

// WITH:
private function format_sequence_response( $sequence ): array {
    $data = $sequence->toArray();
    // Normalize NULL description to empty string for API consistency
    $data['description'] = $data['description'] ?? '';
    return $data;
}
```

---

## Recommendations

### Immediate Actions (Critical Priority)

1. **Add is_public to Lists**
   - File: `classes/Adapters/FluentCRM/Abilities/Lists.php`
   - Lines: 456-464, 518-526, 595-601
   - Add `'is_public' => $list->is_public` to all response arrays

2. **Add Campaign Analytics Fields**
   - File: `classes/Adapters/FluentCRM/Abilities/Campaigns.php`
   - Method: `execute_get_campaign()`, `execute_list_campaigns()`
   - Add all count fields (recipients_count, sent_count, open_count, click_count, bounce_count, unsubscribe_count)

3. **Add Subscriber Core Fields**
   - File: `classes/Adapters/FluentCRM/Abilities/Subscribers.php`
   - Add: user_id, company_id, contact_owner, source, contact_type, total_points, life_time_value, last_activity

### High Priority (Performance & Maintainability)

4. **Convert ALL abilities to toArray() pattern**
   - Eliminates manual field selection
   - Auto-adapts to model changes
   - Reduces maintenance burden
   - Standard Eloquent pattern

5. **Add Sequence Analytics**
   - File: `classes/Adapters/FluentCRM/Abilities/Sequences.php`
   - Add: type, total_sent, total_opened, total_clicked

### Medium Priority (Future Enhancement)

6. **Add Field Documentation**
   - Document why certain fields are excluded (if any)
   - Add PHPDoc blocks explaining field filtering logic
   - Link to VALIDATED_SHAPES.md for field reference

7. **Create Field Coverage Tests**
   - E2E tests validating all documented fields are returned
   - Automated coverage reports comparing schemas to responses
   - CI/CD integration to catch regressions

---

## toArray() Migration Guide

### Why toArray()?

**Benefits**:
- Auto-includes all model fields
- Adapts to schema changes automatically
- Standard Laravel/Eloquent pattern
- Reduces code duplication
- Less maintenance overhead

**Current Problem**:
```php
// Manual (BAD): 30 lines of hardcoded field mapping
$result[] = [
    'id'         => $subscriber->id,
    'email'      => $subscriber->email,
    'first_name' => $subscriber->first_name,
    // ... 25 more lines
];

// toArray() (GOOD): 1 line, auto-complete
$result[] = $subscriber->toArray();
```

### Migration Pattern

**Before**:
```php
public function execute_get_subscriber( array $args ): array {
    $subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

    return $this->get_success_response(
        [
            'subscriber' => [
                'id'         => $subscriber->id,
                'email'      => $subscriber->email,
                // ... 20 more fields manually mapped
            ],
        ],
        'Subscriber retrieved successfully'
    );
}
```

**After**:
```php
public function execute_get_subscriber( array $args ): array {
    $subscriber = \FluentCrm\App\Models\Subscriber::find( $subscriber_id );

    return $this->get_success_response(
        [
            'subscriber' => $subscriber->toArray(),
        ],
        'Subscriber retrieved successfully'
    );
}
```

### Handling Special Cases

**Computed Fields**:
```php
// If model has appends/accessors, they're included automatically
$subscriber->toArray();  // Includes 'full_name', 'photo' accessors
```

**Hiding Sensitive Fields**:
```php
// If you need to hide fields (rare in our case):
$subscriber->makeHidden(['hash', 'ip'])->toArray();
```

**Adding Extra Data**:
```php
$data = $subscriber->toArray();
$data['custom_field'] = 'custom value';
return $this->get_success_response(['subscriber' => $data], 'Success');
```

---

## Test Coverage Implications

### Current Test Issues

**Tests are passing with partial data**:
- Tests only validate fields they explicitly check
- Missing fields are silently ignored
- No comprehensive field coverage validation

**Example**:
```javascript
// Test passes but misses 22 fields
expect(response.data.subscriber).toHaveProperty('email');
expect(response.data.subscriber).toHaveProperty('first_name');
// Missing: user_id, company_id, contact_owner, ... (20 more)
```

### Recommended Test Pattern

**Add field exhaustiveness tests**:
```javascript
describe('Subscriber field coverage', () => {
    it('should return all validated fields from VALIDATED_SHAPES.md', async () => {
        const response = await callTool('fluentcrm-get-subscriber', { subscriber_id: 1 });

        // Validate ALL 48 fields are present
        const expectedFields = [
            'id', 'user_id', 'hash', 'contact_owner', 'company_id',
            'prefix', 'first_name', 'last_name', 'email', 'timezone',
            // ... all 48 fields
        ];

        expectedFields.forEach(field => {
            expect(response.data.subscriber).toHaveProperty(field);
        });
    });
});
```

---

## Appendix: Field Inventory by Model

### Subscriber (48 fields)
**Currently Included (26)**: id, email, status, first_name, last_name, full_name, address_line_1, address_line_2, postal_code, city, state, country, phone, timezone, date_of_birth, hash, created_at, updated_at, photo, tags (rel), lists (rel), notes (rel), meta (rel)

**Missing (22)**: user_id, contact_owner, company_id, prefix, ip, latitude, longitude, total_points, life_time_value, contact_type, source, avatar, last_activity

### Tag (6 fields)
**Currently Included (5)**: id, title, slug, description, created_at, updated_at
**Missing (1)**: None (toArray would include all)

### List (7 fields)
**Currently Included (6)**: id, title, slug, description, created_at, updated_at
**Missing (1)**: is_public

### Campaign (21 fields)
**Currently Included (9)**: id, title, subject, email_body, status, type, template_id, design_template, settings, scheduled_at, created_at, updated_at
**Missing (12)**: recipients_count, sent_count, open_count, click_count, bounce_count, unsubscribe_count, utm_status, created_by, send_at

### Sequence (10 fields)
**Currently Included (5)**: id, title, description, status, settings, created_at, updated_at
**Missing (5)**: type, total_sent, total_opened, total_clicked

---

## Next Steps

1. **Review & Prioritize**: Stakeholder review of missing fields impact
2. **Implement Critical Fixes**: Add is_public, campaign analytics, subscriber core fields
3. **toArray() Migration**: Convert all abilities to toArray() pattern
4. **Update Tests**: Add field exhaustiveness tests
5. **Documentation**: Update ability schemas to reflect new fields
6. **Validation**: Re-run VALIDATED_SHAPES validation after changes
7. **Release**: Version bump with comprehensive field support

---

**Report Generated**: 2025-10-04
**Analyst**: Agent 4 - Field Coverage Analysis
**Source**: tests/e2e/fluentcrm/VALIDATED_SHAPES.md
**Methodology**: Manual schema comparison + ability code inspection