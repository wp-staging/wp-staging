<?php

/**
 * The way into the staging site that was just created — an action rather than
 * the bare address the success modal used to print. Rendered without an address
 * when there is no staging site yet; the browser fills it in when one is created
 * without a page load in between.
 *
 * @var string $stagingSiteUrl
 */
?>
<a
    class="wpstg-btn wpstg-btn-lg wpstg-btn-primary wpstg-onboarding-next__site"
    data-wpstg-onboarding-site-url
    <?php echo $stagingSiteUrl === '' ? 'hidden' : 'href="' . esc_url($stagingSiteUrl) . '"'; ?>
    target="_blank"
    rel="noopener noreferrer"
>
    <?php esc_html_e('Open Staging Site', 'wp-staging'); ?>
    <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <path d="M15 3h6v6"></path>
        <path d="M10 14 21 3"></path>
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
    </svg>
</a>
