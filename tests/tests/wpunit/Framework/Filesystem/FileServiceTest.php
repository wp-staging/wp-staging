<?php

use WPStaging\Framework\Filesystem\FileService;

/**
 * @small
 */
class FileServiceTest extends \Codeception\TestCase\WPTestCase
{
    /** @var FileService */
    private $object;

    /** @var array */
    private $excludedFiles = [];

    protected function setUp() : void
    {
        $this->object = new FileService;
        $this->excludedFiles = [
            '*.log',
            '.log',
            '.htaccess',
            '.git',
        ];

        $this->requestTimeSetUp();
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
     * @covers \WPStaging\Framework\Filesystem\FileService::isFilenameExcluded
     * @dataProvider excludedFilesProvider
     * @param string
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
     * @covers \WPStaging\Framework\Filesystem\FileService::isFilenameExcluded
     * @dataProvider notExcludedFilesProvider
     * @param string
     */
    public function testFileNotExcluded($filePath)
    {
        $this->assertFalse($this->object->isFilenameExcluded($filePath, $this->excludedFiles));
    }
}
