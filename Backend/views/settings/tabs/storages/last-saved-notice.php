<?php

/**
 * @var object $options
 *
 * @see /Backend/views/settings/tabs/storages/amazons3-settings.php
 * @see /Backend/views/settings/tabs/storages/googledrive-settings.php
 * @see /Backend/views/settings/tabs/storages/sftp-settings.php
 *
 */

use WPStaging\Framework\Adapter\DateTimeAdapter;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Core;

$lastUpdated = empty($options['lastUpdated']) ? 0 : Sanitize::sanitizeInt($options['lastUpdated']);
$date = new DateTime();
$date->setTimestamp($lastUpdated);

$dateTimeAdapter = Core\WPStaging::make(DateTimeAdapter::class);

?>

        <?php if ($lastUpdated !== 0) { ?>
            <span class="wpstg-badge wpstg-badge-info">Last Saved: <?php echo esc_html($dateTimeAdapter->transformToWpFormat($date)); ?> </span>
        <?php } else { ?>
            <span class="wpstg-badge wpstg-badge-warning"> <?php esc_html_e('Not saved yet!', 'wp-staging') ?> </span>
        <?php } ?>