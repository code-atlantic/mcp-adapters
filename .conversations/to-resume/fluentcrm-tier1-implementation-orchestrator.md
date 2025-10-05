# Orchestrator Context: MCP Adapters FluentCRM/FluentBoards Development

## Session Context (2025-10-04)

This session focused on:
1. Cleaning up greedy git commits (FunnelSequences.php + FluentBoards docs)
2. Creating orchestrator memory for parallel agent spawning
3. Updating documentation for wp-cli-direct.sh wrapper usage
4. Fixing SUBAGENT_BASELINE.md with critical tool usage guidance
5. FluentCRM TIER1 implementation (38 tools completed)
6. MCP command system creation and documentation

## Current State

### Git Status
**Branch**: `develop`

**Recent Commits** (in order):
1. `e8cb99d` - Add WP-CLI wrapper script documentation to mcp-adapters CLAUDE.md
2. `dd884b4` - Update SUBAGENT_BASELINE with critical wp-cli-direct.sh and PHPCS guidance
3. `c04ba0a` - Register FunnelSequences ability in FluentCRM adapter
4. `0d38ced` - Add FunnelSequences ability for FluentCRM funnel automation (5 tools)
5. `c1cd5ea` - Apply PHPCS formatting to FluentCRM validation scripts
6. `214dc30` - Implement TIER1 contact management and funnel tracking tools (33 tools)
7. `2adada1` - Fix PHPCS formatting in FluentBoards abilities

**Uncommitted Files**:
- `docs/fluentboards/` directory (FluentBoards discovery docs from parallel agent)
  - enhancement-roadmap.md
  - field-coverage.md
  - new-model-opportunities.md
  - quality-audit.md
  - relationship-gaps.md

**Working Directory**: Clean except for uncommitted FluentBoards docs

### FluentCRM TIER1 Implementation Status

**Completed & Committed**:
- ✅ **Contact Management** (33 tools across 8 abilities):
  - SubscriberNotes.php (5 tools) - Contact note CRUD
  - CompanyNotes.php (5 tools) - Company note CRUD
  - Webhooks.php (6 tools) - Incoming webhook management
  - FunnelTracking.php (5 tools) - Funnel enrollment/tracking
  - Enhanced Subscribers.php (+8 tools) - Tag/list relationships
  - Enhanced Companies.php (+4 tools) - Contact relationships
  - Enhanced Campaigns.php (+5 tools) - Email tracking/engagement
  - Updated Funnels.php (toArray pattern)

- ✅ **Funnel Automation** (5 tools):
  - FunnelSequences.php - Sequence workflow management
    - add-funnel-sequence
    - list-funnel-sequences
    - update-funnel-sequence
    - remove-funnel-sequence
    - reorder-funnel-sequences

**Total New Tools**: 38 tools, ~5800 lines of code

**TIER1 Remaining**:
- Campaign Analytics (6 tools) - Campaign performance metrics, engagement tracking
- Company Management (8 tools) - Advanced company operations (beyond current 4 tools)

### FluentBoards Status

**Existing Abilities** (8 files, committed earlier):
- Boards.php (10 tools)
- Tasks.php (11 tools)
- Stages.php (9 tools)
- Comments.php (5 tools)
- Labels.php (6 tools)
- Attachments.php (5 tools)
- Users.php (8 tools)
- Reporting.php (9 tools)

**Uncommitted Work**:
- Discovery/analysis docs in `docs/fluentboards/` (needs separate commit)

**TIER1 Priorities** (Not Yet Implemented):
1. Subtasks - Hierarchical task breakdown
2. Activities/Timeline - Activity feed and audit trail
3. Webhooks - Outgoing webhook automation
4. Advanced Permissions - Board-level user permissions
5. Custom Fields - Task custom field management

### Documentation Updates

**Files Updated & Committed**:

1. **`.claude/SUBAGENT_BASELINE.md`** (`dd884b4`):
   - ✅ Fixed wp-cli-direct.sh usage (MUST include "wp" prefix)
   - ✅ Added PHPCS CLI output escaping guidance
   - ✅ Explained `exec "$@"` behavior
   - ✅ Common mistake prevention examples

2. **`CLAUDE.md`** (plugin, `e8cb99d`):
   - ✅ Added WordPress CLI Operations section
   - ✅ wp-cli-direct.sh wrapper guidance
   - ✅ Linked to SUBAGENT_BASELINE for complete details

