---
description: Polish tool descriptions, parameters, and documentation quality
tags: [enhancement, quality, polish]
disable-model-invocation: false
---

# Enhancement Polish - Quality Improvement

You are polishing quality for: **$ARGUMENTS**

## Objective

Systematically improve tool description quality, parameter documentation, and overall user experience to achieve ≥8.5/10 quality score.

## Prerequisites

**MUST exist:**
- ✅ Existing abilities in `classes/Adapters/$ARGUMENTS/`
- ✅ `tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md` (optional but recommended)

## Context Files to Read

**REQUIRED - Read these first:**
1. All ability files in @classes/Adapters/$ARGUMENTS/Abilities/
2. @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md (Quality standards)
3. @tests/e2e/fluentboards/PHASE_1_COMPLETION_SUMMARY.md (Quality improvement examples)
4. @tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md (if exists)

## Your Task

### Step 1: Description Quality Audit (1 hour)

**Score each tool (1-10 rubric):**

| Criterion | Weight | 0 Points | 1 Point | 2 Points |
|-----------|--------|----------|---------|----------|
| **Clarity** | 2× | Ambiguous | Somewhat clear | Crystal clear |
| **Completeness** | 2× | Missing info | Has basics | Fully describes |
| **Succinctness** | 1× | Too verbose | Acceptable | Perfect brevity |
| **Return Value** | 2× | Not mentioned | Vague | Explicit |
| **Parameters** | 2× | Not explained | Basic | With examples |

**Score formula:**
```
Score = (Clarity×2 + Completeness×2 + Succinctness×1 + ReturnValue×2 + Parameters×2) / 9
```

**Create audit table:**

| Tool | Label | Description | Clarity | Complete | Succinct | Return | Params | Score | Priority |
|------|-------|-------------|---------|----------|----------|--------|--------|-------|----------|
| create-model | ... | ... | 5 | 4 | 6 | 3 | 4 | 5.2/10 | High |
| update-model | ... | ... | 6 | 3 | 5 | 2 | 3 | 4.4/10 | High |

**Identify patterns:**
- Common issue 1: No return value descriptions (affects N tools)
- Common issue 2: Missing optional parameter docs (affects N tools)
- Common issue 3: Redundant namespace in labels (affects N tools)

### Step 2: Apply Improvement Patterns

**Pattern 1: Add Return Value Descriptions**

```php
// BEFORE (0 points for Return Value)
'description' => 'Update an existing model',

// AFTER (2 points for Return Value)
'description' => 'Update model properties. Supports partial updates (any combination of fields). Returns updated model with all fields and new timestamps.',
```

**Pattern 2: Document Optional Parameters**

```php
// BEFORE (1 point for Parameters)
'description' => 'List all models',

// AFTER (2 points for Parameters)
'description' => 'Get all models with optional pagination (page, per_page) and filtering (status, type). Returns models with metadata (total, per_page, current_page).',
```

**Pattern 3: Clarify Partial Update Support**

```php
// BEFORE
'description' => 'Update model',

// AFTER
'description' => 'Update model title, status, or settings. Supports partial updates (any combination of fields). Only specified fields are modified.',
```

**Pattern 4: Shorten Redundant Labels**

```php
// BEFORE
'label' => 'List FluentBoards labels',

// AFTER
'label' => 'List board labels',
```

**Pattern 5: Add "Relations NOT Included" Note**

```php
// BEFORE
'description' => 'Create a new model with properties.',

// AFTER
'description' => 'Create a new model with properties. Returns created model with all fields. Relations NOT included - use get-model with "with" parameter to load relationships.',
```

### Step 3: Parameter Documentation Enhancement

**Add Examples to Complex Parameters:**

```php
// BEFORE
'bg_color' => [
    'type' => 'string',
    'description' => 'Background color',
],

// AFTER
'bg_color' => [
    'type' => 'string',
    'description' => 'Background color (hex) - e.g., #4bce97',
    'pattern' => '^#[0-9a-fA-F]{6}$',
],
```

**Document Enum Values:**

```php
// BEFORE
'status' => [
    'type' => 'string',
    'description' => 'Model status',
],

// AFTER
'status' => [
    'type' => 'string',
    'enum' => ['open', 'in_progress', 'closed'], // From VALIDATED_SHAPES.md
    'description' => 'Model status',
    'default' => 'open',
],
```

**Expand Vague "etc." Descriptions:**

