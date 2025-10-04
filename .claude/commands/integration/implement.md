---
description: Implement MCP abilities for a validated plugin using TDD approach
tags: [integration, implementation, tdd]
disable-model-invocation: false
---

# Plugin Integration - Implementation Phase

You are implementing MCP abilities for: **$ARGUMENTS**

## Objective

Implement complete, production-ready abilities following validation documentation, using toArray() pattern, and TDD principles.

## Prerequisites

**MUST exist before running this command:**
- ✅ `tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md`
- ✅ Validation scripts: `validate-$ARGUMENTS-*.php`

## Context Files to Read

**REQUIRED - Read these first:**
1. @tests/e2e/$ARGUMENTS/VALIDATED_SHAPES.md (Your validation reference)
2. @docs/processes/COMPLETE_INTEGRATION_METHODOLOGY.md (Phase 4: Implementation)
3. @classes/Adapters/FluentCrm/Abilities/Subscribers.php (Example ability class)
4. @classes/Adapters/FluentCrm/BaseAbility.php (Example base class)

## Your Task

### Step 1: Create Ability Structure (30 minutes)

**Create base ability:**
```php
// classes/Adapters/$ARGUMENTS/BaseAbility.php
```

Follow pattern from FluentCrm, include:
- Permission callbacks (can_manage, can_view)
- Model instance helper
- Error handling patterns

**Create ability registry:**
```php
// classes/Adapters/$ARGUMENTS/Servers/AbilityRegistry.php
```

### Step 2: Implement Abilities by Priority

**For each model in VALIDATED_SHAPES.md:**

**Priority 1 - Core CRUD (Required)**:
1. create-[model] - Use ALL fields from validation
2. list-[models] - Pagination + filters from scopes
3. get-[model] - Add `with` parameter for relations
4. update-[model] - Partial updates supported
5. delete-[model] - Soft vs hard delete per validation

**Priority 2 - Relationship Management**:
For each relation in VALIDATED_SHAPES.md:
1. add-[model]-[relation]
2. remove-[model]-[relation]
3. list-[model]-[relation]
4. bulk-sync-[model]-[relation] (if BelongsToMany)

**Priority 3 - Custom Operations**:
Based on scopes/methods in validation:
- Bulk operations
- Status transitions
- Specialized queries

### Step 3: Critical Patterns to Follow

**1. ALWAYS Use toArray():**
```php
public function execute_get_model( array $args ): array {
    $model = Model::find( $args['id'] );

    // ✅ RIGHT
    return $model->toArray();

    // ❌ WRONG - Never do this
    // return ['id' => $model->id, 'name' => $model->name];
}
```

**2. ALWAYS Document Relationship Loading:**
```php
'description' => 'Get model by ID. Relations NOT included by default - use "with" parameter to load relationships.',

'with' => [
    'type' => 'array',
    'description' => 'Relationships to eager load',
    'items' => [
        'type' => 'string',
        'enum' => ['relation1', 'relation2'], // From VALIDATED_SHAPES.md
    ],
],
```

**3. ALWAYS Use Complete Input Schemas:**

Reference VALIDATED_SHAPES.md for EVERY field:
- Required fields from validation
- Optional fields (all of them!)
- Enum values from validation
- Type from validation (string, integer, boolean)
- Examples for complex types (colors, dates, JSON)

**4. Handle Type Consistency:**

Document quirks from VALIDATED_SHAPES.md:
```php
'description' => 'Note: Numeric fields returned as strings (e.g., "0" not 0). IDs in pivot tables are strings.',
```

### Step 4: Register All Abilities

**In AbilityRegistry::register():**
```php
$abilities = [
    new Abilities\Model1(),
    new Abilities\Model2(),
    // ... all models
];

foreach ( $abilities as $ability ) {
    $ability->register();
}
```

**Hook registration:**
```php
add_action( 'init', function() {
    if ( class_exists( '\\Plugin\\Model' ) ) {
        \\MCP\\Adapters\\Adapters\\$ARGUMENTS\\Servers\\AbilityRegistry::register();
    }
}, 20 );
```

### Step 5: Code Quality Checks

**Before marking complete:**

```bash
# PHP linting
npm run lint:php

# Fix auto-fixable issues
npm run lint:php:fix

# Verify abilities registered
wp eval "
\$abilities = apply_filters('wp_abilities_registered', []);
\$plugin_abilities = array_filter(\$abilities, function(\$name) {
    return strpos(\$name, '$ARGUMENTS/') === 0;
});
echo count(\$plugin_abilities) . ' abilities registered';
"
```

## Implementation Checklist

For EACH model:
- [ ] Ability class created
- [ ] CRUD operations (5) implemented
- [ ] Relationship operations (N×4) implemented
- [ ] Custom operations implemented
- [ ] Uses toArray() NOT manual selection
- [ ] All fields from VALIDATED_SHAPES.md in schemas
- [ ] Relationships document "not included on create"
- [ ] `with` parameter for eager loading
- [ ] Type quirks documented
- [ ] Permission callbacks appropriate
- [ ] Registered in AbilityRegistry

## Success Criteria

✅ All planned abilities implemented
✅ All use toArray() pattern
✅ Complete field coverage from validation
✅ Relationship loading documented
✅ Code quality checks pass
✅ Ready for testing phase

## Next Steps

After implementation complete:
```
/test:create $ARGUMENTS
```

## Output Format

Provide implementation summary:

```
✅ IMPLEMENTATION COMPLETE: $ARGUMENTS

Abilities Created:
- Model1: 5 CRUD + 8 relations + 2 custom = 15 tools
- Model2: 5 CRUD + 4 relations + 1 custom = 10 tools
...
Total: N abilities across M models

Code Quality:
- Linting: ✅ Pass
- toArray() usage: ✅ 100%
- Field coverage: ✅ 95%+

Files Created:
- classes/Adapters/$ARGUMENTS/BaseAbility.php
- classes/Adapters/$ARGUMENTS/Abilities/*.php (N files)
- classes/Adapters/$ARGUMENTS/Servers/AbilityRegistry.php

Ready for: /test:create $ARGUMENTS
```
