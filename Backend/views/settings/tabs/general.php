<!-- General Settings -->
<div id="wpstg-tab-container" class="tab_container">
    <form class="wpstg-settings-panel" method="post" action="options.php">
        <?php
        settings_fields("wpstg_settings");

        foreach ($tabs as $id => $name) :
            if ($id === 'mail-settings' || $id === 'remote-storages') {
                continue;
            }

            $form = \WPStaging\Core\WPStaging::getInstance()->get("forms")->get($id);

            if ($form === null) {
                continue;
            }
            ?>
            <div id="<?php echo $id ?>__wpstg_header">
                <table class="wpstg-form-table">
                    <thead>
                    <tr class="wpstg-settings-row">
                        <th class="wpstg-settings-row th" colspan="2">
                            <div class="col-title">
                                <strong><?php
                                    echo $name ?></strong>
                                <span class="description"></span>
                            </div>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[queryLimit]")
                                ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Number of DB rows, that are queried within one request.
                                        The higher the value the faster the database copy process.
                                        To find out the highest possible values try a high value like 10.000 or more. If you get timeout issues, lower it
                                        until you get no more errors during copying process.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong> Default: 10000 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[queryLimit]") ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[querySRLimit]")
                                ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Number of DB rows, that are processed within one request.
                                        The higher the value the faster the database search & replace process.
                                        This is a high memory consumptive process. If you get timeouts lower this value!",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong> Default: 5000 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[querySRLimit]") ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[fileLimit]")
                                ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Number of files to copy that will be copied within one request.
                                        The higher the value the faster the file copy process.
                                        To find out the highest possible values try a high value like 500 or more. If you get timeout issues, lower it
                                        until you get no more errors during copying process.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <br>
                                        <?php
                                        _e(
                                            "<strong>Important:</strong> If CPU Load Priority is <strong>Low</strong>, set a file copy limit value of 50 or higher! Otherwise file copying process takes a lot of time.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <br>
                                        <strong> Default: 50 </strong>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[fileLimit]") ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[maxFileSize]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Maximum size of the files which are allowed to copy. All files larger than this value will be skipped.                                              
                                        Note: Increase this option only if you have a good reason. Files larger than a few megabytes are in 99% of all cases log and backup files which are not needed on a staging site.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong>Default:</strong> 8 MB
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[maxFileSize]") ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[batchSize]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Buffer size for the file copy process in megabyte.
                                        The higher the value the faster large files are copied.
                                        To find out the highest possible values try a high one and lower it until
                                        you get no errors during file copy process. Usually this value correlates directly
                                        with the memory consumption of php so make sure that
                                        it does not exceed any php.ini max_memory limits.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong>Default:</strong> 2 MB
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[batchSize]") ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[cpuLoad]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Using high will result in fast as possible processing but the cpu load
                                        increases and it's also possible that staging process gets interrupted because of too many ajax requests
                                        (e.g. <strong>authorization error</strong>).
                                        Using a lower value results in lower cpu load on your server but also slower staging site creation.",
                                            "wp-staging"
                                        ); ?>
                                        <br>
                                        <strong>Default: </strong> Low
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[cpuLoad]") ?>
                        </td>
                    </tr>
                    <?php
                    if (!defined('WPSTGPRO_VERSION')) {
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php
                                    echo $form->label("wpstg_settings[disableAdminLogin]") ?>
                                    <span class="description">
                                        If you want to remove the requirement to login to the staging site you can deactivate it here.
                                        <strong>Note:</strong> The staging site discourages search engines from indexing the site by setting the 'noindex' tag into header of the staging site.
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo $form->render("wpstg_settings[disableAdminLogin]") ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <?php
                    if (defined('WPSTGPRO_VERSION')) {
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php
                                    echo $form->label("wpstg_settings[keepPermalinks]") ?>
                                    <span class="description">
                                        <?php
                                        echo sprintf(
                                            __(
                                                'Use on the staging site the same permalink structure and do not set permalinks to plain structure. <br/>Read more: <a href="%1$s" target="_blank">Permalink Settings</a> ',
                                                'wp-staging'
                                            ),
                                            'https://wp-staging.com/docs/activate-permalinks-staging-site/'
                                        ); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo $form->render("wpstg_settings[keepPermalinks]") ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[debugMode]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Enable an extended debug mode that creates additional log entries in wp-content/uploads/wp-staging/logs/logfile.log.
                                        <strong>Do NOT activate this until we ask you to do so!</strong>",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[debugMode]") ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[optimizer]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "The Optimizer is a mu plugin that disables all other plugins during WP STAGING processing. This lowers memory consumption and speeds up processing. This should always be enabled!",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[optimizer]") ?>
                        </td>
                    </tr>
                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[unInstallOnDelete]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Check this box if you like to remove all data when the plugin is deleted.
                                        This will not remove staging sites files or database tables.",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[unInstallOnDelete]") ?>
                        </td>
                    </tr>

                    <tr class="wpstg-settings-row">
                        <td class="wpstg-settings-row th">
                            <div class="col-title">
                                <?php
                                echo $form->label("wpstg_settings[checkDirectorySize]") ?>
                                <span class="description">
                                        <?php
                                        _e(
                                            "Check this box if you like WP Staging to check sizes of each directory on scanning process.
                                        <br>
                                        Warning this may cause timeout problems in big directory / file structures.",
                                            "wp-staging"
                                        ); ?>
                                    </span>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo $form->render("wpstg_settings[checkDirectorySize]") ?>
                        </td>
                    </tr>
                    <?php
                    if (defined('WPSTGPRO_VERSION')) {
                        ?>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php
                                    echo $form->label("wpstg_settings[userRoles][]") ?>
                                    <span class="description">
                                        <?php
                                        _e(
                                            'Select the user role you want to give access to the staging site. You can select multiple roles by holding CTRL or ⌘ Cmd key while clicking. <strong>Change this option on the staging site if you want to change the authentication behavior there.</strong>',
                                            'wp-staging'
                                        ); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo $form->render("wpstg_settings[userRoles][]") ?>
                            </td>
                        </tr>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php
                                    echo $form->label("wpstg_settings[usersWithStagingAccess]") ?>
                                    <span class="description">
                                        <?php
                                        _e(
                                            'Specify users who will have access to the staging site regardless of their role. You can enter multiple user names separated by a comma. <strong>Change this option on the staging site if you want to change the authentication behavior there.</strong>',
                                            'wp-staging'
                                        ); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo $form->render("wpstg_settings[usersWithStagingAccess]") ?>
                            </td>
                        </tr>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <?php
                                    echo $form->label("wpstg_settings[adminBarColor]") ?>
                                    <span class="description">
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                echo $form->render("wpstg_settings[adminBarColor]") ?>
                            </td>
                        </tr>
                        <tr class="wpstg-settings-row">
                            <td class="wpstg-settings-row th">
                                <div class="col-title">
                                    <strong><?php echo __('Send Usage Information', 'wp-staging') ?></strong>
                                    <span class="description">
                                        <?php
                                        _e(
                                            'Send usage information to wp-staging.com.',
                                            'wp-staging'
                                        );
                                        echo '<br>';
                                        echo wp_kses_post(__(sprintf('<i>See the data we collect <a href="%s" target="_blank">here</a></i>', 'https://wp-staging.com/what-data-do-we-collect/')), 'wp-staging');
                                        ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $analytics = WPStaging\Core\WPStaging::make(\WPStaging\Framework\Analytics\AnalyticsConsent::class);
                                $analyticsAllowed = $analytics->hasUserConsent();
                                $isAllowed = $analyticsAllowed;
                                $isDisallowed = !$analyticsAllowed && !is_null($analyticsAllowed); // "null" means didn't answer, "false" means declined
                                ?>
                                <div style="font-weight:<?php echo $isAllowed ? 'bold' : ''; ?>"><a href="<?php echo esc_url($analytics->getConsentLink(true)) ?>"><?php echo esc_html__('Yes, send usage information. I\'d like to help improving this plugin.', 'wp-staging') ?></a></div>
                                <div style="margin-top:10px;font-weight:<?php echo $isDisallowed ? 'bold' : ''; ?>"><a href="<?php echo esc_url($analytics->getConsentLink(false)) ?>"><?php echo esc_html__('No, Don\'t send any usage information.', 'wp-staging') ?></a></div>
                                <?php
                                ?>
                            </td>
                        </tr>
                        <?php
                        if (WPStaging\Core\WPStaging::isPro()) :
                            ?>
                            <tr class="wpstg-settings-row">
                                <td class="wpstg-settings-row th">
                                    <b class="wpstg-settings-title"><?php _e('Send Email Error Report', 'wp-staging') ?></b>
                                    <p class="wpstg-settings-message">
                                        <?php _e('If a scheduled backup fails, send an email.', 'wp-staging') ?>
                                    </p>
                                </td>
                                <td>
                                    <input type="checkbox" id="wpstg-send-schedules-error-report" name="wpstg_settings[schedulesErrorReport]" class="wpstg-checkbox wpstg-settings-field" value="true" <?php echo get_option(WPStaging\Pro\Backup\BackupScheduler::BACKUP_SCHEDULE_ERROR_REPORT_OPTION) === 'true' ? 'checked' : '' ?> />
                                </td>
                            </tr>
                            <tr class="wpstg-settings-row">
                                <td>
                                    <b class="wpstg-settings-title"><?php _e('Email Address', 'wp-staging') ?></b>
                                    <p class="wpstg-settings-message">
                                        <?php _e('Send emails to this address', 'wp-staging') ?>
                                    </p>
                                </td>
                                <td>
                                    <input type="text" id="wpstg-send-schedules-report-email" name="wpstg_settings[schedulesReportEmail]" class="wpstg-checkbox wpstg-settings-field" value="<?php echo get_option(WPStaging\Pro\Backup\BackupScheduler::BACKUP_SCHEDULE_REPORT_EMAIL_OPTION) ?>"/>
                                </td>
                            </tr>
                            <?php
                        endif;
                        // show this option only on the staging site
                        if ($this->siteInfo->isStagingSite()) :
                            ?>
                            <tr>
                                <td>
                                    <b class="wpstg-settings-title"><?php _e('Allow Cloning (Staging Site Only)', 'wp-staging') ?></b>
                                    <p class="wpstg-settings-message">
                                        <?php _e('Check this box to make this staging site cloneable.', 'wp-staging') ?>
                                        <?php echo sprintf(__("If you would like to know more about cloning staging sites check out <a href='%s' target='_new'>this article</a>.", 'wp-staging'), 'https://wp-staging.com/docs/cloning-a-staging-site-testing-push-method/'); ?>
                                    </p>
                                </td>
                                <td>
                                    <input type="checkbox" id="wpstg-is-staging-cloneable" name="wpstg_settings[isStagingSiteCloneable]" class="wpstg-checkbox wpstg-settings-field" value="true" <?php echo $this->siteInfo->isCloneable() ? 'checked' : '' ?> />
                                </td>
                            </tr>

                            <?php
                        endif;
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
        endforeach;

        submit_button();
        unset($tabs);
        ?>
    </form>
</div>
