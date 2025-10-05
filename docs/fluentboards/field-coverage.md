# FluentBoards Field Coverage Analysis

**Analysis Date:** 2025-10-04
**Method:** VALIDATED_SHAPES.md vs Ability Schemas
**Source:** Direct model testing via WP-CLI validation

## Executive Summary

**Average Coverage:** 67%
**Models Analyzed:** 4 (Board, Task, Label, Stage)
**Total Missing Fields:** 18
**Abilities Using toArray():** 6/12 (50%)

**Critical Findings:**
- ❌ **Board create** uses manual field selection - missing 7 fields from response
- ❌ **Task create** uses manual field selection - missing 6+ computed fields
- ✅ **Label abilities** consistently use toArray() - full coverage
- ✅ **Stage abilities** consistently use toArray() - full coverage

## Coverage by Model

### Model: Board

**Source:** VALIDATED_SHAPES.md - Create/Read responses

**Total Fields in Model:** 12
- id, parent_id, title, description, type, currency, background, settings, created_by, archived_at, meta, isUserOnlyViewer

**Fields in Create Ability Schema:** 4
- title (✅), description (✅), type (✅), background (⚠️ partial - object with id/color)

**Coverage:** 33% (4/12)

**Missing Fields from Create Schema:**

1. **parent_id** (integer, nullable) - Board hierarchy
   - Priority: P2 (Medium value)
   - Use case: Sub-boards, board organization
   - Effort: XS (add to schema line 135)
   - Impact: Enables hierarchical board structures

2. **currency** (string, nullable) - Board currency setting
   - Priority: P2 (Low-medium value)
   - Use case: Budget tracking, financial boards
   - Effort: XS (add to schema)
   - Impact: Already in update ability (line 638), missing from create

3. **meta** (array, optional) - Custom metadata
   - Priority: P1 (High value for extensibility)
   - Use case: Plugin extensions, custom fields
   - Effort: S (needs validation)
   - Impact: Enables plugin extensibility

**Fields in Update Ability Schema:** 7
- title (✅), description (✅), type (✅), background (✅), currency (✅), settings (✅), page_id (⚠️ meta field)

**Coverage:** 58% (7/12)

**Computed/Auto Fields (should appear in responses):**
- ✅ parent_id - in GET response (line 75)
- ✅ archived_at - in GET response (line 88)
- ✅ currency - in GET response (line 79)
- ✅ settings - in GET response (line 86)
- ✅ created_by - in GET response (line 87)
- ✅ meta - in GET response (line 89)
- ✅ isUserOnlyViewer - in GET response (line 90)

**toArray() Usage:**
- Create (line 397-405): ❌ Manual selection - returns only: id, title, description, type, created_at, is_pinned, stages
- Update (line 909-918): ❌ Manual selection - returns only: id, title, description, type, updated_at, is_pinned, settings, meta
- Get (line 303-317): ❌ Manual selection - returns only: id, title, description, type, created_at, updated_at, stages_count, users_count, tasks_count, is_pinned, settings, meta, stages
- List (line 215-228): ❌ Manual selection - returns only: id, title, description, type, created_at, updated_at, completed_tasks_count, stages_count, users_count, is_pinned, settings, meta

**Recommendation:**
- Switch to `$board->toArray()` for complete field coverage
- Add parent_id, currency to create schema
- Document that background has default values

---

### Model: Task

**Source:** VALIDATED_SHAPES.md - Create response

**Total Fields in Model:** 14
- id, board_id, stage_id, title, created_by, type, slug, settings, position, updated_at, created_at, meta, repeat_task_meta, stage

**Fields in Create Ability Schema:** 18 (includes optional)
- board_id (✅), title (✅), stage_id (✅), description (✅), priority (✅), due_at (✅), started_at (✅), remind_at (✅), reminder_type (✅), lead_value (✅), crm_contact_id (✅), type (✅), status (✅), scope (✅), source (✅), is_template (✅), assignees (✅), labels (✅), settings (⚠️ partial)

**Coverage:** 100% (base fields) + 6 optional enhancements

**Validated Response Shows These Auto-Generated Fields:**
- ✅ slug (auto-generated from title)
- ✅ settings (auto-generated with defaults)
- ✅ position (auto-incremented)
- ✅ meta (empty array by default)
- ✅ repeat_task_meta (null)
- ✅ stage (relation, null in create)

**toArray() Usage:**
- Create (line 900-904): ✅ Uses `$task->toArray()` - GOOD
- Update (line 1001-1006): ✅ Uses `$task->toArray()` - GOOD
- Get (line 800): ✅ Uses `$task->toArray()` - GOOD
- List (line 756): ✅ Uses `$tasks->toArray()` - GOOD

