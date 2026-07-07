<?php

use WPStaging\Core\WPStaging;
use WPStaging\Staging\Service\StagingEngine;

$stagingEngine  = WPStaging::make(StagingEngine::class);
$selectedEngine = $stagingEngine->getEngine();
$groupName      = 'wpstg_staging_engine';

$engines = [
    StagingEngine::ENGINE_LEGACY   => [
        'title'       => esc_html__('Classic Engine', 'wp-staging'),
        'badge'       => esc_html__('DEFAULT', 'wp-staging'),
        'description' => esc_html__('The stable, proven engine used by earlier WP Staging versions.', 'wp-staging'),
        'icon'        => 'clock',
    ],
    StagingEngine::ENGINE_NEXT_GEN => [
        'title'       => esc_html__('Next-Gen Engine', 'wp-staging'),
        'badge'       => esc_html__('BETA', 'wp-staging'),
        'description' => esc_html__('Uses the new staging engine designed for faster and more resilient jobs.', 'wp-staging'),
        'icon'        => 'bolt',
    ],
];

if (!$stagingEngine->isNextGenEnabled()) {
    $engines[StagingEngine::ENGINE_NEXT_GEN]['disabled']    = true;
    $engines[StagingEngine::ENGINE_NEXT_GEN]['badge']       = esc_html__('Temporarily disabled', 'wp-staging');
    $engines[StagingEngine::ENGINE_NEXT_GEN]['description'] = esc_html__('Temporarily unavailable while we finish improvements. It will be back very soon.', 'wp-staging');
}
?>

<section class="wpstg-staging-engine wpstg-my-[18px] wpstg-mb-4" data-selected-engine="<?php echo esc_attr($selectedEngine); ?>">
    <h3 class="wpstg-m-0 wpstg-mb-1.5 wpstg-text-base wpstg-font-bold wpstg-leading-tight wpstg-text-[#2c3338] dark:wpstg-text-slate-100"><?php esc_html_e('Staging Engine', 'wp-staging'); ?></h3>
    <p class="wpstg-m-0 wpstg-mb-3 wpstg-text-sm wpstg-leading-normal wpstg-text-[#536579] dark:wpstg-text-slate-400">
        <?php esc_html_e('Choose the staging engine. This will also be used for future staging actions until changed.', 'wp-staging'); ?>
    </p>
    <div class="wpstg-staging-engine-options wpstg-grid wpstg-grid-cols-1 md:wpstg-grid-cols-2 wpstg-gap-3" role="radiogroup" aria-label="<?php esc_attr_e('Staging Engine', 'wp-staging'); ?>">
        <?php foreach ($engines as $engine => $engineData) :
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
            $iconStateClasses = $isSelected
                ? 'wpstg-bg-[#2563eb] wpstg-text-[#f8fbff] dark:wpstg-bg-blue-600 dark:wpstg-text-white'
                : 'wpstg-bg-[#f1f4f8] wpstg-text-[#7c8da3] dark:wpstg-bg-slate-800 dark:wpstg-text-slate-300';
            $badgeClasses = 'wpstg-bg-slate-100 wpstg-text-slate-700 dark:wpstg-bg-slate-800 dark:wpstg-text-slate-300';
            if (!$isDisabled && $engine === StagingEngine::ENGINE_NEXT_GEN) {
                $badgeClasses = 'wpstg-bg-blue-100 wpstg-text-blue-700 dark:wpstg-bg-blue-900/70 dark:wpstg-text-blue-200';
            }

            $cardInteractionClasses = $isDisabled
                ? 'wpstg-cursor-not-allowed wpstg-opacity-60'
                : 'wpstg-cursor-pointer';
            ?>
            <label class="wpstg-staging-engine-card <?php echo $isSelected ? 'is-selected' : ''; ?> <?php echo $isDisabled ? 'is-disabled' : ''; ?> wpstg-relative wpstg-flex <?php echo esc_attr($cardInteractionClasses); ?> wpstg-flex-col wpstg-gap-1.5 wpstg-rounded-lg wpstg-border wpstg-border-solid <?php echo esc_attr($cardStateClasses); ?> wpstg-px-5 wpstg-py-4 wpstg-transition wpstg-duration-150" data-engine="<?php echo esc_attr($engine); ?>"<?php echo $isDisabled ? ' aria-disabled="true"' : ''; ?>>
                <span class="wpstg-flex wpstg-w-full wpstg-items-center wpstg-gap-3">
                    <span class="wpstg-staging-engine-icon wpstg-staging-engine-icon-<?php echo esc_attr($engineData['icon']); ?> wpstg-inline-flex wpstg-h-10 wpstg-w-10 wpstg-flex-shrink-0 wpstg-items-center wpstg-justify-center wpstg-rounded-lg <?php echo esc_attr($iconStateClasses); ?>" aria-hidden="true">
                        <?php if ($engineData['icon'] === 'bolt') : ?>
                            <svg class="wpstg-h-5 wpstg-w-5" viewBox="0 0 24 24" fill="currentColor" focusable="false" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/></svg>
                        <?php else : ?>
                            <svg class="wpstg-h-5 wpstg-w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" focusable="false" aria-hidden="true"><path d="M12 8v5l3 2"/><path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z"/></svg>
                        <?php endif; ?>
                    </span>
                    <span class="wpstg-flex wpstg-min-w-0 wpstg-flex-1 wpstg-flex-wrap wpstg-items-center wpstg-gap-1.5">
                        <span class="wpstg-text-[15px] wpstg-font-bold wpstg-leading-tight wpstg-text-[#2c3338] dark:wpstg-text-slate-100"><?php echo esc_html($engineData['title']); ?></span>
                        <span class="wpstg-inline-flex wpstg-min-h-5 wpstg-items-center wpstg-rounded <?php echo esc_attr($badgeClasses); ?> wpstg-px-[7px] wpstg-py-0.5 wpstg-text-[11px] wpstg-font-bold wpstg-leading-none"><?php echo esc_html($engineData['badge']); ?></span>
                    </span>
                    <span class="wpstg-ml-auto wpstg-flex wpstg-flex-shrink-0 wpstg-items-center">
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
                <span class="wpstg-block wpstg-text-[13px] wpstg-font-medium wpstg-leading-[1.45] wpstg-text-[#667789] dark:wpstg-text-slate-300"><?php echo esc_html($engineData['description']); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="wpstg-staging-engine-notice <?php echo $selectedEngine === StagingEngine::ENGINE_NEXT_GEN ? 'is-visible wpstg-flex' : 'wpstg-hidden'; ?> wpstg-mt-3 wpstg-items-center wpstg-gap-2.5 wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-border-[#dfe4ea] wpstg-bg-slate-50 wpstg-px-4 wpstg-py-3 wpstg-text-[13px] wpstg-leading-normal wpstg-text-[#152f4f] dark:wpstg-border-slate-700 dark:wpstg-bg-slate-900/70 dark:wpstg-text-slate-300" aria-live="polite">
        <svg class="wpstg-h-4 wpstg-w-4 wpstg-flex-shrink-0 wpstg-text-[#7c8da3] dark:wpstg-text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 16v-4"></path>
            <path d="M12 8h.01"></path>
        </svg>
        <p class="wpstg-m-0">
            <strong class="wpstg-font-bold wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php esc_html_e('Next-Gen Engine will become your default when this staging job starts.', 'wp-staging'); ?></strong>
            <?php esc_html_e('WP STAGING will use it for this staging site and future staging jobs. You can switch back anytime.', 'wp-staging'); ?>
        </p>
    </div>
</section>
