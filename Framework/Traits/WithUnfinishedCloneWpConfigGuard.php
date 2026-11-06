<?php

namespace WPStaging\Framework\Traits;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;

use function WPStaging\functions\debug_log;




trait WithUnfinishedCloneWpConfigGuard
{






    protected function copyWpConfigWithUnfinishedCloneGuard(Filesystem $filesystem, string $sourcePath, string $destinationPath): bool
    {
        $wpConfigContent = file_get_contents($sourcePath);
        if ($wpConfigContent === false) {
            return false;
        }

        $guardedWpConfig = $this->addUnfinishedCloneGuardToWpConfig($wpConfigContent);
        if ($guardedWpConfig === false) {
            return false;
        }

        return $filesystem->create($destinationPath, $guardedWpConfig);
    }









    protected function createGuardOnlyWpConfigWhenMissing(Filesystem $filesystem, string $stagingSiteRootPath): bool
    {
        $stagingSiteRootPath = trailingslashit($stagingSiteRootPath);
        $wpConfigPath        = $stagingSiteRootPath . 'wp-config.php';
        if (file_exists($wpConfigPath)) {
            return true;
        }

        if (is_link($wpConfigPath)) {
            return false;
        }

        if (!is_dir($stagingSiteRootPath) && !$filesystem->mkdir($stagingSiteRootPath)) {
            return false;
        }

        return $filesystem->create($wpConfigPath, $this->getGuardOnlyWpConfig());
    }













    protected function deleteStagingSiteFilesWithWpConfigLast(Filesystem $filesystem, string $stagingSiteRootPath, bool $deleteSelf): bool
    {
        if ($filesystem->normalizePath($stagingSiteRootPath, true) === $filesystem->normalizePath(ABSPATH, true)) {
            throw new \RuntimeException('Refused to delete the WordPress root as a staging site: ' . $stagingSiteRootPath);
        }

        $wpConfigNames        = $this->findWpConfigNamesInStagingSiteRoot($stagingSiteRootPath);
        $originalWpRootPath   = $filesystem->getWpRootPath();
        $originalExcludePaths = $filesystem->getExcludePaths();
        $filesystem->setWpRootPath($stagingSiteRootPath)->setExcludePaths(array_map(function (string $wpConfigName): string {
            return '/' . $wpConfigName;
        }, $wpConfigNames));
        try {
            $isWalkFinished = $filesystem->delete($stagingSiteRootPath, $deleteSelf);
        } catch (\RuntimeException $exception) {
            $this->replaceWpConfigFilesWithGuardOnly($filesystem, $stagingSiteRootPath, $wpConfigNames);
            throw $exception;
        } finally {
            $filesystem->setWpRootPath($originalWpRootPath)->setExcludePaths($originalExcludePaths);
        }

        if (!$isWalkFinished) {
            return false;
        }

        if (array_diff($this->listStagingSiteRootEntries($stagingSiteRootPath), $wpConfigNames) !== []) {
            $this->replaceWpConfigFilesWithGuardOnly($filesystem, $stagingSiteRootPath, $wpConfigNames);
            return true;
        }

        return $filesystem->delete($stagingSiteRootPath, $deleteSelf);
    }









    private function replaceWpConfigFilesWithGuardOnly(Filesystem $filesystem, string $stagingSiteRootPath, array $wpConfigNames)
    {
        foreach ($wpConfigNames as $wpConfigName) {
            $wpConfigPath = trailingslashit($stagingSiteRootPath) . $wpConfigName;
            if (!is_link($wpConfigPath)) {
                $filesystem->create($wpConfigPath, $this->getGuardOnlyWpConfig());
            }
        }
    }






    private function findWpConfigNamesInStagingSiteRoot(string $stagingSiteRootPath): array
    {
        return array_values(preg_grep('/^wp-config\.php$/i', $this->listStagingSiteRootEntries($stagingSiteRootPath)));
    }






