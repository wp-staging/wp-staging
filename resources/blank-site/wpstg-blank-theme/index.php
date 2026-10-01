<?php

if (! defined('ABSPATH')) {
    exit;
}

$wpstg_name = get_bloginfo('name');
$wpstg_desc = get_bloginfo('description');
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('wpstg'); ?>>
<?php wp_body_open(); ?>

    <header class="wpstg-header">
        <a class="wpstg-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="wpstg-brand__mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1772.09 396.7" fill="var(--wpstg-navy)"><defs><linearGradient id="wpstgHeaderGradTop" x1="443.1" y1="120.85" x2="693.38" y2="120.85" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#36b6fc"/><stop offset="1" stop-color="#2eb67d"/></linearGradient><linearGradient id="wpstgHeaderGradBottom" x1="443.09" y1="275.85" x2="693.38" y2="275.85" gradientUnits="userSpaceOnUse"><stop offset="0.01" stop-color="#e01f5a"/><stop offset="1" stop-color="#ecb02e"/></linearGradient></defs><path d="M820.07,192.46h0c-6.85-3.11-19-6.61-37.07-10.71-21.34-4.82-28.93-8.67-31.55-11.06A15.75,15.75,0,0,1,746,158.3c0-5.92,2.59-10.87,7.93-15.15s14.53-6.59,27-6.59c11.9,0,20.93,2.46,26.83,7.3s9.3,12.06,10.33,21.62l.32,2.89,26.69-2-.11-2.89A53.19,53.19,0,0,0,836.39,136c-5.33-8.12-13-14.34-22.87-18.49-9.66-4.07-20.93-6.14-33.5-6.14a81.82,81.82,0,0,0-31.39,5.85c-9.6,4-17,9.89-22.06,17.61a45.08,45.08,0,0,0-7.57,25,40.72,40.72,0,0,0,6.26,22.12c4.12,6.54,10.43,12.06,18.72,16.4,6.36,3.38,17,6.83,32.53,10.56,15,3.59,24.7,6.25,29,7.92,6.35,2.42,10.9,5.37,13.54,8.77a18.33,18.33,0,0,1,3.87,11.71,20.47,20.47,0,0,1-4.1,12.35c-2.75,3.81-7.08,6.89-12.87,9.14a57.94,57.94,0,0,1-21,3.51,60.12,60.12,0,0,1-24.1-4.65c-6.93-3-12.08-7-15.3-11.74s-5.4-11.25-6.31-19l-.34-2.89-26.29,2.29.08,2.83a58.77,58.77,0,0,0,9.54,31.19,55.65,55.65,0,0,0,25,20.73c10.42,4.49,23.43,6.77,38.69,6.77a78.77,78.77,0,0,0,33-6.74c9.89-4.53,17.6-11,22.91-19.2a48,48,0,0,0,8-26.42,44.16,44.16,0,0,0-7.37-25.13C837.69,203.1,830.13,197.09,820.07,192.46Z"/><polygon points="998.02 114.14 861.36 114.14 861.36 139.6 915.67 139.6 915.67 284.99 943.48 284.99 943.48 139.6 998.02 139.6 998.02 114.14"/><path d="M1043.45,114.14,977.84,285h29.63l18.1-49.93h64.77L1109.57,285H1141L1071,114.14Zm37.86,97.16h-47.22l16.6-44.32c2.53-6.91,4.71-13.92,6.51-20.94,2.26,6.89,5.09,14.81,8.44,23.66Z"/><path d="M1228,220.33h48.47v26.32c-4.35,3.57-10.6,7-18.59,10.24a70.59,70.59,0,0,1-26.54,5.23,65.74,65.74,0,0,1-29.48-6.88,45.79,45.79,0,0,1-20.75-20.72c-4.82-9.42-7.26-21.37-7.26-35.52a83.69,83.69,0,0,1,6.06-32,54.36,54.36,0,0,1,9.86-15.45,45.43,45.43,0,0,1,16.53-11.22c6.87-2.87,15.18-4.32,24.7-4.32a55.14,55.14,0,0,1,21.55,4.09c6.3,2.67,11.19,6.23,14.53,10.59s6.37,10.81,8.72,18.81l.83,2.83,25.37-7-.74-2.86c-2.94-11.45-7.32-20.91-13-28.11s-13.82-13-23.89-17a89.55,89.55,0,0,0-33.45-6c-16.84,0-32,3.53-44.93,10.48s-23.28,17.89-30.24,32.32a105.38,105.38,0,0,0-10.36,46.22c0,16.6,3.51,31.86,10.45,45.34a72,72,0,0,0,31.24,31.37c13.6,7.13,28.92,10.74,45.53,10.74a101.78,101.78,0,0,0,36.14-6.66,126.68,126.68,0,0,0,34-19.74l1.13-.91V194.87L1228,195Z"/><rect x="1333.74" y="114.14" width="27.81" height="170.85"/><polygon points="1502.75 236.69 1420.77 114.14 1393.79 114.14 1393.79 284.99 1420.7 284.99 1420.7 162.34 1502.68 284.99 1529.66 284.99 1529.66 114.14 1502.75 114.14 1502.75 236.69"/><path d="M1637.46,195v25.33h48.46v26.32c-4.35,3.57-10.59,7-18.59,10.24a70.57,70.57,0,0,1-26.53,5.23,65.71,65.71,0,0,1-29.48-6.88,45.81,45.81,0,0,1-20.76-20.72c-4.82-9.42-7.26-21.37-7.26-35.52a83.51,83.51,0,0,1,6.07-32,54.36,54.36,0,0,1,9.86-15.45,45.47,45.47,0,0,1,16.52-11.22c6.87-2.87,15.18-4.32,24.71-4.32a55.13,55.13,0,0,1,21.54,4.09c6.31,2.67,11.2,6.23,14.53,10.59s6.37,10.81,8.73,18.81l.83,2.83,25.37-7-.74-2.86c-2.94-11.45-7.33-20.91-13-28.11s-13.81-13-23.89-17a89.53,89.53,0,0,0-33.44-6c-16.84,0-32,3.53-44.94,10.48s-23.28,17.89-30.24,32.32a105.52,105.52,0,0,0-10.36,46.22c0,16.6,3.52,31.86,10.45,45.34a72.08,72.08,0,0,0,31.25,31.37c13.6,7.13,28.91,10.74,45.52,10.74a101.88,101.88,0,0,0,36.15-6.66,127,127,0,0,0,34-19.74l1.13-.91V194.87Z"/><path d="M224,223.35c-1.82,7.27-3.46,14.15-4.92,20.57-2.58-13.76-6-28.21-10.31-43.16L184.5,115.14H153.65l-32,113.52c-.55,2-1.92,7.52-4.18,16.91-1.34-6.68-2.82-13.38-4.4-20L87.46,115.14H58.8L104.13,286H131L166.56,158.2q1-3.55,2.08-7.8c.62,2.28,1.34,4.87,2.15,7.79L206.15,286h25.6l46.84-170.85H250.38Z"/><path d="M404.33,125h0c-5.81-4-13.07-6.78-21.52-8.18-5.93-1.08-14.55-1.63-25.61-1.63H292V286h27.82V219h39.28c24.08,0,41.09-5.22,50.56-15.53s14.17-22.89,14.17-37.62A53.78,53.78,0,0,0,418.53,142,43.69,43.69,0,0,0,404.33,125Zm-44.88,68.57H319.83V140.6H359c9.58,0,16.17.45,19.55,1.35a21.37,21.37,0,0,1,12.13,8.57,27,27,0,0,1,4.67,16c0,8.7-2.57,15.21-7.87,19.9h0C382.15,191.12,372.72,193.52,359.45,193.52Z"/><path fill="url(#wpstgHeaderGradTop)" d="M657.18,106.31a128,128,0,0,0-214.08,65h34.64A94.43,94.43,0,0,1,633.35,130l-41.66,41.38H693.38v-101Z"/><path fill="url(#wpstgHeaderGradBottom)" d="M658.74,225.35a94.44,94.44,0,0,1-155.62,41.38l41.66-41.38H443.09v101l36.2-36a128,128,0,0,0,214.09-65Z"/></svg>
            </span>
            <span class="wpstg-visually-hidden">WP STAGING</span>
        </a>
        <span class="wpstg-tag">
            <span class="wpstg-tag__dot"></span>
            <?php esc_html_e('Staging', 'wp-staging'); ?>
        </span>
    </header>

    <main class="wpstg-main">

        <section class="wpstg-hero">
            <div>
                <span class="wpstg-eyebrow">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                    <?php esc_html_e('Environment ready', 'wp-staging'); ?>
                </span>
                <h1 class="wpstg-title">
                    <?php
                    printf(
                        /* translators: %1$s: highlighted brand name "WP STAGING" with logo icon. %2$s: highlighted word "ready". */
                        esc_html__('Your %1$s environment is %2$s', 'wp-staging'),
                        '<span class="wpstg-title__brand-group"><span class="wpstg-title__brand">WP STAGING</span><svg class="wpstg-title__icon" viewBox="443 69 251 259" width="30" height="30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><linearGradient id="wpstgHeroGradTop" x1="443.1" y1="120.85" x2="693.38" y2="120.85" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#36b6fc"/><stop offset="1" stop-color="#2eb67d"/></linearGradient><linearGradient id="wpstgHeroGradBottom" x1="443.09" y1="275.85" x2="693.38" y2="275.85" gradientUnits="userSpaceOnUse"><stop offset="0.01" stop-color="#e01f5a"/><stop offset="1" stop-color="#ecb02e"/></linearGradient></defs><path fill="url(#wpstgHeroGradTop)" d="M657.18,106.31a128,128,0,0,0-214.08,65h34.64A94.43,94.43,0,0,1,633.35,130l-41.66,41.38H693.38v-101Z"/><path fill="url(#wpstgHeroGradBottom)" d="M658.74,225.35a94.44,94.44,0,0,1-155.62,41.38l41.66-41.38H443.09v101l36.2-36a128,128,0,0,0,214.09-65Z"/></svg></span>',
                        '<em>' . esc_html__('ready', 'wp-staging') . '</em>'
                    );
                    ?>
                </h1>
                <p class="wpstg-lead">
                    <?php
                    echo $wpstg_desc
                        ? esc_html($wpstg_desc)
                        : esc_html__('A private, sandboxed, fresh and blank WordPress site — safe to test, break and rebuild. Nothing here touches your live site. Install a theme to replace this page with your own.', 'wp-staging');
                    ?>
                </p>
                <div class="wpstg-actions">
                    <a class="wpstg-btn wpstg-btn--primary" href="<?php echo esc_url(admin_url()); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>
                        <?php esc_html_e('Open Dashboard', 'wp-staging'); ?>
                    </a>
                    <a class="wpstg-btn wpstg-btn--ghost" href="<?php echo esc_url(home_url('/')); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
                        <?php esc_html_e('View Site', 'wp-staging'); ?>
                    </a>
                </div>
            </div>

            <div class="wpstg-art" aria-hidden="true">
                <svg class="wpstg-art__deco" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                    <path d="M40 250 Q 30 320 110 330" stroke-dasharray="5 7" opacity="0.6"></path>
                    <path d="M470 110 Q 540 120 520 190" stroke-dasharray="5 7" opacity="0.6"></path>
                    <path d="M60 130 Q 20 90 70 60" stroke-dasharray="5 7" opacity="0.45"></path>
                    <g stroke-width="2" opacity="0.55"><path d="M30 200v12M24 206h12"></path></g>
                    <g stroke-width="2" opacity="0.5"><path d="M500 60v10M495 65h10"></path></g>
                    <g stroke-width="2" opacity="0.5"><path d="M470 300v10M465 305h10"></path></g>
                </svg>

                <div class="wpstg-browser">
                    <div class="wpstg-browser__bar"><span></span><span></span><span></span></div>
                    <div class="wpstg-browser__body">
                        <div class="wpstg-browser__nav">
                            <span class="wpstg-wp">W</span>
                            <span class="wpstg-skel" style="width:100%"></span>
                            <span class="wpstg-skel" style="width:70%"></span>
                            <span class="wpstg-skel" style="width:85%"></span>
                            <span class="wpstg-browser__check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"></path></svg></span>
                        </div>
                        <div class="wpstg-browser__img">
                            <span class="wpstg-browser__sun"></span>
                            <svg class="wpstg-browser__hills" height="64" viewBox="0 0 200 64" preserveAspectRatio="none"><path d="M0 64 L55 26 L92 50 L135 18 L200 56 L200 64 Z" fill="#9CC2F7"></path><path d="M0 64 L40 44 L80 60 L120 38 L165 58 L200 44 L200 64 Z" fill="#4FB88C"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="wpstg-chip wpstg-chip--left">
                    <span class="wpstg-chip__ic wpstg-chip__ic--blue"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>
                    <span><span class="wpstg-chip__t"><?php esc_html_e('Isolated', 'wp-staging'); ?></span><span class="wpstg-chip__s"><?php esc_html_e('Safe & private', 'wp-staging'); ?></span></span>
                </div>

                <div class="wpstg-chip wpstg-chip--right">
                    <span class="wpstg-chip__ic wpstg-chip__ic--green"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"></path><path d="M6.453 15h11.094"></path><path d="M8.5 2h7"></path></svg></span>
                    <span><span class="wpstg-chip__t"><?php esc_html_e('Test freely', 'wp-staging'); ?></span><span class="wpstg-chip__s"><?php esc_html_e('Break things', 'wp-staging'); ?></span></span>
                </div>

                <div class="wpstg-shield">
                    <svg width="64" height="72" viewBox="0 0 64 72" fill="none"><path d="M32 2 60 12v24c0 18-14 28-28 34C18 64 4 54 4 36V12z" fill="#fff" stroke="var(--wpstg-blue)" stroke-width="2.5"></path><path d="M32 12 50 18v16c0 11-9 18-18 22C23 52 14 45 14 34V18z" fill="var(--leaf-green)"></path><path d="m25 33 5 5 9-10" stroke="#fff" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" fill="none"></path></svg>
                </div>
            </div>
        </section>

        <section class="wpstg-panel wpstg-panel--status">
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg></span>
                <div>
                    <div class="wpstg-cell__eyebrow"><?php esc_html_e('Status', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__value wpstg-cell__value--ok"><?php esc_html_e('Active', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e('Your staging site is up and running.', 'wp-staging'); ?></div>
                </div>
            </div>
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M3.3 7 12 12l8.7-5"></path><path d="M12 22V12"></path></svg></span>
                <div>
                    <div class="wpstg-cell__eyebrow"><?php esc_html_e('Type', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__value"><?php esc_html_e('Blank Install', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e('Fresh WordPress installation.', 'wp-staging'); ?></div>
                </div>
            </div>
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg></span>
                <div>
                    <div class="wpstg-cell__eyebrow"><?php esc_html_e('Built with', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__value"><?php esc_html_e('WP STAGING', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e('Powered by WP STAGING Pro.', 'wp-staging'); ?></div>
                </div>
            </div>
        </section>

        <section class="wpstg-panel wpstg-panel--features">
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--blue"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg></span>
                <div>
                    <div class="wpstg-cell__title"><?php esc_html_e('Safe Testing', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e('Experiment and make changes without risking your live site.', 'wp-staging'); ?></div>
                </div>
            </div>
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--green"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"></path><path d="M3.34 19a10 10 0 1 1 17.32 0"></path></svg></span>
                <div>
                    <div class="wpstg-cell__title"><?php esc_html_e('Fast Development', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e('Build, test and iterate quickly in your isolated environment.', 'wp-staging'); ?></div>
                </div>
            </div>
            <div class="wpstg-cell">
                <span class="wpstg-ic wpstg-ic--blue"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"></path><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path></svg></span>
                <div>
                    <div class="wpstg-cell__title"><?php esc_html_e('Ready to Deploy', 'wp-staging'); ?></div>
                    <div class="wpstg-cell__desc"><?php esc_html_e("Push your changes to production when you're good to go.", 'wp-staging'); ?></div>
                </div>
            </div>
        </section>

        <section class="wpstg-steps">
            <div class="wpstg-steps__intro">
                <span class="wpstg-steps__star"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path></svg></span>
                <div>
                    <div class="wpstg-steps__title"><?php esc_html_e('Getting Started', 'wp-staging'); ?></div>
                    <div class="wpstg-steps__sub"><?php esc_html_e('Follow these simple steps to get your site ready.', 'wp-staging'); ?></div>
                </div>
            </div>
            <div class="wpstg-step">
                <div class="wpstg-step__head"><span class="wpstg-step__num">1</span><span class="wpstg-step__name"><?php esc_html_e('Install a Theme', 'wp-staging'); ?></span></div>
                <div class="wpstg-step__desc"><?php esc_html_e('Choose and install your favorite theme.', 'wp-staging'); ?></div>
                <span class="wpstg-step__arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></span>
            </div>
            <div class="wpstg-step">
                <div class="wpstg-step__head"><span class="wpstg-step__num">2</span><span class="wpstg-step__name"><?php esc_html_e('Add Plugins', 'wp-staging'); ?></span></div>
                <div class="wpstg-step__desc"><?php esc_html_e('Install essential plugins to extend functionality.', 'wp-staging'); ?></div>
                <span class="wpstg-step__arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></span>
            </div>
            <div class="wpstg-step">
                <div class="wpstg-step__head"><span class="wpstg-step__num">3</span><span class="wpstg-step__name"><?php esc_html_e('Create Content', 'wp-staging'); ?></span></div>
                <div class="wpstg-step__desc"><?php esc_html_e('Add pages, posts and configure your site.', 'wp-staging'); ?></div>
                <span class="wpstg-step__arrow"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg></span>
            </div>
            <div class="wpstg-step">
                <div class="wpstg-step__head"><span class="wpstg-step__num">4</span><span class="wpstg-step__name"><?php esc_html_e('Test & Perfect', 'wp-staging'); ?></span></div>
                <div class="wpstg-step__desc"><?php esc_html_e('Test everything, then deploy with confidence.', 'wp-staging'); ?></div>
            </div>
        </section>

    </main>

    <footer class="wpstg-footer">
        <span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <?php
            printf(
                /* translators: %s: site name. */
                esc_html__('%s is a staging site — changes here do not affect your live website.', 'wp-staging'),
                esc_html($wpstg_name)
            );
            ?>
        </span>
    </footer>

<?php wp_footer(); ?>
</body>
</html>
