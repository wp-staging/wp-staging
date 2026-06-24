<?php

/**
 * Renders the create setup modal footer.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $previewSiteUrl
 */

$renderer->modalFooter(__('Ready. Recommended defaults selected.', 'wp-staging'), function () use ($renderer, $previewSiteUrl) {
    $renderer->footerButton(__('Cancel', 'wp-staging'), 'wpstg-create-modal-cancel');
    ?>
    <button type="button" id="wpstg-start-cloning" class="wpstg--create--staging-site wpstg-setup-cta wpstg-setup-cta--blue" data-url="<?php echo esc_attr($previewSiteUrl); ?>">
        <?php esc_html_e('Create Staging Site', 'wp-staging'); ?><?php $renderer->icon('arrow-right', 'wpstg-btn-icon-sm'); ?>
    </button>
    <?php
}, '', __('A core folder or table is excluded.', 'wp-staging'));
