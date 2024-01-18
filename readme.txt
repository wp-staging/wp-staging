=== WP STAGING WordPress Backup Plugin - Migration Backup Restore  ===

Author URL: https://wp-staging.com/backup-wordpress
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: WP-Staging, WPStagingBackup, ReneHermi, lucatume, lucasbustamante, alaasalama, fayyazfayzi
Donate link: https://wp-staging.com/backup-wordpress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: backup, backups, migrate, migration, wordpress backup, move
Requires at least: 3.6+
Tested up to: 6.4
Stable tag: 3.3.2
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

1. Create new WordPress staging / backup site
2. Select name for staging / backup site
3. Select folders to include in staging / backup site
4. Cloning / backup processing
5. Listed staging / backup sites
5. Listed staging / backup sites
6. Open, edit & delete staging / backup sites
7. Login to staging / backup site
4. Demo of a staging / backup site

== Changelog ==

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

=2.16.0 =
* New: Bring the premium high-performance pro backup feature into the basic version to allow creating a periodic backup of the WordPress website including backup restoring. #1979 #2487
* Enh: Display Google Drive account info permanently on the settings page. Remove the option to customize the Google drive redirect uri. #2399
* Enh: New styled back button #2444
* Fix: Database tables renaming is now done in multiple requests instead of a single request for both PUSH and RESTORE. This allow easily rename hugh number of tables on slow server without worrying about increasing PHP Script timeout limit #2392
* Fix: On backup restore keep or remove the staging site mode depending upon the current site state. E.g. Restoring a backup of a staging site on a live site will remove the staging site mode #2392
* Fix: Small JS errors related to the backup sidebar menu when clicking tabs on the main navigation #1979
* Fix: Backup restore can fail if backup has different access token than current site #2481
* Fix: Several php 8.x warnings on staging site push operation in wpstg debug log #2472
* Fix: When cloning into external database NULLs were converted to empty strings in the staging site #2448
* Fix: Backup created with free version can show a warning if it is restored with pro version #2497
* Dev: Update google webdriver driver test credentials #2450

= 2.15.0 =
* New: Compatible up to WordPress 6.2.2
* New: Pro feature to allow generation of magic login links to staging site. Go to "Actions > Share Login Link". This requires updating WP Staging Pro plugin on staging site #2204
* New: Pro feature to sync active admin account. Go to "Actions > Sync User Account" to synchronize active admin user account with staging site #2183
* New: Feature to disable WP Cron on the staging site #2314
* New: Add two-way encryption for backup storage credentials stored in the database. Read https://wp-staging.com/docs/wp-staging-encryption-setup/ #2310
* New: Show selected plugins and themes count before cloning #2339
* New: Support SSL connection to external databases #2264
* New: Allow WP-CLI to work on the staging site #2280
* New: Button to unselect all staging site tables before cloning #2355
* Enh: Beautify backup and staging log file format #2393
* Enh: Append debug log to download system information #2441
* Enh: Improved code to be compatible with PHPstan level 1 rules #2348
* Enh: Improved code to be compatible with PHPstan level 2 rules #2419 #2459
* Enh: Check if the option_name field contains the primary key before cloning #2386
* Enh: Display the staging site name on generate login link screen #2432
* Enh: Refresh UI and add new animated loader icon to show backup progress #2292 #2349
* Enh: Refresh UI and format system information page #2333
* Enh: Refresh UI an add button to open staging site after updating staging site #2358
* Enh: Refresh UI. Show more clear cron message and take into account that WP_CRON could be disabled if external backup cron is used #2378
* Enh: Improve backup email error report and show more clear messages when a backup fail #2383
* Enh: Display a message when the backup or restore process stops due to lack of server memory #2210
* Enh: Combine wp staging logs and system info logs #2224
* Enh: Show admin notice when optimizer mu-plugin is disabled to improve reliability of backup creation #2301
* Enh: Run backup validation checks again after deleting a backup #2286
* Enh: Toggle triangle icon on multi part backup click #2308
* Enh: Make more clear where to enter the license key #2404
* Enh: Allow uploads directory outside ABSPATH by using the filter `wpstg.backup.directory` #2359
* Enh: Improves the look of the update notice #2294
* Fix: Inaccurate performance values "queries per second" for backup restore #2327
* Fix: Backup download URLs now support mixed http/https scheme #2376
* Fix: Deprecation notice on PHP 8.2 / 8.1 #2389
* Fix: Write the correct version number in wpstg.js when creating a free dist package #2431
* Fix: Correct use of printf and gettext #2313
* Fix: On backup creation MySQL Error 1118 row size too large error can appear #2422
* Fix: Fix line-break issue during files-index validation when validating a backup on Windows OS that was created on Linux OS and vice versa #2402
* Fix: Add Query Compatibility fix when restoring a database backup created on MariaDB with PAGE_COMPRESSED=`ON` on MySQL database #2401
* Fix: PHP 8.1 warnings on backup creation due to using null value on wp_normalize_path #2453
* Fix: Added method to handle sending email in free version with attachments #2417
* Fix: Selecting a child folder automatically selects the parent folder. #2317
* Fix: Toggle correctly the side bar navigation depending if staging or backup tab is active #2261
* Fix: Excluding sub-directories on backup did not work with using the filter 'wpstg.backup.exclude.directories' #2291
* Fix: Database permission warning did not disappear due to escaped database name #2234
* Fix: Remove sanitize_url() deprecated notice #2306
* Fix: Could not download system info #2323
* Fix: Catch runtime exception when checking for valid backups during invalid file index notice #2312
* Fix: Use correct html element id to fix js console error #2344
* Fix: Last backup duration was always 0 min, 0 sec. It is now fixed #2383
* Fix: Retry adding the backup file index up to 3 times if adding the backup file index fails #2383
* Fix: Login redirect loop on wp-admin #2385
* Fix: Remove double `www` prefix if network is cloned to a `www` prefixed hostname but subsite already has `www` prefix #2284
* Dev: Check if the Issue or PR Number exist in the changelog #2394
* Dev: DRY webdriver tests #2276
* Dev: Decouple PRO feature from WPStaging\Backup namespace and move them into WPStaging\Pro\Backup namespace #2296 #2336 #2356
* Dev: Fix issues running basic webdriver tests on Github CI #2319
* Dev: Add timeout to Github actions #2324
* Dev: Add more unit tests to increase reliability of the backup plugin #2273
* Dev: Refactor backup terms `Export` to `Backup` and `Import` to `Restore` for consistency #2265
* Dev: Move src/Pro/Backup to src/Backup as a first step to allow basic Backup feature to be added in Free version of WP Staging #2287 #2297
* Dev: Write wp staging debug logs into global debug.log #2346

