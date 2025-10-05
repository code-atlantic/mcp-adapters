# Sub-Agent Baseline Instructions

**CRITICAL:** These instructions apply to ALL sub-agents spawned by slash commands.

---

## Project-Specific Tool Usage

### ✅ WordPress CLI Commands

**ALWAYS use the wrapper script:**
```bash
# ✅ CORRECT
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh plugin list
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval 'return get_option("home");'

# ❌ WRONG - Never call wp directly
wp plugin list
wp eval 'return get_option("home");'
```

**Why?** The wrapper script:
- Sets correct working directory
- Uses proper WordPress installation path
- Handles Local by Flywheel environment
- Prevents "WordPress not found" errors

**Common WP-CLI Operations:**
```bash
# List plugins
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh plugin list

# Run PHP code in WordPress context
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval 'var_dump(\FluentCrm\App\Models\Subscriber::count());'

# Run validation scripts
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval-file scripts/validate-fluentcrm-Subscriber.php
```

---

### ✅ PHP CodeSniffer (PHPCS/PHPCBF)

**ALWAYS use Composer commands:**
```bash
# ✅ CORRECT - Check coding standards
composer lint

# ✅ CORRECT - Auto-fix violations
composer lint:fix

# ✅ CORRECT - Check specific files
composer lint -- path/to/file.php

# ❌ WRONG - Never call phpcs/phpcbf directly
phpcs path/to/file.php
phpcbf path/to/file.php
vendor/bin/phpcs path/to/file.php
```

**Why?** Composer commands:
- Use project's `.phpcs.xml.dist` ruleset automatically
- Have correct baseline configuration
- Include WordPress Coding Standards (WPCS)
- Apply proper severity levels

**PHPCS Workflow:**
```bash
# 1. Check for violations
composer lint

# 2. Auto-fix what's possible
composer lint:fix

# 3. Verify fixes
composer lint

# 4. If violations remain, manually fix or document
```

---

### ✅ JavaScript/TypeScript Linting

**Use npm scripts:**
```bash
# ✅ CORRECT - Lint JS/TS
npm run lint:js

# ✅ CORRECT - Auto-fix JS/TS
npm run lint:js:fix

# ✅ CORRECT - Format with Prettier
npm run format

# ❌ WRONG - Direct ESLint calls
eslint src/
```

---

## File Paths and Working Directory

### Working Directory
```bash
# Project root (always start here)
cd /Users/danieliser/Local\ Sites/mcp/app/public/wp-content/plugins/mcp-adapters
```

### Important Paths
```bash
# WordPress root (for wp-cli-direct.sh)
/Users/danieliser/Local Sites/mcp/app/public/

# Plugin root (your working directory)
/Users/danieliser/Local Sites/mcp/app/public/wp-content/plugins/mcp-adapters/

# WP-CLI wrapper script
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh

# Abilities
classes/Adapters/<Plugin>/Abilities/

# Tests
tests/e2e/<plugin>/abilities/

# Validation scripts
scripts/validate-<plugin>-<Model>.php

# Documentation
claudedocs/
docs/
```

---

## Command Patterns

### Pattern 1: Validate a Model
```bash
# 1. Create validation script
# (Write to scripts/validate-fluentcrm-Subscriber.php)

# 2. Run validation via WP-CLI wrapper
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval-file scripts/validate-fluentcrm-Subscriber.php

# 3. Check for PHPCS violations
composer lint -- scripts/validate-fluentcrm-Subscriber.php

# 4. Auto-fix violations
composer lint:fix -- scripts/validate-fluentcrm-Subscriber.php
```

### Pattern 2: Update Ability File
```bash
# 1. Read existing file
# (Use Read tool on classes/Adapters/FluentCrm/Abilities/Subscribers.php)

# 2. Edit file
# (Use Edit tool to make changes)

# 3. Check PHPCS
composer lint -- classes/Adapters/FluentCrm/Abilities/Subscribers.php

# 4. Auto-fix
composer lint:fix -- classes/Adapters/FluentCrm/Abilities/Subscribers.php

# 5. Verify no violations remain
composer lint -- classes/Adapters/FluentCrm/Abilities/Subscribers.php
```

### Pattern 3: Create Test File
```bash
# 1. Write test file
# (Write to tests/e2e/fluentcrm/abilities/subscribers.test.ts)

# 2. Format with Prettier
npm run format -- tests/e2e/fluentcrm/abilities/subscribers.test.ts

# 3. Lint TypeScript
npm run lint:js

# 4. Fix violations
npm run lint:js:fix
```

---

## Common Mistakes to Avoid

### ❌ Mistake 1: Calling wp directly
```bash
# WRONG
wp eval 'echo "test";'

# RIGHT
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval 'echo "test";'
```

### ❌ Mistake 2: Using phpcs without ruleset
```bash
# WRONG
phpcs classes/Adapters/FluentCrm/Abilities/Subscribers.php

# RIGHT
composer lint -- classes/Adapters/FluentCrm/Abilities/Subscribers.php
```

### ❌ Mistake 3: Using vendor/bin paths
```bash
# WRONG
vendor/bin/phpcs --standard=.phpcs.xml.dist file.php

# RIGHT
composer lint -- file.php
```

### ❌ Mistake 4: Forgetting to fix PHPCS violations
```bash
# INCOMPLETE - Just checking
composer lint

# COMPLETE - Check and fix
composer lint
composer lint:fix
composer lint  # Verify
```

### ❌ Mistake 5: Not using wrapper script path escaping
```bash
# WRONG - Spaces break command
/Users/danieliser/Local Sites/mcp/app/public/wp-cli-direct.sh eval 'test'

# RIGHT - Escaped spaces
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval 'test'
```

