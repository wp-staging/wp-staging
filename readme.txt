=== WP STAGING - WordPress Backup, Restore & Migration ===

Contributors: WP-Staging, WPStagingBackup, ReneHermi, lucatume, lucasbustamante, alaasalama, fayyazfayzi
Donate link: https://wp-staging.com/backup-wordpress
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: backup, restore, migration, staging, wordpress backup, cloning
Requires at least: 3.6
Tested up to: 7.0
Stable tag: 4.7.0
Requires PHP: 7.0

Backup, restore, staging, and migration for WordPress. Create full-site backups and test updates safely.

== Description ==

<h3>Backup, Restore, Staging, Cloning & Migration for WordPress</h3>

WP STAGING is an all-in-one backup, restore, staging, and migration plugin for WordPress, built for professional workflows with 100% unit-tested code coverage, thousands of automated tests, and extensive end-to-end testing across supported PHP versions.

Create a full backup or an exact clone of your website in minutes. Use it to test plugin and theme updates safely, restore your site when needed, migrate WordPress to another server, or build a staging copy before making changes.

WP STAGING is developed in Germany and designed for agencies, developers, and businesses that need reliable WordPress backup, staging, restore, and migration workflows.

[WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") also includes advanced workflows such as [Remote Sync](https://wp-staging.com/docs/pull-a-wordpress-site-from-one-server-to-another/ "Remote Sync - Pull a WordPress Site from One Server to Another"), which lets you pull a WordPress site securely from one server to another using an API key, and [WP STAGING CLI](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Local Docker Development for WordPress"), which can turn a WP STAGING backup into a local Docker-based development site.

All data stays on your server unless you choose a transfer or remote storage workflow. WP STAGING is designed for speed, reliability, and low-resource environments, including shared hosting.

WP STAGING automatically performs search and replace for links and paths during cloning, backup, restore, and migration workflows.

**This staging and backup plugin can clone your website quickly and efficiently, even if it is running on a weak shared hosting server.**

