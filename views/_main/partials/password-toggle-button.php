<?php

/**
 * Shared password visibility toggle button
 *
 * Include this partial inside a `.wpstg-password-toggle-wrapper` div,
 * immediately after the password `<input>`. CSS controls icon visibility
 * via the `aria-pressed` attribute; JS toggles the attribute on click.
 */

?>
<button
    type="button"
    class="wpstg-password-toggle"
    aria-label="<?php echo esc_attr__('Show password', 'wp-staging'); ?>"
    aria-pressed="false"
    data-label-show="<?php echo esc_attr__('Show password', 'wp-staging'); ?>"
    data-label-hide="<?php echo esc_attr__('Hide password', 'wp-staging'); ?>"
>
    <svg class="wpstg-password-toggle-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="wpstg-password-toggle-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
        <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
        <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
        <path d="m2 2 20 20"/>
    </svg>
</button>
