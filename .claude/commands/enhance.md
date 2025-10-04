---
description: Complete enhancement workflow for existing integration
tags: [enhancement, orchestration, workflow]
disable-model-invocation: false
---

# Complete Enhancement Orchestration

You are orchestrating enhancements for existing integration: **$ARGUMENTS**

## Objective

Execute the complete enhancement workflow: discover missing tools, prioritize by ROI, implement, and polish to production quality.

## Workflow Overview

This command orchestrates:
1. Validation (if needed via `/integration:validate`)
2. Discovery (via `/enhance:discover`)
3. Implementation by priority (via `/enhance:implement`)
4. Polish (via `/enhance:polish`)

## Context Files to Read

**REQUIRED - Read first:**
1. @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md (Part 2: Enhancement)
2. @tests/e2e/fluentboards/TOOLING_AND_DESCRIPTION_AUDIT.md (Example)

## Prerequisites Check

**Check if VALIDATED_SHAPES.md exists:**
```bash
test -f tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md && echo "EXISTS" || echo "MISSING"
```

**If MISSING:**
```
⚠️  VALIDATED_SHAPES.md not found

Need to run validation first:
/integration:validate $ARGUMENTS

This will create the validation documentation needed for enhancement discovery.

Proceed with validation? (y/n)
```

## Execution Plan

### Phase 1: Validation (If Needed)

**If VALIDATED_SHAPES.md missing:**
```
/integration:validate $ARGUMENTS
```

**Skip if exists** and proceed to Phase 2.

### Phase 2: Discovery & Analysis (Estimated: 2-3 hours)

Execute:
```
/enhance:discover $ARGUMENTS
```

**Expected Output:**
- Complete gap matrix
- Unused relations identified (each = 3-5 tools!)
- Field coverage analysis
- Description quality audit
- ROI-based prioritization (P0-P4)
- Total opportunity count

**Decision Point:**
- No opportunities found → Skip to Phase 4 (Polish only)
- Opportunities found → Proceed to Phase 3

### Phase 3: Prioritized Implementation (Estimated: Varies by priority)

**Implement by priority tiers:**

**3a. P0 Implementation (Critical - ~15-20h):**
```
/enhance:implement $ARGUMENTS P0
```

**Review results, then:**

**3b. P1 Implementation (High - ~12-18h):**
```
/enhance:implement $ARGUMENTS P1
```

**Optional - User decides:**

**3c. P2 Implementation (Medium - ~8-12h):**
```
/enhance:implement $ARGUMENTS P2
```

**Decision Point after each tier:**
- Continue to next priority?
- Skip to polish?
- User chooses based on ROI

### Phase 4: Quality Polish (Estimated: 2-3 hours)

Execute:
```
/enhance:polish $ARGUMENTS
```

**Expected Output:**
- Description quality ≥8.5/10
- Parameter documentation ≥85%
- Enum values ≥90% documented
- Consistent patterns

## Orchestration Pattern

```
┌────────────────────────────────────────┐
│ Phase 1: VALIDATION (If Needed)       │
│ Check: VALIDATED_SHAPES.md exists?    │
│                                        │
│ Missing? ──Yes──> /integration:validate│
│    │                                   │
│   No                                   │
│    ↓                                   │
│ ✅ Validation Ready                   │
└────────────────────────────────────────┘
           │
           ↓
┌────────────────────────────────────────┐
│ Phase 2: DISCOVERY                    │
│ /enhance:discover $ARGUMENTS          │
│                                        │
│ Opportunities? ──No──> Skip to Polish │
│    │                                   │
│   Yes (N tools, ~M hours)             │
│    ↓                                   │
│ ✅ Gaps Identified                    │
└────────────────────────────────────────┘
           │
           ↓
┌────────────────────────────────────────┐
│ Phase 3a: P0 IMPLEMENTATION           │
│ /enhance:implement $ARGUMENTS P0      │
│                                        │
│ Success? ──No──> STOP: Fix issues     │
│    │                                   │
│   Yes                                  │
│    ↓                                   │
│ ✅ P0 Complete                        │
│                                        │
│ Continue to P1? ──No──> Skip to Polish│
│    │                                   │
│   Yes                                  │
└────────────────────────────────────────┘
           │
           ↓
┌────────────────────────────────────────┐
│ Phase 3b: P1 IMPLEMENTATION           │
│ /enhance:implement $ARGUMENTS P1      │
│                                        │
│ [Same pattern as P0]                  │
└────────────────────────────────────────┘
           │
           ↓
┌────────────────────────────────────────┐
│ Phase 4: POLISH                       │
│ /enhance:polish $ARGUMENTS            │
│                                        │
│ Quality ≥8.5? ──No──> Apply more fixes│
│    │                                   │
│   Yes                                  │
│    ↓                                   │
│ ✅ Production Quality                │
└────────────────────────────────────────┘
           │
           ↓
    ✅ COMPLETE!
```