2.14.1
* Fix: Remove custom global exception handler to avoid conflicts with other plugins #2424

2.13.0
* New: Major change of the User Interface #2197 #2208
* New: Add an option to send an email error report. You can enable this option in the settings and use a separate email to receive error reports #2205
* New: Button to edit backup schedules #2042 #2135
* New: Show Google Drive backup account information on the plugin's Google Drive backup settings page #2091
* New: Add a cancel button to stop the pushing process #2101
* Enh: Show a notice if site contains backup(s) with invalid files-index #2205
* Enh: Add a retry mechanism to backup restore if a backup restore fails due to 404, 429 or 503 errors #2094
* Enh: Clean up log files and don't show necessary information #2124
* Enh: Show notice how to enable permalinks depending on whether the server is Apache or another. #2143
* Enh: Explain what to enter in the 'version' field of Generic S3 provider #2172
* Enh: Show notice how to fix cron issue when using Litespeed server #2170 #2226
* Enh: Better notification message when there are no files to backup #2175
* Enh: Take sub folder into account when testing location for Google Drive Storage #2197
* Enh: Include the current date and time in the database filename when the backup is a "Multipart Backup" #2126
* Enh: Database restore enhancement to increase the execution time for executing an ajax triggered SQL query when query execution fails for a certain number of retries on weak servers #2117
* Enh: Hide license key for privacy reasons, for instance when plugin is used by agencies on client websites #2118
* Enh: Add filter `wpstg_login_form_logo` to allow custom change of the logo image on login page to staging site #2076
* Enh: Clarification that the maximum retention period for backup in the "Create backup" modal only applies to locally hosted backup files #2085
* Enh: If issue is reported via using the REPORT ISSUE button, send the license key with the submitted message #2087
* Enh: Change some words in UI to be more consistent #2099
* Enh: Show link to docs article in warning if license key can not be activated #2100
* Enh: Cleanup and remove backend/views/notices/poll.php and all related code #2107
* Enh: Clean up backend/views/notices/poll.php and all related unused code #2107
* Fix: Staging site database tables can not be pushed if staging site settings contain different table prefixes #2151
* Fix: Support adding "+" sign in the name of the backup folder by FTP, the name of backup and in the name of clone #2160
* Fix: Check if WP_ENVIRONMENT_TYPE is already defined before adding it to wp-config.php. (Solve conflict with local flywheel). #2165
* Fix: Oldest multipart backups were not deleted during remote upload #2129
* Fix: Prevent fatal error on push when there is no theme installed on production site #2185
* Fix: Continue database backup restore by using shorter name for identifiers name exceeding maximum allowed characters #2114
* Fix: Add a check to prevent warning undefined variable: jobId #2196
* Fix: Better error handling if folder or file is not writeable #2190
* Fix: Handle errors for PHP 8.x during Database backup restore Task when  max_packet_size is bigger than the actual sql result #2125
* Fix: Convert utf8mb4 to utf8 when restoring database in MySQL < 5.7 to prevent MySQL Error 1071 because MySQL < 5.7 has max key length of 767 bytes. By converting utfmb4 to utf8 it reduces the size by 1/4 and allows restoring a backup. #2203
* Fix: Restore of backup blocked due to incorrect permission check #2228
* Fix: WP Staging log backup files could not be deleted #2222
* Fix: Prevents a rare edge case where scheduled backup are continuous started over by implementing a better clean up routing #2231
* Fix: Prevent fatal error on setting page due to using double semicolon(‘::’) to call static method from variable. Affects old php versions only. #2166
* Fix: Old automated local backup were not deleted and thus no new automated backup were created #2141
* Fix: Improve detection if WordPress is installed in subdirectory. If home URL and site URL are different the cloned staging site URLs are sometimes incorrect. #2068
* Fix: Ensure that functions in wp-staging-optimizer.php are declared only one time #2079
* Fix: Remove some php's deprecation warnings for php 8.x #2078
* Fix: Remove error in search & replace of serialized data due to strict types during staging PUSH on PHP 8.1 and higher version. #2065
* Fix: Remove warning "Undefined index: networkClone in single-overview.php line: 54" #2097
* Fix: Attach log files to report mail #2156
* Fix: Unable to upload backup files to more than one remote storage in single backup job #2245
* Fix: Fetch google drive backup files in ascending order by time to fix a backup retention issue for google drive #2245
* Dev: Revert all usage of isWritable and isExecutable #2232
* Dev: Broken test _04PushCest after implementing #2199 #2216
* Dev: Write the version of the plugin into the header of wpstg.js when creating the dist package with make dev_dist #2095
* Dev: Downgrade phpcs library to fix xss tests #2105
* Dev: Show upload_path in system info #2024 #2147

