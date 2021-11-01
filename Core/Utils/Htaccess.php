<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Filesystem\Filesystem;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

/**
 * Description of Htaccess
 *
 * @author IronMan
 */
class Htaccess
{

    /**
     *
     * @var obj
     */
    public $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * Create .htaccess file
     *
     * @param  string  $path Path to file
     * @return boolean
     */
    public function create($path)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
                    '<IfModule mod_mime.c>',
                    'AddType application/octet-stream .log',
                    'AddType application/octet-stream .wpstg',
                    '</IfModule>',
                    '<IfModule mod_dir.c>',
                    'DirectoryIndex index.php',
                    '</IfModule>',
                    '<IfModule mod_autoindex.c>',
                    'Options -Indexes',
                    '</IfModule>',
                ]));
    }

    /**
     * Create .htaccess file for LiteSpeed webserver
     * The LiteSpeed web server has been known to kill or stop processes that take more than a few seconds to run.
     * This will tell LiteSpeed to not abruptly abort requests
     *
     * @param  string  $path Path to file
     * @return boolean
     */
    public function createLitespeed($path)
    {
        return $this->filesystem->createWithMarkers($path, 'LiteSpeed', [
                    '<IfModule Litespeed>',
                    'SetEnv noabort 1',
                    '</IfModule>',
                ]);
    }

    /**
     * Create .htaccess file for staging network
     *
     * @param  string  $path Path to file
     * @param  string  $baseDirectory of staging network
     * @return boolean
     */
    public function createForStagingNetwork($path, $baseDirectory)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
                    'RewriteEngine On',
                    'RewriteBase ' . trailingslashit($baseDirectory),
                    'RewriteRule ^index\.php$ - [L]',
                    '',
                    '# add a trailing slash to /wp-admin',
                    'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]',
                    '',
                    'RewriteCond %{REQUEST_FILENAME} -f [OR]',
                    'RewriteCond %{REQUEST_FILENAME} -d',
                    'RewriteRule ^ - [L]',
                    'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]',
                    'RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]',
                    'RewriteRule . index.php [L]',
                    '',
                ]));
    }
}
