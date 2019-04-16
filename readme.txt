=== WP Staging - DB & File Duplicator & Migration  === 

Author URL: https://wordpress.org/plugins/wp-staging
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: ReneHermi, WP-Staging
Donate link: https://wordpress.org/plugins/wp-staging
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: staging, duplication, cloning, clone, migration, sandbox, test site, testing, backup, post, admin, administration, duplicate posts
Requires at least: 3.6+
Tested up to: 5.1
Stable tag: {{version}}
Requires PHP: 5.3

A duplicator plugin! Clone, duplicate and migrate live sites to independent staging and development sites that are available only to administrators.

== Description == 

<strong>This cloning and staging plugin is well tested and work in progress. <br><br>
If you find any issue, please open a [support ticket](https://wp-staging.com/support/ "support ticket").
</strong>
<br /><br />
<strong>Note: </strong> For pushing & migrating plugins and theme files to live site, check out the pro version [https://wp-staging.com/](https://wp-staging.com/ "WP Staging Pro")
<br /><br />
<blockquote>
<h4> WP Staging for WordPress Migration </h4>
This duplicator plugin allows you to create an staging or development environment in seconds* <br /> <br />
It creates a clone of your website into a subfolder of your main WordPress installation including an entire copy of your database. 
This sounds pretty simple and yes it is! All the hard time-consumptive database and file copying stuff including url replacements is done in the background.
 <br /> <br />
I created this plugin because all other solutions are way too complex, overloaded with dozens of options or having server requirements which are not available on most shared hosting solutions.
All these reasons prevent user from testing new plugins and updates first before installing them on their live website, so its time to release a plugin which has the potential to be merged into everyone´s wordpress workflow.
 <br /><br />
<p><small><em>* Time of creation depends on size of your database and file size</em></small></p>
</blockquote>

WP Staging helps you to prevent your website from being broken or unavailable because of installing untested plugin updates! 
<p>Note: WordPress 5.0 will be shipped with a new visual editor called Gutenberg. Use WP Staging to check if Gutenberg editor is working as intended on your website and that all used plugins are compatible with that new editor.</p>


[youtube https://www.youtube.com/watch?v=Ye3fC6cdB3A]

= Main Features =

* <strong>Easy: </strong> Staging migration applicable for everyone. No configuration needed!
* <strong>Fast: </strong> Migration process lasts only a few seconds or minutes, depending on the site's size and server I/O power
* <strong>Safe: </strong> Access to staging site is granted for administrators only.
<br /><br />
<strong>More safe:</strong> 
<br>
* Admin bar reflects that you are working on a staging site
* Extensive logging if duplication or  migration process should fail.
* New: Compatible to All In One WP Security & Firewall

= What does not work or is not tested when running wordpress migration? =

* Wordpress migration of wordpress multisites (not tested)
* WordPress duplicating process on windows server (not tested but will probably work) 
Edit: Duplication on windows server seems to be working well: [Read more](https://wordpress.org/support/topic/wont-copy-files?replies=5 "Read more") 


<strong>Change your workflow of updating themes and plugins data:</strong>

1. Use WP Staging for migration of a production website to a clone site for staging purposes
2. Customize theme, configuration and plugins or install new plugins
3. Test everything on your staging site first
4. Everything running as expected? You are on the save side for migration of all these modifications to your production site!


<h3> Why should i use a staging website? </h3>

Plugin updates and theme customizations should be tested on a staging platform first. Its recommended to have the staging platform on the same server where the production website is located.
When you run a plugin update or plan to install a new one, it is a necessary task to check first the modifications on a clone of your production website.
This makes sure that any modifications is  working on your website without throwing unexpected errors or preventing your site from loading. (Better known as the wordpress blank page error)

Testing a plugin update before installing it in live environment isn´t done very often by most user because existing staging solutions are too complex and need a lot of time to create a 
up-to-date copy of your website.

Some people are also afraid of installing plugins updates because they follow the rule "never touch a running system" with having in mind that untested updates are increasing the risk of breaking their site.
I totally understand this and i am guilty as well here, but unfortunately this leads to one of the main reasons why WordPress installations are often outdated, not updated at all and unsecure due to this non-update behavior.

<strong> I think its time to change this, so i created "WP Staging" for WordPress migration of staging sites</strong>

<h3> Can´t I just use my local wordpress development copy for testing like xampp / lampp? </h3>

Nope! If your local hardware and software environment is not a 100% exact clone of your production server there is NO guarantee that every aspect 
of your local copy is working on your live website exactely as you would expect it. 
There are some obvious things like differences in the config of php and the server you are running but even such non obvious settings like the amount of ram or the 
the cpu performance can lead to unexpected results on your production website. 
There are dozens of other possible cause of failure which can not be handled well when you are testing your changes on a local staging platform.

This is were WP Staging steps in... Site cloning and staging site creation simplified!

<h3>I just want to migrate the database from one installation to another</h3>
If you want to migrate your local database to a already existing production site you can use a tool like WP Migrate DB.
WP Staging is only for creating a staging site with latest data from your production site. So it goes the opposite way of WP Migrate DB.
Both tools are excellent cooperating eachother.

<h3>What are the benefits compared to a plugin like Duplicator?</h3>
At first, i love the [Duplicator plugin](https://wordpress.org/plugins/duplicator/ "Duplicator plugin"). Duplicator is a great tool for migrating from development site to production one or from production site to development one. 
The downside is that Duplicator needs adjustments, manually interventions and prerequirements for this. Duplicator also needs some skills to be able to create a development / staging site, where WP Staging does not need more than a click from you.
However, Duplicator is best placed to be a tool for first-time creation of your production site. This is something where it is very handy and powerful.

So, if you have created a local or webhosted development site and you need to migrate this site the first time to your production domain than you are doing nothing wrong with using
the Duplicator plugin! If you need all you latest production data like posts, updated plugins, theme data and styles in a testing environment than i recommend to use WP Staging instead!

= I need you feedback =
This plugin has been done in hundreds of hours to work on even the smallest shared webhosting package but i am limited in testing this only on a handful of different server so i need your help:
Please open a [support request](https://wordpress.org/support/plugin/wp-staging/ "support request") and describe your problem exactely. In wp-content/wp-staging/logs you find extended logfiles. Have a look at them and let me know the error-thrown lines.


= Important =

Permalinks are disabled on the staging site because the staging site is cloned into a subfolder and permalinks are not working on all systems
without doing changes to the .htaccess (Apache server) or nginx.conf (Nginx Server).
[Read here](https://wp-staging.com/docs/activate-permalinks-staging-site/ "activate permalinks on staging site") how to activate permalinks on the staging site.

 
= How to install and setup? =
Install it via the admin dashboard and to 'Plugins', click 'Add New' and search the plugins for 'Staging'. Install the plugin with 'Install Now'.
After installation goto the settings page 'Staging' and do your adjustments there.


== Frequently Asked Questions ==

* I can not login to the staging site
If you are using a security plugin like All In One WP Security & Firewall you need to install latest version of WP Staging. 
Go to WP Staging > Settings and add the slug to the custom login page which you set up in All In One WP Security & Firewall plugin.



== Official Site ==
https://wp-staging.com

== Installation ==
1. Download the file "wp-staging.zip":
2. Upload and install it via the WordPress plugin backend wp-admin > plugins > add new > uploads
3. Activate the plugin through the 'Plugins' menu in WordPress.

== Screenshots ==

1. Step 1. Create new WordPress staging site
2. Step 2. Scanning your website for files and database tables
3. Step 3. Wordpress Staging site creation in progress
4. Finish!

== Changelog ==

= 2.5.4 =
* Fix: WordFence firewall rule 'local file inclusion' blocks wp staging initial starting cloning sequence
* Fix: Values of form Extra directories to copy are ignored

= 2.5.3 =
* Fix: Disable the WP-Spamshield Plugin on staging site because it prevents login to staging site
* Fix: Lower the memory consumption when cloning scan process is started
* New: Show PHP user in system info log

= 2.5.2 =
* New: Tested up to WordPress 5.1.1

= 2.5.1 =
* Fix: WP Staging > Tools > System Info does not open 
* New: Open system info log file directly via wp staging > tools

= 2.5.0 =
* Fix: Do not exclude db.php if it is not located in the wp-content folder
* Fix: Select All button for db tables not working properly
* New: Add Filter to exclude certain tables from search & replace operation
* New: Show PHP user in system info
* New: Check if there is already one process running before executing the cloning process
* New: Support up to WordPress 5.1



= 2.4.9 =
* New: Add new db table selection manager
* Fix: Better warning notices before updating a staging site
* Fix: Stop delete process if staging site has been deleted manually before
* Fix: Log file folder does not have correct permission 0755
* Fix: Continue cloning if siteurl & home in wp_options could not be changed
* Tweak: Better warning for update method
* Tweak: DB tables and file verification opened as default option
* Tweak: Skip rows larger than 5MB for search & replace operations to keep memory consumption low
* Tweak: clean up search & replace method
* Tweak: Better FAQ



= 2.4.8 =
* Fix: Prevent throwing error when table prefix of table usermeta can not be changed
* Fix: WP Staging does not run with old WordPress version 3.2
* Fix: Do not show rating box on posts and pages editor
* New: Support WordPress 5.0.3
* New: Add FAQ to footer

= 2.4.7 =
* New: Support WordPress 5.0.2

= 2.4.6 =
* Fix: Can not login to staging site . Changed minimum user capability to 'manage_options' instead 'administrator' 

= 2.4.5 =
* New: Compatible up to WordPress 5.0.1 Gutenberg
* New: Show WP version of staging site in the sysinfo log
* Fix: Make sure optimizer must-use plugin is updated as well after updating the main plugin
* Fix: Plugin can not be uninstalled if WP Staging Pro is activated
* Fix: Prevent error $this not in object context in install.php


= 2.4.4 =
* Fix: Security, prevent downloading wp staging log files by third party users from uploads folder
* New: Compatible up to WordPress 5.0 Gutenberg

= 2.4.3 =
* Fix: Updating staging site does not exclude Windows IIS configuration file web.config and can lead to server error
* Fix: Redirect to the correct url after log in to staging site
* Fix: Supports HTML entities in links created by WP Backery Page Builder Plugin
* New: Support for custom and non-default wp-content and uploads folder
* New: Option to show again the rating notice in a week

= 2.4.2 =
* Fix: Error 500 on some systems when files are collected and plugin iterates through the directories

= 2.4.1 =
* Fix: Error notice if user tries to update staging site located in another database
* Fix: Search & Replace adds staging site subfolder multiple times to replaced string when staging site is updated
* Fix: Missing ajax delay setting

= 2.4.0 =
* New: Compatible to WP 5.0.0 Gutenberg
* New: Increase file scanning process performance
* New: Preview for external database cloning
* New: Add delay between requests setting to prevent timeouts on rate limited servers
* New: Try again automatically cloning process if ajax request has been killed due to server ressource limit error

* Fix: Error when removing heartbeat api
* Fix: remove ? parameter from staging site
* Fix: Do not load theme while WP Staging is running. Prevents processing interruption if there are fatal errors in the theme
* Fix: When cloning has been canceled page needs to be reloaded before beeing able to clone again
* Fix: Under rare circumstances plugins are disabled when wp staging runs.
* Fix: Prevent error 503 (firewall/performance timeout) by adding post parameter to the ajax url
* Fix: Adding automatic resume function to the ajax processing to prevent cloning and pushing interruptions due to hitting server ressource or network glitches.
* Fix: Selected folders are not excluded under Windows IIS server
* Fix: Windows IIS server compatibilility issues resolved


Complete changelog: [https://wp-staging.com/wp-staging-changelog](https://wp-staging.com/wp-staging-changelog)

== Upgrade Notice ==

= 2.5.4 =
* Fix: WordFence firewall rule 'local file inclusion' blocks wp staging initial starting cloning sequence
* Fix: Values of form Extra directories to copy are ignored


