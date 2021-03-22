<!-- Export -->
<div class="postbox">
    <h3>
                <span>
                    <?php _e("Export Settings", "wp-staging")?>
                </span>
    </h3>

    <div class="inside">
        <p>
            <?php _e(
                "Export the WP-Staging settings for this site as a .json file. " .
                "This allows you to easily import the configuration into another site.",
                "wp-staging"
            )?>
        </p>

        <form method="post" action="<?php echo admin_url("admin-post.php?action=wpstg_export")?>">
            <p><input type="hidden" name="wpstg-action" value="export_settings" /></p>
            <p>
                <?php wp_nonce_field("wpstg_export_nonce", "wpstg_export_nonce")?>
                <?php submit_button(__("Export", "wp-staging"), "primary", "submit", false)?>
            </p>
        </form>
    </div>
</div>
<!-- /Export -->

<!-- Import -->
<div class="postbox">
    <h3>
            <span>
                <?php _e("Import Settings", "wp-staging")?>
            </span>
    </h3>

    <div class="inside">
        <p>
            <?php _e(
                "Import the WP-Staging settings from a .json file. This file can be obtained " .
                "by exporting the settings on another site using the form above.",
                "wp-staging"
            )?>
        </p>
        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url("admin-post.php?action=wpstg_import_settings")?>">
            <p>
                <input type="file" name="import_file"/>
            </p>
            <p>
                <input type="hidden" name="wpstg-action" value="import_settings" />
                <?php wp_nonce_field("wpstg_import_nonce", "wpstg_import_nonce")?>
                <?php submit_button(__("Import", "wp-staging"), "secondary", "submit", false)?>
            </p>
        </form>
    </div>
</div>
<!-- /Import -->