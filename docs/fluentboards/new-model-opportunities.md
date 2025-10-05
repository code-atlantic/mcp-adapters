# FluentBoards New Model & Feature Opportunities

**Analysis Date:** 2025-10-04
**Models from FluentBoards Plugin:** 19
**Models with Abilities:** 8
**Models WITHOUT Abilities:** 11

## Executive Summary

**Total New Tool Opportunities:** 67
- Complete new models: 55 tools (11 models × 5 CRUD operations each)
- CRUD completeness gaps: 0 tools (all existing models have full CRUD)
- Custom operations: 7 tools (position, archive/restore, settings)
- Workflow features: 5 tools (bulk operations, templates)

**Priority Distribution:**
- P0 (Critical): 8 tools - 22 hours
- P1 (High): 18 tools - 64 hours
- P2 (Medium): 41 tools - 123 hours
- **Total Estimated Effort: 209 hours**

## Completely Missing Models

### 1. Activity (Audit Log & History)
**Source:** FluentBoards/app/Models/Activity.php
**Table:** wp_fbs_activities
**Purpose:** Track all changes and actions on boards/tasks (audit trail, activity feed)

**Estimated Tools (5):**
1. `fluentboards/create-activity` - Create activity log entry
2. `fluentboards/list-activities` - Get activity feed with filters (object_type, action, created_by)
3. `fluentboards/get-activity` - Get activity details by ID
4. `fluentboards/update-activity` - Update activity description/settings
5. `fluentboards/delete-activity` - Delete activity entry

**Fields from Model:**
- object_id, object_type (polymorphic - board or task)
- action (string - created, updated, deleted, moved, etc.)
- column (string - which field changed)
- old_value, new_value (serialized - before/after values)
- description (text)
- settings (serialized object)
- created_by (user ID)

**Business Value:** ⭐⭐⭐⭐⭐ (Essential for audit trails, compliance, activity feeds)
**Effort:** L (10 hours - complex filtering, polymorphic relations)
**Priority:** P0

---

### 2. Notification System
**Source:** FluentBoards/app/Models/Notification.php
**Table:** wp_fbs_notifications
**Purpose:** User notifications for task assignments, mentions, updates

**Estimated Tools (6):**
1. `fluentboards/create-notification` - Create notification for users
2. `fluentboards/list-notifications` - Get user notifications with read/unread filter
3. `fluentboards/get-notification` - Get notification details
4. `fluentboards/mark-notification-read` - Mark notification as read
5. `fluentboards/mark-all-read` - Mark all user notifications as read
6. `fluentboards/delete-notification` - Delete notification

**Fields from Model:**
- object_id, object_type (polymorphic - board or task)
- activity_by (user who triggered notification)
- task_id (linked task)
- action (notification type)
- description (notification message)
- settings (serialized object)
- users (many-to-many with marked_read_at pivot)

**Business Value:** ⭐⭐⭐⭐⭐ (Core UX feature for team collaboration)
**Effort:** M (8 hours - pivot table management, read/unread states)
**Priority:** P0

---

### 3. Webhook Integration
**Source:** FluentBoards/app/Models/Webhook.php
**Table:** wp_fbs_metas (with object_type='webhook')
**Purpose:** External integrations, trigger actions on task events

**Estimated Tools (5):**
1. `fluentboards/create-webhook` - Register webhook URL for board events
2. `fluentboards/list-webhooks` - Get all webhooks for board
3. `fluentboards/get-webhook` - Get webhook details and mappings
4. `fluentboards/update-webhook` - Update webhook URL or field mappings
5. `fluentboards/delete-webhook` - Remove webhook

**Fields from Model:**
- object_id (board_id)
- object_type ('webhook')
- key (UUID for webhook)
- value (serialized: name, url, field_mappings)

**Business Value:** ⭐⭐⭐⭐ (Important for integrations - Zapier, Make, custom tools)
**Effort:** M (6 hours - UUID generation, field mapping schema)
**Priority:** P1

---

### 4. Task Metadata (Custom Fields)
**Source:** FluentBoards/app/Models/TaskMeta.php
**Table:** wp_fbs_task_metas
**Purpose:** Store task custom fields, subtask relationships, extended properties

**Estimated Tools (5):**
1. `fluentboards/set-task-meta` - Set custom meta value for task
2. `fluentboards/get-task-meta` - Get specific meta value
3. `fluentboards/list-task-metas` - Get all meta for task
4. `fluentboards/update-task-meta` - Update existing meta value
5. `fluentboards/delete-task-meta` - Remove meta key

