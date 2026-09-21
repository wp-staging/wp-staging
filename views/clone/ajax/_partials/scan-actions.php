<?php

/**
 * The buttons closing the selection step: going back, starting the copy, and the disk space estimate.
 *
 * @var stdClass $options
 */

use WPStaging\Backend\Modules\Jobs\Job;

?>
<div class="wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-flex-wrap">
    <button type="button" class="wpstg-prev-step-link wpstg-btn wpstg-btn-md wpstg-btn-secondary">
        <svg class="wpstg-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <?php esc_html_e("Back", "wp-staging") ?>
    </button>

    <?php
    $label  = esc_html__("Start Cloning", "wp-staging");
    $action = 'wpstg_cloning';
    $btnId  = 'wpstg-start-cloning';
    if ($options->current !== null && $options->mainJob === Job::UPDATE) {
        $label  = esc_html__("Update Staging Site", "wp-staging");
        $action = 'wpstg_update';
        $btnId  = 'wpstg-start-updating';
    }
    ?>

    <button type="button" id="<?php echo esc_attr($btnId); ?>" class="wpstg-next-step-link wpstg-btn wpstg-btn-md wpstg-btn-primary" data-action="<?php echo esc_attr($action); ?>" data-url="<?php echo esc_attr(isset($options->currentClone['url']) ? $options->currentClone['url'] : ''); ?>"><?php echo esc_html($label); ?></button>

    <a href="#" id="wpstg-check-space" class="wpstg-btn wpstg-btn-ghost"><?php esc_html_e('Check required disk space', 'wp-staging'); ?></a>
    <div id="wpstg-disk-space-result" class="wpstg-callout wpstg-callout-warning wpstg-mt-2" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>
        </svg>
        <div class="wpstg-text-sm" id="wpstg-disk-space-result-msg"></div>
    </div>
</div>
