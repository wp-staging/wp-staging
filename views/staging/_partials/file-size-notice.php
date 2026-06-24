<?php

$settings      = json_decode(json_encode(get_option('wpstg_settings', [])));
$maxFileSizeMb = isset($settings->maxFileSize) ? $settings->maxFileSize : '8';
?>
<div class="wpstg-callout wpstg-callout-info wpstg-mt-4 wpstg-rounded-lg wpstg-px-4 wpstg-py-3">
    <svg class="wpstg-mt-0.5 wpstg-h-5 wpstg-w-5 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
    <div class="wpstg-text-sm wpstg-leading-6">
        <p class="wpstg-m-0">
            <?php
            echo wp_kses_post(sprintf(
                /* translators: 1: file size in MB, 2: opening link tag, 3: closing link tag */
                __('Files larger than <strong>%1$s MB</strong> are currently excluded from cloning. %2$sChange file size limit%3$s', 'wp-staging'),
                esc_html((string) $maxFileSizeMb),
                '<a href="' . esc_url(admin_url('admin.php?page=wpstg-settings')) . '" target="_blank" rel="noopener noreferrer">',
                '</a>'
            ));
            ?>
        </p>
    </div>
</div>
