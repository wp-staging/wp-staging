<?php

/**
 * Renders a setup modal footer.
 *
 * @var string   $status
 * @var callable $buttons
 * @var string   $extraClass
 * @var string   $statusWarning
 */

$className       = trim($extraClass . ' wpstg-create-setup-modal__footer wpstg-staging-setup-modal__footer');
$statusAttribute = $statusWarning !== ''
    ? ' data-status-default="' . esc_attr($status) . '" data-status-warning="' . esc_attr($statusWarning) . '"'
    : '';
?>
<footer class="<?php echo esc_attr($className); ?>">
    <p class="wpstg-create-footer-status"<?php echo $statusAttribute; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>><span class="wpstg-create-footer-status__dot" aria-hidden="true"></span><span class="wpstg-create-footer-status__text"><?php echo wp_kses_post($status); ?></span></p>
    <div class="wpstg-create-footer-actions">
        <?php $buttons(); ?>
    </div>
</footer>
