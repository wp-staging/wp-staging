<?php

use WPStaging\Framework\Facades\Escape;

/**
 * @var string $extraDirectoriesRootPath
 */
?>
<h4 class="wpstg-selection-title wpstg-m-0 !wpstg-block">
    <?php echo esc_html__("Extra directories to copy", "wp-staging") ?>
</h4>

<textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" class="wpstg-input wpstg-mt-2 !wpstg-h-24 !wpstg-w-full" placeholder="<?php echo esc_attr(trailingslashit($extraDirectoriesRootPath) . 'custom-folder'); ?>&#10;<?php echo esc_attr(trailingslashit($extraDirectoriesRootPath) . 'uploads/custom-folder'); ?>"></textarea>
<p class="wpstg-selection-description wpstg-mt-2">
    <span>
        <?php
        echo sprintf(
            Escape::escapeHtml(__("Enter one folder path per line.<br>Folders must be located within: %s", 'wp-staging')),
            esc_html($extraDirectoriesRootPath)
        );
        ?>
    </span>
</p>
