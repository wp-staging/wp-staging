<?php

/**
 * @see src/views/clone/index.php
 *
 * @var object $license
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;

?>

<div id="wpstg-top-header">
    <span class="wpstg-logo">
        <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo-white-transparent.png")) ?>" width="212" alt="">
    </span>

    <div class="wpstg-version">
    <?php
    echo 'WP Staging v. ' . esc_html(WPStaging::getVersion());
    echo ' <a href="' . esc_url(Language::localizeUrl('https://wp-staging.com')) . '" target="_blank">Free Version</a>
            <div class="wpstg-upgrade-license-container">
            <a href="' . esc_url(Language::localizeUrl('https://wp-staging.com')) . '" class="wpstg-upgrade-license-button" target="_blank">' . esc_html__('Upgrade to Pro', 'wp-staging') . '</a>
            </div>';
    ?>
    </div>
</div>
