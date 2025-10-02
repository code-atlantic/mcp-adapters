#!/usr/bin/env node
/* eslint-disable no-console */

/**
 * Release preparation script for EDD Release Manager
 *
 * Workflow:
 * 1. Calculate next version (or use provided version)
 * 2. Start git flow release branch
 * 3. Update version in plugin files
 * 4. Update changelog
 * 5. Build release
 * 6. Commit changes
 * 7. Finish git flow release
 * 8. Push changes
 *
 * Usage:
 *   node bin/prepare-release.js [version] [options]
 *
 * Options:
 *   --major      Increment major version
 *   --minor      Increment minor version
 *   --patch      Increment patch version [default]
 *   --dry-run    Show what would be done
 *   --auto       Skip confirmations
 */

const fs = require('fs');
const { execSync } = require('child_process');
const path = require('path');

// Parse arguments.
const args = process.argv.slice(2);
const flags = {
	major: args.includes('--major'),
	minor: args.includes('--minor'),
	patch: args.includes('--patch') || (!args.includes('--major') && !args.includes('--minor')),
	dryRun: args.includes('--dry-run'),
	auto: args.includes('--auto'),
};

// Version from argument.
const versionArg = args.find(arg => !arg.startsWith('--'));

// Colors.
const colors = {
	red: '\x1b[31m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	blue: '\x1b[34m',
	reset: '\x1b[0m',
	bold: '\x1b[1m',
};

function color(c, text) {
	return `${colors[c]}${text}${colors.reset}`;
}

function log(msg) {
	console.log(msg);
}

function success(msg) {
	console.log(color('green', `✅ ${msg}`));
}

function error(msg) {
	console.error(color('red', `❌ ${msg}`));
	process.exit(1);
}

function warn(msg) {
	console.log(color('yellow', `⚠️  ${msg}`));
}

function exec(cmd, options = {}) {
	if (flags.dryRun) {
		log(color('blue', `[DRY RUN] ${cmd}`));
		return '';
	}
	try {
		return execSync(cmd, { encoding: 'utf8', stdio: options.silent ? 'pipe' : 'inherit', ...options });
	} catch (e) {
		if (!options.ignoreError) {
			error(`Command failed: ${cmd}\n${e.message}`);
		}
		return '';
	}
}

// Read current version from plugin file.
function getCurrentVersion() {
	const pluginFile = path.join(__dirname, '../mcp-adapters.php');
	const content = fs.readFileSync(pluginFile, 'utf8');
	const match = content.match(/define\(\s*'MCP_ADAPTERS_VERSION',\s*'([^']+)'/);
	if (!match) {
		error('Could not find version in plugin file');
	}
	return match[1];
}

// Calculate next version.
function getNextVersion(current) {
	const parts = current.split('.').map(Number);
	if (flags.major) {
		return `${parts[0] + 1}.0.0`;
	}
	if (flags.minor) {
		return `${parts[0]}.${parts[1] + 1}.0`;
	}
	return `${parts[0]}.${parts[1]}.${parts[2] + 1}`;
}

// Main workflow.
function main() {
	log(color('bold', '🚀 EDD Release Manager - Release Preparation\n'));

	// Get current version.
	const currentVersion = getCurrentVersion();
	log(`Current version: ${color('cyan', currentVersion)}`);

	// Determine new version.
	const newVersion = versionArg || getNextVersion(currentVersion);
	log(`New version: ${color('green', newVersion)}\n`);

	// Confirm.
	if (!flags.auto && !flags.dryRun) {
		warn('This will start git flow release. Continue? (Ctrl+C to cancel)');
		exec('read -p "Press Enter to continue..."', { shell: '/bin/bash' });
	}

	// Start git flow release.
	log(color('bold', '\n📦 Starting git flow release...'));
	exec(`git flow release start ${newVersion}`);

	// Update versions using utility script.
	log(color('bold', '\n✍️  Updating version numbers...'));
	exec(`node bin/update-versions.js ${newVersion}`);

	// Update changelog using utility script.
	log(color('bold', '\n📝 Updating CHANGELOG.md...'));
	exec(`node bin/update-changelog.js ${newVersion}`);

	// Install dependencies.
	log(color('bold', '\n📦 Installing dependencies...'));
	exec('composer install --no-dev --optimize-autoloader');

	// Build release.
	log(color('bold', '\n🔨 Building release...'));
	exec('npm run release');

	// Commit changes.
	log(color('bold', '\n💾 Committing changes...'));
	exec('git add -A');
	exec(`git commit -m "Release v${newVersion}"`);

	// Finish git flow release.
	log(color('bold', '\n🏁 Finishing git flow release...'));
	exec(`git flow release finish -m "Release v${newVersion}" ${newVersion}`);

	// Push changes.
	log(color('bold', '\n⬆️  Pushing changes...'));
	exec('git push origin develop');
	exec('git push origin main');
	exec(`git push origin v${newVersion}`);

	success(`\n🎉 Release v${newVersion} complete!`);
}

// Run.
try {
	main();
} catch (e) {
	error(e.message);
}