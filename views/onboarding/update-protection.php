<?php

/**
 * Update Protection status line, shown between the lede and the three actions.
 *
 * Deliberately not a card: it is not a fourth thing to choose, only a sentence
 * saying the feature is already on before the user meets it on the plugins
 * screen. Rendering it also counts as the modal's introduction, so the same
 * explanation is not repeated the first time an update is intercepted.
 *
 * @see \WPStaging\Backup\Service\UpdateProtectionSettings
 */

use WPStaging\Backup\Service\UpdateProtectionSettings;
use WPStaging\Core\WPStaging;

$updateProtection = WPStaging::make(UpdateProtectionSettings::class);
$updateProtection->markIntroSeen('modal');

$isProtectionEnabled = $updateProtection->isEnabled();
?>
<div
    class="wpstg-onboarding-protection"
    data-wpstg-update-protection
    data-state="<?php echo $isProtectionEnabled ? 'on' : 'off'; ?>"
    aria-live="polite"
>
    <p class="wpstg-onboarding-protection__line">
        <svg class="wpstg-onboarding-protection__shield" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"></path>
        </svg>

        <span data-wpstg-update-protection-on>
            <strong><?php esc_html_e('Plugin updates are now protected', 'wp-staging'); ?></strong>
            <?php esc_html_e('WP STAGING checks for a recent backup before plugin updates and offers to create one when needed.', 'wp-staging'); ?>
        </span>

        <span data-wpstg-update-protection-off>
            <strong><?php esc_html_e('Update Protection is off', 'wp-staging'); ?></strong>
            <?php esc_html_e('Plugin updates will run normally without WP STAGING checking for a recent backup first.', 'wp-staging'); ?>
        </span>

        <span data-wpstg-update-protection-confirm>
            <strong><?php esc_html_e('Turn off Update Protection?', 'wp-staging'); ?></strong>
            <?php esc_html_e('Plugin updates will work normally without this backup prompt. WP STAGING will remain active.', 'wp-staging'); ?>
        </span>
    </p>

    <p class="wpstg-onboarding-protection__actions">
        <span class="wpstg-onboarding-protection__state" data-wpstg-update-protection-on>
            <?php esc_html_e('Update Protection is on', 'wp-staging'); ?>
        </span>
        <button
            type="button"
            class="wpstg-onboarding-protection__link"
            data-wpstg-update-protection-action="confirm-disable"
            data-wpstg-update-protection-on
        >
            <?php esc_html_e('Turn off', 'wp-staging'); ?>
        </button>

        <button
            type="button"
            class="wpstg-onboarding-protection__link"
            data-wpstg-update-protection-action="enable"
            data-wpstg-update-protection-off
        >
            <?php esc_html_e('Turn on', 'wp-staging'); ?>
        </button>

        <button
            type="button"
            class="wpstg-onboarding-protection__link wpstg-onboarding-protection__link--strong"
            data-wpstg-update-protection-action="disable"
            data-wpstg-update-protection-confirm
        >
            <?php esc_html_e('Turn Off Update Protection', 'wp-staging'); ?>
        </button>
        <button
            type="button"
            class="wpstg-onboarding-protection__link"
            data-wpstg-update-protection-action="keep"
            data-wpstg-update-protection-confirm
        >
            <?php esc_html_e('Keep Protection', 'wp-staging'); ?>
        </button>
    </p>
</div>
