<?php $numberOfLoadingBars = $numberOfLoadingBars ?? 10;?>

<div class="wpstg-loading-placeholder-container">
    <div class="wpstg-dom-loading-wrapper">
        <?php
        for ($i = 0; $i < $numberOfLoadingBars; $i++) {
            $classes = ($i === 0) ? ' wpstg-loading-first-line-bar' : (($i === $numberOfLoadingBars - 1) ? ' wpstg-loading-last-line-bar' : '');
            echo '<div class="wpstg-loading-lines-bar' . esc_attr($classes) . '"></div>';
        }
        ?>
        <div class="wpstg-loading-line"></div>
    </div>
</div>
