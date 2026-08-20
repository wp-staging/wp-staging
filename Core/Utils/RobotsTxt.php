<?php

namespace WPStaging\Core\Utils;

use WPStaging\Framework\Filesystem\Filesystem;

 
if (!defined("WPINC")) {
    die;
}





class RobotsTxt
{





    public $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }







    public function create($path)
    {
        return $this->filesystem->create($path, implode(PHP_EOL, [
            'User-agent: *',
            'Disallow: /',
        ]));
    }
}
