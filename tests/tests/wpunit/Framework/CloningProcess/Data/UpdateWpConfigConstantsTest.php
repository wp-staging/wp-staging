<?php

namespace Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Framework\CloningProcess\Data\DataCloningDto;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigConstants;

/**
 * Class UpdateWpConfigConstantsTest
 *
 * @package Framework\CloningProcess\Data
 */
class UpdateWpConfigConstantsTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

    public function setUp(): void
    {
        // Before...
        parent::setUp();
        // Your set up methods here.

        // Reset the filters
        remove_all_filters('wpstaging_wpconfigconstants_replace_or_add');
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();
    }

    /**
     * Create a stub of DataCloningDto,
     * with only a handful of methods
     * actually returning results.
     *
     * @param string $stringToTest
     *
     * @return \PHPUnit\Framework\MockObject\Stub|DataCloningDto
     */
    protected function makeDto(string $stringToTest, bool $isExternal): DataCloningDto
    {
        $dto = new DataCloningDto(
            $this->createStub(Job::class),
            $this->createStub(\wpdb::class),
            $this->createStub(\wpdb::class),
            $isExternal,
            '',
            $stringToTest,
            $stringToTest,
            $stringToTest,
            $stringToTest,
            '',
            '',
            [],
            '',
            '',
            '',
            new \stdClass,
            '',
            '',
            ''
        );

        return $dto;
    }

    /**
     * Overrides some methods of UpdateWpConfigConstants
     * for improved testability.
     *
     * @param DataCloningDto $dto
     *
     * @param string         $wp_config
     *
     * @return UpdateWpConfigConstants
     */
    protected function makeSut(DataCloningDto $dto, string $original_wp_config): UpdateWpConfigConstants
    {
        $sut = new class($dto) extends UpdateWpConfigConstants {
            /**
             * @var string The variable that represents the contents of wp-config.php
             */
            public $wp_config = '';

            /**
             * Overrides the original method to READ from a variable,
             * instead of wp-config.php.
             *
             * @return string
             */
            public function readWpConfig()
            {
                return $this->wp_config;
            }

            /**
             * Overrides the original method to WRITE to a variable,
             * instead wp-config.php.
             *
             * @param $content
             */
            public function writeWpConfig($content)
            {
                $this->wp_config = $content;
            }
        };

        $sut->wp_config = $original_wp_config;

        return $sut;
    }

    /**
     * Adds a filter to only replace specific constants.
     *
     * @param array $constantsToReplace The array of constants to replace.
     */
    protected function addFilterToReplaceOnly(array $constantsToReplace): void
    {
        add_filter(
            'wpstg_constants_replace_or_add',
            function ($replaceOrAdd) use ($constantsToReplace) {
                return array_filter(
                    $replaceOrAdd,
                    function ($item) use ($constantsToReplace) {
                        // Only keep in this array the $constantsToReplace
                        return in_array($item, $constantsToReplace);
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }
        );
    }

    protected function getOriginalWPConfig(): string
    {
        return <<<PHP
<?php
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
// DB_NAME will come here:
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;
    }

    /** @test */
    public function shouldAllowExternalDbCredentialsWithSingleQuotes()
    {
        $string_with_quotes = "SomeString'WithSingleQuotes";
        $dto                = $this->makeDto($string_with_quotes, true);
        $sut                = $this->makeSut($dto, $this->getOriginalWPConfig());
        $this->addFilterToReplaceOnly(['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']);

        $sut->execute();

        $expected = <<<PHP
<?php
define('DB_HOST', 'SomeString\'WithSingleQuotes');
define('DB_USER', 'SomeString\'WithSingleQuotes');
define('DB_PASSWORD', 'SomeString\'WithSingleQuotes');
// DB_NAME will come here:
define('DB_NAME', 'SomeString\'WithSingleQuotes'); 
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $this->assertEquals($expected, $sut->wp_config);
    }

    /** @test */
    public function shouldAllowExternalDbCredentialsWithDoubleQuotes()
    {
        $string_to_test = 'SomeString"WithDoubleQuotes';
        $dto            = $this->makeDto($string_to_test, true);
        $sut            = $this->makeSut($dto, $this->getOriginalWPConfig());
        $this->addFilterToReplaceOnly(['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']);

        $sut->execute();

        $expected = <<<PHP
<?php
define('DB_HOST', 'SomeString"WithDoubleQuotes');
define('DB_USER', 'SomeString"WithDoubleQuotes');
define('DB_PASSWORD', 'SomeString"WithDoubleQuotes');
// DB_NAME will come here:
define('DB_NAME', 'SomeString"WithDoubleQuotes'); 
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $this->assertEquals($expected, $sut->wp_config);
    }

    /** @test */
    public function shouldAllowExternalDbCredentialsWithSpecialCharacters()
    {
        $string_to_test = '!@#$%"&*()0123456789-=\'abcABC';
        $dto            = $this->makeDto($string_to_test, true);
        $sut            = $this->makeSut($dto, $this->getOriginalWPConfig());
        $this->addFilterToReplaceOnly(['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME']);

        $sut->execute();

        $expected = <<<PHP
<?php
define('DB_HOST', '!@#$%"&*()0123456789-=\'abcABC');
define('DB_USER', '!@#$%"&*()0123456789-=\'abcABC');
define('DB_PASSWORD', '!@#$%"&*()0123456789-=\'abcABC');
// DB_NAME will come here:
define('DB_NAME', '!@#$%"&*()0123456789-=\'abcABC'); 
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $this->assertEquals($expected, $sut->wp_config);
    }

    /** @test */
    public function shouldOverrideWPEnvironmentTypeConstantIfSet()
    {
        $original = <<<PHP
<?php
define('WP_ENVIRONMENT_TYPE', 'production');
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $dto = $this->makeDto('', true);
        $sut = $this->makeSut($dto, $original);
        $this->addFilterToReplaceOnly(['WP_ENVIRONMENT_TYPE']);

        $sut->execute();

        $expected = <<<PHP
<?php
define('WP_ENVIRONMENT_TYPE', 'staging');
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $this->assertEquals($expected, $sut->wp_config);
    }

    /** @test */
    public function shouldAddWPEnvironmentTypeConstantIfNotSet()
    {
        $original = <<<PHP
<?php
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $dto = $this->makeDto('', true);
        $sut = $this->makeSut($dto, $original);
        $this->addFilterToReplaceOnly(['WP_ENVIRONMENT_TYPE']);

        $sut->execute();

        $expected = <<<PHP
<?php
define('WP_ENVIRONMENT_TYPE', 'staging'); 
if ( ! defined( 'ABSPATH' ) ) {};
?>
PHP;

        $this->assertEquals($expected, $sut->wp_config);
    }
}
