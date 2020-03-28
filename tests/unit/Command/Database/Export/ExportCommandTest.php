<?php

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use WPStaging\Command\Database\Export\ExportCommand;
use WPStaging\Command\Database\Export\ExportDto;
use WPStaging\Manager\Database\TableDto;
use WPStaging\Manager\Database\TableManager;
use WPStaging\Service\Collection\Collection;
use WPStaging\Test\WpTestCase;

/**
 * @large
 */
class ExportCommandTest extends WpTestCase
{
    /** @var MockObject|LoggerInterface */
    private $mockLogger;

    /** @var MockObject|TableManager */
    private $mockTableManager;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::setUpBeforeClass();
    }

    public function setUp()
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_NAME']),
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD']
        );

        $pdo->exec(file_get_contents(__DIR__ . '/database-sample.sql'));

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
                    'host' => $_ENV['DB_HOST'],
                    'port' => $_ENV['DB_PORT'],
                    'name' => $_ENV['DB_NAME'],
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'prefix' => 'wp_',
                    'directory' => static::$vfs->url() . DIRECTORY_SEPARATOR,
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
                    'host' => $_ENV['DB_HOST'],
                    'port' => $_ENV['DB_PORT'],
                    'name' => $_ENV['DB_NAME'],
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'prefix' => 'wp_',
                    'format' => ExportCommand::FORMAT_SQL,
                    'directory' => static::$vfs->url() . DIRECTORY_SEPARATOR,
                    'version' => 'test',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider generateSqlFileProvider
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
