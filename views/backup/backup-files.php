<?php

use WPStaging\Core\WPStaging;
use WPStaging\Backup\Service\DirectoryExplorer\Scan;

$timeFormatOption = get_option('time_format');

/** @var Scan */
$scan = WPStaging::make(Scan::class);
$scan->addStagingSitesDirsToDisabledDirs();

?>
<div id="wpstg-directories-listing">
    <?php echo $scan->listDirectoryForBackup(); // phpcs:ignore ?>
</div>
