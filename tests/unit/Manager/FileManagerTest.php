<?php

use Codeception\Test\Unit;
use WPStaging\Manager\FileSystem\FileManager;

class FileManagerTest extends Unit
{
    /** @var FileManager */
    private $object;

    /** @var array */
    private $excludedFiles = [];

    protected function setUp()
    {
        $this->object = new FileManager;
        $this->excludedFiles = [
            '*.log',
            '.log',
            '.htaccess',
            '.git',
        ];

        return parent::setUp();
    }

    public function excludedFilesProvider()
    {
        return [
            ['/var/www/htdocs/domain.com/wp-content/uploads/datalogger.log'],
            ['/var/www/htdocs/domain.com/wp-content/uploads/.htaccess'],
            ['/var/www/htdocs/domain.com/wp-content/uploads/.git'],
        ];
    }

    /**
     * @covers \WPStaging\Manager\FileSystem\FileManager::isFilenameExcluded
     * @dataProvider excludedFilesProvider
     * @param string $filePath
     */
    public function testFileExcluded($filePath)
    {
        $this->assertTrue($this->object->isFilenameExcluded($filePath, $this->excludedFiles));
    }

    public function notExcludedFilesProvider()
    {
        return [
            ['/var/www/htdocs/domain.com/wp-content/uploads/data.logger.php'],
            ['/var/www/htdocs/domain.com/wp-content/uploads/logger.php'],
            ['/var/www/htdocs/domain.com/wp-content/uploads/.log.test'],
        ];
    }

    /**
     * @covers \WPStaging\Manager\FileSystem\FileManager::isFilenameExcluded
     * @dataProvider notExcludedFilesProvider
     * @param string $filePath
     */
    public function testFileNotExcluded($filePath)
    {
        $this->assertFalse($this->object->isFilenameExcluded($filePath, $this->excludedFiles));
    }
}
