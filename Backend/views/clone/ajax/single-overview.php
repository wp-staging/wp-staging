<?php

/**
 * @see \WPStaging\Backend\Administrator::ajaxOverview
 *
 * @var array  $availableClones
 * @var string $iconPath
 * @var        $license
 */

use WPStaging\Framework\Facades\Escape;

?>
<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" data-action="wpstg_scanning">
        <?php echo esc_html__("Create Staging Site", "wp-staging") ?>
    </button>
    <div id="wpstg-report-issue-wrapper">
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo esc_html__("Contact Us", "wp-staging"); ?>
        </button>
        <?php require_once($this->path . 'views/_main/report-issue.php'); ?>
    </div>
</div>

<?php if (isset($availableClones) && !empty($availableClones)) : ?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php esc_html_e("Your Staging Sites:", "wp-staging") ?>
        </h3>
        <?php foreach ($availableClones as $cloneID => $data) : ?>
            <div id="<?php echo esc_attr($data['directoryName']); ?>" data-clone-id="<?php echo esc_attr($cloneID); ?>" class="wpstg-clone">
                <?php $urlLogin = esc_url($data["url"]); ?>
                <div class="wpstg-clone-header">
                    <a href="<?php echo esc_url($urlLogin) ?>" class="wpstg-clone-title" target="_blank">
                        <?php echo isset($data["cloneName"]) ? esc_html($data["cloneName"]) : esc_html($data["directoryName"]); ?>
                    </a>
                    <?php if (is_multisite()) : ?>
                    <div class="wpstg-clone-labels">
                        <span class="wpstg-clone-label"><?php echo !empty($data['networkClone']) ? esc_html__('Network Site', 'wp-staging') : esc_html__('Single Site', 'wp-staging') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="wpstg-clone-actions">
                        <div class="wpstg-dropdown wpstg-action-dropdown">
                            <a href="#" class="wpstg-dropdown-toggler">
                                <?php esc_html_e("Actions", "wp-staging"); ?>
                                <span class="wpstg-caret"></span>
                            </a>
                            <div class="wpstg-dropdown-menu">
                                <?php
                                do_action('wpstg.views.single_overview.before_existing_clones_actions', $cloneID, $data, $license);
                                ?>
                                <a href="<?php echo esc_url($urlLogin) ?>" class="wpstg-open-clone wpstg-clone-action" target="_blank" title="<?php echo esc_html__("Open the staging site in a new tab", "wp-staging") ?>">
                                    <?php esc_html_e("Open", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo esc_attr($cloneID) ?>" title="<?php echo esc_html__("Update and overwrite the selected staging site with the production site. You can select files and database tables on the next page. This action will not replace nor modify the wp-config.php on the staging site!", "wp-staging") ?>">
                                    <?php esc_html_e("Update", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-reset-clone wpstg-clone-action" data-clone="<?php echo esc_attr($cloneID) ?>" data-network="<?php echo is_multisite() && !empty($data['networkClone'])  ? 'yes' : 'no' ?>" title="<?php echo esc_attr__("Replace the selected staging site with the production site completely. This includes replacing the wp-config.php and all files and data. Confirm to proceed on the next page.", "wp-staging") ?>">
                                    <?php esc_html_e("Reset", "wp-staging"); ?>
                                </a>
                                <a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo esc_attr($cloneID) ?>" title="<?php echo esc_html__("Delete the selected staging site. Select specific folders and database tables in the next step.", "wp-staging") ?>">
                                    <?php esc_html_e("Delete", "wp-staging"); ?>
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
                    $url      = ! empty($data['url']) ? sprintf('<a href="%1$s" target="_blank">%1$s</a>', esc_url($data['url'])) : '';
                    $datetime = ! empty($data['datetime']) ? get_date_from_gmt(date("Y-m-d H:i:s", $data['datetime']), "D, d M Y H:i:s T") : '&nbsp;&nbsp;&nbsp;';
                    $owner    = ! empty($data['ownerId']) ? get_userdata($data['ownerId']) : null;
                    $ownerName = ! empty($owner->user_login) ? $owner->user_login : 'N/A';
                    $statusTooltip = __("This clone is incomplete and does not work. Clone or update it again! \n\n" .
                                      "Important: Keep the browser open until the cloning is finished. \n" .
                                      "It will not proceed if your browser is not open.\n\n" .
                                      "If you have an unstable internet connection and cloning breaks due to that, clone again only the folders wp-admin, wp-includes, and all database tables.\n\n" .
                                      "That will not take much time. Then, you can proceed with the wp-content folder that usually needs the most disk space. " .
                                      "If it interrupts again, at least it will not break the existing staging site again, and you can repeat and resume the last operation.", 'wp-staging');

                    if (!empty($data['status']) && $data['status'] !== 'finished') {
                        $status = sprintf(
                            Escape::escapeHtml(__('Status: <span class="wpstg-staging-status wpstg-bold" title="%s">%s</span>', 'wp-staging')),
                            $statusTooltip,
                            $data['status']
                        );
                    } else {
                        $status = '&nbsp;&nbsp;&nbsp;';
                    }


                    echo sprintf(
                        Escape::escapeHtml(__('Database: <span class="wpstg-bold">%s</span>', 'wp-staging')),
                        esc_html($dbname)
                    );
                    echo '</br>';
                    echo sprintf(
                        Escape::escapeHtml(__('Database Prefix: <span class="wpstg-bold">%s</span>', 'wp-staging')),
                        esc_html($prefix)
                    );
                    echo '</br>';
                    echo sprintf(
                        Escape::escapeHtml(__('Directory: <span class="wpstg-bold">%s</span>', 'wp-staging')),
                        esc_html($cloneDir)
                    );
                    echo '</br>';
                    echo sprintf(
                        Escape::escapeHtml(__('URL: <span class="wpstg-bold">%s</span>', 'wp-staging')),
                        Escape::escapeHtml($url)
                    );
                    echo '</br>';
                    echo sprintf(
                        Escape::escapeHtml(__('Created By: <span class="wpstg-bold">%s</span>', 'wp-staging')),
                        esc_html($ownerName)
                    );
                    echo '</br>';
                    echo Escape::escapeHtml($status);
                    echo '</br>';
                    echo sprintf(
                        Escape::escapeHtml(__('Updated: <span>%s</span>', 'wp-staging')),
                        esc_html($datetime)
                    );

                    // Todo: Remove in future versions
                    if (function_exists('do_action_deprecated')) {
                        // do_action_deprecated exists since WP 4.6
                        do_action_deprecated("wpstg.views.single_overview.after_existing_clones_details", [$cloneID, $data, $license], '2.7.6', '', 'This will be removed from the future update');
                    }
                    ?>
                </div>
            </div>
        <?php endforeach ?>
        <div class="wpstg-fs-14" id="info-block-how-to-push">
            <?php esc_html_e("How to:", "wp-staging") ?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php esc_html_e("Push staging site to production", "wp-staging") ?></a>
        </div>
    </div>
    <!-- /Existing Clones -->
<?php endif ?>

<div id="wpstg-no-staging-site-results" class="wpstg-clone" <?php echo $availableClones !== [] ? 'style="display: none;"' : '' ?> >
    <img class="wpstg--dashicons" src="<?php echo esc_url($iconPath); ?>" alt="cloud">
    <div class="no-staging-site-found-text">
        <?php esc_html_e('No Staging Site found. Create your first Staging Site above!', 'wp-staging'); ?>
    </div>
</div>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->