**Fields from Model:**
- task_id
- key (meta key - custom field name or SUBTASK_GROUP_CHILD)
- value (serialized - any data type)

**Use Cases:**
- Custom fields (estimated hours, client name, invoice ID)
- Subtask parent-child relationships
- Extended task properties

**Business Value:** ⭐⭐⭐⭐ (Enables custom workflows and extensibility)
**Effort:** S (4 hours - simple CRUD with serialization)
**Priority:** P1

---

### 5. Board Metadata (Custom Properties)
**Source:** FluentBoards/app/Models/Meta.php (with board object_type)
**Table:** wp_fbs_metas
**Purpose:** Store board custom fields and extended properties

**Estimated Tools (5):**
1. `fluentboards/set-board-meta` - Set custom meta value for board
2. `fluentboards/get-board-meta` - Get specific meta value
3. `fluentboards/list-board-metas` - Get all meta for board
4. `fluentboards/update-board-meta` - Update existing meta value
5. `fluentboards/delete-board-meta` - Remove meta key

**Use Cases:**
- Custom board fields (department, project code, budget)
- Integration settings
- Extended board properties

**Business Value:** ⭐⭐⭐ (Nice-to-have for custom workflows)
**Effort:** S (4 hours - simple CRUD with serialization)
**Priority:** P2

---

### 6. Teams (User Groups)
**Source:** FluentBoards/app/Models/Team.php
**Table:** wp_fbs_teams (inferred from model)
**Purpose:** Organize users into teams for board access

**Estimated Tools (5):**
1. `fluentboards/create-team` - Create user team
2. `fluentboards/list-teams` - Get all teams
3. `fluentboards/get-team` - Get team details with members
4. `fluentboards/update-team` - Update team name/settings
5. `fluentboards/delete-team` - Remove team

**Business Value:** ⭐⭐⭐ (Useful for enterprise, team-based access)
**Effort:** M (5 hours - team-user relations)
**Priority:** P2

---

### 7. Task Images (Visual Attachments)
**Source:** FluentBoards/app/Models/TaskImage.php
**Table:** wp_fbs_task_images (inferred)
**Purpose:** Image attachments for tasks (separate from file attachments)

**Estimated Tools (5):**
1. `fluentboards/upload-task-image` - Upload image to task
2. `fluentboards/list-task-images` - Get all images for task
3. `fluentboards/get-task-image` - Get image details and URL
4. `fluentboards/set-cover-image` - Set task cover image
5. `fluentboards/delete-task-image` - Remove image

**Business Value:** ⭐⭐⭐ (Visual task management, design workflows)
**Effort:** M (6 hours - image upload, processing, cover selection)
**Priority:** P2

---

### 8. Comment Images (Visual Context)
**Source:** FluentBoards/app/Models/CommentImage.php
**Table:** wp_fbs_comment_images (inferred)
**Purpose:** Images embedded in comments for visual feedback

**Estimated Tools (4):**
1. `fluentboards/upload-comment-image` - Upload image with comment
2. `fluentboards/list-comment-images` - Get all images for comment
3. `fluentboards/get-comment-image` - Get image URL
4. `fluentboards/delete-comment-image` - Remove image

**Business Value:** ⭐⭐⭐ (Enhances collaboration with visual feedback)
**Effort:** M (5 hours - image upload, comment association)
**Priority:** P2

---

### 9. Notification Users (Read Status)
**Source:** FluentBoards/app/Models/NotificationUser.php
**Table:** wp_fbs_notification_users (pivot)
**Purpose:** Track which users received/read notifications

**Estimated Tools (3):**
1. `fluentboards/get-user-notifications` - Get notifications for current user
2. `fluentboards/get-unread-count` - Count unread notifications
3. `fluentboards/bulk-mark-read` - Mark multiple notifications as read

**Business Value:** ⭐⭐⭐⭐ (Critical for notification UX)
**Effort:** S (4 hours - pivot queries, aggregations)
**Priority:** P1

---

### 10. Board Terms (Generic Term Model)
**Source:** FluentBoards/app/Models/BoardTerm.php
**Table:** wp_fbs_board_terms
**Purpose:** Base model for stages/labels (already exposed via Stage/Label abilities)

