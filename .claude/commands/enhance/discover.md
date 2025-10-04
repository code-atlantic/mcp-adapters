---
description: Discover missing tools and quality improvements using parallel SuperClaude agents
tags: [enhancement, discovery, gap-analysis]
disable-model-invocation: false
---

# Enhancement Discovery - Parallel Gap Analysis

You are analyzing existing integration for: **$ARGUMENTS**

## Objective

Systematically discover missing tool opportunities using **concurrent multi-agent analysis** with SuperClaude orchestration.

## Prerequisites

**MUST exist:**
- ✅ `tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md` (or run `/integration:validate` first)
- ✅ Existing abilities in `classes/Adapters/$ARGUMENTS/`

## Context Files

**Auto-read by agents:**
- @tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md
- @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md
- @classes/Adapters/$ARGUMENTS/Abilities/*.php

## Execution Strategy: Parallel Multi-Agent Analysis

Instead of sequential analysis, spawn **4 concurrent agents** for comprehensive discovery:

```bash
/sc:spawn "Run comprehensive $ARGUMENTS enhancement analysis with 4 concurrent agents: Agent 1 analyzes existing abilities for relationship gaps using /sc:analyze --focus architecture, Agent 2 analyzes validated models for tool opportunities using /sc:task --focus explore, Agent 3 scores current description quality with /sc:analyze --focus quality, Agent 4 identifies field coverage gaps comparing VALIDATED_SHAPES.md vs ability schemas. Each agent uses /sc:document to create findings. All in one message/response." --strategy parallel --concurrent 4 --think
```

### Agent 1: Relationship Gap Analysis
**Focus:** Missing relationship tools from existing abilities
**Flags:** `--focus architecture --scope project`
**Task:**
- Read all ability files in `classes/Adapters/$ARGUMENTS/Abilities/`
- Read VALIDATED_SHAPES.md for all relations
- Create gap matrix showing missing relationship tools
- Each relation = 3-5 missing tools (add, remove, list, bulk)

**Output:** `docs/$ARGUMENTS/relationship-gaps.md`

### Agent 2: New Model Tool Opportunities
**Focus:** Tools from newly validated models
**Flags:** `--focus explore --task-manage`
**Task:**
- Read VALIDATED_SHAPES.md for all models
- For each model, identify tool opportunities:
  - CRUD operations (if model has ability)
  - Relationship operations
  - Custom operations (scopes, computed fields)
- Calculate potential tools per model

**Output:** `docs/$ARGUMENTS/new-model-opportunities.md`

### Agent 3: Description Quality Audit
**Focus:** Score all tool descriptions 1-10
**Flags:** `--focus quality --uc`
**Task:**
- Read all ability files
- Score each tool description using rubric:
  - Clarity (2× weight)
  - Completeness (2× weight)
  - Succinctness (1× weight)
  - Return value (2× weight)
  - Parameters (2× weight)
- Identify all tools scoring <8.5/10
- List specific improvements needed

**Output:** `docs/$ARGUMENTS/quality-audit.md`

### Agent 4: Field Coverage Gap Analysis
**Focus:** Missing fields in ability schemas
**Flags:** `--focus architecture --scope file`
**Task:**
- For each model in VALIDATED_SHAPES.md:
  - Count total fields from validation
  - Count fields in create/update schemas
  - Calculate coverage percentage
  - List missing fields by priority
- Identify abilities using manual field selection vs toArray()

**Output:** `docs/$ARGUMENTS/field-coverage.md`

## After All Agents Complete

**Consolidate findings with synthesis:**

```bash
/sc:analyze "Synthesize all 4 agent findings from docs/$ARGUMENTS/ into comprehensive gap matrix with USER VALUE prioritization (TIER 1: critical user needs, TIER 2: high value, TIER 3: enhanced usability). Calculate ROI for each opportunity." --format report --think-hard
```

**Create implementation roadmap:**

```bash
/sc:document "docs/$ARGUMENTS/enhancement-roadmap.md" "Create implementation roadmap from gap analysis with: TIER 1/2/3 breakdown, user stories for each feature, estimated hours, expected tool counts, business value justification" --type guide --style detailed
```

## Gap Matrix Format

Each finding should include:

```markdown
### Missing Feature: [Feature Name]

**User Story:** "As [user], I need to [action] so that [benefit]"

**Current State:** ❌ Not possible / ⚠️ Manual workaround

**Proposed Tools:**
1. [tool-name] - [description]
2. [tool-name] - [description]

**Business Value:** ⭐⭐⭐⭐⭐ (1-5 stars)
**Effort:** M (XS/S/M/L/XL) = 4-8 hours
**ROI:** (5 stars × 0.9 adoption) / 6 hours = 0.75/hour
**Priority:** P0 (Critical user need)

**Implementation Notes:**
- Use toArray() pattern
- Reference VALIDATED_SHAPES.md for schema
- Add relationship loading docs
```

## Priority Assignment

**TIER 1 - Critical User Needs (P0):**
- Features users explicitly ask for
- Core CRM/workflow functionality
- High value (⭐⭐⭐⭐⭐) + reasonable effort (≤8h)
- ROI ≥ 0.5/hour

**TIER 2 - High Value (P1):**
- Productivity enhancements
- Medium-high value (⭐⭐⭐⭐) + medium effort (4-12h)
- ROI ≥ 0.3/hour

**TIER 3 - Enhanced Usability (P2):**
- Nice-to-have features
- Medium value (⭐⭐⭐) + any effort
- Quick wins (description fixes, field additions)

## Success Criteria

✅ 4 concurrent agents complete discovery
✅ Comprehensive gap matrix created
✅ All relationships mapped (each = 3-5 tools!)
✅ Field coverage percentages calculated
✅ Description quality scored (1-10)
✅ ROI calculated for each opportunity
✅ USER VALUE prioritization (not technical complexity)
✅ Implementation roadmap ready

## Expected Findings

**Typical Results:**
- Relationship gaps: 10-20 missing tools
- New model opportunities: 15-30 tools
- Description improvements: 20-40 tools
- Field coverage gaps: 30-60% missing fields

**Total:** 40-90 enhancement opportunities

## Next Steps

After discovery complete:

**Implement by TIER:**
```bash
/enhance:implement $ARGUMENTS TIER1 --think --validate
```

**Or polish quality first:**
```bash
/enhance:polish $ARGUMENTS --loop --iterations 2
```

## Output Format

Provide executive summary:

```
✅ DISCOVERY COMPLETE: $ARGUMENTS

## Multi-Agent Analysis Results

**Agent 1 - Relationship Gaps:**
- Relations discovered: 8
- Missing tools: 28 (8 relations × 3.5 avg)
- Critical gaps: assignees, watchers, comments

**Agent 2 - New Models:**
- Models analyzed: 7
- Tool opportunities: 35
- High-value features: notes, webhooks, tracking

**Agent 3 - Quality Audit:**
- Tools scored: 45
- Below threshold (<8.5): 23 tools
- Average score: 7.2/10 → Target: 8.8/10

**Agent 4 - Field Coverage:**
- Models analyzed: 10
- Average coverage: 48%
- Missing fields: 180 total
- toArray() needed: 6 abilities

## Synthesis

**Total Opportunities:** 63 tools
- TIER 1 (Critical): 18 tools (~20h)
- TIER 2 (High): 25 tools (~30h)
- TIER 3 (Enhanced): 20 tools (~15h)

**Business Impact:**
- Current: Basic CRUD
- After TIER 1: Essential features working
- After TIER 1+2: Production-ready

**Roadmap:** docs/$ARGUMENTS/enhancement-roadmap.md

Ready for: /enhance:implement $ARGUMENTS TIER1 --think --validate
```
