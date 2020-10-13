<?php

use WPStaging\Backend\Pro\Modules\Jobs\Copiers\Copier;
use WPStaging\Backend\Pro\Modules\Jobs\Copiers\ThemesCopier;
use WPStaging\Framework\Filesystem\Filesystem;

class ThemesCopierTest extends \Codeception\TestCase\WPTestCase
{
    /** @var WpunitTester */
    protected $tester;

    private function makeTheme(bool $isTemp = false, bool $isBackup = false)
    {
        if ($isTemp) {
            $themeId = Copier::PREFIX_TEMP . 'foo' . rand(0, 1000);
        } elseif ($isBackup) {
            $themeId = Copier::PREFIX_BACKUP . 'foo' . rand(0, 1000);
        } else {
            $themeId = 'foo' . rand(0, 1000);
        }

        $this->tester->haveTheme($themeId, 'index_' . $themeId, 'functions_' . $themeId);

        return $themeId;
    }

    private function makeManyThemes(int $qt, bool $isTemp = false, bool $isBackup = false)
    {
        $themes = [];
        for ($i = 0; $i === $qt; $i++) {
            $themes[] = $this->makeTheme();
        }

        return $themes;
    }

    /** @test */
    public function shouldMoveTempsAndBackupsWhilePreservingThemes()
    {
        $themes       = $this->makeManyThemes(5);
        $tempThemes   = $this->makeManyThemes(5, true);
        $backupThemes = $this->makeManyThemes(5, false, true);

        $copier = new ThemesCopier(new Filesystem);
        $copier->copy();

        foreach ($themes as $theme) {
            $this->assertFileExists(trailingslashit(WP_CONTENT_DIR . 'themes/') . $theme);
        }

        foreach ($tempThemes as $theme) {
            $realTheme = str_replace(Copier::PREFIX_TEMP, '', $theme);
            $this->assertFileExists(trailingslashit(WP_CONTENT_DIR) . 'themes/' . $realTheme);
            $this->assertFileDoesNotExist(trailingslashit(WP_CONTENT_DIR) . 'themes/' . $theme);
        }

        foreach ($backupThemes as $theme) {
            $this->assertFileDoesNotExist(trailingslashit(WP_CONTENT_DIR) . 'themes/' . $theme);
        }
    }

    /** @test */
    public function shouldMoveNewTempTheme()
    {
        $tempTheme = trailingslashit(WP_CONTENT_DIR) . 'themes/' . Copier::PREFIX_TEMP . 'NewTheme';

        $this->assertFileDoesNotExist($tempTheme);
        $this->tester->haveTheme(Copier::PREFIX_TEMP . 'NewTheme', 'index_NewTheme', 'functions_NewTheme');
        $this->assertFileExists($tempTheme);

        $copier = new ThemesCopier(new Filesystem);
        $copier->copy();

        $newPath = str_replace(Copier::PREFIX_TEMP, '', $tempTheme);

        $this->assertFileDoesNotExist($tempTheme);
        $this->assertFileExists($newPath);

        $this->tester->openFile(trailingslashit($newPath) . 'functions.php');
        $this->tester->canSeeFileContentsEqual('<?php functions_NewTheme');
    }

    /** @test */
    public function shouldOverrideExistingThemeWithTempTheme()
    {
        $existingTheme = trailingslashit(WP_CONTENT_DIR) . 'themes/' . 'ExistingTheme';
        $tempTheme     = trailingslashit(WP_CONTENT_DIR) . 'themes/' . Copier::PREFIX_TEMP . 'ExistingTheme';

        $this->assertFileDoesNotExist($existingTheme);
        $this->assertFileDoesNotExist($tempTheme);
        $this->tester->haveTheme('ExistingTheme', 'index_ExistingTheme', 'functions_ExistingTheme');
        $this->tester->haveTheme(Copier::PREFIX_TEMP . 'ExistingTheme', 'index_ExistingThemeOverride', 'functions_ExistingThemeOverride');
        $this->assertFileExists($existingTheme);
        $this->assertFileExists($tempTheme);

        $copier = new ThemesCopier(new Filesystem);
        $copier->copy();

        $this->assertFileExists($existingTheme);
        $this->assertFileDoesNotExist($tempTheme);

        $this->tester->openFile(trailingslashit($existingTheme) . 'functions.php');
        $this->tester->canSeeFileContentsEqual('<?php functions_ExistingThemeOverride');
    }
}
