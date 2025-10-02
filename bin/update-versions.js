#!/usr/bin/env node
/**
 * Update version numbers across plugin files
 *
 * Usage:
 *   node bin/update-versions.js <version> [options]
 *
 * Parameters:
 *   <version>   The version number to set (e.g., 1.0.5)
 *   --dry-run   Show what would change without modifying files
 *   --plugin    Only update plugin metadata files
 *   --docblock  Only update @since X.X.X docblocks
 *   --comment   Only update X.X.X in comments
 *   --all       Update all files (default)
 */

const fs = require('fs');
const path = require('path');
const glob = require('glob');

// Parse arguments.
const args = process.argv.slice(2);
const version = args.find(arg => !arg.startsWith('--'));
const flags = {
	dryRun: args.includes('--dry-run'),
	plugin: args.includes('--plugin'),
	docblock: args.includes('--docblock'),
	comment: args.includes('--comment'),
	all: args.includes('--all') || (!args.includes('--plugin') && !args.includes('--docblock') && !args.includes('--comment')),
};

if (!version) {
	console.error('❌ Please provide a version number');
	console.log('Usage: node bin/update-versions.js <version> [options]');
	process.exit(1);
}

// Validate version format.
if (!/^\d+\.\d+\.\d+$/.test(version)) {
	console.error(`❌ Invalid version format: ${version}`);
	console.log('Expected format: X.Y.Z (e.g., 1.0.5)');
	process.exit(1);
}

const excludedDirs = [
	'node_modules/**',
	'vendor/**',
	'vendor-prefixed/**',
	'bin/**',
	'build/**',
];

// Version patterns for plugin metadata files.
const versionPatterns = [
	{
		name: 'Plugin header Version',
		regex: /^([\t ]*\*[\t ]*Version:[\t ]*)(.*)/gm,
		replacement: (newVersion) => `$1${newVersion}`,
	},
	{
		name: 'Plugin constant',
		regex: /(define\(\s*'EDD_RELEASE_MANAGER_VERSION',\s*')[\d.]+(')/g,
		replacement: (newVersion) => `$1${newVersion}$2`,
	},
	{
		name: 'README stable tag',
		regex: /^(Stable tag:[\t ]*)(.*)/gm,
		replacement: (newVersion) => `$1${newVersion}`,
	},
	{
		name: 'package.json version',
		regex: /(\s*"version":\s*")(\d+\.\d+\.\d+)(")/gm,
		replacement: (newVersion) => `$1${newVersion}$3`,
	},
	{
		name: 'composer.json version',
		regex: /(\s*"version":\s*")(\d+\.\d+\.\d+)(")/gm,
		replacement: (newVersion) => `$1${newVersion}$3`,
	},
];

// Docblock patterns for @since/@deprecated/@version.
const docblockPatterns = [
	{
		name: 'Docblock @since/@deprecated/@version X.X.X',
		regex: /((@deprecated|@since|@version)\s+)X\.X\.X/gm,
		replacement: (newVersion) => (_match, tag) => `${tag}${newVersion}`,
	},
];

// Comment patterns for inline X.X.X references.
const commentPatterns = [
	{
		name: 'Single-line comment X.X.X',
		regex: /(\/\/.*\s+)X\.X\.X/gm,
		replacement: (newVersion) => (_match, prefix) => `${prefix}${newVersion}`,
	},
	{
		name: 'Block comment X.X.X',
		regex: /(\/\*.*\s+)X\.X\.X/gm,
		replacement: (newVersion) => (_match, prefix) => `${prefix}${newVersion}`,
	},
	{
		name: 'Docblock line X.X.X',
		regex: /(\s+\*.*\s+)X\.X\.X/gm,
		replacement: (newVersion) => (_match, prefix) => `${prefix}${newVersion}`,
	},
];

/**
 * Update version in a file with given patterns.
 *
 * @param {string} filePath Path to file.
 * @param {string} newVersion New version number.
 * @param {boolean} dryRun Dry run flag.
 * @param {Array} patterns Replacement patterns.
 * @return {number} Number of replacements made.
 */
function updateVersionInFile(filePath, newVersion, dryRun, patterns) {
	if (!fs.existsSync(filePath)) {
		return 0;
	}

	const contents = fs.readFileSync(filePath, 'utf8');
	let newContents = contents;
	let replaceCount = 0;

	patterns.forEach((pattern) => {
		const before = newContents;
		newContents = newContents.replace(
			pattern.regex,
			pattern.replacement(newVersion)
		);
		if (newContents !== before) {
			replaceCount++;
		}
	});

	if (newContents !== contents) {
		if (dryRun) {
			console.log(`📝 Would update: ${filePath} (${replaceCount} replacement(s))`);
		} else {
			fs.writeFileSync(filePath, newContents, 'utf8');
			console.log(`✅ Updated: ${filePath} (${replaceCount} replacement(s))`);
		}
		return replaceCount;
	}

	return 0;
}

let totalUpdates = 0;

// Update plugin metadata files.
if (flags.all || flags.plugin) {
	console.log('\n📦 Updating plugin metadata files...\n');

	const pluginFile = path.join(process.cwd(), 'edd-release-manager.php');
	const readmeFile = path.join(process.cwd(), 'readme.txt');
	const packageJsonFile = path.join(process.cwd(), 'package.json');
	const composerJsonFile = path.join(process.cwd(), 'composer.json');

	totalUpdates += updateVersionInFile(pluginFile, version, flags.dryRun, versionPatterns);
	totalUpdates += updateVersionInFile(readmeFile, version, flags.dryRun, versionPatterns);
	totalUpdates += updateVersionInFile(packageJsonFile, version, flags.dryRun, versionPatterns);
	totalUpdates += updateVersionInFile(composerJsonFile, version, flags.dryRun, versionPatterns);
}

// Update docblocks in PHP files.
if (flags.all || flags.docblock) {
	console.log('\n📄 Updating docblocks in PHP files...\n');

	const phpFiles = glob.sync('**/*.php', { ignore: excludedDirs });
	phpFiles.forEach((file) => {
		totalUpdates += updateVersionInFile(file, version, flags.dryRun, docblockPatterns);
	});
}

// Update comments in PHP files.
if (flags.all || flags.comment) {
	console.log('\n💬 Updating comments in PHP files...\n');

	const phpFiles = glob.sync('**/*.php', { ignore: excludedDirs });
	phpFiles.forEach((file) => {
		totalUpdates += updateVersionInFile(file, version, flags.dryRun, commentPatterns);
	});
}

// Summary.
console.log('\n' + '='.repeat(50));
if (flags.dryRun) {
	console.log(`🔍 DRY RUN: Would update ${totalUpdates} location(s)`);
	console.log('Run without --dry-run to apply changes');
} else {
	console.log(`✅ Successfully updated ${totalUpdates} location(s) to version ${version}`);
}
console.log('='.repeat(50) + '\n');