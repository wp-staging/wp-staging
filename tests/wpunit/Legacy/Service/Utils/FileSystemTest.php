<?php

namespace WPStaging;

use WPStaging\Framework\Utils\FileSystem;

class FileSystemTest extends \Codeception\TestCase\WPTestCase
{
    /** @var FileSystem */
    protected $fileSystem;

    protected function setUp(): void
    {
    	parent::setUp();
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
