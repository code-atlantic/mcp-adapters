# FluentCRM Analytics and Reporting Validation

**Date**: 2025-10-04
**Site**: http://mcp.local
**FluentCRM Version**: Detected via constant FLUENTCRM
**Validation Script**: `validate-fluentcrm-analytics.php`

## Executive Summary

This document validates FluentCRM's analytics and reporting structures, documenting database models, aggregation methods, and API response formats for campaign analytics, subscriber metrics, and dashboard reporting.

---

## 1. Database Models

### 1.1 CampaignEmail Model

**Table**: `fc_campaign_emails`
**Class**: `\FluentCrm\App\Models\CampaignEmail`
**Purpose**: Tracks individual email sends per subscriber per campaign

#### Schema Fields

| Field | Type | Purpose | Notes |
|-------|------|---------|-------|
| `id` | bigint | Primary key | Auto-increment |
| `campaign_id` | bigint | Foreign key to campaigns | Required |
| `subscriber_id` | bigint | Foreign key to subscribers | Required |
| `email_address` | varchar | Email address | Snapshot at send time |
| `email_subject` | text | Email subject line | May reference subject variations |
| `email_subject_id` | bigint | Subject variation ID | For A/B testing |
| `email_body` | longtext | Parsed email body | Cached after first send |
| `email_headers` | text | Email headers | Serialized array |
| `email_hash` | varchar(32) | Tracking hash | MD5 hash for pixel/link tracking |
| `is_open` | int | Open count | Increments on each open |
| `click_counter` | int | Click count | Increments on each click |
| `is_unsubscribed` | tinyint | Unsubscribe flag | 0 or 1 |
| `is_parsed` | tinyint | Body parse flag | 0 or 1, optimization |
| `status` | varchar(20) | Email status | sent, delivered, bounced, failed, pending |
| `created_at` | timestamp | Send timestamp | First send time |
| `updated_at` | timestamp | Last action timestamp | Opens/clicks update this |

#### Relationships

```php
// One-to-many inverse
campaign()   // belongsTo Campaign
subscriber() // belongsTo Subscriber
subject()    // belongsTo Subject (email_subject_id)
```

#### Key Methods

| Method | Return | Purpose |
|--------|--------|---------|
| `data()` | array | Email data for sending (to, subject, body, headers) |
| `previewData()` | array | Preview data with full parsing |
| `getEmailSubject()` | string | Resolved email subject |
| `getEmailBody()` | string | Fully parsed and tracked email body |
| `getClicks()` | Collection | Click metrics for this email |
| `getSubjectCount($campaignId)` | Collection | Subject line counts for campaign |
| `getOpenCount($subjectId)` | int | Open count for subject variation |
| `markAs($status)` | self | Update status |
| `markAsSent($status)` | self | Mark as sent |
| `markAsFailed($status)` | self | Mark as failed |

#### Analytics Queries

**Sent Count**:
```php
CampaignEmail::where('campaign_id', $id)
    ->whereIn('status', ['sent', 'delivered'])
    ->count();
```

**Open Count** (unique opens):
```php
CampaignEmail::where('campaign_id', $id)
    ->where('is_open', '>', 0)
    ->count();
```

**Click Count** (unique clicks):
```php
CampaignEmail::where('campaign_id', $id)
    ->where('click_counter', '>', 0)
    ->count();
```

**Bounce Count**:
```php
CampaignEmail::where('campaign_id', $id)
    ->where('status', 'bounced')
    ->count();
```

**Unsubscribe Count**:
```php
CampaignEmail::where('campaign_id', $id)
    ->where('is_unsubscribed', 1)
    ->count();
```

#### Rate Calculations

```php
// Open Rate
$open_rate = $sent > 0 ? round(($opened / $sent) * 100, 2) : 0;

// Click Rate (CTR)
$click_rate = $sent > 0 ? round(($clicked / $sent) * 100, 2) : 0;

// Click-to-Open Rate (CTOR)
$ctor = $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0;

// Bounce Rate
$bounce_rate = $sent > 0 ? round(($bounced / $sent) * 100, 2) : 0;

// Unsubscribe Rate
$unsub_rate = $sent > 0 ? round(($unsubscribed / $sent) * 100, 2) : 0;
```

---

### 1.2 CampaignUrlMetric Model