---

## Quality Gates

### Before Completing ANY Task:

1. **PHPCS Check (PHP files)**
   ```bash
   composer lint -- path/to/modified/file.php
   # If violations exist:
   composer lint:fix -- path/to/modified/file.php
   ```

2. **Prettier Check (JS/TS files)**
   ```bash
   npm run format -- path/to/modified/file.ts
   ```

3. **Verify Changes Work**
   ```bash
   # For validation scripts:
   /Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval-file scripts/validate-plugin-Model.php

   # For tests:
   npm run test:e2e -- tests/e2e/plugin/abilities/test-file.test.ts
   ```

---

## Error Recovery

### If WP-CLI Fails:
```bash
# Error: "WordPress installation not found"
# Solution: You forgot to use wp-cli-direct.sh wrapper

# WRONG
wp plugin list

# RIGHT
/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh plugin list
```

### If PHPCS Fails:
```bash
# Error: "Ruleset not found" or "Standard not specified"
# Solution: You called phpcs directly instead of through composer

# WRONG
phpcs file.php

# RIGHT
composer lint -- file.php
```

### If Path Not Found:
```bash
# Error: "No such file or directory: /Users/danieliser/Local Sites/..."
# Solution: You forgot to escape spaces in paths

# WRONG
cd /Users/danieliser/Local Sites/mcp/app/public

# RIGHT
cd /Users/danieliser/Local\ Sites/mcp/app/public
```

---

## Success Criteria for Sub-Agents

Before reporting task complete, verify:

✅ All PHP files pass `composer lint`
✅ All JS/TS files pass `npm run lint:js`
✅ All WP-CLI commands used wrapper script
✅ All validation scripts run successfully
✅ All test files formatted with Prettier
✅ No direct calls to `wp`, `phpcs`, `phpcbf`, `eslint`
✅ All paths properly escaped (spaces = `\ `)

---

## Quick Reference

| Tool | ❌ WRONG | ✅ RIGHT |
|------|---------|---------|
| **WP-CLI** | `wp eval` | `/Users/danieliser/Local\ Sites/mcp/app/public/wp-cli-direct.sh eval` |
| **PHPCS** | `phpcs file.php` | `composer lint -- file.php` |
| **PHPCBF** | `phpcbf file.php` | `composer lint:fix -- file.php` |
| **ESLint** | `eslint src/` | `npm run lint:js` |
| **Prettier** | `prettier --write` | `npm run format` |

---

## Tool Selection Strategy

### When to Use Edit vs sd/sed

**Use Edit Tool (Claude native):**
- Single file, single replacement
- Complex string matching with exact context
- When you need to preserve exact indentation
- Small number of replacements (1-3)

```bash
# ✅ GOOD: Edit tool for precise single replacement
Edit(file.php, old_string="exact context here", new_string="replacement")
```

**Use sd (modern sed):**
- Multiple files need same replacement
- Simple pattern replacements across codebase
- Batch operations (>3 similar changes)
- Regex replacements

```bash
# ✅ GOOD: Batch operations with sd in single tool call
for file in $(rg -l 'pattern' --type php); do
  sd 'old1' 'new1' "$file"
  sd 'old2' 'new2' "$file"
  sd 'old3' 'new3' "$file"
done
```

**Never use sed:**
```bash
# ❌ WRONG: Old-school sed
sed -i '' 's/old/new/g' file.php

# ✅ RIGHT: Modern sd
sd 'old' 'new' file.php
```

### Search Tool Selection

**Use Grep Tool (Claude native):**
- Content search within files
- Pattern matching with context
- Supports ripgrep syntax
- Best for finding code locations

```bash
# ✅ GOOD: Grep tool for content search
Grep(pattern="function.*create", path="classes/", output_mode="content", -n=true)
```

**Use Glob Tool (Claude native):**
- File name pattern matching
- Finding files by extension
- Fast file discovery

```bash
# ✅ GOOD: Glob for file discovery
Glob(pattern="**/*.test.ts", path="tests/e2e/")
```

**Never use bash grep/find:**
```bash
# ❌ WRONG: Bash grep
grep -r "pattern" .

# ✅ RIGHT: Grep tool
Grep(pattern="pattern", path=".")
```

### File Reading

**Use Read Tool (Claude native):**
- Always for reading file contents
- Supports offset/limit for large files
- Can read images, PDFs, notebooks

```bash
# ✅ GOOD: Read tool
Read(file_path="/absolute/path/file.php")
```

**Never use cat/head/tail:**
```bash
# ❌ WRONG: Bash cat
cat file.php

# ✅ RIGHT: Read tool
Read(file_path="file.php")
```

### Batch Operations Priority

**For multiple similar operations, ALWAYS use single tool call with loop:**

```bash
# ✅ EXCELLENT: Single bash call with loop
for file in classes/Adapters/*/Abilities/*.php; do
  sd 'FluentCRM' 'FluentCrm' "$file"
  composer lint:fix -- "$file"
done

# ❌ POOR: Multiple separate tool calls
# (Don't call sd 100 times - batch them!)
```

### Modern CLI Tools Available

| Tool | Purpose | Use Instead Of |
|------|---------|----------------|
| `sd` | Search & replace | `sed` |
| `rg` | Content search | `grep` |
| `fd` | File finding | `find` |
| `bat` | File viewing | `cat` |
| `jq` | JSON processing | Manual parsing |

---

**REMEMBER:** These tools are project-configured. Using them directly bypasses critical configuration and will cause failures!