**Note:** This is a base model - Stages and Labels extend it. NO new tools needed as they're already covered by existing Stage and Label abilities.

**Business Value:** N/A (implementation detail)
**Effort:** 0 hours
**Priority:** N/A

---

### 11. Attachment (File Model - Already Exposed)
**Source:** FluentBoards/app/Models/Attachment.php
**Note:** Already has 6 abilities in Attachments.php - Full CRUD coverage exists

**Business Value:** N/A (already implemented)
**Effort:** 0 hours
**Priority:** N/A

---

## CRUD Completeness Gaps

### Analysis Result: ✅ COMPLETE

All models with existing abilities have full CRUD coverage:

**Boards** (10 tools):
- ✅ list-boards, get-board, create-board, update-board, delete-board
- ✅ Plus: pin-board, unpin-board, archive-board, restore-board, duplicate-board

**Tasks** (13 tools):
- ✅ list-tasks, get-task, create-task, update-task, delete-task
- ✅ Plus: move-task, clone-task, archive-task, restore-task, change-task-status, update-task-dates, assign-yourself-to-task, detach-yourself-from-task

**Stages** (11 tools):
- ✅ list-stages, create-stage, update-stage, delete-stage
- ✅ Plus: restore-stage, reorder-stages, move-all-tasks, archive-all-tasks, get-archived-stages, sort-stage-tasks, get-stage-positions

**Labels** (7 tools):
- ✅ list-labels, create-label, update-label, delete-label
- ✅ Plus: add-label-to-task, remove-label-from-task, get-task-labels

**Attachments** (6 tools):
- ✅ get-task-attachments, add-task-attachment, update-attachment, delete-attachment
- ✅ Plus: upload-attachment-file, get-attachment-files

**Comments** (7 tools):
- ✅ get-comments, add-comment, update-comment, delete-comment
- ✅ Plus: update-reply, delete-reply, update-comment-privacy

**Users** (16 tools):
- ✅ Full member management, permissions, super admin, user info, activities, boards

**Reporting** (12 tools):
- ✅ Comprehensive reports: project, timesheet, stage-wise, dashboard, activities, member reports

**No CRUD gaps identified** - All existing models have complete operations.

---

## Custom Operations from Validation

### 1. Stage Archive/Restore (Soft Delete Pattern)

**Evidence from VALIDATED_SHAPES.md:**
```
Stages use soft deletion with archived_at timestamp
- archived_at: null (active)
- archived_at: "2025-10-04 18:01:26" (archived)
```

**Existing Tools:**
- ✅ `fluentboards/delete-stage` - Archives stage (sets archived_at)
- ✅ `fluentboards/restore-stage` - Restores archived stage (clears archived_at)
- ✅ `fluentboards/get-archived-stages` - Lists archived stages

**Status:** ✅ FULLY IMPLEMENTED - No gaps

---

### 2. Task Position Management

**Evidence from VALIDATED_SHAPES.md:**
```
Task has position field (integer, auto-incremented)
Controls display order within stage
```

**Existing Tools:**
- ✅ `fluentboards/move-task` - Moves task to stage (position handled by API)
- ⚠️ NO explicit position control within same stage

**Missing Tools (2):**

**Tool:** `fluentboards/reorder-task-in-stage`
**Description:** "Change task position within its current stage"
**User Story:** "As a user, I need to reorder tasks by priority within my current stage without moving to another stage"
**Parameters:**
- task_id (required, integer)
- new_position (required, integer)
**Business Value:** ⭐⭐⭐⭐ (UX essential for task prioritization)
**Effort:** M (4 hours - position recalculation for other tasks)
**Priority:** P1

**Tool:** `fluentboards/move-task-to-position`
**Description:** "Move task to specific position in target stage"
**User Story:** "As a user, I want to move task to exact position in another stage (e.g., top of 'In Progress')"
**Parameters:**
- task_id (required)
- target_stage_id (required)
- position (required, integer)
**Business Value:** ⭐⭐⭐⭐⭐ (core workflow - precise task placement)
**Effort:** M (5 hours - cross-stage position coordination)
**Priority:** P0

---

### 3. Board Settings Management

**Evidence from VALIDATED_SHAPES.md:**
```
Board has settings field (object, nullable)
Contains board configuration
```

**Existing Tools:**
- ⚠️ Settings included in update-board but no dedicated access

**Missing Tools (2):**

