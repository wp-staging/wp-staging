<?php

/**
 * Newsfeed view template
 *
 * Renders the newsfeed section in the WP Staging admin sidebar.
 * Data is fetched from remote JSON files and rendered using this template.
 * Collapsible state is managed via localStorage in JavaScript.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Newsfeed\NewsfeedProvider;

$provider = WPStaging::make(NewsfeedProvider::class);
$data     = $provider->getNewsfeedData();

if (empty($data)) {
    return;
}

$proFeatureCount = $provider->countProFeatures($data);

/**
 * Render tip text with embedded link
 *
 * @param array $tip Tip data with text, link, and link_text
 * @return string Rendered HTML
 */
$renderTipText = function (array $tip): string {
    if (empty($tip['link']) || empty($tip['link_text'])) {
        return esc_html($tip['text'] ?? '');
    }

    $link = '<a href="' . esc_url($tip['link']) . '" target="_blank">' . esc_html($tip['link_text']) . '</a>';
    $text = $tip['text'] ?? '';

    // Replace {link} placeholder with actual link
    if (strpos($text, '{link}') !== false) {
        $parts = explode('{link}', $text);
        return esc_html($parts[0]) . $link . esc_html($parts[1] ?? '');
    }

    // If no placeholder, append link at end
    return esc_html($text) . ' ' . $link;
};
?>

    <div class="wpstg-newsfeed-container wpstg-u-block" id="wpstg-newsfeed-container">
        <!-- Unified Header (Always Visible, Clickable) -->
        <div class="wpstg-newsfeed-collapsed-header" data-version="<?php echo esc_attr($data['version']); ?>" role="button" aria-expanded="true">
            <div class="wpstg-newsfeed-collapsed-left">
                <span class="wpstg-newsfeed-version">v.<?php echo esc_html($data['version']); ?></span>
                <span class="wpstg-newsfeed-date"><?php
                    $timestamp = !empty($data['date']) ? strtotime($data['date']) : false;
                    echo esc_html($timestamp !== false ? date_i18n('M j, Y', $timestamp) : __('Unknown date', 'wp-staging'));
                ?></span>
                <span class="wpstg-newsfeed-new-badge" style="display: none;"><?php esc_html_e('NEW', 'wp-staging'); ?></span>
                <?php if (!empty($data['intro']['description'])) : ?>
                <span class="wpstg-newsfeed-intro-brief">
                    <?php echo esc_html($data['intro']['description']); ?>
                </span>
                <?php endif; ?>
                <?php if ($proFeatureCount > 0) : ?>
                    <span class="wpstg-newsfeed-pro-teaser">✨ <?php
                        // translators: %d is the number of Pro features
                        printf(esc_html(_n('%d new Pro feature', '%d new Pro features', $proFeatureCount, 'wp-staging')), absint($proFeatureCount));
                    ?></span>
                <?php endif; ?>
            </div>
            <div class="wpstg-newsfeed-collapsed-right">
                <span class="wpstg-newsfeed-toggle-link">
                    <span class="wpstg-toggle-text"><?php esc_html_e('View Details', 'wp-staging'); ?></span>
                    <span class="wpstg-toggle-icon">‹</span>
                </span>
            </div>
        </div>

        <!-- Collapsible Content -->
        <div class="wpstg-newsfeed-content" style="display: none;">
            <?php if (!empty($data['highlights'])) : ?>
            <div class="wpstg-newsfeed-section wpstg-newsfeed-highlights">
                <h3 class="wpstg-newsfeed-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3498db" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-newsfeed-icon"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path><path d="M20 3v4"></path><path d="M22 5h-4"></path><path d="M4 17v2"></path><path d="M5 18H3"></path></svg> <?php esc_html_e('New & Improved', 'wp-staging'); ?></h3>
                <ul class="wpstg-newsfeed-list">
                    <?php foreach ($data['highlights'] as $item) : ?>
                    <li class="wpstg-newsfeed-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-newsfeed-check-icon"><path d="M20 6 9 17l-5-5"></path></svg>
                        <span class="wpstg-newsfeed-item-text">
                            <?php if (!empty($item['pro_only'])) : ?>
                                <span class="wpstg-newsfeed-badge-pro">PRO</span>
                            <?php endif; ?>
                            <strong><?php echo esc_html(rtrim($item['title'] ?? '', '.')); ?></strong><?php
                            if (!empty($item['description'])) :
                                ?>: <?php echo esc_html($item['description']);
                            endif;
                            ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['fixes'])) : ?>
            <div class="wpstg-newsfeed-section wpstg-newsfeed-fixes">
                <h3 class="wpstg-newsfeed-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-newsfeed-icon"><path d="m8 2 1.88 1.88"></path><path d="M14.12 3.88 16 2"></path><path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"></path><path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6"></path><path d="M12 20v-9"></path><path d="M6.53 9C4.6 8.8 3 7.1 3 5"></path><path d="M6 13H2"></path><path d="M3 21c0-2.1 1.7-3.9 3.8-4"></path><path d="M20.97 5c0 2.1-1.6 3.8-3.5 4"></path><path d="M22 13h-4"></path><path d="M17.2 17c2.1.1 3.8 1.9 3.8 4"></path></svg> <?php esc_html_e('Bug Fixes', 'wp-staging'); ?></h3>
                <ul class="wpstg-newsfeed-grid">
                    <?php foreach ($data['fixes'] as $fix) : ?>
                    <li>
                        <?php if (!empty($fix['pro_only'])) : ?>
                            <span class="wpstg-newsfeed-badge-pro">PRO</span>
                        <?php endif; ?>
                        <?php echo esc_html($fix['description'] ?? ''); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (WPStaging::isBasic()) : ?>
            <!-- Upgrade Banner (Free Version Only) -->
            <div class="wpstg-newsfeed-upgrade-banner">
                <span class="wpstg-upgrade-banner-text">
                    <?php if ($proFeatureCount > 0) : ?>
                    ✨ <?php
                        // translators: %d is the number of Pro features
                        printf(esc_html__('Unlock %d Pro features', 'wp-staging'), absint($proFeatureCount));
                    ?>
                    <?php else : ?>
                    ✨ <?php esc_html_e('Unlock all Pro features', 'wp-staging'); ?>
                    <?php endif; ?>
                </span>
                <a href="https://wp-staging.com/#pricing" target="_blank" rel="noopener" class="wpstg-upgrade-banner-btn">
                    <?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer (Always Visible) -->
        <div class="wpstg-newsfeed-footer">
            <?php if (!empty($data['tips'])) : ?>
            <div class="wpstg-newsfeed-tips">
                <?php foreach ($data['tips'] as $tip) : ?>
                <div class="wpstg-newsfeed-tip-item">
                    <strong><?php echo esc_html(($tip['icon'] ?? '') . ' ' . ($tip['label'] ?? '')); ?>:</strong>
                    <?php
                    // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in $renderTipText
                    echo $renderTipText($tip);
                    ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="wpstg-newsfeed-history-link">
                <a href="<?php echo esc_url($data['changelog_url'] ?? 'https://wp-staging.com/wp-staging-pro-changelog/'); ?>" target="_blank">
                    <?php esc_html_e('View full changelog history', 'wp-staging'); ?> &rarr;
                </a>
            </div>
        </div>
    </div>