3. **Root `CLAUDE.md`** (not in git, updated):
   - ✅ Same WP-CLI wrapper documentation
   - ⚠️ File not tracked in git (WordPress root is not a git repo)

### Orchestrator Memories Created

**Memory 1: Project-Wide Orchestrator Context** (`aa4e161f-0ed0-4a14-96a1-1cbb4e35fb2c`)
- Tags: `["orchestrator", "project-context", "mcp-adapters", "fluentcrm", "fluentboards", "tier-system", "command-system"]`
- Importance: 0.95
- Contains:
  - Project structure and location
  - Critical tool usage rules (wp-cli-direct.sh, composer, modern CLI tools)
  - Command system overview and TIER definitions
  - Recent TIER1 FluentCRM work completed (38 tools)
  - TIER1 remaining work
  - Sub-agent baseline location
  - Parallel agent patterns
  - Quality standards
  - Common mistakes to avoid
  - Current state snapshot

**Memory 2: FluentBoards Orchestrator Context** (`445bcf9d-0f24-4ab0-9bf5-58f29290d691`)
- Tags: `["orchestrator", "fluentboards", "project-context", "mcp-adapters", "tier-system", "separation-of-concerns"]`
- Importance: 0.95
- Contains:
  - FluentBoards-specific structure
  - FluentBoards command system
  - TIER system for FluentBoards
  - Current implementation status (8 abilities)
  - TIER1 priorities (subtasks, activities, webhooks, etc.)
  - Model structure and relationships
  - FluentBoards vs FluentCRM separation rules
  - Quality standards and patterns
  - Uncommitted work (docs/fluentboards/)
  - Next steps

**Recall Strategy**:
To activate orchestrator mode, query memories with:
- Tag: `orchestrator`
- Query: "orchestrator context" or "project orchestrator"
- Both memories will provide complete context for spawning parallel agents

## Critical Tool Usage Rules (From SUBAGENT_BASELINE.md)

### WP-CLI Wrapper Script

**✅ CORRECT**:
```bash
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh wp plugin list
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh wp eval 'var_dump(\FluentCrm\App\Models\Subscriber::count());'
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh wp eval-file wp-content/plugins/mcp-adapters/scripts/validate-subscriber.php
```

**❌ WRONG**:
```bash
wp plugin list  # Missing wrapper
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh plugin list  # Missing "wp" prefix
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval-file scripts/validate.php  # Missing "wp" prefix
```

**Why**:
- Wrapper uses `exec "$@"` - it runs the FULL command you pass
- Must include "wp" prefix: `wp-cli-direct.sh wp plugin list`
- Missing "wp" causes: `exec: plugin: not found`

### PHPCS Commands

**✅ CORRECT**:
```bash
composer lint        # Check coding standards
composer format      # Auto-fix violations
```

**❌ WRONG**:
```bash
phpcs path/to/file.php
phpcbf path/to/file.php
vendor/bin/phpcs path/to/file.php
```

### PHPCS CLI Output Escaping

**When to use `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped`**:
- ✅ WP-CLI scripts (eval, eval-file contexts)
- ✅ Validation scripts run via wp-cli-direct.sh
- ✅ CLI debugging/reporting tools
- ✅ Scripts in `scripts/` or `validate-*.php` files

**When NOT to ignore**:
- ❌ Web-facing code (abilities, REST API, admin pages)
- ❌ Anything that outputs to HTML
- ❌ User-facing interfaces
- ❌ AJAX responses

**Example**:
```php
// ✅ CORRECT - Validation script
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo "Validation Results\n";

// ❌ WRONG - Web context (security vulnerability)
echo '<div>' . $user_input . '</div>';

// ✅ CORRECT - Web context
echo '<div>' . esc_html( $user_input ) . '</div>';
```

## Command System Reference

