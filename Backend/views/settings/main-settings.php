<div class="wpstg_admin">
    <?php require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php'); ?>

    <ul class="nav-tab-wrapper">
        <?php
        $tabs = \WPStaging\Core\WPStaging::getInstance()->get("tabs")->get();
        $activeTab = (isset($_GET["tab"]) && array_key_exists($_GET["tab"], $tabs)) ? $_GET["tab"] : "general";

        # Loop through tabs
        foreach ($tabs as $id => $name):
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
    <h2 class="nav-tab-wrapper"></h2>

    <div id="wpstg-tab-container" class="tab_container">
        <div class="panel-container">
            <form method="post" action="options.php">
                <?php
                settings_fields("wpstg_settings");

                foreach ($tabs as $id => $name):
                    $form = \WPStaging\Core\WPStaging::getInstance()->get("forms")->get($id);

                    if ($form === null) {
                        continue;
                    }
                    ?>
                    <div id="<?php
                    echo $id ?>__wpstg_header">
                        <table class="wpstg-form-table">
                            <thead>
                            <tr class="row">
                                <th class="row th" colspan="2">
                                    <div class="col-title">
                                        <strong><?php
                                            echo $name ?></strong>
                                        <span class="description"></span>
                                    </div>
                                </th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[queryLimit]")
                                        ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "Number of DB rows, that are copied within one ajax query.
                                                The higher the value the faster the database copy process.
                                                To find out the highest possible values try a high value like 1.000 or more. If you get timeout issues, lower it
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
                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[querySRLimit]")
                                        ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "Number of DB rows, that are processed within one ajax query.
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

                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[fileLimit]")
                                        ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "Number of files to copy that will be copied within one ajax request.
                                                The higher the value the faster the file copy process.
                                                To find out the highest possible values try a high value like 500 or more. If you get timeout issues, lower it
                                                until you get no more errors during copying process.",
                                                    "wp-staging"
                                                ); ?>
                                                <br>
                                                <br>
                                                <?php
                                                _e(
                                                    "<strong>Important:</strong> If CPU Load Priority is <strong>Low</strong> set a file copy limit value of 50 or higher! Otherwise file copying process takes a lot of time.",
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

                            <tr class="row">
                                <td class="row th">
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
                            <tr class="row">
                                <td class="row th">
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

                            <tr class="row">
                                <td class="row th">
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
                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[delayRequests]") ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "If your server uses rate limits it blocks requests and WP Staging can be interrupted. You can resolve that by adding one or more seconds of delay between the processing requests. ",
                                                    "wp-staging"
                                                ); ?>
                                                <br>
                                                <strong>Default: </strong> 0s
                                            </span>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    echo $form->render("wpstg_settings[delayRequests]") ?>
                                </td>
                            </tr>
                            <?php
                            if (!defined('WPSTGPRO_VERSION')) {
                                ?>
                                <tr class="row">
                                    <td class="row th">
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
                                <tr class="row">
                                    <td class="row th">
                                        <div class="col-title">
                                            <?php
                                            echo $form->label("wpstg_settings[keepPermalinks]") ?>
                                            <span class="description">
                                                <?php
                                                echo sprintf(
                                                    __(
                                                        'Keep permalinks original setting activated and do not disable permalinks on staging site. <br/>Read more: <a href="%1$s" target="_blank">Permalink Settings</a> ',
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
                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[debugMode]") ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "This will enable an extended debug mode which creates additional entries
                                                in <strong>wp-content/uploads/wp-staging/logs/logfile.log</strong>.
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
                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[optimizer]") ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "The Optimizer is a mu plugin which disables all other plugins during WP Staging processing. Usually this makes the cloning process more reliable. If you experience issues, disable the Optimizer.",
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

                            <tr class="row">
                                <td class="row th">
                                    <div class="col-title">
                                        <?php
                                        echo $form->label("wpstg_settings[unInstallOnDelete]") ?>
                                        <span class="description">
                                                <?php
                                                _e(
                                                    "Check this box if you like WP Staging to completely remove all of its data when the plugin is deleted.
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

                            <tr class="row">
                                <td class="row th">
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
                                <tr class="row">
                                    <td class="row th">
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
                                <tr class="row">
                                    <td class="row th">
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
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                endforeach;
                // Show submit button any tab but add-ons
                if ($activeTab !== "add-ons") {
                    submit_button();
                }
                unset($tabs);
                ?>
            </form>
        </div>
    </div>
</div>