[vimeo https://vimeo.com/999447985]

== Frequently Asked Questions ==

= Why should I use a staging site and backup workflow? =

Plugin updates, theme changes, and custom code should be tested before they reach your live site. A staging workflow lets you clone your production website, test changes safely, and keep a working backup ready in case something goes wrong.

Usually, it is best to run the staging site on an environment as close as possible to the production server. That is the best way to catch compatibility issues before they affect your live site.

WP STAGING combines backup, restore, staging, and migration in one workflow, so you can protect your live website, reduce downtime risk, and ship changes with more confidence.

= Is WP STAGING a backup plugin? =

Yes. WP STAGING started as a staging plugin and evolved into a full backup, restore, staging, and migration solution for WordPress.

Even the free version lets you create backups and restore them when needed. [WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") adds more advanced backup workflows, cloud storage destinations, migration tools, and developer-focused features.

= How is WP STAGING different from other backup plugins? =

WP STAGING combines backup, restore, staging, cloning, and migration in one workflow. While many backup plugins focus mainly on archive-based backups or simple migration, WP STAGING also helps you create a working staging copy, test updates safely, and restore your site when needed.

Some backup plugins focus mainly on creating backup archives, while WP STAGING also creates working staging copies for safer testing and rollback workflows. This is especially useful when you want production-like validation before pushing changes live.

Some backup plugins may not fully support custom tables in all scenarios. WP STAGING is designed to work reliably with staging workflows and custom table prefixes used by its own cloned environments.

[WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") also includes advanced workflows such as [Remote Sync](https://wp-staging.com/docs/pull-a-wordpress-site-from-one-server-to-another/ "Remote Sync - Pull a WordPress Site from One Server to Another") and [WP STAGING CLI](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Local Docker Development for WordPress"), which can turn a backup into a local Docker-based development site. That makes WP STAGING especially attractive for developers, agencies, and site owners who want more than a basic backup plugin.

= How do I back up and restore a WordPress site? =

After installing WP STAGING, go to the backup section in the plugin and create a full-site backup. You can then restore that backup if a plugin update, theme change, deployment, or unexpected issue breaks your site.

WP STAGING is designed to make backup and restore simple, even on shared hosting and large WordPress installations.

= What is Remote Sync in WP STAGING Pro? =

Remote Sync is a Pro feature that lets you pull a WordPress site securely from one server to another using an API key. Instead of manually exporting databases and copying files, you connect the two sites and start the sync from inside WP STAGING.

This is especially useful for agencies, developers, and site owners who want a faster and more reliable workflow for moving content between WordPress installs.

Learn more:
[Remote Sync: Pull a WordPress Site from One Server to Another](https://wp-staging.com/docs/pull-a-wordpress-site-from-one-server-to-another/ "Remote Sync - Pull a WordPress Site from One Server to Another")

= How can I turn a backup into a local Docker development site? =

[WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") includes access to [WP STAGING CLI](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Local Docker Development for WordPress"), which can turn a WP STAGING backup into a local Docker-based WordPress site with one command.

This is ideal for debugging, QA, development, and reproducing client issues locally. It helps you create repeatable local environments without building custom Docker setups for every project.

Learn more:
[WP STAGING CLI – Upgrade Now](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Upgrade Now")

= How do I migrate WordPress to another host or server? =

[WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") includes migration workflows that help you move a WordPress website to another host, domain, or server.

If you want a guided step-by-step walkthrough, see:
[How to Migrate Your WordPress Site to a New Host](https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/ "How to Migrate Your WordPress Site to a New Host")

= Why do I need a backup plugin at all? =

Consistent website backups are the foundation of a robust disaster recovery strategy. They protect your website against failed updates, user mistakes, malware cleanup, hosting issues, hardware failures, software malfunctions, and data loss.

Backups should include website files, databases, user data, and configuration data. A combination of full backups and incremental backups can improve storage efficiency while keeping restore points current.

If your website generates leads, sales, traffic, or customer trust, regular backups are not optional. A reliable backup and restore workflow can save hours of downtime and expensive recovery work.

= Can I activate permalinks on the staging site? =

Permalinks are disabled on the staging site after the first cloning process.

Read this guide to activate permalinks on your staging site:
[Activate Permalinks on the Staging Site](https://wp-staging.com/docs/activate-permalinks-staging-site/ "Activate Permalinks on the Staging Site")

= I cannot log in to the staging or backup site =

If you use a security plugin such as Wordfence, iThemes Security, All In One WP Security & Firewall, or a plugin that hides the default WordPress login URL, make sure you are running the latest version of WP STAGING.

If you still cannot log in, go to WP STAGING > Settings and disable WP STAGING extra authentication. Your admin dashboard will still remain protected.

= Can I just use my local WordPress development system for testing and backup? =

You can always test your website locally, but if your local hardware and software environment is not an exact clone of your production server, there is no guarantee that every aspect of your local copy will behave the same way.

Differences in PHP version, server stack, memory, CPU performance, and filesystem behavior can all lead to unexpected results on production. That is why staging on infrastructure close to production remains valuable.

[WP STAGING | PRO](https://wp-staging.com/backup-wordpress "WP STAGING - Backup & Cloning") also gives you a more advanced local workflow through [WP STAGING CLI](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Local Docker Development for WordPress"), which can turn a backup into a local Docker-based development site.

= Is WP STAGING available in multiple languages? =

Yes. WP STAGING is available in multiple languages, and several translations are already complete or nearly complete.

You can view translated plugin pages here:

[English](https://wordpress.org/plugins/wp-staging/ "WP STAGING on WordPress.org")
[French](https://fr.wordpress.org/plugins/wp-staging/ "WP STAGING en Français")
[German](https://de.wordpress.org/plugins/wp-staging/ "WP STAGING auf Deutsch")
[Spanish](https://es.wordpress.org/plugins/wp-staging/ "WP STAGING en Español")
[Croatian](https://hr.wordpress.org/plugins/wp-staging/ "WP STAGING na hrvatskom")
[Dutch](https://nl.wordpress.org/plugins/wp-staging/ "WP STAGING in het Nederlands")
[Finnish](https://fi.wordpress.org/plugins/wp-staging/ "WP STAGING suomeksi")
[Greek](https://el.wordpress.org/plugins/wp-staging/ "WP STAGING στα Ελληνικά")
[Hungarian](https://hu.wordpress.org/plugins/wp-staging/ "WP STAGING magyarul")
[Indonesian](https://id.wordpress.org/plugins/wp-staging/ "WP STAGING dalam Bahasa Indonesia")
[Italian](https://it.wordpress.org/plugins/wp-staging/ "WP STAGING in Italiano")
[Persian](https://fa.wordpress.org/plugins/wp-staging/ "WP STAGING به فارسی")
[Polish](https://pl.wordpress.org/plugins/wp-staging/ "WP STAGING po polsku")
[Portuguese (Brazil)](https://br.wordpress.org/plugins/wp-staging/ "WP STAGING em Português do Brasil")
[Russian](https://ru.wordpress.org/plugins/wp-staging/ "WP STAGING по-русски")
[Turkish](https://tr.wordpress.org/plugins/wp-staging/ "WP STAGING Türkçe")
[Vietnamese](https://vi.wordpress.org/plugins/wp-staging/ "WP STAGING bằng Tiếng Việt")

If you want to help improve translations, please get in touch with us through the support forum.

= Can I give feedback for WP STAGING? =

Yes. If something does not work as expected, please open a support request and describe the issue in as much detail as possible.

We continuously improve WP STAGING based on user feedback, real-world hosting environments, and developer use cases.

Open support:
[WP STAGING Support Forum](https://wordpress.org/support/plugin/wp-staging/ "WP STAGING Support Forum")

== WP STAGING FREE - BACKUP & STAGING FEATURES ==

* Clone the entire production site into a subdirectory like example.com/staging-site.
* High-performance backup and cloning, even for websites with very large databases.
* Backup scheduling with automatic daily backups.
* Easy to use: create a clone or backup in one click.
* Efficient background processing without slowing down your website.
* No Software as a Service and no external account required.
* All your data stays on your server. Your data belongs to you only.
* No server timeouts on huge websites or weak servers.
* Fast backup, clone, and restore workflows depending on site size and server resources.
* Use the clone as part of your backup and update strategy.
* Only administrators can access the cloned or backup website.
* SEO-friendly staging sites with login protection and no-index handling.
* The admin bar on the staging / backup website is orange colored and shows when you work on the staging site.
* Extensive logging features.
* Supports Apache, Nginx, Microsoft IIS, and LiteSpeed Server.
* Every release passes extensive automated tests to keep the plugin robust, reliable, and fast.
* Fast and professional support team.

== WP STAGING | PRO - BACKUP & STAGING FEATURES ==

The features below are available in [WP STAGING | PRO](https://wp-staging.com/backup-pro-features "WP STAGING | PRO Features").

* Remote Sync - Pull a WordPress site securely from one server to another.
* WP STAGING CLI - Turn a backup into a local Docker-based development site.
* Migrate and transfer WordPress to another host or domain.
* Push a staging website including plugins, themes, and media files to production with one click.
* Clone a backup or staging site to a separate database.
* Choose a custom directory for a backup or cloned site.
* Select a custom subdomain destination like dev.example.com.
* Define user roles for accessing the clone or backup site. This can be clients or external developers.
* Multisite support for migration, backup, and cloning.
* Schedule recurring backups by time and interval.
* Download and upload backups to another server for migration and transfer.
* Backup retention settings.
* Custom backup names.
* Email notifications if a backup cannot be created.
* Backup for WordPress multisites.
* Backup to Google Drive.
* Backup to Amazon S3.
* Backup to (S)FTP.
* Backup to Dropbox.
* Custom backup folder destinations for cloud storage providers.
* Priority support.

== DOCUMENTATION ==

<strong>How to Backup and Restore WordPress</strong>
[Backup and Restore WordPress](https://wp-staging.com/docs/how-to-backup-and-restore-your-wordpress-website/ "Backup and Restore WordPress")

<strong>Backup & Transfer WordPress Site to Another Host</strong>
[How to Migrate Your WordPress Site to a New Host](https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/ "How to Migrate Your WordPress Site to a New Host")

<strong>Remote Sync</strong>
[Pull a WordPress Site from One Server to Another](https://wp-staging.com/docs/pull-a-wordpress-site-from-one-server-to-another/ "Remote Sync - Pull a WordPress Site from One Server to Another")

<strong>Local Docker Development with WP STAGING CLI</strong>
[WP STAGING CLI – Upgrade Now](https://wp-staging.com/cli/upgrade "WP STAGING CLI - Local Docker Development for WordPress")

<strong>All Backup Guides</strong>
[All Backup Guides](https://wp-staging.com/docs/category/backup-restore/ "All Backup Guides")

<strong>Working with Staging Sites</strong>
[Working with Staging Sites](https://wp-staging.com/docs/category/working-with-wp-staging/ "Working with Staging Sites")

<strong>FAQ for Backup & Cloning</strong>
[FAQ for Backup & Cloning](https://wp-staging.com/docs/category/frequently-asked-questions/ "Backup & Cloning FAQ")

<strong>Troubleshooting Backup & Cloning</strong>
[Troubleshooting Backup & Cloning](https://wp-staging.com/docs/category/troubleshooting/ "Troubleshooting Backup & Cloning")

== WP STAGING BACKUP & CLONING TECHNICAL REQUIREMENTS & INFORMATION ==

* Works on latest version of WordPress
* Minimum Supported WordPress Version 3.8
* Cloning and Backup work on all webhosts
* No extra libraries required
* Backup & cloning supports huge websites
* Custom backup format is much faster and smaller than any tar or zip compression
* Backup & cloning works in low memory & shared hosting environments

== SUPPORT ==

[WP STAGING Backup & Cloning](https://wp-staging.com/backup-wordpress "WP STAGING Backup & Cloning")

== Installation ==

= Installation via admin plugin search =

1. Go to Plugins > Add new. Select "Author" from the dropdown near search input.
2. Search for "WP STAGING".
3. Find "WP STAGING - WordPress Backup, Restore & Migration" and click the "Install Now" button.
4. Activate the plugin.
5. The plugin should be shown below settings menu.

= Admin Installer via zip =

1. Visit the Add New plugin screen and click the "Upload Plugin" button.
2. Click the "Browse..." button and select the zip file of our plugin.
3. Click "Install Now" button.
4. Once uploading is done, activate WP STAGING - WordPress Backup, Restore & Migration.
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

= 4.7.0 =
* New: Browse and inspect the contents of any backup — view included files, folders, and database tables. In Pro, extract specific items from a backup to another folder without restoring the entire website. #2523
* New: Add support for sites protected with HTTP Basic Authentication. Scheduled backups and external crons now work on password-protected sites. Remote Sync can also connect to remote sites with HTTP auth protection. (Pro) #4884
* New: Automatically create missing directories on the remote server during SFTP/FTP backup uploads. Previously, the destination path had to exist on the server beforehand. (Pro) #4654
* Enh: Add Test Connection button for HTTP Basic Auth settings to verify credentials before saving. #4939
* Enh: Add option to change the database table prefix when extracting a backup with the WP Staging Restore Tool, allowing SQL imports into any database. (Pro) #4677
* Enh: Move CLI banner outside AJAX container on backup page. #4819
* Enh: Replace alarming WordPress version warning banner with a calm, neutral compatibility status notice inside the plugin UI. #4917
* Enh: Show CLI integration banner in free version as upsell for Docker environments feature. #4910
* Enh: Simplify Pro activation banner with one-click AJAX activation and standardize border-radius across all notices and banners. #4949
* Enh: Add validation to prevent staging prefix conflicts. Blocks using wpstg_ as a staging-site table prefix. Prefixes like wpstg0_, wpstg1_, etc. are still allowed. (Pro) #4799
* Fix: Allow deletion of orphaned staging sites that no longer exist in the file system but are still listed in the WP STAGING interface. #4610
* Fix: Drop-in files like object-cache.php and advanced-cache.php are now properly removed during push when “uninstall plugins and themes” is selected. Previously, an incorrect file path could leave these files behind, potentially causing fatal errors. (Pro) #4799
* Fix: WP STAGING options no longer get corrupted during Push. A prefix-replacement bug could accidentally rewrite wpstg_* option names to wp_*, breaking configuration and stored staging data. (Pro) #4799
* Fix: During full network restore, subsites’ URLs are not replaced in the options table. #4752
* Fix: Ensure WP STAGING admin notices display correctly in dark mode on WordPress 7.0. #4929
* Fix: Fixed an error that could cause the Backup page to crash. #4906
* Fix: Fixed an error that could prevent the Backup page from loading. #4901
* Fix: Prefix not replaced inside serialized option and meta values during database normalization. (Pro) #4676
* Fix: Prevent WP STAGING admin notices from appearing on third-party plugin pages with similar slug prefixes. #4823
* Fix: Prevent backup modal from jumping when expanding collapsible sections beyond viewport height. #4787
* Fix: Creating a temporary login link no longer invalidates the creator’s own user session. (Pro) #4806
* Fix: Remote Sync pull gets stuck at “Initializing” when the SSE connection is not established due to a race condition in job detection. (Pro) #4895
* Fix: Resolve false-positive backup validation error caused by skipped files not being counted in the total. #4933
* Fix: Restore the General Settings UI by removing duplicate CSS that overrode page styling. #4926
* Fix: Show detailed log entries when filtering logs by “all” or “info” during backup and staging operations. #4876
* UX: Add Close button on success modal of remote storage backup delete option. #4772
* UX: Improve sticky sidebar position on System Info page to stay at the top while scrolling. #4903
* Dev: Add support for skip-tests label in CI workflows to skip test execution. #4900
* Dev: Fix playwright tests failing for thirdparty site structure. #4890
* Dev: Integrate SolidJS in our dev environment to build reactive components. #4798
* Dev: Optimize make dev_dist build time and skip unit tests on macOS. #4879

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