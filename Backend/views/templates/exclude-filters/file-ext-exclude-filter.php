<?php
/**
 * @var string $extension
 *
 * @see \WPStaging\Framework\Filesystem\Filters\ExcludeFilter::renderExclude For details on $extension.
 */
?>
<tr>
    <td class="wpstg-exclude-filter-name-column"><?php _e('File Extension', 'wp-staging') ?></td>
    <td class="wpstg-exclude-filter-exclusion-column">
        <input type="text" name='wpstgFileExtExcludeRule[]' class="wpstg-exclude-rule-input file-ext" value="<?php echo isset($extension) ? $extension : '' ?>" />
        <div class="wpstg--tooltip wpstg--exclude-rules--tooltip">
            <button class="wpstg-exclusion-rule-info" type="button">i</button>
            <p class="wpstg--tooltiptext has-top-arrow"><?php echo sprintf(__('Exclude files by extension. For example to exclude zip files, type %s to exclude all zip files.', 'wp-staging'), '<code class="wpstg-code">zip</code>') ?> </p>
        </div>
    </td>
    <td class="wpstg-exclude-filter-action-column"><button class="wpstg-remove-exclude-rule">×</button></td>
</tr>
