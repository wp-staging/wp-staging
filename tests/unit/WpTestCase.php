<?php

namespace WPStaging\Test;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

abstract class WpTestCase extends TestCase
{
    /** @var vfsStreamDirectory  */
    protected static $vfs;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$vfs = vfsStream::setup('root');
        vfsStream::copyFromFileSystem($_ENV['WP_ROOT']);
    }
}
