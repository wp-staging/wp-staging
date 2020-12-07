<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\Command\Database\Snapshot\SnapshotHandler;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Directories;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\Iterators;

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends Job {

    /** @var array */
    private $directories = [];

    /** @var Directories */
    private $objDirectories;

    /**
     * Upon class initialization
     */
    protected function initialize() {
        $this->objDirectories = new Directories();

        $this->getTables();

        $this->getDirectories();

        $this->installOptimizer();
    }

    /**
     * Start Module
     * @return $this|object
     * @throws \Exception
     */
    public function start() {
        // Basic Options
        $this->options->root           = str_replace( ["\\", '/'], DIRECTORY_SEPARATOR, WPStaging::getWPpath() );
        $this->options->existingClones = get_option( "wpstg_existing_clones_beta", [] );
        $this->options->current        = null;

        if( isset( $_POST["clone"] ) && array_key_exists( $_POST["clone"], $this->options->existingClones ) ) {
            $this->options->current = $_POST["clone"];
        }

        // Tables
        $this->options->clonedTables = [];

        // Files
        $this->options->totalFiles    = 0;
        $this->options->totalFileSize = 0;
        $this->options->copiedFiles   = 0;


        // Directories
        $this->options->includedDirectories      = [];
        $this->options->includedExtraDirectories = [];
        $this->options->excludedDirectories      = [];
        $this->options->extraDirectories         = [];
        $this->options->directoriesToCopy        = [];
        $this->options->scannedDirectories       = [];

        // Job
        $this->options->currentJob  = "PreserveDataFirstStep";
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = 'cloning';

        // Delete previous cached files
        $this->cache->delete( "files_to_copy" );
        $this->cache->delete( "clone_options" );

        $this->saveOptions();

        return $this;
    }

    /**
     * Make sure the Optimizer mu plugin is installed before cloning or pushing
     */
    private function installOptimizer() {
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();
    }

    /**
     * Format bytes into human readable form
     * @param float $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize( $bytes, $precision = 2 ) {
        if( ( double ) $bytes < 1 ) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $bytes = ( double ) $bytes;
        $base  = log( $bytes ) / log( 1000 ); // 1024 would be for MiB KiB etc
        $pow   = pow( 1000, $base - floor( $base ) ); // Same rule for 1000

        return round( $pow, $precision ) . ' ' . $units[( int ) floor( $base )];
    }

    /**
     * @param null|array $directories
     * @param bool $forceDisabled
     * @return string
     */
    public function directoryListing( $directories = null, $forceDisabled = false ) {
        if( $directories == null ) {
            $directories = $this->directories;
        }

        // Sort results
        uksort( $directories, 'strcasecmp' );

        $output = '';
        foreach ( $directories as $name => $directory ) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if( !is_array( $directory ) ) {
                continue;
            }

            // Need to preserve keys so no array_shift()
            $data = reset( $directory );
            unset( $directory[key( $directory )] );


            $isChecked = (
                    empty( $this->options->includedDirectories ) ||
                    in_array( $data["path"], $this->options->includedDirectories )
                    );

            $dataPath = isset( $data["path"] ) ? $data["path"] : '';
            $dataSize = isset( $data["size"] ) ? $data["size"] : '';


            // Select all wp core folders and their sub dirs.
            // Unselect all other folders (default setting)
            $isDisabled = ($name !== 'wp-admin' &&
                    $name !== 'wp-includes' &&
                    $name !== 'wp-content' &&
                    $name !== 'sites') &&
                    strpos( strrev( wpstg_replace_windows_directory_separator( $dataPath ) ), strrev( wpstg_replace_windows_directory_separator( ABSPATH . "wp-admin" ) ) ) === false &&
                    strpos( strrev( wpstg_replace_windows_directory_separator( $dataPath ) ), strrev( wpstg_replace_windows_directory_separator( ABSPATH . "wp-includes" ) ) ) === false &&
                    strpos( strrev( wpstg_replace_windows_directory_separator( $dataPath ) ), strrev( wpstg_replace_windows_directory_separator( ABSPATH . "wp-content" ) ) ) === false ? true : false;

            // Extra class to differentiate between wp core and non core folders
            $class = !$isDisabled ? 'wpstg-root' : 'wpstg-extra';

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir " . $class . "'";

            if( $isChecked && !$isDisabled && !$forceDisabled )
                $output .= " checked";

            $output .= " name='selectedDirectories[]' value='{$dataPath}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs ";
            if( !$isChecked || $isDisabled ) {
                $output .= " disabled";
            }
            $output .= "'>{$name}";
            $output .= "</a>";
            $output .= "<span class='wpstg-size-info'>{$this->formatSize( $dataSize )}</span>";
            $output .= isset( $this->settings->debugMode ) ? "<span class='wpstg-size-info'> {$dataPath}</span>" : "";

            if( !empty( $directory ) ) {
                $output .= "<div class='wpstg-dir wpstg-subdir'>";
                $output .= $this->directoryListing( $directory, $isDisabled );
                $output .= "</div>";
            }

            $output .= "</div>";
        }

        return $output;
    }

    /**
     * Checks if there is enough free disk space to create staging site
     * Returns null when can't run disk_free_space function one way or another
     * @return bool|null
     */
    public function hasFreeDiskSpace() {
        if( !function_exists( "disk_free_space" ) ) {
            return null;
        }


        $data = [
            'usedspace' => $this->formatSize( $this->getDirectorySizeInclSubdirs( WPStaging::getWPpath() ) )
        ];

        echo json_encode( $data );
        die();
    }

    /**
     * Get Database Tables
     */
    protected function getTables() {
        $db = WPStaging::getInstance()->get( "wpdb" );

        $sql = "SHOW TABLE STATUS";

        $tables = $db->get_results( $sql );

        $currentTables = [];

        // Reset excluded Tables than loop through all tables
        $this->options->excludedTables = [];
        foreach ( $tables as $table ) {

            // Create array of unchecked tables
            // On the main website of a multisite installation, do not select network site tables beginning with wp_1_, wp_2_ etc.
            // (On network sites, the correct tables are selected anyway)
            if (( ! empty($db->prefix) && strpos($table->Name, $db->prefix) !== 0)
                || (is_multisite() && is_main_site() && preg_match('/^'.$db->prefix.'\d+_/', $table->Name))) {
                $this->options->excludedTables[] = $table->Name;
            }

            if ((strpos($table->Name, SnapshotHandler::PREFIX_MANUAL) !== 0)
                && (strpos($table->Name, SnapshotHandler::PREFIX_AUTOMATIC) !== 0)
                && ($table->Comment !== "VIEW")) {
                $currentTables[] = [
                    "name" => $table->Name,
                    "size" => ($table->Data_length + $table->Index_length)
                ];
            }
        }

        $this->options->tables = json_decode( json_encode( $currentTables ) );
    }


    /**
     * Get directories and main meta data about'em recursively
     */
    protected function getDirectories() {

        $directories = new Iterators\RecursiveDirectoryIterator( WPStaging::getWPpath() );

        foreach ( $directories as $directory ) {
            // Not a valid directory
            if( ($path = $this->getPath( $directory )) === false ) {
                continue;
            }

            $this->handleDirectory( $path );

            // Get Sub-directories
            $this->getSubDirectories( $directory->getRealPath() );
        }

        // Gather Plugins
        $this->getSubDirectories( WP_PLUGIN_DIR );

        // Gather Themes
        $this->getSubDirectories( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . "themes" );

        // Gather Custom Uploads Folder if there is one
        $this->getSubDirectories( $this->getUploadDir() );
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function getSubDirectories( $path ) {

        if( !is_readable( $path ) ) {
            return false;
        }

        if( !is_dir( $path ) ) {
            return false;
        }

        // IMPORTANT: If this is not used and a folder belongs to another user
        // DirectoryIterator() will throw a fatal error which can not be catched with is_readable()
        if( !opendir( $path ) ) {
            return false;
        }

        $directories = new \DirectoryIterator( $path );

        foreach ( $directories as $directory ) {
            // Not a valid directory
            if( ($path = $this->getPath( $directory )) === false ) {
                continue;
            }

            $this->handleDirectory( $path );
        }
        return false;
    }

    /**
     * Get Path from $directory
     * @param string
     * @return bool|string
     */
    protected function getPath( $directory ) {

        /*
         * Do not follow root path like src/web/..
         * This must be done before \SplFileInfo->isDir() is used!
         * Prevents open base dir restriction fatal errors
         */

        if( strpos( $directory->getRealPath(), WPStaging::getWPpath() ) !== 0 ) {
            return false;
        }
        $path = str_replace( WPStaging::getWPpath(), null, $directory->getRealPath() );

        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if( !$directory->isDir() || strlen( $path ) < 1 ) {
            return false;
        }

        return $path;
    }

    /**
     * Organizes $this->directories
     * @param string $path
     */
    protected function handleDirectory( $path ) {
        $directoryArray = explode( DIRECTORY_SEPARATOR, $path );
        $total          = is_array( $directoryArray ) || $directoryArray instanceof \Countable ? count( $directoryArray ) : 0;

        if( $total < 1 ) {
            return;
        }

        $total        = $total - 1;
        $currentArray = &$this->directories;

        for ( $i = 0; $i <= $total; $i++ ) {
            if( !isset( $currentArray[$directoryArray[$i]] ) ) {
                $currentArray[$directoryArray[$i]] = [];
            }

            $currentArray = &$currentArray[$directoryArray[$i]];

            // Attach meta data to the end
            if( $i < $total ) {
                continue;
            }

            $fullPath = WPStaging::getWPpath() . $path;
            $size     = $this->getDirectorySize( $fullPath );

            $currentArray["metaData"] = [
                "size" => $size,
                "path" => WPStaging::getWPpath() . $path,
            ];
        }
    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    protected function getDirectorySize( $path ) {
        if( !isset( $this->settings->checkDirectorySize ) || $this->settings->checkDirectorySize !== '1' ) {
            return null;
        }

        return $this->objDirectories->size( $path );
    }

    /**
     * Get total size of a directory including all its subdirectories
     * @param string $dir
     * @return int
     */
    protected function getDirectorySizeInclSubdirs( $dir ) {
        $size = 0;
        foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $each ) {
            $size += is_file( $each ) ? filesize( $each ) : $this->getDirectorySizeInclSubdirs( $each );
        }
        return $size;
    }


    /**
     * Get absolute WP uploads path e.g.
     * Multisites: /var/www/htdocs/example.com/wp-content/uploads/sites/1 or /var/www/htdocs/example.com/wp-content/blogs.dir/1/files
     * Single sites: /var/www/htdocs/example.com/wp-content/uploads
     * @return string
     */
    protected function getUploadDir() {
        $uploads = wp_upload_dir( null, false );
        $baseDir = wpstg_replace_windows_directory_separator( $uploads['basedir'] );

        // If multisite (and if not the main site in a post-MU network)
        if( is_multisite() && !( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {
            // blogs.dir is used on WP 3.5 and lower
            if( strpos( $baseDir, 'blogs.dir' ) !== false ) {
                // remove this piece from the basedir: /blogs.dir/2/files
                $uploadDir = wpstg_replace_first_match( '/blogs.dir/' . get_current_blog_id() . '/files', null, $baseDir );
                $dir       = wpstg_replace_windows_directory_separator( $uploadDir . '/blogs.dir' );
            } else {
                // remove this piece from the basedir: /sites/2
                $uploadDir = wpstg_replace_first_match( '/sites/' . get_current_blog_id(), null, $baseDir );
                $dir       = wpstg_replace_windows_directory_separator( $uploadDir . '/sites' );
            }


            return $dir;
        }
        return $baseDir;
    }

}
