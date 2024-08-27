<div id="wpstg--modal--backup--download-modal" style="display: none">
    <div id="wpstg--modal--backup--download-inner-modal">
        <div class="wpstg--modal--download--parts-container" style="display: none">
            <div class="wpstg--modal--fetching--data wpstg-swal2-ajax-loader">
                <img src="<?php echo esc_url($urlAssets . 'img/wpstaging-icon.png'); ?>" style="width: 20px;height:20px;margin-right:20px;"/>
                <p><?php esc_html_e('Fetching backups parts...', 'wp-staging') ?></p>
            </div>
            <p class="wpstg--modal--download--text" style="display: none;text-align:left;"><?php esc_html_e('Download all available backup parts or only the ones that you want to restore or keep for later.', 'wp-staging') ?></p>
            <div class="wpstg--modal--download--parts" style="display: none">
            </div>
        </div>
    </div>
</div>
