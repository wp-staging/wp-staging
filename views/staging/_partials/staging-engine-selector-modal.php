<?php

use WPStaging\Core\WPStaging;
use WPStaging\Staging\Service\StagingEngine;

/**
 * Compact staging engine selector for setup modals.
 *
 * @var string $selectedEngine
 * @var string $groupName
 * @var string $selectorClass
 */

$stagingEngine  = WPStaging::make(StagingEngine::class);
$selectedEngine = empty($selectedEngine) ? $stagingEngine->getEngine() : $selectedEngine;
$groupName      = empty($groupName) ? 'wpstg_staging_engine' : $groupName;
$selectorClass  = empty($selectorClass) ? '' : $selectorClass;

$modalEngines = [
    StagingEngine::ENGINE_LEGACY   => [
        'title'       => esc_html__('Classic Engine', 'wp-staging'),
        'badge'       => '',
        'description' => esc_html__('Stable and widely tested.', 'wp-staging'),
        'badgeClass'  => 'wpstg-bg-blue-600 wpstg-text-white dark:wpstg-bg-blue-500 dark:wpstg-text-white',
        'icon'        => '<path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
    ],
    StagingEngine::ENGINE_NEXT_GEN => [
        'title'       => esc_html__('Next-Gen Engine', 'wp-staging'),
        'badge'       => esc_html__('Beta', 'wp-staging'),
        'description' => esc_html__('Faster cloning engine for testing. Switch back anytime.', 'wp-staging'),
        'feature'     => esc_html__('Up to 3× faster', 'wp-staging'),
        'badgeClass'  => 'wpstg-badge-amber',
        'icon'        => '<path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"></path>',
    ],
];

if (!$stagingEngine->isNextGenEnabled()) {
    $modalEngines[StagingEngine::ENGINE_NEXT_GEN]['disabled']    = true;
    $modalEngines[StagingEngine::ENGINE_NEXT_GEN]['badge']       = esc_html__('Temporarily disabled', 'wp-staging');
    $modalEngines[StagingEngine::ENGINE_NEXT_GEN]['badgeClass']  = 'wpstg-inline-flex wpstg-items-center wpstg-rounded-full wpstg-bg-slate-100 wpstg-px-[7px] wpstg-py-0.5 wpstg-text-[11px] wpstg-font-bold wpstg-leading-none wpstg-text-slate-700 dark:wpstg-bg-slate-800 dark:wpstg-text-slate-300';
    $modalEngines[StagingEngine::ENGINE_NEXT_GEN]['description'] = esc_html__('Temporarily unavailable while we finish improvements. It will be back very soon.', 'wp-staging');
    unset($modalEngines[StagingEngine::ENGINE_NEXT_GEN]['feature']);
}
?>

