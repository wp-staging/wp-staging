<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Newsfeed\NewsfeedProvider;

?>

<div id="wpstg-news" class="wpstg-block">
        <?php
        $newsfeedProvider = WPStaging::make(NewsfeedProvider::class);
        $newsfeedProvider->printNewsFeed();
        ?>
</div>
