<?php

use WPStaging\Backend\Pro\Modules\Jobs\Copiers\Copier;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\PluginsCopier;
use WPStaging\Framework\Filesystem\Filesystem;

class PluginsCopierTest extends \Codeception\TestCase\WPTestCase
{
    /** @var WpunitTester */
    protected $tester;

    /** @var array Files to be deleted after each test */
    protected $toDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->toDelete as $fileOrFolder) {
            if (is_file($fileOrFolder)) {
                if ( ! unlink($fileOrFolder)) {
                    throw new Exception('Could not delete the file ' . $fileOrFolder);
                }
            } else {
                if ( ! \tad\WPBrowser\rrmdir($fileOrFolder)) {
                    throw new Exception('Could not delete the folder ' . $fileOrFolder);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Our own implementation of "havePlugin",
     * since wp-browser currently deletes
     * the Plugins folder for single-file plugins.
     *
     * @see https://github.com/lucatume/wp-browser/issues/459
     *
     * @param string $path
     * @param string $code
     *
     * @throws Exception
     */
    private function havePlugin(string $path, string $code)
    {
        $fullPath = trailingslashit(WP_PLUGIN_DIR) . $path;

        $isPluginInsideFolder = false;

        if (dirname($fullPath) !== WP_PLUGIN_DIR) {
            $isPluginInsideFolder = true;

            $dir = dirname($fullPath);

            if ( ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new \Exception(
                    __CLASS__,
                    "Could not create [{$dir}] plugin folder."
                );
            }
        }

        $contents = <<<PHP
<?php
/*
Plugin Name: $path
Description: $path
*/

$code
PHP;

        $put = file_put_contents($fullPath, $contents);

        if ( ! $put) {
            throw new \Exception(
                __CLASS__,
                "Could not create [{$fullPath}] plugin file."
            );
        }

        if ($isPluginInsideFolder) {
            $this->toDelete[] = $dir;
        } else {
            $this->toDelete[] = $fullPath;
        }
    }

    /** @test */
    public function shouldMoveNewTempPluginInsideFolder()
    {
        $tempPluginFile = Copier::PREFIX_TEMP . 'foo/bar.php';
        $tempPluginPath = trailingslashit(WP_PLUGIN_DIR) . $tempPluginFile;

        $this->assertFileDoesNotExist($tempPluginPath);
        $this->havePlugin($tempPluginFile, 'code-for-plugin-inside-folder');
        $this->assertFileExists($tempPluginPath);

        $copier = new PluginsCopier(new Filesystem);
        $copier->copy();

        $newPluginPath = str_replace(Copier::PREFIX_TEMP, '', $tempPluginPath);

        $this->assertFileExists($newPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);

        $this->tester->openFile($newPluginPath);
        $this->tester->seeInThisFile('code-for-plugin-inside-folder');
        $this->toDelete[] = $newPluginPath;
    }

    /** @test */
    public function shouldOverrideExistingPluginWithTempPluginInsideFolder()
    {
        $existingPlugin     = 'foo/bar.php';
        $tempPlugin         = Copier::PREFIX_TEMP . 'foo/bar.php';
        $existingPluginPath = trailingslashit(WP_PLUGIN_DIR) . 'foo/bar.php';
        $tempPluginPath     = trailingslashit(WP_PLUGIN_DIR) . Copier::PREFIX_TEMP . 'foo/bar.php';

        $this->assertFileDoesNotExist($existingPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);
        $this->havePlugin($existingPlugin, 'ExistingPluginInFolder');
        $this->havePlugin($tempPlugin, 'NewPluginInFolder');
        $this->assertFileExists($existingPluginPath);
        $this->assertFileExists($tempPluginPath);

        $copier = new PluginsCopier(new Filesystem);
        $copier->copy();

        $this->assertFileExists($existingPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);

        $this->tester->openFile($existingPluginPath);
        $this->tester->dontSeeInThisFile('ExistingPluginInFolder');
        $this->tester->seeInThisFile('NewPluginInFolder');
        $this->toDelete[] = $existingPluginPath;
    }

    /**
     * Test skipped for now, as PluginsCopier does not copy single-file plugins, yet.
     *
     * @see \WPStaging\Backend\Pro\Modules\Jobs\ScanDirectories::getStagingPlugins
     */
    public function shouldMoveNewTempPluginSingleFile()
    {
        $tempPluginFile = Copier::PREFIX_TEMP . 'foo.php';
        $tempPluginPath = trailingslashit(WP_PLUGIN_DIR) . $tempPluginFile;

        $this->assertFileDoesNotExist($tempPluginPath);
        $this->havePlugin($tempPluginFile, 'single-file-code');
        $this->assertFileExists($tempPluginPath);

        $copier = new PluginsCopier(new Filesystem);
        $copier->copy();

        $newPluginPath = str_replace(Copier::PREFIX_TEMP, '', $tempPluginPath);


        $this->assertFileExists($newPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);

        $this->tester->openFile($newPluginPath);
        $this->tester->seeInThisFile('single-file-code');

        $this->toDelete[] = $newPluginPath;
    }

    /**
     * Test skipped for now, as PluginsCopier does not copy single-file plugins, yet.
     *
     * @see \WPStaging\Backend\Pro\Modules\Jobs\ScanDirectories::getStagingPlugins
     */
    public function shouldOverrideExistingPluginWithTempSingleFilePlugin()
    {
        $existingPlugin     = 'foo.php';
        $tempPlugin         = Copier::PREFIX_TEMP . 'foo.php';
        $existingPluginPath = trailingslashit(WP_PLUGIN_DIR) . 'foo.php';
        $tempPluginPath     = trailingslashit(WP_PLUGIN_DIR) . Copier::PREFIX_TEMP . 'foo.php';

        $this->assertFileDoesNotExist($existingPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);
        $this->havePlugin($existingPlugin, 'ExistingPluginSingle');
        $this->havePlugin($tempPlugin, 'NewPluginSingle');
        $this->assertFileExists($existingPluginPath);
        $this->assertFileExists($tempPluginPath);

        $copier = new PluginsCopier(new Filesystem);
        $copier->copy();

        $this->assertFileExists($existingPluginPath);
        $this->assertFileDoesNotExist($tempPluginPath);

        $this->tester->openFile($existingPluginPath);
        $this->tester->dontSeeInThisFile('ExistingPluginSingle');
        $this->tester->seeInThisFile('NewPluginSingle');
        $this->toDelete[] = $existingPluginPath;
    }
}
