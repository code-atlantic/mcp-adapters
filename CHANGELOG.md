# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-10-01

### Added
- **Bidirectional MCP Integration**
  - McpClient class for consuming external MCP servers
  - McpClientManager for client lifecycle management
  - Auto-registration of remote tools/resources/prompts as WordPress abilities
  - Support for Bearer, API Key, and Basic authentication
  - Permission control via `mcp_client_permission` filter

- **FluentBoards Adapter (80+ abilities)**
  - Complete board management (create, update, delete, archive, restore, duplicate)
  - Task lifecycle management (create, update, move, assign, clone)
  - Stage management with reordering and bulk operations
  - Comment and reply system with privacy controls
  - User/member management with role and permission handling
  - Attachment management (files and links)
  - Label creation and assignment
  - Analytics and reporting (dashboards, timesheets, insights)
  - MCP prompts for project overview, workflow analysis, status updates

- **MCP Servers**
  - AllAbilitiesServer - Universal server exposing all registered abilities
  - BoardCrudServer - Focused server for FluentBoards board operations
  - FullFluentBoardsServer - Complete FluentBoards capability exposure

- **Dashboard Features**
  - Real-time MCP server and client status monitoring
  - Quick test interface for enum pattern validation
  - Server tool counts and health indicators

- **Developer Features**
  - Comprehensive CLAUDE.md documentation for AI-assisted development
  - Example code for client registration (`examples/client-example.php`)
  - GitHub Updater support for auto-updates
  - Release automation with bin scripts

### Fixed
- WordPress Abilities API naming convention enforcement (namespace/ability-name)
- Permission callback visibility (must be public for is_callable())
- Ability registration validation (null vs false returns)

### Infrastructure
- GitHub Actions workflow for automated releases
- Git flow configuration (main/develop branches)
- NPM release scripts (prepare, build, version, changelog)
- Code quality tools (PHPCS, PHPStan, PHPUnit)

[Unreleased]: https://github.com/code-atlantic/mcp-adapters/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/code-atlantic/mcp-adapters/releases/tag/v0.1.0
