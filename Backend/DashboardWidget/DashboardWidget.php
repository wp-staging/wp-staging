<?php

namespace WPStaging\Backend\DashboardWidget;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;

/**
 * Renders the WP Staging admin dashboard widget.
 *
 * The widget loads a curated list of WP Staging documentation articles via WordPress'
 * built-in feed loader. The feed is cached on the site for 24 hours.
 */
class DashboardWidget
{
    const WIDGET_ID          = 'wpstg_dashboard_widget';
    const FEED_URL_EN        = 'https://wp-staging.com/newsfeed/dashboard-en.xml';
    const FEED_URL_DE        = 'https://wp-staging.com/newsfeed/dashboard-de.xml';
    const FEED_CACHE_SECONDS = DAY_IN_SECONDS;
    const MAX_ARTICLES       = 5;

    /** @var Language */
    private $language;

    /**
     * @param Language $language
     */
    public function __construct(Language $language)
    {
        $this->language = $language;
    }

    /**
     * Registers the widget with WordPress.
     *
     * Hooked to `wp_dashboard_setup` by DashboardWidgetServiceProvider.
     * Users can hide the widget via the standard WordPress "Screen Options" menu.
     *
     * @return void
     */
    public function register()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_add_dashboard_widget(
            self::WIDGET_ID,
            __('WP Staging — Tips & Guides', 'wp-staging'),
            [$this, 'render']
        );
    }

    /**
     * Outputs the widget body. Called by WordPress when rendering the dashboard.
     *
     * @return void
     */
    public function render()
    {
        $items = $this->fetchArticles();

        if (empty($items)) {
            $this->renderEmptyState();
            return;
        }

        $this->renderArticles($items);
    }

    /**
     * Fetches the curated documentation feed and returns the parsed items.
     *
     * @return array<int, array{title:string, link:string, summary:string}>
     */
    private function fetchArticles()
    {
        add_filter('wp_feed_cache_transient_lifetime', [$this, 'feedCacheLifetime'], 10, 1);
        $feed = fetch_feed($this->getFeedUrl());
        remove_filter('wp_feed_cache_transient_lifetime', [$this, 'feedCacheLifetime'], 10);

        if (is_wp_error($feed) || !is_object($feed)) {
            return [];
        }

        $maxItems = $feed->get_item_quantity(self::MAX_ARTICLES);
        $rssItems = $feed->get_items(0, $maxItems);

        $articles = [];
        foreach ($rssItems as $item) {
            $title = (string)$item->get_title();
            $link  = (string)$item->get_permalink();
            if ($title === '' || $link === '') {
                continue;
            }

            $articles[] = [
                'title'   => $title,
                'link'    => $link,
                'summary' => wp_trim_words((string)$item->get_description(), 18, '&hellip;'),
            ];
        }

        return $articles;
    }

    /**
     * Returns the feed URL for the current admin locale, with plugin version and
     * multisite query parameters appended for access-log segmentation.
     *
     * @return string
     */
    private function getFeedUrl()
    {
        $baseUrl = $this->language->getLocaleLanguageCode() === 'de' ? self::FEED_URL_DE : self::FEED_URL_EN;
        $version = defined('WPSTGPRO_VERSION') ? WPSTGPRO_VERSION : (defined('WPSTG_VERSION') ? WPSTG_VERSION : '');
        $edition = WPStaging::isBasic() ? 'free' : 'pro';

        return add_query_arg(
            [
                'v'  => $version,
                'e'  => $edition,
                'ms' => is_multisite() ? '1' : '0',
            ],
            $baseUrl
        );
    }

    /**
     * Filter callback that overrides the default 12h SimplePie transient lifetime.
     *
     * @param int $seconds Default lifetime in seconds passed by WordPress.
     * @return int
     */
    public function feedCacheLifetime($seconds)
    {
        return self::FEED_CACHE_SECONDS;
    }

    /**
     * @return void
     */
    private function renderEmptyState()
    {
        ?>
        <p><?php echo esc_html__('Documentation links will appear here as soon as the feed is available.', 'wp-staging'); ?></p>
        <?php
    }

    /**
     * @param array<int, array{title:string, link:string, summary:string}> $items
     * @return void
     */
    private function renderArticles(array $items)
    {
        ?>
        <ul class="wpstg-dashboard-widget-list">
            <?php foreach ($items as $item) : ?>
                <li style="margin-bottom:10px;">
                    <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                        <strong><?php echo esc_html($item['title']); ?></strong>
                    </a>
                    <?php if ($item['summary'] !== '') : ?>
                        <div style="color:#646970;font-size:12px;margin-top:2px;">
                            <?php echo esc_html($item['summary']); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}