    private function listStagingSiteRootEntries(string $stagingSiteRootPath): array
    {
        if (!is_dir($stagingSiteRootPath)) {
            return [];
        }

        $entries = scandir($stagingSiteRootPath);
        if ($entries === false) {
            throw new \RuntimeException('Cannot list the staging site root: ' . $stagingSiteRootPath);
        }

        return array_diff($entries, ['.', '..']);
    }









    protected function guardWpConfigBeforeReset(Filesystem $filesystem, string $path): bool
    {
        if (!file_exists($path)) {
            return !is_link($path);
        }

        if (!is_file($path)) {
            return false;
        }

        $content = is_readable($path) ? file_get_contents($path) : false;
        if ($content === false) {
            debug_log('Cannot read staging configuration; replacing it with an unfinished-clone guard: ' . $path, 'warning');
        }

 
        $isResetGuard = $content !== false && (strpos($content, 'WPSTAGING_UNFINISHED_CLONE_GUARD_START') === false
            || strpos($content, $this->getUnfinishedResetGuardMarker()) !== false);
        $guarded = $this->addUnfinishedCloneGuardToWpConfig($content === false ? '<?php' : $content, $isResetGuard);
        if ($guarded === false) {
            return false;
        }

        if (!is_link($path) && $filesystem->create($path, $guarded)) {
            return true;
        }

 
        $temporaryPath = $path . '.wpstg-reset-' . uniqid('', true) . '.php';
        $this->liftReadOnlyAttributeFromWpConfigOnWindows($path);

        try {
            return $filesystem->create($temporaryPath, $guarded)
                && $filesystem->renameDirect($temporaryPath, $path);
        } finally {
            if (is_file($temporaryPath)) {
                $filesystem->delete($temporaryPath);
            }
        }
    }










    private function liftReadOnlyAttributeFromWpConfigOnWindows(string $path)
    {
        if (WPStaging::isWindowsOs()) {
            @chmod($path, 0644);
        }
    }







    protected function removeUnfinishedResetGuardFromWpConfig(string $wpConfigContent): string
    {
        if (strpos($wpConfigContent, $this->getUnfinishedResetGuardMarker()) === false) {
            return $wpConfigContent;
        }

        return $this->removeUnfinishedCloneGuardFromWpConfig($wpConfigContent);
    }








    protected function stagingHasSiteurl(wpdb $database, string $prefix): bool
    {
        $table = $prefix . 'options';
        if ($database->get_var($database->prepare('SHOW TABLES LIKE %s', $database->esc_like($table))) !== $table) {
            return false;
        }

        $table = str_replace('`', '``', $table);
        return (bool)$database->get_var("SELECT option_value FROM `{$table}` WHERE option_name = 'siteurl' LIMIT 1");
    }





    protected function getTextDatabaseConstantsMustFollow(string $wpConfigContent): string
    {
        if (strpos($wpConfigContent, $this->getUnfinishedCloneGuardEndMarker()) === false) {
            return '<?php';
        }

        return $this->getUnfinishedCloneGuardEndMarker();
    }






    protected function removeUnfinishedCloneGuardFromWpConfig(string $wpConfigContent): string
    {
        $wpConfigWithoutGuard = preg_replace('/\R?\/\*\* WPSTAGING_UNFINISHED_CLONE_GUARD_START \*\/.*?\/\*\* WPSTAGING_UNFINISHED_CLONE_GUARD_END \*\//s', '', $wpConfigContent);
        if ($wpConfigWithoutGuard === null) {
            throw new \RuntimeException('Can not lift the unfinished clone guard from wp-config.php.');
        }

        return $wpConfigWithoutGuard;
    }







