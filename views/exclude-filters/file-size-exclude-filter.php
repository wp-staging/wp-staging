<?php

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;

/**
 * @var string $comparison
 * @var string $bytes
 * @var string $size
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderSizeExclude For details on $comparison, $size and $bytes.
 */
?>
<div class="wpstg-exclude-row">
    <div class="wpstg-exclude-filter-name-column"><strong><?php esc_html_e('Skip file size', 'wp-staging') ?></strong></div>
    <div class="wpstg-exclude-filter-exclusion-column">
        <select class="wpstg-exclude-rule-input wpstg-file-size-exclude-select" name="wpstgFileSizeExcludeRuleCompare[]">
            <option value="<?php echo ExcludeFilter::SIZE_LESS_THAN ?>" <?php echo !empty($comparison) && $comparison === ExcludeFilter::SIZE_LESS_THAN ? "selected" : '' ?>><?php esc_html_e('LESS THAN', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::SIZE_GREATER_THAN ?>" <?php echo !empty($comparison) && $comparison === ExcludeFilter::SIZE_GREATER_THAN ? "selected" : '' ?>><?php esc_html_e('GREATER THAN', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::SIZE_EQUAL_TO ?>" <?php echo !empty($comparison) && $comparison === ExcludeFilter::SIZE_EQUAL_TO ? "selected" : '' ?>><?php esc_html_e('EXACT', 'wp-staging') ?></option>
        </select>
        <input type="number" class="wpstg-exclude-rule-input wpstg-file-size-exclude-input wpstg-textbox" name="wpstgFileSizeExcludeRuleSize[]" value="<?php echo !empty($bytes) ? Sanitize::sanitizeInt($bytes) : '0' ?>" />
        <select class="wpstg-exclude-rule-input wpstg-file-size-exclude-select-small" name="wpstgFileSizeExcludeRuleByte[]">
            <option value="<?php echo ExcludeFilter::SIZE_KB ?>" <?php echo !empty($size) && strpos($size, ExcludeFilter::SIZE_KB) !== false ? "selected" : '' ?>>KB</option>
            <option value="<?php echo ExcludeFilter::SIZE_MB ?>" <?php echo !empty($size) && strpos($size, ExcludeFilter::SIZE_MB) !== false ? "selected" : '' ?>>MB</option>
            <option value="<?php echo ExcludeFilter::SIZE_GB ?>" <?php echo !empty($size) && strpos($size, ExcludeFilter::SIZE_GB) !== false ? "selected" : '' ?>>GB</option>
        </select>
    </div>
    <div class="wpstg-exclude-filter-action-column wpstg-exclude-rule-action">
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <img class="wpstg--dashicons" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>svg/info-outline.svg" alt="info" />
            <div class='wpstg--tooltiptext has-top-arrow'>
                <?php echo sprintf(esc_html__('Exclude files by size. For example to exclude files greater than 10 MB, select %s and type %s in next input box and select %s.', 'wp-staging'), '<code class="wpstg-code">' . esc_html__('GREATER THAN', 'wp-staging') . '</code>', '<code class="wpstg-code">10</code>', '<code class="wpstg-code">MB</code>') ?>
            </div>
        </div>
        <div>
            <button type="button" class="wpstg-remove-exclude-rule" aria-label="<?php esc_attr_e('Delete rule', 'wp-staging'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"></path></svg></button>
        </div>
    </div>
</div>
