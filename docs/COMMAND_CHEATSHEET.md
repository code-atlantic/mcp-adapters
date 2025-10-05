# MCP Adapters Command Cheatsheet

Quick reference for all custom slash commands with common workflows.

---

## 🚀 Quick Start: New Plugin Integration

```bash
# 1. Validate all models (3-4 hours)
/integration:validate <plugin>

# 2. Discover enhancement opportunities (2 hours)
/enhance:discover <plugin>

# 3. Implement critical features - TIER1 (8-12 hours)
/enhance:implement <plugin> TIER1

# 4. Create comprehensive tests (6-8 hours)
/test:create <plugin>

# 5. Polish descriptions (2-3 hours)
/enhance:polish <plugin>
```

**Total Time:** ~20-30 hours (vs 80-100h sequential) = **65-70% time savings**

---

## 📋 All Commands

### Integration Commands

#### `/integration:validate <plugin> [models...]`
Validate plugin model structures and create tool opportunities.

**Examples:**
```bash
# Validate all models in plugin
/integration:validate fluentcrm

# Validate specific models only
/integration:validate fluentcrm SubscriberNote CompanyNote Webhook

# Validate all FluentBoards models
/integration:validate fluentboards
```

**Output:**
- `scripts/validate-<plugin>-<Model>.php` - WP-CLI validation scripts
- `tests/e2e/<plugin>/<Model>-validation.md` - Complete schemas
- Tool opportunity analysis

**Time:** 3-4 hours for 7 models (vs 10-12h sequential)

---

### Enhancement Commands

#### `/enhance:discover <plugin>`
Comprehensive gap analysis with 4 concurrent agents.

**Examples:**
```bash
/enhance:discover fluentcrm
/enhance:discover fluentboards
```

**Output:**
- `docs/<plugin>/relationship-gaps.md` - Missing relationship tools
- `docs/<plugin>/new-model-opportunities.md` - Tool opportunities
- `docs/<plugin>/quality-audit.md` - Description quality scores
- `docs/<plugin>/field-coverage.md` - Missing fields analysis
- `docs/<plugin>/enhancement-roadmap.md` - **Implementation plan**

**Time:** ~2 hours (vs 3h sequential)

---

#### `/enhance:implement <plugin> [TIER1|TIER2|TIER3]`
Implement features using concurrent agents based on roadmap.

**TIER Definitions:**
- **TIER1**: Critical user needs (features users NEED to do their job)
- **TIER2**: High-value productivity (makes work faster/easier)
- **TIER3**: Enhanced usability (nice-to-have polish)

**Examples:**
```bash
# Implement TIER1 (default - critical features)
/enhance:implement fluentcrm
/enhance:implement fluentcrm TIER1

# Implement TIER2 (high-value productivity)
/enhance:implement fluentcrm TIER2

# Implement TIER3 (usability enhancements)
/enhance:implement fluentcrm TIER3
```

**What Gets Implemented:**

**TIER1 Example (FluentCRM):**
- Funnel automation tools (13 tools) - Add sequences, enroll subscribers
- Campaign analytics (6 tools) - Performance tracking
- Company management (8 tools) - B2B relationship tools
- Field coverage fixes (toArray() pattern)

**TIER2 Example (FluentCRM):**
- Bulk operations (bulk-tag, bulk-enroll)
- Advanced filtering (by engagement, custom fields)
- Segment management
- Email templates

**TIER3 Example (FluentCRM):**
- Sorting options
- Additional filters
- Export operations
- Preview tools

**Time:**
- TIER1: 8-12 hours (vs 15-20h sequential)
- TIER2: 6-8 hours (vs 12-18h sequential)
- TIER3: 3-5 hours (vs 8-12h sequential)

---

#### `/enhance:polish <plugin>`
Polish tool descriptions to ≥8.5/10 quality standard.

**Examples:**
```bash
# Polish all descriptions
/enhance:polish fluentcrm

# Polish with multiple improvement passes
/enhance:polish fluentcrm --loop --iterations 2
```

**What Gets Fixed:**
- Removes redundant label prefixes
- Adds return value descriptions
- Enhances parameter examples
- Documents partial update support
- Clarifies pagination metadata
- Adds relationship loading behavior

**Time:** 2-3 hours for 135 tools

---

### Testing Commands

#### `/test:create <plugin>`
Create comprehensive E2E test suite using concurrent agents.

**Examples:**
```bash
# Create all tests
/test:create fluentcrm

# Create tests with Playwright integration
/test:create fluentcrm --play
```

**What Gets Created:**
- Complete E2E test suite in `tests/e2e/<plugin>/abilities/*.test.ts`
- CRUD operation tests
- Relationship loading tests
- Type consistency tests
- Edge case tests
- List operation tests with filtering

**Behavior:**
- **Updates existing test files** (adds new describe() blocks)
- **Creates new files** only if missing
- **Never creates** duplicate files like `tier1-*.test.ts`