2.12.0
* New: Optional feature to split backup files by type (plugins, media, themes) and maximum file size. Use filter `wpstg.backup.isMultipartBackup` to activate this option. Use `wpstg.backup.maxMultipartBackupSize` filter to adjust maximum file size for split backup, default is 2GB #1804
* New: Add filter `wpstg.remoteStorages.chunkSize` (in bytes) to change chunk size of backups upload to remote storages #2047
* New: Add filter `wpstg.remoteStorages.delayBetweenRequests` (in milliseconds) to add delay between requests to upload chunks to remote storages #1997
* New: Add filter `wpstg.backup.tables.non-prefixed` to allow backup and restore of non WordPress prefixed tables #2018
* New: Add option to download backup log files via ACTIONS > Log File #2025
* New: Send mail report if unable to upload backup to remote storage(s) during automated backup #2025
* Enh: Dont show recommendation message on using the same MySQL/MariaDB version while restoring if already the same MySQL/MariaDB version #1997
* Enh: Preserve remote storages options during clone update. #2004
* Fix: Store taskQueue in jobDataDto instead of a separate file #1997
* Fix: Added upload_path in system info #2024
* Fix: BINARY and NULL were not correctly search replaced if restoring the backup on the same site #2043
* Fix: Correct database server type and MySQL/MariaDB version in System Info Tab #2043
* Fix: Exclude filters in the UI for the staging site now allow adding dot `.` for extension, file and folder exclusion rules #2053
* Fix: Issue during cleaning of other files in wp-content directory when actual uploads directory is not direct child of wp-content directory #2041
* Fix: UPLOAD path was not correctly search replaced if source and destination site had a different relative upload path #2041
* Fix: Importing a multisite backup with domains as network sites created wrong URLs for network sites if backup is restored into a multisite where network sites are subdirectories #2038
* Fix: Allow database creation during push if multisite and mainsite #2032
* Fix: Preserve scheduled backup plans during push #2032
* Fix: Stop uploading backup to remote storage(s) after failure on certain amount of retries #2025
* Fix: Dont copy google drive option during new or reset clone. This will make sure both sites will have different Refresh token. So revoking refresh token on one site doesn't break uploading process for the other #2004
* Fix: Fix php warning when null is passed as argument to trim function #2059
* Fix: Improve admin notice when JETPACK_STAGING_MODE is active on staging site #2014
* Dev: Bump minimatch from 3.0.4 to 3.0.8 in /src/assets #2007
* Dev: Make extra_hosts section in docker DRY #2070
* Dev: Split webdriver tests to speed up running and allow parallel execution of them #2057
* Dev: Improve login page authentication message #2058

2.11.0
* New: Compatible up to WordPress 6.1.1
* New: Add support for uploading backups to DigitalOcean Spaces, Wasabi and other S3 compatible storages #1966
* Enh: Allow backup upload to Amazon S3 when bucket has Lock Object and retention enabled #1973
* Enh: Show warning if test connection to backup storage provider fails during save settings #1965
* Enh: Show warning if there are more than 4 overdue backup cron jobs #1986
* Enh: Show message if unable to pre-scan directories before cloning #1993
* Fix: Could not delete oldest backup from (S)FTP cloud storage provider if FTP location was set in FTP settings #1953
* Fix: Under rare circumstances a fatal error is thrown during backup if scheduled time is NULL
* Fix: `SSL` and `Passive` checkboxes were not considered during FTP backup storage test connection #1965
* Fix: Fatal error when set_time_limit() has been disabled by the hosting provider #1977
* Fix: Preserve backup cloud storage provider settings when pushing and improve Google Drive backup authentication #1999
* Dev: Deprecated heredoc syntax for variables. Fix unit tests for php 8.2RC #1975

