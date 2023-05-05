<?php

use WPStaging\Framework\Facades\Sanitize;

?>
<div class="wpstg_admin" id="wpstg-clonepage-wrapper">
    <?php

    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/header.php');

    $isActiveSystemInfoPage = true;
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/main-navigation.php');
    ?>

    <div class="wpstg-tabs-container" id="wpstg-tools">
        <div class="wpstg-metabox-holder">
            <?php require_once $this->path . "views/tools/tabs/system-info.php"?>
        </div>
    </div>
</div>
<div class="wpstg-footer-logo" style="">
    <a href="https://wp-staging.com/tell-me-more/"><img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="140"></a>
</div>
