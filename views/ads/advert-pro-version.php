<div id="wpstg-ad-top" class="wpstg-dark-alert wpstg-ad-gradient-opacity">
    <script>
      const adTopElement = document.getElementById('wpstg-ad-top');

      adTopElement?.addEventListener('click', function (event) {
        if (event.target.tagName.toLowerCase() !== 'a') {
          this.classList.toggle('wpstg-ad-top-expanded');
        }
      });

      adTopElement?.querySelector('ul')?.addEventListener('click', function (e) {
        const adTop = document.getElementById('wpstg-ad-top');
        const isExpanded = adTop.classList.contains('wpstg-ad-top-expanded');

        if (e.target.tagName.toLowerCase() === 'a' && !isExpanded) {
          e.preventDefault();
          adTop.classList.add('wpstg-ad-top-expanded');
        } else if (!isExpanded) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    </script>
    <!-- SVG arrow button -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="wpstg-ad-svg-arrow-btn">
        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 3 3m0 0 3-3m-3 3v-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div class="wpstg-ad-content">
        <span class="wpstg-ad-header"><?php esc_html_e('Backup and Migration - Become a Pro User!', 'wp-staging'); ?></span>
        <p class=""><?php esc_html_e('Explore the features below. Click on any feature to learn more!', 'wp-staging'); ?></p>
        <ul>
            <li><a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank" rel="noopener"><?php esc_html_e('Push staging sites to production site.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/how-to-disable-woocommerce-subscriptions-on-a-staging-site/" target="_blank" rel="noopener"><?php esc_html_e('Disable WooCommerce Background Scheduler to prevent subscription handling on a staging site.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/pro-features/#Create_New_Admin_Account_on_the_Staging_Site" target="_blank" rel="noopener"><?php esc_html_e('Create new admin accounts for staging sites.', 'wp-staging'); ?></a></li>
            <li><a href=" https://wp-staging.com/docs/actions-and-filters/" target="_blank" rel="noopener"><?php esc_html_e('Smart rules to exclude logs, cache files, revisions, unused plugins & themes.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/create-magic-login-links/" target="_blank" rel="noopener"><?php esc_html_e('Create magic login links to staging sites.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/synchronization-of-user-account-credentials-with-staging-site/" target="_blank" rel="noopener"><?php esc_html_e('Synchronize admin user account between production and staging site.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/how-to-create-a-scheduled-backup/" target="_blank" rel="noopener"><?php esc_html_e('Unlimited number of scheduled backup plans.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/how-to-backup-and-restore-your-wordpress-website/#Restore_the_Backup_on_the_Same_or_Another_Server_Migration" target="_blank" rel="noopener"><?php esc_html_e('Restore backups on other websites.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/#How_to_Migrate_WordPress_to_a_New_Host" target="_blank" rel="noopener"><?php esc_html_e('Migrate websites to other hosting providers.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/" target="_blank" rel="noopener"><?php esc_html_e('Move websites from one domain to another.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/docs/backup-restore-of-an-entire-multisite-network-to-another-multisite-network/" target="_blank" rel="noopener"><?php esc_html_e('Backup and clone entire WordPress Multisites.', 'wp-staging'); ?></a></li>
            <li class="wpstg-sub-ads">
                <?php
                printf(
                    esc_html__('Upload backups to cloud providers like %1$s, %2$s, %3$s, and more.', 'wp-staging'),
                    '<a href="https://wp-staging.com/docs/create-google-api-credentials-to-authenticate-to-google-drive/" target="_blank" rel="noopener">' . esc_html__('Google Drive', 'wp-staging') . '</a>',
                    '<a href="https://wp-staging.com/docs/how-to-backup-website-to-amazon-s3-bucket/" target="_blank" rel="noopener">' . esc_html__('Amazon S3', 'wp-staging') . '</a>',
                    '<a href="https://wp-staging.com/docs/how-to-backup-a-wordpress-site-transfer-backup-file-to-another-server-with-ftp-sftp/" target="_blank" rel="noopener">' . esc_html__('SFTP', 'wp-staging') . '</a>'
                );
                ?>
            </li>
            <li><a href="https://wp-staging.com/docs/how-to-backup-and-restore-your-wordpress-website/" target="_blank" rel="noopener"><?php esc_html_e('Backup & migration for wordpress.com hosted sites.', 'wp-staging'); ?></a></li>
            <li><a href="https://wp-staging.com/quality-assurance-for-wp-staging/" target="_blank" rel="noopener"><?php esc_html_e('100% code coverage through extensive unit and end-to-end testing.', 'wp-staging'); ?></a></li>
        </ul>
        <a href="https://wp-staging.com/pro-features/" target="_blank" id="wpstg-button-backup-upgrade" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet" rel="noopener"><?php esc_html_e('See All Pro Features', 'wp-staging'); ?></a>
    </div>
</div>