2.10.0
* New: Compatible up to WordPress 6.0.3
* New: Show loader icon while saving settings or testing backup remote storages connections #1925
* New: Show settings last saved time for backup remote storages SFTP, Amazon S3 and Google Drive #1925
* New: Show last update and install date for WP STAGING | PRO plugin in System Info #1928
* New: Show selected themes and plugins for UPDATE and RESET clone jobs #1926
* New: Fix issues when restoring multisites backup if network subsites have different domains. It now support restore or conversion of domain based subsite to subdirectory based subsite #1872
* New: Option to disable local storage space and upload backup(s) only to remote storage spaces #1935
* Enh: Huge improve of backup restoring performance by factor 2-3. #1951
* Enh: Huge improve of backup creating performance on slow database servers. #1951
* Enh: Add extra search & replace rule for elementor generated data #1902
* Enh: Add dropdown to select bucket region for S3 backups instead of typing it in manually. Improve Amazon S3 settings page #1943
* Enh: Skip search & replace if restoring a backup on the same site #1949
* Enh: Add extra search & replace rule for elementor generated data #1949
* Enh: Add search replace filter for database backup restore #1872
* Enh: Allow access of staging site by using user email addresses beside usernames #1928
* Enh: Add option to not ask for license key activation on local development sites #1913
* Enh: Add better log messages for non working cron jobs #1907
* Fix: Prevent a rare situation where the database is copied slowly with only one row per request #1951
* Fix: Table selection ignored when creating a new staging site #1946
* Fix: Could not properly restore network sites when a multisite backup was restored on a new WordPress that had a different table prefix than the source website #1948
* Fix: Adjusted multiple SplFileObject methods (due to unconsistent behaviour of these methods across multiple PHP versions) to support PHP 8.0 > 8.0.19, PHP 8.1 > 8.1.6 and upcoming PHP 8.2 #1903
* Fix: Deleting the oldest remote backup from SFTP, Amazon S3 and Google Drive fails sometimes #1890
* Fix: No update notification visible in wp staging user interface when there is a new pro version available #1894
* Fix: Clean up code #1871
* Fix: Unable to create backup if there are files in WP STAGING cache folder that can not be deleted like .nfs* files. #1859
* Fix: Analytics reporting does not contain the list of installed plugins #1896
* Fix: If an Amazon S3 api key contained + character, it turned into space character when saving in database #1912
* Fix: Always store the names of installed plugins, mu-plugins and themes in backup metadata #1906
* Fix: When restoring database with large amount of tables on PHP > 8.0.1 some tables doesn't get created due unconsistent behaviour of FileObject library across PHP versions. #1872
* Fix: Undefined var message in the log files if SSL connection could not be established due to outdated TLS on client server #1913
* Fix: While creating a single staging site out of a network site, the folders (uploads/sites/ID) from other network sites weren't excluded and contained in the staging site #1922
* Fix: Selecting parent folder does not automatically select its subfolders for UPDATE and RESET clone jobs. #1926
* Fix: Amazon S3 only supports a maximum of 10,000 chunks for uploading a single backup file. With previous 5MB chunk size, backup uploads to S3 failed if they are bigger than 50 GB. Now backup chunk size is adjusted according to the size of the backup file by making sure total chunks are less than 10,000 #1924
* Fix: Error when pushing a staging site and all folders are selected #1883
* Dev: Add testing suite to run unit tests against multiple PHP versions #1903
* Dev: Add sass/scss support for compiling css #1925
* Dev: Add Xdebug support for PHP 8.1, use custom php.ini in PHP 8.0 and PHP 8.1 #1928
* Dev: Update DI52 version to 3.0 for performance gain #1934

= 2.9.20 =
* Fix: Prevent internal error when clicking on Test Connection link on SFTP remote storage backup settings page. #1869
* Fix: Properly catch runtime Exception during Backup Create and Backup Restore #1833
* Fix: Connection to external database is broken if the password has special characters #1862
* Fix: Can not login to staging site if special characters are used in password due to improper sanitization #1877
* Tweak: Improve visual design of the upgrade screen
* Tweak: Better error logging if backup could not be uploaded to sftp and if path does not exist on SFTP remote server. #1869
* Dev: XDebug support for docker PHP image v.8.x #1867

= 2.9.19 =
* New: Compatible up to WordPres 6.0.2
* Security: Further improve sanitization and escaping of variables to prevent potential XSS attacks with high privileges #1836
* Enh: Show better response from remote when license can not be activated #1818
* Fix: Fatal error Uncaught TypeError on google drive backup upload settings page under rare circumstances when site is translated #1849
* Fix: Fatal error on Windows OS when pushing a staging site and activating the backup option. It deletes the WP Staging content directory including its cache files file during files copying process, resulting in a failed push #1851

= 2.9.18 =
* Fix: Does not sanitise and escape some of its backup settings, which could allow high privilege users such as admin to perform Stored Cross-Site Scripting attacks (XSS) even when the unfiltered_html capability is disallowed (for example in multisite setup) #1825

= 2.9.17 =
* New: Support up to WordPress 6.0.1
* Fix: Important update! Deselecting all tables does not lead to exclusion of tables as expected, but leads to selection of all tables. Thus all tables are copied and possibly overwritten instead of deselected. Applies to new cloning, UPDATE and RESET of a staging page. That can lead to data loss in the staging site. An update is strongly recommended! The problem appeared for the first time in version 4.2.8. #1814
* Fix: Can not upload backup file to google drive if the google api returns incorrect value for available storage size (negative value). This sometimes happens for Google workspace accounts and does not affect all users. #1799
* Fix: Plugin wps-hide-login could not be excluded during cloning process, preventing users from log in to the staging site #1812

