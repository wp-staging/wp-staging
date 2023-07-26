<?php

use WPStaging\Backend\Modules\Jobs\Scan;

/**
 * This file is currently being called for the both FREE and PRO version:
 * @see src/Backend/views/clone/ajax/scan.php:getDirectoryHtml
 *
 * @var Scan   $scan
 * @var string $prefix
 * @var string $relPath
 * @var string $class
 * @var string $dirType
 * @var string $isScanned
 * @var string $isNavigateable
 * @var bool   $shouldBeChecked
 * @var bool   $parentChecked
 * @var string $directoryDisabled
 * @var string $dirName
 * @var string $gifLoaderPath
 * @var string $formattedSize
 * @var bool   $isDebugMode
 * @var string $dataPath
 * @var string $basePath
 * @var bool   $forceDefault
 * @var string $dirPath
 */

?>

<div class="wpstg-dir">
    <input type="checkbox"
        name='selectedDirectories[]'
        value='<?php echo esc_attr($prefix) . esc_attr($relPath) ; ?>'
        class="wpstg-checkbox wpstg-checkbox--small wpstg-check-dir <?php echo esc_attr($class); ?>"
        wpstg-data-dir-type='<?php echo esc_attr($dirType); ?>'
        wpstg-data-prefix='<?php echo esc_attr($prefix); ?>'
        wpstg-data-path='<?php echo esc_attr($relPath); ?>'
        wpstg-data-scanned='<?php echo esc_attr($isScanned); ?>'
        wpstg-data-navigateable='<?php echo esc_attr($isNavigateable); ?>'
        <?php echo ($shouldBeChecked && ($parentChecked !== false)) ? 'checked' : ''; ?> />
    <a href="#" class='wpstg-expand-dirs <?php echo esc_attr($directoryDisabled); ?>'><?php echo esc_html($dirName); ?></a>
    <?php if ($isNavigateable === 'true' && !empty($gifLoaderPath)) : ?>
    <img src='<?php echo esc_url($gifLoaderPath); ?>' class='wpstg-is-dir-loading' alt='loading' />
    <?php endif; ?>
    <span class='wpstg-size-info'><?php echo esc_html($formattedSize); ?></span>
    <?php if ($isDebugMode) : ?>
    <span class='wpstg-size-info'><?php echo esc_html($dataPath); ?></span>
    <?php endif; ?>
    <?php if ($isScanned === 'true') : ?>
        <div class="wpstg-dir wpstg-subdir" style="display: none;">
        <?php
            $scan->setBasePath($basePath);
            $scan->setPathIdentifier($prefix);
            $directories = $scan->getDirectories($dirPath, $return = true);
            echo $scan->directoryListing($parentChecked, $forceDefault, $directories); // phpcs:ignore
        ?>
        </div>
    <?php endif; ?>
</div>
