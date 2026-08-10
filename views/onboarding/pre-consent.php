<?php

/**
 * The neutral first-run shell shown while the analytics question is still open.
 *
 * Identical for both variants, and deliberately silent about staging, backups
 * and Desktop: naming any first action would leak part of the treatment.
 *
 * @see \WPStaging\Framework\Onboarding\FreeOnboarding::getStage()
 */

?>
<div class="wpstg-preconsent" role="status" aria-live="polite">
    <p class="wpstg-preconsent__text"><?php esc_html_e('Setting up WP STAGING…', 'wp-staging'); ?></p>
</div>