= 2.9.16 =
* Fix: On some servers, autoloader tries to load Composer\InstalledVersions although this doesn't exist. We fix this by only loading classes that exist #1801
* Enh: Some shared hosting servers like DreamHost doesn't allow sending large data through URL which resulted in interval server error 500 when fetching the backup list. We changed the way of sending data now through request body which allow listing backups on such shared hosting servers #1788
* Enh: Improve workflow to support tables with long name exceeding the 64 characters MySQL limit when a tmp prefix during restore is added. Those tables are now temporarily renamed to temporary short names during the restore process and after successful restore renamed back to their original names #1784
* Fix: Restoring a backup, site language is not properly imported, resulting in switching the imported site back to site default language. Reason: Language files are imported before importing other files during backup restore. This led to cleaning the restored language files while cleaning other files. Now language directory is skipped during the cleaning of existing "other" files #1794
* Fix: Check/Uncheck of the plugins and themes checkbox in the PUSH UI didn't affect the children checkboxes. This issue is fixed and children checkboxes are properly toggled #1797
* Fix: If all tables were excluded during PUSH, it was treated as if all tables were selected. Now tables selection is properly handled during the PUSH #1797

= 2.9.15 =
* New: Add sFTP support to upload backup files automatically via (s)FTP to a remote server or NAS system #1677
* Enh: Cloning/Push stops if folder name contains backslash character (\) on Linux OS #1744
* Enh: Don't copy or update wp-config.php if staging site is updated by using the UPDATE button #1747
* Enh: Restoring a backup from a staging site that uses the meta tag noindex, causes the imported site to also not be indexed. In the worst case, this can result in a production site not being indexable after restoring a backup. This update ensures that the index meta value of the imported site is preserved when a backup is restored. #1777
* Enh: If Jetpack plugin is active, use the special Jetpack Staging Mode by adding the constant JETPACK_STAGING_MODE to wp-config.php of the staging site #1780
* Fix: On some servers, autoloader tries to load Composer\InstalledVersions although this doesn't exist. We fix this by only loading classes that exist #1801
* Fix: Fatal error if php curl() module is missing and backup is uploaded to Google Drive or Amazon S3 #1769
* Fix: Fatal error on cloning due to strict standard issue in DbRowsGeneratorTrait when user has E_STRICT or E_DEPRECATED constants active in PHP #1772
* Fix: Fatal error on plugin activation if there is no write permission on the backup files. Happens only on updating from a very old version to latest one and the backup metadata update routine is fired #1776
* Dev: Add automated test for scheduled backup plans #1764

= 2.9.14 =
* Fix: Certain default plugins like wps-hide are not excluded anymore during cloning #1742
* Fix: Scheduled backup not always executed #1754
* Fix: Backup folder is deleted during backup restore on Windows OS #1737
* Fix: On backup restore retry deleting an item again in next request instead of re adding it at the end of queue, if item isn't completely deleted in current request #1758
* Enh: Refactor normalizePath() #1751
* Enh: Optimize table selection to reduce POST characters. Send either selected tables or excluded tables whichever is smaller along to reduce the POST size for cloning and pushing #1727
* Enh: Allow automatic update of WP STAGING | PRO on the staging site. It can still be disabled using the filter wpstg.notices.disable.plugin-update-notice #1749
* Enh: Add filter wpstg.backup.restore.extended-search-replace. The extended search replace allow properly replacing to destination URL for some plugins like rev-sliders in backup restore #1741

= 2.9.13 =
* New: Support up to WordPress 6.0
* Fix: Don't load mbstring polyfill file at all if iconv extension isn't loaded #1734
* Enh: Increasing Backup Filescanner Performance. Lower backup directory and file scanner request time #1714
* Enh: Rename cancel button of backup schedules modal make it more responsive #1714

= 2.9.12 =
* Fix: If there is a damaged backup in backup folder, automated backup does not work any longer #1707
* Fix: Support UNC paths like //servername/path Unix or \\servername\path DOS/Windows #1698
* Fix: Remove prefixed vendor namespace from the Normalizer class in the idn-intl polyfill #1720
* Fix: Handle SSL related errors and catch other exceptions while making remote request to refresh Google token #1718
* Fix: Backup does not restore theme if theme does not have a style.css #1719
* Fix: Missing exception in Backup Extractor.php #1724
* Enh: List damaged backup files in the UI and mark them #1710
* Enh: Remove message "backup metadata not found" in debug log #1710
* Enh: Clean up debug messages #1722
* Enh: Add missing escaping of POST output #1705