**Tool:** `fluentboards/get-board-settings`
**Description:** "Get board configuration settings object"
**User Story:** "As an integration, I need to check board settings before performing operations (e.g., permissions, automation rules)"
**Parameters:**
- board_id (required)
**Returns:** Settings object with all configuration
**Business Value:** ⭐⭐⭐ (integration support, automation)
**Effort:** XS (1 hour - simple accessor)
**Priority:** P2

**Tool:** `fluentboards/update-board-settings`
**Description:** "Update board settings (partial update supported)"
**User Story:** "As a board admin, I need to configure board behavior (default stage, automation, permissions) without updating other fields"
**Parameters:**
- board_id (required)
- settings (required, object - partial update)
**Business Value:** ⭐⭐⭐⭐ (admin features, fine-grained control)
**Effort:** S (3 hours - merge settings, validation)
**Priority:** P1

---

### 4. Task Settings Management

**Evidence from VALIDATED_SHAPES.md:**
```
Task has settings object with:
- cover: { backgroundColor: "" }
- subtask_count: 0
- attachment_count: 0
- subtask_completed_count: 0
```

**Existing Tools:**
- ⚠️ Settings included in update-task but no dedicated access

**Missing Tools (1):**

**Tool:** `fluentboards/update-task-cover`
**Description:** "Update task cover image or background color"
**User Story:** "As a user, I want to visually distinguish important tasks with cover colors/images"
**Parameters:**
- task_id (required)
- cover_type (required: 'color' | 'image')
- cover_value (required: hex color or image_id)
**Business Value:** ⭐⭐⭐ (visual task management)
**Effort:** S (3 hours - settings merge, validation)
**Priority:** P2

---

### 5. Stage Position Management

**Evidence from VALIDATED_SHAPES.md:**
```
Stage has position field (integer)
Determines stage order on board
```

**Existing Tools:**
- ✅ `fluentboards/reorder-stages` - Full stage reordering
- ✅ `fluentboards/get-stage-positions` - Get position map

**Status:** ✅ FULLY IMPLEMENTED - No gaps

---

### 6. Auto-Slug Generation

**Evidence from VALIDATED_SHAPES.md:**
```
Task auto-generates slug from title
slug: "test-task"
```

**Status:** ✅ Automatic feature in create-task - No tools needed

---

### 7. Default Settings Population

**Evidence from VALIDATED_SHAPES.md:**
```
Stage auto-generates settings if not provided:
settings: {
  "default_task_status": "open",
  "is_template": false
}
```

**Status:** ✅ Automatic feature in create-stage - No tools needed

---

## Workflow & Business Logic Opportunities

### 1. Bulk Task Operations

**Evidence:** Multiple tasks commonly need same operations

**Missing Tools (4):**

**1. `fluentboards/bulk-update-tasks`**
**Description:** "Update multiple tasks at once (status, priority, assignee)"
**User Story:** "As a project manager, I need to change priority for all tasks in a sprint at once"
**Parameters:**
- task_ids (required, array of integers)
- updates (required, object: { status?, priority?, assigned_to? })
**Business Value:** ⭐⭐⭐⭐ (productivity, sprint planning)
**Effort:** M (6 hours - batch processing, validation)
**Priority:** P1

**2. `fluentboards/bulk-move-tasks`**
**Description:** "Move multiple tasks to target stage"
**User Story:** "As a team lead, I want to move all 'Review' tasks to 'Done' at sprint end"
**Parameters:**
- task_ids (required, array)
- target_stage_id (required)
- position_strategy (optional: 'top' | 'bottom' | 'preserve_order')
**Business Value:** ⭐⭐⭐⭐⭐ (sprint planning, workflow management)
**Effort:** M (6 hours - batch move, position calculation)
**Priority:** P0

**3. `fluentboards/bulk-delete-tasks`**
**Description:** "Delete multiple tasks (with archive option)"
**User Story:** "As a board admin, I need to cleanup completed tasks at project end"
**Parameters:**
- task_ids (required, array)
- permanent (optional, boolean - default false archives)
**Business Value:** ⭐⭐⭐ (cleanup, maintenance)
**Effort:** S (4 hours - batch delete, cascade handling)
**Priority:** P2

