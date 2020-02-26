<?php

namespace WPStaging;

use WPStaging\Service\Utils\FileSystem;

class FileSystemTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $fileSystem;

    protected function _before()
    {

    }

    protected function _after()
    {
    }

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->fileSystem = new FileSystem();
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
