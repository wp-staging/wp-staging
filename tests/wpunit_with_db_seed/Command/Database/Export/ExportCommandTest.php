<?php

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use WPStaging\Command\Database\Export\ExportCommand;
use WPStaging\Command\Database\Export\ExportDto;
use WPStaging\Manager\Database\TableDto;
use WPStaging\Manager\Database\TableManager;
use WPStaging\Service\Collection\Collection;

class ExportCommandTest extends \Codeception\TestCase\WPTestCase
{
    /** @var MockObject|LoggerInterface */
    private $mockLogger;

    /** @var MockObject|TableManager */
    private $mockTableManager;

    /** @var \Codeception\Module\WPDb */
    private $wpdb;

	/** @var \Codeception\Module\WPLoader */
	private $wploader;

    public function setUp(): void
    {
    	parent::setUp();

    	$this->wpdb = $this->getModule('WPDb');
    	$this->wploader = $this->getModule('WPLoader');

    	$this->wpdb->importSqlDumpFile(codecept_data_dir('seeds/database-export.sql'));

        $this->mockTableManager = $this->getMockBuilder(TableManager::class)
                                 ->disableOriginalConstructor()
                                 ->getMock()
        ;

        $this->mockTableManager->method('findStartsWith')->willReturn($this->getTableCollection());

        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
                                 ->disableOriginalConstructor()
                                 ->getMock()
        ;
    }

    public function getIncludeTablesProvider()
    {
        return [
            [
                (new ExportDto)->hydrate([
                    'host' => 'database',
                    'port' => 3306,
                    'name' => $this->wpdb->currentDatabase,
                    'username' => $this->wpdb->_getConfig('user'),
                    'password' => $this->wpdb->_getConfig('password'),
                    'prefix' => 'wp_',
                    'directory' => $this->wploader->_getConfig('wpRootFolder'),
                    'version' => 'test',
                ]),
                [
                    'wp_commentmeta',
                    'wp_comments',
                    'wp_links',
                    'wp_options',
                    'wp_postmeta',
                    'wp_posts',
                    'wp_term_relationships',
                    'wp_term_taxonomy',
                    'wp_termmeta',
                    'wp_terms',
                    'wp_usermeta',
                    'wp_users',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getIncludeTablesProvider
     * @group slow
     *
     * @param ExportDto $dto
     * @param array $expected
     *
     * @throws ReflectionException
     */
    public function testGetIncludeTables(ExportDto $dto, array $expected)
    {
        $exporter = new ExportCommand($dto, $this->mockTableManager, $this->mockLogger);

        $reflector = new ReflectionClass(ExportCommand::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $method = $reflector->getMethod('getIncludeTables');
        $method->setAccessible(true);

        $result = $method->invoke($exporter);
        $this->assertEquals($expected, $result);
    }

    public function generateSqlFileProvider()
    {
        // ifsnop zipped formats might not work properly with file streams
        // see https://www.php.net/manual/en/function.gzopen.php#105676
        // see http://php.net/manual/en/function.gzdecode.php#112200
        // that's why this tests includes ExportCommand::FORMAT_SQL
        return [
            [
                (new ExportDto)->hydrate([
	                'host' => 'database',
	                'port' => 3306,
	                'name' => $this->wpdb->currentDatabase,
	                'username' => $this->wpdb->_getConfig('user'),
	                'password' => $this->wpdb->_getConfig('password'),
                    'prefix' => 'wp_',
                    'format' => ExportCommand::FORMAT_SQL,
                    'directory' => $this->wploader->_getConfig('wpRootFolder'),
                    'version' => 'test',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider generateSqlFileProvider
     * @group slow
     *
     * @param ExportDto $dto
     * @throws ReflectionException
     */
    public function testGenerateSqlFile(ExportDto $dto)
    {
        $exporter = new ExportCommand($dto, $this->mockTableManager, $this->mockLogger);

        $reflector = new ReflectionClass(ExportCommand::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $method = $reflector->getMethod('generateSqlFile');
        $method->setAccessible(true);

        $result = $method->invoke($exporter);

        $this->assertFileExists($result);
        $this->assertGreaterThan(10, filesize($result));
    }

    /**
     * @dataProvider generateSqlFileProvider
     * @group slow
     *
     * @param ExportDto $dto
     *
     * @throws Exception
     */
    public function testExecute(ExportDto $dto)
    {
        $exporter = new ExportCommand($dto, $this->mockTableManager, $this->mockLogger);
        $exporter->execute();

        /** @noinspection PhpParamsInspection */
        $this->assertFileExists($dto->getFullPath());
        $this->assertGreaterThan(10, filesize($dto->getFullPath()));
    }

    protected function getTableCollection()
    {
        $tableCollection = new Collection(TableDto::class);
        $tableCollection->attachAllByArray([
            [
                'Name' => 'wp_commentmeta',
            ],
            [
                'Name' => 'wp_comments',
            ],
            [
                'Name' => 'wp_links',
            ],
            [
                'Name' => 'wp_options',
            ],
            [
                'Name' => 'wp_postmeta',
            ],
            [
                'Name' => 'wp_posts',
            ],
            [
                'Name' => 'wp_term_relationships',
            ],
            [
                'Name' => 'wp_term_taxonomy',
            ],
            [
                'Name' => 'wp_termmeta',
            ],
            [
                'Name' => 'wp_terms',
            ],
            [
                'Name' => 'wp_usermeta',
            ],
            [
                'Name' => 'wp_users',
            ],
        ]);

        return $tableCollection;
    }
}
