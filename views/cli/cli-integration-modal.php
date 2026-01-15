<?php

/**
 * CLI Integration Modal - Modal content for CLI installation wizard
 *
 * This file contains the hidden modal content that is shown when the user
 * clicks "Create Local Site" in the CLI integration banner.
 *
 * IMPORTANT: The "Copy" button text is intentionally NOT translatable.
 * Translations could make the button too wide and overlap with terminal commands.
 * The corresponding "Copied!" text in cli-integration-modal.js is also hardcoded.
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Sanitize;

/**
 * @global string $table_prefix
 */
global $table_prefix;

/**
 * @var bool   $isDeveloperOrHigher Whether user has Developer plan or higher
 * @var array  $backups             Array of available backups
 * @var string $urlAssets           URL to assets directory
 */

// Get site configuration for modal
$sanitize     = WPStaging::make(Sanitize::class);
$phpVersion   = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$wpVersion    = get_bloginfo('version');
$prodUrl      = home_url();
$prodHost     = wp_parse_url($prodUrl, PHP_URL_HOST);
// Sanitize domain for safe use in CLI commands (only alphanumeric, dots, hyphens)
$localDomain  = $sanitize->sanitizeDomainForCli(preg_replace('/\.[a-zA-Z0-9]+$/', '', $prodHost) . '.local');

