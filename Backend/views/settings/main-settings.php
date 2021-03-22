<?php settings_errors(); ?>
<div class="wpstg_admin">
    <?php require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php'); ?>

    <div class="wpstg-tabs-container" id="wpstg-settings">
        <ul class="nav-tab-wrapper">
            <?php
            $tabs = \WPStaging\Core\WPStaging::getInstance()->get("tabs")->get();
            $activeTab = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? $_GET["tab"] : "general";

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

                $activeClass = ($activeTab === $id) ? " nav-tab-active" : '';
                ?>
                <li>
                    <a href="<?php
                    echo $url ?>" title="<?php
                    echo esc_attr($name) ?>" class="nav-tab<?php
                    echo $activeClass ?>">
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
