<?php
/** @noinspection PhpUndefinedClassInspection */

use PHPUnit\Framework\MockObject\MockObject;
use WPStaging\Manager\FileSystem\DirectoryManager;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\WpDefaultDirectories;

class DirectoryManagerTest extends \Codeception\TestCase\WPTestCase
{

    /** @var DirectoryManager */
    private $manager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('ABSPATH')) {
            define('ABSPATH', static::$vfs->url() . DIRECTORY_SEPARATOR);
        }

        if (!defined('WPINC')) {
            define('WPINC', WpDefaultDirectories::WP_INCLUDES);
        }

        require_once ABSPATH . 'wp-includes/formatting.php';
        require_once ABSPATH . 'wp-includes/functions.php';
    }

    protected function setUp(): void
    {
        $this->manager = new DirectoryManager($this->getDirectoryAdapterMock());
        parent::setUp();
    }

    /**
     * @return MockObject|Directory
     */
    protected function getDirectoryAdapterMock()
    {
        /** @var MockObject $mock */
        $mock = $this->getMockBuilder(Directory::class)
                     ->disableOriginalConstructor()
                     ->getMock()
        ;

        $mock->method('getUploadsDirectory')->willReturn(ABSPATH . $this->getRelativeUploadsDir());

        return $mock;
    }

    protected function getRelativeUploadsDir()
    {
        return WpDefaultDirectories::WP_CONTENT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }

    public function testGetRelativeUploadsDirectory()
    {
        $this->assertEquals($this->getRelativeUploadsDir(), $this->manager->getRelativeUploadsDirectory());
    }

    /**
     * @param string $dirname
     * @dataProvider customUploadDirectoriesProvider
     */
    public function testProvideCustomUploadsDirectory($dirname)
    {
        $fullPath = ABSPATH . $this->getRelativeUploadsDir() . trim($dirname, '/\\') . DIRECTORY_SEPARATOR;
        $this->assertSame($fullPath, $this->manager->provideCustomUploadsDirectory($dirname));
    }

    public function customUploadDirectoriesProvider()
    {
        return [
            ['wp-staging/' . md5(mt_rand())],
            ['wp-staging/some-other/' . md5(mt_rand())],
            ['wp-staging/another/' . md5(mt_rand())],
            ['wp-staging/snapshot/' . md5(mt_rand())],
            ['wp-staging/export/' . md5(mt_rand())],
            ['wp-staging/export/snapshot/' . md5(mt_rand())],
        ];
    }
}
