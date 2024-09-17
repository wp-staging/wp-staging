<?php

/**
 * @var string $extension
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderExclude For details on $extension.
 */

use WPStaging\Framework\Facades\Sanitize;

?>
<tr>
    <td class="wpstg-exclude-filter-name-column"><?php esc_html_e('File Extension', 'wp-staging') ?></td>
    <td class="wpstg-exclude-filter-exclusion-column">
        <input type="text" name='wpstgFileExtExcludeRule[]' class="wpstg-exclude-rule-input file-ext" value="<?php echo !empty($extension) ? Sanitize::sanitizeString($extension) : '' ?>" />
    </td>
    <td class="wpstg-exclude-filter-action-column wpstg-exclude-rule-action">
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <img class="wpstg--dashicons" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>svg/info-outline.svg" alt="info" />
            <div class='wpstg--tooltiptext has-top-arrow'>
                <?php echo sprintf(esc_html__('Exclude files by extension. For example to exclude zip files, type %s to exclude all zip files.', 'wp-staging'), '<code class="wpstg-code">zip</code>') ?>
            </div>
        </div>
        <div>
            <img class="wpstg-remove-exclude-rule" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>img/trash.svg" alt="delete">
        </div>
    </td>
</tr>
