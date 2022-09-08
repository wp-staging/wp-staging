<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-warning">
    <p>
        <?php

        echo sprintf(
            Escape::escapeHtml(__(
                '<strong>This version of WP STAGING has not been tested with WordPress %2$s.</strong>' .
                '<br/><br/>WP STAGING has an enterprise-level quality control that performs a compatibility audit on every new WordPress release.' .
                '<br/>We prioritize testing the Pro version of the plugin first, which receives the compatibility audit earlier than the Free version. If you are in a rush, upgrade to Pro today to get the latest compatible version of WP STAGING or wait a few days until we update the free version.' .
                '<a href="%1$s" target="_blank"><strong>Get the Latest Pro Version Now</strong></a>.'
            ), 'wp-staging'),
            'https://wp-staging.com?utm_source=free-plugin&utm_medium=backend&utm_campaign=compatible-message',
            esc_html(get_bloginfo('version'))
        );
        ?>
    </p>
</div>
