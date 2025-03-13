<?php
/**
 * Email template file
 *
 * @var string $encodedSvg Base64 encoded SVG logo
 * @var string $htmlMessage Main message content
 * @var array $details List of details
 * @var string $footerMessage Footer content
 * @var bool $isBasic Whether using basic version
 * @var string $recipient Recipient email
 * @var string $year Current year
 * @var string $siteUrl Website URL
 * @var string $pluginName Plugin name string
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?php esc_html_e("WP Staging Notification", 'wp-staging'); ?> </title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <body style="margin: 0; padding: 0;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td bgcolor="#f4f4f4" align="center" style="padding: 15px;">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; background-color: #ffffff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr style="background: linear-gradient(129deg, #35b6f4 0%, #2eb67e 50%, #ea9f33 50%, #e12758 100%)">
                            <td style="padding: 15px 20px 10px 25px;">
                                <picture>
                                    <source srcset="<?php echo esc_html($encodedSvg); ?>" type="image/svg+xml">
                                    <img src="https://wp-staging.com/mail-template/wp-staging-logo.png" alt="WP STAGING Logo" width="210">
                                </picture>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style="padding: 40px 30px; font-family: Arial, sans-serif; line-height: 1.6;">
                                <p style="margin: 0 0 20px 0; color: #333;"><?php echo wp_kses_post($htmlMessage); ?></p>
                                <?php if (count($details) > 0) : ?>
                                    <?php require __DIR__ . '/email-details.php';?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td bgcolor="#f4f4f4" style="padding: 20px 30px; text-align: center; font-family: Arial, sans-serif; font-size: 12px; color: #666;">
                                <?php require __DIR__ . '/email-footer.php'; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
