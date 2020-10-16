<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxOverview
 * @var array availableClones
 * @var $license
 */
?>
<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn wpstg-blue-primary wpstg-button" data-action="wpstg_scanning">
        <?php echo __( "Create new staging site", "wp-staging" ) ?>
    </button>
</div>

<?php if( isset( $availableClones ) && !empty( $availableClones ) ): ?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php _e( "Your Staging Sites:", "wp-staging" ) ?>
        </h3>
        <?php foreach ( $availableClones as $name => $data ): ?>
            <div id="<?php echo $data["directoryName"]; ?>" class="wpstg-clone">

                <?php $urlLogin = $data["url"]; ?>

                <a href="<?php echo $urlLogin ?>" class="wpstg-clone-title" target="_blank">
                    <?php echo $data["directoryName"]; ?>
                </a>

                <?php
                do_action('wpstg.views.single_overview.before_existing_clones_buttons', $name, $data, $license);

                // Todo: Remove in future versions
                echo apply_filters_deprecated("wpstg_before_stage_buttons", [$html = '', $name, $data], '2.7.6', 'wpstg.views.single_overview.before_existing_clones_buttons', 'The replacement filter uses do_action()');
                ?>

                <a href="<?php echo $urlLogin ?>" class="wpstg-open-clone wpstg-clone-action" target="_blank" title="<?php echo __( "Open the staging site in a new tab", "wp-staging" ) ?>">
                    <?php _e( "Open", "wp-staging" ); ?>
                </a>

                <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo $name ?>" title="<?php echo __( "Update and overwrite this clone. Select folders and database tables in the next step.", "wp-staging" ) ?>">
                    <?php _e( "Update", "wp-staging" ); ?>
                </a>

                <a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo $name ?>" title="<?php echo __( "Delete this clone. Select specific folders and database tables in the next step.", "wp-staging" ) ?>">
                    <?php _e( "Delete", "wp-staging" ); ?>
                </a>

                <?php
                do_action('wpstg.views.single_overview.after_existing_clones_buttons', $name, $data, $license);

                // Todo: Remove in future versions
                echo apply_filters_deprecated("wpstg_after_stage_buttons", [$html = '', $name, $data], '2.7.6', 'wpstg.views.single_overview.after_existing_clones_buttons', 'The replacement filter uses do_action()');
                ?>

                <div class="wpstg-staging-info">
                    <?php
                    $dbname   = ! empty($data['databaseDatabase']) ? $data['databaseDatabase'] : DB_NAME;
                    $prefix   = ! empty($data['prefix']) ? $data['prefix'] : '';
                    $cloneDir = ! empty($data['path']) ? $data['path'] : '';
                    $url      = ! empty($data['url']) ? sprintf('<a href="%1$s" target="_blank">%1$s</a>', $data['url']) : '';
                    $datetime = ! empty($data['datetime']) ? date("D, d M Y H:i:s T", $data['datetime']) : '&nbsp;&nbsp;&nbsp;';

                    $statusTooltip = "This clone is incomplete and does not work. Clone or update it again! \n\n".
                                      "Important: Keep the browser open until the cloning is finished. \n".
                                      "It will not proceed if your browser is not open.\n\n".
                                      "If you have an unstable internet connection and cloning breaks due to that, clone again only the folders wp-admin, wp-includes, and all database tables.\n\n".
                                      "That will not take much time. Then, you can proceed with the wp-content folder that usually needs the most disk space. ".
                                      "If it interrupts again, at least it will not break the existing staging site again, and you can repeat and resume the last operation.";

                    if (!empty($data['status']) && $data['status'] !== 'finished') {
                        $status = sprintf(
                                __('Status: <span class="wpstg-bold" style="color:#ffc2c2;" title="%s">%s</span>', 'wp-staging'),
                                $statusTooltip,
                                $data['status']
                        );
                    } else {
                        $status = '&nbsp;&nbsp;&nbsp;';
                    }


                    echo sprintf(__('Database: <span class="wpstg-bold">%s</span>', 'wp-staging'), $dbname);
                    echo '</br>';
                    echo sprintf(__('Database Prefix: <span class="wpstg-bold">%s</span>', 'wp-staging'), $prefix);
                    echo '</br>';
                    echo sprintf(__('Directory: <span class="wpstg-bold">%s</span>', 'wp-staging'), $cloneDir);
                    echo '</br>';
                    echo sprintf(__('URL: <span class="wpstg-bold">%s</span>', 'wp-staging'), $url);
                    echo '</br>';
                    echo $status;
                    echo '</br>';
                    echo sprintf(__('Updated: <span>%s</span>', 'wp-staging'), $datetime);

                    do_action('wpstg.views.single_overview.after_existing_clones_details', $name, $data, $license);
                    ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <!-- /Existing Clones -->
<?php endif ?>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
