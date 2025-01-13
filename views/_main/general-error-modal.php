<div id="wpstg-general-error-modal" class="wpstg-contact-us-modal">
    <div class="wpstg-modal-content">
        <?php require(WPSTG_VIEWS_DIR . '_main/partials/contact-us-header.php'); ?>
        <div class="wpstg-contact-us-report-issue">
            <div class="wpstg-contact-us-troubleshot-container">
                <h2><?php esc_html_e('Nothing to worry but there is a glitch...', "wp-staging"); ?></h2>
                <p><?php esc_html_e('This hiccup wasn\'t in the plan, but we are on it. We\'ll rectify the problem for you, for free!', "wp-staging"); ?></p>
                <p><?php esc_html_e('Click the button below to help us fixing it for you!', "wp-staging"); ?></p>
                <?php require(WPSTG_VIEWS_DIR . '_main/partials/share-debug-code.php'); ?>
            </div>
        </div>
        <div class="wpstg-modal-footer"></div>
        <div class="wpstg-contact-us-success-form">
            <?php require(WPSTG_VIEWS_DIR . '_main/partials/contact-us-success.php'); ?>
        </div>
    </div>
</div>
