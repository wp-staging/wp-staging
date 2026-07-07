<?php

/**
 * Renders the setup modal ready summary card.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $title
 * @var string                                    $description
 * @var string                                    $databaseDescription
 * @var string                                    $filesDescription
 * @var bool                                      $showRuntimeBehavior
 * @var string                                    $runtimeDescription
 * @var string                                    $cardClass
 * @var string                                    $runtimeTitle
 * @var string                                    $engineSuffix
 * @var string                                    $runtimeIcon
 * @var array                                     $customizeLinks
 * @var bool                                      $runtimeLocked
 */

if (empty($title)) {
    $title = __('Ready to create your staging site', 'wp-staging');
}

if (empty($description)) {
    $description = __('We will copy your WordPress database and files using safe staging defaults. No customization is required.', 'wp-staging');
}

if (empty($databaseDescription)) {
    $databaseDescription = __('WordPress tables selected automatically', 'wp-staging');
}

$filesDescriptionIsHtml = false;
if (empty($filesDescription)) {
    $filesDescription = sprintf(
        /* translators: %s: file-size limit in MB (a number). */
        esc_html__('Plugins, themes, uploads, core. Files over %s MB are skipped to speed up cloning — customizable.', 'wp-staging'),
        '<span data-wpstg-files-skip-size>8</span>'
    );
    $filesDescriptionIsHtml = true;
}

if (empty($runtimeDescription)) {
    $runtimeDescription = __('Staging isolation settings selected', 'wp-staging');
}

if ($runtimeLocked) {
    $runtimeDescription = __('Basic isolation active. Advanced controls available in Pro.', 'wp-staging');
}

if (empty($runtimeTitle)) {
    $runtimeTitle = __('Staging isolation', 'wp-staging');
}

if (empty($engineSuffix)) {
    $engineSuffix = __(' Engine - faster one available', 'wp-staging');
}

