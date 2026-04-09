<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\SiteInfo;

$siteInfo = WPStaging::make(SiteInfo::class);
 settings_errors(); ?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php
    require_once(WPSTG_VIEWS_DIR . (defined('WPSTGPRO_VERSION') ? 'pro/_main/header.php' : '_main/header.php'));

    $isActiveSettingsPage   = true;
    $wpstgDeferCompatNotice = true;
    require_once(WPSTG_VIEWS_DIR . '_main/main-navigation.php');
    ?>
    <div class="wpstg-loading-bar-container">
        <div class="wpstg-loading-bar"></div>
    </div>
    <?php
    $tabs = \WPStaging\Core\WPStaging::getInstance()->get("tabs")->get();
    if (empty($tabs['temporary-login'])) {
        $tabs['temporary-login'] = esc_html__("Temporary Logins", "wp-staging");
    }

    if (empty($tabs['remote-sync-settings'])) {
        $tabs['remote-sync-settings'] = esc_html__("Remote Sync Connection Key", "wp-staging");
    }

    $activeTab  = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? Sanitize::sanitizeString($_GET["tab"]) : "general";
    $currentUrl = remove_query_arg('sub-tab');
    ?>
    <div class="wpstg-settings-layout" id="wpstg-settings">
        <aside class="wpstg-settings-sidebar">
            <span class="wpstg-settings-sidebar-label"><?php esc_html_e('Settings', 'wp-staging'); ?></span>
            <nav class="wpstg-settings-sidebar-nav">
                <?php foreach ($tabs as $id => $name) :
                    $url = esc_url(
                        add_query_arg(
                            [
                                "settings-updated" => false,
                                "tab"              => $id,
                            ],
                            $currentUrl
                        )
                    );
                    $activeClass = ($activeTab === $id) ? ' wpstg-settings-sidebar-item--active' : '';
                    ?>
                    <a href="<?php echo esc_url($url); ?>"
                       title="<?php echo esc_attr($name); ?>"
                       class="wpstg-settings-sidebar-item<?php echo esc_attr($activeClass); ?>">
                        <?php echo wp_kses($name, ['br' => []]); ?>
                    </a>
                    <?php
                    unset($url, $activeClass);
                endforeach; ?>
            </nav>
        </aside>
        <div class="wpstg-settings-content-area">
            <?php
            $containerClass = '';
            if ($activeTab === 'general') {
                $containerClass = 'wpstg-settings-container';
            }
            ?>
            <div class="wpstg-metabox-holder <?php echo esc_html($containerClass); ?>">
                <?php
                if (file_exists(WPSTG_VIEWS_DIR . "pro/settings/tabs/" . $activeTab . ".php")) {
                    require_once WPSTG_VIEWS_DIR . "pro/settings/tabs/" . $activeTab . ".php";
                } else {
                    require_once WPSTG_VIEWS_DIR . "settings/tabs/" . $activeTab . ".php";
                }
                ?>
            </div>
        </div>
    </div>
    <?php
        require_once WPSTG_VIEWS_DIR . "_main/footer.php";
    ?>
</div>