**4. `fluentboards/bulk-assign-labels`**
**Description:** "Apply label to multiple tasks"
**User Story:** "As a project manager, I want to tag all Q4 tasks with 'Q4-2025' label"
**Parameters:**
- task_ids (required, array)
- label_id (required)
**Business Value:** ⭐⭐⭐⭐ (categorization, filtering)
**Effort:** S (4 hours - batch relation creation)
**Priority:** P1

---

### 2. Board Templates & Duplication

**Evidence:** Boards have type field, structure is reusable

**Existing Tools:**
- ✅ `fluentboards/duplicate-board` - Already exists!

**Missing Tools (1):**

**Tool:** `fluentboards/export-board-template`
**Description:** "Export board structure as reusable JSON template (stages, labels, settings)"
**User Story:** "As a team lead, I want to share our sprint board template with other teams"
**Parameters:**
- board_id (required)
- include_tasks (optional, boolean - default false)
**Returns:** JSON template with stages, labels, settings
**Business Value:** ⭐⭐⭐ (team efficiency, standardization)
**Effort:** M (5 hours - JSON export, template schema)
**Priority:** P2

---

## Summary by Category

### CRUD Completeness
- Missing delete operations: 0 tools
- Missing update operations: 0 tools
- Missing get operations: 0 tools
- Total CRUD gaps: **0 tools**
- Estimated effort: **0 hours**

### New Model Coverage
- Activity (audit log): 5 tools - 10 hours
- Notification: 6 tools - 8 hours
- Webhook: 5 tools - 6 hours
- TaskMeta: 5 tools - 4 hours
- Board Meta: 5 tools - 4 hours
- Teams: 5 tools - 5 hours
- TaskImage: 5 tools - 6 hours
- CommentImage: 4 tools - 5 hours
- NotificationUser: 3 tools - 4 hours
- Total new models: **55 tools** (11 models, 2 already covered)
- Estimated effort: **52 hours**

### Custom Operations
- Task position in stage: 2 tools - 9 hours
- Board settings: 2 tools - 4 hours
- Task cover: 1 tool - 3 hours
- Total custom: **5 tools** (2 already implemented: stage archive/restore, stage reorder)
- Estimated effort: **16 hours**

### Workflow Features
- Bulk task operations: 4 tools - 20 hours
- Board templates: 1 tool - 5 hours (1 already exists: duplicate-board)
- Total workflow: **5 tools**
- Estimated effort: **25 hours**

### Grand Total
**67 new tool opportunities**
**93 total hours estimated effort**

---

## Priority Breakdown

### TIER 1 - Critical (P0)
**Tools: 8 | Hours: 22**

**Focus:** Core workflow, essential user value

1. `fluentboards/create-activity` - Audit trail foundation (10h)
2. `fluentboards/create-notification` - Notification system (8h)
3. `fluentboards/move-task-to-position` - Precise task placement (5h)
4. `fluentboards/bulk-move-tasks` - Sprint workflow (6h)

**Plus 4 more activity tools: list/get/update/delete (included in 10h)**

---

### TIER 2 - High (P1)
**Tools: 18 | Hours: 64**

**Focus:** Important features, productivity, team collaboration

**Notification System (3 tools - 8h):**
- list-notifications, get-notification, mark-notification-read

**Webhooks (5 tools - 6h):**
- Full CRUD for external integrations

**Task Meta (5 tools - 4h):**
- Custom fields and extensibility

**Position Management (1 tool - 4h):**
- reorder-task-in-stage

**Settings (1 tool - 3h):**
- update-board-settings

**NotificationUser (3 tools - 4h):**
- get-user-notifications, get-unread-count, bulk-mark-read

**Bulk Operations (3 tools - 14h):**
- bulk-update-tasks, bulk-assign-labels

**Other (1 tool - 8h):**
- mark-all-read, delete-notification

---

### TIER 3 - Medium (P2)
**Tools: 41 | Hours: 123**

**Focus:** Nice-to-have, admin features, visual enhancements

**Board Meta (5 tools - 4h):**
- Custom board fields

**Teams (5 tools - 5h):**
- Team-based access control

**Task Images (5 tools - 6h):**
- Visual task management

**Comment Images (4 tools - 5h):**
- Visual feedback in comments

**Board Settings (1 tool - 1h):**
- get-board-settings

**Task Cover (1 tool - 3h):**
- update-task-cover

**Bulk Delete (1 tool - 4h):**
- bulk-delete-tasks

**Templates (1 tool - 5h):**
- export-board-template

**Activity Management (3 tools - included in P0):**
- Plus notification delete, webhook full CRUD

