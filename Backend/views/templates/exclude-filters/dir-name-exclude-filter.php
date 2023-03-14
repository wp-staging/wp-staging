<?php

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;

/**
 * @var string $rule
 * @var string $name
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderExclude For details on $rule and $name.
 */
?>
<tr>
    <td class="wpstg-exclude-filter-name-column"><?php esc_html_e('Folder Name', 'wp-staging') ?></td>
    <td class="wpstg-exclude-filter-exclusion-column">
        <select class="wpstg-exclude-rule-input wpstg-path-exclude-select" name="wpstgDirNameExcludeRulePos[]">
            <option value="<?php echo ExcludeFilter::NAME_BEGINS_WITH ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_BEGINS_WITH ? 'selected' : '' ?>><?php esc_html_e('BEGINS WITH', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_ENDS_WITH ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_ENDS_WITH ? 'selected' : '' ?>><?php esc_html_e('ENDS WITH', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_EXACT_MATCHES ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_EXACT_MATCHES ? 'selected' : '' ?>><?php esc_html_e('EXACT MATCHES', 'wp-staging') ?></option>
            <option value="<?php echo ExcludeFilter::NAME_CONTAINS ?>" <?php echo isset($rule) && $rule === ExcludeFilter::NAME_CONTAINS ? 'selected' : '' ?>><?php esc_html_e('CONTAINS', 'wp-staging') ?></option>
        </select>
        <input type="text" class="wpstg-exclude-rule-input" name="wpstgDirNameExcludeRulePath[]" value="<?php echo isset($name) ? Sanitize::sanitizeString($name) : '' ?>" />
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <button class="wpstg-exclusion-rule-info" type="button">i</button>
            <p class="wpstg--tooltiptext has-top-arrow"><?php echo sprintf(esc_html__('Exclude folders by name. For example to exclude all folder with name node_modules, select %s and type %s in the input box.', 'wp-staging'), '<code class="wpstg-code">' . esc_html__('EXACT MATCHES', 'wp-staging') . '</code>', '<code class="wpstg-code">node_modules</code>') ?>
            </p>
        </div>
    </td>
    <td class="wpstg-exclude-filter-action-column">
        <img class="wpstg-remove-exclude-rule" src="<?php echo esc_url(trailingslashit(WPSTG_PLUGIN_URL)) . 'assets/'; ?>img/trash.svg" alt="">
    </td>
</tr>
