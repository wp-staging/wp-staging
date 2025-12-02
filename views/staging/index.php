<?php

/**
 * This views is used in staging site feature. The inner content of this view is changes according to the step of the staging site feature.
 * @see src/views/clone/index.php
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\CliIntegrationNotice;

// Show CLI integration notice
$cliNotice = WPStaging::make(CliIntegrationNotice::class);
$cliNotice->maybeShowCliNotice();

?>

<div id="wpstg-workflow"></div>