### TIER System (USER VALUE Priority)
- **TIER1** = Critical user needs (can't do job without it)
- **TIER2** = High-value productivity (makes work way easier)
- **TIER3** = Enhanced usability (nice-to-have)

### Key Commands
```bash
# Validation
/integration:validate fluentcrm|fluentboards

# Discovery
/enhance:discover fluentcrm|fluentboards

# Implementation
/enhance:implement fluentcrm TIER1 --focus "feature-name"
/enhance:implement fluentboards TIER1 --focus "feature-name"

# Testing
/test:create fluentcrm --focus tier1
/test:create fluentboards --focus tier1

# Polish
/enhance:polish fluentcrm --loop --iterations 2
```

### Command Locations
- **Commands**: `.claude/commands/` directory
- **Cheatsheet**: `docs/COMMAND_CHEATSHEET.md` (523 lines)
- **Baseline**: `.claude/SUBAGENT_BASELINE.md` (452 lines)

## MCP Command System Creation

### Command System Architecture

The custom command system was created to streamline MCP adapter development with tier-based prioritization:

**Command Structure** (`.claude/commands/`):
```
.claude/commands/
├── integration/
│   └── validate.md          # /integration:validate - Concurrent model validation
├── enhance/
│   ├── discover.md          # /enhance:discover - Enhancement opportunity analysis
│   ├── implement.md         # /enhance:implement - TIER-based implementation
│   └── polish.md            # /enhance:polish - Quality improvement iterations
└── test/
    └── create.md            # /test:create - E2E test generation
```

**Command Features**:
- **TIER-based prioritization** - Focus on user value (TIER1=critical, TIER2=productivity, TIER3=usability)
- **Parallel agent orchestration** - `--concurrent N` flag for batch processing
- **Focus targeting** - `--focus "feature-name"` for specific areas
- **Iterative improvement** - `--loop --iterations N` for quality passes
- **Intelligent delegation** - Sub-agent spawning with @.claude/SUBAGENT_BASELINE.md context injection

### FluentCRM Command Integration

**Model Validation** (`/integration:validate fluentcrm`):
- Validates all FluentCRM models concurrently
- Checks field coverage, relationships, CRUD operations
- Identifies enhancement opportunities
- Generates validation reports

**Enhancement Discovery** (`/enhance:discover fluentcrm`):
- Analyzes FluentCRM codebase for opportunities
- Categorizes by TIER (user value)
- Identifies missing relationships, fields, operations
- Prioritizes by business impact

**TIER Implementation** (`/enhance:implement fluentcrm TIER1`):
- Implements critical user needs first (TIER1)
- Spawns parallel agents for large scopes
- Focuses on specific areas with `--focus "funnel-automation"`
- Injects SUBAGENT_BASELINE for tool usage correctness

**Testing** (`/test:create fluentcrm --focus tier1`):
- Generates E2E tests for implemented abilities
- Validates all parameters and error cases
- Tests relationships and edge cases

**Examples**:
```bash
# Validate all FluentCRM models
/integration:validate fluentcrm

# Discover enhancement opportunities
/enhance:discover fluentcrm

# Implement TIER1 features (critical user needs)
/enhance:implement fluentcrm TIER1

# Implement with specific focus
/enhance:implement fluentcrm TIER1 --focus "funnel-automation"

# Create tests for TIER1 features
/test:create fluentcrm --focus tier1

# Polish implementation with 2 improvement iterations
/enhance:polish fluentcrm --loop --iterations 2
```

### Command System Benefits

1. **Consistency**: All agents follow same patterns and tool usage rules
2. **Efficiency**: Parallel processing with intelligent batching
3. **Quality**: SUBAGENT_BASELINE prevents common mistakes
4. **Traceability**: Clear TIER-based roadmap and progress tracking
5. **Scalability**: Easy to add new plugins (FluentBoards, etc.)

### TIER System Definition

**TIER1** - Critical User Needs (can't do job without it):
- Funnel automation sequence management
- Complete field coverage for core operations
- Essential CRUD operations for all models
- Campaign analytics and tracking
- Company management operations

**TIER2** - High-Value Productivity (makes work way easier):
- Bulk operations (tag subscribers, move tasks)
- Advanced filtering and search
- Reporting and analytics
- Workflow automation features
- Template systems

**TIER3** - Enhanced Usability (nice-to-have):
- Custom sorting options
- Additional export formats
- UI enhancements
- Advanced customization options
- Power user features

## Next Steps / Recommendations

### Immediate Actions
1. **Commit FluentBoards Docs**:
   ```bash
   cd /Users/danieliser/Local\ Sites/mcp/app/public/wp-content/plugins/mcp-adapters
   git add docs/fluentboards/
   git commit -m "Add FluentBoards discovery and enhancement analysis

   Discovery documentation:
   - enhancement-roadmap.md - Overall enhancement strategy
   - field-coverage.md - Model field coverage analysis
   - new-model-opportunities.md - Additional model integration
   - quality-audit.md - Code quality assessment
   - relationship-gaps.md - Relationship mapping gaps

   Generated by parallel agent during FluentBoards analysis."
   ```

2. **Test TIER1 FluentCRM Work**:
   ```bash
   /test:create fluentcrm --focus tier1
   ```
   - Tests all 38 new tools across contact management and funnel automation
   - Validates FunnelSequences, SubscriberNotes, CompanyNotes, Webhooks, FunnelTracking
   - Validates enhanced Subscribers, Companies, Campaigns

3. **Complete TIER1 FluentCRM**:
   - Campaign Analytics (6 tools) - Performance metrics, engagement tracking
   - Company Management (8 tools) - Advanced operations

### Parallel Agent Workflow

**To spawn multiple orchestrators**:
1. Recall orchestrator memories:
   ```
   Query: "orchestrator context"
   Tags: ["orchestrator", "project-context"]
   ```

2. Spawn separate agents:
   ```bash
   # FluentCRM Agent
   /enhance:implement fluentcrm TIER1 --focus "campaign-analytics"

   # FluentBoards Agent (parallel)
   /enhance:implement fluentboards TIER1 --focus "subtasks"
   ```

3. Keep commits separated by plugin

## Session Metrics

**Time Spent**: ~45 minutes
**Commits Made**: 7 commits
**Lines Added**: ~6800 lines (code + docs)
**Tools Implemented**: 38 new tools
**Documentation Updated**: 3 files (SUBAGENT_BASELINE.md, 2x CLAUDE.md)
**Memories Created**: 2 orchestrator memories
**Issues Fixed**: Greedy commits, wp-cli-direct.sh documentation, PHPCS guidance

## Key Learnings / Decisions

1. **Greedy Commits are Bad**: Fixed by splitting into separate commits for:
   - PHPCS formatting (validation scripts only)
   - FunnelSequences.php implementation
   - FluentCrmAdapter registration
   - FluentBoards docs (uncommitted, needs separate commit)

2. **wp-cli-direct.sh Wrapper**: Must include "wp" prefix because wrapper uses `exec "$@"`
   - Common mistake: `wp-cli-direct.sh plugin list` → `exec: plugin: not found`
   - Correct: `wp-cli-direct.sh wp plugin list`

3. **PHPCS CLI Context**: Validation scripts need `phpcs:ignore` for OutputNotEscaped
   - CLI context (wp eval-file) is safe from XSS
   - Web context (REST API, admin pages) must escape output

4. **Orchestrator Memories**: Created for parallel agent spawning
   - Project-wide context (FluentCRM + FluentBoards)
   - FluentBoards-specific context
   - Easy recall via tags and queries

5. **TIER1 Priority Misunderstanding**: "funnel-automation" initially interpreted as general CRM features
   - User wanted: Funnel sequence workflow management (add/remove/reorder steps)
   - Agent built: SubscriberNotes, CompanyNotes, Webhooks (valuable but wrong focus)
   - Solution: Kept the work (valuable TIER1), then built actual FunnelSequences.php

## File Locations

**Project Root**: `/Users/danieliser/Local Sites/mcp/app/public/wp-content/plugins/mcp-adapters`

**Key Directories**:
- `.claude/` - Command system and baseline docs
- `classes/Adapters/FluentCrm/Abilities/` - FluentCRM MCP tools
- `classes/Adapters/FluentBoards/Abilities/` - FluentBoards MCP tools
- `docs/` - Command cheatsheet and discovery docs
- `scripts/` - Validation and utility scripts

**Key Files**:
- `.claude/SUBAGENT_BASELINE.md` - Tool usage rules for spawned agents
- `.claude/commands/` - Slash command implementations
- `docs/COMMAND_CHEATSHEET.md` - Complete command reference
- `CLAUDE.md` - Plugin-level Claude guidance

## Contact Information

**User**: danieliser
**Project**: MCP Adapters (WordPress plugin for AI integration)
**Environment**: Local by Flywheel
**Git Branch**: develop
**Session Date**: 2025-10-04

---

## Quick Resume Checklist

When resuming this session:
- [ ] Review uncommitted `docs/fluentboards/` directory
- [ ] Check git status for any new changes
- [ ] Recall orchestrator memories if spawning parallel agents
- [ ] Review TIER1 completion status (FluentCRM ~60% complete)
- [ ] Consider next steps: Test TIER1, Complete Campaign Analytics, or FluentBoards TIER1
