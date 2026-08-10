<?php

/**
 * The glyph for a WP STAGING capability, so every surface that names one draws
 * the same thing.
 *
 * @var string $capability One of the OnboardingJourney CAPABILITY_* values.
 * @var int    $iconSize   Optional, defaults to the size the selector cards use.
 */

use WPStaging\Framework\Onboarding\OnboardingJourney;

$size = empty($iconSize) ? 22 : (int)$iconSize;
?>
<svg width="<?php echo esc_attr($size); ?>" height="<?php echo esc_attr($size); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <?php if ($capability === OnboardingJourney::CAPABILITY_STAGING) : ?>
        <rect x="9" y="9" width="12" height="12" rx="2.5"></rect>
        <path d="M5 15H4.5A2.5 2.5 0 0 1 2 12.5v-8A2.5 2.5 0 0 1 4.5 2h8A2.5 2.5 0 0 1 15 4.5V5"></path>
    <?php else : ?>
        <path d="M12 2v8"></path>
        <path d="m16 6-4 4-4-4"></path>
        <rect x="2" y="14" width="20" height="8" rx="2.5"></rect>
        <path d="M6 18h.01"></path>
        <path d="M10 18h.01"></path>
    <?php endif; ?>
</svg>
