<div class="wpstg-consent-modal-main-wrapper">
    <div class="wpstg-consent-modal-content">
        <div class="wpstg-consent-modal-display-flex">
            <div class="wpstg-consent-modal-install-image-block">
                <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/img/wp-staging-logo-256x256.png'); ?>"/>
            </div>
            <div class="wpstg-consent-modal-install-description-block">
                <strong class="wpstg-consent-modal-header"><?php esc_html_e('Enable security alerts', 'wp-staging') ?></strong>
                <p class="wpstg-consent-modal-install-description-text"><?php esc_html_e('Enable alerts for', 'wp-staging') ?>
                    <strong><?php esc_html_e('crucial security', 'wp-staging') ?></strong>
                    <?php esc_html_e(', and feature updates, as well as non-sensitive inspection monitoring from our plugin.', 'wp-staging') ?>
                </p>
            </div>
        </div>
        <div class="">
            <button id="wpstg-consent-modal-btn-success"><?php esc_html_e('Allow & Continue', 'wp-staging') ?></button>
        </div>
        <div id="wpstg-consent-modal-permission-list">
            <span><?php esc_html_e('Allow these permissions:', 'wp-staging') ?></span>
            <ul class="wpstg-consent-modal-install-permissions-list">
                <li><strong><?php esc_html_e('Contact:', 'wp-staging') ?></strong> <?php esc_html_e('Name and email', 'wp-staging') ?> </li>
                <li><strong><?php esc_html_e('Site information:', 'wp-staging') ?></strong> <?php esc_html_e('URL, WP version, php & server info, installed plugins & themes', 'wp-staging') ?></li>
                <li><strong><?php esc_html_e('Notifications:', 'wp-staging') ?></strong> <?php esc_html_e('Updates, announcements, no spam', 'wp-staging') ?></li>
                <li><strong><?php esc_html_e('Plugin events:', 'wp-staging') ?></strong> <?php esc_html_e('Activation, deactivation, uninstall, cloning and backup events', 'wp-staging') ?></li>
            </ul>
        </div>
        <div class="wpstg-consent-modal-install-footer">
            <span class="wpstg-consent-modal-button" id="wpstg-admin-notice-learn-more"><?php esc_html_e('Read More', 'wp-staging') ?></span>
            <span><?php esc_html_e('Monitored by ', 'wp-staging') ?><a href="https://wp-staging.com/" target="_blank"><?php esc_html_e('wp-staging.com', 'wp-staging') ?></a></span>
            <span id="wpstg-skip-activate-notice" class="wpstg-consent-modal-button"><?php esc_html_e('Skip', 'wp-staging') ?></span>
        </div>
    </div>
</div>