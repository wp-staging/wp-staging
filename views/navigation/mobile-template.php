<?php

/**
 * @see views/navigation/web-template.php
 * @var string $licenseMessage
 * @var array $menu
 * @var string $wpstgAdminUrl
 */
?>

<div class="wpstg-navbar-wrapper wpstg-navigation-menu-mobile">
    <button class="wpstg-navbar-toggler wpstg-mr-10px" type="button" onclick="WPStaging.handleToggleElement(this)" data-wpstg-target="#wpstg-navbar-menu">
        <span class="wpstg-hamburger-icon"></span>
    </button>
    <div id="wpstg-navbar-menu" class="wpstg-navbar-menu hidden">
        <?php foreach ($menu as $tabKey => $tab) :?>
        <div>
            <a href="javascript:void(0)" data-target-url="<?php echo esc_url($wpstgAdminUrl . $tab['page']); ?>" class="wpstg-button <?php echo ($tab['isActive']) ? 'wpstg--tab--active' : '' ?>"  data-target-id="<?php esc_attr_e($tab['id'], 'wp-staging') ?>">
                <?php
                if ($tabKey !== 'tab-license') {
                    echo esc_html($tab['tab']);
                }

                if ($tabKey === 'tab-license' && defined('WPSTGPRO_VERSION')) :
                    echo esc_html($tab['tab']);
                    ?>
                    <span class="wpstg--red-warning"><?php echo esc_html($licenseMessage); ?></span>
                <?php endif;

                if ($tabKey === 'tab-license' && !defined('WPSTGPRO_VERSION')) : ?>
                    <span class="wpstg--red-warning"><?php echo esc_html($tab['tab']); ?> </span>
                <?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