<section class="wpstg-staging-engine wpstg-modal-staging-engine-selector <?php echo esc_attr($selectorClass); ?>" data-selected-engine="<?php echo esc_attr($selectedEngine); ?>">
    <div class="wpstg-staging-engine-options wpstg-grid wpstg-grid-cols-1 md:wpstg-grid-cols-2 wpstg-gap-2" role="radiogroup" aria-label="<?php esc_attr_e('Copy method', 'wp-staging'); ?>">
        <?php foreach ($modalEngines as $engine => $engineData) :
            $isSelected       = $selectedEngine === $engine;
            $isDisabled       = !empty($engineData['disabled']);
            $cardStateClasses = $isSelected
                ? implode(' ', [
                    'wpstg-border-[#2563eb]',
                    'wpstg-bg-[#f4f8ff]',
                    'wpstg-shadow-[0_0_0_1px_#2563eb]',
                    'hover:wpstg-border-[#2563eb]',
                    'hover:wpstg-shadow-[0_0_0_1px_#2563eb]',
                    'dark:wpstg-border-blue-500',
                    'dark:wpstg-bg-blue-950/30',
                    'dark:wpstg-shadow-[0_0_0_1px_#3b82f6]',
                    'dark:hover:wpstg-border-blue-400',
                    'dark:hover:wpstg-shadow-[0_0_0_1px_#60a5fa]',
                ])
                : implode(' ', [
                    'wpstg-border-[#dfe4ea]',
                    'wpstg-bg-white',
                    'hover:wpstg-border-[#9fb5cf]',
                    'hover:wpstg-shadow-sm',
                    'dark:wpstg-border-slate-700',
                    'dark:wpstg-bg-slate-900/70',
                    'dark:hover:wpstg-border-slate-500',
                    'dark:hover:wpstg-shadow-none',
                ]);
            $cardInteractionClasses = $isDisabled
                ? 'wpstg-cursor-not-allowed wpstg-opacity-60'
                : 'wpstg-cursor-pointer';
            ?>
            <label class="wpstg-staging-engine-card <?php echo $isSelected ? 'is-selected' : ''; ?> <?php echo $isDisabled ? 'is-disabled' : ''; ?> wpstg-relative wpstg-flex wpstg-min-h-[92px] <?php echo esc_attr($cardInteractionClasses); ?> wpstg-flex-col wpstg-justify-start wpstg-rounded-lg wpstg-border wpstg-border-solid <?php echo esc_attr($cardStateClasses); ?> wpstg-px-4 wpstg-py-3 wpstg-transition wpstg-duration-150" data-engine="<?php echo esc_attr($engine); ?>"<?php echo $isDisabled ? ' aria-disabled="true"' : ''; ?>>
                <span class="wpstg-flex wpstg-w-full wpstg-items-start wpstg-gap-3">
                    <span class="wpstg-flex wpstg-min-w-0 wpstg-flex-1 wpstg-flex-wrap wpstg-items-center wpstg-gap-2">
                        <svg class="wpstg-h-[18px] wpstg-w-[18px] wpstg-flex-shrink-0 <?php echo $isSelected ? 'wpstg-text-[#2563eb] dark:wpstg-text-blue-300' : 'wpstg-text-[#64748b] dark:wpstg-text-slate-400'; ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $engineData['icon']; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped ?></svg>
                        <span class="wpstg-text-[13.5px] wpstg-font-bold wpstg-leading-tight wpstg-text-[#1f2937] dark:wpstg-text-slate-100"><?php echo esc_html($engineData['title']); ?></span>
                        <?php if (!empty($engineData['badge'])) : ?>
                            <span class="<?php echo esc_attr($engineData['badgeClass']); ?>"><?php echo esc_html($engineData['badge']); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="wpstg-ml-auto wpstg-flex wpstg-flex-shrink-0 wpstg-items-center wpstg-pt-0.5">
                        <input
                            class="wpstg-radio wpstg-radio-sm wpstg-radio-animated wpstg-staging-engine-radio"
                            type="radio"
                            name="<?php echo esc_attr($groupName); ?>"
                            value="<?php echo esc_attr($engine); ?>"
                            <?php checked($isSelected); ?>
                            <?php disabled($isDisabled); ?>
                        />
                    </span>
                </span>
                <span class="wpstg-mt-2 wpstg-block wpstg-text-sm wpstg-font-normal wpstg-leading-5 wpstg-text-[#60718a] dark:wpstg-text-slate-300"><?php echo esc_html($engineData['description']); ?></span>
                <?php if (!empty($engineData['feature'])) : ?>
                    <span class="wpstg-mt-2 wpstg-inline-flex wpstg-w-fit wpstg-items-center wpstg-gap-1 wpstg-rounded-full wpstg-bg-emerald-50 wpstg-px-2 wpstg-py-0.5 wpstg-text-[11px] wpstg-font-bold wpstg-leading-none wpstg-text-emerald-700 dark:wpstg-bg-emerald-500/15 dark:wpstg-text-emerald-300">
                        <svg class="wpstg-h-3 wpstg-w-3 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"></path></svg><?php echo esc_html($engineData['feature']); ?>
                    </span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
</section>