---

## Implementation Recommendations

### Phase 1: Foundation (P0) - 22 hours
**Goal:** Enable audit logging and notifications (core UX)

1. Activity model (5 tools) - Audit trail
2. Notification model (6 tools) - User notifications
3. Position precision (2 tools) - Task placement
4. Critical bulk ops (1 tool) - Sprint workflow

**Deliverable:** Activity feed, notification system, precise task control

---

### Phase 2: Productivity (P1) - 64 hours
**Goal:** Integrations, custom fields, bulk operations

1. Webhooks (5 tools) - External integrations
2. Task Meta (5 tools) - Custom fields
3. Notification Users (3 tools) - Read status
4. Bulk operations (3 tools) - Batch updates
5. Settings management (2 tools) - Fine-grained control

**Deliverable:** Integration capability, extensibility, batch productivity

---

### Phase 3: Enhancement (P2) - 123 hours
**Goal:** Visual features, teams, templates

1. Board Meta (5 tools)
2. Teams (5 tools)
3. Task Images (5 tools)
4. Comment Images (4 tools)
5. Templates (1 tool)
6. Remaining bulk ops (1 tool)

**Deliverable:** Visual task management, team features, template system

---

## Model Coverage Status

| Model | Table | Has Abilities | CRUD Complete | Custom Ops | Priority |
|-------|-------|---------------|---------------|------------|----------|
| Board | fbs_boards | ✅ Yes | ✅ 5/5 | ⚠️ Settings (2) | P1 |
| Task | fbs_tasks | ✅ Yes | ✅ 5/5 | ⚠️ Position (2), Cover (1) | P0/P1 |
| Stage | fbs_board_terms | ✅ Yes | ✅ 5/5 | ✅ All (archive/restore/reorder) | ✅ |
| Label | fbs_board_terms | ✅ Yes | ✅ 5/5 | ✅ All | ✅ |
| Attachment | fbs_attachments | ✅ Yes | ✅ 5/5 | ✅ All | ✅ |
| Comment | fbs_comments | ✅ Yes | ✅ 6/6 | ✅ All (privacy, replies) | ✅ |
| User | wp_users | ✅ Yes | ✅ Full | ✅ All (roles, perms) | ✅ |
| Reporting | (views) | ✅ Yes | N/A | ✅ All | ✅ |
| **Activity** | fbs_activities | ❌ No | ❌ 0/5 | ❌ 0 | **P0** |
| **Notification** | fbs_notifications | ❌ No | ❌ 0/6 | ❌ 0 | **P0** |
| **Webhook** | fbs_metas | ❌ No | ❌ 0/5 | ❌ 0 | **P1** |
| **TaskMeta** | fbs_task_metas | ❌ No | ❌ 0/5 | ❌ 0 | **P1** |
| **Board Meta** | fbs_metas | ❌ No | ❌ 0/5 | ❌ 0 | **P2** |
| **Team** | fbs_teams | ❌ No | ❌ 0/5 | ❌ 0 | **P2** |
| **TaskImage** | fbs_task_images | ❌ No | ❌ 0/5 | ❌ 0 | **P2** |
| **CommentImage** | fbs_comment_images | ❌ No | ❌ 0/4 | ❌ 0 | **P2** |
| **NotificationUser** | fbs_notification_users | ❌ No | ❌ 0/3 | ❌ 0 | **P1** |
| BoardTerm | fbs_board_terms | ✅ (via Stage/Label) | ✅ | ✅ | ✅ |
| Relation | fbs_relations | ✅ (via Labels) | ✅ | ✅ | ✅ |

**Coverage:** 8/19 models (42%)
**Opportunity:** 11 models for new tools (58%)

---

## Next Steps

1. **Validate Priority:** Review P0 tools with product team
2. **Technical Spike:** Activity & Notification implementation (2 hours)
3. **Schema Documentation:** Validate table structures for new models
4. **API Research:** Verify FluentBoards internal APIs for new operations
5. **Implementation Plan:** Create detailed specs for Phase 1 tools

---

## Notes

- All estimates include: ability registration, input validation, permission checks, error handling, unit tests
- Custom operations leverage existing models (settings merging, position recalculation)
- Bulk operations need transaction support for consistency
- Template/export features need JSON schema definition
- Image tools require WordPress media library integration
- Webhook tools need UUID generation and security considerations
