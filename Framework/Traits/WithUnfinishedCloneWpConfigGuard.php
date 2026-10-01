<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Filesystem\Filesystem;




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





    private function addUnfinishedCloneGuardToWpConfig(string $wpConfigContent)
    {
        $wpConfigContent = $this->removeUnfinishedCloneGuardFromWpConfig($wpConfigContent);
        $guardPosition = $this->findPositionAfterOpeningPhpTag($wpConfigContent);
        if ($guardPosition === false) {
            return false;
        }

        return substr_replace($wpConfigContent, PHP_EOL . $this->getUnfinishedCloneGuard(), $guardPosition, 0);
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




    private function getUnfinishedCloneGuard(): string
    {
        return '/** WPSTAGING_UNFINISHED_CLONE_GUARD_START */' . PHP_EOL
            . 'http_response_code(503);' . PHP_EOL
            . "exit('This staging site is not finished yet. Delete it or clone it again with WP STAGING.');" . PHP_EOL
            . $this->getUnfinishedCloneGuardEndMarker();
    }




    private function getUnfinishedCloneGuardEndMarker(): string
    {
        return '/** WPSTAGING_UNFINISHED_CLONE_GUARD_END */';
    }
}
