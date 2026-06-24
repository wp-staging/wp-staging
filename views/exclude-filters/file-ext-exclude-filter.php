<?php

/**
 * @var string $extension
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderExclude For details on $extension.
 */

use WPStaging\Framework\Facades\Sanitize;

?>
<div class="wpstg-exclude-row">
    <div class="wpstg-exclude-filter-name-column"><strong><?php esc_html_e('Skip extension', 'wp-staging') ?></strong></div>
    <div class="wpstg-exclude-filter-exclusion-column">
        <input type="text" name='wpstgFileExtExcludeRule[]' class="wpstg-exclude-rule-input file-ext" value="<?php echo !empty($extension) ? Sanitize::sanitizeString($extension) : '' ?>" />
    </div>
    <div class="wpstg-exclude-filter-action-column wpstg-exclude-rule-action">
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <img class="wpstg--dashicons" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>svg/info-outline.svg" alt="info" />
            <div class='wpstg--tooltiptext has-top-arrow'>
                <?php echo sprintf(esc_html__('Exclude files by extension. For example to exclude zip files, type %s to exclude all zip files.', 'wp-staging'), '<code class="wpstg-code">zip</code>') ?>
            </div>
        </div>
        <div>
            <button type="button" class="wpstg-remove-exclude-rule" aria-label="<?php esc_attr_e('Delete rule', 'wp-staging'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"></path></svg></button>
        </div>
    </div>
</div>
