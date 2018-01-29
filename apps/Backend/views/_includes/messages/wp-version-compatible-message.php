<div class="error">
    <p>
        <?php
        echo sprintf( __(
                        '<strong>Watch out:</strong> This version of WP Staging has not been tested with your WordPress version %2$s.' .
                        '<br/>As WP Staging is using crucial DB and file functions it\'s important that you are using a ' .
                        'WP Staging version <br> which has been verified to be working with your WordPress version. ' .
                        'You risk unexpected data lose if you do not so! ' .
                        '<p><strong>Get the latest WP Staging plugin from <a href="%1$s" target="_blank">https://wp-staging.com</a>.</strong>', 'wpstg'), 'https://wp-staging.com', get_bloginfo( 'version' ) 
        );
        ?>
    </p>
</div>