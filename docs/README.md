# MCP Adapters Documentation

**Build robust, tested WordPress plugin integrations for the Model Context Protocol**

---

## 📚 Integration Guide Series

This documentation suite provides a complete methodology for building MCP adapters for WordPress plugins using Test-Driven Development and API validation.

### Core Guides

1. **[Complete Integration Methodology](./processes/COMPLETE_INTEGRATION_METHODOLOGY.md)** ⭐ **START HERE** - Production-ready process (v2.0)
   - **New Integrations** (2-5 days) - Validation-first TDD approach
   - **Existing Enhancements** (1-2 days) - Tool discovery & quality improvement
   - Parallel agent validation (95% time savings)
   - ROI-based prioritization matrix
   - Quality standards: ≥8.5/10 descriptions, ≥80% test pass
   - **Proven:** FluentBoards (86% pass, 18→47 tools), FluentCRM (10 models/1 day)
   - **Use this:** For ALL integrations - new or enhancement

2. **Supporting Guides** (Referenced by Complete Methodology):
   - **[API Validation Guide](./API_VALIDATION_GUIDE.md)** - Schema discovery techniques
   - **[Test Development Guide](./TEST_DEVELOPMENT_GUIDE.md)** - TDD patterns
   - **[Integration Workflow](./INTEGRATION_WORKFLOW.md)** - Step-by-step process

3. **Process Documentation** (For reference):
   - **[New Integration Process](./processes/NEW_INTEGRATION_PROCESS.md)** - Detailed new integration
   - **[Discovery to Analysis](../tests/e2e/DISCOVERY_TO_ANALYSIS_PROCESS.md)** - Tool discovery methodology
   - **[Validation Summary](./processes/VALIDATION_SUMMARY.md)** - FluentCRM case study

### Supporting Documentation

- **[Testing Tips](../tests/e2e/TESTING_TIPS.md)** - Common pitfalls and solutions
  - Authentication in REST API
  - Permission callback signatures
  - Test data isolation
  - Import path issues

### Example Integrations

- **[FluentBoards Validated Shapes](../tests/e2e/fluentboards/VALIDATED_SHAPES.md)** - Real-world example (86% test pass rate)
  - Actual API structures
  - Database schema
  - Common patterns
  - Testing implications

- **[FluentCRM Validated Shapes](../tests/e2e/fluentcrm/VALIDATED_SHAPES.md)** - Complete 10-model validation
  - All model structures documented
  - Type consistency findings
  - Relationship patterns
  - Edge cases identified

- **[FluentCRM Gap Analysis](../FLUENTCRM_VALIDATION_ANALYSIS.md)** - Implementation roadmap
  - Comparison vs current abilities
  - Critical patterns identified
  - Testing gaps documented
  - Prioritized improvements

---

## Quick Start

### For New Integrations

Follow the complete workflow:

```bash
# 1. Validate the plugin API
wp eval-file validate-plugin-name.php

# 2. Document shapes
vim tests/e2e/plugin-name/VALIDATED_SHAPES.md

# 3. Write failing tests
vim tests/e2e/plugin-name/abilities/model.test.ts

# 4. Implement abilities
vim classes/Adapters/PluginName/Abilities/Model.php

# 5. Make tests pass
npm run test:e2e

# 6. Refactor
composer lint && composer format
```

**See:** [Integration Workflow](./INTEGRATION_WORKFLOW.md) for detailed steps

### For Existing Integrations

Ensure quality and completeness:

```bash
# 1. Validate current API
wp eval-file validate-plugin-name.php

# 2. Compare with existing code
diff validation-output.json VALIDATED_SHAPES.md

# 3. Update abilities to match reality
# - Add missing fields
# - Remove non-existent fields
# - Fix incorrect queries

# 4. Update tests
npm run test:e2e

# 5. Document findings
vim VALIDATED_SHAPES.md
```

**See:** [API Validation Guide](./API_VALIDATION_GUIDE.md) for methodology

---

## Key Principles

### 1. Validate First

**Never assume** how a plugin's API works:
- ❌ Documentation (often outdated)
- ❌ Logical inference ("it should work this way")
- ❌ Similar plugins (each has unique quirks)

**Always validate** against actual models and database:
- ✅ WP-CLI direct model testing
- ✅ Database schema inspection
- ✅ Edge case verification

### 2. Test First

Write tests **before** implementation:
- Tests based on validated shapes
- Tests fail initially (RED)
- Implement to make tests pass (GREEN)
- Refactor with confidence

### 3. Document Everything

