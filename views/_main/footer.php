<div id="wpstg-footer-container" class="wpstg-mr-2.5 wp:wpstg-mr-5">
    <?php if (empty($hideNewsfeed)) : ?>
        <?php
        require_once(WPSTG_VIEWS_DIR . '_main/newsfeed.php');
        require_once(WPSTG_VIEWS_DIR . '_main/faq.php');
        ?>
    <?php endif; ?>
</div>
<div class="wpstg-footer-logo wpstg-mr-2.5 wp:wpstg-mr-5">
    <a href="https://wp-staging.com/tell-me-more/"><img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="140"></a>
</div>
<div class="wpstg-partner-footer wpstg-mr-2.5 wp:wpstg-mr-5">
    <div>Partnered with <a href="https://wp-staging.com/borlabs-cookie/" target="_blank">Borlabs</a></div>
</div>
<?php
require_once(WPSTG_VIEWS_DIR . '_main/general-error-modal.php');
