---
description: Guide to MCP Adapter integration commands
---

# MCP Adapter Integration Commands

Slash commands for orchestrating WordPress plugin integrations with MCP adapters.

---

## Quick Start

### New Integration (Full Workflow)

```
/integrate fluentboards
```

Runs complete workflow automatically:
- ✅ Validation (3-4h)
- ✅ Implementation (1-3d)
- ✅ Testing (4-8h)
- ✅ Polish (2-3h)

**Timeline**: 2-5 days for production-ready integration

### Existing Enhancement (Discover & Improve)

```
/enhance fluentcrm
```

Runs enhancement workflow:
- ✅ Validation (if needed)
- ✅ Discovery (2-3h)
- ✅ P0 Implementation (~20h)
- ✅ P1 Implementation (~15h)
- ✅ Polish (2-3h)

**Timeline**: 1-2 days for significant improvements

---

## Command Reference

### Integration Commands (New Plugins)

#### `/integrate [plugin-name]`
**Complete end-to-end integration orchestrator**

Automatically executes all phases:
1. Validation via `/integration:validate`
2. Implementation via `/integration:implement`
3. Testing via `/test:create`
4. Polish via `/enhance:polish`

**Use when**: Starting fresh integration from scratch

**Example**:
```
/integrate woocommerce
```

**Output**: Production-ready integration in 2-5 days

---

#### `/integration:validate [plugin-name]`
**Comprehensive API structure validation**

Spawns 3-8 parallel agents to validate:
- Database schemas
- CRUD operations
- Relationships
- Type consistency
- Edge cases

Creates:
- `validate-[plugin]-*.php` scripts
- `tests/e2e/[plugin]/VALIDATED_SHAPES.md`

**Use when**: Starting validation phase or updating documentation

**Example**:
```
/integration:validate fluentboards
```

**Timeline**: 3-4 hours (parallel execution)

---

#### `/integration:implement [plugin-name]`
**Implement abilities from validation**

Creates complete ability structure:
- BaseAbility.php
- Ability classes (one per model)
- AbilityRegistry.php
- All using toArray() pattern

Implements:
- CRUD operations (5 per model)
- Relationship management (4 per relation)
- Custom operations

**Prerequisites**: VALIDATED_SHAPES.md must exist

**Use when**: Ready to implement after validation

**Example**:
```
/integration:implement fluentboards
```

**Timeline**: 1-3 days depending on complexity

---

### Enhancement Commands (Existing Plugins)

#### `/enhance [plugin-name]`
**Complete enhancement orchestrator**

Automatically executes:
1. Validation (if needed)
2. Discovery via `/enhance:discover`
3. Implementation via `/enhance:implement` (P0, P1)
4. Polish via `/enhance:polish`

**Use when**: Improving existing integration

**Example**:
```
/enhance fluentcrm
```

**Output**: Significantly improved integration (45% → 85% coverage typical)

---

#### `/enhance:discover [plugin-name]`
**Systematic gap analysis and opportunity discovery**

Discovers:
- Unused relations (each = 3-5 tools!)
- Missing fields in schemas
- Unused scopes and methods
- Description quality issues
- Business value and ROI

Creates:
- Gap matrix
- ROI-based priorities (P0-P4)
- Implementation roadmap

**Prerequisites**: Existing abilities + VALIDATED_SHAPES.md (or creates it)

**Use when**: Want to find what's missing or improvable

**Example**:
```
/enhance:discover fluentboards
```

**Timeline**: 2-3 hours

**Typical Output**: 15-30 tool opportunities discovered

---

#### `/enhance:implement [plugin-name] [priority]`
**Implement prioritized enhancements**

Implements tools by priority tier:
- `P0`: Critical features (~15-20h)
- `P1`: High value features (~12-18h)
- `P2`: Medium value (~8-12h)
- `P3`: Low value (~2-5h)

**Prerequisites**: Discovery complete

**Use when**: Ready to implement after discovery

**Examples**:
```
/enhance:implement fluentboards P0
/enhance:implement fluentcrm P1
```

**Timeline**: Varies by priority (see estimates above)

---

#### `/enhance:polish [plugin-name]`
**Quality improvement for descriptions and documentation**

Improves:
- Tool descriptions (target: ≥8.5/10)
- Parameter documentation (≥85% with examples)
- Enum value documentation (≥90%)
- Consistent patterns

