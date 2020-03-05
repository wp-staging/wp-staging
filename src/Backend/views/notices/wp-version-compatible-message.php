<div class="wpstg-error">
    <p>
        <?php
        echo sprintf( __(
                        'Your version of WP Staging has not been tested with WordPress %2$s.' .
                        '<br/>WP Staging is using crucial DB and file functions, so it\'s important that you are using a ' .
                        'WP Staging version <br> which has been verified to be fully working with your WordPress version. ' .
                        'You risk unexpected results, up to data lose if you do not so. ' .
                        '<p><a href="%1$s" target="_blank"><strong>Get the latest version Now</strong></a>.', 'wp-staging'), 'https://wp-staging.com', get_bloginfo( 'version' )
        );
        ?>
    </p>
</div>
