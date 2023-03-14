<?php

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;

/**
 * @var string $comparison
 * @var int $bytes
 * @var string $size
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderSizeExclude For details on $comparison, $size and $bytes.
 */
?>
<tr>
    <td class="wpstg-exclude-filter-name-column"><?php esc_html_e('File Size', 'wp-staging') ?></td>
    <td class="wpstg-exclude-filter-exclusion-column">
        <select class="wpstg-exclude-rule-input wpstg-file-size-exclude-select" name="wpstgFileSizeExcludeRuleCompare[]">
            <option value="<?php echo ExcludeFilter::SIZE_LESS_THAN ?>" <?php echo isset($comparison) && $comparison === ExcludeFilter::SIZE_LESS_THAN ? "selected" : '' ?>><?php esc_html_e('LESS THAN', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::SIZE_GREATER_THAN ?>" <?php echo isset($comparison) && $comparison === ExcludeFilter::SIZE_GREATER_THAN ? "selected" : '' ?>><?php esc_html_e('GREATER THAN', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::SIZE_EQUAL_TO ?>" <?php echo isset($comparison) && $comparison === ExcludeFilter::SIZE_EQUAL_TO ? "selected" : '' ?>><?php esc_html_e('EXACT', 'wp-staging') ?></option>
        </select>
        <input type="number" class="wpstg-exclude-rule-input wpstg-file-size-exclude-input wpstg-textbox" name="wpstgFileSizeExcludeRuleSize[]" value="<?php echo isset($bytes) ? Sanitize::sanitizeInt($bytes) : '0' ?>" />
        <select class="wpstg-exclude-rule-input wpstg-file-size-exclude-select-small" name="wpstgFileSizeExcludeRuleByte[]">
            <option value="<?php echo ExcludeFilter::SIZE_KB ?>" <?php echo isset($size) && strpos($size, ExcludeFilter::SIZE_KB) !== false ? "selected" : '' ?>>KB</option>
            <option value="<?php echo ExcludeFilter::SIZE_MB ?>" <?php echo isset($size) && strpos($size, ExcludeFilter::SIZE_MB) !== false ? "selected" : '' ?>>MB</option>
            <option value="<?php echo ExcludeFilter::SIZE_GB ?>" <?php echo isset($size) && strpos($size, ExcludeFilter::SIZE_GB) !== false ? "selected" : '' ?>>GB</option>
        </select>
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <button class="wpstg-exclusion-rule-info" type="button">i</button>
            <p class="wpstg--tooltiptext has-top-arrow"><?php echo sprintf(esc_html__('Exclude files by size. For example to exclude files greater than 10 MB, select %s and type %s in next input box and select %s.', 'wp-staging'), '<code class="wpstg-code">' . esc_html__('GREATER THAN', 'wp-staging') . '</code>', '<code class="wpstg-code">10</code>', '<code class="wpstg-code">MB</code>') ?>
            </p>
        </div>
    </td>
    <td class="wpstg-exclude-filter-action-column">
        <img class="wpstg-remove-exclude-rule" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>img/trash.svg" alt="">
    </td>
</tr>
