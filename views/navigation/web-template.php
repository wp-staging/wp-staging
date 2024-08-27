<?php
/**
 * @see views/_main/main-navigation.php
 * @see views/clone/index.php
 * @var bool $isBackupPage
 * @var bool $isStagingPage
 * @var object $backupNotice
 * @var object $license
 * @var bool $isCalledFromIndex
 */

$wpstgAdminUrl          = get_admin_url() . 'admin.php?page=';
$menu = [
    'tab-staging' => [
        'tab'       => esc_html('Staging'),
        'id'        => 'wpstg--tab--toggle--staging',
        'targetId'  => '',
        'targetUrl' => $wpstgAdminUrl . 'wpstg_clone',
        'page'      => 'wpstg_clone',
        'isActive'  => !empty($isStagingPage),
    ],
    'tab-backup' => [
        'tab'       => esc_html('Backup & Migration'),
        'id'        => 'wpstg--tab--toggle--backup',
        'targetId'  => '',
        'targetUrl' => $wpstgAdminUrl . 'wpstg_backup',
        'page'      => 'wpstg_backup',
        'isActive'  => !empty($isBackupPage),
    ],
    'tab-settings' => [
        'tab'       => __('Settings', 'wp-staging'),
        'id'        => 'wpstg--tab--toggle--settigs',
        'targetId'  => '',
        'targetUrl' => esc_url($wpstgAdminUrl) . 'wpstg-settings',
        'page'      => 'wpstg-settings',
        'isActive'  => !empty($isActiveSettingsPage),
    ],
    'tab-system-info' => [
        'tab'       => esc_html('System Info'),
        'id'        => 'wpstg--tab--toggle--systeminfo',
        'targetId'  => '',
        'targetUrl' => esc_url($wpstgAdminUrl) . 'wpstg-tools',
        'page'      => 'wpstg-tools',
        'isActive'  => !empty($isActiveSystemInfoPage),
    ],
    'tab-license' => [
        'tab'       => __('Upgrade to Pro', 'wp-staging'),
        'id'        => 'wpstg--tab--toggle--license',
        'targetId'  => '',
        'targetUrl' => 'https://wp-staging.com',
        'page'      => 'wpstg-license',
        'isActive'  => !empty($isActiveLicensePage),
    ]
];

if ($isCalledFromIndex) {
    $menu['tab-staging']['targetId']  = '#wpstg--tab--staging';
    $menu['tab-backup']['targetId']   = '#wpstg--tab--backup';
    $menu['tab-staging']['targetUrl'] = 'javascript:void(0)';
    $menu['tab-backup']['targetUrl']  = 'javascript:void(0)';
}

$licenseMessage    = '';
if (defined('WPSTGPRO_VERSION')) {
    $licenseMessage                   = isset($license->license) && $license->license === 'valid' ? '' : __('(Unregistered)', 'wp-staging');
    $menu['tab-license']['tab']       = __('License', 'wp-staging');
    $menu['tab-license']['targetUrl'] = esc_url($wpstgAdminUrl) . 'wpstg-license';
}
?>
<div class="wpstg--tab--header">
    <ul class="wpstg-navigation-menu">
        <li class="wpstg-tab-navigation wpstg_admin">
            <?php require_once(WPSTG_VIEWS_DIR . 'navigation/mobile-template.php'); ?>
        </li>
        <?php foreach ($menu as $tabKey => $tab) :?>
            <li>
                <a href="<?php esc_attr_e($tab['targetUrl'], 'wp-staging') ?>" class="wpstg--tab--content <?php echo ($tab['isActive']) ? 'wpstg--tab--active' : '' ?> wpstg-button" data-target="<?php esc_attr_e($tab['targetId'], 'wp-staging') ?>" id="<?php esc_attr_e($tab['id'], 'wp-staging') ?>">
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
                <?php
                if ($tabKey === 'tab-backup') {
                    $backupNotice->maybeShowBackupNotice();
                }
                ?>
            </li>
        <?php endforeach; ?>
        <li class="wpstg-tab-item--vert-center wpstg-tab-header-loader">
            <span class="wpstg-loader"></span>
        </li>
        <li class="wpstg-contact-us-wrapper">
            <?php
            require_once(WPSTG_VIEWS_DIR . '_main/contact-us.php');
            ?>
        </li>
        <li class="wpstg-tab-item--vert-center">
            <?php require_once(WPSTG_VIEWS_DIR . '_main/darkmode-toggle-button.php'); ?>
        </li>
    </ul>
</div>
