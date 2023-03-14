<?php

use WPStaging\Framework\Facades\Sanitize;

 settings_errors(); ?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php');

    $isActiveSettingsPage = true;
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/main-navigation.php');
    ?>
    <div class="wpstg-tabs-container" id="wpstg-settings">

        <ul class="wpstg-nav-tab-wrapper">

            <?php
            $tabs = \WPStaging\Core\WPStaging::getInstance()->get("tabs")->get();
            $activeTab = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? Sanitize::sanitizeString($_GET["tab"]) : "general";

            # Loop through tabs
            foreach ($tabs as $id => $name) :
                $url = esc_url(
                    add_query_arg(
                        [
                            "settings-updated" => false,
                            "tab" => $id
                        ]
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
            <?php require_once $this->path . "views/settings/tabs/" . $activeTab . ".php"?>
        </div>
    </div>
</div>
<div class="wpstg-footer-logo" style="">
    <a href="https://wp-staging.com/tell-me-more/"><img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="140"></a>
</div>
