<div class="wpstg_admin">
    <span class="wp-staginglogo">
        <img src="<?php echo $this->url . "img/logo_clean_small_212_25.png"?>">
    </span>

    <span class="wpstg-version">
        <?php if (\WPStaging\WPStaging::SLUG === "wp-staging-pro") echo "Pro" ?> Version <?php echo \WPStaging\WPStaging::VERSION ?>
    </span>

    <div class="wpstg-header">
        <iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwordpress.org%2Fplugins%2Fwp-staging%2F&amp;width=100&amp;layout=standard&amp;action=like&amp;show_faces=false&amp;share=true&amp;height=35&amp;appId=449277011881884" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:96px; height:20px;" allowTransparency="true"></iframe>
        <a class="twitter-follow-button" href="https://twitter.com/wpstg" data-size="small" id="twitter-wjs" style="display: block;">Follow @wpstg</a>&nbsp;
        <a class="twitter-follow-button" href="https://twitter.com/renehermenau" data-size="small" id="twitter-wjs" style="display: block;">Follow @renehermenau</a>&nbsp;
        <a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one%20click%20WordPress%20testing%20site&via=wpstg" class="twitter-hashtag-button" data-size="small" data-related="ReneHermenau" data-url="https://wordpress.org/plugins/wp-staging/" data-dnt="true">Tweet #wpstaging</a>
    </div>

    <ul class="nav-tab-wrapper">
        <?php
        $tabs       = $this->di->get("admin-tabs")->get();
        $activeTab  = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? $_GET["tab"] : "general";

        # Loop through tabs
        foreach ($this->di->get("admin-tabs") as $id => $name):
            $url = esc_url(add_query_arg(array(
                "settings-updated"  => false,
                "tab"               => $id
            )));

            $activeClass = ($activeTab === $id) ? " nav-tab-active" : '';
        ?>
            <li>
                <a href="<?php echo $url?>" title="<?php echo esc_attr($name)?>" class="nav-tab<?php echo $activeClass?>">
                    <?php echo esc_html($name)?>
                </a>
            </li>
        <?php
            unset($url, $activeClass);
        endforeach;
        unset($tabs);
        ?>
    </ul>


    <div id="tab_container" class="tab_container">
        <div class="panel-container">
            <form method="post" action="options.php">
                <?php
                settings_fields("wpstg_settings");

                // Show submit button any tab but add-ons
                if ($activeTab !== "add-ons")
                {
                    submit_button();
                }
                ?>
            </form>
        </div>
    </div>
</div>
