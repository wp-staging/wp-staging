<div class="error">
    <p>
        <?php
        echo sprintf( __(
                        'You are using a version of WP Staging which has not been tested with your WordPress version %2$s.' .
                        '<br/> As WP Staging is using crucial DB and file functions it\'s important that you are using a ' .
                        'WP Staging version <br> which has been verified to be working with your WordPress version. ' .
                        'You risk unexpected results up to data lose if you do not so. ' .
                        '<p>Please look at <a href="%1$s" target="_blank">%1$s</a> for the latest WP Staging version.', 'wpstg'), 'https://wp-staging.com', get_bloginfo( 'version' ) 
        );
        ?>
    </p>
</div>