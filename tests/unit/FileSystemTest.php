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

    public function testExcludedFiles()
    {

        $file1 = '/var/www/htdocs/domain.com/wp-content/uploads/data.logger.php';
        $file2 = '/var/www/htdocs/domain.com/wp-content/uploads/datalogger.log';
        $file3 = '/var/www/htdocs/domain.com/wp-content/uploads/logger.php';
        $file4 = '/var/www/htdocs/domain.com/wp-content/uploads/.htaccess';
        $file5 = '/var/www/htdocs/domain.com/wp-content/uploads/.git';
        $file6 = '/var/www/htdocs/domain.com/wp-content/uploads/.log.test';

        $excludedFiles = array(
            '*.log',
            '.log',
            '.htaccess',
            '.git',
        );

        $this->assertFalse($this->fileSystem->isFilenameExcluded($file1, $excludedFiles));
        $this->assertTrue($this->fileSystem->isFilenameExcluded($file2, $excludedFiles));
        $this->assertFalse($this->fileSystem->isFilenameExcluded($file3, $excludedFiles));
        $this->assertTrue($this->fileSystem->isFilenameExcluded($file4, $excludedFiles));
        $this->assertTrue($this->fileSystem->isFilenameExcluded($file5, $excludedFiles));
        $this->assertFalse($this->fileSystem->isFilenameExcluded($file6, $excludedFiles));

    }
}