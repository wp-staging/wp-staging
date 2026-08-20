<?php

/**
 * The neutral first-run shell shown while the analytics question is still open.
 *
 * Deliberately silent about staging, backups and Desktop: the selector asks
 * which of them comes first, and naming one here would pre-empt that question.
 *
 * @see \WPStaging\Framework\Onboarding\FreeOnboarding::getStage()
 */

?>
<div class="wpstg-preconsent" role="status" aria-live="polite">
    <p class="wpstg-preconsent__text"><?php esc_html_e('Setting up WP STAGING…', 'wp-staging'); ?></p>
</div>