    protected function replaceUnfinishedCloneGuardWithLiveTablesStop(string $wpConfigContent, string $expectedPrefix): string
    {
        if (strpos($wpConfigContent, 'WPSTAGING_UNFINISHED_CLONE_GUARD_START') === false) {
            return $wpConfigContent;
        }

        global $wpdb;
        $rewrittenPrefixLine = '/^' . preg_quote("\$table_prefix = '{$expectedPrefix}'; // Changed by WP STAGING", '/') . '/m';
        if (preg_match($rewrittenPrefixLine, $wpConfigContent) !== 1) {
            throw new \RuntimeException(sprintf(
                /* translators: 1: staging table prefix, 2: table-prefix line for the live site's wp-config.php */
                __('WP STAGING kept the unfinished-clone guard active because it cannot verify that the staging site\'s wp-config.php sets $table_prefix to the staging prefix %1$s. Clone and reset copy the live site\'s wp-config.php, so edit that file: replace its $table_prefix assignment with %2$s and keep it the only one, on a line of its own below every include and directly above the wp-settings.php require. Then reset this staging site, or delete it and clone again.', 'wp-staging'),
                $expectedPrefix,
                sprintf("\$table_prefix = '%s';", $wpdb->base_prefix)
            ));
        }

        $wpConfigWithStop = $this->addLiveTablesStopToWpConfig($wpConfigContent, $expectedPrefix, $wpdb->base_prefix);
        return $this->removeUnfinishedCloneGuardFromWpConfig($wpConfigWithStop);
    }











    private function addLiveTablesStopToWpConfig(string $wpConfigContent, string $expectedPrefix, string $livePrefix): string
    {
        $marker          = '/** WPSTAGING_LIVE_TABLES_STOP */';
        $wpConfigContent = preg_replace('/^.*' . preg_quote($marker, '/') . '\R?/m', '', $wpConfigContent) ?? $wpConfigContent;
        if ($expectedPrefix === $livePrefix) {
            return $wpConfigContent;
        }

        $stop = sprintf(
            "if (\${'table_prefix'} === %s) { http_response_code(503); exit(%s); } %s\n",
            var_export($livePrefix, true),
            var_export('WP STAGING stopped this staging site because it is connected to the database tables of the site it was cloned from. Something wp-config.php runs after $table_prefix, an included file or a later assignment, sets the live site\'s prefix. Move the $table_prefix line in the live site\'s wp-config.php below every include and directly above the wp-settings.php require, then reset this staging site.', true),
            $marker
        );

        preg_match_all('/^[ \t]*(?=(?:require|include)(?:_once)?[\s(]*(?:ABSPATH|__DIR__|dirname\(\s*__FILE__\s*\))\s*\.\s*[\'"]\/?wp-settings\.php)/m', $wpConfigContent, $requires, PREG_OFFSET_CAPTURE);
        if (empty($requires[0])) {
            throw new \RuntimeException(sprintf(
                /* translators: 1: require line for the live site's wp-config.php */
                __('WP STAGING kept the unfinished-clone guard active because it cannot find the wp-settings.php require in the staging site\'s wp-config.php. Clone and reset copy the live site\'s wp-config.php, so edit that file: ensure it loads wp-settings.php with %1$s on a line of its own. Then reset this staging site, or delete it and clone again.', 'wp-staging'),
                "require_once ABSPATH . 'wp-settings.php';"
            ));
        }

        $stopOffsets = [];
        foreach ($requires[0] as list($indentation, $lineOffset)) {
            $previousToken = $this->findCodeTokenBeforeWpConfigOffset($wpConfigContent, $lineOffset + strlen($indentation));
            if ($previousToken === false) {
                continue;
            }

 
            if (!in_array($previousToken, [';', '{', '}', ':', T_OPEN_TAG], true)) {
                $stopOffsets = [];
                break;
            }

            $stopOffsets[] = $lineOffset;
        }

        if (empty($stopOffsets)) {
            throw new \RuntimeException(__('WP STAGING kept the unfinished-clone guard active because it cannot place its live-tables stop safely before the wp-settings.php require in the staging site\'s wp-config.php. Clone and reset copy the live site\'s wp-config.php, so edit that file: keep the require on a line of its own, and put braces around it when an if, else or loop controls it. Then reset this staging site, or delete it and clone again.', 'wp-staging'));
        }

        if (count($stopOffsets) < $this->countWpSettingsRequires($wpConfigContent)) {
            throw new \RuntimeException(sprintf(
                /* translators: 1: require line for the live site's wp-config.php */
                __('WP STAGING kept the unfinished-clone guard active because it cannot find the wp-settings.php require in the staging site\'s wp-config.php. Clone and reset copy the live site\'s wp-config.php, so edit that file: ensure it loads wp-settings.php with %1$s on a line of its own. Then reset this staging site, or delete it and clone again.', 'wp-staging'),
                "require_once ABSPATH . 'wp-settings.php';"
            ));
        }

        foreach (array_reverse($stopOffsets) as $lineOffset) {
            $wpConfigContent = substr_replace($wpConfigContent, $stop, $lineOffset, 0);
        }

        return $wpConfigContent;
    }





