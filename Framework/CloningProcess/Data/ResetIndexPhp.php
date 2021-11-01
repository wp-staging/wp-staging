<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;

class ResetIndexPhp extends FileCloningService
{

    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        if (!$this->isSubDir()) {
            $this->log("WP installation is not in a subdirectory! Skipping this step");
            return true;
        }

        $this->log("WP installation is in a subdirectory");
        $content = $this->readFile('index.php');

        /*
         * Before WordPress 5.4: require( dirname( __FILE__ ) . '/wp-blog-header.php' );
         * Since WordPress 5.4:  require __DIR__ . '/wp-blog-header.php';
         */
        $pattern = "/require(.*)wp-blog-header.php(.*)/";
        if (preg_match($pattern, $content, $matches)) {
            $replace = "require __DIR__ . '/wp-blog-header.php'; // " . $matches[0] . " Changed by WP-Staging";
            if (($content = preg_replace([$pattern], $replace, $content)) === null) {
                throw new FatalException("Failed to reset index.php for sub directory: regex error");
            }
        } else {
            throw new FatalException("Failed to reset index.php for sub directory. Can not find code 'require(.*)wp-blog-header.php' or require __DIR__ . '/wp-blog-header.php'; in index.php");
        }

        $this->writeFile('index.php', $content);

        return true;
    }
}
