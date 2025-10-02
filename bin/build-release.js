#!/usr/bin/env node
/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );
const glob = require( 'glob' );
const { execSync } = require( 'child_process' );

/**
 * EDD Release Manager Plugin Builder
 *
 * This script handles the complete release process for the EDD Release Manager plugin:
 * 1. Cleans previous build artifacts
 * 2. Installs production dependencies (Composer)
 * 3. Copies distribution files based on package.json files array
 * 4. Creates versioned zip file
 * 5. Cleans up temporary files
 *
 * Usage:
 *   npm run release
 *   npm run release:test    (keep build directory, verbose output)
 *   npm run release:quick   (skip npm, minimal output)
 *
 * Options:
 *   --project-root <path>  Project root directory (default: current working directory)
 *   --plugin-name <name>   Plugin name override (default: from package.json name)
 *   --zip-name <name>      Zip file name override (default: {plugin-name}_{version}.zip)
 *   --output-dir <path>    Output directory for zip file (default: project root)
 *   --keep-build          Keep build directory after creating zip
 *   --skip-composer       Skip composer install step
 *   --skip-npm            Skip npm build step (no-op for this plugin)
 *   --quiet               Minimal output (progress only)
 *   --verbose             Show detailed output from build commands
 *   --help                Show this help message
 */

class EddReleaseManagerBuilder {
	constructor( options = {} ) {
		this.options = {
			projectRoot: null,
			pluginName: null,
			zipFileName: null,
			outputDir: null,
			keepBuild: false,
			skipComposer: false,
			skipNpm: false,
			quiet: false,
			verbose: false,
			...options,
		};

		this.setupPaths();
		this.loadPackageJSON();
	}

	setupPaths() {
		// Default project root to current working directory (where npm script was called from)
		this.projectRoot = this.options.projectRoot || process.cwd();

		this.buildPath = path.join( this.projectRoot, 'build' );
		this.outputDir = this.options.outputDir || this.projectRoot;

		if ( this.options.verbose ) {
			console.log( `Project root: ${ this.projectRoot }` );
		}
	}

	loadPackageJSON() {
		const packagePath = path.join( this.projectRoot, 'package.json' );

		if ( ! fs.existsSync( packagePath ) ) {
			throw new Error( `package.json not found at ${ packagePath }` );
		}

		this.packageJSON = require( packagePath );
		this.pluginName = this.options.pluginName || this.packageJSON.name;
		this.version = this.packageJSON.version;

		if ( this.options.verbose ) {
			console.log(
				`Building release for: ${ this.pluginName } v${ this.version }`
			);
		}
	}

	removeDirectory( directoryPath ) {
		if ( fs.existsSync( directoryPath ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing directory: ${ path.relative(
						this.projectRoot,
						directoryPath
					) }`
				);
			}

			if ( fs.rmSync ) {
				// Use rmSync if available (Node 14.14+)
				fs.rmSync( directoryPath, { recursive: true, force: true } );
			} else {
				// Use rmdirSync for backward compatibility
				fs.rmdirSync( directoryPath, { recursive: true } );
			}
		}
	}

	executeCommand( command, description ) {
		if ( this.options.verbose ) {
			console.log( `${ description }...` );
			console.log( `Running: ${ command }` );
		} else if ( this.options.quiet ) {
			// Show minimal progress
			process.stdout.write( `${ description }... ` );
		} else {
			// Default mode: show description but not command
			console.log( `${ description }...` );
		}

		try {
			const result = execSync( command, {
				cwd: this.projectRoot,
				stdio: this.options.verbose ? 'inherit' : 'pipe',
				encoding: 'utf8',
				env: { ...process.env }, // Clean environment without our script's arguments
			} );

			if ( this.options.quiet ) {
				console.log( '✅' );
			} else if ( ! this.options.verbose ) {
				console.log( '✅ Done' );
			}

			return result;
		} catch ( error ) {
			if ( this.options.quiet || ! this.options.verbose ) {
				console.log( '❌' );
			}

			console.error( `\n❌ Failed to execute: ${ command }` );

			// Always show error output, even in quiet mode
			if ( error.stdout ) {
				console.error( 'STDOUT:' );
				console.error( error.stdout.toString() );
			}
			if ( error.stderr ) {
				console.error( 'STDERR:' );
				console.error( error.stderr.toString() );
			}

			console.error( `Exit code: ${ error.status }` );
			process.exit( 1 );
		}
	}

	cleanBuildArtifacts() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Cleaning build artifacts ===' );
		}

		// Clean build directory
		this.removeDirectory( this.buildPath );

		// Clean any existing plugin directory in root
		const pluginDir = path.join( this.projectRoot, this.pluginName );
		this.removeDirectory( pluginDir );

		// Clean any existing zip files for this version
		const existingZip = path.join(
			this.outputDir,
			`${ this.pluginName }_${ this.version }.zip`
		);

		if ( fs.existsSync( existingZip ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing existing zip: ${ path.relative(
						this.projectRoot,
						existingZip
					) }`
				);
			}
			fs.unlinkSync( existingZip );
		}

