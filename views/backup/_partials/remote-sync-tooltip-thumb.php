<?php

/**
 * Poster image for the Remote Sync demo video, which is only fetched from Vimeo once the visitor clicks it.
 *
 * @var string $urlAssets
 */

?>
<span class="wpstg-remote-sync-tooltip-thumb"
      role="button" tabindex="0"
      aria-label="<?php echo esc_attr__('Play demo video', 'wp-staging'); ?>"
      data-vimeo-id="1162852843"
      data-img="<?php echo esc_url($urlAssets); ?>img/thumbnail-small-dark.webp">
    <img class="wpstg-remote-sync-tooltip-thumb-img"
         src="<?php echo esc_url($urlAssets); ?>img/thumbnail-small-dark.webp"
         alt="<?php echo esc_attr__('Remote Sync demo', 'wp-staging'); ?>"
         width="320" height="180" loading="lazy" />
    <span class="wpstg-remote-sync-tooltip-duration">46s</span>
</span>
