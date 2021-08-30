<?php
/**
 * @see \WPStaging\Backend\Administrator::ajaxOverview
 *
 * @var array  $availableClones
 * @var string $iconPath
 * @var        $license
 */
?>
<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" data-action="wpstg_scanning">
        <?php echo __("Create new staging site", "wp-staging") ?>
    </button>
</div>

<?php if (isset($availableClones) && !empty($availableClones)) : ?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php _e("Your Staging Sites:", "wp-staging") ?>
        </h3>
        <?php foreach ($availableClones as $cloneID => $data) : ?>
            <div id="<?php echo $data['directoryName']; ?>" data-clone-id="<?php echo $cloneID; ?>" class="wpstg-clone">
                <?php $urlLogin = $data["url"]; ?>
                <div class="wpstg-clone-header">
                    <a href="<?php echo $urlLogin ?>" class="wpstg-clone-title" target="_blank">
                        <?php echo isset($data["cloneName"]) ? $data["cloneName"] : $data["directoryName"]; ?>
                    </a>
                    <div class="wpstg-clone-actions">
                        <div class="wpstg-dropdown wpstg-action-dropdown">
                            <a href="#" class="wpstg-dropdown-toggler transparent">
                                <?php _e("Actions", "wp-staging"); ?>
                                <span class="wpstg-caret"></span>
                            </a>
                            <div class="wpstg-dropdown-menu">
                                <?php
                                do_action('wpstg.views.single_overview.before_existing_clones_actions', $cloneID, $data, $license);
                                ?>
                                <a href="<?php echo $urlLogin ?>" class="wpstg-open-clone wpstg-clone-action" target="_blank" title="<?php echo __("Open the staging site in a new tab", "wp-staging") ?>">
                                    <?php _e("Open", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo $cloneID ?>" title="<?php echo __("Update and overwrite this clone. Select folders and database tables in the next step.", "wp-staging") ?>">
                                    <?php _e("Update", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-reset-clone wpstg-clone-action" data-clone="<?php echo $cloneID ?>" title="<?php echo __("Reset this clone with existing production site. Confirm to proceed.", "wp-staging") ?>">
                                    <?php _e("Reset", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo $cloneID ?>" title="<?php echo __("Delete this clone. Select specific folders and database tables in the next step.", "wp-staging") ?>">
                                    <?php _e("Delete", "wp-staging"); ?>
                                </a>
                                <?php
                                do_action('wpstg.views.single_overview.after_existing_clones_actions', $cloneID, $data, $license);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wpstg-staging-info">
                    <?php
                    $dbname   = ! empty($data['databaseDatabase']) ? $data['databaseDatabase'] : DB_NAME;
                    $prefix   = ! empty($data['prefix']) ? $data['prefix'] : '';
                    $cloneDir = ! empty($data['path']) ? $data['path'] : '';
                    $url      = ! empty($data['url']) ? sprintf('<a href="%1$s" target="_blank">%1$s</a>', $data['url']) : '';
                    $datetime = ! empty($data['datetime']) ? date("D, d M Y H:i:s T", $data['datetime']) : '&nbsp;&nbsp;&nbsp;';
                    $owner    = ! empty($data['ownerId']) ? get_userdata($data['ownerId']) : null;
                    $ownerName = ! empty($owner->user_login) ? $owner->user_login : 'N/A';
                    $statusTooltip = "This clone is incomplete and does not work. Clone or update it again! \n\n" .
                                      "Important: Keep the browser open until the cloning is finished. \n" .
                                      "It will not proceed if your browser is not open.\n\n" .
                                      "If you have an unstable internet connection and cloning breaks due to that, clone again only the folders wp-admin, wp-includes, and all database tables.\n\n" .
                                      "That will not take much time. Then, you can proceed with the wp-content folder that usually needs the most disk space. " .
                                      "If it interrupts again, at least it will not break the existing staging site again, and you can repeat and resume the last operation.";

                    if (!empty($data['status']) && $data['status'] !== 'finished') {
                        $status = sprintf(
                            __('Status: <span class="wpstg-staging-status wpstg-bold" title="%s">%s</span>', 'wp-staging'),
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
                    echo sprintf(__('Created By: <span class="wpstg-bold">%s</span>', 'wp-staging'), $ownerName);
                    echo '</br>';
                    echo $status;
                    echo '</br>';
                    echo sprintf(__('Updated: <span>%s</span>', 'wp-staging'), $datetime);

                    // Todo: Remove in future versions
                    if (function_exists('do_action_deprecated')) {
                        // do_action_deprecated exists since WP 4.6
                        echo do_action_deprecated("wpstg.views.single_overview.after_existing_clones_details", [$cloneID, $data, $license], '2.7.6', '', 'This will be removed from the future update');
                    }
                    ?>
                </div>
            </div>
        <?php endforeach ?>
        <div class="wpstg-fs-14">
            <?php _e("How to:", "wp-staging") ?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php _e("Push staging site to production", "wp-staging") ?></a>
        </div>
    </div>
    <!-- /Existing Clones -->
<?php endif ?>

<div id="wpstg-no-staging-site-results" class="wpstg-clone" <?php echo $availableClones !== [] ? 'style="display: none;"' : '' ?> >
    <img class="wpstg--dashicons" src="<?php echo $iconPath; ?>" alt="cloud">
    <div class="no-staging-site-found-text">
        <?php _e('No Staging Site found. Create your first Staging Site above!', 'wp-staging'); ?>
    </div>
</div> 

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
