<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Staging\Service\StagingEngine;

/**
 * Renders the "What would you like to create?" choice and, for a blank site, its WordPress version.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var bool $isProLicenseActive
 */

$isNextGenAvailable = WPStaging::make(StagingEngine::class)->isNextGenEnabled();

$blankHint = '';
if (!$isProLicenseActive) {
    $blankHint = __('Available in WP STAGING Pro.', 'wp-staging');
} elseif (!$isNextGenAvailable) {
    $blankHint = __('Needs the Next-Gen transfer method, which is temporarily unavailable.', 'wp-staging');
}

$isBlankSelectable = $blankHint === '';
?>
<section class="wpstg-create-type" data-wpstg-create-type-section>
    <h2 class="wpstg-create-type__title"><?php esc_html_e('What would you like to create?', 'wp-staging'); ?></h2>
    <div class="wpstg-create-type__options" role="radiogroup" aria-label="<?php esc_attr_e('What would you like to create?', 'wp-staging'); ?>">
        <label class="wpstg-create-type__option">
            <input
                class="wpstg-create-type__input"
                type="radio"
                name="wpstg_create_type"
                id="wpstg_create_type_clone"
                value="clone"
                checked
            />
            <span class="wpstg-create-type__body">
                <?php $renderer->icon('copy', 'wpstg-create-type__icon'); ?>
                <span class="wpstg-create-type__label"><?php esc_html_e('Copy this site (Staging)', 'wp-staging'); ?></span>
            </span>
        </label>
        <label class="wpstg-create-type__option<?php echo $isBlankSelectable ? '' : ' is-locked'; ?>"<?php echo empty($blankHint) ? '' : ' title="' . esc_attr($blankHint) . '"'; ?>>
            <input
                class="wpstg-create-type__input"
                type="radio"
                name="wpstg_create_type"
                id="wpstg_is_blank_site"
                value="blank"
                <?php disabled(!$isBlankSelectable); ?>
            />
            <span class="wpstg-create-type__body">
                <?php $renderer->icon('sparkles', 'wpstg-create-type__icon'); ?>
                <span class="wpstg-create-type__heading">
                    <span class="wpstg-create-type__label"><?php esc_html_e('Create Blank WP Site', 'wp-staging'); ?></span>
                    <?php if (!$isProLicenseActive) : ?>
                        <a class="wpstg-badge-amber wpstg-create-type__badge" href="<?php echo esc_url(Language::getUpgradeUrl('blank_site_badge')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Requires WP STAGING Pro', 'wp-staging'); ?>"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Available in Pro', 'wp-staging'); ?></a>
                    <?php endif; ?>
                </span>
            </span>
        </label>
        <?php if (!$isProLicenseActive) : ?>
            <a class="wpstg-create-summary-pro-link wpstg-create-type__blank-note" href="<?php echo esc_url(Language::getUpgradeUrl('blank_site')); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Requires WP STAGING Pro', 'wp-staging'); ?>"><?php $renderer->icon('sparkles', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Upgrade to create blank staging sites', 'wp-staging'); ?></a>
        <?php elseif (!$isNextGenAvailable) : ?>
            <p class="wpstg-create-type__hint wpstg-create-type__blank-note"><?php echo esc_html($blankHint); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($isBlankSelectable) : ?>
        <div class="wpstg-create-type__version" data-wpstg-blank-site-version hidden>
            <label class="wpstg-create-type__version-label" for="wpstg_blank_wp_version"><?php esc_html_e('WordPress Version', 'wp-staging'); ?></label>
            <select
                id="wpstg_blank_wp_version"
                class="wpstg-input wpstg-input-sm"
                data-wpstg-blank-version-select
                data-loading="<?php esc_attr_e('Loading versions…', 'wp-staging'); ?>"
                data-error="<?php esc_attr_e('Could not load versions', 'wp-staging'); ?>"
            >
                <option value=""><?php esc_html_e('Loading versions…', 'wp-staging'); ?></option>
            </select>
            <p class="wpstg-create-type__version-description"><?php esc_html_e('A fresh WordPress is installed instead of cloning this site. No plugins, themes, uploads, or database content are copied.', 'wp-staging'); ?></p>
            <p class="wpstg-create-type__version-notice" data-wpstg-blank-version-notice hidden>
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: %s: "Add admin account" button that opens the new admin account section. */
                        __('Your current WordPress password uses the 6.8+ (bcrypt) format, which this version cannot read. Use %s below to create a fresh login for the staging site.', 'wp-staging'),
                        '<button type="button" class="wpstg-create-type__version-notice-action" data-wpstg-open-admin-account>' . esc_html__('Add admin account', 'wp-staging') . '</button>'
                    ),
                    [
                        'button' => [
                            'type'                          => [],
                            'class'                         => [],
                            'data-wpstg-open-admin-account' => [],
                        ],
                    ]
                );
                ?>
            </p>
        </div>
    <?php endif; ?>
</section>
