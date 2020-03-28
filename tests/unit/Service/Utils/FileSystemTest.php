<?php

namespace WPStaging;

use PHPUnit\Framework\TestCase;
use WPStaging\Service\Utils\FileSystem;

/**
 * @small
 */
class FileSystemTest extends TestCase
{
    /** @var FileSystem */
    protected $fileSystem;

    protected function setUp()
    {
        $this->fileSystem = new FileSystem;
    }

    public function testCompatiblePath()
    {
        // test must be executed on linux to succeed. TODO Can we test windows here?
        $linux = '/wp-content/uploads';
        $this->assertEquals($this->fileSystem->compatiblePath($linux), $linux);

    }

    public function testReplaceWindowsDirSeparator()
    {
        $this->assertEquals($this->fileSystem->replaceWindowsDirSeparator('\wp-content\uploads'), '/wp-content/uploads');
    }
}
