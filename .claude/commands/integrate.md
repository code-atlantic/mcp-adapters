---
description: Complete end-to-end integration workflow for a WordPress plugin
tags: [integration, orchestration, workflow]
disable-model-invocation: false
---

# Complete Integration Orchestration

You are orchestrating complete integration for: **$ARGUMENTS**

## Objective

Execute the complete integration workflow from validation through testing, coordinating all phases automatically.

## Workflow Overview

This command orchestrates the complete process:
1. Validation (via `/integration:validate`)
2. Implementation (via `/integration:implement`)
3. Testing (via `/test:create`)
4. Polish (via `/enhance:polish`)

## Context Files to Read

**REQUIRED - Read first:**
1. @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md (Complete process)
2. @docs/processes/VALIDATION_SUMMARY.md (Success examples)

## Execution Plan

### Phase 1: Validation (Estimated: 3-4 hours)

Execute:
```
/integration:validate $ARGUMENTS
```

**Expected Output:**
- N validation scripts created
- VALIDATED_SHAPES.md consolidated
- Tool opportunities identified
- Ready for implementation

**Decision Point:**
- If validation fails or incomplete → STOP, fix issues
- If validation complete → Proceed to Phase 2

### Phase 2: Implementation (Estimated: 1-3 days)

Execute:
```
/integration:implement $ARGUMENTS
```

**Expected Output:**
- BaseAbility.php created
- N ability classes created
- AbilityRegistry configured
- All tools use toArray() pattern
- Code linting passes

**Decision Point:**
- If implementation fails → STOP, debug issues
- If abilities registered successfully → Proceed to Phase 3

### Phase 3: Testing (Estimated: 4-8 hours)

Execute:
```
/test:create $ARGUMENTS
```

**Expected Output:**
- Test files created for all models
- ≥80% pass rate achieved
- Type consistency validated
- Edge cases covered

**Decision Point:**
- If pass rate < 80% → Review failures, fix, re-run
- If pass rate ≥ 80% → Proceed to Phase 4

### Phase 4: Polish (Estimated: 2-3 hours)

Execute:
```
/enhance:polish $ARGUMENTS
```

**Expected Output:**
- Description quality ≥8.5/10
- Parameter documentation ≥85%
- Enum values ≥90% documented
- Consistent patterns applied

**Decision Point:**
- If quality < targets → Apply more improvements
- If quality ≥ targets → COMPLETE ✅

## Your Task

**As orchestrator, you will:**

1. **Execute each phase in sequence**
2. **Validate success criteria before proceeding**
3. **Provide progress updates after each phase**
4. **Stop and alert user if any phase fails**
5. **Create final integration summary**

## Orchestration Pattern

```
┌─────────────────────────────────────────┐
│ Phase 1: VALIDATION                     │
│ /integration:validate $ARGUMENTS        │
│                                          │
│ Success? ──No──> STOP: Fix validation   │
│    │                                     │
│   Yes                                    │
│    ↓                                     │
│ ✅ Validation Complete                  │
└─────────────────────────────────────────┘
           │
           ↓
┌─────────────────────────────────────────┐
│ Phase 2: IMPLEMENTATION                 │
│ /integration:implement $ARGUMENTS       │
│                                          │
│ Success? ──No──> STOP: Fix abilities    │
│    │                                     │
│   Yes                                    │
│    ↓                                     │
│ ✅ Implementation Complete              │
└─────────────────────────────────────────┘
           │
           ↓
┌─────────────────────────────────────────┐
│ Phase 3: TESTING                        │
│ /test:create $ARGUMENTS                 │
│                                          │
│ Pass ≥80%? ──No──> STOP: Fix tests      │
│    │                                     │
│   Yes                                    │
│    ↓                                     │
│ ✅ Tests Passing                        │
└─────────────────────────────────────────┘
           │
           ↓
┌─────────────────────────────────────────┐
│ Phase 4: POLISH                         │
│ /enhance:polish $ARGUMENTS              │
│                                          │
│ Quality ≥8.5? ──No──> Apply more fixes  │
│    │                                     │
│   Yes                                    │
│    ↓                                     │
│ ✅ Quality Achieved                     │
└─────────────────────────────────────────┘
           │
           ↓
    ✅ COMPLETE!
```

