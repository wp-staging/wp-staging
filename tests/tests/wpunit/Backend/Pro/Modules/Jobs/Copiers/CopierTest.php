<?php

use WPStaging\Framework\Filesystem\Filesystem;

class CopierTest extends \Codeception\TestCase\WPTestCase
{
    /** @var WpunitTester */
    protected $tester;

    /** @var array $toDelete Files to delete after each test. */
    private $toDelete = [];


    public function _tearDown(): void
    {
        $filesystem = new WPStaging\Framework\Filesystem\Filesystem;
        foreach ($this->toDelete as $toDelete) {
            // Let's not write nor delete anything outside WordPress, for safety.
            $safePath = ABSPATH . str_replace(ABSPATH, '', $toDelete);
            $filesystem->delete($safePath);
        }

        parent::_tearDown();
    }

    protected function makeCopier()
    {
        return new class(new Filesystem) extends WPStaging\Backend\Pro\Modules\Jobs\Copiers\Copier {
            public function isAllowedToRenameOrRemove($path)
            {
                return parent::isAllowedToRenameOrRemove($path);
            }

            public function getPluginsDir()
            {
                return $this->pluginsDir;
            }

            public function getThemesDir()
            {
                return $this->themesDir;
            }
        };
    }

    /** @test */
    public function shouldAllowToRenameOrRemoveInPluginsFolder()
    {
        $copier = $this->makeCopier();

        $paths = [
            // Single-file
            $copier->getPluginsDir() . 'foo.php',
            // Single-file, with extra slash
            $copier->getPluginsDir() . '/foo.php',
            // File inside folder
            $copier->getPluginsDir() . 'foo/foo.php',
            // File inside folder, with extra slash
            $copier->getPluginsDir() . '/foo/foo.php',
        ];

        foreach ($paths as $index => $path) {
            $this->maybeCreatePath($path);
            $this->assertTrue($copier->isAllowedToRenameOrRemove($path), sprintf("%s (%d) should be allowed, but wasn't.", $path, $index));
        }
    }

    /** @test */
    public function shouldAllowToRenameOrRemoveInThemesFolder()
    {
        $copier = $this->makeCopier();

        $paths = [
            // Direct child of theme
            $copier->getThemesDir() . 'foo',
            // Direct child of theme, with extra slash
            $copier->getThemesDir() . '/foo',
        ];

        foreach ($paths as $path) {
            $this->maybeCreatePath($path);
            $this->assertTrue($copier->isAllowedToRenameOrRemove($path), sprintf("%s should be allowed, but wasn't.", $path));
        }
    }

    /** @test */
    public function shouldNowAllowToRenameOutsidePluginsOrThemesFolder()
    {
        $copier = $this->makeCopier();

        $paths = [
            // Uploads folder
            trailingslashit(WP_CONTENT_DIR) . 'uploads/foo',
            // Go up the themes directory
            $copier->getThemesDir() . '../foo',
            // Go up the themes directory, with extra slash
            $copier->getThemesDir() . '/../foo',
            // Go up the themes directory, with extra slash 2
            $copier->getThemesDir() . '..//foo',
            // Go up the plugins directory
            $copier->getPluginsDir() . '../foo',
            trailingslashit(WP_CONTENT_DIR) . 'foo',
            trailingslashit(WP_CONTENT_DIR),
            '/',
        ];

        foreach ($paths as $path) {
            $this->maybeCreatePath($path);
            $this->assertFalse($copier->isAllowedToRenameOrRemove($path), sprintf("%s should NOT be allowed, but WAS.", $path));
        }
    }


    protected function maybeCreatePath($path)
    {
        if (file_exists($path)) {
            return;
        }

        // Let's not write nor delete anything outside WordPress, for safety.
        // Contrary to \WPStaging\Framework\Filesystem\Filesystem::safePath,
        // this doesn't require the file to exist.
        $safePath = ABSPATH . str_replace(ABSPATH, '', $path);

        $dir = dirname($safePath);

        if ( ! file_exists($dir)) {
            wp_mkdir_p($dir);
            $this->toDelete[] = $dir;
        }

        if ( ! file_exists($safePath)) {
            file_put_contents($safePath, '');
            $this->toDelete[] = $safePath;
        }
    }
}
