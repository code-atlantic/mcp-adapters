# MCP Adapters Custom Command Usage Guide

Complete reference for all custom slash commands in this project.

## Command Syntax Rules

### Basic Format
```bash
/command:subcommand <plugin> [arguments] [--flags]
```

### Argument Parsing
- **First word** = Plugin name (required)
- **Subsequent words** = Command-specific arguments (optional)
- **FLAGS** = Applied separately after command execution (not part of arguments)

### Examples
```bash
/enhance:discover fluentcrm
# → Plugin: fluentcrm
# → No additional args

/enhance:implement fluentcrm TIER1
# → Plugin: fluentcrm
# → Tier: TIER1

/integration:validate fluentcrm SubscriberNote CompanyNote
# → Plugin: fluentcrm
# → Models: SubscriberNote, CompanyNote

/enhance:implement fluentcrm TIER2 --think --validate
# → Plugin: fluentcrm
# → Tier: TIER2
# → FLAGS applied separately by agent
```

## Available Commands

### 1. Integration Commands

#### `/integration:validate <plugin> [models...]`

**Purpose:** Validate plugin model structures and create MCP tool opportunities documentation.

**Arguments:**
- `plugin` (required) - Plugin name (fluentcrm, fluentboards, etc.)
- `models...` (optional) - Space-separated list of model names to validate
  - If omitted, validates ALL models in plugin

**Behavior:**
- Spawns N concurrent agents (1 per model)
- Each agent creates validation script + markdown documentation
- Uses VALIDATED_SHAPES.md pattern for documentation
- Identifies 4-6 tool opportunities per model

**Prerequisites:**
- Plugin must exist in `classes/Adapters/<plugin>/`
- Models must have corresponding Eloquent model files

**Outputs:**
- `scripts/validate-<plugin>-<Model>.php` - WP-CLI validation scripts
- `tests/e2e/<plugin>/<Model>-validation.md` - Complete validation docs
- Tool opportunity analysis per model

**Examples:**
```bash
# Validate all FluentCRM models
/integration:validate fluentcrm

# Validate specific FluentCRM models (7 critical models)
/integration:validate fluentcrm SubscriberNote CompanyNote Webhook FunnelSubscriber SubscriberMeta CampaignEmail FunnelSequence

# Validate all FluentBoards models
/integration:validate fluentboards
```

**Time:** 3-4 hours for 7 models (vs 10-12h sequential)

---

### 2. Enhancement Commands

#### `/enhance:discover <plugin>`

**Purpose:** Comprehensive enhancement analysis with 4 concurrent agents.

**Arguments:**
- `plugin` (required) - Plugin name

**Behavior:**
- **Agent 1**: Analyzes relationship gaps (`--focus architecture`)
- **Agent 2**: Identifies new model tool opportunities (`--focus explore`)
- **Agent 3**: Scores description quality (`--focus quality`)
- **Agent 4**: Calculates field coverage gaps (`--focus architecture`)
- Synthesizes findings with `/sc:analyze --think-hard`
- Creates prioritized TIER1/2/3 roadmap

**Prerequisites:**
- `tests/e2e/<plugin>/VALIDATED_SHAPES.md` must exist
- Run `/integration:validate` first if missing

**Outputs:**
- `docs/<plugin>/relationship-gaps.md` - Missing relationship tools
- `docs/<plugin>/new-model-opportunities.md` - 99 new tool ideas
- `docs/<plugin>/quality-audit.md` - Description quality scores
- `docs/<plugin>/field-coverage.md` - Missing field analysis
- `docs/<plugin>/discovery-summary.md` - Quick reference
- `docs/<plugin>/enhancement-roadmap.md` - **Implementation plan**

**Examples:**
```bash
# Discover FluentCRM enhancement opportunities
/enhance:discover fluentcrm

# Discover FluentBoards enhancements
/enhance:discover fluentboards
```

**Time:** ~2 hours (vs 3h sequential)

---

#### `/enhance:implement <plugin> [TIER1|TIER2|TIER3]`

**Purpose:** Implement enhancements using concurrent agents based on roadmap.