## Progress Tracking

**After each phase, update user:**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  $ARGUMENTS INTEGRATION PROGRESS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Phase 1: VALIDATION    (Complete)
   • Models validated: N
   • Tool potential: N tools
   • Time: 3.5 hours

🔄 Phase 2: IMPLEMENTATION (In Progress)
   • Abilities created: N/M
   • Current: Implementing Model X

⏳ Phase 3: TESTING       (Pending)

⏳ Phase 4: POLISH        (Pending)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Success Criteria (All Must Pass)

**Phase 1 - Validation:**
- ✅ All models validated with scripts
- ✅ VALIDATED_SHAPES.md created
- ✅ No validation errors

**Phase 2 - Implementation:**
- ✅ All planned abilities created
- ✅ toArray() pattern used (100%)
- ✅ Code linting passes
- ✅ Abilities registered successfully

**Phase 3 - Testing:**
- ✅ Test files for all models
- ✅ Pass rate ≥80%
- ✅ Type consistency validated
- ✅ Edge cases covered

**Phase 4 - Polish:**
- ✅ Description quality ≥8.5/10
- ✅ Parameters with examples ≥85%
- ✅ Enum values documented ≥90%

## Final Integration Summary

After all phases complete:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ✅ $ARGUMENTS INTEGRATION COMPLETE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 METRICS

Models Integrated: N
Total Abilities: N
Test Pass Rate: XX%
Quality Score: X.X/10

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📁 FILES CREATED

Validation:
  • validate-$ARGUMENTS-*.php (N scripts)
  • tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md

Implementation:
  • classes/Adapters/$ARGUMENTS/BaseAbility.php
  • classes/Adapters/$ARGUMENTS/Abilities/*.php (N files)
  • classes/Adapters/$ARGUMENTS/Servers/AbilityRegistry.php

Testing:
  • tests/e2e/$ARGUMENTS/*.test.ts (N files)
  • N tests created, XX% passing

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ QUALITY CHECKLIST

[✅] Validation complete
[✅] toArray() pattern used (100%)
[✅] Field coverage ≥95%
[✅] Relations documented
[✅] Type consistency handled
[✅] Tests ≥80% pass
[✅] Descriptions ≥8.5/10
[✅] Parameters ≥85% documented
[✅] Code linting passes

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏱️  TIMELINE

Phase 1 (Validation):    3-4 hours
Phase 2 (Implementation): 1-3 days
Phase 3 (Testing):       4-8 hours
Phase 4 (Polish):        2-3 hours

Total: 2-5 days ✅ (Met timeline target)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🎯 READY FOR

✅ Production deployment
✅ User documentation
✅ Team onboarding

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Error Handling

**If any phase fails:**

1. **STOP execution**
2. **Report specific failure:**
   ```
   ⚠️  INTEGRATION PAUSED: $ARGUMENTS

   Failed Phase: [PHASE NAME]
   Error: [Specific error details]

   Next Steps:
   1. Review error above
   2. Fix issue manually
   3. Re-run: /integration:validate $ARGUMENTS
      OR continue from: /integration:implement $ARGUMENTS
   ```

3. **DO NOT proceed to next phase**
4. **Wait for user to resolve issue**

## Notes for User

**This orchestrator will:**
- ✅ Run all phases automatically
- ✅ Validate success before proceeding
- ✅ Stop if any phase fails
- ✅ Provide progress updates
- ✅ Create comprehensive summary

**Estimated total time:** 2-5 days (depending on plugin complexity)

**You can also run phases individually:**
- `/integration:validate $ARGUMENTS` - Just validation
- `/integration:implement $ARGUMENTS` - Just implementation
- `/test:create $ARGUMENTS` - Just testing
- `/enhance:polish $ARGUMENTS` - Just polish

## Output Format

Provide initial plan, then execute:

```
🚀 STARTING INTEGRATION: $ARGUMENTS

Integration Plan:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Phase 1: Validation      (3-4h)
Phase 2: Implementation  (1-3d)
Phase 3: Testing         (4-8h)
Phase 4: Polish          (2-3h)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Starting Phase 1...

[Execute /integration:validate $ARGUMENTS]

[Progress updates after each phase]

[Final summary when complete]
```
