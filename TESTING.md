# MCP Adapters Testing Guide

## Prerequisites

1. **Start Local Site**: Ensure the "mcp" Local by Flywheel site is running
2. **Verify FluentBoards**: FluentBoards plugin should be active
3. **Authentication**: You'll need admin credentials (default: admin/password)

## Testing MCP Server Registration

### 1. Check Available REST Routes

```bash
curl -s "http://mcp.local/wp-json/" | jq -r '.routes | keys[]' | grep -i mcp
```

**Expected Output:**
```
/fluentboards-board-crud/mcp
/fluentboards/mcp
/wp/v2/wpmcp
/wp/v2/wpmcp/streamable
```

### 2. Test Full FluentBoards MCP Server

List all available tools:

```bash
curl -s "http://mcp.local/wp-json/fluentboards/mcp" \
  -X POST \
  -H "Content-Type: application/json" \
  -u admin:password \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/list",
    "params": {}
  }' | jq '.result.tools[] | .name'
```

**Expected Output:** 84 FluentBoards abilities including:
- `fluentboards/create-board`
- `fluentboards/list-boards`
- `fluentboards/create-task`
- `fluentboards/list-tasks`
- ... and 80 more

### 3. Test Board CRUD Server

List tools from the minimal Board CRUD server:

```bash
curl -s "http://mcp.local/wp-json/fluentboards-board-crud/mcp" \
  -X POST \
  -H "Content-Type: application/json" \
  -u admin:password \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/list",
    "params": {}
  }' | jq '.result.tools[] | .name'
```

**Expected Output:** 10 board management abilities:
- `fluentboards/create-board`
- `fluentboards/list-boards`
- `fluentboards/get-board`
- `fluentboards/update-board`
- `fluentboards/delete-board`
- ... etc

### 4. Test Ability Execution

Example: List boards

```bash
curl -s "http://mcp.local/wp-json/fluentboards/mcp" \
  -X POST \
  -H "Content-Type: application/json" \
  -u admin:password \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "fluentboards/list-boards",
      "arguments": {
        "include_archived": false
      }
    }
  }' | jq '.'
```

### 5. Test Enum Abilities

Test verbose enum (returns full objects):

```bash
curl -s "http://mcp.local/wp-json/fluentboards/mcp" \
  -X POST \
  -H "Content-Type: application/json" \
  -u admin:password \
  -d '{
    "jsonrpc": "2.0",
    "id": 4,
    "method": "tools/call",
    "params": {
      "name": "fluentboards/test-verbose-enum",
      "arguments": {
        "status": "active"
      }
    }
  }' | jq '.'
```

Test pattern enum (uses JSON Schema pattern):

```bash
curl -s "http://mcp.local/wp-json/fluentboards/mcp" \
  -X POST \
  -H "Content-Type: application/json" \
  -u admin:password \
  -d '{
    "jsonrpc": "2.0",
    "id": 5,
    "method": "tools/call",
    "params": {
      "name": "fluentboards/test-pattern-enum",
      "arguments": {
        "status": "active"
      }
    }
  }' | jq '.'
```

## Troubleshooting

### "Sorry, you are not allowed to do that" (401 error)

- Check that you're using correct admin credentials
- Try creating an Application Password in WordPress admin: Users → Profile → Application Passwords

### "Tools not found" or Empty Response

1. Check if abilities are registered:
```bash
# Via WordPress CLI (if available)
wp eval "print_r(array_keys(WP_Abilities_Registry::get_instance()->get_all_abilities()));"
```

2. Check plugin activation:
```bash
# Via WordPress CLI
wp plugin list | grep -E "(mcp-adapter|mcp-adapters|abilities-api|fluent-boards)"
```

### Database Connection Errors

- Ensure Local site is running in Local by Flywheel app
- Check that MySQL service is started in Local

## Expected Results Summary

### v0.1.0 Release

✅ **84 FluentBoards Abilities** across 8 categories:
- Board Management: 11 abilities
- Task Management: 13 abilities
- Stage Management: 10 abilities
- Comments: 8 abilities
- Labels: 7 abilities
- Attachments: 5 abilities
- Users & Activities: 11 abilities
- Reporting: 9 abilities
- Test Abilities: 4 abilities (enum testing)

✅ **2 Concurrent MCP Servers**:
- Full FluentBoards Server (84 abilities)
- Board CRUD Server (10 abilities)

✅ **All abilities use namespace/ability-name convention** (lowercase, dashes)
✅ **Permission callbacks are public** for proper validation
✅ **Hook timing is correct** with ensure_abilities_registered()
✅ **No duplicate registrations** with registration guard
✅ **Clean debug logs** (all error_log removed)
✅ **WordPress coding standards** compliant
