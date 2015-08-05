=== WP Staging - Site cloning and staging site creation simplified === 

Author URL: https://wordpress.org/plugins/wp-staging
Plugin URL: https://wordpress.org/plugins/wp-staging
Contributors: ReneHermi
Donate link: https://wordpress.org/plugins/wp-staging
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: staging, development, cloning, admin, page, content, plugin, media, backup, test, testing, sandbox
Requires at least: 3.6+
Tested up to: 4.2.4
Stable tag: 0.9.1

WP Staging creates independent staging and development sites that are only available to administrators.

== Description == 

<strong>This software is beta and work in progress! <br>
If you find a bug please open a ticket in the [support request](https://wordpress.org/support/plugin/wp-staging/ "support forum") so i am able to fix it!
</strong>

> #### WP Staging
> This plugin allows you to create an staging or development environment in seconds* <br />
> It creates a clone of your website into a subfolder of your current WordPress installation with an entire copy of your database. 
> This sounds pretty simple and yes it is! All the hard time consumpting database and file copy stuff including url replacements is done in the background.
>
> I created this plugin because all other solutions are way too complex, overloaded with dozens of options or having server requirements which are not available on most shared hosting solutions.
> All these reasons prevent user from testing new plugins and updates first before installing them on their live website, so its time to release a plugin which has the potential to be merged into everyone´s wordpress workflow.
>
><p><small><em>* Time of creation depends on size of your database and file size</em></small></p>
>

WP Staging can prevent your website from being broken or unavailable because of installing untested plugin updates! 

Change your workflow of updating themes and plugins data:

<li> 1. Use WP Staging to create a clone of your website with all your latest production data</li>
<li> 2. Customize theme, configuration and plugins or install new ones</li>
<li> 3. Test everything on your staging site firstz</li>
<li> 4. Everything running as expected? You are on the save side to do all these modifications on your production site!</li>


<h3> Why should i use a staging website? </h3>

Plugin updates and theme customizations should be tested on a staging platform first. Its recommended to have the staging platforme on the same server where the production website is located.
When you run a plugin update or plan to install a new one, it is a necessary task to check first the modifications on a clone of your production website.
This makes sure that any modifications is  working on your website without throwing unexpected errors or preventing your site from loading. (Better known as the wordpress blank page error)

Unfortunately, testing a plugin update before installing it in live environment isn´t done very often by most user because existing staging solutions are too complex and need a lot of time to create a 
up-to-date copy of your website.

Some people are also afraid of installing plugins updates because they follow the rule "never touch a running system" with having in mind that untested updates are increasing the risk of breaking their site.
I totally understand this and i am guilty as well here, but unfortunately this leads to one of the main reasons why WordPress installations are often outdated, not updated at all and unsecure due to this non-update behavior.

<strong> I think its time to change this, so i created "WP Staging" </strong>

<h3> Can´t i just use my local wordpress development copy for testing like xampp / lampp? </h3>

Nope! If your local hardware and software environment is not a 100% exact clone of your production server there is NO guarantee that every aspect 
of your local copy is working on your live website exactely as you would expect it. 
There are some obvious things like differences in the config of php and the server you are running but even such non obvious settings like the amount of ram or the 
the cpu performance can lead to unexpected results on your production website. 
There are dozens of other possible cause of failure which can not be handled well when you are testing your changes on a local staging platform.

This is were WP Staging steps in... Site cloning and staging site creation simplified!

= Main Features =

* Creates a staging website with a few clicks
* Access to the staging site will be granted only for administrators
* Admin bar reflects that you are working on a staging site
* No access to staging site for search engines
* Extensive logging if things goes wrong. (Find them in wp-content/wp-staging/logs)

= What does not work or is not tested? =

* Staging of wordpress multisites (not tested)
* Staging on windows server (not tested but will probably work)

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

1. Create a staging website


== Changelog ==

= 0.9.1 =
* Fix: Change search and replace function for table wp_options. This prevented on some sites the moving of serialized theme data

= 0.9 =
* New: Release

== Upgrade Notice ==

= 0.9 =
0.9 <strong> Initial release</strong>