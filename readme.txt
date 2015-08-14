=== WP Staging - DB & File WordPress Migration === 

Author URL: https://wordpress.org/plugins/wp-staging
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: ReneHermi, WP-Staging
Donate link: https://wordpress.org/plugins/wp-staging
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: staging, migration, development, migrate, cloning, clone, admin, page, content, plugin, media, backup, test, testing, sandbox, widget, post, plugin, admin, posts, sidebar, shortcode, google
Requires at least: 3.6+
Tested up to: 4.2.4
Stable tag: 0.9.4

Clone, duplicate and migrate live sites to independent staging and development sites that are available only to administrators.

== Description == 

<strong>This software is brandnew, well tested but still beta and work in progress! <br>
If you find a bug please open a ticket in the [support request](https://wordpress.org/support/plugin/wp-staging/ "support forum"). Every issue will be fixed asap!
</strong>

<blockquote>
<h4> WP Staging for WordPress Migration </h4>
This plugin allows you to create an staging or development environment in seconds* <br /> <br />
It creates a clone of your website into a subfolder of your current WordPress installation with an entire copy of your database. 
This sounds pretty simple and yes it is! All the hard time consumptive database and file copy stuff including url replacements is done in the background.
 <br /> <br />
I created this plugin because all other solutions are way too complex, overloaded with dozens of options or having server requirements which are not available on most shared hosting solutions.
All these reasons prevent user from testing new plugins and updates first before installing them on their live website, so its time to release a plugin which has the potential to be merged into everyone´s wordpress workflow.
 <br /> <br />
<p><small><em>* Time of creation depends on size of your database and file size</em></small></p>
</blockquote>

WP Staging can prevent your website from being broken or unavailable because of installing untested plugin updates! 

= Main Features =

* <strong>Easy: </strong> Staging migration possible for everyone. No configuration needed!
* <strong>Fast: </strong> Migration takes only a few seconds or minutes, depending on the size.
* <strong>Search Engine Friendly: </strong> Access to the staging site will be granted only for administrators
<br /><br />
<strong>Safe</strong> 
<br>
* Admin bar reflects that you are working on a staging site
* No access to staging site for search engines and other user
* Extensive logging if migration goes wrong.

= What does not work or is not tested when running wordpress migration? =

* Wordpress migration of wordpress multisites (not tested)
* WordPress migration process on windows server (not tested but will probably work) 
Edit: Migration on windows server seems to be working well: [Read more](https://wordpress.org/support/topic/wont-copy-files?replies=5 "Read more") 


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

<h3> Can´t i just use my local wordpress development copy for testing like xampp / lampp? </h3>

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


= I need you feedback =
This plugin has been done in hundreds of hours to work on even the smallest shared webhosting package but i am limited in testing this only on a handful of different server so i need your help:
Please open a [support request](https://wordpress.org/support/plugin/wp-staging/ "support request") and describe your problem exactely. In wp-content/wp-staging/logs you find extended logfiles. Have a look at them and let me know the error-thrown lines.


= Important =

Per default the staging site will have permalinks disabled because the staging site will be cloned into a subfolder and regular permalinks are not working 
without doing changes to your .htaccess or nginx.conf.
In the majority of cases this is abolutely fine for a staging platform and you still will be able to test new plugins and do some theme changes on your staging platform. 
If you need the same permalink stucture on your staging platform as you have in your prodcution website you have to create a custom .htaccess for apache webserver 
or to adjust your nginx.conf.

 
= How to install and setup? =
Install it via the admin dashboard and to 'Plugins', click 'Add New' and search the plugins for 'Staging'. Install the plugin with 'Install Now'.
After installation goto the settings page 'Staging' and do your adjustments there.


== Frequently Asked Questions ==


== Official Site ==


== Installation ==
1. Download the file "wp-staging" , unzip and place it in your wp-content/plugins/wp-staging folder. You can alternatively upload and install it via the WordPress plugin backend.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Start Plugins->Staging

== Screenshots ==

1. Step 1. Create new staging site
2. Step 2. Scanning your website for files and database tables
3. Step 3. Staging site creation in progress
4. Finish!


== Changelog ==

= 0.9.4 =
* Fix: Large files are copied partly
* New: Option for cloning sites which are moved into a subdirectory. Read more: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
* New: Create an alternative copy method for large files
* New: Add new author WP-Staging to the readme.txt and to the wordpress repository

= 0.9.3 =
* Fix: Rating container is not shown because of wrong wordpress option name
* Tweak: Change color of the rating links

= 0.9.2 =
* Fix: A conflict with the plugin WP Migrate DB (Pro)
* Fix: Limit the staging name to maximum of 16 characters for migration process

= 0.9.1 =
* Fix: Change search and replace function for table wp_options when running migration. This prevented on some sites the moving of serialized theme data

= 0.9 =
* New: Release

== Upgrade Notice ==

= 0.9.4 =
0.9.4 <strong> Important update!</strong>