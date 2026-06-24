<?php

/**
 * Renders a setup "what to copy" card.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $checkboxId
 * @var string                                    $checkboxClass
 * @var string                                    $title
 * @var callable                                  $summary
 * @var string                                    $buttonClass
 * @var string                                    $buttonLabel
 * @var string                                    $buttonIcon
 * @var callable|null                             $details
 * @var array                                     $checkboxOptions
 * @var callable|null                             $afterContent
 */

$checkboxOptions = array_merge([
    'classes'      => $checkboxClass,
    'usePrimitive' => true,
], $checkboxOptions);
?>
<div class="wpstg-create-copy-card">
    <?php \WPStaging\Framework\Facades\UI\Checkbox::render($checkboxId, $checkboxId, 'true', true, $checkboxOptions); ?>
    <div class="wpstg-create-copy-card__content">
        <strong><?php echo esc_html($title); ?></strong>
        <?php $summary(); ?>
        <button type="button" class="wpstg-create-copy-customize <?php echo esc_attr($buttonClass); ?>">
            <?php $renderer->icon($buttonIcon, 'wpstg-h-4 wpstg-w-4'); ?>
            <?php echo esc_html($buttonLabel); ?>
        </button>
        <?php if ($afterContent !== null) {
            $afterContent();
        } ?>
    </div>

    <?php if ($details !== null) : ?>
        <div class="wpstg-create-copy-details" style="display: none;" aria-hidden="true">
            <?php $details(); ?>
        </div>
    <?php endif; ?>
</div>