    private function countWpSettingsRequires(string $wpConfigContent): int
    {
        $count     = 0;
        $inRequire = false;
        foreach (token_get_all($wpConfigContent) as $token) {
            $tokenId   = is_array($token) ? $token[0] : null;
            $tokenText = is_array($token) ? $token[1] : $token;

            if (in_array($tokenId, [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) {
                $inRequire = true;
                continue;
            }

            if (!$inRequire) {
                continue;
            }

            if (
                in_array($tokenId, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                && preg_match('/^[\'"]?\/?wp-settings\.php[\'"]?$/', $tokenText) === 1
            ) {
                $count++;
                $inRequire = false;
                continue;
            }

            if ($tokenText === ';' || $tokenId === T_CLOSE_TAG) {
                $inRequire = false;
            }
        }

        return $count;
    }






    private function findCodeTokenBeforeWpConfigOffset(string $wpConfigContent, int $offset)
    {
        $position      = 0;
        $previousToken = null;
        foreach (token_get_all($wpConfigContent) as $token) {
            if ($position >= $offset) {
                return $position === $offset ? $previousToken : false;
            }

            list($tokenId, $tokenText) = is_array($token) ? $token : [$token, $token];
            $position += strlen($tokenText);
            if (!in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $previousToken = $tokenId;
            }
        }

        return false;
    }






    private function addUnfinishedCloneGuardToWpConfig(string $wpConfigContent, bool $isResetGuard = false)
    {
        $wpConfigContent = $this->removeUnfinishedCloneGuardFromWpConfig($wpConfigContent);
        $guardPosition = $this->findPositionAfterOpeningPhpTag($wpConfigContent);
        if ($guardPosition === false) {
            return false;
        }

        return substr_replace($wpConfigContent, PHP_EOL . $this->getUnfinishedCloneGuard($isResetGuard), $guardPosition, 0);
    }







    private function findPositionAfterOpeningPhpTag(string $wpConfigContent)
    {
        $offset = 0;
        foreach (token_get_all($wpConfigContent) as $token) {
            $tokenId = is_array($token) ? $token[0] : null;
            if ($tokenId === T_OPEN_TAG) {
                return $offset + strlen(rtrim($token[1]));
            }

            if ($tokenId !== T_INLINE_HTML) {
                return false;
            }

            $offset += strlen($token[1]);
        }

        return false;
    }




    private function getGuardOnlyWpConfig(): string
    {
        return '<?php' . PHP_EOL . $this->getUnfinishedCloneGuard() . PHP_EOL;
    }





    private function getUnfinishedCloneGuard(bool $isResetGuard = false): string
    {
        return '/** WPSTAGING_UNFINISHED_CLONE_GUARD_START */' . PHP_EOL
            . ($isResetGuard ? $this->getUnfinishedResetGuardMarker() . PHP_EOL : '')
            . 'http_response_code(503);' . PHP_EOL
            . "exit('This staging site is not finished yet. Delete it or clone it again with WP STAGING.');" . PHP_EOL
            . $this->getUnfinishedCloneGuardEndMarker();
    }

 
    private function getUnfinishedResetGuardMarker(): string
    {
        return '/** WPSTAGING_UNFINISHED_RESET_GUARD */';
    }




    private function getUnfinishedCloneGuardEndMarker(): string
    {
        return '/** WPSTAGING_UNFINISHED_CLONE_GUARD_END */';
    }
}