// Sanitize table prefix for safe use in CLI commands (only alphanumeric, underscores)
$tablePrefix  = $sanitize->sanitizeTablePrefixForCli($table_prefix);
$licenseKey       = trim(get_option('wpstg_license_key', ''));
// Sanitize license key for safe use in CLI commands (only alphanumeric, hyphens)
$licenseKeySanitized = $sanitize->sanitizeLicenseKeyForCli($licenseKey);
$licenseFlag      = !empty($licenseKeySanitized) ? ' -l ' . $licenseKeySanitized : '';
$maskedLicenseKey = !empty($licenseKeySanitized) ? substr($licenseKeySanitized, 0, 4) . '...' . substr($licenseKeySanitized, -4) : '';
$licenseFlagMasked = !empty($licenseKeySanitized) ? ' -l ' . $maskedLicenseKey : '';
$hasLicense       = !empty($licenseKeySanitized);
?>
<div id="wpstg-cli-install-modal-content" style="display: none;">
    <div class="wpstg-cli-modal-layout">
        <!-- Main Content Area -->
        <div class="wpstg-cli-modal-main">
            <div class="wpstg-cli-modal-header">
                <div class="wpstg-cli-modal-header-top">
                    <h2 class="wpstg-cli-modal-title"><?php echo esc_html__('Create a local copy of this site', 'wp-staging'); ?></h2>
                    <button type="button" class="wpstg-cli-modal-close-btn" title="<?php echo esc_attr__('Close', 'wp-staging'); ?>" aria-label="<?php echo esc_attr__('Close', 'wp-staging'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <p class="wpstg-cli-modal-subtitle">
                    <?php echo esc_html__('Use WP Staging CLI to create an isolated Docker container and clone this site', 'wp-staging'); ?>
                </p>
            </div>

            <!-- Step Navigation -->
            <div class="wpstg-cli-step-nav">
                <button class="wpstg-cli-step-nav-item wpstg-cli-step-nav-active" data-nav-step="1" aria-label="<?php echo esc_attr__('Go to step 1: Install CLI', 'wp-staging'); ?>">
                    <span class="wpstg-cli-step-nav-number">1</span>
                    <span class="wpstg-cli-step-nav-label"><?php echo esc_html__('Install CLI', 'wp-staging'); ?></span>
                </button>
                <div class="wpstg-cli-step-nav-line"></div>
                <button class="wpstg-cli-step-nav-item" data-nav-step="2" aria-label="<?php echo esc_attr__('Go to step 2: Create Site', 'wp-staging'); ?>">
                    <span class="wpstg-cli-step-nav-number">2</span>
                    <span class="wpstg-cli-step-nav-label"><?php echo esc_html__('Create Site', 'wp-staging'); ?></span>
                </button>
                <div class="wpstg-cli-step-nav-line"></div>
                <button class="wpstg-cli-step-nav-item" data-nav-step="3" aria-label="<?php echo esc_attr__('Go to step 3: Restore Backup', 'wp-staging'); ?>">
                    <span class="wpstg-cli-step-nav-number">3</span>
                    <span class="wpstg-cli-step-nav-label"><?php echo esc_html__('Restore', 'wp-staging'); ?></span>
                </button>
            </div>

            <div class="wpstg-cli-install-steps wpstg--steps wpstg--steps--slider" data-wpstg-current-step="1">
                <!-- Select your operating system -->
                <div class="wpstg-cli-step wpstg--step is-active" data-wpstg-step="1">
                    <h4 class="wpstg-cli-step-title">
                        <?php echo esc_html__('Select your operating system:', 'wp-staging'); ?>
                    </h4>

                    <!-- OS Selection Tabs -->
                    <div class="wpstg-cli-tabs" role="tablist">
                        <button class="wpstg-cli-tab" data-tab="mac" role="tab" aria-selected="false">
                            <svg class="wpstg-cli-tab-icon wpstg-cli-tab-icon-mac" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <!-- Apple Logo -->
                                <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                            </svg>
                            <?php echo esc_html__('macOS', 'wp-staging'); ?>
                        </button>
                        <button class="wpstg-cli-tab" data-tab="linux" role="tab" aria-selected="false">
                            <svg class="wpstg-cli-tab-icon wpstg-cli-tab-icon-linux" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 709 858" fill="currentColor">
                                <!-- Tux penguin -->
                                <path d="M372.35 0.00031522C366.841 -0.00975478 361.095 0.22141 355.103 0.699145C203.663 12.8955 243.826 172.884 241.578 226.46C238.812 265.639 230.869 296.515 203.923 334.817C172.265 372.463 127.695 433.394 106.582 496.821C96.6167 526.756 91.8748 557.267 96.2541 586.144C94.8823 587.373 93.5763 588.66 92.3345 589.986C83.0399 599.908 76.1723 611.931 68.52 620.026C61.3707 627.158 51.1848 629.87 39.9913 633.886C28.7935 637.904 16.4992 643.827 9.03654 658.148C9.03654 658.148 9.03654 658.153 9.03154 658.155C9.01936 658.178 9.00607 658.201 8.99338 658.225C2.23776 670.843 4.2461 685.374 6.30931 698.836C8.37258 712.298 10.4613 725.019 7.68879 733.65C-1.17347 757.873 -2.30493 774.629 3.93382 786.782C10.1859 798.961 23.0406 804.337 37.5692 807.372C66.6266 813.441 105.978 811.932 138.099 828.838C170.22 845.743 203.88 851.909 230.734 845.775C250.204 841.328 266.009 829.735 274.126 811.896C295.13 811.792 318.179 802.896 355.1 800.863C380.147 798.846 411.436 809.758 447.426 807.76C448.366 811.664 449.728 815.42 451.592 818.989C451.611 819.025 451.629 819.062 451.648 819.098C465.599 846.996 491.522 859.757 519.148 857.577C546.81 855.394 576.221 839.083 599.454 811.178C622.688 783.274 660.3 771.881 685.24 756.839C697.709 749.317 707.828 739.9 708.61 726.221C709.391 712.549 701.364 697.228 682.908 676.74L682.903 676.735C682.896 676.726 682.888 676.718 682.881 676.71C676.817 669.857 673.924 657.155 670.815 643.63C667.708 630.112 664.24 615.53 653.142 606.08C653.12 606.061 653.098 606.043 653.076 606.024C648.659 602.175 644.069 599.563 639.447 597.808C654.876 552.048 648.822 506.483 633.247 465.307C614.132 414.773 580.76 370.741 555.274 340.619C526.746 304.635 498.842 270.471 499.392 220.012C500.242 143.001 507.863 0.185839 372.346 0.000887425L372.35 0.00031522ZM390.696 118.644C398.386 118.644 404.953 120.898 411.7 125.794C418.553 130.769 423.491 136.996 427.47 145.691C431.376 154.166 433.255 162.457 433.439 172.295C433.439 172.553 433.439 172.774 433.513 173.032C433.587 183.128 431.855 191.713 428.023 200.483C425.836 205.487 423.325 209.687 420.319 213.323C419.299 212.833 418.239 212.362 417.14 211.91C409.522 208.647 403.678 206.57 398.807 204.88C400.572 202.758 402.043 200.241 403.335 197.094C405.288 192.341 406.246 187.698 406.431 182.171C406.431 181.95 406.504 181.766 406.504 181.508C406.615 176.202 405.915 171.67 404.367 167.027C402.746 162.163 400.682 158.663 397.698 155.752C394.713 152.841 391.729 151.514 388.155 151.404C387.987 151.394 387.82 151.394 387.655 151.394C384.293 151.404 381.374 152.557 378.353 155.085C375.184 157.738 372.826 161.128 370.873 165.845C368.92 170.561 367.962 175.24 367.778 180.804C367.741 181.025 367.741 181.21 367.741 181.431C367.678 184.489 367.872 187.291 368.343 190.011C361.464 186.584 354.982 184.247 348.898 182.799C348.551 180.168 348.35 177.453 348.286 174.614V173.84C348.176 163.781 349.834 155.159 353.703 146.389C357.572 137.62 362.362 131.319 369.105 126.197C375.848 121.076 382.48 118.717 390.328 118.644L390.696 118.644ZM285.445 126.977C290.546 126.989 295.111 128.693 299.833 132.464C304.954 136.553 308.823 141.786 312.066 149.155C315.308 156.524 317.04 163.894 317.519 172.59V172.663C317.751 176.315 317.723 179.753 317.435 183.082C316.426 183.369 315.437 183.679 314.469 184.013C308.968 185.906 304.121 188.486 299.896 191.276C300.309 188.358 300.371 185.397 300.054 182.096C300.017 181.911 300.017 181.764 300.017 181.58C299.574 177.195 298.653 173.51 297.106 169.789C295.448 165.92 293.605 163.193 291.173 161.093C288.97 159.19 286.887 158.316 284.596 158.333C284.359 158.333 284.12 158.346 283.878 158.366C281.298 158.587 279.161 159.84 277.135 162.309C275.108 164.777 273.782 167.836 272.824 171.889C271.866 175.942 271.608 179.921 272.013 184.49C272.013 184.674 272.05 184.822 272.05 185.006C272.492 189.428 273.377 193.112 274.961 196.834C276.582 200.666 278.461 203.393 280.893 205.493C281.301 205.845 281.704 206.161 282.105 206.443C279.575 208.39 278.367 209.287 276.294 210.81C274.965 211.786 273.382 212.947 271.541 214.304C267.528 210.544 264.397 205.819 261.659 199.597C258.417 192.228 256.685 184.859 256.169 176.163V176.09C255.69 167.394 256.538 159.914 258.933 152.176C261.328 144.438 264.533 138.838 269.176 134.232C273.819 129.626 278.498 127.305 284.136 127.01C284.576 126.986 285.013 126.975 285.445 126.977ZM333.313 187.305C345.169 187.259 359.437 191.147 376.71 202.252C387.32 209.152 395.576 209.729 414.586 217.871L414.609 217.881L414.632 217.89C423.779 221.643 429.148 226.535 431.771 231.686C434.394 236.837 434.458 242.423 432.262 248.299C427.872 260.052 413.857 272.42 394.189 278.562L394.171 278.57L394.153 278.578C384.564 281.693 376.202 288.574 366.343 294.211C356.485 299.848 345.33 304.395 330.162 303.517C317.249 302.765 309.53 298.381 302.551 292.772C295.571 287.162 289.489 280.116 280.583 274.886L280.561 274.872L280.537 274.859C266.192 266.75 258.36 257.372 255.902 249.242C253.444 241.112 255.75 234.172 262.868 228.838C270.87 222.841 276.429 218.762 280.132 216.044C283.81 213.345 285.341 212.333 286.512 211.208C286.518 211.199 286.524 211.195 286.531 211.19L286.536 211.181C292.548 205.489 302.133 195.12 316.581 190.147C321.553 188.435 327.102 187.331 333.313 187.306L333.313 187.305ZM433.511 259.615C446.428 310.536 476.473 384.086 495.784 419.979C506.048 439.022 526.469 479.483 535.292 528.23C549.889 528.336 564.136 533.168 577.252 539.363C614.28 557.394 627.975 573.073 621.39 594.472C619.222 594.396 617.088 594.404 615.008 594.454C614.814 594.459 614.621 594.464 614.427 594.469C619.789 577.507 607.911 564.996 576.258 550.674C543.427 536.23 517.266 537.667 512.844 566.961C512.561 568.495 512.334 570.059 512.16 571.645C509.707 572.497 507.241 573.584 504.769 574.937C489.353 583.372 480.939 598.678 476.259 617.45C471.583 636.207 470.242 658.883 468.952 684.374V684.387C468.163 697.205 462.893 714.541 457.549 732.903C403.767 771.266 329.124 787.888 265.748 744.637C261.453 737.842 256.527 731.111 251.455 724.471C248.217 720.231 244.89 716.017 241.583 711.862C248.088 711.867 253.618 710.802 258.088 708.776C263.645 706.255 267.551 702.207 269.482 697.011C273.346 686.618 269.466 671.956 257.096 655.214C244.728 638.473 229.78 613.578 198.998 594.7C198.998 594.7 198.993 594.7 198.993 594.695C190.788 589.55 184.498 578.943 183.948 559.484C184.923 524.841 187.197 495.51 201.085 462.462C221.315 416.606 263.629 337.085 266.989 273.705C268.722 274.963 274.676 278.981 277.329 280.49C277.334 280.498 277.341 280.498 277.346 280.499C285.109 285.07 290.939 291.752 298.49 297.82C306.055 303.901 315.507 309.152 329.786 309.984C346.453 310.949 359.156 305.786 369.564 299.835C379.955 293.893 388.257 287.317 396.122 284.75C396.134 284.742 396.146 284.742 396.158 284.739C412.777 279.541 425.991 270.343 433.511 259.614L433.511 259.615ZM539.82 548.638C556.051 548.106 575.706 557.601 584.632 561.594C608.656 572.685 610.673 584.191 604.114 598.156C598.587 608.695 566.932 631.96 546.219 627.288C518.925 618.764 518.105 583.674 520.795 565.656C522.236 553.421 529.656 548.685 539.82 548.638ZM511.888 584.064C513.246 606.097 524.148 628.569 543.427 633.433C564.525 638.991 594.943 620.89 607.786 606.125C610.409 602.522 610.725 599.647 613.157 599.588C624.422 599.316 639.121 600.959 647.692 608.352C656.262 615.745 661.914 629.873 664.972 643.18C668.031 656.487 668.409 670.709 681.825 685.67C695.241 700.632 702.477 717.754 701.995 726.196C701.512 734.638 694.009 745.054 682.722 751.861C660.157 765.471 621.982 776.425 596.445 807.358C574.271 833.746 542.067 849.346 518.255 851.226C494.442 853.105 469.147 838.472 457.035 814.161L457.007 814.106L456.978 814.052C449.464 799.758 453.844 778.2 460.174 754.405C466.504 730.611 475.099 702.929 476.316 683.072V683.04V683.01C477.604 657.567 481.028 629.348 485.302 612.204C489.576 595.06 499.806 585.448 511.73 578.924C512.286 578.62 511.337 584.333 511.885 584.063L511.888 584.064ZM131.333 582.686C144.83 584.73 159.101 599.418 170.441 614.799C181.782 630.181 192.834 650.928 202.629 671.699C212.424 692.469 232.068 710.724 247.656 731.132C263.244 751.539 273.057 772.774 271.25 794.052C269.442 815.33 252.358 834.387 229.202 839.676C206.053 844.963 172.921 840.193 139.895 822.811C106.869 805.429 69.1147 808.004 42.651 802.477C29.4155 799.712 14.5294 792.556 10.5656 784.834C6.6016 777.113 5.76313 762.391 12.0929 744.379C18.4226 726.368 15.8398 712.058 13.8173 698.862C11.7948 685.666 9.30575 673.412 14.4641 663.63C19.6225 653.847 31.085 644.122 41.5878 640.354C52.0905 636.586 63.0446 635.626 72.5585 625.85C82.0725 616.074 88.6517 603.802 96.6444 595.269C107.362 585.314 117.638 580.655 131.333 582.686Z"/>
                                <!-- Mouth -->
                                <path d="M382.654 259.653C367.013 267.806 348.744 277.693 329.304 277.693C309.874 277.693 294.524 268.713 283.484 259.963C277.964 255.593 273.484 251.243 270.104 248.083C264.24 243.454 264.942 236.962 267.352 237.153C271.39 237.658 272.001 242.975 274.544 245.353C277.984 248.573 282.294 252.743 287.514 256.883C297.954 265.153 311.874 273.203 329.304 273.203C346.704 273.203 367.016 262.988 379.414 256.033C386.438 252.093 395.376 245.03 402.671 239.676C408.251 235.58 408.048 230.647 412.656 231.184C417.264 231.721 413.855 236.644 407.402 242.275C400.949 247.907 390.854 255.379 382.654 259.653Z"/>
                                <!-- Eyes -->
                                <path d="M343.851 202.833C344.498 204.909 347.844 204.565 349.777 205.561C351.473 206.435 352.838 208.35 354.745 208.405C356.566 208.458 359.399 207.775 359.636 205.969C359.949 203.583 356.465 202.067 354.223 201.193C351.338 200.069 347.643 199.498 344.937 201.003C344.316 201.347 343.64 202.156 343.851 202.833Z"/>
                                <path d="M324.086 202.833C323.44 204.909 320.093 204.565 318.16 205.561C316.464 206.435 315.099 208.35 313.192 208.405C311.371 208.458 308.538 207.775 308.301 205.969C307.989 203.583 311.472 202.067 313.714 201.193C316.599 200.069 320.294 199.498 323.001 201.003C323.621 201.347 324.297 202.156 324.086 202.833Z"/>
                            </svg>
                            <?php echo esc_html__('Linux', 'wp-staging'); ?>
                        </button>
                        <button class="wpstg-cli-tab" data-tab="windows" role="tab" aria-selected="false">
                            <svg class="wpstg-cli-tab-icon wpstg-cli-tab-icon-windows" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 88 88" fill="currentColor">
                                <!-- Windows Logo - Classic 4-pane design -->
                                <path d="M0 12.402L35.687 7.586v34.423H0zm40.375-5.278L88 0v41.964H40.375zM0 45.898h35.687v34.423L0 75.307zm40.375 0H88v41.964l-47.625-6.936z"/>
                            </svg>
                            <?php echo esc_html__('Windows', 'wp-staging'); ?>
                        </button>
                    </div>
                    <h4 class="wpstg-cli-step-title">
                        <?php echo esc_html__('Step 1: Install WP Staging CLI', 'wp-staging'); ?>
                    </h4>
                    <p class="wpstg-cli-step-note">
                        <?php echo esc_html__('One time install that works with Docker Desktop and Docker Engine.', 'wp-staging'); ?>
                    </p>

                    <!-- Mac Tab Content -->
                    <div class="wpstg-cli-tab-content" data-tab-content="mac" role="tabpanel" style="display: none;">
                        <div class="wpstg-cli-command-box">
                            <?php
                            $cmdMacFull = 'curl -fsSL https://wp-staging.com/install.sh | bash' . ($hasLicense ? ' -s --' . $licenseFlag : '');
                            $cmdMacMasked = 'curl -fsSL https://wp-staging.com/install.sh | bash' . ($hasLicense ? ' -s --' . $licenseFlagMasked : '');
                            ?>
                            <input id="wpstg-cli-cmd-mac-full" type="hidden" value="<?php echo esc_attr($cmdMacFull); ?>" />
                            <input id="wpstg-cli-cmd-mac-masked" type="hidden" value="<?php echo esc_attr($cmdMacMasked); ?>" />
                            <span class="wpstg-cli-command-prefix">$</span>
                            <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-mac-text" <?php echo $hasLicense ? 'data-masked="true"' : ''; ?>><?php echo esc_html($hasLicense ? $cmdMacMasked : $cmdMacFull); ?></span>
                            <div class="wpstg-cli-terminal-actions wpstg-cli-step1-actions">
                                <?php if ($hasLicense) : ?>
                                <button type="button" class="wpstg-cli-license-toggle" data-os="mac" title="<?php echo esc_attr__('Show/hide license key', 'wp-staging'); ?>">
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                                <button class="wpstg-cli-copy-button" data-wpstg-source="#wpstg-cli-cmd-mac-full">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Linux Tab Content -->
                    <div class="wpstg-cli-tab-content" data-tab-content="linux" role="tabpanel" style="display: none;">
                        <div class="wpstg-cli-command-box">
                            <?php
                            $cmdLinuxFull = 'curl -fsSL https://wp-staging.com/install.sh | bash' . ($hasLicense ? ' -s --' . $licenseFlag : '');
                            $cmdLinuxMasked = 'curl -fsSL https://wp-staging.com/install.sh | bash' . ($hasLicense ? ' -s --' . $licenseFlagMasked : '');
                            ?>
                            <input id="wpstg-cli-cmd-linux-full" type="hidden" value="<?php echo esc_attr($cmdLinuxFull); ?>" />
                            <input id="wpstg-cli-cmd-linux-masked" type="hidden" value="<?php echo esc_attr($cmdLinuxMasked); ?>" />
                            <span class="wpstg-cli-command-prefix">$</span>
                            <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-linux-text" <?php echo $hasLicense ? 'data-masked="true"' : ''; ?>><?php echo esc_html($hasLicense ? $cmdLinuxMasked : $cmdLinuxFull); ?></span>
                            <div class="wpstg-cli-terminal-actions wpstg-cli-step1-actions">
                                <?php if ($hasLicense) : ?>
                                <button type="button" class="wpstg-cli-license-toggle" data-os="linux" title="<?php echo esc_attr__('Show/hide license key', 'wp-staging'); ?>">
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                                <button class="wpstg-cli-copy-button" data-wpstg-source="#wpstg-cli-cmd-linux-full">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <p class="wpstg-cli-wsl-note"><?php echo esc_html__('Also works in WSL on Windows.', 'wp-staging'); ?></p>
                    </div>

                    <!-- Windows Tab Content -->
                    <div class="wpstg-cli-tab-content" data-tab-content="windows" role="tabpanel" style="display: none;">
                        <p class="wpstg-cli-windows-label"><?php echo esc_html__('Windows PowerShell:', 'wp-staging'); ?></p>
                        <div class="wpstg-cli-command-box">
                            <?php
                            $licenseFlagPs = !empty($licenseKey) ? ' -l ' . $licenseKey : '';
                            $licenseFlagPsMasked = !empty($licenseKey) ? ' -l ' . $maskedLicenseKey : '';
                            $cmdPsFull = '& ([scriptblock]::Create((irm https://wp-staging.com/install.ps1)))' . $licenseFlagPs;
                            $cmdPsMasked = '& ([scriptblock]::Create((irm https://wp-staging.com/install.ps1)))' . $licenseFlagPsMasked;
                            ?>
                            <input id="wpstg-cli-cmd-ps-full" type="hidden" value="<?php echo esc_attr($cmdPsFull); ?>" />
                            <input id="wpstg-cli-cmd-ps-masked" type="hidden" value="<?php echo esc_attr($cmdPsMasked); ?>" />
                            <span class="wpstg-cli-command-prefix">$</span>
                            <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-ps-text" <?php echo $hasLicense ? 'data-masked="true"' : ''; ?>><?php echo esc_html($hasLicense ? $cmdPsMasked : $cmdPsFull); ?></span>
                            <div class="wpstg-cli-terminal-actions wpstg-cli-step1-actions">
                                <?php if ($hasLicense) : ?>
                                <button type="button" class="wpstg-cli-license-toggle" data-os="ps" title="<?php echo esc_attr__('Show/hide license key', 'wp-staging'); ?>">
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                                <button class="wpstg-cli-copy-button" data-wpstg-source="#wpstg-cli-cmd-ps-full">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <p class="wpstg-cli-windows-label"><?php echo esc_html__('Windows CMD:', 'wp-staging'); ?></p>
                        <div class="wpstg-cli-command-box">
                            <?php
                            $licenseFlagCmd = !empty($licenseKey) ? ' -l ' . $licenseKey : '';
                            $licenseFlagCmdMasked = !empty($licenseKey) ? ' -l ' . $maskedLicenseKey : '';
                            $cmdCmdFull = 'curl -fsSL https://wp-staging.com/install.cmd -o install.cmd && install.cmd' . $licenseFlagCmd . ' && del install.cmd';
                            $cmdCmdMasked = 'curl -fsSL https://wp-staging.com/install.cmd -o install.cmd && install.cmd' . $licenseFlagCmdMasked . ' && del install.cmd';
                            ?>
                            <input id="wpstg-cli-cmd-cmd-full" type="hidden" value="<?php echo esc_attr($cmdCmdFull); ?>" />
                            <input id="wpstg-cli-cmd-cmd-masked" type="hidden" value="<?php echo esc_attr($cmdCmdMasked); ?>" />
                            <span class="wpstg-cli-command-prefix">$</span>
                            <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-cmd-text" <?php echo $hasLicense ? 'data-masked="true"' : ''; ?>><?php echo esc_html($hasLicense ? $cmdCmdMasked : $cmdCmdFull); ?></span>
                            <div class="wpstg-cli-terminal-actions wpstg-cli-step1-actions">
                                <?php if ($hasLicense) : ?>
                                <button type="button" class="wpstg-cli-license-toggle" data-os="cmd" title="<?php echo esc_attr__('Show/hide license key', 'wp-staging'); ?>">
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                                <button class="wpstg-cli-copy-button" data-wpstg-source="#wpstg-cli-cmd-cmd-full">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Create Local WordPress Site -->
                <div class="wpstg-cli-step wpstg--step" data-wpstg-step="2" style="display: none;">
                    <h4 class="wpstg-cli-step-title">
                        <?php echo esc_html__('Step 2: Create Local WordPress Site', 'wp-staging'); ?>
                    </h4>
                    <p class="wpstg-cli-step-note">
                        <?php echo esc_html__('Launch a fresh WordPress instance with the same PHP and WordPress versions as this site uses.', 'wp-staging'); ?>
                    </p>
                    <div class="wpstg-cli-command-box wpstg-cli-command-with-success">
                        <?php $cmdAdd = sprintf('wpstaging add %s --wp=%s --php=%s --db-prefix=%s', $localDomain, $wpVersion, $phpVersion, $tablePrefix);?>
                        <input id="wpstg-cli-cmd-add" type="hidden" value="<?php echo esc_attr($cmdAdd); ?>" />
                        <span class="wpstg-cli-command-prefix">$</span>
                        <span class="wpstg-cli-command-text"><?php echo esc_html($cmdAdd); ?></span>
                        <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                        <button class="wpstg-cli-copy-button" data-wpstg-source="#wpstg-cli-cmd-add">
                            Copy
                        </button>
                        <div class="wpstg-cli-success-indicator">
                            <svg class="wpstg-cli-success-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span class="wpstg-cli-success-text"><?php echo esc_html__('WordPress instance ready in 30 seconds', 'wp-staging'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Import & Restore Backup -->
                <div class="wpstg-cli-step wpstg--step" data-wpstg-step="3" style="display: none;">
                    <h4 class="wpstg-cli-step-title">
                        <?php echo esc_html__('Step 3: Import & Restore Backup', 'wp-staging'); ?>
                    </h4>
                    <p class="wpstg-cli-step-note">
                        <?php echo esc_html__('Select a backup to restore on your local Docker site. Then run the commands below in your terminal.', 'wp-staging'); ?>
                    </p>

                    <!-- Backup Selection List -->
                    <div id="wpstg-cli-backup-list-container">
                        <?php include __DIR__ . '/cli-backup-list.php'; ?>
                    </div>

                    <?php
                    // Get first backup for default command
                    $firstBackup = null;
                    foreach ($backups as $backup) {
                        if (!$backup->isCorrupt && !$backup->isLegacy) {
                            $firstBackup = $backup;
                            break;
                        }
                    }

                    // Use first backup URL or placeholder
                    $defaultBackupUrl = $firstBackup ? $firstBackup->downloadUrl : 'https://example.com/backup.wpstg';

                    // Create masked URL for display (mask the unique identifier part of filename)
                    $maskedBackupUrl = preg_replace('/(_[0-9]{8}-[0-9]{6}_[a-f0-9]+)(\.wpstg)$/i', '_*****$2', $defaultBackupUrl);

                    $cmdRestore1Full      = sprintf("curl -fSL \\\n  %s \\\n  -o backup.wpstg", $defaultBackupUrl);
                    $cmdRestore1Masked    = sprintf("curl -fSL \\\n  %s \\\n  -o backup.wpstg", $maskedBackupUrl);
                    $cmdRestore2          = sprintf(
                        "SITE=%s && \\\n" .
                        "source <(grep -E \"^(DB_|CONTAINER_IP)\" \$HOME/wpstaging/sites/\$SITE/.env) && \\\n" .
                        "wpstaging restore \\\n" .
                        "--path=\$HOME/wpstaging/sites/\$SITE/www \\\n" .
                        "--db-host=\$CONTAINER_IP:\$DB_PORT \\\n" .
                        "--db-name=\$DB_NAME \\\n" .
                        "--db-user=\$DB_USER \\\n" .
                        "--db-password=\$DB_PASSWORD \\\n" .
                        "--db-prefix=%s \\\n" .
                        "backup.wpstg",
                        $localDomain,
                        $tablePrefix
                    );
                    $cmdRestoreFull       = $cmdRestore1Full . "\n\n" . $cmdRestore2;
                    $cmdRestoreMasked     = $cmdRestore1Masked . "\n\n" . $cmdRestore2;
                    ?>
                    <div id="wpstg-cli-restore-commands-container"<?php echo $firstBackup ? '' : ' style="display: none;"'; ?>>
                    <div class="wpstg-cli-command-box wpstg-cli-command-multiline">
                        <input id="wpstg-cli-cmd-restore" type="hidden" value="<?php echo esc_attr($cmdRestoreFull); ?>" />
                        <input id="wpstg-cli-cmd-restore-1-full" type="hidden" value="<?php echo esc_attr($cmdRestore1Full); ?>" />
                        <input id="wpstg-cli-cmd-restore-1-masked" type="hidden" value="<?php echo esc_attr($cmdRestore1Masked); ?>" />
                        <div class="wpstg-cli-command-lines-scroll">
                            <div class="wpstg-cli-command-line">
                                <span class="wpstg-cli-command-prefix">$</span>
                                <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-restore-1" data-masked="true"><?php echo esc_html($cmdRestore1Masked); ?></span>
                            </div>
                            <div class="wpstg-cli-command-line">
                                <span class="wpstg-cli-command-prefix">$</span>
                                <span class="wpstg-cli-command-text" id="wpstg-cli-cmd-restore-2"><?php echo esc_html($cmdRestore2); ?></span>
                            </div>
                        </div>
                        <div class="wpstg-cli-terminal-footer">
                            <div class="wpstg-cli-success-indicator">
                                <svg class="wpstg-cli-success-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span class="wpstg-cli-success-text"><?php echo esc_html__('Backup successfully restored', 'wp-staging'); ?></span>
                            </div>
                            <div class="wpstg-cli-terminal-actions">
                                <button type="button" class="wpstg-cli-url-toggle" id="wpstg-cli-url-toggle" title="<?php echo esc_attr__('Show/hide full URL', 'wp-staging'); ?>">
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-closed" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <svg class="wpstg-cli-eye-icon wpstg-cli-eye-open" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <!-- "Copy" is NOT translatable - long translations would overlap terminal commands -->
                                <button class="wpstg-cli-copy-button wpstg-cli-footer-copy" data-wpstg-source="#wpstg-cli-cmd-restore">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>
                    <input type="hidden" id="wpstg-cli-local-domain" value="<?php echo esc_attr($localDomain); ?>" />
                    <input type="hidden" id="wpstg-cli-table-prefix" value="<?php echo esc_attr($tablePrefix); ?>" />
                </div>
            </div>

            <!-- Step Hint -->
            <div class="wpstg-cli-step-hint-container">
                <span class="wpstg-cli-step-hint"><?php echo esc_html__('After installation, click "Next step" to create your instance.', 'wp-staging'); ?></span>
            </div>

            <!-- Modal Footer -->
            <div class="wpstg-cli-modal-footer">
                <a href="https://wp-staging.com/docs/set-up-wp-staging-cli/" target="_blank" rel="noreferrer noopener" class="wpstg-cli-docs-link">
                    <?php echo esc_html__('Documentation', 'wp-staging'); ?>
                    <svg class="wpstg-cli-external-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
                <div class="wpstg-cli-step-buttons-right">
                    <span class="wpstg-cli-step-counter"><?php echo esc_html__('Step 1 of 3', 'wp-staging'); ?></span>
                    <button type="button" class="wpstg-cli-step-btn-back" style="display: none;">
                        <?php echo esc_html__('Back', 'wp-staging'); ?>
                    </button>
                    <button type="button" class="wpstg-cli-step-btn-next">
                        <?php echo esc_html__('Next step', 'wp-staging'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="wpstg-cli-sidebar">
            <div class="wpstg-cli-sidebar-section wpstg-cli-sidebar-requirements">
                <h3 class="wpstg-cli-sidebar-title"><?php echo esc_html__('Prerequisites', 'wp-staging'); ?></h3>
                <div class="wpstg-cli-sidebar-card">
                    <p class="wpstg-cli-sidebar-card-title"><?php echo esc_html__('Requires Docker', 'wp-staging'); ?></p>
                    <p class="wpstg-cli-sidebar-card-text"><?php echo esc_html__('WP Staging CLI uses Docker to run an isolated local copy of this site. Install and start Docker Desktop (or Docker Engine) before continuing.', 'wp-staging'); ?></p>
                    <a href="https://wp-staging.com/launch-docker" target="_blank" rel="noreferrer noopener" class="wpstg-cli-sidebar-link">
                        <?php echo esc_html__('Docker setup guide', 'wp-staging'); ?>
                        <svg class="wpstg-cli-external-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>
            </div>

            <div class="wpstg-cli-sidebar-section wpstg-cli-sidebar-summary">
                <h3 class="wpstg-cli-sidebar-title"><?php echo esc_html__('What you\'ll get', 'wp-staging'); ?></h3>
                <ul class="wpstg-cli-sidebar-list">
                    <li class="wpstg-cli-sidebar-list-item">
                        <div class="wpstg-cli-sidebar-check-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="wpstg-cli-sidebar-list-text"><strong><?php echo esc_html__('Matches production versions:', 'wp-staging'); ?></strong> <?php echo esc_html__('Uses the same WordPress and PHP versions.', 'wp-staging'); ?></p>
                    </li>
                    <li class="wpstg-cli-sidebar-list-item">
                        <div class="wpstg-cli-sidebar-check-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="wpstg-cli-sidebar-list-text"><strong><?php echo esc_html__('Database and files cloned:', 'wp-staging'); ?></strong> <?php echo esc_html__('Creates a local copy of your site content.', 'wp-staging'); ?></p>
                    </li>
                    <li class="wpstg-cli-sidebar-list-item">
                        <div class="wpstg-cli-sidebar-check-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="wpstg-cli-sidebar-list-text"><strong><?php echo esc_html__('Local HTTPS:', 'wp-staging'); ?></strong> <?php echo esc_html__('Automated SSL for local testing.', 'wp-staging'); ?></p>
                    </li>
                    <li class="wpstg-cli-sidebar-list-item">
                        <div class="wpstg-cli-sidebar-check-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="wpstg-cli-sidebar-list-text"><strong><?php echo esc_html__('Docker Compose included:', 'wp-staging'); ?></strong> <?php echo esc_html__('You can edit and control the entire Docker setup.', 'wp-staging'); ?></p>
                    </li>
                    <li class="wpstg-cli-sidebar-list-item">
                        <div class="wpstg-cli-sidebar-check-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="wpstg-cli-sidebar-list-text"><strong><?php echo esc_html__('Fast local spin-up:', 'wp-staging'); ?></strong> <?php echo esc_html__('Creates a ready-to-use local site quickly.', 'wp-staging'); ?></p>
                    </li>
                </ul>
            </div>

            <div class="wpstg-cli-sidebar-footer">
                <p class="wpstg-cli-sidebar-footer-label"><?php echo esc_html__('Need Help?', 'wp-staging'); ?></p>
                <a href="https://wp-staging.com/support/" target="_blank" rel="noreferrer noopener" class="wpstg-cli-sidebar-support-btn">
                    <?php echo esc_html__('Contact Support', 'wp-staging'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