**Arguments:**
- `plugin` (required) - Plugin name
- `tier` (optional) - Implementation tier (defaults to TIER1)
  - **TIER1**: Critical user features (15-25 tools, 2 weeks)
  - **TIER2**: High-value features (12-18 tools, 5 weeks)
  - **TIER3**: Enhanced usability (8-15 tools, 3 weeks)

**Behavior:**
- Parses `docs/<plugin>/enhancement-roadmap.md`
- Spawns agents based on tier scope:
  - TIER1: 5+3 agents (2 batches)
  - TIER2: 5 agents (1 batch)
  - TIER3: 4 agents (1 batch)
- Each agent implements complete abilities with toArray() pattern
- Uses VALIDATED_SHAPES.md for complete schemas
- Ensures ≥8.5/10 description quality

**Prerequisites:**
- `docs/<plugin>/enhancement-roadmap.md` (from `/enhance:discover`)
- `tests/e2e/<plugin>/VALIDATED_SHAPES.md`
- Existing abilities in `classes/Adapters/<plugin>/Abilities/`

**Outputs:**
- New/updated ability files in `classes/Adapters/<plugin>/Abilities/`
- 15-25 new MCP tools per tier
- Complete field coverage (toArray() pattern)
- Production-quality descriptions

**Examples:**
```bash
# Implement TIER1 critical features (default)
/enhance:implement fluentcrm

# Explicitly specify TIER1
/enhance:implement fluentcrm TIER1

# Implement TIER2 high-value features
/enhance:implement fluentcrm TIER2

# Implement TIER3 usability enhancements
/enhance:implement fluentcrm TIER3
```

**Time:**
- TIER1: 8-12 hours (vs 15-20h sequential)
- TIER2: 6-8 hours (vs 12-18h sequential)
- TIER3: 3-5 hours (vs 8-12h sequential)

---

#### `/enhance:polish <plugin>`

**Purpose:** Polish description quality to ≥8.5/10 standard.

**Arguments:**
- `plugin` (required) - Plugin name

**Behavior:**
- Analyzes all tool descriptions
- Identifies tools below 8.5/10 threshold
- Adds examples, return values, relationship documentation
- Improves clarity and completeness

**Prerequisites:**
- Existing abilities in `classes/Adapters/<plugin>/Abilities/`

**Examples:**
```bash
# Polish FluentCRM descriptions
/enhance:polish fluentcrm --loop --iterations 2
```

**Time:** 2-3 hours for 135 tools

---

### 3. Testing Commands

#### `/test:create <plugin> [--focus <tier>]`

**Purpose:** Create comprehensive E2E tests using concurrent agents.

**Arguments:**
- `plugin` (required) - Plugin name
- `--focus` (optional) - Focus on specific tier tests

**Behavior:**
- **Batch 1**: 8 concurrent agents create core ability tests
- **Batch 2**: 3 concurrent agents create remaining + integration tests
- Each test file includes:
  - CRUD operations (from VALIDATED_SHAPES.md)
  - Relationship operations
  - Type consistency checks
  - Edge cases
  - List operations with filtering

**Prerequisites:**
- `tests/e2e/<plugin>/VALIDATED_SHAPES.md`
- Implemented abilities in `classes/Adapters/<plugin>/Abilities/`

**Outputs:**
- `tests/e2e/<plugin>/*.test.ts` - Complete E2E test suite
- Coverage for all tools and relationships

**Examples:**
```bash
# Create all FluentCRM tests
/test:create fluentcrm

# Create tests for specific tier
/test:create fluentcrm --focus TIER1
```

**Time:** 6-8 hours (vs 1-2 days sequential)

---

## Command Workflow Patterns

### Pattern 1: Full Integration Workflow (New Plugin)

```bash
# Phase 1: Validation (3-4 hours)
/integration:validate newplugin

# Phase 2: Discovery (2 hours)
/enhance:discover newplugin

# Phase 3: Implementation - TIER1 (8-12 hours)
/enhance:implement newplugin TIER1

# Phase 4: Polish (2-3 hours)
/enhance:polish newplugin --loop --iterations 2

# Phase 5: Testing (6-8 hours)
/test:create newplugin

# Phase 6: Implementation - TIER2 (6-8 hours)
/enhance:implement newplugin TIER2

# Phase 7: Final testing
/test:create newplugin --focus TIER2
```

**Total Time:** ~30-40 hours (vs 80-100h sequential) = **60-65% savings**

