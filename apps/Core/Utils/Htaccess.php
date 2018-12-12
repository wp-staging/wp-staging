<?php

namespace WPStaging\Utils;

use WPStaging\Utils\Filesystem;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Description of Htaccess
 *
 * @author IronMan
 */
class Htaccess {
    
    /**
     * 
     * @var obj
     */
    public $filesystem;
    
    public function __construct() {
        $this->filesystem = new Filesystem();
    }

    /**
     * Create .htaccess file
     *
     * @param  string  $path Path to file
     * @return boolean
     */
    public function create( $path ) {
        return $this->filesystem->create( $path, implode( PHP_EOL, array(
                    '<IfModule mod_mime.c>',
                    'AddType application/octet-stream .log',
                    '</IfModule>',
                    '<IfModule mod_dir.c>',
                    'DirectoryIndex index.php',
                    '</IfModule>',
                    '<IfModule mod_autoindex.c>',
                    'Options -Indexes',
                    '</IfModule>',
                ) ) );
    }

    /**
     * Create .htaccess file (LiteSpeed)
     *
     * @param  string  $path Path to file
     * @return boolean
     */
    public function createLitespeed( $path ) {
        return $this->filesystem->createWithMarkers( $path, 'LiteSpeed', array(
                    '<IfModule Litespeed>',
                    'SetEnv noabort 1',
                    '</IfModule>',
                ) );
    }

}