**Table**: `fc_campaign_url_metrics`
**Class**: `\FluentCrm\App\Models\CampaignUrlMetric`
**Purpose**: Tracks URL clicks and unsubscribe actions per subscriber per campaign

#### Schema Fields

| Field | Type | Purpose | Notes |
|-------|------|---------|-------|
| `id` | bigint | Primary key | Auto-increment |
| `campaign_id` | bigint | Foreign key to campaigns | Required |
| `subscriber_id` | bigint | Foreign key to subscribers | Required |
| `url_id` | bigint | Foreign key to url_stores | Links to URL storage table |
| `type` | varchar(20) | Metric type | click, unsubscribe |
| `counter` | int | Event count | Increments on repeated events |
| `created_at` | timestamp | First event timestamp | |
| `updated_at` | timestamp | Last event timestamp | |

#### Relationships

```php
campaign()   // belongsTo Campaign
subscriber() // belongsTo Subscriber
url_stores() // belongsTo UrlStores (url_id)
```

#### Key Methods

| Method | Return | Purpose |
|--------|--------|---------|
| `maybeInsert($data)` | self | Upsert: create or increment counter |
| `getLinksReport($campaignId)` | array | URL click report with totals |
| `getCampaignAnalytics($campaignId)` | array | Formatted analytics with rates |
| `getSubjectStats($campaign)` | array | Subject variation performance |
| `getClickMetrics($campaignId, $subjectId)` | Collection | Click metrics for subject |

#### Analytics Queries

**Total Clicks** (aggregated):
```php
CampaignUrlMetric::where('campaign_id', $id)
    ->where('type', 'click')
    ->sum('counter');
```

**Unique Clicks** (distinct users):
```php
CampaignUrlMetric::where('campaign_id', $id)
    ->where('type', 'click')
    ->distinct('subscriber_id')
    ->count();
```

**Unsubscribe Count**:
```php
CampaignUrlMetric::where('campaign_id', $id)
    ->where('type', 'unsubscribe')
    ->distinct()
    ->count('subscriber_id');
```

**Links Report**:
```php
$metrics = CampaignUrlMetric::select([
        DB::raw('count(*) as total'),
        'fc_url_stores.url',
        'fc_url_stores.id'
    ])
    ->where('fc_campaign_url_metrics.campaign_id', $id)
    ->where('fc_campaign_url_metrics.type', 'click')
    ->groupBy('fc_campaign_url_metrics.url_id')
    ->join('fc_url_stores', 'fc_url_stores.id', '=', 'fc_campaign_url_metrics.url_id')
    ->orderBy('total', 'DESC')
    ->get();
```

#### Response Structure: `getLinksReport($campaignId)`

```php
[
    [
        'total' => 45,        // int: Total clicks
        'url'   => 'https://example.com/page',  // string: Clicked URL
        'id'    => 123        // int: URL ID
    ],
    // ... more URLs ordered by click count DESC
]
```

#### Response Structure: `getCampaignAnalytics($campaignId)`

```php
[
    'open' => [
        'total'      => 150,          // int: Unique opens
        'label'      => 'Open Rate (150)',
        'type'       => 'open',
        'is_percent' => true,
        'icon_class' => 'dashicons dashicons-buddicons-pm'
    ],
    'click' => [
        'total'      => 75,           // int: Unique clicks
        'label'      => 'Click Rate (75)',
        'type'       => 'click',
        'is_percent' => true,
        'icon_class' => 'el-icon el-icon-position'
    ],
    'ctor' => [
        'total'      => '50.00%',     // string: Formatted percentage
        'label'      => 'Click To Open Rate',
        'type'       => 'ctor',
        'icon_class' => 'el-icon el-icon-chat-dot-square'
    ],
    'unsubscribe' => [
        'total'      => 3,            // int: Unsubscribe count
        'label'      => 'Unsubscribe (3)',
        'type'       => 'unsubscribe',
        'is_percent' => true,
        'icon_class' => 'el-icon el-icon-warning-outline'
    ],
    'revenue' => [                    // Optional: Only if commerce data exists
        'label'      => 'Revenue (USD)',
        'type'       => 'revenue',
        'total'      => '1234.56',    // string: Formatted currency
        'icon_class' => 'el-icon el-icon-money'
    ]
]
```

---