```php
// BEFORE
'settings' => [
    'description' => 'Settings object (field1, field2, etc.)',
],

// AFTER
'settings' => [
    'description' => 'Settings object. Supported fields: field1 (string), field2 (boolean), field3 (enum: a|b|c)',
    'properties' => [
        'field1' => ['type' => 'string'],
        'field2' => ['type' => 'boolean'],
        'field3' => ['type' => 'string', 'enum' => ['a', 'b', 'c']],
    ],
],
```

### Step 4: Consistency Check

**Ensure consistent patterns across all tools:**

**Pagination:**
```php
// All list tools should have consistent pagination
'page' => [
    'type' => 'integer',
    'description' => 'Page number',
    'default' => 1,
    'minimum' => 1,
],
'per_page' => [
    'type' => 'integer',
    'description' => 'Items per page',
    'default' => 20,
    'minimum' => 1,
    'maximum' => 100,
],
```

**Relationship Loading:**
```php
// All get tools should have consistent 'with' parameter
'with' => [
    'type' => 'array',
    'description' => 'Relationships to eager load',
    'items' => [
        'type' => 'string',
        'enum' => ['relation1', 'relation2'], // From VALIDATED_SHAPES.md
    ],
],
```

**Delete Confirmation:**
```php
// Potentially destructive operations should clarify behavior
'description' => 'Delete model permanently (hard delete). Cannot be undone.',

// OR if soft delete
'description' => 'Archive model (soft delete). Can be restored later.',
```

### Step 5: Verification

**Re-score after improvements:**

| Tool | Before | After | Improvement |
|------|--------|-------|-------------|
| create-model | 5.2/10 | 8.9/10 | +3.7 ✅ |
| update-model | 4.4/10 | 8.7/10 | +4.3 ✅ |
| list-models | 7.0/10 | 9.0/10 | +2.0 ✅ |

**Calculate metrics:**
- Tools improved: N
- Average improvement: +X points
- Tools ≥8.5/10: N/N (100%)
- Parameters with examples: XX% (target: ≥85%)
- Enum values documented: YY% (target: ≥90%)

**Run quality checks:**
```bash
# Linting
npm run lint:php

# Verify descriptions not too long
grep -r "'description' =>" classes/Adapters/$ARGUMENTS/ | awk '{if (length > 200) print}'
```

## Polish Checklist

- [ ] All tool descriptions scored (1-10)
- [ ] Return values documented (100% of tools)
- [ ] Optional parameters explained
- [ ] Partial updates clarified
- [ ] Redundant namespaces removed
- [ ] "Relations not included" added to create tools
- [ ] Examples added to complex parameters
- [ ] Enum values documented (≥90%)
- [ ] "etc." descriptions expanded
- [ ] Consistent pagination across list tools
- [ ] Consistent relationship loading
- [ ] Delete behavior clarified
- [ ] Re-scored after improvements
- [ ] Average score ≥8.5/10

## Success Criteria

✅ All tools scored initially
✅ Improvement patterns applied systematically
✅ Average quality score ≥8.5/10
✅ Parameters with examples ≥85%
✅ Enum values documented ≥90%
✅ Consistent patterns across all tools
✅ Code linting passes

## Best Practice Examples (10/10 Tools)

Document your best tools as templates:

```markdown
### Perfect Example: `$ARGUMENTS-move-all-items`

**Label**: "Move all items between categories"
**Description**: "Move all items from source category to destination category. Updates timestamps on all moved items. Returns count of items moved."

**Score**: 10/10
- ✅ Clarity: Immediately clear what it does
- ✅ Completeness: Source, destination, side effects, return
- ✅ Succinctness: 18 words, perfect brevity
- ✅ Return Value: Explicit "returns count"
- ✅ Parameters: Clear source/destination

**Why Perfect**: Verb-noun pattern, explains transformation, documents side effects, explicit return
```

## Output Format

```
✅ POLISH COMPLETE: $ARGUMENTS

Quality Improvement:
- Before: X.X/10 average
- After: Y.Y/10 average
- Improvement: +Z.Z points

Tools Improved: N
- Scores increased: N tools
- Scores maintained ≥8.5: N tools
- Tools now ≥8.5/10: N/N (100%)

Parameter Documentation:
- Before: XX% with examples
- After: YY% with examples
- Improvement: +Zpp

Enum Documentation:
- Before: XX%
- After: YY%
- Improvement: +Zpp

Changes Made:
- Return values added: N tools
- Optional params documented: N tools
- Partial updates clarified: N tools
- Labels shortened: N tools
- Examples added: N parameters
- Enums documented: N fields
- "etc." expanded: N descriptions

Best Practices Documented: N examples

Code Quality: ✅ Linting passes

Ready for: Production use or /test:validate $ARGUMENTS
```