$renderMiniCard = function ($key, $icon, $title, $description, $descriptionIsHtml = false) use ($customizeLinks, $renderer) {
    $customizeLink = isset($customizeLinks[$key]) && is_array($customizeLinks[$key]) ? $customizeLinks[$key] : [];
    $miniCardAttributes = '';

    if (!empty($customizeLink)) {
        $miniCardAttributes = sprintf(
            ' data-wpstg-ready-customize data-wpstg-customize-panel="%s" data-wpstg-customize-content="%s" data-wpstg-customize-trigger="%s"',
            esc_attr(isset($customizeLink['panel']) ? $customizeLink['panel'] : ''),
            esc_attr(isset($customizeLink['content']) ? $customizeLink['content'] : ''),
            esc_attr(isset($customizeLink['trigger']) ? $customizeLink['trigger'] : '')
        );
    }
    ?>
    <div class="wpstg-create-ready-mini-card <?php echo !empty($customizeLink) ? 'wpstg-create-ready-mini-card--customizable' : ''; ?>"<?php echo $miniCardAttributes; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
        <?php $renderer->icon($icon); ?>
        <span class="wpstg-min-w-0 wpstg-flex-1">
            <span class="wpstg-create-ready-mini-card__title">
                <strong><?php echo esc_html($title); ?></strong>
                <?php if (!empty($customizeLink)) : ?>
                    <button
                        type="button"
                        class="wpstg-create-ready-customize"
                        data-wpstg-ready-customize
                        data-wpstg-customize-panel="<?php echo esc_attr(isset($customizeLink['panel']) ? $customizeLink['panel'] : ''); ?>"
                        data-wpstg-customize-content="<?php echo esc_attr(isset($customizeLink['content']) ? $customizeLink['content'] : ''); ?>"
                        data-wpstg-customize-trigger="<?php echo esc_attr(isset($customizeLink['trigger']) ? $customizeLink['trigger'] : ''); ?>"
                    >
                        <?php echo esc_html(isset($customizeLink['label']) ? $customizeLink['label'] : __('Customize', 'wp-staging')); ?>
                    </button>
                <?php endif; ?>
            </span>
            <small><?php echo $descriptionIsHtml ? wp_kses_post($description) : esc_html($description); ?></small>
        </span>
    </div>
    <?php
};
?>
<section class="wpstg-create-ready-card <?php echo esc_attr($cardClass); ?>">
    <div class="wpstg-flex wpstg-items-start wpstg-gap-4">
        <span class="wpstg-create-ready-card__icon" aria-hidden="true">
            <span class="wpstg-create-ready-card__icon-check"><?php $renderer->icon('check', 'wpstg-h-4 wpstg-w-4'); ?></span>
            <span class="wpstg-create-ready-card__icon-warning"><?php $renderer->icon('warning', 'wpstg-h-4 wpstg-w-4'); ?></span>
        </span>
        <div class="wpstg-min-w-0">
            <h2 class="wpstg-m-0 wpstg-text-[15px] wpstg-font-bold wpstg-leading-tight wpstg-text-green-900 dark:wpstg-text-green-200" data-ready-title-default="<?php echo esc_attr($title); ?>" data-ready-title-warning="<?php esc_attr_e('A core folder is excluded', 'wp-staging'); ?>" data-ready-title-warning-table="<?php esc_attr_e('A core table is excluded', 'wp-staging'); ?>" data-ready-title-warning-both="<?php esc_attr_e('Core folders and tables are excluded', 'wp-staging'); ?>"><?php echo esc_html($title); ?></h2>
            <p class="wpstg-m-0 wpstg-mt-2 wpstg-max-w-2xl wpstg-text-[13px] wpstg-font-normal wpstg-leading-relaxed" data-ready-text-default="<?php echo esc_attr($description); ?>" data-ready-text-warning="<?php esc_attr_e('Your staging site may not work as expected. Re-add the folder below if you are not sure.', 'wp-staging'); ?>" data-ready-text-warning-table="<?php esc_attr_e('Your staging site may not work as expected. Re-add the table below if you are not sure.', 'wp-staging'); ?>" data-ready-text-warning-both="<?php esc_attr_e('Your staging site may not work as expected. Re-add the excluded folders and tables below if you are not sure.', 'wp-staging'); ?>"><?php echo esc_html($description); ?></p>
        </div>
    </div>
    <div class="wpstg-create-ready-grid">
        <?php $renderMiniCard('database', 'database', __('Database', 'wp-staging'), $databaseDescription); ?>
        <?php $renderMiniCard('files', 'folder', __('Files', 'wp-staging'), $filesDescription, $filesDescriptionIsHtml); ?>
        <?php $engineDescription = '<span class="wpstg-create-summary-engine-inline">' . esc_html($renderer->getSelectedEngineName()) . '</span><span class="wpstg-create-summary-engine-suffix">' . esc_html($engineSuffix) . '</span>'; ?>
        <?php $renderMiniCard('engine', 'beaker', __('Copy method', 'wp-staging'), $engineDescription, true); ?>
        <?php if ($showRuntimeBehavior) :
            $runtimeCustomize = isset($customizeLinks['runtime']) && is_array($customizeLinks['runtime']) ? $customizeLinks['runtime'] : [];
            $runtimeCardClasses = 'wpstg-create-ready-mini-card';
            if ($runtimeLocked) {
                $runtimeCardClasses .= ' wpstg-create-ready-mini-card--pro';
            }

            if (!empty($runtimeCustomize)) {
                $runtimeCardClasses .= ' wpstg-create-ready-mini-card--customizable';
            }
            ?>
            <div class="<?php echo esc_attr($runtimeCardClasses); ?>"<?php echo !empty($runtimeCustomize) ? ' data-wpstg-ready-customize data-wpstg-customize-panel="' . esc_attr(isset($runtimeCustomize['panel']) ? $runtimeCustomize['panel'] : '') . '"' : ''; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?>>
                <?php $renderer->icon($runtimeLocked ? 'lock' : $runtimeIcon); ?>
                <span class="wpstg-min-w-0 wpstg-flex-1">
                    <span class="wpstg-create-ready-mini-card__title">
                        <strong><?php echo esc_html($runtimeTitle); ?></strong>
                        <?php if (!empty($runtimeCustomize)) : ?>
                            <button type="button" class="wpstg-create-ready-customize" data-wpstg-ready-customize data-wpstg-customize-panel="<?php echo esc_attr(isset($runtimeCustomize['panel']) ? $runtimeCustomize['panel'] : ''); ?>"><?php echo esc_html(isset($runtimeCustomize['label']) ? $runtimeCustomize['label'] : __('Customize', 'wp-staging')); ?></button>
                        <?php endif; ?>
                    </span>
                    <small><?php echo esc_html($runtimeDescription); ?></small>
                </span>
            </div>
        <?php endif; ?>
    </div>
</section>
