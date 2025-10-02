# MCP Adapters

WordPress plugin providing bidirectional MCP (Model Context Protocol) integration for AI-powered WordPress automation.

## Overview

This plugin enables **bidirectional** AI integration with WordPress:

1. **Outbound (McpClient)**: Consume external MCP servers - External AI tools become WordPress abilities
2. **Inbound (MCP Servers)**: Expose WordPress as MCP server - WordPress abilities accessible via MCP protocol

Uses the modern WordPress Abilities API architecture for better performance and maintainability.

## Features

- **Bidirectional MCP Integration**: Both consume and expose MCP capabilities
- **McpClient**: Connect to external MCP servers with Bearer/API Key/Basic auth
- **FluentBoards Adapter**: Complete integration for FluentBoards project management (80+ abilities)
- **All-Abilities Server**: Universal server exposing every registered ability
- **Dashboard Widget**: Real-time status monitoring and ability testing
- **Auto-Updates**: GitHub Updater support for seamless updates
- **Extensible Design**: Easy to add adapters for other WordPress plugins

## Requirements

- WordPress 6.4+
- PHP 8.0+
- [WordPress Abilities API](https://github.com/WordPress/abilities-api)
- [MCP Adapter Plugin](https://github.com/WordPress/mcp-adapter)

## Installation

1. Install required dependencies:
   ```bash
   # Install MCP Adapter plugin
   git clone https://github.com/WordPress/mcp-adapter.git wp-content/plugins/mcp-adapter

   # Activate dependencies
   wp plugin activate mcp-adapter
   ```

2. Install this plugin:
   ```bash
   composer install --no-dev --optimize-autoloader
   wp plugin activate mcp-adapters
   ```

## FluentBoards Adapter

The FluentBoards adapter provides comprehensive project management capabilities:

### Core Abilities (81 total)
- **Boards** (10): Create, update, delete, archive, restore, duplicate, pin/unpin
- **Tasks** (13): Full task lifecycle management, assignment, movement, cloning
- **Stages** (11): Stage management, reordering, bulk task operations
- **Comments** (7): Comment and reply management with privacy controls
- **Users** (15): Member management, roles, permissions, bulk operations
- **Attachments** (6): File and link attachment management
- **Labels** (7): Label creation, assignment, and management
- **Reporting** (12): Analytics, dashboards, timesheets, and insights

### Prompts (4 total)
- **Project Overview**: Executive-level portfolio analysis
- **Analyze Workflow**: Team productivity and optimization insights
- **Status Checkin**: Progress updates and milestone tracking
- **Team Productivity**: Performance metrics and recommendations

## Architecture

```
MCP\Adapters\
├── Plugin.php                    # Main plugin coordinator
├── Adapters\
│   └── FluentBoards\
│       ├── FluentBoardsAdapter.php # Adapter orchestrator
│       ├── BaseAbility.php        # Shared functionality
│       ├── Abilities\              # Core abilities (8 files)
│       │   ├── Boards.php
│       │   ├── Tasks.php
│       │   ├── Stages.php
│       │   ├── Comments.php
│       │   ├── Users.php
│       │   ├── Attachments.php
│       │   ├── Labels.php
│       │   └── Reporting.php
│       └── Prompts\               # Analysis prompts (4 files)
│           ├── ProjectOverview.php
│           ├── AnalyzeWorkflow.php
│           ├── StatusCheckin.php
│           └── TeamProductivity.php
```

## Development

### Code Standards
- PHP 8.0+ with strict typing
- WordPress Coding Standards
- PSR-4 autoloading
- Comprehensive error handling

### Testing
```bash
# PHP syntax check
composer lint:php

# Run tests (when available)
composer test
```

### Adding New Adapters

1. Create adapter directory: `classes/Adapters/PluginName/`
2. Implement abilities using `wp_register_ability()`
3. Add adapter to `Plugin::initialize_adapters()`
4. Update autoloader and test

## License

GPL-2.0-or-later

## Contributing

1. Fork the repository
2. Create feature branch
3. Follow coding standards
4. Add tests for new functionality
5. Submit pull request

## Changelog

### 1.0.0
- Initial release
- Complete FluentBoards adapter implementation
- 81 abilities + 4 prompts
- Modern WordPress Abilities API architecture
- 30% performance improvement over legacy RegisterMcpTool pattern