		// Clean any existing zip files for -latest.zip
		const latestZip = path.join(
			this.outputDir,
			`${ this.pluginName }-latest.zip`
		);

		if ( fs.existsSync( latestZip ) ) {
			if ( this.options.verbose ) {
				console.log(
					`Removing existing zip: ${ path.relative(
						this.projectRoot,
						latestZip
					) }`
				);
			}
			fs.unlinkSync( latestZip );
		}
	}

	runComposerInstall() {
		if ( this.options.skipComposer ) {
			if ( ! this.options.quiet ) {
				console.log( '\nSkipping Composer install as requested' );
			}
			return;
		}

		if ( ! this.options.quiet ) {
			console.log( '\n=== Installing production dependencies ===' );
		}

		// Check if composer.json exists
		const composerPath = path.join( this.projectRoot, 'composer.json' );
		if ( ! fs.existsSync( composerPath ) ) {
			if ( ! this.options.quiet ) {
				console.log( 'No composer.json found, skipping Composer install' );
			}
			return;
		}

		this.executeCommand(
			'composer install -o --no-dev --classmap-authoritative',
			'Installing Composer dependencies'
		);
	}

	copyDistributionFiles() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Copying distribution files ===' );
		}

		// Create build directory
		if ( ! fs.existsSync( this.buildPath ) ) {
			fs.mkdirSync( this.buildPath, { recursive: true } );
		}

		// Get files array from package.json, or use default patterns
		const filePatterns = this.packageJSON.files || [
			'*.php',
			'admin/**/*',
			'assets/**/*',
			'classes/**/*',
			'includes/**/*',
			'languages/**/*',
			'vendor/autoload.php',
			'vendor/composer/*.php',
			'vendor/composer/*.json',
			'readme.txt',
			'LICENSE',
		];

		if ( this.options.verbose ) {
			console.log( `Using file patterns:`, filePatterns );
		}

		let fileCount = 0;

		// Process each pattern
		filePatterns.forEach( ( pattern ) => {
			const files = glob.sync( path.join( this.projectRoot, pattern ) );

			files.forEach( ( file ) => {
				const relativePath = path.relative( this.projectRoot, file );

				// Skip if it's the build directory itself, node_modules, or dev files
				if (
					relativePath.startsWith( 'build' ) ||
					relativePath.startsWith( 'node_modules' ) ||
					relativePath.startsWith( '.git' ) ||
					relativePath.startsWith( 'bin' ) ||
					relativePath === 'package.json' ||
					relativePath === 'package-lock.json' ||
					relativePath === 'composer.lock' ||
					relativePath.includes( '.phpcs.xml' )
				) {
					return;
				}

				const dest = path.join( this.buildPath, relativePath );
				// Ensure destination directory exists
				const destDir = path.dirname( dest );
				if ( ! fs.existsSync( destDir ) ) {
					fs.mkdirSync( destDir, { recursive: true } );
				}

				// Copy file or directory
				if ( fs.lstatSync( file ).isDirectory() ) {
					if ( ! fs.existsSync( dest ) ) {
						fs.mkdirSync( dest, { recursive: true } );
					}
				} else {
					fs.copyFileSync( file, dest );
					fileCount++;
				}
			} );
		} );

		if ( this.options.verbose ) {
			console.log(
				`Files copied to: ${ path.relative(
					this.projectRoot,
					this.buildPath
				) }`
			);
		} else if ( ! this.options.quiet ) {
			console.log( `✅ Copied ${ fileCount } files` );
		}
	}

	createZipFiles() {
		if ( ! this.options.quiet ) {
			console.log( '\n=== Creating release zip ===' );
		}

		const pluginDir = path.join( this.projectRoot, this.pluginName );
		const zipName =
			this.options.zipFileName ||
			`${ this.pluginName }_${ this.version }.zip`;
		const zipPath = path.join( this.outputDir, zipName );

		// Move build directory to plugin name
		if ( fs.existsSync( pluginDir ) ) {
			this.removeDirectory( pluginDir );
		}

		fs.renameSync( this.buildPath, pluginDir );

		// Create latest zip file
		this.executeCommand(
			`zip -r "${ this.pluginName }-latest.zip" "${ this.pluginName }"`,
			`Creating latest zip file`
		);

		// Copy (cp) to versioned zip file
		this.executeCommand(
			`cp "${ this.pluginName }-latest.zip" "${ zipName }"`,
			`Creating versioned zip file`
		);

		// Move zip to output directory if different from project root
		if ( this.outputDir !== this.projectRoot ) {
			const sourceZip = path.join( this.projectRoot, zipName );
			if ( fs.existsSync( sourceZip ) ) {
				fs.renameSync( sourceZip, zipPath );
			}
		}

		// Always show the final result
		console.log(
			`\n✅ Release created: \n- ${ path.relative(
				process.cwd(),
				`${ this.pluginName }-latest.zip`
			) } \n- ${ path.relative( process.cwd(), zipPath ) }`
		);

		return zipPath;
	}

	cleanup() {
		if ( this.options.keepBuild ) {
			if ( ! this.options.quiet ) {
				console.log( '\n=== Keeping build directory as requested ===' );
			}
			return;
		}

		if ( ! this.options.quiet ) {
			console.log( '\n=== Cleaning up ===' );
		}

		const pluginDir = path.join( this.projectRoot, this.pluginName );
		this.removeDirectory( pluginDir );
		this.removeDirectory( this.buildPath );
	}

	async build() {
		console.log(
			`🚀 Building ${ this.pluginName } v${ this.version }${
				! this.options.quiet ? '\n' : ''
			}`
		);

		try {
			this.cleanBuildArtifacts();
			this.runComposerInstall();
			this.copyDistributionFiles();
			// eslint-disable-next-line no-unused-vars
			const _zipPath = this.createZipFiles();
			this.cleanup();

			if ( ! this.options.quiet ) {
				console.log( `\n✅ Release build completed successfully!` );
			}
		} catch ( error ) {
			console.error( `\n❌ Release build failed:`, error.message );
			process.exit( 1 );
		}
	}
}

