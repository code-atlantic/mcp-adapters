#!/usr/bin/env node
/**
 * Update CHANGELOG.md and readme.txt with release version
 *
 * This script:
 * 1. Reads unreleased changes from CHANGELOG.md
 * 2. Creates a new version section with today's date
 * 3. Updates readme.txt changelog section
 *
 * Usage:
 *   node bin/update-changelog.js <version> [options]
 *
 * Parameters:
 *   <version>   The version number (e.g., 1.0.5)
 *   --verbose   Show extracted unreleased changes
 *   -v          Short for --verbose
 */

const fs = require('fs');
const path = require('path');

// Parse arguments.
const args = process.argv.slice(2);
const version = args.find(arg => !arg.startsWith('--') && !arg.startsWith('-'));
const isVerbose = args.includes('--verbose') || args.includes('-v');

if (!version) {
	console.error('❌ Please provide a version number');
	console.log('Usage: node bin/update-changelog.js <version> [options]');
	process.exit(1);
}

// Validate version format.
if (!/^\d+\.\d+\.\d+$/.test(version)) {
	console.error(`❌ Invalid version format: ${version}`);
	console.log('Expected format: X.Y.Z (e.g., 1.0.5)');
	process.exit(1);
}

const releaseDate = new Date().toISOString().split('T')[0]; // YYYY-MM-DD.
const changelogPath = path.join(process.cwd(), 'CHANGELOG.md');
const readmePath = path.join(process.cwd(), 'readme.txt');

// Check if CHANGELOG.md exists.
if (!fs.existsSync(changelogPath)) {
	console.error('❌ CHANGELOG.md not found');
	console.log('Create CHANGELOG.md with an ## Unreleased section');
	process.exit(1);
}

// Read CHANGELOG.md.
let changelogContent = fs.readFileSync(changelogPath, 'utf8');

// Extract unreleased changes.
const unreleasedStart = changelogContent.indexOf('## Unreleased');
const nextVersionStart = changelogContent.indexOf('## v', unreleasedStart + 1);

if (unreleasedStart === -1) {
	console.error('❌ No "## Unreleased" section found in CHANGELOG.md');
	process.exit(1);
}

if (nextVersionStart === -1) {
	console.error('❌ No version section found after "## Unreleased" in CHANGELOG.md');
	console.log('Add at least one version section (e.g., ## v1.0.0 - 2024-01-01)');
	process.exit(1);
}

// Extract content between "## Unreleased\n\n" and next "## v" section.
const unreleasedContentStart = unreleasedStart + '## Unreleased\n\n'.length;
const unreleasedChangesText = changelogContent
	.substring(unreleasedContentStart, nextVersionStart)
	.trim();

if (!unreleasedChangesText) {
	console.error('❌ No unreleased changes found in CHANGELOG.md');
	console.log('Add changes under the ## Unreleased section');
	process.exit(1);
}

// Count meaningful lines.
const unreleasedLines = unreleasedChangesText
	.split('\n')
	.filter((line) => {
		const trimmed = line.trim();
		return trimmed !== '' && !/^\*\*.*\*\*$/.test(trimmed);
	});
const changeCount = unreleasedLines.length;

if (isVerbose) {
	console.log('\n📋 Unreleased Changes:\n');
	console.log(unreleasedChangesText);
	console.log('\n' + '='.repeat(50) + '\n');
}

// Update CHANGELOG.md.
const beforeUnreleased = changelogContent.substring(0, unreleasedStart);
const afterUnreleased = changelogContent.substring(nextVersionStart);
const updatedChangelog =
	beforeUnreleased +
	`## Unreleased\n\n## v${version} - ${releaseDate}\n\n${unreleasedChangesText}\n\n` +
	afterUnreleased;

fs.writeFileSync(changelogPath, updatedChangelog, 'utf8');
console.log(`✅ Updated CHANGELOG.md with v${version}`);

// Update readme.txt if it exists.
if (fs.existsSync(readmePath)) {
	const readmeContent = fs.readFileSync(readmePath, 'utf8');

	// Find the changelog section and first version entry.
	const changelogPattern =
		/== Changelog ==\n\n.*?\n\n(= v?\d+\.\d+\.\d+[^\n]*=)/s;
	const changelogMatch = readmeContent.match(changelogPattern);

	if (!changelogMatch) {
		console.warn('⚠️  Could not find changelog section in readme.txt');
		console.log('   Skipping readme.txt update');
	} else {
		// Detect if existing entries use "v" prefix.
		const firstVersionEntry = changelogMatch[1];
		const usesVPrefix = firstVersionEntry.includes('= v');
		const versionPrefix = usesVPrefix ? 'v' : '';

		// Create new version entry.
		const newVersionEntry = `= ${versionPrefix}${version} =\n\n${unreleasedChangesText}\n\n`;

		// Insert new version entry before first existing version.
		const newChangelog = readmeContent.replace(
			changelogPattern,
			(match, firstVersion) => {
				return match.replace(
					firstVersion,
					`${newVersionEntry}${firstVersion}`
				);
			}
		);

		fs.writeFileSync(readmePath, newChangelog.trim() + '\n', 'utf8');
		console.log(`✅ Updated readme.txt with v${version}`);
	}
}

// Summary.
console.log('\n' + '='.repeat(50));
console.log(`✅ Changelog updated successfully`);
console.log(`   Version: ${version}`);
console.log(`   Date: ${releaseDate}`);
console.log(`   Changes: ${changeCount} entry/entries`);
console.log('='.repeat(50) + '\n');