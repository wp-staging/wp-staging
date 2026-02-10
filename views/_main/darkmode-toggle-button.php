<?php

use WPStaging\Framework\Settings\DarkMode;

$defaultColorMode = get_option(DarkMode::OPTION_DEFAULT_COLOR_MODE, '');

// Normalize: anything other than 'light' or 'dark' becomes 'system'
if ($defaultColorMode !== 'light' && $defaultColorMode !== 'dark') {
    $defaultColorMode = 'system';
}
?>

<div class="wpstg-theme-toggle" data-theme-mode="<?php echo esc_attr($defaultColorMode); ?>">
    <button
        type="button"
        data-theme-btn
        class="wpstg-theme-toggle-btn"
        title="Theme: <?php echo esc_attr(ucfirst($defaultColorMode)); ?>"
        aria-label="Theme: <?php echo esc_attr(ucfirst($defaultColorMode)); ?>"
    >
        <svg data-theme-icon="system" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3V1M12 23v-2M4.22 4.22L5.64 5.64M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
            <path d="M12 7a5 5 0 0 1 0 10" fill="none" />
            <path d="M12 7a5 5 0 0 0 0 10" fill="currentColor" opacity="0.3" stroke="currentColor" />
        </svg>

        <svg data-theme-icon="light" class="wpstg-hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5" />
            <line x1="12" y1="1" x2="12" y2="3" />
            <line x1="12" y1="21" x2="12" y2="23" />
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
            <line x1="1" y1="12" x2="3" y2="12" />
            <line x1="21" y1="12" x2="23" y2="12" />
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
        </svg>

        <svg data-theme-icon="dark" class="wpstg-hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>

        <span data-theme-badge="auto" class="wpstg-theme-toggle-badge">A</span>
    </button>
</div>