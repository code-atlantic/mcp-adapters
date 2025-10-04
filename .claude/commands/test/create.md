---
description: Create comprehensive E2E test suite using concurrent SuperClaude agents
tags: [testing, e2e, quality]
disable-model-invocation: false
---

# E2E Test Creation - Parallel Test Suite Generation

You are creating E2E tests for: **$ARGUMENTS**

## Objective

Create comprehensive E2E test suite using **concurrent agent execution** for maximum test coverage.

## Prerequisites

**MUST exist:**
- ✅ `tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md`
- ✅ Abilities in `classes/Adapters/$ARGUMENTS/Abilities/`
- ✅ Test configuration in `tests/e2e/test-config.ts`

## Context Files

**Auto-read by agents:**
- @tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md (source of truth for assertions)
- @classes/Adapters/$ARGUMENTS/Abilities/*.php (tools to test)
- @tests/e2e/fluentboards/*.test.ts (example tests, 86% coverage)
- @tests/e2e/test-config.ts (test setup)

## Execution Strategy: Parallel Test Creation

### IMPORTANT: Check for Existing Tests First

```bash
# List existing test files to avoid duplicates
ls -1 tests/e2e/$ARGUMENTS/abilities/*.test.ts 2>/dev/null || echo "No existing tests"

# Count abilities needing tests
ls classes/Adapters/$ARGUMENTS/Abilities/*.php | wc -l
```

**UPDATE EXISTING vs CREATE NEW:**
- **If test file exists:** UPDATE it with new test cases (add describe() blocks)
- **If test file missing:** CREATE new test file
- **NEVER create files like tier1-*.test.ts or feature-*.test.ts** - always use ability name

### Spawn Concurrent Test Agents

**For 10-15 abilities, use 2 batches:**

**Batch 1: Core Abilities (8 concurrent agents)**

```bash
/sc:spawn "Create/update E2E tests for $ARGUMENTS with 8 concurrent agents using /sc:test. Each agent works on one ability test file (update if exists, create if missing): Agent 1: ability1.test.ts, Agent 2: ability2.test.ts, Agent 3: ability3.test.ts, Agent 4: ability4.test.ts, Agent 5: ability5.test.ts, Agent 6: ability6.test.ts, Agent 7: ability7.test.ts, Agent 8: ability8.test.ts. Reference VALIDATED_SHAPES.md for assertions. Test CRUD + relationships + types + edge cases. If file exists, ADD new describe() blocks with clear section names (no TIER1/toArray jargon). All in one message/response." --strategy parallel --concurrent 8 --focus testing --play
```

**Agent Distribution Strategy:**
- **Agents 1-6:** One large ability each (subscribers, campaigns, etc.)
- **Agent 7:** Two related smaller abilities (notes, meta)
- **Agent 8:** Three small abilities (settings, analytics)

**Batch 2: Remaining Tests (3 concurrent agents)**

```bash
/sc:spawn "Create final E2E tests with 3 concurrent agents. Agent 1: remaining-ability1.test.ts, Agent 2: remaining-ability2.test.ts, Agent 3: integration-tests.test.ts (cross-ability workflows). All in one message/response." --strategy parallel --concurrent 3 --focus testing
```

## Test Structure (For Each Agent)

### File Template

**File:** `tests/e2e/$ARGUMENTS/{ability}.test.ts`

```typescript
import { describe, it, expect, beforeAll, afterEach } from '@jest/globals';
import { TestHelper } from '../test-helper';
import type { MCPClient } from '../types';

describe('$ARGUMENTS - {Ability} Operations', () => {
  let client: MCPClient;
  let helper: TestHelper;

  beforeAll(async () => {
    helper = new TestHelper('$ARGUMENTS');
    client = await helper.getClient();
  });

  afterEach(async () => {
    await helper.cleanup();
  });

  describe('CRUD Operations', () => {
    it('should create resource with complete data', async () => {
      // From VALIDATED_SHAPES.md: Create operation
      const response = await client.callTool('create-resource', {
        field1: 'value1',
        field2: 'value2',
        // All fields from VALIDATED_SHAPES.md
      });

      // From VALIDATED_SHAPES.md: Auto-generated fields
      expect(response).toHaveProperty('id');
      expect(typeof response.id).toBe('number');
      expect(response).toHaveProperty('created_at');
      expect(response).toHaveProperty('updated_at');

      // From VALIDATED_SHAPES.md: Returned fields
      expect(response.field1).toBe('value1');
      expect(response.field2).toBe('value2');

      // From VALIDATED_SHAPES.md: Relations NOT included on create
      expect(response).not.toHaveProperty('relation1');
      expect(response).not.toHaveProperty('relation2');
    });

    it('should read resource with relationships', async () => {
      const created = await client.callTool('create-resource', {...});

      // From VALIDATED_SHAPES.md: Eager loading with ->with()
      const response = await client.callTool('get-resource', {
        id: created.id,
        with: ['relation1', 'relation2'],
      });

      expect(response).toHaveProperty('relation1');
      expect(Array.isArray(response.relation1)).toBe(true);
    });

    it('should update resource partially', async () => {
      const created = await client.callTool('create-resource', {...});

      // From VALIDATED_SHAPES.md: Partial updates allowed
      const response = await client.callTool('update-resource', {
        id: created.id,
        field1: 'updated',
      });

      expect(response.field1).toBe('updated');
      expect(response.field2).toBe(created.field2); // Unchanged

      // From VALIDATED_SHAPES.md: Timestamps updated
      expect(response.updated_at).not.toBe(created.updated_at);
    });

    it('should delete resource', async () => {
      const created = await client.callTool('create-resource', {...});

      await client.callTool('delete-resource', { id: created.id });

      // Verify deletion
      await expect(
        client.callTool('get-resource', { id: created.id })
      ).rejects.toThrow();
    });
  });

  describe('Relationship Operations', () => {
    // From VALIDATED_SHAPES.md: Relationship structures
    it('should attach relation', async () => {
      const resource = await client.callTool('create-resource', {...});
      const relation = await client.callTool('create-relation', {...});

      const response = await client.callTool('add-resource-relation', {
        resource_id: resource.id,
        relation_id: relation.id,
      });

      expect(response).toHaveProperty('id');
    });

    it('should list relations', async () => {
      // Test list-resource-relations
    });

    it('should detach relation', async () => {
      // Test remove-resource-relation
    });

    it('should bulk sync relations', async () => {
      // Test bulk-sync-resource-relations
    });
  });

  describe('Type Consistency', () => {
    it('should return correct field types', async () => {
      const response = await client.callTool('create-resource', {...});

      // From VALIDATED_SHAPES.md: Type Consistency section
      expect(typeof response.id).toBe('number');
      expect(typeof response.name).toBe('string');
      expect(typeof response.is_active).toBe('boolean');

      // Integer fields as numbers
      expect(typeof response.count).toBe('number');

      // Nullable fields
      if (response.optional_field !== null) {
        expect(typeof response.optional_field).toBe('string');
      }
    });
  });

  describe('Edge Cases', () => {
    // From VALIDATED_SHAPES.md: Edge Cases section
    it('should handle minimal required data', async () => {
      const response = await client.callTool('create-resource', {
        required_field: 'value',
        // Only required fields
      });

      expect(response).toHaveProperty('id');
      // Optional fields should have defaults or null
    });

    it('should handle empty strings vs null', async () => {
      // Test from VALIDATED_SHAPES.md edge cases
    });

    it('should validate enum values', async () => {
      // From VALIDATED_SHAPES.md: Status values, etc.
      await expect(
        client.callTool('create-resource', {
          status: 'invalid_status',
        })
      ).rejects.toThrow();
    });

    it('should respect max lengths', async () => {
      // From VALIDATED_SHAPES.md: Field constraints
    });
  });

  describe('List Operations', () => {
    it('should paginate results', async () => {
      // Create multiple resources
      for (let i = 0; i < 15; i++) {
        await client.callTool('create-resource', {
          name: `Resource ${i}`,
        });
      }

      const page1 = await client.callTool('list-resources', {
        page: 1,
        per_page: 10,
      });

      expect(page1.data.length).toBe(10);
      expect(page1.total).toBeGreaterThanOrEqual(15);
      expect(page1.current_page).toBe(1);
    });

    it('should filter by status', async () => {
      // From VALIDATED_SHAPES.md: Scopes/filters available
    });

    it('should sort results', async () => {
      // Test sorting options
    });
  });
});
```

## Test Coverage Requirements (Per Agent)

**Minimum coverage per ability:**

✅ **CRUD Operations** (4 tests minimum)
- Create with complete data
- Read with relationships
- Update partial fields
- Delete and verify

✅ **Relationship Operations** (4 tests per relation)
- Attach/add relation
- List relations
- Detach/remove relation
- Bulk sync (if applicable)

✅ **Type Consistency** (1 test)
- Verify all field types from VALIDATED_SHAPES.md
- Check integer vs string consistency
- Verify boolean types
- Test nullable fields

✅ **Edge Cases** (3-5 tests)
- Minimal required data
- Empty strings vs null
- Enum validation
- Max length validation
- Default values

✅ **List Operations** (3 tests)
- Pagination
- Filtering (if scopes exist)
- Sorting

**Expected:** 15-25 tests per ability

## Quality Gates (Per Agent)

**Pre-test creation:**
- ✅ VALIDATED_SHAPES.md read for model structure
- ✅ Ability file read for available tools
- ✅ Example tests reviewed for patterns

**During test creation:**
- ✅ Every assertion references VALIDATED_SHAPES.md
- ✅ All tools from ability are tested
- ✅ All relationships are tested
- ✅ Type consistency verified
- ✅ Edge cases from validation docs included

**Post-test creation:**
- ✅ File compiles (TypeScript check)
- ✅ All tests have descriptive names
- ✅ Comments reference VALIDATED_SHAPES.md
- ✅ Test count ≥15 per ability

## After All Agents Complete

**Run full test suite:**

```bash
npm run test:e2e -- tests/e2e/$ARGUMENTS/
```

**Generate coverage report:**

```bash
npm run test:e2e:coverage -- tests/e2e/$ARGUMENTS/
```

**Analyze coverage:**

```bash
/sc:analyze "Review E2E test coverage for $ARGUMENTS. Compare against FluentBoards 86% coverage benchmark. Identify any missing test scenarios." --focus testing --format report
```

## Success Criteria

✅ Test files created for ALL abilities
✅ Minimum 15 tests per ability
✅ All CRUD operations tested
✅ All relationships tested
✅ Type consistency verified
✅ Edge cases from VALIDATED_SHAPES.md covered
✅ Tests reference validation docs in comments
✅ Overall coverage ≥80% (target: 86% like FluentBoards)
✅ All tests passing

## Expected Outcomes

**For 12 abilities:**
- Test files: 12-15 (some combined)
- Total tests: ~180-250
- Coverage: 80-86%
- Wall time: 6-8 hours (vs 1-2 days sequential)
- Time savings: 60-70%

**Quality metrics:**
- Tests per ability: 15-25
- Assertions per test: 3-8
- VALIDATED_SHAPES.md references: 100%
- Edge case coverage: Complete

## Next Steps

After tests complete:

**Fix any failures:**
```bash
/sc:troubleshoot "E2E test failures in $ARGUMENTS" --focus testing
```

**Improve coverage:**
```bash
/sc:test "Add missing test scenarios for $ARGUMENTS based on coverage report" --focus quality
```

**Document test patterns:**
```bash
/sc:document "tests/e2e/$ARGUMENTS/README.md" "Test suite overview, coverage, patterns, running tests" --type guide
```

## Output Format

Provide test suite summary:

```
✅ E2E TEST SUITE COMPLETE: $ARGUMENTS

## Test Files Created: N

- ability1.test.ts (18 tests)
- ability2.test.ts (22 tests)
- ability3.test.ts (16 tests)
[... all test files ...]

## Coverage Summary

**Total Tests:** X
**Coverage:** Y%

**By Category:**
- CRUD operations: 100% (all abilities)
- Relationships: 95% (Z relations tested)
- Type consistency: 100%
- Edge cases: 90%
- List operations: 85%

## Comparison to Benchmark

**FluentBoards:** 86% coverage
**$ARGUMENTS:** Y% coverage
**Status:** ✅ Meets/exceeds benchmark

## Test Execution

**All tests passing:** ✅ X/X
**Failures:** 0
**Skipped:** 0

**Run tests:**
npm run test:e2e -- tests/e2e/$ARGUMENTS/

**Coverage report:**
npm run test:e2e:coverage -- tests/e2e/$ARGUMENTS/

Ready for: Production deployment or /sc:improve for additional scenarios
```
