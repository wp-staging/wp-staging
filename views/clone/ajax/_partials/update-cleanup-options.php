<?php

/**
 * Choices offered only when an existing staging site is being updated: what to wipe before the copy starts.
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var bool                                 $uploadsSymlinked
 */

use WPStaging\Framework\Facades\UI\Checkbox;

?>
<p class="wpstg--advanced-settings--checkbox">
    <label for="wpstg-clean-plugins-themes"><?php esc_html_e('Clean Plugins/Themes', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg-clean-plugins-themes', 'wpstg-clean-plugins-themes', 'true'); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Delete all plugins & themes on staging site before starting update process.', 'wp-staging'); ?>
        </span>
    </span>
</p>
<p class="wpstg--advanced-settings--checkbox">
    <label for="wpstg-clean-uploads"><?php esc_html_e('Clean Uploads', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg-clean-uploads', 'wpstg-clean-uploads', 'true'); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php esc_html_e('Delete entire folder wp-content/uploads on staging site including all images before starting update process.', 'wp-staging'); ?>
            <?php echo $uploadsSymlinked ? "<br/><br/><b>" . esc_html__("Note: This option is disabled as uploads directory is symlinked", "wp-staging") . "</b>" : '' ?>
        </span>
    </span>
</p>
