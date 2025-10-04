---
description: Implement prioritized enhancements using concurrent SuperClaude agents
tags: [enhancement, implementation]
disable-model-invocation: false
---

# Enhancement Implementation - Parallel Execution

## Parse Arguments

Extract plugin name and tier from `$ARGUMENTS`:

**Expected format:** `<plugin> [TIER1|TIER2|TIER3]`

**Examples:**
- `/enhance:implement fluentcrm` → Plugin: fluentcrm, Tier: TIER1 (default)
- `/enhance:implement fluentcrm TIER2` → Plugin: fluentcrm, Tier: TIER2
- `/enhance:implement fluentboards TIER1` → Plugin: fluentboards, Tier: TIER1

**Parsing logic:**
```
words = split($ARGUMENTS by space)
plugin = words[0]
tier = words[1] if exists else "TIER1"
flags = words[2:] if exist (ignore - FLAGS are applied separately)
```

You are implementing enhancements for: **{plugin}** (Tier: **{tier}**)

## Objective

Implement prioritized tools using **concurrent agent execution** for maximum efficiency.

## Prerequisites

**MUST exist:**
- ✅ `docs/{plugin}/enhancement-roadmap.md` (from `/enhance:discover {plugin}`)
- ✅ `tests/e2e/{plugin}/VALIDATED_SHAPES.md`
- ✅ Existing abilities in `classes/Adapters/{plugin}/Abilities/`

## Context Files

