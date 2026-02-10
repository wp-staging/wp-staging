=== WP STAGING WordPress Backup Plugin - Migration Backup Restore  ===

Author URL: https://wp-staging.com/backup-wordpress
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: WP-Staging, WPStagingBackup, ReneHermi, lucatume, lucasbustamante, alaasalama, fayyazfayzi
Donate link: https://wp-staging.com/backup-wordpress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: backup, backups, migrate, migration, wordpress backup
Requires at least: 3.6+
Tested up to: 6.9
Stable tag: 4.6.0
Requires PHP: 7.0

Backup & Backup Restore. Migration & Staging – 1-Click Enterprise Backup Plugin, 100% Unit Tested. Keep Your Backups Safe and Secure.

== Description ==

<h3>Backup, Staging, Cloning & Migration of WordPress Sites</h3>
WP STAGING is a professional all in one <strong>backup, staging, and duplicator plugin</strong>. Unit and e2e tested on an enterprise level for all version of php 7.0 - 8.4.

Instantly* create an exact backup and clone of your website, perfect for staging, development, or simply keeping your data safe. *Cloning and backup time depends on the size of your website.
Perfect for staging, backup, or development purposes.

With WP STAGING, you can easily clone your site to a subfolder or subdomain (Pro version), complete with a full database copy, ensuring a seamless transition and a reliable backup. All data stays on your server and will not be transferred to any third party!

Our powerful backup tool is designed for speed and efficiency, making it one of the fastest backup and restore plugins available for WordPress. Even this free version allows you to restore a backup of your website in minutes if anything goes wrong. Experience peace of mind with WP STAGING.

For pushing & migrating a staging site to the production site and uploading a backup to cloud providers and for more premium features, check out [WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning")

WP STAGING runs all the time-consumptive operations for database and file cloning and backup operations in the background. This tool does <strong>automatically a search & replacement</strong> of all links and paths.

**This staging and backup plugin can clone your website quickly and efficiently, even if it is running on a weak shared hosting server.**

WP STAGING can prevent your website from breaking or going offline due to installing untested plugins!

[vimeo https://vimeo.com/999447985]

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

= 4.6.0 =
* New: Introduce Remote Sync to pull data from a remote site. (Pro) #4596
* New: Compatible up to WordPress 6.9.1
* New: Add UI component system for more consistent and polished styling. #4837
* New: Add confetti celebration when creating a staging site or backup for the first time. #4803
* New: Add smooth shrink-to-dock animation when dismissing the CLI banner. #4840
* New: Redesign the license page with a modern look and updated feature descriptions. #4774
* Enh: Allow staging site creation in wp-content/wp-staging-sites when root folder is not writable. #4785
* Enh: Improve backup file validation performance by processing smaller files in memory. #4695
* Enh: Remove the "Check Directory Size" setting to simplify the settings page. #4715
* Enh: Simplify CLI integration Step 3 to use a single restore command with the --from flag. #4829
* Fix: Allow saving cloud storage settings even when the connection has not been tested yet, with a clear warning. (Pro) #4575
* Fix: Resolve CI cache conflict between Pro and Basic builds. #4868
* Fix: Resolve backup restore failure caused by empty chunk size. #4672
* Fix: License text in CLI banner was unreadable in dark mode due to incorrect text color. #4791
* Fix: Fully remove cloud storage settings on logout/revoke and clearly inform users before deletion. (Pro) #4758
* Fix: Preserve plugin settings during staging site updates, creation, and reset. #4744
* Fix: Sort backups by creation date in CLI integration Step 3. #4859
* Fix: Clean up stale CSS files during asset build process. #4853
* Fix: Correct broken footer layout on the FAQ card. #4831
* UX: Fix typo in the staging site update warning dialog. #4792
* UX: Improve backup page loading speed with parallel requests and better error handling. #4817
* UX: Improve loading states with skeleton placeholders, less blank space on the backup page, and shorter loading bar timeout. #4815
* UX: Redesign the System Info page with a cleaner layout and improved usability. #4746
* Dev: Add PHPCS rule to detect accidental double dollar signs ($$) as potential typos. #4645
* Dev: Add critical warning in CLAUDE.md to never push directly to master. #4857
* Dev: Add Tailwind CSS to streamline UI development. #4775
* Dev: Auto-copy built CSS/JS assets to dist folders after running make assets. #4847
* Dev: Consolidate duplicate license-checking logic in CLI integration notice. #4844
* Dev: Fix Playwright UI command (make e2e_playwright_ui) not working on macOS. #4760
* Dev: Fix Playwright UI command (make e2e_playwright_ui) not working on macOS. #4765
* Dev: Fix incremental asset builds skipping rebuild after a failed build. #4851
* Dev: Fix missing sanitizeArrayString call and resolve "Staging page container not found" error on non-staging pages. #4809
* Dev: Improve sanitizeArrayString to handle multidimensional arrays recursively. #4802
* Dev: Fix missing semicolons in nginx Docker config files. #4757
* Dev: Prepare PUSH for refactoring. #4701
* Dev: Remove flaky email notification test in backup Playwright suite. #4750
* Dev: Update Dropbox Refresh Token. #4793
* Dev: Update ESLint configuration to automatically fix linting issues. #4731
* Dev: Update ESLint config for improved JavaScript formatting. #4708
* Dev: Improve code readability by using positive variable names. #3675

WP STAGING Backup & Cloning | Full changelog:
[https://wp-staging.com/wp-staging-changelog](https://wp-staging.com/wp-staging-changelog)

== Upgrade Notice ==
Compatible up to WordPress 6.9.1.
Many improvements for reliability and bug fixes. Please update to the latest version!