**Use when**: Final quality pass or improving existing tools

**Example**:
```
/enhance:polish fluentboards
```

**Timeline**: 2-3 hours

**Typical Improvement**: 7.8/10 → 9.0/10 quality

---

### Testing Commands

#### `/test:create [plugin-name]`
**Create comprehensive E2E test suite**

Creates test files with:
- CRUD operation tests
- Relationship loading tests
- Type consistency tests
- Edge case tests

Tests against VALIDATED_SHAPES.md for accuracy.

**Prerequisites**: VALIDATED_SHAPES.md + implemented abilities

**Use when**: Ready to validate implementation

**Example**:
```
/test:create fluentboards
```

**Timeline**: 4-8 hours

**Target**: ≥80% pass rate on first run

---

## Command Workflows

### Workflow 1: Complete New Integration

```bash
# Single command (automated)
/integrate [plugin-name]

# OR step-by-step (manual control)
/integration:validate [plugin-name]    # 3-4h
/integration:implement [plugin-name]   # 1-3d
/test:create [plugin-name]             # 4-8h
/enhance:polish [plugin-name]          # 2-3h
```

**Result**: Production-ready integration, 2-5 days

---

### Workflow 2: Enhance Existing Integration

```bash
# Single command (automated)
/enhance [plugin-name]

# OR step-by-step (manual control)
/enhance:discover [plugin-name]              # 2-3h
/enhance:implement [plugin-name] P0          # ~20h
/enhance:implement [plugin-name] P1          # ~15h (optional)
/enhance:polish [plugin-name]                # 2-3h
```

**Result**: Significantly improved integration, 1-2 days

---

### Workflow 3: Quality Polish Only

```bash
/enhance:polish [plugin-name]
```

**Use when**: Integration complete, just need quality improvements

**Result**: ≥8.5/10 quality scores, 2-3 hours

---

### Workflow 4: Validation Update

```bash
/integration:validate [plugin-name]
```

**Use when**: Plugin updated, need to re-validate API changes

**Result**: Updated VALIDATED_SHAPES.md, 3-4 hours

---

## Success Metrics

### Coverage Metrics

**Target for Complete Integration**:
- Relations exposed: ≥75%
- Fields documented: ≥75%
- Overall API coverage: ≥75%

**Typical Enhancement Results**:
- Before: 35-50% coverage
- After: 75-90% coverage
- Gain: +40pp typical

### Quality Metrics

**Target Scores**:
- Description quality: ≥8.5/10
- Parameters with examples: ≥85%
- Enum values documented: ≥90%
- Test pass rate: ≥80%

**Typical Enhancement Results**:
- Descriptions: 7.8 → 9.0 (+1.2 points)
- Parameters: 40% → 90% (+50pp)
- Enums: 30% → 95% (+65pp)

### Time Metrics

**New Integration**: 2-5 days
- Simple (1-3 models): 2-3 days
- Moderate (4-7 models): 3-4 days
- Complex (8+ models): 4-5 days

**Enhancement**: 1-2 days
- Discovery + P0: 1 day
- + P1 + Polish: 2 days

---

## Best Practices

### 1. Always Validate First

```bash
# ✅ GOOD
/integration:validate plugin-name
/integration:implement plugin-name

# ❌ BAD
/integration:implement plugin-name  # Without validation
```

**Why**: VALIDATED_SHAPES.md is source of truth for all implementation

### 2. Follow Priority Order

```bash
# ✅ GOOD
/enhance:implement plugin P0
/enhance:implement plugin P1
/enhance:implement plugin P2

# ❌ BAD
/enhance:implement plugin P2  # Before P0/P1
```

**Why**: P0/P1 have highest ROI (business value / effort)

### 3. Polish at the End

```bash
# ✅ GOOD
/integration:implement plugin
/test:create plugin
/enhance:polish plugin

# ❌ BAD
/enhance:polish plugin  # Before implementation complete
```

**Why**: Polishing incomplete work wastes time on changing code

### 4. Test After Implementation

```bash
# ✅ GOOD
/integration:implement plugin
/test:create plugin

# ❌ BAD
/test:create plugin  # No abilities to test
```

**Why**: Tests validate abilities exist and work correctly

---

## Common Use Cases

### Use Case 1: Brand New Plugin

**Goal**: Integrate WooCommerce from scratch

**Command**:
```
/integrate woocommerce
```