**Auto-read by agents:**
- @docs/{plugin}/enhancement-roadmap.md
- @tests/e2e/{plugin}/VALIDATED_SHAPES.md
- @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md
- @classes/Adapters/{plugin}/Abilities/*.php

## Execution Strategy: Parallel Implementation

### TIER1 - Critical User Features

**Typical scope:** 15-25 tools, 3-5 abilities affected

**Strategy:** Split into 2 batches for optimal concurrency

**Batch 1: New Abilities (5 concurrent agents)**

```bash
/sc:spawn "Implement {tier} {plugin} features with 5 concurrent agents using /sc:task --strategy systematic. Each agent creates/updates one ability file: Agent 1: [Ability1.php], Agent 2: [Ability2.php], Agent 3: [Ability3.php], Agent 4: [Ability4.php], Agent 5: [Ability5.php]. Use toArray() pattern, reference VALIDATED_SHAPES.md for complete schemas, write production-quality descriptions with return values and examples. All in one message/response." --strategy parallel --concurrent 5 --think --validate
```

**Agent Requirements:**
- **Flags per agent:** `--focus quality --scope file --c7`
- **Pattern compliance:** toArray(), not manual field selection
- **Schema source:** VALIDATED_SHAPES.md (complete and accurate)
- **Description quality:** ≥8.5/10 with return values and param examples
- **Relationship docs:** "Relations NOT included by default - use 'with' parameter"

**Batch 2: Relationship Updates (3 concurrent agents)**

```bash
/sc:spawn "Continue TIER1 with 3 concurrent agents. Each updates existing ability with relationship tools: Agent 1: [Ability1.php], Agent 2: [Ability2.php], Agent 3: [Ability3.php]. Add add/remove/list/bulk operations for relationships. All in one message/response." --strategy parallel --concurrent 3 --think
```

### TIER2 - High Value Features

**Typical scope:** 12-18 tools, 3-5 abilities

**Single batch execution:**

```bash
/sc:spawn "Implement TIER2 {plugin} features with 5 concurrent agents. Each agent handles one ability enhancement using /sc:task --focus quality: Agent 1: [Ability1.php enhancement], Agent 2: [Ability2.php enhancement], Agent 3: [Ability3.php enhancement], Agent 4: [Ability4.php advanced filtering], Agent 5: [Ability5.php bulk operations]. All in one message/response." --strategy parallel --concurrent 5 --think
```

### TIER3 - Enhanced Usability

**Typical scope:** 8-15 quick wins

**Quick wins in parallel:**

```bash
/sc:spawn "Implement TIER3 {plugin} enhancements with 4 concurrent agents. Focus on quick wins: Agent 1: Advanced filtering for 2-3 abilities, Agent 2: Bulk operations for 2-3 abilities, Agent 3: Sorting options, Agent 4: Remaining relationship tools. All in one message/response." --strategy parallel --concurrent 4
```

## Implementation Patterns (For Each Agent)

### Pattern 1: New Ability Creation

**File:** `classes/Adapters/{plugin}/Abilities/NewAbility.php`

**Template:**
```php
<?php
declare(strict_types=1);

namespace Automattic\MCPAdapters\Adapters\{plugin}\Abilities;

use Automattic\MCPAdapters\Adapters\{plugin}\Base;

class NewAbility extends Base {
    public function register_tools(): array {
        return [
            'create-resource' => [
                'description' => 'Create resource. Returns created resource with all fields, auto-generated ID, and timestamps. Relations NOT included by default - use "with" parameter to load related data.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        // Complete schema from VALIDATED_SHAPES.md
                    ],
                    'required' => [...],
                ],
            ],
        ];
    }

    protected function create_resource(array $params): array {
        $model = Model::create($params);
        return $model->toArray(); // NOT manual selection!
    }
}
```

### Pattern 2: Add Relationship Tools

**Update existing ability:**

```php
'add-resource-relation' => [
    'description' => 'Attach relation to resource. Returns updated resource with attached relation. Use "with" => ["relation"] to see attached items.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'resource_id' => ['type' => 'integer'],
            'relation_id' => ['type' => 'integer'],
            'pivot_data' => [ // If BelongsToMany
                'type' => 'object',
                'properties' => [...],
            ],
        ],
        'required' => ['resource_id', 'relation_id'],
    ],
],

'remove-resource-relation' => [...],
'list-resource-relations' => [...],
'bulk-sync-resource-relations' => [...],
```

### Pattern 3: toArray() Not Manual Selection

**❌ WRONG:**
```php
return [
    'id' => $model->id,
    'name' => $model->name,
    'status' => $model->status,
];
```

**✅ RIGHT:**
```php
return $model->toArray(); // Complete, future-proof, includes all fields
```

### Pattern 4: Relationship Loading

**Every ability with relationships MUST document:**

```php
'with' => [
    'type' => 'array',
    'description' => 'Relationships to eager load. Available: tags, lists, companies, custom_fields',
    'items' => [
        'type' => 'string',
        'enum' => ['tags', 'lists', 'companies', 'custom_fields'],
    ],
],
```

## Quality Gates (Applied by Each Agent)

**Pre-implementation:**
- ✅ VALIDATED_SHAPES.md read and understood
- ✅ Existing ability patterns reviewed
- ✅ toArray() pattern confirmed

**During implementation:**
- ✅ Complete schemas from validation docs
- ✅ Production-quality descriptions (≥8.5/10)
- ✅ Return value documentation
- ✅ Parameter examples for complex types
- ✅ Relationship loading explained

**Post-implementation:**
- ✅ Tool registration verified
- ✅ No manual field selection (grep for `'id' => $model->`)
- ✅ All relationships documented
- ✅ Descriptions include "Relations NOT included by default"

## After All Agents Complete

**Verify implementation:**

```bash
/sc:analyze "Review all modified abilities in classes/Adapters/{plugin}/Abilities/ for: toArray() usage, complete schemas, description quality ≥8.5/10, relationship documentation" --focus quality --scope project
```

**Run tests if available:**

```bash
npm run test:e2e -- tests/e2e/{plugin}/
```

## Success Criteria

✅ All {tier} tools implemented
✅ toArray() pattern used (no manual selection)
✅ Complete schemas from VALIDATED_SHAPES.md
✅ Description quality ≥8.5/10
✅ Relationship loading documented
✅ Return values documented
✅ Parameter examples provided
✅ No breaking changes to existing tools

## Expected Outcomes

**TIER1 Implementation:**
- New abilities: 3-5 files created
- Updated abilities: 2-4 files modified
- New tools: 15-25
- User features: ALL critical needs met
- Time: 8-12 hours wall time (vs 15-20 sequential)

**TIER2 Implementation:**
- Enhanced abilities: 3-5 files
- New tools: 12-18
- Features: High-value productivity
- Time: 6-8 hours wall time (vs 12-18 sequential)

**TIER3 Implementation:**
- Quick wins: 4-6 abilities touched
- New tools: 8-15
- Features: Enhanced usability
- Time: 3-5 hours wall time (vs 8-12 sequential)

## Next Steps

After implementation:

**Polish descriptions:**
```bash
/enhance:polish {plugin} --loop --iterations 2
```

**Create tests:**
```bash
/test:create {plugin} --focus {tier}
```

**Verify coverage:**
```bash
/sc:analyze "Calculate coverage: current tools vs total opportunities from enhancement-roadmap.md" --format report
```

## Output Format

Provide implementation summary:

```
✅ IMPLEMENTATION COMPLETE: {plugin} {tier}

## Changes Made

**New Abilities Created:** N
- AbilityName1.php (X tools)
- AbilityName2.php (Y tools)

**Abilities Updated:** M
- ExistingAbility1.php (+Z tools)
- ExistingAbility2.php (+W tools)

**Total New Tools:** X+Y+Z+W

## Pattern Compliance

✅ toArray() usage: 100% (N/N abilities)
✅ Complete schemas: Verified from VALIDATED_SHAPES.md
✅ Description quality: 8.9/10 average (target: ≥8.5)
✅ Relationship docs: All documented with "with" parameter
✅ Return values: All tools documented

## User Impact

**Can Now:**
- ✅ [Feature 1] (Critical user need)
- ✅ [Feature 2] (High value)
- ✅ [Feature 3] (Productivity)

**Coverage:**
- Before: X tools (Y% coverage)
- After: X+N tools (Z% coverage)
- Improvement: +N tools (+P% coverage)

Ready for: /enhance:polish {plugin} or /test:create {plugin}
```
