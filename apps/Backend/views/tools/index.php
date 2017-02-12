<div class="wrap" id="wpstg-tools">
    <ul class="nav-tab-wrapper">
        <?php
        $tabs       = $this->di->get("tabs")->get();
        $activeTab  = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? $_GET["tab"] : "import_export";

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

    <div class="metabox-holder">

        <!-- Export -->
        <div class="postbox">
            <h3>
                <span>
                    <?php _e("Export Settings", "wpstg")?>
                </span>
            </h3>

            <div class="inside">
                <p>
                    <?php _e(
                        "Export the WP-Staging settings for this site as a .json file. ".
                        "This allows you to easily import the configuration into another site.",
                        "wpstg"
                    )?>
                </p>

                <form method="post" action="<?php echo admin_url("admin.php?page=wpstg-tools&amp;tab=import_export")?>">
                    <p><input type="hidden" name="wpstg-action" value="export_settings" /></p>
                    <p>
                        <?php wp_nonce_field("wpstg_export_nonce", "wpstg_export_nonce")?>
                        <?php submit_button(__("Export", "wpstg"), "primary", "submit", false)?>
                    </p>
                </form>
            </div>
        </div>
        <!-- /Export -->

        <!-- Import -->
        <div class="postbox">
            <h3>
                <span>
                    <?php _e("Import Settings", "wpstg")?>
                </span>
            </h3>

            <div class="inside">
                <p>
                    <?php _e(
                        "Import the WP-Staging settings from a .json file. This file can be obtained ".
                        "by exporting the settings on another site using the form above.",
                        "wpstg"
                    )?>
                </p>
                <form method="post" enctype="multipart/form-data" action="<?php echo admin_url("admin.php?page=wpstg-tools&amp;tab=import_export")?>">
                    <p>
                        <input type="file" name="import_file"/>
                    </p>
                    <p>
                        <input type="hidden" name="wpstg-action" value="import_settings" />
                        <?php wp_nonce_field("wpstg_import_nonce", "wpstg_import_nonce")?>
                        <?php submit_button(__("Import", "wpstg"), "secondary", "submit", false )?>
                    </p>
                </form>
            </div>
        </div>
        <!-- /Import -->

        <form action="<?php echo esc_url(admin_url("admin.php?page=wpstg-tools&amp;tab=system_info"))?>" method="post" dir="ltr">
            <textarea class="wpstg-sysinfo" readonly="readonly" id="system-info-textarea" name="wpstg-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo $this->di->get("systemInfo")?></textarea>
            <p class="submit">
                <input type="hidden" name="wpstg-action" value="download_sysinfo" />
                <?php submit_button("Download System Info File", "primary", "wpstg-download-sysinfo", false )?>
            </p>
        </form>
    </div>
</div>