= 2.9.11 =
* New: Add Amazon S3 as backup cloud storage option for backup upload #1665
* Fix: Fatal error due to missing BackupScheduler class in Free version #1688
* Fix: Can not recursive scan file system if there is a symlink in root directory linking to the root directory itself. #1688
* Fix: Can not download backup files. Incorrect backup download link if wp-content folder is symlinked to another folder outside the wp root folder #1697
* Fix: Error on downloading backup in IIS due to section in parent web.config that overwrites the WP STAGING generated web.config in backup folder #1699
* Fix: PHP Fatal error while cloning if php iconv extension is not active. Continue cloning even if punycode related code doesn't work due to missing extensions #1702
* Enh: Remove duplicated mbstring class #1702

= 2.9.10 =
* Fix: Fatal error on missing Backup Scheduler class in Free version #1688
* Fix: Fix recursive scanning if there is symlink in root directory linking to root directory itself #1688

= 2.9.9 =
* New: Support up to WordPress 5.9.3
* New: Added upgrade routine for backup created with version 4.1.9 and 4.2.0 to fix backup metadata info #1647
* New: Add multiple filters to keep existing media files, plugins, themes and mu-plugins after backup restore #1617
* New: Clean existing files during backup restore which are not in backup #1617
* Fix: Backup creation is blocked by mod_security if access tokens contain 0x string #1651
* Fix: Unable to upload backup created with version 4.1.9 and 4.2.0 using WP Staging Backup Uploader #1647
* Fix: Unable to import multisite backup when restoring backup into domain other than it was created on #1644
* Fix: If there is an mysql error on copying a single row, it can lead to a interrupt of the whole clone job due to a browser memory exhaust error because whole sql query is written into html log element . #1654
* Fix: Cloning does not work if php mb module is not installed #1669
* Fix: Catch fatal error happening on backup upgrade routine #1663
* Fix: Only process one queue action at a time. This make sure another action doesn't conflict with the action in process. Also fix the wpstg_queue backup table growing problem #1652
* Enh: Save log file name instead of complete task response in wp_wpstg_queue table. This reduces the size of backup queue table #1623
* Enh: Stop the backup job on critical errors during scheduled backup #1623
* Dev: Test for cleaning up files before backup restore fails on second run #1681

= 2.9.8 =
* Fix: Fatal error if another plugin uses the same google library as WP STAGING uses for the backup storage provider #1641

= 2.9.7 =
* New: Support up to WordPress 5.9.2
* New: Feature to upload backups to Google Drive (PRO) #1453
* New: Add filter wpstg.frontend.showLoginForm to force disable login form for the staging / backup site access #1577
* New: Option to schedule a backup without creating one (PRO) #1588
* Enh: Improve backup schedules error reporting by showing cron related notice on backups page and sending schedules error report mails (PRO) #1588
* Enh: Improve subdirectory WordPress install detection by adding support for idn(internationalized domain names) #1564
* Enh: Change backup lock process mechanism from using database to file based lock #1561
* Enh: Make files & folders exclude filters work on WordPress root directory #1606
* Enh: Remove old database only backup before PUSH process (PRO) #1608
* Enh: Exclude .htaccess from root directory only during cloning process #1616
* Enh: Don't backup table wp_wpstg_queue table (PRO Version) #1624
* Update: Bump minimist from 1.2.5 to 1.2.6 in /tests/js #1627
* Update: Bump postcss from 8.2.10 to 8.2.13 in /src/assets #1547
* Update: Bump mustache/mustache from 2.13.0 to 2.14.1 #1543
* Update: Bump nanoid from 3.1.22 to 3.3.1 in /src/assets #1626
* Fix: Correctly set multisite subsites urls with www prefix when cloning and restoring a backup (PRO) #1567
* Fix: Backup error "OutOfRangeException" was never caught when insert query exceeds max allowed packet size (PRO)  #1570
* Fix: Add backup logic to check extended query string size against max allowed packet size which otherwise leads to a fatal error (PRO) #1570
* Fix: Handle critical error if WP STAGINGS settings get corrupted. Give option to restore default settings #1602
* Fix: Recreate cron jobs of existing backup plans when adding a new backup schedules (PRO) #1588
* Fix: Enqueue a failed task/item again and set the queue's offset to the end of file #1609
* Fix: Stop cloning if destination directory/clone name already exists #1607
* Fix: Continue cloning process even if copying a table failed #1578
* Fix: Don't remove freemius options if entire multisite is cloned. Prevents a fatal error. (PRO) #1629
#1638

= 2.9.6 =
* New: Support up to WordPress 5.9.1
* New: Add filter wpstg.frontend.showLoginForm to allow third party plugin disabling login form for the staging site #1577
* New: Add labels to distinguish between network and single site clones on multisite
* Fix: Handle issue when showing staging sites in System Info #1560
* Fix: Fix Rows Generator for zero or negative values for Primary Key Index #1584
* Fix: Set option "Keep permalinks" on the staging site when updating a staging site if "keep permalinks" is active on the production site initially #1562
* Fix: Updating an existing multisite clone converted the clone to a single site #1565 #1589

= 2.9.5 =
* New: Create backups and restore of multisites (PRO) #1458
* Fix: Force AnalyticsSender to convert wpstg_settings to array before usage #1559
* Fix: Cloning backup Search & Replace does not work with new primary key conditional query #1556
* Fix: Fix labels on backup sites #1551
* Fix: Backup restore can not unserialize escaped serialized strings #1554

