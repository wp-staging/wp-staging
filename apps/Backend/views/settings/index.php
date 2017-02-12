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
        $tabs       = $this->di->get("tabs")->get();
        $activeTab  = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? $_GET["tab"] : "general";

        # Loop through tabs
        foreach ($tabs as $id => $name):
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
        ?>
    </ul>
    <h2 class="nav-tab-wrapper"></h2>

    <div id="tab_container" class="tab_container">
        <div class="panel-container">
            <form method="post" action="options.php">
                <?php
                settings_fields("wpstg_settings");

                foreach ($tabs as $id => $name):
                    $form = $this->di->get("forms")->get($id);

                    if (null === $form)
                    {
                        continue;
                    }
                    ?>
                    <div id="<?php echo $id?>__wpstg_header">
                        <table class="form-table">
                            <thead>
                                <tr class="row">
                                    <th class="row th" colspan="2">
                                        <div class="col-title">
                                            <strong><?php echo $name?></strong>
                                            <span class="description"></span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[wpstg_query_limit]")?>
                                            <span class="description">
                                                Number of DB rows, that will be copied within one ajax request.
                                                The higher the value the faster the database copy process.
                                                To find out the highest possible values try a high value like 1.000 or more and decrease it
                                                until you get no more errors during copy process.
                                                <br>
                                                <strong> Default: 100 </strong>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[wpstg_query_limit]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[wpstg_batch_size]")?>
                                            <span class="description">
                                                Buffer size for the file copy process in megabyte.
                                                The higher the value the faster large files will be copied.
                                                To find out the highest possible values try a high one and lower it until
                                                you get no errors during file copy process. Usually this value correlates directly
                                                with the memory consumption of php so make sure that
                                                it does not exceed any php.ini max_memory limits.
                                                <br>
                                                <strong>Default:</strong> 2
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[wpstg_batch_size]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[wpstg_cpu_load]")?>
                                            <span class="description">
                                                Using high will result in fast as possible processing but the cpu load
                                                increases and it's also possible that staging process gets interrupted because of too many ajax requests
                                                (e.g. <strong>authorization error</strong>).
                                                Using a lower value results in lower cpu load on your server but also slower staging site creation.
                                                <br>
                                                <strong>Default: </strong> Medium
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[wpstg_cpu_load]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[optimizer]")?>
                                            <span class="description">
                                                Select the plugins that should be disabled during build process of the staging site.
                                                Some plugins slow down the copy process and add overhead to each request, requiring extra CPU and memory consumption.
                                                Some of them can interfere with cloning process and cause them to fail, so we recommend to select all plugins here.

                                                <br><br>
                                                <strong>Note:</strong> This does not disable plugins on your staging site. You have to disable them there separately.
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[optimizer]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[disable_admin_login]")?>
                                            <span class="description">
                                                Use this option only if you are using a custom login page and not the default login.php.
                                                If you enable this option you are allowing everyone including search engines
                                                to see your staging site, so you have to create a custom authentication like using .htaccess
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[disable_admin_login]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[wordpress_subdirectory]")?>
                                            <span class="description">
                                                Use this option when you gave wordpress its own subdirectory.
                                                if you enable this, WP Staging will reset the index.php of the clone site to the originally one.
                                                <br>
                                                <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Read more in the WordPress Codex</a>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[wordpress_subdirectory]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[debug_mode]")?>
                                            <span class="description">
                                                This will enable an extended debug mode which creates additional entries
                                                in <strong>wp-content/wp-staging/logs</strong>.
                                                Please enable this when we ask you to do so.
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[debug_mode]")?>
                                    </td>
                                </tr>

                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php echo $form->label("wpstg_settings[uninstall_on_delete]")?>
                                            <span class="description">
                                                Check this box if you like WP Staging to completely remove all of its data when the plugin is deleted.
                                                This will not remove staging sites files or database tables.
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $form->render("wpstg_settings[uninstall_on_delete]")?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php
                endforeach;
                // Show submit button any tab but add-ons
                if ($activeTab !== "add-ons")
                {
                    submit_button();
                }
                unset($tabs);
                ?>
            </form>
        </div>
    </div>
</div>
