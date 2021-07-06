<?php

use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;

/**
 * @var string $rule
 * @var string $name
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderExclude For details on $rule and $name.
 */
?>
<tr>
    <td class="wpstg-exclude-filter-name-column"><?php _e('File Name', 'wp-staging') ?></td>
    <td class="wpstg-exclude-filter-exclusion-column">
        <select class="wpstg-exclude-rule-input wpstg-path-exclude-select" name="wpstgFileNameExcludeRulePos[]">
            <option value="<?php echo ExcludeFilter::NAME_BEGINS_WITH ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_BEGINS_WITH ? 'selected' : '' ?>><?php _e('BEGINS WITH', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_ENDS_WITH ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_ENDS_WITH ? 'selected' : '' ?>><?php _e('ENDS WITH', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_EXACT_MATCHES ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_EXACT_MATCHES ? 'selected' : '' ?>><?php _e('EXACT MATCHES', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_CONTAINS ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_CONTAINS ? 'selected' : '' ?>><?php _e('CONTAINS', 'wp-staging') ?></option>
        </select>
        <input type="text" class="wpstg-exclude-rule-input" name="wpstgFileNameExcludeRulePath[]" value="<?php echo isset($name) ? $name : '' ?>" />
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <button class="wpstg-exclusion-rule-info" type="button">i</button>
            <p class="wpstg--tooltiptext has-top-arrow"><?php echo sprintf(__('Exclude files by name. For example to exclude all files which have %s at the end of the name, select %s and type %s in the input box.', 'wp-staging'), '<code class="wpstg-code">-class</code>', '<code class="wpstg-code">' . __('ENDS WITH', 'wp-staging') . '</code>', '<code class="wpstg-code">-class</code>') ?>
            </p>
        </div>
    </td>
    <td class="wpstg-exclude-filter-action-column"><button class="wpstg-remove-exclude-rule">×</button></td>
</tr>