---

### Pattern 2: Add Missing Models to Existing Plugin

```bash
# Validate only new models
/integration:validate fluentcrm SubscriberNote CompanyNote Webhook

# Re-run discovery with new models
/enhance:discover fluentcrm

# Implement TIER1 features for new models
/enhance:implement fluentcrm TIER1

# Create tests for new abilities
/test:create fluentcrm --focus TIER1
```

---

### Pattern 3: Quality Improvement Pass

```bash
# Discover quality gaps
/enhance:discover fluentcrm

# Review quality-audit.md results

# Polish all descriptions
/enhance:polish fluentcrm --loop --iterations 2

# Verify with analysis
/sc:analyze "Calculate average description quality for all FluentCRM tools" --focus quality
```

---

## FLAGS Integration

All commands support global FLAGS applied AFTER execution:

### Common FLAG Patterns

```bash
# Analysis depth
/enhance:discover fluentcrm --think         # Standard depth (~4K tokens)
/enhance:discover fluentcrm --think-hard    # Deep analysis (~10K tokens)
/enhance:discover fluentcrm --ultrathink    # Maximum depth (~32K tokens)

# Execution control
/enhance:implement fluentcrm TIER1 --validate          # Pre-execution validation
/enhance:implement fluentcrm TIER1 --think --validate  # Analysis + validation
/enhance:implement fluentcrm TIER1 --safe-mode         # Maximum safety

# Output optimization
/enhance:discover fluentcrm --uc                       # Ultra-compressed output
/enhance:implement fluentcrm TIER1 --focus quality     # Quality-focused execution

# Testing
/test:create fluentcrm --play                          # Enable Playwright integration
/test:create fluentcrm --focus testing --play          # Testing-focused with browser
```

---

## Troubleshooting

### Issue: "Plugin not found"
**Solution:** Ensure plugin directory exists in `classes/Adapters/<plugin>/`

### Issue: "VALIDATED_SHAPES.md missing"
**Solution:** Run `/integration:validate <plugin>` first

### Issue: "enhancement-roadmap.md not found"
**Solution:** Run `/enhance:discover <plugin>` before `/enhance:implement`

### Issue: Arguments not parsed correctly
**Problem:** Used extra words in plugin name
**Solution:** Plugin name must be single word (first argument)

**Wrong:**
```bash
/enhance:implement "my plugin" TIER1
```

**Right:**
```bash
/enhance:implement myplugin TIER1
```

---

## Best Practices

### 1. **Always Validate First**
Before any enhancement work, run validation to establish complete schemas:
```bash
/integration:validate <plugin>
```

### 2. **Discovery Before Implementation**
Always discover opportunities before implementing:
```bash
/enhance:discover <plugin>
# Review roadmap
/enhance:implement <plugin> TIER1
```

### 3. **Implement Tiers Sequentially**
TIER1 → TIER2 → TIER3 for best results (TIER1 fixes foundation)

### 4. **Use FLAGS Appropriately**
- `--think` for standard operations
- `--validate` for critical changes
- `--safe-mode` for production environments

### 5. **Review Outputs Before Proceeding**
Each phase creates documentation - review before next phase:
- Check `docs/<plugin>/enhancement-roadmap.md` before implementing
- Review `docs/<plugin>/quality-audit.md` before polishing
- Verify `tests/e2e/<plugin>/VALIDATED_SHAPES.md` for test accuracy

---

## Quick Reference

| Command | Purpose | Time | Prerequisites |
|---------|---------|------|---------------|
| `/integration:validate` | Validate models | 3-4h | Plugin exists |
| `/enhance:discover` | Find opportunities | 2h | VALIDATED_SHAPES.md |
| `/enhance:implement` | Build features | 8-12h | Roadmap exists |
| `/enhance:polish` | Improve quality | 2-3h | Abilities exist |
| `/test:create` | Create tests | 6-8h | Abilities + validation |

---

## Support

For issues or questions:
1. Check VALIDATED_SHAPES.md for model schemas
2. Review enhancement-roadmap.md for implementation plan
3. Verify prerequisites exist for each command
4. Use FLAGS for execution control

---

**Last Updated:** 2025-10-04
**Commands Version:** 2.0 (with argument parsing)
