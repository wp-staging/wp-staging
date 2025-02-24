<?php

use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Staging\Service\DirectoryScanner as Scanner;

/**
 * @see \WPStaging\Staging\Service\DirectoryScanner:renderDirectoryNode
 *
 * @var Scanner $scanner
 * @var string  $prefix
 * @var string  $relPath
 * @var string  $class
 * @var string  $dirType
 * @var string  $isScanned
 * @var string  $isNavigatable
 * @var bool    $shouldBeChecked
 * @var bool    $parentChecked
 * @var string  $directoryDisabled
 * @var string  $dirName
 * @var string  $gifLoaderPath
 * @var string  $formattedSize
 * @var bool    $isDebugMode
 * @var string  $dataPath
 * @var string  $basePath
 * @var bool    $forceDefault
 * @var string  $dirPath
 * @var bool    $isLink
 */

?>

<div class="wpstg-dir">
    <?php
        $dataAttributes = [
            'dirType'       => $dirType,
            'path'          => $relPath,
            'prefix'        => $prefix,
            'isScanned'     => $isScanned,
            'isNavigatable' => $isNavigatable,
        ];
        $attributes = [
            'classes'    => 'wpstg-check-dir ' . $class,
            'isDisabled' => $isDisabled,
        ];
        Checkbox::render('', 'selectedDirectories[]', $prefix . $relPath, ($shouldBeChecked && ($parentChecked !== false)), $attributes, $dataAttributes);
        ?>
    <a href="#" class='wpstg-expand-dirs <?php echo ($isDisabled) ? 'wpstg-storage-settings-disabled' : ''; ?> <?php echo esc_attr($directoryDisabled); ?>'><?php echo esc_html($dirName); ?></a>
    <?php if ($isLink) : ?>
        <span class="wpstg--tooltip">
            <img class="wpstg--dashicons wpstg-mb--4" src="<?php echo esc_url($scan->getInfoIcon()); ?>" alt="info" />
            <span class="wpstg--tooltiptext">
                <?php echo sprintf(
                    esc_html__('The folder %s is a symlink. All the data in this folder will be selected by default.', 'wp-staging'),
                    esc_html($dirName)
                ); ?>
            </span>
        </span>
    <?php endif; ?>
    <?php if ($isNavigatable === 'true' && !empty($gifLoaderPath)) : ?>
    <img src='<?php echo esc_url($gifLoaderPath); ?>' class='wpstg-is-dir-loading' alt='loading' />
    <?php endif; ?>
    <span class='wpstg-size-info'><?php echo esc_html($formattedSize); ?></span>
    <?php if ($isDebugMode) : ?>
    <span class='wpstg-size-info'><?php echo esc_html($dataPath); ?></span>
    <?php endif; ?>
    <?php if ($isScanned === 'true') : ?>
        <div class="wpstg-dir wpstg-subdir" style="display: none;">
        <?php
            $directories = $scanner->scanDirectory($dirPath, $basePath, $prefix);
            echo $scanner->directoryListing($directories, $parentChecked, $forceDefault); // phpcs:ignore
        ?>
        </div>
    <?php endif; ?>
</div>