### 1.3 Subscriber Model (Analytics Methods)

**Table**: `fc_subscribers`
**Class**: `\FluentCrm\App\Models\Subscriber`

#### Analytics-Related Fields

| Field | Type | Purpose |
|-------|------|---------|
| `status` | varchar(20) | Subscriber status (subscribed, pending, unsubscribed, bounced) |
| `created_at` | timestamp | Signup date |
| `updated_at` | timestamp | Last modification |
| `last_activity` | timestamp | Last engagement timestamp |
| `total_points` | int | Engagement score |
| `life_time_value` | decimal | LTV from commerce integrations |

#### Analytics Methods

While the Subscriber model doesn't have a built-in `stats()` method in the core, analytics are typically accessed through:

**Email Sent Count**:
```php
$count = CampaignEmail::where('subscriber_id', $id)->count();
```

**Email Open Count**:
```php
$count = CampaignEmail::where('subscriber_id', $id)
    ->where('is_open', '>', 0)
    ->count();
```

**Email Click Count**:
```php
$count = CampaignEmail::where('subscriber_id', $id)
    ->where('click_counter', '>', 0)
    ->count();
```

**Campaigns Received**:
```php
$campaigns = CampaignEmail::where('subscriber_id', $id)
    ->distinct('campaign_id')
    ->pluck('campaign_id');
```

---

### 1.4 Campaign Model (Analytics Methods)

**Table**: `fc_campaigns`
**Class**: `\FluentCrm\App\Models\Campaign`

Campaign analytics are typically computed via CampaignEmail and CampaignUrlMetric aggregations rather than stored fields.

#### Key Analytics Relationships

```php
// All emails sent for this campaign
$campaign->emails();  // hasMany CampaignEmail

// URL metrics for campaign
CampaignUrlMetric::where('campaign_id', $campaign->id);
```

---

## 2. Reporting Methods

### 2.1 Dashboard Stats

**Purpose**: System-wide overview metrics
**Implemented In**: `Reporting.php::execute_get_dashboard_stats()`

#### Response Structure

```json
{
    "success": true,
    "message": "Dashboard statistics retrieved successfully",
    "data": {
        "subscribers": {
            "total": 5420,
            "active": 4850,
            "pending": 320,
            "unsubscribed": 250,
            "new_30_days": 145
        },
        "campaigns": {
            "total": 87,
            "active": 3,
            "sent": 82,
            "sent_30_day": 12
        },
        "automations": {
            "total": 15,
            "active": 12
        },
        "organization": {
            "lists": 8,
            "tags": 24
        },
        "generated_at": "2025-10-04 14:23:45",
        "timezone": "America/New_York"
    }
}
```

#### Query Breakdown

```php
// Subscriber Statistics
$total_subscribers     = Subscriber::count();
$active_subscribers    = Subscriber::where('status', 'subscribed')->count();
$pending_subscribers   = Subscriber::where('status', 'pending')->count();
$unsubscribed_contacts = Subscriber::where('status', 'unsubscribed')->count();

// Campaign Statistics
$total_campaigns  = Campaign::count();
$active_campaigns = Campaign::whereIn('status', ['working', 'processing'])->count();
$sent_campaigns   = Campaign::where('status', 'sent')->count();

// Automation Statistics
$total_automations  = Funnel::count();
$active_automations = Funnel::where('status', 'published')->count();

// Organization
$total_lists = Lists::count();
$total_tags  = Tag::count();

// Recent Activity
$thirty_days_ago    = gmdate('Y-m-d H:i:s', strtotime('-30 days'));
$new_subscribers_30 = Subscriber::where('created_at', '>=', $thirty_days_ago)->count();
$campaigns_sent_30  = Campaign::where('status', 'sent')
    ->where('created_at', '>=', $thirty_days_ago)
    ->count();
```

---

### 2.2 Subscriber Growth

**Purpose**: Track subscriber acquisition over time
**Implemented In**: `Reporting.php::execute_get_subscriber_growth()`

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `start_date` | string | No | -90 days | Start date (YYYY-MM-DD) |
| `end_date` | string | No | today | End date (YYYY-MM-DD) |
| `group_by` | string | No | day | Grouping: day, week, month |

#### Response Structure