Create living documentation:
- `VALIDATED_SHAPES.md` - Source of truth for API structures
- Inline code comments for gotchas
- Test descriptions explain expected behavior
- User guides with real examples

---

## Common Discoveries

### Relations NOT Included on Create

**Example:** FluentBoards boards don't include `stages` on creation

```typescript
// ❌ FAILS
const board = await createBoard();
const stageId = board.data.board.stages[0].id;  // undefined!

// ✅ WORKS
const board = await createBoard();
const details = await getBoard(board.data.board.id);
const stageId = details.data.board.stages[0].id;
```

### Missing Database Columns

**Example:** FluentBoards relations table has NO `foreign_type` column

```php
// ❌ SQL ERROR
$relation->where('foreign_type', 'label')->delete();

// ✅ WORKS
$relation->where('object_type', 'task')
         ->where('foreign_id', $label_id)
         ->delete();
```

### Type Inconsistencies

**Example:** IDs alternate between int and string

```typescript
// Create: {"created_by": 0}
// Fetch: {"created_by": "0"}

// ✅ Handle both
expect(parseInt(board.created_by)).toBe(0);
```

**See:** [Testing Tips](../tests/e2e/TESTING_TIPS.md) for more examples

---

## Project Structure

```
mcp-adapters/
├── docs/                           # This directory
│   ├── README.md                   # This file
│   ├── API_VALIDATION_GUIDE.md     # Validation methodology
│   ├── TEST_DEVELOPMENT_GUIDE.md   # TDD approach
│   └── INTEGRATION_WORKFLOW.md     # Complete process
│
├── tests/e2e/                      # E2E test suite
│   ├── utils/
│   │   ├── mcp-client.ts          # Shared MCP client
│   │   └── test-config.ts          # Environment config
│   ├── TESTING_TIPS.md             # Common pitfalls
│   ├── fluentboards/               # Example integration
│   │   ├── abilities/*.test.ts     # Test files
│   │   └── VALIDATED_SHAPES.md     # API structures
│   └── fluentcrm/                  # Another integration
│
└── classes/Adapters/               # Ability implementations
    ├── FluentBoards/
    │   ├── BaseAbility.php
    │   └── Abilities/
    │       ├── Boards.php
    │       ├── Tasks.php
    │       └── Labels.php
    └── FluentCrm/
```

---

## Workflow Summary

### Phase 1: Discovery & Validation
1. Explore plugin structure
2. Check database schema
3. Test models with WP-CLI
4. Document in `VALIDATED_SHAPES.md`

### Phase 2: Planning
1. Define all abilities needed
2. Plan test coverage
3. Set up project structure

### Phase 3: Test-Driven Development
1. Write failing tests (RED)
2. Implement abilities (GREEN)
3. Refactor (GREEN)
4. Repeat for each ability

### Phase 4: Verification
1. Run full test suite
2. Code quality checks
3. Manual testing
4. Cross-reference validation

### Phase 5: Documentation
1. Update ability checklist
2. Create user guide
3. Update README

---

## Tools & Commands

### Validation

```bash
# Create validation script
vim validate-plugin-name.php

# Run with WP-CLI
wp eval-file validate-plugin-name.php

# Check database
wp db query "DESCRIBE wp_plugin_table"
```

### Testing

```bash
# Run all tests
npm run test:e2e

# Run specific suite
npm run test:e2e -- tests/e2e/plugin/abilities/model.test.ts

# Watch mode
npm run test:e2e:watch

# Coverage
npm run test:e2e:coverage
```

### Code Quality

```bash
# PHP linting
composer lint

# PHP formatting
composer format

# Run all quality checks
composer lint && composer format && npm run test:e2e
```

---

## Contributing

When adding new integrations:

1. **Follow the workflow** - Don't skip validation or tests
2. **Document everything** - Create `VALIDATED_SHAPES.md`
3. **Write tests first** - TDD ensures quality
4. **Keep tests clean** - Isolated data, proper cleanup
5. **Update this guide** - Add lessons learned

---

## Support & Resources

- **Issues:** Report bugs or request features via GitHub Issues
- **Questions:** Check [Testing Tips](../tests/e2e/TESTING_TIPS.md) first
- **Examples:** See FluentBoards integration as reference

---

## Success Metrics

A quality integration has:

- ✅ **Complete Validation** - VALIDATED_SHAPES.md with all models
- ✅ **High Test Coverage** - >80% coverage, all abilities tested
- ✅ **Clean Code** - Passes linters, well-documented
- ✅ **User Documentation** - Clear guide with examples
- ✅ **Maintenance Plan** - Process for handling plugin updates

---

**Remember:** Validate first, test first, implement last - this order prevents hours of debugging!
