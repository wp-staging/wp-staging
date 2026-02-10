<?php
/**
 * CLI Integration Notice - Banner promoting WP Staging CLI tool
 *
 * This file contains only the banner/notice HTML that appears in the WP admin.
 * The modal content is loaded from src/views/cli/cli-integration-modal.php
 *
 * Variables passed from CliIntegrationNotice::maybeShowCliNotice():
 * @var bool   $isDeveloperOrHigher Whether user has Developer plan or higher
 * @var string $planName            The name of the user's license plan
 * @var array  $backups             List of available backups
 * @var string $urlAssets           URL to the assets directory
 */
?>

<div class="wpstg-banner wpstg-banner-cli" id="wpstg-cli-integration-banner" data-is-developer="<?php echo $isDeveloperOrHigher ? '1' : '0'; ?>">
    <!-- Top-right dismiss control -->
    <div class="wpstg-banner-dismiss-top">
        <button
            type="button"
            class="wpstg-btn wpstg-btn-sm wpstg-btn-ghost wpstg-banner-dismiss-btn"
            id="wpstg-cli-notice-close"
            aria-label="<?php esc_attr_e('Remind me tomorrow', 'wp-staging'); ?>"
        >
            <svg class="wpstg-btn-icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <?php esc_html_e('Later', 'wp-staging'); ?>
        </button>
        <button
            type="button"
            class="wpstg-btn wpstg-btn-sm wpstg-btn-ghost wpstg-banner-dismiss-btn"
            id="wpstg-cli-notice-hide-forever"
            aria-label="<?php esc_attr_e('Don\'t show this notice again', 'wp-staging'); ?>"
        >
            <svg class="wpstg-btn-icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18"/>
                <path d="m6 6 12 12"/>
            </svg>
            <?php esc_html_e('Dismiss', 'wp-staging'); ?>
        </button>
    </div>

    <!-- Main content area -->
    <div class="wpstg-banner-content">
        <!-- Icon box (blue tint) -->
        <div class="wpstg-icon-box wpstg-icon-box-blue wpstg-banner-icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>

        <!-- Text content -->
        <div class="wpstg-banner-text">
            <div class="wpstg-banner-header">
                <span class="wpstg-badge-new"><?php esc_html_e('NEW', 'wp-staging'); ?></span>
                <h3 class="wpstg-banner-title">
                    <?php esc_html_e('Local Docker Environments', 'wp-staging'); ?>
                </h3>
            </div>
            <p class="wpstg-banner-description">
                <?php esc_html_e('Turn backups into local Docker sites with one command. Perfect for development and testing.', 'wp-staging'); ?>
            </p>
            <?php if ($isDeveloperOrHigher) : ?>
                <p class="wpstg-banner-plan-info">
                    <?php
                    printf(
                        esc_html__('Included in your %s plan.', 'wp-staging'),
                        '<strong>' . esc_html($planName) . '</strong>'
                    );
                    ?>
                </p>
            <?php else : ?>
                <p class="wpstg-banner-plan-info">
                    <?php
                    printf(
                        esc_html__('Requires Developer plan or higher. Your plan: %s.', 'wp-staging'),
                        '<strong>' . esc_html($planName) . '</strong>'
                    );
                    ?>
                </p>
            <?php endif; ?>

            <!-- Action buttons aligned with text -->
            <div class="wpstg-banner-buttons">
                <?php if ($isDeveloperOrHigher) : ?>
                    <button type="button" class="wpstg-btn wpstg-btn-sm wpstg-btn-primary" id="wpstg-cli-install-button">
                        <svg class="wpstg-btn-icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 10v6"></path>
                            <path d="M9 13h6"></path>
                            <path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"></path>
                        </svg>
                        <?php esc_html_e('Create Local Site', 'wp-staging'); ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpstg-license')); ?>" class="wpstg-btn wpstg-btn-sm wpstg-btn-primary">
                        <?php esc_html_e('Upgrade Plan', 'wp-staging'); ?>
                    </a>
                <?php endif; ?>
                <a href="https://wp-staging.com/docs/set-up-wp-staging-cli/" target="_blank" rel="noreferrer noopener" class="wpstg-btn wpstg-btn-sm wpstg-btn-ghost wpstg-banner-learn-more">
                    <?php esc_html_e('Learn More', 'wp-staging'); ?>
                    <svg class="wpstg-btn-icon-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Include the modal content (hidden until triggered by JS)
include __DIR__ . '/../cli/cli-integration-modal.php';
?>