```json
{
    "success": true,
    "message": "Subscriber growth data retrieved successfully",
    "data": {
        "date_range": {
            "start": "2024-07-06",
            "end": "2025-10-04",
            "group_by": "day"
        },
        "summary": {
            "total_new": 145,
            "total_subscribed": 132,
            "total_pending": 10,
            "total_unsubscribed": 3
        },
        "growth": [
            {
                "period": "2024-07-06",
                "new": 3,
                "subscribed": 2,
                "pending": 1,
                "unsubscribed": 0,
                "cumulative": 4503
            },
            {
                "period": "2024-07-07",
                "new": 5,
                "subscribed": 4,
                "pending": 1,
                "unsubscribed": 0,
                "cumulative": 4508
            }
            // ... more periods
        ]
    }
}
```

#### Grouping Logic

```php
$format_map = [
    'day'   => 'Y-m-d',       // 2024-07-06
    'week'  => 'Y-W',         // 2024-27
    'month' => 'Y-m'          // 2024-07
];

$subscribers = Subscriber::whereBetween('created_at', [$start, $end])
    ->select('created_at', 'status')
    ->get();

foreach ($subscribers as $subscriber) {
    $period = gmdate($format_map[$group_by], strtotime($subscriber->created_at));
    // Aggregate by period...
}
```

---

### 2.3 Engagement Metrics

**Purpose**: Overall email engagement rates across campaigns
**Implemented In**: `Reporting.php::execute_get_engagement_metrics()`

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days_back` | int | No | 30 | Days to analyze (max: 365) |

#### Response Structure

```json
{
    "success": true,
    "message": "Engagement metrics retrieved successfully",
    "data": {
        "analysis_period": {
            "days": 30,
            "from_date": "2024-09-04 00:00:00",
            "to_date": "2025-10-04 14:23:45"
        },
        "metrics": {
            "avg_open_rate": "24.50%",
            "avg_click_rate": "3.75%",
            "avg_unsubscribe_rate": "0.15%",
            "avg_bounce_rate": "1.25%"
        },
        "totals": {
            "campaigns": 12,
            "sent": 45000,
            "opened": 11025,
            "clicked": 1687,
            "bounced": 562,
            "unsubscribed": 67
        }
    }
}
```

#### Calculation Logic

```php
$date_from = gmdate('Y-m-d H:i:s', strtotime("-{$days_back} days"));

// Get campaigns in period
$campaigns = Campaign::where('status', 'sent')
    ->where('created_at', '>=', $date_from)
    ->pluck('id')
    ->toArray();

// Aggregate metrics across campaigns
$total_sent         = CampaignEmail::whereIn('campaign_id', $campaigns)
    ->whereIn('status', ['sent', 'delivered'])
    ->count();

$total_opened       = CampaignEmail::whereIn('campaign_id', $campaigns)
    ->where('is_open', '>', 0)
    ->count();

$total_clicked      = CampaignEmail::whereIn('campaign_id', $campaigns)
    ->where('click_counter', '>', 0)
    ->count();

// Calculate average rates
$avg_open_rate = $total_sent > 0 ? round(($total_opened / $total_sent) * 100, 2) : 0;
```

---

### 2.4 Campaign Comparison

**Purpose**: Side-by-side performance comparison
**Implemented In**: `CampaignAnalytics.php::execute_compare_campaigns()`

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `campaign_ids` | array | Yes | 2-10 campaign IDs to compare |

#### Response Structure

```json
{
    "success": true,
    "message": "Campaign comparison data retrieved successfully",
    "data": {
        "total_campaigns": 3,
        "campaigns": [
            {
                "campaign_id": 45,
                "title": "Summer Sale 2024",
                "subject": "Save 30% This Weekend Only",
                "status": "sent",
                "sent_at": "2024-07-15 10:00:00",
                "metrics": {
                    "sent": 5000,
                    "opened": 1250,
                    "clicked": 187,
                    "bounced": 45,
                    "unsubscribed": 8
                },
                "rates": {
                    "open_rate": 25.00,
                    "click_rate": 3.74,
                    "click_to_open_rate": 14.96,
                    "bounce_rate": 0.90,
                    "unsubscribe_rate": 0.16
                }
            }
            // ... more campaigns
        ]
    }
}
```

---

### 2.5 Deliverability Report

**Purpose**: Email delivery health metrics
**Implemented In**: `Reporting.php::execute_get_deliverability_report()`

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `days_back` | int | No | 30 | Analysis period (max: 365) |

#### Response Structure

```json
{
    "success": true,
    "message": "Deliverability report retrieved successfully",
    "data": {
        "analysis_period": {
            "days": 30,
            "from_date": "2024-09-04 00:00:00",
            "to_date": "2025-10-04 14:23:45"
        },
        "metrics": {
            "total_sent": 45000,
            "total_delivered": 44325,
            "total_bounced": 675,
            "bounce_rate": "1.50%",
            "delivery_rate": "98.50%"
        },
        "bounce_types": {
            "hard_bounces": 0,
            "soft_bounces": 0,
            "note": "Detailed bounce type tracking requires specific email service provider integration"
        },
        "health_status": "excellent"
    }
}
```

#### Health Status Logic

```php
$health_status = $bounce_rate < 2
    ? 'excellent'
    : ($bounce_rate < 5 ? 'good' : 'needs_attention');