## Progress Tracking

**After each phase:**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  $ARGUMENTS ENHANCEMENT PROGRESS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Phase 1: VALIDATION     (Complete/Skipped)
✅ Phase 2: DISCOVERY      (Complete)
   • Gap: 29 tools, 18→47 (+161%)
   • P0: 13 tools (~20h)
   • P1: 11 tools (~15h)

🔄 Phase 3a: P0 IMPLEMENTATION (In Progress)
   • Completed: 8/13 tools
   • Current: Task Comments CRUD

⏳ Phase 3b: P1 IMPLEMENTATION (Pending)
⏳ Phase 4: POLISH             (Pending)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## User Decision Points

**After Discovery:**
```
📊 DISCOVERY RESULTS: $ARGUMENTS

Opportunities Found: 29 tools

Priority Breakdown:
• P0 (Critical):  13 tools (~20h) - Team collaboration
• P1 (High):      11 tools (~15h) - Rich features
• P2 (Medium):     3 tools (~5h)  - Convenience
• P3 (Low):        2 tools (~2h)  - Edge cases

Recommended Path:
1. Implement P0 (highest ROI)
2. Implement P1 (still high value)
3. Skip P2-P3 (low ROI)

Proceed with P0? (y/n/custom)
```

**After Each Priority:**
```
✅ P0 IMPLEMENTATION COMPLETE

Results:
• Tools added: 13
• Coverage: 45% → 70% (+25pp)
• Tests: 95% passing
• Time: 18 hours

Next Options:
1. Continue to P1 (11 tools, ~15h, high ROI)
2. Skip to Polish (finish what we have)
3. Custom (implement specific P1 items)

Choose: (1/2/3)
```

## Success Criteria

**Phase 2 - Discovery:**
- ✅ All models inspected
- ✅ All relations mapped
- ✅ ROI calculated for each opportunity
- ✅ Priorities assigned (P0-P4)

**Phase 3 - Implementation (per priority):**
- ✅ All tools in priority tier implemented
- ✅ Tests passing for new tools
- ✅ Code quality checks pass

**Phase 4 - Polish:**
- ✅ Description quality ≥8.5/10
- ✅ Parameters ≥85% with examples
- ✅ Enums ≥90% documented

## Final Enhancement Summary

After all phases complete:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅ $ARGUMENTS ENHANCEMENT COMPLETE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 IMPROVEMENTS

Tools:
  Before: 18 tools
  After:  38 tools
  Added:  +20 tools (+111%)

Coverage:
  Before: 45% API coverage
  After:  85% API coverage
  Gained: +40 percentage points

Quality:
  Descriptions: 7.8/10 → 9.0/10 (+1.2)
  Parameters:   40% → 90% (+50pp with examples)
  Enums:        30% → 95% (+65pp documented)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 BY PRIORITY

P0 Implemented: 13 tools (~18h)
  • Task Assignees (4 tools)
  • Task Comments (5 tools)
  • Task Subtasks (4 tools)

P1 Implemented: 11 tools (~14h)
  • Task Watchers (3 tools)
  • Task Attachments (4 tools)
  • Description fixes (4 tools)

P2-P3: Skipped (low ROI)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

💰 ROI ANALYSIS

Time Invested: 35 hours
Value Added: ⭐⭐⭐⭐⭐ Critical features enabled
User Impact: Solo → Enterprise-ready

Before: Basic CRUD only
After:  Complete team collaboration platform

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ QUALITY VERIFIED

[✅] toArray() pattern (100%)
[✅] Field coverage ≥85%
[✅] Descriptions ≥8.5/10
[✅] Parameters ≥85%
[✅] Tests ≥80% pass
[✅] Code linting passes

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎉 READY FOR PRODUCTION

Integration upgraded from "functional" to "enterprise-grade"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Error Handling

**If discovery finds no opportunities:**
```
ℹ️  No enhancement opportunities found for $ARGUMENTS

Current State:
• Relations: 100% exposed
• Fields: 95% coverage
• Quality: 9.0/10

This integration is already excellent! 🎉

Skip to polish for final touches? (y/n)
```

## Output Format

```
🔍 STARTING ENHANCEMENT: $ARGUMENTS

Enhancement Plan:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Phase 1: Validation (if needed)
Phase 2: Discovery      (2-3h)
Phase 3: Implementation (varies)
Phase 4: Polish         (2-3h)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Starting Phase 1: Validation Check...

[Execute workflow]

[Decision points]

[Final summary]
```
