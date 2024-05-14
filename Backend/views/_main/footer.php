<?php

use WPStaging\Core\WPStaging;

?>
<div class="wpstg-footer-logo">
    <a href="https://wp-staging.com/tell-me-more/"><img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="140"></a>
</div>
<div class="wpstg-partner-footer">
    <div>Partnered with <a href="https://wp-staging.com/borlabs-cookie/" target="_blank">Borlabs Cookie</a></div>
</div>
<?php
if (!WPStaging::isPro()) {
    require_once(WPSTG_PLUGIN_DIR . 'Backend/views/_main/general-error-modal.php');
}
?>