// CLI argument parsing
function parseArgs() {
	const args = process.argv.slice( 2 );
	const options = {};

	for ( let i = 0; i < args.length; i++ ) {
		const arg = args[ i ];

		switch ( arg ) {
			case '--help':
				showHelp();
				process.exit( 0 );
				break;

			case '--project-root':
				options.projectRoot = args[ ++i ];
				break;

			case '--plugin-name':
				options.pluginName = args[ ++i ];
				break;

			case '--zip-name':
				options.zipFileName = args[ ++i ];
				break;

			case '--output-dir':
				options.outputDir = args[ ++i ];
				break;

			case '--keep-build':
				options.keepBuild = true;
				break;

			case '--skip-composer':
				options.skipComposer = true;
				break;

			case '--skip-npm':
				options.skipNpm = true;
				break;

			case '--quiet':
				options.quiet = true;
				break;

			case '--verbose':
				options.verbose = true;
				break;

			default:
				if ( arg.startsWith( '--' ) ) {
					console.error( `Unknown option: ${ arg }` );
					process.exit( 1 );
				}
		}
	}

	return options;
}

function showHelp() {
	console.log( `
EDD Release Manager Plugin Builder

Usage: npm run release [-- options]

Available Scripts:
  npm run release        Full production build
  npm run release:test   Build with verbose output and keep build directory
  npm run release:quick  Quick build (skip npm step, minimal output)

Options:
  --project-root <path>   Project root directory (default: current working directory)
  --plugin-name <name>    Plugin name override (default: from package.json name)
  --zip-name <name>       Zip file name override (default: {plugin-name}_{version}.zip)
  --output-dir <path>     Output directory for zip file (default: project root)
  --keep-build           Keep build directory after creating zip
  --skip-composer        Skip composer install step
  --skip-npm             Skip npm build step (no-op for this plugin)
  --quiet                Minimal output (progress only)
  --verbose              Show detailed output from build commands
  --help                 Show this help message

Examples:
  npm run release
  npm run release:test
  npm run release -- --verbose --keep-build
  npm run release -- --output-dir ./releases
` );
}

// Main execution
if ( require.main === module ) {
	const options = parseArgs();
	const builder = new EddReleaseManagerBuilder( options );
	builder.build().catch( ( error ) => {
		console.error( `\n❌ Release build failed:`, error.message );
		process.exit( 1 );
	} );
}

module.exports = EddReleaseManagerBuilder;
/* eslint-enable no-console */