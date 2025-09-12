<?php
$numberOfLoadingBars = $numberOfLoadingBars ?? 10;
$numberOfFallbackPlaceholders = 10;
?>

<div class="wpstg-loading-placeholder-container">
    <div class="wpstg-dom-loading-wrapper">
        <?php
        for ($i = 0; $i < $numberOfLoadingBars; $i++) {
            $classes = '';

            if ($i === 0) {
                $classes .= ' wpstg-loading-first-line-bar';
            } elseif ($i === $numberOfLoadingBars - 1) {
                $classes .= ' wpstg-loading-last-line-bar';
            }

            echo '<div class="wpstg-loading-lines-bar' . esc_attr($classes) . '"></div>';

            // Add fallback placeholders
            if ($i === 0 || $i === $numberOfLoadingBars - 1) {
                for ($j = 0; $j < $numberOfFallbackPlaceholders; $j++) {
                    echo '<div class="wpstg-loading-lines-bar wpstg-fallback-placeholder"></div>';
                }
            }
        }
        ?>
        <div class="wpstg-loading-line"></div>
    </div>
</div>
