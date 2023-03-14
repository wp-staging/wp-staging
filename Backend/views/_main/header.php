<?php

/**
 * @see /Backend/Pro/views/licensing.php
 *
 * @var object $license
 */

$availableLicensePlansByPriceId = [
    'priceId' => [
        '1' => [
            'name' => 'Personal License',
        ],
        '7' => [
            'name' => 'Business License',
        ],
        '13' => [
            'name' => 'Developer License',
        ],
        '3' => [
            'name' => 'Agency License',
        ]
    ]
];

$customerName    = !empty($license->customer_name) ? $license->customer_name : '';
$customerEmail   = !empty($license->customer_email) ? $license->customer_email : '';
$licensePriceId  = !empty($license->price_id) ? $license->price_id : '';
$licensePlanName = !empty($availableLicensePlansByPriceId['priceId'][$licensePriceId]['name']) ? $availableLicensePlansByPriceId['priceId'][$licensePriceId]['name'] : '';
?>

<div id="wpstg-top-header">
<span class="wpstg-logo">
    <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo-white-transparent.png")) ?>" width="212" alt="">
</span>

    <span class="wpstg-version">
    <?php

    echo 'WP Staging ';

    if (defined('WPSTGPRO_VERSION')) {
        echo "Pro";
    } ?> v.
        <?php

        echo esc_html(WPStaging\Core\WPStaging::getVersion());

        if (defined('WPSTGPRO_VERSION')) {
            if (!empty($licensePlanName)) {
                echo ' <a href="https://wp-staging.com" target="_blank">' . esc_html($licensePlanName) . '</a>';
            }

            if (!empty($customerName) || !empty($customerEmail)) {
                echo '<br>';
            }

            if (!empty($customerName)) {
                echo esc_html($customerName) . ' ';
            }

            if (!empty($customerEmail)) {
                echo sprintf('&lt;%s&gt', esc_html($customerEmail));
            }
        } else {
            echo ' <a href="https://wp-staging.com" target="_blank">Free Version</a>';
        }
        ?>
    </span>
</div>
<div class="wpstg-header">
    <?php if (isset($_GET['page']) && $_GET['page'] === 'wpstg_clone' || $_GET['page'] === 'wpstg_backup') { ?>
        <?php
        $latestReleasedVersion = get_option('wpstg_version_latest');
        $display               = 'none;';

        if (defined('WPSTGPRO_VERSION')) {
            $outdatedVersionCheck  = new WPStaging\Backend\Notices\OutdatedWpStagingNotice();
            $latestReleasedVersion = $outdatedVersionCheck->getLatestWpstgProVersion();
            if ($outdatedVersionCheck->isOutdatedWpStagingProVersion()) {
                $display = 'block;';
            }
        }
        ?>

        <div id="wpstg-update-notify" style="display:<?php echo esc_attr($display); ?>">
            <strong><?php echo sprintf(__("New: WP Staging Pro v. %s is available.", 'wp-staging'), esc_html($latestReleasedVersion)); ?></strong><br/>
            <?php echo sprintf(__('Important: Please update the plugin before pushing the staging site to production site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
        </div>

    <?php } ?>

</div>
