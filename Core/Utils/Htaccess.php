<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;

 
if (!defined("WPINC")) {
    die;
}






class Htaccess
{



    const FILTER_CREATE_LITE_SPEED_SERVER_CONFIG = 'wpstg.create_litespeed_server_config';




    const PHP_HANDLER_EXTENSIONS = '.php .php3 .php4 .php5 .php7 .php8 .phtml .phps .phar .pht';





    public $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }







    public function create($path)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
            '<IfModule mod_mime.c>',
            'AddType application/octet-stream .log',
            'AddType application/octet-stream .wpstg',
            'AddType application/octet-stream .wpstgtmp',
            'RemoveHandler ' . self::PHP_HANDLER_EXTENSIONS,
            'RemoveType ' . self::PHP_HANDLER_EXTENSIONS,
            '</IfModule>',
            '<IfModule mod_dir.c>',
            'DirectoryIndex index.html index.php',
            '</IfModule>',
            '<IfModule mod_autoindex.c>',
            'Options -Indexes',
            '</IfModule>',
        ]));
    }









    public function createLitespeed($path)
    {
        if (!Hooks::applyFilters(self::FILTER_CREATE_LITE_SPEED_SERVER_CONFIG, false)) {
            return false;
        }

        return $this->filesystem->createWithMarkers($path, 'LiteSpeed', [
            '<IfModule Litespeed>',
            'SetEnv noabort 1',
            '</IfModule>',
        ]);
    }








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