```

**Health Thresholds**:
- **excellent**: Bounce rate < 2%
- **good**: Bounce rate 2-5%
- **needs_attention**: Bounce rate > 5%

---

## 3. Type Consistency

### 3.1 Count Fields

All count queries return **integer** types:

```php
// ✓ Correct: Returns int
$count = CampaignEmail::where(...)->count();  // int

// ✓ Correct: Returns int
$total = Subscriber::count();  // int
```

### 3.2 Rate Calculations

Rate calculations return **float** or **string** (formatted):

```php
// Returns float
$rate = round(($opened / $sent) * 100, 2);  // float: 24.5

// In responses, typically formatted as string with %
$rate_string = $rate . '%';  // string: "24.5%"
```

### 3.3 Timestamps

Timestamps are **string** (MySQL datetime format) or **DateTime** objects:

```php
// Database stores as string
$subscriber->created_at;  // string: "2024-07-06 14:23:45"

// Eloquent may cast to DateTime
$subscriber->created_at;  // DateTime object
```

### 3.4 Currency Values

Currency stored as **cents (integer)**, displayed as **formatted string**:

```php
// Storage
$cents = 123456;  // int: 123456 cents = $1,234.56

// Display
$formatted = number_format($cents / 100, 2);  // string: "1234.56"
```

---

## 4. Aggregation Patterns

### 4.1 Simple Count

```php
Subscriber::count();
Subscriber::where('status', 'subscribed')->count();
```

### 4.2 Group By

```php
Subscriber::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();

// Result:
// [
//     {'status': 'subscribed', 'count': 4850},
//     {'status': 'pending', 'count': 320},
//     {'status': 'unsubscribed', 'count': 250}
// ]
```

### 4.3 Date Range Filtering

```php
$start = '2024-07-01 00:00:00';
$end   = '2024-07-31 23:59:59';

Subscriber::whereBetween('created_at', [$start, $end])->count();
```

### 4.4 Join with Relationship

```php
CampaignEmail::where('campaign_id', $id)
    ->with('subscriber')  // Eager load relationship
    ->get();
```

### 4.5 Conditional Aggregation

```php
// Count with condition
CampaignEmail::where('campaign_id', $id)
    ->where('is_open', '>', 0)
    ->count();

// Multiple conditions
CampaignEmail::where('campaign_id', $id)
    ->whereIn('status', ['sent', 'delivered'])
    ->count();
```

---

## 5. Performance Considerations

### 5.1 Indexed Fields

Analytics queries rely on indexed fields for performance:

- `campaign_id` - Primary foreign key for campaign queries
- `subscriber_id` - Primary foreign key for subscriber queries
- `status` - Frequently filtered for campaign/email states
- `created_at` - Date range queries for growth trends

### 5.2 Caching Opportunities

**Static Campaign Body** (line 405-437 in CampaignEmail.php):
```php
static $parsedEmailBody = [];
if (isset($parsedEmailBody[$this->campaign_id])) {
    return $parsedEmailBody[$this->campaign_id];
}
```

**Archived Campaign Body Cache** (line 414-419):
```php
if ($this->campaign->status == 'archived') {
    $cachedEmailBody = fluentcrm_get_campaign_meta($this->campaign_id, '_cached_email_body', true);
    if ($cachedEmailBody) {
        return $cachedEmailBody;
    }
}
```

### 5.3 Bulk Query Optimization

When retrieving campaign analytics for multiple campaigns:

```php
// ✓ Good: Single query for all campaigns
$campaigns = Campaign::whereIn('id', $campaign_ids)->get();