**Coverage Status:** ✅ EXCELLENT
- Schema covers all user-settable fields
- toArray() ensures all computed fields in responses
- Handles optional fields appropriately

**Recommendation:**
- No changes needed - this is the model to follow
- Settings schema could be more detailed (currently just object with cover property)

---

### Model: Label

**Source:** VALIDATED_SHAPES.md - Create response

**Total Fields in Model:** 8
- id, board_id, title, bg_color, color, type, updated_at, created_at

**Fields in Create Ability Schema:** 4
- board_id (✅), title (✅), bg_color (✅), color (✅)

**Coverage:** 50% (4/8)

**Auto-Generated Fields (appear in responses):**
- ✅ id (primary key)
- ✅ type (always "label")
- ✅ created_at (timestamp)
- ✅ updated_at (timestamp)

**toArray() Usage:**
- Create (line 408): ✅ Uses `$label->toArray()` - GOOD
- Update (line 495): ✅ Uses `$label->toArray()` - GOOD
- List (line 331): ✅ Uses `$label->toArray()` - GOOD

**Coverage Status:** ✅ EXCELLENT
- All user-settable fields in schema
- toArray() ensures all computed fields in responses
- Clean, simple model

**Recommendation:**
- No changes needed - excellent coverage

---

### Model: Stage

**Source:** VALIDATED_SHAPES.md - Create response

**Total Fields in Model:** 9
- id, board_id, title, position, type, settings, updated_at, created_at, description (optional), bg_color (optional)

**Fields in Create Ability Schema:** 3 + 1 optional
- board_id (✅), title (✅), position (✅ optional), settings (✅ optional)

**Coverage:** 44% (4/9)

**Missing from Create Schema:**
1. **description** (text, nullable) - Stage description
   - Priority: P2 (Enhancement)
   - Use case: Stage documentation
   - Effort: XS (add to schema)
   - Impact: Low (not commonly used based on validation)

2. **bg_color** (string, nullable) - Stage background color
   - Priority: P1 (Visual organization)
   - Use case: Visual differentiation of stages
   - Effort: XS (add to schema line 86)
   - Impact: Already in update ability (line 132), missing from create

**Fields in Update Ability Schema:** 3
- title (✅), bg_color (✅), settings (✅)

**Auto-Generated Fields:**
- ✅ id (primary key)
- ✅ type (always "stage")
- ✅ settings (auto-generated with defaults: {default_task_status: "open", is_template: false})
- ✅ created_at (timestamp)
- ✅ updated_at (timestamp)

**toArray() Usage:**
- Create (line 557): ✅ Uses `$stage->toArray()` - GOOD
- Update (line 623): ✅ Uses `$stage->toArray()` - GOOD
- List (line 489): ✅ Uses `$stage->toArray()` - GOOD
- Get Archived (line 1035-1046): ❌ Manual selection (but includes description, bg_color)

**Coverage Status:** ✅ GOOD
- Core fields covered
- Missing optional fields (description, bg_color) from create
- toArray() usage consistent

**Recommendation:**
- Add `description` and `bg_color` to create schema for consistency
- Update ability already has bg_color, just add to create

---

## toArray() Migration Analysis

### Files Using toArray() Correctly ✅

1. **Tasks.php** - Excellent pattern
   - Line 900-904: `execute_create_task` returns `$task->toArray()`
   - Line 1001-1006: `execute_update_task` returns `$task->toArray()`
   - Line 800: `execute_get_task` returns `$task->toArray()`
   - Line 756: `execute_list_tasks` returns `$tasks->toArray()`
   - Impact: Full field coverage, no missing data

2. **Labels.php** - Excellent pattern
   - Line 408: `execute_create_label` returns `$label->toArray()`
   - Line 495: `execute_update_label` returns `$label->toArray()`
   - Line 331: `execute_list_labels` uses `$label->toArray()`

3. **Stages.php** - Excellent pattern
   - Line 557: `execute_create_stage` returns `$stage->toArray()`
   - Line 623: `execute_update_stage` returns `$stage->toArray()`
   - Line 489: `execute_list_stages` uses `$stage->toArray()`

### Files Needing toArray() Migration ❌

