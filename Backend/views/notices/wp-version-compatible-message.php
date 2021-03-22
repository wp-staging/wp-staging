<div class="wpstg-error">
    <p>
        <?php
        echo sprintf( __(
            'Your version of WP STAGING has not been tested with WordPress %2$s.' .
            '<br/><br/>WP STAGING has an enterprise-level quality control that performs a compatibility audit on every new WordPress release.' .
            '<br/>We prioritize testing the Pro version of the plugin first, which receives the compatibility audit earlier than the Free version. If you are in a rush, upgrade to Pro today to get the latest version of WPSTAGING.' .
            '<p><a href="%1$s" target="_blank"><strong>Get the latest version Now</strong></a>.', 'wp-staging'), 'https://wp-staging.com', get_bloginfo( 'version' )
        );
        ?>
    </p>
</div>