= 2.9.4 =
* New: Add filter to change the cache folder for creating & restoring backups #1528
* New: Huge performance improvement for search & replace in cloning / pushing / backup process #1522
* Fix: Call to undefined function human_readable_duration() on backup creation if WP is older than 5.1 #1527 #1525 #1535
* Dev: Add unit tests for Times class that is used in backup listing view
* Dev: Update db_version in SQL dumps to match WordPress 5.9 db version #1539
* Dev: Add command to get db_version from database

= 2.9.3 =
* New: Add support for WordPress 5.8.3
* New: Add filter for excluding files during cloning / backup #1494
* New: Add filter for overwriting max execution for database backup restore #1512
* New: Add filter to allow overwriting of the maximum allowed request time to make sure backup restore works for huge files. (19.000.000M database rows) #1510
* Tweak: Show custom uploads folder in tooltip description and explain better that changing a symlink image will also change the image on the production site. #1495
* Fix: If cloning a multisite subsite into external database, it does not clone / backup wp_users and wp_usermeta #1515
* Fix: Skip tmp single file plugin during backup PUSH copy process #1491
* Fix: Preserve table selection during PUSH and UPDATE even when all backup tables unselected #1488
* Fix: Make sure maximum memory consumption during cloning or backup is never higher than 256MB #1502
* Fix: Use custom implementation of wp_timezone() for backward compatibility to WordPress older than 5.3 #1505
* Fix: Override FileObject::fgets to make them behave exactly from SplFileObject of PHP < 8.0.1 #1506

= 2.9.2 =
* Hotfix: Fix CLONE PUSH BACKUP on Medium and High CPU Load on WP STAGING 2.9.1. Improve Performance of database backup #1492

= 2.9.1 =
* New: If cpu load setting is low make use of the file copy limit for pushing / backup process to increase copy speed #1485
* Enh: Add warning notice if WP_CRON_DISABLED is set to true as backup BG Processing depends upon it #1467
* Fix: Add own implementation of get_current_network_id() for backward compatibility when creating backup #1438
* Fix: Updating or resetting staging / backup site skips all WordPress core folders #1435
* Fix: Prevent 504 Gateway Timeout issue during Backup restore by force a low CPU LOAD (i.e. 10s) #1420
* Fix: Wrong directory path is displayed when update/reset a staging / backup site #1447
* Fix: Override SplFileObject::seek to make it consistent across all PHP version including PHP 8 when creating backup #1444
* Fix: Make FileObject::seek behave exactly as SplFileObject::seek from PHP < 8.0 when creating backup #1480
* Fix: Search Replace now works for Visual Composer / WP Bakery encoded pages on cloning backup creation #1442
* Fix: Adjust CSS of the "Backup in Progress" element #1466
* Fix: Clarify email sending tooltip description #1469
* Fix: Adjust CSS of the loader icon for showing backup creation #1487
* Tweak: Retain WP STAGING ( backup ) options during push #1417
* Tweak: Make PHP 5.6 minimum supported PHP version for backup #1448
* Tweak: Set WordPress 4.4 as minimum required WordPress version #1449
* Dev: Fix Queue working in PHP 8 and Add PHP 8 unit tests in backup fast tests #1450
* Dev: Cancel pending or running github actions backup fast tests if there is a new push on the same PR #1486
* Dev: Fix Queue working in PHP 8 and Add PHP 8 unit tests in fast tests #1450
* Dev: Cancel pending or running github actions fast tests if there is a new push on the same PR #1486

= 2.9.0 =
* New: Compatible up to WordPress 5.8.2
* Fix: Update notice is shown even when using latest version #1398
* Fix: Backup & cloning 100% compatible with PHP 8.0.12 #1281
* Fix: Skip search replace on backup & cloning query if it's size exceed preg functions limit #1404
* Fix: Skip inserting backup & cloning query if it exceeds mysql max_allowed_package. Show warning to user #1405
* Fix: Make db option wpstg_staging_sites always return an array #1413
* Fix: Fix dependency injection for backup notices class. Solve conflict with TranslatePress #1416
* Tweak: Use php version number as tag for php docker container #1407
* Tweak: Improve symlink tooltip text #1411
* Tweak: Refactor WP STAGING Pro to WP STAGING | PRO in notices #1409
* Tweak: Remove 16 characters limitation for the backup & CLONE NAME and keep it for CLONE DIRECTORY #1412

