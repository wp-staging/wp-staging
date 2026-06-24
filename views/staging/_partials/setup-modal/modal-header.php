<?php

/**
 * Renders a setup modal header.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $title
 * @var string                                    $description
 * @var string                                    $titleId
 * @var string                                    $stepLabel
 * @var string                                    $extraClass
 * @var string                                    $afterDescription
 * @var string                                    $icon
 */

$hasIcon   = !empty($icon);
$className = trim($extraClass . ' wpstg-create-setup-modal__header wpstg-staging-setup-modal__header' . ($hasIcon ? ' wpstg-create-setup-modal__header--with-icon' : ''));
?>
<header class="<?php echo esc_attr($className); ?>">
    <?php if ($hasIcon) : ?>
        <span class="wpstg-create-header-icon" aria-hidden="true"><?php $renderer->icon($icon, 'wpstg-h-5 wpstg-w-5'); ?></span>
    <?php endif; ?>
    <div class="wpstg-create-header-text">
        <?php if (!empty($stepLabel)) : ?>
            <?php $renderer->stepBadge($stepLabel); ?>
        <?php endif; ?>
        <h1
            <?php if (!empty($titleId)) : ?>
                id="<?php echo esc_attr($titleId); ?>"
            <?php endif; ?>
            class="wpstg-u-m-0 <?php echo !empty($stepLabel) ? 'wpstg-mt-2 ' : ''; ?>wpstg-text-2xl wpstg-font-bold wpstg-leading-8 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"
        ><?php echo esc_html($title); ?></h1>
        <?php if (!empty($description)) : ?>
            <p class="wpstg-m-0 wpstg-mt-1 wpstg-text-base wpstg-leading-6 wpstg-text-[#536579] dark:wpstg-text-slate-400"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php echo wp_kses_post($afterDescription); ?>
    </div>
</header>
