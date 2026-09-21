<?php

/**
 * One command line block: the terminal window, its copy/reveal buttons, and the command itself.
 *
 * @var string $osId       Matches the data-os attribute and the element ids the JS toggles.
 * @var string $cmdFull
 * @var string $cmdMasked
 * @var bool   $hasLicense
 */

?>
<input id="wpstg-cli-cmd-<?php echo esc_attr($osId); ?>-full" type="hidden" value="<?php echo esc_attr($cmdFull); ?>" />
<input id="wpstg-cli-cmd-<?php echo esc_attr($osId); ?>-masked" type="hidden" value="<?php echo esc_attr($cmdMasked); ?>" />
<div class="wpstg-cli-terminal wpstg-w-full wpstg-rounded-xl wpstg-overflow-hidden wpstg-bg-terminal-bg">
    <div class="wpstg-cli-terminal-header wpstg-flex wpstg-items-center wpstg-justify-between wpstg-px-4 wpstg-bg-terminal-bg">
        <?php require WPSTG_VIEWS_DIR . 'cli/_partials/terminal-dots.php'; ?>
        <div class="wpstg-flex wpstg-items-center wpstg-gap-1">
            <?php if ($hasLicense) : ?>
            <button type="button" class="wpstg-cli-license-toggle wpstg-p-2 wpstg-rounded-lg wpstg-border-0 wpstg-bg-transparent wpstg-transition-all wpstg-duration-200 wpstg-text-terminal-muted hover:wpstg-bg-terminal-border wpstg-cursor-pointer" data-os="<?php echo esc_attr($osId); ?>" title="<?php echo esc_attr__('Show/hide license key', 'wp-staging'); ?>">
                <?php require WPSTG_VIEWS_DIR . 'cli/_partials/icons/eye-toggle.php'; ?>
            </button>
            <?php endif; ?>
            <button type="button" class="wpstg-cli-copy-button wpstg-p-2 wpstg-rounded-lg wpstg-border-0 wpstg-bg-transparent wpstg-transition-all wpstg-duration-200 wpstg-text-terminal-muted hover:wpstg-bg-terminal-border wpstg-cursor-pointer" data-wpstg-source="#wpstg-cli-cmd-<?php echo esc_attr($osId); ?>-full" title="<?php echo esc_attr__('Copy command', 'wp-staging'); ?>">
                <?php require WPSTG_VIEWS_DIR . 'cli/_partials/icons/copy.php'; ?>
            </button>
        </div>
    </div>
    <div class="wpstg-cli-terminal-body wpstg-px-4 wpstg-py-3">
        <code class="wpstg-flex wpstg-items-center wpstg-text-sm wpstg-leading-relaxed wpstg-break-all wpstg-font-mono wpstg-text-terminal-text">
            <span class="wpstg-mr-3 wpstg-font-semibold wpstg-text-terminal-prompt">$</span>
            <span id="wpstg-cli-cmd-<?php echo esc_attr($osId); ?>-text" <?php echo $hasLicense ? 'data-masked="true"' : ''; ?>><?php echo esc_html($hasLicense ? $cmdMasked : $cmdFull); ?></span>
        </code>
    </div>
</div>
