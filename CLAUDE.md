# WP Staging Pro - Source Code Structure

This directory contains the main source code for the WP Staging Pro WordPress plugin.

## Key Architecture Components

### Core Directories
- **`Framework/`** - Core framework and utilities (DI container, filesystem, database helpers)
- **`Backend/`** - WordPress admin interface and management functionality
- **`Frontend/`** - Public-facing functionality and user interfaces
- **`Staging/`** - Core staging/cloning functionality
- **`Backup/`** - Backup creation, management, and restoration features
- **`Pro/`** - Pro-only features (push/pull, advanced options, cloud storage)
- **`Basic/`** - Core functionality available in both free and pro versions

### Important Files
- **`wp-staging-pro.php`** - Main plugin file (Pro version)
- **`bootstrap.php`** - Plugin initialization and setup
- **`constantsPro.php`** - Pro version constants and configuration
- **`autoloader.php`** - Class autoloader for plugin classes

### Build Process
- **Source files are in `src/`** - This is the development code
- **Distribution files go to `dist/`** - Built versions with prefixed vendors
- **Always test with distribution files** - They have proper vendor prefixing

### Vendor Libraries
- **`composer.json`** - Defines PHP dependencies
- **Libraries are prefixed during build** - Prevents conflicts with other plugins
- **Use `make dev_dist`** - To create properly prefixed distribution version

### Free vs Pro Structure
- **`FREE-MAIN-PLUGIN/`** - Contains free version bootstrap files
- **`Pro/`** - Pro-only features and functionality
- **Constants control feature availability** - WPSTG_DEV_BASIC for free mode

## Development Workflow
1. Edit source files in `src/`
2. Run `make dev_dist` to build distribution versions
3. Test with distribution files in `dist/wp-staging-pro/`
4. Use Docker environment with `make up` for testing