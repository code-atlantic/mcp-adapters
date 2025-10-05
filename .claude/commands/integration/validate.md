---
description: Validate a WordPress plugin's API structures comprehensively using parallel agents
tags: [integration, validation, discovery]
disable-model-invocation: false
---

# Plugin API Validation - Comprehensive Discovery

You are running the **Plugin API Validation** phase for: **$ARGUMENTS**

## Objective

Comprehensively validate all models, relationships, and API structures for the specified plugin using parallel agent execution and WP-CLI direct model access.

## Context Files to Read

**REQUIRED - Read these first:**
1. @.claude/SUBAGENT_BASELINE.md (Project-specific tool usage, WP-CLI, PHPCS)
2. @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md (Phase 2: Parallel Validation)
3. @tests/e2e/fluentcrm/VALIDATED_SHAPES.md (Example reference)
4. @tests/e2e/fluentboards/VALIDATED_SHAPES.md (Example reference)

## Your Task

### Step 1: Plugin Discovery (30 minutes)

**Locate the plugin:**
```bash
cd wp-content/plugins/$ARGUMENTS
```

**Find all models:**
```bash
find . -name "*.php" -type f | xargs grep -l "class.*Model" | head -20
```

**Identify key models:**
- List all model files found
- Group by feature area (Core, Content, Analytics, Settings, etc.)
- Estimate: 3-10 models depending on complexity

### Step 2: Spawn Validation Agents (Parallel Execution with SuperClaude)

**Agent Allocation:**
- Simple plugin (1-3 models): Spawn 3 concurrent agents, 1 model each
- Moderate plugin (4-7 models): Spawn 5-7 concurrent agents, 1 model each
- Complex plugin (8+ models): Spawn 8-10 concurrent agents, 1 model each

**Use SuperClaude /sc:spawn for parallel execution:**

```bash
/sc:spawn "READ @.claude/SUBAGENT_BASELINE.md for project tool usage rules. Validate N $ARGUMENTS models concurrently. Each agent validates one model using /sc:task --focus explore: creates WP-CLI validation script in validate-$ARGUMENTS-{model}.php using wp-cli-direct.sh wrapper, tests DB schema + CRUD + relationships + types + edge cases, documents findings in tests/e2e/$ARGUMENTS/{model}-validation.md following @tests/e2e/fluentcrm/VALIDATED_SHAPES.md pattern, identifies 4-6 tool opportunities per model. Use 'composer lint:fix' for PHPCS, NOT direct phpcs calls. All agents work in parallel, all in one message/response." --strategy parallel --concurrent N
```

**Example for 7 models:**
```bash
/sc:spawn "Validate 7 FluentCRM models concurrently: Subscriber, Tag, List, Campaign, Funnel, Company, Template. Each agent uses /sc:task --focus explore to validate one model, creates validation script, tests CRUD + relationships, documents in individual markdown, identifies tool opportunities. All in one message/response." --strategy parallel --concurrent 7
```

**Agent Task Requirements:**

Each agent MUST create:

1. **PHP Validation Script** that tests:
   - Database schema (SHOW CREATE TABLE)
   - Create operation (minimal + full)
   - Read operation (base + with relationships)
   - Update operation (partial updates)
   - Delete operation (soft/hard)
   - Relationship loading (eager loading with ->with())
   - Type consistency (int vs string, NULL vs empty)
   - Edge cases (minimal data, empty strings, defaults)

2. **Markdown Documentation** with:
   - Complete database schema
   - CRUD examples with request/response
   - Relationship structures (especially pivot tables)
   - Type consistency findings
   - Computed/accessor fields
   - Settings/meta object structures
   - Edge cases and gotchas
   - Tool opportunities discovered

### Step 3: Monitor and Consolidate with SuperClaude

**After all agents complete (automatic with /sc:spawn):**

Use `/sc:document` to consolidate findings:

```bash
/sc:document "Consolidate all validation reports from tests/e2e/$ARGUMENTS/*-validation.md into tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md. Organize by model, include all DB schemas, CRUD examples, relationships, type findings, edge cases, and tool opportunities. Maintain consistent formatting." --type external --style detailed
```

### Step 4: Create Summary

**Read the consolidated VALIDATED_SHAPES.md and create:**

1. **Model Summary Table:**
   | Model | Fields | Relations | Computed | Tool Potential |
   |-------|--------|-----------|----------|----------------|
   | Model1 | 25 | 3 | 2 | 12 tools |

2. **Critical Findings:**
   - Relations discovered (each = 3-5 tools!)
   - Unused fields in existing abilities
   - Type inconsistencies
   - Edge cases requiring handling

3. **Tool Opportunity Count:**
   - CRUD operations: N models × 5 = X tools
   - Relationship management: N relations × 4 = Y tools
   - Custom operations: Z tools
   - **Total Potential**: X + Y + Z tools

## Success Criteria

✅ All models validated with PHP scripts
✅ Complete VALIDATED_SHAPES.md consolidated
✅ Critical patterns documented (toArray, relations, types)
✅ Tool opportunities identified and counted
✅ Ready for next phase (implementation or gap analysis)

## Next Steps

After validation complete, user can run:
- `/integration:implement` - For new integrations
- `/enhance:discover` - For existing integrations

## Output Format

Provide a concise summary:

```
✅ VALIDATION COMPLETE: $ARGUMENTS

Models Validated: N
Validation Scripts: N files created
Documentation: tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md (X lines)

Critical Findings:
- Relations discovered: N (potential: N×4 tools)
- Unused fields: [list]
- Type issues: [summary]

Tool Potential:
- Current: X tools
- Possible: Y tools
- Gap: +Z tools (+N%)

Ready for: /integration:implement or /enhance:discover
```
