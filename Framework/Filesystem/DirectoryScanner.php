<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Filesystem;

class DirectoryScanner
{
    /**
     * @param string $directory
     * @param array  $excludedDirectories
     *
     * @return array
     */
    public function scan($directory, array $excludedDirectories = [])
    {
        try {
            $it = new \DirectoryIterator($directory);
        } catch (\Exception $e) {
            return [];
        }

        /*
         * Normalize the excluded directories to maximize the chances
         * of matching a excluded directory if we mean to.
         *
         * Exclusion: /var/www/single/wp-content/plugins/WooCommerce/
         * Becomes:   /var/www/single/wp-content/plugins/woocommerce
         *
         * So that any of these exclusions matches:
         * /var/www/single/wp-content/plugins/woocommerce
         * /var/www/single/wp-content/plugins/woocommerce/
         * /var/www/single/wp-content/plugins/WooCommerce
         * /var/www/single/wp-content/plugins/WooCommerce/
         */
        $excludedDirectories = array_map(function ($item) {
            return untrailingslashit(strtolower($item));
        }, $excludedDirectories);

        /**
         * Allow user to filter the excluded directories in a site export.
         * @todo Should we add UI for this?
         */
        $excludedDirectories = (array)apply_filters('wpstg.export.site.directory.excluded', $excludedDirectories);

        $dirs = [];

        /** @var \SplFileInfo $item */
        foreach ($it as $item) {
            if ($item->isDir() && $item->getFilename() != "." && $item->getFilename() != "..") {
                if (!in_array(untrailingslashit(strtolower($item->getRealPath())), $excludedDirectories)) {
                    $dirs[] = $item->getRealPath();
                }
            }
        }

        return $dirs;
    }
}