**Time:** 6-8 hours (vs 1-2 days sequential)

---

## 🔥 Common Workflows

### Workflow 1: Complete New Plugin Integration

```bash
# Step 1: Validation (3-4h)
/integration:validate myplugin

# Step 2: Discovery (2h)
/enhance:discover myplugin

# Step 3: Review roadmap, then implement TIER1 (8-12h)
/enhance:implement myplugin TIER1

# Step 4: Create tests (6-8h)
/test:create myplugin

# Step 5: Polish descriptions (2-3h)
/enhance:polish myplugin --loop --iterations 2

# Step 6: Implement TIER2 when ready (6-8h)
/enhance:implement myplugin TIER2
```

**Total: ~30-40 hours** (vs 80-100h sequential)

---

### Workflow 2: Add Missing Models to Existing Plugin

```bash
# Validate only new models
/integration:validate fluentcrm SubscriberNote CompanyNote Webhook FunnelSubscriber SubscriberMeta CampaignEmail FunnelSequence

# Re-run discovery with new models
/enhance:discover fluentcrm

# Implement TIER1 features for new models
/enhance:implement fluentcrm TIER1

# Create tests for new abilities
/test:create fluentcrm
```

**Total: ~15-20 hours**

---

### Workflow 3: Quality Improvement Pass

```bash
# Discover quality gaps
/enhance:discover fluentcrm

# Review quality-audit.md results

# Polish all descriptions
/enhance:polish fluentcrm --loop --iterations 2

# Verify improvement
/sc:analyze "Calculate average description quality for all FluentCRM tools" --focus quality
```

**Total: ~3-4 hours**

---

### Workflow 4: Continue Existing Integration (Where You Left Off)

```bash
# Check current status
ls -la docs/fluentcrm/  # See what docs exist
cat docs/fluentcrm/enhancement-roadmap.md  # See what's planned

# If TIER1 partially done, continue:
/enhance:implement fluentcrm TIER1

# If TIER1 complete, move to TIER2:
/enhance:implement fluentcrm TIER2

# If implementation done, add tests:
/test:create fluentcrm

# Final polish:
/enhance:polish fluentcrm
```

---

## 🎯 FLAGS Reference

All commands support FLAGS for execution control:

### Analysis Depth
```bash
--think         # Standard analysis (~4K tokens)
--think-hard    # Deep analysis (~10K tokens)
--ultrathink    # Maximum depth (~32K tokens)
```

### Execution Control
```bash
--validate      # Pre-execution validation
--safe-mode     # Maximum safety checks
--loop          # Enable iterative improvement
--iterations N  # Set improvement cycle count
```

### Focus Areas
```bash
--focus quality        # Quality-focused execution
--focus testing        # Testing-focused with Playwright
--focus architecture   # Architecture analysis
--focus security       # Security review
```

### Output Optimization
```bash
--uc            # Ultra-compressed output (30-50% reduction)
--scope file    # File-level scope
--scope project # Project-level scope
```

### Examples with FLAGS
```bash
/enhance:discover fluentcrm --think-hard
/enhance:implement fluentcrm TIER1 --validate
/test:create fluentcrm --focus testing --play
/enhance:polish fluentcrm --loop --iterations 2 --uc
```

---

## 📊 Understanding TIER System

### What Each TIER Means

| TIER | Purpose | User Perspective | Example Features |
|------|---------|------------------|------------------|
| **TIER1** | Critical needs | "I can't do my job without this" | Funnel automation, field coverage, GDPR compliance |
| **TIER2** | High-value productivity | "This makes my work way easier" | Bulk operations, advanced filtering, segments |
| **TIER3** | Enhanced usability | "This is nice to have" | Sorting, additional filters, export tools |

### Implementation Order

**Always implement sequentially:**
1. TIER1 first (foundation - things are broken without it)
2. TIER2 second (productivity - make it better)
3. TIER3 third (polish - make it nice)