**Timeline**: ~5 days (complex plugin, 10+ models)

**Result**: Production-ready WooCommerce adapter

---

### Use Case 2: Improve Incomplete Integration

**Goal**: FluentBoards has 18 tools but could have 47

**Commands**:
```
/enhance:discover fluentboards
# Finds 29 missing opportunities

/enhance:implement fluentboards P0
# Adds 13 critical tools

/enhance:implement fluentboards P1
# Adds 11 high-value tools

/enhance:polish fluentboards
# Improves quality 7.8 → 9.0
```

**Timeline**: ~2 days

**Result**: 18 → 42 tools (+133%), enterprise-ready

---

### Use Case 3: Update After Plugin Upgrade

**Goal**: FluentCRM 2.8 → 2.9 added new features

**Commands**:
```
/integration:validate fluentcrm
# Updates VALIDATED_SHAPES.md

/enhance:discover fluentcrm
# Finds new model/relations

/enhance:implement fluentcrm P0
# Implements critical new features
```

**Timeline**: 1 day

**Result**: Updated for new plugin version

---

### Use Case 4: Quality Improvement Only

**Goal**: Improve tool descriptions for better UX

**Command**:
```
/enhance:polish fluentboards
```

**Timeline**: 2-3 hours

**Result**: ≥8.5/10 descriptions, ≥85% parameter docs

---

## Troubleshooting

### "VALIDATED_SHAPES.md not found"

**Solution**:
```
/integration:validate [plugin-name]
```

Creates the required validation documentation.

---

### "Abilities not registered"

**Check**:
```bash
wp eval "
\$abilities = apply_filters('wp_abilities_registered', []);
\$plugin_abilities = array_filter(\$abilities, function(\$name) {
    return strpos(\$name, 'plugin-name/') === 0;
});
echo count(\$plugin_abilities) . ' abilities';
"
```

**Fix**: Check AbilityRegistry::register() is hooked to `init` priority 20+

---

### "Tests failing with type errors"

**Check**: VALIDATED_SHAPES.md "Type Consistency" section

**Common issues**:
- IDs: expect number in model, string in pivots
- Numeric fields: returned as strings despite int in DB
- Use validation docs to adjust test assertions

---

### "Description score still low after polish"

**Check**: `/enhance:polish` output for specific issues

**Common fixes**:
- Add return value descriptions (+2 points)
- Document optional parameters (+1 point)
- Add parameter examples (+1 point)
- Clarify partial update support (+1 point)

---

## Command Arguments

### Plugin Name

**Format**: lowercase, hyphenated
- ✅ `fluentboards`
- ✅ `fluent-crm`
- ✅ `woocommerce`
- ❌ `FluentBoards` (uppercase)
- ❌ `fluent_crm` (underscore)

### Priority Levels

**Valid values**: `P0`, `P1`, `P2`, `P3`
- `P0`: Critical (highest ROI)
- `P1`: High value
- `P2`: Medium value
- `P3`: Low value

**Default**: `P0` if not specified

---

## Files Created by Commands

### `/integration:validate`
```
validate-[plugin]-*.php              # Validation scripts
tests/e2e/[plugin]/
  ├── VALIDATED_SHAPES.md            # Complete API reference
  └── *-validation.md                # Individual validations
```

### `/integration:implement`
```
classes/Adapters/[Plugin]/
  ├── BaseAbility.php                # Base class
  ├── Abilities/
  │   ├── Model1.php                 # Ability classes
  │   └── Model2.php
  └── Servers/
      └── AbilityRegistry.php        # Registration
```

### `/test:create`
```
tests/e2e/[plugin]/
  ├── model1.test.ts                 # Test files
  └── model2.test.ts
```

### `/enhance:discover`
```
[Plugin] Discovery Document          # Gap analysis
(in command output, not file)
```

---

## Next Steps

After running commands, see:
- **docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md** - Full methodology
- **docs/processes/VALIDATION_SUMMARY.md** - FluentCRM case study
- **tests/e2e/fluentboards/TOOLING_AND_DESCRIPTION_AUDIT.md** - Enhancement example

---

## Support

**Issues**: Report via GitHub Issues
**Questions**: Check COMPLETE_INTEGRATION_METHODOLOGY.md first
**Examples**: See FluentBoards/FluentCRM for reference

---

**Version**: 1.0
**Created**: 2025-10-04
**Status**: ✅ Production-Ready
