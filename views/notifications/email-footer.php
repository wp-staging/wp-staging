<?php
/**
 * Email footer template
 *
 * @var bool $isBasic Whether using basic version
 * @var string $year Current year
 * @var string $siteUrl Website URL
 * @var string $pluginName Plugin name
 * @var string $recipient Recipient email
 */
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
    <tr>
        <td bgcolor="#f4f4f4" style="padding: 20px 30px; text-align: center; font-family: Arial, sans-serif; font-size: 12px; color: #666;">
            <p style="margin: 0;">
                © <?php echo esc_html($year); ?>
                WP Staging
                <?php echo ($isBasic ? ' .' : ' Pro.'); ?>
                <?php echo esc_html__('All rights reserved.', 'wp-staging'); ?>
            </p>
            <p style="margin: 10px 0 0;">
                <?php echo sprintf(
                    esc_html__('This message was sent by the %s from the website %s', 'wp-staging'),
                    esc_html($pluginName),
                    '<a href="' . esc_url($siteUrl) . '">' . esc_html($siteUrl) . '</a>'
                ); ?>
            </p>
            <p style="margin: 10px 0 0;">
                <?php echo sprintf(
                    esc_html__('It was sent to the email address %s which can be set up on %s', 'wp-staging'),
                    esc_html($recipient),
                    '<a href="' . esc_url($siteUrl . '/wp-admin/admin.php?page=wpstg-settings') . '">' . esc_html__('Settings.', 'wp-staging') . '</a>'
                ); ?>
            </p>
            <?php if ($isBasic) : ?>
                <p style="margin: 10px 0 0;">
                    <a href="https://wp-staging.com/" style="color: #0073a8; text-decoration: none;">
                        <?php echo esc_html__('Get more control over your notifications by using WP Staging Pro.', 'wp-staging'); ?>
                    </a>
                </p>
            <?php endif; ?>
            <p style="margin: 10px 0 0;">
                <?php echo esc_html__('Please do not reply to this email.', 'wp-staging'); ?>
            </p>
        </td>
    </tr>
</table>
