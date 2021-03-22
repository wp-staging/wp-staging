<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */
?>
<div class="wpstg--modal--backup--import--filesystem">
    <button class="wpstg--backup--import--option wpstg-blue-primary" data-option="upload">
        <?php esc_html_e('GO BACK', 'wp-staging') ?>
    </button>
    <div style="margin-top: .25em;font-size:14px;">
        <?php
        echo __('Upload import file to server directory:', 'wp-staging') . '<br>';
        echo esc_html($directory->getPluginUploadsDirectory());
        ?>
    </div>
    <ul></ul>
</div>
