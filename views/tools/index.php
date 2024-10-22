<?php

use WPStaging\Framework\Facades\Sanitize;

?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php

    require_once(WPSTG_VIEWS_DIR . (defined('WPSTGPRO_VERSION') ? 'pro/_main/header.php' : '_main/header.php'));

    $isActiveSystemInfoPage = true;
    require_once(WPSTG_VIEWS_DIR . '_main/main-navigation.php');
    ?>
    <div class="wpstg-loading-bar-container">
        <div class="wpstg-loading-bar"></div>
    </div>
    <div class="wpstg-tabs-container" id="wpstg-tools">
        <div class="wpstg-metabox-holder">
            <?php
            $numberOfLoadingBars = 100;
            include(WPSTG_VIEWS_DIR . '_main/loading-placeholder.php');
            require_once(WPSTG_VIEWS_DIR . "tools/tabs/system-info.php");
            ?>
        </div>
    </div>
    <?php
        require_once(WPSTG_VIEWS_DIR . '_main/footer.php');
    ?>
</div>