// ✗ Bad: N+1 queries
foreach ($campaign_ids as $id) {
    $campaign = Campaign::find($id);  // Separate query per campaign
}
```

---

## 6. Known Limitations

### 6.1 Historical Data

FluentCRM doesn't track historical snapshots by default:

- List/tag membership changes over time not tracked
- Subscriber status transitions not logged (only current status stored)
- Growth trends calculated from `created_at`, not true snapshots

### 6.2 Advanced Analytics

Some features require FluentCRM Pro or external integrations:

- **Revenue Attribution**: Requires WooCommerce/EDD integration + Pro
- **Email Client Stats**: Requires user agent tracking (not built-in)
- **Device Stats**: Requires user agent tracking (not built-in)
- **Unsubscribe Reasons**: Requires custom form or Pro features
- **Bounce Types**: Requires ESP integration (hard vs soft)

### 6.3 Real-Time Limitations

Analytics are computed on-demand via database queries:

- Large datasets may require pagination
- No pre-aggregated summary tables
- Real-time dashboards may be slow with 100k+ emails

---

## 7. Validation Results

**Validation Script**: `validate-fluentcrm-analytics.php`
**Status**: Ready for execution (requires database connection)

### Test Coverage

The validation script tests:

1. ✓ Model class existence and table names
2. ✓ Model relationships (campaign, subscriber, url_stores)
3. ✓ Tracking field presence and types
4. ✓ Analytics method availability
5. ✓ Aggregation query execution
6. ✓ Rate calculation accuracy
7. ✓ Response structure validation
8. ✓ Type consistency across operations
9. ✓ Dashboard stat generation
10. ✓ Deliverability calculations

### Running Validation

```bash
cd "/Users/danieliser/Local Sites/mcp/app/public"
php wp-content/plugins/mcp-adapters/validate-fluentcrm-analytics.php
```

**Requirements**:
- FluentCRM plugin active
- Database connection active
- At least one campaign and subscriber for full testing

---

## 8. Implementation Notes

### 8.1 MCP Adapter Integration

The FluentCRM MCP adapter implements these analytics through:

**CampaignAnalytics.php**:
- `fluentcrm/get-campaign-analytics` - Core campaign metrics
- `fluentcrm/get-campaign-contacts` - Campaign recipient list
- `fluentcrm/get-campaign-clicks` - URL click tracking
- `fluentcrm/get-campaign-opens` - Open tracking data
- `fluentcrm/compare-campaigns` - Multi-campaign comparison

**Reporting.php**:
- `fluentcrm/get-dashboard-stats` - System overview
- `fluentcrm/get-subscriber-growth` - Growth trends
- `fluentcrm/get-engagement-metrics` - Engagement rates
- `fluentcrm/get-deliverability-report` - Delivery health

### 8.2 Query Patterns

All analytics abilities follow consistent patterns:

1. **Validation**: Check model availability and campaign/subscriber existence
2. **Aggregation**: Use Eloquent query builder for counts/sums
3. **Calculation**: Compute rates with safe division (check for > 0)
4. **Formatting**: Return consistent response structure with success/data/message
5. **Type Safety**: Ensure integer counts, float rates, string timestamps

### 8.3 Error Handling

```php
try {
    // Analytics query
    $result = CampaignEmail::where(...)->count();

    return $this->get_success_response([...], 'Message');
} catch (\Exception $e) {
    return $this->get_error_response(
        'Failed to retrieve: ' . $e->getMessage(),
        'analytics_failed'
    );
}
```

---

## 9. Conclusion

FluentCRM's analytics architecture is well-structured with:

- **Comprehensive tracking** via CampaignEmail and CampaignUrlMetric models
- **Flexible aggregation** through Eloquent query builder
- **Consistent response formats** across all reporting endpoints
- **Type-safe operations** with proper integer/float handling
- **Performance optimizations** through caching and indexed queries

The MCP adapter successfully exposes these capabilities through standardized tools that maintain FluentCRM's analytics integrity while providing AI-friendly interfaces.