**Why?** TIER1 features often unlock TIER2/3 capabilities. Example:
- TIER1: Add `create-funnel-sequence` (can't build automation without it)
- TIER2: Add `bulk-enroll-in-funnel` (needs sequences to exist first)
- TIER3: Add `preview-funnel` (nice visualization of existing automation)

---

## 🔍 Quick Diagnostics

### Check What Stage You're At

```bash
# See what's been validated
ls tests/e2e/<plugin>/VALIDATED_SHAPES.md

# See discovery results
ls docs/<plugin>/enhancement-roadmap.md

# See what's implemented
ls classes/Adapters/<plugin>/Abilities/*.php

# See what's tested
ls tests/e2e/<plugin>/abilities/*.test.ts
```

### Verify Prerequisites

```bash
# Before /enhance:discover
ls tests/e2e/<plugin>/VALIDATED_SHAPES.md  # Must exist

# Before /enhance:implement
ls docs/<plugin>/enhancement-roadmap.md    # Must exist

# Before /test:create
ls classes/Adapters/<plugin>/Abilities/    # Must have abilities
ls tests/e2e/<plugin>/VALIDATED_SHAPES.md  # Must exist
```

---

## ⚡ Time Savings Summary

| Command | Sequential | Concurrent | Savings |
|---------|-----------|------------|---------|
| `/integration:validate` (7 models) | 10-12h | 3-4h | 65% |
| `/enhance:discover` | 3h | 2h | 33% |
| `/enhance:implement TIER1` | 15-20h | 8-12h | 50% |
| `/enhance:implement TIER2` | 12-18h | 6-8h | 50% |
| `/enhance:implement TIER3` | 8-12h | 3-5h | 60% |
| `/test:create` | 1-2 days | 6-8h | 70% |
| `/enhance:polish` | 4-5h | 2-3h | 40% |

**Overall integration:** 80-100h → 30-40h = **60-65% time savings**

---

## 🚨 Common Mistakes to Avoid

### ❌ Wrong: Skipping Discovery
```bash
/enhance:implement fluentcrm TIER1  # No roadmap exists!
```

### ✅ Right: Discovery First
```bash
/enhance:discover fluentcrm         # Creates roadmap
/enhance:implement fluentcrm TIER1  # Uses roadmap
```

---

### ❌ Wrong: Implementing TIER2 Before TIER1
```bash
/enhance:implement fluentcrm TIER2  # TIER1 not done!
```

### ✅ Right: Sequential TIER Implementation
```bash
/enhance:implement fluentcrm TIER1  # Foundation first
/enhance:implement fluentcrm TIER2  # Productivity second
/enhance:implement fluentcrm TIER3  # Polish last
```

---

### ❌ Wrong: Testing Before Implementation
```bash
/test:create fluentcrm  # No abilities implemented yet!
```

### ✅ Right: Implement Then Test
```bash
/enhance:implement fluentcrm TIER1  # Implement features
/test:create fluentcrm              # Test them
```

---

### ❌ Wrong: Multiple Words in Plugin Name
```bash
/enhance:discover "my plugin name"  # Won't work!
```

### ✅ Right: Single-Word Plugin Name
```bash
/enhance:discover myplugin          # Works!
```

---

## 📝 Command Decision Tree

```
┌─ Starting new plugin?
│  ├─ YES → /integration:validate <plugin>
│  │       └─→ /enhance:discover <plugin>
│  │           └─→ /enhance:implement <plugin> TIER1
│  │               └─→ /test:create <plugin>
│  │                   └─→ /enhance:polish <plugin>
│  └─ NO ↓
│
┌─ Have roadmap already?
│  ├─ YES → Check roadmap for current TIER
│  │       └─→ /enhance:implement <plugin> TIER[1|2|3]
│  └─ NO → /enhance:discover <plugin> first
│
┌─ Implementation done, need tests?
│  └─ YES → /test:create <plugin>
│
┌─ Everything done, improve quality?
│  └─ YES → /enhance:polish <plugin>
│
┌─ Adding new models to existing plugin?
│  └─ YES → /integration:validate <plugin> Model1 Model2
│          └─→ /enhance:discover <plugin>  (refreshes roadmap)
│              └─→ /enhance:implement <plugin> TIER1
```

---

## 🎓 Pro Tips

### 1. **Always Review Roadmap Before Implementing**
```bash
/enhance:discover fluentcrm
# Read docs/fluentcrm/enhancement-roadmap.md
# Understand what TIER1/2/3 include
/enhance:implement fluentcrm TIER1
```

### 2. **Use Focus Flags for Targeted Work**
```bash
# Only implement funnel automation from TIER1
/enhance:implement fluentcrm TIER1 --focus "funnel-automation"
```

### 3. **Validate Early, Validate Often**
```bash
# Use --validate flag for critical changes
/enhance:implement fluentcrm TIER1 --validate
```

### 4. **Check Existing Tests Before Creating**
```bash
# See what tests already exist
ls tests/e2e/fluentcrm/abilities/*.test.ts

# Command will UPDATE existing, not duplicate
/test:create fluentcrm
```

### 5. **Polish After Major Changes**
```bash
# After implementing TIER1, polish affected descriptions
/enhance:implement fluentcrm TIER1
/enhance:polish fluentcrm --loop --iterations 2
```

---

## 📚 Full Documentation

For complete documentation, see:
- [COMMAND_USAGE_GUIDE.md](../claudedocs/COMMAND_USAGE_GUIDE.md) - Detailed command reference
- [COMPLETE_INTEGRATION_METHODOLOGY.md](./processes/COMPLETE_INTEGRATION_METHODOLOGY.md) - Full methodology

---

**Last Updated:** 2025-10-04
**Version:** 2.0 (with TIER system and FLAGS)
