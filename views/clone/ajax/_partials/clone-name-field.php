<?php

/**
 * Where the user names the staging site, with the slot the browser fills when the name is refused.
 *
 * @var stdClass $options
 */

use WPStaging\Framework\Facades\Sanitize;

?>
<label id="wpstg-clone-label" for="wpstg-new-clone-id">
    <input type="text" id="wpstg-new-clone-id" class="wpstg-input wpstg-input-lg"
        placeholder="<?php esc_html_e('Enter Site Name (Optional)', 'wp-staging') ?>"
        data-clone="<?php echo esc_attr($options->current); ?>"
        <?php if ($options->current !== null) {
            $siteName = isset($options->currentClone['cloneName']) ? Sanitize::sanitizeString(wpstg_urldecode($options->currentClone['cloneName'])) : $options->currentClone['directoryName'];
            echo ' value="' . esc_attr($siteName) . '"';
            echo " disabled='disabled'";
        } ?> />
</label>

<div id="wpstg-clone-id-error" class="wpstg-callout wpstg-callout-warning wpstg-mt-2" style="display:none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
    <div class="wpstg-text-sm" id="wpstg-clone-id-error-msg"></div>
</div>
