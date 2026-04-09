<?php

$settings      = json_decode(json_encode(get_option('wpstg_settings', [])));
$maxFileSizeMb = isset($settings->maxFileSize) ? $settings->maxFileSize : '8';
?>
<div class="wpstg-callout wpstg-callout-info" style="margin: 15px 0 0;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
    <div class="wpstg-text-sm">
        <p class="wpstg-m-0">
            <?php
            echo wp_kses_post(sprintf(
                __('Files larger than <strong>%s MB</strong> are skipped during site processing.', 'wp-staging'),
                esc_html((string) $maxFileSizeMb)
            ));
            ?>
            <br>
            <?php
            echo wp_kses_post(sprintf(
                /* translators: %1$s is the opening link tag, %2$s is the closing link tag */
                __('You can %1$schange this limit in the settings%2$s.', 'wp-staging'),
                '<a href="' . esc_url(admin_url('admin.php?page=wpstg-settings')) . '" target="_blank" rel="noopener noreferrer">',
                '</a>'
            ));
            ?>
        </p>
    </div>
</div>