= 2.8.9 =
* New: Show a summary of selected tables and plugins in the backup push selection
* New: Ask user to allow sending non-personalized usage information for improving the backup & staginguser experience
* New: Adding improved and dedicated WP STAGING debug log for backup and staging.
* New: Better support for custom ( backup ) tables without a wp core table prefix. Allow cloning / backup of custom tables that do not begin with a WP table prefix to an external database #1304
* New: Now you can create a staging / backup environment for your entire multisite network #1263
* New: Add new logic for showing update notification for PRO version, compatible to staged rollout releases #1308
* New: Show warning notice about not changing wp-content or uploads dir path on staging / backup site #1313
* Tweak: Disable the notice about not messing with uploads or wp-content directories in backup site #1385
* Tweak: Lower memory consumption on backup creation #1301
* Tweak: Fix open staging / backup site button and text #1321
* Tweak: Layout of database comparison modal #1332
* Tweak: Make staging / upgrade site upgrade routine run always #1358
* Fix: Feedback modal not opened after disabling the backup plugin #1373
* Fix: Prevent cloning error by enclosing table name with backticks in CLONE / BACKUP and PUSH jobs #1388
* Fix: Duplicate primary key error that could occur on Push a backup / staging site #1322
* Fix: Dont rename themes/plugins with tmp prefix during push of staging / backup site if content cleaning option is enabled #1305
* Fix: No search & replace of wp option db_version if table prefix is db_, during CLONE / BACKUP / PUSH #1307
* Fix: Avoid upgrade error if wp option wpstg_staging_sites is empty or not an array not an array #1331
* Fix: Show an error if table can not be copied for any reason on cloning / backup process #1302
* Fix: CSS issue vertical center align finish label after push #1311
* Fix: Use WordPress local timezone when logging for clone and backups #1323
* Fix: Better support for custom plugin directories on the staging / backup site #1314
* Fix: Not all cloning / backup settings are cleaned during uninstall #1325
* Fix: Staging / backup site does not have orange admin bar after cloning #1329
* Fix: Warning if array key offset does not exist on search & replace of a backup #1334
* Fix: Disable WordFence plugin on the staging /backup site to prevent by renaming .user.ini to .user.ini.bak #1338
* Fix: Prevent empty database prefix in staging / backup site options if site is cloned to external database #1340
* Fix: List of staging / backup sites contains duplicate entries if staging sites were initially created with wp staging free 2.8.6, then upgraded to pro 4.0.3 and pro 4.0.5 #1349
* Fix: Show error and stop cloning / backup process if unable to create staging / backup site destination folder #1348
* Fix: Fix issue about checking rest url on backup / staging site #1354
* Fix: Fix exclude condition for tables during PUSH of a staging / backup site #1364
* Fix: Fix PUSH process when no table is selected #1387
* Fix: Enclose table name with backticks during quering in CLONE / BAKUP and PUSH jobs #1388

= 2.8.8 =
* New: Compatible up to WordPress 5.8.1
* Enh: Refactor the wp_login action hook to work with different parameter count than the one in WordPress Core #1223
* Enh: Sort new staging backup sites in descending order by creation time #1226
* Enh: Warn if creating a backup in PHP 32 bit version #1231
* Enh: Update the backup upload success message #1221
* Enh: Show a notice if there is a new WP STAGING free version of the backup plugin #1250
* Enh: Rename db option wpstg_existing_clones_beta to wpstg_staging_sites #1211
* Enh: Update the warning message shown when the delete process of the staging backup site fails #1257
* Enh: Allow use of REST API on staging backup sites without login #1287
* Enh: Add new EDD software licensing updater for the pro version of the WP STAGING backup plugin #1294
* Fix: New pro version does not recognize staging backup sites created with older free version #1293
* Fix: Fix a rare issue that could happen when creating a new staging backup site or restoring a backup when there is a row with primary key with value zero #1271
* Fix: Try to repair MyISAM table if it's corrupt when creating a Backup #1222
* Fix: Fix an issue on backup creation that would cause a database export to loop when encountering a table with negative integers or zeros as a primary key value #1251
* Fix: Lock specific tables while exporting a backup, to prevent a rare duplicated row issue #1245
* Fix: If the memory exhausts during a database export using the Backup feature, lower the memory usage/speed of the export and try again automatically #1230
* Fix: Prevent failure of adding database to backup from causing a loop #1231
* Fix: Fix issue when old backup clones from version 1.1.6 or lower replaces the existing clones from later version when upgrading from FREE to PRO version #1233
* Fix: Fix inconsistent Unselect All Tables button's action #1243
* Fix: Replace undefined date with proper formatted date during backups for some warning and critical messages #1244
* Fix: Split file scanning of wp-content into scanning of plugins, themes, uploads and other directories to reduce timeout issues #1247
* Fix: Rename .user.ini to .user.ini.bak after cloning to reduce fatal errors on staging backup site. Also show a notice. #1255
* Fix: Skip scanning the root directory if all other directories are unselected before starting a backup staging site #1256
* Fix: Show correct insufficient space message instead of permission error if unable to copy or create a backup site due to insufficient space #1283
* Fix: Fix showing of error when unable to count tables rows and wrap table name when fetching rows during backup #1285
* Fix: Remove custom error handler that could cause errors due to notices being thrown #1292
* Fix: Fix an error that would occur when PHP lacked permission to get the size of a directory when pushing a staging backup site to production #1295
* Dev: Set the version of Selenium containers to 3.141.59-20210713 to avoid issues with broken latest version of selenium #1234

WP STAGING Backup & Cloning | Full changelog:
[https://wp-staging.com/wp-staging-changelog](https://wp-staging.com/wp-staging-changelog)

== Upgrade Notice ==
New support for WordPress.com hosts. Multiple security enhancements. Updating is recommended!