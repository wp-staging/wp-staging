<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\SiteInfo;

$siteInfo = WPStaging::make(SiteInfo::class);
 settings_errors(); ?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php
    require_once(WPSTG_VIEWS_DIR . (defined('WPSTGPRO_VERSION') ? 'pro/_main/header.php' : '_main/header.php'));

    $isActiveSettingsPage = true;
    require_once(WPSTG_VIEWS_DIR . '_main/main-navigation.php');
    ?>
    <div class="wpstg-loading-bar-container">
        <div class="wpstg-loading-bar"></div>
    </div>
    <div class="wpstg-tabs-container" id="wpstg-settings">

        <ul class="wpstg-nav-tab-wrapper">

            <?php
            $tabs = \WPStaging\Core\WPStaging::getInstance()->get("tabs")->get();
            if (empty($tabs['temporary-login'])) {
                $tabs['temporary-login'] = esc_html__("Temporary Logins", "wp-staging");
            }

            $activeTab = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? Sanitize::sanitizeString($_GET["tab"]) : "general";

            $currentUrl = remove_query_arg('sub-tab');
            # Loop through tabs
            foreach ($tabs as $id => $name) :
                $url = esc_url(
                    add_query_arg(
                        [
                            "settings-updated" => false,
                            "tab" => $id
                        ],
                        $currentUrl
                    )
                );

                $activeClass = ($activeTab === $id) ? " wpstg-nav-tab-active" : '';
                ?>
                <li>
                    <a href="<?php
                    echo esc_url($url) ?>" title="<?php
                    echo esc_attr($name) ?>" class="wpstg-nav-tab<?php
                    echo esc_attr($activeClass) ?>">
                        <?php
                        echo esc_html($name) ?>
                    </a>
                </li>
                <?php
                unset($url, $activeClass);
            endforeach;
            ?>
        </ul>

        <div class="wpstg-metabox-holder">
            <?php
            if ($activeTab === 'general') {
                $numberOfLoadingBars = 57;
                include(WPSTG_VIEWS_DIR . '_main/loading-placeholder.php');
            }

            if (file_exists(WPSTG_VIEWS_DIR . "pro/settings/tabs/" . $activeTab . ".php")) {
                require_once WPSTG_VIEWS_DIR . "pro/settings/tabs/" . $activeTab . ".php";
            } else {
                require_once WPSTG_VIEWS_DIR . "settings/tabs/" . $activeTab . ".php";
            }
            ?>
        </div>
    </div>
    <?php
        require_once WPSTG_VIEWS_DIR . "_main/footer.php";
    ?>
</div>

