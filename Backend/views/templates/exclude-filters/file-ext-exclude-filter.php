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
        <input type="text" name='wpstgFileExtExcludeRule[]' class="wpstg-exclude-rule-input file-ext" value="<?php echo isset($extension) ? Sanitize::sanitizeString($extension) : '' ?>" />
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <button class="wpstg-exclusion-rule-info" type="button">i</button>
            <p class="wpstg--tooltiptext has-top-arrow"><?php echo sprintf(esc_html__('Exclude files by extension. For example to exclude zip files, type %s to exclude all zip files.', 'wp-staging'), '<code class="wpstg-code">zip</code>') ?> </p>
        </div>
    </td>
    <td class="wpstg-exclude-filter-action-column">
        <img class="wpstg-remove-exclude-rule" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>img/trash.svg" alt="">
    </td>
</tr>