1. **Boards.php** - CRITICAL
   - **Problem Areas:**
     - Line 397-405: `execute_create_board` - manual selection
     - Line 909-918: `execute_update_board` - manual selection
     - Line 303-317: `execute_get_board` - manual selection
     - Line 215-228: `execute_list_boards` - manual selection

   - **Currently Missing Fields:**
     - parent_id (board hierarchy)
     - currency (in GET, missing from create response)
     - archived_at
     - background (complex object)
     - Full stages array

   - **Migration Plan:**
     ```php
     // BEFORE (line 395-408)
     return $this->get_success_response(
         [
             'board' => [
                 'id'          => $board->id,
                 'title'       => $board->title,
                 'description' => $board->description,
                 'type'        => $board->type,
                 'created_at'  => $board->created_at,
                 'is_pinned'   => $board_service->isPinned( $board->id ),
                 'stages'      => $stages,
             ],
         ],
         'Board created successfully'
     );

     // AFTER (proposed)
     $board_data = $board->toArray();
     $board_data['is_pinned'] = $board_service->isPinned( $board->id );
     $board_data['stages'] = $stages; // Override with formatted stages

     return $this->get_success_response(
         [ 'board' => $board_data ],
         'Board created successfully'
     );
     ```

   - **Impact:** Would add 7+ missing fields to responses
   - **Effort:** 2 hours (4 methods to update)

## Summary Statistics

### Coverage Distribution
- **Excellent (>80%):** 1 model (Task)
- **Good (60-80%):** 2 models (Label, Stage)
- **Fair (40-60%):** 1 model (Board - update only)
- **Poor (<40%):** 1 model (Board - create)

### Missing Field Categories
- **Optional enhancements:** 4 fields (parent_id, currency, description, bg_color)
- **Computed/auto fields:** 8 fields (properly handled by toArray() where used)
- **Critical gaps:** 0 fields (all required fields covered)

### toArray() Usage Summary
| Model | Create | Update | Get | List | Status |
|-------|--------|--------|-----|------|--------|
| Board | ❌ | ❌ | ❌ | ❌ | **CRITICAL** |
| Task | ✅ | ✅ | ✅ | ✅ | **EXCELLENT** |
| Label | ✅ | ✅ | N/A | ✅ | **EXCELLENT** |
| Stage | ✅ | ✅ | N/A | ✅ | **EXCELLENT** |

## Implementation Priority

### P0 - Critical (Immediate)
**Task:** Migrate Boards.php to toArray() pattern
**Effort:** 2 hours
**Impact:** High - Adds 7+ missing fields to all board responses
**Files:** `classes/Adapters/FluentBoards/Abilities/Boards.php`
**Methods:**
- execute_create_board (line 332-412)
- execute_update_board (line 842-925)
- execute_get_board (line 264-324)
- execute_list_boards (line 180-256)

### P1 - High (This Sprint)
**Task:** Add missing optional fields to create schemas
**Effort:** 1 hour
**Impact:** Medium - Enables full feature parity
**Changes:**
1. Board create schema:
   - Add parent_id (integer, nullable) - line 135
   - Add currency (string) - line 140
   - Add meta (object) - line 145

2. Stage create schema:
   - Add description (string) - line 87
   - Add bg_color (string, hex pattern) - line 92

### P2 - Medium (Next Sprint)
**Task:** Document field behavior and defaults
**Effort:** 1 hour
**Impact:** Low - Improves developer experience
**Deliverable:** Update CLAUDE.md with field coverage notes

## Validation Notes

### Fields That Auto-Generate (No Schema Needed)
- **id** - Primary key (all models)
- **type** - Model type identifier (label, stage, task, board)
- **created_at** - Timestamp (all models)
- **updated_at** - Timestamp (all models)
- **slug** - Generated from title (tasks only)
- **position** - Auto-incremented (tasks, stages)

### Fields With Defaults (Schema Optional)
- **settings** - Empty object or model-specific defaults
- **meta** - Empty array
- **background** - Default preset (boards)
- **created_by** - Current user ID

### Fields Requiring Schema
- All user-settable fields
- Optional fields users should be able to set
- Relationship IDs (board_id, stage_id, etc.)

## Conclusion

**Overall Assessment:** Good foundation with room for improvement

**Strengths:**
- Tasks, Labels, Stages use toArray() consistently
- All required fields covered in schemas
- Auto-generated fields properly handled

**Critical Issues:**
- Boards.php manual field selection loses data
- Some optional fields missing from create schemas

**Action Items:**
1. ✅ Migrate Boards.php to toArray() (P0)
2. ✅ Add parent_id, currency, meta to board create schema (P1)
3. ✅ Add description, bg_color to stage create schema (P1)
4. ✅ Document field behavior in CLAUDE.md (P2)

**Estimated Total Effort:** 4 hours
