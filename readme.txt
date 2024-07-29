=== WP STAGING WordPress Backup Plugin - Migration Backup Restore  ===

Author URL: https://wp-staging.com/backup-wordpress
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: WP-Staging, WPStagingBackup, ReneHermi, lucatume, lucasbustamante, alaasalama, fayyazfayzi
Donate link: https://wp-staging.com/backup-wordpress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: backup, backups, migrate, migration, wordpress backup
Requires at least: 3.6+
Tested up to: 6.6
Stable tag: 3.8.3
Requires PHP: 7.0

Backup Restore Migrate Staging Duplicator - 100% unit tested.

== Description ==

<h3>Backup, Staging, Cloning & Migration of WordPress Sites</h3>
WP STAGING is a professional all in one <strong>backup, staging, and duplicator plugin</strong>. Create an exact copy and backup of your website in seconds! Perfect for staging, backup, or development purposes.
(Cloning and backup time depends on the size of your website)

This backup and staging tool creates a clone of your website into a subfolder or subdomain (Pro) of your main WordPress installation. The cloned site includes an entire copy of your database.

For pushing & migrating plugins and themes to the live site, creating a backup and upload a backup to cloud providers, check out [WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning")

WP STAGING runs all the time-consumptive operations for database and file cloning and backup operations in the background. This tool does <strong>automatically a search & replacement</strong> of all links and paths.

**This staging and backup plugin can clone your website quickly and efficiently, even if it is running on a weak shared hosting server.**

WP STAGING can prevent your website from breaking or going offline due to installing untested plugins!

[youtube https://www.youtube.com/watch?v=vkv52s36Yvg]

== Frequently Asked Questions ==

= Why should I use a Backup & Staging Website? =

Plugin updates and theme customizations should be tested on a staging / backup platform before applying them on the production website.
Usually, it's recommended having the staging / backup platform on an identical server like the production server. You can only catch all possible errors during testing with the same hardware and software environment for your test & backup website.

So, before you update a plugin or install a new one, it is highly recommended to check out the modifications on a clone / backup of your production website.
That ensures that any modifications work on your production website without throwing unexpected errors or preventing your site from loading, better known as the "WordPress blank page error."

Testing a plugin update before installing it in a production environment isn't done very often by most users because existing staging solutions are too complex and need a lot of time to create a
an up-to-date copy of your website.

You could be afraid of installing plugins updates because you follow "never touch a running system." You know that untested updates increase the risk of breaking your site.

That's is one of the main reasons WordPress installations are often outdated, not updated at all, and insecure because of this non-update behavior.

<strong> It's time to change this, so there is no easier way than using "WP STAGING" for backup, cloning, and migration of your WordPress website.</strong>

= How to install and set up a staging site / site backup? =
Install WP STAGING backup via the admin dashboard. Go to 'Plugins', click 'Add New' and search the plugins for 'WP STAGING'. Install the plugin with 'Install Now'.
After installation, go to WP STAGING > Staging Sites and create your first staging / backup site

= Is WP STAGING a backup plugin? =
Yes, absolutely! WP STAGING started as a staging tool but evolved to a full fledged wordpress backup plugin. Even the free version can be used for backup purposes and comes with automatic backup background processing.
The pro version delivers you few more backup features like uploading a backup to cloud backup file storage providers like google drive, (s)FTP, dropbox, Wasabi, DigitalOcean or Amazon S3 but even the free version allows you to restore the backup files in case something happens to your production site. There are many other backup plugins out there but WP STAGING's goal is to bring the reliability and performance of business and enterprise level quality assurance to a WordPress backup plugin to a new level.

We are offering a basic but still powerful backup feature free of charge for all users. If you want more, WP STAGING PRO will provide a full-fledged premium backup solution with enterprise code quality affordable for everyone.

[Video: How we run automated tests on WP STAGING](https://www.youtube.com/watch?v=Tf9C9Pgu7Bs)

= What is the difference between WP STAGING backup and other backup plugins? =

----------------------------------------------
Note: WP STAGING | PRO provides more advanced backup functionality compared to other backup plugins. The speed and Performance of WP STAGING's backup feature often exceed even the most prominent and most well-established backup plugins.
We are now adding more advanced backup features to deliver what other existing backup plugins are still missing.
----------------------------------------------

You may have heard about other popular backup plugins like All in one Migration, BackWPUp, BackupWordPress, Simple Backup, WordPress Backup to Dropbox, or similar WordPress backup plugins and now wonder about the difference between WP STAGING and those backup tools.

Other backup plugins usually create a backup of your WordPress filesystem and a database backup that you can use to restore your website if it becomes corrupted or you want to go back in time to a previous state.

The backup files are compressed and can not be executed directly. WP STAGING, on the other hand, creates a full backup of the whole file system and the database in a working state that you can open like your original production website.

Even though WP STAGING's basic has been started as pure staging plugin it now comes with powerful backup features. So it shifted from being mainly a staging plugin to a staging and backup plugin that can be used to restore a backup and bring back you website to a previous state
If you go with the WP STAGING | PRO version, you will get the same backup functionality of other backup plugins but at a much higher tested level and performance.

Note, that some free backup plugins are not able to support custom tables. (For instance, the free version of Updraft plus backup plugin). In that case, your backup plugin is not able to create a backup of your staging site when it is executed on the production site.
The reason is that the tables created by WP STAGING are custom tables beginning with another table prefix.
To bypass this limitation and to be able to create a backup of your staging site, you can use any backup plugin or the WP STAGING backup plugin on the staging site and create the backup from that site. That works well even with every other WordPress backup plugin.

= Why do I need a Backup plugin at all? =
Consistent website backups are the foundation of a robust disaster recovery strategy. For mission-critical websites,
frequent backups safeguard against data loss from hardware failures, software malfunctions, or even ransomware attacks.
By creating backups of website files, databases, and configurations at regular intervals, You can ensure a swift
restoration process, minimizing downtime and potential revenue loss.
Backups should encompass all essential data, including website code, content management system files,
user data stored in databases, and website configurations. Utilizing a combination of full backups and incremental
backups optimizes storage efficiency while capturing the latest website updates.
Furthermore, employing automated backup solutions streamlines the process, eliminating human error
and ensuring consistent data protection.
By prioritizing website backups, You demonstrate a commitment to data security and business continuity.

= I want to backup my local website and copy it to production and another host =
If you want to migrate your local website to an already existing production site, you can use our pro version [WP STAGING | PRO](https://wp-staging.com).
WP STAGING is intended to create a staging site with the latest data from your production site or create a backup of it.

= What are the benefits compared to a migration and backup plugin like Duplicator? =
We like the Duplicator plugin. Even though Duplicator is not as fast as WP STAGING for creating a backup,  it's still is a great tool for migrating from a development site to production one or from production site to development one. Overall it's a good tool to create a backup of your WordPress website.
The downside is that before you can even create an export or backup file with Duplicator, a lot of adjustments, manual interventions, and requirements are needed before you can start the backup process.
The backup plugin Duplicator also needs some skills to be able to create a backup and development/staging site. In contrast, WP STAGING does not need more than a click from you to create a backup or staging site.

If you have created a local or web-hosted development site and you need to migrate that site the first time to your production domain, you do nothing wrong by using
the Duplicator plugin! If you need all your latest production data like posts, updated plugins, theme data, and styles in a testing environment or want to create a quick backup before testing out something, then we recommend using WP STAGING instead!

If speed, performance, and code quality are a matter for you as well, give WP STAGING a try.

= I can not log in to the staging / backup site =
If you are using a security plugin like Wordfence, iThemes Security, All In One WP Security & Firewall, or a plugin that hides the WordPress default login URL, make sure that you have installed the latest version of WP STAGING to access your cloned backup site.
Suppose you can still not log in to your staging / backup site. In that case, you can go to WP STAGING > settings and disable there the WP STAGING extra authentication. Your admin dashboard will still be protected and not accessible to public users.

= Can I activate permalinks on the staging / backup site? =

Permalinks are disabled on the staging / backup site after first time cloning / backup creation
[Read here](https://wp-staging.com/docs/activate-permalinks-staging-site/ "activate permalinks on staging site") how to activate permalinks on the staging site.

= How to use a Backup file to Migrate WordPress Backup to another Host or Domain
The pro version of WP STAGING can backup your whole WordPress website.
With the pro backup function, you can backup and copy your entire WordPress website to another domain, new host, or new server very easily, and often faster and more reliable than with any other existing backup plugins.
Have a look at [https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/](this article), that introduces the backup feature.

= Is There a Translation of WP STAGING in my Language? =

We have translated WP STAGING into five languages nearly completely:

English: [WP STAGING Backup & Duplicator WordPress Plugin. Backup & Migrate WordPress websites.](https://wordpress.org/plugins/wp-staging/)
French: [Plugin WordPress de sauvegarde et de duplication WP STAGING. Sauvegarder et migrer les sites Web WordPress.](https://fr.wordpress.org/plugins/wp-staging/)
German: [WP STAGING Backup & Duplicator WordPress Plugin. Migrate WordPress Webseiten.](https://de.wordpress.org/plugins/wp-staging/)
Spanish: [WP STAGING Complemento de copia de seguridad y duplicador de WordPress. Copia de seguridad y migración de sitios web de WordPress.](https://es.wordpress.org/plugins/wp-staging/)

The following languages have been partially translated. You can help us with the translation:

Croatian: [WP STAGING Backup & Duplicator WordPress dodatak. Izradite sigurnosnu kopiju i migrirajte WordPress web stranice.](https://hr.wordpress.org/plugins/wp-staging/)
Dutch: [WP STAGING Back-up & Duplicator WordPress-plug-in. Back-up en migratie van WordPress-websites.](https://nl.wordpress.org/plugins/wp-staging/)
Finnish: [WP STAGING Backup & Duplicator WordPress-laajennus. Varmuuskopioi ja siirrä WordPress-verkkosivustoja.](https://fi.wordpress.org/plugins/wp-staging/)
Greek: [WP STAGING Πρόσθετο WordPress Backup & Duplicator. Δημιουργία αντιγράφων ασφαλείας και μετεγκατάσταση ιστοσελίδων WordPress.](https://el.wordpress.org/plugins/wp-staging/)
Hungarian: [WP STAGING Backup & Duplicator WordPress beépülő modul. WordPress-webhelyek biztonsági mentése és migrálása.](https://hu.wordpress.org/plugins/wp-staging/)
Indonesian: [WP Staging Backup & Duplikator Plugin WordPress. Cadangkan & Migrasi situs web WordPress.](https://id.wordpress.org/plugins/wp-staging/)
Italian: [WP STAGING Plugin WordPress per backup e duplicatori. Backup e migrazione di siti Web WordPress.](https://it.wordpress.org/plugins/wp-staging/)
Persian: [WP STAGING پشتیبان گیری و افزونه وردپرس Duplicator. پشتیبان گیری و مهاجرت از وب سایت های وردپرسی.](https://fa.wordpress.org/plugins/wp-staging/)
Polish: [WP STAGING Wtyczka WordPress do tworzenia kopii zapasowych i powielania. Twórz kopie zapasowe i migruj witryny WordPress.](https://pl.wordpress.org/plugins/wp-staging/)
Portuguese (Brazil): [WP STAGING Backup & Duplicador Plugin WordPress. Backup e migração de sites WordPress.](https://br.wordpress.org/plugins/wp-staging/)
Russian: [Плагин WP STAGING Backup & Duplicator для WordPress. Резервное копирование и перенос сайтов WordPress.](https://ru.wordpress.org/plugins/wp-staging/)
Turkish: [WP STAGING Yedekleme ve Çoğaltıcı WordPress Eklentisi. WordPress web sitelerini yedekleyin ve taşıyın.](https://tr.wordpress.org/plugins/wp-staging/)
Vietnamese: [WP STAGING Backup & Duplicator WordPress Plugin. Sao lưu và di chuyển các trang web WordPress.](https://vi.wordpress.org/plugins/wp-staging/)

= Can I give you some feedback for WP STAGING Backup & Cloning? =
This plugin has been created in thousands of hours and works even with the smallest shared web hosting package.
We also use an enterprise-level approved testing coding environment to ensure that the cloning and backup process runs rock-solid on your system.
If you are a developer, you will probably like to hear that we use Codeception and PHPUnit for our backup plugin.

As there are infinite variations of possible server constellations, it still can happen that something does not work for you 100%. In that case,
please open a [support request](https://wordpress.org/support/plugin/wp-staging/ "Support Request") and describe your issue.

== WP STAGING FREE - BACKUP & STAGING FEATURES ==

* Clones the entire production site into a subdirectory like example.com/staging-site.
* High Performance - Backup and clone an entire website, even with millions of database rows faster and less resource-intensive than with other plugins
* Backup schedule. Create an automatic daily backup plan.
* Easy to use! Create a clone / backup site by clicking one button
* High Performance Background Processor - Runs the backup in the background very efficiently without slowing down your website
* No Software as a Service - No account needed! All your data stays on your server. Your data belongs to you only.
* No server timeouts on huge websites or small and weak servers
* Very fast - Migration and clone / backup process takes only a few seconds or minutes, depending on the website's size and server I/O power.
* Use the clone as part of your backup strategy
* Only administrators can access the clone / backup website.
* SEO friendly: The clone website is unavailable to search engines due to a custom login prompt and the meta tag no-index.
* The admin bar on the staging / backup website is orange colored and shows when you work on the staging site.
* Extensive logging features
* Supports all popular web servers: Apache, Nginx, Microsoft IIS, LiteSpeed Server
* Every release passes thousands of unit and acceptance tests to make the plugin extremely robust, reliable and fast on an enterprise code quality level
* Fast and professional support team

== WP STAGING | PRO - BACKUP & STAGING FEATURES ==

The backup & cloning features below are Premium. You need WP STAGING | PRO to use those features. [More about WP STAGING | PRO](https://wp-staging.com/backup-pro-features)!

* Migration - Migrate and transfer WordPress to another host or domain
* Push staging website including all plugins, themes, and media files to the production website wth one click
* Clone the backup / clone site to a separate database
* Choose custom directory for backup & cloned site
* Select custom subdomain as destination for backup / clone site like dev.example.com
* Authentication - Define user roles for accessing the clone / backup site only. This can be clients or external developers.
* Multisite Support - Migrate, backup and clone WordPress multisites
* Backup Plans - Schedule recurring multiple backups by hours, time and interval
* Backup Transfer - Download and upload backups to another server for migration and website transfer
* Backup Retention - Select number of backups you want to keep on your server or cloud remote storage provider
* Backup Custom Names: Choose custom backup names to differentiate easily between different backup files
* Mail notifications - Be notified if a backup can not be created.
* Backup of WordPress multisites
* Backup to Google Drive
* Backup to Amazon S3
* Backup to (s)FTP
* Backup to Dropbox
* Specify custom backup folder destination for backup cloud storage providers
* Priority Support for backup & cloning or if something does not work as expected for you.

== DOCUMENTATION ==

== Backup, Restore & Migration ==

<strong>How to Backup and Restore WordPress</strong>
[https://wp-staging.com/docs/how-to-backup-and-restore-your-wordpress-website/](https://wp-staging.com/docs/how-to-backup-and-restore-your-wordpress-website/ "Backup and Restore WordPress")

<strong>Backup & Transfer WordPress Site to Another Host</strong>
[https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/](https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/ "Backup and Transfer WordPress to new host")

<strong>All Backup Guides</strong>
[https://wp-staging.com/docs/category/backup-restore/](https://wp-staging.com/docs/category/backup-restore/ "All Backup Guides")

<strong>Working with Staging Sites </strong>
[https://wp-staging.com/docs/category/working-with-wp-staging/](https://wp-staging.com/docs/category/working-with-wp-staging/ "Working with Staging Sites")

<strong>FAQ for Backup & Cloning</strong>
[https://wp-staging.com/docs/category/frequently-asked-questions/](https://wp-staging.com/docs/category/frequently-asked-questions/ "Backup & Cloning FAQ")

<strong>Troubleshooting Backup & Cloning</strong>
[https://wp-staging.com/docs/category/troubleshooting/](https://wp-staging.com/docs/category/troubleshooting/ "Troubleshooting Backup & Cloning")

<strong>Change your workflow of updating themes and plugins:</strong>

1. Use WP STAGING to clone a production website for staging, testing, or backup purposes
2. Create a backup of your website
3. Customize the theme, configuration, update or install new plugins
4. Test everything on your staging site and keep a backup of the original site
5. If the staging site works 100%, start the migration and copy all updates to your production site!
6. If something does not work as expected, restore the previous backup

<h3> Can´t I just use my local WordPress development system like xampp / lampp for testing and backup purposes? </h3>

You can always test your website locally, but if your local hardware and software environment is not a 100% exact clone of your production server, there is NO guarantee that every aspect of your local copy works on your production website exactly as you expect it.

There are noticeable differences like the PHP version or the server your website is running under. Still, even such non-obvious settings like the amount of RAM or the CPU performance can lead to unexpected results on your production website.

There are dozens of other reasons why a local test website will never mimic the production site server. That's why a successful local test or backup site is no guarantee that the site will run in the production environment as expected.

That is where WP STAGING shows its strengths... Site cloning, backup, and staging site creation simplified. WordPress cloning on an enterprise-ish level!

== WP STAGING BACKUP & CLONING TECHNICAL REQUIREMENTS & INFORMATION ==

* Works on latest version of WordPress
* Minimum Supported WordPress Version 3.8
* Cloning and Backup work on all webhosts
* No extra libraries required
* Backup & cloning supports huge websites
* Custom backup format is much faster and smaller than any tar or zip compression
* Backup & cloning works in low memory & shared hosting environments

== SUPPORT ==
[https://wp-staging.com/backup-wordpress](https://wp-staging.com/backup-wordpress "https://wp-staging.com/backup-wordpress")

== Installation ==

= Installation via admin plugin search =
1. Go to Plugins > Add new. Select "Author" from the dropdown near search input.
2. Search for "WP STAGING".
3. Find "WP STAGING WordPress Backup Plugin" and click the "Install Now" button.
4. Activate the plugin.
5. The plugin should be shown below settings menu.

= Admin Installer via zip =
1. Visit the Add New plugin screen and click the "Upload Plugin" button.
2. Click the "Browse..." button and select the zip file of our plugin.
3. Click "Install Now" button.
4. Once uploading is done, activate WP STAGING WordPress Backup Plugin.
5. The plugin should be shown below the settings menu.

== Screenshots ==

1. Create new WordPress staging / backup site (Dark Mode)
2. Create new WordPress staging / backup site (Lite Mode)
3. Select name for staging / backup site (Lite Mode)
4. Create Full Site Backup (Dark Mode)
5. Create Full Site Backup (Lite Mode)
6. Cloning / backup processing (Dark Mode)
7. Backup Complete (Dark Mode)
8. Login to staging site
9. Staging demo site

== Changelog ==

= 3.8.3 =
* New: Compatible up to WordPress 6.6.1
* UX: Make backup log window more appealing and consistent. #3604
* UX: Refresh error message when clicking the backup menu tab. #3587
* Fix: Disable email notifications when a backup successfully runs. #3517
* Fix: Properly catch fatal errors when merging logs into single file when sending backup error report. #3573
* Fix: Make the backup restoreable even when it has not correctly replaced table constraint(s). #3595
* Fix: Update new admin login password if user account already exists while creating staging site. (Pro) #3598
* Dev: Refactor backup remote storage downloading code. (Pro) #2751

= 3.8.2 =
* New: Compatible up to WordPress 6.6
* New: Add super admin role when creating login link. Existing staging sites need to be updated. (Pro) #3520
* New: Redesign process logs to make them more appealing and robust, ensuring they look good. #3281
* Security: Encrypt sensitive information when downloading the system info files. #3305
* Enh: Implement a mechanism that can be used to better log failure of jobs. #3436
* Enh: Add upgrade routine to enable email notifications for free version by default. #3491
* Enh: Add a tooltip to the backup modal explaining the function of "Validate Backup". #3513
* Fix: Backup Restore failed to read the cache file of old object data when using PHP 7.2. #3539
* Fix: Make sure to backup all other files in the WP root directory when running background backup. #3564
* Fix: Ensure that backup process works properly when attempting to create multipart backup with free version. #3444
* Fix: Show correct timestamp when retrieving remote backup from an FTP storage provider. (Pro) #3499
* Fix: Google authentication throws exception when user cancels backup auth process. (Pro) #3510
* Fix: Fatal error on activation of WP Staging Pro on PHP 7.0. (Pro) #3580
* Fix: Sometimes warnings were generated during PUSH when trying to cleanup tmp directory for plugins and themes. #3588
* UX: Ensure smooth transition of HTML attributes in advanced options. #3535
* UX: Toggle `Email Address` and `Slack Webhook URL` fields when email and slack notifications enabled. #3532
* Dev: Don't rerun CI workflows when one of the changelogs is adjusted. #3493
* Dev: Failing unit tests at \NoticesTest::shouldShowDisabledOptimizerNotice(). #3601

= 3.8.1 =
* New: Compatible to WordPress 6.5.5
* New: Enable remote backup loading for dropbox storage provider. (Pro) #3475
* New: Add 'Upload to Cloud' button to upload existing local backups to cloud storage. (Pro) #3331
* New: Add option to backup custom directories in WordPress root path. #2903
* New: Add backup notifications via Slack. (Pro) #3297
* New: Add backup email notifications to WP Staging free version. #3297
* Enh: Show a message when ajax requests get blocked by a firewall rule. #3449
* Fix: Magic login link does not work when it is used more than one time. Requires updating existing staging sites to fix this. (Pro) #3512
* Fix: Handle staging and backup creation when file name contains new line character. #3417
* Fix: Make "copy to clipboard" button works properly in all browsers, regardless of protocol (HTTP, HTTPS). #3443
* Fix: Show correct folder count if the staging site file structure contains multiple plugin and theme folders. #3419
* Fix: Prevent backup retention from being modified when scheduling backup. #3422
* Fix: Show 'Settings form' after authenticating with storage providers Google Drive and Dropbox. #3356
* Fix: The site URL is not replaced correctly in the blog table on PUSH for network subsites that have a different domain than the main site. (Pro) #3501
* Fix: Several PHP warnings when using RESET on an existing staging site. #3438
* Fix: Optimize and clean up CSS. Fix X and Github icons. Remove of '!important' declarations in dark theme. #3448
* Fix: Validate new admin account email address before cloning. #3467
* Fix: Make sure appropriate message is displayed after successful backup. #3474
* Fix: Some files may not be scanned and/or copied during staging site creation if their relative path to ABSPATH contains the value of ABSPATH. #3476
* Fix: Use wp_kses instead of esc_html when logging backup message in logger, to keep json formatting for messages. #3536
* Fix: Error 500 when listing backup due to open_basedir restriction on ABSPATH. (Pro) #3548
* Dev: Add unit test to make sure file extraction task works for multiple requests. #3481
* Dev: Improve basic performance cest e2e and reduce flakiness. #3522
* Dev: Rename `Compressor` service to `Archiver` service to match what this service does. #3496
* Dev: DRY multipart code, so that compression feature can be used with it. #3498
* Dev: Add developer docs for the standalone installer script. #3235

= 3.7.1 =
* New: Compatible up to WordPress 6.5.4
* New: Automatic login to staging site after initial creation by creating a temporary login. #3198
* New: Add option to run backup in background without keeping browser open. #3286
* Security: Sanitize parameters in remote storage settings to prevent possible path traversal and executing of potential malicious code. #3461
* Enh: Add support for Wordfence 2FA authentication in the WP Staging login form. #3358
* Enh: Refactor dropbox and google drive sign in buttons. (Pro) #3405
* Enh: Reducing plugin size by minifying js and css files and removing map files. #3279
* Enh: Redesign plugin deactivation feedback form. #3000
* Enh: Hide sensitive values in system info. #3447
* Fix: Unable to restore backup when it contains huge number of files which requires extracting in multiple requests. #3477
* Fix: Improve reliability and robustness of the background processor: Stalled actions will automatically be cancelled if they are in processing state for more than 15 mins. #3454
* Fix: Backup by URl throws error "Invalid backup file content". #3404
* Fix: Standalone restorer randomly terminated while restoring large files. #3348
* Fix: The backup version of WP Staging Restore is not up to date. #3425
* Fix: Refactor the contact form. New default options for sending backup log files and accepting privacy policy. #3370
* Fix: Ensure listing of remote backups and uploading of local backups to cloud storage works correctly. #3434
* Fix: Hide sensitive fields (secret key, access key...) in backup storages settings. #3389
* Fix: Don't optimize the .htaccess as default any longer if server is litespeed. Revert to old behavior by using the filter `wpstg.create_litespeed_server_config`. #3409
* Fix: Table Renaming Task fails during Restore and Push if database prefix contains capital letter(s) and database is hosted on Windows based OS system i.e. `Microsoft Azure`. #3372
* Fix: Disconnect google drive account if it fails to refresh access token. #3388
* Fix: Cloud storage options are overlapped by other elements. #3343
* Fix: Don't load and list remote backup for dropbox as it is not supported yet. (Pro) #3407
* Dev: Refactor BackupValidateTask to BackupSignerTask to match the action it does. Also move the signer related logic to separate service. #3367
* Dev: Add pre-requisite code for a new faster and more secure backup format. #2915
* Dev: Add option in UI to validate backup files during backup creation. #3368
* Dev: Auto eslint js files and format scss files during `make watch`. #3398
* Dev: Add phpcs rule to make each file ends with only one empty line. #3390
* Dev: Replace rollup-plugin-postcss with rollup-plugin-styles to have better control over source maps. #3429
* Fix: Unable to restore backup when it contains huge number of files which requires extracting in multiple requests. #3477

= 3.7.0 (Skipped) =

= 3.6.0 =
* New: Compatible up to WordPress 6.5.3
* New: Implemented dark mode UX with options to switch between 'Default OS Mode', 'Lite Mode' and 'Dark Mode' #3261
* New: Now you can restore backup of single site and/or multisite subsite to self or another multisite subsite. (Pro) #3240
* New: Allow user to push all folders under (/wp-content/). #2760
* New: Add the a new user role 'visitor' to share login link option. (Pro) #3332
* Enh: Add type hinting for ProTemplateIncluder. #3337
* Enh: Make sure to prevent other plugins from injecting their messages into WP Staging UI. #3364 #3036
* Fix: Keep cloud storage connected to Google Drive even if files listing from remote storage fails. (Pro) #3347
* Enh: Revamped system-info page, 'Purge Backup Queue' modal and moved JavaScript code to a separate file. #3262
* Enh: Automatically exclude uploads folder during push if it is a symlink. #2989
* Fix: Sync User Account feature duplicates existing user as administrator role. #3311
* Fix: Backup restore stuck on `importing users for subsite` when restoring an old backup on single site. #3373
* Fix: Make sure to handle fatal error due to missing COLLATE while creating 'wp_wpstg_queue' table for scheduled backup. #3359
* Fix: Hide the 'wp-content/wp-staging-sites' folder from staging site directory selection, as it is always excluded during cloning. #3267
* Fix: Show exact error message for open_basedir restriction error if destination directory does not have write permissions. #3116
* Fix: Memory usage of the staging site is higher than of the live site. #3307
* Fix: Make sure to only sync production site's users fields that exist in cloned site's users table. #3362
* Fix: Send log files from last 14 days and compress them before sending. Add Contact Us button to error messages. #3323
* Fix: Make sure to display default login link on custom login form if login is blocked by a security plugin with OTP or 2FA enabled. #3293
* Fix: Ensure that the All in One Security Plugin (AIOS) isn't disabled by the wp staging optimizer when AIOS's salt option is enabled. #3351
* Fix: Reconnect DB if `mysql has gone away` during update of queue table. #3354
* Fix: Create backup folder in google drive, if it does not exist, before uploading to backup cloud provider. #3381
* Fix: Make sure loading bar is removed once a WP Staging page is refreshed successfully #3365
* Dev: Add end-to-end tests for the standalone installer script. #3025
* UX: Make sure that backup cards always look good. #3345
* UX: Make sure that automatic backup icon looks good. #3338
* UX: Display backup name and cloud storage settings in 'Edit Backup Plans' Modal. #3299

= 3.5.0 =
* New: Tested on WordPress 6.5.2
* New: Add option to download and restore backup directly from cloud storage providers Google Drive, Amazon S3, OneDrive, FTP, SFTP. #1968
* New: First release of the standalone WP Staging Restore tool. Add the constant WPSTG_ACTIVATE_RESTORER to wp-config.php if you want to test it. #2435
* Fix: Generated create table DDL is corrupted during backup creation if table DDL contains multiple constraints with `CASCADE`, `SET NULL`, `SET DEFAULT`, `RESTRICT` or `NO ACTION` referentials actions. #3303
* Fix: Backup Type missing in scheduling options. Resulting in creation of entire multisite backup even for just subsite backup on multisite. #3312
* Fix: Resolve browser warnings due to invalid HTML syntax. #2490
* Fix: Make sure to remove the loading placeholder after 5 seconds if the server call experiences delays or fails due to any error. #3294
* Security: Use more secure implementation to invalidate expired login links. #3270
* Security: Prevent accessing the system info from unauthenticated users. #3290
* Security: Check if uploaded backup file is a valid WP Staging backup file before uploading it to the server. #3318 #3273

= 3.4.3 =
* New: Tested on WordPress 6.5.0
* Fix: If endurance-page-cache mu-plugin is installed (on Bluehost and Hostgator) a staging site shows white page error then. #3216
* Fix: Backup retention does not work for backups created on a staging site. #3138
* Fix: Backup log entries could contain log entries from staging processing under certain circumstances. #3079
* Fix: Moved tmp directory to wp-content/wp-staging/tmp to fix a cross-device link error on sites hosted on Azure. #3213
* Fix: Make sure that there are no errors in console after uploading backup to remote storages. #3258
* Fix: Handle complex table relation syntax on SQL dumper and restorer. #3259
* Fix: Validate and handle null values before invoking strlen() to ensure compatibility and prevent runtime errors. #3127
* Fix: Make network cloning more robust by supporting different combination of www prefix. #3230
* Fix: On some server files were not properly extracted. Using a fallback function now. #3272
* Fix: Fix condition to check custom destination path for staging site on basic version. #3282
* Fix: Warning "Indirect modification of overloaded elements" of WP_Hook. #3155
* UX: Make sure the loading placeholders are rendered properly over all pages. #3207
* UX: Beautify "license invalid" messages. #3237
* Enh: Don't prefix html attribute 'data' by 'wpstg'. #3048
* Enh: Add pro clone features as inactive items to Actions button in wp staging free version. #3228
* Enh: Show error message if custom selected destination path for staging site will be same as root of live sites folder. #3204
* Enh: Show better backup logs and warnings if backup fails. #3263
* Dev: Add support for retrying failed tests in codeceptione2e suites. This is done to counter test flakiness. #3118
* Dev: Update outdated code of wpstg uncomment command. #3245
* Dev: e2e_backup_test make command was not using basic plugin when running basic tests. #3225
* Dev: Adding improved logging. #3252
* Dev: Make sure the debug.log is kept clean from unwanted logs when running e2e tests. #3202
* Dev: Refactor 'ThirdParty' namespace changing Framework/Support/ThirdParty to Framework/ThirdParty. #3224

= 3.4.2 =
* [skipped]

= 3.4.1 =
* Enh: Remove files that were false-positive detected as malicious. #3184
* Fix: Remove two css files accidentially loaded on the frontpage. #3208
* Fix: Make sure to not encode single and double quotes while downloading log files. #3168

= 3.4.0 =
* New: Add option to create a new admin user account for your staging site during it's creation. #3087
* New: Add option to use non-blocking process for uploading to FTP storage when using FTP extension for backup upload. #3103
* New: Add advanced excludes option at WP CLI backup-create command. #3114
* New: Add 'Delete Settings' button within the backup storage provider interface to facilitate the removal of the cloud provider configuration. #3108
* New: Add support for Search Replace of urls in content of network subsites. #2960
* New: Add option on edit staging site page for testing database connection. #3106
* New: If uploads folder is symlinked exclude it from disk space calculation before creating a staging site. #3092
* Enh: Update look and feel of backup scheduling modal. #3090
* Enh: Add page loader for each page of the user interface. #3142
* Enh: Add new upgrade buttons to header and license page. #3135
* Enh: Add more information like database name to system information. #3125
* Enh: Avoid scanning of excluded directories during the push and backup process. #3049
* Fix: Fatal error on old WordPress 4.4.32 due to using of get_current_network_id(). #3174
* Fix: Base prefix wrongly replaced for users and usermeta tables in views when creating backup of views in multisite subsite. #3128
* Fix: Prevent error while directory listing protection due to open_basedir restriction. #3180
* Fix: Update free version plugin meta description to "Required by WP Staging Pro". #3171
* Fix: Remove redundant admin notices for invalid license keys during activation. #3139
* Security: Fix a potential security error and add better sanitizing for backup title. #3152
* Fix: Make sure EDD license checks are triggered only once. #3179
* Fix: Google drive authentication not working properly under all circumstances. #3156
* Fix: Selected custom tables on a staging site that had a different prefix than the prefix in the wp-config.php could not be pushed anymore. #3170
* Fix: Prefix for user capabilities was not replaced when creating a backup of network subsite. #3129
* Fix: The "Prefix" field was empty for listed staging sites if they were created in an external database and the prefix was not specified. #3166
* Fix: The optimizer setting was shown as disabled, even if it was still active. #3151
* Fix: Add loading icon beside 'Refresh License Status' button and adjusted loading bars on licensing page. #3185
* Fix: Super (network) admins were not able to login with when network subsite backup was restored on a single site. #3191
* Fix: Reference Error `wpstgPro is not defined` during staging site creation on FREE version. #3136
* Fix: Make sure that backup plugin notice doesn't overlap Create Staging Site button in UI. #3148
* Fix: List of active plugins in system info is misleading. #2996
* Fix: Make sure that mail setting page looks good on all screen resolutions. #3094
* Fix: Incorrect process modal title for preserving data task. #3130
* Fix: DRY properties in BackupMetadata and remove error message "trying to hydrate dto errors BackupMetadata::setCurrentNetworkId()" #3199
* Dev: Update DI52 library to latest version for small performance gain. #3146
* Dev: Fix missing adminer host on wpstg command. #3120
* Dev: Load Basic or Pro service provider once other dependencies are loaded. #3160
* Dev: Populate dev hosts from env variables to add to hosts file. #3122
* Dev: Add adminer to dev tools for database management. #3112
* Dev: Fast test fails on GitHub due to the missing of 'wpstgBackupHeader.txt' file, checksum needed to be updated each time. #3110
* Dev: Update Github actions to latest version that uses Node 20 to avoid deprecation message Github CI. #3200

= 3.3.3 =
* New: Support for WordPress 6.4.3
* New: Add filter `wpstg.push_excluded_directories` to exclude specific folders during push. #3050
* New: Add 'Do review link' to backup success modal after staging site and backup creation. #3085
* Enh: Reduce number of API calls to wp-staging.com for version number checking. #3091
* Enh: Add option to use APPEND Mode for uploading backup using FTP from settings UI. #3044
* Enh: Add loading icon during activation of the free version. #3041
* Enh: Improve the message when backup has been created with older version of WP Staging. #3033
* Enh: Make sure the checkbox icon appears at centre on all system. #2920
* Enh: Make sure that font size and view layout is consistent in staging and reset modal. #3104
* Fix: Can not update email address for sending error reports. #3109
* Fix: Deprecation message about dynamic properties thrown by Google Drive Api Model class. Show exact error message when unable to get resume URI for Google Drive backup upload. #3076
* Fix: Make sure to not check external DB credentials in free version while creating staging site. #3054
* Fix: Editing the backup schedule sometime re-creates the schedule cron at a wrong time. #3101
* Fix: Add filter to hide primary key changes message and include primary key details in the system information. #2972
* Fix: Not all files are sometimes pushed under certain situation. #3082
* Fix: Scheduled Backups unable to run when a manually created backup exists. #3089
* Fix: The backup retention number of Google Drive backups isn't honored. #3063
* Fix: Handle issues when unable to fetch information for external database during cloning requirements. #3029
* Fix: Show correct version of WP Staging in backup and staging log files. #3010
* Fix: Type error when passing multiple parameters using hooks methods. #3064
* Fix: Reduce height of the delete staging site modal. #3058
* Tweak: Keep only wp-staging* plugins active during database renaming process on backup restore to avoid conflict. #3095
* Tweak: Deprecate Filter 'wpstg.ftpclient.forceUseFtpExtension' as we already provide alternate option in FTP settings UI. #3053
* Tweak: Improve success message after push about clearing site and theme cache, which may be required if the front page appears different than expected. #3003
* Dev: Add initial level logic to support Backup Compression and Restore of Compressed Backups. #2555
* Dev: Reduce number of manual e2e dispatch call by adding wp cli and cloud storages e2e in full PRO e2e suite and run them in parallel. #3073

= 3.3.2 =
* Fix: Catch type errors on properties hydration for Backup Metadata and other backup related tasks. #3072

= 3.3.1 =
* Fix: Handle warnings when unable to unserialize serialized data during cloning. #3004
* Fix: Don't logout when restoring backup wp.com site on last step when database was not restored. #3031
* Fix: PHP Fatal error: Uncaught TypeError when using Litespeed server. #3060

= 3.3.0 =
* New: Add backup and restore support for sites hosted on WordPress.com. #2433
* New: Add input form for backup URL directly migrate a backup to another site. #2752
* Enh: Allow to re-authenticate if current session expires during creating staging site, backup or push process. #2285
* Enh: Show all network site host-names in system info. #2953
* Enh: During PUSH, use temp directory outside of plugins and themes directories to avoid plugin duplication and conflicts in case of a failure. #1595
* Enh: Refactor and DRY backup cache class. #2991
* Enh: Show admin message if user has another backup plugin installed and tell how amazing the WP Staging feature is:-) #2966
* Enh: Add dev filter activation. #2976
* Enh: Don't reload all staging sites after creating staging site for smooth user experience. #2940
* Enh: Show better error message when cloning process stops due to memory exhaustion. #2935
* Fix: Although all tables were renamed correctly during backup restore, the backup restore log sometimes show incorrect number of tables restored. #2974
* Fix: Can't serialize unserialized data that has an instance of a class in the object. Relevant for backup and backup restore. #2981
* Fix: Make sure to execute backup performance javascript only when creating a backup. #3019
* Fix: Installing the required free version when installing the pro version might disable all network active plugins on a multisite network. #2997
* Fix: Improve cleaning up of cache files after pushing and backup. #3021
* Fix: If only one file is selected for pushing, that file is not copied. #3011
* Fix: Under rare circumstances a push could miss to copy the last file of a queue. #2901
* Fix: On Bitnami Hosted WordPress Sites, Plugins and Themes are not replaced during PUSH due to symlinked wp-content folder. #2692
* Fix: Undefined index of databaseSsl #2995
* Fix: Notice "Free version required" may show up on network admin page while backup free version is not network activated. #2949
* Fix: Don't show "Customized uploads folder notice" when host is flywheel. #2970
* Fix: Make sure javascript events are only registered when the corresponding element is loaded in the DOM. #3039
* Dev: Fix reauthentication e2e when creating backup. #3006
* Dev: Add a windows based docker setup to run all backup and staging e2e and unit tests on a real Windows environment. #2699
* Dev: Update nodejs docker image for building and compiling assets.#3016

= 3.2.0 =
* New: Support up to WordPress 6.4.2
* New: Adding feature to create backup of individual network site in multisite installations. #2795
* New: Add backup settings in backup logs. #2969
* Fix: Automatic backup repair if the backup file index is corrupted. #2861
* Fix: Saves remote storage backups in the database for use when calculating the number of backup to keep on remote storage. #2856
* Fix: Prevent accessing the content of backup cache files created during cloning and pushing jobs. #2984

= 3.1.4 =
* New: Add smart exclusion options for backup creation and allow exclusion of cache files, post revisions, spam comments, unused plugins and themes and deactivated plugins with a single click. #2758
* New: Add 'Refresh License Status' button beside 'Activate License' and 'Deactivate License' button. #2809
* New: Show modal in free version when there are performance issues after creating backup. #2721
* New: Add option to upload already created backup to a cloud storage. This feature will be disabled by default. But can be enabled by defining WPSTG_ALLOW_REMOTE_UPLOAD constant to true. #2610
* Enh: Formatting and cleaning up legacy backup related core/utils code. #2910
* Enh: Improve the admin notice about the required free version during plugin installation to make it more visually striking. #2885
* Enh: Add placeholders for pipe and colon characters in file and path names so that they can easily be extracted from backup file index. #2575
* Enh: Disable the 'start backup' button when no storage is selected. #2921
* Enh: Formatting and clean up for WPStaging PHPCS Sniff code. #2851
* Fix: Page loading wrapper was overlapping elements on staging page. Add a loading bar below the header menu when switching between staging and backup main tabs. Adjust the delete modal! #2952 #2938
* Fix: Exclude unused themes filter doesn't work properly for entire multisite backup. #2927
* Fix: Urls containing port i.e. example.com:8080 were not correctly replaced during using backup for site migration if destination site has different port. #2919
* Fix: Due to presence of www prefix in constant DOMAIN_CURRENT_SITE on the destination site during backup restore, both source and destination weren't considered same site for updating subsites related URLs. #2954
* Fix: Remove Type strictness from custom wp_mail function used in staging site when mail sending is blocked to avoid "Uncaught TypeError". #2932
* Fix: Unable to create directory with correct permissions in ABSPATH when ABSPATH has wrong or custom permission i.e. 0170 but ABSPATH is writeable during staging site creation. #2925
* Fix: Upsell welcome page shows up when pro is installed and free activated. #2939
* Tweak: Show a proper message on FREE version why the backup cannot be restored if backup has same site url with a different url scheme. #2841
* Dev: Some e2e tests were still using old checksum file for comparing plugin dist package, which leads to plugin not being latest for those tests. #2916
* Dev: Add PHPCS rule improvements for return type hint. #2899
* Dev: Moved backup cloud storage related e2e tests to separate suite and DRY workflows generation for e2e tests. #2951
* Dev: Don't run storage related tests on basic e2e backup tests. #2930
* Dev: Add digitalocean backup dev credentials into dev doc. #2963

= 3.1.3 =
* Fix: There was a potential security vulnerability due to which a malicious user could eventually exploit and access the wp staging cache folder. This issue is now fixed. The security issue was found by Thanks to Dmitrii Ignatyev from cleantalk.org. #2908
* Fix: Siteground related issue: file_put_contents() doesn't free up resource automatically immediately, which caused error 500 during backup extraction on SiteGround hosting (due to limited resources). #2868
* Fix: For domain based subsites during backup restore, home and site_url were not adjusted properly automatically. #2857

= 3.1.2 =
* New: Compatible up to WordPress 6.4.1
* New: Add latest cloning, pushing, backup and backup restore logs when the user downloads the log files or opens support ticket and share debug information. #2806
* Fix: Warnings in WordPress 6.4 because WordPress removed property $wpdb->use_mysqli. This could lead to a backup error. #2881
* Dev: Add missing twentytwentyone theme to multi_tests to make backup tests work. #2888
* Dev: Add a Sniff rule to check for proper use of esc_html_e in backup related code. #2875
* Dev: Use cache and run unit tests in parallel to reduce time taken by fast backup tests. #2862
* Dev: Fix DB version in DB seed file for multisites backup tests. #2891
* Dev: Fix issue with WP CLI e2e backup test not running due to missing core plugin. #2896
* Dev: Add make command to update DB backup seed files. #2893
* Dev: Make sure that the .gitignore file remains intact and doesn't get deleted when running ./wpstg changelog:update command. #2873
* Dev: PHPCS rule for adding a newline after "for, foreach, if" block statements. #2831
* Dev: Use constants for job type for cloning/pushing backup process instead of hard coding them. #2850

= 3.1.1 =
* New: Make WP Staging compatible up to PHP 8.3 RC. #2543
* New: Add option in settings UI to force use FTP extension over FTP curl method for remote uploading backup using FTP. #2731
* New: Add constant `WP_DEVELOPMENT_MODE` with value `all` to new staging site. `WP_DEVELOPMENT_MODE` constant was added in WordPress 6.3. #2792
* New: If the "wpstg_push_excluded_tables" filter is used to exclude tables from the push process, these tables are deactivated in the push table selection. #2776
* Enh: Make sure pro upgrade button font color can not be overwritten by third party. #2846
* Fix: Improve design of the "delete staging site" modal. #2843
* Fix: Existing previously created backup files may be invalid under certain circumstances and can lead to a faulty website after restoration. This is a highly recommended update! Please create a new backup after installing this update to ensure that this potential error does not affect your backup file. #2861
* Fix: Remove warnings in debug.log that say "WP STAGING: Another instance of WPSTAGING active...". #2849
* Dev: Use 7zip instead of zip to achieve better compression ratio to create smaller plugin zip packages. #2854
* Dev: Make Flywheel e2e tests pass that were failing due to missing file in flywheel structure. #2871

= 3.1.0 =
* New: Compatible up to WordPress 6.3.2
* New: Exclude GoDaddy mu-plugin by default when creating a staging site. #2744
* New: Add modal to opt-in for diagnostic monitoring after first installation. #2391
* New: An active installation of the free core plugin will be mandatory for pro version 5.1.0 and later. #2612
* Enh: Added information into system info logs to indicate whether the site is a single site or a multi-site. #2790
* Enh: Show notice that permalinks won't work on WP Engine sites. #2142
* Enh: Add Contact Us button to the main menu bar. #2763
* Enh: Update license updater and fix a small error that can lead to broken API requests. #2817
* Enh: Remove type strictness from optimizer plugin functions which are used with wp hooks to avoid conflict with other plugins. #2830
* Fix: Backup uploading to FTP fails if ftpclient is used and ssl enabled. #2750
* Fix: Staging login doesn't work if Wordfence Activated option exists in the database but there is no Wordfence plugin active. #2812
* Fix: Magic login link does not work if only the free version is active on the staging site. This requires updating WP Staging Free and Pro plugin on staging site. #2781
* Fix: Custom WP content paths inside ABSPATH were not correctly cloned for newly created staging site. This could led to missing images and languages. #2740
* Fix: When editing staging sites database connection, don't make live sites database prefix mandatory when staging site uses an external database. #2768
* Fix: Deprecated warning in login form can prevent login to staging site. #2804
* Fix: Upgrade routine was not working for wpstg_queue table for adding response field in the table for FREE version. #2828
* Fix: During the file extraction of backup restore, `file_put_contents` is more consistent and faster than `touch`. Can prevent a site from being broken after backup restore due to possible bug in php 8.1.22. #2807
* Dev: Add setup for e2e testing of wordpress.com support. #2739

= 3.0.6 =
* Fix: There could be a fatal error after plugin activation on multisites if a plugin uses the filter `site_option_active_sitewide_plugins`. #2785
* Fix: Theme Twenty Twenty Three has a bug that leads to corrupt staging sites. The transient `_transient_wp_core_block_css_files` breaks the css after migrating or creating a new staging site. This transient will be deleted on a freshly created staging site. Related: https://wordpress.org/support/topic/wordpress-block-styles-not-loading-in-frontend/ #2778
* Dev: Add make command to check class method return type hints #2769

= 3.0.5 =
* New: Compatible up to WordPress 6.3.1
* New: Add support for Wordfence 2FA authentication in the WP Staging login form. #2253
* New: Add two new filters to allow updating active plugins on staging site after cloning and on live site after pushing. `wpstg.cloning.update_active_plugins` and `wpstg_pushing_update_active_plugins`. #2409
* Enh: Added real time elapsed time counter and restructured new staging process modals. Fixes an issue where log files are mixed up in the log window after canceling a staging process. #2651
* Enh: Enable "Email Error Report Sending" as default in WP STAGING | PRO during initial plugin installation #2722
* Enh: Wrap WP Staging hooks in custom methods to make WP Staging plugin(s) less exploitable. #2748
* Fix: Php error 'cross-device link' can lead to backup restoring failing on Microsoft Azure servers. #2548
* Fix: Drop-in plugins like object-cache.php and advanced-cache.php were not excluded by default during cloning. #2746
* Fix: Exclude wp-content/wp-staging-sites directory during CLONING and BACKUP and also keep this directory during Backup Restore #2765
* Fix: The delete confirmation modal shows a wrong staging site name when there is more than one staging site. #2741
* Fix: Exclude All in One Security Plugin (AIOS) from being disabled by wp staging optimizer during staging or backup operations when AIOS has the post prefix option enabled as this will make wp staging unusable. #2672
* Fix: After backup creation and backup restoring, the log entries in the success modal get purged and are not visible anymore. #2719
* Fix: Make the batch renaming process for tables (in both RESTORE and PUSH) more robust and less error prone. #2772
* Dev: Fix inverse usage of TaskResponseDto::isRunning #2643

= 3.0.4 =
* New: Allow the usage of wildcards characters in the push filter 'wpstg_preserved_options'. #2546
* New: Exclude multiple database values from copying to a staging site table through the 'wpstg.cloning.database.queryRows' filter. #2545
* New: You can add the new constant WPSTAGING_DEV_SITE into wp-config.php that determines if site is a staging/dev site or regular one. #2556
* Fix: Cannot upload single backup files larger than 25GB on SFTP Storage. #2539
* Enh: Removed console logs and clean up code. #2561
* Enh: Rename logfile to wpstg-bundled-logs.txt while downloading and sending report to support team. #2593
* Enh: Improve admin notice if cron jobs do not work. #2723
* Fix: Fatal error due to foreign key constraints on tmp or bak tables when cleaning WP Staging tmp and bak tables before Pushing a staging site to live. #2728
* Fix: Fatal error during backup restore if site has a drop in plugin like db.php or object-cache.php due to incorrect checking the checksum of the drop in plugin file. #2715
* Fix: Add new constant `WPSTG_MEMORY_EXHAUST_FIX`, that can prevent memory exhausted errors during backup creation. #2551
* Fix: A custom UPLOADS folder outside wp-content was not pre-selected and copied during cloning process and tustom UPLOADS folder was not copied during pushing a staging site as well. #2736
* Fix: Improve admin notice when a pro backup is restored with the free version on another domain, host or server. #2697
* Tweak: Adjust the requirements for restoring backup by making it dependant on backup structure instead of plugin version. #2717
* Dev: Bump AWS PHP SDK package to latest version #2295
* Dev: Remove PHP 5.6 compatibility code #2714
* Dev: Reduce file size of the free plugin by removing unused backup cloud storages libraries from vendor packages. #2710
* Dev: Add e2e tests for admin notices for backup and staging #2652
* Dev: Delete tests output dir content while running `make reset` and `./wpstg build:webdriver-rebuild-dist` #2726

= 3.0.3 =
* New: Add dropbox cloud storage backup provider. #1881
* New: Add support for custom domain based multisites. Network sites will be created in a subdomain. E.g.If the main site is "example.com" and the staging site destination url is "staging.example.com" then the live network site example.org will be cloned to staging.example.org and the network site example.net will be cloned to staging.example.net automatically. #2600
* New: Add more options to wp cli backup backup-create command #2468
* New: Add new contact us modal for free version to be able to provide better support to free users. #2246
* New: Create staging site as default into 'example.com/wp-content/wp-staging-sites/staging-site- name' if root folder is not writable. #2438
* Enh: Revise warnings in the symlink modal tooltip and improve the HTML syntax structure #2668
* Enh: Transform the 'click here' hyperlink into a button on the staging site to enable staging site cloning #2664
* Enh: Show admin notice on staging site if symlink option has been used to create it #2667
* Enh: Added target URL when transferring staging site to live site #2362
* Fix: Show admin notice on all pages on live or staging site if current site uses wpstgtmp_ or wpstgbak_ table prefixes as live table. #2666
* Fix: Canceling the New CLONING process would delete all tables of production site if advanced settings were used to provide custom prefix for staging site with same database and host as production site. #2665
* Fix: WP Staging backup folder gets deleted during restore on Windows OS. This can lead to an interuption and fatal error of the backup restore. #2690
* Fix: Improve condition whether a table belongs to current site table during PUSH when cleaning temporary tables. #2686
* Fix: If database tables prefix contains underscore like wp_12345, the sql backup part in multiparts backup is detected as separate backup. #2656
* Fix: Having residual tables with wpstgbak_ prefix can leads to unsuccessful PUSH and backup RESTORE. Unless the current site prefix is wpstgbak_, these tables are now removed before starting RESTORE and PUSH #2576
* Fix: Added style enhancement of changing the cursor to a pointer when interacting with the 'Contact Us' button #2662
* Fix: Copied generated login link contains inline css style. #2654

= 3.0.2 =
* New: Support for WordPress 6.3
* New: Add more options to wp cli backup-create command #2468
* Enh: Increase log file storing time to 14 days. #2625
* Enh: Add admin notice to explain the new backup feature in free version #2524
* Enh: Enhance free version backup modal UI #2608
* Enh: Don't allow saving empty table prefix for a staging site when using the edit button #2572
* Fix: Use DTO instead of separate file for managing index sizes while creating backup to avoid filesystem lock. #2640
* Fix: Backup creation fails on PHP 8 because unable to get primary key for table. #2629
* Fix: Stop backup restore if no sql query is found to be executed but the backup contains a database to be restored #2560
* Fix: Show admin notice if there is no WordPress table prefix in the database #2586
* Dev: Add changelog entries in dedicated files #2623
* Dev: No changelog needed on release branches #2763

= 3.0.1 =
* New: Make UI more consistent and use same success and processing modals for staging site creation as for backup creation #2221
* Fix: Rating banner can not be dismissed #2632
* Fix: Multipart backup scheduled to be sent to google drive does not send all parts #2516
* Fix: Could not backup tables that contain multiple primary keys (composite keys) #2616
* Fix: Stop backup restore and add better logging if sql file is not readable during backup restore #2560
* Fix: New delete modal does not show all tables on sites with many db tables due to CSS issue #2221
* Fix: Resolved conflict with plugin "Admin and Site Enhancements" #2513
* Fix: Prevent UI issue and word wrapping on line that says: "No staging site found" on MacOS on Chrome #2552
* Dev: Undefined method interfaceDatabaseClient::fetchAll() in phpstan #2622
* Dev: e2e tests fail on multi sites #2631

= 3.0.1 =
* New: Make UI more consistent and use same success and processing modals for staging site creation as for backup creation #2221
* Fix: Rating banner can not be dismissed #2632
* Fix: Multipart backup scheduled to be sent to google drive does not send all parts #2516
* Fix: Could not backup tables that contain multiple primary keys (composite keys) #2616
* Fix: Stop backup restore and add better logging if sql file is not readable during backup restore #2560
* Fix: New delete modal does not show all tables on sites with many db tables due to CSS issue #2221
* Fix: Resolved conflict with plugin "Admin and Site Enhancements" #2513
* Fix: Prevent UI issue and word wrapping on line that says: "No staging site found" on MacOS on Chrome #2552
* Dev: Undefined method interfaceDatabaseClient::fetchAll() in phpstan #2622
* Dev: e2e tests fail on multi sites #2631

= 3.0.0 =
* New: Drop support for php 5.6. Minimum php version is 7.0 #2579
* New: Flywheel hosting compatibility. Create staging sites and create backups on hosting providers where the WordPress core is located outside the public dir. #2372
* New: Add system info to backup log file #2309
* Enh: Check if sftp backup directory path is writeable while testing remote connection #2506
* Enh: Add a tooltip to inform that backups will be created only for the current site and do not contain other sites, like staging websites #2483
* Enh: Allow the user to click on "Copy" to copy the generated login link #2443
* Enh: Improved code to be compatible with PHPstan level 3 rules #2461
* Enh: Changed the order of login link expiry to day, min, sec #2380
* Enh: Update back button color to make clear it is an active button #2512
* Enh: Display the size of backup index parts in the list of backups #1678
* Enh: Show the "Push Changes" button always in WP STAGING PRO and disable it until the license is activated #2466
* Fix: WP CLI command `wp wpstg backup-status` did not work as expected #2467
* Fix: Unselect folders starting with wp-admin* and wp-includes* when creating a new staging site #2340
* Fix: Show the "primary key" warning only on WP STAGING admin page #2477
* Fix: Multisite related constants missing during network clone when the original wp-config.php is not valid defaul multisite configuration file #2504
* Fix: Restoring a backup fails because redis/memcache not configured on the destination site. This disables object cache if object-cache.php is not identical on backup and restoring site #2517
* Fix: Php error 'Undefined constant DOMAIN_CURRENT_SITE' #2512
* Fix: Microsoft IIS 7.5 with php 8.1.9 produces unexpected time format when using microtime() resulting in fatal error "division by zero" while creating a backup #2571
* Fix: `www` prefix is set in `domain` property of subsite would result into repeating `www` prefix during network clone #2544
* Fix: Don't escape MySQL binary and blob data during cloning as this results into invalid data #2565
* Tweak: Small visual glitches on tooltip in upload backup modal #2496
* Dev: Improved usage of $status variable in backup and cloning #2332
* Dev: This PR does a comprehensive pass on the codebase to verify that authorization checks are being done on callbacks that takes user input. #2531

WP STAGING Backup & Cloning | Full changelog:
[https://wp-staging.com/wp-staging-changelog](https://wp-staging.com/wp-staging-changelog)

== Upgrade Notice ==
Compatible up to WordPress 6.6.0. Multiple enhancements in UI and performance. Updating is recommended!
