<?php

/**
 * @see /Backend/Pro/views/licensing.php
 *
 * @var object $license
 */

$availableLicensePlansByPriceId = [
    'priceId' => [
        '1'  => [
            'name' => 'Personal License',
        ],
        '7'  => [
            'name' => 'Business License',
        ],
        '13' => [
            'name' => 'Developer License',
        ],
        '3'  => [
            'name' => 'Agency License',
        ]
    ]
];

$customerName      = !empty($license->customer_name) ? $license->customer_name : '';
$customerEmail     = !empty($license->customer_email) ? $license->customer_email : '';
$licensePriceId    = !empty($license->price_id) ? $license->price_id : '';
$licensePlanName   = !empty($availableLicensePlansByPriceId['priceId'][$licensePriceId]['name']) ? $availableLicensePlansByPriceId['priceId'][$licensePriceId]['name'] : '';
$showUpgradeButton = !empty($licensePriceId) && $licensePriceId !== '3';
?>

<div id="wpstg-top-header">
    <span class="wpstg-logo">
        <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo-white-transparent.png")) ?>" width="212" alt="">
    </span>

    <div class="wpstg-version">
    <?php

    echo 'WP Staging ';

    if (WPStaging\Core\WPStaging::isPro()) {
        echo "Pro";
    } ?> v.
        <?php

        echo esc_html(WPStaging\Core\WPStaging::getVersion());

        if (WPStaging\Core\WPStaging::isPro()) {
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

            if ($showUpgradeButton) {
                echo '<div class="wpstg-upgrade-license-container">
                            <a href="' . esc_url(admin_url('admin.php?page=wpstg-license')) . '" class="wpstg-upgrade-license-button" target="_self">Upgrade License</a>
                      </div>';
            }
        } else {
            echo ' <a href="https://wp-staging.com" target="_blank">Free Version</a>
                  <div class="wpstg-upgrade-license-container">
                    <a href="https://wp-staging.com" class="wpstg-upgrade-license-button" target="_blank">Upgrade to Pro</a>
                  </div>';
        }
        ?>
    </div>
</div>
